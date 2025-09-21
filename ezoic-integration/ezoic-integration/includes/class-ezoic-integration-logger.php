<?php

namespace Ezoic_Namespace;

/**
 * Centralized logging utility for Ezoic Integration plugin
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */
class Ezoic_Integration_Logger
{
	/**
	 * Log a general message with Ezoic prefix
	 *
	 * @param string $message The message to log
	 * @param string $context Optional context identifier (e.g., 'AdsTxt', 'JS Integration')
	 */
	public static function log($message, $context = '')
	{
		$prefix = $context ? "[ Ezoic - {$context} ]" : '[ Ezoic ]';
		error_log($prefix . ' ' . $message);

		if (self::is_debug_enabled()) {
			$log_entry = array(
				'timestamp' => microtime(true),
				'type' => 'log',
				'context' => $context,
				'message' => $message
			);
			self::$debug_logs[] = $log_entry;
		}
	}

	/**
	 * Log an error message
	 *
	 * @param string $message The error message to log
	 * @param string $context Optional context identifier
	 * @param int|string $position_id Optional position ID for debug table filtering
	 */
	public static function log_error($message, $context = '', $position_id = null)
	{
		$prefix = $context ? "[ Ezoic - {$context} ]" : '[ Ezoic ]';
		$full_message = $prefix . ' ERROR: ' . $message;
		error_log($full_message);

		if (self::is_debug_enabled()) {
			$log_entry = array(
				'timestamp' => microtime(true),
				'type' => 'error',
				'context' => $context,
				'message' => 'ERROR: ' . $message
			);
			if ($position_id !== null) {
				$log_entry['position_id'] = $position_id;
			}
			self::$debug_logs[] = $log_entry;
		}
	}

	/**
	 * Log a warning message
	 *
	 * @param string $message The warning message to log
	 * @param string $context Optional context identifier
	 * @param int|string $position_id Optional position ID for debug table filtering
	 */
	public static function log_warning($message, $context = '', $position_id = null)
	{
		$prefix = $context ? "[ Ezoic - {$context} ]" : '[ Ezoic ]';
		$full_message = $prefix . ' WARNING: ' . $message;
		error_log($full_message);

		if (self::is_debug_enabled()) {
			$log_entry = array(
				'timestamp' => microtime(true),
				'type' => 'warn',
				'context' => $context,
				'message' => 'WARNING: ' . $message
			);
			if ($position_id !== null) {
				$log_entry['position_id'] = $position_id;
			}
			self::$debug_logs[] = $log_entry;
		}
	}

	/**
	 * Log debug information (only if debugging is enabled)
	 *
	 * @param string $message The debug message to log
	 * @param string $context Optional context identifier
	 * @param int|string $position_id Optional position ID for debug table filtering
	 */
	public static function log_debug($message, $context = '', $position_id = null)
	{
		if (self::is_debug_enabled()) {
			$prefix = $context ? "[ Ezoic - {$context} ]" : '[ Ezoic ]';
			$full_message = $prefix . ' DEBUG: ' . $message;
			error_log($full_message);
			$log_entry = array(
				'timestamp' => microtime(true),
				'type' => 'debug',
				'context' => $context,
				'message' => 'DEBUG: ' . $message
			);
			if ($position_id !== null) {
				$log_entry['position_id'] = $position_id;
			}
			self::$debug_logs[] = $log_entry;
		}
	}

	/**
	 * Log an exception with full details
	 *
	 * @param \Exception $exception The exception to log
	 * @param string $context Optional context identifier
	 */
	public static function log_exception($exception, $context = '')
	{
		$message = sprintf(
			'EXCEPTION: %s in %s:%d - %s',
			get_class($exception),
			$exception->getFile(),
			$exception->getLine(),
			$exception->getMessage()
		);
		$prefix = $context ? "[ Ezoic - {$context} ]" : '[ Ezoic ]';
		$full_message = $prefix . ' ' . $message;
		error_log($full_message);

		if (self::is_debug_enabled()) {
			$log_entry = array(
				'timestamp' => microtime(true),
				'type' => 'error',
				'context' => $context,
				'message' => $message
			);
			self::$debug_logs[] = $log_entry;
		}
	}

	/**
	 * Track a successful position insertion for later verification
	 */
	public static function track_insertion($position_id)
	{
		if (self::is_debug_enabled()) {
			// Only track unique position IDs to avoid duplicates
			if (!in_array($position_id, self::$inserted_positions)) {
				self::$inserted_positions[] = $position_id;
			}
		}
	}

	/**
	 * Log debug information to ezJsDebug system when EZOIC_DEBUG is enabled
	 *
	 * @param string $message The debug message to log
	 * @param string $context Optional context identifier
	 * @param string $type Debug type (log, error, warn, debug, info)
	 * @param int|string $position_id Optional position ID for debug filtering
	 */
	public static function console_debug($message, $context = '', $type = 'log', $position_id = null)
	{
		if (self::is_debug_enabled()) {
			$log_entry = array(
				'timestamp' => microtime(true),
				'type' => $type,
				'context' => $context,
				'message' => $message
			);

			if ($position_id !== null) {
				$log_entry['position_id'] = $position_id;
			}

			self::$debug_logs[] = $log_entry;

			// Ensure output hook is registered
			if (!has_action('wp_footer', array(__CLASS__, 'render_console_output'))) {
				add_action('wp_footer', array(__CLASS__, 'render_console_output'));
			}
			if (!has_action('admin_footer', array(__CLASS__, 'render_console_output'))) {
				add_action('admin_footer', array(__CLASS__, 'render_console_output'));
			}
		}
	}

	/**
	 * Log API communication errors with request details
	 *
	 * @param string $endpoint The API endpoint
	 * @param mixed $response The response data
	 * @param string $context Optional context identifier
	 */
	public static function log_api_error($endpoint, $response, $context = 'API')
	{
		$message = sprintf(
			'API Error communicating with %s: %s',
			$endpoint,
			is_array($response) || is_object($response) ? print_r($response, true) : $response
		);
		self::log_error($message, $context);
	}

	private static $debug_logs = array();
	private static $inserted_positions = array();

	/**
	 * Check if debug mode is enabled via EZOIC_DEBUG constant or URL parameters
	 *
	 * @return bool True if debug mode is enabled
	 */
	private static function is_debug_enabled()
	{
		// Check for EZOIC_DEBUG constant
		if (defined('EZOIC_DEBUG') && EZOIC_DEBUG) {
			return true;
		}

		// Check for URL parameters set to 1
		if ((isset($_GET['ez_js_debugger']) && $_GET['ez_js_debugger'] == '1') ||
			(isset($_GET['ez_js_preview']) && $_GET['ez_js_preview'] == '1')
		) {
			return true;
		}

		// Check for cookies set to 1
		return (isset($_COOKIE['ez_js_debugger']) && $_COOKIE['ez_js_debugger'] == '1') ||
			(isset($_COOKIE['ez_js_preview']) && $_COOKIE['ez_js_preview'] == '1');
	}


	/**
	 * Render debug logs to ezJsDebug system
	 */
	public static function render_console_output()
	{
		static $rendered = false;

		if ($rendered || empty(self::$debug_logs)) {
			return;
		}

		echo '<script type="text/javascript">';

		// Output debug logs as JavaScript variable
		if (!empty(self::$debug_logs)) {
			echo 'window.ezJsDebug = ' . json_encode(self::$debug_logs) . ";\n";
		}

		// Check if inserted positions actually exist on the page
		if (!empty(self::$inserted_positions)) {
			echo "
		// Make verification function available globally for debugger
		window.ezAdVerification = function() {
			var insertedPositions = " . json_encode(self::$inserted_positions) . ";
			var placeholderCache = new Map();
			var htmlCache = document.body.innerHTML;

			function verifyPosition(positionId) {
				var cacheKey = 'placeholder-' + positionId;
				var foundMethods = [];

				// Check cached selector result
				if (!placeholderCache.has(cacheKey)) {
					var placeholderSelector = '#ezoic-pub-ad-placeholder-' + positionId;
					var element = document.querySelector(placeholderSelector);
					placeholderCache.set(cacheKey, element !== null);
				}

				if (placeholderCache.get(cacheKey)) {
					foundMethods.push('ezoic placeholder div');
				}

				if (htmlCache.indexOf('id=\"ezoic-pub-ad-placeholder-' + positionId + '\"') !== -1) {
					foundMethods.push('placeholder id in HTML');
				}

				var found = foundMethods.length > 0;
				var matchDetails = found ? ' (found via: ' + foundMethods.join(', ') + ')' : '';

				if (!window.ezJsDebug) window.ezJsDebug = [];
				window.ezJsDebug.push({
					'timestamp': Date.now() / 1000,
					'type': found ? 'success' : 'warn',
					'context': 'Ad Verification',
					'message': (found ? 'Verified placement on page' : 'Not found on page') + matchDetails,
					'position_id': positionId
				});
			}

			function processBatch(positions, batchSize, callback) {
				if (positions.length === 0) {
					if (callback) callback();
					return;
				}

				var batch = positions.splice(0, batchSize);
				batch.forEach(verifyPosition);

				// Use setTimeout to yield control back to browser
				setTimeout(function() {
					processBatch(positions, batchSize, callback);
				}, 0);
			}

			var positionsCopy = insertedPositions.slice();
			processBatch(positionsCopy, 10, function() {
				// Batch processing complete
				window.dispatchEvent(new CustomEvent('ezDebugDataUpdated'));
			});
		};

		// Run on load and notify debugger when complete
		window.addEventListener('load', function() {
			// Use requestIdleCallback to defer heavy DOM operations
			if (window.requestIdleCallback) {
				window.requestIdleCallback(function() {
					window.ezAdVerification();
				});
			} else {
				// Fallback with small delay to not block initial rendering
				setTimeout(function() {
					window.ezAdVerification();
				}, 50);
			}
		});
		";
		}

		echo '</script>';

		$rendered = true;
		self::$debug_logs = array();
		self::$inserted_positions = array();
	}
}
