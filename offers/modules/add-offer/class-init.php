<?php

namespace Deco\Bundles\Offers\Modules\Add_Offer;

use Deco\Helpers;

class Init {

	static $bundle_uri;
	static $bundle_path;
	static $table_offers_agents;
	static $prefix = 'deco_offer_';
	static $yoast_prefix = '_yoast_wpseo_primary_';
	static $post_type = 'offers';
	static $form_error = 'bundles/add-offer/form-error.twig';
	static $form_success = 'bundles/add-offer/form-success.twig';
	static $template_path = 'bundles/add-offer/form-%type%.twig';
	static $template_offer_type_path = 'bundles/add-offer/offer-type.twig';

	static $address = array(
		'address_region_name'          => '',
		'address_locality_name'        => '',
		'address_street_name'          => '',
		'address_region_id'            => '',
		'address_district_region_id'   => '',
		'address_locality_id'          => '',
		'address_district_locality_id' => '',
		'address_microdistrict_id'     => '',
		'address_street_id'            => '',
		'address_house_number'         => '',
		'address_metro'                => '',
		'address_metro_ids'            => '',
		'address_latitude'             => '',
		'address_longitude'            => '',
	);

	static $apartment_defaults = array(
		'deal_type'             => '',
		'offer_category'        => '',
		'offer_type'            => '',
		'gallery'               => array(),
		'adFormDescr'           => '',
		'price'                 => '',
		'square'                => '',
		'living_area'           => '',
		'kitchen_area'          => '',
		'count_rooms'           => '',
		'count_bathrooms'       => '',
		'floor'                 => '',
		'floors'                => '',
		'repair'                => '',
		'code'                  => '',
		'heating'               => '',
		'wall_type'             => '',
		'year_built'            => '',
		'ceiling_height'        => '',
		'ifs'                   => '',
		'parking'               => '',
		'elite_real_estate'     => '',
		'post_id'               => '',
		'property_classes'      => '',
		'type_payment'          => '',
		'type_proposition'      => '',
		'comfort'               => '',
		'without_furniture'     => '',
		'deco_offer_car_places' => '',
	);

	static $house_defaults = array(
		'deal_type'             => '',
		'offer_category'        => '',
		'offer_type'            => '',
		'gallery'               => array(),
		'adFormDescr'           => '',
		'price'                 => '',
		'square'                => '',
		'living_area'           => '',
		'kitchen_area'          => '',
		'land_area'             => '',
		'count_rooms'           => '',
		'count_bathrooms'       => '',
		//'floor'              => '',
		'floors'                => '',
		//		'repair'                => '',
		'sewerage'              => '',
		'window_type'           => '',
		'code'                  => '',
		'heating'               => '',
		'wall_type'             => '',
		'year_built'            => '',
		'ceiling_height'        => '',
		'distance'              => '',
		'ifs'                   => '',
		'plumbing'              => '',
		'parking'               => '',
		'elite_real_estate'     => '',
		'post_id'               => '',
		'property_classes'      => '',
		'type_payment'          => '',
		'type_proposition'      => '',
		'comfort'               => '',
		'deco_offer_car_places' => '',
	);

	static $commercial_real_estate_defaults = array(
		'deal_type'         => '',
		'offer_category'    => '',
		'offer_type'        => '',
		'gallery'           => array(),
		'adFormDescr'       => '',
		'price'             => '',
		'square'            => '',
		'land_area'         => '',
		'count_rooms'       => '',
		'lodge_class'       => '',
		'floor'             => '',
		'floors'            => '',
		'building_type'     => '',
		'repair'            => '',
		'sewerage'          => '',
		'window_type'       => '',
		'code'              => '',
		'heating'           => '',
		'wall_type'         => '',
		'year_built'        => '',
		'distance'          => '',
		'ifs'               => '',
		/*'kitchen'            => '',*/
		'parking'           => '',
		'elite_real_estate' => '',
		'post_id'           => '',
	);

	static $land_plot_defaults = array(
		'deal_type'        => '',
		'offer_category'   => '',
		'offer_type'       => '',
		'gallery'          => array(),
		'adFormDescr'      => '',
		'price'            => '',
		'square'           => '',
		//		'land_plot_area'     => '',
		'cadastral_number' => '',
		'intended_purpose' => '',
		'location'         => '',
		'distance'         => '',
		'ifs'              => '',
		'electricity'      => '',
		'gasmain'          => '',
		'plumbing'         => '',
		'sewerage'         => '',
		'post_id'          => '',
		'type_payment'     => '',
		'type_proposition' => '',
	);

	static $garage_parking_defaults = array(
		'deal_type'             => '',
		'offer_category'        => '',
		'offer_type'            => '',
		'gallery'               => array(),
		'adFormDescr'           => '',
		'price'                 => '',
		'square'                => '',
		'ceiling_height'        => '',
		'year_built'            => '',
		'ifs'                   => '',
		'deco_offer_car_places' => '',
		'electricity'           => '',
		'post_id'               => '',
	);

	public static function init() {
		global $wpdb;

		self::$bundle_uri  = str_replace( ABSPATH, site_url() . '/', dirname( __FILE__ ) ) . '/';
		self::$bundle_path = dirname( __FILE__ ) . '/';

		self::$table_offers_agents = $wpdb->base_prefix . 'offers_agents';

		add_action( 'init', __CLASS__ . '::init_actions' );


		self::$apartment_defaults              = array_merge( self::$apartment_defaults, self::$address );
		self::$house_defaults                  = array_merge( self::$house_defaults, self::$address );
		self::$commercial_real_estate_defaults = array_merge( self::$commercial_real_estate_defaults, self::$address );
		self::$land_plot_defaults              = array_merge( self::$land_plot_defaults, self::$address );
		self::$garage_parking_defaults         = array_merge( self::$garage_parking_defaults, self::$address );

	}

	public static function init_actions() {


		add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_assets' );

		add_action( 'admin_menu', __CLASS__ . '::notification_bubble_in_menu' );
		add_action( 'trashed_post', __CLASS__ . '::trashed_offer' );

		add_filter( 'media_upload_tabs', __CLASS__ . '::profile_image_tabs', 99 );
		add_action( 'pre_get_posts', __CLASS__ . '::users_own_attachments' );

		add_action( 'wp_ajax_deco_change_offer_type', __CLASS__ . '::ajax_change_offer_type' );
		add_action( 'wp_ajax_deco_add_offer_show_metro', __CLASS__ . '::show_metro', 10 );
		add_action( 'wp_ajax_nopriv_deco_add_offer_show_metro', __CLASS__ . '::show_metro', 10 );

		add_action( 'wp_ajax_deco_add_offer', __CLASS__ . '::ajax_add_offer' );
		add_action( 'deco_add_offer', __CLASS__ . '::add_offer', 10, 1 );

		add_action( 'wp_ajax_deco_offer_gallery_upload', __CLASS__ . '::ajax_handle_offer_gallery' );

		add_action( 'wp_ajax_deco_add_offer_gallery_upload', __CLASS__ . '::add_offer_gallery_upload' );
		add_action( 'wp_ajax_deco_add_offer_remove_gallery_item', __CLASS__ . '::add_offer_gallery_image_remove' );
	}

	public static function enqueue_assets() {
		$is_edit_page_query_var = get_query_var( 'is_edit_offer_page' );
		$is_edit_offer_page     = isset( $is_edit_page_query_var ) && $is_edit_page_query_var == 1 ? true : false;

		if ( ( ! is_admin() && get_query_var( 'is_add_offer_page' ) ) || $is_edit_offer_page ) {
			wp_enqueue_media();
			wp_enqueue_style( 'add-offer-style',
				self::$bundle_uri . 'assets/css/style.css',
				'',
				filemtime( self::$bundle_path . 'assets/css/style.css' )
			);

			wp_enqueue_script( 'add-offer-script',
				self::$bundle_uri . 'assets/js/script.js',
				array( 'jquery' ),
				filemtime( self::$bundle_path . 'assets/js/script.js' ),
				true
			);

			wp_localize_script(
				'add-offer-script',
				'offer_params',
				array(
					'ajax_url'       => admin_url( 'admin-ajax.php' ),
					'_wpnonce'       => wp_create_nonce( 'deco-add-offer' ),
					'wp_media_title' => __( 'Add offer gallery', 'realia' ),
					'messages'       => array(
						'imageLargeError'      => __( 'Image is too large', 'realia' ),
						'siteUrlNotValid'      => __( 'Please specify a valid site url', 'realia' ),
						'errorIncorrectFormat' => apply_filters( 'deco_i18n_front', 'This files have incorrect format: %filenames%' ),
						'errorEmptyFiles'      => apply_filters( 'deco_i18n_front', 'This files are empty: %filenames%' ),
						'errorTooLargeFiles'   => apply_filters( 'deco_i18n_front', 'This files sizes are too large : %filenames%' ),
						'errorPopupTitle'      => apply_filters( 'deco_i18n_front', 'Errors during files upload' ),
					)
				)
			);

			wp_localize_script(
				'add-offer-script',
				'offer_gallery_params',
				array(
					//					'runtimes'            => 'html5,silverlight,flash,html4',
					'runtimes'            => 'flash,html5,html4',
					'browse_button'       => 'upload-btn-inp-offer',
					'unique_names'        => true,
					'resize'              => array(
						'enabled' => false,
						/*'width'   => 100, // enter your width here
						'height'  => 100, // enter your width here
						'quality' => 100,*/
					),
					'file_data_name'      => 'offer-data-upload',
					'multiple_queues'     => false,
					'multi_selection'     => true,
					//					'max_file_size'       => wp_max_upload_size() . 'b',
					'max_file_size'       => '3mb',
					'url'                 => admin_url( 'admin-ajax.php' ),
					'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
					'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
					'filters'             => array(
						'max_file_size'      => '3mb',
						array(
							'title'      => 'Image files',
							'extensions' => 'jpg,JPG,jpeg,JPEG,gif,png,PNG'
						),
						'prevent_duplicates' => true
					),
					'multipart'           => true,
					'urlstream_upload'    => true,
					// additional post data to send to our ajax hook
					'multipart_params'    => array(
						'_ajax_nonce' => wp_create_nonce( 'offer-gallery-upload' ),
						'action'      => 'deco_offer_gallery_upload',            // the ajax action name
					),
				)
			);
		}

	}

	public static function styles() {
		wp_register_style( 'offers-styles', self::$bundle_uri . 'assets/css/style.css' );
		wp_enqueue_style( 'offers-styles' );
	}

	public static function trashed_offer( $post_id ) {
		global $wpdb;

		return;

		$message_not_send = get_post_meta( $post_id, 'message_not_send', true );

		if ( $message_not_send ) {
			return;
		}


		// Отключаем уведомление о модерации объявления, необходимо когда идет массовая синхронизация объявлений
		if ( apply_filters( 'deco_offers_moderated_notification_disable', false ) ) {
			return;
		}

		// Отключаем уведомление если POST запрос от пользователя в админке и объявление из xml
//			if ( $_SERVER['REQUEST_METHOD'] == 'POST' && strpos( $_SERVER['HTTP_REFERER'], '/wp-admin/' ) !== false ) {

		$is_offer_from_xml = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}deco_xml_data_offers WHERE post_id = %d", $post_id ) );
		if ( $is_offer_from_xml ) {
			return;
		}
//			}


		$trashed_post = get_post( $post_id );
		if ( self::$post_type != $trashed_post->post_type ) {
			return;
		}

		$user_id = get_post_meta( $post_id, 'deco_offer_added_user_id', true );

		if ( empty( $user_id ) ) {
			return;
		}

		if ( ! $user = get_user_by( 'id', $user_id ) ) {
			return;
		}

		$user_data = $user->data;

		if ( ! $user_data->user_email || ! email_exists( $user_data->user_email ) ) {
			return;
		}

		$message = sprintf( 'Здравствуйте, %s!', $user_data->display_name ) . "\r\n\r\n";
		$message .= sprintf( 'К сожалению, ваше объявление <strong>"%s"</strong> от %s было удалено.', qtranxf_use( 'ru', $trashed_post->post_title ), date( 'd-m-Y', strtotime( $trashed_post->post_date ) ) ) . "\r\n\r\n";
		$message .= 'Чтобы добавить новое, перейдите по ссылке:' . "\r\n\r\n";
		$message .= site_url( "/add-offer" ) . "\r\n";

		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$title = sprintf( 'Уведомление №%s от %s', $post_id, 'Realia.ua' );

		wp_mail( $user_data->user_email, wp_specialchars_decode( $title ), $message );
	}

	public static function notification_bubble_in_menu() {
		global $menu;
		$pending_items = self::get_pending_items();
		foreach ( $menu as $key => $item ) {
			if ( $item[2] == 'edit.php?post_type=' . self::$post_type ) {
				$menu[ $key ][0] .= $pending_items ? " <span class='update-plugins count-1' title='title'><span class='update-count'>$pending_items</span></span>" : '';
			}
		}
	}

	public static function get_pending_items() {
		global $wpdb;
		$pending_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s AND post_status = 'pending'", self::$post_type ) );

		return (int) $pending_count;
	}

	public static function profile_image_tabs( $tabs ) {
		if ( ! is_admin() ) {
			unset( $tabs['gallery'] );
			unset( $tabs['type'] );
		}

		return ( $tabs );
	}

	public static function users_own_attachments( $wp_query ) {

		global $current_user, $pagenow;

		if ( ! is_a( $current_user, 'WP_User' ) ) {
			return $wp_query;
		}

		if ( ! in_array( $pagenow, array( 'upload.php', 'admin-ajax.php' ) ) ) {
			return $wp_query;
		}
		$s = $wp_query->query['post_type'];
		if ( ! current_user_can( 'manage_options' ) && ( $wp_query->query['post_type'] == 'attachment' ) ) {
			$wp_query->set( 'author', $current_user->ID );
		}

		return $wp_query;
	}

	public static function ajax_add_offer() {
		global $wpdb;
		$context['textdomain'] = $textdomain = 'realia';

		$result['status'] = 204;

		$user_id = get_current_user_id();

//		$residue = apply_filters( 'deco_user_get_posts_residue', $user_id );

		$active_subscription = apply_filters( 'deco_get_active_subscription', $user_id );

		$free_residue = apply_filters( 'deco_user_get_posts_residue_free', $user_id );

		$is_agent = apply_filters( 'deco_profile_is_user_role', 'agent' );

		$is_agency = apply_filters( 'deco_profile_is_user_role', 'agency' );

		$max_publish_offers  = apply_filters( 'deco_user_get_posts_max', $user_id );
		$left_publish_offers = apply_filters( 'deco_user_get_posts_use', $user_id );
		$show_error          = false;

		/** Если агент/агенство
		 * выполняем проверку подписки, пакетов
		 */

		if ( $_POST['post_status'] !== 'archive' ) {

			if ( $is_agency || $is_agent ) {


				if ( $left_publish_offers >= $max_publish_offers ) {

					if ( ! $active_subscription ) {
						$result['message'] = __( 'You\'ve reached a limit to accommodate more, select the appropriate plan, or pay <a href="%s">additional ads</a>', 'realia' );

						$result['message'] = sprintf(
							$result['message'],
							site_url( 'profile/paid-services' )
						);

						$result['html'] = \Timber::compile( self::$form_error, $context );

						echo json_encode( $result );

						die();

					} else {
						$show_error = true;
					}

				}


			} else {
				/**
				 * Иначе это пользователь обычный
				 * у которого доступно только покупка пакетов
				 */

				if ( $left_publish_offers >= $max_publish_offers ) {
					$show_error = true;
				}

			}

		}

		if ( $show_error ) {
			$result['message'] = __( 'You have no available ad', 'realia' );

			$result['html'] = \Timber::compile( self::$form_error, $context );

			echo json_encode( $result );

			die();
		}


		if ( ! $_POST || ! isset( $_POST['offer_category'] ) || empty( $_POST['offer_category'] ) ) {
			$result['message'] = __( 'Empty POST', $textdomain );
			$result['html']    = \Timber::compile( self::$form_error, $context );
			echo json_encode( $result );
			die();
		}

		if ( ! check_ajax_referer( 'deco-add-offer', '_ajax_offer_nonce', false ) ) {
			$result['message'] = __( 'Nonce fail!', $textdomain );
			$result['html']    = \Timber::compile( self::$form_error, $context );
			echo json_encode( $result );
			die();
		}

//		$user_id = get_current_user_id();

		$res     = self::add_offer( $user_id );
		$post_id = $res['post_id'];

		if ( $post_id ) {

			$current_user = get_userdata( $user_id );
			if ( ! ( $current_user == false ) ) {
				$notification_args = array(
					'user'    => $current_user,
					'post_id' => $post_id
				);
				do_action( 'deco_offer_create_notification', $notification_args );
			}

			$result['post_id'] = $post_id;
			$result['message'] = __( 'Congrats', 'realia' );
			$result['html']    = \Timber::compile( self::$form_success, $context );
			$result['status']  = 200;
		}

		echo json_encode( $result );
		die();
	}

	public static function add_offer( $user_id = '' ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$result = array();

		$offer_category = get_term_by( 'id', $_POST['offer_category'], 'offer_category' );

		if ( $offer_category->slug == 'kvartir' ) {
			$defaults = self::$apartment_defaults;
		} elseif ( $offer_category->slug == 'domov' ) {
			$defaults = self::$house_defaults;
		} elseif ( $offer_category->slug == 'kommercheskoj-nedvizhimosti' ) {
			$defaults = self::$commercial_real_estate_defaults;
		} elseif ( $offer_category->slug == 'zemelnyh-uchastkov' ) {
			$defaults = self::$land_plot_defaults;
		} elseif ( $offer_category->slug == 'garazhej-i-parkingov' ) {
			$defaults = self::$garage_parking_defaults;
		}

		$data = wp_parse_args( array_intersect_key( $_POST, array_flip( array_keys( $defaults ) ) ), $defaults );

		$option_qtranslate_term_name = get_option( 'qtranslate_term_name' );
		$type_term                   = get_term_by( 'id', $data['deal_type'], 'deal_type' );
		$term                        = get_term_by( 'id', $data['offer_type'], 'offer_type' );

		$alternative_title = get_term_meta( $term->term_id, 'deco_offer_type_alternative_title_object', true );
		$offer_cat_name_ru = qtranxf_use( 'ru', $alternative_title ) ? qtranxf_use( 'ru', $alternative_title ) : mb_strtolower( $option_qtranslate_term_name[ $term->name ]['ru'] );
		$offer_cat_name_ua = qtranxf_use( 'ua', $alternative_title ) ? qtranxf_use( 'ua', $alternative_title ) : mb_strtolower( $option_qtranslate_term_name[ $term->name ]['ua'] );

		$title = '[:ru]' . $option_qtranslate_term_name[ $type_term->name ]['ru'] . ' ' . $offer_cat_name_ru . '[:ua]' . $option_qtranslate_term_name[ $type_term->name ]['ua'] . ' ' . $offer_cat_name_ua . '[:]';

		$post_id = intval( $data['post_id'] );

		$post_status = 'publish';

		$data['price'] = str_replace( ',', '.', trim( $data['price'] ) );

		if ( isset( $data['price'] ) && ! empty( $data['price'] ) ) {
			$is_price = floatval( $data['price'] );
			if ( is_int( $is_price ) || is_float( $is_price ) ) {
				$post_status = 'publish';
			}
		}

		if ( $_POST['post_status'] === 'archive' ) {
			$post_status = 'archive';
		}

		$post_data = array(
			'ID'           => $post_id,
			'post_title'   => $title,
			'post_content' => $data['adFormDescr'],
			'post_status'  => $post_status,
			'post_type'    => self::$post_type
		);

		$is_edit = $post_id;

		//$post_id = wp_insert_post( wp_slash( $post_data ) );
		$post_id = wp_insert_post( $post_data );

		if ( isset( $data['gallery'] ) && is_array( $data['gallery'] ) ) {

			$count = 1;

			$slides        = $map_marker_slides = '';
			$photo_list    = array();
			$single_slides = array();
			foreach ( $data['gallery'] as $item ) {
				if ( $count <= 5 ) {
					$slide            = $item['offer_blog'] ? $item['offer_blog'] : wp_get_attachment_image_src( $item['id'], 'offer_blog_loop_tease' )[0];
					$map_thumb        = $item['tooltip_tease'] ? $item['tooltip_tease'] : wp_get_attachment_image_src( $item['id'], 'tooltip_tease_on_map' )[0];
					$map_marker_thumb = $item['offer_map_marker'] ? $item['offer_map_marker'] : wp_get_attachment_image_src( $item['id'], 'offer_map_marker_tease' )[0];
					if ( $count == 1 ) {
						update_post_meta( $post_id, 'deco_offer_thumbnail', $slide );
						update_post_meta( $post_id, 'deco_offer_map_thumb', $map_thumb );
					}
					$slides .= '<span class="pu-img-src" data-img-src="' . $slide . '"></span>';
				}

				$photo_list[ $item['id'] ] = $item['src'];
				//$photo_list[(int)$item['id']] = $item['src'];

				$single_slide = $item['offer_single_tease'] ? $item['offer_single_tease'] : wp_get_attachment_image_src( $item['id'], 'offer_single_tease' )[0];
				//$single_slides .= '<img src="' . $single_slide . '" data-full="' . $item->src . '">';

				$single_slides[] = array(
					'src'  => $single_slide,
					'full' => $item['src'],
				);

				$map_marker_slides .= '<div class="flat-modal-slider-item">
            <div class="flat-modal-previev js_modal-slide-item" data-modal-src="' . $single_slide . '" style="background-image: url(' . $map_marker_thumb . ');"></div>
        </div>';

				$count ++;
			}

			if ( ! empty( $photo_list ) ) {
				update_post_meta( $post_id, 'deco_offer_photo_list', $photo_list );
			}

			if ( ! empty( $slides ) ) {
				update_post_meta( $post_id, 'deco_offer_home_slides', $slides );
			}

			if ( ! empty( $single_slides ) ) {
				update_post_meta( $post_id, 'deco_offer_single_slides', $single_slides );
			}

			if ( ! empty( $map_marker_slides ) ) {
				update_post_meta( $post_id, 'deco_offer_map_marker_slides', $map_marker_slides );
			}

		}

		// Other
		if ( $data['deal_type'] ) {
			wp_set_post_terms( $post_id, array( $data['deal_type'] ), 'deal_type' );
			update_post_meta( $post_id, self::$yoast_prefix . 'deal_type', $data['deal_type'] );
		}

		if ( $data['offer_category'] ) {
			wp_set_post_terms( $post_id, array( $data['offer_category'] ), 'offer_category' );
			update_post_meta( $post_id, self::$yoast_prefix . 'offer_category', $data['offer_category'] );
		}

		if ( $data['offer_type'] ) {
			wp_set_post_terms( $post_id, array( $data['offer_type'] ), 'offer_type' );
			update_post_meta( $post_id, self::$yoast_prefix . 'offer_type', $data['offer_type'] );
		}

		if ( $data['count_rooms'] ) {
			wp_set_post_terms( $post_id, array( $data['count_rooms'] ), 'count_rooms' );
			update_post_meta( $post_id, self::$yoast_prefix . 'count_rooms', $data['count_rooms'] );
		}

		if ( $data['count_bathrooms'] ) {
			wp_set_post_terms( $post_id, array( $data['count_bathrooms'] ), 'count_bathrooms' );
			update_post_meta( $post_id, self::$yoast_prefix . 'count_bathrooms', $data['count_bathrooms'] );
		}

		if ( $data['repair'] ) {
			wp_set_post_terms( $post_id, array( $data['repair'] ), 'repair' );
			update_post_meta( $post_id, self::$yoast_prefix . 'repair', $data['repair'] );
		}

		if ( $data['code'] ) {
			wp_set_post_terms( $post_id, array( $data['code'] ), 'code' );
			update_post_meta( $post_id, self::$yoast_prefix . 'code', $data['code'] );
		}

		if ( $data['heating'] ) {
			wp_set_post_terms( $post_id, array( $data['heating'] ), 'heating' );
			update_post_meta( $post_id, self::$yoast_prefix . 'heating', $data['heating'] );
		}

		if ( $data['wall_type'] ) {
			wp_set_post_terms( $post_id, array( $data['wall_type'] ), 'wall_type' );
			update_post_meta( $post_id, self::$yoast_prefix . 'wall_type', $data['wall_type'] );
		}

		if ( $data['ifs'] ) {
			wp_set_post_terms( $post_id, $data['ifs'], 'ifs' );
			$ifs = explode( ',', $data['ifs'] );
			update_post_meta( $post_id, self::$yoast_prefix . 'ifs', $ifs[0] );
		}

		if ( $data['parking'] ) {
			wp_set_post_terms( $post_id, array( $data['parking'] ), 'parking' );
			update_post_meta( $post_id, self::$yoast_prefix . 'parking', $data['parking'] );
		}

		if ( $data['sewerage'] ) {
			wp_set_post_terms( $post_id, array( $data['sewerage'] ), 'sewerage' );
			update_post_meta( $post_id, self::$yoast_prefix . 'sewerage', $data['sewerage'] );
		}

		if ( $data['window_type'] ) {
			wp_set_post_terms( $post_id, array( $data['window_type'] ), 'window_type' );
			update_post_meta( $post_id, self::$yoast_prefix . 'window_type', $data['window_type'] );
		}

		if ( $data['plumbing'] ) {
			wp_set_post_terms( $post_id, array( $data['plumbing'] ), 'plumbing' );
			update_post_meta( $post_id, self::$yoast_prefix . 'plumbing', $data['plumbing'] );
		}

		if ( $data['lodge_class'] ) {
			wp_set_post_terms( $post_id, array( $data['lodge_class'] ), 'lodge_class' );
			update_post_meta( $post_id, self::$yoast_prefix . 'lodge_class', $data['lodge_class'] );
		}

		if ( $data['building_type'] ) {
			wp_set_post_terms( $post_id, array( $data['building_type'] ), 'building_type' );
			update_post_meta( $post_id, self::$yoast_prefix . 'building_type', $data['building_type'] );
		}

		/*if ( $data['kitchen'] ) {
			wp_set_post_terms( $post_id, array( $data['kitchen'] ), 'kitchen' );
			update_post_meta( $post_id, self::$yoast_prefix . 'kitchen', $data['kitchen'] );
		}*/

		if ( $data['intended_purpose'] ) {
			wp_set_post_terms( $post_id, array( $data['intended_purpose'] ), 'intended_purpose' );
			update_post_meta( $post_id, self::$yoast_prefix . 'intended_purpose', $data['intended_purpose'] );
		}

		//		if ( $data['location'] ) {
		//			wp_set_post_terms( $post_id, array( $data['location'] ), 'location' );
		//			update_post_meta( $post_id, self::$yoast_prefix . 'location', $data['location'] );
		//		}
		if ( $data['location'] ) {
			wp_set_post_terms( $post_id, $data['location'], 'location' );
			$location = explode( ',', $data['location'] );
			update_post_meta( $post_id, self::$yoast_prefix . 'location', $location[0] );
		}

		if ( $data['electricity'] ) {
			wp_set_post_terms( $post_id, array( $data['electricity'] ), 'electricity' );
			update_post_meta( $post_id, self::$yoast_prefix . 'electricity', $data['electricity'] );
		}

		if ( $data['gasmain'] ) {
			wp_set_post_terms( $post_id, array( $data['gasmain'] ), 'gasmain' );
			update_post_meta( $post_id, self::$yoast_prefix . 'gasmain', $data['gasmain'] );
		}

		if ( $data['property_classes'] ) {
			wp_set_post_terms( $post_id, array( $data['property_classes'] ), 'property_classes' );
			update_post_meta( $post_id, self::$yoast_prefix . 'property_classes', $data['property_classes'] );
		}

		if ( $data['type_payment'] ) {
			wp_set_post_terms( $post_id, array( $data['type_payment'] ), 'type_payment' );
			update_post_meta( $post_id, self::$yoast_prefix . 'type_payment', $data['type_payment'] );
		}

		if ( $data['type_proposition'] ) {
			wp_set_post_terms( $post_id, array( $data['type_proposition'] ), 'type_proposition' );
			update_post_meta( $post_id, self::$yoast_prefix . 'type_proposition', $data['type_proposition'] );
		}

		if ( $data['comfort'] ) {
			wp_set_post_terms( $post_id, array( $data['comfort'] ), 'comfort' );
			update_post_meta( $post_id, self::$yoast_prefix . 'comfort', $data['comfort'] );
		}
		/* End Taxonomies */


		/** Address */
		$address_need_check = 0;
		update_post_meta( $post_id, 'address_house_number', $data['address_house_number'] );
		if ( isset( $data['address_street_id'] ) && ! empty( $data['address_street_id'] ) ) {
			update_post_meta( $post_id, 'address_region_id', $data['address_region_id'] );
			update_post_meta( $post_id, 'address_district_region_id', $data['address_district_region_id'] );
			update_post_meta( $post_id, 'address_locality_id', $data['address_locality_id'] );
			update_post_meta( $post_id, 'address_district_locality_id', $data['address_district_locality_id'] );
			update_post_meta( $post_id, 'address_microdistrict_id', $data['address_microdistrict_id'] );
			update_post_meta( $post_id, 'address_street_id', $data['address_street_id'] );

			if ( ! empty( $data['address_latitude'] ) && ! empty( $data['address_longitude'] ) ) {
				update_post_meta( $post_id, 'address_latitude', $data['address_latitude'] );
				update_post_meta( $post_id, 'address_longitude', $data['address_longitude'] );
			} else {
				$street           = apply_filters( 'deco_address_get_data_by_id', 'wp_address_street', $data['address_street_id'] );
				$address_line_arr = array();

				$address_line_arr[] = $street->street_region_title_ru;
				$address_line_arr[] = $street->street_district_region_title_ru;
				$address_line_arr[] = $street->street_locality_title_ru;
				$address_line_arr[] = $street->street_district_locality_title_ru;
				$address_line_arr[] = $street->street_title_ru;
				$address_line_arr[] = $data['address_house_number'];

				$address_line_arr = array_filter( $address_line_arr );
				$address_line_arr = array_unique( $address_line_arr );

				$address_line = implode( ', ', $address_line_arr );

				$coords = apply_filters( 'deco_address_get_google_coords_by_address_line', array(
					'address_line' => $address_line
				) );

				update_post_meta( $post_id, 'address_latitude', $coords['lat'] );
				update_post_meta( $post_id, 'address_longitude', $coords['lng'] );
			}
		} else {
			$address_need_check = 1;
			update_post_meta( $post_id, 'address_need_check_region_name', $data['address_region_name'] );
			update_post_meta( $post_id, 'address_need_check_locality_name', $data['address_locality_name'] );
			update_post_meta( $post_id, 'address_need_check_street_name', $data['address_street_name'] );
			update_post_meta( $post_id, 'address_need_check', 1 );
		}

		update_post_meta( $post_id, 'offer_added_by_front_page', 1 );


		$taxonomy_locality = array(
			2    => 'metro',
			9330 => 'metro_kharkiv',
			1412 => 'metro_dnepr',
			1421 => 'metro_krog',
		);

		$metro_taxonomy = '';
		if ( isset( $taxonomy_locality[ $data['address_locality_id'] ] ) ) {
			$metro_taxonomy = $taxonomy_locality[ $data['address_locality_id'] ];
		}


		if ( ! empty( $data['address_metro_ids'] ) && $metro_taxonomy ) {
			$metro_terms = explode( ',', $data['address_metro_ids'] );
			if ( count( $metro_terms ) > 0 ) {
				wp_set_post_terms( $post_id, $metro_terms, $metro_taxonomy );
				update_post_meta( $post_id, self::$yoast_prefix . $metro_taxonomy, $metro_terms[0] );
				update_post_meta( $post_id, 'address_metro_ids', $data['address_metro_ids'] );
			}
		} else if ( ! empty( $data['address_metro'] ) && $metro_taxonomy ) {
			$metro_terms = array();
			update_post_meta( $post_id, 'address_metro', $data['address_metro'] );
			$metro_list = explode( ',', $data['address_metro'] );
			foreach ( $metro_list as $metro_list_item ) {
				if ( isset( $data['address_region_id'] ) && $data['address_region_id'] == 1 ) {
					$metro_term_item = get_term_by( 'name', $metro_list_item, $metro_taxonomy );
					$metro_terms[]   = $metro_term_item->term_id;
				}
			}

			if ( count( $metro_terms ) > 0 && $metro_taxonomy ) {
				wp_set_post_terms( $post_id, $metro_terms, $metro_taxonomy );
				update_post_meta( $post_id, self::$yoast_prefix . $metro_taxonomy, $metro_terms[0] );
			}

		}

		// @todo Не удалять,это для дебага добавления
		//		$res['$coords']      = $coords;
		//		$res['$metro_terms'] = $metro_terms;
		//		$res['$data']        = $data;
		//		$res['$street']      = $street;
		//		echo json_encode( $res );
		//		die();

		/** END Address */

		/* Metas */

		if ( $data['price'] ) {
			update_post_meta( $post_id, self::$prefix . 'price', $data['price'] );
			if ( $address_need_check == 0 && $post_status == 'pending' ) {
				$post_status     = 'publish';
				$new_post_status = array(
					'ID'          => $post_id,
					'post_status' => $post_status
				);
				wp_update_post( $new_post_status );
			}
		}

		if ( $data['square'] ) {
			update_post_meta( $post_id, self::$prefix . 'square', $data['square'] );
		}

		if ( $data['without_furniture'] ) {
			update_post_meta( $post_id, self::$prefix . 'without_furniture', $data['without_furniture'] );
		} else {
			update_post_meta( $post_id, self::$prefix . 'without_furniture', false );
		}

		if ( $data['living_area'] ) {
			update_post_meta( $post_id, self::$prefix . 'living_area', $data['living_area'] );
		}

		if ( $data['kitchen_area'] ) {
			update_post_meta( $post_id, self::$prefix . 'kitchen_area', $data['kitchen_area'] );
		}

		if ( $data['land_area'] ) {
			update_post_meta( $post_id, self::$prefix . 'land_area', $data['land_area'] );
		}

		if ( $data['elite_real_estate'] ) {
			update_post_meta( $post_id, self::$prefix . 'elite_real_estate', $data['elite_real_estate'] );
		}

		//		if ( $data['land_plot_area'] ) {
		//			update_post_meta( $post_id, self::$prefix . 'land_plot_area', $data['land_plot_area'] );
		//		}

		if ( $data['floor'] ) {
			update_post_meta( $post_id, self::$prefix . 'floor', $data['floor'] );
		}

		if ( $data['deco_offer_car_places'] ) {
			update_post_meta( $post_id, 'deco_offer_car_places', $data['deco_offer_car_places'] );
		}

		if ( $data['floors'] ) {
			update_post_meta( $post_id, self::$prefix . 'floors', $data['floors'] );
		}

		if ( $data['year_built'] ) {
			update_post_meta( $post_id, self::$prefix . 'year_built', $data['year_built'] );
		}

		if ( $data['ceiling_height'] ) {
			update_post_meta( $post_id, self::$prefix . 'ceiling_height', $data['ceiling_height'] );
		}

		if ( $data['distance'] ) {
			update_post_meta( $post_id, self::$prefix . 'distance', $data['distance'] );
		}

		if ( $data['cadastral_number'] ) {
			update_post_meta( $post_id, self::$prefix . 'cadastral_number', $data['cadastral_number'] );
		}
		/* End Metas */

		if ( $user_id ) {
			$blog_id        = get_current_blog_id();
			$user_ids       = array();
			$deco_users_ids = array();

			update_post_meta( $post_id, 'deco_offer_added_user_id', $user_id );

			// Remove all records by post_id and blog_id (for multisite)
			$wpdb->delete( self::$table_offers_agents,
				array(
					'post_id' => $post_id,
					'blog_id' => $blog_id
				)
			);

			$cost = $data['price'];

			$cost = str_replace( ',', '.', $cost );
			$cost = is_float( $cost ) ? floatval( $cost ) : $cost;
			$cost = is_int( $cost ) ? intval( $cost ) : $cost;

			$data = new \stdClass();

			$data->id   = $user_id;
			$data->cost = $cost;

			$user_ids[]     = $data;
			$deco_users_ids = json_encode( $user_ids );

			if ( ! empty( $user_ids ) ) {
				foreach ( $user_ids as $item ) {
					$user_id   = $item->id;
					$agency_id = intval( get_user_meta( $user_id, 'deco_agency_term_id', true ) );
					$wpdb->insert(
						self::$table_offers_agents,
						array(
							'post_id'   => $post_id,
							'user_id'   => $item->id,
							'price'     => $item->cost,
							'blog_id'   => $blog_id,
							'agency_id' => $agency_id,
							'active'    => ( $post_status == 'publish' ) ? 1 : 0
						)
					);
				}
				if ( $deco_users_ids ) {
					update_post_meta( $post_id, 'deco_users_ids', $deco_users_ids );
				}

			}
		}

		$post_status = 'pending';

		$result['post_id']     = $post_id;
		$result['post_status'] = $post_status;


		return $result;
	}

	public static function ajax_change_offer_type() {
		$type           = isset( $_GET['type'] ) ? $_GET['type'] : '';
		$template       = isset( $_GET['template'] ) ? $_GET['template'] : 1;
		$cookie_gallery = empty( $_GET['gallery_from_cookie'] ) ? '' : $_GET['gallery_from_cookie'];

		$result['status'] = 204;

		if ( $type ) {
			$term = get_term_by( 'slug', $type, 'offer_category' );
			if ( $term && ! is_wp_error( $term ) ) {
				$context               = \Timber::get_context();
				$context['textdomain'] = 'realia';

				$context['offer_type'] = get_terms( array(
					'taxonomy'   => 'offer_type',
					'meta_key'   => 'deco_offer_type_cat_id',
					'meta_value' => $term->term_id,
					'hide_empty' => false
				) );

				$context['ifs'] = get_terms( array(
					'taxonomy'   => 'ifs',
					'meta_key'   => 'deco_ifs_cat_' . $term->term_id,
					'meta_value' => $term->term_id,
					'hide_empty' => false
				) );

				if ( in_array( $type, array(
					'kvartir',
					'domov',
					'kommercheskoj-nedvizhimosti',
					'garazhej-i-parkingov'
				) ) ) {
					// Паркинг
					$context['parking'] = get_terms( array( 'taxonomy' => 'parking', 'hide_empty' => false ) );

					$year_now    = date( 'Y' );
					$years_built = array();
					for ( $i = $year_now; $i >= 1960; $i -- ) {
						$years_built[ $i ] = $i;
					}
					$years_built[1959] = 'До 1960';

					$context['year_built'] = $years_built;
				}

				if ( in_array( $type, array( 'kvartir', 'domov', 'kommercheskoj-nedvizhimosti' ) ) ) {
					// Новострой, вторичный рынок
					$context['code'] = get_terms( array( 'taxonomy' => 'code', 'hide_empty' => false ) );

					// Количество комнат
					$context['count_rooms'] = get_terms( array( 'taxonomy' => 'count_rooms', 'hide_empty' => false ) );

					// Ремонт
					$context['repair'] = get_terms( array( 'taxonomy' => 'repair', 'hide_empty' => false ) );

					// Тип стен
					$context['wall_type'] = get_terms( array( 'taxonomy' => 'wall_type', 'hide_empty' => false ) );

					// Отопление
					$context['heating'] = get_terms( array( 'taxonomy' => 'heating', 'hide_empty' => false ) );

					$floors = array();
					for ( $i = 1; $i <= 100; $i ++ ) {
						$floors[ $i ] = $i;
					}
					$context['floors'] = $floors;

					$house_floors = array();
					for ( $j = 1; $j <= 5; $j ++ ) {
						$house_floors[ $j ] = $j;
					}
					$context['house_floors'] = $house_floors;
				}

				if ( in_array( $type, array( 'domov', 'kommercheskoj-nedvizhimosti', 'zemelnyh-uchastkov' ) ) ) {
					// Канализация
					$context['sewerage'] = get_terms( array( 'taxonomy' => 'sewerage', 'hide_empty' => false ) );
				}

				if ( in_array( $type, array( 'kvartir', 'domov' ) ) ) {
					// Количество санузлов
					$context['count_bathrooms'] = get_terms( array(
						'taxonomy'   => 'count_bathrooms',
						'hide_empty' => false
					) );
				}

				if ( in_array( $type, array( 'domov', 'kommercheskoj-nedvizhimosti' ) ) ) {
					// Тип окон
					$context['window_type'] = get_terms( array( 'taxonomy' => 'window_type', 'hide_empty' => false ) );
				}

				if ( in_array( $type, array( 'domov', 'zemelnyh-uchastkov' ) ) ) {
					//Водопровод
					$context['plumbing'] = get_terms( array( 'taxonomy' => 'plumbing', 'hide_empty' => false ) );
				}

				if ( in_array( $type, array( 'zemelnyh-uchastkov', 'garazhej-i-parkingov' ) ) ) {
					//Электричество
					$context['electricity'] = get_terms( array( 'taxonomy' => 'electricity', 'hide_empty' => false ) );
				}

				if ( $type == 'kommercheskoj-nedvizhimosti' ) {
					// Класс помещение
					$context['lodge_class'] = get_terms( array( 'taxonomy' => 'lodge_class', 'hide_empty' => false ) );

					// Тип здания
					$context['building_type'] = get_terms( array(
						'taxonomy'   => 'building_type',
						'hide_empty' => false
					) );

					// Наличие кухни
					//$context['kitchen'] = get_terms( array( 'taxonomy' => 'kitchen', 'hide_empty' => false ) );
				}

				if ( $type == 'zemelnyh-uchastkov' ) {
					// Целевое назначение
					$context['intended_purpose'] = get_terms( array(
						'taxonomy'   => 'intended_purpose',
						'hide_empty' => false
					) );

					// Газопровод
					$context['gasmain'] = get_terms( array( 'taxonomy' => 'gasmain', 'hide_empty' => false ) );

					//Локация
					$context['location'] = get_terms( array( 'taxonomy' => 'location', 'hide_empty' => false ) );
				}

				//				$context['bathroom']    = get_terms( array( 'taxonomy' => 'bathroom', 'hide_empty' => false ) );
				$context['city']   = get_terms( array( 'taxonomy' => 'city', 'hide_empty' => false ) );
				$context['street'] = get_terms( array( 'taxonomy' => 'street', 'hide_empty' => false ) );

				$context['property_classes'] = get_terms( array(
					'taxonomy'   => 'property_classes',
					'hide_empty' => false
				) );
				$context['type_payment']     = get_terms( array(
					'taxonomy'   => 'type_payment',
					'hide_empty' => false
				) );
				$context['type_proposition'] = get_terms( array(
					'taxonomy'   => 'type_proposition',
					'hide_empty' => false
				) );
				$context['comfort']          = get_terms( array( 'taxonomy' => 'comfort', 'hide_empty' => false ) );

				$context['template'] = $template;


				if ( $type == 'zemelnyh-uchastkov' ) {
					$result['offer_type'] = \Timber::compile( 'bundles/add-offer/offer-type-zemelnyh-uchastkov.twig', $context );
				} else {
					$result['offer_type'] = \Timber::compile( self::$template_offer_type_path, $context );
				}

				if ( ! empty( $cookie_gallery ) ) {
					$gallery_ids = explode( ',', $cookie_gallery );
					if ( is_array( $gallery_ids ) ) {
						if ( count( $gallery_ids ) > 10 ) {
							$gallery_ids = array_slice( $gallery_ids, 0, 10 );
						}
						$gallery_files = array();
						foreach ( $gallery_ids as $single_id ) {
							$gallery_item_data = self::get_gallery_item_data( [ 'item_id' => $single_id ] );
							$gallery_files[]   = $gallery_item_data['file'];
						}
					}
					if ( ! empty( $gallery_files ) ) {
						$context['add_offer_gallery_files'] = $gallery_files;
					}
				}

				$result['html']   = \Timber::compile( str_replace( '%type%', $type, self::$template_path ), $context );
				$result['status'] = 200;
			}
		}

		echo json_encode( $result );
		die();
	}

	public static function ajax_handle_offer_gallery() {


		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'mess' => 'Please sing in!' ) );
		}

		if ( count( $_FILES ) == 0 ) {
			wp_send_json_error( array( 'mess' => 'Error upload file, empty data!' ) );
		}

		$status = media_handle_upload( 'offer-data-upload', 0 );

		$html = '';

		if ( ! is_wp_error( $status ) ) {

			$data         = array();
			$data['id']   = $status;
			$images_sizes = array(
				'offer_blog_src'         => 'offer_blog_loop_tease',
				'tooltip_tease_src'      => 'tooltip_tease_on_map',
				'offer_map_marker_src'   => 'offer_map_marker_tease',
				'offer_single_tease_src' => 'offer_single_tease',
				'modal_src'              => 'full',
				'bg'                     => 'add_offer_gallery_tease',
			);

			foreach ( $images_sizes as $key => $value ) {
				$attachment   = wp_get_attachment_image_src( $status, $value );
				$data[ $key ] = $attachment[0];
			}

			$html = \Timber\Timber::compile( 'bundles/add-offer/parts/uploaded-file-form.twig', $data );

		}

		echo $html;
		die();

	}

	public static function add_offer_gallery_upload() {

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'mess' => 'Please sing in!' ) );
		}

		if ( count( $_FILES ) == 0 ) {
			wp_send_json_error( array( 'mess' => 'Error upload file, empty data!' ) );
		}

		$gallery_from_cookie = empty( $_POST['gallery_from_cookie'] ) ? '' : $_POST['gallery_from_cookie'];

		$i    = 0;
		$html = '';

		if ( ! empty( $gallery_from_cookie ) ) {
			$gallery_ids = explode( ',', $gallery_from_cookie );
		} else {
			$gallery_ids = array();
		}

		while ( $i < count( $_FILES ) ) {

			$file         = 'add_offer_gallery_item_' . $i;
			$file_index[] = $file;

			$status = media_handle_upload( $file, 0 );

			$statuses[] = $status;

			if ( ! is_wp_error( $status ) ) {

				$gallery_ids[] = $status;

				$data = self::get_gallery_item_data( [ 'item_id' => $status ] );

				$html .= \Timber\Timber::compile( 'bundles/add-offer/parts/uploaded-file-form.twig', $data );

			}

			$i ++;
		}

		wp_send_json_success( array( 'html' => $html, 'data' => $data, 'gallery' => $gallery_ids ) );

	}

	private static function get_gallery_item_data( $args = array() ) {

		$item_id = empty( $args['item_id'] ) ? '' : $args['item_id'];

		$result = array();

		if ( ! empty( $item_id ) ) {
			$result['file']['id'] = $item_id;

			$image_meta = wp_get_attachment_metadata( $item_id );

			$images_sizes = array(
				'offer_blog_src'         => 'offer_blog_loop_tease',
				'tooltip_tease_src'      => 'tooltip_tease_on_map',
				'offer_map_marker_src'   => 'offer_map_marker_tease',
				'offer_single_tease_src' => 'offer_single_tease',
				'modal_src'              => 'full',
				'bg'                     => 'add_offer_gallery_tease',
			);

			if ( ! empty( $image_meta['file'] ) ) {

				$path_parts = explode( '/', $image_meta['file'] );
				if ( is_array( $path_parts ) ) {

					$full_size_name = array_pop( $path_parts );
					$upload_dir     = wp_upload_dir();
					$path_to_image  = $upload_dir['baseurl'] . '/' . implode( '/', $path_parts ) . '/';

					foreach ( $images_sizes as $key => $value ) {
						if ( $value == 'full' ) {
							$attachment = $path_to_image . $full_size_name;
						} else {
							$attachment = $path_to_image . $image_meta['sizes'][ $value ]['file'];
						}
						$result['file'][ $key ] = $attachment;
					}
				}

			}
		}


		return $result;

	}

	public static function add_offer_gallery_image_remove() {

		$attachment_id = empty( $_POST['item_id'] ) ? '' : $_POST['item_id'];

		if ( ! empty( $attachment_id ) ) {

			$attach_path = get_attached_file( (int) $attachment_id );

			if ( $attach_path !== false ) {
				$attach_delete = wp_delete_attachment( $attachment_id );
				if ( $attach_delete !== false ) {
					wp_send_json_success( array( 'msg' => 'attachment ' . $attachment_id . ' deleted' ) );
				}
			}

		}

		wp_send_json_error( array( 'msg' => 'Empty attachment id' ) );

	}

	public static function show_metro() {
		$locality = $_REQUEST['locality'];

		$result['metro'] = apply_filters( 'deco_get_metro_by_locality', array(
			'locality' => $locality,
			'template' => 'bundles/add-offer/metro.twig',
		) );
		wp_send_json_success( $result );

	}

}

