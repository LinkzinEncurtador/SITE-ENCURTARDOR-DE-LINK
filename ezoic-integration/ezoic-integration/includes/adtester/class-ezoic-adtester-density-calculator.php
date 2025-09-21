<?php

namespace Ezoic_Namespace;

/**
 * Density Calculator for WordPress Plugin
 * Mirrors the density strategies from the Go ad-positioner service
 */
class Ezoic_AdTester_Density_Calculator
{
	const MAX_INDEXES = 102;

	/**
	 * Get density array based on level (1=highest, 5=lowest)
	 *
	 * @param int $density_level Density level 1-5
	 * @return array Array of paragraph indexes to use for ad insertion
	 */
	public static function get_density($density_level)
	{
		// Default to moderate density if invalid level
		if ($density_level < 1 || $density_level > 5) {
			$density_level = 3;
		}

		switch ($density_level) {
			case 1: // Min density (fewest ads)
				return self::density_lowest();
			case 2: // Low density
				return self::density_lower();
			case 3: // Moderate density (Mid)
				return self::density_mid();
			case 4: // High density
				return self::density_higher();
			case 5: // Max density (most ads)
				return self::density_highest();
			default:
				return self::density_mid();
		}
	}

	/**
	 * Highest density - ads after every paragraph (1,2,3,4,5...)
	 */
	private static function density_highest()
	{
		$highest = array();
		// Generate consecutive 0-indexed positions: [0,1,2,3,4,5...]
		// This will display as paragraphs 1,2,3,4,5... in the admin
		for ($i = 0; $i < self::MAX_INDEXES; $i++) {
			$highest[] = $i;
		}

		return $highest;
	}

	/**
	 * Higher density - ads after every 2nd paragraph (1,3,5,7,9...)
	 */
	private static function density_higher()
	{
		$higher = array();
		// Generate pattern: [0, 2, 4, 6, 8, ...] (displays as paragraphs 1,3,5,7,9...)

		for ($i = 0; $i < self::MAX_INDEXES; $i += 2) {
			$higher[] = $i;
		}

		return $higher;
	}

	/**
	 * Mid density - ads after paragraphs 1, 3, 5, then every 3rd
	 */
	private static function density_mid()
	{
		$mid = array();
		$mid = array_merge($mid, array(0, 2, 4)); // paragraphs 1, 3, 5

		$counter = 7;
		for ($i = 3; $i <= self::MAX_INDEXES; $i++) {
			$mid[] = $counter - 1;
			$counter += 3;
		}
		return $mid;
	}

	/**
	 * Lower density - ads after paragraphs 1, 3, 6, then every 4th
	 */
	private static function density_lower()
	{
		$lower = array();
		$lower = array_merge($lower, array(0, 2, 5)); // paragraphs 1, 3, 6

		$counter = 9;
		for ($i = 3; $i <= self::MAX_INDEXES; $i++) {
			$lower[] = $counter - 1;
			$counter += 4;
		}
		return $lower;
	}

	/**
	 * Lowest density - ads after every 5th paragraph
	 */
	private static function density_lowest()
	{
		$lowest = array();

		$counter = 1;
		for ($i = 0; $i <= self::MAX_INDEXES; $i++) {
			$lowest[] = $counter - 1;
			$counter += 5;
		}
		return $lowest;
	}

	/**
	 * Get density level for a specific page type from config
	 *
	 * @param object $config Configuration object
	 * @param string $page_type Page type (post, page, home, category)
	 * @return int Density level (1-5, defaults to 3 for optimized mode)
	 */
	public static function get_density_level_for_page_type($config, $page_type)
	{
		// Check if density settings exist
		if (!isset($config->density_settings) || !is_object($config->density_settings) && !is_array($config->density_settings)) {
			return 3; // Default moderate density
		}

		$density_settings = (array) $config->density_settings;

		// Check if settings exist for this page type
		if (!isset($density_settings[$page_type])) {
			return 3; // Default moderate density
		}

		$page_settings = (array) $density_settings[$page_type];

		// If optimized mode or no custom level, use moderate density
		if (!isset($page_settings['mode']) || $page_settings['mode'] === 'optimized') {
			return 3;
		}

		// Return custom level, default to 3 if invalid
		$level = isset($page_settings['level']) ? (int) $page_settings['level'] : 3;
		$final_level = ($level >= 1 && $level <= 5) ? $level : 3;

		return $final_level;
	}
}
