<?php

namespace Ezoic_Namespace;

class Ezoic_Leap_Wp_Data
{

	public static function set_debug_to_ezoic()
	{
		set_transient('ezoic_send_debug', array(1, 1));
	}

	public function send_debug_to_ezoic($force_send = false)
	{

		$ezoic_send_debug = get_transient('ezoic_send_debug');
		if ($force_send || $ezoic_send_debug) {

			if (! is_array($ezoic_send_debug)) {
				$ezoic_send_debug = array(1, 1);
			}

			$request = $this->build_data_request(array(), $ezoic_send_debug[1]);
			$response = wp_remote_post(Ezoic_Integration_Plugin_Data_Service::ENDPOINT_URL, $request);
			delete_transient('ezoic_send_debug');
		}
	}

	/**
	 * Get list of wordpress plugins with status
	 *
	 * @return array[]
	 */
	public function get_plugin_data()
	{

		// Get all plugins
		if (! function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		// Get active plugins
		$active_plugins = get_option('active_plugins');

		// Add 'Active' boolean to $all_plugins array.
		foreach ($all_plugins as $key => $value) {
			$is_active                     = in_array($key, $active_plugins);
			$all_plugins[$key]['Active'] = $is_active;

			$plugin_slug = dirname(plugin_basename($key));
			if ($plugin_slug == ".") {
				$plugin_slug = basename($key, '.php');
			}
			$all_plugins[$key]['Slug'] = $plugin_slug;
		}

		return $all_plugins;
	}


	/**
	 * @param $data
	 * @param bool $status
	 *
	 * @return array
	 */
	public function build_data_request($data, $status = 1)
	{
		$request_params = Ezoic_Integration_Plugin_Data_Service::build_plugin_data((bool) $status);

		$data = wp_json_encode($request_params);
		if (! $data) {
			$request_params = \ez_utf8_converter($request_params);
			$data           = wp_json_encode($request_params);
			if (! $data) {
				// encoding still failed, just send base data
				$request_params = Ezoic_Integration_Plugin_Data_Service::build_plugin_data((bool) $status);
				$data           = wp_json_encode($request_params);
			}
		}

		$request = array(
			'timeout' => 30,
			'body'    => $data,
			'headers' => array(
				'X-Wordpress-Integration' => 'true',
				'Expect'                  => '',
				'X-From-Req'              => 'wp'
			),
		);

		return $request;
	}
}
