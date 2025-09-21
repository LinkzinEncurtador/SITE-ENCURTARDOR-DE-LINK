<?php
namespace Ezoic_Namespace;

/**
 * Register all features for the plugin.
 *
 * @link       https://ezoic.com
 * @since      2.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */

/**
 * Register all features for the plugin.
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 * @author     Ezoic Inc. <support@ezoic.com>
 */

class Ezoic_Integration_Features {

    /**
     * The array of features registered with WordPress.
     *
     * @since    2.0
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $features;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.0
	 * @access   protected
	 * @var      Ezoic_Integration_Features $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
     * Initialize the collections used to maintain the features.
     *
     * @since    2.0
     */
    public function __construct( $loader ) {

        $this->features = array();
        $this->loader = $loader;

    }

    /**
     * Add a new feature to the collection.
     *
     * @since    2.0
     */
    public function add_feature( $feature ) {

        $this->features = $this->add( $this->features, $feature );

    }

    /**
     * A utility function that is used to register the features into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $features            The collection of features that is being registered.
     * @param    string               $feature             The name of the Ezoic feature that is being registered.
     * @return   array                                     The collection of features registered.
     */
    private function add( $features, $feature ) {

        $features[] = array(
            'feature'          => $feature,
        );

        return $features;

    }

    /**
     * Register the features.
     *
     * @since    2.0
     */
    public function run() {
        $loader = $this->loader;

        foreach ( $this->features as $feature ) {
            $f = $feature['feature'];

            if ( $f->is_public_enabled() && !is_admin() ) {
				$f->register_public_hooks( $loader );
            }

            if ( $f->is_admin_enabled() && is_admin() ) {
				$f->register_admin_hooks( $loader );
            }

        }
    }
}
