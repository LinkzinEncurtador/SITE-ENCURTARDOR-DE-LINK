<?php
namespace Ezoic_Namespace;

class Ezoic_Cache_Identity {
	const WP_SUPER_CACHE = 0;
	const W3_TOTAL_CACHE = 1;
	const UNKNOWN_CACHE = 2;
	const WP_ROCKET_CACHE = 3;
}

class Ezoic_Cache_Type {
	const HTACCESS_CACHE = 0;
	const PHP_CACHE = 1;
	const NO_CACHE = 2;
}

class Ezoic_Integration_Cache_Identifier {
	private $cache_method;
	private $cache_identity;
	private $cache_path;
	private $config_path;

	public function __construct() {

		if ( ! defined( 'ABSPATH' ) ) {
			// determine root wp dir path
			// up from '/wp-content[4]/plugins[3]/ezoic-integration[2]/includes[1]/'
			$abs_path = realpath(__DIR__ . '/../../../..') . '/';
			if(file_exists($abs_path . 'wp-load.php')) {
				define( 'ABSPATH', realpath(__DIR__ . '/../../../..') . '/' );
			} else {
				// loop to find
				$dir_path = $this->wp_get_web_root();
				define( 'ABSPATH', $dir_path. '/' );
			}
		}

		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
		}

		$this->cache_identity = $this->determine_cache_identity();
		$this->cache_method = $this->determine_cache_method();
		$this->cache_path = $this->determine_cache_absolute_path();
		$this->config_path = dirname(__FILE__) . "/config/ezoic_config.json";
	}

	private function wp_get_web_root() {
		$dir = dirname(__FILE__);
		do {
			if( file_exists($dir."/wp-load.php") ) {
				return $dir;
			}
		} while( $dir = realpath("$dir/..") );
		return null;
	}

	public function get_cache_absolute_path() {
		return $this->cache_path;
	}

	public function activate_cache_workaround() {
		if( $this->cache_method == Ezoic_Cache_Type::HTACCESS_CACHE ) {
			$this->generate_htaccess_file();
		}

		if( $this->cache_method == Ezoic_Cache_Type::PHP_CACHE ) {
			$this->modify_wp_settings();
		}
	}

	public function deactivate_cache_workaround() {
		if( $this->cache_method == Ezoic_Cache_Type::HTACCESS_CACHE ) {
			$this->remove_htaccess_file();
		}

		if( $this->cache_method == Ezoic_Cache_Type::PHP_CACHE ) {
			$this->restore_wp_settings();
		}
	}

	public function get_cache_identity() {
		return $this->cache_identity;
	}

	public function get_cache_type() {
		return $this->cache_method;
	}

	public function get_config() {
		if( file_exists($this->config_path) && is_readable($this->config_path) ) {
			$cache_content = file_get_contents($this->config_path);
			$content = json_decode($cache_content, true);
			if(is_array($content)) {
				return $content;
			}
		}

		return array();
	}

	private function determine_cache_method() {
		if( $this->cache_identity == Ezoic_Cache_Identity::WP_SUPER_CACHE ) {
			return $this->determine_wpsc_cache_type();
		}

		if( $this->cache_identity == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
			return $this->determine_w3tc_cache_type();
		}

		if( $this->cache_identity == Ezoic_Cache_Identity::WP_ROCKET_CACHE ) {
			return $this->determine_wprocket_cache_type();
		}

		return Ezoic_Cache_Type::NO_CACHE;
	}

	private function determine_wprocket_cache_type() {
		//We will attempt to modify the sub htaccess file anyway,
		//so lets just call it htaccess
		if ( defined( 'WP_ROCKET_ADVANCED_CACHE' ) ) {
			return Ezoic_Cache_Type::PHP_CACHE;
		}

		return Ezoic_Cache_Type::HTACCESS_CACHE;
	}

	private function determine_w3tc_cache_type() {
		$filename = WP_CONTENT_DIR . "/w3tc-config/master.php";
		if ( file_exists( $filename ) && is_readable( $filename ) ) {
			//Grab our config file and remove first 14 characters since it's php code
			$content = file_get_contents( $filename );
			$content = substr($content, 14);
			$config = json_decode( $content , true );
			if ( is_array( $config ) ) {
				if( isset($config["pgcache.enabled"]) ) {
					if( $config["pgcache.enabled"] == 1 && !defined('WPINC') ) {
						return Ezoic_Cache_Type::HTACCESS_CACHE;
					} else {
						return Ezoic_Cache_Type::PHP_CACHE;
					}
				}
			}
		}

		return Ezoic_Cache_Type::PHP_CACHE;
	}

	private function determine_wpsc_cache_type() {
		global $wp_cache_mod_rewrite;

		if( isset( $wp_cache_mod_rewrite ) ) {
			if( $wp_cache_mod_rewrite == 1 ) {
				return Ezoic_Cache_Type::HTACCESS_CACHE;
			} else {
				return Ezoic_Cache_Type::PHP_CACHE;
			}
		}

		return Ezoic_Cache_Type::PHP_CACHE;
	}

	private function determine_cache_identity() {

		if( $this->is_wp_super_cache() ) {
			return Ezoic_Cache_Identity::WP_SUPER_CACHE;
		}

		if( $this->is_w3_total_cache() ) {
			return Ezoic_Cache_Identity::W3_TOTAL_CACHE;
		}

		if( $this->is_wp_rocket_cache() ) {
			return Ezoic_Cache_Identity::WP_ROCKET_CACHE;
		}

		return Ezoic_Cache_Identity::UNKNOWN_CACHE;
	}

	private function is_wp_super_cache() {
		return function_exists( 'wp_cache_set_home' );
	}

	private function is_w3_total_cache() {
		if ( function_exists( 'is_plugin_active' ) ) {
			return ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) );
		}

		$filename = WP_CONTENT_DIR . "/w3tc-config/master.php";
		return defined( 'W3TC' ) && defined( 'W3TC_DIR' ) && file_exists( $filename );
	}

	private function is_wp_rocket_cache() {
		if ( function_exists( 'is_plugin_active' ) ) {
			return ( is_plugin_active( 'wp-rocket/wp-rocket.php' ) );
		}
		return file_exists( WP_CONTENT_DIR . '/plugins/wp-rocket/inc/front/process.php' ) &&
			file_exists( WP_CONTENT_DIR . '/plugins/wp-rocket/vendor/autoload.php' ) &&
			defined( 'WP_ROCKET_ADVANCED_CACHE' );
	}

	private function determine_cache_absolute_path() {
		if( $this->cache_identity == Ezoic_Cache_Identity::WP_SUPER_CACHE ) {
			global $cache_path;

			if( is_string($cache_path) ) {
				return $cache_path . 'supercache/';
			}
		}

		if( $this->cache_identity == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
			return WP_CONTENT_DIR . '/cache/page_enhanced/';
		}

		if( $this->cache_identity == Ezoic_Cache_Identity::WP_ROCKET_CACHE ) {
			return WP_CONTENT_DIR . '/cache/wp-rocket/';
		}

		return "";
	}

	public function generate_htaccess_file() {

		if ( empty( $this->determine_cache_absolute_path() ) ) {
			// no caching plugin detected, don't modify htaccess
			return;
		}

		// get path to cache folder and insert out htaccess file or modify current htaccess file
		$file_path = $this->determine_cache_absolute_path() . ".htaccess";

		if(file_exists($file_path) && !is_writable($file_path)) {
			//wp_die( 'Ezoic Integration not activated due to htaccess permissions: ' . $file_path );
			return;
		}

		// make sure we start clean
		$this->remove_htaccess_file();

		// verify cache handler file exists
		if(!file_exists( WP_CONTENT_DIR . '/plugins' . '/' . EZOIC__PLUGIN_SLUG . '/ezoic-cache-handle/ezoic-handle-cache.php' ) ) {
			return;
		}

		$content = '';
		if ( file_exists( $file_path ) ) {
			$content = file_get_contents( $file_path );
		}

		$line_content = preg_split("/\r\n|\n|\r/", $content);

		$ezoic_content = array("#BEGIN_EZOIC_INTEGRATION_HTACCESS_CACHE_HANDLER",
			'<IfModule mod_rewrite.c>',
			'   RewriteEngine On',
			'   RewriteRule .* "/wp-content/plugins/' . EZOIC__PLUGIN_SLUG . '/ezoic-cache-handle/ezoic-handle-cache.php" [L]',
			'</IfModule>',
			"#END_EZOIC_INTEGRATION_HTACCESS_CACHE_HANDLER");

		$finalLineContent = array_merge($ezoic_content, $line_content);
		$modified_content = implode("\n", $finalLineContent);

		try {
			$success = file_put_contents($file_path, $modified_content);
			if ( ! $success ) {
				// Handle the error, e.g., log it or notify the admin
				return;
			}
		} catch (\Exception $e) {
			\error_log($e->getMessage());
			return;
		}
	}

	public function remove_htaccess_file() {
		//Get path to cache folder and din htaccess file,
		//see if we are the only code in the file and then remove it
		$file_path = $this->determine_cache_absolute_path() . ".htaccess";

		if(empty($file_path) || !file_exists($file_path) || !is_writable($file_path)) {
			return;
		}

		$content = file_get_contents( $file_path );
		$line_content = preg_split( "/\r\n|\n|\r/", $content );
		//Find all text between #EZOIC_INTEGRATION_MODIFICATION
		$begin_ezoic_content = 0;
		$end_ezoic_content = 0;
		$found_start = false;
		foreach( $line_content as $key => $value ) {
			if( $value == "#BEGIN_EZOIC_INTEGRATION_HTACCESS_CACHE_HANDLER" ) {
				$begin_ezoic_content = $key;
				$found_start = true;
			} elseif ( $value == "#END_EZOIC_INTEGRATION_HTACCESS_CACHE_HANDLER") {
				$end_ezoic_content = $key;
			}
		}

		// If we never found the starting comment block return early.
		if(!$found_start) {
			return;
		}

		for( $i = $begin_ezoic_content; $i <= $end_ezoic_content; $i++ ) {
			unset($line_content[$i]);
		}

		$modified_content = implode("\n", $line_content);
		//Dump out to advanced cache file

		try {
			$success = file_put_contents( $file_path, $modified_content );
			if ( ! $success ) {
				// Handle the error, e.g., log it or notify the admin
				return;
			}
		} catch (\Exception $e) {
			\error_log($e->getMessage());
			return;
		}
	}

	public function modify_advanced_cache() {
		// get advanced cache file
		$advanced_path = "";
		if ( $this->cache_identity == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
			$advanced_path = "/plugins/w3-total-cache/wp-content";
		}

		$file_path = WP_CONTENT_DIR . $advanced_path . '/advanced-cache.php';
		if ( ! file_exists( $file_path ) || ! is_writable( $file_path ) ) {
			return;
		}

		// make sure we start clean
		$this->restore_advanced_cache();

		// verify factory file exists
		if ( ! file_exists( WP_CONTENT_DIR . '/plugins' . '/' . EZOIC__PLUGIN_SLUG . '/includes/class-ezoic-integration-factory.php' ) ) {
			return;
		}

		$content = trim( file_get_contents( $file_path ) );

		// empty file, add in opening php tag
		if ( empty( $content ) ) {
			$content = "<?php\n";
		}

		$line_content = preg_split( "/\r\n|\n|\r/", $content );

		// insert our ezoic middleware code
		$ezoic_content = array(
			"#BEGIN_EZOIC_INTEGRATION_PHP_CACHE_HANDLER",
			'$ezoic_factory_file = WP_CONTENT_DIR . \'/plugins\' . \'/' . EZOIC__PLUGIN_SLUG . '/includes/class-ezoic-integration-factory.php\';',
			'if ( false == strpos( $_SERVER[\'REQUEST_URI\'], \'wp-admin\' ) && file_exists( $ezoic_factory_file ) ) {',
			'   require_once($ezoic_factory_file);',
			'   $ezoic_factory = new Ezoic_Namespace\Ezoic_Integration_Factory();',
			'   if ( $ezoic_factory->bypass_middleware === false ) {',
			'       $ezoic_integrator = $ezoic_factory->new_ezoic_integrator(Ezoic_Namespace\Ezoic_Cache_Type::PHP_CACHE);',
			'       register_shutdown_function(array($ezoic_integrator, "apply_ezoic_middleware"));',
			'   }',
			"}",
			"#END_EZOIC_INTEGRATION_PHP_CACHE_HANDLER"
		);

		array_splice( $line_content, 1, 0, $ezoic_content );
		$modified_content = implode( "\n", $line_content );

		// dump out to advanced cache file
		try {
			$success = file_put_contents( $file_path, $modified_content );
			if ( ! $success ) {
				// Handle the error, e.g., log it or notify the admin
				echo "We failed to modify our advanced Cache file.";
				return;
			}
		} catch (\Exception $e) {
			\error_log($e->getMessage());
			return;
		}
	}

	public function restore_advanced_cache() {
		//get advanced cache file
		$advanced_path = "";
		if( $this->cache_identity == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
			$advanced_path = "/plugins/w3-total-cache/wp-content";
		}

		$file_path = WP_CONTENT_DIR . $advanced_path . '/advanced-cache.php';
		if(!file_exists($file_path) || !is_writable($file_path)) {
			return;
		}

		$content = file_get_contents($file_path);
		$line_content = preg_split("/\r\n|\n|\r/", $content);
		//Find all text between #EZOIC_INTEGRATION_MODIFICATION
		$begin_ezoic_content = 0;
		$end_ezoic_content = 0;
		foreach( $line_content as $key => $value ) {
			if( $value == "#BEGIN_EZOIC_INTEGRATION_PHP_CACHE_HANDLER" ) {
				$begin_ezoic_content = $key;
			} elseif ( $value == "#END_EZOIC_INTEGRATION_PHP_CACHE_HANDLER") {
				$end_ezoic_content = $key;
			}
		}

		if($begin_ezoic_content == 0 ) {
			//No modification need 0th line should be php declaration
			return;
		}

		for( $i = $begin_ezoic_content; $i <= $end_ezoic_content; $i++ ) {
			unset($line_content[$i]);
		}

		$modified_content = implode("\n", $line_content);
		//Dump out to advanced cache file
		file_put_contents($file_path, $modified_content);
	}

	public function generate_config() {
		$cache_content = array( "cache_path" => $this->cache_path, "cache_identity" => $this->cache_identity );
		$file_contents = json_encode($cache_content);

		if(!file_exists($this->config_path) || !is_writable($this->config_path)) {
			return;
		}

		if ( ( $handle = fopen( $this->config_path, 'w' ) ) !== false ) {
			fwrite( $handle, $file_contents );
			fclose( $handle );
		}
	}

	public function modify_wp_settings() {

	}

	public function restore_wp_settings() {

	}

}
