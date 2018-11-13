<?php

namespace Deco\Bundles\Offers\Modules\Offers_Xml;

class Init {

	public static function init() {
//		Includes\Sync_Xml::init();

		add_action( 'deco_offers_xml_import_to_table', __CLASS__ . '::sync_to_table', 10, 2 );
		add_action( 'deco_offers_xml_import', __CLASS__ . '::test_sync', 10, 2 );
//		add_action( 'deco_offers_xml_import2', __CLASS__ . '::start_cron_sync', 10, 2 );
//		add_action( 'deco_offers_xml_sync_offers', __CLASS__ . '::offers_sync_pagination', 10, 2 );
//		add_action( 'deco_cron_offers_start_sync', __CLASS__ . '::start_cron_sync' );

		add_action( 'deco_offers_xml_sync_pagination_thread', __CLASS__ . '::sync_pagination_thread' );
		add_action( 'deco_offers_xml_sync_threads', __CLASS__ . '::sync_start_threads' );

		add_action( 'deco_offers_sync_gallery', __CLASS__ . '::sync_gallery', 10, 2 );
		add_filter( 'deco_offers_get_terms', __CLASS__ . '::get_terms' );
		add_filter( 'get_offer_category_and_offer_type_by_terms', __CLASS__ . '::get_offer_category_and_offer_type_by_terms', 10, 3 );

		add_filter( 'deco_offers_xml_check_feed', __CLASS__ . '::check_feed' );

		include_once "includes/class-wp-cli.php";

		self::route_aspo_biz_report();
		Includes\Reports::init();

	}

	public static function sync_to_table( $user_id, $sync_id ) {
		global $wpdb;
		$users_xml = $wpdb->get_row( "select * from wp_deco_xml_list where user_id = $user_id and id = $sync_id" );

		// Загрузка в таблицу wp_deco_xml_data_offers
		call_user_func( 'Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type\\' . $users_xml->feed_standard . '::sync_xml', array(
			'user_id'   => $user_id,
			'xml_link'  => $users_xml->link,
			'sync_id'   => $sync_id,
			'feed_type' => $users_xml->feed_type,
		) );

		// Загрузка в базу wp
//		call_user_func_array( 'Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type\\' . $users_xml->feed_standard . '::sync_start', array(
//			$user_id,
//			$sync_id
//		) );

	}

	public static function test_sync( $user_id, $sync_id ) {
		global $wpdb;
		$users_xml = $wpdb->get_row( "select * from wp_deco_xml_list where user_id = $user_id and id = $sync_id" );

		// Загрузка в таблицу wp_deco_xml_data_offers
//		call_user_func( 'Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type\\' . $users_xml->feed_standard . '::sync_xml', array(
//			'user_id'  => $user_id,
//			'xml_link' => $users_xml->link,
//			'sync_id'  => $sync_id
//		) );

		// Загрузка в базу wp
		call_user_func_array( 'Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type\\' . $users_xml->feed_standard . '::sync_start', array(
			$user_id,
			$sync_id
		) );

	}

	public static function start_import_cli() {
		global $wpdb;

		$users_xml = $wpdb->get_results( "select * from wp_deco_xml_list order by create_date desc" );
		foreach ( $users_xml as $item ) {
//?			self::xml_sync_by_user( $item->user_id, $item->link, $item->id );
//			self::start_cron_sync( $item->user_id, $item->link );
		}

	}

	public static function start_cron_sync( $user_id, $sync_id ) {
		global $wpdb;
		$users_xml = $wpdb->get_row( "select * from wp_deco_xml_list where user_id = $user_id" );
		// ?
		$wpdb->query( "update wp_deco_xml_list set status = 'started_sync', finish_sync = '' where user_id = $user_id and id = $sync_id" );
		// ?
		$time_start        = current_time( 'timestamp' );
		$time_start_format = date( 'Y-m-d H:i:s', $time_start );
		$wpdb->query( "update wp_deco_xml_list set start_sync = '$time_start_format' where user_id = $user_id and id = $sync_id" );
		call_user_func_array( 'Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type\\' . $users_xml->feed_standard . '::sync_xml', array(
			'user_id'  => $user_id,
			'xml_link' => $users_xml->link,
			'sync_id'  => $sync_id
		) );
		self::delete_old_offers_by_xml( $user_id, $sync_id );
		call_user_func( 'Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type\\' . $users_xml->feed_standard . '::sync_start', array(
			'user_id'   => $user_id,
			'sync_id'   => $sync_id,
			'feed_type' => $users_xml->feed_type,
		) );

		$time_end        = current_time( 'timestamp' );
		$time_end_format = date( 'Y-m-d H:i:s', $time_end );

		$duration = round( abs( $time_start - $time_end ) / 60, 2 );

		$wpdb->query( "update wp_deco_xml_list set finish_sync = '$time_end_format', duration = '$duration',  status = 'finished_sync' where user_id = $user_id and id = $sync_id" );
	}

	protected static function delete_old_offers_by_xml( $user_id, $sync_id ) {
		global $wpdb;

		$offers = $wpdb->get_results( "select post_id from wp_deco_xml_data_offers where user_id = $user_id and status = 'delete' and sync_id = $sync_id" );

		$i     = 0;
		$count = count( $offers );

		foreach ( $offers as $item ) {
			$i ++;
			$deco_offer_photo_list = get_post_meta( $item->post_id, 'deco_offer_photo_list', true );
			if ( is_array( $deco_offer_photo_list ) && count( $deco_offer_photo_list ) > 0 ) {
				foreach ( $deco_offer_photo_list as $id => $link ) {
					wp_delete_attachment( $id, true );
				}
			}

			wp_delete_post( $item->post_id, true );

			$mem = memory_get_peak_usage();
			self::wp_cli_log( "Remove offers: $i of $count | mem - $mem" );
		}
		$removed_counts = count( $offers );
		$wpdb->query( "update wp_deco_xml_list set removed_counts = $removed_counts where user_id = $user_id and id = $sync_id" );
		$wpdb->query( "delete from wp_deco_xml_data_offers where user_id = $user_id and status = 'delete' and sync_id = $sync_id" );
	}


	/** Многопоточная синхронизация */


	public static function sync_start_threads( $args ) {
		global $wpdb;

		$user_id = $args['user_id'];
		$sync_id = $args['sync_id'];


		\Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::sync_start_threads( $user_id, $sync_id );


	}

	public static function sync_pagination_thread( $args ) {
		$id      = $args['id'];
		$user_id = $args['user_id'];
		$sync_id = $args['sync_id'];

		\Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::sync_pagination_thread( $id, $user_id, $sync_id );

	}

	protected static function wp_cli_log( $text ) {
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::log( $text );
		}

	}

	public static function sync_gallery( $post_id = 0, $photo_images ) {

		if ( empty( $photo_images ) ) {
			return;
		}

		if ( $post_id === 0 ) {
			return;
		}
		$gallery_arr = array();

		$photo_images = explode( ',', $photo_images );

		// Удаляем аттачменты для обновления новыми
		$deco_offer_photo_list = get_post_meta( $post_id, 'deco_offer_photo_list', true );
		if ( is_array( $deco_offer_photo_list ) && count( $deco_offer_photo_list ) > 0 ) {
			foreach ( $deco_offer_photo_list as $id => $link ) {
				wp_delete_attachment( $id, true );
			}
			delete_post_meta( $post_id, '_thumbnail_id' );
		}

		$num = 0;
		foreach ( $photo_images as $url ) {
			$num ++;

			$attach_id = 0;


			$upload_dir    = wp_upload_dir();
			$filename      = basename( $url );
			$crc32_url_img = crc32( $url );

//			self::wp_cli_log( "Photos file name: $filename" );

			if ( strpos( $filename, '?' ) !== false ) {
				$filename = substr( $filename, 0, strpos( $filename, '?' ) );
			}
			list( $file_name_without_ext, $ext ) = explode( '.', $filename );
			$ext                   = strtolower( $ext );
			$file_name_without_ext = strtolower( $file_name_without_ext );

			$filename = $post_id . '-' . $file_name_without_ext . '-' . $crc32_url_img . '.' . $ext;


			if ( empty( $ext ) ) {
				continue;
			}

			$file = $upload_dir['path'] . '/' . $filename;

			$image_data = wp_remote_get( $url );

			if ( isset( $image_data['body'] ) && ! empty( $image_data['body'] ) ) {

				file_put_contents( $file, $image_data['body'] );

				if ( file_exists( $file ) ) {

					$wp_filetype = wp_check_filetype( $filename, null );
					$attachment  = array(
						'ID'             => $attach_id,
						'post_mime_type' => $wp_filetype['type'],
						'post_title'     => sanitize_file_name( $filename ),
						'post_status'    => 'inherit'
					);
					if ( $attach_id ) {
//						self::wp_cli_log( "Photos file_get UPDATE " );
					} else {
//						self::wp_cli_log( "Photos file_get INSERT " );
					}

					$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
					require_once( ABSPATH . 'wp-admin/includes/image.php' );


					add_filter( 'intermediate_image_sizes_advanced', function ( $sizes, $metadata ) {
						return array();
					}, 99, 2 );

					add_filter( 'fallback_intermediate_image_sizes', function ( $sizes, $metadata ) {
						return array();
					}, 99, 2 );


					$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

					$attach_data['sizes']       = array();
					$_wp_additional_image_sizes = wp_get_additional_image_sizes();

					$attach_data['sizes']['thumbnail'] = array(
						"file"      => $filename,
						"width"     => 150,
						"height"    => 150,
						"mime-type" => $wp_filetype['type']
					);

					foreach ( $_wp_additional_image_sizes as $key => $val ) {
						$attach_data['sizes'][ $key ] = array(
							"file"      => $filename,
							"width"     => $val['width'],
							"height"    => $val['width'],
							"mime-type" => $wp_filetype['type']
						);
					}


					wp_update_attachment_metadata( $attach_id, $attach_data );

				} else {
//					self::wp_cli_log( "Photos file not exist " );
				}
			}

			if ( $attach_id ) {
//				self::wp_cli_log( "Photos file_get ADD TO GELLERY " );
				$gallery_arr[ $attach_id ] = str_replace( ABSPATH, site_url() . '/', $file );
			}

			if ( $num === 1 ) {
				update_post_meta( $post_id, '_thumbnail_id', $attach_id );
			}

		}
		if ( count( $gallery_arr ) > 0 ) {
//			self::wp_cli_log( "Photos file_get SAVEGELLERY " );
			update_post_meta( $post_id, 'deco_offer_photo_list', $gallery_arr );
		}
	}


	public static function get_terms( $data_for_sync ) {
		$data_for_sync['type_sell_term'] = get_term_by( 'slug', 'prodazha', 'deal_type' );
		$data_for_sync['type_rent_term'] = get_term_by( 'slug', 'arenda', 'deal_type' );

		$data_for_sync['category_house']                  = get_term_by( 'slug', 'domov', 'offer_category' );
		$data_for_sync['category_apartment']              = get_term_by( 'slug', 'kvartir', 'offer_category' );
		$data_for_sync['category_commercial-real-estate'] = get_term_by( 'slug', 'kommercheskoj-nedvizhimosti', 'offer_category' );
		$data_for_sync['category_land_plot']              = get_term_by( 'slug', 'zemelnyh-uchastkov', 'offer_category' );

		$data_for_sync['offer_type_house']                              = get_term_by( 'slug', 'house', 'offer_type' );
		$data_for_sync['offer_type_dacha']                              = get_term_by( 'slug', 'dacha', 'offer_type' );
		$data_for_sync['offer_type_apartment']                          = get_term_by( 'slug', 'apartment', 'offer_type' );
		$data_for_sync['offer_type_dachnyj-uchastok']                   = get_term_by( 'slug', 'dachnyj-uchastok', 'offer_type' );
		$data_for_sync['offer_type_prochee']                            = get_term_by( 'slug', 'prochee', 'offer_type' );
		$data_for_sync['offer_type_ofisnoe-pomeshhenie']                = get_term_by( 'slug', 'ofisnoe-pomeshhenie', 'offer_type' );
		$data_for_sync['offer_type_ofisnoe-zdanie']                     = get_term_by( 'slug', 'ofisnoe-zdanie', 'offer_type' );
		$data_for_sync['offer_type_kafe-bar-restoran']                  = get_term_by( 'slug', 'kafe-bar-restoran', 'offer_type' );
		$data_for_sync['offer_type_torgovye-ploshhadi']                 = get_term_by( 'slug', 'torgovye-ploshhadi', 'offer_type' );
		$data_for_sync['offer_type_skladskoe-pomeshhenie']              = get_term_by( 'slug', 'skladskoe-pomeshhenie', 'offer_type' );
		$data_for_sync['offer_type_obekt-sfery-uslug']                  = get_term_by( 'slug', 'obekt-sfery-uslug', 'offer_type' );
		$data_for_sync['offer_type_kommercheskogo-naznacheniya']        = get_term_by( 'slug', 'kommercheskogo-naznacheniya', 'offer_type' );
		$data_for_sync['offer_type_komnata']                            = get_term_by( 'slug', 'komnata', 'offer_type' );
		$data_for_sync['offer_type_chast-doma']                         = get_term_by( 'slug', 'chast-doma', 'offer_type' );
		$data_for_sync['offer_type_pod-zhiluyu-zastrojku']              = get_term_by( 'slug', 'pod-zhiluyu-zastrojku', 'offer_type' );
		$data_for_sync['offer_type_rekreatsionnogo-naznacheniya']       = get_term_by( 'slug', 'rekreatsionnogo-naznacheniya', 'offer_type' );
		$data_for_sync['offer_type_selskohozyajstvennogo-naznacheniya'] = get_term_by( 'slug', 'selskohozyajstvennogo-naznacheniya', 'offer_type' );


		$data_for_sync['category_garazhej-i-parkingov'] = get_term_by( 'slug', 'garazhej-i-parkingov', 'offer_category' );
		$data_for_sync['offer_type_garazh']             = get_term_by( 'slug', 'garazh', 'offer_type' );
		$data_for_sync['offer_type_mesto-na-parkovke']  = get_term_by( 'slug', 'mesto-na-parkovke', 'offer_type' );

		$data_for_sync['option_qtranslate_term_name'] = get_option( 'qtranslate_term_name' );

		return $data_for_sync;
	}

	public static function get_offer_category_and_offer_type_by_terms( $category, $data_for_sync, $data ) {
		$data['offer_category'] = false;
		$data['offer_type']     = false;

		$category = trim( $category );

		switch ( $category ) {
			// =============================================================================
			case 'дача':
			case 'dacha':
				$data['offer_category'] = $data_for_sync['category_house'];
				$data['offer_type']     = $data_for_sync['offer_type_dacha'];
				break;
			case 'комната':
			case 'Комната':
			case 'room':
				$data['offer_category'] = $data_for_sync['category_apartment'];
				$data['offer_type']     = $data_for_sync['offer_type_komnata'];
				break;
			case 'Дом':
			case 'дом':
			case 'cottage':
			case 'Частный дом':
			case 'коттедж':
			case 'Таунхаусы':
			case 'home':
			case 'townhouse':
			case 'homes':
				$data['offer_category'] = $data_for_sync['category_house'];
				$data['offer_type']     = $data_for_sync['offer_type_house'];
				break;
			// =============================================================================
			case 'часть дома':
			case 'part-home':
				$data['offer_category'] = $data_for_sync['category_house'];
				$data['offer_type']     = $data_for_sync['offer_type_chast-doma'];
				break;
			// =============================================================================
			case 'квартира':
			case 'Квартира':
			case 'Квартиры':
			case 'квартиры':
			case 'apartment':
			case 'apartments':
				$data['offer_category'] = $data_for_sync['category_apartment'];
				$data['offer_type']     = $data_for_sync['offer_type_apartment'];
				break;
			// =============================================================================
			case 'земельный участок':
			case 'Участок':
			case 'участок':
			case 'land':
				$data['offer_category'] = $data_for_sync['category_land_plot'];
				$data['offer_type']     = $data_for_sync['offer_type_dachnyj-uchastok'];
				break;
			case 'участок для строительства жилья':
			case 'для строительства жилья':
				$data['offer_category'] = $data_for_sync['category_land_plot'];
				$data['offer_type']     = $data_for_sync['offer_type_prochee'];
				break;
			case 'Дома и земля':
			case 'дома и земля':
			case 'дом с участком':
			case 'Дом с участком':
				$data['offer_category'] = $data_for_sync['category_house'];
				$data['offer_type']     = $data_for_sync['offer_type_house'];
				break;
			case 'участок для строительства коммерческих объектов':
			case 'commercial-land':
				$data['offer_category'] = $data_for_sync['category_land_plot'];
				$data['offer_type']     = $data_for_sync['offer_type_kommercheskogo-naznacheniya'];
				break;
			case 'agricultural-land':
				$data['offer_category'] = $data_for_sync['category_land_plot'];
				$data['offer_type']     = $data_for_sync['offer_type_selskohozyajstvennogo-naznacheniya'];
				break;
			case 'recreational-land':
				$data['offer_category'] = $data_for_sync['category_land_plot'];
				$data['offer_type']     = $data_for_sync['offer_type_rekreatsionnogo-naznacheniya'];
				break;
			case 'land-for-building':
				$data['offer_category'] = $data_for_sync['category_land_plot'];
				$data['offer_type']     = $data_for_sync['offer_type_pod-zhiluyu-zastrojku'];
				break;
			// =============================================================================
			case 'commercial_real_estate':
			case 'коммерция':
			case 'office-space':
			case 'business':
			case 'industry':
			case 'office':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-pomeshhenie'];
				break;
			case 'Бизнес-центр':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-zdanie'];
				break;
			case 'здание':
			case 'office-building':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-zdanie'];
				break;
			case 'Кафе/Ресторан':
			case 'cafe':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_kafe-bar-restoran'];
				break;
			case 'Магазин':
			case 'торговая площадка':
			case 'Торговое место':
			case 'Торговые помещения':
			case 'для красоты, отдыха, оздоровления':
			case 'sevice':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_torgovye-ploshhadi'];
				break;
			case 'Офис':
			case 'Офисы':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-pomeshhenie'];
				break;
			case 'помещение свободного назначения':
			case 'facilities-free-destination':
			case 'hall':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-pomeshhenie'];
				break;
			case 'Складской комплекс':
			case 'Промышленность':
			case 'Склады':
			case 'warehouses':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_skladskoe-pomeshhenie'];
				break;
			case 'Спортивный зал':
			case 'Спортивный комплекс':
			case 'object-services':
			case 'hotel':
			case 'recreation-center':
				$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
				$data['offer_type']     = $data_for_sync['offer_type_obekt-sfery-uslug'];
				break;
			// =============================================================================
			case 'box-in-the-garage-complex':
			case 'detached-garage':
				$data['offer_category'] = $data_for_sync['category_garazhej-i-parkingov'];
				$data['offer_type']     = $data_for_sync['offer_type_garazh'];
				break;
			case 'underground-parking':
			case 'place-in-garage-cooperative':
			case 'parking-place':
				$data['offer_category'] = $data_for_sync['category_garazhej-i-parkingov'];
				$data['offer_type']     = $data_for_sync['offer_type_mesto-na-parkovke'];
				break;
		}


		return $data;
	}


	public static function check_feed( $link ) {

		return Includes\Xml::get_xml_data_by_link( $link );
	}

	public static function route_aspo_biz_report() {

		\Routes::map( 'xml/aspo_biz_report/', function ( $params ) {
			\Routes::load( 'deco-framework/deco/bundles/offers/modules/offers-xml/includes/templates/aspo_biz_report.php', $params, '' );
		} );

	}

}



