<?php

namespace Ezoic_Namespace;

class Ezoic_Content_Util {
	public static function is_error( $result ) {
		if ( gettype( $result ) === 'string' ) {
			return true;
		}

		return false;
	}

	public static function get_id() {
		$value = \get_option( 'cms_id');
		if ( $value && $value != "0") {
			return $value;
		}
		$tenant_name = str_replace(".", "-", parse_url( get_site_url(), PHP_URL_HOST ) );
		$url = "/api/v1/cms?tenant=" . $tenant_name;
		$request = array(
			"method" => "GET",
			"headers" => array(
				"Content-Type" => "application/json",
			),
		);
		$response = Ezoic_Content_Request::send_backend_request( $url, $request );
		if ($response != "") {
			$json = json_decode($response);
			$value = trim($json->{'data'}->{'EzoicCMSId'});
			\update_option( 'cms_id', $value );
			return $value;
		} else {
 			return "0";
		}
	}
}
