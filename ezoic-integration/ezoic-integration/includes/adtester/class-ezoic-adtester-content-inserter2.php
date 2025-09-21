<?php

namespace Ezoic_Namespace;

/**
 * Parent Filter Definition
 */
class Ezoic_AdTester_Parent_Filter
{
	// Parses a selector (e.g. div#my-id.my-class)
	const FILTER_PARSER = '/^([\*|\w|\-]+)?(#[\w|\-]+)?(\.[\w|\-|\.]+)*$/i';

	public $tag;
	public $id;
	public $class;

	public function __construct() {}

	/**
	 * Parses a filter string and returns an Ezoic_AdTester_Parent_Filter
	 */
	public static function parse_filter($filter)
	{
		// Parse filter string
		preg_match_all(Ezoic_AdTester_Parent_Filter::FILTER_PARSER, $filter, $parsed);

		// The new filter
		$new_filter = new Ezoic_AdTester_Parent_Filter();

		// Tag/Element
		if (!empty($parsed[1][0])) {
			$new_filter->tag = \ez_strtolower($parsed[1][0]);
		}

		// Id
		if (!empty($parsed[2][0])) {
			$new_filter->id = \ez_strtolower(\ez_substr($parsed[2][0], 1));
		}

		// Class
		if (!empty($parsed[3][0])) {
			$new_filter->class = \ez_strtolower(\ez_substr($parsed[3][0], 1));
		}

		return $new_filter;
	}

	/**
	 * Indicates if the filter matches the current paragraph
	 */
	public function is_valid($paragraph)
	{
		foreach ($paragraph->lineage as $parent_element) {
			// Evaluate filter
			$tag_match		= !isset($this->tag)		|| (\array_key_exists('tag', $parent_element) && $this->tag === $parent_element['tag']);
			$id_match		= !isset($this->id)		|| (\array_key_exists('id', $parent_element) && $this->id === $parent_element['id']);
			$class_match	= !isset($this->class)	|| (\array_key_exists('class_list', $parent_element) && in_array($this->class, $parent_element['class_list']));

			// Return true if all matches are true
			if ($tag_match && $id_match && $class_match) {
				return true;
			}
		}

		return false;
	}
}

class Ezoic_AdTester_Content_Inserter2 extends Ezoic_AdTester_Inserter
{
	private $position_offset = 0;
	private $paragraphs;
	private $paragraph_limit_warning_shown = false;

	public function __construct($config)
	{
		parent::__construct($config);
	}

	/**
	 * Insert placeholders into content
	 */
	public function insert($content)
	{
		// Reset paragraph limit warning flag for new content processing
		$this->paragraph_limit_warning_shown = false;

		// Validation
		if (!isset($content) || \ez_strlen($content) === 0) {
			Ezoic_Integration_Logger::console_debug(
				"Content insertion skipped - content is empty or null",
				'Content Ads',
				'info'
			);
			return $content;
		}

		// Find rules that apply to this page
		$rules = $this->config->filtered_placeholder_rules;

		// Stop processing if there are no rules to process for this page
		if (\count($rules) === 0) {
			return $content;
		}

		// Sort rules based on paragraph order
		\usort($rules, function ($a, $b) {
			if ((int) $a->display_option < (int) $b->display_option) {
				return -1;
			} else {
				return 1;
			}
		});

		// Extract all paragraph tags
		$this->paragraphs = $this->get_paragraphs($content);

		// Insert placeholders
		foreach ($rules as $rule) {
			if ($rule->display != 'disabled') {
				$placeholder = $this->config->placeholders[$rule->placeholder_id];

				// Skip if this placeholder already exists in content
				if (strpos($content, "ezoic-pub-ad-placeholder-{$placeholder->position_id}") !== false) {
					Ezoic_Integration_Logger::console_debug(
						"Placement skipped - placeholder already exists in content. Placeholder ID: {$rule->placeholder_id}",
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

					default:
						continue 2;
				}
			} else {
				Ezoic_Integration_Logger::console_debug(
					"Placement skipped - display is disabled. Placeholder ID: {$rule->placeholder_id}",
					'Content Ads',
					'info',
					$rule->placeholder_id
				);
			}
		}

		return $content;
	}

	/**
	 * Inserts a placeholder either before or after a paragraph
	 */
	private function relative_to_paragraph($placeholder, $paragraph_number, $content, $mode = 'before')
	{
		// Get markup for the placeholder
		$placeholder_markup		= $placeholder->embed_code(2);
		$placeholder_markup_len	= ez_strlen($placeholder_markup);
		$placement_paragraph		= -1;

		// Attempt to parse the placement display option
		if (ez_strlen($paragraph_number) > 0 && is_numeric($paragraph_number)) {
			$placement_paragraph = (int) $paragraph_number;
		} else {
			Ezoic_Integration_Logger::console_debug(
				"Insertion failed: Invalid paragraph '{$paragraph_number}' (must be numeric)",
				'Content Ads',
				'warn',
				$placeholder->position_id
			);
			return $content;
		}

		// If the placement display option is out of bounds, return the content
		if ($placement_paragraph == -1 || $placement_paragraph > \count($this->paragraphs)) {
			// Only show paragraph limit warning once per content processing
			if (!$this->paragraph_limit_warning_shown) {
				Ezoic_Integration_Logger::console_debug(
					"Paragraph {$placement_paragraph} not found (only " . \count($this->paragraphs) . " available). Additional paragraph insertions may also fail.",
					'Content Ads',
					'warn'
				);
				$this->paragraph_limit_warning_shown = true;
			}
			return $content;
		}

		// Select paragraph (convert to 0-based index)
		$paragraph_index = $placement_paragraph - 1;
		$target_paragraph = $this->paragraphs[$paragraph_index];

		// Determine insertion location
		$position = -1;
		if ($mode === 'before') {
			$position = $target_paragraph->open;
		} else {
			$position = $target_paragraph->close;
		}

		// Insert placeholder
		$original_content_length = strlen($content);
		$insertion_position = $position + $this->position_offset;

		$content = \ez_substr_replace($content, $placeholder_markup, $insertion_position);
		$this->position_offset += $placeholder_markup_len;

		// Check if insertion actually happened
		$new_content_length = strlen($content);
		if ($new_content_length > $original_content_length) {
			Ezoic_Integration_Logger::track_insertion($placeholder->position_id);
			Ezoic_Integration_Logger::console_debug(
				"Inserted {$mode} paragraph {$placement_paragraph}",
				'Content Ads',
				'info',
				$placeholder->position_id
			);
		} else {
			Ezoic_Integration_Logger::console_debug(
				"Failed insertion: Content unchanged (insertion error)",
				'Content Ads',
				'warn',
				$placeholder->position_id
			);
		}

		if (defined('EZOIC_DEBUG') && EZOIC_DEBUG) {
			$debugInfo = "";
			$debugInfo .= 'Placeholder ID: ' . $placeholder->position_id . PHP_EOL;
			$debugInfo .= 'Paragraph Insertion Number: ' . $paragraph_number . PHP_EOL;
			$debugInfo .= 'Mode: ' . $mode . PHP_EOL;
			$commented_content = "<!--[if IE 3 ]>Debugging Placeholder Insert: \n" . print_r($debugInfo, true) . "\n<![endif]-->";
			$content = $content . $commented_content;
		}

		return $content;
	}

	/**
	 * Extracts paragraphs and filters them based on filter rules
	 */
	private function get_paragraphs($content)
	{
		$filters = array();

		// Extract filter rules
		if (!empty($this->config->parent_filters)) {
			foreach ($this->config->parent_filters as $filter) {
				$filters[] = Ezoic_AdTester_Parent_Filter::parse_filter($filter);
			}
		}

		// Extract paragraphs
		$paragraphs = Ezoic_AdTester_Tag_Parser::parse($content, $this->config->paragraph_tags);

		// Final list of counted paragraphs
		$filtered_paragraphs = array();

		// Evaluate parent filters
		if (count($filters) > 0) {
			// Evaluate every paragraph
			foreach ($paragraphs as $paragraph) {
				$paragraph_valid = true;

				// Apply parent filters
				for ($idx = 0; $paragraph_valid && $idx < count($filters); $idx++) {
					$filter = $filters[$idx];
					$paragraph_valid = !$filter->is_valid($paragraph);
				}

				// Apply word count filter (skip for img tags since they don't have meaningful word counts)
				if ($paragraph_valid && isset($this->config->skip_word_count) && $this->config->skip_word_count > 0 && $paragraph->tag !== 'img') {
					$sub_content = \ez_substr($content, $paragraph->open, $paragraph->close - $paragraph->open);
					$word_count = \ez_word_count($sub_content);

					$paragraph_valid = $word_count >= $this->config->skip_word_count;
				}

				// Record paragraph if valid
				if ($paragraph_valid) {
					$filtered_paragraphs[] = $paragraph;
				}
			}
		} else {
			return $paragraphs;
		}

		return $filtered_paragraphs;
	}
}
