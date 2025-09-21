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
class Ezoic_Response_Data {
	private $ez_headers;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
	}

	public function handle_ezoic_response( $final , $response ) {
		$success_codes = [200, 202];

		if( !is_wp_error($response) && isset($response['response']) &&
		    isset($response['response']['code']) &&
		    in_array($response['response']['code'], $success_codes) ) {
			//Get headers and add them to the response
			$this->ez_headers = wp_remote_retrieve_headers( $response );
			$this->alter_response_headers();

			//Replace final content with ezoic content
			if( is_array($response) && isset($response['body']) ) {
				$final = $response['body'];
			} else {
				$final = $response;
			}

		} else {
			if( is_wp_error($response) ) {
				$final = $final . "<!-- " . $response->get_error_message() . " -->";
			} elseif (isset($response['response']) &&
			          isset($response['response']['code']) &&
			          !in_array($response['response']['code'], $success_codes)) {
				$final = $final . "<!-- " . $response['response']['code'] . " -->";
			}
		}

		return $final;
	}

	private function alter_response_headers() {
		if( !is_null($this->ez_headers) && !headers_sent() ){

			$headers = array();

			if( is_array($this->ez_headers) ) {
				$headers = $this->ez_headers;
			} else {
				$headers = $this->ez_headers->getAll();
			}

			foreach( $headers as $key => $header ) {
				//Avoid content encoding as this will cause rendering problems
				if( !$this->is_bad_header($key) ) {
					if (strtolower($key) === 'set-cookie') {
						Ezoic_Integration_Request_Utils::handle_cookies_from_header_value($header);
						continue;
					}
					$this->handle_header_object($key, $header);
				}
			}
		}
	}

	private function is_bad_header($key) {
		return ($key == 'Content-Encoding'
			|| $key == 'content-encoding'
			|| $key == 'Transfer-Encoding'
			|| $key == 'transfer-encoding'
			|| $key == 'Content-Length'
			|| $key == 'content-length'
			|| $key == 'Accept-Ranges'
			|| $key == 'accept-ranges'
			|| $key == 'Status'
			|| $key == 'status');
	}

	private function handle_header_object($key, $header) {
		if( is_array($header) ) {
			foreach( $header as $subheader) {
				header($key . ': ' . $subheader, false);
			}
		} else {
			header($key . ': ' . $header);
		}
	}

}
