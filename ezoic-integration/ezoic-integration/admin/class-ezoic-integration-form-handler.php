<?php

namespace Ezoic_Namespace;

/**
 * Form handling and POST request processing for Ezoic Integration plugin
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
class Ezoic_Integration_Form_Handler
{
	/**
	 * JS Integration settings instance
	 *
	 * @var Ezoic_JS_Integration_Settings
	 */
	private $js_integration_settings;

	/**
	 * Cache instance for handling clear cache operations
	 *
	 * @var Ezoic_Integration_Cache
	 */
	private $cache;

	/**
	 * Settings helpers instance
	 *
	 * @var Ezoic_Settings_Helpers
	 */
	private $helpers;

	/**
	 * Initialize the form handler with dependencies
	 */
	public function __construct($js_integration_settings = null, $cache = null, $helpers = null)
	{
		$this->js_integration_settings = $js_integration_settings ?: new Ezoic_JS_Integration_Settings();
		$this->cache = $cache ?: new Ezoic_Integration_Cache();
		$this->helpers = $helpers ?: new Ezoic_Settings_Helpers();
	}

	/**
	 * Handle all POST form submissions
	 */
	public function handle_form_submissions()
	{
		if (empty($_POST)) {
			return array();
		}

		$messages = array();

		if ($_POST['action'] == 'clear_cache') {
			$this->handle_clear_cache();
			$messages[] = array(
				'type' => 'success',
				'message' => __('Cache successfully cleared!', 'ezoic')
			);
		} elseif ($_POST['action'] == 'enable_js_integration') {
			$this->handle_enable_js_integration();
		}

		return $messages;
	}

	/**
	 * Handle enabling JavaScript integration
	 */
	public function handle_enable_js_integration()
	{
		if (isset($_POST['action']) && $_POST['action'] === 'enable_js_integration') {
			if (!wp_verify_nonce($_POST['js_integration_nonce'], 'enable_js_integration_nonce')) {
				wp_die('Security check failed');
			}

			// Enable JavaScript integration
			update_option('ezoic_js_integration_enabled', true);

			// Clear duplicate script detection cache when JS integration is enabled
			delete_transient('ezoic_duplicate_scripts_check');

			// Send plugin data to notify backend of integration change
			Ezoic_Integration_Plugin_Data_Service::schedule_plugin_data_send();

			// Disable WordPress integration to prevent conflicts
			$ezoic_options = \get_option('ezoic_integration_options');
			if (!$ezoic_options) {
				$ezoic_options = $this->helpers->get_default_advanced_options();
			}
			$ezoic_options['disable_wp_integration'] = true;
			update_option('ezoic_integration_options', $ezoic_options);

			// Set default JS integration options if they don't exist
			if (false === get_option('ezoic_js_integration_options')) {
				update_option('ezoic_js_integration_options', $this->js_integration_settings->default_js_integration_options());
			}

			// Always auto-enable ads.txt detection when JS integration is enabled
			update_option('ezoic_adstxtmanager_auto_detect', 'on');

			// Force generate WP placeholders for JavaScript integration to ensure they're available in Ezoic backend
			try {
				$adtester = new Ezoic_AdTester();
				$adtester->force_generate_placeholders();
				
				// Initialize config to ensure active placements are set up
				// This fetches placeholders from API and sets up active_placements
				$adtester->initialize_config();
			} catch (\Exception $e) {
				Ezoic_Integration_Logger::log_exception($e, 'JS Integration Form Handler');
			}

			// Trigger ads.txt detection and setup immediately
			$detection_result = Ezoic_AdsTxtManager::ezoic_detect_adstxtmanager_id();

			// Always run setup when enabling JS integration to ensure proper configuration
			$atm_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true);
			if ($atm_id > 0) {
				// Run setup to ensure ads.txt redirect is properly configured
				$solutionFactory = new Ezoic_AdsTxtManager_Solution_Factory();
				$adsTxtSolution = $solutionFactory->GetBestSolution();
				$adsTxtSolution->SetupSolution();

				// Verify and update status after setup
				$redirect_result = Ezoic_AdsTxtManager::ezoic_verify_adstxt_redirect();
				update_option('ezoic_adstxtmanager_status', $redirect_result);
			}

			// Trigger integration recheck by clearing the check time
			$options = \get_option('ezoic_integration_status');
			$options['check_time'] = '';
			update_option('ezoic_integration_status', $options);

			// Redirect based on where it was enabled from
			if (isset($_POST['from_integration_tab']) || isset($_POST['redirect_to_integration_tab'])) {
				wp_redirect(admin_url('admin.php?page=' . EZOIC__PLUGIN_SLUG . '&tab=js_integration&js_integration_enabled=1'));
			} else {
				wp_redirect(admin_url('admin.php?page=' . EZOIC__PLUGIN_SLUG . '&js_integration_enabled=1'));
			}
			exit;
		}
	}

	/**
	 * Handle disabling JavaScript integration
	 */
	public function handle_disable_js_integration()
	{
		if (isset($_POST['action']) && $_POST['action'] === 'disable_js_integration') {
			if (!wp_verify_nonce($_POST['js_integration_disable_nonce'], 'disable_js_integration_nonce')) {
				wp_die('Security check failed');
			}

			// Disable JavaScript integration
			update_option('ezoic_js_integration_enabled', false);

			// Send plugin data to notify backend of integration change
			Ezoic_Integration_Plugin_Data_Service::schedule_plugin_data_send();

			// Trigger integration recheck by clearing the check time
			$options = \get_option('ezoic_integration_status');
			$options['check_time'] = '';
			update_option('ezoic_integration_status', $options);

			// Redirect to Integration tab
			wp_redirect(admin_url('admin.php?page=' . EZOIC__PLUGIN_SLUG . '&tab=js_integration&js_integration_disabled=1'));
			exit;
		}
	}

	/**
	 * Clear the cache when ezoic caching is enabled
	 */
	public function handle_clear_cache()
	{
		if (defined('EZOIC_CACHE') && EZOIC_CACHE) {
			$this->cache->Clear();
		}
	}

	/**
	 * Handle updates to ezoic integration options
	 *
	 * @param array $old_value Old options value
	 * @param array $new_value New options value
	 */
	public function handle_update_ezoic_integration_options($old_value, $new_value)
	{
		// Ensure we have arrays and set defaults for missing keys
		if (!is_array($old_value)) {
			$old_value = array();
		}
		if (!is_array($new_value)) {
			$new_value = array();
		}

		// Set defaults for caching if not present
		$old_caching = isset($old_value['caching']) ? $old_value['caching'] : false;
		$new_caching = isset($new_value['caching']) ? $new_value['caching'] : false;

		// Flush the cache for the site
		if ($old_value !== $new_value) {
			if (Ezoic_Cdn::ezoic_cdn_is_enabled()) {
				$cdn = new Ezoic_Cdn();
				$cdn->ezoic_cdn_purge($cdn->ezoic_cdn_get_domain());
			}
		}

		// Return if the caching value has not changed. This occurs when
		// another setting is updated and caching is left alone.
		if ($old_caching == $new_caching) {
			return;
		}

		// Clear the cache just in case there are old files in it.
		$this->cache->clear();

		$cache_integrator = new Ezoic_Integration_Cache_Integrator();

		// Remove the WP_CACHE define from wp-config.php.
		if ($cache_integrator->clean_wp_config() === false) {
			$this->handle_caching_update_error($new_value, 'Unable to clean the wp-config.php file. Please make sure the file exists and has write-able permissions.');
			return;
		}

		// Remove the advanced cache file.
		if ($cache_integrator->remove_advanced_cache() === false) {
			$this->handle_caching_update_error($new_value, 'Unable to remove the advanced-cache.php file. Please make sure the file exists and has write-able permissions.');
			return;
		}

		// Only perform these steps if caching was just turned on.
		if ($new_caching == '1') {

			// Define WP_CACHE in wp-config.php.
			if ($cache_integrator->configure_wp_config() === false) {
				$this->handle_caching_update_error($new_value, 'Unable to update the wp-config.php file. Please make sure the file exists and has write-able permissions.');
				return;
			}

			// Insert the advanced cache file.
			if ($cache_integrator->insert_advanced_cache() === false) {
				$this->handle_caching_update_error($new_value, 'Unable to insert the advanced-cache.php file. Please make sure the /wp-content directory has write-able permissions.');
				return;
			}
		}
	}

	/**
	 * Handle caching update errors
	 *
	 * @param array $options Options array
	 * @param string $message Error message
	 */
	public function handle_caching_update_error($options, $message)
	{
		// Handle errors while trying to turn on caching.
		add_settings_error('caching', 'caching-error', "Error while configuring Ezoic Caching: $message");
		$options['caching'] = '0';
		\update_option('ezoic_integration_options', $options);
	}

	/**
	 * Handle cloud integrated sites with caching enabled
	 *
	 * @param object $plugin_admin Plugin admin instance
	 */
	public function handle_cloud_integrated_with_caching($plugin_admin)
	{
		if (!is_admin() || !$plugin_admin->is_cloud_integrated()) {
			return;
		}

		$old_options = \get_option('ezoic_integration_options');
		if (!isset($old_options['caching']) || $old_options['caching'] == 0) {
			return;
		}

		$new_options = $old_options;
		$new_options['caching'] = 0;
		\update_option('ezoic_integration_options', $new_options);
		$this->handle_update_ezoic_integration_options($old_options, $new_options);
	}

	/**
	 * Sanitize advanced options
	 *
	 * @param array $settings Settings to sanitize
	 * @return array Sanitized settings
	 */
	public function sanitize_advanced_options($settings)
	{
		$old_options = get_option('ezoic_integration_options');

		if ($settings['disable_wp_integration'] != $old_options['disable_wp_integration']) {
			// recheck for integration change
			$options = \get_option('ezoic_integration_status');
			$options['check_time'] = '';
			update_option('ezoic_integration_status', $options);

			// If disable_wp_integration is changed to "No" (0), disable JS Integration
			if ($settings['disable_wp_integration'] == 0 && $old_options['disable_wp_integration'] == 1) {
				// Disable JavaScript integration when WP integration is enabled
				update_option('ezoic_js_integration_enabled', false);
				
				// Clear duplicate script detection cache when JS integration is disabled
				delete_transient('ezoic_duplicate_scripts_check');
			}
		}

		return $settings;
	}

	/**
	 * Sanitize JavaScript integration options (delegated)
	 *
	 * @param array $settings Settings to sanitize
	 * @return array Sanitized settings
	 */
	public function sanitize_js_integration_options($settings)
	{
		return $this->js_integration_settings->sanitize_js_integration_options($settings);
	}
}
