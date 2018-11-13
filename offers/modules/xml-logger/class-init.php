<?php

namespace Deco\Bundles\Offers\Modules\XML_Logger;


class Init {

	static $bundle_uri;
	static $bundle_path;

	public static function init() {

		add_action( 'admin_menu', __CLASS__ . '::add_admin_menus', 10 );

		self::$bundle_path = __DIR__ . '/';
		self::$bundle_uri  = str_replace( ABSPATH, site_url() . '/', self::$bundle_path );

	}

	public static function add_admin_menus() {

		$page_title = 'Логи XML';
		$menu_title = 'Логи XML';
		$capability = 'edit_posts';
		$menu_slug  = 'xml_logger';
		$function   = __CLASS__ . '::xml_logger_page';
		$icon_url   = 'dashicons-list-view';
		$position   = 31;

		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );

	}

	public static function xml_logger_page() {

		require_once self::$bundle_path . 'includes/class-xml-logger-list-table.php';
		$wp_list_table = new \XML_Logger_List_Table();

		$wp_list_table->prepare_items();

		?>
        <div class="loader-wrapper" style="display: none;">
            <div class="load-container">
                <a class="loader"></a>
            </div>
        </div>
        <div class="wrap">
            <h2>Логи XML</h2>
            <br>
			<?php $wp_list_table->display(); ?>
        </div>

		<?php

	}

}