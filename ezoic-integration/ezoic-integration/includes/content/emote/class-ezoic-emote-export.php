<?php

namespace Ezoic_Namespace;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://emote.com
 * @since      1.0.0
 *
 * @package    Emote
 * @subpackage Emote/admin
 */
class Emote_Export extends Ezoic_Content_Export {
	private $export_transient;
	private $export_request_header;
	private $export_cron_event;
	private $export_archive_name;
	private $export_module;

	public function __construct() {
		$this->export_transient = 'ezoic_emote_export';
		$this->export_request_header = 'x-ezoic-emote-export';
		$this->export_cron_event = 'ez_emote_export_init';
		$this->export_archive_name = 'emote_files.zip';
		$this->export_module = 'emote';
	}

	public function get_transient_name() {
		return $this->export_transient;
	}

	public function get_request_header() {
		return $this->export_request_header;
	}

	public function get_cron_event_name() {
		return $this->export_cron_event;
	}

	public function get_archive_name() {
		return $this->export_archive_name;
	}

	public function get_module_name() {
		return $this->export_module;
	}

	/**
	 * Import Initiation Endpoint
	 */
	public function register_export_endpoints() {
		register_rest_route( 'ezoic-emote/v1', '/export/initiate', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'initiate_export_event' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		) );

		register_rest_route( 'ezoic-emote/v1', '/export/cancel', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'cancel_export_event' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route('ezoic-emote/v1', '/export/verify', array(
			'methods' => \WP_REST_Server::READABLE,
			'callback' => array($this, 'verify_export_files'),
			'permission_callback' => '__return_true',
			'show_in_index'       => false,
		));

		register_rest_route('ezoic-emote/v1', '/export/cleanup', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array($this, 'cleanup_export_files'),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route( 'ezoic-emote/v1', '/export/retry', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'attempt_archive_upload' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route( 'ezoic-emote/v1', '/replace', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'emote_replace_toggle' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route( 'ezoic-emote/v1', '/emote', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'emote_toggle' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route( 'ezoic-emote/v1', '/emote-check', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'emote_check' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));
	}

	public function emote_replace_toggle ( $request ) {
		$is_enabled = $request->get_param('is_enabled');
		$emote_key = $request->get_param('key');
		$is_valid = Emote_Export::verify_emote_key( $emote_key );

		if ( !$is_valid ) {
			return new \WP_REST_Response(array('success' => 'false'), 401);
		}

		// Toggle if not set
		if ( $is_enabled == "" ) {
			$current = \get_option( 'ez_emote_enabled', "false" );
			if ( $current == "true" ) {
				$is_enabled = "false";
			} else {
				$is_enabled = "true";
			}
		}

		// To replace wordpress comment sections
		\update_option( 'ez_emote_enabled', $is_enabled );

		return new \WP_REST_Response(array('success' => 'true', 'replaced' => $is_enabled), 200);
	}

	public function emote_check ( $request ) {
		return new \WP_REST_Response(array(
			'success' => 'true',
			'emote' =>  \get_option( 'ez_emote', 'false' ),
			'replaced' => \get_option( 'ez_emote_enabled', 'false' )
		), 200);
	}

	public function emote_toggle ( $request ) {
		$is_enabled = $request->get_param( 'is_enabled' );
		$emote_key = $request->get_param( 'key' );
		$is_valid = Emote_Export::verify_emote_key( $emote_key );

		// if valid key save key

		if( !$is_valid ) {
			return new \WP_REST_Response(array('success' => $is_valid ), 401);
		}

		// To show the emote tab or not
		\update_option( 'ez_emote', $is_enabled );

		return new \WP_REST_Response(array('success' => $is_valid ), 200);
	}

	private function verify_emote_key ( $key ) {
		$api_url = 'https://i.emote.com/api/domain/auth/verify';
		$body_data = json_encode( array( 'key' => $key, 'domain' => site_url() ) );

		$request_args = array(
			'body'    => $body_data,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$response = wp_remote_post( $api_url, $request_args );

		if (is_wp_error($response)) {
			error_log('Error making API request: ' . $response->get_error_message());
			return false;
		} else {
			$response_body = wp_remote_retrieve_body($response);
			error_log('Error making API request: ' . $response_body);
			$data = json_decode($response_body, true);
			if (isset($data['success']) && is_bool($data['success'])) {
				return $data['success'];
			}
		}
		return false;
	}

	public function initiate_export_event ( $request ) {
		return $this->run_or_schedule_export( $request );
	}

	public function export( $tenant ) {
		// notify import has started
		$this->update_status( 'In Progress' );

		//prevent timeout for long-running script
		set_time_limit( 0 );

		$db = new Ezoic_Content_Database();
		$exported_tables = $db->export_database( $this->get_database_tablenames() );
		if ( Ezoic_Content_Util::is_error( $exported_tables ) ) {
			$this->send_alert( 'Failed to export database ' . $exported_tables );
			return $exported_tables;
		}

		$file = new Ezoic_Content_File();
		$export_archive_created = $file->package_export_files( $this->get_archive_name(), $this->get_export_filenames( false ) );
		if ( Ezoic_Content_Util::is_error( $export_archive_created ) ) {
			$this->send_alert( 'Failed to create export archive ' . $export_archive_created );
			return $export_archive_created;
		}

		$uploaded_attempted = $this->attempt_archive_upload( $tenant );
		if ( Ezoic_Content_Util::is_error( $uploaded_attempted ) ) {
			$this->send_alert( 'Failed to upload export archive ' . $uploaded_attempted);
			return $uploaded_attempted;
		}

		$file->cleanup_files( $this->get_export_filenames( true ) );

		// if no errors, return true
		$this->update_status( 'SUCCESS' );
		delete_transient( $this->get_transient_name() );
		return true;
	}

	protected function get_export_filenames( $include_assets ) {
		return array_merge(
			array(
				$this->get_archive_name()
			),
			Ezoic_Content_Database::get_database_table_files( $this->get_database_tablenames() )
		);
	}

	protected function get_database_tablenames() {
		return array(
			'comments'
		);
	}
}
