<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Revenue {

	public $position_id;
	public $revenue;
    public $revenue_percentage;

	public function __construct( $position_id, $revenue, $revenue_percentage ) {
		$this->position_id = $position_id;
        $this->revenue = $revenue;
        $this->revenue_percentage = $revenue_percentage;
	}

	public static function from_pubads( $adPositionId, $revenue ) {
		return new Ezoic_AdTester_Revenue( $adPositionId, $revenue->revenue, $revenue->revenuePercentage );
	}
}
