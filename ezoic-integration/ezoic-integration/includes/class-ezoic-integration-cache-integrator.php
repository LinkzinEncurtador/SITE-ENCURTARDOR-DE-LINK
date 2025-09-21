<?php
namespace Ezoic_Namespace;

class Ezoic_Integration_Cache_Integrator {

    public $config_path;
    public $advanced_cache_path;
    private $gen_file_comment;

    private $has_advanced_cache;
    private $has_fancy_permalinks;

    public function __construct() {
        $this->config_path = $this->get_wp_config_path();
        $this->advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';
        $this->gen_file_comment = '// Added by the Ezoic Integration.';

        $this->has_advanced_cache = file_exists( WP_CONTENT_DIR . '/advanced-cache.php' );

		  $permalink_structure = get_option( 'permalink_structure' );
        $this->has_fancy_permalinks = !empty( $permalink_structure );
    }

    /**
     * Remove content that has been added to the wp-config.php file.
     */
    public function clean_wp_config() {
        if(!file_exists($this->config_path) || !is_writable($this->config_path)) {
		    return false;
	    }

        $content = file_get_contents( $this->config_path );
        $line_content = preg_split( "/\r\n|\n|\r/", $content );

        // Search and remove lines that include 'WP_CACHE' or 'EZOIC_CACHE' from the file.
        foreach( $line_content as $key => $value ) {
            if( strpos($value, "'WP_CACHE'") || strpos($value, "'EZOIC_CACHE'") ) {
                unset($line_content[$key]);
            }
        }

        $modified_content = implode( "\n", $line_content );
        if (file_put_contents( $this->config_path, $modified_content ) === false) {
            return false;
        }

        return true;
    }


    /**
     * Add necessary lines of code to the wp-config.php file.
     */
    public function configure_wp_config() {
        if( !file_exists( $this->config_path ) || !is_writable( $this->config_path ) ) {
		    return false;
	    }

        $content = file_get_contents( $this->config_path );
        $line_content = preg_split( "/\r\n|\n|\r/", $content );
        array_splice( $line_content, 1, 0, "define( 'WP_CACHE', true );\ndefine( 'EZOIC_CACHE', true ); $this->gen_file_comment" );
        $modified_content = implode( "\n", $line_content );
        if (file_put_contents( $this->config_path, $modified_content ) === false) {
            return false;
        };

        return true;
    }

    /**
     * Creates and adds content to the advanced-cache.php file which is used to server
     * cached content before wordpress is loaded. Doing this significantly decreased ttfb.
     */
    public function insert_advanced_cache() {
        $array_content = array(
        "<?php",
        $this->gen_file_comment,
        '$factory_path = WP_CONTENT_DIR . "/plugins/' .  EZOIC__PLUGIN_SLUG . '/includes/class-ezoic-integration-factory.php";',
        'if (!is_admin() && file_exists($factory_path)) {',
        '   require_once($factory_path);',
        '   $ezoic_factory = new Ezoic_Namespace\Ezoic_Integration_Factory();',
        '   $ezoic_cache = $ezoic_factory->new_ezoic_cache();',
        '   $active_template = $ezoic_cache->get_active_template_cookie();',
        '   if ($ezoic_cache->IsCached($active_template)) {',
        '       echo trim($ezoic_cache->GetPage($active_template));',
        '	    exit();',
        '   }',
        '}',
        );
        $contents = implode("\n", $array_content);
        if (file_put_contents($this->advanced_cache_path, $contents) === false) {
            return false;
        };

        return true;
    }

    /**
	 * Remove the advanced-cache.php file.
     */
    public function remove_advanced_cache() {
        if (!file_exists($this->advanced_cache_path)) {
            return true;
        }

        if(!is_writable($this->advanced_cache_path)) {
		    return false;
	    }

        if (unlink($this->advanced_cache_path) === false) {
            return false;
        }

        return true;
    }

    /**
     * Checks to see if the cache is able to be turned on based on the current
     * setup of the site.
     */
    public function has_valid_setup() {
	    return ! $this->has_advanced_cache() &&
	           $this->has_fancy_permalinks() &&
	           $this->has_writeable_wp_config() &&
	           $this->has_writeable_wp_content() &&
	           Ezoic_Integration_Admin::is_cloud_integrated() == false;
    }

    /**
     * Checks to see if the site already has an advanced-cache.php file
     * which would mean that another caching plugin is installed.
     */
    public function has_advanced_cache() {
        return $this->has_advanced_cache;
    }

    /**
     * Checks to see if the site is using fancy permalinks or not by
     * seeing if the permalink_structure option is empty or not.
     */
    public function has_fancy_permalinks() {
        return $this->has_fancy_permalinks;
    }

    /**
     * Checks to see if the site has a writeable wp config.
     */
    public function has_writeable_wp_config() {
        return is_writable($this->config_path);
    }

    /**
     * Checks to see if the site has a writeable wp content directory.
     */
    public function has_writeable_wp_content() {
        return is_writable(WP_CONTENT_DIR);
    }

    /**
     * Gets the path of the wp-config.php file because it can be found in various different directories.
     * This code is based on the logic found here: https://github.com/WordPress/WordPress/blob/master/wp-load.php
     */
    private function get_wp_config_path() {
        if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
            return ABSPATH . 'wp-config.php';
        } elseif ( file_exists( dirname( ABSPATH ) . '/wp-config.php') && !file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
            return dirname( ABSPATH ) . '/wp-config.php';
        } else {
            return '';
        }
    }

}
