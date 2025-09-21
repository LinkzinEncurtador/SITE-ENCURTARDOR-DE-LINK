<?php

namespace Ezoic_Namespace;

class Ezoic_CMS extends Ezoic_Feature {
	private $cms_sync;

	private $cms_export;

	private static $is_enabled = null;

	public function __construct() {
		$this->feature_flag();
		$this->check();

		if ( $this->is_admin_enabled ) {
			$this->cms_sync = new Ezoic_CMS_Sync();
			$this->cms_export = new Ezoic_CMS_Export();
		}
	}

	public function register_public_hooks( $loader ) {
		// sync hooks must be registered in pubic hooks, otherwise Live Sync hooks will not load on certain pages
		$this->register_sync_hooks( $loader );
		$this->register_export_hooks( $loader );
	}

	public function register_admin_hooks( $loader ) {
		$this->register_sync_hooks( $loader );
		$this->register_export_hooks( $loader );
	}

	public function register_sync_hooks( $loader ) {
		$loader->add_action( 'create_term', $this->cms_sync, 'term_added' );
		$loader->add_action( 'edit_term', $this->cms_sync, 'term_updated', 10, 3 );
		$loader->add_action( 'delete_term', $this->cms_sync, 'term_deleted', 10, 5 );
		$loader->add_action( 'customize_save_after', $this->cms_sync, 'logo_update' );

		$loader->add_action( 'wp_generate_attachment_metadata', $this->cms_sync, 'media_added', 10, 2 );
		$loader->add_action( 'delete_attachment', $this->cms_sync, 'media_deleted', 10, 2);

		//$loader->add_action( 'draft_to_draft', $this->cms_sync, 'post_updated', 10, 1 );
		$loader->add_action( 'publish_to_publish', $this->cms_sync, 'post_updated', 10, 1 );
		$loader->add_action( 'draft_to_publish', $this->cms_sync, 'post_updated', 10, 1 );
		$loader->add_action( 'publish_to_draft', $this->cms_sync, 'post_updated', 10, 1 );
		//$loader->add_action( 'auto-draft_to_draft', $this->cms_sync, 'post_added', 10, 1 );
		//$loader->add_action( 'auto-draft_to_publish', $this->cms_sync, 'post_added', 10, 1 );
		// $loader->add_action( 'save_post', $this->cms_sync, 'post_saved', 10, 3 );
		$loader->add_action( 'wp_after_insert_post', $this->cms_sync, 'post_saved', 10, 3 );
		$loader->add_action( 'publish_to_trash', $this->cms_sync, 'post_deleted', 10, 1 ); // for page too?
		$loader->add_action( 'draft_to_trash', $this->cms_sync, 'post_deleted', 10, 31);
		$loader->add_action( 'auto-draft_to_trash', $this->cms_sync, 'post_deleted', 10, 1);

		// TODO: update attachment hooks
		//$loader->add_action( 'add_attachment', $this->cms_sync, 'add_attachment' );
		//$loader->add_action( 'edit_attachment', $this->cms_sync, 'edit_attachment' );
		//$loader->add_action( 'delete_attachment', $this->cms_sync, 'delete_attachment' );

		// TODO: This should be handled in posts need to test
		// $loader->add_action( 'added_term_relationship', $this->cms_sync, 'ez_cms_add_term_rel' );
		// $loader->add_action( 'deleted_term_relationships', $this->cms_sync, 'ez_cms_delete_term_rel' );

		// update menu hooks
		$loader->add_action( 'wp_create_nav_menu', $this->cms_sync, 'menu_added' );
		$loader->add_action( 'wp_update_nav_menu', $this->cms_sync, 'menu_updated' );
		$loader->add_action( 'wp_delete_nav_menu', $this->cms_sync, 'menu_deleted' );

		$loader->add_action( 'delete_user', $this->cms_sync, 'user_deleted' );
		$loader->add_action( 'user_register', $this->cms_sync, 'user_created', 10, 2 );
		$loader->add_action( 'profile_update', $this->cms_sync, 'user_updated', 10, 3 );

		// TODO: these are not defined
		// $loader->add_action( 'after_switch_theme', $this->cms_sync, 'update_theme_notification' );
		// $loader->add_action( 'update_option_blogname', $this->cms_sync, 'send_blogname' );
		// $loader->add_action( 'update_option_blogdescription', $this->cms_sync, 'send_blogdescription' );

		// TODO: need to update permalink structure
		// $loader->add_action( 'permalink_structure_changed', $this->cms_sync, 'send_permalink_structure' );
	}

	public function register_export_hooks( $loader ) {
		$loader->add_action( 'rest_api_init', $this->cms_export, 'register_export_endpoints' );
		$loader->add_action( 'rest_api_init', $this->cms_sync, 'ez_cms_sync_options_endpoint' );
		$loader->add_action( 'rest_api_init', $this->cms_sync, 'ez_cms_sync_origin_theme_endpoint' );
		$loader->add_action( 'rest_api_init', $this->cms_sync, 'ez_cms_sync_linklists_endpoint' );

		$loader->add_action ( 'ez_cms_export_init', $this->cms_export, 'export', 10, 1 );
	}

	private function check() {
		if ( isset( $_SERVER[ 'HTTP_X_EZOIC_CMS_CHECK' ] ) ) {
			$value = $_SERVER[ 'HTTP_X_EZOIC_CMS_CHECK' ];

			if ($value == 'true') {
				$option = \get_option( 'ez_cms_enabled', 'false' );
				self::$is_enabled = $option;
				if ($option == 'true') {
					$enabled = 'enabled';
				} else {
					$enabled = 'disabled';
				}
				header('X-Ezoic-WP-CMS: ' . $enabled);
			}
		}
	}

	/**
	 * Determine if the feature is enabled
	 */
	private function feature_flag() {
	  $value = self::$is_enabled;
		// If feature header is present, set option accordingly
		if ( isset( $_SERVER[ 'HTTP_X_EZOIC_CMS' ] ) ) {
			$value = $_SERVER[ 'HTTP_X_EZOIC_CMS' ];

			self::$is_enabled = $value;

			\update_option( 'ez_cms_enabled', $value );
		}

		if ($value == null) {
				$value = \get_option( 'ez_cms_enabled', 'false' );
				self::$is_enabled = $value;
		}

		// Enable feature if needed
		$this->is_public_enabled	= $value == 'true';
		$this->is_admin_enabled		= $value == 'true';
	}

}
