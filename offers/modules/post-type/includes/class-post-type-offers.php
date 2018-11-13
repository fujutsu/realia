<?php

namespace Deco\Bundles\Offers\Modules\Post_Type\Includes;

class Post_Type_Offers {
	private static $post_type = 'offers';
	public static $img_path;

	public static function init() {
		self::$img_path = \Deco_Site::$theme_uri . '/assets/img/defaults/';

		add_action( 'init', __CLASS__ . '::register' );
//		add_filter( 'enter_title_here', __CLASS__ . '::change_editor_title_text' );
		add_action( 'save_post', __CLASS__ . '::save_offer' );
		add_action( 'deco_offers_custom_save_post', __CLASS__ . '::save_offer' );
		add_filter( 'wp_insert_post_data', __CLASS__ . '::modify_post_title', 99, 2 );
		add_filter( 'manage_edit-' . self::$post_type . '_columns', __CLASS__ . '::add_columns' );
		add_action( 'manage_posts_custom_column', __CLASS__ . '::manage_columns', 10, 2 );
		add_filter( 'deco_offers_percentage_of_fill_data', __CLASS__ . '::percentage_of_fill_data', 10, 2 );
		add_action( 'pre_get_posts', __CLASS__ . '::pre_get_posts', 10 );
		add_action( 'before_delete_post', __CLASS__ . '::delete', 10 );
		add_action( 'init', __CLASS__ . '::custom_post_status', 8 );
		add_action( 'transition_post_status', __CLASS__ . '::on_all_status_transitions', 10, 3 );
		add_action( 'deco_save_offer_additional_data', __CLASS__ . '::save_add_data' );
		add_action( 'deco_offer_generate_photo_list', __CLASS__ . '::generate_photo_list' );
		add_action( 'deco_offer_check_offer_category', __CLASS__ . '::check_offer_category' );
	}

	public static function register() {

		$labels = array(
			'name'               => 'Объявления',
			'singular_name'      => 'Объявления',
			'add_new'            => 'Добавить',
			'add_new_item'       => 'Добавить новое Объявление',
			'edit_item'          => 'Редактировать Объявление',
			'new_item'           => 'Новое Объявление',
			'view_item'          => 'Просмотр Объявлений',
			'search_items'       => 'Поиск Объявления',
			'not_found'          => 'Ничего не найдено',
			'not_found_in_trash' => 'Ничего не найдено в корзине',
		);

		register_post_type( self::$post_type, array(
			'labels'       => $labels,
			'public'       => true,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-megaphone',
			//'capability_type' => 'offer',
			'has_archive'  => true,
			'hierarchical' => false,
			'supports'     => array( 'editor', 'comments' ),
			'rewrite'      => array( 'slug' => '' ),
		) );

	}

	public static function custom_post_status() {
		register_post_status( 'archive', array(
			'label'                     => 'Архивные',
			'public'                    => true,
			'private'                   => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Архивные <span class="count">(%s)</span>', 'Архивные <span class="count">(%s)</span>' ),
		) );
		register_post_status( 'sales', array(
			'label'                     => 'Проданные',
			'public'                    => false,
			'private'                   => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Проданные <span class="count">(%s)</span>', 'Проданные <span class="count">(%s)</span>' ),
		) );

		register_post_status( 'address_check', array(
			'label'                     => 'Необходимо проверить адрес',
			'public'                    => false,
			'private'                   => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( '<span style="color: red;">Необходимо проверить адрес <span class="count">(%s)</span></span>', '<span style="color: red;">Необходимо проверить адрес <span class="count">(%s)</span></span>' ),
		) );
	}

	public static function change_editor_title_text( $title ) {
		$screen = get_current_screen();

		if ( self::$post_type == $screen->post_type ) {
			$title = 'Введите заголовок объявления';
		}

		return $title;
	}

	public static function modify_post_title( $data, $postarr ) {
		if ( $data['post_type'] === 'offers' ) {
			$option_qtranslate_term_name = get_option( 'qtranslate_term_name' );
			$type_term                   = get_term_by( 'id', $postarr['yoast_wpseo_primary_deal_type_term'], 'deal_type' );
			$term                        = get_term_by( 'id', $postarr['yoast_wpseo_primary_offer_type_term'], 'offer_type' );

			if ( is_wp_error( $term ) || empty( $term ) || is_wp_error( $type_term ) || empty( $type_term ) ) {
				return $data;
			}

			$alternative_title = get_term_meta( $term->term_id, 'deco_offer_type_alternative_title_object', true );
			$offer_cat_name_ru = qtranxf_use( 'ru', $alternative_title ) ? qtranxf_use( 'ru', $alternative_title ) : mb_strtolower( $option_qtranslate_term_name[ $term->name ]['ru'] );
			$offer_cat_name_ua = qtranxf_use( 'ua', $alternative_title ) ? qtranxf_use( 'ua', $alternative_title ) : mb_strtolower( $option_qtranslate_term_name[ $term->name ]['ua'] );

			$title = '[:ru]' . $option_qtranslate_term_name[ $type_term->name ]['ru'] . ' ' . $offer_cat_name_ru . '[:ua]' . $option_qtranslate_term_name[ $type_term->name ]['ua'] . ' ' . $offer_cat_name_ua . '[:]';

			$data['post_title'] = $title;
		}

		return $data;
	}

	public static function add_columns( $columns ) {

		$new_columns = array(
			'cb'                      => $columns['cb'],
			'offers_thumbnail'        => 'Миниатюра',
			'title'                   => $columns['title'],
			'offers_original_link'    => 'Ссылка на оригинал',
//			'author'                  => $columns['author'],
//			'categories' => $columns['categories'],
//			'tags'       => $columns['tags'],
			'deco_offer_price'        => 'Стоимость',
			'offers_locality'         => 'Адрес',
			'offers_metro'            => 'Метро',
			'taxonomy-offer_category' => $columns['taxonomy-offer_category'],
			'taxonomy-offer_type'     => $columns['taxonomy-offer_type'],
			'taxonomy-deal_type'      => $columns['taxonomy-deal_type'],
			'offer-agent-agency'      => 'Кто',
			'comments'                => $columns['comments'],
			'date'                    => $columns['date'],
		);

		return $new_columns;
	}

	public static function manage_columns( $column, $id ) {
		global $wpdb;

		switch ( $column ) {
			case 'offers_thumbnail' :
				$thumb_url = get_post_meta( $id, 'deco_offer_thumbnail', true );

				if ( empty( $thumb_url ) ) {
					$thumb_url = self::$img_path . 'thumbnail.png';
				}

				if ( ! empty( $thumb_url ) ) {

					$thumb_url = str_replace( 'https://cdn.realia.ua', site_url(), $thumb_url );
					?>
                    <div>
                        <a href="<?php echo admin_url( 'post.php?post=' . $id . '&action=edit' ); ?>">
                            <img src="<?php echo $thumb_url; ?>" style="max-width: 100px;"/>
                        </a>
                    </div>
					<?php
				}
				break;

			case 'offer-agent-agency':

				static $user = array();

				$arrancy_id = $wpdb->get_var( "select user_id from wp_deco_xml_data_offers where post_id = $id limit 1" );
				if ( ! isset( $user[ $arrancy_id ] ) ) {
					$user[ $arrancy_id ] = get_userdata( $arrancy_id );
				}

				if ( isset( $user[ $arrancy_id ] ) ) {
					echo '<a href="' . admin_url( 'user-edit.php?user_id=' . $arrancy_id ) . '" target="_blank">' . $user[ $arrancy_id ]->display_name . '</a>';
				}

				break;

			case 'offers_locality':
				echo apply_filters( 'deco_get_address_string_for_admin_column', array(
					'post_id' => $id
				) );
				break;
			case 'deco_offer_price':
				echo '$' . number_format( floatval( get_post_meta( $id, 'deco_offer_price', true ) ), 0, ' ', ' ' );
				break;

			case 'offers_metro':
				$metros = wp_get_object_terms( $id, array( 'metro', 'metro_kharkiv', 'metro_dnepr', 'metro_krog' ) );
				if ( $metros && ! is_wp_error( $metros ) ) {
					$metros_arr = array();
					foreach ( $metros as $metro ) {
						$metros_arr[] = '<a href="' . admin_url( 'edit.php?post_type=offers&taxonomy=' . $metro->taxonomy . '&term=' . $metro->slug ) . '">' . qtranxf_use( 'ru', $metro->name ) . '</a>';
					}

					if ( count( $metros_arr ) > 0 ) {
						echo implode( ',<br> ', $metros_arr );
					}
				}

				break;

			case 'offers_original_link':
				$link = $wpdb->get_var( "select url from wp_deco_xml_data_offers where post_id = $id" );
				if ( $link ) {
					echo '<a href="' . $link . '" target="_blank">Посмотреть</a>';
				}

				break;

		}
	}

	public static function save_offer( $post_id ) {
		global $wpdb;

		if ( get_post_type( $post_id ) != self::$post_type ) {
			return;
		}

		self::generate_photo_list( $post_id );
		self::offer_square( $post_id );
		self::offer_land_area( $post_id );
		self::offer_distance( $post_id );
		self::offer_cost( $post_id );
		self::offer_save_percentage_of_filled_data( $post_id );
		self::offer_year_built( $post_id );
		self::offer_coords( $post_id );
		self::check_offer_category( $post_id );

	}

	/**
	 * Проверить наличие категории у Предложения
	 * Если отсутствует, ставить статус pending
	 */
	public static function check_offer_category( $args ) {
		global $wpdb;

		$post_id = empty( $args['post_id'] ) ? 0 : (int) $args['post_id'];
		$status  = empty( $args['status'] ) ? 'pending' : $args['status'];

		if ( $post_id && $_SERVER['REQUEST_METHOD'] !== 'POST' ) {

			$offer_category = wp_get_object_terms( $post_id, 'offer_category' );

			if ( ! $offer_category || is_wp_error( $offer_category ) ) {
				$wpdb->update(
					'wp_posts',
					array(
						'post_status' => $status,
					),
					array(
						'ID' => $post_id,
					)
				);
			}

		}
	}


	public static function generate_photo_list( $post_id ) {
		$deco_offer_photo_list = isset( $_POST['deco_offer_photo_list'] ) ? $_POST['deco_offer_photo_list'] : get_post_meta( $post_id, 'deco_offer_photo_list', true );

		if ( $deco_offer_photo_list ) {
			$count            = 1;
			$slides           = $map_marker_slides = '';
			$single_slides    = array();
			$map_marker_thumb = array( '' );
			foreach ( $deco_offer_photo_list as $id => $value ) {
				if ( $count <= 5 ) {
					$slide    = wp_get_attachment_image_src( $id, 'full' );
					$slide[0] = apply_filters( 'replace_image_size_by_pj_cdn', $slide[0], 'offer_blog_loop_tease' );

					$message_object = wp_get_attachment_image_src( $id, 'full' );

					if ( $count == 1 ) {
						update_post_meta( $post_id, 'deco_offer_thumbnail', $slide[0] );
						update_post_meta( $post_id, 'message_object', $message_object[0] );
						update_post_meta( $post_id, '_thumbnail_id', $id );
					}
					$slides .= '<span class="pu-img-src" data-img-src="' . $slide[0] . '"></span>';
				}
				$single_slide    = wp_get_attachment_image_src( $id, 'full' );
				$single_slide[0] = apply_filters( 'replace_image_size_by_pj_cdn', $single_slide[0], 'offer_single_tease' );

				$single_slides[]   = array(
					'src'  => $single_slide[0],
					'full' => $value,
				);
				$map_marker_slides .= '<div class="flat-modal-slider-item">
            <div class="flat-modal-previev js_modal-slide-item" data-modal-src="' . $single_slide[0] . '" style="background-image: url(' . $map_marker_thumb[0] . ');"></div>
        </div>';
				$count ++;
			}
			if ( ! empty( $slides ) ) {
				update_post_meta( $post_id, 'deco_offer_home_slides', $slides );
			}
			if ( ! empty( $single_slides ) ) {
				update_post_meta( $post_id, 'deco_offer_single_slides', $single_slides );
			}
//			if ( ! empty( $map_marker_slides ) ) {
//				update_post_meta( $post_id, 'deco_offer_map_marker_slides', $map_marker_slides );
//			}

		}

	}

	private static function offer_square( $post_id ) {
		global $wpdb;

		$blog_id = get_current_blog_id();

		$deal_type_slug = self::get_deal_slug( $post_id );
		$offer_category = self::get_offer_cat_slug( $post_id );

		$wpdb->query( "delete from {$wpdb->base_prefix}offers_area where post_id = $post_id and blog_id = $blog_id" );

		$deco_offer_square = self::format_range_data( $post_id, 'deco_offer_square' );

		if ( $deco_offer_square > 0 ) {
			$wpdb->query( "insert into {$wpdb->base_prefix}offers_area (area, post_id, blog_id, deal_type, offer_category) values ($deco_offer_square, $post_id, $blog_id, $deal_type_slug, $offer_category)" );
		}
	}

	private static function offer_year_built( $post_id ) {
		global $wpdb;

		$blog_id = get_current_blog_id();

		$deal_type_slug = self::get_deal_slug( $post_id );
		$offer_category = self::get_offer_cat_slug( $post_id );

		$wpdb->query( "delete from {$wpdb->base_prefix}offers_year_built where post_id = $post_id and blog_id = $blog_id" );

		$deco_offer_year_built = isset( $_POST['deco_offer_year_built'] ) ? $_POST['deco_offer_year_built'] : get_post_meta( $post_id, 'deco_offer_year_built', true );

		if ( $deco_offer_year_built > 0 ) {
			$wpdb->query( "insert into {$wpdb->base_prefix}offers_year_built (year_built, post_id, blog_id, deal_type, offer_category) values ($deco_offer_year_built, $post_id, $blog_id, $deal_type_slug, $offer_category)" );
		}
	}

	private static function offer_coords( $post_id ) {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && is_admin() ) {
			$address_line = '';
			$address_line .= $_POST['address_region_name'] ? $_POST['address_region_name'] : '';
			$address_line .= $_POST['address_locality_name'] ? ', ' . $_POST['address_locality_name'] : '';
			$address_line .= $_POST['address_street_name'] ? ', ' . $_POST['address_street_name'] : '';
			$address_line .= $_POST['address_house_number'] ? ', ' . $_POST['address_house_number'] : '';

			if ( ! empty( $address_line ) ) {
				$coords = \Deco\Bundles\Address\Modules\Google_Maps\Init::address_get_google_coords_by_address_line( array( 'address_line' => $address_line ) );

				if ( ! empty( $coords['lat'] ) && ! empty( $coords['lng'] ) ) {
					update_post_meta( $post_id, 'address_latitude', $coords['lat'] );
					update_post_meta( $post_id, 'address_longitude', $coords['lng'] );
				}
			}
		}
	}

	private static function offer_land_area( $post_id ) {
		global $wpdb;

		$blog_id = get_current_blog_id();

		$deal_type_slug = self::get_deal_slug( $post_id );
		$offer_category = self::get_offer_cat_slug( $post_id );

		$wpdb->query( "delete from {$wpdb->base_prefix}offers_land_area where post_id = $post_id and blog_id = $blog_id" );

		$deco_land_area = self::format_range_data( $post_id, 'deco_offer_land_area' );

		if ( $deco_land_area > 0 ) {
			$wpdb->query( "insert into {$wpdb->base_prefix}offers_land_area (area, post_id, blog_id, deal_type, offer_category) values ($deco_land_area, $post_id, $blog_id, $deal_type_slug, $offer_category)" );
		}
	}

	private static function offer_distance( $post_id ) {
		global $wpdb;

		$blog_id = get_current_blog_id();

		$deal_type_slug = self::get_deal_slug( $post_id );
		$offer_category = self::get_offer_cat_slug( $post_id );

		$wpdb->query( "delete from {$wpdb->base_prefix}offers_distance where post_id = $post_id and blog_id = $blog_id" );

		$deco_distance = self::format_range_data( $post_id, 'deco_offer_distance' );

		if ( $deco_distance > 0 ) {
			$wpdb->query( "insert into {$wpdb->base_prefix}offers_distance (distance, post_id, blog_id, deal_type, offer_category) values ($deco_distance, $post_id, $blog_id, $deal_type_slug, $offer_category)" );
		}
	}

	private static function offer_cost( $post_id ) {
		global $wpdb;

		$wpdb->query( "delete from {$wpdb->base_prefix}offers_cost where post_id = $post_id" );

		$deal_type_slug = self::get_deal_slug( $post_id );
		$offer_category = self::get_offer_cat_slug( $post_id );

		$deco_offer_price = self::format_range_data( $post_id, 'deco_offer_price' );

		if ( $deco_offer_price > 0 && $offer_category ) {
			$wpdb->query( "insert into {$wpdb->base_prefix}offers_cost (cost, post_id, deal_type, offer_category) values ($deco_offer_price, $post_id, $deal_type_slug, $offer_category)" );
		}
	}

	private static function format_range_data( $post_id, $meta_key = '' ) {
		$result = isset( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : get_post_meta( $post_id, $meta_key, true );
		$result = str_replace( ',', '.', $result );

		return floatval( $result );
	}

	private static function get_deal_slug( $post_id ) {

		$deal_type_id = isset( $_POST['tax_input']['deal_type'] ) ? $_POST['tax_input']['deal_type'] : '';

		if ( is_array( $deal_type_id ) ) {
			$deal_type_id = array_filter( $deal_type_id );
			$deal_type_id = intval( array_values( array_filter( $deal_type_id ) )[0] );
		}

		if ( empty( $deal_type_id ) ) {
			$deal_type    = wp_get_post_terms( $post_id, 'deal_type' );
			$deal_type_id = $deal_type[0]->term_id;
		}

		return $deal_type_id;
	}

	private static function get_offer_cat_slug( $post_id ) {

		$offer_cat_id = isset( $_POST['tax_input']['offer_category'] ) ? $_POST['tax_input']['offer_category'] : '';
		if ( is_array( $offer_cat_id ) ) {
			$offer_cat_id = array_filter( $offer_cat_id );
			$offer_cat_id = intval( array_values( array_filter( $offer_cat_id ) )[0] );
		}

		if ( empty( $offer_cat_id ) ) {
			$offer_category = wp_get_post_terms( $post_id, 'offer_category' );
			$offer_cat_id   = $offer_category[0]->term_id;
		} /*else {
			$offer_category_slug = '';

			$offer_category = get_term_by( 'id', $offer_cat_id, 'offer_category' );
			if ( ! is_wp_error( $offer_category ) ) {
				$offer_category_slug = $offer_category->slug;
			}
		}*/

		return $offer_cat_id;
	}

	private static function offer_save_percentage_of_filled_data( $post_id ) {
		global $wpdb;

		return;
		$blog_id = get_current_blog_id();

		if ( $wpdb->get_row( "select id from {$wpdb->base_prefix}deco_users_offers_promotion where post_id = $post_id and blog_id = $blog_id and active = 1 limit 1" ) ) {
			$wpdb->update(
				$wpdb->base_prefix . 'deco_users_offers_promotion',
				array(
					'filling_percentage' => self::percentage_of_fill_data( $post_id )
				),
				array(
					'post_id' => $post_id,
					'blog_id' => $blog_id,
					'active'  => 1
				),
				array( '%d' ),
				array(
					'%d',
					'%d',
					'%d',
				)
			);
		}
	}

	/**
	 * Function calc percentage filled fields and taxonomies for display in top offers by relevant
	 */
	public static function percentage_of_fill_data( $post_id, $blog_id = 1 ) {

		$offer_category = wp_get_post_terms( $post_id, 'offer_category' );

		if ( isset( $offer_category[0] ) ) {
			$offer_category = $offer_category[0];
		} else {

			return 0;
		}

		$filling_meta_keys = array();
		$filling_taxonomy  = array();

		switch ( $offer_category->slug ) {

			case 'kvartir':
				// Kvartira
				$filling_meta_keys = array(
					'deco_offer_location',
					'deco_offer_price',
					'deco_offer_square',
					'deco_offer_photo_list',
					'deco_offer_house_number',
					'deco_offer_available_square',
					'deco_offer_living_area',
					'deco_offer_kitchen_area',
					'deco_offer_floor',
					'deco_offer_year_built',
					'deco_offer_ceiling_height',
				);

				$filling_taxonomy = array(
					'count_rooms',
					'count_balconies',
					'wall_type',
					'repair',
					'ifs',
					'count_bathrooms',
					'bathroom',
					'heating',
					'parking',
					'metro',
				);
				break;

			case 'domov' :
				$filling_meta_keys = array(
					'deco_offer_location',
					'deco_offer_price',
					'deco_offer_square',
					'deco_offer_photo_list',
					'deco_offer_house_number',
					'deco_offer_available_square',
					'deco_offer_living_area',
					'deco_offer_kitchen_area',
					'deco_offer_land_area',
					'deco_offer_floor',
					'deco_offer_year_built',
					'deco_offer_ceiling_height',
					'deco_offer_distance',
				);

				$filling_taxonomy = array(
					'count_rooms',
					'window_type',
					'wall_type',
					'repair',
					'ifs',
					'count_bathrooms',
					'bathroom',
					'heating',
					'sewerage',
					'plumbing',
					'metro',
				);

				break;
			case 'kommercheskoj-nedvizhimosti' :
				$filling_meta_keys = array(
					'deco_offer_location',
					'deco_offer_price',
					'deco_offer_square',
					'deco_offer_photo_list',
					'deco_offer_house_number',
					'deco_offer_available_square',
					'deco_offer_living_area',
					'deco_offer_kitchen_area',
					'deco_offer_land_area',
					'deco_offer_floor',
					'deco_offer_year_built',
					'deco_offer_ceiling_height',
					'deco_offer_distance',
				);

				$filling_taxonomy = array(
					'lodge_class',
					'building_type',
					/*'kitchen',*/
					'window_type',
					'sewerage',
					'wall_type',
					'repair',
					'ifs',
					'heating',
					'parking',
					'count_rooms',
					'metro',
				);

				break;
			case 'zemelnyh-uchastkov' :
				$filling_meta_keys = array(
					'deco_offer_location',
					'deco_offer_price',
					'deco_offer_square',
					'deco_offer_photo_list',
					'deco_offer_distance',
					'deco_offer_cadastral_number',
				);

				$filling_taxonomy = array(
					'location',
					'ifs',
					'metro',
					'sewerage',
					'plumbing',
					'electricity',
					'gasmain',
					'intended_purpose',
				);

				break;
			case 'garazhej-i-parkingov' :
				$filling_meta_keys = array(
					'deco_offer_location',
					'deco_offer_price',
					'deco_offer_square',
					'deco_offer_year_built',
					'deco_offer_photo_list',
				);

				$filling_taxonomy = array(
					'ifs',
					'electricity',
					'parking',
					'metro',
				);
				break;
		}

		$quan_fields          = 0;
		$quan_no_empty_fields = 0;

		foreach ( $filling_meta_keys as $meta_key ) {
			$quan_fields ++;
			$filling = isset( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : get_post_meta( $post_id, $meta_key, true );
			if ( ! empty( $filling ) ) {
				$quan_no_empty_fields ++;
			}
		}

		foreach ( $filling_taxonomy as $taxonomy ) {
			$term = wp_get_post_terms( $post_id, $taxonomy );
			$quan_fields ++;
			if ( count( $term ) > 0 ) {
				$quan_no_empty_fields ++;
			}
		}

		return round( ( 100 / $quan_fields ) * $quan_no_empty_fields, 0 );

	}


	public static function pre_get_posts( $query ) {
		if ( is_admin() && $query->is_main_query() ) {
			if ( isset( $_GET['agency_agent_id'] ) && intval( $_GET['agency_agent_id'] ) ) {
				$query->set( 'author', $_GET['agency_agent_id'] );
			}

		}
	}

	public static function delete( $post_id ) {
		global $wpdb;

		$post_type = get_post_type( $post_id );

		if ( $post_type == self::$post_type ) {


			$wpdb->update(
				'wp_deco_xml_data_offers',
				array(
					'post_id' => 0,
				),
				array(
					'post_id' => $post_id,
				)
			);

//			$wpdb->delete( 'wp_deco_xml_data_offers',
//				array(
//					'post_id' => $post_id,
//				)
//			);

			$wpdb->update(
				'wp_deco_xml_data_offermeta',
				array(
					'post_id' => 0,
				),
				array(
					'post_id' => $post_id,
				)
			);

			$deco_offer_photo_list = get_post_meta( $post_id, 'deco_offer_photo_list', true );
			if ( is_array( $deco_offer_photo_list ) && count( $deco_offer_photo_list ) > 0 ) {
				foreach ( $deco_offer_photo_list as $id => $link ) {
					wp_delete_attachment( $id, true );
				}
			}

		}
	}

	public static function save_add_data( $post_id ) {

		self::offer_square( $post_id );
		self::offer_land_area( $post_id );
		self::offer_distance( $post_id );
		self::offer_cost( $post_id );
		self::offer_save_percentage_of_filled_data( $post_id );

	}

	public static function on_all_status_transitions( $new_status, $old_status, $post ) {
		global $wpdb;
		if ( $post->post_type === 'offers' ) {
			$post_id = $post->ID;
			if ( in_array( $new_status, array( 'publish', 'pending' ) ) ) {
				if ( ! $wpdb->get_row( "select id from wp_deco_user_packages where user_id = {$post->post_author} and post_id = $post_id" ) ) {
					$wpdb->insert(
						'wp_deco_user_packages',
						array(
							'user_id'   => $post->post_author,
							'type'      => 'post',
							'post_id'   => $post_id,
							'timestamp' => current_time( 'mysql' ),
						)
					);
				}
			} else {
				$wpdb->delete(
					'wp_deco_user_packages',
					array(
						'post_id' => $post_id,
					)
				);
			}
		}
	}
}
