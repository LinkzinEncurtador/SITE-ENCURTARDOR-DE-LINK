<?php

namespace Ezoic_Namespace;

/**
 * Settings helper utilities for Ezoic Integration plugin
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
class Ezoic_Settings_Helpers
{
	/**
	 * Prepare all context data needed for rendering the settings page
	 *
	 * @return array Context data for page rendering
	 */
	public function prepare_page_context()
	{
		return array(
			'cdn_warning' => $this->get_cdn_warning(),
			'atm_warning' => $this->get_atm_warning(),
			'js_integration_warning' => $this->get_js_integration_warning(),
			'atm_status' => Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true),
			'atm_detection_result' => null // Will be set by get_atm_warning if needed
		);
	}

	/**
	 * Generate CDN warning indicator
	 *
	 * @return string HTML warning indicator or empty string
	 */
	public function get_cdn_warning()
	{
		$cdn_warning = "";
		$api_key = Ezoic_Cdn::ezoic_cdn_api_key();
		if (!empty($api_key)) {
			$ping_test = Ezoic_Cdn::ezoic_cdn_ping();
			if (!empty($ping_test) && is_array($ping_test) && $ping_test[0] == false) {
				$cdn_warning = "<span class='dashicons dashicons-warning ez_error'></span>";
			}
		}
		return $cdn_warning;
	}

	/**
	 * Generate ATM (Ads.txt Manager) warning indicator
	 *
	 * @return array Array containing 'warning' HTML and 'detection_result' if applicable
	 */
	public function get_atm_warning()
	{
		$atm_warning = "";
		$atm_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id();
		$atm_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
		$atm_detection_result = null;

		if (Ezoic_AdsTxtManager::ezoic_should_show_adstxtmanager_setting()) {
			// Only check ads.txt detection during integration recheck (not on every page load)
			$options = get_option('ezoic_integration_status');
			$time_check = time() - 21600; // 6 hours
			$is_recheck = !isset($options['is_integrated']) || $options['check_time'] <= $time_check || (isset($_GET['recheck']) && $_GET['recheck']);

			if (Ezoic_AdsTxtManager::ezoic_adstxtmanager_auto_detect() && $is_recheck) {
				$atm_detection_result = Ezoic_AdsTxtManager::ezoic_detect_adstxtmanager_id();
			} elseif (!Ezoic_AdsTxtManager::ezoic_adstxtmanager_auto_detect() && $is_recheck && $atm_id > 0) {
				// For manual ATM ID entries, refresh status during recheck to ensure accuracy
				$redirect_result = Ezoic_AdsTxtManager::ezoic_verify_adstxt_redirect();
				update_option('ezoic_adstxtmanager_status', $redirect_result);
				// Refresh cached status after updating
				$atm_status = $redirect_result;
			}

			if ($atm_detection_result && isset($atm_detection_result['error'])) {
				if (in_array($atm_detection_result['error'], array('connection_error', 'server_error', 'setup_failed'))) {
					$atm_warning = "<span class='dashicons dashicons-warning ez_error'></span>";
				} else {
					$atm_warning = "<span class='dashicons dashicons-warning ez_warning'></span>";
				}
			} elseif ($atm_detection_result && isset($atm_detection_result['success']) && $atm_detection_result['success']) {
				// Auto-detection was successful, use current cached status
				// $atm_status is already set and current from the initial call
			} elseif (isset($atm_status['status']) && !$atm_status['status']) {
				// Show error for failed ads.txt redirect when JS Integration is enabled, otherwise warning
				$js_integration_enabled = get_option('ezoic_js_integration_enabled', false);
				if ($js_integration_enabled) {
					$atm_warning = "<span class='dashicons dashicons-warning ez_error'></span>";
				} else {
					$atm_warning = "<span class='dashicons dashicons-warning ez_warning'></span>";
				}
			}
		}

		return array(
			'warning' => $atm_warning,
			'detection_result' => $atm_detection_result,
			'status' => $atm_status
		);
	}

	/**
	 * Generate JS integration warning indicator for duplicate scripts
	 * Uses cached results to avoid unnecessary HTTP requests
	 *
	 * @return string HTML warning indicator or empty string
	 */
	public function get_js_integration_warning()
	{
		$js_warning = "";

		// Only check if JS integration is enabled
		if (!get_option('ezoic_js_integration_enabled', false)) {
			return $js_warning;
		}

		$js_options = get_option('ezoic_js_integration_options', array());
		$auto_insert_enabled = isset($js_options['js_auto_insert_scripts']) ? $js_options['js_auto_insert_scripts'] : 1;
		$privacy_enabled = isset($js_options['js_enable_privacy_scripts']) ? $js_options['js_enable_privacy_scripts'] : 1;

		// Check for duplicate scripts using cached results
		// This will only make HTTP requests on plugin pages and cache the results
		$js_integration_settings = new Ezoic_JS_Integration_Settings();
		$duplicate_scripts = $js_integration_settings->get_all_duplicate_scripts();

		$has_warnings = false;

		// Check SA scripts if auto-insert is enabled
		if ($auto_insert_enabled && $duplicate_scripts['sa']) {
			$has_warnings = true;
		}

		// Check privacy scripts if privacy scripts are enabled
		if ($privacy_enabled && $duplicate_scripts['privacy']) {
			$has_warnings = true;
		}

		if ($has_warnings) {
			$js_warning = "<span class='dashicons dashicons-warning ez_warning'></span>";
		}

		return $js_warning;
	}

	/**
	 * Get active incompatible plugins for compatibility warnings
	 *
	 * @return array Array of incompatible plugins with hosting issues flag
	 */
	public function get_compatibility_issues()
	{
		$hosting_issue = false;
		$incompatible_plugins = Ezoic_Integration_Compatibility_Check::get_active_incompatible_plugins();
		$compatible_plugins = Ezoic_Integration_Compatibility_Check::get_compatible_plugins_with_recommendations();

		// Check for WPEngine hosting compatibility issues
		$options = get_option('ezoic_integration_status');
		/** @phpstan-ignore-next-line */
		if (function_exists('is_wpe')) {
			if (\is_wpe() && (isset($options['integration_type']) && $options['integration_type'] == "wp")) {
				$hosting_issue = true;
			}
		}

		return array(
			'incompatible_plugins' => $incompatible_plugins,
			'compatible_plugins' => $compatible_plugins,
			'hosting_issue' => $hosting_issue,
			'has_issues' => (count($incompatible_plugins) > 0 || count($compatible_plugins) > 0 || $hosting_issue)
		);
	}

	/**
	 * Determine the active tab from request parameters
	 *
	 * @param string $default_tab Default tab if none specified
	 * @return string The active tab name
	 */
	public function determine_active_tab($default_tab = 'integration_status')
	{
		if (isset($_GET['tab']) && !empty($_GET['tab'])) {
			return $_GET['tab'];
		}
		return $default_tab;
	}

	/**
	 * Check if we should show form submission success/error messages
	 *
	 * @return array Message data for display
	 */
	public function get_form_messages()
	{
		$messages = array();

		// Check for JavaScript integration status messages
		if (isset($_GET['js_integration_disabled']) && $_GET['js_integration_disabled'] == '1') {
			$messages[] = array(
				'type' => 'success',
				'message' => __('JavaScript Integration has been disabled. Your site will now use automatic integration detection.', 'ezoic')
			);
		}

		if (isset($_GET['js_integration_enabled']) && $_GET['js_integration_enabled'] == '1') {
			$messages[] = array(
				'type' => 'success',
				'message' => __('JavaScript Integration has been enabled! You can now configure it in the Integration tab.', 'ezoic')
			);
		}

		return $messages;
	}

	/**
	 * Get default display options array
	 *
	 * @return array Default options
	 */
	public function get_default_display_options()
	{
		return array(
			'is_integrated' => false,
			'integration_type' => 'off',
			'check_time' => '',
		);
	}

	/**
	 * Get default advanced options array
	 *
	 * @return array Default advanced options
	 */
	public function get_default_advanced_options()
	{
		return array(
			'verify_ssl' => true,
			'caching' => false,
			'disable_wp_integration' => true, // disable wp integration by default
		);
	}
}
