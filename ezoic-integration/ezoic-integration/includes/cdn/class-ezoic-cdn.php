<?php

namespace Ezoic_Namespace;

use WP_Error;
use WP_Post;
use WP_Comment;
use WP_Theme;

/**
 * Class Ezoic_Cdn
 * @package Ezoic_Namespace
 */
class Ezoic_Cdn extends Ezoic_Feature {

	private $ezoic_cdn_already_purged = array();
	private $ezoic_cdn_keys_purged = array();

	public function __construct() {
		$this->is_public_enabled = true;
		$this->is_admin_enabled  = true;
	}

	// -------------------------------------------------------------------------
	// Hook Registration
	// -------------------------------------------------------------------------

	public function register_public_hooks( $loader ) {
		// Post-related hooks - these must be public
		$loader->add_action( 'publish_post', $this, 'ezoic_cdn_published', 10, 2 );
		$loader->add_action( 'publish_page', $this, 'ezoic_cdn_published', 10, 2 );
		$loader->add_action( 'transition_post_status', $this, 'ezoic_cdn_handle_post_transition', 10, 3 );

		// Comment-related hooks
		$loader->add_action( 'comment_post', $this, 'ezoic_handle_new_comment', 100, 3 );

		// Ezoic hooks
		$loader->add_action( 'ezoic_purge_domain', $this, 'ezoic_cdn_purge_domain_hook', 10, 0 );
		$loader->add_action( 'ezoic_purge_url', $this, 'ezoic_cdn_purge_url_hook', 10, 1 );
		$loader->add_action( 'ezoic_purge_urls', $this, 'ezoic_cdn_purge_urls_hook', 10, 1 );
		$loader->add_action( 'ezoic_purge_home', $this, 'ezoic_cdn_purge_home_hook', 10, 0 );
		$loader->add_action( 'ezoic_purge_post', $this, 'ezoic_cdn_purge_post_hook', 10, 1 );
	}

	public function register_admin_hooks( $loader ) {
		// Post-related hooks
		$loader->add_action( 'post_updated', $this, 'ezoic_cdn_post_updated', 10, 3 );
		$loader->add_action( 'after_delete_post', $this, 'ezoic_cdn_post_deleted', 100, 2 );

		// Comment-related hooks
		$loader->add_action( 'edit_comment', $this, 'ezoic_cdn_edit_comment', 100, 2 );
		$loader->add_action( 'delete_comment', $this, 'ezoic_cdn_delete_comment', 100, 2 );
		$loader->add_action( 'trash_comment', $this, 'ezoic_cdn_delete_comment', 100, 2 );
		$loader->add_action( 'wp_set_comment_status', $this, 'ezoic_handle_comment_status_change', 10, 2 );
		$loader->add_action( 'transition_comment_status', $this, 'ezoic_handle_comment_transition', 10, 3 );

		// Theme and plugin hooks
		$loader->add_action( 'switch_theme', $this, 'ezoic_cdn_switch_theme', 100, 3 );
		$loader->add_action( 'activated_plugin', $this, 'ezoic_cdn_activated_plugin', 100, 2 );
		$loader->add_action( 'deactivated_plugin', $this, 'ezoic_cdn_deactivated_plugin', 100, 2 );

		// Navigation menu hooks
		$loader->add_action( 'wp_create_nav_menu', $this, 'ezoic_cdn_purge_domain_hook', 10, 0 );
		$loader->add_action( 'wp_update_nav_menu', $this, 'ezoic_cdn_purge_domain_hook', 10, 0 );
		$loader->add_action( 'wp_delete_nav_menu', $this, 'ezoic_cdn_purge_domain_hook', 10, 0 );

		// Cache plugin hooks
		$loader->add_action( 'w3tc_flush_posts', $this, 'ezoic_cdn_cachehook_purge_posts_action', 2100 );
		$loader->add_action( 'w3tc_flush_post', $this, 'ezoic_cdn_cachehook_purge_post_action', 2100, 1 );
		$loader->add_action( 'w3tc_flush_all', $this, 'ezoic_cdn_cachehook_purge_posts_action', 2100 );
		$loader->add_action( 'wp_cache_cleared', $this, 'ezoic_cdn_cachehook_purge_posts_action', 2100 );
		$loader->add_action( 'rocket_purge_cache', $this, 'ezoic_cdn_rocket_purge_action', 2100, 4 );
		$loader->add_action( 'after_rocket_clean_post', $this, 'ezoic_cdn_rocket_clean_post_action', 2100, 3 );

		// Other hooks
		$loader->add_action( 'template_redirect', $this, 'ezoic_cdn_add_headers' );
		$loader->add_action( 'admin_notices', $this, 'ezoic_cdn_display_admin_notices' );

		// Ezoic hooks
		$loader->add_action( 'ezoic_cdn_scheduled_clear', $this, 'ezoic_cdn_scheduled_clear_action', 1, 1 );
		$loader->add_action( 'ezoic_purge_domain', $this, 'ezoic_cdn_purge_domain_hook', 10, 0 );
		$loader->add_action( 'ezoic_purge_url', $this, 'ezoic_cdn_purge_url_hook', 10, 1 );
		$loader->add_action( 'ezoic_purge_urls', $this, 'ezoic_cdn_purge_urls_hook', 10, 1 );
		$loader->add_action( 'ezoic_purge_home', $this, 'ezoic_cdn_purge_home_hook', 10, 0 );
		$loader->add_action( 'ezoic_purge_post', $this, 'ezoic_cdn_purge_post_hook', 10, 1 );
	}

	// -------------------------------------------------------------------------
	// API and Helper Methods
	// -------------------------------------------------------------------------

	private static function get_api_url( $endpoint ) {
		$base_url   = EZOIC_API_URL . $endpoint;
		$query_args = array(
			'developerKey' => self::ezoic_cdn_api_key()
		);

		$current_hook = current_filter();
		if ( ! empty( $current_hook ) ) {
			$query_args['wpHook'] = $current_hook;
		}

		$url = add_query_arg( $query_args, $base_url );

		return $url;
	}

	/**
	 * Helper Function to retrieve the API Key from WordPress Options
	 *
	 * @param boolean $refresh Set to true if you want to force re-fetching of the option rather than use static version.
	 *
	 * @return string API Key
	 * @since 1.0.0
	 */
	public static function ezoic_cdn_api_key( $refresh = false ) {
		static $api_key = null;
		if ( is_null( $api_key ) || $refresh ) {
			$api_key = get_option( 'ezoic_cdn_api_key' );
		}

		return $api_key;
	}


	/**
	 * Helper function to get the Ezoic Domain from the WordPress Options
	 *
	 * @param boolean $default Set to true if you want to generate the domain from WordPress Site URL.
	 *
	 * @return string Domain Name as defined in Ezoic
	 * @since 1.1.1
	 */
	public static function ezoic_cdn_get_domain( $default = false ) {
		$cdn_domain = get_option( 'ezoic_cdn_domain' );

		if ( ! $cdn_domain || $default ) {
			$cdn_domain = wp_parse_url( get_site_url(), PHP_URL_HOST );
			$cdn_domain = preg_replace( '@^www\.@msi', '', $cdn_domain );
			update_option( 'ezoic_cdn_domain', $cdn_domain );
		}

		return $cdn_domain;
	}

	/**
	 * @param $url
	 *
	 * @return array|string|string[]|null
	 */
	public static function ezoic_cdn_parse_domain( $url ) {
		$cdn_domain = wp_parse_url( $url, PHP_URL_HOST );
		$cdn_domain = preg_replace( '@^www\.@msi', '', $cdn_domain );

		return $cdn_domain;
	}

	/**
	 * Helper function to determine if fb share cache clearing is enabled
	 *
	 * @return boolean Facebook Clear Cache Enabled
	 * @since 2.6.26
	 *
	 */
	public static function fb_clear_cache_enabled() {
		return get_option( 'fb_clear_cache_enabled', 'off' ) === "on";
	}


	/**
	 * Helper function to get the Facebook App ID from the WordPress Options
	 *
	 * @return string Facebook App ID
	 * @since 2.6.26
	 *
	 */
	public static function fb_get_app_id() {
		return get_option( 'fb_app_id' );
	}

	/**
	 * Helper function to get the Facebook App Secret from the WordPress Options
	 *
	 * @return string Facebook App Secret
	 * @since 2.6.26
	 *
	 */
	public static function fb_get_app_secret() {
		return get_option( 'fb_app_secret' );
	}

	/**
	 * Helper function to get the Facebook App Auth Token from the WordPress Options
	 *
	 * @return string Facebook App Auth Token
	 * @since 2.6.26
	 *
	 */
	public static function fb_get_app_auth_token() {
		return get_option( 'fb_app_auth_token' );
	}

	/**
	 * Helper function to determine if auto-purging of the Ezoic CDN is enabled or not.
	 *
	 * Note if there is not an API key stored, this is always false.
	 *
	 * @param boolean $refresh Set to true if you want to re-fetch the option instead of using static variable.
	 *
	 * @return boolean
	 * @see ezoic_cdn_api_key()
	 * @since 1.0.0
	 */
	public static function ezoic_cdn_is_enabled( $refresh = false ) {
		static $cdn_enabled = null;
		if ( ! self::ezoic_cdn_api_key() ) {
			return false;
		}
		if ( is_null( $cdn_enabled ) || $refresh ) {
			$cdn_enabled = ( get_option( 'ezoic_cdn_enabled', 'on' ) === 'on' );
		}

		return $cdn_enabled;
	}

	public static function ezoic_cdn_always_clear_post_ids( $refresh = false ) {
		static $post_ids = null;
		if ( is_null( $post_ids ) || $refresh ) {
			$post_ids = get_option( 'ezoic_cdn_always_clear_posts' );
		}

		return $post_ids;
	}

	/**
	 * Helper function to retrieve urls to always purge
	 *
	 * @param boolean
	 *
	 * @return array
	 * @since 2.7.5
	 */
	public static function ezoic_cdn_always_clear_urls( $refresh = false ) {
		static $urls = null;
		if ( is_null( $urls ) || $refresh ) {
			$urls = get_option( 'ezoic_cdn_always_clear_urls', '' );
		}

		return $urls;
	}

	/**
	 * Helper function to determine if 'show post IDs' feature is on
	 *
	 * @param boolean
	 *
	 * @return boolean
	 * @since 2.5.10
	 */
	public static function ezoic_cdn_show_post_ids( $refresh = false ) {
		static $show_post_ids = null;
		if ( is_null( $show_post_ids ) || $refresh ) {
			$show_post_ids = ( get_option( 'ezoic_cdn_show_post_ids ', 'on' ) === 'on' );
		}

		return $show_post_ids;
	}

	/**
	 * Helper Function to determine if we are always purging the home page when purging anything.
	 *
	 * @param boolean $refresh Set to true if you want to re-fetch the option instead of using static variable.
	 *
	 * @return boolean
	 * @since 1.1.2
	 */
	public static function ezoic_cdn_always_purge_home( $refresh = false ) {
		static $always_home = null;
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return false;
		}
		if ( is_null( $always_home ) || $refresh ) {
			$always_home = ( get_option( 'ezoic_cdn_always_home', 'on' ) === 'on' );
		}

		return (bool) $always_home;
	}


	/**
	 * Helper function to determine if verbose mode is on.
	 *
	 * @param boolean $refresh Set to true if you want to re-fetch the option instead of using the static variable.
	 *
	 * @return boolean
	 * @since 1.1.2
	 */
	public static function ezoic_cdn_verbose_mode( $refresh = false ) {
		static $verbose_mode = null;
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return false;
		}
		if ( is_null( $verbose_mode ) || $refresh ) {
			$verbose_mode = ( get_option( 'ezoic_cdn_verbose_mode', 'off' ) === 'on' );
		}

		return (bool) $verbose_mode;
	}

	// -------------------------------------------------------------------------
	// Cache Clearing Methods
	// -------------------------------------------------------------------------

	public function ezoic_cdn_clear_url( $url = null ) {
		if ( in_array( $url, $this->ezoic_cdn_already_purged, true ) ) {
			return;
		}

		if ( ! is_string( $url ) ) {
			return;
		}

		$api_url = $this->get_api_url( '/gateway/cdnservices/clearcache' );

		$verbose = self::ezoic_cdn_verbose_mode();

		$args = array(
			'timeout'     => 45,
			'blocking'    => $verbose,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( array( 'url' => $url ) ),
		);

		$results = wp_remote_post( $api_url, $args );

		if ( $verbose ) {
			self::ezoic_cdn_add_notice( 'Single URL', $results, $url );
		}

		$this->ezoic_cdn_already_purged[] = $url;

		self::ezoic_cdn_purge_home();

		return $results;
	}

	/**
	 * Uses Ezoic CDN API to purge cache for an array of URLs
	 *
	 * @param array $urls List of URLs to purge from Ezoic Cache.
	 * @param bool $scheduled True if this is a scheduled run of this removal request.
	 *
	 * @return array|void|WP_Error wp_remote_post() response array
	 * @since 1.1.3 Once a removal has been submitted, submit another one 1 minute later.
	 * @since 1.0.0
	 */
	function ezoic_cdn_clear_urls( $urls = array(), $scheduled = false ) {
		$urls = array_merge( $urls, self::ezoic_cdn_get_urls_to_always_purge() );

		$urls = array_filter( array_unique( array_diff( $urls, $this->ezoic_cdn_already_purged ) ) );
		sort( $urls );

		if ( ! $urls ) {
			return;
		}

		$domain = self::ezoic_cdn_get_domain();

		// remove any non-string elements or offsite URLs
		$removed_urls = array();
		foreach ( $urls as $i => $url ) {
			$parsed_domain = self::ezoic_cdn_parse_domain( $url );
			if ( ! is_string( $url ) || ! strstr( $parsed_domain, $domain ) ) {
				$removed_urls[] = $urls[ $i ];
				unset( $urls[ $i ] );
			}
		}

		if ( empty( $urls ) ) {
			return;
		}

		$api_url = $this->get_api_url( '/gateway/cdnservices/bulkclearcache' );

		$verbose = self::ezoic_cdn_verbose_mode();

		$args = array(
			'timeout'     => 45,
			'blocking'    => $verbose,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( array( 'urls' => array_values( $urls ) ) ),
		);

		$results = wp_remote_post( $api_url, $args );

		$this->ezoic_cdn_already_purged = array_merge( $this->ezoic_cdn_already_purged, $urls );

		if ( $verbose ) {
			$label = ( $scheduled ) ? 'Scheduled Purge' : 'Bulk Purge';
			self::ezoic_cdn_add_notice( $label, $results, $urls );
			if ( ! empty( $removed_urls ) ) {
				self::ezoic_cdn_add_notice( "Removed URLs (not matching: $domain)", $removed_urls, null, 'warning' );
			}
		}

		return $results;
	}

	/**
	 * Pings Ezoic CDN API for successful integration
	 *
	 * @return array|void|WP_Error wp_remote_post() response array
	 * @since 1.1.3 Once a removal has been submitted, submit another one 1 minute later.
	 * @since 1.0.0
	 */
	public static function ezoic_cdn_ping() {
		$api_key = self::ezoic_cdn_api_key();

		if ( empty( $api_key ) ) {
			return array( false, "Please enter a valid CDN API key." );
		}

		$api_url = self::get_api_url( '/gateway/cdnservices/ping' );

		$args = array(
			'timeout'     => 45,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => '',
			'sslverify'   => false
		);

		$results = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $results ) ) {
			$error_string = $results->get_error_message();
			if ( is_array( $error_string ) || is_object( $error_string ) ) {
				return array( false, print_r( $error_string, true ) );
			} else {
				return array( false, $error_string );
			}
		} else {
			$response = json_decode( $results['body'], true );
			if ( $response && is_array( $response ) && $response['Success'] ) {
				// successfully busted cache!
				return array( true, "" );
			} else {
				// error
				error_log( 'Error accessing Ezoic API: ' . $response['Error'] );

				return array( false, $response['Error'] );
			}
		}
	}

	public function ezoic_cdn_clear_surrogate_keys( $keys = array(), $domain = null ) {
		$keys = array_merge( $keys, self::ezoic_cdn_get_surrogate_keys_to_always_purge() );

		if ( ! $domain ) {
			$domain = self::ezoic_cdn_get_domain();
		}

		$keys = array_unique( array_diff( $keys, $this->ezoic_cdn_keys_purged ) );

		if ( ! $keys ) {
			return;
		}

		$api_url = $this->get_api_url( '/gateway/cdnservices/clearbysurrogatekeys' );

		$verbose = self::ezoic_cdn_verbose_mode();

		$args = array(
			'timeout'     => 45,
			'blocking'    => $verbose,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode(
				array(
					'keys'   => implode( ',', $keys ),
					'domain' => $domain,
				)
			),
		);

		$results = wp_remote_post( $api_url, $args );

		$this->ezoic_cdn_keys_purged = array_merge( $this->ezoic_cdn_keys_purged, $keys );

		if ( $verbose ) {
			self::ezoic_cdn_add_notice( 'Surrogate Key Purge', $results, $keys );
		}

		return $results;
	}

	public function ezoic_cdn_purge( $domain = null ) {
		$api_key = self::ezoic_cdn_api_key();
		if ( empty( $api_key ) ) {
			return;
		}

		$api_url = $this->get_api_url( '/gateway/cdnservices/purgecache' );

		$verbose = self::ezoic_cdn_verbose_mode();

		$args = array(
			'timeout'     => 45,
			'blocking'    => $verbose,
			'httpversion' => '1.1',
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( array( 'domain' => $domain ) ),
		);

		$results = wp_remote_post( $api_url, $args );

		if ( $verbose ) {
			self::ezoic_cdn_add_notice( 'Purge', $results, array( 'domain' => $domain ) );
		}

		return $results;
	}

	public function ezoic_cdn_purge_home() {
		if ( ! self::ezoic_cdn_always_purge_home() ) {
			return false;
		}

		$urls = array(
			get_site_url(),
			get_home_url(),
			get_post_type_archive_link( 'post' ),
		);

		$urls = array_unique( $urls );

		return self::ezoic_cdn_clear_urls( $urls );
	}

	// -------------------------------------------------------------------------
	// URL and Surrogate Key Generation Methods
	// -------------------------------------------------------------------------

	public function ezoic_cdn_get_recache_urls_by_post( $post_id, WP_Post $post = null ) {
		if ( ! $post ) {
			$post = get_post( $post_id );
		}

		$urls = array();

		$url = get_permalink( $post );
		if ( $url ) {
			$urls[] = $url;
		}
		if ( 'page' !== $post->post_type ) {
			$url = get_post_type_archive_link( $post->post_type );
			if ( $url ) {
				$urls[] = $url;
			}
		}

		$categories = get_the_terms( $post, 'category' );
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$urls[] = get_term_link( $category );
				$urls[] = get_category_feed_link( $category->term_id, 'atom' );
				$urls[] = get_category_feed_link( $category->term_id, 'rss2' );
			}
		}

		$tags = get_the_terms( $post, 'post_tag' );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$urls[] = get_term_link( $tag );
				$urls[] = get_tag_feed_link( $tag->term_id, 'atom' );
				$urls[] = get_tag_feed_link( $tag->term_id, 'rss2' );
			}
		}

		$taxonomies = get_object_taxonomies( $post, 'names' );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( in_array( $taxonomy, array( 'category', 'post_tag', 'author' ), true ) ) {
					continue;
				}

				$terms = get_the_terms( $post, $taxonomy );
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$urls[] = get_term_link( $term, $taxonomy );
						$urls[] = get_term_feed_link( $term->term_id, $taxonomy, 'atom' );
						$urls[] = get_term_feed_link( $term->term_id, $taxonomy, 'rss2' );
					}
				}
			}
		}

		$urls[] = get_author_posts_url( $post->post_author );
		$urls[] = get_author_feed_link( $post->post_author, 'atom' );
		$urls[] = get_author_feed_link( $post->post_author, 'rss2' );

		if ( function_exists( 'coauthors' ) ) {
			$authors = get_coauthors( $post_id );
			if ( $authors ) {
				foreach ( $authors as $author ) {
					$urls[] = get_author_posts_url( $author->ID, $author->user_nicename );
					$urls[] = get_author_feed_link( $author->ID, 'atom' );
					$urls[] = get_author_feed_link( $author->ID, 'rss2' );
				}
			}
		}

		/*if ( comments_open( $post ) ) {
			$urls[] = get_bloginfo( 'comments_atom_url' );
			$urls[] = get_bloginfo( 'comments_rss2_url' );
			$urls[] = get_post_comments_feed_link( $post_id, 'atom' );
			$urls[] = get_post_comments_feed_link( $post_id, 'rss2' );
		}*/

		if ( self::ezoic_cdn_always_purge_home() ) {
			$urls[] = get_site_url( null, '/' );
			$urls[] = get_home_url( null, '/' );
		}

		if ( 'post' !== $post->post_type ) {
			return $urls;
		}

		$urls[] = get_bloginfo( 'atom_url' );
		$urls[] = get_bloginfo( 'rss_url' );
		$urls[] = get_bloginfo( 'rss2_url' );
		$urls[] = get_bloginfo( 'rdf_url' );

		$date   = strtotime( $post->post_date );
		$urls[] = get_year_link( gmdate( 'Y', $date ) );
		$urls[] = get_month_link( gmdate( 'Y', $date ), gmdate( 'm', $date ) );
		$urls[] = get_day_link( gmdate( 'Y', $date ), gmdate( 'm', $date ), gmdate( 'j', $date ) );

		// GTranslate
		$modified_urls = self::modify_urls_for_gtranslate( $urls );
		if ( is_array( $modified_urls ) && ! empty( $modified_urls ) ) {
			$urls = array_merge( $urls, $modified_urls );
		}

		$urls = array_unique( $urls );

		return $urls;
	}

	private function ezoic_cdn_get_recache_urls_by_comment( $comment ) {
		$urls = array();

		if ( ! $comment ) {
			return $urls;
		}

		$post_id = $comment->comment_post_ID;
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $urls;
		}

		// Add the single post URL
		$url = get_permalink( $post );
		if ( $url ) {
			$urls[] = $url;
		}

		// Add comment feed URLs
		if ( comments_open( $post ) ) {
			$urls[] = get_post_comments_feed_link( $post_id, 'atom' );
			$urls[] = get_post_comments_feed_link( $post_id, 'rss2' );
		}

		// Add site-wide comment feed URLs
		$urls[] = get_bloginfo( 'comments_atom_url' );
		$urls[] = get_bloginfo( 'comments_rss2_url' );

		return array_unique( $urls );
	}

	public static function ezoic_cdn_get_surrogate_keys_by_post( $post_id, WP_Post $post = null ) {
		if ( ! $post ) {
			$post = get_post( $post_id );
		}

		$keys = array();

		$keys[] = "single-{$post_id}";

		$categories = get_the_terms( $post, 'category' );
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$keys[] = "category-{$category->term_id}";
				$keys[] = "category-{$category->slug}";
			}
		}

		$tags = get_the_terms( $post, 'post_tag' );
		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$keys[] = "tag-{$tag->term_id}";
				$keys[] = "tag-{$tag->slug}";
			}
		}

		$taxonomies = get_object_taxonomies( $post, 'names' );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( in_array( $taxonomy, array( 'category', 'post_tag', 'author' ), true ) ) {
					continue;
				}

				$terms = get_the_terms( $post, $taxonomy );
				if ( $terms ) {
					foreach ( $terms as $term ) {
						$keys[] = "tax-{$taxonomy}-{$term->term_id}";
						$keys[] = "tax-{$taxonomy}-{$term->slug}";
					}
				}
			}
		}

		$keys[] = 'author-' . get_the_author_meta( 'user_nicename', $post->post_author );

		if ( function_exists( 'coauthors' ) ) {
			$authors = get_coauthors( $post_id );
			if ( $authors ) {
				foreach ( $authors as $author ) {
					$keys[] = "author-{$author->user_nicename}";
				}
			}
		}

		if ( self::ezoic_cdn_always_purge_home() ) {
			$keys[] = 'front';
			$keys[] = 'home';
		}

		if ( 'post' !== $post->post_type ) {
			return array_unique( $keys );
		}

		$date   = strtotime( $post->post_date );
		$keys[] = 'date-' . gmdate( 'Y', $date );
		$keys[] = 'date-' . gmdate( 'Ym', $date );
		$keys[] = 'date-' . gmdate( 'Ymd', $date );

		sort( $keys );

		return array_unique( $keys );
	}

	// -------------------------------------------------------------------------
	// Hook Callback Methods
	// -------------------------------------------------------------------------

	public function ezoic_cdn_published( $post_id, $post ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		// Check if this is a revision
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check if this is a new post or an update to an existing published post
		$is_new_post         = $post->post_date === $post->post_modified;
		$is_published_update = $post->post_status === 'publish' && ! $is_new_post;

		if ( $is_new_post || $is_published_update ) {
			$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id, $post );

			if ( $is_new_post ) {
				// If this is a new post, also clear the home page
				$home_url = get_home_url();
				$urls[]   = $home_url;
			}

			self::ezoic_cdn_clear_urls( $urls );

			$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id, $post );
			self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
		}
	}

	public function ezoic_cdn_handle_post_transition( $new_status, $old_status, $post ) {
		if ( $new_status === 'future' ) {
			return;
		}

		// Only proceed if transitioning to 'publish' status from a non-published status
		if ( $new_status !== 'publish' || $old_status === 'publish' ) {
			return;
		}

		$this->ezoic_cdn_published( $post->ID, $post );
	}

	public function ezoic_cdn_post_updated( $post_id, WP_Post $post_after, WP_Post $post_before ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		if ( wp_is_post_revision( $post_after ) ) {
			return;
		}

		// If the post wasn't and isn't published, no need to clear cache
		if ( 'publish' !== $post_before->post_status && 'publish' !== $post_after->post_status ) {
			return;
		}

		$urls = [];
		$keys = [];

		// Get URLs and keys for the current post state
		if ( 'publish' === $post_after->post_status ) {
			$urls = $this->ezoic_cdn_get_recache_urls_by_post( $post_id, $post_after );
			$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id, $post_after );
		}

		// If the post was previously published, also get URLs and keys based on its previous state
		if ( 'publish' === $post_before->post_status ) {
			$urls = array_merge( $urls, $this->ezoic_cdn_get_recache_urls_by_post( $post_id, $post_before ) );
			$keys = array_merge( $keys, self::ezoic_cdn_get_surrogate_keys_by_post( $post_id, $post_before ) );
		}

		$urls = array_unique( $urls );
		$keys = array_unique( $keys );

		// Clear URLs
		if ( ! empty( $urls ) ) {
			self::ezoic_cdn_clear_urls( $urls );
		}

		// Clear surrogate keys
		if ( ! empty( $keys ) ) {
			self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
		}
	}

	public function ezoic_cdn_post_deleted( $post_id, WP_Post $old_post = null ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		if ( empty( $old_post ) ) {
			$old_post = get_post( $post_id );
		}

		if ( wp_is_post_revision( $old_post ) ) {
			return;
		}

		if ( 'publish' !== $old_post->post_status ) {
			return;
		}

		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id, $old_post );
		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id, $old_post );
		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}

	public function ezoic_handle_new_comment( $comment_id, $comment_approved, $commentdata ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		// Only proceed if the comment is approved
		if ( 1 !== $comment_approved && 'approve' !== $comment_approved ) {
			return;
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		$post_id = $comment->comment_post_ID;
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		// Clear comment-specific URLs
		$comment_urls = self::ezoic_cdn_get_recache_urls_by_comment( $comment );
		self::ezoic_cdn_clear_urls( $comment_urls );

	}

	public function ezoic_handle_comment_status_change( $comment_id, $comment_status ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		$old_status = $comment->comment_approved;

		// Clear cache only if the comment is being approved or unapproved
		if ( ( $old_status !== '1' && $comment_status === '1' ) ||
			 ( $old_status === '1' && $comment_status !== '1' ) ) {
			$this->ezoic_handle_new_comment( $comment_id, $comment_status, null );
		}
	}

	public function ezoic_handle_comment_transition( $new_status, $old_status, $comment ) {
		// Clear cache if the comment is being added to or removed from the page
		if ( ( $old_status === 'approved' && $new_status !== 'approved' ) ||
			 ( $old_status !== 'approved' && $new_status === 'approved' ) ) {
			$this->ezoic_handle_new_comment( $comment->comment_ID, $new_status, null );
		}
	}

	public function ezoic_cdn_edit_comment( $comment_id, $data ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		$old_comment = get_comment( $comment_id );
		if ( ! $old_comment ) {
			return;
		}

		// Clear cache if the comment approval status has changed
		if ( $old_comment->comment_approved !== $data['comment_approved'] ) {
			$this->ezoic_handle_new_comment( $comment_id, $data['comment_approved'], null );
		}
	}

	public function ezoic_cdn_delete_comment( $comment_id, WP_Comment $comment = null ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		if ( empty( $comment ) ) {
			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				return;
			}
		}

		// Only clear cache for approved comments
		if ( $comment->comment_approved !== '1' ) {
			return;
		}

		$urls = self::ezoic_cdn_get_recache_urls_by_comment( $comment );
		$urls = array_unique( $urls );

		self::ezoic_cdn_clear_urls( $urls );
	}

	public function ezoic_cdn_activated_plugin( $plugin, $network_wide ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	public function ezoic_cdn_deactivated_plugin( $plugin, $network_deactivating ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	public function ezoic_cdn_switch_theme( $new_name, WP_Theme $new_theme, WP_Theme $old_theme ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	public function ezoic_cdn_add_headers() {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		global $wp_query;

		$object         = get_queried_object();
		$surrogate_keys = array();
		$last_modified  = time();

		$browser_max_age = 60 * 60; // Browser Cache pages 1 hour.
		$server_max_age  = 86400 * 365 * 3; // Server Cache pages 3 years.

		if ( is_singular() ) {
			$surrogate_keys[] = 'single';
			$surrogate_keys[] = 'single-' . get_post_type();
			$surrogate_keys[] = 'single-' . get_the_ID();

			$last_modified = strtotime( $object->post_modified );
		} elseif ( is_archive() ) {
			$surrogate_keys[] = 'archive';
			if ( is_category() ) {
				$surrogate_keys[] = 'category';
				$surrogate_keys[] = 'category-' . $object->slug;
				$surrogate_keys[] = 'category-' . $object->term_id;
			} elseif ( is_tag() ) {
				$surrogate_keys[] = 'tag';
				$surrogate_keys[] = 'tag-' . $object->slug;
				$surrogate_keys[] = 'tag-' . $object->term_id;
			} elseif ( is_tax() ) {
				$surrogate_keys[] = 'tax';
				$surrogate_keys[] = "tax-{$object->taxonomy}";
				$surrogate_keys[] = "tax-{$object->taxonomy}-{$object->slug}";
				$surrogate_keys[] = "tax-{$object->taxonomy}-{$object->term_id}";
			} elseif ( is_date() ) {
				$surrogate_keys[] = 'date';
				if ( is_day() ) {
					$surrogate_keys[] = 'date-day';
					$surrogate_keys[] = "date-{$wp_query->query_vars['year']}{$wp_query->query_vars['monthnum']}{$wp_query->query_vars['day']}";
				} elseif ( is_month() ) {
					$surrogate_keys[] = 'date-month';
					$surrogate_keys[] = "date-{$wp_query->query_vars['year']}{$wp_query->query_vars['monthnum']}";
				} elseif ( is_year() ) {
					$surrogate_keys[] = 'date-year';
					$surrogate_keys[] = "date-{$wp_query->query_vars['year']}";
				}
			} elseif ( is_author() ) {
				$surrogate_keys[] = 'author';
				$surrogate_keys[] = "author-{$object->user_nicename}";
			} elseif ( is_post_type_archive() ) {
				$surrogate_keys[] = 'type-' . get_post_type();
			}

			$paged = get_query_var( 'pagenum' ) ? get_query_var( 'pagenum' ) : false;
			if ( ! $paged && get_query_var( 'paged' ) ) {
				$paged = get_query_var( 'paged' );
			}
			if ( $paged ) {
				$surrogate_keys[] = 'paged';
				$surrogate_keys[] = "paged-{$paged}";
			}
		}

		if ( is_front_page() ) {
			$surrogate_keys[] = 'front';
			$browser_max_age  = 600;   // Home page likely changes frequently, browser cache only 10 minutes.
			$server_max_age   = 86400; // Home page likely changes frequently, server cache only 1 day.
		}
		if ( is_home() ) {
			$surrogate_keys[] = 'home';
			$browser_max_age  = 600;
			$server_max_age   = 86400;
		}

		if ( ! headers_sent() ) {
			if ( is_user_logged_in() ) {
				header( 'Cache-Control: max-age=0, no-store', true );
				header_remove( 'Expires' );
				header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', $last_modified ), true );
			} else {
				header( "Cache-Control: max-age={$browser_max_age}, s-maxage={$server_max_age}, public", true );
				header_remove( 'Expires' );
				header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s \G\M\T', $last_modified ), true );
			}
			if ( $surrogate_keys ) {
				header( 'Surrogate-Key: ' . implode( ' ', $surrogate_keys ), true );
			}
		}
	}

	public function ezoic_cdn_display_admin_notices() {
		if ( ! self::ezoic_cdn_verbose_mode() || ! current_user_can( 'administrator' ) ) {
			return;
		}
		$notices = get_transient( 'ezoic_cdn_admin_notice' );
		if ( ! $notices ) {
			return;
		}

		foreach ( $notices as $key => $notice ) {
			?>
			<div class="notice notice-<?php echo esc_attr( $notice['class'] ); ?> is-dismissible">
				<p><strong>Ezoic CDN Notice <?php echo esc_attr( $key ); ?>: <?php echo esc_attr( $notice['label'] ); ?></strong></p>
				<?php
				echo '<pre>Input: ';
				print_r( $notice['params'] );
				echo "\nResult: ";
				print_r( $notice['results'] );
				echo '</pre>';
				echo '<!-- Raw Results: ';
				print_r( $notice['raw'] );
				echo '-->';
				?>
			</div>
			<?php
		}

		delete_transient( 'ezoic_cdn_admin_notice' );
	}

	public function ezoic_cdn_scheduled_clear_action( $urls = array() ) {
		self::ezoic_cdn_clear_urls( $urls, true );
	}

	public function ezoic_cdn_cachehook_purge_posts_action() {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}

		self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
	}

	public function ezoic_cdn_cachehook_purge_post_action( $post_id = null ) {
		if ( ! self::ezoic_cdn_is_enabled() || ! $post_id ) {
			return;
		}
		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post_id );
		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post_id );
		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );

		return true;
	}

	public function ezoic_cdn_rocket_purge_action( $type = 'all', $id = 0, $taxonomy = '', $url = '' ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		switch ( $type ) {
			case 'all':
				return self::ezoic_cdn_purge( self::ezoic_cdn_get_domain() );
			case 'post':
				$urls = self::ezoic_cdn_get_recache_urls_by_post( $id );
				self::ezoic_cdn_clear_urls( $urls );

				$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $id );
				self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );

				return;
			case 'term':
				$urls   = array();
				$urls[] = get_term_link( $id, $taxonomy );
				$urls[] = get_term_feed_link( $id, $taxonomy, 'atom' );
				$urls[] = get_term_feed_link( $id, $taxonomy, 'rss2' );
				self::ezoic_cdn_clear_urls( $urls );

				$term = get_term( $id, $taxonomy );

				if ( 'category' === $taxonomy ) {
					$keys[] = "category-{$id}";
					$keys[] = "category-{$term->slug}";
				} elseif ( 'post_tag' === $taxonomy ) {
					$keys[] = "tag-{$id}";
					$keys[] = "tag-{$term->slug}";
				} else {
					$keys[] = "tax-{$taxonomy}-{$id}";
					$keys[] = "tax-{$taxonomy}-{$term->slug}";
				}
				self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );

				return;
			case 'url':
				$urls = array( $url );
				self::ezoic_cdn_clear_urls( $urls );

				return;
		}
	}

	public function ezoic_cdn_rocket_clean_post_action( $post, $purge_urls = array(), $lang = '' ) {
		if ( ! self::ezoic_cdn_is_enabled() ) {
			return;
		}
		$urls = self::ezoic_cdn_get_recache_urls_by_post( $post->ID, $post );
		$urls = array_merge( $urls, $purge_urls );
		$urls = array_unique( $urls );
		self::ezoic_cdn_clear_urls( $urls );

		$keys = self::ezoic_cdn_get_surrogate_keys_by_post( $post->ID, $post );
		self::ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}

	public static function ezoic_cdn_get_urls_to_always_purge() {
		$urls_to_purge = array();

		$post_ids_to_purge  = self::ezoic_cdn_get_always_clear_post_ids();
		$user_urls_to_purge = self::ezoic_cdn_get_always_clear_urls();

		foreach ( $post_ids_to_purge as $id ) {
			$url = get_permalink( $id );

			if ( $url ) {
				$urls_to_purge[] = $url;
			}
		}

		foreach ( $user_urls_to_purge as $url ) {
			$url             = trim( $url );
			$urls_to_purge[] = $url;
		}

		return $urls_to_purge;
	}

	public static function ezoic_cdn_get_surrogate_keys_to_always_purge() {
		$keys_to_purge = array();

		$post_ids_to_purge = self::ezoic_cdn_get_always_clear_post_ids();

		foreach ( $post_ids_to_purge as $id ) {
			$keys_to_purge = array_merge( $keys_to_purge, self::ezoic_cdn_get_surrogate_keys_by_post( $id ) );
		}

		return array_unique( $keys_to_purge );
	}

	public static function ezoic_cdn_get_always_clear_post_ids() {
		$str_ids = get_option( 'ezoic_cdn_always_clear_posts' );

		return self::ezoic_cdn_split_post_ids_str( $str_ids );
	}

	public static function ezoic_cdn_split_post_ids_str( $str_ids ) {
		if ( empty( $str_ids ) ) {
			return array();
		}

		return preg_split( '/,\s*/', $str_ids );
	}

	public static function ezoic_cdn_get_always_clear_urls() {
		$str_urls = get_option( 'ezoic_cdn_always_clear_urls', '' );

		return self::ezoic_cdn_split_urls_str( $str_urls );
	}

	public static function ezoic_cdn_split_urls_str( $str_urls ) {
		if ( empty( $str_urls ) ) {
			return array();
		}

		return preg_split( '/\n/', $str_urls );
	}

	public function ezoic_cdn_purge_domain_hook() {
		$domain = self::ezoic_cdn_get_domain();

		$this->ezoic_cdn_purge( $domain );
	}

	public function ezoic_cdn_purge_url_hook( $url ) {
		$this->ezoic_cdn_clear_url( $url );
	}

	public function ezoic_cdn_purge_urls_hook( $urls ) {
		$this->ezoic_cdn_clear_urls( $urls );
	}

	public function ezoic_cdn_purge_home_hook() {
		$this->ezoic_cdn_clear_url( get_home_url( null, '/' ) );
	}

	public function ezoic_cdn_purge_post_hook( $post_id = null ) {
		if ( ! self::ezoic_cdn_is_enabled() || ! $post_id ) {
			return;
		}

		$urls = $this->ezoic_cdn_get_recache_urls_by_post( $post_id );
		$this->ezoic_cdn_clear_urls( $urls );

		$keys = $this->ezoic_cdn_get_surrogate_keys_by_post( $post_id );
		$this->ezoic_cdn_clear_surrogate_keys( $keys, self::ezoic_cdn_get_domain() );
	}

	public function modify_urls_for_gtranslate( $urls ) {
		if ( ! class_exists( 'GTranslate' ) ) {
			return $urls;
		}

		$gt_options = get_option( 'GTranslate' );
		if ( empty( $gt_options['incl_langs'] ) ) {
			return $urls;
		}

		$is_enterprise = ! empty( $gt_options['enterprise_version'] ) && $gt_options['enterprise_version'] == '1';
		$is_pro        = ! empty( $gt_options['pro_version'] ) && $gt_options['pro_version'] == '1';

		if ( ! $is_enterprise && ! $is_pro ) {
			return $urls;
		}

		$language_codes = $gt_options['incl_langs'];
		$purge_home     = self::ezoic_cdn_always_purge_home();
		$modified_urls  = array();

		foreach ( $urls as $original_url ) {
			$url_parts   = self::parse_url_components( $original_url );
			$main_domain = self::ezoic_cdn_parse_domain( $original_url );

			foreach ( $language_codes as $code ) {
				if ( $is_enterprise ) {
					$new_url = $url_parts['scheme'] . '://' . $code . '.' . $main_domain . $url_parts['path'] . $url_parts['query'] . $url_parts['fragment'];
				} else {
					$path_with_code = '/' . $code . ( isset( $url_parts['path'] ) ? '/' . ltrim( $url_parts['path'], '/' ) : '' );
					$new_url        = $url_parts['scheme_and_host'] . $path_with_code . $url_parts['query'] . $url_parts['fragment'];
				}

				if ( ! in_array( $new_url, $modified_urls ) ) {
					$modified_urls[] = $new_url;
				}
			}

			if ( $is_enterprise && ! in_array( $original_url, $modified_urls ) ) {
				$modified_urls[] = $original_url;
			}

			if ( $purge_home ) {
				foreach ( $language_codes as $code ) {
					$site_url_code = get_site_url( null, '/' . $code );
					if ( ! in_array( $site_url_code, $modified_urls ) ) {
						$modified_urls[] = $site_url_code;
					}
					$home_url_code = get_home_url( null, '/' . $code );
					if ( ! in_array( $home_url_code, $modified_urls ) ) {
						$modified_urls[] = $home_url_code;
					}
				}
			}
		}

		sort( $modified_urls );

		return $modified_urls;
	}

	public function parse_url_components( $url ) {
		$parsed_url      = wp_parse_url( $url );
		$scheme          = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] : 'http';
		$host            = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$scheme_and_host = $scheme . '://' . $host;

		return array(
			'scheme'          => $scheme,
			'host'            => $host,
			'scheme_and_host' => $scheme_and_host,
			'path'            => isset( $parsed_url['path'] ) ? '/' . ltrim( $parsed_url['path'], '/' ) : '/',
			'query'           => isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '',
			'fragment'        => isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '',
		);
	}

	private function ezoic_cdn_add_notice( $label, $results, $params = null, $class = 'info' ) {
		static $notices = array();

		$raw = null;

		if ( ! $notices ) {
			$notices = get_transient( 'ezoic_cdn_admin_notice' );
		}

		if ( is_array( $results ) && ! empty( $results['response'] ) && ! empty( $results['body'] ) ) {
			$raw = $results;

			$results         = $raw['response'];
			$results['body'] = $raw['body'];
		}

		$notices[] = array(
			'label'   => $label,
			'results' => $results,
			'params'  => $params,
			'class'   => $class,
			'raw'     => $raw,
		);

		set_transient( 'ezoic_cdn_admin_notice', $notices, 600 );
	}
}
