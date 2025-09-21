<?php
namespace Ezoic_Namespace;

interface iEzoic_Integration_Response {
    public function handle_ezoic_response( $final, $response);
    public function get_active_template( $response );
}
