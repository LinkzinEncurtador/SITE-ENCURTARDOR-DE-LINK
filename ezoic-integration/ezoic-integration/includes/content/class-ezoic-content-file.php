<?php

namespace Ezoic_Namespace;

/**
 *
 * @package    Ezoic_CMS_File
 */
class Ezoic_Content_File {
	/**
	 * Recursively determines the file paths for all images in the uploads directory in the filesystem
	 * the default storage place for all WP images
	 */
	private function uploaded_image_filepaths() {
		// images might not be located here
		$uploads_dir = $_SERVER['DOCUMENT_ROOT'] . '/wp-content' . '/uploads';

		// test opening dir first, otherwise get fatal error from RecursiveIteratorIterator if fopen fails in constructor
		$exists_dir = fopen( $uploads_dir, "r");
		if ( !$exists_dir ) {
			return "could not open directory to get images";
		} else {
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $uploads_dir ),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);
		}

		$paths = array();

		foreach ( $files as $name => $file ) {
			if ( !$file->isDir() ) {
				// Get real and relative path for current file
				$file_path = $file->getRealPath();
				$relative_path = substr( $file_path, strlen( $uploads_dir ) + 1 );
				$file_size = filesize( $file_path );

				// make sure its a full sized image. if it is, add to archive
				if ( !preg_match( '/[0-9]{3}x[0-9]{3}/', $relative_path ) ) {
					$paths[] = array(
						'full_path' => $file_path,
						'relative_path' => $relative_path,
						'file_size' => $file_size
					);
				}
			}
		}

		// make column array for sorting
		$sizes = array();
		foreach ($paths as $key => $row) {
			$sizes[$key] = $row['file_size'];
		}

		// Sort descending by file size
		array_multisort($sizes, SORT_DESC, $paths);

		return $paths;
	}

	public function create_asset_archives() {
		$max_archive_size = 52428800; // 50 MB in bytes

		$image_batch_size = 10;
		$image_paths = $this->uploaded_image_filepaths();
		if ( Ezoic_Content_Util::is_error( $image_paths ) ) {
			return $image_paths;
		}

		$temp_dir = get_temp_dir();
		$asset_archive_count = 0;
		$bin_count = 0;
		// Use assoc array to 'pack bins' of files
		// Each bin will have an array of images (full + relative file path)
		// with a bin size
		// Aiming to fill each bin as much as possible
		$bins = array();
		foreach ( $image_paths as $image ) {
			$file_size = $image['file_size'];
			$file_path = $image['full_path'];

			if ( $file_size > $max_archive_size ) {
				if ( is_readable( $file_path ) ) {
					// Package into separate archive
					$archive_filepath = $temp_dir . $asset_archive_count . '_assets.zip';
					$zip = new \ZipArchive();
					if ( $zip->open( $archive_filepath, ( \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) !== true ) {
						return "Failed to create zip file for images";
					}

					$relative_path = $image['relative_path'];
					$res = $zip->addFile( $file_path, $relative_path );
					if ( !$res ) {
						return "Could not add image file to zip: " . $file_path;
					}
					$res = $zip->close();
					if ( !$res ) {
						$status = $zip->getStatusString();
						return "Failed to close images zip archive " . $status;
					}
					$asset_archive_count += 1;
				}
			} else {
				// Should be able to fit file into bin
				$packed = false;
				for ($i=0; $i < $bin_count; $i++) {
					if ( $bins[$i]['bin_size'] + $file_size <= $max_archive_size ) {
						// add archive to bin
						$bins[$i]['files'][] = $image;
						$bins[$i]['bin_size'] += $file_size;
						// update bin size
						$packed = true;
					}
				}

				if ( !$packed ) {
					// Didn't find bin to add file to
					// Create new bin and add file
					$bins[] = array(
						'bin_size' => $file_size,
						'files' => array( $image ),
					);
					$bin_count += 1;
				}
			}
		}

		// Each bin of files gets packaged into an archive
		foreach ( $bins as $index => $bin ) {
			$archive_filepath = $temp_dir . $asset_archive_count . '_assets.zip';
			$zip = new \ZipArchive();
			if ( $zip->open( $archive_filepath, ( \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) !== true ) {
				return "Failed to create zip file for images";
			}

			foreach( $bin['files'] as $file_index => $image ) {
				$file_path = $image['full_path'];
				$relative_path = $image['relative_path'];
				if ( is_readable( $file_path ) ) {
					$res = $zip->addFile( $file_path, $relative_path );
					if ( !$res ) {
						return "Could not add image file to zip: " . $file_path;
					}
				}
			}
			$res = $zip->close();
			if ( !$res ) {
				$status = $zip->getStatusString();
				return "Failed to close images zip archive " . $status;
			}

			$asset_archive_count += 1;
		}

		return true;
	}

	/**
	 * Zip all relevant files
	 */
	public function package_export_files( $archive_name, $export_files ) {
		$temp_dir = get_temp_dir();
		$archive_filepath = $temp_dir . $archive_name;
		$zip = new \ZipArchive();

		$archive_opened = $zip->open( $archive_filepath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );

		if ( $archive_opened !== true ) {
			return "Failed to open zip archive at location: " . $archive_filepath . " with error: " . print_r( $archive_opened, true );
		}

		foreach ( $export_files as $file ) {
			$file_path = $temp_dir . $file;

			if ( is_readable( $file_path ) ) {
				$file_added = $zip->addFile( $file_path, $file );
				if ( !$file_added ) {
					return "Failed to add file to zip archive at location: " . $archive_filepath;
				}
			}
		}

		// Zip archive will be created only after closing object
		$archive_closed = $zip->close();
		if ( !$archive_closed ) {
			return "Failed to close zip archive at location: " . $archive_filepath;
		}
		return true;
	}

	public function verify_files( $files_to_check ) {
		$temp_dir = get_temp_dir();
		$filesizes = array();
		foreach ( $files_to_check as $file ) {
			$file_path = $temp_dir . $file;
			if ( file_exists( $file_path ) ) {
				$filesizes[$file] = filesize( $file_path );
			} else {
				$filesizes[$file] = 'Does not exist';
			}
		}
		return $filesizes;
	}

	public function cleanup_files( $files_to_delete ) {
		$temp_dir = get_temp_dir();
		foreach ( $files_to_delete as $file ) {
			$file_path = $temp_dir . $file;
			if ( file_exists( $file_path ) ) {
				error_log( '[CMS EXPORT] Cleanup - Deleting ' . $file_path );
				unlink( $file_path );
			} else {
				error_log( '[CMS EXPORT] Cleanup - Did not find ' . $file_path );
			}
		}
	}


}
