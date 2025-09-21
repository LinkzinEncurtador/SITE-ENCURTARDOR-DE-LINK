<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Placeholder
{
	const EMBED_CODE_TEMPLATE = '<!-- Ezoic - %s - %s --><div id="ezoic-pub-ad-placeholder-%d" %s data-inserter-version="%d"></div><!-- End Ezoic - %s - %s -->';
	const JS_EMBED_CODE_TEMPLATE = '<!-- Ezoic - %s - %s --><div id="ezoic-pub-ad-placeholder-%d"%s data-inserter-version="%d"></div><script data-ezoic="1">ezstandalone.cmd.push(function () { ezstandalone.showAds(%d); });</script><!-- End Ezoic - %s - %s -->';

	// Track if any JS placeholders have been inserted
	private static $js_placeholders_inserted = false;

	public $id;
	public $position_id;
	public $position_type;
	public $name;
	public $is_video_placeholder;

	// Getter for camelCase property name for frontend compatibility
	public function __get($property)
	{
		if ($property === 'positionType') {
			return $this->position_type;
		}
		if ($property === 'positionId') {
			return $this->position_id;
		}
		return null;
	}

	public function __construct($id, $position_id, $name, $position_type, $is_video_placeholder)
	{
		$this->id					= $id;
		$this->position_id			= $position_id;
		$this->position_type		= $position_type;
		$this->name					= $name;
		$this->is_video_placeholder	= $is_video_placeholder;
	}

	/**
	 * Calculates the correct embed code to inject into the page
	 */
	public function embed_code($inserter_version = -1)
	{
		// Check if JavaScript integration is enabled and placeholders should be used
		$js_integration_enabled = get_option('ezoic_js_integration_enabled', false);
		$js_options = get_option('ezoic_js_integration_options');
		$use_js_placeholders = $js_integration_enabled && isset($js_options['js_use_wp_placeholders']) && $js_options['js_use_wp_placeholders'];

		if ($use_js_placeholders) {
			// Mark that a JS placeholder was inserted
			self::$js_placeholders_inserted = true;

			// Return JavaScript ad code for JS integration
			$dataAttr = "";
			if ($this->is_video_placeholder) {
				$dataAttr = ' data-ezhumixplayerlocation="true"';
			}

			return sprintf(
				self::JS_EMBED_CODE_TEMPLATE,
				$this->name,
				$this->position_type,
				$this->position_id,
				$dataAttr,
				$inserter_version,
				$this->position_id,
				$this->name,
				$this->position_type
			);
		}

		// Default WordPress integration placeholder
		$dataAttr = "";
		if ($this->is_video_placeholder) {
			$dataAttr = 'data-ezhumixplayerlocation="true"';
		}
		return sprintf(self::EMBED_CODE_TEMPLATE, $this->name, $this->position_type, $this->position_id, $dataAttr, $inserter_version, $this->name, $this->position_type);
	}

	public static function from_pubad($ad)
	{
		$placeholder = new Ezoic_AdTester_Placeholder($ad->id, $ad->adPositionId, $ad->name, $ad->positionType, $ad->isVideoPlaceholder);

		return $placeholder;
	}

	/**
	 * Check if any JS placeholders have been inserted on this page
	 */
	public static function js_placeholders_inserted()
	{
		return self::$js_placeholders_inserted;
	}

	/**
	 * Reset the JS placeholders tracking (useful for testing or page resets)
	 */
	public static function reset_js_placeholders_tracking()
	{
		self::$js_placeholders_inserted = false;
	}
}
