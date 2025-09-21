<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/class-ezoic-integration-request-utils.php');
require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-request.php');

class Ezoic_Integration_CURL_Request implements iEzoic_Integration_Request {
	private $request_data;

	public function __construct() {
		$this->request_data = Ezoic_Integration_Request_Utils::get_request_base_data();
	}

	public function get_content_response_from_ezoic( $final_content, $available_templates = array() ) {
		return $this->request_data_from_ezoic($final_content, $available_templates);
	}

	public function request_data_from_ezoic( $final_content, $available_templates ) {
		$timeout = 5;

		if ( isset( $_REQUEST['ez_timeout'] ) && \is_numeric( $_REQUEST['ez_timeout'] ) ) {
			$timeout = intval($_REQUEST['ez_timeout']);
		}

		$cache_key = md5($final_content);

		if ( function_exists('is_ssl') && is_ssl() ) {
			$this->request_data["request_headers"]["X-Forwarded-Proto"] = "https";
		}

		$http_status_code = 200;

		if (function_exists('http_response_code')) {
			$http_status_code = http_response_code();
		}

		$request_params = array(
			'cache_key' => $cache_key,
			'action' => 'get-index-series',
			'status_code' => $http_status_code,
			'content_url' => $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
			'request_headers' => $this->request_data["request_headers"],
			'response_headers' => $this->request_data["response_headers"],
			'http_method' => $this->request_data["http_method"],
			'ezoic_api_version' => $this->request_data["ezoic_api_version"],
			'ezoic_wp_integration_version' => $this->request_data["ezoic_wp_plugin_version"],
			'ezoic_wp_integration_request_type' => 'curl',
			'request_type' => 'cache_only',
			'available_templates' => implode(',', $available_templates),
			'ezoic_wp_caching' => defined('EZOIC_CACHE') && EZOIC_CACHE
		);

		if(!empty($_GET)){
			$request_params = array_merge($request_params, $_GET);
		}

		$headers = array(
			'X-Wordpress-Integration: true',
			'X-Forwarded-For: ' . $this->request_data["client_ip"],
			'Content-Type: application/x-www-form-urlencoded',
			'Expect:',
			'X-From-Req: curl',
		);

		$cookies = "";
		foreach( $_COOKIE as $key => $value ) {
			$cookies = $cookies.$key."=".$value.";";
		}
		$settings = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_COOKIE => $cookies,
			CURLOPT_URL => $this->request_data["ezoic_request_url"],
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POST => true,
			CURLOPT_HEADER => true,
			CURLOPT_POSTFIELDS => http_build_query($request_params),
			CURLOPT_USERAGENT => ! empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
		);

		if ( function_exists( 'get_option' ) ) {
			$ezoic_options = \get_option( 'ezoic_integration_options' );
			if ( $ezoic_options['verify_ssl'] == false ) {
				$settings[ CURLOPT_SSL_VERIFYPEER ] = false;
				$settings[ CURLOPT_SSL_VERIFYHOST ] = false;
			}
		}

		$curl = curl_init();

		$result = Ezoic_Integration_Request_Utils::make_curl_request( $settings, $curl );

		if ( $this->non_valid_cached_content( $result ) || $result["error"] != "" ) {
			//Set content for non cached response
			$request_params['content']      = $final_content;
			$request_params['request_type'] = 'with_content';
			$settings[ CURLOPT_POSTFIELDS ] = http_build_query( $request_params );
			$result = Ezoic_Integration_Request_Utils::make_curl_request( $settings, $curl );
		}

		curl_close($curl);

		return $result;
	}

	private function non_valid_cached_content( $result ) {
		return ($result["status_code"] == 404 || $result["status_code"] == 400);
	}
}
