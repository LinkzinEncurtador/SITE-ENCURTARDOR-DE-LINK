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
class Ezoic_Request_Filter {

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

		return is_admin()
		       || ( isset( $ezoic_options['disable_wp_integration'] ) && $ezoic_options['disable_wp_integration'] == true )
		       || isset( $this->headers['x-middleton'] )
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
			$contentType = $this->headers['Accept'];

			if( is_array($contentType) ) {
				foreach( $contentType as $name => $value ) {
					if ( in_array( $value, array( "application/json", "application/javascript", "text/javascript" ) ) ) {
						return true;
					}
				}
			}
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

		return false;
	}

}
