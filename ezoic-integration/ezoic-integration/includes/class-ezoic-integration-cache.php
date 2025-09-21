<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-cache.php');

class Ezoic_Integration_Cache implements iEzoic_Integration_Cache {
	private $cache_path;
	private $file_name;
	private $separator;
	private $request_path;
	private $cache_comment;

	public function __construct() {
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$this->cache_path = WP_CONTENT_DIR . '/cache/ezoic';

			if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
				$_SERVER['SERVER_NAME'] = Ezoic_Integration_Request_Utils::get_domain();
			}

			$this->request_path = $this->cache_path . '/' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

			if (substr($this->request_path, -1) != '/') {
				$this->request_path .= '/';

			}
			$this->file_name = 'index.html';
			$this->separator = '-';
			$this->cache_comment = '<!-- Served From The Ezoic Wordpress Cache -->';
		}
	}

	/**
	 * Retrieves a cached page based on the active template provided.
	 */
	public function get_page( $active_template ) {
		$file_path = $this->build_file_path($active_template);

		if ( !\is_file( $file_path ) ) {
			return '';
		}

		$content = file_get_contents($file_path);
		$content .= $this->cache_comment;
		return $content;
	}

	/**
	 * Inserts a page into the cache based on the active template provided.
	 */
	public function set_page( $active_template, $content ) {
		if ( empty( $active_template ) ) {
			return;
		}

		$file_path = $this->build_file_path( $active_template );
		$this->file_force_contents( $file_path, $content );
	}

	/**
	 * Checks to see whether or not the page is cached for the given
	 * active template.
	 */
	public function is_cached( $active_template ) {
		$available_templates = $this->get_available_templates();
		if (in_array($active_template, $available_templates)) {
			return true;
		}

		return false;
	}

	/**
	 * Based on the request path, it searches the directory and returns
	 * and array of the available active templates based on the prefixes
	 * of the html files.
	 */
	public function get_available_templates( ) {
		$templates = array();
		if (!is_dir($this->request_path)) {
			return $templates;
		}

		$files = array_diff(scandir($this->request_path), array('..', '.'));
		foreach ($files as $file) {
			if (is_dir($this->request_path . $file)) {
				continue;
			}
			$parts = explode($this->separator, $file);
			array_push($templates, $parts[0]);
		}
		return $templates;
	}

	/**
	 * Searches for the active template cookie in the request and returns
	 * the value for it.
	 */
	public function get_active_template_cookie() {
		foreach ($_COOKIE as $key => $val) {
			if (strpos($key, 'active_template') !== false) {
					$parts = explode('.', $val);
					return $parts[0];
			}
		}
		return '';
	}

	/**
	 * Clears the cache by recursively deleting all files and
	 * directors starting at the ezoic cache directory.
	 */
	public function clear() {
		if ( is_dir( $this->cache_path ) ) {
			$this->delete_directory($this->cache_path);
		}
	}

	/**
	 * Checks to see if the request is allowed to be cached or not.
	 */
	public function is_cacheable() {
		return empty($_SERVER['QUERY_STRING']) // Do not cache pages with a query string.
		&& !preg_match('/(.*favicon.*)/', $this->request_path) // Do not cache the favicon.ico request.
		&& !is_user_logged_in(); // Do not cache pages for logged in users.
	}

	/**
	 * Inserts a file by creating all necessary directories and then
	 * inserting the file.
	 */
	private function file_force_contents($dir, $contents) {
		$parts = explode('/', $dir);
		$file = array_pop($parts);
		$dir = '';
		foreach($parts as $part) {
			if (!is_dir($dir .= "/$part")) {
					if (mkdir($dir) === false) {
						error_log("failed to create directory: $dir");
						return;
					}
			}
		}
		file_put_contents("$dir/$file", $contents);
	}

	/**
	 * Builds a file path for the request based on the active template provided.
	 */
	private function build_file_path( $active_template ) {
		return $this->request_path . $active_template . $this->separator . $this->file_name;
	}

	/**
	 * Starts at the given path and recursively deleted all files and directories.
	 */
	private function delete_directory($path) {
		if (!is_dir($path)) {
			return;
		}

		$objects = \scandir($path);
		foreach ($objects as $object) {
			if ($object == '.' || $object == '..') {
					continue;
			}

			$object_path = $path . '/' . $object;
			if (is_dir($object_path)) {
					$this->delete_directory($object_path);
			} else{
					unlink($object_path);
			}

		}

		rmdir($path);
	}
}
