<?php

namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-content-collector.php' );

class Ezoic_Integration_File_Content_Collector implements iEzoic_Integration_Content_Collector {
	private $config_path;

	public function __construct() {
		$this->config_path = dirname( __FILE__ ) . "/config/ezoic_config.json";
	}

	public function get_orig_content() {
		$content = "";

		if ( file_exists( $this->config_path ) && is_readable( $this->config_path ) ) {
			$cache_content  = file_get_contents( $this->config_path );
			$config_content = json_decode( $cache_content, true );
			$file_name      = "";
			if ( $config_content["cache_identity"] == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
				$file_name = "_index.html";
			} elseif ( $config_content["cache_identity"] == Ezoic_Cache_Identity::WP_SUPER_CACHE ||
			           $config_content["cache_identity"] == Ezoic_Cache_Identity::WP_ROCKET_CACHE ) {
				$file_name = "index.html";
			}

			$content = $this->get_cached_file_contents( $config_content, $file_name );
		}

		return $content;
	}

	private function get_cached_file_contents( $config_content, $file_name ) {

		$cached_file = $config_content['cache_path'] . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . $file_name;

		if ( ! file_exists( $cached_file ) || ! is_readable( $cached_file ) ) {

			// recheck with SSL filename
			$file_name   = "_index_slash_ssl.html"; // W3_TOTAL_CACHE cache file
			$cached_file = $config_content['cache_path'] . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . $file_name;

			if ( ! file_exists( $cached_file ) || ! is_readable( $cached_file ) ) {
				return '';
			}

		}

		$content = file_get_contents( $cached_file, true );
		$content .= "<!-- grabbed from cache file -->";

		return $content;
	}
}
