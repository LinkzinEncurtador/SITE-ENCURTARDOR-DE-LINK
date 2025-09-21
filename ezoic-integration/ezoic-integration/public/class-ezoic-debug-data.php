<?php
namespace Ezoic_Namespace;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/public
 * @author     Ezoic Inc. <support@ezoic.com>
 */
class Ezoic_Debug_Data {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
	}

	public function get_debug_information() {
		global $wp;

		$home_url = home_url( $wp->request );
		if (substr($home_url,-1) != '/' && function_exists('should_current_path_end_in_slash') && should_current_path_end_in_slash()) {
			$home_url = $home_url . '/';
		}
		$buffer_num = $this->startBufferLevel;
		$current_url = add_query_arg( $_SERVER['QUERY_STRING'], '', $home_url );

		$data = array();

		if(function_exists('get_plugins')) {
			$data['plugins'] = get_plugins();
		}

		if(function_exists('phpversion')) {
			$data['php_version'] = phpversion();
		}

		$debug_content = array("Home URL" => $home_url, "Ez Buffer level" => $buffer_num, "Current URL" => $current_url);
		$debug_content = array_merge($debug_content, $data);
		return "<!-- " . print_r($debug_content, true) . "-->";
	}
}
