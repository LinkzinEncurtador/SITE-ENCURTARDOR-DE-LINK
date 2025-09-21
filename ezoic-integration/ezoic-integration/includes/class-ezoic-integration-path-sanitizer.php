<?php

namespace Ezoic_Namespace;

/**
 * Path sanitization utility for Ezoic Integration plugin
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */
class Ezoic_Integration_Path_Sanitizer
{
	/**
	 * Sanitize and validate a file path to prevent directory traversal
	 *
	 * @param string $path The path to sanitize
	 * @return string|false The sanitized path with trailing slash, or false on failure
	 */
	public static function sanitize_path($path)
	{
		if (empty($path)) {
			return false;
		}

		// Resolve the real path to prevent directory traversal
		$real_path = realpath($path);
		if ($real_path === false) {
			return false;
		}

		// Ensure trailing slash for directory paths
		return trailingslashit($real_path);
	}

	/**
	 * Get sanitized WordPress home path
	 *
	 * @return string|false The sanitized home path, or false on failure
	 */
	public static function get_home_path()
	{
		$home_path = get_home_path();
		return self::sanitize_path($home_path);
	}

	/**
	 * Safely construct a file path within a base directory
	 *
	 * @param string $base_path The base directory path
	 * @param string $filename The filename to append
	 * @return string|false The constructed path, or false on failure
	 */
	public static function construct_file_path($base_path, $filename)
	{
		$sanitized_base = self::sanitize_path($base_path);
		if ($sanitized_base === false) {
			return false;
		}

		// Sanitize filename to prevent path traversal
		$safe_filename = basename($filename);
		if (empty($safe_filename) || $safe_filename !== $filename) {
			return false;
		}

		return $sanitized_base . $safe_filename;
	}

	/**
	 * Validate that a path exists and is within expected boundaries
	 *
	 * @param string $path The path to validate
	 * @param string $allowed_base Optional base path that the file must be within
	 * @return bool True if path is valid and safe
	 */
	public static function is_path_safe($path, $allowed_base = '')
	{
		$sanitized_path = self::sanitize_path($path);
		if ($sanitized_path === false) {
			return false;
		}

		// If base path is specified, ensure the path is within it
		if (!empty($allowed_base)) {
			$sanitized_base = self::sanitize_path($allowed_base);
			if ($sanitized_base === false) {
				return false;
			}

			// Check if the path starts with the allowed base
			if (strpos($sanitized_path, $sanitized_base) !== 0) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitize and validate a domain name
	 *
	 * @param string $domain The domain to sanitize
	 * @return string|false The sanitized domain, or false on failure
	 */
	public static function sanitize_domain($domain)
	{
		if (empty($domain)) {
			return false;
		}

		// Remove www. prefix
		$domain = preg_replace('/^www\./', '', $domain);

		// Sanitize as text field to remove any dangerous characters
		$sanitized = sanitize_text_field($domain);

		// Basic domain validation pattern
		if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]*[a-zA-Z0-9]$/', $sanitized)) {
			return false;
		}

		return $sanitized;
	}

	/**
	 * Create a safe redirect URL for ads.txt manager
	 *
	 * @param int $atm_id The ads.txt manager ID
	 * @param string $domain The domain name
	 * @return string|false The sanitized redirect URL, or false on failure
	 */
	public static function create_adstxt_redirect_url($atm_id, $domain)
	{
		// Validate ATM ID
		$atm_id = intval($atm_id);
		if ($atm_id <= 0) {
			return false;
		}

		// Sanitize domain
		$sanitized_domain = self::sanitize_domain($domain);
		if ($sanitized_domain === false) {
			return false;
		}

		// Construct safe URL
		return sprintf(
			'https://srv.adstxtmanager.com/%d/%s',
			$atm_id,
			urlencode($sanitized_domain)
		);
	}
}
