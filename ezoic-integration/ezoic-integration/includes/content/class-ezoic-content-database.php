<?php

namespace Ezoic_Namespace;

/**
 *
 * @package    Ezoic_CMS_File
 */
class Ezoic_Content_Database {

	private static $table_headers = array(
		'postmeta'				=> array( 'meta_id', 'post_id', 'meta_key', 'meta_value' ),
		'terms' 				=> array( 'term_id', 'name', 'slug', 'term_group' ),
		'termmeta'				=> array( 'meta_id', 'term_id', 'meta_key', 'meta_value' ),
		'term_relationships'	=> array( 'object_id', 'term_taxonomy_id', 'term_order' ),
		'term_taxonomy' 		=> array( 'term_taxonomy_id', 'term_id', 'taxonomy', 'description', 'parent', 'count' ),
		'commentmeta'			=> array( 'meta_id', 'comment_id', 'meta_key', 'meta_value' ),
		'usermeta'				=> array( 'umeta_id', 'user_id', 'meta_key', 'meta_value' ),
		'options'				=> array( 'option_id', 'option_name', 'option_value', 'autoload' ),
		'posts' 				=> array(
									'ID',
									'post_author',
									'post_date',
									'post_date_gmt',
									'post_content',
									'post_title',
									'post_excerpt',
									'post_status',
									'comment_status',
									'ping_status',
									'post_password',
									'post_name',
									'to_ping',
									'pinged',
									'post_modified',
									'post_modified_gmt',
									'post_content_filtered',
									'post_parent',
									'guid',
									'menu_order',
									'post_type',
									'post_mime_type',
									'comment_count'),
		'comments'				=> array(
									'comment_ID',
									'comment_post_ID',
									'comment_author',
									'comment_author_email',
									'comment_author_url',
									'comment_author_IP',
									'comment_date',
									'comment_date_gmt',
									'comment_content',
									'comment_karma',
									'comment_approved',
									'comment_agent',
									'comment_type',
									'comment_parent',
									'user_id' ),
		'users'					=> array(
									'ID',
									'user_login',
									'user_pass',
									'user_nicename',
									'user_email',
									'user_url',
									'user_registered',
									'user_activation_key',
									'user_status',
									'display_name' ),
		'links' 				=> array(
									'link_id',
									'link_url',
									'link_name',
									'link_image',
									'link_target',
									'link_description',
									'link_visible',
									'link_owner',
									'link_rating',
									'link_updated',
									'link_rel',
									'link_notes',
									'link_rss' ),
	);

	public function export_database( $tables ) {
		foreach( $tables as $table ) {
			// Exports WP DB table to CSV and saves to /tmp directory
			$export_result = $this->export_database_table( $table );
			if ( Ezoic_Content_Util::is_error( $export_result ) ) {
				error_log( '[CMS EXPORT] Error on table: ' . $table . ' - ' . $export_result );
				return $export_result;
			}
		}
	}

	public static function get_database_tables( $tables ) {
		return array_map( array( 'self', 'get_wp_table_name' ), $tables );
	}

	public static function get_database_table_files( $tables ) {
		return array_map( array( 'self', 'get_wp_file_name' ), $tables );
	}

	public static function get_wp_table_name( $table ) {
		global $wpdb;
		return $wpdb->prefix . $table;
	}

	public static function get_wp_file_name( $table ) {
		return 'wp_' . $table . '.csv';
	}

	private function export_database_table( $tablename ) {
		$batch_size = 10000;

		$total_rows = $this->get_total_rows( $tablename );
		if ( Ezoic_Content_Util::is_error( $total_rows ) ) {
			return $total_rows;
		}

		if ( $total_rows > 0 ) {
			$iterations = $this->calculate_iterations( $total_rows, $batch_size );
			return $this->export_table_to_csv( $batch_size, $iterations, $tablename );
		}

		return false;
	}

	/**
	 * finds the total number of rows we will be sending over for posts, terms, term relationships and image relationships
	 * This later gets divided into chunks to be sent to Ezoic CMS
	 */
	private function get_total_rows( $table ) {
		global $wpdb;

		$wp_tablename = self::get_wp_table_name( $table );
		$query = "SELECT COUNT(*) AS total FROM `{$wp_tablename}`";

		$result = $wpdb->get_results( $query, ARRAY_A );
		if (!$result || count( $result ) === 0 || $wpdb->last_error !== '') {
			return 'Error - Expected count result for table: ' . $table;
		}

		return intval( $result[0]["total"] );
	}

	private function calculate_iterations( $total_rows, $batch_size ) {
		if ( $total_rows === 0 ) {
			return 0;
		}

		$rounded_iterations = 0;
		if ( $total_rows >= $batch_size ) {
			$num_iterations = $total_rows / $batch_size;
			$rounded_iterations = round( $num_iterations, 0, PHP_ROUND_HALF_DOWN );

			if ( $total_rows > ( $rounded_iterations * $batch_size ) ) {
				return $rounded_iterations + 1;
			}

			return $rounded_iterations;
		}

		return 1;
	}

	private function export_table_to_csv( $batch_size, $iterations, $table ) {
		global $wpdb;

		// Use a static file prefix for import server
		$filepath = get_temp_dir() . self::get_wp_file_name( $table );
		$fp = fopen( $filepath, 'w' );
		if ( !$fp ) {
			// return error string here to show we could not access fs
			return "Failed to open file for writing: " . $output_file;
		}

		$columns = self::$table_headers[ $table ];

		// Add header to CSV file
		if ( $table === 'comments' ) {
			// Adds a custom column to the comments table
			// will be used to associate the url path of page the comment appears on to the comment
			$put_csv_result = fputcsv( $fp, array_merge( $columns, array( 'post_url' ) ) );
		} else {
			$put_csv_result = fputcsv( $fp, $columns );
		}

		if ( !$put_csv_result ) {
			fclose( $fp );
			return "Failed to put CSV headers into file: " . $output_file;
		}

		$offset = 0;
		$append = false;
		for( $i = 1; $i <= $iterations; $i++ ) {
			$columns_as_string = implode(',', $columns);
			$wp_tablename = self::get_wp_table_name( $table );
			$query = "SELECT $columns_as_string FROM `$wp_tablename` LIMIT $offset, $batch_size";

			$rows = $wpdb->get_results($query, ARRAY_A);
			if ( !$rows || count( $rows ) === 0 || $wpdb->last_error !== '' ) {
				return 'Error - Expected rows in result for table: ' . $table;
			}

			foreach ( $rows as $row ) {
				if ( $table === 'comments' ) {
					$postID = $row['comment_post_ID'];
					$row['post_url'] = parse_url( get_permalink( $postID ), PHP_URL_PATH );
				}
				$put_csv_result = $this->fputcsv_escaped( $fp, $row );
				if (!$put_csv_result) {
					// return error string and close file
					fclose( $fp );
					return "Failed to put row into file: " . $row;
				}
			}

			$offset += $batch_size;
		}

		fclose($fp);

		return true;
	}

	/**
	 * Write to the CSV file, ensuring escaping works across versions of
	 * PHP.
	 *
	 * PHP 5.5.4 uses '\' as the default escape character. This is not RFC-4180 compliant.
	 * \0 disables the escape character.
	 *
	 * @see https://bugs.php.net/bug.php?id=43225
	 * @see https://bugs.php.net/bug.php?id=50686
	 * @see https://github.com/woocommerce/woocommerce/issues/19514
	 * @see https://github.com/woocommerce/woocommerce/pull/19678
	 * @param resource $handle Resource we are writing to.
	 * @param array    $export_row Row to export.
	 */
	private function fputcsv_escaped( $handle, $export_row ) {
		if ( version_compare( PHP_VERSION, '5.5.4', '<' ) ) {
			ob_start();
			$temp = fopen( 'php://output', 'w' );
    		fputcsv( $temp, $export_row, ",", '"' );
			fclose( $temp );
			$row = ob_get_clean();
			$row = str_replace( '\\"', '\\""', $row );
			return fwrite( $handle, $row );
		} else {
			return fputcsv( $handle, $export_row, ",", '"', "\0" );
		}
	}
}
