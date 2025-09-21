<?php
namespace Ezoic_Namespace;

interface iEzoic_Integration_Request {
    public function get_content_response_from_ezoic( $final_content, $available_templates = array() );
}
