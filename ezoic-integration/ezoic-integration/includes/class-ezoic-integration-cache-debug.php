<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-debug.php');

class Ezoic_Integration_Cache_Debug implements iEzoic_Integration_Debug {
	public function get_debug_information() {
		$debug_data = array();

		$debug_data["config_file"] = $this->get_config_file_info();
		$debug_data["sub_htaccess"] = $this->get_low_level_htaccess();
		$debug_data["advanced_cache"] = $this->get_advanced_cache_file_info();
		$debug_data["main_htaccess"] = $this->get_top_level_htaccess();

		$request_params = array(
			//'cache_key' => $cache_key,
			'action' => 'get-index-series',
			'wp_debug_info' => print_r($debug_data,true),
			'content_url' => $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
		);

		if(!empty($_GET)){
			$request_params = array_merge($request_params, $_GET);
		}

		$settings = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => EZOIC_GATEWAY_URL . '/wp/data.go',
			CURLOPT_TIMEOUT => 5,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPHEADER => array(
				'X-Wordpress-Integration: true',
				'X-Forwarded-For: ' => Ezoic_Integration_Request_Utils::get_client_ip(),
				'Content-Type: application/x-www-form-urlencoded',
				'Expect:',
			),
			CURLOPT_POST => true,
			CURLOPT_HEADER => true,
			CURLOPT_POSTFIELDS => http_build_query($request_params)
		);

		Ezoic_Integration_Request_Utils::make_curl_request($settings);

		return "<!-- debug information was stored -->";
	}

	public function we_should_debug() {
		if( isset($_GET["ez_store_wp_debug"]) && $_GET["ez_store_wp_debug"] == "1" ) {
			return true;
		}

		return false;
	}

	private function get_config_file_info() {
		$config_file = dirname(__FILE__) . "/config/ezoic_config.json";
		$config_string = "";

		if( file_exists($config_file) && is_readable($config_file) ) {
			$cache_content = file_get_contents($config_file);
			$content = json_decode($cache_content, true);
			$config_string .= print_r($content, true);
		}

		return $config_string;
	}

	private function get_advanced_cache_file_info() {
		$current_dir = dirname(__FILE__);
		$directories = explode("/", $current_dir);
		//pop off three directories
		array_pop($directories);
		array_pop($directories);
		array_pop($directories);

		$wp_content_dir = implode("/", $directories);
		$advanced_cache_file = $wp_content_dir . "/advanced-cache.php";

		if (!file_exists($advanced_cache_file) || !is_readable($advanced_cache_file)) {
			return "Advanced Cache File does not exist ($advanced_cache_file)";
		}
		return file_get_contents($advanced_cache_file);
	}

	private function get_top_level_htaccess() {
		$current_dir = dirname(__FILE__);
		$directories = explode("/", $current_dir);
		//pop off four directories
		array_pop($directories);
		array_pop($directories);
		array_pop($directories);
		array_pop($directories);

		$wpDir = implode("/", $directories);
		$htaccess_file = $wpDir . "/.htaccess";

		if (!file_exists($htaccess_file) || !is_readable($htaccess_file)) {
			return "Top level htaccess does not exist ($htaccess_file)";
		}
		return file_get_contents($htaccess_file);
	}

	private function get_low_level_htaccess() {
		//Get path to cache folder and insert out htaccess file or modify current htaccess file
		$config_file = dirname(__FILE__) . "/config/ezoic_config.json";
		$content = file_get_contents($config_file);
		$config_content = json_decode($content, true);

		$htaccess_file = $config_content['cache_path'] . '.htaccess';
		if ( !file_exists( $htaccess_file ) || !is_readable( $htaccess_file ) ) {
			return "Low level htaccess does not exist (" . $htaccess_file . ")";
		}
		$content = file_get_contents( $htaccess_file, true );

		return $content;
	}
}
