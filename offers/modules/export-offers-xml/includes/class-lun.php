<?php

namespace Deco\Bundles\Offers\Modules\Export_Offers_Xml\Includes;

class Lun {

	public static function init() {

		add_action( 'deco_export_xml_lun', __CLASS__ . '::export_xml_lun' );

	}

	public static function export_xml_lun() {

		$count_args = array(
			'post_type'      => 'offers',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		);

		$count_query  = new \WP_Query( $count_args );
		$offers_count = $count_query->found_posts;

		$uploads_dir = wp_upload_dir();
		$base_dir    = $uploads_dir['basedir'];
		file_put_contents( $base_dir . '/export_xml/lun.xml', '' );

		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->startDocument( '1.0', 'UTF-8' );
		$xmlWriter->startElement( 'page' );
		$xmlWriter->writeAttribute( 'xmlns', 'http://www.w3.org/2001/XMLSchema-instance' );
		$xmlWriter->writeElement( 'generation_time', date( 'c' ) );
		$xmlWriter->startElement( 'announcements' );

		if ( ! empty( $offers_count ) ) {
			$posts_per_page = 20;
			$pages          = ceil( $offers_count / $posts_per_page );
			$i              = 0;

			if ( ! mkdir( $base_dir . '/export_xml/', 0777, true ) && ! is_dir( $base_dir . '/export_xml/' ) ) {
				mkdir( $base_dir . '/export_xml/', 0777, true );
			}

			for ( $page = 0; $page <= $pages; $page ++ ) {

				$offset = $page * $posts_per_page;

				$args = array(
					'post_type'      => 'offers',
					'post_status'    => 'publish',
					'posts_per_page' => $posts_per_page,
					'offset'         => $offset,
				);

				$offers = \Timber\Timber::get_posts( $args, 'Deco\Entities\Story_Post' );

				foreach ( $offers as $offer ) {
					$i ++;
					$data_xml = self::get_offer_data_for_xml_lun( $offer );
					unset( $offer );

					$xmlWriter->startElement( 'announcement' );
					foreach ( $data_xml as $key => $value ) {
						if ( $value ) {
							if ( $key === 'images' ) {
								$images = $value;
								if ( ! empty( $images ) ) {
									$xmlWriter->startElement( 'images' );
									foreach ( $images as $image ) {
										$xmlWriter->writeElement( 'image', $image );
									}
									$xmlWriter->endElement(); // images
								}
							} else {
								$xmlWriter->writeElement( $key, $value );
							}
						}
					}
					$xmlWriter->endElement(); // announcement

					unset( $data_xml );


				}
				unset( $offers );
				wp_cache_flush();

				file_put_contents( $base_dir . '/export_xml/lun.xml', $xmlWriter->flush( true ), FILE_APPEND );
			}

		}

		$xmlWriter->endElement(); // announcements
		$xmlWriter->endElement(); // page
		$xmlWriter->endDocument();
		file_put_contents( $base_dir . '/export_xml/lun.xml', $xmlWriter->flush( true ), FILE_APPEND );
		$xmlWriter->flush();


		// Compressing
		$fp_out = gzopen( $base_dir . '/export_xml/lun.xml.gz', 'w9' );
		$fp_in  = fopen( $base_dir . '/export_xml/lun.xml', 'rb' );
		while ( ! feof( $fp_in ) ) {
			gzwrite( $fp_out, fread( $fp_in, 1024 * 512 ) );
		}
		fclose( $fp_in );
		gzclose( $fp_out );
	}

	private static function get_offer_data_for_xml_lun( $offer ) {
		global $wpdb;

		$region_col            = $wpdb->get_col( "SELECT title_ru FROM wp_address_region WHERE id = '$offer->address_region_id'" );
		$district_region_col   = $wpdb->get_col( "SELECT title_ru FROM wp_address_district_region WHERE id = '$offer->address_district_region_id'" );
		$locality_col          = $wpdb->get_col( "SELECT title_ru FROM wp_address_locality WHERE id = '$offer->address_locality_id'" );
		$district_locality_col = $wpdb->get_col( "SELECT title_ru FROM wp_address_district_locality WHERE id = '$offer->address_district_locality_id'" );
		$street_col            = $wpdb->get_col( "SELECT title_ru FROM wp_address_street WHERE id = '$offer->address_street_id'" );

		if ( $wpdb->get_row( "select id from {$wpdb->base_prefix}deco_users_offers_promotion where post_id = '$offer->ID' and active = 1 limit 1" ) ) {
			$is_premium = 'true';
		}

		if ( ! empty( $region_col ) && $offer->address_locality_id !== '2' ) {
			$region = $region_col[0];
			$region = str_replace( 'область', '', $region );
		}

		if ( ! empty( $district_region_col ) ) {
			$district_region = $district_locality_col[0];
		}

		if ( ! empty( $locality_col ) ) {
			$locality = $locality_col[0];
		}

		if ( ! empty( $district_locality_col ) ) {
			$district_locality = $district_locality_col[0];
		}

		if ( ! empty( $street_col ) ) {
			$street = $street_col[0];
		}

		$deal_type = $offer->terms( 'deal_type' );
		if ( $deal_type ) {
			$type = qtranxf_use( 'ru', $deal_type[0]->name );
		}

		$offer_category = $offer->terms( 'offer_category' );
		$offer_type     = $offer->terms( 'offer_type' );
		if ( $offer_category ) {
			$offer_category = $offer_category[0];
			$offer_type     = $offer_type[0];
			if ( $offer_category->term_id === 15 ) {
				$property_type = 'квартира';
			} elseif ( $offer_category->term_id === 16 ) {
				if ( $offer_type->term_id === 2087 ) {
					$property_type = 'дом';
				} elseif ( $offer_type->term_id === 89 ) {
					$property_type = 'дача';
				} elseif ( $offer_type->term_id === 101 ) {
					$property_type = 'часть дома';
				} else {
					$property_type = 'дом';
				}
			} elseif ( $offer_category->term_id === 17 ) {
				if ( $offer_type->term_id === 94 ) {
					$property_type = 'объект сферы услуг';
				} elseif ( $offer_type->term_id === 96 ) {
					$property_type = 'отель';
				} elseif ( $offer_type->term_id === 97 ) {
					$property_type = 'офисное здание';
				} elseif ( $offer_type->term_id === 99 ) {
					$property_type = 'офис';
				} elseif ( $offer_type->term_id === 102 ) {
					$property_type = 'производстенное помещение';
				} elseif ( $offer_type->term_id === 105 ) {
					$property_type = 'склад';
				} elseif ( $offer_type->term_id === 106 ) {
					$property_type = 'торговая площадка';
				} else {
					$property_type = 'коммерческая недвижимость';
				}
			} elseif ( $offer_category->term_id === 18 ) {
				if ( $offer_type->term_id === 2392 ) {
					$property_type = 'дачный участок';
				} elseif ( $offer_type->term_id === 2393 ) {
					$property_type = 'участок коммерческого назначения';
				} elseif ( $offer_type->term_id === 2394 ) {
					$property_type = 'участок под жилую застройку';
				} elseif ( $offer_type->term_id === 2395 ) {
					$property_type = 'участок промназначения';
				} elseif ( $offer_type->term_id === 2396 ) {
					$property_type = 'участок рекреационного назначения';
				} elseif ( $offer_type->term_id === 2397 ) {
					$property_type = 'участок рекреационного назначения';
				} else {
					$property_type = 'земельный участок';
				}
			} elseif ( $offer_category->term_id === 19 ) {
				if ( $offer_type->term_id === 88 ) {
					$property_type = 'гараж';
				} elseif ( $offer_type->term_id === 93 ) {
					$property_type = 'парковочное место';
				} else {
					$property_type = 'паркинг';
				}
			}
		}

		$property_type = ucfirst( $property_type );

		$count_rooms = $offer->terms( 'count_rooms' );
		if ( $count_rooms ) {
			$rooms = qtranxf_use( 'ru', $count_rooms[0]->name );
		}

		$type_proposition = $offer->terms( 'type_proposition' );
		if ( $type_proposition ) {
			foreach ( $type_proposition as $term ) {
				if ( $term->term_id === 2372 ) {
					$is_owner = 'true';
					break;
				}
			}
		}

		$parking = $offer->terms( 'parking' );
		if ( $parking ) {
			$parking = 'true';
		}

		$wall_type = $offer->terms( 'wall_type' );
		if ( $wall_type ) {
			$wall_type = $wall_type[0]->name;
		}

		$bathroom = $offer->terms( 'bathroom' );
		if ( $bathroom ) {
			$wc_type = strtolower( $bathroom[0]->name );
		}

		$count_balconies = $offer->terms( 'count_balconies' );
		if ( $count_balconies && ! empty( $count_balconies ) ) {
			$balcony = 'true';
		}

		$heating = $offer->terms( 'heating' );
		if ( $heating ) {
			$heating_system = strtolower( $heating[0]->name );
		}

		$address = '';
		if ( ! empty( $street ) ) {
			$address .= $street . ', ';
		}

		if ( ! empty( $offer->address_house_number ) ) {
			$address .= $offer->address_house_number . ', ';
		}

		if ( ! empty( $district_locality ) ) {
			$address .= $district_locality . ', ';
		}

		if ( ! empty( $locality ) ) {
			$address .= $locality . ', ';
		}

		if ( $offer->address_locality_id !== '2' ) {
			if ( ! empty( $district_region ) ) {
				$address .= $district_region . ', ';
			}
		}

		if ( ! empty( $region ) ) {
			$address .= $region . ', ';
		}

		$address = substr( $address, 0, - 2 );

		$images = array();

		if ( ! empty( $offer->deco_offer_photo_list ) ) {
			foreach ( $offer->deco_offer_photo_list as $url ) {
				$images[] = $url;
			}
		}

		$offer_users   = $offer->deco_users_ids;
		$offer_user    = $offer_users[0];
		$offer_user_id = $offer_user['id'];

		$author       = get_userdata( $offer_user_id );
		$contact_name = $author->display_name;

		$offices = get_user_meta( $offer_user_id );
		$phones  = '';
		foreach ( $offices as $office ) {
			if ( ! empty( $office['phone'] ) ) {
				$phones .= $office['phone'] . ', ';
			}
		}

		$phones = substr( $phones, 0, - 2 );

		$data_for_XML = array(
			'add_time'       => date( 'c', strtotime( $offer->post_date ) ),
			'update_time'    => date( 'c', strtotime( $offer->post_modified ) ),
			'contract_type'  => $type,
			'realty_type'    => $property_type,
			'region'         => $region,
			'rajon'          => $district_region,
			'city'           => $locality,
			'district'       => $district_locality,
			'street'         => $street,
			'house'          => $offer->address_house_number,
			'room_count'     => $rooms,
			'floor'          => $offer->deco_offer_floor,
			'floor_count'    => $offer->deco_offer_floors,
			'living_area'    => $offer->deco_offer_living_area,
			'kitchen_area'   => $offer->deco_offer_kitchen_area,
			'price'          => $offer->deco_offer_price,
			'currency'       => '$',
			'wall_type'      => $wall_type,
			'ceiling_height' => $offer->deco_offer_ceiling_height,
			'is_owner'       => $is_owner,
			'built_year'     => $offer->deco_offer_year_built,
			'wc_type'        => $wc_type,
			'has_parking'    => $parking,
			'is_premium'     => $is_premium,
			'heating_system' => $heating_system,
			'has_balcony'    => $balcony,
			'title'          => htmlspecialchars( qtranxf_use( 'ru', $offer->post_title ) . ' - ' . $address ),
			'text'           => htmlspecialchars( qtranxf_use( 'ru', $offer->post_content ) ),
			'phones'         => $phones,
			'contact_name'   => $contact_name,
			'url_uk'         => str_replace( 'realia.ua', 'realia.ua/ua', $offer->get_permalink() ),
			'url'            => $offer->get_permalink(),
			'images'         => $images,
		);

		if ( $offer_category->term_id === 18 ) {
			$data_for_XML['land_area'] = $offer->deco_offer_square;
		} else {
			$data_for_XML['total_area'] = $offer->deco_offer_square;
		}

		$data_for_XML = array_filter( $data_for_XML );

		return $data_for_XML;

	}

}