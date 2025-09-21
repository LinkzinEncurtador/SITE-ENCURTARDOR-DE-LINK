<?php

namespace Ezoic_Namespace;

class Ezoic_Emote_Settings
{
	private $emote_enabled;
	private $emote_replace;

	public function __construct()
	{
		$this->emote_enabled = \get_option('ez_emote', 'false') == 'true';
		$this->emote_replace = \get_option('ez_emote_enabled', 'false') == 'true';
	}

	public function initialize_emote_settings()
	{
		// If emote isn't set up, don't even show the option
		if (!$this->emote_enabled) {
			return;
		}

		add_settings_section(
			'ezoic_emote_settings_section',
			__('Emote Settings', 'ezoic'),
			array($this, 'ezoic_emote_settings_overview'),
			'ezoic_emote_settings'
		);

		add_settings_field(
			'ezoic_replace_comments',
			'Replace WordPress Comments',
			array($this, 'ezoic_replace_comments_field'),
			'ezoic_emote_settings',
			'ezoic_emote_settings_section'
		);

		register_setting('ezoic_emote_settings', 'ez_emote_enabled');
	}

	// Output page header with overview of settings
	public function ezoic_emote_settings_overview()
	{
		echo '<p>' . __('These settings are for the Emote commenting widget', 'ezoic') . '</p>';
		echo '<hr/>';
	}

	// Ouput field
	public function ezoic_replace_comments_field()
	{
?>
		<input type="radio" id="ezoic_emote_replace_comment" name="ez_emote_enabled" value="true"
			<?php
			if ($this->emote_replace) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_emote_replace_comment">Enabled</label>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="radio" id="ezoic_emote_default_comment" name="ez_emote_enabled" value="false"
			<?php
			if (! $this->emote_replace) {
				echo ('checked="checked"');
			}
			?> />
		<label for="ezoic_emote_default_comment">Disabled</label>
		<p class="description">
			Replaces the default WordPress comment section with Emote on pages with comments enabled
		</p>
<?php
	}
}
