<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Emote
 * @subpackage Ezoic_Emote/public
 */
namespace Ezoic_Namespace;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ezoic_Emote
 * @subpackage Ezoic_Emote/public
 * @author     Ryan Outtrim <routtrim@ezoic.com>
 */
class Ezoic_Emote_Template {
	public function emote_comments_template( $comment_template ) {
		if ( !( is_singular() ) || (\get_option( 'ez_emote_enabled', 'false' ) != 'true' )) {
			return;
		}
		return dirname(__FILE__) . '/emote-comments.php';
	}

	public function enqueue_emote_script() {
		remove_action( 'comment_form', 'comment_form' );
		wp_enqueue_script( 'emote_js', 'https://i.emote.com/js/emote.js', false);
	}

	public function emote_block_template( $pre_render,  $parsed_block ) {
		if ( 'core/comments' === $parsed_block['blockName'] ) {
			if ( !( is_singular() ) || (\get_option( 'ez_emote_enabled', 'false' ) != 'true' || !comments_open() )) {
				return '';
			}
			return '<div id="emote_com"></div>';
		}
	}
}
