<?php
namespace Ezoic_Namespace;

//Request utils are meant to handle pieces of data that can be shared between any
//Type of request being sent to ezoic. Eg. Data that can be accessed via standard
//php functions and calls.
class Ezoic_Integration_Request_Utils {

	public static function parse_response_headers($resp_headers) {
		$modified_headers = array();
		if (is_array($resp_headers)) {
			foreach ($resp_headers as $header) {
				if (strpos($header, ':') !== false) {
					list($headername, $headervalue) = explode(':', $header, 2);
					$modified_headers[trim($headername)] = trim($headervalue);
				}
			}
		}
		return $modified_headers;
	}

	public static function make_curl_request($settings, $curl_init = null) {
		if (!function_exists('curl_exec')) {
			if (function_exists('wp_remote_request') && isset($settings[CURLOPT_URL])) {
				return self::make_wp_remote_request($settings);
			} else {
				return array("body" => '', "headers" => array(), "status_code" => 0, "error" => 'curl_exec is disabled and wp_remote_request is not available.');
			}
		}

		$curl = !empty($curl_init) ? $curl_init : curl_init();
		curl_setopt_array($curl, $settings);

		$result = curl_exec($curl);
		if ($result === false) {
			$error = curl_error($curl);
			$httpCode = 0;
			$headers = array();
		} else {
			$error = '';
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$headerContent = substr($result, 0, $headerSize);
			$headers = self::parse_response_headers(explode("\r\n", $headerContent));
			$result = substr($result, $headerSize);
		}

		if (empty($curl_init)) {
			curl_close($curl);
		}

		return array("body" => $result, "headers" => $headers, "status_code" => $httpCode, "error" => $error);
	}

	private static function make_wp_remote_request($settings) {
		$headersArray = isset($settings[CURLOPT_HTTPHEADER]) ? $settings[CURLOPT_HTTPHEADER] : array();
		$headers = self::parse_response_headers($headersArray);

		// Initialize default arguments for wp_remote_request
		$args = array(
			'headers'     => $headers,
			'method'      => 'GET',  // Default to GET
			'data_format' => 'body',
		);

		// Check if this is a POST request (indicated by presence of CURLOPT_POSTFIELDS)
		if (isset($settings[CURLOPT_POSTFIELDS])) {
			$args['method'] = 'POST';  // Change method to POST
			$args['body'] = $settings[CURLOPT_POSTFIELDS];  // Set the post fields
		}

		// Override the method if explicitly set in CURLOPT_CUSTOMREQUEST
		if (isset($settings[CURLOPT_CUSTOMREQUEST])) {
			$args['method'] = $settings[CURLOPT_CUSTOMREQUEST];
		}

		// Make the request
		$response = wp_remote_request($settings[CURLOPT_URL], $args);

		if (is_wp_error($response)) {
			return array("body" => '', "headers" => array(), "status_code" => 0, "error" => $response->get_error_message());
		}

		return array(
			"body" => wp_remote_retrieve_body($response),
			"headers" => wp_remote_retrieve_headers($response),
			"status_code" => wp_remote_retrieve_response_code($response),
			"error" => ''
		);
	}

	public static function get_ezoic_server_address() {
		return EZOIC_GATEWAY_URL . "/wp/data.go";
	}

	public static function get_client_ip() {
		$ip = "";

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is passed from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

	public static function get_request_base_data() {
		$request_base_data = array();
		$request_base_data["request_headers"] = getallheaders();
		$resp_headers = headers_list();
		$request_base_data["response_headers"] = Ezoic_Integration_Request_Utils::parse_response_headers($resp_headers);

		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$request_base_data["http_method"] = $_SERVER['REQUEST_METHOD'];
		} else {
			$request_base_data["http_method"] = 'GET';
		}

		$request_base_data["ezoic_request_url"] = Ezoic_Integration_Request_Utils::get_ezoic_server_address();
		$request_base_data["client_ip"] = Ezoic_Integration_Request_Utils::get_client_ip();

		if( defined('EZOIC_API_VERSION') ) {
			$request_base_data["ezoic_api_version"] = EZOIC_API_VERSION;
		} else {
			$request_base_data["ezoic_api_version"] = '';
		}

		if ( defined( 'EZOIC_INTEGRATION_VERSION' ) ) {
			$request_base_data["ezoic_wp_plugin_version"] = EZOIC_INTEGRATION_VERSION;
		} else {
			$request_base_data["ezoic_wp_plugin_version"] = '?';
		}

		return $request_base_data;
	}

	/**
	 * Fetches the domain and TLD from the current request URL
	 *
	 * @access private
	 * @return string
	 */
	public static function get_domain() {
		$domain = "";
		if ( function_exists( 'site_url' ) ) {
			$domain = parse_url( site_url(), PHP_URL_HOST );
		} else {
			// todo: find a backup domain parser
		}

		return $domain;
	}

	/**
	 * Check if is an AMP request
	 *
	 * @return bool
	 */
	public static function is_amp_endpoint() {
		global $wp;

		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
			return true;
		}

		if ( isset( $wp ) ) {
			if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Handle cookies from Set-Cookie header value(s)
	 * 
	 * @param string|array $header_value The header value(s) from the Set-Cookie header
	 * @return void
	 */
	public static function handle_cookies_from_header_value($header_value) {
		$cookies = is_array($header_value) ? $header_value : array($header_value);
		
		// If no specific allowed cookies list is provided, we allow all cookies
		// Otherwise, we use the provided list of allowed cookies (keys should be lowercase)
		$allowed_cookies = array(
			'ez-consent-humix' => true,
			'humvtoken' => true
		);
		
		foreach ($cookies as $cookie_str) {
			// Parse the cookie string
			$cookie_parts = explode(';', $cookie_str);
			$main_part = array_shift($cookie_parts);
			list($name, $value) = explode('=', $main_part, 2);

			// Skip if this cookie is not in the allowed list
			if (!isset($allowed_cookies[strtolower($name)])) {
				continue;
			}

			// Extract cookie attributes
			$cookie_params = array(
				'expires' => 0,
				'path' => '/',
				'domain' => '',
				'secure' => false,
				'httponly' => false
			);

			foreach ($cookie_parts as $part) {
				$part = trim($part);
				if (strtolower($part) === 'secure') {
					$cookie_params['secure'] = true;
				} elseif (strtolower($part) === 'httponly') {
					$cookie_params['httponly'] = true;
				} else {
					$attr_parts = explode('=', $part, 2);
					if (count($attr_parts) === 2) {
						$attr_name = strtolower(trim($attr_parts[0]));
						$attr_value = trim($attr_parts[1]);
						if ($attr_name === 'expires') {
							$cookie_params['expires'] = strtotime($attr_value);
						} elseif ($attr_name === 'path') {
							$cookie_params['path'] = $attr_value;
						} elseif ($attr_name === 'domain') {
							$cookie_params['domain'] = $attr_value;
						}
					}
				}
			}

			// Set the cookie
			setcookie(
				$name,
				$value,
				$cookie_params['expires'],
				$cookie_params['path'],
				$cookie_params['domain'],
				$cookie_params['secure'],
				$cookie_params['httponly']
			);
		}
	}
}
