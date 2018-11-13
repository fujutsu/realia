<?php

namespace Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type;

use Deco\Bundles\Custom_Post_Types\Includes\Post_Type_Offers;

class Dom_Ria extends \Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml {

	public static function sync_xml( $args ) {
		global $wpdb;
		$user_id   = $args['user_id'];
		$xml_link  = $args['xml_link'];
		$sync_id   = $args['sync_id'];
		$feed_type = $args['feed_type'];

		// Получение xml по разным условиям
		$xml_data = self::get_xml_data_by_link( $xml_link );


		$i       = 0;
		$created = 0;
		$updated = 0;
		$removed = 0;

		$start_sync = date( 'Y-m-d H:i:s' );
		$wpdb->query( "UPDATE wp_deco_xml_list SET start_sync = '$start_sync' WHERE id = $sync_id" );
		$create_datetime = date( 'Y-m-d H:i:s' );

		$log = $wpdb->insert(
			$wpdb->prefix . 'deco_xml_logger',
			array(
				'sync_id'         => $sync_id,
				'user_id'         => $user_id,
				'xml_type'        => $feed_type,
				'xml_standard'    => 'Dom_Ria',
				'start_sync'      => $start_sync,
				'create_datetime' => $create_datetime
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);

		$xml_data = $xml_data['realty'];

//		var_dump( $xml_data[0] );
//		die();

		$count = count( $xml_data );
		$wpdb->query( "update wp_deco_xml_data_offers set status = 'delete' where user_id = $user_id and sync_id = $sync_id" );
		$wpdb->query( "update wp_deco_xml_list set count = $count where user_id = $user_id and id = $sync_id" );

		foreach ( $xml_data as $key => $item ) {

			$i ++;
			$data_update_insert        = array();
			$format_data_update_insert = array();

//			if ( $i > 10 ) {
//				break;
//			}

//			print_r( $item );

			$internal_id = 0;

			if ( isset( $item['local_realty_id'] ) ) {
				$internal_id = $item['local_realty_id'];
			}

			if ( ! $internal_id ) {
				self::wp_cli_log( "Import xml: $i of $count | 'internal_id' is null" );
				unset( $xml_data[ $key ] );
				continue;
			}

			$last_update_date = isset( $item['last-update-date'] ) ? strtotime( $item['last-update-date'] ) : current_time( 'timestamp' );

			$updated_post = false;
			$post         = $wpdb->get_row( "select last_update_date from wp_deco_xml_data_offers where internal_id = '$internal_id' and user_id = $user_id and sync_id = $sync_id" );
			if ( $post ) {

				if ( $last_update_date > strtotime( $post->last_update_date ) ) {
					$updated_post = true;
				} else {
					/*					unset( $xml_data[ $key ] );
										$data_update_insert['status'] = 'not_need_update';
										$format_data_update_insert[]  = '%s';

										$wpdb->update(
											'wp_deco_xml_data_offers',
											$data_update_insert,
											array(
												'internal_id' => $internal_id,
												'user_id'     => $user_id,
												'sync_id'     => $sync_id,
											),
											$format_data_update_insert,
											array( '%s', '%d', '%d' )
										);
										$mem = memory_get_peak_usage();
										self::wp_cli_log( "Import xml: $i of $count | NOT NEED UPDATE | mem: $mem" );
										continue;*/

				}

			}

			if ( $internal_id ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'internal_id', $internal_id );
			}

			if ( isset( $item['advert_type'] ) ) {
				$mem          = memory_get_peak_usage();
				$item['type'] = trim( $item['advert_type'] );

				if ( $item['type'] === 'долгосрочная аренда' ) {
					$item['type'] = 'аренда';
				}

				if ( empty( $item['advert_type'] ) ) {
					self::wp_cli_log( "Import xml EMPTY Type: $i of $count | mem - $mem" );
					unset( $xml_data[ $key ] );
					continue;
				}

				self::set_value( $data_update_insert, $format_data_update_insert, 'type', $item['advert_type'] );
			}

			if ( isset( $item['realty_type'] ) ) {
				$mem = memory_get_peak_usage();
				if ( $feed_type === 'commercial_real_estate' ) {
					$item['realty_type'] = $feed_type;
				} else {
					$item['realty_type'] = trim( $item['realty_type'] );
					if ( empty( $item['realty_type'] ) ) {
						self::wp_cli_log( "Import xml EMPTY Category: $i of $count | mem - $mem" );
						unset( $xml_data[ $key ] );
						continue;
					}
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'category', $item['realty_type'] );
			}

			if ( isset( $item['other_stuff'] ) ) {
				if ( isset( $item['other_stuff']['slug'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'url', $item['other_stuff']['slug'] );
				}

				if ( isset( $item['other_stuff']['date'] ) ) {
					$date = date( 'Y-m-d H:i:s', $item['other_stuff']['date'] );
					self::set_value( $data_update_insert, $format_data_update_insert, 'creation_date', $date );
				}
			}

			if ( isset( $item['last-update-date'] ) ) {
				$date = date( 'Y-m-d H:i:s', $last_update_date );

				self::set_value( $data_update_insert, $format_data_update_insert, 'last_update_date', $date );
			}

			if ( isset( $item['expire-date'] ) ) {
				$date = date( 'Y-m-d H:i:s', strtotime( $item['expire-date'] ) );
				self::set_value( $data_update_insert, $format_data_update_insert, 'expire_date', $date );
			}


			if ( isset( $item['location']['country'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_country', $item['location']['country'] );
			}

			if ( isset( $item['state'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_region', $item['state'] );
			}

			if ( isset( $item['city'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_locality_name', $item['city'] );
			}

			if ( isset( $item['district'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_district_locality', $item['district'] );
			}

			if ( isset( $item['street'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_address', $item['street'] );
			}

			if ( isset( $item['latitude'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_latitude', $item['latitude'] );
			}

			if ( isset( $item['longitude'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_longitude', $item['longitude'] );
			}

			if ( isset( $item['characteristics'] ) ) {

				$price = trim( $item['characteristics']['price'] );

				if ( $price == '1' ) {
					self::wp_cli_log( "Import xml PRICE = 1: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				} else if ( empty( $price ) ) {
					self::wp_cli_log( "Import xml EMPTY PRICE: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				}

				if ( isset( $item['characteristics']['price'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'price', $item['characteristics']['price'] );
				}
				if ( isset( $item['characteristics']['currency'] ) ) {
					self::set_name_currency( $data_update_insert, $format_data_update_insert, 'currency', $item['characteristics']['currency'] );
				}
			}

			if ( isset( $item['photos_urls'] ) ) {
				$item['photos_urls']['loc'] = array_filter( $item['photos_urls']['loc'] );
				$item['photos_urls']['loc'] = array_unique( $item['photos_urls']['loc'] );

				if ( count( $item['photos_urls']['loc'] ) > 0 ) {
					if ( count( $item['photos_urls']['loc'] ) == 1 ) {
						$images_str = $item['photos_urls']['loc'][0];
					} else {
						$images_str = implode( ',', $item['photos_urls']['loc'] );
					}
					self::set_value( $data_update_insert, $format_data_update_insert, 'image', $images_str );
				} else {
					self::wp_cli_log( "Import xml EMPTY GALLERY: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				}
			}

			if ( isset( $item['description'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'description', $item['description'] );
			}

			if ( isset( $item['characteristics'] ) ) {
				if ( isset( $item['characteristics']['total_area'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'area', $item['characteristics']['total_area'] );
				}

				if ( isset( $item['characteristics']['living_area'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'living_area', $item['characteristics']['living_area'] );
				}

				if ( isset( $item['characteristics']['kitchen_area'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'kitchen_area', $item['characteristics']['kitchen_area'] );
				}

				if ( isset( $item['characteristics']['rooms_count'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'rooms', $item['characteristics']['rooms_count'] );
				}

				if ( isset( $item['characteristics']['floor'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'floor', $item['characteristics']['floor'] );
				}

				if ( isset( $item['characteristics']['floors'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'floors_total', $item['characteristics']['floors'] );
				}
			}

//			print_r($data_update_insert);
//			die();

			if ( $post ) {

				$updated ++;
				$data_update_insert['status'] = 'update';
				$format_data_update_insert[]  = '%s';
				$wpdb->update(
					'wp_deco_xml_data_offers',
					$data_update_insert,
					array(
						'internal_id' => $internal_id,
						'user_id'     => $user_id,
						'sync_id'     => $sync_id,
					),
					$format_data_update_insert,
					array( '%s', '%d', '%d' )
				);

				if ( $updated_post ) {

					// Offer meta
					$data_offer_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}deco_xml_data_offers WHERE user_id = '$user_id' AND sync_id = '$sync_id' AND internal_id = '$internal_id'" );
					if ( $data_offer_id ) {
						$custom_fields = $wpdb->get_results( "SELECT xml_field, display_on_site FROM {$wpdb->prefix}deco_xml_mapping_fields WHERE sync_id = '$sync_id' AND user_id ='$user_id'", ARRAY_A );
						if ( ! empty( $custom_fields ) ) {
							foreach ( $custom_fields as $custom_field ) {
								$display_on_site = (int) $custom_field['display_on_site'];
								$custom_field    = $custom_field['xml_field'];
								$meta_key_crc32  = crc32( $custom_field );
								$meta_key_exists = $wpdb->get_var( "SELECT meta_key FROM {$wpdb->prefix}deco_xml_data_offermeta WHERE data_offer_id = '$data_offer_id' AND meta_key_crc32 = '$meta_key_crc32'" );
								if ( is_array( $item[ $custom_field ] ) ) {
									$custom_field_value = maybe_serialize( $item[ $custom_field ] );
								} else {
									$custom_field_value = $item[ $custom_field ];
								}
								if ( crc32( $custom_field_value ) === 0 ) {
									continue;
								}
								if ( $meta_key_exists ) {
									$update = $wpdb->update(
										$wpdb->prefix . 'deco_xml_data_offermeta',
										array(
											'meta_value'       => $custom_field_value,
											'meta_value_crc32' => crc32( $custom_field_value ),
											'display_on_site'  => $display_on_site
										),
										array(
											'data_offer_id'  => $data_offer_id,
											'meta_key_crc32' => crc32( $custom_field ),
										),
										array( '%s', '%d', '%d' )
									);
								} else {
									$insert = $wpdb->insert(
										$wpdb->prefix . 'deco_xml_data_offermeta',
										array(
											'data_offer_id'    => $data_offer_id,
											'post_id'          => 0,
											'meta_key'         => $custom_field,
											'meta_key_crc32'   => crc32( $custom_field ),
											'meta_value'       => $custom_field_value,
											'meta_value_crc32' => crc32( $custom_field_value ),
											'display_on_site'  => $display_on_site
										),
										array( '%d', '%d', '%s', '%d', '%s', '%d', '%d' )
									);
								}
							}
						}
					}

					$wpdb->query( "update wp_deco_xml_list set updated_counts = $updated where user_id = $user_id and id = $sync_id" );
				}
			} else {
				$created ++;
				$data_update_insert['user_id'] = $user_id;
				$format_data_update_insert[]   = '%d';
				$data_update_insert['sync_id'] = $sync_id;
				$format_data_update_insert[]   = '%d';
				$data_update_insert['status']  = 'new';
				$format_data_update_insert[]   = '%s';
				$result                        = $wpdb->insert(
					'wp_deco_xml_data_offers',
					$data_update_insert,
					$format_data_update_insert
				);
//				self::offers_sync( $user_id, $sync_id, $wpdb->insert_id );

				// Offer meta
				$data_offer_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}deco_xml_data_offers WHERE user_id = '$user_id' AND sync_id = '$sync_id' AND internal_id = '$internal_id'" );
				if ( $data_offer_id ) {
					$custom_fields = $wpdb->get_results( "SELECT xml_field, display_on_site FROM {$wpdb->prefix}deco_xml_mapping_fields WHERE sync_id = '$sync_id' AND user_id ='$user_id'", ARRAY_A );
					if ( ! empty( $custom_fields ) ) {
						foreach ( $custom_fields as $custom_field ) {
							$display_on_site = (int) $custom_field['display_on_site'];
							$custom_field    = $custom_field['xml_field'];
							$meta_key_crc32  = crc32( $custom_field );
							$meta_key_exists = $wpdb->get_var( "SELECT meta_key FROM {$wpdb->prefix}deco_xml_data_offermeta WHERE data_offer_id = '$data_offer_id' AND meta_key_crc32 = '$meta_key_crc32'" );
							if ( is_array( $item[ $custom_field ] ) ) {
								$custom_field_value = maybe_serialize( $item[ $custom_field ] );
							} else {
								$custom_field_value = $item[ $custom_field ];
							}
							if ( crc32( $custom_field_value ) === 0 ) {
								continue;
							}
							if ( $meta_key_exists ) {
								$update = $wpdb->update(
									$wpdb->prefix . 'deco_xml_data_offermeta',
									array(
										'meta_value'       => $custom_field_value,
										'meta_value_crc32' => crc32( $custom_field_value ),
										'display_on_site'  => $display_on_site
									),
									array(
										'data_offer_id'  => $data_offer_id,
										'meta_key_crc32' => crc32( $custom_field ),
									),
									array( '%s', '%d', '%d' )
								);
							} else {
								$insert = $wpdb->insert(
									$wpdb->prefix . 'deco_xml_data_offermeta',
									array(
										'data_offer_id'    => $data_offer_id,
										'post_id'          => 0,
										'meta_key'         => $custom_field,
										'meta_key_crc32'   => crc32( $custom_field ),
										'meta_value'       => $custom_field_value,
										'meta_value_crc32' => crc32( $custom_field_value ),
										'display_on_site'  => $display_on_site
									),
									array( '%d', '%d', '%s', '%d', '%s', '%d', '%d' )
								);
							}
						}
					}
				}

				$wpdb->query( "update wp_deco_xml_list set created_counts = $created where user_id = $user_id and id = $sync_id" );

			}

//			print_r( $data_update_insert );
//			print_r( $format_data_update_insert );
//			print_r( $item );
//			die();
			unset( $xml_data[ $key ] );

			$mem = memory_get_peak_usage();
			self::wp_cli_log( "Import xml: $i of $count | created - $created | updated - $updated | mem - $mem" );
		}

		$removed = $wpdb->get_var( "SELECT COUNT(*) FROM wp_deco_xml_data_offers WHERE status = 'delete' AND user_id = $user_id AND sync_id = $sync_id" );

		$wpdb->query( "update wp_deco_xml_list set removed_counts = $removed where user_id = $user_id AND id = $sync_id" );
		$wpdb->query( "update wp_deco_xml_list set updated_counts = $updated where user_id = $user_id and id = $sync_id" );
		$wpdb->query( "update wp_deco_xml_list set created_counts = $created where user_id = $user_id and id = $sync_id" );

		unset( $data_update_insert );
		unset( $format_data_update_insert );

		unset( $xml_data );
	}
}