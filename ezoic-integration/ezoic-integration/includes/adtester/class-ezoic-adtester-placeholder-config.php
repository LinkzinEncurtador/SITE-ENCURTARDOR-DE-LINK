<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Placeholder_Config
{
	public $page_type;
	public $placeholder_id;
	public $display;
	public $display_option;
	public $is_default;

	public function __construct($page_type, $placeholder_id, $display, $display_option, $is_default)
	{
		$this->page_type			= $page_type;
		$this->placeholder_id	= $placeholder_id;
		$this->display				= $display;
		$this->display_option	= $display_option;
		$this->is_default			= $is_default;
	}
}
