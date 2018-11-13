<?php

namespace Deco\Bundles\Offers\Modules\Post_Type;

class Init {

	public static function init() {
		Includes\Meta_Fields::init();
		Includes\Post_Type_Offers::init();
	}
}