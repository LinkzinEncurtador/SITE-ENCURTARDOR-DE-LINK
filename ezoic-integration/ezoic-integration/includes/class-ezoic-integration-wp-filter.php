<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-filter.php');
require_once( dirname(__FILE__) . '/class-ezoic-integration-request-utils.php');

/**
 * Class Ezoic_Integration_WP_Filter
 * @package Ezoic_Namespace
 */
class Ezoic_Integration_WP_Filter implements iEzoic_Integration_Filter {
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

		$ezoic_options = \get_option( 'ezoic_integration_options' );

		$request_method = 'NONE';
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$request_method = $_SERVER['REQUEST_METHOD'];
		}

		return is_admin()
		       || ( isset( $ezoic_options['disable_wp_integration'] ) && $ezoic_options['disable_wp_integration'] == true )
		       || isset( $this->headers['x-middleton'] )
		       || isset( $this->headers['X-Middleton'] )
		       || $this->is_special_content_type()
		       || $this->is_special_route()
		       || $request_method === 'POST'
		       || $request_method === 'PUT'
		       || $request_method === 'DELETE'
		       || $this->is_ez_debug
		       || $GLOBALS['EZOIC_CALL_COUNT'] > 1;
	}

	private function is_special_content_type() {
		if(isset($this->headers['Accept']) ) {
			$content_type = $this->headers['Accept'];

			if ( is_array( $content_type ) ) {
				foreach ( $content_type as $name => $value ) {
					if ( in_array( $value, array( "application/json", "application/javascript", "text/javascript" ) ) ) {
						return true;
					}
				}
			}
		}

		$resp_headers = headers_list();
		$resp_headers = Ezoic_Integration_Request_Utils::parse_response_headers( $resp_headers );
		$header_response = $this->handle_content_type_header( $resp_headers );
		if( $header_response === true ) {
			return $header_response;
		}

		$header_response = $this->is_file_transfer( $resp_headers );

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

	private function is_file_transfer($response_headers) {
		$header_text = "";
		if(isset($response_headers["Content-Description"])) {
			$header_text = "Content-Description";
		} else if (isset($response_headers["Content-description"])) {
			$header_text = "Content-description";
		} else if (isset($response_headers["content-description"])) {
			$header_text = "content-description";
		}

		if($header_text == "") {
			return false;
		}

		$content_type = $response_headers[$header_text];
		$parsed_header = explode(";", $content_type);
		if( trim($parsed_header[0]) == "File Transfer" ) {
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
