<?php

namespace Deco\Bundles\Offers\Modules\XML_Manager;


class Init {

	static $bundle_uri;
	static $bundle_path;
	public $wp_list_table;

	public static function init() {

		self::$bundle_path = __DIR__ . '/';
		self::$bundle_uri  = str_replace( ABSPATH, site_url() . '/', self::$bundle_path );


		add_action( 'admin_menu', __CLASS__ . '::add_admin_menus', 10 );
		add_filter( 'parent_file', __CLASS__ . '::set_current_menu', 999 );

		if ( false !== strpos( $_SERVER['REQUEST_URI'], 'edit.php?post_type=xml_list' ) ) {
			add_action( 'admin_head', __CLASS__ . '::css', 99 );
			self::change_active_xml();
		}
		if ( is_admin() ) {
			self::update_xml_now();
		}
		Includes\Post_Type::init();
		Includes\Meta_Fields::init();
		Includes\Methods::init();

		add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueue_scripts' );

	}

	public static function enqueue_scripts() {

		wp_enqueue_script( 'deco-xml-list', self::$bundle_uri . 'assets/js/admin-script.js', array(), filemtime( self::$bundle_path . 'assets/js/admin-script.js' ), true );

	}

	public static function add_admin_menus() {
		$page_title = 'Менеджер XML';
		$menu_title = 'Менеджер XML';
		$capability = 'edit_posts';
		$menu_slug  = 'edit.php?post_type=xml_list';
		$icon_url   = 'dashicons-list-view';
		$position   = 31;

		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, '', $icon_url, $position );

		$submenu_pages = array(
			array(
				'parent_slug' => $menu_slug,
				'page_title'  => 'Менеджер XML',
				'menu_title'  => 'Менеджер XML',
				'capability'  => 'edit_posts',
				'menu_slug'   => 'edit.php?post_type=xml_list',
				'function'    => null,
			)
		);

		foreach ( $submenu_pages as $submenu ) {
			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$submenu['menu_slug'],
				$submenu['function']
			);
		}

	}

	public static function set_current_menu( $parent_file ) {
		global $submenu_file, $current_screen;

		$is_current_menu = false;

		$post_type = $current_screen->post_type;

		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
		}


		if ( $post_type === 'xml_list' ) {
			$is_current_menu = true;
			$submenu_file    = 'edit.php?post_type=' . $post_type;
		}
		if ( $is_current_menu ) {
			$parent_file = 'edit.php?post_type=xml_list';
		}

		return $parent_file;

	}

	public static function change_active_xml() {
		global $wpdb;


		if ( $_SERVER['REQUEST_METHOD'] === 'GET' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'xml_list' && ! empty( $_GET['status_xml'] ) && in_array( $_GET['status_xml'], array(
				'disable',
				'enable'
			) ) ) {
			$nonce  = empty( $_GET['nonce'] ) ? '' : $_GET['nonce'];
			$action = empty( $_GET['status_xml'] ) ? '' : $_GET['status_xml'];
			$verify = wp_verify_nonce( $nonce, $action . '_xml' );

			if ( $verify ) {

				$post_id = (int) $_GET['post_id'];
				$status  = $_GET['status_xml'];

				$active = 0;

				$data = array(
					'post_status' => 'draft'
				);

				if ( $status === 'enable' ) {
					$active = 1;

					$data = array(
						'post_status' => 'publish'
					);
				}

				$wpdb->update(
					'wp_deco_xml_list',
					array(
						'is_active' => $active,
					),
					array(
						'post_id' => $post_id,
					)
				);

				$wpdb->update(
					'wp_posts',
					$data,
					array(
						'ID' => $post_id
					)
				);
				wp_redirect( $_SERVER['HTTP_REFERER'] );
				die();
			}
		}

	}

	public static function update_xml_now() {
		global $wpdb;


		if ( $_SERVER['REQUEST_METHOD'] === 'GET' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'xml_list' && ! empty( $_GET['update_now'] ) && $_GET['update_now'] === 'now' ) {
			$nonce  = empty( $_GET['nonce'] ) ? '' : $_GET['nonce'];
			$verify = wp_verify_nonce( $nonce, 'update_now' );

			if ( $verify ) {

				$post_id = (int) $_GET['post_id'];
				$link_id = $wpdb->get_var( "select id from wp_deco_xml_list where post_id = $post_id" );
				if ( $link_id ) {

					$wpdb->delete(
						'wp_deco_xml_queue_sync',
						array(
							'sync_id' => $link_id,
						)
					);

					$wpdb->insert(
						'wp_deco_xml_queue_sync',
						array(
							'sync_id'      => $link_id,
							'update_queue' => current_time( 'mysql' ),
						)
					);
//					echo $wpdb->last_error;
//					die();
				}
				$request_uri = $_SERVER['REQUEST_URI'];
				$request_uri = remove_query_arg( array( 'post_id', 'update_now', 'nonce' ), $request_uri );
				wp_redirect( $request_uri );
				die();
			}
		}

	}


	public static function css() {
		?>
        <style>
            #screen-meta-links,
            .subsubsub,
            .alignleft {
                display: none;
            }
        </style>
		<?php
	}
}
