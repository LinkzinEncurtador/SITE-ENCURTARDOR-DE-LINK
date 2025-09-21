<?php
namespace Ezoic_Namespace;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/public
 */

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
class Ezoic_Request_Data {

	private $req_headers;
	private $resp_headers;
	private $http_method;
	private $ez_request_url;
	private $ez_api_version;
	private $ez_wp_plugin_version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
		$this->req_headers = getallheaders();
		$resp_headers = headers_list();
		$this->resp_headers = $this->parse_response_headers($resp_headers);
		$this->http_method = $_SERVER['REQUEST_METHOD'];
		$this->ez_request_url = $this->get_ezoic_server_address();

		if( defined('EZOIC_API_VERSION') ) {
			$this->ez_api_version = EZOIC_API_VERSION;
		} else {
			$this->ez_api_version = '';
		}

		if ( defined( 'EZOIC_INTEGRATION_VERSION' ) ) {
			$this->ez_wp_plugin_version = EZOIC_INTEGRATION_VERSION;
		} else {
			$this->ez_wp_plugin_version = '1.0.0';
		}
	}

	private function parse_response_headers( $resp_headers ) {
		$modified_headers = array();
		if( is_array($resp_headers) ) {
			foreach($resp_headers as $key => $header) {
				list($header_name, $header_value) = explode(":", $header, 2);
				$modified_headers[$header_name] = $header_value;
			}
		}

		return $modified_headers;
	}

	public function get_content_response_from_ezoic( $final_content ) {
		$cache_key = md5($final_content);
		//Create proper request data structure
		$request = $this->get_ezoic_request($cache_key);

		//Attempt to retrieve cached content
		$response = $this->get_cached_content_ezoic_response( $request );

		//Only upload non cached data on bad cache response and no wordpress error
		if( !is_wp_error($response) && $this->non_valid_cached_content($response) ) {
			//Send content to ezoic and return back altered content
			$response = $this->get_non_cached_content_ezoic_response($final_content, $request);
		}

		return $response;
	}

	private function get_ezoic_server_address() {
		return EZOIC_GATEWAY_URL . "/wp/data.go";
	}

	private function get_cached_content_ezoic_response( $request ) {
		$request['body']['request_type'] = 'cache_only';
		$result = wp_remote_post($this->ez_request_url, $request);

		return $result;
	}

	private function get_non_cached_content_ezoic_response( $final, $request ) {
		//Set content for non cached response
		$request['body']['content'] = $final;
		$request['body']['request_type'] = 'with_content';

		$result = wp_remote_post($this->ez_request_url, $request);

		return $result;
	}

	private function non_valid_cached_content( $result ) {
		return ($result['response']['code'] == 404 || $result['response']['code'] == 400);
	}

	private function get_ezoic_request( $cache_key ) {
		global $wp;
		//Form current url
		$home_url = home_url( $wp->request );
		if (substr($home_url,-1) != '/' && function_exists('should_current_path_end_in_slash') && should_current_path_end_in_slash()) {
			$home_url = $home_url . '/';
		}
		$current_url = add_query_arg( $_SERVER['QUERY_STRING'], '', $home_url );

		$request_params = array(
			'cache_key' => $cache_key,
			'action' => 'get-index-series',
			'content_url' => $current_url,
			'request_headers' => $this->req_headers,
			'response_headers' => $this->resp_headers,
			'http_method' => $this->http_method,
			'ezoic_api_version' => $this->ez_api_version,
			'ezoic_wp_integration_version' => $this->ez_wp_plugin_version,
		);

		if(!empty($_GET)){
			$request_params = array_merge($request_params, $_GET);
		}

		unset($this->req_headers["Content-Length"]);
		$this->req_headers['X-Wordpress-Integration'] = 'true';

		//Get IP for X-Forwarded-For
		$ip = $this->get_client_ip();

		$request = array(
			'timeout' => 5,
			'body' => $request_params,
			'headers' => array('X-Wordpress-Integration' => 'true', 'X-Forwarded-For' => $ip, 'Expect' => ''),
			'cookies' => $this->build_cookies_for_request(),
		);

		return $request;
	}

	private function get_client_ip() {
		$ip = "";

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
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
				$cookies[] = [ 'name' => $name, 'value' => $value ];
			}
		}

		return array_map( function ( $cookie ) {
			return new \WP_Http_Cookie( $cookie );
		}, $cookies );
	}

}
