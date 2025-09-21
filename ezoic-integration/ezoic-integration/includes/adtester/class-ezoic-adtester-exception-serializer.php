<?php

namespace Ezoic_Namespace;

/**
 * Serializes a PHP exception as JSON
 */
class Ezoic_AdTester_Exception_Serializer {
	const ANONYMOUS_CLASS_PREFIX = "class@anonymous\x00";

	private $exception;
	private $tags;

	public function __construct( $exception, $tags ) {
		$this->exception	= $exception;
		$this->tags			= $tags;
	}

	/**
	 * Serializes the exception as JSON
	 */
	public function serialize() {
		global $wp_version;

		$payload = array();

		// Set defaults

		// Id - uuid
		$payload[ 'id' ]				= str_replace( '-', '', Ezoic_AdTester_Exception_Serializer::gen_uuid() );

		// Timestamp - In seconds with decimals
		$payload[ 'timestamp' ]		= \microtime(true);

		// Data type of the exception
		$payload[ 'type' ]			= \get_class( $this->exception );

		// Exception message
		$payload[ 'value' ]			= $this->exception->getMessage();

		// Stacktrace
		$payload[ 'stacktrace' ]	= $this->parse_stacktrace();

		// Tags
		$payload[ 'tags' ]			= $this->tags;
		$payload[ 'tags' ][ 'wordpress_version' ] = $wp_version;

		// WordPress version
		$payload[ 'wp_version' ] = $wp_version;

		// Platform - Always PHP
		$payload[ 'platform' ]		= 'php';

		// Platform Version
		$payload[ 'platform_version' ] = \phpversion();

		// Error level - Defaulting to error, though could be adjusted as needed
		$payload[ 'level' ]			= 'error';

		// Current domain
		$payload[ 'server_name' ]	= Ezoic_Integration_Request_Utils::get_domain();

		// Current plugin version
		if ( \defined( 'EZOIC_INTEGRATION_VERSION' ) ) {
			$payload[ 'release' ]	= 'ezoic-integration@' . EZOIC_INTEGRATION_VERSION;
		}

		// Current request url
		if ( isset( $_SERVER ) ) {
		$payload[ 'request' ] = array(
				'scheme'			=> $_SERVER[ 'REQUEST_SCHEME' ],
				'host'			=> $_SERVER[ 'HTTP_HOST' ],
				'path'			=> $_SERVER[ 'REQUEST_URI' ],
				'query_string'	=> $_SERVER[ 'QUERY_STRING' ],
				'method'			=> $_SERVER[ 'REQUEST_METHOD' ]
			);
		}

		return \json_encode( $payload );
	}

	/**
	 * Parses a PHP exception stacktrace into something reasonable
	 */
	private function parse_stacktrace() {
		$frames = array();
		$trace = $this->exception->getTrace();
		$file = '<unknown>';
		$line = -1;

		// Loop through each frame of the stack trace
		foreach ( $trace as $trace_frame ) {
			// Set file name for next iteration
			if ( isset( $trace_frame[ 'file' ] ) ) {
				$file = $trace_frame[ 'file' ];
			} else {
				$file = '<unknown>';
			}

			// Set line number for next iteration
			if ( isset( $trace_frame[ 'line' ] ) ) {
				$line = $trace_frame[ 'line' ];
			} else {
				$line = -1;
			}

			// Append the processed frame to the trace
			\array_unshift( $frames, $this->parse_stackframe( $trace_frame, $file, $line ) );
		}

		return $frames;
	}

	/**
	 * Parses a PHP exception stack frame into something reasonable
	 */
	private function parse_stackframe( $frame, $file, $line ) {
		// The filename can be in any of these formats:
		//   - </path/to/filename>
		//   - </path/to/filename>(<line number>) : eval()'d code
		//   - </path/to/filename>(<line number>) : runtime-created function
		if ( preg_match( '/^(.*)\((\d+)\) : (?:eval\(\)\'d code|runtime-created function)$/ ', $file, $matches ) ) {
			$file = $matches[ 1 ];
			$line = (int) $matches[ 2 ];
		}

		$function_name = null;
		$raw_function_name = null;

		// Set function name to class name, if available
		if ( isset($frame[ 'class' ]) && isset( $frame[ 'function' ] ) ) {
			$function_name = $frame[ 'class' ];

			// If no class name is defined, output the default anonymous class identifier
			if ( strpos( $function_name, Ezoic_AdTester_Exception_Serializer::ANONYMOUS_CLASS_PREFIX ) === 0 ) {
				$function_name = Ezoic_AdTester_Exception_Serializer::ANONYMOUS_CLASS_PREFIX . substr( $frame['class'], \strlen( Ezoic_AdTester_Exception_Serializer::ANONYMOUS_CLASS_PREFIX ) );
			}

			// Parse raw function name
			$raw_function_name = sprintf( '%s::%s', $frame[ 'class' ], $frame[ 'function' ] );
			$function_name = sprintf( '%s::%s', preg_replace( '/0x[a-fA-F0-9]+$/', '', $function_name ), $frame[ 'function' ] );
		} elseif ( isset( $frame[ 'function' ] ) ) {
			$function_name = $frame[ 'function' ];
		}

		// Generate a generic container for the serialized frame
		$result = array(
			'function'				=> $function_name,
			'file_path'				=> $file,
			'line'					=> $line,
			'raw_function_name'	=> $raw_function_name,
			'file'					=> $file
		);

		return $result;
	}

	/**
	 * Generates a psuedo-random uuid based on the current timestamp and request url
	 */
	private static function gen_uuid() {
		// MT_RAND_PHP does not exist in PHP prior to verison 7.1.0
		if ( \version_compare( \phpversion(), "7.1.0", "ge" ) ) {
			$random_seed = \mt_srand( \crc32( \serialize( [microtime( true ), \site_url() ] ) ), MT_RAND_PHP );
		} else {
			$random_seed = \mt_srand( \crc32( \serialize( [microtime( true ), \site_url() ] ) ) );
		}

		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
}
