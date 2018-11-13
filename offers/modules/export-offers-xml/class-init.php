<?php

namespace Deco\Bundles\Offers\Modules\Export_Offers_Xml;

class Init {

	public static function init() {

		Includes\Mitula::init();
		Includes\Lun::init();

		include_once "includes/class-wp-cli.php";

	}

}