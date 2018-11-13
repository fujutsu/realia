<?php

namespace Deco\Bundles\Offers\Modules\Archive_Offers;

class Init {

	public static function init() {

		Includes\Methods::init();
		Includes\Custom_Table::init();

		include_once "includes/class-wp-cli-archive-offers.php";

	}

}