<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-filter.php');
require_once( dirname(__FILE__) . '/class-ezoic-integration-request-utils.php');

class Ezoic_Integration_Cache_Filter implements iEzoic_Integration_Filter {
	private $is_ez_debug;
	private $headers;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param $headers
	 * @param $is_ez_debug
	 *
	 * @since    1.0.0
	 */
	public function __construct($headers, $is_ez_debug) {
		$this->headers = $headers;
		$this->is_ez_debug = $is_ez_debug;
	}

	public function we_should_return_orig() {

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		if ( function_exists( 'get_option' ) && function_exists( 'wp_cache_get' ) ) {
			$ezoic_options = \get_option( 'ezoic_integration_options' );
			if ( isset( $ezoic_options['disable_wp_integration'] ) && $ezoic_options['disable_wp_integration'] == true ) {
				return true;
			}
		}

		return isset( $this->headers['x-middleton'] )
		       || isset( $this->headers['X-Middleton'] )
		       || $this->is_special_content_type()
		       || $this->is_special_route()
		       || $_SERVER['REQUEST_METHOD'] === 'POST'
		       || $_SERVER['REQUEST_METHOD'] === 'PUT'
		       || $_SERVER['REQUEST_METHOD'] === 'DELETE'
		       || $this->is_ez_debug;
	}

	private function is_special_content_type() {
		if(isset($this->headers['Accept']) ) {
			$content_type = $this->headers['Accept'];

			if( is_array($content_type) ) {
				foreach( $content_type as $name => $value ) {
					if ( in_array( $value, array( "application/json", "application/javascript", "text/javascript" ) ) ) {
						return true;
					}
				}
			}
		}

		$resp_headers = headers_list();
		$resp_headers = Ezoic_Integration_Request_Utils::parse_response_headers($resp_headers);
		$header_response = $this->handle_content_type_header($resp_headers);

		return $header_response;
	}

	private function handle_content_type_header($response_headers) {
		$header_text = "";
		if(isset($response_headers["Content-type"])) {
			$header_text = "Content-type";
		} else if (isset($response_headers["Content-Type"])) {
			$header_text = "Content-Type";
		} else if (isset($response_headers["content-type"])) {
			$header_text = "content-type";
		}

		if($header_text == "") {
			return false;
		}

		$content_type = $response_headers[$header_text];
		$parsed_header = explode(";", $content_type);
		if( trim($parsed_header[0]) != "text/html" ) {
			return true;
		}

		return false;
	}

	private function is_special_route() {
		global $wp;

		// relative current URI:
		if ( isset( $wp ) ) {
			$current_url = add_query_arg( null, null );
		} else {
			$current_url = $_SERVER['REQUEST_URI'];
		}

		if( preg_match('/(.*\/wp\/v2\/.*)/', $current_url) ) {
			return true;
		}

		if( preg_match('/(.*wp-login.*)/', $current_url) ) {
			return true;
		}

		if( preg_match('/(.*wp-admin.*)/', $current_url) ) {
			return true;
		}

		/*if( preg_match('/(.*wp-content.*)/', $current_url) ) {
		return true;
		}*/

		if ( preg_match('/(.*wp-json.*)/', $current_url) ) {
			return true;
		}

		if ( preg_match('/sitemap(.*)\.xml/', $current_url) ) {
			return true;
		}

		return false;
	}
}
