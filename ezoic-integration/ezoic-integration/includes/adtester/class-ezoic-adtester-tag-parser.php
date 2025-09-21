<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Paragraph
{
	public $open;
	public $close;
	public $tag;
	public $id;
	public $lineage;
	public $class_list;

	public function __construct($tag, $open, $close, $lineage, $class_list)
	{
		$this->tag			= $tag;
		$this->open			= $open;
		$this->close		= $close;
		$this->lineage		= $lineage;
		$this->class_list	= $class_list;
	}
}

class Ezoic_AdTester_Tag_Parser
{
	// Parsers
	const TAG_NAME_PARSER			= '/<([a-zA-Z][^\t\n\r\f \/>\x00]*)\s*/ui';
	const PATTERN_TAG_OPEN			= '/<[a-zA-Z][^\t\n\r\f \/>\x00]*(?:[\s\/]*(?:(?<=[\'"\s\/])[^\s\/>][^\s\/=>]*(?:\s*=+\s*(?:\'[^\']*\'|"[^"]*"|(?![\'"])[^>\s]*)\s*)?(?:\s|\/(?!>))*)*)?\s*/ui';
	const PATTERN_TAG_CLOSE			= '/<\/\s*([a-zA-Z][-.a-zA-Z0-9:_]*)\s*>/ui';
	const PATTERN_ATTR				= '/([^\r\n\t\f\v= \'"]+)(?:=(["\'])?((?:.(?!\2?\s+(?:\S+)=|\2))+.)\2?)?/ui';
	const PATTERN_COMMENT_OPEN		= '/<!--/ui';
	const PATTERN_COMMENT_CLOSE	= '/-->/ui';

	/**
	 * This is a static class, don't allow instances
	 */
	private function __construct() {}

	/**
	 * Parses HTML content for all included tags
	 */
	public static function parse($content, $paragraph_tags)
	{
		// Validation
		if (
			!isset($content) ||
			\ez_strlen($content) === 0 ||
			!isset($paragraph_tags) ||
			\count($paragraph_tags) === 0
		) {
			return $content;
		}

		// Find all open and close tags
		\preg_match_all(Ezoic_AdTester_Tag_Parser::PATTERN_TAG_OPEN, $content, $matches_open, PREG_OFFSET_CAPTURE);
		\preg_match_all(Ezoic_AdTester_Tag_Parser::PATTERN_TAG_CLOSE, $content, $matches_close, PREG_OFFSET_CAPTURE);

		// Find comment tag ranges
		$comment_tag_ranges = Ezoic_AdTester_Tag_Parser::get_comment_ranges($content);

		// Merge found tags into a sorted list
		$tags = Ezoic_AdTester_Tag_Parser::merge($matches_open[0], $matches_close[0]);

		// Current tag name
		$curent_tag = '';

		// Flags to pause parsting
		$in_void_element		= false;
		$in_script_element	= false;

		// echo print_r($tags,true);
		$tag_stack	= array();
		$result		= array();

		// Walk the list of found tags
		foreach ($tags as $tag) {
			// Is this an open tag?
			$is_open_tag = \ez_substr($tag[0], 0, 2) !== '</';

			// Peek at the top item in the stack
			$last_tag = '';
			if (\count($tag_stack) > 0) {
				$last_tag = \end($tag_stack)->tag;
			}

			if ($is_open_tag) {
				// Opening tag

				// Extract tag element name
				\preg_match_all(Ezoic_AdTester_Tag_Parser::TAG_NAME_PARSER, $tag[0], $tag_name);
				$tag_element = \ez_strtolower($tag_name[1][0]);

				// Opening script element, stop recording tags (BEFORE processing)
				if ($tag_element === 'script') {
					$in_script_element = true;
				}

				// Skip tag if we are within a script element
				if (!$in_script_element) {
					$stack_item = new Ezoic_AdTester_Paragraph($tag_element, $tag[1], -1, null, null);

					$tag_attrs = Ezoic_AdTester_Tag_Parser::get_attrs($tag[0]);

					$stack_item->id			= $tag_attrs['id'];
					$stack_item->class_list	= $tag_attrs['classes'];

					// Store lineage
					//TODO: This may be better served using a tree
					$stack_item->lineage = array_map(function ($item) {
						return array('tag' => $item->tag, 'class_list' => $item->class_list, 'id' => $item->id);
					}, $tag_stack);

					array_push($tag_stack, $stack_item);
				}
			} else {
				// Extract tag element name
				$tag_element = \ez_strtolower(\ez_substr($tag[0], 2, \ez_strlen($tag[0]) - 3));

				// Closing tag - pop element from the stack and store
				if (!$in_script_element) {
					// If this closing element cannot be attributed to the top of the stack, begin walking the stack
					while (\count($tag_stack) > 0 && $tag_element !== $last_tag) {
						$tag_temp = array_pop($tag_stack);
						$tag_temp->close = $tag[1];

						// Store tag details if this is a paragraph tag
						if (\in_array($tag_temp->tag, $paragraph_tags) && !Ezoic_AdTester_Tag_Parser::is_in_comment($tag_temp, $comment_tag_ranges)) {
							$result[] = $tag_temp;
						}

						// Peek at the top of the stack
						if (\count($tag_stack) > 0) {
							$last_tag = \end($tag_stack)->tag;
						} else {
							$last_tag = '';
						}
					}

					// If the stack is not empty, pop and store
					if (\count($tag_stack) > 0) {
						$tag_temp = array_pop($tag_stack);
						$tag_temp->close = $tag[1] + \ez_strlen($tag[0]);

						// Store tag details if this is a paragraph tag
						if (\in_array($tag_element, $paragraph_tags) && !Ezoic_AdTester_Tag_Parser::is_in_comment($tag_temp, $comment_tag_ranges)) {
							$result[] = $tag_temp;
						}
					}
				}

				// Closing script element, begin recording tags again
				if ($tag_element === 'script') {
					$in_script_element = false;
					$tag_temp = array_pop($tag_stack);
				}
			}
		}

		return $result;
	}

	/**
	 * Indicates if the indicated tag is within a comment block
	 */
	private static function is_in_comment($tag, $comment_ranges)
	{
		foreach ($comment_ranges as $range) {
			if (!array_key_exists('open', $range) || !array_key_exists('close', $range)) {
				continue;
			}

			// If the tag open position is within the current comment range
			if ($tag->open > $range['open'] && $tag->open < $range['close']) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Obtains a list of comment ranges
	 */
	private static function get_comment_ranges($content)
	{
		\preg_match_all(Ezoic_AdTester_Tag_Parser::PATTERN_COMMENT_OPEN, $content, $comment_open_tags, PREG_OFFSET_CAPTURE);
		\preg_match_all(Ezoic_AdTester_Tag_Parser::PATTERN_COMMENT_CLOSE, $content, $comment_close_tags, PREG_OFFSET_CAPTURE);

		//TODO: Need range check
		$comment_tags = Ezoic_AdTester_Tag_Parser::merge($comment_open_tags[0], $comment_close_tags[0]);

		$comment_ranges = array();
		$tag_stack = array();

		// Walk the stack of comment tags
		foreach ($comment_tags as $tag) {
			if ($tag[0] === '<!--') {
				\array_push($tag_stack, array('open' => $tag[1], 'close' => 0));
			} else {
				$comment_tag = array_pop($tag_stack);
				$comment_tag['close'] = $tag[1];

				$comment_ranges[] = $comment_tag;
			}
		}

		return $comment_ranges;
	}

	/**
	 * Fetches a list of attributes (id and class) applied to a given tag
	 */
	private static function get_attrs($tag_string)
	{
		$result = array('id' => '', 'classes' => array());

		\preg_match_all(Ezoic_AdTester_Tag_Parser::PATTERN_ATTR, $tag_string, $attr_matches);

		// If there are no attributes, return
		if (\count($attr_matches) !== 4 || \count($attr_matches[1]) <= 1) {
			return $result;
		}

		// Find the class and id list index
		$class_list_idx = -1;
		$id_idx = -1;
		for ($specifier_idx = 0; $class_list_idx === -1 && $specifier_idx < \count($attr_matches[1]); $specifier_idx++) {
			if (\ez_strtolower($attr_matches[1][$specifier_idx]) === 'class') {
				$class_list_idx = $specifier_idx;
			}

			if (\ez_strtolower($attr_matches[1][$specifier_idx]) === 'id') {
				$id_idx = $specifier_idx;
			}
		}

		// Extract class list
		if ($class_list_idx !== -1) {
			// Extract class list
			$class_list_raw = $attr_matches[3][$class_list_idx];
			$class_list_split = \explode(' ', $class_list_raw);

			// Add classes to the final result
			foreach ($class_list_split as $class_name) {
				$result['classes'][] = \trim($class_name);
			}
		}

		// Extract id
		if ($id_idx !== -1) {
			$result['id'] = $attr_matches[3][$id_idx];
		}

		return $result;
	}

	/**
	 * Merge and sort two arrays of tag matches
	 */
	private static function merge($left, $right)
	{
		$result = array();

		while (count($left) > 0 && count($right) > 0) {
			if ($left[0][1] > $right[0][1]) {
				$result[] = $right[0];
				$right = array_slice($right, 1);
			} else {
				$result[] = $left[0];
				$left = array_slice($left, 1);
			}
		}

		while (count($left) > 0) {
			$result[] = $left[0];
			$left = array_slice($left, 1);
		}

		while (count($right) > 0) {
			$result[] = $right[0];
			$right = array_slice($right, 1);
		}

		return $result;
	}
}
