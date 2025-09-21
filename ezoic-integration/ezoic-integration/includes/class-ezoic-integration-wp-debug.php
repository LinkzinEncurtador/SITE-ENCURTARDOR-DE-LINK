<?php

namespace Ezoic_Namespace;

require_once(dirname(__FILE__) . '/interface-ezoic-integration-debug.php');

class Ezoic_Integration_WP_Debug implements iEzoic_Integration_Debug
{
	private $cache_identity;

	/**
	 * Ezoic_Integration_WP_Debug constructor.
	 *
	 * @param $cache_identity
	 */
	public function __construct($cache_identity)
	{
		$this->cache_identity = $cache_identity;
	}

	public function get_debug_information()
	{
		global $wp;
		$home_url = home_url($wp->request);
		if (substr($home_url, -1) != '/' && function_exists('should_current_path_end_in_slash') && should_current_path_end_in_slash()) {
			$home_url = $home_url . '/';
		}

		$current_url = add_query_arg($_SERVER['QUERY_STRING'], '', $home_url);

		$data = array();

		if (function_exists('get_plugins')) {
			$all_plugins    = get_plugins();
			$active_plugins = get_option('active_plugins');
			$plugins        = array();
			foreach ($all_plugins as $key => $value) {
				$plugins[$key]           = $value;
				$is_active                 = in_array($key, $active_plugins);
				$plugins[$key]['Active'] = $is_active ? "true" : "false";
			}
			$data['Plugins'] = $plugins;
		}

		if (function_exists('phpversion')) {
			$data['PHP'] = phpversion();
		}

		$ez_plugin = $this->get_ez_plugin_settings();

		$debug_content = array(
			"Home URL"    => $home_url,
			"Current URL" => $current_url,
			"Cache Type"  => $this->cache_identity,
			"EZ Plugin"   => $ez_plugin
		);
		$debug_content = array_merge($debug_content, $data);

		return PHP_EOL . PHP_EOL . "<!--[if IE 3 ]>" . PHP_EOL . print_r($debug_content, true) . "<![endif]-->";
	}

	public function we_should_debug()
	{
		return EZOIC_DEBUG;
	}

	/**
	 * Debug output of Ezoic plugin setting values
	 *
	 * @return array
	 */
	private function get_ez_plugin_settings()
	{
		$ez_plugin                              = array();
		$ez_plugin['ezoic_integration_status']  = \get_option('ezoic_integration_status');
		$ez_plugin['ezoic_integration_options'] = \get_option('ezoic_integration_options');

		$ping_test = "empty";
		$ping      = array(false, "");
		$api_key   = Ezoic_Cdn::ezoic_cdn_api_key();
		if (! empty($api_key)) {
			$ping = Ezoic_Cdn::ezoic_cdn_ping();
			if ($ping[0] == true) {
				$ping_test = "valid";
			} else {
				$ping_test = "error";
			}
		}

		$ez_plugin['ezoic_cdn_api_key']['status'] = $ping_test;
		$ez_plugin['ezoic_cdn_api_key']['error']  = $ping[1];
		$ez_plugin['ezoic_cdn_enabled']           = \get_option('ezoic_cdn_enabled');
		$ez_plugin['ezoic_cdn_domain']            = \get_option('ezoic_cdn_domain');
		$ez_plugin['ezoic_cdn_always_home']       = \get_option('ezoic_cdn_always_home');
		$ez_plugin['ezoic_integration_status']    = \get_option('ezoic_integration_status');

		$ez_plugin['ezoic_js_integration_enabled'] = \get_option('ezoic_js_integration_enabled');
		$ez_plugin['ezoic_js_integration_options'] = \get_option('ezoic_js_integration_options');

		return $ez_plugin;
	}
}
