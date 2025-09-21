<?php

namespace Ezoic_Namespace;

class Ezoic_Microdata extends Ezoic_Feature {
	private $microdata;

	public function __construct() {
		$this->is_admin_enabled  = false;
		$this->is_public_enabled = false;


		$this->feature_flag();

		$this->microdata = new Ezoic_Microdata_Filters();
	}

	public function register_public_hooks( $loader ) {
		// Register built-in wordpress hooks to callbacks
		$loader->add_action( 'get_template_part',              $this->microdata, 'get_template_part', 10, 2 );
		$loader->add_filter( 'query_vars',                     $this->microdata, 'query_vars', 10, 2 );
		$loader->add_filter( 'the_time',                       $this->microdata, 'time_target', 10, 2 );
		$loader->add_filter( 'the_content',                    $this->microdata, 'content_target', 10, 2 );
		$loader->add_filter( 'navigation_markup_template',	   $this->microdata, 'annotate_navigation', 10, 2 );
		$loader->add_filter( 'bloginfo',                       $this->microdata, 'bloginfo', 10, 2 );
		$loader->add_filter( 'get_sidebar',                    $this->microdata, 'register_widgets', 10 );
		$loader->add_filter( 'get_the_author',                 $this->microdata, 'author_target', 5 );
		$loader->add_filter( 'get_the_archive_title',          $this->microdata, 'get_the_archive_title', 5, 2 );
		$loader->add_filter( 'wp_list_categories',             $this->microdata, 'category_list_item_target', 5 );
		$loader->add_filter( 'get_the_author_display_name',    $this->microdata, 'author_display_filter', 5 ); //      themes may esc html
		$loader->add_filter( 'the_author_posts_link',          $this->microdata, 'the_author_posts_link', 5 );
		$loader->add_filter( 'comments_number',                $this->microdata, 'comments_number', 5 );
		$loader->add_filter( 'get_comment_author_link',        $this->microdata, 'get_comment_author_link', 5 );
		$loader->add_filter( 'get_comment_author_url_link',    $this->microdata, 'get_comment_author_url_link', 5 );
		$loader->add_filter( 'comment_reply_link',             $this->microdata, 'comment_reply_link_filter', 5 );
		$loader->add_filter( 'get_avatar',                     $this->microdata, 'get_avatar', 5 );
		$loader->add_filter( 'post_thumbnail_html',            $this->microdata, 'post_thumbnail_html', 5 );
		$loader->add_filter( 'comments_popup_link_attributes', $this->microdata, 'comments_popup_link_attributes', 5 );
		$loader->add_filter( 'wp_kses_allowed_html',           $this->microdata, 'wp_kses_allowed_html', 5, 2 );
		$loader->add_filter( 'the_excerpt',                    $this->microdata, 'excerpt_target' );
		$loader->add_filter( 'the_tags',                       $this->microdata, 'tags_target' );
		$loader->add_filter( 'get_search_form',                $this->microdata, 'search_form_target' );
		$loader->add_filter( 'the_category',                   $this->microdata, 'category_target' );
		$loader->add_filter( 'dynamic_sidebar_before', 		   $this->microdata, 'annotate_sidebar_before' );
		$loader->add_filter( 'dynamic_sidebar_after', 		   $this->microdata, 'annotate_sidebar_after' );

		// Register custom Ezoic hooks
		$loader->add_filter( 'ez_title_primary',               $this->microdata, 'annotate_title', 10, 2 );
		$loader->add_filter( 'ez_headline',                    $this->microdata, 'annotate_title', 10, 2 );
		$loader->add_filter( 'ez_title_secondary',             $this->microdata, 'annotate_title', 10, 2 );
		$loader->add_filter( 'ez_next_post_title',             $this->microdata, 'annotate_pagination', 10, 2 );
		$loader->add_filter( 'ez_previous_post_title',         $this->microdata, 'annotate_pagination', 10, 2 );
		$loader->add_filter( 'ez_widget_output',               $this->microdata, 'widget', 5 );

		// ob_flush executes here
		$loader->add_filter( 'ez_body_attributes',             $this->microdata, 'body_output', 5 );
		$loader->add_filter( 'ez_main_attributes',             $this->microdata, 'main_output', 5 );
		$loader->add_filter( 'ez_author_meta',                 $this->microdata, 'author_meta_output', 5 );
		$loader->add_filter( 'ez_author_attributes',           $this->microdata, 'author_output', 5 );
		$loader->add_filter( 'ez_pagination_links',            $this->microdata, 'modify_pagination_links', 5 );
		$loader->add_filter( 'ez_comment_replace',             $this->microdata, 'modify_ez_comments', 5 );
		$loader->add_filter( 'ez_head_tag',                    $this->microdata, 'modify_head_tag', 5 );
	}

	public function register_admin_hooks( $loader ) {

	}

	/**
	 * Determine if the feature is enabled
	 */
	private function feature_flag() {
		$cms_enabled = \get_option( 'ez_cms_enabled', 'false' );
		$microdata_enabled = 'false';


		if ( isset( $_SERVER[ 'HTTP_X_EZOIC_CMS' ] ) ) {
			$microdata_enabled = $_SERVER[ 'HTTP_X_EZOIC_CMS' ];

			if ( $microdata_enabled == 'true' && $cms_enabled != 'true') {
				$microdata_enabled = 'false';
			}
		} else if ( isset( $_SERVER[ 'HTTP_X_EZOIC_MICRODATA' ] ) ) {
			$microdata_enabled= $_SERVER[ 'HTTP_X_EZOIC_MICRODATA' ];
		}

		// Enable feature if needed
		$this->is_public_enabled	= $microdata_enabled == 'true';
	}
}
