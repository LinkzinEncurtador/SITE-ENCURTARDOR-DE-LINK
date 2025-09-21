<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Content_Inserter extends Ezoic_AdTester_Inserter
{
	private $before_paragraph_pos;
	private $after_paragraph_pos;
	private $position_offset = 0;

	public function __construct($config)
	{
		parent::__construct($config);
	}

	public function insert($content)
	{
		$this->before_paragraph_pos = $this->paragraph_tag_positions($content, true);
		$this->after_paragraph_pos = $this->paragraph_tag_positions($content, false);

		$rules = $this->config->filtered_placeholder_rules;

		// Sort rules by paragraph number
		\usort($rules, function ($a, $b) {
			if ((int) $a->display_option < (int) $b->display_option) {
				return -1;
			} else {
				return 1;
			}
		});

		// Insert placeholders
		foreach ($rules as $rule) {
			if ($rule->display != 'disabled') {
				$placeholder = $this->config->placeholders[$rule->placeholder_id];

				// Skip if this placeholder already exists in content
				if (strpos($content, "ezoic-pub-ad-placeholder-{$placeholder->position_id}") !== false) {
					Ezoic_Integration_Logger::console_debug(
						"Placement skipped - placeholder already exists in content.",
						'Content Ads',
						'info',
						$rule->placeholder_id
					);
					continue;
				}

				switch ($rule->display) {
					case 'before_paragraph':
						$content = $this->relative_to_paragraph($placeholder, $rule->display_option, $content, 'before');
						break;

					case 'after_paragraph':
						$content = $this->relative_to_paragraph($placeholder, $rule->display_option, $content, 'after');
						break;
				}
			}
		}

		return $content;
	}

	/**
	 * Inserts a placeholder either before or after a paragraph
	 */
	private function relative_to_paragraph($placeholder, $paragraph_number, $content, $mode = 'before')
	{
		$placeholder_markup		= $placeholder->embed_code(1);
		$placeholder_markup_len	= ez_strlen($placeholder_markup);
		$placement_paragraph		= -1;
		$paragraph_pos				= $this->before_paragraph_pos;

		if ($mode == 'after') {
			$paragraph_pos = $this->after_paragraph_pos;
		}

		if (ez_strlen($paragraph_number) > 0 && is_numeric($paragraph_number)) {
			$placement_paragraph = (int) $paragraph_number;
		} else {
			return $content;
		}

		if ($placement_paragraph == -1 || $placement_paragraph > \count($paragraph_pos)) {
			return $content;
		}

		$position = $paragraph_pos[$placement_paragraph - 1];
		$content = \ez_substr_replace($content, $placeholder_markup, $position + $this->position_offset);
		$this->position_offset += $placeholder_markup_len;

		return $content;
	}

	/**
	 * Identifies the starting position of each paragraph
	 */
	private function paragraph_tag_positions($content, $open_tag = true)
	{
		$paragraphs = array();

		// Identify location for each paragraph tag type (e.g. 'p', 'div', etc)
		foreach ($this->config->paragraph_tags as $tag) {
			// Create proper HTML tag opening
			if ($open_tag) {
				$paragraph_tag = '<' . $tag;
			} else {
				$paragraph_tag = '</' . $tag . '>';
			}

			// Use ez_stripos becaues preg_match_all does not support unicode
			$offset = -1;
			$content_length = \strlen($content);
			while ($offset + 1 < $content_length && \ez_stripos($content, $paragraph_tag, $offset + 1) !== false) {
				$offset = \ez_stripos($content, $paragraph_tag, $offset + 1);
				if (!$open_tag) {
					$offset += \ez_strlen($paragraph_tag);
				}

				// if open tag, need make sure the tag ends, e.g. <p vs <pre
				if ($open_tag) {
					$next_char_pos = $offset + \ez_strlen($paragraph_tag);
					if ($next_char_pos < $content_length) {
						$next_char = $content[$next_char_pos];
						if ($next_char !== '>' && !\ez_ctype_space($next_char)) {
							continue;
						}

						// Additional validation: ensure we're at the start of a tag, not inside an attribute
						// Check that the character before our match is '<'
						if ($offset > 0 && $content[$offset - 1] !== '<') {
							continue; // Skip this match as it's not at the start of a tag
						}
					}
				}

				$paragraphs[] = $offset;
			}
		}

		// Sort paragraphs, if needed
		if (count($this->config->paragraph_tags) > 1) {
			sort($paragraphs);
		}

		return $paragraphs;
	}
}
