<?php

namespace Ezoic_Namespace;

use WP_Error;
use WP_Post;
use WP_Comment;
use WP_Theme;

class FacebookShareCache extends Ezoic_Feature
{
	private $fbAppId;
	private $fbAppSecret;
	private $fbAuthToken;
	private $fb_clear_cache_enabled = 'off';
	private $retries                = 5;

	public function __construct()
	{
		$this->fbAppId     = get_option('fb_app_id');
		$this->fbAppSecret = get_option('fb_app_secret');
		$this->fbAuthToken = get_option('fb_app_auth_token');

		$this->fb_clear_cache_enabled = get_option('fb_clear_cache_enabled', 'off');

		$this->is_public_enabled = true;
		$this->is_admin_enabled  = true;
	}

	public function register_admin_hooks($loader) {}

	public function register_public_hooks($loader)
	{
		$loader->add_action('publish_future_post', $this, 'facebook_cache_future_post', 10);
		$loader->add_action('publish_post', $this, 'facebook_cache_published', 10, 2);
		$loader->add_action('publish_page', $this, 'facebook_cache_published', 10, 2);
		$loader->add_action('ezoic_purge_url', $this, 'facebook_cache_purge_url_hook', 10, 1);
		$loader->add_action('ezoic_purge_urls', $this, 'facebook_cache_purge_urls_hook', 10, 1);
		$loader->add_action('ezoic_purge_home', $this, 'facebook_cache_purge_home_hook', 10, 0);
	}


	function is_facebook_clear_cache_enabled()
	{
		return $this->fb_clear_cache_enabled === 'on';
	}

	/**
	 * @param int $post_id
	 */
	function facebook_cache_future_post($post_id)
	{
		if (!self::is_facebook_clear_cache_enabled()) {
			return;
		}

		self::facebook_cache_published($post_id, get_post($post_id));
	}

	/**
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	function facebook_cache_published($post_id, $post)
	{
		if (!self::is_facebook_clear_cache_enabled()) {
			return;
		}

		$ezCdn = new Ezoic_Cdn();


		if ($post->post_status === 'publish') {
			if (wp_is_post_revision($post_id)) {
				return;
			}

			$urls = $ezCdn->ezoic_cdn_get_recache_urls_by_post($post_id, $post);
			self::clear_fb_share_cache_batch($urls);
		}
	}

	/**
	 * Implementation of post_updated action
	 *
	 * When a post is modified, clear facebook share cache for the post URL and all related archive pages (both before and after the change)
	 *
	 * @param int $post_id ID of the Post that has been modified.
	 * @param WP_Post $post_after Post object following the update.
	 * @param WP_Post $post_before Post object before the update.
	 *
	 * @return void
	 * @see clear_fb_share_cache_batch()
	 * @since 2.6.29
	 */
	function facebook_cache_post_updated($post_id, WP_Post $post_after, WP_Post $post_before)
	{


		$ezCdn = new Ezoic_Cdn();
		if (!self::is_facebook_clear_cache_enabled()) {
			return;
		}
		if (wp_is_post_revision($post_after)) {
			return;
		}

		// If the post wasn't published before and isn't published now, there is no need to purge anything.
		if ('publish' !== $post_before->post_status && 'publish' !== $post_after->post_status) {
			return;
		}

		$urls = $ezCdn->ezoic_cdn_get_recache_urls_by_post($post_id, $post_before);
		$urls = array_merge($urls, $ezCdn->ezoic_cdn_get_recache_urls_by_post($post_id, $post_after));
		$urls = array_unique($urls);

		self::clear_fb_share_cache_batch($urls);
	}

	public function facebook_cache_purge_url_hook($url)
	{
		$this->clear_fb_share_cache($url);
	}

	public function facebook_cache_purge_urls_hook($urls)
	{
		$this->clear_fb_share_cache_batch($urls);
	}

	public function facebook_cache_purge_home_hook()
	{
		$this->clear_fb_share_cache(get_home_url(null, '/'));
	}

	/**
	 * retrieves and stores a facebook authentication token
	 *
	 * @return string - fresh token
	 * @since 2.6.29
	 */
	public function update_fb_auth_token()
	{
		$this->fbAuthToken = $this->get_auth_token();

		return $this->fbAuthToken;
	}

	/**
	 * returns a curl handle for facebook or a given url if specified
	 *
	 * @param string $furl Post object before the update.
	 *
	 * @return \CurlHandle
	 * @since 2.6.29
	 */
	private function get_fb_curl_handle($furl = "https://graph.facebook.com")
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $furl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POST, true);

		return $ch;
	}

	/**
	 * Clear the Facebook Share Cache for a single URL
	 *
	 * @param string $url the URL of the cache object to be purged from FB
	 *
	 * @return void
	 * @see clear_fb_share_cache_batch()
	 * @since 2.6.29
	 */
	public function clear_fb_share_cache($cacheRefreshURL = '')
	{
		if (!self::is_facebook_clear_cache_enabled()) {
			return;
		}

		// Don't attempt API call if required credentials are missing
		if (empty($this->fbAppId) || empty($this->fbAppSecret) || empty($this->fbAuthToken)) {
			return;
		}

		$graphUrl = "https://graph.facebook.com";

		$params = array(
			'id'           => $cacheRefreshURL,
			'scrape'       => true,
			'access_token' => $this->fbAuthToken
		);

		$data    = [
			'body' => $params
		];
		$res     = wp_remote_post($graphUrl, $data);
		$resBody = wp_remote_retrieve_body($res);
		$resBody = json_decode($resBody);

		if (is_object($resBody)) {
			if (property_exists($resBody, 'error')) {
				error_log("FBERR::" . $resBody->error->message . " :: " . $cacheRefreshURL);
			}
		} else {
			error_log("FBERR :: response not an object: " . gettype($resBody));
			error_log("FBERR :: " . var_export($resBody, true));
		}
	}

	/**
	 * Purge multiple URLs from Facebook Share Cache
	 *
	 * @param array $urls an array of URL strings to be purged
	 *
	 * @return void
	 * @see clear_fb_share_cache_batch()
	 * @since 2.6.29
	 */
	public function clear_fb_share_cache_batch(array $urls)
	{

		if (!self::is_facebook_clear_cache_enabled()) {
			return;
		}

		//can easily swap this for get_option from a csv field
		$exclusions = [
			'/comments/feed/',
			'/category/',
			'/author/',
			'/feed/'
		];

		//filter out each exclusion
		foreach ($exclusions as $exclusion) {

			//filter on the exclusion
			$urls = array_filter($urls, function ($url) use ($exclusion) {
				return ! (strpos($url, $exclusion) > -1);
			});
		}

		foreach ($urls as $url) {
			$this->clear_fb_share_cache($url);
		}
	}

	/**
	 * Validates an App ID
	 *
	 * This function is anticipating FB graph to reply with an error complaining about client secret validation
	 * if the id is invalid it will state "Invalid Client ID" code 101
	 *
	 * @param integer AppID string - number, less than 20 chars generally
	 *
	 * @return boolean | answers the question whether the supplied ID is valid
	 * @since 2.6.29
	 */
	public function validate_app_id($appId)
	{
		// Don't attempt validation if App ID is empty
		if (empty($appId)) {
			return false;
		}

		$params = [
			'client_id'     => $appId,
			'client_secret' => '',
			'grant_type'    => 'client_credentials'
		];

		$options = array(
			'body' => $params,
		);

		$response = wp_remote_post("https://graph.facebook.com/oauth/access_token", $options);

		$bodyObject = json_decode(wp_remote_retrieve_body($response));

		if (
			isset($bodyObject->error)
		) {

			$errorObject = $bodyObject->error;
			if (
				$errorObject->type == 'OAuthException'
				&& $errorObject->code === 1
				&& $errorObject->message === "Error validating client secret."
			) {
				return true;
			} else {

				$errorString = "Type:" . $errorObject->type . ' Code: ' . $errorObject->code . ' Message: ' . $errorObject->message;

				error_log("FB Error:  " . $errorString);

				return false;
			}
		} else {
			error_log("FB :: app id is not valid ");
		}


		return false;
	}

	/**
	 * Validate App Secret | basically just validate that the string is md5
	 *
	 * @param string $appSecret
	 *
	 * @return boolean $isValid
	 * @since 2.6.29
	 */
	public function validate_app_secret($appSecret)
	{
		return isset($appSecret) && preg_match('/^[a-f0-9]{32}$/', $appSecret);
	}

	/**
	 * Retrieves a facebook authentication token
	 *
	 * @return string Returns an authentication token string
	 * @since 2.6.29
	 */
	public function get_auth_token()
	{
		$ch = $this->get_fb_curl_handle("https://graph.facebook.com/oauth/access_token");

		$params = [
			'client_id'     => $this->fbAppId,
			'client_secret' => $this->fbAppSecret,
			'grant_type'    => 'client_credentials'
		];


		if (strlen($this->fbAppSecret) === 0 || strlen($this->fbAppId) === 0) {
			return '';
		}

		$data = http_build_query($params);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
		));

		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		try {
			$res = curl_exec($ch);
			$res = json_decode($res);

			if (strlen($res->access_token)) {
				return $res->access_token;
			} else {
				return '';
			}
		} catch (\Exception $e) {
			error_log($e->getMessage());
			return '';
		}
	}
}
