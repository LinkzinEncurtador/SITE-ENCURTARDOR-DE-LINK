<?php

namespace Ezoic_Namespace;

/**
 * Centralized service for managing plugin data communication with backend
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */
class Ezoic_Integration_Plugin_Data_Service
{
	/**
	 * Backend endpoint for plugin data
	 */
	const ENDPOINT_URL = "https://publisherbe.ezoic.com/pub/v1/wordpressintegration/v1/wp/plugin";

	/**
	 * Build plugin data array
	 *
	 * @param bool $is_active Whether the plugin is active
	 * @return array Plugin data matching WpPluginData struct
	 */
	public static function build_plugin_data($is_active = true)
	{
		global $wp;

		$domain = home_url($wp->request ?? '');
		$domain = wp_parse_url($domain)['host'];

		// Check JavaScript integration settings
		$js_integration_enabled = get_option('ezoic_js_integration_enabled', false);
		$js_options = get_option('ezoic_js_integration_options', array());
		$auto_insert_enabled = $js_integration_enabled && isset($js_options['js_auto_insert_scripts']) && $js_options['js_auto_insert_scripts'];
		$wp_placeholders_enabled = $js_integration_enabled && isset($js_options['js_use_wp_placeholders']) && $js_options['js_use_wp_placeholders'];

		return array(
			'domain'          => $domain,
			'js_enabled'      => (bool) $js_integration_enabled,
			'auto_insert'     => (bool) $auto_insert_enabled,
			'wp_placeholders' => (bool) $wp_placeholders_enabled,
			'version'         => defined('EZOIC_INTEGRATION_VERSION') ? EZOIC_INTEGRATION_VERSION : '1.0.0',
			'is_active'       => (bool) $is_active,
		);
	}

	/**
	 * Build HTTP request array for plugin data
	 *
	 * @param array $plugin_data Plugin data array
	 * @return array HTTP request array
	 */
	public static function build_request($plugin_data)
	{
		return array(
			'timeout' => 30,
			'body'    => json_encode($plugin_data),
			'headers' => array(
				'X-Wordpress-Integration' => 'true',
				'Expect'                  => '',
				'X-From-Req'              => 'wp'
			),
		);
	}

	/**
	 * Send plugin data to backend
	 *
	 * @param bool $is_active Whether the plugin is active
	 * @return array|WP_Error Response from wp_remote_post
	 */
	public static function send_plugin_data($is_active = true)
	{
		$plugin_data = self::build_plugin_data($is_active);
		$request = self::build_request($plugin_data);

		return wp_remote_post(self::ENDPOINT_URL, $request);
	}

	/**
	 * Schedule plugin data send via transient
	 * This will be processed by the existing send_debug_to_ezoic() method
	 */
	public static function schedule_plugin_data_send()
	{
		set_transient('ezoic_send_debug', array(1, 1));
	}

	/**
	 * Send plugin deactivation data immediately
	 *
	 * @return array|WP_Error Response from wp_remote_post
	 */
	public static function send_deactivation_data()
	{
		return self::send_plugin_data(false);
	}
}
