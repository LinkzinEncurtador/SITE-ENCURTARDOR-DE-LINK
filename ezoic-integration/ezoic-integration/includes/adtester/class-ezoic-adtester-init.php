<?php

namespace Ezoic_Namespace;

/**
 * Helper class used to initialize placeholders upon plugin activation
 */
class Ezoic_AdTester_Init
{
	public function __construct() {
	}

	/**
	 * Initializes the default set of ad placeholders
	 */
	public function initialize( $adtester ) {
		$token = '';

		// Use auth key to send a request to initialize the domain
		$domain = Ezoic_Integration_Request_Utils::get_domain();

		$requestURL = Ezoic_AdTester::INIT_ENDPOINT . $domain;

		// Use API Key, if available
		if ( Ezoic_Cdn::ezoic_cdn_api_key() != null ) {
			$requestURL .= '&developerKey=' . Ezoic_Cdn::ezoic_cdn_api_key();
		} else {
			$token = Ezoic_Integration_Authentication::get_token();
		}

		$response = \wp_remote_post( $requestURL, array(
			'method'		=> 'POST',
			'timeout'	=> 120,
			'headers'	=> array(
				'Authentication' => 'Bearer ' . $token
			),
			'body'		=> array()
		) );

		if ( \is_wp_error( $response ) ) {
			Ezoic_AdTester::log( 'Unable to initialize plugin with backend, please try to deactivate and reactivate' );
			return;
		}

		// Load response body
		$body = \wp_remote_retrieve_body( $response );

		// Deserialize body
		$deserialized = json_decode( $body );

		// Initialize local configuration
		$adtester->initialize_config();

		// Flush the cache for the site
		if ( Ezoic_Cdn::ezoic_cdn_is_enabled() ) {
			$cdn = new Ezoic_Cdn();
			$cdn->ezoic_cdn_purge( $cdn->ezoic_cdn_get_domain() );
		}
	}
}
