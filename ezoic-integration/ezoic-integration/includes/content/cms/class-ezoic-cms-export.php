<?php

namespace Ezoic_Namespace;

/**
 *
 * @package    Ezoic_CMS_Export
 * @author     Eric Raio <eraio@ezoic.com>
 */
class Ezoic_CMS_Export extends Ezoic_Content_Export {
	private $export_transient;
	private $export_request_header;
	private $export_cron_event;
	private $export_archive_name;
	private $export_module;

	public function __construct() {
		$this->export_transient = 'ezoic_cms_export';
		$this->export_request_header = 'x-ezoic-cms-export';
		$this->export_cron_event = 'ez_cms_export_init';
		$this->export_archive_name = 'cms_files.zip';
		$this->export_module = 'cms';
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
	 * Export Endpoints
	 */
	public function register_export_endpoints() {
		register_rest_route( 'ezoic-cms/v1', '/export/initiate', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'initiate_export_event' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route( 'ezoic-cms/v1', '/export/cancel', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'cancel_export_event' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route('ezoic-cms/v1', '/export/verify', array(
			'methods' => \WP_REST_Server::READABLE,
			'callback' => array( $this, 'verify_export_files' ),
			'permission_callback' => '__return_true',
			'show_in_index'       => false,
		));

		register_rest_route('ezoic-cms/v1', '/export/cleanup', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'cleanup_export_files' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route( 'ezoic-cms/v1', '/export/retry', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'retry_upload' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index'       => false,
		));

		register_rest_route( 'ezoic-cms/v1', '/export/menus', array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'menus_export' ),
			'permission_callback' => array( $this, 'check_headers' ),
			'show_in_index' => false,
		));
	}

	public function menus_export() {
		$sync = new Ezoic_CMS_Sync();
		$menus = $sync->get_all_menus();
		$response = new \WP_REST_Response( $menus, 200 );
		return $response;
	}

	/*
	 *	Wordpress to Ezoic CMS Export
	 *	Process exports all database tables and image files to Ezoic CMS
	 *	1. Extract data from database (and get image files)
	 *	2. Package files in .zip file
	 *	3. Upload file to Ezoic CMS import server
	 */

	public function initiate_export_event ( $request ) {
		// Activate the logo sync when export starts
		$sync = new Ezoic_CMS_Sync;
		$sync->logo_force_update("true");

		return $this->run_or_schedule_export( $request );
	}

	public function export( $tenant ) {
		// notify export has started
		$this->update_status( 'In Progress' );

		//prevent timeout for long-running script
		set_time_limit( 0 );

		$db = new Ezoic_Content_Database();
		$exported_tables = $db->export_database( $this->get_database_tablenames() );
		if ( Ezoic_Content_Util::is_error( $exported_tables ) ) {
			$this->send_alert( 'Failed to export database ' . $exported_tables );
			return $exported_tables;
		}

		$this->update_status( 'Assets Exporting' );

		$file = new Ezoic_Content_File();
		$image_archive_created = $file->create_asset_archives();
		if ( Ezoic_Content_Util::is_error( $image_archive_created ) ) {
			$this->send_alert( 'Failed to create image archive: ' . $image_archive_created );
			return $image_archive_created;
		}

		$assets_uploaded = $this->attempt_asset_archive_upload( $tenant );
		if ( Ezoic_Content_Util::is_error( $assets_uploaded ) ) {
			$this->send_alert( 'Failed to upload asset archives ' . $assets_uploaded );
			return $assets_uploaded;
		}

		$this->update_status( 'Assets Uploaded' );

		$export_archive_created = $file->package_export_files( $this->get_archive_name(), $this->get_export_filenames( false ) );
		if ( Ezoic_Content_Util::is_error( $export_archive_created ) ) {
			$this->send_alert( 'Failed to create export archive ' . $export_archive_created );
			return $export_archive_created;
		}

		$upload_attempted = $this->attempt_archive_upload( $tenant );
		if ( Ezoic_Content_Util::is_error( $upload_attempted ) ) {
			$this->send_alert( 'Failed to upload export archive ' . $upload_attempted );
			return $upload_attempted;
		}

		$file->cleanup_files( $this->get_export_filenames( true ) );

		// if no errors, return true
		$this->update_status( 'Export From Source Uploaded' );
		delete_transient( $this->get_transient_name() );
		return true;
	}

	protected function get_export_filenames( $include_assets ) {
		$temp_archive_paths = array();
		if ( $include_assets ) {
			foreach( glob( get_temp_dir() . '*_assets.zip' ) as $asset_archive ) {
				$temp_archive_paths[] = basename( $asset_archive );
			}
		}

		return array_merge(
			$temp_archive_paths,
			array(
				$this->get_archive_name()
			),
			Ezoic_Content_Database::get_database_table_files( $this->get_database_tablenames() )
		);
	}

	protected function get_database_tablenames() {
		return array(
			'terms',
			'posts',
			'postmeta',
			'term_relationships',
			'comments',
			'commentmeta',
			'options',
			'term_taxonomy',
			'termmeta',
			'users',
			'usermeta',
			'links'
		);
	}
}
