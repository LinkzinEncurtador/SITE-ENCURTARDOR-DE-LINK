<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/class-ezoic-integration-request-utils.php');
require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-request.php');

class Ezoic_Integration_WP_Request implements iEzoic_Integration_Request {
	private $request_data;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
		$this->request_data = Ezoic_Integration_Request_Utils::get_request_base_data();
	}

	public function get_content_response_from_ezoic( $final_content, $available_templates = array() ) {
		$cache_key = md5( $final_content );
		//Create proper request data structure
		$request = $this->get_ezoic_request ($cache_key,  $available_templates );

		//Attempt to retrieve cached content
		$response = $this->get_cached_content_ezoic_response( $request );

		//Only upload non cached data on bad cache response and no wordpress error
		if( !is_wp_error($response) && $this->non_valid_cached_content($response) ) {
			//Send content to ezoic and return back altered content
			$response = $this->get_non_cached_content_ezoic_response( $final_content, $request );
		}

		return $response;
	}

	private function get_cached_content_ezoic_response( $request ) {
		$request['body']['request_type'] = 'cache_only';
		$result = wp_remote_post(Ezoic_Integration_Request_Utils::get_ezoic_server_address(), $request);

		return $result;
	}

	private function get_non_cached_content_ezoic_response( $final, $request ) {
		//Set content for non cached response
		$request['body']['content'] = $final;
		$request['body']['request_type'] = 'with_content';
		$result = wp_remote_post(Ezoic_Integration_Request_Utils::get_ezoic_server_address(), $request);

		return $result;
	}

	private function non_valid_cached_content( $result ) {
		return ($result['response']['code'] == 404 || $result['response']['code'] == 400);
	}

	private function get_ezoic_request( $cache_key,  $available_templates ) {
		global $wp;
		if ( !isset( $_SERVER['QUERY_STRING'] ) ) {
			return;
		}


		//Form current url
		$home_url = home_url( $wp->request );
		if (substr($home_url,-1) != '/' && function_exists('should_current_path_end_in_slash') && should_current_path_end_in_slash()) {
			$home_url = $home_url . '/';
		}

		$current_url = add_query_arg( $_SERVER['QUERY_STRING'], '', $home_url );

		if (function_exists('is_ssl') && is_ssl()) {
			$this->request_data["request_headers"]["X-Forwarded-Proto"] = "https";
		}

		$http_status_code = 200;

		if (function_exists('http_response_code')) {
			$http_status_code = http_response_code();
		}

		$timeout = 5;

		if ( isset( $_REQUEST['ez_timeout'] ) && \is_numeric($_REQUEST['ez_timeout']) ) {
			$timeout = intval( $_REQUEST['ez_timeout'] );
		}

		$request_params = array(
			'cache_key' => $cache_key,
			'action' => 'get-index-series',
			'status_code' => $http_status_code,
			'content_url' => $current_url,
			'request_headers' => $this->request_data["request_headers"],
			'response_headers' => $this->request_data["response_headers"],
			'http_method' => $this->request_data["http_method"],
			'ezoic_api_version' => $this->request_data["ezoic_api_version"],
			'ezoic_wp_integration_version' => $this->request_data["ezoic_wp_plugin_version"],
			'ezoic_wp_integration_request_type' => 'wp',
			'available_templates' => implode(',',  $available_templates),
			'ezoic_wp_caching' => defined('EZOIC_CACHE') && EZOIC_CACHE
		);

		if(!empty($_GET)){
			$request_params = array_merge($request_params, $_GET);
		}

		unset($this->request_data["request_headers"]["Content-Length"]);
		$this->request_data["request_headers"]['X-Wordpress-Integration'] = 'true';

		//Get IP for X-Forwarded-For
		$ip = $this->request_data["client_ip"];

		$request = array(
			'timeout' => $timeout,
			'body' => $request_params,
			'headers' => array('X-Wordpress-Integration' => 'true', 'X-Forwarded-For' => $ip, 'Expect' => '', 'X-From-Req' => 'wp'),
			'cookies' => $this->build_cookies_for_request(),
		);

		return $request;
	}

	private function build_cookies_for_request() {
		// Build proper cookies for WP remote post
		$cookies = [];

		foreach ( $_COOKIE as $name => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $arr_name => $arr_value ) {
					if ( is_array( $arr_value ) ) {
						$arr_value = json_encode( $arr_value );
					}
					$cookies[] = [ 'name' => $name . '[' . $arr_name . ']', 'value' => $arr_value ];
				}
			} else {
				$cookies[] = [ 'name' => $name, 'value' => strval( $value ) ];
			}
		}

		return array_map( function ( $cookie ) {
			return new \WP_Http_Cookie( $cookie );
		}, $cookies );
	}

}
