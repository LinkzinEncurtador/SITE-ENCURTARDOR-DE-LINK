<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_HTML_Inserter extends Ezoic_AdTester_Inserter
{
	public function __construct($config)
	{
		parent::__construct($config);
	}

	private function get_rules()
	{
		$rules = array();

		foreach ($this->config->placeholder_config as $ph_config) {
			if ($ph_config->page_type == $this->page_type && ($ph_config->display == 'before_element' || $ph_config->display == 'after_element')) {
				$placeholder = $this->config->placeholders[$ph_config->placeholder_id];

				if (Ezoic_AdTester_Inserter::should_include_placeholder($this->config, $placeholder, true)) {
					$rules[] = $ph_config;
				}
			}
		}

		return $rules;
	}

	/**
	 * Protect JavaScript and other content from phpQuery HTML parsing
	 */
	private function protect_js_content($content, &$protected_content)
	{
		$protected_content = array();
		$counter = 0;
		$original_content = $content;
		$original_length = strlen($content);

		// 1. Protect conditional comments containing scripts/styles (must be first)
		$content = preg_replace_callback(
			'/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/is',
			function ($matches) use (&$protected_content, &$counter) {
				$placeholder_id = 'EZOIC_CONDITIONAL_' . $counter . '_PLACEHOLDER';
				$protected_content[$placeholder_id] = $matches[0];
				$counter++;
				return '<!--' . $placeholder_id . '-->';
			},
			$content
		);

		// Check if conditional comments protection failed
		if (empty($content) || strlen($content) < ($original_length * 0.1)) {
			Ezoic_Integration_Logger::log_error(
				"HTML insertion failed - content processing error (comments)",
				'HTML Ads'
			);
			return $original_content; // Return original if first step failed
		}

		// 2. Protect script tags with better regex
		$content = preg_replace_callback(
			'/<script\b[^>]*>.*?<\/script>/is',
			function ($matches) use (&$protected_content, &$counter) {
				$placeholder_id = 'EZOIC_SCRIPT_' . $counter . '_PLACEHOLDER';
				$protected_content[$placeholder_id] = $matches[0];
				$counter++;
				return '<!--' . $placeholder_id . '-->';
			},
			$content
		);

		// Check if script protection failed
		if (empty($content) || strlen($content) < ($original_length * 0.1)) {
			Ezoic_Integration_Logger::log_error(
				"HTML insertion failed - content processing error (script)",
				'HTML Ads'
			);
			return $original_content; // Always return original if protection fails
		}

		// 3. Protect style tags
		$content = preg_replace_callback(
			'/<style\b[^>]*>.*?<\/style>/is',
			function ($matches) use (&$protected_content, &$counter) {
				$placeholder_id = 'EZOIC_STYLE_' . $counter . '_PLACEHOLDER';
				$protected_content[$placeholder_id] = $matches[0];
				$counter++;
				return '<!--' . $placeholder_id . '-->';
			},
			$content
		);

		// Check if style protection failed
		if (empty($content) || strlen($content) < ($original_length * 0.1)) {
			Ezoic_Integration_Logger::log_error(
				"HTML insertion failed - content processing error (style)",
				'HTML Ads'
			);
			return $original_content; // Always return original if protection fails
		}


		return $content;
	}
	/**
	 * Restore protected JavaScript content
	 */
	private function restore_js_content($content, $protected_content)
	{
		foreach ($protected_content as $placeholder_id => $original_content) {
			$placeholder = '<!--' . $placeholder_id . '-->';
			$content = str_replace($placeholder, $original_content, $content);
		}

		return $content;
	}

	/**
	 * Perform a server-side element insertion
	 */
	public function insert_server($content)
	{
		// Check if content is empty or null
		if (!isset($content) || strlen($content) === 0) {
			return $content;
		}

		// Skip HTML inserter for large content - let regular content filters handle insertion
		if (strlen($content) > 2000000) { // 2MB limit
			Ezoic_Integration_Logger::console_debug(
				"HTML insertion skipped - content too large (" . strlen($content) . " bytes > 2MB limit)",
				'HTML Ads',
				'info'
			);
			return $content;
		}

		// Do not run if dom module not loaded
		if (!extension_loaded("dom")) {
			Ezoic_Integration_Logger::log_error(
				"HTML insertion failed - DOM PHP extension not loaded",
				'HTML Ads'
			);
			return $content;
		}

		$rules = $this->get_rules();

		// If no rules to process, move on
		if (empty($rules)) {
			Ezoic_Integration_Logger::console_debug(
				"HTML insertion skipped - no HTML element rules found for page type '{$this->page_type}'",
				'HTML Ads',
				'info'
			);
			return $content;
		}

		$body_tag_matches = preg_split('/(<body.*?' . '>)/i', $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		if (\count($body_tag_matches) !== 3) {
			Ezoic_Integration_Logger::console_debug(
				"HTML insertion skipped - could not parse body tag. Found " . count($body_tag_matches) . " parts instead of 3",
				'HTML Ads',
				'warn'
			);
			return $content;
		}

		// Pull-in phpQuery to parse the document
		require_once(dirname(__FILE__) . '/../vendor/phpQuery.php');

		// Protect JavaScript and style content from phpQuery HTML parsing
		$protected_content = array();
		$original_body_content = $body_tag_matches[2];
		$body_content = $this->protect_js_content($original_body_content, $protected_content);

		// Check if protection failed and returned original content
		if ($body_content === $original_body_content && !empty($protected_content)) {
			// Protection failed but we have some protected content, skip HTML inserter entirely
			Ezoic_Integration_Logger::log_error(
				"HTML insertion failed - JS protection failed with " . count($protected_content) . " protected elements",
				'HTML Ads'
			);
			return $content;
		} elseif ($body_content === $original_body_content) {
			// Protection returned original content, likely due to failure, skip processing
			Ezoic_Integration_Logger::console_debug(
				"HTML insertion skipped - JS protection returned original content unchanged",
				'HTML Ads',
				'warn'
			);
			return $content;
		}

		// Parse only the body content with phpQuery
		\libxml_use_internal_errors(true);
		try {
			$doc = \phpQuery::newDocument($body_content);
			$content = $doc;
		} catch (\Exception $e) {
			Ezoic_Integration_Logger::log_error(
				"HTML insertion failed - phpQuery parsing error: " . $e->getMessage(),
				'HTML Ads'
			);
			return $body_tag_matches[0] . $body_tag_matches[1] . $body_content;
		}
		\libxml_use_internal_errors(false);

		foreach ($rules as $rule) {
			$nodes = @\pq($rule->display_option, $doc);
			$placeholder = $this->config->placeholders[$rule->placeholder_id];

			if (count($nodes) === 0) {
				Ezoic_Integration_Logger::console_debug(
					"Insertion failed: HTML selector '{$rule->display_option}' not found on page",
					'HTML Ads',
					'error',
					$placeholder->position_id
				);
				continue;
			}

			foreach ($nodes as $found_node) {

				if ($rule->display === 'before_element') {
					\pq($found_node)->before($placeholder->embed_code());
					Ezoic_Integration_Logger::track_insertion($placeholder->position_id);
					Ezoic_Integration_Logger::console_debug(
						"Inserted before element: {$rule->display_option}",
						'HTML Ads',
						'info',
						$placeholder->position_id
					);
				} elseif ($rule->display === 'after_element') {
					\pq($found_node)->after($placeholder->embed_code());
					Ezoic_Integration_Logger::track_insertion($placeholder->position_id);
					Ezoic_Integration_Logger::console_debug(
						"Inserted after element: {$rule->display_option}",
						'HTML Ads',
						'info',
						$placeholder->position_id
					);
				}
			}
		}

		$processed_content = $content->html();

		// Restore protected JavaScript and style content
		$processed_content = $this->restore_js_content($processed_content, $protected_content);

		return $body_tag_matches[0] . $body_tag_matches[1] . $processed_content;
	}

	/**
	 * Imports elements into the DOM
	 * @param $nodes Nodes to import
	 * @param $parent Parent node of the $target element
	 * @param $target Target element before which nodes should be inserted
	 */
	private function insert_nodes($nodes, $parent, $target)
	{
		$reversed_nodes = array_reverse($nodes);
		$current_node = $target;
		foreach ($reversed_nodes as $node) {
			$parent->insertBefore($node, $current_node);
			$current_node = $node;
		}
	}

	/**
	 * Creates a DOMNode from markup
	 */
	private function create_nodes($markup)
	{
		$node = new \DOMDocument();

		@$node->loadHTML('<span>' . $markup . '</span>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		$nodesToInsert = $node->getElementsByTagName('span')->item(0)->childNodes;

		return $nodesToInsert;
	}
}
