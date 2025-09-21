<?php
namespace Ezoic_Namespace;

interface iEzoic_Integration_Cache {
    public function get_page( $active_template );
    public function set_page( $active_template, $content );
    public function is_cached( $active_template );
    public function is_cacheable();
    public function get_available_templates();
}
