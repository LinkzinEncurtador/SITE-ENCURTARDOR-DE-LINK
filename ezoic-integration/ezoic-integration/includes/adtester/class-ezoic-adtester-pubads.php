<?php

namespace Ezoic_Namespace;

/**
 * A data object which represents a single ad placeholder
 */
#[\AllowDynamicProperties]
class Ezoic_AdTester_PublisherAd
{

	public $id;
	public $domainId;
	public $adTypeId;
	public $adPositionId;
	public $name;
	public $positionType;
	public $isAdPicker;
	public $isVideoPlaceholder;

	// Not exposing these properties publicly unless they need to be used
	private $adCode;
	private $isActive;
	private $isAutoInsert;
	private $isPathFinder;
	private $replaceRegex;
	private $createdBy;
	private $isUnwrappedAd;
	private $isMultisize;
	private $allowedAdditionalSizes;
	private $disableDurations;
	private $allowFluid;
	private $adDisclosureId;
	private $disclosureText;

	/**
	 * Creates a placeholder based on a json payload
	 */
	public function __construct($payload)
	{
		if ($payload) {
			$this->set($payload);
		}
	}

	/**
	 * Get the isActive status of this placeholder
	 */
	public function is_active()
	{
		return $this->isActive;
	}

	/**
	 * Decode publisher ads from an array
	 *
	 * @access private
	 * @param array $data Array containing publisher ad collection
	 */
	private function set($data)
	{
		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}
	}
}

/**
 * A data object representing a list of ad placeholders
 */
class Ezoic_AdTester_PublisherAds
{
	public $ads = array();
	public $default_config = array();
	public $placeholders_created = false;
	public $revenues = array();
	public $adpos_service = null;

	/**
	 * Initializes the list via a back-channel call to the backend systems
	 */
	public function __construct()
	{
		$token = '';

		// Fetch domain and TLD (e.g. example.com from www.example.com)
		$domain = Ezoic_Integration_Request_Utils::get_domain();

		// Build request
		$requestURL = Ezoic_AdTester::ADS_ENDPOINT . $domain;

		// Use API Key, if available
		if (Ezoic_Cdn::ezoic_cdn_api_key() != null) {
			$requestURL .= '&developerKey=' . Ezoic_Cdn::ezoic_cdn_api_key();
			$token = Ezoic_Cdn::ezoic_cdn_api_key();
		} else {
			// Fetch autentication token
			$token = Ezoic_Integration_Authentication::get_token();
		}

		// If there is no token (failed authentication), then exit early
		if ($token == '') {
			return;
		}

		// Send request to Ezoic
		$response = wp_remote_get($requestURL, array(
			'method'	=> 'GET',
			'timeout'	=> '10',
			'headers'	=> array(
				'Authentication' => 'Bearer ' . $token
			)
		));

		if (!is_wp_error($response)) {
			$body = wp_remote_retrieve_body($response);

			// Deserialize response
			$deserialized = json_decode($body);

			// Initialize $ads
			$this->set($deserialized);
		}
	}

	/**
	 * Decode publisher ads from an array
	 *
	 * @access private
	 * @param object $data Object containing publisher ad collection
	 */
	private function set($data)
	{
		if ($data) {
			// Ensure placeholders exist in payload before attempting to deserialize
			if (\property_exists($data, 'placeholders') && isset($data->placeholders)) {

				// Deserialize ads
				foreach ($data->placeholders as $ad) {
					$new_ad = new Ezoic_AdTester_PublisherAd($ad);
					$this->ads[] = $new_ad;
				}

				// Deserialize default configuration
				foreach ($data->defaultConfiguration as $config) {
					$this->default_config[] = array(
						"page_type"			=> $config->pageType,
						"name"				=> $config->name,
						"position_type"		=> $config->positionType,
						"display"			=> $config->display,
						"display_option"	=> $config->displayOption
					);
				}

				$this->placeholders_created = $data->placeholdersCreated;
			}

			// Deserialize revenues
			if (isset($data->revenues)) {
				foreach ($data->revenues as $adPositionId => $revenue) {
					$this->revenues[$adPositionId] = Ezoic_AdTester_Revenue::from_pubads($adPositionId, $revenue);
				}
			}

			if (isset($data->adPosService)) {
				$this->adpos_service = $data->adPosService;
			}
		}
	}

	/**
	 * Returns the total number of ads for this domain
	 */
	public function count()
	{
		return count($this->ads);
	}

	/**
	 * Creates the embed code from the collection of ads
	 *
	 * @access private
	 * @param string $positionType Position type description
	 */
	public function get_embed_code($positionType)
	{
		$embedCode = "";

		if (!is_null($this->ads)) {
			foreach ($this->ads as $ad) {
				if ($ad->positionType == $positionType) {
					$embedCode = $embedCode . $ad->embed_code();
				}
			}
		}

		return $embedCode;
	}
}
