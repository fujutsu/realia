<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class Realia_Offers extends \WP_CLI_Command {

	public function import( $args, $assoc_args ) {
		$user_id           = $assoc_args['user_id'];
		$file_name         = $assoc_args['file_name'];
		$offer_category_id = $assoc_args['offer_category_id'];

		self::import_offers_for_current_user( $user_id, $file_name, $offer_category_id );
	}

	public function import_media( $args, $assoc_args ) {
		$user_id   = $assoc_args['user_id'];
		$file_name = $assoc_args['file_name'];

		self::import_offers_media( $user_id, $file_name );
	}

	public static function import_offers_for_current_user( $user_id, $file_name, $offer_category_id ) {
		$upload_dir_path = ABSPATH . 'wp-content/uploads/import_offers_adslfkjasdfhjkwhejkhqwekjr';

		$import_data = json_decode( file_get_contents( $upload_dir_path . '/' . $user_id . '_import_offers.json' ) );

		if ( $import_data->status == 'cancel' ) {
			$import_data->status = 'finish';
			file_put_contents( $upload_dir_path . '/' . $user_id . '_import_offers.json', json_encode( (array) $import_data ) );
			exit;
		}

		$data_file = array_map( 'str_getcsv', file( $file_name ) );
//		print_r($data_file);

		if ( empty( $import_data->total ) ) {
//			$data_file = file_get_contents( $file_name );
//			if ( $data_file ) {
//				$data  = explode( "\r", $data_file );
//				$i     = 1;
//				$count = 0;
//				unset( $data[0] );
//				foreach ( $data as $line ) {
//					$line = self::str_to_utf8( $line );
//					$item = explode( ';', $line );
//
//					if ( !empty( $item[1] ) ) {
//						$count ++;
//					}
//
//					$i ++;
//				}
//			}

			if ( $data_file ) {
				$i     = 1;
				$count = 0;
				$data  = $data_file;
				unset( $data[0] );
				foreach ( $data as $line ) {
					if ( ! empty( $line[1] ) ) {
						$count ++;
					}

					$i ++;
				}
			}

			$import_data->total = $count;
			$data_arr           = (array) $import_data;
			file_put_contents( $upload_dir_path . '/' . $user_id . '_import_offers.json', json_encode( $data_arr ) );
		}

		if ( $data_file ) {
			$data = $data_file;
			unset( $data[0] );
			$i = 1;
			foreach ( $data as $line ) {
				$import_data = json_decode( file_get_contents( $upload_dir_path . '/' . $user_id . '_import_offers.json' ) );
				if ( $import_data->status == 'cancel' ) {
					$import_data->status = 'finish';
					file_put_contents( $upload_dir_path . '/' . $user_id . '_import_offers.json', json_encode( (array) $import_data ) );
					exit;
				} elseif ( $import_data->status == 'finish' ) {
					exit;
				}

//				$line = self::str_to_utf8( $line );
//				$item = explode( ';', $line );
				$res = '';

				if ( ! empty( $line[1] ) ) {
					$res = self::save_offer( $offer_category_id, $line, $user_id );
				}

				if ( isset( $res['post_id'] ) && $res['post_id'] > 0 ) {
					if ( $res['post_status'] == 'publish' ) {
						$published              = $import_data->published + 1;
						$import_data->published = $published;
					} else {
						$moderation              = $import_data->moderation + 1;
						$import_data->moderation = $moderation;
					}
					$imported              = $import_data->imported + 1;
					$import_data->imported = $imported;

					array_push( $import_data->imported_posts, $res['post_id'] );
				}

				$live_import_data    = json_decode( file_get_contents( $upload_dir_path . '/' . $user_id . '_import_offers.json' ) );
				$import_data->status = $live_import_data->status;

				$data_arr = (array) $import_data;

				file_put_contents( $upload_dir_path . '/' . $user_id . '_import_offers.json', json_encode( $data_arr ) );

				$i ++;
			}

			$import_data->status = 'finish';
			file_put_contents( $upload_dir_path . '/' . $user_id . '_import_offers.json', json_encode( (array) $import_data ) );
		}

	}

	public static function save_offer( $offer_category_id = '', $data = array(), $user_id = '' ) {
		global $wpdb;

		$prefix              = 'deco_offer_';
		$yoast_prefix        = '_yoast_wpseo_primary_';
		$post_type           = 'offers';
		$table_offers_agents = $wpdb->base_prefix . 'offers_agents';

//		if ( empty( $data ) || empty( $offer_category_id ) ) {
//			return false;
//		}

//		if ( empty( $data[1] ) ) {
//			$result['post_id'] = '';
//			return $result;
//		}

		$offer_category = get_term_by( 'term_id', $offer_category_id, 'offer_category' );

		if ( $offer_category->slug == 'kvartir' ) {
			$data = self::prepare_apartment_data( $data );
		} elseif ( $offer_category->slug == 'domov' ) {
			$data = self::prepare_house_data( $data );
		} elseif ( $offer_category->slug == 'kommercheskoj-nedvizhimosti' ) {
			$data = self::prepare_commercial_real_estate_data( $data );
		} elseif ( $offer_category->slug == 'zemelnyh-uchastkov' ) {
			$data = self::prepare_land_plot_data( $data );
		} elseif ( $offer_category->slug == 'garazhej-i-parkingov' ) {
//			$data = self::prepare_garage_parking_data( $data );
		}

		$option_qtranslate_term_name = get_option( 'qtranslate_term_name' );

		$type_term = get_term_by( 'term_id', $data['deal_type'], 'deal_type' );

		if ( ! empty( $data['offer_type'] ) ) {
			$offer_type = get_term_by( 'term_id', $data['offer_type'], 'offer_type' );

			$alternative_title = get_term_meta( $data['offer_type'], 'deco_offer_type_alternative_title_object', true );
			$offer_cat_name_ru = $alternative_title ? self::qtrans_return( 'ru', $alternative_title ) : mb_strtolower( $option_qtranslate_term_name[ $offer_type->name ]['ru'] );
			$offer_cat_name_ua = $alternative_title ? self::qtrans_return( 'ua', $alternative_title ) : mb_strtolower( $option_qtranslate_term_name[ $offer_type->name ]['ua'] );
		} else {
			if ( $offer_category ) {
				$offer_type = get_term_by( 'name', $offer_category->name, 'offer_type' );
				if ( ! empty( $offer_type ) && ! is_wp_error( $offer_type ) ) {
					$data['offer_type'] = $offer_type->term_id;
					$alternative_title  = get_term_meta( $data['offer_type'], 'deco_offer_type_alternative_title_object', true );
					$offer_cat_name_ru  = $alternative_title ? self::qtrans_return( 'ru', $alternative_title ) : mb_strtolower( $option_qtranslate_term_name[ $offer_type->name ]['ru'] );
					$offer_cat_name_ua  = $alternative_title ? self::qtrans_return( 'ua', $alternative_title ) : mb_strtolower( $option_qtranslate_term_name[ $offer_type->name ]['ua'] );
				} else {
					$offer_cat_name_ru = mb_strtolower( $option_qtranslate_term_name[ $offer_category->name ]['ru'] );
					$offer_cat_name_ua = mb_strtolower( $option_qtranslate_term_name[ $offer_category->name ]['ua'] );
				}
			} elseif ( ! empty( $data['offer_type_string'] ) ) {
				$offer_cat_name_ru = mb_strtolower( $data['offer_type_string'] );
				$offer_cat_name_ua = mb_strtolower( $data['offer_type_string'] );
			}
		}

		$title = '[:ru]' . $option_qtranslate_term_name[ $type_term->name ]['ru'] . ' ' . $offer_cat_name_ru . '[:ua]' . $option_qtranslate_term_name[ $type_term->name ]['ua'] . ' ' . $offer_cat_name_ua . '[:]';

//		if ( empty( $user_id ) ) {
//			$user_id = get_current_user_id();
//		}

//		$is_update_offer = false;
//		if ( isset( $data['ID'] ) && !empty( $data['ID'] ) ) {
//			$is_update_offer = true;
//			$user_ids        = array();
//
//			$deco_offer_added_user_id = get_post_meta( $data['ID'], 'deco_offer_added_user_id', true );
//			$deco_users_ids           = get_post_meta( $data['ID'], 'deco_users_ids', true );
//			if ( $deco_users_ids ) {
//				$user_ids = json_decode( stripslashes( $deco_users_ids ) );
//			}
//
//			if ( ( $user_id != $deco_offer_added_user_id ) && !in_array( $user_id, $user_ids ) ) {
//				return 'ERROR';
//				$res['message'] = 'Access denied';
//			}
//		}

		$post_status     = 'publish';
		$message_pending = '';
		if ( empty( $data['offer_type'] ) || empty( $data['city'] ) || empty( $data['street'] ) || empty( $data['price'] ) ) {
			$post_status = 'pending';
//			$count           = 1;
//			$message_pending = 'Причина модерации:';
//			if ( empty( $data['offer_type'] ) ) {
//				$message_pending .= $count . '. Уточнение типа';
//				$message_pending .= '<br>Входные данные: ' . ( $data['offer_type_string'] ? $data['offer_type_string'] : 'пусто' );
//				$count ++;
//			}
//
//			if ( empty( $data['city'] ) ) {
//				$message_pending .= $count . '. Не указан город';
//				$count ++;
//			}
//
//			if ( empty( $data['street'] ) ) {
//				$message_pending .= $count . '. Не указана улица';
//				$count ++;
//			}
//
//			if ( empty( $data['location_id'] ) ) {
//				$message_pending .= $count . '. Не найден адрес';
//				$message_pending .= '<br>Входные данные: ' . $data[4] . ' ' . $data[5] . ' ' . $data[6] . ' ' . $data[7] . ' ' . $data[8];
//				$count ++;
//			}
//
//			if ( empty( $data['price'] ) ) {
//				$message_pending .= $count . '. Не указана цена';
//				$count ++;
//			}

		}

		$post_data = array(
			'post_title'   => $title,
			'post_content' => $data['adFormDescr'],
			'post_author'  => $user_id,
			'post_status'  => $post_status,
			'post_type'    => $post_type
		);

		if ( isset( $data['ID'] ) && ! empty( $data['ID'] ) ) {
			$post_data['ID'] = $data['ID'];
		}

		//$post_id = wp_insert_post( wp_slash( $post_data ) );
		$post_id = wp_insert_post(  $post_data  );

//		if ( $post_status == 'pending' && $message_pending ) {
//			update_post_meta( $post_id, 'deco_offer_pending_message', $message_pending );
//		}

		if ( isset( $data['gallery'] ) && is_array( $data['gallery'] ) ) {

			$count = 1;

			$slides        = $map_marker_slides = $photo_list = '';
			$single_slides = array();
			foreach ( $data['gallery'] as $image_url ) {

				if ( $count > 1 ) {
					continue;
				}

				$attachment_id = self::upload_image( $image_url, $post_id, $count );

				if ( ! $attachment_id ) {
					continue;
				}

				if ( $count <= 5 ) {
					$slide            = wp_get_attachment_image_src( $attachment_id, 'offer_blog_loop_tease' )[0];
					$map_thumb        = wp_get_attachment_image_src( $attachment_id, 'tooltip_tease_on_map' )[0];
					$map_marker_thumb = wp_get_attachment_image_src( $attachment_id, 'offer_map_marker_tease' )[0];
					if ( $count == 1 ) {
						update_post_meta( $post_id, 'deco_offer_thumbnail', $slide );
						update_post_meta( $post_id, 'deco_offer_map_thumb', $map_thumb );
					}
					$slides .= '<span class="pu-img-src" data-img-src="' . $slide . '"></span>';
				}

				$photo_list[ $attachment_id ] = $image_url;

				$single_slide = wp_get_attachment_image_src( $attachment_id, 'offer_single_tease' )[0];
				//$single_slides .= '<img src="' . $single_slide . '" data-full="' . $image_url . '">';

				$single_slides[] = array(
					'src'  => $single_slide,
					'full' => $image_url,
				);

				$map_marker_slides .= '<div class="flat-modal-slider-item">
		            <div class="flat-modal-previev js_modal-slide-item" data-modal-src="' . $map_marker_thumb . '" style="background-image: url(\'' . $map_marker_thumb . '\');"></div>
		        </div>';

				$count ++;
			}

			update_post_meta( $post_id, 'deco_offer_photo_import_list', implode( ',', $data['gallery'] ) );

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

		/* Taxonomies */
		if ( $data['deal_type'] ) {
			wp_set_post_terms( $post_id, array( $data['deal_type'] ), 'deal_type' );
			update_post_meta( $post_id, $yoast_prefix . 'deal_type', $data['deal_type'] );
		}

		if ( $data['offer_category'] ) {
			wp_set_post_terms( $post_id, array( $data['offer_category'] ), 'offer_category' );
			update_post_meta( $post_id, $yoast_prefix . 'offer_category', $data['offer_category'] );
		}

		if ( $data['offer_type'] ) {
			wp_set_post_terms( $post_id, array( $data['offer_type'] ), 'offer_type' );
			update_post_meta( $post_id, $yoast_prefix . 'offer_type', $data['offer_type'] );
		}

		if ( $data['count_rooms'] ) {
			wp_set_post_terms( $post_id, array( $data['count_rooms'] ), 'count_rooms' );
			update_post_meta( $post_id, $yoast_prefix . 'count_rooms', $data['count_rooms'] );
		}

		if ( $data['count_bathrooms'] ) {
			wp_set_post_terms( $post_id, array( $data['count_bathrooms'] ), 'count_bathrooms' );
			update_post_meta( $post_id, $yoast_prefix . 'count_bathrooms', $data['count_bathrooms'] );
		}

		if ( $data['repair'] ) {
			wp_set_post_terms( $post_id, array( $data['repair'] ), 'repair' );
			update_post_meta( $post_id, $yoast_prefix . 'repair', $data['repair'] );
		}

		if ( $data['code'] ) {
			wp_set_post_terms( $post_id, array( $data['code'] ), 'code' );
			update_post_meta( $post_id, $yoast_prefix . 'code', $data['code'] );
		}

		if ( $data['heating'] ) {
			wp_set_post_terms( $post_id, array( $data['heating'] ), 'heating' );
			update_post_meta( $post_id, $yoast_prefix . 'heating', $data['heating'] );
		}

		if ( $data['wall_type'] ) {
			wp_set_post_terms( $post_id, array( $data['wall_type'] ), 'wall_type' );
			update_post_meta( $post_id, $yoast_prefix . 'wall_type', $data['wall_type'] );
		}

		if ( $data['metro'] ) {
			wp_set_post_terms( $post_id, array( $data['metro'] ), 'metro' );
			update_post_meta( $post_id, $yoast_prefix . 'metro', $data['metro'] );
		}

		if ( $data['ifs'] ) {
			wp_set_post_terms( $post_id, $data['ifs'], 'ifs' );
//			$ifs = explode( ',', $data['ifs'] );
			update_post_meta( $post_id, $yoast_prefix . 'ifs', $data['ifs'][0] );
		}

		if ( $data['parking'] ) {
			wp_set_post_terms( $post_id, array( $data['parking'] ), 'parking' );
			update_post_meta( $post_id, $yoast_prefix . 'parking', $data['parking'] );
		}

		if ( $data['sewerage'] ) {
			wp_set_post_terms( $post_id, array( $data['sewerage'] ), 'sewerage' );
			update_post_meta( $post_id, $yoast_prefix . 'sewerage', $data['sewerage'] );
		}

		if ( $data['window_type'] ) {
			wp_set_post_terms( $post_id, array( $data['window_type'] ), 'window_type' );
			update_post_meta( $post_id, $yoast_prefix . 'window_type', $data['window_type'] );
		}

		if ( $data['plumbing'] ) {
			wp_set_post_terms( $post_id, array( $data['plumbing'] ), 'plumbing' );
			update_post_meta( $post_id, $yoast_prefix . 'plumbing', $data['plumbing'] );
		}

		if ( $data['lodge_class'] ) {
			wp_set_post_terms( $post_id, array( $data['lodge_class'] ), 'lodge_class' );
			update_post_meta( $post_id, $yoast_prefix . 'lodge_class', $data['lodge_class'] );
		}

		if ( $data['building_type'] ) {
			wp_set_post_terms( $post_id, array( $data['building_type'] ), 'building_type' );
			update_post_meta( $post_id, $yoast_prefix . 'building_type', $data['building_type'] );
		}

//		if ( $data['kitchen'] ) {
//			wp_set_post_terms( $post_id, array( $data['kitchen'] ), 'kitchen' );
//			update_post_meta( $post_id, $yoast_prefix . 'kitchen', $data['kitchen'] );
//		}

		if ( $data['intended_purpose'] ) {
			wp_set_post_terms( $post_id, array( $data['intended_purpose'] ), 'intended_purpose' );
			update_post_meta( $post_id, $yoast_prefix . 'intended_purpose', $data['intended_purpose'] );
		}

		if ( $data['location'] ) {
			wp_set_post_terms( $post_id, array( $data['location'] ), 'location' );
			update_post_meta( $post_id, $yoast_prefix . 'location', $data['location'] );
		}

		if ( $data['electricity'] ) {
			wp_set_post_terms( $post_id, array( $data['electricity'] ), 'electricity' );
			update_post_meta( $post_id, $yoast_prefix . 'electricity', $data['electricity'] );
		}

		if ( $data['gasmain'] ) {
			wp_set_post_terms( $post_id, array( $data['gasmain'] ), 'gasmain' );
			update_post_meta( $post_id, $yoast_prefix . 'gasmain', $data['gasmain'] );
		}

		if ( $data['property_classes'] ) {
			wp_set_post_terms( $post_id, array( $data['property_classes'] ), 'property_classes' );
			update_post_meta( $post_id, $yoast_prefix . 'property_classes', $data['property_classes'] );
		}

		if ( $data['type_payment'] ) {
			wp_set_post_terms( $post_id, array( $data['type_payment'] ), 'type_payment' );
			update_post_meta( $post_id, $yoast_prefix . 'type_payment', $data['type_payment'] );
		}

		if ( $data['type_proposition'] ) {
			wp_set_post_terms( $post_id, array( $data['type_proposition'] ), 'type_proposition' );
			update_post_meta( $post_id, $yoast_prefix . 'type_proposition', $data['type_proposition'] );
		}

		if ( $data['comfort'] ) {
			wp_set_post_terms( $post_id, array( $data['comfort'] ), 'comfort' );
			update_post_meta( $post_id, $yoast_prefix . 'comfort', $data['comfort'] );
		}
		/* End Taxonomies */


		/** Address */
		do_action( 'deco_get_address_by_string_address', array(
			'post_id'                => $post_id,
			'region_name'            => $data['county'],
			'locality_name'          => $data['city'],
			'district_locality_name' => $data['borough'],
			'street_name'            => $data['street'] . ', ' . $data['house_number'],
		) );

		do_action( 'deco_set_coords_by_address_line', array(
			'post_id'           => $post_id,
			'address_line'      => $data['county'] . ', ' . $data['city'] . ', ' . $data['street'] . ', ' . $data['house_number'],
			'address_latitude'  => '',
			'address_longitude' => '',
		) );
		/** END Address */


		/* Metas */

		if ( $data['price'] ) {
			update_post_meta( $post_id, $prefix . 'price', $data['price'] );
		}

		if ( $data['square'] ) {
			update_post_meta( $post_id, $prefix . 'square', $data['square'] );
		}

		if ( $data['available_square'] ) {
			update_post_meta( $post_id, $prefix . 'available_square', $data['available_square'] );
		}

		if ( $data['living_area'] ) {
			update_post_meta( $post_id, $prefix . 'living_area', $data['living_area'] );
		}

		if ( $data['kitchen_area'] ) {
			update_post_meta( $post_id, $prefix . 'kitchen_area', $data['kitchen_area'] );
		}

		if ( $data['land_area'] ) {
			update_post_meta( $post_id, $prefix . 'land_area', $data['land_area'] );
		}

//		if ( $data['land_plot_area'] ) {
//			update_post_meta( $post_id, $prefix . 'land_plot_area', $data['land_plot_area'] );
//		}


		if ( $data['floor'] ) {
			update_post_meta( $post_id, $prefix . 'floor', $data['floor'] );
		}

		if ( $data['floors'] ) {
			update_post_meta( $post_id, $prefix . 'floors', $data['floors'] );
		}

		if ( $data['year_built'] ) {
			update_post_meta( $post_id, $prefix . 'year_built', $data['year_built'] );
		}

		if ( $data['ceiling_height'] ) {
			update_post_meta( $post_id, $prefix . 'ceiling_height', $data['ceiling_height'] );
		}

		if ( $data['elite_real_estate'] ) {
			update_post_meta( $post_id, $prefix . 'elite_real_estate', 'on' );
		}

		if ( $data['distance'] ) {
			update_post_meta( $post_id, $prefix . 'distance', $data['distance'] );
		}

		if ( $data['cadastral_number'] ) {
			update_post_meta( $post_id, $prefix . 'cadastral_number', $data['cadastral_number'] );
		}
//		/* End Metas */

//		$user_id = get_current_user_id();

		if ( $user_id ) {
			global $wpdb;
			$blog_id        = get_current_blog_id();
			$user_ids       = array();
			$deco_users_ids = array();

			update_post_meta( $post_id, 'deco_offer_added_user_id', $user_id );

			// Remove all records by post_id and blog_id (for multisite)
			$wpdb->delete( $table_offers_agents,
				array(
					'post_id' => $post_id,
					'blog_id' => $blog_id
				)
			);

			$cost = $data['price'];

			$cost = str_replace( ',', '.', $cost );
			$cost = is_float( $cost ) ? floatval( $cost ) : $cost;
			$cost = is_int( $cost ) ? intval( $cost ) : $cost;

			$user_data = new \stdClass();

			$user_data->id   = $user_id;
			$user_data->cost = $cost;

			$user_ids[]     = $user_data;
			$deco_users_ids = json_encode( $user_ids );

			if ( ! empty( $user_ids ) ) {
				foreach ( $user_ids as $item ) {
					$user_id   = $item->id;
					$agency_id = intval( get_user_meta( $user_id, 'deco_agency_term_id', true ) );
					$wpdb->insert(
						$table_offers_agents,
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

		do_action( 'save_post', $post_id );
//		do_action( 'save_post_offers', $post_id );
//		ep_sync_post( $post_id );
//		passthru( "wp elasticpress index --posts-per-page=1 --post-type='offers'" );

		$result['post_id']     = $post_id;
		$result['post_status'] = $post_status;

		return $result;
	}

	public static function import_offers_media( $user_id, $file_name ) {
		$upload_dir_path = ABSPATH . 'wp-content/uploads/import_offers_adslfkjasdfhjkwhejkhqwekjr';

		$import_media_data = json_decode( file_get_contents( $file_name ) );

		if ( isset( $import_media_data->imported_posts ) && is_array( $import_media_data->imported_posts ) ) {
			foreach ( $import_media_data->imported_posts as $post_id ) {
				$gallery           = get_post_meta( $post_id, 'deco_offer_photo_import_list', true );
				$import_media_data = json_decode( file_get_contents( $file_name ) );
				if ( $import_media_data->status == 'cancel' ) {
					$import_media_data->status = 'finish';
					file_put_contents( $file_name, json_encode( (array) $import_media_data ) );
					exit;
				} elseif ( $import_media_data->status == 'finish' ) {
					exit;
				}

				if ( ! empty( $gallery ) ) {
					$gallery = explode( ',', $gallery );

					$count         = 1;
					$slides        = /*$single_slides =*/
					$map_marker_slides = $photo_list = '';
					$single_slides = array();
					foreach ( $gallery as $image_url ) {

						$attachment_id = self::upload_image( $image_url, $post_id, $count );

						if ( ! $attachment_id ) {
							continue;
						}

						if ( $count <= 5 ) {
							$slide            = wp_get_attachment_image_src( $attachment_id, 'offer_blog_loop_tease' )[0];
							$map_thumb        = wp_get_attachment_image_src( $attachment_id, 'tooltip_tease_on_map' )[0];
							$map_marker_thumb = wp_get_attachment_image_src( $attachment_id, 'offer_map_marker_tease' )[0];
							if ( $count == 1 ) {
								update_post_meta( $post_id, 'deco_offer_thumbnail', $slide );
								update_post_meta( $post_id, 'deco_offer_map_thumb', $map_thumb );
							}
							$slides .= '<span class="pu-img-src" data-img-src="' . $slide . '"></span>';
						}

						$photo_list[ $attachment_id ] = $image_url;

						$single_slide = wp_get_attachment_image_src( $attachment_id, 'offer_single_tease' )[0];
						//$single_slides .= '<img src="' . $single_slide . '" data-full="' . $image_url . '">';

						$single_slides[] = array(
							'src'  => $single_slide,
							'full' => $image_url,
						);

						$map_marker_slides .= '<div class="flat-modal-slider-item">
		            <div class="flat-modal-previev js_modal-slide-item" data-modal-src="' . $map_marker_thumb . '" style="background-image: url(\'' . $map_marker_thumb . '\');"></div>
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

				$imported                    = $import_media_data->imported + 1;
				$import_media_data->imported = $imported;

				$live_import_data          = json_decode( file_get_contents( $file_name ) );
				$import_media_data->status = $live_import_data->status;

				$data_arr = (array) $import_media_data;

				file_put_contents( $file_name, json_encode( $data_arr ) );
			}

			$import_media_data->status = 'finish';
			file_put_contents( $file_name, json_encode( (array) $import_media_data ) );
		}
	}

	public static function qtrans_return( $needle_lang = '', $string = '' ) {
		global $q_config;
		$result = array();

		if ( empty( $string ) ) {
			return $string;
		}

		if ( empty( $needle_lang ) ) {
			$needle_lang = $q_config['language'];
		}

		if ( empty( $needle_lang ) ) {
			return $string;
		}

		foreach ( $q_config['enabled_languages'] as $language ) {
			$result[ $language ] = '';
		}

		$split_regex = "#(<!--:[a-z]{2}-->|<!--:-->|\[:[a-z]{2}\]|\[:\]|\{:[a-z]{2}\}|\{:\})#ism";
		$blocks      = preg_split( $split_regex, $string, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

		$current_language = false;
		foreach ( $blocks as $block ) {
			// detect c-tags
			if ( preg_match( "#^<!--:([a-z]{2})-->$#ism", $block, $matches ) ) {
				$current_language = $matches[1];
				continue;
				// detect b-tags
			} elseif ( preg_match( "#^\[:([a-z]{2})\]$#ism", $block, $matches ) ) {
				$current_language = $matches[1];
				continue;
				// detect s-tags @since 3.3.6 swirly bracket encoding added
			} elseif ( preg_match( "#^\{:([a-z]{2})\}$#ism", $block, $matches ) ) {
				$current_language = $matches[1];
				continue;
			}
			switch ( $block ) {
				case '[:]':
				case '{:}':
				case '<!--:-->':
					$current_language = false;
					break;
				default:
					// correctly categorize text block
					if ( $current_language ) {
						if ( ! isset( $result[ $current_language ] ) ) {
							$result[ $current_language ] = '';
						}
						$result[ $current_language ] .= $block;
						$found[ $current_language ] = true;
						$current_language           = false;
					} else {
						foreach ( $q_config['enabled_languages'] as $language ) {
							$result[ $language ] .= $block;
						}
					}
					break;
			}
		}
		//it gets trimmed later in qtranxf_use() anyway, better to do it here
		foreach ( $result as $lang => $text ) {
			$result[ $lang ] = trim( $text );
		}

		return $result[ $needle_lang ];
	}

	public static function prepare_apartment_data( $data ) {

//		$location_address = preg_replace( "#(.*?)\(.*?\)(.*?)#is", "\\1\\3", $data[7] ) . ',' . str_replace( ' ', '', $data[8] ) . ', ' . $data[6] . ( $data[5] ? ', ' . $data[5] : '' ) . ( $data[4] ? ', ' . $data[4] : '' );

		$offer_cat = get_term_by( 'slug', 'kvartir', 'offer_category' );

		$offer_data = array(
			'blog_id'           => $data[0] ? (int) explode( '-', $data[0] )[0] : '',
			'ID'                => $data[0] ? (int) explode( '-', $data[0] )[1] : '',
			'deal_type'         => get_term_by( 'name', $data[1], 'deal_type' )->term_id,
			'offer_category'    => $offer_cat->term_id,
			'offer_type'        => get_term_by( 'name', trim( $data[2] ), 'offer_type' )->term_id,
			'offer_type_string' => $data[2],
			'gallery'           => explode( ',', $data[3] ),
//			'location_address'   => $location_address,
			'county'            => $data[4],
			'borough'           => $data[5],
			'city'              => trim( $data[6] ),
			'street'            => $data[7],
			'house_number'      => $data[8],
			'adFormDescr'       => $data[9],
			'price'             => str_replace( ' ', '', $data[10] ),
			'square'            => $data[11],
			'living_area'       => $data[12],
			'kitchen_area'      => $data[13],
			'count_rooms'       => get_term_by( 'name', $data[14], 'count_rooms' )->term_id,
			'count_bathrooms'   => get_term_by( 'name', $data[15], 'count_bathrooms' )->term_id,
			'floor'             => $data[16],
			'floors'            => $data[17],
			'repair'            => get_term_by( 'name', $data[18], 'repair' )->term_id,
			'code'              => get_term_by( 'name', $data[19], 'code' )->term_id,
			'heating'           => get_term_by( 'name', $data[20], 'heating' )->term_id,
			'wall_type'         => get_term_by( 'name', $data[21], 'wall_type' )->term_id,
			'year_built'        => $data[22],
			'ceiling_height'    => $data[23],
			'elite_real_estate' => ( $data[24] == 'да' ) ? 1 : 0,
			'metro'             => self::get_metro_ids( $data[25] ),
			'ifs'               => self::get_ifs_ids( $data[26], $offer_cat->term_id ),
			'parking'           => get_term_by( 'name', $data[27], 'parking' )->term_id,
			'property_classes'  => get_term_by( 'name', $data[28], 'property_classes' )->term_id,
			'type_payment'      => get_term_by( 'name', $data[29], 'type_payment' )->term_id,
			'type_proposition'  => get_term_by( 'name', $data[30], 'type_proposition' )->term_id,
			'comfort'           => get_term_by( 'name', $data[31], 'comfort' )->term_id,
		);

		return $offer_data;
	}

	public static function prepare_house_data( $data ) {

//		$offer_cat = get_term_by( 'slug', 'domov', 'offer_category' );
		$offer_type_id = '';
		$offer_cat_id  = '';
		$offer_type    = get_term_by( 'name', trim( $data[2] ), 'offer_type' );

		if ( ! empty( $offer_type ) && ! is_wp_error( $offer_type ) ) {
			$offer_type_id     = $offer_type->term_id;
			$offer_type_cat_id = get_term_meta( $offer_type_id, 'deco_offer_type_cat_id', true );
			if ( ! empty( $offer_type_cat_id ) ) {
				$offer_cat = get_term_by( 'term_id', $offer_type_cat_id, 'offer_category' );
				if ( ! empty( $offer_cat ) && ! is_wp_error( $offer_cat ) ) {
					$offer_cat_id = $offer_cat->term_id;
				}
			}
		}

		$offer_data = array(
			'blog_id'           => $data[0] ? (int) explode( '-', $data[0] )[0] : '',
			'ID'                => $data[0] ? (int) explode( '-', $data[0] )[1] : '',
			'deal_type'         => get_term_by( 'name', $data[1], 'deal_type' )->term_id,
			'offer_category'    => $offer_cat_id,
			'offer_type'        => $offer_type_id,
			'gallery'           => explode( ',', $data[3] ),
			'county'            => $data[4],
			'borough'           => $data[5],
			'city'              => trim( $data[6] ),
			'street'            => $data[7],
			'house_number'      => $data[8],
			'adFormDescr'       => $data[9],
			'price'             => str_replace( ' ', '', $data[10] ),
			'square'            => $data[11],
			'living_area'       => $data[12],
			'kitchen_area'      => $data[13],
			'land_area'         => $data[14],
			'count_rooms'       => get_term_by( 'name', $data[15], 'count_rooms' )->term_id,
			'count_bathrooms'   => get_term_by( 'name', $data[16], 'count_bathrooms' )->term_id,
//			'floor'              => $data[16],
			'floors'            => $data[17],
//			'repair'            => get_term_by( 'name', $data[18], 'repair' )->term_id,
			'sewerage'          => get_term_by( 'name', $data[19], 'sewerage' )->term_id,
			'window_type'       => get_term_by( 'name', $data[20], 'window_type' )->term_id,
			'heating'           => get_term_by( 'name', $data[21], 'heating' )->term_id,
			'wall_type'         => get_term_by( 'name', $data[22], 'wall_type' )->term_id,
			'year_built'        => $data[23],
			'ceiling_height'    => $data[24],
			'distance'          => $data[25],
			'plumbing'          => get_term_by( 'name', $data[26], 'plumbing' )->term_id,
			'elite_real_estate' => ( $data[27] == 'да' ) ? 1 : 0,
			'metro'             => self::get_metro_ids( $data[28] ),
			'ifs'               => $offer_cat_id ? self::get_ifs_ids( $data[29], $offer_cat_id ) : '',
			'ifs_string'        => $data[29],
			'parking'           => get_term_by( 'name', $data[30], 'parking' )->term_id,
			'property_classes'  => get_term_by( 'name', $data[28], 'property_classes' )->term_id,
			'type_payment'      => get_term_by( 'name', $data[29], 'type_payment' )->term_id,
			'type_proposition'  => get_term_by( 'name', $data[30], 'type_proposition' )->term_id,
			'comfort'           => get_term_by( 'name', $data[31], 'comfort' )->term_id,
		);

		return $offer_data;
	}

	public static function prepare_commercial_real_estate_data( $data ) {
		$offer_cat = get_term_by( 'slug', 'kommercheskoj-nedvizhimosti', 'offer_category' );

		$offer_data = array(
			'blog_id'          => $data[0] ? (int) explode( '-', $data[0] )[0] : '',
			'ID'               => $data[0] ? (int) explode( '-', $data[0] )[1] : '',
			'deal_type'        => get_term_by( 'name', $data[1], 'deal_type' )->term_id,
			'offer_category'   => $offer_cat->term_id,
			'offer_type'       => get_term_by( 'name', trim( $data[2] ), 'offer_type' )->term_id,
			'gallery'          => explode( ',', $data[3] ),
			'county'           => $data[4],
			'borough'          => $data[5],
			'city'             => trim( $data[6] ),
			'street'           => $data[7],
			'house_number'     => $data[8],
			'adFormDescr'      => $data[9],
			'price'            => str_replace( ' ', '', $data[10] ),
			'square'           => $data[11],
			'available_square' => $data[12],
			'land_area'        => $data[13],
			'count_rooms'      => get_term_by( 'name', $data[14], 'count_rooms' )->term_id,
			'lodge_class'      => get_term_by( 'name', $data[15], 'lodge_class' )->term_id,
			'floor'            => $data[16],
			'floors'           => $data[17],
			'building_type'    => get_term_by( 'name', $data[18], 'building_type' )->term_id,
			'repair'           => get_term_by( 'name', $data[19], 'repair' )->term_id,
			'sewerage'         => get_term_by( 'name', $data[20], 'severage' )->term_id,
			'window_type'      => get_term_by( 'name', $data[21], 'window_type' )->term_id,
			'code'             => get_term_by( 'name', $data[22], 'code' )->term_id,
			'heating'          => get_term_by( 'name', $data[23], 'heating' )->term_id,
			'wall_type'        => get_term_by( 'name', $data[24], 'wall_type' )->term_id,
			'year_built'       => $data[25],
			'ceiling_height'   => $data[26],
			'metro'            => $data[27],
			'distance'         => $data[28],
			'ifs'              => self::get_ifs_ids( $data[29], $offer_cat->term_id ),
//			'kitchen'            => get_term_by( 'name', $data[29], 'kitchen' )->term_id,
			'parking'          => get_term_by( 'name', $data[30], 'parking' )->term_id,
		);

		return $offer_data;
	}

	public static function prepare_land_plot_data( $data ) {
		$offer_cat = get_term_by( 'slug', 'zemelnyh-uchastkov', 'offer_category' );

		$offer_data = array(
			'blog_id'          => $data[0] ? (int) explode( '-', $data[0] )[0] : '',
			'ID'               => $data[0] ? (int) explode( '-', $data[0] )[1] : '',
			'deal_type'        => get_term_by( 'name', $data[1], 'deal_type' )->term_id,
			'offer_category'   => $offer_cat->term_id,
			'offer_type'       => get_term_by( 'name', trim( $data[2] ), 'offer_type' )->term_id,
			'gallery'          => explode( ',', $data[3] ),
			'county'           => $data[4],
			'borough'          => $data[5],
			'city'             => trim( $data[6] ),
			'street'           => $data[7],
			'house_number'     => $data[8],
			'adFormDescr'      => $data[9],
			'price'            => str_replace( ' ', '', $data[10] ),
			'square'           => $data[11],
			//		'land_plot_area'     => '',
			'cadastral_number' => $data[12],
			'intended_purpose' => get_term_by( 'name', $data[13], 'intended_purpose' )->term_id,
			'location'         => get_term_by( 'name', $data[14], 'location' )->term_id,
			'distance'         => $data[15],
			'ifs'              => self::get_ifs_ids( $data[16], $offer_cat->term_id ),
			'electricity'      => get_term_by( 'name', $data[17], 'electricity' )->term_id,
			'gasmain'          => get_term_by( 'name', $data[18], 'gasmain' )->term_id,
			'plumbing'         => get_term_by( 'name', $data[19], 'plumbing' )->term_id,
			'sewerage'         => get_term_by( 'name', $data[20], 'sewerage' )->term_id,
			'type_proposition' => get_term_by( 'name', $data[30], 'type_proposition' )->term_id,
			'comfort'          => get_term_by( 'name', $data[31], 'comfort' )->term_id,
		);

		return $offer_data;
	}

	public static function upload_image( $image_src, $post_id, $count = 1 ) {
		$attachment_id = false;

		$file         = trim( $image_src );
		$filename     = basename( $file );
		$file_content = file_get_contents( $file );
		if ( $file_content === false ) {
			return false;
		}
		$upload_file = wp_upload_bits( $filename, null, $file_content );
		if ( ! $upload_file['error'] ) {
			$filename      = basename( $upload_file['url'] );
			$wp_filetype   = wp_check_filetype( $filename, null );
			$attachment    = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_parent'    => ( $count == 1 ) ? $post_id : 0,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);
			$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $post_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
			}
		}

		return $attachment_id;
	}

	public static function get_ifs_ids( $ifs_data = '', $offer_cat_id = null ) {
		$ifs = array();
		if ( ! empty( $ifs_data ) ) {
			$ifs_ids = array();
			$ifs     = explode( ',', $ifs_data );
			foreach ( $ifs as $ifs_item ) {
				$name = trim( $ifs_item );
				$term = get_term_by( 'name', $name, 'ifs' );

				if ( empty( $term ) || is_wp_error( $term ) ) {
					$term_id = wp_insert_term( strip_tags( $name ), 'ifs' );
					if ( ! empty( $term_id ) && ! is_wp_error( $term_id ) && $offer_cat_id ) {
						update_term_meta( $term_id['term_id'], 'deco_ifs_cat_' . $offer_cat_id, sanitize_text_field( $offer_cat_id ) );
					}
				} else {
					$term_id['term_id'] = $term->term_id;
				}

				if ( ! empty( $term_id ) && ! is_wp_error( $term_id ) ) {
					$ifs_ids[] = $term_id['term_id'];
				}
			}
			$ifs = $ifs_ids;
//			$ifs = implode( ',', $ifs_ids );
		}

		return $ifs;
	}

	public static function get_metro_ids( $metro_data = '' ) {
		$metro_ids = array();
		if ( ! empty( $metro_data ) ) {
			$metro = explode( ',', $metro_data );
			foreach ( $metro as $item ) {
				$name = trim( $item );
				$term = get_term_by( 'name', $name, 'metro' );

				if ( empty( $term ) || is_wp_error( $term ) ) {
					$term_id = wp_insert_term( strip_tags( $name ), 'metro' );
				} else {
					$term_id['term_id'] = $term->term_id;
				}

				if ( ! empty( $term_id ) && ! is_wp_error( $term_id ) ) {
					$metro_ids[] = $term_id['term_id'];
				}
			}
		}

		return $metro_ids;
	}

	public static function is_valid_csv_file( $offer_category_id, $data ) {
		$is_valid       = false;
		$offer_category = get_term_by( 'term_id', $offer_category_id, 'offer_category' );

		$apartment_fields = array(
			'ID',
			'Тип сделки',
			'Уточнение типа',
			'Фотографии',
			'Область',
			'Район',
			'Город/населенный пункт (село, пгт)',
			'Улица',
			'Номер дома',
			'Описание',
			'Цена обьекта ($)',
			'Общая площадь (м2)',
			'Жилая площадь (м2)',
			'Площадь кухни (м2)',
			'Количество комнат',
			'Количество С/У',
			'Этаж',
			'Этажей в доме',
			'Ремонт',
			'Вторичный рынок / Новостройка',
			'Отопление',
			'Структура стен',
			'Год постройки',
			'Высота потолков (м)',
			'Элитная недвижимость',
			'Метро',
			'Удобства и коммуникации',
			'Паркинг',
			'Класс жилья',
			'Тип оплаты',
			'Тип предложения',
			'Удобства',
		);

		$house_fields = array();

		$commercial_real_estate_fields = array(
			'ID',
			'Тип сделки',
			'Уточнение типа',
			'Фотографии',
			'Область',
			'Район',
			'Город/населенный пункт (село, пгт)',
			'Улица',
			'Номер дома',
			'Описание',
			'Цена обьекта (грн)',
			'Общая площадь (м2)',
			'Доступная площадь (м2)',
			'Площадь участка (м2)',
			'Количество комнат',
			'Класс помещения',
			'Этаж',
			'Этажей в доме',
			'Тип здания',
			'Ремонт',
			'Канализация',
			'Тип окон',
			'Вторичный рынок / Новостройка',
			'Отопление',
			'Структура стен',
			'Год постройки',
			'Высота потолков (м)',
			'Метро',
			'Удаленность от города (км)',
			'Удобства и коммуникации',
			'Наличие кухни',
			'Паркинг',
		);

		$land_plots_fields = array(
			'ID',
			'Тип сделки',
			'Уточнение типа',
			'Фотографии',
			'Область',
			'Район',
			'Город/населенный пункт (село, пгт)',
			'Улица',
			'Номер дома',
			'Описание',
			'Цена обьекта (грн)',
			'Площадь участка (соток)',
			'Кадастровый номер',
			'Целевое назначение',
			'Локация',
			'Удаленность от города (км)',
			'Удобства и коммуникации',
			'Электричество',
			'Газопровод',
			'Водопровод',
			'Канализация',
		);

		$garage_parking_fields = array();

		if ( $offer_category->slug == 'kvartir' ) {
			$is_valid = $data === $apartment_fields;
		} elseif ( $offer_category->slug == 'domov' ) {
			$is_valid = $data === $house_fields;
		} elseif ( $offer_category->slug == 'kommercheskoj-nedvizhimosti' ) {
			$is_valid = $data === $commercial_real_estate_fields;
		} elseif ( $offer_category->slug == 'zemelnyh-uchastkov' ) {
			$is_valid = $data === $land_plots_fields;
		} elseif ( $offer_category->slug == 'garazhej-i-parkingov' ) {
			$is_valid = $data === $garage_parking_fields;
		}

		return array( 'data' => $data, 'fields' => $apartment_fields );
	}

	public static function str_to_utf8( $str ) {

		if ( mb_detect_encoding( $str, 'UTF-8', true ) === false ) {
			$str = mb_convert_encoding( $str, 'UTF-8', 'CP-1251' );
		}

		return $str;
	}
}

WP_CLI::add_command( 'realia_offers', 'Realia_Offers' );