<?php

namespace Ezoic_Namespace;

/**
 * Compatibility check for Ezoic plugin against other plugins.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */

/**
 * Class Ezoic_Integration_Compatibility_Check
 * @package Ezoic_Namespace
 */
class Ezoic_Integration_Compatibility_Check
{

	// incompatible plugins regardless of integration type
	static $all_incompatible_plugins = array(
		'Ezoic CDN Manager' => array(
			'versions' => 'all',
			'message' => 'Ezoic CDN Manager functionality has been implemented into this main Ezoic plugin. Please deactivate and remove the Ezoic CDN Manager plugin.'
		),
	);

	// list of know incompatible plugins for wordpress integration
	static $known_incompatible_plugins = array(
		'Accelerated Mobile Pages' => array(
			'versions' => 'all',
			'message' => 'Must disable this plugin and can use another AMP plugin for conflict-free mobile site and monetization.',
			'allow_install' => true
		),
		'Ads.txt Manager' => array(
			'versions' => 'all',
			'message'  => 'For WordPress integration, we recommend disabling this plugin and using our <a href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=adstxtmanager_settings">Ads.txt Manager</a> setup.',
			'allow_install' => true
		),
		'AMP' => array(
			'versions' => 'all',
			'message' => 'Must disable this plugin and can use another AMP plugin for conflict-free mobile site and monetization.',
			'allow_install' => true
		),
		/*'Wordfence Security' => array(
			'versions' => 'all',
			'message' => 'Please disable this plugin or contact Wordfence to whitelist Ezoic IPs to avoid Origin Error (if Ezoic IPs are already whitelisted, you can ignore this message). For more information on Origin Error, please visit <a target="_blank" rel="noopener noreferrer" href="https://support.ezoic.com/kb/article/how-to-fix-origin-errors">support.ezoic.com/kb/article/how-to-fix-origin-errors</a>.',
			'allow_install' => true
		),
		'Wordfence Login Security' => array(
			'versions' => 'all',
			'message' => 'Please disable this plugin or contact Wordfence to whitelist Ezoic IPs to avoid Origin Error (if Ezoic IPs are already whitelisted, you can ignore this message). For more information on Origin Error, please visit <a target="_blank" rel="noopener noreferrer" href="https://support.ezoic.com/kb/article/how-to-fix-origin-errors">support.ezoic.com/kb/article/how-to-fix-origin-errors</a>.',
			'allow_install' => true
		),
		'Wordfence Assistant' => array(
			'versions' => 'all',
			'message' => 'Please disable this plugin or contact Wordfence to whitelist Ezoic IPs to avoid Origin Error (if Ezoic IPs are already whitelisted, you can ignore this message). For more information on Origin Error, please visit <a target="_blank" rel="noopener noreferrer" href="https://support.ezoic.com/kb/article/how-to-fix-origin-errors">support.ezoic.com/kb/article/how-to-fix-origin-errors</a>.',
			'allow_install' => true
		),*/
		'Swift Performance Lite' => array(
			'versions' => 'all',
			'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts. Sites can elect to use a whitelisted WP caching plugin.'
		),
		'LiteSpeed Cache' => array(
			'versions' => 'all',
			'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts. Sites can elect to use a whitelisted WP caching plugin.'
		),
		'WP Fastest Cache' => array(
			'versions' => 'all',
			'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts.'
		),
		'Autoptimize' => array(
			'versions' => 'all',
			'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts.'
		),
		'WP-Optimize - Clean, Compress, Cache' => array(
			'versions' => 'all',
			'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts. Sites can elect to use a whitelisted WP caching plugin.'
		),
		'SG Optimizer' => array(
			'versions' => 'all',
			'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts. Sites can elect to use a whitelisted WP caching plugin.'
		),
	);

	// list of known compatible plugins
	static $whitelisted_plugins = array(
		// 'W3 Total Cache' => array(
		// 	'versions' => 'all',
		// 	'message' => 'Ezoic\'s Leap optimization features may require that these plugins be turned off or that all minification, caching, or "speed" optimizations are disabled to prevent conflicts. Leap optimally replaces the functionality of these plugins as it relates to site speed.'
		// ),
		// 'WP Super Cache' => array(
		// 	'versions' => 'all',
		// 	'message' => 'Ezoic\'s Leap optimization features may require that these plugins be turned off or that all minification, caching, or "speed" optimizations are disabled to prevent conflicts. Leap optimally replaces the functionality of these plugins as it relates to site speed.'
		// ),
		// 'WP Rocket' => array(
		// 	'versions' => 'all',
		// 	'message' => 'Ezoic\'s Leap optimization features may require that these plugins be turned off or that all minification, caching, or "speed" optimizations are disabled to prevent conflicts. Leap optimally replaces the functionality of these plugins as it relates to site speed.'
		// ),
	);

	/**
	 * Get plugins that are known to be NOT compatible with Ezoic.
	 *
	 * @param $activation
	 *
	 * @return array
	 */
	public static function get_active_incompatible_plugins($activation = false)
	{
		$active_plugins       = self::get_active_plugins();
		$incompatible_plugins = array();

		// incompatible with wordpress integration
		if (Ezoic_Integration_Admin::is_wordpress_integrated()) {
			foreach ($active_plugins as $filename => $plugin) {
				if (self::is_in_plugins_list($plugin, self::$known_incompatible_plugins)) {
					if (
						$activation
						&& isset(self::$known_incompatible_plugins[$plugin['name']]['allow_install'])
						&& self::$known_incompatible_plugins[$plugin['name']]['allow_install'] == true
					) {
						// skip activation wp_die()
						continue;
					}
					$plugin['message']  = self::$known_incompatible_plugins[$plugin['name']]['message'];
					$plugin['filename'] = $filename;
					array_push($incompatible_plugins, $plugin);
				}
			}
		}

		// incompatible with any integration type
		foreach ($active_plugins as $filename => $plugin) {
			if (self::is_in_plugins_list($plugin, self::$all_incompatible_plugins)) {
				$plugin['message']  = self::$all_incompatible_plugins[$plugin['name']]['message'];
				$plugin['filename'] = $filename;
				array_push($incompatible_plugins, $plugin);
			}
		}
		return $incompatible_plugins;
	}

	/**
	 * Get plugins that are known to be compatible with Ezoic but can be replaced by another Ezoic product.
	 * @return array
	 */
	public static function get_compatible_plugins_with_recommendations()
	{
		$active_plugins = self::get_active_plugins();
		$plugins = array();
		foreach ($active_plugins as $filename => $plugin) {
			if ($plugin['name'] == EZOIC__PLUGIN_NAME || self::is_in_plugins_list($plugin, self::$known_incompatible_plugins)) {
				continue;
			}
			if (self::is_in_plugins_list($plugin, self::$whitelisted_plugins)) {
				$plugin['message'] = self::$whitelisted_plugins[$plugin['name']]['message'];
				array_push($plugins, $plugin);
			}
		}
		return $plugins;
	}

	public static function get_active_plugins()
	{
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();
		$active_plugins = get_option('active_plugins');
		$plugins = array();
		foreach ($all_plugins as $key => $value) {
			if (in_array($key, $active_plugins)) {
				$plugins[$key] = array(
					'name'    => $value['Name'],
					'version' => $value['Version'],
				);
			}
		}

		return $plugins;
	}

	private static function is_in_plugins_list($plugin, $plugins_list)
	{
		foreach ($plugins_list as $name => $info) {
			$versions = $info['versions'];
			if ($plugin['name'] == $name) {
				if ($versions == 'all' || in_array($plugin['version'], $versions)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get activation or deactivation link of a plugin
	 *
	 * @param $plugin
	 * @param string $action
	 *
	 * @return string
	 */
	public static function plugin_action_url($plugin, $action = 'deactivate')
	{
		if (strpos($plugin, '/')) {
			$plugin = str_replace('\/', '%2F', $plugin);
		}
		$url = sprintf(admin_url('plugins.php?action=' . $action . '&plugin=%s&plugin_status=all&paged=1&s'), $plugin);
		$_REQUEST['plugin'] = $plugin;
		$url = wp_nonce_url($url, $action . '-plugin_' . $plugin);
		return $url;
	}
}
