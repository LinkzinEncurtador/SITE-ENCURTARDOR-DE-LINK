<?php

namespace {
	include_once 'include-functions.php';
}

namespace Ezoic_Namespace {
	if (! defined('EZOIC_INTEGRATION_VERSION')) {
		define('EZOIC_INTEGRATION_VERSION', '2.19.5'); // update plugin version number
	}

	if (! defined('EZOIC_API_VERSION')) {
		define('EZOIC_API_VERSION', '1.0.0');
	}

	if (! defined('EZOIC_DEBUG')) {
		define('EZOIC_DEBUG', isset($_GET['ez_wp_debug']) && $_GET['ez_wp_debug'] == '1');
	}

	if (! defined('EZOIC_GATEWAY_URL')) {
		define('EZOIC_GATEWAY_URL', getenv('EZOIC_GATEWAY_URL') ? getenv('EZOIC_GATEWAY_URL') : 'https://g.ezoic.net');
	}

	require_once(dirname(__FILE__) . '/ezoic-integration-classes.php');

	if (! isset($GLOBALS['EZOIC_CALL_COUNT'])) {
		$GLOBALS['EZOIC_CALL_COUNT'] = 0;
	}

	class Ezoic_Integration_Factory
	{
		private $cache_type;
		public $bypass_middleware = false;

		public function __construct()
		{
			$this->bypass_middleware = self::bypass_middleware();
		}

		/**
		 * Determines whether Ezoic middleware should be skipped
		 *
		 * @return bool
		 */
		public static function bypass_middleware()
		{
			$headers          = getallheaders();
			$header           = array_change_key_case($headers); // Convert all keys to lower
			$cloud_integrated = isset($header['x-middleton']) && $header['x-middleton'] == '1';

			$return_orig = isset($_GET['ez_wp_force_static']) && $_GET['ez_wp_force_static'] == '1';

			$wp_rocket_preload = isset($_SERVER['HTTP_USER_AGENT']) && (in_array(
				$_SERVER['HTTP_USER_AGENT'],
				array('EzoicStatic', 'WP Rocket/Partial_Preload', 'WP Rocket/Preload', 'WP Rocket/Sitemaps')
			));

			return $cloud_integrated || $return_orig || $wp_rocket_preload || Ezoic_Wp_Integration::is_special_route();
		}

		public function new_ezoic_integrator($cache_type)
		{
			$this->cache_type = $cache_type;
			$GLOBALS['EZOIC_CALL_COUNT'] += 1;

			if ($cache_type != Ezoic_Cache_Type::NO_CACHE) {
				ob_start();
				//echo "<!--we are caching-->";
			} else {
				//echo "<!--we are not caching-->";
			}

			return new Ezoic_Integrator(
				$this->new_ezoic_request(),
				$this->new_ezoic_response(),
				$this->new_ezoic_content_collector(),
				$this->new_ezoic_filter(),
				$this->new_ezoic_endpoint(),
				$this->new_ezoic_cache()
			);
		}

		private function new_ezoic_request()
		{
			if ($this->cache_type != Ezoic_Cache_Type::NO_CACHE && function_exists('curl_version')) {
				return new Ezoic_Integration_CURL_Request();
			}

			return new Ezoic_Integration_WP_Request();
		}

		private function new_ezoic_response()
		{
			if ($this->cache_type != Ezoic_Cache_Type::NO_CACHE && function_exists('curl_version')) {
				return new Ezoic_Integration_CURL_Response();
			}

			return new Ezoic_Integration_WP_Response();
		}

		private function new_ezoic_filter()
		{

			$is_debug = EZOIC_DEBUG;

			if ($this->cache_type != Ezoic_Cache_Type::NO_CACHE) {
				return new Ezoic_Integration_Cache_Filter(getallheaders(), $is_debug);
			}

			return new Ezoic_Integration_WP_Filter(getallheaders(), $is_debug);
		}

		private function new_ezoic_content_collector()
		{
			if ($this->cache_type == Ezoic_Cache_Type::HTACCESS_CACHE) {
				return new Ezoic_Integration_File_Content_Collector();
			}

			return new Ezoic_Integration_Buffer_Content_Collector();
		}

		private function new_ezoic_endpoint()
		{
			//Always use file based routes since
			//Some database access stuff is broken on certain domains
			return new Ezoic_Integration_Cache_Endpoints();
		}

		public function new_ezoic_cache()
		{
			return new Ezoic_Integration_Cache;
		}

		public function NewEzoicCache()
		{
			return $this->new_ezoic_cache();
		}

		// NOTE: This is for backwards compatibility referencing this function in a cache.
		public function NewEzoicIntegrator($cache_type)
		{
			return $this->new_ezoic_integrator($cache_type);
		}
	}
}
