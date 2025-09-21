<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester extends Ezoic_Feature
{
	const INIT_ENDPOINT			= EZOIC_URL . '/pub/v1/wordpressintegration/v1/initialize?d=';
	const ADS_ENDPOINT			= EZOIC_URL . '/pub/v1/wordpressintegration/v1/publisherads?d=';
	const STATUS_ENDPOINT		= EZOIC_URL . '/pub/v1/wordpressintegration/v1/status?d=';
	const FORCE_GEN_ENDPOINT	= EZOIC_URL . '/pub/v1/wordpressintegration/v1/initialize?force=true&d=';
	const EXCEPTION_ENDPOINT	= EZOIC_API_URL . '/wpservice/send-error';

	private $do_insert = true;
	private $conditional_tags = array();
	public $revenues = array();

	public $config;

	public function __construct()
	{
		// Activate feature if enabled
		// $activated = $this->enable();
		$this->is_public_enabled	= true;
		$this->is_admin_enabled		= true;

		$this->config = Ezoic_AdTester_Config::load();
		
		// Initialize active placements if they're empty
		// This ensures wp_* placeholders are active even without visiting admin page
		if (empty($this->config->active_placements) && !empty($this->config->placeholders)) {
			$this->config->initialize_active_placements();
			$this->update_config();
		}
	}

	/**
	 * Initialize filtered placeholder rules once for all inserters to use
	 * Only called when needed and after WordPress query is available
	 */
	private function initialize_filtered_rules()
	{
		// Return early if already initialized
		if (!empty($this->config->filtered_placeholder_rules)) {
			return;
		}
		// Figure out page type using centralized helper
		$page_type = Ezoic_AdPos::get_current_page_type();

		$rules = array();

		// Only log this once per page load to avoid spam
		static $logged_page_info = false;
		if (!$logged_page_info) {
			$page_type_counts = array();
			foreach ($this->config->placeholder_config as $config) {
				$page_type_counts[$config->page_type] = (isset($page_type_counts[$config->page_type]) ? $page_type_counts[$config->page_type] : 0) + 1;
			}

			$matching_placements = isset($page_type_counts[$page_type]) ? $page_type_counts[$page_type] : 0;

			$placement_selection_enabled = isset($this->config->enable_placement_id_selection) && $this->config->enable_placement_id_selection === true;

			Ezoic_Integration_Logger::console_debug(
				"Ad Inserter running - Page Type: {$page_type}, Active Placements: {$matching_placements}",
				'Ad System',
				'info'
			);
			$logged_page_info = true;
		}

		foreach ($this->config->placeholder_config as $ph_config) {
			if ($ph_config->page_type != $page_type) {
				continue;
			}

			$placeholder = isset($this->config->placeholders[$ph_config->placeholder_id]) ? $this->config->placeholders[$ph_config->placeholder_id] : null;
			if (!$placeholder) {
				Ezoic_Integration_Logger::console_debug(
					"Placement skipped - placeholder not found. Placeholder ID: {$ph_config->placeholder_id}",
					'Ad System'
				);
				continue;
			}

			$position_type = $placeholder->position_type;

			if (Ezoic_AdTester_Inserter::should_include_placeholder($this->config, $placeholder)) {
				// Only include each placeholder_id once to prevent duplicates
				if (!isset($rules[$ph_config->placeholder_id])) {
					$rules[$ph_config->placeholder_id] = $ph_config;
					Ezoic_Integration_Logger::console_debug(
						"Position Type `{$position_type}` included for insertion.",
						'Ad System',
						'info',
						$placeholder->position_id
					);
				}
			}
		}

		// Store the computed rules in the config object for all inserters to use
		$this->config->filtered_placeholder_rules = $rules;
	}

	/**
	 * Initialize conditional tags lazily when needed
	 */
	private function initialize_conditional_tags()
	{
		// Return early if already initialized
		if (!empty($this->conditional_tags)) {
			return;
		}

		$this->conditional_tags['archive'] = 	function () {
			return \is_archive();
		};
		$this->conditional_tags['author'] = 	function () {
			return \is_author();
		};
		$this->conditional_tags['blog'] = 		function () {
			return \is_front_page() && \is_home();
		};
		$this->conditional_tags['category'] = 	function () {
			return \is_category();
		};
		$this->conditional_tags['date'] = 		function () {
			return \is_date();
		};
		$this->conditional_tags['front'] = 		function () {
			return \is_front_page();
		};
		$this->conditional_tags['home'] = 		function () {
			return \is_home();
		};
		$this->conditional_tags['page'] = 		function () {
			return \is_page();
		};
		$this->conditional_tags['post'] = 		function () {
			return \is_single() || \is_archive();
		};
		$this->conditional_tags['search'] = 	function () {
			return \is_search();
		};
		$this->conditional_tags['single'] = 	function () {
			return \is_single();
		};
		$this->conditional_tags['sticky'] = 	function () {
			return \is_sticky();
		};
		$this->conditional_tags['tag'] = 		function () {
			return \is_tag();
		};
		$this->conditional_tags['tax'] = 		function () {
			return \is_tax();
		};
	}

	/**
	 * Get filtered placeholder rules, initializing them if needed
	 */
	public function get_filtered_placeholder_rules()
	{
		$this->initialize_filtered_rules();
		return $this->config->filtered_placeholder_rules;
	}

	/**
	 * Register admin hooks (mostly for placeholder initialization)
	 */
	public function register_admin_hooks($loader)
	{
		$loader->add_action('ez_after_activate', $this, 'initialize');
		$loader->add_action('init', $this, 'schedule_fetch_placeholders');
	}

	/**
	 * Register public hooks (mostly for ad insertion)
	 */
	public function register_public_hooks($loader)
	{
		$loader->add_action('init', $this, 'schedule_fetch_placeholders');
		$loader->add_action('wp', $this, 'initialize_page');
		$loader->add_action('wp_head', $this, 'initialize_inserter');
		$loader->add_action('set_current_user', $this, 'set_no_ads_cookie');
		$loader->add_action('wp_head', $this, 'insert_meta_tags');

		if ($this->use_html_inserter()) {
			$loader->add_action('wp_head', $this, 'output_buffer_start', PHP_INT_MAX);

			// Register multiple priority fallbacks for wp_footer
			$this->register_footer_fallbacks($loader);

			// Also try alternative hooks as final backup
			$loader->add_action('shutdown', $this, 'output_buffer_cleanup', 999);
		}

		$loader->add_filter('the_content', $this, 'set_content_placeholder', PHP_INT_MAX);
		$loader->add_filter('the_excerpt', $this, 'set_excerpt_placeholder');
		$loader->add_action('init', $this, 'set_sidebar_placeholder', 20); // Run after widgets_init (priority 1)
		$loader->add_action('wp_body_open', $this, 'set_before_content_placeholder');
		$loader->add_action('wp_footer', $this, 'set_after_content_placeholder');

		// Output debugging information
		if (EZOIC_DEBUG) {
			// Add debugging information
			$loader->add_action('ez_debug_output', $this, 'add_debugging_info');

			// Download placeholders, if needed (can only be used every 5 minutes)
			if (isset($_GET['ez_wp_fetch_ph']) && $_GET['ez_wp_fetch_ph'] == '1') {
				\add_action('init', function () {
					$last_fetch = $this->config->last_placeholder_fetch;
					if (!isset($last_fetch) || ($last_fetch + 5 * 60) < \time()) {
						$this->config->last_placeholder_fetch = \time();
						if ($this->initialize_config() === false) {
							$this->update_config();
						}
					}
				});
			}
		}
	}

	/**
	 * Fetch placeholder definitions from the backend and populate in the local configuration
	 */
	public function initialize_config()
	{
		// Fetch placeholders from backend
		$publisher_ads = new Ezoic_AdTester_PublisherAds();

		// Create an indexed list of configs
		$existing_configs = array();
		foreach ($this->config->placeholder_config as &$ph_config) {
			$existing_configs[$ph_config->placeholder_id . $ph_config->page_type] = $ph_config;
		}

		// Update placeholders
		foreach ($publisher_ads->ads as $ad) {
			// Skip if empty ID or bottom_floating
			// Skip AdPicker placements unless Select Placement ID is enabled
			if (
				$ad->id == ''
				|| 	$ad->positionType == 'bottom_floating'
				|| 	(isset($ad->isAdPicker) && $ad->isAdPicker && $this->config->enable_placement_id_selection !== true)
			) {
				continue;
			}

			// Filter wp_ placeholders, but include non-wp placeholders if placement ID selection is enabled
			$is_wp_placeholder = \ez_strpos($ad->name, 'wp_') === 0;
			$include_non_wp = $this->config->enable_placement_id_selection === true;

			if (!$is_wp_placeholder && !$include_non_wp) {
				continue;
			}

			$is_new = false;

			if (!isset($this->config->placeholders[$ad->id])) {
				$new_placeholder = Ezoic_AdTester_Placeholder::from_pubad($ad);
				$this->config->placeholders[$ad->id] = $new_placeholder;

				$is_new = true;
			} else if ($this->config->placeholders[$ad->id]->is_video_placeholder != $ad->isVideoPlaceholder) {
				$this->config->placeholders[$ad->id]->is_video_placeholder = $ad->isVideoPlaceholder;
			}

			// Add default configuration for new placeholders or existing wp_ placeholders without config
			foreach ($publisher_ads->default_config as $default_config) {
				if ($default_config['position_type'] == $ad->positionType) {
					// Check if this placeholder already has a configuration for this page type
					$has_existing_config = false;
					foreach ($this->config->placeholder_config as $existing_config) {
						if ($existing_config->placeholder_id == $ad->id && $existing_config->page_type == $default_config['page_type']) {
							$has_existing_config = true;
							break;
						}
					}

					// Add default config if placeholder is new OR it's a wp_ placeholder without existing config
					if ($is_new || (!$has_existing_config && \ez_strpos($ad->name, 'wp_') === 0)) {
						$this->config->placeholder_config[] = new Ezoic_AdTester_Placeholder_Config($default_config['page_type'], $ad->id, $default_config['display'], $default_config['display_option'], true);
					}
				}
			}
		}

		// Get revenue values from publisher ads
		$this->revenues = $publisher_ads->revenues;

		if (isset($publisher_ads->adpos_service) && $publisher_ads->adpos_service != $this->config->enable_adpos_integration) {
			$this->config->enable_adpos_integration = $publisher_ads->adpos_service;
			self::log('APS Integration: ' . ($this->config->enable_adpos_integration ? 'Enabled' : 'Disabled'));
		}

		// Initialize active placements with wp_* defaults if not already set
		$this->config->initialize_active_placements();

		// Fix any invalid active placement IDs using the fresh API data
		$corrections = $this->fix_active_placements_with_api_data($publisher_ads);
		if (!empty($corrections)) {
			foreach ($corrections as $correction) {
				if ($correction['action'] === 'corrected') {
					self::log("Fixed active placement for '{$correction['position_type']}': changed position ID from {$correction['old_position_id']} to {$correction['new_position_id']} (using placeholder '{$correction['new_placeholder_name']}')");
				} elseif ($correction['action'] === 'removed') {
					self::log("Removed invalid active placement for '{$correction['position_type']}': position ID {$correction['old_position_id']} ({$correction['reason']})");
				}
			}
		}

		// Clean up any inactive placeholder configurations
		$this->config->cleanup_all_inactive_placeholder_configs();

		// Store config
		$this->update_config();
	}

	/**
	 * Fix active placements using fresh API data
	 */
	private function fix_active_placements_with_api_data($publisher_ads)
	{
		$corrections = array();
		$valid_position_ids = array();
		$position_type_to_ad = array();

		// Build lookup tables for valid placeholders using API data
		foreach ($publisher_ads->ads as $ad) {
			if ($ad->is_active() == 1) { // Only consider active placeholders
				$valid_position_ids[] = $ad->adPositionId;

				// For each position type, prefer wp_ placeholders as replacements
				$position_type = $ad->positionType;
				if (
					!isset($position_type_to_ad[$position_type]) ||
					strpos($ad->name, 'wp_') === 0
				) {
					$position_type_to_ad[$position_type] = $ad;
				}
			}
		}

		// Check and fix each active placement
		foreach ($this->config->active_placements as $position_type => $position_id) {
			$position_id = intval($position_id);

			// Check if the position ID exists and is active
			if (!in_array($position_id, $valid_position_ids)) {
				// Invalid placement found - try to fix it
				if (isset($position_type_to_ad[$position_type])) {
					// Replace with a valid placeholder for this position type
					$replacement_ad = $position_type_to_ad[$position_type];
					$this->config->active_placements[$position_type] = $replacement_ad->adPositionId;

					$corrections[] = array(
						'action' => 'corrected',
						'position_type' => $position_type,
						'old_position_id' => $position_id,
						'new_position_id' => $replacement_ad->adPositionId,
						'new_placeholder_name' => $replacement_ad->name
					);
				} else {
					// No valid placeholder found for this position type - remove it
					unset($this->config->active_placements[$position_type]);

					$corrections[] = array(
						'action' => 'removed',
						'position_type' => $position_type,
						'old_position_id' => $position_id,
						'reason' => 'no_valid_placeholder_available'
					);
				}
			}
		}

		return $corrections;
	}

	/**
	 * Generate missing default configurations for wp_ placeholders
	 * This method can be called to fix placeholders that are showing as disabled
	 */
	public function generate_missing_wp_configs()
	{
		// Fetch fresh publisher ads data to get default configs
		$publisher_ads = new Ezoic_AdTester_PublisherAds();

		// Only proceed if we have default config data
		if (empty($publisher_ads->default_config)) {
			//self::log('Cannot generate missing configs: No default configuration data available');
			return false;
		}

		$configs_added = 0;

		// Loop through all wp_ placeholders
		foreach ($this->config->placeholders as $placeholder_id => $placeholder) {
			if (\ez_strpos($placeholder->name, 'wp_') === 0) {
				// Check each default config to see if we need to add it
				foreach ($publisher_ads->default_config as $default_config) {
					if ($default_config['position_type'] == $placeholder->position_type) {
						// Check if this placeholder already has a configuration for this page type
						$has_existing_config = false;
						foreach ($this->config->placeholder_config as $existing_config) {
							if ($existing_config->placeholder_id == $placeholder_id && $existing_config->page_type == $default_config['page_type']) {
								$has_existing_config = true;
								break;
							}
						}

						// Add default config if missing
						if (!$has_existing_config) {
							$this->config->placeholder_config[] = new Ezoic_AdTester_Placeholder_Config(
								$default_config['page_type'],
								$placeholder_id,
								$default_config['display'],
								$default_config['display_option'],
								true
							);
							$configs_added++;
						}
					}
				}
			}
		}

		if ($configs_added > 0) {
			$this->update_config();
			return true;
		} else {
			return false;
		}
	}


	/**
	 * retrieves placeholders by domain with optional from/to date range filter
	 */
	public function retrieve_placeholders($dateFrom, $dateTo)
	{

		// Use auth key to send a request to initialize the domain
		$domain = Ezoic_Integration_Request_Utils::get_domain();
		$requestURL = Ezoic_AdTester::ADS_ENDPOINT . $domain;

		$requestOptions = [
			'method'	=> 'GET',
			'timeout'	=> 120,
			'body'		=> array()
		];

		try {
			// Use API Key, if available
			if (Ezoic_Cdn::ezoic_cdn_api_key() != null) {
				$requestURL .= '&developerKey=' . Ezoic_Cdn::ezoic_cdn_api_key();

				//add token since we dont have an api key
			} else {
				$token = Ezoic_Integration_Authentication::get_token();

				$requestOptions['headers'] = [
					'Authentication' => 'Bearer ' . $token
				];
			}

			//add from and to date if available
			if ($dateFrom != false && $dateTo != false) {
				$requestURL .= '&dateFrom=' . $dateFrom;
				$requestURL .= '&dateTo=' . $dateTo;
			}

			//Send the request to backend
			$response = \wp_remote_post($requestURL, $requestOptions);

			// If an error was returned, log it
			if (\is_wp_error($response)) {
				Ezoic_AdTester::log('Unable to retrieve placeholder data, please refresh and try again');
				return new \WP_REST_Response('Unable to retrieve placeholder data, please refresh and try again', 500);
			}

			return new \WP_REST_Response(array(
				'status'        => 200,
				'response'      => 'OK',
				'body_response' => $response['body']
			));
		} catch (\Exception $ex) {
			// Send error to our backend
			$handler = new Ezoic_AdTester_Exception_Handler($ex, array('module' => 'adtester', 'task' => 'force generate'));
			$handler->handle();
		}
	}

	/**
	 * Schedule daily cron to update placeholders
	 */
	public function schedule_fetch_placeholders()
	{
		\add_action('fetch_placeholders', array($this, 'fetch_placeholders'));
		if (!wp_next_scheduled('fetch_placeholders')) {
			wp_schedule_event(time(), 'daily', 'fetch_placeholders');
		}
	}

	/**
	 * fetch placeholders - called via a cron
	 */
	public function fetch_placeholders()
	{
		$this->initialize_config();
	}

	/**
	 * Generated default placeholders, bypassing logic to prevent adding placeholder to existing accounts
	 */
	public function force_generate_placeholders()
	{
		$token = '';
		// Use auth key to send a request to initialize the domain
		$domain = Ezoic_Integration_Request_Utils::get_domain();
		$requestURL = Ezoic_AdTester::FORCE_GEN_ENDPOINT . $domain;

		try {
			// Use API Key, if available
			if (Ezoic_Cdn::ezoic_cdn_api_key() != null) {
				$requestURL .= '&developerKey=' . Ezoic_Cdn::ezoic_cdn_api_key();
			} else {
				$token = Ezoic_Integration_Authentication::get_token();
			}

			// Send request
			$response = \wp_remote_post($requestURL, array(
				'method'		=> 'POST',
				'timeout'	=> 120,
				'headers'	=> array(
					'Authentication' => 'Bearer ' . $token
				),
				'body'		=> array()
			));

			// If an error was returned, log it
			if (\is_wp_error($response)) {
				Ezoic_AdTester::log('Unable to force generation of placeholders, please refresh and try again');
				return;
			}
		} catch (\Exception $ex) {
			// Send error to our backend
			$handler = new Ezoic_AdTester_Exception_Handler($ex, array('module' => 'adtester', 'task' => 'force generate'));
			$handler->handle();
		}
	}

	/**
	 * Initialize HTML Element Picker
	 */
	public function initialize_inserter()
	{
		if ($this->should_skip_insertion()) {
			return;
		}
	}

	/**
	 * Insert sidebar placeholders
	 */
	public function set_sidebar_placeholder()
	{
		if ($this->should_skip_insertion()) {
			return;
		}

		try {
			$inserter = new Ezoic_AdTester_Sidebar_Inserter($this->config);
			$inserter->insert();
		} catch (\Exception $ex) {
			// Send error to our backend
			$handler = new Ezoic_AdTester_Exception_Handler($ex, array('module' => 'adtester', 'task' => 'sidebar insertion'));
			$handler->handle();
		}
	}

	/**
	 * Insert placeholders which require access to full page content
	 */
	private function set_final_content_placeholder($content)
	{
		if ($this->should_skip_insertion()) {
			return $content;
		}

		// Store original content for fallback
		$original_content = $content;

		try {
			// Invoke server-side HTML element inserter
			$inserter = new Ezoic_AdTester_HTML_Inserter($this->config);
			$inserted_content = $inserter->insert_server($content);

			// Return original content if insertion failed or resulted in empty/truncated content
			// Check if content is empty or significantly smaller than original (less than 10%)
			$original_length = strlen($original_content);
			$inserted_length = strlen($inserted_content);

			if (empty($inserted_content) || ($original_length > 1000 && $inserted_length < ($original_length * 0.1))) {
				Ezoic_Integration_Logger::log_error(
					"HTML insertion failed - returned original content. Original length: {$original_length}, Inserted length: {$inserted_length}",
					'HTML Ads'
				);
				return $original_content;
			}

			$content = $inserted_content;
		} catch (\Exception $ex) {
			// Send error to our backend
			$handler = new Ezoic_AdTester_Exception_Handler($ex, array('module' => 'adtester', 'task' => 'final content insertion'));
			$handler->handle();

			// Log and return original content on exception
			Ezoic_Integration_Logger::log_error(
				"HTML insertion exception - returned original content: " . $ex->getMessage(),
				'HTML Ads'
			);
			return $original_content;
		}

		return $content;
	}

	public function can_use_new_inserter()
	{
		if (!function_exists('iconv')) {
			return false;
		}

		return true;
	}

	/**
	 * Inserts before content placeholders
	 */
	public function set_before_content_placeholder()
	{
		if ($this->should_skip_insertion()) {
			return;
		}

		$inserter = new Ezoic_AdTester_Page_Inserter($this->config);

		$inserter->insert('before_content');
	}

	/**
	 * Inserts after content placeholders
	 */
	public function set_after_content_placeholder()
	{
		if ($this->should_skip_insertion()) {
			return;
		}

		$inserter = new Ezoic_AdTester_Page_Inserter($this->config);

		$inserter->insert('after_content');
	}

	/**
	 * Inserts excerpt placeholders
	 */
	private $excerpt_number = 0;
	public function set_excerpt_placeholder($content)
	{
		if ($this->should_skip_insertion()) {
			return $content;
		}

		// Initialize filtered rules now that WordPress query is available
		$this->initialize_filtered_rules();

		// Store original content for fallback
		$original_content = $content;
		$this->excerpt_number++;

		try {
			$inserter = new Ezoic_AdTester_Excerpt_Inserter($this->config, $this->excerpt_number);
			$inserted_content = $inserter->insert($content);

			// Return original content if insertion failed or resulted in empty content
			if (empty($inserted_content)) {
				Ezoic_Integration_Logger::console_debug(
					"Content insertion failed - no content returned",
					'Content Ads',
					'warn'
				);
				return $original_content;
			}

			$content = $inserted_content;
		} catch (\Exception $ex) {
			// Send error to our backend
			$handler = new Ezoic_AdTester_Exception_Handler($ex, array('module' => 'adtester', 'task' => 'excerpt insertion'));
			$handler->handle();

			// Return original content on exception
			return $original_content;
		}

		return $content;
	}

	/**
	 * Inserts in-content placeholders
	 */
	public function set_content_placeholder($content)
	{
		if (!(\in_the_loop() && \is_main_query())) {
			return $content;
		}

		if ($this->should_skip_insertion()) {
			return $content;
		}

		// Initialize filtered rules now that WordPress query is available
		$this->initialize_filtered_rules();

		// Check if content is empty before attempting any insertion
		if (!isset($content) || strlen($content) === 0) {
			return $content;
		}

		// Store original content for fallback
		$original_content = $content;

		// Use new processor if available
		if (\class_exists('DOMDocument') && isset($_GET['ez_wp_new_inserter']) && $_GET['ez_wp_new_inserter'] == '1') {
			try {
				$inserter = new Ezoic_AdTester_Content_Inserter3($this->config);
				$inserted_content = $inserter->insert($content);

				// Return original content if insertion failed or resulted in empty content
				if (empty($inserted_content)) {
					Ezoic_Integration_Logger::console_debug(
						"Content insertion failed - no content returned",
						'Content Ads',
						'warn'
					);
					return $original_content;
				}

				return $inserted_content;
			} catch (\Exception $ex) {
				Ezoic_Integration_Logger::log_error('Error using new insertion engine: ' . $ex, 'AdTester');
				// Continue to fallback inserters below
			}
		}

		// Use legacy inserter
		if (!$this->can_use_new_inserter()) {
			try {
				$inserter = new Ezoic_AdTester_Content_Inserter($this->config);
				$inserted_content = $inserter->insert($content);

				// Return original content if insertion failed or resulted in empty content
				if (empty($inserted_content)) {
					Ezoic_Integration_Logger::console_debug(
						"Content insertion failed - no content returned",
						'Content Ads',
						'warn'
					);
					return $original_content;
				}

				return $inserted_content;
			} catch (\Exception $ex) {
				// Send error to our backend
				$handler = new Ezoic_AdTester_Exception_Handler($ex, array('module' => 'adtester', 'task' => 'content insertion fallback'));
				$handler->handle();
				return $original_content;
			}
		}

		// Attempt to use the new inserter, if it fails, fallback to old inserter
		try {
			// If unicode support isn't loaded, encode the html
			$content = \ez_encode_unicode($content);
			$inserter = new Ezoic_AdTester_Content_Inserter2($this->config);
			if (EZOIC_DEBUG) {
				$commented_content = "<!--[if IE 3 ]>Debugging Pre Insertion Content Start: \n" . print_r($content, true) . "\n<![endif]-->";
				$content = $content . $commented_content;
			}
			$inserted_content = $inserter->insert($content);

			// Return original content if insertion failed or resulted in empty content
			if (empty($inserted_content)) {
				Ezoic_Integration_Logger::console_debug(
					"Content insertion failed - no content returned",
					'Content Ads',
					'warn'
				);
				return $original_content;
			}

			$content = $inserted_content;
		} catch (\Exception $ex) {
			try {
				$inserter = new Ezoic_AdTester_Content_Inserter($this->config);
				$inserted_content = $inserter->insert($original_content);

				// Return original content if insertion failed or resulted in empty content
				if (empty($inserted_content)) {
					Ezoic_Integration_Logger::console_debug(
						"Content insertion failed - no content returned",
						'Content Ads',
						'warn'
					);
					return $original_content;
				}

				$content = $inserted_content;
			} catch (\Exception $fallback_ex) {
				// Both inserters failed, return original content
				$handler = new Ezoic_AdTester_Exception_Handler($fallback_ex, array('module' => 'adtester', 'task' => 'content insertion final fallback'));
				$handler->handle();
				return $original_content;
			}

			// Send error to our backend
			$handler = new Ezoic_AdTester_Exception_Handler($ex, array('module' => 'adtester', 'task' => 'content insertion'));
			$handler->handle();
		}

		return $content;
	}

	/**
	 * Inserts code necessary for the HTML Element Picker
	 */
	public function initialize_page()
	{
		if (isset($_GET['ez_wp_config_sync']) && $_GET['ez_wp_config_sync'] === '1') {
			$this->config->sync();
		}

		// If ad placement service integration is enabled, do not insert ads
		if ($this->config->enable_adpos_integration) {
			$this->do_insert = false;
		}

		if (isset($_POST['ez_wp_select_element'])) {
			$this->do_insert = false;

			//self::log('element select mode');

			// Remove admin bar
			\add_filter('show_admin_bar', '__return_false');

			// Register script/css to handle element selection
			wp_enqueue_script('ezoic_integration', EZOIC__PLUGIN_URL . 'admin/js/ad-select-elements.js', array(), EZOIC_INTEGRATION_VERSION);
			wp_enqueue_style('ezoic_integration', EZOIC__PLUGIN_URL . 'admin/css/ad-select-elements.css');
		} else {
			if (isset($this->config->exclude_urls) && count($this->config->exclude_urls) > 0 && function_exists('preg_match')) {
				$current_url = $_SERVER['REQUEST_URI'];
				foreach ($this->config->exclude_urls as $excl) {
					if ($this->is_url_match($current_url, $excl)) {
						$this->do_insert = false;
						break;
					}
				}
			}
		}
	}

	private function is_url_match($url, $test)
	{
		// Empty test string
		if ($test == '') {
			return false;
		}

		// Regex
		$excl_escaped = '#' . \str_replace('?', '\\?', $test) . '$#';

		return \preg_match($excl_escaped, $url);
	}

	/**
	 * Note the initial activation of the plugin
	 */
	public function initialize()
	{
		// Initialize default placeholders
		$init = new Ezoic_AdTester_Init();
		$init->initialize($this);

		// Flush cache, if the key is present
		if (!empty(Ezoic_Cdn::ezoic_cdn_api_key())) {
			$cdn = new Ezoic_Cdn();
			$cdn->ezoic_cdn_purge($cdn->ezoic_cdn_get_domain());
		}

		$initialized_set = \get_option('ez_ad_initialized');
		if (!$initialized_set) {
			\add_option('ez_ad_initialized', \time());
		}
	}

	/**
	 * Begin capturing body output
	 */
	public function output_buffer_start()
	{
		ob_start();
	}

	/**
	 * Complete capturing HTML body and process
	 */
	public function output_buffer_end()
	{
		$content = ob_get_clean();

		$content = $this->set_final_content_placeholder($content);

		echo $content;
	}

	/**
	 * Register multiple fallback hooks with different priorities to ensure HTML insertion works
	 */
	private function register_footer_fallbacks($loader)
	{
		// Use a single priority first
		$loader->add_action('wp_footer', $this, 'robust_output_buffer_end', 5);

		// Emergency fallbacks only for extreme edge cases
		$loader->add_action('shutdown', $this, 'output_buffer_cleanup', 999);
		$loader->add_action('wp_print_footer_scripts', $this, 'robust_output_buffer_end', 999);
	}

	private $buffer_processed = false;

	/**
	 * Robust output buffer end - prevents multiple processing
	 */
	public function robust_output_buffer_end()
	{
		// Prevent multiple processing
		if ($this->buffer_processed) {
			return;
		}

		// Check if there's actually an active buffer
		if (ob_get_level() === 0) {
			return;
		}

		$this->buffer_processed = true;

		$content = ob_get_clean();

		$content = $this->set_final_content_placeholder($content);
		echo $content;
	}


	/**
	 * Ultimate cleanup method - final safety net
	 */
	public function output_buffer_cleanup()
	{
		// If we already processed the buffer, don't do it again
		if ($this->buffer_processed) {
			return;
		}

		// Check if there's still an active output buffer
		if (ob_get_level() > 0) {
			$this->buffer_processed = true;
			$content = ob_get_clean();

			Ezoic_Integration_Logger::console_debug(
				"HTML insertion via shutdown hook - wp_footer priorities failed, using fallback.",
				'HTML Ads',
				'warn'
			);

			$content = $this->set_final_content_placeholder($content);
			echo $content;
		}
	}


	/**
	 * Determine if the feature is enabled
	 */
	private function enable()
	{
		$value = \get_option('ez_ad_integration_enabled', 'false');

		// If feature header is present, set option accordingly
		if (isset($_SERVER['HTTP_X_EZOIC_WP_ADS'])) {
			$value = $_SERVER['HTTP_X_EZOIC_WP_ADS'];

			\update_option('ez_ad_integration_enabled', $value);
		}

		// Enable feature if needed
		$this->is_public_enabled	= $value == 'true';
		$this->is_admin_enabled		= $value == 'true';
	}

	/**
	 * Sets a cookie for the current user used to convey that no ads should be shown.
	 * Cookie is deleted if no user is logged in or if the current user is not a member
	 * of a user role that has ads disabled for them.
	 */
	public function set_no_ads_cookie()
	{
		$cookieName = 'x-ez-wp-noads';

		// If a user has ads disabled, set the cookie.
		if ($this->user_has_ads_disabled()) {
			// If the cookie doesn't exist create the cookie
			// 0 means a cookie expires at the end of the session (when the browser closes)
			if (!isset($_COOKIE[$cookieName])) {
				setcookie($cookieName, '1',  0);
			}
		} else {
			// If the cookie exists delete the cookie by setting the expire date-time to 1 in UNIX time
			if (isset($_COOKIE[$cookieName])) {
				setcookie($cookieName, '0',  1);
			}
		}
	}

	/**
	 * Returns if the current user is a member of a role that has ads disabled
	 *
	 * @return bool
	 */
	public function user_has_ads_disabled()
	{
		if (!is_user_logged_in()) {
			return false;
		}

		if (
			!isset($this->config->user_roles_with_ads_disabled)
			|| 	!isset(wp_get_current_user()->roles)
		) {
			return false;
		}

		// Make sure we compare equivalent role names
		$currentUserRoles = array_map('strtolower', wp_get_current_user()->roles);
		$userRolesWithAdsDisabled = array_map('strtolower', $this->config->user_roles_with_ads_disabled);

		// array_diff() returns the values in the first array that are not present in the second array,
		// so if array_diff() returns a shorter array then $currentUserRoles there's a match in the arrays.
		$diff = array_diff($currentUserRoles, $userRolesWithAdsDisabled);

		return \count($currentUserRoles) != \count($diff);
	}

	/**
	 * Inserts meta tags into the document header. Designed to be called with
	 * the wp_head action hook.
	 */
	public function insert_meta_tags()
	{
		// Add meta tags for user role
		if (is_user_logged_in()) {
			$user = wp_get_current_user();
			$roles = $user->roles;

			echo '<meta name="ez-user-role" content="' . implode(',', $roles) . '">';
		}


		if (!isset($this->config->meta_tags)) {
			return;
		}

		// Get current URL properties

		// Given example website "https://www.superawesomeblog.com/blog/2022/01/01/bobbys-birthday"
		$http = ''; // https://
		$host = ''; // www.superawesomeblog.com
		$uri = ''; // /blog/2022/01/01/bobbys-birthday

		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
			$http = 'https://';
		} else {
			$http = 'http://';
		}

		if (isset($_SERVER['HTTP_HOST'])) {
			$host = $_SERVER['HTTP_HOST'];
		}

		if (isset($_SERVER['REQUEST_URI'])) {
			$uri = $_SERVER['REQUEST_URI'];
		}

		// Get current categories
		$categories = get_the_category();

		// Insert meta tags
		foreach ($this->config->meta_tags as $tag) {
			if (!isset($tag->insertionText)) {
				continue;
			}

			if (isset($tag->displayOption) && $tag->displayOption == 'all') {
				echo $tag->insertionText;
				continue;
			}

			if (isset($tag->pageUrls)) {
				foreach ($tag->pageUrls as $url) {
					$trimmed_url = trim($url, '/');
					if ($trimmed_url == trim($uri, '/')) {
						echo $tag->insertionText;
						continue 2;
					}
					if ($trimmed_url == trim($host . $uri, '/')) {
						echo $tag->insertionText;
						continue 2;
					}
					if ($trimmed_url == trim($http . $host . $uri, '/')) {
						echo $tag->insertionText;
						continue 2;
					}
				}
			}

			if (isset($tag->pageTypes)) {
				foreach ($tag->pageTypes as $pageType) {
					$this->initialize_conditional_tags();
					if (isset($pageType->value) && $this->conditional_tags[$pageType->value]() == true) {
						echo $tag->insertionText;
						continue 2;
					}
				}
			}

			if (isset($tag->postCategories)) {
				foreach ($categories as $cat) {
					foreach ($tag->postCategories as $post_cat) {
						if ((isset($cat->name) && $cat->name == $post_cat)
							|| 	(isset($cat->slug) && $cat->slug == strtolower($post_cat))
						) {
							echo $tag->insertionText;
							continue 3;
						}
					}
				}
			}
		}
	}

	/**
	 * Determine if any current placeholderse require html insertion
	 */
	private function use_html_inserter()
	{
		foreach ($this->config->placeholder_config as $ph_config) {
			if ($ph_config->display == 'before_element' || $ph_config->display == 'after_element') {
				return true;
			}
		}

		return false;
	}

	public function update_config()
	{
		//self::log( 'updating configuration ' );

		Ezoic_AdTester_Config::store($this->config);
	}

	/**
	 * Outputs general debugging information at the bottom of the page
	 */
	public function add_debugging_info($content)
	{
		global $wp_version;

		$info = PHP_EOL . PHP_EOL . '<!--[if IE 3 ]>' . PHP_EOL;
		$info .= 'AdTester Debugging Info:' . PHP_EOL;

		try {
			$theme = \wp_get_theme();

			$info .= 'WordPress Version: ' . $wp_version . PHP_EOL;
			$info .= 'Ez Plugin Version: ' . EZOIC_INTEGRATION_VERSION . PHP_EOL;

			$mbstring_status = extension_loaded('mbstring');
			$info .= 'Multibyte String Support: ' . $mbstring_status . PHP_EOL;

			if (!is_null($theme)) {
				$info .= 'Theme: ' . $theme->Name . ' ' . $theme->Version . PHP_EOL;
			}

			$info .= PHP_EOL;

			$info .= 'Placeholders: ' . \count($this->config->placeholders) . PHP_EOL;

			// Add active placements info
			if (isset($this->config->active_placements)) {
				if (is_object($this->config->active_placements)) {
					// If it's an object (like the JSON structure you showed)
					$active_placements_array = (array) $this->config->active_placements;
					$info .= 'Active Placements: ' . \count($active_placements_array) . PHP_EOL;
					if (!empty($active_placements_array)) {
						$info .= '  Placements: ' . PHP_EOL;
						foreach ($active_placements_array as $placement_name => $placement_id) {
							$info .= '    ' . $placement_name . ': ' . $placement_id . PHP_EOL;
						}
					}
				} elseif (is_array($this->config->active_placements)) {
					// If it's an array of objects
					$info .= 'Active Placements: ' . \count($this->config->active_placements) . PHP_EOL;
					if (!empty($this->config->active_placements)) {
						$placement_names = array();
						foreach ($this->config->active_placements as $placement) {
							if (isset($placement->id) && isset($placement->name)) {
								$placement_names[] = $placement->name . ': ' . $placement->id;
							} elseif (isset($placement->id)) {
								$placement_names[] = 'ID: ' . $placement->id;
							}
						}
						if (!empty($placement_names)) {
							$info .= '  Placements: ' . PHP_EOL;
							foreach ($placement_names as $placement_info) {
								$info .= '    ' . $placement_info . PHP_EOL;
							}
						}
					}
				}
			}

			$info .= PHP_EOL;

			$info .= 'Page Skipped: ' . !$this->do_insert . PHP_EOL;
			$info .= 'Is page: ' . \is_page() . PHP_EOL;
			$info .= 'Is single (post): ' . \is_single() . PHP_EOL;
			$info .= 'Is singular: ' . \is_singular() . PHP_EOL;
			$info .= 'Is front page: ' . \is_front_page() . PHP_EOL;
			$info .= 'Is category: ' . \is_category() . PHP_EOL;
			$info .= 'Is archive: ' . \is_archive() . PHP_EOL;

			$taxonomy = \get_queried_object();
			if (!is_null($taxonomy) && !is_null($taxonomy->post_type)) {
				$info .= 'Taxonomy: ' . $taxonomy->post_type . PHP_EOL;
			}

			$sidebars = \get_option('sidebars_widgets');
			if (!empty($sidebars)) {
				$info .= 'Sidebars: ' . \implode(', ', \array_keys($sidebars));
			}

			$info .= PHP_EOL . PHP_EOL;

			// Only display full config if this flag is specified
			if (isset($_GET['ez_wp_config']) && $_GET['ez_wp_config'] == '1') {
				$info .= 'Config:' . PHP_EOL;
				$info .= print_r($this->config, true);
			}

			$info .= PHP_EOL . PHP_EOL;
		} catch (\Exception $exp) {
			$info = 'Error fetching debug data: ' . $exp;
		}

		$info .= '<![endif]-->';

		$info .= PHP_EOL . PHP_EOL;

		echo $info;
	}

	private function should_skip_insertion()
	{
		// Skip insertion if JS integration is enabled but WP placeholders are disabled
		$js_integration_enabled = get_option('ezoic_js_integration_enabled', false);
		$is_preview_mode = Ezoic_Integration::is_js_preview_mode();

		if ($js_integration_enabled || $is_preview_mode) {
			if ($is_preview_mode) {
				// In preview mode, always use WP placeholders
				$use_wp_placeholders = true;
			} else {
				$js_options = get_option('ezoic_js_integration_options', array());
				$use_wp_placeholders = isset($js_options['js_use_wp_placeholders']) && $js_options['js_use_wp_placeholders'];
			}

			if (!$use_wp_placeholders) {
				return true; // Skip WordPress ad insertion when JS integration is enabled but WP placeholders are disabled
			}
		}

		return !$this->do_insert
			|| !isset($this->config->placeholders)
			|| !is_array($this->config->placeholders)
			|| empty($this->config->placeholders)
			|| $this->user_has_ads_disabled();
	}

	public static function log($str)
	{
		Ezoic_Integration_Logger::log($str, 'AdTester');
	}
}
