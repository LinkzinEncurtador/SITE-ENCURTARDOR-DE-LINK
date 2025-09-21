<?php

namespace Ezoic_Namespace;

/**
 * Class Ezoic_AdsTxtManager
 * @package Ezoic_Namespace
 */
class Ezoic_AdsTxtManager extends Ezoic_Feature
{

	const GET_ADSTXTMANAGER_ID_ENDPOINT	= EZOIC_URL . '/pub/v1/wordpressintegration/v1/adstxtmanager?d=';

	protected $wp_filesystem;
	protected $is_public_enabled;
	protected $is_admin_enabled;

	public function __construct()
	{
		$this->is_public_enabled = true;
		$this->is_admin_enabled  = true;
		$this->setup_wp_filesystem();
	}

	public function register_public_hooks($loader)
	{
		// include these for non is_admin() calls
		//$loader->add_action('init', $this, 'ezoic_handle_adstxt', 1);
		$loader->add_action('parse_request', $this, 'ezoic_handle_adstxt', 1);
	}

	public function register_admin_hooks($loader)
	{
		// Only show notices on ads.txt settings page
		$loader->add_action('admin_init', $this, 'register_adstxt_notices');

		$solutionFactory = new Ezoic_AdsTxtManager_Solution_Factory();
		$adsTxtSolution = $solutionFactory->GetBestSolution();
		$loader->add_action('update_option_adstxtmanager_id', $adsTxtSolution, 'SetupSolution');
	}

	public function register_adstxt_notices()
	{
		global $pagenow;
		if (in_array($pagenow, array('options-general.php')) && (isset($_GET['page']) && $_GET['page'] == EZOIC__PLUGIN_SLUG) && isset($_GET['tab']) && $_GET['tab'] == 'adstxtmanager_settings') {
			add_action('admin_notices', array($this, 'ezoic_adstxtmanager_display_notice'));
		}
	}

	public static function ezoic_adstxtmanager_id($refresh = false)
	{
		static $adstxtmanager_id = null;
		if (is_null($adstxtmanager_id) || $refresh) {
			$adstxtmanager_id = (int)get_option('ezoic_adstxtmanager_id');
		}

		return $adstxtmanager_id;
	}


	public static function ezoic_adstxtmanager_auto_detect()
	{
		$auto_detect = false;
		$adstxtmanager_auto_detect = (get_option('ezoic_adstxtmanager_auto_detect', 'on') === 'on');
		if ($adstxtmanager_auto_detect === true) {
			$auto_detect = $adstxtmanager_auto_detect;
		}
		return $auto_detect;
	}

	/**
	 * Initialize the WP file system.
	 *
	 * @return object
	 */
	private function setup_wp_filesystem()
	{
		global $wp_filesystem;

		if (empty($wp_filesystem)) {
			$file_path = ABSPATH . '/wp-admin/includes/file.php';
			if (file_exists($file_path)) {
				require_once $file_path;

				// Try to initialize with 'direct' method first to avoid FTP issues
				$filesystem_result = WP_Filesystem('direct');

				// If direct method fails, try default method
				if (!$filesystem_result && empty($wp_filesystem)) {
					WP_Filesystem();
				}

				// Log if filesystem still failed to initialize
				if (empty($wp_filesystem)) {
					Ezoic_Integration_Logger::log_debug("WordPress filesystem failed to initialize", 'AdsTxtManager');
				}
			}
		}

		// Return true/false based on filesystem initialization success
		return !empty($wp_filesystem);
	}

	public function ezoic_handle_adstxt($wp)
	{
		if ($wp->request === 'ads.txt') {
			$adstxtmanager_id = self::ezoic_adstxtmanager_id(true);
			if (is_int($adstxtmanager_id) && $adstxtmanager_id > 0) {
				$domain = wp_parse_url(get_site_url(), PHP_URL_HOST);
				$domain = preg_replace('/^www\./', '', $domain);

				wp_redirect('https://srv.adstxtmanager.com/' . $adstxtmanager_id . '/' . $domain, 301);
				exit();
			}
		}
	}

	/**
	 * @return array
	 */
	public static function ezoic_verify_adstxt_redirect()
	{
		$cache_buster = '?v=' . time() . '&r=' . wp_rand(1000, 9999);
		$ads_txt_url = home_url('/ads.txt' . $cache_buster);

		$response = wp_remote_get($ads_txt_url, array(
			'timeout' => 5,
			'redirection' => 0,
			'headers' => array(
				'Cache-Control' => 'no-cache, no-store, must-revalidate',
				'Pragma' => 'no-cache',
				'Expires' => '0'
			),
		));

		if (!is_wp_error($response)) {
			$response_code = wp_remote_retrieve_response_code($response);
			$response_headers = wp_remote_retrieve_headers($response);

			if ($response_code >= 301 && $response_code <= 308 && isset($response_headers['location'])) {
				$redirect_location = $response_headers['location'];

				if (strpos($redirect_location, 'srv.adstxtmanager.com') !== false) {
					$final_response = wp_remote_get($redirect_location, array(
						'timeout' => 5,
						'redirection' => 0,
						'headers' => array(
							'Cache-Control' => 'no-cache, no-store, must-revalidate',
							'Pragma' => 'no-cache',
							'Expires' => '0'
						),
					));

					if (!is_wp_error($final_response)) {
						$final_response_code = wp_remote_retrieve_response_code($final_response);

						if ($final_response_code == 404) {
							return array('status' => false, 'error' => 'invalid_atm_id', 'message' => 'The ATM ID you entered was not found. Please check your Publisher Dashboard for the correct ID.');
						} elseif ($final_response_code == 200) {
							return array('status' => true);
						}
					}

					return array('status' => true);
				}
			}
		}

		return array(
			'status' => false,
			'error' => 'redirect_failed',
			'message' => 'Your ads.txt is not redirecting properly. Please check your website configuration, including permalink settings (Settings > Permalinks), .htaccess file permissions, and possible plugin conflicts that may affect URL rewriting or redirects.'
		);
	}

	function ezoic_adstxtmanager_display_notice()
	{
		if (self::ezoic_should_show_adstxtmanager_setting() == false) {
			return;
		}

		$adstxtmanager_id = self::ezoic_adstxtmanager_id(true);
		$detection_result = null;

		// Run auto-detection if enabled
		if (self::ezoic_adstxtmanager_auto_detect()) {
			$detection_result = self::ezoic_detect_adstxtmanager_id();
		}

		$adstxtmanager_id = self::ezoic_adstxtmanager_id(true);

		// For manual ATM ID entries, verify redirect status
		if (!self::ezoic_adstxtmanager_auto_detect() && !empty($adstxtmanager_id) && $adstxtmanager_id > 0) {
			$redirect_result = self::ezoic_verify_adstxt_redirect();
			update_option('ezoic_adstxtmanager_status', $redirect_result);
		}

		$adstxtmanager_status = self::ezoic_adstxtmanager_status(true);
		if (self::ezoic_adstxtmanager_auto_detect() && $detection_result && isset($detection_result['error'])) {
			switch ($detection_result['error']) {
				case 'connection_error':
					$has_cdn_key = !empty(Ezoic_Cdn::ezoic_cdn_api_key());
?>
					<div class="notice notice-error">
						<p><strong>Connection Error:</strong> Unable to connect to Ezoic servers to detect your Ads.txt Manager ID.</p>
						<?php if (!$has_cdn_key): ?>
							<p>Try adding your <a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=cdn_settings">CDN API Key</a> for better connectivity, or contact support if the issue persists.</p>
						<?php else: ?>
							<p>Please check your server connection and try again later, or contact support if the issue persists.</p>
						<?php endif; ?>
					</div>
				<?php
					break;
				case 'server_error':
				?>
					<div class="notice notice-error">
						<p><strong>Server Error:</strong> Ezoic servers returned an error (HTTP <?php echo isset($detection_result['code']) ? $detection_result['code'] : 'unknown'; ?>). Please try again later or contact support if the issue persists.</p>
					</div>
				<?php
					break;
				case 'setup_failed':
				?>
					<div class="notice notice-error">
						<p><strong>Setup Failed:</strong> <?php echo isset($detection_result['message']) ? esc_html($detection_result['message']) : 'Unable to configure ads.txt redirect. Please contact support for assistance.'; ?></p>
					</div>
				<?php
					break;
				case 'not_enabled':
				default:
				?>
					<div class="notice notice-warning">
						<p><strong>Ads.txt Manager Setup Required</strong></p>
						<p>Automatic Detection is enabled but no Ads.txt Manager ID was found.</p>
						<p>Please visit your <a href="https://pubdash.ezoic.com/ezoicads/adtransparency" target="_blank">Publisher Dashboard</a> to complete your Ads.txt setup.</p>
					</div>
				<?php
					break;
			}
		} elseif (self::ezoic_adstxtmanager_auto_detect() && $detection_result && isset($detection_result['success']) && $detection_result['success'] && !empty($adstxtmanager_id) && $adstxtmanager_id > 0) {
			if (isset($adstxtmanager_status['status']) && $adstxtmanager_status['status'] === true) {
				?>
				<div class="notice notice-success">
					<p><strong>Success:</strong> Your ads.txt redirect is successfully set up.&nbsp;&nbsp;&nbsp;<a class="button button-info" href="/ads.txt?<?php echo substr(md5(mt_rand()), 0, 10); ?>" target="_blank">View Ads.txt</a></p>
				</div>
			<?php
			}
		} else if (!empty($adstxtmanager_id) && $adstxtmanager_id > 0) {
			if (isset($adstxtmanager_status['status']) && $adstxtmanager_status['status'] === true) {
			?>
				<div class="notice notice-success">
					<p><strong>Success:</strong> Your ads.txt redirect is successfully set up.&nbsp;&nbsp;&nbsp;<a class="button button-info" href="/ads.txt?<?php echo substr(md5(mt_rand()), 0, 10); ?>" target="_blank">View Ads.txt</a></p>
				</div>
				<?php
			} else if (isset($adstxtmanager_status['status']) && $adstxtmanager_status['status'] === false) {
				if (isset($adstxtmanager_status['error']) && $adstxtmanager_status['error'] === 'invalid_atm_id') {
				?>
					<div class="notice notice-error">
						<p><strong>Invalid Ads.txt Manager ID:</strong> The ID you entered doesn't exist in your Ezoic account.</p>
						<p>Your ads.txt redirect is working correctly, but it needs a valid Ads.txt Manager ID to function properly.</p>
						<p><strong>To fix this:</strong></p>
						<ul style="margin-left: 20px; list-style: disc;">
							<li style="margin-bottom: 8px;"><strong>Enable Auto-Detection</strong> below to automatically find and use the correct ID</li>
							<li style="margin-bottom: 8px;">Or check your correct Ads.txt Manager ID in your <a href="https://pubdash.ezoic.com/ezoicads/adtransparency" target="_blank"><strong>Ezoic Publisher Dashboard</strong></a></li>
						</ul>
					</div>
				<?php
				} else {
				?>
					<div class="notice notice-warning">
						<p><strong>Setup Issue:</strong> Your ads.txt redirect isn't working properly.</p>
						<p>This could be due to server configuration, plugin conflicts, or file permissions. Try refreshing this page, or contact support if the issue persists.</p>
					</div>
<?php
				}
			}
		}

		if (!is_int($adstxtmanager_id)) {
			delete_option('ezoic_adstxtmanager_id');
		}
	}

	public static function ezoic_should_show_adstxtmanager_setting()
	{
		if (!Ezoic_Integration_Admin::is_cloud_integrated() || Ezoic_Integration_Admin::is_javascript_integrated()) {
			return true;
		}

		return false;
	}

	public static function ezoic_adstxtmanager_status($refresh = false)
	{
		static $adstxtmanager_status = null;
		if (is_null($adstxtmanager_status) || $refresh) {
			// Use cached status from WordPress options instead of checking redirect on every call
			$cached_status = get_option('ezoic_adstxtmanager_status');
			if ($cached_status && isset($cached_status['status'])) {
				$adstxtmanager_status = $cached_status;
			} else {
				// Fallback: if no cached status, check redirect (happens on first setup)
				$adstxtmanager_status = array();
				$adstxtmanager_id = self::ezoic_adstxtmanager_id(true);

				if (!empty($adstxtmanager_id) && $adstxtmanager_id > 0) {
					$redirect_result = self::ezoic_verify_adstxt_redirect();
					$adstxtmanager_status = $redirect_result;
				} else {
					$adstxtmanager_status = array('status' => false, 'error' => 'no_atm_id', 'message' => 'Please enter your ATM ID or enable auto-detection to set up ads.txt management.');
				}
			}
		}

		return $adstxtmanager_status;
	}

	public static function ezoic_detect_adstxtmanager_id()
	{
		if (!self::ezoic_adstxtmanager_auto_detect()) {
			return array('success' => false, 'error' => 'disabled');
		}

		$domain = Ezoic_Integration_Request_Utils::get_domain();
		$requestURL = self::GET_ADSTXTMANAGER_ID_ENDPOINT . $domain;

		if (Ezoic_Cdn::ezoic_cdn_api_key() != null) {
			$requestURL .= '&developerKey=' . Ezoic_Cdn::ezoic_cdn_api_key();
			$token = Ezoic_Cdn::ezoic_cdn_api_key();
		} else {
			$token = Ezoic_Integration_Authentication::get_token();
		}

		if ($token != '') {
			$response = wp_remote_get($requestURL, array(
				'method'		=> 'GET',
				'timeout'	=> '10',
				'headers'	=> array(
					'Authentication' => 'Bearer ' . $token
				),
			));

			if (!is_wp_error($response)) {
				$response_code = wp_remote_retrieve_response_code($response);

				if ($response_code !== 200 && $response_code !== 404) {
					return array('success' => false, 'error' => 'server_error', 'code' => $response_code);
				}

				$body = wp_remote_retrieve_body($response);
				$deserialized = json_decode($body);
				if ($deserialized) {
					if ($deserialized->status && $deserialized->data) {
						$data = $deserialized->data;
						$new_atm_id = (int) $data->ads_txt_manager_id;

						update_option('ezoic_adstxtmanager_id', $new_atm_id);

						if ($new_atm_id > 0 && self::ezoic_adstxtmanager_auto_detect()) {
							$redirect_result = self::ezoic_verify_adstxt_redirect();
							if (!$redirect_result['status']) {
								$solutionFactory = new Ezoic_AdsTxtManager_Solution_Factory();
								$adsTxtSolution = $solutionFactory->GetBestSolution();
								$adsTxtSolution->SetupSolution();

								$redirect_result = self::ezoic_verify_adstxt_redirect();
								if (!$redirect_result['status']) {
									$error_message = isset($redirect_result['message']) && !empty($redirect_result['message'])
										? $redirect_result['message']
										: 'Unable to set up ads.txt redirect automatically. Please contact support or try manual setup.';

									return array('success' => false, 'error' => 'setup_failed', 'message' => $error_message);
								}
							}

							update_option('ezoic_adstxtmanager_status', $redirect_result);
						}

						return array('success' => true);
					} else {
						return array('success' => false, 'error' => 'not_enabled');
					}
				}
			} else {
				Ezoic_Integration_Logger::log_api_error(self::GET_ADSTXTMANAGER_ID_ENDPOINT, $response, 'AdsTxtManager');
				return array('success' => false, 'error' => 'connection_error');
			}
		}

		return array('success' => false, 'error' => 'no_auth');
	}
}
