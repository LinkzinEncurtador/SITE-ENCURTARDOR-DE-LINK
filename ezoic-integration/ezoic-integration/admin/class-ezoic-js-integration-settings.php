<?php

namespace Ezoic_Namespace;

/**
 * JavaScript Integration Settings for the Ezoic plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
class Ezoic_JS_Integration_Settings
{
	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct()
	{
		// Constructor can be empty for now
	}

	/**
	 * Initialize JavaScript integration settings
	 */
	public function initialize_js_integration_settings()
	{
		// Only initialize if JS integration is enabled
		if (!get_option('ezoic_js_integration_enabled', false)) {
			return;
		}

		if (false === get_option('ezoic_js_integration_options')) {
			$default_array = $this->default_js_integration_options();
			update_option('ezoic_js_integration_options', $default_array);
		}

		add_settings_section(
			'ezoic_js_integration_section',
			__('JavaScript Integration Settings', 'ezoic'),
			array($this, 'js_integration_settings_callback'),
			'ezoic_js_integration_options'
		);

		add_settings_field(
			'js_auto_insert_scripts',
			__('Auto-insert Scripts', 'ezoic'),
			array($this, 'js_auto_insert_scripts_callback'),
			'ezoic_js_integration_options',
			'ezoic_js_integration_section'
		);

		add_settings_field(
			'js_enable_privacy_scripts',
			__('Enable Privacy Scripts', 'ezoic'),
			array($this, 'js_enable_privacy_scripts_callback'),
			'ezoic_js_integration_options',
			'ezoic_js_integration_section'
		);

		add_settings_field(
			'js_use_wp_placeholders',
			__('Use WordPress Placeholders', 'ezoic'),
			array($this, 'js_use_wp_placeholders_callback'),
			'ezoic_js_integration_options',
			'ezoic_js_integration_section'
		);

		register_setting(
			'ezoic_js_integration_options',
			'ezoic_js_integration_options',
			array(
				'default' => $this->default_js_integration_options(),
				'type' => 'array',
				'sanitize_callback' => array($this, 'sanitize_js_integration_options')
			)
		);
	}

	/**
	 * Default JavaScript integration options
	 */
	public function default_js_integration_options()
	{
		return array(
			'js_auto_insert_scripts' => 1,
			'js_enable_privacy_scripts' => 1,
			'js_use_wp_placeholders' => 1
		);
	}

	/**
	 * JavaScript integration settings section callback
	 */
	public function js_integration_settings_callback()
	{
		echo '<p>' . __('Configure how JavaScript integration works on your site.', 'ezoic') . '</p>';
		echo '<hr/>';
	}

	/**
	 * Auto-insert scripts field callback
	 */
	public function js_auto_insert_scripts_callback($args)
	{
		$options = get_option('ezoic_js_integration_options', $this->default_js_integration_options());
		$value = isset($options['js_auto_insert_scripts']) ? $options['js_auto_insert_scripts'] : 1;

		$html = '<input type="checkbox" id="js_auto_insert_scripts" name="ezoic_js_integration_options[js_auto_insert_scripts]" value="1"' . checked(1, $value, false) . '/>';
		$html .= '<label for="js_auto_insert_scripts">' . __('Automatically insert Ezoic scripts into your pages', 'ezoic') . '</label>';
		$html .= '<p class="description">' . __('Essential JavaScript files that initialize the Ezoic ad system.', 'ezoic') . '</p>';

		// Check if SA scripts are already detected on the site (only show SA script warnings for this setting)
		if ($value && $this->is_sa_script_detected()) {
			$html .= '<div class="notice notice-warning inline" style="margin: 10px 0; padding: 8px 12px;">';
			$html .= '<p style="margin: 0;"><strong>' . __('Warning:', 'ezoic') . '</strong> ';
			$html .= __('Ezoic ad scripts (sa.min.js) are already detected on your site. Please remove existing ad scripts before enabling auto-insert.', 'ezoic');
			$html .= '</p></div>';
		}

		echo $html;
	}

	/**
	 * Enable privacy scripts field callback
	 */
	public function js_enable_privacy_scripts_callback($args)
	{
		$options = get_option('ezoic_js_integration_options', $this->default_js_integration_options());
		$value = isset($options['js_enable_privacy_scripts']) ? $options['js_enable_privacy_scripts'] : 1;

		$html = '<input type="checkbox" id="js_enable_privacy_scripts" name="ezoic_js_integration_options[js_enable_privacy_scripts]" value="1"' . checked(1, $value, false) . '/>';
		$html .= '<label for="js_enable_privacy_scripts">' . __('Enable privacy compliance scripts', 'ezoic') . '</label>';
		$html .= '<p class="description">' . __('The privacy scripts handle user consent management and ensure compliance with privacy regulations.', 'ezoic') . '</p>';

		// Check if privacy/CMP scripts are already detected on the site
		if ($value && $this->is_privacy_script_detected()) {
			$html .= '<div class="notice notice-warning inline" style="margin: 10px 0; padding: 8px 12px;">';
			$html .= '<p style="margin: 0;"><strong>' . __('Warning:', 'ezoic') . '</strong> ';
			$html .= __('Ezoic CMP/privacy scripts are already detected on your site. Please remove existing CMP scripts before enabling privacy scripts.', 'ezoic');
			$html .= '</p></div>';
		}

		echo $html;
	}

	/**
	 * Use WordPress placeholders field callback
	 */
	public function js_use_wp_placeholders_callback($args)
	{
		$options = get_option('ezoic_js_integration_options', $this->default_js_integration_options());
		$value = isset($options['js_use_wp_placeholders']) ? $options['js_use_wp_placeholders'] : 1;

		$html = '<input type="checkbox" id="js_use_wp_placeholders" name="ezoic_js_integration_options[js_use_wp_placeholders]" value="1"' . checked(1, $value, false) . '/>';
		$html .= '<label for="js_use_wp_placeholders">' . __('Use WordPress-generated ad placeholders', 'ezoic') . '</label>';
		$html .= '<p class="description">' . sprintf(
			__('Use ad placeholders that are automatically inserted by <a href="%s">Ad Placements</a>. When disabled, placeholders must be inserted manually.', 'ezoic'),
			admin_url('admin.php?page=' . EZOIC__PLUGIN_SLUG . '&tab=ad_settings')
		) . '</p>';

		echo $html;
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

			// Clear duplicate script detection cache when JS integration is disabled
			delete_transient('ezoic_duplicate_scripts_check');

			// Send plugin data to notify backend of integration change
			Ezoic_Integration_Plugin_Data_Service::schedule_plugin_data_send();

			// Trigger integration recheck by clearing the check time
			$options = get_option('ezoic_integration_status');
			$options['check_time'] = '';
			update_option('ezoic_integration_status', $options);

			// Optionally clear JavaScript integration options
			// delete_option('ezoic_js_integration_options');

			// Redirect to Integration tab
			wp_redirect(admin_url('admin.php?page=' . EZOIC__PLUGIN_SLUG . '&tab=js_integration&js_integration_disabled=1'));
			exit;
		}
	}

	/**
	 * Sanitize JavaScript integration options
	 */
	public function sanitize_js_integration_options($settings)
	{
		// Get current options to merge with
		$current_options = get_option('ezoic_js_integration_options', $this->default_js_integration_options());

		// Sanitize each setting
		$sanitized = array();
		$sanitized['js_auto_insert_scripts'] = isset($settings['js_auto_insert_scripts']) ? 1 : 0;
		$sanitized['js_enable_privacy_scripts'] = isset($settings['js_enable_privacy_scripts']) ? 1 : 0;
		$sanitized['js_use_wp_placeholders'] = isset($settings['js_use_wp_placeholders']) ? 1 : 0;

		// Check if any relevant settings changed
		$settings_changed = (
			$current_options['js_auto_insert_scripts'] !== $sanitized['js_auto_insert_scripts'] ||
			$current_options['js_enable_privacy_scripts'] !== $sanitized['js_enable_privacy_scripts'] ||
			$current_options['js_use_wp_placeholders'] !== $sanitized['js_use_wp_placeholders']
		);

		// Trigger plugin data send if settings changed
		if ($settings_changed) {
			Ezoic_Integration_Plugin_Data_Service::schedule_plugin_data_send();
		}

		// Check if WP placeholders setting was just enabled
		$wp_placeholders_just_enabled =
			!isset($current_options['js_use_wp_placeholders']) ||
			!$current_options['js_use_wp_placeholders'] &&
			$sanitized['js_use_wp_placeholders'];

		// If WP placeholders were just enabled and JS integration is active, force generate placeholders
		if ($wp_placeholders_just_enabled && get_option('ezoic_js_integration_enabled', false)) {
			try {
				$adtester = new Ezoic_AdTester();
				$adtester->force_generate_placeholders();
			} catch (\Exception $e) {
				Ezoic_Integration_Logger::log_exception($e, 'JS Integration Settings');
			}
		}

		// Trigger integration recheck if any settings changed
		if ($sanitized !== $current_options) {
			$options = get_option('ezoic_integration_status');
			$options['check_time'] = '';
			update_option('ezoic_integration_status', $options);

			// Clear duplicate script detection cache when settings change
			delete_transient('ezoic_duplicate_scripts_check');
		}

		return $sanitized;
	}

	/**
	 * Check if sa.min.js script is already loaded on the site (cached version)
	 *
	 * @param bool $force_check Force a fresh check, bypassing cache
	 * @return bool True if duplicate scripts detected, false otherwise
	 */
	public function is_sa_script_detected($force_check = false)
	{
		$all_scripts = $this->get_all_duplicate_scripts($force_check);
		return $all_scripts['sa'];
	}

	/**
	 * Determine if we should check for duplicate scripts
	 * Only check on plugin pages to avoid unnecessary HTTP requests
	 *
	 * @return bool True if should check, false otherwise
	 */
	private function should_check_duplicate_scripts()
	{
		// Only check in admin area
		if (!is_admin()) {
			return false;
		}

		// Check if we're on the plugin settings page
		$current_page = isset($_GET['page']) ? $_GET['page'] : '';
		if ($current_page === EZOIC__PLUGIN_SLUG) {
			return true;
		}

		// Also check if we're saving JS integration settings
		if (isset($_POST['option_page']) && $_POST['option_page'] === 'ezoic_js_integration_options') {
			return true;
		}

		return false;
	}

	/**
	 * Perform the actual duplicate script detection for both SA and privacy scripts
	 * Makes a single HTTP request to check for all script types
	 *
	 * @return array Array with 'sa' and 'privacy' boolean values
	 */
	private function perform_duplicate_scripts_check()
	{
		// Get the site's homepage content
		$url = home_url('/');
		$response = wp_remote_get($url, array(
			'timeout' => 10,
			'user-agent' => 'WordPress/Ezoic Plugin Script Detection'
		));

		if (is_wp_error($response)) {
			return array('sa' => false, 'privacy' => false);
		}

		$contents = wp_remote_retrieve_body($response);
		if (empty($contents)) {
			return array('sa' => false, 'privacy' => false);
		}

		// Check for SA scripts
		$sa_duplicate = false;
		$script_count = substr_count($contents, 'ezojs.com/ezoic/sa.min.js');
		if ($script_count > 0) {
			$plugin_script_found = strpos($contents, 'id="ezoic-wp-plugin-js"') !== false;
			if ($plugin_script_found) {
				$sa_duplicate = $script_count > 1;  // Duplicates detected
			} else {
				$sa_duplicate = true; // External scripts detected
			}
		}

		// Check for privacy/CMP scripts
		$privacy_duplicate = false;
		$privacy_patterns = array(
			'cmp.gatekeeperconsent.com/min.js',
			'the.gatekeeperconsent.com/cmp.min.js'
		);

		$total_privacy_scripts = 0;
		foreach ($privacy_patterns as $pattern) {
			$total_privacy_scripts += substr_count($contents, $pattern);
		}

		if ($total_privacy_scripts > 0) {
			$plugin_cmp_found = strpos($contents, 'id="ezoic-wp-plugin-cmp"') !== false;
			$plugin_gatekeeper_found = strpos($contents, 'id="ezoic-wp-plugin-gatekeeper"') !== false;
			$plugin_privacy_scripts_found = $plugin_cmp_found && $plugin_gatekeeper_found;

			if ($plugin_privacy_scripts_found) {
				// Both plugin scripts found - check if there are additional scripts
				$privacy_duplicate = $total_privacy_scripts > 2;  // More than the 2 plugin scripts
			} else {
				$privacy_duplicate = true; // External scripts detected or incomplete plugin scripts
			}
		}

		return array(
			'sa' => $sa_duplicate,
			'privacy' => $privacy_duplicate
		);
	}

	/**
	 * Check if privacy/CMP scripts are already loaded on the site (cached version)
	 *
	 * @param bool $force_check Force a fresh check, bypassing cache
	 * @return bool True if duplicate privacy scripts detected, false otherwise
	 */
	public function is_privacy_script_detected($force_check = false)
	{
		$all_scripts = $this->get_all_duplicate_scripts($force_check);
		return $all_scripts['privacy'];
	}


	/**
	 * Check if any duplicate scripts (SA or privacy) are detected
	 *
	 * @param bool $force_check Force a fresh check, bypassing cache
	 * @return array Array with 'sa' and 'privacy' boolean values
	 */
	public function get_all_duplicate_scripts($force_check = false)
	{
		// Check if we should perform the detection (only on plugin pages)
		if (!$force_check && !$this->should_check_duplicate_scripts()) {
			// Return cached result or default if no cache
			$cached_result = get_transient('ezoic_duplicate_scripts_check');
			return $cached_result !== false ? $cached_result : array('sa' => false, 'privacy' => false);
		}

		// Perform the actual check with single HTTP request
		$result = $this->perform_duplicate_scripts_check();

		// Cache the result for 1 hour
		set_transient('ezoic_duplicate_scripts_check', $result, HOUR_IN_SECONDS);

		return $result;
	}

	/**
	 * Clear duplicate script detection cache for all script types
	 * Useful for forcing a fresh check
	 */
	public function clear_duplicate_script_cache()
	{
		delete_transient('ezoic_duplicate_scripts_check');
	}

	/**
	 * Render the JS integration tab content
	 */
	public function render_js_integration_tab()
	{
		// Check if WordPress integration is active and show recommendation
		$wp_integration_active = !get_option('ezoic_js_integration_enabled', false) &&
			Ezoic_Integration_Admin::is_wordpress_integrated();

		// Show recommendation message if WordPress integration is active
		if ($wp_integration_active && !get_option('ezoic_js_integration_enabled', false)) {
			echo '<div class="notice notice-info" style="margin: 20px 0; padding: 12px; background-color: #e7f3ff; border-left: 4px solid #0073aa;">';
			echo '<h4 style="margin-top: 0; color: #0073aa;"><span class="dashicons dashicons-info" style="vertical-align: middle; margin-right: 5px;"></span>' . __('Recommendation: Switch to JavaScript Integration', 'ezoic') . '</h4>';
			echo '<p>' . __('Your site is currently using WordPress Integration. We recommend switching to JavaScript Integration for better performance and more advanced features.', 'ezoic') . '</p>';
			echo '<p><strong>' . __('Benefits of Ezoic JavaScript Integration:', 'ezoic') . '</strong></p>';
			echo '<ul style="margin-left: 20px; list-style: disc;">';
			echo '<li>' . __('Quick, simple setup', 'ezoic') . '</li>';
			echo '<li>' . __('No changes to DNS required', 'ezoic') . '</li>';
			echo '<li>' . __('Complete control &amp; customization', 'ezoic') . '</li>';
			echo '<li>' . __('Lightweight scripts', 'ezoic') . '</li>';
			echo '<li>' . __('&#8230; and more!', 'ezoic') . '</li>';
			echo '</ul>';
			echo '</div>';
		}

		// Only show settings if JS integration is enabled
		if (get_option('ezoic_js_integration_enabled', false)) {
			settings_fields('ezoic_js_integration_options');
			do_settings_sections('ezoic_js_integration_options');
			submit_button('Save Settings');
		} else {
			// Check if site is cloud integrated and show warning
			if (Ezoic_Integration_Admin::is_cloud_integrated()) {
				echo '<div class="notice notice-warning" style="margin: 20px 0; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">';
				echo '<h4 style="margin-top: 0; color: #856404;"><span class="dashicons dashicons-warning" style="vertical-align: middle; margin-right: 5px;"></span>' . __('Cloud Integration Active', 'ezoic') . '</h4>';
				echo '<p>' . __('Your site is using Ezoic Cloud Integration, which already handles script delivery. Enabling JavaScript Integration may cause conflicts.', 'ezoic') . '</p>';
				echo '</div>';
			}

			// Just show the turn on button directly, don't call do_settings_sections
			echo '<h3>' . __('JavaScript Integration Settings', 'ezoic') . '</h3>';
			echo '<p>' . __('JavaScript integration is currently disabled. Enable it to configure advanced settings for your Ezoic ads.', 'ezoic') . '</p><hr/><br/>';
			echo '<form method="post" action="" style="margin-top: 20px;">';
			echo wp_nonce_field('enable_js_integration_nonce', 'js_integration_nonce', true, false);
			echo '<input type="hidden" name="action" value="enable_js_integration"/>';
			echo '<input type="hidden" name="from_integration_tab" value="1"/>';
			echo '<input type="submit" class="button button-primary" value="Enable JavaScript Integration" style="background: #0073aa; color: white; border-color: #005a87;"/>';
			echo '</form>';
		}
	}

	/**
	 * Render the help documentation section for JS integration
	 */
	public function render_help_section()
	{
		if (get_option('ezoic_js_integration_enabled', false)) {
			// Get JS integration options to check if auto-insert scripts is enabled
			$js_options = get_option('ezoic_js_integration_options', $this->default_js_integration_options());
			$auto_insert_enabled = isset($js_options['js_auto_insert_scripts']) ? $js_options['js_auto_insert_scripts'] : 1;
?>
			<div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
				<div style="margin-bottom: 15px;">
					<p style="color: #666; margin-bottom: 10px;"><?php _e('Need help with JavaScript integration?', 'ezoic'); ?></p>
					<a href="https://docs.ezoic.com/docs/ezoicads/" target="_blank" class="button button-secondary" style="margin-right: 10px;">
						<span class="dashicons dashicons-external" style="vertical-align: middle; margin-right: 5px;"></span>
						<?php _e('JavaScript Integration Documentation', 'ezoic'); ?>
					</a>
					<?php if ($auto_insert_enabled): ?>
						<a href="<?php echo esc_url(home_url('?ez_js_debugger=1')); ?>" target="_blank" class="button button-secondary">
							<span class="dashicons dashicons-admin-tools" style="vertical-align: middle; margin-right: 5px;"></span>
							<?php _e('Open Debugger', 'ezoic'); ?>
						</a>
					<?php endif; ?>
				</div>
				<p style="color: #666;"><?php _e('Turn off automatic JavaScript integration.', 'ezoic'); ?></p>
				<form method="post" action="<?php echo admin_url('admin.php?page=' . EZOIC__PLUGIN_SLUG . '&tab=js_integration'); ?>" style="display: inline;">
					<?php wp_nonce_field('disable_js_integration_nonce', 'js_integration_disable_nonce'); ?>
					<input type="hidden" name="action" value="disable_js_integration" />
					<input type="submit" name="disable_js_integration" class="button button-link-delete" value="<?php _e('Turn Off', 'ezoic'); ?>" />
				</form>
			</div>
<?php
		}
	}


	/**
	 * Auto-detect and setup ads.txt redirect when JavaScript integration is enabled
	 * This ensures that ads.txt redirect is automatically configured if an ATM ID is available
	 */
	private function auto_setup_adstxt_redirect_for_js_integration()
	{
		try {
			// Check if ads.txt manager auto-detect is enabled
			if (!Ezoic_AdsTxtManager::ezoic_adstxtmanager_auto_detect()) {
				return false;
			}

			// Check if ATM ID is already set
			$existing_atm_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true);
			if (!empty($existing_atm_id) && $existing_atm_id > 0) {
				// ATM ID already exists, ensure the solution is set up
				$solutionFactory = new Ezoic_AdsTxtManager_Solution_Factory();
				$adsTxtSolution = $solutionFactory->GetBestSolution();
				$adsTxtSolution->SetupSolution();
				return true;
			}

			// Try to auto-detect ATM ID from backend
			$atm_detection_result = Ezoic_AdsTxtManager::ezoic_detect_adstxtmanager_id();

			if (is_array($atm_detection_result) && isset($atm_detection_result['success']) && $atm_detection_result['success']) {
				// ATM ID was successfully detected and saved, now set up the solution
				$solutionFactory = new Ezoic_AdsTxtManager_Solution_Factory();
				$adsTxtSolution = $solutionFactory->GetBestSolution();
				$adsTxtSolution->SetupSolution();

				// Clear any existing status to allow for fresh verification
				delete_option('ezoic_adstxtmanager_status');

				return true;
			}

			return false;
		} catch (\Exception $e) {
			Ezoic_Integration_Logger::log_exception($e, 'JS Integration');
			return false;
		}
	}
}
