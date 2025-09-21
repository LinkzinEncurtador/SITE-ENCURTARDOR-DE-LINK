<?php

namespace Ezoic_Namespace;

class Ezoic_Emote extends Ezoic_Feature {
	private $emote;
	private $emote_export;
	private static $is_enabled = null;

	public function __construct() {
		$this->feature_flag();
		$this->check();



		$this->emote_export = new Emote_Export();

		if ( $this->is_public_enabled ) {
			$this->emote = new Ezoic_Emote_Template();
		}
	}

	public function register_public_hooks( $loader ) {
		$value = \get_option( 'ez_emote_enabled', 'false' );
		if( $value == "true" ) {
			$loader->add_action( 'comments_template', $this->emote, 'emote_comments_template');
			$loader->add_filter( 'pre_render_block', $this->emote, 'emote_block_template', 10, 2);
			$loader->add_action( 'wp_enqueue_scripts', $this->emote, 'enqueue_emote_script', 99);
		}
		$this->register_import_hooks( $loader );
	}

	public function register_admin_hooks( $loader ) {
		$this->register_import_hooks( $loader );
	}

	public function register_import_hooks( $loader ) {
		$loader->add_action( 'rest_api_init', $this->emote_export, 'register_export_endpoints' );
		$loader->add_action( 'ez_emote_import_init', $this->emote_export, 'export' );
	}

	private function check() {
		if ( isset( $_SERVER[ 'HTTP_X_EMOTE_CHECK' ] ) ) {
				$value = $_SERVER[ 'HTTP_X_EMOTE_CHECK' ];

			if ($value == 'true') {
					$option = \get_option( 'ez_emote_enabled', 'false' );
					self::$is_enabled = $option;
					if ($option == 'true') {
							$enabled = 'enabled';
					} else {
							$enabled = 'disabled';
					}
					header('X-Ezoic-WP-Emote: ' . $enabled);
			}
		}
	}

	/**
	 * Determine if the feature is enabled
	 */
	private function feature_flag() {
	  $value = self::$is_enabled;
		// If feature header is present, set option accordingly
		if ( isset( $_SERVER[ 'HTTP_X_EMOTE' ] ) ) {
			$value = $_SERVER[ 'HTTP_X_EMOTE' ];

			self::$is_enabled = $value;

			\update_option( 'ez_emote_enabled', $value );
		}

		if ($value == null) {
			$value = \get_option( 'ez_emote_enabled', 'false' );
				self::$is_enabled = $value;
		}

		// Enable feature if needed
		$this->is_public_enabled	= true;
		$this->is_admin_enabled		= true;
	}

}
