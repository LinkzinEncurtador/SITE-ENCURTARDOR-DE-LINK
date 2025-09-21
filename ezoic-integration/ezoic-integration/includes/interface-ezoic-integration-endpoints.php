<?php
namespace Ezoic_Namespace;

interface iEzoic_Integration_Endpoints {
    public function bust_endpoint_cache();
    public function is_ezoic_endpoint();
    public function get_endpoint_asset();
}
