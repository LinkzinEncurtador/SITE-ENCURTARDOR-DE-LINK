<?php

namespace Ezoic_Namespace;

class Ezoic_Wp_Integration extends Ezoic_Feature {

	public function __construct() {
		$this->is_public_enabled = true;
		$this->is_admin_enabled  = false;
	}

	/**
	 * Register admin hooks
	 */
	public function register_admin_hooks( $loader ) {
	}

	/**
	 * Register public hooks
	 */
	public function register_public_hooks( $loader ) {
		// Only subscribe to output buffer capturing if using WP integration
		if ( $this->should_enable_full_ob() ) {
			$loader->add_action( 'plugins_loaded', $this, 'ez_buffer_start', 0 );
			$loader->add_action( 'shutdown', $this, 'ez_buffer_end', 0 );
		}
	}

	public function ez_buffer_start() {
		//if (!ob_get_status()) {
		ob_start();
		//}
		//echo "<!--buffer start-->";
	}

	public function ez_buffer_end() {
		//if(ob_get_level() > 0) {
		$ezoic_factory    = new Ezoic_Integration_Factory();
		$ezoic_integrator = $ezoic_factory->new_ezoic_integrator( Ezoic_Cache_Type::NO_CACHE );
		$ezoic_integrator->apply_ezoic_middleware();
		//} else {
		//echo "<!--buffer end-->";
		//}
	}

	private function should_enable_full_ob() {

		if ( Ezoic_Integration_Factory::bypass_middleware() ) {
			return false;
		}

		if ( Ezoic_Integration_Request_Utils::is_amp_endpoint() ) {
			return false;
		}

		$integration_opt = \get_option( 'ezoic_integration_options' );

		if ( is_array( $integration_opt ) && ! isset( $integration_opt['disable_wp_integration'] ) ) {
			// enable default wp integration for legacy versions
			$integration_opt['disable_wp_integration'] = 0;
		}

		$integration_enabled = isset( $integration_opt ) && \is_array( $integration_opt ) && $integration_opt['disable_wp_integration'] == 0;

		// CMS feature flag
		$cms_opt = \get_option( 'ez_cms_enabled', 'false' );
		$cms_enabled = $cms_opt != 'false';

		if ( $integration_enabled ) {
			$cache_identifier = new Ezoic_Integration_Cache_Identifier();
			$cache_identity   = $cache_identifier->get_cache_identity();
			if ( $cache_identity !== Ezoic_Cache_Type::NO_CACHE ) {
				// reading from caches, don't buffer
				return false;
			}
		}

		return $integration_enabled || $cms_enabled;
	}

	public static function is_special_route() {
		global $wp;

		// relative current URI:
		if ( isset( $wp ) ) {
			$current_url = add_query_arg( null, null );
		} else {
			$current_url = $_SERVER['REQUEST_URI'];
		}

		if ( preg_match( '/(.*\/wp\/v2\/.*)/', $current_url ) ) {
			return true;
		}

		if ( preg_match( '/(.*wp-login.*)/', $current_url ) ) {
			return true;
		}

		if ( preg_match( '/(.*wp-admin.*)/', $current_url ) ) {
			return true;
		}

		/*if( preg_match('/(.*wp-content.*)/', $current_url) ) {
		return true;
		}*/

		if ( preg_match( '/(.*wp-json.*)/', $current_url ) ) {
			return true;
		}

		if ( preg_match( '/sitemap(.*)\.xml/', $current_url ) ) {
			return true;
		}

		return false;
	}

}
