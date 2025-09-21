<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-response.php');

class Ezoic_Integration_CURL_Response implements iEzoic_Integration_Response {
    private $ez_headers;

    public function handle_ezoic_response( $final, $response ) {
        if( $response["error"] == NULL &&
            $response["status_code"] == 200  ) {
                $this->ez_headers = $response["headers"];
                //$this->ez_headers = wp_remote_retrieve_headers( $response );
                $this->alter_response_headers();

                //Replace final content with ezoic content
                if( is_array($response) && isset($response['body']) ) {
                    $final = $response['body'];
                } else {
                    $final = $response;
                }
        } else {
            if( $response["error"] != NULL ) {
                $final = $final . "<!-- " . $response["error"] . " -->";
            } elseif ($response["status_code"] != 200) {
                $final = $final . "<!-- " . $response["status_code"] . " -->";
            }
        }

        return $final;
    }

	/**
	 * Grabs the active template provided in the header.
	 */
	public function get_active_template( $response ) {
		if ($response["error"] == NULL && isset($response['headers']) && isset($response['headers']['x-wordpress-use-template'])) {
			return $response['headers']['x-wordpress-use-template'];
		}
		return '';
    }

    private function alter_response_headers() {
		if( !is_null($this->ez_headers) && !headers_sent() ){

			$headers = array();

			if( is_array($this->ez_headers) ) {
				$headers = $this->ez_headers;
			} else {
				$headers = $this->ez_headers->getAll();
			}

			foreach( $headers as $key => $header ) {
				//Avoid content encoding as this will cause rendering problems
				if( !$this->is_bad_header($key) ) {
					if (strtolower($key) === 'set-cookie') {
						Ezoic_Integration_Request_Utils::handle_cookies_from_header_value($header);
						continue;
					}
					$this->handle_header_object($key, $header);
				}
			}
		}
	}

	private function is_bad_header($key) {
		return ($key == 'Content-Encoding'
		    || $key == 'content-encoding'
			|| $key == 'Transfer-Encoding'
			|| $key == 'transfer-encoding'
			|| $key == 'Content-Length'
			|| $key == 'content-length'
			|| $key == 'Accept-Ranges'
			|| $key == 'accept-ranges'
			|| $key == 'Status'
			|| $key == 'status');
	}

	private function handle_header_object($key, $header) {
		if( is_array($header) ) {
			foreach( $header as $subheader) {
				header($key . ': ' . $subheader, false);
			}
		} else {
			header($key . ': ' . $header);
		}
	}
}
