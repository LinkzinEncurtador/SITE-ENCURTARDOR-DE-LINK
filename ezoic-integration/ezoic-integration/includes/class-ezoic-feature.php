<?php

namespace Ezoic_Namespace;

abstract class Ezoic_Feature {
    protected $is_public_enabled;

    protected $is_admin_enabled;

    abstract public function register_public_hooks( $loader );

    abstract public function register_admin_hooks( $loader );

    public function is_public_enabled(){
        return $this->is_public_enabled;
    }

    public function is_admin_enabled(){
        return $this->is_admin_enabled;
    }
}
