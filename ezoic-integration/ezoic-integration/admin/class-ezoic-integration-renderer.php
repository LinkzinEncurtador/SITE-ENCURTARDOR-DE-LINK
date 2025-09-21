<?php

namespace Ezoic_Namespace;

/**
 * HTML rendering and display callbacks for Ezoic Integration plugin
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
class Ezoic_Integration_Renderer
{
	/**
	 * JS Integration settings instance
	 *
	 * @var Ezoic_JS_Integration_Settings
	 */
	private $js_integration_settings;

	/**
	 * Ad settings instance
	 *
	 * @var Ezoic_Integration_Ad_Settings
	 */
	private $ad_settings;

	/**
	 * Settings helpers instance
	 *
	 * @var Ezoic_Settings_Helpers
	 */
	private $helpers;

	/**
	 * Initialize the renderer with dependencies
	 */
	public function __construct($js_integration_settings = null, $ad_settings = null, $helpers = null)
	{
		$this->js_integration_settings = $js_integration_settings ?: new Ezoic_JS_Integration_Settings();
		$this->ad_settings = $ad_settings ?: new Ezoic_Integration_Ad_Settings();
		$this->helpers = $helpers ?: new Ezoic_Settings_Helpers();
	}

	/**
	 * Render the main settings page
	 *
	 * @param string $active_tab The active tab
	 * @param array $context Page context data
	 * @param array $messages Form submission messages
	 */
	public function render_settings_page($active_tab, $context, $messages = array())
	{
		$atm_warning = $context['atm_warning']['warning'];
		$cdn_warning = $context['cdn_warning'];
		$js_integration_warning = $context['js_integration_warning'];
?>
		<div class="wrap" id="ez_integration">
			<?php
			// Display form submission messages
			foreach ($messages as $message) {
				$class = $message['type'] === 'success' ? 'updated' : 'error';
			?>
				<div id="message" class="<?php echo $class; ?> notice is-dismissible">
					<p><strong><?php echo esc_html($message['message']); ?></strong></p>
				</div>
			<?php
			}

			// Display URL parameter messages
			$url_messages = $this->helpers->get_form_messages();
			foreach ($url_messages as $message) {
				$class = $message['type'] === 'success' ? 'updated' : 'error';
			?>
				<div id="message" class="<?php echo $class; ?> notice is-dismissible">
					<p><strong><?php echo esc_html($message['message']); ?></strong></p>
				</div>
			<?php
			}
			?>

			<p><img src="<?php echo plugins_url('/admin/img', EZOIC__PLUGIN_FILE); ?>/ezoic-logo.png" width="190" height="40" alt="Ezoic" /></p>

			<?php $this->render_tab_navigation($active_tab, $atm_warning, $cdn_warning, $js_integration_warning); ?>

			<form method="post" action="options.php" id="ezoic_settings">
				<?php $this->render_tab_content($active_tab); ?>
			</form>

			<?php if ($active_tab == 'js_integration') {
				$this->js_integration_settings->render_help_section();
			} ?>
		</div><!-- /.wrap -->
	<?php
	}

	/**
	 * Render the tab navigation
	 *
	 * @param string $active_tab The active tab
	 * @param string $atm_warning ATM warning indicator
	 * @param string $cdn_warning CDN warning indicator
	 * @param string $js_integration_warning JS integration warning indicator
	 */
	private function render_tab_navigation($active_tab, $atm_warning, $cdn_warning, $js_integration_warning)
	{
	?>
		<h2 class="nav-tab-wrapper">
			<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=integration_status"
				class="nav-tab <?php echo $active_tab == 'integration_status' ? 'nav-tab-active' : ''; ?>"><?php _e('Dashboard', 'ezoic'); ?></a>
			<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=js_integration"
				class="nav-tab <?php echo $active_tab == 'js_integration' ? 'nav-tab-active' : ''; ?>"><?php _e('Integration', 'ezoic'); ?> <?php echo $js_integration_warning; ?></a>
			<?php if (Ezoic_AdsTxtManager::ezoic_should_show_adstxtmanager_setting()) { ?>
				<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=adstxtmanager_settings"
					class="nav-tab <?php echo $active_tab == 'adstxtmanager_settings' ? 'nav-tab-active' : ''; ?>"><?php
																													_e('Ads.txt Setup', 'ezoic');
																													?> <?php echo $atm_warning; ?></a>
			<?php } ?>
			<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=ad_settings"
				class="nav-tab <?php echo $active_tab == 'ad_settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Ad Placements', 'ezoic'); ?></a>

			<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=cdn_settings"
				class="nav-tab <?php echo $active_tab == 'cdn_settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Cache Settings', 'ezoic'); ?> <?php echo $cdn_warning; ?></a>

			<?php if ((\get_option('ez_emote', 'false') == 'true')) { ?>
				<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=emote_settings"
					class="nav-tab <?php echo $active_tab == 'emote_settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Emote Settings', 'ezoic'); ?></a>
			<?php } ?>

			<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=advanced_options"
				class="nav-tab <?php echo $active_tab == 'advanced_options' ? 'nav-tab-active' : ''; ?>"><?php _e('Advanced', 'ezoic'); ?></a>

			<?php if ('Ezoic' === EZOIC__SITE_NAME) { ?>
				<a href="https://support.ezoic.com/" target="_blank" class="nav-tab" id="help-tab">
					<?php _e('Help Center', 'ezoic'); ?>
				</a>
				<a href="<?php echo EZOIC__SITE_LOGIN; ?>" target="_blank" class="nav-tab" id="pubdash-tab">
					<?php _e('Publisher Dashboard', 'ezoic'); ?>
				</a>
			<?php } ?>
		</h2>
<?php
	}

	/**
	 * Render the content for the active tab
	 *
	 * @param string $active_tab The active tab
	 */
	private function render_tab_content($active_tab)
	{
		if ($active_tab == 'ezoic_speed_settings') {
			settings_fields('ezoic_speed_settings');
			do_settings_sections('ezoic_speed_settings');
			submit_button('Save Settings');
		} elseif ($active_tab == 'advanced_options') {
			settings_fields('ezoic_integration_options');
			do_settings_sections('ezoic_integration_settings');
			submit_button('Save Settings');
		} elseif ($active_tab == 'cdn_settings') {
			settings_fields('ezoic_cdn');
			do_settings_sections('ezoic_cdn');
			submit_button('Save Settings');
		} elseif ($active_tab == 'ad_settings') {
			$this->ad_settings->render_settings_page_content();
		} elseif ($active_tab == 'adstxtmanager_settings') {
			settings_fields('ezoic_adstxtmanager');
			do_settings_sections('ezoic_adstxtmanager');
			submit_button('Save Settings');
		} elseif ($active_tab == 'emote_settings') {
			settings_fields('ezoic_emote_settings');
			do_settings_sections('ezoic_emote_settings');
			submit_button('Save Settings');
		} elseif ($active_tab == 'js_integration') {
			$this->js_integration_settings->render_js_integration_tab();
		} else {
			settings_fields('ezoic_integration_status');
			do_settings_sections('ezoic_integration_status');
		}
	}

	/**
	 * Callback for general options section
	 */
	public function general_options_callback()
	{
		$options = \get_option('ezoic_integration_status');

		echo '<hr/>';
		$this->display_notice($options);

		// Display duplicate script warning if detected
		$this->display_duplicate_script_warning();

		if (isset($_GET['create_default']) && $_GET['create_default']) {
			// $init = new Ezoic_AdTester_Init();
			// $init->initialize();
		}
	}

	/**
	 * Callback for ads settings section
	 */
	public function ads_settings_callback()
	{
		echo 'Hello World!';
	}

	/**
	 * Callback for advanced options section
	 */
	public function advanced_options_callback()
	{
		echo '<p>' . __('These settings can be used to enhance your default WordPress integration. They should only be used if you are an advanced user and know what you are doing. If you have any questions, feel free to reach out to <a href="https://support.ezoic.com/" target="_blank" rel="noreferrer noopener">our support</a>.', 'ezoic') . '</p>';
		echo '<hr/>';
	}

	/**
	 * Callback for integration status display
	 */
	public function is_integrated_callback()
	{
		$options = \get_option('ezoic_integration_status');
		$ezoic_options = \get_option('ezoic_integration_options');

		$html = '<input type="hidden" id="is_integrated" name="ezoic_integration_status[is_integrated]" value="1" ' . checked(
			1,
			isset($options['is_integrated']) ? $options['is_integrated'] : 0,
			false
		) . '/>';

		if ($options['is_integrated']) {
			if (Ezoic_Integration_Admin::is_cloud_integrated()) {
				// cloud
				$html .= '<div style="display: flex; align-items: center; padding: 12px 0;">';
				$html .= '<span class="dashicons dashicons-cloud-saved text-success" style=" font-size: 20px; margin-right: 8px;"></span>';
				$html .= '<span class="text-success" style="font-weight: 600; font-size: 14px;">Cloud Integrated</span>';
				$html .= '</div>';
			} elseif (!empty($options['integration_type']) && $options['integration_type'] == "sa") {
				// Check if plugin is managing the SA integration
				$js_integration_enabled = get_option('ezoic_js_integration_enabled', false);
				$js_options = get_option('ezoic_js_integration_options');
				$auto_insert_enabled = $js_integration_enabled && isset($js_options['js_auto_insert_scripts']) && $js_options['js_auto_insert_scripts'];

				if ($auto_insert_enabled) {
					// SA integration managed by plugin
					$html .= '<div style="display: flex; align-items: center; padding: 12px 0; margin-bottom: 16px;">';
					$html .= '<span class="dashicons dashicons-saved text-info" style=" font-size: 20px; margin-right: 8px;"></span>';
					$html .= '<span class="text-info" style=" font-weight: 600; font-size: 14px;">JavaScript Integration (Managed by Plugin)</span>';
					$html .= '</div>';
					$html .= '<a class="button button-primary" href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=js_integration" style="text-decoration: none;">Configure Integration Settings</a>';
				} else {
					// SA integration detected but not managed by plugin
					$html .= '<div style="display: flex; align-items: center; padding: 12px 0;">';
					$html .= '<span class="dashicons dashicons-saved text-info" style=" font-size: 20px; margin-right: 8px;"></span>';
					$html .= '<span class="text-info" style=" font-weight: 600; font-size: 14px;">JavaScript Integration Detected</span>';
					$html .= '</div>';
				}
			} elseif (!empty($options['integration_type']) && $options['integration_type'] == "ba") {
				// basic
				$html .= '<div style="display: flex; align-items: center; padding: 12px 0;">';
				$html .= '<span class="dashicons dashicons-saved text-success" style=" font-size: 20px; margin-right: 8px;"></span>';
				$html .= '<span class="text-success" style="font-weight: 600; font-size: 14px;">Basic Integrated</span>';
				$html .= '</div>';
			} elseif (isset($ezoic_options['disable_wp_integration']) && $ezoic_options['disable_wp_integration'] == true) {
				// no integration detected
				$html .= '<div style="padding: 12px 0; margin-bottom: 16px;">';
				$html .= '<div style="display: flex; align-items: center; margin-bottom: 16px;">';
				$html .= '<span class="dashicons dashicons-clock text-danger" style=" font-size: 20px; margin-right: 8px;"></span>';
				$html .= '<span class="text-danger" style=" font-weight: 600; font-size: 14px;">Waiting on Integration</span>';
				$html .= '</div>';

				$html .= '<div style="display: flex; flex-direction: column; gap: 12px; max-width: 280px;">';
				// Add Enable JavaScript Integration option first
				if (!get_option('ezoic_js_integration_enabled', false)) {
					$html .= '<form method="post" action="" style="margin: 0;">';
					$html .= wp_nonce_field('enable_js_integration_nonce', 'js_integration_nonce', true, false);
					$html .= '<input type="hidden" name="action" value="enable_js_integration"/>';
					$html .= '<input type="submit" class="button button-secondary" value="Enable JavaScript Integration" style="background: #0073aa; color: white; border-color: #005a87; width: 100%;"/>';
					$html .= '</form>';
				}

				$html .= '<a class="button button-success" href="https://pubdash.ezoic.com/integration" target="_blank" style="color: white; text-decoration: none; background: #5fa624; border-color: #53951a; text-align: center; width: 100%; box-sizing: border-box;">Integration Options <span class="dashicons dashicons-external" style="font-size: 16px; vertical-align: middle;"></span></a>';
				$html .= '</div></div>';
			} else {
				// wordpress
				$html .= '<div style="display: flex; align-items: center; padding: 12px 0;">';
				$html .= '<span class="dashicons dashicons-wordpress-alt text-success" style=" font-size: 20px; margin-right: 8px;"></span>';
				$html .= '<span class="text-success" style="font-weight: 600; font-size: 14px;">WordPress Integrated</span>';
				$html .= '</div>';
			}
		} elseif (get_option('ezoic_js_integration_enabled', false)) {
			// Manual JavaScript integration enabled via plugin (no automatic integration detected)
			$html .= '<div style="padding: 12px 0; margin-bottom: 16px;">';
			$html .= '<div style="display: flex; align-items: center; margin-bottom: 8px;">';
			$html .= '<span class="dashicons dashicons-saved text-info" style=" font-size: 20px; margin-right: 8px;"></span>';
			$html .= '<span class="text-info" style=" font-weight: 600; font-size: 14px;">Manual JavaScript Integration Enabled</span>';
			$html .= '</div>';
			$html .= '<p style="color: #646970; font-size: 13px; margin: 8px 0 16px 0;"><em>Note: Integration not yet detected by Ezoic. It may take a few minutes for changes to be recognized.</em></p>';
			$html .= '<a class="button button-primary" href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=js_integration" style="text-decoration: none;">Configure Integration Settings</a>';
			$html .= '</div>';
		} else {
			$html .= '<div style="padding: 12px 0; margin-bottom: 16px;">';
			$html .= '<div style="display: flex; align-items: center; margin-bottom: 16px;">';
			$html .= '<span class="dashicons dashicons-clock text-danger" style=" font-size: 20px; margin-right: 8px;"></span>';
			$html .= '<span class="text-danger" style=" font-weight: 600; font-size: 14px;">Waiting on Integration</span>';
			$html .= '</div>';

			$html .= '<div style="display: flex; flex-direction: column; gap: 12px; max-width: 280px;">';
			// Add Enable JavaScript Integration option first
			if (!get_option('ezoic_js_integration_enabled', false)) {
				$html .= '<form method="post" action="" style="margin: 0;">';
				$html .= wp_nonce_field('enable_js_integration_nonce', 'js_integration_nonce', true, false);
				$html .= '<input type="hidden" name="action" value="enable_js_integration"/>';
				$html .= '<input type="submit" class="button button-secondary" value="Enable JavaScript Integration" style="background: #0073aa; color: white; border-color: #005a87; width: 100%;"/>';
				$html .= '</form>';
			}

			$html .= '<a class="button button-success" href="https://pubdash.ezoic.com/integration" target="_blank" style="color: white; text-decoration: none; background: #5fa624; border-color: #53951a; text-align: center; width: 100%; box-sizing: border-box;">Integration Options <span class="dashicons dashicons-external" style="font-size: 16px; vertical-align: middle;"></span></a>';
			$html .= '</div></div>';
		}

		echo '<div style="margin-bottom: 30px;">' . $html . '</div>';
	}

	/**
	 * Callback for ads.txt manager status display
	 */
	public function adstxt_manager_status_callback()
	{
		// Use standard status check - prioritize actual redirect functionality over detection issues
		$atm_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);

		if (isset($atm_status['status']) && $atm_status['status'] === true) {
			echo '<div style="display: flex; align-items: center; padding: 12px 0;">';
			echo '<span class="dashicons dashicons-saved text-success" style=" font-size: 20px; margin-right: 8px;"></span>';
			echo '<span class="text-success" style="font-weight: 600; font-size: 14px;">Successfully Setup</span>';
			echo '</div>';
		} else {
			echo '<div style="padding: 12px 0; margin-bottom: 16px;">';
			echo '<div style="display: flex; align-items: center; margin-bottom: 16px;">';
			echo '<span class="dashicons dashicons-warning text-danger" style=" font-size: 20px; margin-right: 8px;"></span>';
			echo '<span class="text-danger" style=" font-weight: 600; font-size: 14px;">Setup Required</span>';
			echo '</div>';
			echo '<a class="button button-primary" href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=adstxtmanager_settings" style="text-decoration: none;">Set Up Ads.txt</a>';
			echo '</div>';
		}
		echo '<div style="margin-bottom: 30px;"></div>';
	}

	/**
	 * Callback for check time display
	 */
	public function check_time_callback()
	{
		$options = \get_option('ezoic_integration_status');
		$date_format = get_option('date_format') . ' ' . get_option('time_format');
		$check_time = !empty($options['check_time']) ? wp_date($date_format, $options['check_time']) : '';

		$html = '<input type="hidden" id="check_time" name="ezoic_integration_status[check_time]" value="' . $options['check_time'] . '"/>';
		$html .= '<div><em>' . $check_time . '</em> &nbsp;<a href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=integration_status&recheck=1"><span class="dashicons dashicons-update" title="WordPress Integrated" style="text-decoration: none;"></span></a></div>';

		echo $html;
	}

	/**
	 * Callback for SSL verification setting
	 *
	 * @param array $args Callback arguments
	 */
	public function verify_ssl_callback($args)
	{
		$options = \get_option('ezoic_integration_options');

		$html = '<select id="verify_ssl" name="ezoic_integration_options[verify_ssl]">';
		$html .= '<option value="1" ' . selected($options['verify_ssl'], 1, false) . '>' . __('Yes', 'ezoic') . '</option>';
		$html .= '<option value="0" ' . selected($options['verify_ssl'], 0, false) . '>' . __('No', 'ezoic') . '</option>';
		$html .= '</select>';
		$html .= '<td><p>' . $args[0] . '</p></td>';

		echo $html;
	}

	/**
	 * Callback for disable WP integration setting
	 *
	 * @param array $args Callback arguments
	 */
	public function disable_wp_integration_callback($args)
	{
		$options = \get_option('ezoic_integration_options');

		// disable by default
		if (!isset($options['disable_wp_integration'])) {
			$options['disable_wp_integration'] = 0;
			\update_option('ezoic_integration_options', $options);
		}

		$cache_identifier = new Ezoic_Integration_Cache_Identifier();
		if ($options['disable_wp_integration'] == 1) {
			//modify htaccess files
			$cache_identifier->remove_htaccess_file();
			//modify php files
			$cache_identifier->restore_advanced_cache();
		} else {
			//modify htaccess files
			$cache_identifier->generate_htaccess_file();
			//modify php files
			$cache_identifier->modify_advanced_cache();
		}

		$html = '<select id="disable_wp_integration" name="ezoic_integration_options[disable_wp_integration]">';
		$html .= '<option value="0" ' . selected($options['disable_wp_integration'], 0, false) . '>' . __('No', 'ezoic') . '</option>';
		$html .= '<option value="1" ' . selected($options['disable_wp_integration'], 1, false) . '>' . __('Yes', 'ezoic') . '</option>';
		$html .= '</select>';
		$html .= '<td><p>' . $args[0] . '</p></td>';

		echo $html;
	}

	/**
	 * Callback for plugin compatibility warnings
	 *
	 * @param array $args Callback arguments containing plugin arrays
	 */
	public function plugin_compatibility_callback($args)
	{
		$incompatible_plugins = $args[0];
		$compatible_plugins = $args[1];
		$options = \get_option('ezoic_integration_status');

		$html = '';

		// Check if running on WPEngine on non cloud sites
		if (function_exists('is_wpe') && call_user_func('is_wpe') && isset($options['integration_type']) && $options['integration_type'] == "wp") {
			$html .= '<h3><span class="dashicons dashicons-warning text-danger"></span> Incompatibility with WPEngine</h3>';
			$html .= 'There are incompatibilities with Ezoic WordPress integration and WPEngine hosting. We recommend switching to Ezoic Cloud integration. <a href="' . EZOIC__SITE_LOGIN . '?redirect=%2Fintegration" target="_blank">Click here to explore other integration options</a>.<br /><br />';
		}

		// incompatible plugins
		if (count($incompatible_plugins) > 0) {
			$html .= '<h4><strong><span class="dashicons dashicons-warning text-danger"></span> Incompatible Plugins Detected</strong></h4>';

			if (Ezoic_Integration_Admin::is_wordpress_integrated()) {
				$html .= 'The following plugin(s) must be disabled to fully utilize <strong>Ezoic WordPress integration</strong> without issues or conflicts.<br/>We recommend switching to our <a href="' . EZOIC__SITE_LOGIN . '?redirect=%2Fintegration" target="_blank">JavaScript Integration</a> for improved speed and compatibility';
				if (count($compatible_plugins) > 0) {
					$html .= ', or review additional Ezoic Recommendations below';
				}
				$html .= '.<br /><br /><br/>';
			} else {
				$html .= 'The following plugin(s) must be disabled to fully utilize Ezoic without issues or conflicts. ';
				if (count($compatible_plugins) > 0) {
					$html .= 'See Ezoic Recommendations below.';
				}
				$html .= '<br /><br /><br/>';
			}

			foreach ($incompatible_plugins as $plugin) {
				$html .= '<strong>' . $plugin['name'] . ' (' . $plugin['version'] . ') </strong>';
				$html .= '<br />';
				$html .= $plugin['message'];

				$deactivate_link = Ezoic_Integration_Compatibility_Check::plugin_action_url($plugin['filename']);
				$html .= '<br/><p><a class="button button-primary" href="' . $deactivate_link . '">Deactivate Plugin</a></p>';

				$html .= '<br /><br />';
			}
		}

		// show compatible plugins that can be replaced by Ezoic product and display recommendations
		if (count($compatible_plugins) > 0) {
			if (count($incompatible_plugins) > 0) {
				$html .= '<hr/><br/>';
			}

			$plugin_string = '';
			foreach ($compatible_plugins as $plugin) {
				$plugin_string .= '<strong>' . $plugin['name'] . '</strong><br />';
				$plugin_string .= $plugin['message'] . '<br /><br />';
			}
			$html .= '<h3>Ezoic Recommendations</h3>
				The following plugin(s) <i>may not</i> be compatible with Ezoic:<br /><br />'
				. $plugin_string . '<br />';
		}

		echo '<div style="margin-bottom: 30px;">' . $html . '</div>';
	}

	/**
	 * Display integration notice
	 *
	 * @param array $options Integration status options
	 */
	public function display_notice($options)
	{

		// enable WP integration
		if (isset($_GET['wp_integration']) && $_GET['wp_integration']) {
			$integration_options = \get_option('ezoic_integration_options');
			$integration_options['disable_wp_integration'] = 0;
			\update_option('ezoic_integration_options', $integration_options);
		}

		$time_check = time() - 21600; // 6 hours
		if (!isset($options['is_integrated']) || $options['check_time'] <= $time_check || (isset($_GET['recheck']) && $_GET['recheck'])) {

			$results = $this->get_integration_check_ezoic_response();

			$update = array();
			$update['is_integrated'] = $results['result'];
			$update['integration_type'] = $results['integration'];
			$update['check_time'] = time();
			update_option('ezoic_integration_status', $update);

			if (false === $results['result']) {

				if (!empty($results['error'])) {
					$args = apply_filters(
						'ezoic_view_arguments',
						array('type' => 'integration_error'),
						'ezoic-integration-admin'
					);
				} else {
					$args = apply_filters(
						'ezoic_view_arguments',
						array('type' => 'not_integrated'),
						'ezoic-integration-admin'
					);
				}

				foreach ($args as $key => $val) {
					$$key = $val;
				}
			}
			$file = EZOIC__PLUGIN_DIR . 'admin/partials/' . 'ezoic-integration-admin-display' . '.php';
			include($file);
		}
	}

	/**
	 * Get integration check response from Ezoic
	 *
	 * @return array Response data
	 */
	public function get_integration_check_ezoic_response()
	{
		$content = 'ezoic integration test';
		$response = $this->request_data_from_ezoic($content);

		// no integration, recheck for sa/ba
		if ($response['result'] !== true) {
			$response = $this->request_data_from_ezoic($content, get_home_url() . '?ezoic_domain_verify=1');
		}

		return $response;
	}

	/**
	 * Request data from Ezoic API
	 *
	 * @param string $final_content Content to send
	 * @param string $request_url Optional request URL
	 * @return array Response data
	 */
	private function request_data_from_ezoic($final_content, $request_url = "")
	{
		$timeout = 5;
		$cache_key = md5($final_content);
		$request_data = Ezoic_Integration_Request_Utils::get_request_base_data();

		if (empty($request_url)) {
			$request_url = Ezoic_Integration_Request_Utils::get_ezoic_server_address();
		}

		$request_params = array(
			'cache_key' => $cache_key,
			'action' => 'get-index-series',
			'content_url' => get_home_url() . '?ezoic_domain_verify=1',
			'request_headers' => $request_data["request_headers"],
			'response_headers' => $request_data["response_headers"],
			'http_method' => $request_data["http_method"],
			'ezoic_api_version' => $request_data["ezoic_api_version"],
			'ezoic_wp_integration_version' => $request_data["ezoic_wp_plugin_version"],
			'content' => $final_content,
			'request_type' => 'with_content',
		);

		$ezoic_options = \get_option('ezoic_integration_options');
		$cache_identifier = new Ezoic_Integration_Cache_Identifier();
		$cache_type = $cache_identifier->get_cache_type();

		if ($cache_type != Ezoic_Cache_Type::NO_CACHE && function_exists('curl_version')) {

			$settings = array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $request_url,
				CURLOPT_TIMEOUT => $timeout,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER => array(
					'X-Wordpress-Integration: true',
					'X-Forwarded-For: ' . $request_data["client_ip"],
					'Content-Type: application/x-www-form-urlencoded',
					'Expect:',
				),
				CURLOPT_POST => true,
				CURLOPT_HEADER => true,
				CURLOPT_POSTFIELDS => http_build_query($request_params),
				CURLOPT_USERAGENT => !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
			);

			if (isset($ezoic_options['verify_ssl']) && $ezoic_options['verify_ssl'] == false) {
				$settings[CURLOPT_SSL_VERIFYPEER] = false;
				$settings[CURLOPT_SSL_VERIFYHOST] = false;
			}

			$result = Ezoic_Integration_Request_Utils::make_curl_request($settings);

			if (!empty($result['error'])) {
				return array("result" => false, "error" => $result['error'], "integration" => "off");
			}
		} else {

			unset($request_data["request_headers"]["Content-Length"]);
			$request_data["request_headers"]['X-Wordpress-Integration'] = 'true';

			$settings = array(
				'timeout' => $timeout,
				'body' => $request_params,
				'headers' => array(
					'X-Wordpress-Integration' => 'true',
					'X-Forwarded-For' => $request_data["client_ip"],
					'Expect' => ''
				),
			);

			if (isset($ezoic_options['verify_ssl']) && $ezoic_options['verify_ssl'] == false) {
				$settings['sslverify'] = false;
			}

			$result = wp_remote_post($request_url, $settings);

			if (is_wp_error($result)) {
				return array("result" => false, "error" => $result->get_error_message(), "integration" => "off");
			}
		}

		if (is_array($result) && isset($result['body'])) {
			$final = $result['body'];
		} else {
			$final = $result;
		}

		return $this->parse_page_contents($final);
	}

	/**
	 * Parse page contents for integration detection
	 *
	 * @param string $contents Page contents
	 * @return array Parsed results
	 */
	private function parse_page_contents($contents)
	{
		$ezoic_options = \get_option('ezoic_integration_options');
		$results = array('result' => false, 'integration' => "off");

		if (Ezoic_Integration_Admin::is_cloud_integrated()) {
			$results['integration'] = "cloud";
			$results['result'] = true;
		} elseif ((isset($ezoic_options['disable_wp_integration']) && !$ezoic_options['disable_wp_integration']) && strpos(
			$contents,
			'This site is operated by Ezoic and Wordpress Integrated'
		) !== false) {
			$results['integration'] = "wp";
			$results['result'] = true;
		} elseif (strpos($contents, 'go.ezoic.net/ezoic/ezoic.js') !== false) {
			$results['integration'] = "js";
			$results['result'] = true;
		} elseif (strpos($contents, 'g.ezoic.net/ezoic/sa.min.js') !== false || strpos($contents, 'ezojs.com/ezoic/sa.min.js') !== false) {
			$results['integration'] = "sa";
			$results['result'] = true;
		} elseif (strpos($contents, 'g.ezoic.net/ez.min.js') !== false || strpos($contents, 'ezojs.com/ez.min.js') !== false || strpos(
			$contents,
			'ezojs.com/basicads.js?d='
		) !== false) {
			$results['integration'] = "ba";
			$results['result'] = true;
		}

		return $results;
	}

	/**
	 * Display warning for duplicate Ezoic scripts
	 */
	private function display_duplicate_script_warning()
	{
		// Only show warning if JS integration is enabled
		if (!get_option('ezoic_js_integration_enabled', false)) {
			return;
		}

		$js_options = get_option('ezoic_js_integration_options', array());
		$auto_insert_enabled = isset($js_options['js_auto_insert_scripts']) ? $js_options['js_auto_insert_scripts'] : 1;
		$privacy_enabled = isset($js_options['js_enable_privacy_scripts']) ? $js_options['js_enable_privacy_scripts'] : 1;

		// Check for duplicate scripts
		$duplicate_scripts = $this->js_integration_settings->get_all_duplicate_scripts();
		$warnings = array();

		if ($auto_insert_enabled && $duplicate_scripts['sa']) {
			$warnings[] = __('Ezoic ad scripts (sa.min.js)', 'ezoic');
		}
		if ($privacy_enabled && $duplicate_scripts['privacy']) {
			$warnings[] = __('Ezoic CMP/privacy scripts', 'ezoic');
		}

		if (!empty($warnings)) {
			echo '<div class="notice notice-warning" style="margin: 20px 0; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">';
			echo '<h4 style="margin-top: 0; color: #856404;"><span class="dashicons dashicons-warning" style="vertical-align: middle; margin-right: 5px;"></span>' . __('Duplicate Ezoic Scripts Detected', 'ezoic') . '</h4>';
			echo '<p>' . sprintf(__('The following duplicate scripts are detected on your site: %s. Having duplicate scripts can cause conflicts and performance issues.', 'ezoic'), '<strong>' . implode(', ', $warnings) . '</strong>') . '</p>';
			echo '<p><strong>' . __('Recommended Actions:', 'ezoic') . '</strong></p>';
			echo '<ul style="margin-left: 20px; list-style: disc;">';
			echo '<li>' . sprintf(__('Go to the <a href="%s">Integration settings</a> and disable the relevant script options', 'ezoic'), admin_url('admin.php?page=' . EZOIC__PLUGIN_SLUG . '&tab=js_integration')) . '</li>';
			echo '<li>' . __('Remove existing Ezoic scripts from your theme or other plugins', 'ezoic') . '</li>';
			echo '<li>' . __('Re-enable the script options once other scripts are removed', 'ezoic') . '</li>';
			echo '</ul>';
			echo '</div>';
		}
	}
}
