<?php

namespace Deco\Bundles\Offers\Modules\Offers_Xml\Includes;

class Xml {

	static $max_la = 20;
	static $max_threads = 10;

	protected static $table_fields = array(
		'internal_id'                => '%s',
		'internal_id_crc32'          => '%d',
		'type'                       => '%s',
		'property_type'              => '%s',
		'category'                   => '%s',
		'url'                        => '%s',
		'creation_date'              => '%s',
		'last_update_date'           => '%s',
		'expire_date'                => '%s',
		// custom params
		'est_amount_of_bathrooms'    => '%s',
		'est_near_residence'         => '%s',
		'est_headline'               => '%s',
		'est_agent_contract_type'    => '%s',
		'est_agent_contract_awards'  => '%s',
		'est_agent_contract_from'    => '%s',
		'est_agent_contract_till'    => '%s',
		'est_type_of_flat'           => '%s',
		'quality'                    => '%s',
		'balcony'                    => '%s',
		'lift'                       => '%s',
		'payed_adv'                  => '%s',
		'manually_added'             => '%s',
		'location_country'           => '%s',
		'location_region'            => '%s',
		'location_locality_name'     => '%s',
		'location_district_locality' => '%s',
		'location_address'           => '%s',
		'location_latitude'          => '%s',
		'location_longitude'         => '%s',
		'sales_agent_name'           => '%s',
		'sales_agent_phone'          => '%s',
		'sales_agent_category'       => '%s',
		'sales_agent_url'            => '%s',
		'price'                      => '%s',
		'currency'                   => '%s',
		'image'                      => '%s',
		'description'                => '%s',
		'area'                       => '%s',
		'rooms'                      => '%s',
		'floor'                      => '%s',
		'floors_total'               => '%s',
		'living_area'                => '%s',
		'kitchen_area'               => '%s',
		'ceiling_height'             => '%s',
		'is_new'                     => '%s',
		'status'                     => '%d',
		'hash'                       => '%s',
	);

	protected static function objectsIntoArray( $arrObjData, $arrSkipIndices = array() ) {
		$arrData = array();

		// if input is object, convert into array
		if ( is_object( $arrObjData ) ) {
			$arrObjData = get_object_vars( $arrObjData );
		}

		if ( is_array( $arrObjData ) ) {
			foreach ( $arrObjData as $index => $value ) {
				if ( is_object( $value ) || is_array( $value ) ) {
					$value = self::objectsIntoArray( $value, $arrSkipIndices ); // recursive call
				}
				if ( in_array( $index, $arrSkipIndices ) ) {
					continue;
				}
				$arrData[ $index ] = $value;
			}
		}

		return $arrData;
	}

	// конкатенація значень всіх тегів в строку для її хешування  (3-вимірний масив)
	public static function hash_offer( $offer ) {

		$excluded_tags = array( 'creation-date', 'last-update-date', 'expire-date' );
		$concat        = '';

		foreach ( $offer as $tag => $value ) {

			if ( in_array( $tag, $excluded_tags, true ) ) {
				continue;
			}

			if ( is_array( $value ) ) {

				foreach ( $value as $value2 ) {

					if ( is_array( $value2 ) ) {

						foreach ( $value2 as $value3 ) {
							$concat .= $value3;
						}
					} else {
						$concat .= $value2;
					}
				}
			} else {
				$concat .= $value;
			}
		}

		$offer_hash = hash( 'md5', $concat );

		return $offer_hash;
	}


	protected static function qtrans_return( $needle_lang = '', $string = '' ) {
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
						$found[ $current_language ]  = true;
						$current_language            = false;
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

	protected static function prepare_apartment_data( $data ) {

		$repair_term_id = self::get_repair( $data['quality'] );

		$offer_data = array(
			'internal_id'                => $data['internal_id'],
			'blog_id'                    => $data['blog_id'],
			'ID'                         => $data['post_id'],
			'deal_type'                  => $data['deal_type'],
			'offer_category'             => $data['offer_category'],
			'offer_type'                 => $data['offer_type'],
			'location_latitude'          => $data['location_latitude'],
			'location_longitude'         => $data['location_longitude'],
			'location_region'            => $data['location_region'],
			'location_locality_name'     => $data['location_locality_name'],
			'location_district_locality' => $data['location_district_locality'],
			'location_address'           => $data['location_address'],
			'image'                      => $data['image'],
			'content'                    => $data['content'],
			'price'                      => $data['price'],
			'currency'                   => $data['currency'],
			'rate_currency'              => $data['rate_currency'],
			'square'                     => $data['area'],
			'living_area'                => $data['living_area'],
			'kitchen_area'               => $data['kitchen_area'],
			'count_balconies'            => get_term_by( 'slug', $data['count_balconies'], 'count_balconies' )->term_id,
			'lift'                       => get_term_by( 'slug', $data['lift'], 'ifs' )->term_id,
			'parking'                    => get_term_by( 'slug', $data['parking'], 'parking' )->term_id,
			'sea'                        => get_term_by( 'slug', $data['u-morya'], 'comfort' )->term_id,
			'count_bathrooms'            => get_term_by( 'slug', $data['est_amount_of_bathrooms'], 'count_bathrooms' )->term_id,
			'count_rooms'                => get_term_by( 'slug', $data['count_rooms'], 'count_rooms' )->term_id,
			'bathroom_connected'         => get_term_by( 'slug', $data['bathroom_connected'], 'bathroom' )->term_id,
			'floor'                      => $data['floor'],
			'floors'                     => $data['floors'],
			'repair'                     => $repair_term_id,
			'code'                       => '',
			'heating'                    => '',
			'wall_type'                  => '',
			'year_built'                 => '',
			'ceiling_height'             => '',
			'elite_real_estate'          => 0,
			'metro'                      => '',
			'ifs'                        => '',
			'sales_agent_name'           => $data['sales_agent_name'],
			'sales_agent_phone'          => $data['sales_agent_phone'],
			'payed_adv'                  => $data['payed_adv']
		);

		return $offer_data;
	}

	protected static function prepare_house_data( $data ) {

		$repair_term_id = self::get_repair( $data['quality'] );

		$offer_data = array(
			'internal_id'                => $data['internal_id'],
			'blog_id'                    => $data['blog_id'],
			'ID'                         => $data['post_id'],
			'deal_type'                  => $data['deal_type'],
			'offer_category'             => $data['offer_category'],
			'offer_type'                 => $data['offer_type'],
			'location_latitude'          => $data['location_latitude'],
			'location_longitude'         => $data['location_longitude'],
			'location_region'            => $data['location_region'],
			'location_locality_name'     => $data['location_locality_name'],
			'location_district_locality' => $data['location_district_locality'],
			'location_address'           => $data['location_address'],
			'image'                      => $data['image'],
			'content'                    => $data['content'],
			'price'                      => $data['price'],
			'currency'                   => $data['currency'],
			'rate_currency'              => $data['rate_currency'],
			'square'                     => $data['area'],
			'living_area'                => $data['living_area'],
			'kitchen_area'               => $data['kitchen_area'],
			'count_balconies'            => get_term_by( 'slug', $data['count_balconies'], 'count_balconies' )->term_id,
			'parking'                    => get_term_by( 'slug', $data['parking'], 'parking' )->term_id,
			'sea'                        => get_term_by( 'slug', $data['u-morya'], 'comfort' )->term_id,
			'count_bathrooms'            => get_term_by( 'slug', $data['est_amount_of_bathrooms'], 'count_bathrooms' )->term_id,
			'count_rooms'                => get_term_by( 'slug', $data['count_rooms'], 'count_rooms' )->term_id,
			'bathroom_connected'         => get_term_by( 'slug', $data['bathroom_connected'], 'bathroom' )->term_id,
			'land_area'                  => '',
			'floors'                     => $data['floors'],
			'repair'                     => $repair_term_id,
			'sewerage'                   => '',
			'window_type'                => '',
			'heating'                    => '',
			'wall_type'                  => '',
			'year_built'                 => '',
			'ceiling_height'             => '',
			'distance'                   => '',
			'plumbing'                   => '',
			'elite_real_estate'          => 0,
			'metro'                      => '',
			'ifs'                        => '',
			'ifs_string'                 => '',
			'sales_agent_name'           => $data['sales_agent_name'],
			'sales_agent_phone'          => $data['sales_agent_phone'],
			'payed_adv'                  => $data['payed_adv']
		);

		return $offer_data;
	}

	protected static function prepare_commercial_real_estate_data( $data ) {

		$repair_term_id = self::get_repair( $data['quality'] );

		$offer_data = array(
			'internal_id'                => $data['internal_id'],
			'blog_id'                    => $data['blog_id'],
			'ID'                         => $data['post_id'],
			'deal_type'                  => $data['deal_type'],
			'offer_category'             => $data['offer_category'],
			'offer_type'                 => $data['offer_type'],
			'location_latitude'          => $data['location_latitude'],
			'location_longitude'         => $data['location_longitude'],
			'location_region'            => $data['location_region'],
			'location_locality_name'     => $data['location_locality_name'],
			'location_district_locality' => $data['location_district_locality'],
			'location_address'           => $data['location_address'],
			'image'                      => $data['image'],
			'content'                    => $data['content'],
			'price'                      => $data['price'],
			'currency'                   => $data['currency'],
			'rate_currency'              => $data['rate_currency'],
			'square'                     => $data['area'],
			'available_square'           => '',
			'land_area'                  => '',
			'count_rooms'                => get_term_by( 'name', $data['count_rooms'], 'count_rooms' )->term_id,
			'lodge_class'                => '',
			'floor'                      => $data['floor'],
			'floors'                     => $data['floors'],
			'building_type'              => '',
			'repair'                     => $repair_term_id,
			'sewerage'                   => '',
			'window_type'                => '',
			'code'                       => '',
			'heating'                    => '',
			'wall_type'                  => '',
			'year_built'                 => '',
			'ceiling_height'             => '',
			'metro'                      => '',
			'distance'                   => '',
			'ifs'                        => '',
			'parking'                    => '',
			'sales_agent_name'           => $data['sales_agent_name'],
			'sales_agent_phone'          => $data['sales_agent_phone'],
			'payed_adv'                  => $data['payed_adv']
		);

		return $offer_data;
	}

	protected static function prepare_land_plot_data( $data ) {
		$offer_data = array(
			'internal_id'                => $data['internal_id'],
			'internal_id_crc32'          => $data['internal_id_crc32'],
			'blog_id'                    => $data['blog_id'],
			'ID'                         => $data['post_id'],
			'deal_type'                  => $data['deal_type'],
			'offer_category'             => $data['offer_category'],
			'offer_type'                 => isset( $data['offer_type'] ) ? $data['offer_type'] : 0,
			'location_latitude'          => $data['location_latitude'],
			'location_longitude'         => $data['location_longitude'],
			'location_region'            => $data['location_region'],
			'location_locality_name'     => $data['location_locality_name'],
			'location_district_locality' => $data['location_district_locality'],
			'location_address'           => $data['location_address'],
			'image'                      => $data['image'],
			'content'                    => $data['content'],
			'price'                      => $data['price'],
			'currency'                   => $data['currency'],
			'rate_currency'              => $data['rate_currency'],
			'square'                     => $data['area'],
			//		'land_plot_area'     => '',
			'cadastral_number'           => '',
			'intended_purpose'           => '',
			'distance'                   => '',
			'ifs'                        => '',
			'electricity'                => '',
			'gasmain'                    => '',
			'plumbing'                   => '',
			'sewerage'                   => '',
			'sales_agent_name'           => $data['sales_agent_name'],
			'sales_agent_phone'          => $data['sales_agent_phone'],
			'payed_adv'                  => $data['payed_adv']
		);

		return $offer_data;
	}


	protected static function get_repair( $repair_name ) {
		$repair_term_id = 0;
		if ( ! empty( $repair_name ) ) {
			$repair = get_term_by( 'name', $repair_name, 'repair' );
			if ( $repair && ! is_wp_error( $repair ) ) {
				$repair_term_id = $repair->term_id;
			} else {
				$repair = wp_insert_term( $repair_name, 'repair' );
				if ( $repair && ! is_wp_error( $repair ) ) {
					$repair_term_id = $repair['term_id'];
				}
			}

		}

		return $repair_term_id;
	}

	public static function wp_cli_log( $text ) {
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::log( $text );
		}

		$file_path = ABSPATH . 'logs/log.txt';

		$time    = date( 'Y-m-d H:i:s' );
		$content = file_get_contents( $file_path, 'r' );
		$content .= $time . ': ' . $text . "\n";

		file_put_contents( $file_path, $content );
	}

	protected static function set_value( &$data_update_insert, &$format_data_update_insert, $field, $value ) {
		$field                        = trim( $field );
		$data_update_insert[ $field ] = trim( $value );
		$format_data_update_insert[]  = self::$table_fields[ $field ];
	}

	protected static function get_custom_params( $data_for_sync ) {
		global $wpdb;

		// Курс валют для преобразования UAH и EUR в USD
		$currency_all = $wpdb->get_results( "select code, rate from {$wpdb->base_prefix}currency" );
		foreach ( $currency_all as $item ) {
			$data_for_sync['rate_currency'][ $item->code ] = $item->rate;
		}


		return $data_for_sync;
	}

	protected static function set_name_currency( &$data_update_insert, &$format_data_update_insert, $field, $value ) {

		$convert_currency = array(
			'$'   => 'USD',
			'грн' => 'UAH',
			'€'   => 'EUR'
		);

		$value = strtr( $value, $convert_currency );

		$field                        = trim( $field );
		$data_update_insert[ $field ] = trim( $value );
		$format_data_update_insert[]  = self::$table_fields[ $field ];
	}


	private static function recalc_price( $data ) {
		$price = empty( $data['price'] ) ? '' : $data['price'];
//		self::wp_cli_log( print_r( $data, true ) );

		if ( $price ) {
			switch ( $data['currency'] ) {
				case 'EUR':
					$price = round( $price * $data['rate_currency']['eur'], 0 );
					$price = round( $price / $data['rate_currency']['usd'], 0 );
					break;
				case 'UAH':
					$price = round( $price / $data['rate_currency']['usd'] );
					break;
			}

		}

		return $price;
	}

	protected static function sync_pagination( $offers, $user_id, $sync_id, $page, $sync_pages, &$sync_i, $sync_count, $data_for_sync ) {

		$option_qtranslate_term_name = $data_for_sync['option_qtranslate_term_name'];

		foreach ( $offers as $item ) {
			$sync_i ++;

			$data = array(
				'xml_data_id'                => $item->id,
				'internal_id'                => $item->internal_id,
				'internal_id_crc32'          => $item->internal_id_crc32,
				'ID'                         => $item->post_id,
				'image'                      => $item->image,
				'blog_id'                    => $item->blog_id,
				'price'                      => $item->price,
				'currency'                   => $item->currency,
				'rate_currency'              => $data_for_sync['rate_currency'],
				'area'                       => $item->area,
				'living_area'                => $item->living_area,
				'kitchen_area'               => $item->kitchen_area,
				'floor'                      => $item->floor,
				'floors'                     => $item->floors_total,
				'count_rooms'                => (int) $item->rooms,
				'est_amount_of_bathrooms'    => (int) $item->est_amount_of_bathrooms,
				'location_latitude'          => $item->location_latitude,
				'location_longitude'         => $item->location_longitude,
				'quality'                    => $item->quality,
				'location_region'            => $item->location_region,
				'location_locality_name'     => $item->location_locality_name,
				'location_district_locality' => $item->location_district_locality,
				'location_address'           => $item->location_address,
				'sales_agent_name'           => $item->sales_agent_name,
				'sales_agent_phone'          => $item->sales_agent_phone,
				'payed_adv'                  => $item->payed_adv
			);

			if ( ! empty( $item->description ) ) {
				$data['content'] = $item->description;
			} else if ( ! empty( $item->est_headline ) ) {
				$data['content'] = $item->est_headline;
			}

			if ( in_array( mb_strtolower( $item->type ), array( 'продажа', 'Продажа', 'продаж' ) ) ) {
				$data['deal_type'] = $data_for_sync['type_sell_term'];
			} else {
				$data['deal_type'] = $data_for_sync['type_rent_term'];
			}
			$data = apply_filters( 'get_offer_category_and_offer_type_by_terms', $item->category, $data_for_sync, $data );

			self::save( $data, $option_qtranslate_term_name, $user_id, $sync_id );

			$mem = memory_get_peak_usage();
			self::wp_cli_log( "Sync offers: $sync_i of $sync_count | page " . ( $page + 1 ) . " of " . ( $sync_pages + 1 ) . " | mem - $mem    | time: " . date( 'H:i:s' ) );
		}

	}

	protected static function save( $data = array(), $option_qtranslate_term_name, $user_id = 0, $sync_id ) {
		global $wpdb;
		$post_id     = $data['ID'];
		$xml_data_id = $data['xml_data_id'];

		$prefix       = 'deco_offer_';
		$yoast_prefix = '_yoast_wpseo_primary_';

		$offer_category = $data['offer_category'];
		$address        = $data['address'];

		if ( $offer_category->slug === 'kvartir' ) {
			$data = self::prepare_apartment_data( $data );
		} elseif ( $offer_category->slug === 'domov' ) {
			$data = self::prepare_house_data( $data );
		} elseif ( $offer_category->slug === 'kommercheskoj-nedvizhimosti' ) {
			$data = self::prepare_commercial_real_estate_data( $data );
		} elseif ( $offer_category->slug === 'zemelnyh-uchastkov' ) {
			$data = self::prepare_land_plot_data( $data );
		}
//		elseif ( $offer_category->slug == 'garazhej-i-parkingov' ) {
//			$data = self::prepare_garage_parking_data( $data );
//		}

		$type_term = $data['deal_type'];

		$offer_cat_name_ru = mb_strtolower( $option_qtranslate_term_name[ $offer_category->name ]['ru'] );
		$offer_cat_name_ua = mb_strtolower( $option_qtranslate_term_name[ $offer_category->name ]['ua'] );

		$title = '[:ru]' . $option_qtranslate_term_name[ $type_term->name ]['ru'] . ' ' . $offer_cat_name_ru . '[:ua]' . $option_qtranslate_term_name[ $type_term->name ]['ua'] . ' ' . $offer_cat_name_ua . '[:]';

		$post_status = 'draft';


		$data['content'] = trim( str_replace( $data['internal_id'], ' ', $data['content'] ) );
		$post_data       = array(
			'post_title'    => $title,
			'post_content'  => $data['content'],
			'post_author'   => $user_id,
			'post_status'   => $post_status,
			'post_date'     => current_time( 'mysql' ),
			'post_modified' => current_time( 'mysql' ),
			'post_type'     => 'offers',
			'meta_input'    => array(
				'message_not_send' => 1
			)
		);

		if ( isset( $data['content'] ) ) {
			$post_data['post_content'] = $data['content'];
		} else {
			$post_data['post_content'] = '<dvi></dvi>';
		}

		remove_all_actions( 'save_post', 10 );
		add_filter( 'deco_offers_moderated_notification_disable', function ( $disable ) {
			return true;
		}, 10 );

		$update_post = false;
		if ( $post_id ) {
			$update_post = true;
			echo 'update';
			$post_data['ID'] = $post_id;
			wp_update_post( $post_data );

			$wpdb->update(
				'wp_deco_xml_data_offers',
				array(
					'status' => 'updated'
				),
				array(
					'post_id' => $post_id,
				),
				array( '%s' ),
				array( '%d' )
			);

			$update = $wpdb->update(
				'wp_deco_xml_data_offermeta',
				array(
					'post_id' => $post_id
				),
				array(
					'data_offer_id' => $xml_data_id
				)
			);


		} else {
			echo 'create' . PHP_EOL;
			$post_id = wp_insert_post( $post_data );

			$wpdb->update(
				'wp_deco_xml_data_offers',
				array(
					'post_id' => $post_id,
					'status'  => 'created'
				),
				array(
					'internal_id' => $data['internal_id'],
					'sync_id'     => $sync_id
				)
			);

			$update = $wpdb->update(
				'wp_deco_xml_data_offermeta',
				array(
					'post_id' => $post_id
				),
				array(
					'data_offer_id' => $xml_data_id
				)
			);

		}


		if ( ! $post_id ) {
			return;
		}
		do_action( 'deco_offers_sync_gallery', $post_id, $data['image'] );

		if ( ! get_post_meta( $post_id, 'deco_offer_photo_list', true ) ) {
			wp_delete_post( $post_id, true );
			$wpdb->delete(
				'wp_deco_xml_data_offers',
				array(
					'id' => $xml_data_id
				)
			);

			$wpdb->delete(
				'wp_deco_xml_data_offermeta',
				array(
					'data_offer_id' => $xml_data_id
				)
			);

			return;
		}


		/* Taxonomies */
		if ( $data['deal_type'] ) {
			wp_set_post_terms( $post_id, array( $data['deal_type']->term_id ), 'deal_type' );
			update_post_meta( $post_id, $yoast_prefix . 'deal_type', $data['deal_type']->term_id );
		}

		if ( $data['offer_category'] ) {
			wp_set_post_terms( $post_id, array( $data['offer_category']->term_id ), 'offer_category' );
			update_post_meta( $post_id, $yoast_prefix . 'offer_category', $data['offer_category']->term_id );

		}


		if ( $data['offer_type'] ) {
			wp_set_post_terms( $post_id, array( $data['offer_type']->term_id ), 'offer_type' );
			update_post_meta( $post_id, $yoast_prefix . 'offer_type', $data['offer_type']->term_id );
		}

		if ( ! $update_post ) {

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
				update_post_meta( $post_id, $yoast_prefix . 'ifs', $data['ifs'][0] );
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

			if ( $data['electricity'] ) {
				wp_set_post_terms( $post_id, array( $data['electricity'] ), 'electricity' );
				update_post_meta( $post_id, $yoast_prefix . 'electricity', $data['electricity'] );
			}

			if ( $data['gasmain'] ) {
				wp_set_post_terms( $post_id, array( $data['gasmain'] ), 'gasmain' );
				update_post_meta( $post_id, $yoast_prefix . 'gasmain', $data['gasmain'] );
			}

			// ====== NEW
			if ( $data['count_balconies'] ) {
				wp_set_post_terms( $post_id, array( $data['count_balconies'] ), 'count_balconies' );
				update_post_meta( $post_id, $yoast_prefix . 'count_balconies', $data['count_balconies'] );
			}

			if ( $data['lift'] ) {
				wp_set_post_terms( $post_id, array( $data['lift'] ), 'ifs' );
				update_post_meta( $post_id, $yoast_prefix . 'lift', $data['lift'] );
			}

			if ( $data['parking'] ) {
				wp_set_post_terms( $post_id, array( $data['parking'] ), 'parking' );
				update_post_meta( $post_id, $yoast_prefix . 'parking', $data['parking'] );
			}

			if ( $data['sea'] ) {
				wp_set_post_terms( $post_id, array( $data['sea'] ), 'comfort' );
				update_post_meta( $post_id, $yoast_prefix . 'sea', $data['sea'] );
			}

			if ( $data['bathroom_connected'] ) {
				wp_set_post_terms( $post_id, array( $data['bathroom_connected'] ), 'bathroom' );
				update_post_meta( $post_id, $yoast_prefix . 'bathroom_connected', $data['bathroom_connected'] );
			}

			if ( $data['payed_adv'] ) {
				$payed_adv_info = maybe_unserialize( $data['payed_adv'] );

				if ( $payed_adv_info['paid_service'] === 'realia_hot' ) {
					$product_id   = '633';
					$product_slug = 'promotion_highlight_in_color';
				} elseif ( $payed_adv_info['paid_service'] === 'realia_top' ) {
					$product_id   = '632';
					$product_slug = 'promotion_top';
				}

				$insert_data = array(
					'active'         => 1,
					'post_id'        => $post_id,
					'blog_id'        => 1,
					'user_id'        => 0,
					'user_role'      => '',
					'product_id'     => $product_id,
					'product_slug'   => $product_slug,
					'order_id'       => 0,
					'order_item_id'  => 0,
					'order_price'    => 0,
					'datetime'       => $payed_adv_info['date_end'],
					'deal_type'      => $data['deal_type']->slug,
					'offer_category' => $data['offer_category']->slug,
				);

				$insert_data_type = array(
					'%d',
					'%d',
					'%d',
					'%d',
					'%s',
					'%d',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s',
				);


				if ( $product_slug === 'promotion_top' ) {
					$insert_data['filling_percentage'] = apply_filters( 'deco_offers_percentage_of_fill_data', $post_id, '1' );
					$insert_data_type[]                = '%d';
				}

				$wpdb->insert(
					$wpdb->base_prefix . 'deco_users_offers_promotion',
					$insert_data,
					$insert_data_type
				);
			}

			/** Определяем метро рядом */
			do_action( 'deco_address_metro_near_set', array(
				'post_id' => $post_id,
				'lat'     => $data['location_latitude'],
				'lng'     => $data['location_longitude'],
			) );
			/** ======================== */

			/* End Taxonomies */


			/** Address */
			// Coords
			// сохранить адрес
			do_action( 'deco_get_address_by_string_address', array(
				'post_id'                => $post_id,
				'region_name'            => $data['location_region'],
				'locality_name'          => $data['location_locality_name'],
				'district_locality_name' => $data['location_district_locality'],
				'street_name'            => $data['location_address'],
			) );

			do_action( 'deco_set_coords_by_address_line', array(
				'post_id'           => $post_id,
				'address_line'      => $data['location_region'] . ', ' . $data['location_locality_name'] . ', ' . $data['location_address'],
				'address_latitude'  => $data['location_latitude'],
				'address_longitude' => $data['location_longitude'],
			) );
			/** END Address */


			/* Metas */

			$price = self::recalc_price( $data );

			update_post_meta( $post_id, $prefix . 'price', $price );

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

			if ( ! $update_post && ( ! $user_id || $user_id === '0' ) ) {
				update_post_meta( $post_id, 'deco_offer_sales_agent_name', $data['sales_agent_name'] );
				if ( $data['sales_agent_phone'] ) {
					$phones         = $data['sales_agent_phone'];
					$phones_to_meta = explode( ',', $phones );
					update_post_meta( $post_id, 'deco_offer_sales_agent_phones', $phones_to_meta );
				}
			} elseif ( ! $update_post && $user_id ) {
				update_post_meta( $post_id, 'deco_offer_added_user_id', $user_id );

				$user_id = get_post_meta( $post_id, 'deco_offer_added_user_id', true );

				$cost = str_replace( ',', '.', $data['price'] );
				if ( is_float( $cost ) ) {
					$cost = floatval( $cost );
				} else {
					$cost = (int) $cost;
				}


				$user_data = new \stdClass();

				$user_data->id   = $user_id;
				$user_data->cost = $cost;

				$user_ids[] = $user_data;

				$deco_users_ids = json_encode( $user_ids );

				$agency_id = (int) get_user_meta( $user_id, 'deco_agency_term_id', true );
				$wpdb->insert(
					$wpdb->base_prefix . 'offers_agents',
					array(
						'post_id'   => $post_id,
						'user_id'   => $user_id,
						'price'     => $cost,
						'blog_id'   => 1,
						'agency_id' => $agency_id,
						'active'    => 1
					)
				);
				update_post_meta( $post_id, 'deco_users_ids', $deco_users_ids );
			}


//		/* End Metas */

			// Если нет категории или Тип сделки, пост ставим в Ожидание подтверждения
			if ( $data['offer_category'] === false || $data['offer_type'] === false ) {
				wp_update_post( array(
					'ID'          => $post_id,
					'post_status' => 'pending'
				) );
			} else {
				wp_update_post( array(
					'ID'          => $post_id,
					'post_status' => 'publish'
				) );
			}

		} else {

			$price = self::recalc_price( $data );
			update_post_meta( $post_id, $prefix . 'price', $price );

		}
//		Post_Type_Offers::save_offer( $post_id );

		do_action( 'deco_offers_custom_save_post', $post_id );

		// Удалим метку что бы не отправлялись письма при импорте
		delete_post_meta( $post_id, 'message_not_send' );

//		die();
	}


	/** Многопоточная синхронизация */
	public static function sync_pagination_thread( $id, $user_id, $sync_id ) {
		global $wpdb;

		self::wp_cli_log( "  Sync offer start: offer_id $id | sync_id $sync_id" );
		$data_for_sync = apply_filters( 'deco_offers_get_terms', array() );
		$data_for_sync = self::get_custom_params( $data_for_sync );

		$option_qtranslate_term_name = $data_for_sync['option_qtranslate_term_name'];

		$item = $wpdb->get_row( "select * from wp_deco_xml_data_offers where id = $id limit 1" );


		$data = array(
			'xml_data_id'                => $item->id,
			'internal_id'                => $item->internal_id,
			'ID'                         => $item->post_id,
			'image'                      => $item->image,
			'blog_id'                    => $item->blog_id,
			'price'                      => $item->price,
			'currency'                   => $item->currency,
			'rate_currency'              => $data_for_sync['rate_currency'],
			'area'                       => $item->area,
			'living_area'                => $item->living_area,
			'kitchen_area'               => $item->kitchen_area,
			'floor'                      => $item->floor,
			'floors'                     => $item->floors_total,
			'location_latitude'          => $item->location_latitude,
			'location_longitude'         => $item->location_longitude,
			'quality'                    => $item->quality,
			'location_region'            => $item->location_region,
			'location_locality_name'     => $item->location_locality_name,
			'location_district_locality' => $item->location_district_locality,
			'location_address'           => $item->location_address,
			'est_amount_of_bathrooms'    => ( $item->est_amount_of_bathrooms > 3 ) ? '4' : $item->est_amount_of_bathrooms,
			'count_balconies'            => ( $item->balcony > 3 ) ? '4-i-bolshe' : $item->balcony,
			'lift'                       => ( $item->lift ) ? 'lift' : '',
			'ceiling_height'             => $item->ceiling_height,
			'count_rooms'                => ( $item->rooms > 3 ) ? '4' : $item->rooms,
			'sales_agent_name'           => $item->sales_agent_name,
			'sales_agent_phone'          => $item->sales_agent_phone,
			'payed_adv'                  => $item->payed_adv
		);

		if ( ! empty( $item->description ) ) {
			$data['content'] = $item->description;
		} else if ( ! empty( $item->est_headline ) ) {
			$data['content'] = $item->est_headline;
		}

		if ( mb_strtolower( $item->type ) === 'продажа' or mb_strtolower( $item->type ) === 'продаж' ) {
			$data['deal_type'] = $data_for_sync['type_sell_term'];
		} elseif ( mb_strtolower( $item->type ) === 'аренда' ) {
			$data['deal_type'] = $data_for_sync['type_rent_term'];
		}

		if ( strpos( $item->est_amount_of_bathrooms, 'совмещен' ) !== false ) {
			$data['bathroom_connected'] = 'sovmeshhennyj';
		}


		$est_near_residence = explode( ', ', $item->est_near_residence );

		if ( isset( $est_near_residence['стоянка'] ) ) {
			$data['parking'] = 'gostevoj';
		}
		if ( isset( $est_near_residence['море'] ) ) {
			$data['sea'] = 'u-morya';
		}

		$data = apply_filters( 'get_offer_category_and_offer_type_by_terms', $item->category, $data_for_sync, $data );

		self::save( $data, $option_qtranslate_term_name, $user_id, $sync_id );

		// якшо останій пост від силки, то процесінг... = '' і процес оновлення закінчено
		$last_thread = $wpdb->get_row( "SELECT id FROM wp_deco_xml_data_offers WHERE NOT status = 'delete' AND user_id = $user_id AND sync_id = $sync_id AND post_id = 0" );

		exec( 'pgrep -f sync_start_thread_item', $pids ); // получить количество потоков синхронизации и проверить является ли поток последним
		$threads_count = count( $pids );

		// если существет только один последний поток sync_start_thread_item, то есть от функции pgrep (а другой проверочный)
		if ( ! $last_thread && $threads_count == 2 ) {
			$time_end        = current_time( 'timestamp' );
			$time_end_format = date( 'Y-m-d H:i:s', $time_end );
			$wpdb->query( "update wp_deco_xml_list set finish_sync = '$time_end_format', status = 'finished_sync' where user_id = $user_id and id = $sync_id" );

			$sync_finished = date( "Y-m-d H:i:s" );

			$wpdb->query( "UPDATE wp_deco_xml_list_meta SET sync_finished = '$sync_finished' WHERE sync_id = $sync_id" );

			// XML log
			$log_data = $wpdb->get_row( "SELECT * FROM wp_deco_xml_list WHERE id = '$sync_id' AND user_id = '$user_id'" );
			$log_id   = $wpdb->get_var( "SELECT id FROM wp_deco_xml_logger WHERE sync_id = '$sync_id' AND user_id = '$user_id' AND start_sync = '$log_data->start_sync'" );

			$posts_moderation = $wpdb->get_var( "SELECT COUNT(post_status) FROM wp_posts WHERE ID IN(SELECT post_id FROM wp_deco_xml_data_offers WHERE post_id > 0 AND sync_id = $sync_id) AND post_status in ('pending', 'address_check', 'draft')" );
			$posts_publish    = $wpdb->get_var( "SELECT COUNT(post_status) FROM wp_posts WHERE ID IN(SELECT post_id FROM wp_deco_xml_data_offers WHERE post_id > 0 AND sync_id = $sync_id) AND post_status = 'publish' " );

			$wpdb->update( 'wp_deco_xml_logger',
				array(
					'finish_sync' => $sync_finished,
					'xml_count'   => $log_data->count,
					'published'   => $posts_publish,
					'moderated'   => $posts_moderation,
					'new'         => $log_data->created_counts,
					'deleted'     => $log_data->removed_counts,
				),
				array(
					'id' => $log_id
				),
				array(
					'%s',
					'%d',
					'%d',
					'%d',
					'%d',
					'%d'
				),
				array(
					'%d'
				)
			);
			// End XML log

			$wpdb->query( "DELETE FROM wp_deco_xml_queue_sync WHERE sync_id = $sync_id" );
			self::wp_cli_log( "Sync xml end: sync_id $sync_id" );
		}
		self::wp_cli_log( "  Sync offer end: offer_id $id | sync_id $sync_id" );
	}

	public static function sync_start_threads( $user_id, $sync_id ) {
		global $wpdb;

		$syn_per_records = 50;

		$where = "user_id = $user_id and status in ('update','new') and sync_id = $sync_id";
//		$where = "user_id = $user_id and status in ('not_need_update','update','new') and sync_id = $sync_id";
		$where .= " and post_id = 0";

		$sql = "select COUNT(*) from wp_deco_xml_data_offers where $where";

		$offers_count = $wpdb->get_var( $sql );

		// якшо останій пост від силки, то процесінг... = '' і процес оновлення закінчено
		$item = $wpdb->get_row( "SELECT id FROM wp_deco_xml_data_offers WHERE NOT status = 'delete' AND user_id = $user_id AND sync_id = $sync_id AND post_id = 0" );

		if ( $offers_count == 0 ) {
			$time_end        = current_time( 'timestamp' );
			$time_end_format = date( 'Y-m-d H:i:s', $time_end );
			$wpdb->query( "update wp_deco_xml_list set finish_sync = '$time_end_format', status = 'finished_sync' where user_id = $user_id and id = $sync_id" );

			$sync_finished = date( "Y-m-d H:i:s" );
			$wpdb->query( "UPDATE wp_deco_xml_list_meta SET sync_finished = '$sync_finished' WHERE sync_id = $sync_id" );

			// XML log
			$log_data = $wpdb->get_row( "SELECT * FROM wp_deco_xml_list WHERE id = '$sync_id' AND user_id = '$user_id'" );
			$log_id   = $wpdb->get_var( "SELECT id FROM wp_deco_xml_logger WHERE sync_id = '$sync_id' AND user_id = '$user_id' AND start_sync = '$log_data->start_sync'" );

			$posts_moderation = $wpdb->get_var( "SELECT COUNT(post_status) FROM wp_posts WHERE ID IN(SELECT post_id FROM wp_deco_xml_data_offers WHERE post_id > 0 AND sync_id = $sync_id) AND post_status in ('pending', 'address_check', 'draft')" );
			$posts_publish    = $wpdb->get_var( "SELECT COUNT(post_status) FROM wp_posts WHERE ID IN(SELECT post_id FROM wp_deco_xml_data_offers WHERE post_id > 0 AND sync_id = $sync_id) AND post_status = 'publish' " );

			$wpdb->update( 'wp_deco_xml_logger',
				array(
					'finish_sync' => $sync_finished,
					'xml_count'   => $log_data->count,
					'published'   => $posts_publish,
					'moderated'   => $posts_moderation,
					'new'         => $log_data->created_counts,
					'deleted'     => $log_data->removed_counts,
				),
				array(
					'id' => $log_id
				),
				array(
					'%s',
					'%d',
					'%d',
					'%d',
					'%d',
					'%d'
				),
				array(
					'%d'
				)
			);
			// End XML log

			$wpdb->query( "DELETE FROM wp_deco_xml_queue_sync WHERE sync_id = $sync_id" );
			self::wp_cli_log( "Sync xml end: sync_id $sync_id" );

			return;
		}

		$current_path = ABSPATH;
		$sync_i       = 0;
		$sync_pages   = floor( $offers_count / $syn_per_records );
		$start_time   = current_time( 'mysql' );
		$time_start   = current_time( 'timestamp' );

		$start_sync = date( 'Y-m-d H:i:s' );

		self::wp_cli_log( "Sync xml start: sync_id $sync_id | offers_count $offers_count | syn_per_records $syn_per_records | sync_pages $sync_pages" );

		// Разбили синхрона на части что бы не нагружать сервер
		for ( $page = 0; $page <= $sync_pages; $page ++ ) {

			$offers = $wpdb->get_results( "select * from wp_deco_xml_data_offers where $where limit $syn_per_records" );

			foreach ( $offers as $item ) {

				self::balance_loadavg_import( $sync_i );

				$sync_i ++;

				$load = sys_getloadavg();

				$command = "cd $current_path && wp realia_xml sync_start_thread_item --id={$item->id} --user_id={$item->user_id} --sync_id={$item->sync_id} --allow-root";
				passthru( "( $command & ) >> /dev/null 2>&1" );

				$time_end    = current_time( 'timestamp' );
				$time_during = round( abs( $time_start - $time_end ) / 60, 2 );

				$mem = memory_get_peak_usage();

				self::wp_cli_log( "Sync offer info: offer_id {$item->id} | $sync_i of $offers_count | page $page of $sync_pages | start time: $start_time | Time during (min): $time_during | mem - $mem | LA: {$load[0]} (1 min)" );
			}

			// если завершились все потоки очередного $page, то пускать следующий
			do {
				unset( $pids );
				exec( 'pgrep -f sync_start_thread_item', $pids ); // получить количество потоков синхронизации и проверить является ли поток последним
				$threads_count = count( $pids );

				// если загрузочные потоки закончились, а остался только один последний проверочный поток sync_start_thread_item, то есть от функции pgrep
				if ( $threads_count == 1 ) {
					break;
				}
				sleep( 5 );

			} while ( 1 );
		}
	}

	/** ================================================================= */
	protected static function balance_loadavg_import( $sync_i ) {

		// echo 'proc: ' . (int)`ps aux|grep sync_start_thread_item|wc -l` . PHP_EOL;

		$sync_i ++;
		$max_la      = self::$max_la;
		$max_threads = self::$max_threads;

		$load = sys_getloadavg();
		exec( 'pgrep -f sync_start_thread_item', $pids ); // получить количество потоков
		$threads_count = count( $pids );

		if ( $load[0] <= 0.1 ) {
			sleep( 10 );
		} elseif ( $load[0] > 0.1 && $load[0] < $max_la ) {
			sleep( 5 );
		}

		if ( $load[0] >= $max_la || $threads_count >= $max_threads ) {

			do {
				$load = sys_getloadavg();
				unset( $pids );
				exec( 'pgrep -f sync_start_thread_item', $pids ); // получить количество потоков
				$threads_count = count( $pids );

				if ( $load[0] >= $max_la ) {
					self::wp_cli_log( "    Sleep LA: iteration ({$sync_i}) ({$max_la} LA max)- {$load[0]} LA (10 sec)" );
				}
				if ( $threads_count >= $max_threads ) {
					self::wp_cli_log( "    Sleep TC: iteration ({$sync_i}) ({$max_threads} TC max)- {$threads_count} TC (10 sec)" );
				}

				sleep( 10 );

				if ( $load[0] < $max_la && $threads_count < $max_threads ) {
					break;
				}
			} while ( 1 );
		}
	}

	public static function get_xml_data_by_link( $xml_link ) {

		if ( false !== strpos( $xml_link, '.xml.gz' ) ) {

			$file_name = crc32( $xml_link ) . '.xml';

			$file = tempnam( "/tmp", $file_name );

			$content = file_get_contents( $xml_link );


			$uncompressed = gzdecode( $content );
			file_put_contents( $file, $uncompressed );

			if ( file_exists( $file ) ) {
				$xmlObj = simplexml_load_file( $file );
				@unlink( $file );
			} else {
				self::wp_cli_log( "Error XML file data!" );

				return;
			}

		} else {
			$xml = wp_remote_get( $xml_link );

			if ( isset( $xml['body'] ) ) {

				$file_name = crc32( $xml_link ) . '.xml';

				$file = tempnam( "/tmp", $file_name );

				file_put_contents( $file, $xml['body'] );

				if ( file_exists( $file ) ) {
					$xmlObj = simplexml_load_file( $file );
					@unlink( $file );
				} else {
					self::wp_cli_log( "Error XML file data!" );

					return '';
				}
			}

		}

		$xml_data = self::objectsIntoArray( $xmlObj );
		unset( $xmlObj );

		return $xml_data;
	}

}
