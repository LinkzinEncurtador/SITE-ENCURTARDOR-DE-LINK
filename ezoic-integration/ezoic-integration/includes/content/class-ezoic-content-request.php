<?php

namespace Ezoic_Namespace;

/**
 * Utility class for requests out to Ezoic CMS system
 */
class Ezoic_Content_Request {
	// Returns HTTP Protocol used by the server
	// Used in determine_base_url() to set current domain w/ protocol
	public static function get_http_protocol() {
		if (isset($_SERVER['HTTPS']) &&
			($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
			isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
			$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}
		return $protocol;
	}

	/**
	 * @return string FOR DEVELOPMENT: protocol, host and port. e.g. http://localhost:1337
	 * Finds the host. Because the Ezoic CMS and the Wordpress site should have the same
	 * domain name, we can use whatever is currently in use here to reach our Ezoic CMS server
	 * FOR PRODUCTION, it just takes the baseURL and appends /ez-json
	 */
	public static function determine_base_url() {
		$base_url = '';
		if ( isset($_SERVER['SERVER_NAME']) ) {
			$host = $_SERVER['SERVER_NAME'];
			$base_url = self::get_http_protocol() . $host . '/ez-json';
		} else {
			$referer_array = explode( ':', $_SERVER['HTTP_REFERER'] );
			$base_url_array = array( $referer_array[0], $referer_array[1] );
			$exploded_base = explode( '/', $base_url_array[1] );
			$base_string = '//' . $exploded_base[2];
			$base_url = $base_url_array[0] . ':' . $base_string . '/ez-json';
		}
		return $base_url;
	}

	// Returns domain name set on server w/o protocol
	public static function find_host( ) {
		if ( $_SERVER['SERVER_NAME'] ) {
			return $_SERVER['SERVER_NAME'];
		}
		return "";
	}

	public static function send_backend_request( $url_path, $request ) {
		$backend_url = "https://content-backend.ezoic.com" . $url_path;

		$ezoic_auth = new Ezoic_Auth();
		$token = $ezoic_auth->get_token();
		if ( ! $token ) {
			\error_log( "Unable to get authorization token for CMS live sync. Not sending request" );
			return "";
		} else {
			$token = "Bearer " . $token;
		}

		$request["headers"]["Authorization"] = $token;

		$response = wp_remote_post( $backend_url, $request );
		$status_code = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
  		$response_body = wp_remote_retrieve_body( $response );

		// Log request if non-200 status received
		if ( $status_code == '' ) {
			// Log entire response object if the response does not contain the status code
			// This likely means the connction was refused
			\error_log( '[CMS] ' . $backend_url . " - " . $response->get_error_message() );
		} else if ( $status_code != 200 ) {
			\error_log( "[CMS] " . $status_code . ": " . $backend_url . " --- " . print_r( $request, true ) );
		}

		if ( is_wp_error( $response ) ) {
			return "";
		}

		return $response_body;
	}

	public static function send_request( $url_path, $request ) {
		$cms_url = self::determine_base_url() . $url_path;
		$ezoic_auth = new Ezoic_Auth();
		$token = $ezoic_auth->get_token();
		if ( ! $token ) {
			\error_log( "Unable to get authorization token for CMS live sync. Not sending request" );
			return;
		}
		$request["headers"]["X-Ezoic-CMS-WP-Auth"] = "Bearer " . $token;

		$response = wp_remote_post( $cms_url, $request );
		$status_code = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Log request if non-200 status received
		if ( $status_code == '' ) {
			// Log entire response object if the response does not contain the status code
			// This likely means the connction was refused
			\error_log( '[CMS] ' . $cms_url . " - " . $response->get_error_message() );
		} else if ( $status_code != 200 ) {
			\error_log( "[CMS] " . $status_code . ": " . $cms_url . " --- " . print_r( $request, true ) );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new \WP_REST_Response(
			array(
				'status' => $status_code,
				'response' => $response_message,
				'body' => $response_body
			)
		);
	}

	/**
	 * CMS SYNC Requests
	 */
	public static function send_sync_options( $options ) {
		global $wp;
		$domain = home_url( $wp->request );
		$domain = wp_parse_url( $domain )['host'];

		$request_params = array(
			'domain'				=> $domain,
			'site_url'			=> get_bloginfo( 'wpurl' ),
			'home_url'			=> get_bloginfo( 'url' ),
		);

		$request_params = array(
			'payload' => array_merge( $request_params, $options )
		);

		$request = array(
			'timeout' => 30,
			'method'  => 'POST',
			'body'    => json_encode( $request_params, JSON_UNESCAPED_SLASHES ),
			'headers' => array(
				'Content-Type'			=> 'application/json',
				'X-From-Req'			=> 'wp',
				'X-Ez-CMS-API'			=> 'true',
				'X-Ezoic-Import'		=> 'true',
			),
		);

		return self::send_backend_request( '/api/v1/workers/site/options/update', $request );
	}

	public static function send_sync_theme( $theme_urls ) {
		$request = array(
			'timeout' => 30,
			'method'  => 'POST',
			'body'    => json_encode( $theme_urls, JSON_UNESCAPED_SLASHES ),
			'headers' => array(
				'Content-Type'		=> 'application/json',
				'X-From-Req' 		=> 'wp'
			),
		);

		return self::send_request( '/template', $request );
	}

	public static function send_sync_linklists( $linklists ) {
		$body = json_encode($linklists, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
		if ( !$body || $body == "[]" ) {
			error_log( "[CMS] Empty linklist; Not sending linklist request" );
			return;
		}

		$request = array(
			"method" => 'POST',
			"headers" => array(
				"Content-Type" 	  => "application/json",
				'X-From-Req'	  => 'wp',
        			'X-Ezoic-Import'  => 'true',
				'X-Ez-CMS-API'    => 'true',
			),
			"body" => $body,
		);

		return self::send_request( '/linklists', $request );
	}

	/**
	 * CMS IMPORT Requests
	 */
	public static function send_export_status( $status, $module ) {
		// Send alert to CMS
		$payload = array(
			'domain' 	=> self::find_host(),
			'status' 	=> $status,
			'type'		=> $module . " Import",
			'source' 	=> 'wordpress'
		);

		$request = array(
			'timeout'	=> 30,
			'method'	=> 'POST',
			'body'		=> json_encode( $payload ),
			'headers'	=> array(
				'Content-Type' 		=> 'application/json',
				'X-From-Req'		=> 'wp',
				'X-Ezoic-Import' 	=> 'true',
				'X-Ez-CMS-API'		=> 'true',
			),
		);

		return self::send_backend_request( '/api/v1/import/status', $request );
	}

	public static function send_export_alert( $message, $module ) {
		// Send alert to CMS
		$payload = array(
			'domain' 	=> self::find_host(),
			'message' 	=> $message,
			'source'	=> 'wordpress',
			'type' 		=> $module . " Import",
			'status'	=> 'FAILED'
		);

		$request = array(
			'timeout'	=> 30,
			'method'	=> 'POST',
			'body'		=> json_encode( $payload ),
			'headers'	=> array(
				'Content-Type' 		=> 'application/json',
				'X-From-Req'		=> 'wp',
				'X-Ezoic-Import' 	=> 'true',
				'X-Ez-CMS-API'		=> 'true',
			),
		);

		return self::send_backend_request( '/api/v1/import/alert', $request );
	}

	public static function notify_asset_upload_complete( $tenant) {
		$request = array(
			'timeout'	=> 30,
			'method'	=> 'POST',
			'headers'	=> array(
				'X-From-Req'		=> 'wp',
				'X-Ezoic-Import' 	=> 'true',
			),
		);

		return self::send_backend_request( "/api/v1/tenants/$tenant/workers/site/assets/startimport", $request );
	}

	public static function send_file_upload( $upload_url, $file_path, $filename, $auth_token ) {
		if ( !$auth_token ) {
			\error_log("Empty auth token");
		}

		$curl = curl_init();
		if ( !$curl ) {
			return "cURL req to import server could not be initialized";
		}

		$options_set = curl_setopt_array( $curl, array(
			CURLOPT_URL => $upload_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			// Only allow HTTPS - Must specify https as protocol in URL
			CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
			// Use HTTP2 over TLS
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
			CURLOPT_POST => 1,
			// Sets content-type as multi-part form data
			CURLOPT_POSTFIELDS => array(
				'file' => new \CURLFILE(
					$file_path,
					"application/zip",
					$filename
				),
			),
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $auth_token,
				),
		));

		if ( !$options_set ) {
			return "cURL options could not be properly set";
		}

		$response = curl_exec( $curl );
		$resp_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		if ( !$response || $resp_code < 200 || $resp_code > 299 ) {
			if ( $resp_code && $response) {
				return "cURL execution to " . curl_getinfo( $curl, CURLINFO_EFFECTIVE_URL ) . " failed with code $resp_code: $response";
			}
			return "cURL execution failed: " . curl_error( $curl );
		}

		curl_close( $curl );
		return true;
	}
}
