<?php

namespace Ezoic_Namespace;

/**
 * Handles serialization and transmission/logging of error messages
 */
class Ezoic_AdTester_Exception_Handler {
	private $exception;
	private $tags;

	public function __construct( $exception, $tags ) {
		$this->exception	= $exception;
		$this->tags			= $tags;
	}

	/**
	 * Handles serialization and transmission/logging of error
	 */
	public function handle() {
		// This method CANNOT fail - failure here would cause all kinds of problems, so we'll use an empty try/catch
		try {
			// Serialize the error
			$serializer = new Ezoic_AdTester_Exception_Serializer( $this->exception, $this->tags );
			$serialized = $serializer->serialize();

			// Always log the error
			Ezoic_Integration_Logger::log_error($serialized, 'AdTester');

			// Send error home
			$this->send_home( $serialized );
		} catch ( \Exception $ex ) {
			Ezoic_Integration_Logger::log_error('Unable to log exception: ' . print_r($ex, true), 'AdTester');
		}
	}

	/**
	 * Sends exception to error endpoint
	 */
	private function send_home( $serialized_error ) {
		// Only send exception if the API key is present
		if ( Ezoic_Cdn::ezoic_cdn_api_key() != null ) {
			$request_url = Ezoic_AdTester::EXCEPTION_ENDPOINT . '?developerKey=' . Ezoic_Cdn::ezoic_cdn_api_key();

			// Send request
			$response = \wp_remote_post( $request_url, array(
				'method'		=> 'POST',
				'timeout'	=> 20,
				'headers'	=> array( 'Content-Type' => 'application/json' ),
				'body'		=> $serialized_error
			) );

			// If an error was returned, log it
			if ( \is_wp_error( $response ) ) {
				Ezoic_Integration_Logger::log_error('Unable to force generation of placeholders, please refresh and try again', 'AdTester');
				return;
			}
		}
	}
}
