<?php

namespace Ezoic_Namespace;

/**
 * The settings coordinator of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ezoic-integration-compatibility-check.php';
include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ezoic-integration-cache-integrator.php';
include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ezoic-integration-cache.php';
include_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ezoic-js-integration-settings.php';
include_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ezoic-settings-helpers.php';
include_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ezoic-integration-form-handler.php';
include_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ezoic-integration-renderer.php';

/**
 * Class Ezoic_Integration_Admin_Settings
 * @package Ezoic_Namespace
 */
class Ezoic_Integration_Admin_Settings
{

	private $cache;
	private $ad_settings;
	private $js_integration_settings;
	private $helpers;
	private $form_handler;
	private $renderer;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		// Store parameters for potential future use
		$this->cache            = new Ezoic_Integration_Cache;
		$this->ad_settings      = new Ezoic_Integration_Ad_Settings();
		$this->js_integration_settings = new Ezoic_JS_Integration_Settings();

		// Initialize new components
		$this->helpers = new Ezoic_Settings_Helpers();
		$this->form_handler = new Ezoic_Integration_Form_Handler($this->js_integration_settings, $this->cache, $this->helpers);
		$this->renderer = new Ezoic_Integration_Renderer($this->js_integration_settings, $this->ad_settings, $this->helpers);
	}

	/**
	 * This function introduces the theme options into the 'Appearance' menu and into a top-level menu.
	 */
	public function setup_plugin_options_menu()
	{

		$options = \get_option('ezoic_integration_status');

		// Check for incompatible plugins with Ezoic
		$compatibility_issues = $this->helpers->get_compatibility_issues();

		$badge_count = count($compatibility_issues['incompatible_plugins']);
		if (function_exists('is_wpe') && \is_wpe()) {
			if (isset($options['integration_type']) && $options['integration_type'] == "wp") {
				$badge_count++;
			}
		}

		// Add ads.txt setup to badge count if required (use cached status to avoid performance impact)
		if (!empty($options['integration_type']) && !Ezoic_Integration_Admin::is_cloud_integrated() && Ezoic_AdsTxtManager::ezoic_should_show_adstxtmanager_setting()) {
			$atm_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(false); // Use cached status
			if (!(isset($atm_status['status']) && $atm_status['status'] === true)) {
				$badge_count++;
			}
		}

		// Add duplicate script detection to badge count if JS integration is enabled and scripts are auto-inserted
		// Uses cached results to avoid unnecessary HTTP requests
		if (get_option('ezoic_js_integration_enabled', false)) {
			$js_options = get_option('ezoic_js_integration_options', array());
			$auto_insert_enabled = isset($js_options['js_auto_insert_scripts']) ? $js_options['js_auto_insert_scripts'] : 1;
			$privacy_enabled = isset($js_options['js_enable_privacy_scripts']) ? $js_options['js_enable_privacy_scripts'] : 1;
			
			$duplicate_scripts = $this->js_integration_settings->get_all_duplicate_scripts();
			
			// Check for SA script duplicates if auto-insert is enabled
			if ($auto_insert_enabled && $duplicate_scripts['sa']) {
				$badge_count++;
			}
			
			// Check for privacy script duplicates if privacy scripts are enabled
			if ($privacy_enabled && $duplicate_scripts['privacy']) {
				$badge_count++;
			}
		}

		$incompatible_count   = '';
		if ($badge_count > 0) {
			$incompatible_count = ' <span class="awaiting-mod">' . $badge_count . '</span>';
		}

		// Add the menu to the Plugins set of menu items
		add_options_page(
			EZOIC__PLUGIN_NAME,
			EZOIC__PLUGIN_NAME . $incompatible_count,
			'manage_options',
			EZOIC__PLUGIN_SLUG,
			array(
				$this,
				'render_settings_page_content',
			)
		);
	}

	/**
	 * Renders a settings page
	 *
	 * @param string $active_tab
	 */
	public function render_settings_page_content($active_tab = '')
	{
		// Determine active tab from URL parameters (ignore passed parameter since WordPress doesn't pass any)
		$active_tab = $this->helpers->determine_active_tab();

		// Prepare page context (warnings, status, etc.)
		$context = $this->helpers->prepare_page_context();

		// Handle form submissions and get any messages
		$messages = $this->form_handler->handle_form_submissions();

		// Render the page
		$this->renderer->render_settings_page($active_tab, $context, $messages);
	}

	/**
	 * Provides default values for the Display Options.
	 *
	 * @return array
	 */
	public function default_display_options()
	{
		return $this->helpers->get_default_display_options();
	}

	/**
	 * Provide default values for the Social Options.
	 *
	 * @return array
	 */
	public function default_advanced_options()
	{
		return $this->helpers->get_default_advanced_options();
	}

	/**
	 * Initializes options page by registering the Sections, Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_display_options()
	{

		$options = \get_option('ezoic_integration_status');

		// If the plugin options don't exist, create them
		$default_array = $this->default_display_options();

		if (false == $options) {
			add_option('ezoic_integration_status', $default_array);
		} else {
			$array_diff = array_diff_key($options, $default_array);
			if (! empty($array_diff)) {
				$options = array_merge($default_array, $options);
				\update_option('ezoic_integration_status', $options);
			}
		}
		$options = \get_option('ezoic_integration_status');

		// enable WP integration
		if (isset($_GET['wp_integration']) && $_GET['wp_integration']) {
			$integration_options                           = \get_option('ezoic_integration_options');
			$integration_options['disable_wp_integration'] = 0;
			\update_option('ezoic_integration_options', $integration_options);
			// clear to recheck integration
			$options['check_time'] = '';
		}

		// Check/update integration type
		Ezoic_Integration_Admin::is_cloud_integrated();

		$time_check = time() - 21600; // 6 hours
		if (!isset($options['is_integrated']) || $options['check_time'] <= $time_check || (isset($_GET['recheck']) && $_GET['recheck'])) {

			$results = $this->renderer->get_integration_check_ezoic_response();

			$update                     = array();
			$update['is_integrated']    = $results['result'];

			if ($results['result'] == true) {
				$update['integration_type'] = $results['integration'];
			}

			$update['check_time']       = time();
			update_option('ezoic_integration_status', $update);
		}

		// Re-get options data
		$options    = \get_option('ezoic_integration_status');

		add_settings_section(
			'general_settings_section',
			__('Integration Status', 'ezoic'),
			array($this->renderer, 'general_options_callback'),
			'ezoic_integration_status'
		);

		add_settings_field(
			'is_integrated',
			__('Ezoic Integration', 'ezoic'),
			array($this->renderer, 'is_integrated_callback'),
			'ezoic_integration_status',
			'general_settings_section',
			array()
		);

		if (!empty($options['integration_type']) && !Ezoic_Integration_Admin::is_cloud_integrated()) {
			add_settings_field(
				'adstxt_manager_status',
				__('Ads.txt Setup', 'ezoic'),
				array($this->renderer, 'adstxt_manager_status_callback'),
				'ezoic_integration_status',
				'general_settings_section'
			);
		}

		// Detect and display any incompatible or potentially incompatible plugins
		$compatibility_issues = $this->helpers->get_compatibility_issues();

		if ($compatibility_issues['has_issues']) {
			add_settings_field(
				'plugin_compatibility',
				__('Compatibility Warning', 'ezoic'),
				array($this->renderer, 'plugin_compatibility_callback'),
				'ezoic_integration_status',
				'general_settings_section',
				array($compatibility_issues['incompatible_plugins'], $compatibility_issues['compatible_plugins'])
			);
		}

		add_settings_field(
			'check_time',
			__('Last Checked', 'ezoic'),
			array($this->renderer, 'check_time_callback'),
			'ezoic_integration_status',
			'general_settings_section',
			array()
		);

		register_setting(
			'ezoic_integration_status',
			'ezoic_integration_status'
		);
	}

	/**
	 * Handle integration option updates
	 *
	 * @param array $old_value
	 * @param array $new_value
	 */
	public function handle_update_ezoic_integration_options($old_value, $new_value)
	{
		$this->form_handler->handle_update_ezoic_integration_options($old_value, $new_value);
	}

	/**
	 * Handle cloud integrated caching
	 *
	 * @param object $plugin_admin
	 */
	public function handle_cloud_integrated_with_caching($plugin_admin)
	{
		$this->form_handler->handle_cloud_integrated_with_caching($plugin_admin);
	}

	/**
	 * Handle cache clearing
	 */
	public function handle_clear_cache()
	{
		$this->form_handler->handle_clear_cache();
	}

	/**
	 * Initializes the advanced options by registering the Sections, Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_advanced_options()
	{
		if (false == \get_option('ezoic_integration_options')) {
			$default_array = $this->default_advanced_options();
			update_option('ezoic_integration_options', $default_array);
		}

		add_settings_section(
			'advanced_settings_section',
			__('Advanced Settings', 'ezoic'),
			array($this->renderer, 'advanced_options_callback'),
			'ezoic_integration_settings'
		);

		add_settings_field(
			'disable_wp_integration',
			'Disable WP Integration',
			array($this->renderer, 'disable_wp_integration_callback'),
			'ezoic_integration_settings',
			'advanced_settings_section',
			array(
				__('When not on Ezoic Cloud integration, this will disable automatic default WordPress integration.', 'ezoic'),
			)
		);

		add_settings_field(
			'verify_ssl',
			'Verify SSL',
			array($this->renderer, 'verify_ssl_callback'),
			'ezoic_integration_settings',
			'advanced_settings_section',
			array(
				__('Turns off SSL verification. Recommended to Yes. Only disable if experiencing SSL errors.', 'ezoic'),
			)
		);

		register_setting(
			'ezoic_integration_options',
			'ezoic_integration_options',
			array('default' => $this->default_advanced_options(), 'type' => 'array', 'sanitize_callback' => array($this->form_handler, 'sanitize_advanced_options'))
		);
	}

	/**
	 * Delegate JavaScript integration initialization to separate class
	 */
	public function initialize_js_integration_settings()
	{
		$this->js_integration_settings->initialize_js_integration_settings();
	}

	/**
	 * Handle enabling JavaScript integration
	 */
	public function handle_enable_js_integration()
	{
		$this->form_handler->handle_enable_js_integration();
	}

	/**
	 * Handle disabling JavaScript integration
	 */
	public function handle_disable_js_integration()
	{
		$this->form_handler->handle_disable_js_integration();
	}

	/**
	 * Sanitize JS integration options
	 */
	public function sanitize_js_integration_options($settings)
	{
		return $this->form_handler->sanitize_js_integration_options($settings);
	}
}
