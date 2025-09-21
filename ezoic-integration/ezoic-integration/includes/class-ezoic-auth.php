<?php

namespace Ezoic_Namespace;

class Ezoic_Auth {
  private $access_token = "";
  private $client_id = "";
  private $client_secret = "";

  const auth_base_url = "https://auth.ezoic.com/oauth/token?grant_type=client_credentials&";
  const wp_service_url = "https://wp-service.ezoic.com";
  const get_token_endpoint = self::wp_service_url . "/wp/v1/auth/v1/auth";
  const get_secret_endpoint = self::wp_service_url . "/wp/v1/auth/v1/secret";

  function __construct($client_id = "", $client_secret = "") {
    if ( $client_id == "" || $client_secret == "" ) {
      $this->set_client_id_and_secret_from_options();
      if ( !$client_id ) {
        $this->request_client_id_secret();
      }
    } else {
      $this->client_id = $client_id;
      $this->client_secret = $client_secret;
    }

    $this->get_token();
  }

  function set_client_id_and_secret_from_options() {
    $this->client_id = get_option("ezoic_auth_client_id");
    $secret_encrypted = get_option("ezoic_auth_client_secret");
    if ( $secret_encrypted ) {
      $this->client_secret = self::decrypt_secret($secret_encrypted);
    }
  }

  function request_token_from_auth() {
    if ( ! $this->client_secret || strlen($this->client_secret) < 15 ) {
      return;
    }
    $auth_url = self::auth_base_url . "client_id=" . $this->client_id .
      "&client_secret=" . $this->client_secret;
    $response = wp_remote_get($auth_url);
    $status_code = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
    if ( $status_code != 200 ) {
      \error_log("Received a non-200 status code from auth.ezoic.com: " .
        "status_code: " . \strval($status_code) . " message: " . $response_message);
      return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $deserialized = json_decode( $body );
    if ($deserialized) {
      $this->access_token = $deserialized->access_token;
    } else {
      \error_log( 'Error communicating with auth.ezoic.com: ' .
        print_r( $response, true ) );
      return false;
    }
    $this->store_token_to_options();
    return $this->access_token;
  }

  public function get_token() {
    if ( !$this->access_token ) {
      $this->get_token_from_options();
    }
    if ( !$this->access_token ) {
      $this->request_token_from_auth();
    }
    return $this->access_token;
  }

  public function request_client_id_secret() {
    // curl wp-service rather than pub backend for token
    $token = Ezoic_Integration_Authentication::get_token( self::get_token_endpoint );
    if ( ! $token ) {
      \error_log( "Error retrieving token" );
    }
    $response = wp_remote_get( self::get_secret_endpoint, array(
      'headers' => array(
        'Authorization' => 'Bearer ' . $token,
        'Referer' => \site_url() // send over site url with referer header
      ),
      'timeout' => 5
    ));

    if ( ! is_wp_error( $response ) ) {
      $responseBody = wp_remote_retrieve_body( $response );
      $parsed       = json_decode( $responseBody );
      if ( is_null( $parsed->data ) ) {
        error_log( 'Error communicating with auth endpoint: ' . $responseBody );
      } else {
				$this->client_id = isset($parsed->data->id) ? $parsed->data->id : '';
        $this->client_secret = isset($parsed->data->secret) ? $parsed->data->secret : '';
        $secret_encrypted = self::encrypt_secret($this->client_secret);
        update_option( "ezoic_auth_client_id", $this->client_id );
        update_option( "ezoic_auth_client_secret", $secret_encrypted );
      }
    } else {
      error_log( 'Error communicating with auth endpoint: ' . print_r($response) );
    }
  }

  // Encrypt and descrypt using AUTH_KEY in wp-config as encryption key
  public static function encrypt_secret($s) {
    if ( ! \function_exists( "openssl_encrypt" ) ) {
      \error_log("Open SSL function doesn't exist");
      return false;
    }
    $result = \openssl_encrypt($s, "AES-128-CBC", AUTH_KEY,
      $options = 0, $iv = substr( SECURE_AUTH_KEY, 0, 16 ));
    if ( $result === false ) {
      \error_log("Error encrypting secret");
      return false;
    }
    return $result;
  }

  public static function decrypt_secret($s) {
    if ( ! \function_exists( "openssl_decrypt" ) ) {
      \error_log("Open SSL function doesn't exist");
      return false;
    }
    $result = \openssl_decrypt($s, "AES-128-CBC", AUTH_KEY,
      $options = 0, $iv = substr( SECURE_AUTH_KEY, 0, 16 ));
    if ( $result === false ) {
      \error_log("Error decrypting secret");
      return false;
    }
    return $result;
  }

  function store_token_to_options() {
    if ( ! $this->access_token ) {
      return false;
    }
    $token_encrypted = self::encrypt_secret($this->access_token);
    update_option( "ezoic_auth_access_token", $token_encrypted );
    update_option( "ezoic_token_generated_time", time() );
  }

  function get_token_from_options() {
    $token_generated_time = get_option( "ezoic_token_generated_time" );
    // token is valid for one hour
    if ( ! $token_generated_time || time() - $token_generated_time > 3600 ) {
      $this->access_token = "";
      return;
    }
    $this->access_token = self::decrypt_secret( get_option( "ezoic_auth_access_token" ) );
    return $this->access_token;
  }
}
