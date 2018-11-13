<?php

namespace Deco\Bundles\Offers\Modules\Export_Offers_Xml\Includes;

class Mitula {

	public static function init() {

		add_action( 'deco_export_xml_mitula', __CLASS__ . '::export_xml_mitula' );

	}

	public static function export_xml_mitula() {

		add_filter( 'locale', function () {
			return 'ru_UA';
		} );

		$count_args = array(
			'post_type'      => 'offers',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		);

		$count_query  = new \WP_Query( $count_args );
		$offers_count = $count_query->found_posts;

		$uploads_dir = wp_upload_dir();
		$base_dir    = $uploads_dir['basedir'];
		file_put_contents( $base_dir . '/export_xml/mitula.xml', '' );

		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->startDocument( '1.0', 'UTF-8' );
		$xmlWriter->startElement( 'Mitula' );

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
					$data_xml = self::get_offer_data_for_xml_mitula( $offer );
					unset( $offer );

					$xmlWriter->startElement( 'ad' );
					foreach ( $data_xml as $key => $value ) {
						if ( $value ) {
							if ( $key === 'pictures' ) {
								$pictures = $value;
								if ( ! empty( $pictures ) ) {
									$xmlWriter->startElement( 'pictures' );
									foreach ( $pictures as $picture ) {
										if ( $picture['picture_url'] ) {
											$xmlWriter->startElement( 'picture' );
											$xmlWriter->startElement( 'picture_url' );
											$xmlWriter->writeCdata( $picture['picture_url'] );
											$xmlWriter->endElement();
											if ( $picture['picture_title'] ) {
												$xmlWriter->startElement( 'picture_title' );
												$xmlWriter->writeCdata( $picture['picture_title'] );
												$xmlWriter->endElement();
											}
											$xmlWriter->endElement(); // picture
										}
									}
									$xmlWriter->endElement(); // pictures
								}
							} else {

								$xmlWriter->startElement( $key );
								$xmlWriter->writeCdata( $value );
								$xmlWriter->endElement();

							}
						}
					}
					$xmlWriter->endElement(); // ad

					unset( $data_xml );
				}
				unset( $offers );
				wp_cache_flush();

				file_put_contents( $base_dir . '/export_xml/mitula.xml', $xmlWriter->flush( true ), FILE_APPEND );
			}
		}

		$xmlWriter->endElement(); // Mitula
		$xmlWriter->endDocument();
		file_put_contents( $base_dir . '/export_xml/mitula.xml', $xmlWriter->flush( true ), FILE_APPEND );
		$xmlWriter->flush();

		// Compressing
		$fp_out = gzopen( $base_dir . '/export_xml/mitula.xml.gz', 'w9' );
		$fp_in  = fopen( $base_dir . '/export_xml/mitula.xml', 'rb' );
		while ( ! feof( $fp_in ) ) {
			gzwrite( $fp_out, fread( $fp_in, 1024 * 512 ) );
		}
		fclose( $fp_in );
		gzclose( $fp_out );
	}

	private static function get_offer_data_for_xml_mitula( $offer ) {
		global $wpdb;

		$region_col            = $wpdb->get_col( "SELECT title_ru FROM wp_address_region WHERE id = '$offer->address_region_id'" );
		$district_region_col   = $wpdb->get_col( "SELECT title_ru FROM wp_address_district_region WHERE id = '$offer->address_district_region_id'" );
		$locality_col          = $wpdb->get_col( "SELECT title_ru FROM wp_address_locality WHERE id = '$offer->address_locality_id'" );
		$district_locality_col = $wpdb->get_col( "SELECT title_ru FROM wp_address_district_locality WHERE id = '$offer->address_district_locality_id'" );
		$street_col            = $wpdb->get_col( "SELECT title_ru FROM wp_address_street WHERE id = '$offer->address_street_id'" );

		if ( ! empty( $region_col ) && $offer->address_locality_id !== '2' ) {
			$region = $region_col[0];
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
			$type = $deal_type[0]->name;
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

		$count_rooms = $offer->terms( 'count_rooms' );
		if ( $count_rooms ) {
			$rooms = $count_rooms[0]->name;
		}

		$count_bathrooms = $offer->terms( 'count_bathrooms' );
		if ( $count_bathrooms ) {
			$bathrooms = $count_bathrooms[0]->name;
		}

		$type_proposition = $offer->terms( 'type_proposition' );
		if ( $type_proposition ) {
			foreach ( $type_proposition as $term ) {
				if ( $term->term_id === 2372 ) {
					$is_owner = '1';
					break;
				}
			}
		}

		$repair = $offer->terms( 'repair' );
		if ( $repair ) {
			$condition = $repair[0]->name;
		}

		$parking = $offer->terms( 'parking' );
		if ( $parking ) {
			$parking = '1';
		} else {
			$parking = '0';
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

		$pictures = array();

		if ( ! empty( $offer->deco_offer_photo_list ) ) {
			foreach ( $offer->deco_offer_photo_list as $id => $url ) {
				$image_title = get_the_title( $id );
				if ( ! empty( $image_title ) ) {
					if ( false !== strpos( '.jpg', $image_title ) ||
					     false !== strpos( '.jpeg', $image_title ) ||
					     false !== strpos( '.png', $image_title ) ) {
						$picture['picture_title'] = $image_title;
					}
				}
				$picture['picture_url'] = $url;
				$pictures[]             = $picture;
			}
		}

		$offer_users   = $offer->deco_users_ids;
		$offer_user    = $offer_users[0];
		$offer_user_id = $offer_user['id'];

		$author      = get_userdata( $offer_user_id );
		$author_role = $author->wp_capabilities;

		if ( array_key_exists( 'agency', $author_role ) ) {
			$agency_name = $author->display_name;
		}

		$date            = date( 'd/m/Y', strtotime( $offer->post_date ) );
		$time            = date( 'H:i', strtotime( $offer->post_date ) );
		$expiration_date = date( 'd/m/Y', strtotime( '+1 month', strtotime( $offer->post_date ) ) );

		if ( $offer->deco_offer_without_furniture === 'on' ) {
			$is_furnished = '0';
		}

		$data_for_XML = array(
			'id'              => $offer->ID,
			'url'             => $offer->get_permalink(),
			'title'           => qtranxf_use( 'ru', $offer->post_title ) . ' - ' . $address,
			'content'         => qtranxf_use( 'ru', $offer->post_content ),
			'type'            => $type,
			'property_type'   => $property_type,
			'floor_area'      => (float) $offer->deco_offer_square,
			'city'            => $locality,
			'city_area'       => $district_locality,
			'region'          => $region,
			'rooms'           => $rooms,
			'bathrooms'       => $bathrooms,
			'latitude'        => $offer->address_latitude,
			'longitude'       => $offer->address_longitude,
			'price'           => $offer->deco_offer_price,
			'address'         => $address,
			'pictures'        => $pictures,
			'parking'         => $parking,
			'agency'          => $agency_name,
			'date'            => $date,
			'time'            => $time,
			'floor_number'    => $offer->deco_offer_floor,
			'is_furnished'    => $is_furnished,
			'expiration_date' => $expiration_date,
			'condition'       => $condition,
			'year'            => $offer->deco_offer_year_built,
			'by_owner'        => $is_owner,
		);

		$data_for_XML = array_filter( $data_for_XML );

		return $data_for_XML;

	}

}