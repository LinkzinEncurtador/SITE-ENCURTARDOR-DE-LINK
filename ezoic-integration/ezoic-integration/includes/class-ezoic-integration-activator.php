<?php

namespace Ezoic_Namespace;

/**
 * Fired during plugin activation
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-wp-endpoints.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache-identifier.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-compatibility-check.php';

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 * @author     Ezoic Inc. <support@ezoic.com>
 */
class Ezoic_Integration_Activator {
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// check plugin compatibility
		self::check_compatibility();

		// Trigger to indicate initial activation
		\add_option( 'activated_plugin', 'ezoic_integration' );

		// Add option to disable ad features, for now
		\add_option( 'ez_ad_integration_enabled', 'false' );

		// Create endpoints db table
		$ez_endpoints      = new Ezoic_Integration_WP_Endpoints();
		$sql               = $ez_endpoints->get_table_create_statement();
		$current_version   = $ez_endpoints->get_table_version();
		$installed_version = \get_option( 'ezoic_db_option' );

		if ( $installed_version !== $current_version ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			update_option( 'ezoic_db_version', $current_version );
		}

		// Let's figure out if any caching is going on
		$cache_identifier = new Ezoic_Integration_Cache_Identifier();

		// Let's determine what kind of caching is going on
		if ( $cache_identifier->get_cache_type() == Ezoic_Cache_Type::HTACCESS_CACHE ) {
			//modify htaccess files
			$cache_identifier->generate_htaccess_file();
			//modify php files
			$cache_identifier->modify_advanced_cache();
		} elseif ( $cache_identifier->get_cache_type() == Ezoic_Cache_Type::PHP_CACHE ) {
			//modify htaccess files
			$cache_identifier->generate_htaccess_file();
			//modify php files
			$cache_identifier->modify_advanced_cache();
		}

		// Generate our config so we know where our possible HTACCESS files will be located
		$cache_identifier->generate_config();

		// clear any plugin caches
		set_transient( 'ezoic_clear_caches', true );

		do_action( 'ez_activate' );
	}

	/**
	 * Check plugin compatibility
	 */
	private static function check_compatibility() {

		// Check for incompatible plugins with Ezoic
		$incompatible_plugins = Ezoic_Integration_Compatibility_Check::get_active_incompatible_plugins( true );
		if ( count( $incompatible_plugins ) > 0 ) {
			$plugin_string = '';
			foreach ( $incompatible_plugins as $plugin ) {
				$plugin_string .= '<strong>' . $plugin['name'] . ' (' . $plugin['version'] . ') </strong><br />';
				$plugin_string .= $plugin['message'] . '';

				$deactivate_link = Ezoic_Integration_Compatibility_Check::plugin_action_url( $plugin['filename'] );
				$plugin_string   .= '<p><a class="button button-primary" href="' . $deactivate_link . '">Deactivate Plugin</a></p>';

				$plugin_string .= '<br /><br />';
			}

			if ( $plugin_string != '' ) {
				deactivate_plugins( EZOIC__PLUGIN_FILE );

				$title   = 'Incompatible Plugins Detected!';
				$message = '<h3>Incompatible Plugins Detected!</h3>';
				$message .= 'The following plugins are not compatible with ' . EZOIC__PLUGIN_NAME . ':<br /><br /><br />
                       ' . $plugin_string;

				$message .= '<strong>Please deactivate the incompatible plugins, and reactivate the ' . EZOIC__PLUGIN_NAME . ' plugin.</strong><br/><br/>For more information, please visit <a href="https://www.ezoic.com/compatibility" target="_blank">https://www.ezoic.com/compatibility</a>.';

				$args = array(
					'back_link' => true,
				);

				wp_die( $message, $title, $args );
			}
		}
	}
}
