<?php

namespace Ezoic_Namespace;

/**
 * Helper class to handle authentication against the publisher backend
 */
class Ezoic_Integration_Authentication {
	const AUTH_ENDPOINT = EZOIC_URL . "/pub/v1/wordpressintegration/v1/auth";
	const VERIFY_ENDPOINT = EZOIC_URL . "/pub/v1/wordpressintegration/v1/verify";

	/**
	 * Registers the necessary callback endpoint for authentication
	 */
	public function register() {
		register_rest_route( 'ezoic/v1', 'verify', array(
			'methods'             => \WP_REST_SERVER::READABLE,
			'callback'            => array( $this, 'verify' ),
			'args'                => array(),
			'permission_callback' => '__return_true',
			'show_in_index'       => false
		) );
	}

	/**
	 * Fetches a token for use in calls to the backend
	 */
	public static function get_token($requestURL = "") {
		// Build request
		if ($requestURL == ""){
			$requestURL = self::AUTH_ENDPOINT;
		}

		// Expire token request after 5 seconds
		$expires = time() + 50;

		// Create token
		$token = self::generate_token( $expires );

		// Encode payload
		$payload = json_encode( array(
			'siteUrl'  => \site_url(),
			'token'    => $token,
			'callback' => \get_rest_url( null, 'ezoic/v1/verify' )
		) );

		// Fetch auth key
		$response = wp_remote_post( $requestURL, array(
			'headers' => array( 'Content-Type' => 'application/json; charset=utf8' ),
			'body'    => $payload,
			'timeout' => 5
		) );

		// If a token was obtained, return it
		if ( ! is_wp_error( $response ) ) {
			$responseBody = wp_remote_retrieve_body( $response );
			$parsed       = json_decode( $responseBody );

			if ( is_null( $parsed->data ) ) {
				error_log( 'Error communicating with auth endpoint: ' . $responseBody );
			} else {
				return $parsed->data;
			}
		}

		return "";
	}

	/**
	 * Verifies that a token issued by the plugin is valid
	 */
	public function verify( $request_data ) {
		// Decode authentication payload
		$token = $request_data->get_header( 'X-AUTH-TOKEN' );
		if ( !isset( $token ) ) {
			$response = new \WP_Error( 'invalid_token', 'Token not present', array( "status" => 400 ) );

			return $response;
		}

		$domain  = Ezoic_Integration_Request_Utils::get_domain();

		// Extract expiration
		$splitToken = explode( ':', $token );
		$expires    = (int) $splitToken[1];

		// Ensure the token has not timed out
		if ( time() > $expires ) {
			$response = new \WP_Error( 'invalid_token', 'Token has expired', array( "status" => 400 ) );

			return $response;
		}

		// Ensure token is valid
		$calculatedToken = $this->generate_token( $expires );
		if ( $calculatedToken != $token ) {
			$response = new \WP_Error( 'invalid_token', 'Token not valid', array( "status" => 400 ) );

			return $response;
		}

		// Signal that the token is valid
		return new \WP_REST_Response( array(
			'status'        => 200,
			'response'      => 'OK',
			'body_response' => 'token validated'
		) );
	}

	/**
	 * Generates a token hash for use in a callback from the backend for verification
	 */
	private static function generate_token( $expires ) {
		$domain = Ezoic_Integration_Request_Utils::get_domain();
		$token  = hash_hmac( 'sha256', $domain . ':' . $expires, AUTH_KEY ) . ':' . $expires;

		return $token;
	}

	/**
	 * Verify domain exists in Ezoic platform
	 *
	 * @return boolean
	 */
	public static function verify_domain($requestURL = "") {
		// Build request
		if ($requestURL == "") {
			$requestURL = self::VERIFY_ENDPOINT;
		}

		// Expire token request after 5 seconds
		$expires = time() + 50;

		// Create token
		$token = self::generate_token( $expires );

		// Encode payload
		$payload = json_encode( array(
			'siteUrl' => \site_url(),
			'token'   => $token,
		) );

		// Fetch auth key
		$response = wp_remote_post( $requestURL, array(
			'headers' => array( 'Content-Type' => 'application/json; charset=utf8' ),
			'body'    => $payload,
			'timeout' => 5
		) );

		// If a token was obtained, return it
		if ( ! is_wp_error( $response ) ) {
			$responseBody = wp_remote_retrieve_body( $response );
			$parsed       = json_decode( $responseBody );

			if ( is_null( $parsed->data ) ) {
				error_log( 'Error communicating with auth endpoint: ' . $responseBody );
			} else {
				return $parsed->data;
			}
		}

		return false;
	}
}
