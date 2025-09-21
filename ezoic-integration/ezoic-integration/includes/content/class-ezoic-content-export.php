<?php

namespace Ezoic_Namespace;

abstract class Ezoic_Content_Export {
	abstract public function register_export_endpoints();
	abstract public function initiate_export_event( $request );
	abstract public function export( $tenant );
	abstract protected function get_export_filenames( $include_assets );
	abstract protected function get_database_tablenames();

	// Getters for child class properties
	abstract public function get_transient_name();
	abstract public function get_request_header();
	abstract public function get_cron_event_name();
	abstract public function get_archive_name();
	abstract public function get_module_name();

	public function check_headers( $request ) {
		// $is_export_request = $request->get_header( $this->get_request_header() );
		// if ( is_null( $is_export_request ) || $is_export_request != 'true' ) {
		// 	return false;
		// }

		return true;
	}

	protected function run_or_schedule_export( $request ) {
		// Return "Conflict" if already scheduled or in progress
		$response = new \WP_REST_Response( 'Failed to initiate export', 409 );

		// check for transient variable -- visible in options.php
		$ezoic_export_status = get_transient( $this->get_transient_name() );
		if ( $ezoic_export_status == false ) {
			$request_body = $request->get_json_params();

			if ( $request_body && isset( $request_body['tenant'] ) ) {
				if ( isset( $request_body['debug'] ) && $request_body['debug'] == 'true' ) {
					//	Synchronous export left for debugging
					return $this->run_debug_export( $request_body['tenant'] );
				} else {
					$response = $this->schedule_export_cron( $request_body['tenant'] );
				}
			} else {
				$response = new \WP_REST_RESPONSE( 'Missing Tenant Info', 400 );
			}

		} // else there is a scheduled export or export in progress
		return $response;
	}

	protected function run_debug_export( $tenant ) {
		set_transient( $this->get_transient_name(), time() );
		$result = $this->export( $tenant );

		if ( Ezoic_Content_Util::is_error( $result ) ) {
			$response = new \WP_REST_Response( "Something went wrong during Export. Error: " . print_r( $result, true ), 500);
		} else {
			$response = new \WP_REST_Response( "Success, Export files built and sent", 200 );
		}

		return $response;
	}

	protected function schedule_export_cron( $tenant ) {
		// Register cron job that calls export init action
		// See class-ezoic-cms.php
		$schedule_error = wp_schedule_single_event( time(), $this->get_cron_event_name(), array( $tenant ), true);
		if ( is_wp_error( $schedule_error ) ) {
			// Failed to initiate export
			// Return WP Error object as response
			$response = $schedule_error;
		} else {
			// Store when init job was created
			// Will return 202
			set_transient( $this->get_transient_name(), time() );
			$this->update_status( 'Scheduled' );
			$response = new \WP_REST_Response( 'Success: Export initiated' , 202);
		}
		return $response;
	}

	public function cancel_export_event() {
		$ezoic_export_status = get_transient( $this->get_transient_name() );
		if ( $ezoic_export_status != false ) {
			delete_transient( $this->get_transient_name() );
		}

		$schedule_error = wp_clear_scheduled_hook( $this->get_cron_event_name() );
		if ( is_wp_error( $schedule_error ) ) {
			return new \WP_REST_Response( 'Failed to unschedule export', 500 );
		}

		return new \WP_REST_Response( 'Success: Export Canceled' , 200);
	}

	public function retry_upload( $request ) {
		$response = new \WP_REST_Response( 'Bad Request', 400 );

		$request_body = $request->get_json_params();
		if ( $request_body && isset( $request_body['tenant'] ) ) {
			$result = $this->attempt_archive_upload( $request_body['tenant'] );

			if ( is_string( $result ) ) {
				\error_log( $result );
				$response = new \WP_REST_Response( 'Failed to upload export', 500 );
			} else {
				$response = new \WP_REST_Response( 'Success', 200 );
			}
		}
		return $response;
	}

	public function attempt_asset_archive_upload( $tenant ) {
		$temp_dir = get_temp_dir();
		$upload_url = "https://content-backend.ezoic.com/api/v1/tenants/$tenant/upload/assets";

		$host = Ezoic_Content_Request::find_host();
		if ( $host === "" ) {
			return "Could not find value for host";
		}

		$ezoic_auth = new Ezoic_Auth();
		$token = $ezoic_auth->get_token();
		if ( $token == false ) {
			\error_log( 'Unable to get authorization token for CMS export. Not sending request' );
			return 'Unable to get authorization token for CMS export';
		}

		foreach( glob( $temp_dir . '*_assets.zip' ) as $filepath ) {
			$filename = basename( $filepath );
			$upload_result = Ezoic_Content_Request::send_file_upload( $upload_url, $filepath, $filename,
			$token );

			if ( Ezoic_Content_Util::is_error( $upload_result ) ) {
				\error_log( 'Failed to upload asset archive: ' . $filepath );
				return $upload_result;
			}
		}

		$notify_response = Ezoic_Content_Request::notify_asset_upload_complete( $tenant );
		if ( $notify_response === "" ) {
			\error_log( 'Failed to notify that all assets have been uploaded' );
			return 'Failed to notify that all assets have been uploaded';
		}

		return true;
	}

	public function attempt_archive_upload( $tenant ) {
		$temp_dir = get_temp_dir();
		$export_archive = $temp_dir . $this->get_archive_name();
		$upload_url = "https://content-backend.ezoic.com/api/v1/tenants/$tenant/upload/wp";

		if ( !file_exists( $export_archive ) ) {
			return "Failed to find export archive for upload";
		}

		$host = Ezoic_Content_Request::find_host();
		if ( $host === "" ) {
			return "Could not find value for host";
		}
		$filename = $host . "-dInE96LZv7Jp.zip";

		$ezoic_auth = new Ezoic_Auth();
		$token = $ezoic_auth->get_token();
		if ( $token == false ) {
			\error_log( 'Unable to get authorization token for CMS export. Not sending request' );
			return 'Unable to get authorization token for CMS export';
		}
		return Ezoic_Content_Request::send_file_upload( $upload_url, $export_archive, $filename,
			$token );
	}

	protected function send_alert( $failureInfo ) {
		delete_transient( $this->get_transient_name() );

		$message = 'Export Failure: ' . $failureInfo;

		return Ezoic_Content_Request::send_export_alert( $message, $this->get_module_name() );
	}

	protected function update_status( $status ) {
		return Ezoic_Content_Request::send_export_status( $status, $this->get_module_name() );
	}

	// function to return filesizes of built files in the temp dir
	public function verify_export_files() {
		$file = new Ezoic_Content_File();
		$filesizes = $file->verify_files( $this->get_export_filenames( true ) );

		$response = new \WP_REST_Response( json_encode( $filesizes ), 200 );
		return $response;
	}

	// WARNING: Also delete packaged zip file!!!
	public function cleanup_export_files() {
		$file = new Ezoic_Content_File();
		$file->cleanup_files( $this->get_export_filenames( true ) );

		$response = new \WP_REST_Response( 'Export files cleanup completed', 200 );
		return $response;
	}
}
