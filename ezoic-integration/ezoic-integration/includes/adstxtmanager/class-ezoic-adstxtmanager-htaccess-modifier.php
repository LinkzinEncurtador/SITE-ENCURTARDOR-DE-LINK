<?php

namespace Ezoic_Namespace;

/**
 * Class Ezoic_AdsTxtManager_Htaccess_Modifier
 * @package Ezoic_Namespace
 */
class Ezoic_AdstxtManager_Htaccess_Modifier implements iAdsTxtManager_Solution
{

	public function SetupSolution()
	{
		$this->GenerateHTACCESSFile();

		$fileModifier = new Ezoic_AdsTxtManager_File_Modifier();
		$fileModifier->SetupSolution();

		$redirect_result = Ezoic_AdsTxtManager::ezoic_verify_adstxt_redirect();
		update_option('ezoic_adstxtmanager_status', $redirect_result);
	}

	public function TearDownSolution()
	{
		$this->RemoveHTACCESSFile();

		$fileModifier = new Ezoic_AdsTxtManager_File_Modifier();
		$fileModifier->TearDownSolution();

		if (get_option('ezoic_adstxtmanager_status') !== false) {
			delete_option('ezoic_adstxtmanager_status');
		}
	}

	private function determineHTACCESSRootPath()
	{
		return Ezoic_Integration_Path_Sanitizer::get_home_path();
	}

	public function GenerateHTACCESSFile()
	{
		global $wp, $wp_filesystem;
		$message = '';
		$rootPath = $this->determineHTACCESSRootPath();

		if ($rootPath === false) {
			$message = "Cannot determine website root path for .htaccess file.";
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			return;
		}

		// Check if wp_filesystem is properly initialized
		if (empty($wp_filesystem) || !is_object($wp_filesystem)) {
			$message = "WordPress filesystem is not properly initialized. Please check your server configuration or contact your hosting provider.";
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			return;
		}

		$filePath = $rootPath . ".htaccess";

		// Safely check file operations with error handling
		try {
			$file_exists = $wp_filesystem->exists($filePath);
			$file_readable = $file_exists ? $wp_filesystem->is_readable($filePath) : false;
			$file_writable = $file_exists ? $wp_filesystem->is_writable($filePath) : false;
		} catch (\Exception $e) {
			$message = "Error accessing .htaccess file: " . $e->getMessage() . ". Please check your server configuration or contact your hosting provider.";
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			return;
		}

		if (empty($filePath) || !$file_exists || !$file_readable || !$file_writable) {
			$message = "Cannot access your .htaccess file. Please check that the file exists and has proper write permissions.";
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			return;
		}

		$this->RemoveHTACCESSFile();

		$adstxtmanager_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true);

		if (empty($adstxtmanager_id) || !is_int($adstxtmanager_id) || $adstxtmanager_id <= 0) {
			return;
		}

		// Sanitize domain extraction
		$domain = home_url($wp->request);
		$parsed_url = parse_url($domain);
		if (!$parsed_url || !isset($parsed_url['host'])) {
			$message = "Cannot determine domain for ads.txt redirect.";
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			return;
		}
		$domain = Ezoic_Integration_Path_Sanitizer::sanitize_domain($parsed_url['host']);
		if ($domain === false) {
			$message = "Invalid domain for ads.txt redirect.";
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			return;
		}

		try {
			$content = $wp_filesystem->get_contents($filePath);
		} catch (\Exception $e) {
			$message = "Error reading .htaccess file: " . $e->getMessage() . ". Please check your server configuration or contact your hosting provider.";
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			return;
		}

		// Create safe redirect URL
		$redirect_url = Ezoic_Integration_Path_Sanitizer::create_adstxt_redirect_url($adstxtmanager_id, $domain);
		if ($redirect_url === false) {
			$message = "Cannot create valid ads.txt redirect URL.";
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			return;
		}

		$atmContent = array(
			"#BEGIN_ADSTXTMANAGER_HTACCESS_HANDLER",
			'<IfModule mod_rewrite.c>',
			'Redirect 301 /ads.txt ' . $redirect_url,
			'</IfModule>',
			"#END_ADSTXTMANAGER_HTACCESS_HANDLER"
		);

		$atmFinalContent = implode("\n", $atmContent);
		$modifiedContent = $atmFinalContent . "\n" . $content;

		try {
			$success = $wp_filesystem->put_contents($filePath, $modifiedContent);
			@clearstatcache();

			if (!$success) {
				$message = "Unable to update your .htaccess file for ads.txt redirect. Please check file permissions or contact your hosting provider.";
			}
		} catch (\Exception $e) {
			$message = "Error writing to .htaccess file: " . $e->getMessage() . ". Please check your server configuration or contact your hosting provider.";
		}

		if (!empty($message)) {
			$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
			$adstxtmanager_status['message'] = $message;
			update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
		}
	}

	public function RemoveHTACCESSFile()
	{
		global $wp_filesystem;
		$rootPath = $this->determineHTACCESSRootPath();

		if ($rootPath === false) {
			return;
		}

		if (empty($wp_filesystem) || !is_object($wp_filesystem)) {
			return;
		}

		$filePath = $rootPath . ".htaccess";

		// Safely check file operations with error handling
		try {
			$file_exists = $wp_filesystem->exists($filePath);
			$file_writable = $file_exists ? $wp_filesystem->is_writable($filePath) : false;
		} catch (\Exception $e) {
			Ezoic_Integration_Logger::log_exception("Filesystem error during .htaccess cleanup: " . $e->getMessage(), 'AdsTxtManager');
			return;
		}

		if (empty($filePath) || !$file_exists || !$file_writable) {
			return;
		}

		try {
			$content = $wp_filesystem->get_contents($filePath);
		} catch (\Exception $e) {
			Ezoic_Integration_Logger::log_exception("Filesystem error reading .htaccess during cleanup: " . $e->getMessage(), 'AdsTxtManager');
			return;
		}

		if ($content === false) {
			return;
		}

		$lineContent = preg_split("/\r\n|\n|\r/", $content);
		$beginAtmContent = 0;
		$endAtmContent = 0;
		foreach ($lineContent as $key => $value) {
			if ($value == "#BEGIN_ADSTXTMANAGER_HTACCESS_HANDLER") {
				$beginAtmContent = $key;
			} elseif ($value == "#END_ADSTXTMANAGER_HTACCESS_HANDLER") {
				$endAtmContent = $key;
			}
		}

		if ($endAtmContent == 0) {
			return;
		}

		for ($i = $beginAtmContent; $i <= $endAtmContent; $i++) {
			unset($lineContent[$i]);
		}

		$modifiedContent = implode("\n", $lineContent);

		try {
			$success = $wp_filesystem->put_contents($filePath, $modifiedContent);
			if (!$success) {
				Ezoic_Integration_Logger::log_error("Failed to write modified .htaccess file", 'AdsTxtManager');
			}
		} catch (\Exception $e) {
			Ezoic_Integration_Logger::log_exception("Filesystem error writing .htaccess during cleanup: " . $e->getMessage(), 'AdsTxtManager');
		}
	}
}
