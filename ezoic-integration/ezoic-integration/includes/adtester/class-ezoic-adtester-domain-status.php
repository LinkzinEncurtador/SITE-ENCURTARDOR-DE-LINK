<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Domain_Status
{
	public $monetization_eligible		= false;
	public $placeholder_count_other	= 0;
	public $placeholder_count_wp		= 0;
	public $placeholders_created		= false;
	public $has_error						= false;
	public $error_message				= '';

	public function __construct($fetch)
	{
		if ($fetch) {
			$this->fetch();
		}
	}

	/**
	 * Retreives the domain status from the backend
	 */
	public function fetch()
	{
		$token = '';

		// Fetch domain and TLD (e.g. example.com from www.example.com)
		$domain = Ezoic_Integration_Request_Utils::get_domain();

		// Build request
		$requestURL = Ezoic_AdTester::STATUS_ENDPOINT . $domain;

		// Use API Key, if available
		if (Ezoic_Cdn::ezoic_cdn_api_key() != null) {
			$requestURL .= '&developerKey=' . Ezoic_Cdn::ezoic_cdn_api_key();
			$token = Ezoic_Cdn::ezoic_cdn_api_key();
		} else {
			// Fetch autentication token
			$token = Ezoic_Integration_Authentication::get_token();
		}

		if ($token == '') {
			$this->has_error = true;
			$this->error_message = 'Unable to authenticate with backend, try adding your CDN API key.';
			return $this;
		}

		// Send request to Ezoic
		$response = wp_remote_get($requestURL, array(
			'method'		=> 'POST',
			'timeout'	=> '10',
			'headers'	=> array(
				'Authentication' => 'Bearer ' . $token
			)
		));

		if (!is_wp_error($response)) {
			$body = wp_remote_retrieve_body($response);

			// Deserialize response
			$deserialized = json_decode($body);

			// Check if the API returned an error
			if ($deserialized && isset($deserialized->status) && $deserialized->status === false) {
				$this->has_error = true;
				// Use the user-friendly message if available, otherwise fall back to errorMessage
				if (isset($deserialized->message) && !empty($deserialized->message)) {
					$this->error_message = $deserialized->message;
				} elseif (isset($deserialized->errorMessage) && !empty($deserialized->errorMessage)) {
					$this->error_message = $deserialized->errorMessage;
				} else {
					$this->error_message = 'Unknown error occurred while communicating with Ezoic backend.';
				}
			} else {
				// Initialize $ads with successful response
				$this->set($deserialized->data);
			}
		} else {
			Ezoic_Integration_Logger::log_error('Error communicating with backend: ' . print_r($response, true), 'AdTester');
			$this->has_error = true;
			$this->error_message = print_r($response, true);
		}

		return $this;
	}

	private function set($data)
	{
		if ($data) {
			$this->monetization_eligible		= $data->monetizationEligible;
			$this->placeholders_created		= $data->placeholdersCreated;
			$this->placeholder_count_other	= $data->placeholderCountOther;
			$this->placeholder_count_wp		= $data->placeholderCountWordPress;
		}

		if ($this->placeholders_created) {
			\delete_option('ez_adtester_generate');
		}
	}
}
