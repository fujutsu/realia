<?php

namespace Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type;

class Lun_UA extends \Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml {

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
				'xml_standard'    => 'Lun_UA',
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

		$xml_data = $xml_data['announcements']['announcement'];

		$count = count( $xml_data );
		$wpdb->query( "update wp_deco_xml_data_offers set status = 'delete' where user_id = $user_id and sync_id = $sync_id" );

		$wpdb->query( "update wp_deco_xml_list set count = $count where user_id = $user_id and id = $sync_id" );

		foreach ( $xml_data as $key => $item ) {

			$i ++;
			$data_update_insert        = array();
			$format_data_update_insert = array();

			$internal_id = '';

			if ( isset( $item['@attributes']['id'] ) ) {
				$internal_id = $item['@attributes']['id'];
			}

			if ( empty( $internal_id ) && ! empty( $item['url'] ) ) {
				$internal_id = crc32( $item['url'] );
			}

			if ( $internal_id ) {
				$internal_id_crc32 = crc32( $internal_id );
			}

			if ( ! $internal_id ) {
				self::wp_cli_log( "Import xml: $i of $count | 'internal_id' is null" );
				unset( $xml_data[ $key ] );
				continue;
			}

			$last_update_date = isset( $item['update_time'] ) ? strtotime( $item['update_time'] ) : current_time( 'timestamp' );

			$updated_post = false;
			$post         = $wpdb->get_row( "select last_update_date from wp_deco_xml_data_offers where internal_id = '$internal_id' and user_id = $user_id and sync_id = $sync_id" );
			if ( $post ) {

				if ( $last_update_date > strtotime( $post->last_update_date ) ) {
					$updated_post = true;
				} else {
					/*
					unset( $xml_data[ $key ] );
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
					continue;
					*/
				}

			}

			if ( $internal_id ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'internal_id', $internal_id );
				self::set_value( $data_update_insert, $format_data_update_insert, 'internal_id_crc32', $internal_id_crc32 );
			}

			if ( isset( $item['contract_type'] ) ) {
				$item['contract_type'] = trim( $item['contract_type'] );
				if ( empty( $item['contract_type'] ) ) {
					$mem = memory_get_peak_usage();
					self::wp_cli_log( "Import xml EMPTY Type: $i of $count | mem - $mem" );
					unset( $xml_data[ $key ] );
					continue;
				}

				self::set_value( $data_update_insert, $format_data_update_insert, 'type', $item['contract_type'] );
			}


			if ( isset( $item['realty_type'] ) ) {
				if ( $feed_type === 'commercial_real_estate' ) {
					$item['realty_type'] = $feed_type;
				} else {
					$item['realty_type'] = trim( $item['realty_type'] );
					if ( empty( $item['realty_type'] ) ) {
						$mem = memory_get_peak_usage();
						self::wp_cli_log( "Import xml EMPTY Category: $i of $count | mem - $mem" );
						unset( $xml_data[ $key ] );
						continue;
					}
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'category', $item['realty_type'] );
			}

			if ( isset( $item['url'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'url', $item['url'] );
			}

			if ( isset( $item['add_time'] ) ) {
				$date = date( 'Y-m-d H:i:s', strtotime( $item['add_time'] ) );
				self::set_value( $data_update_insert, $format_data_update_insert, 'creation_date', $date );
			}

			if ( isset( $item['update_time'] ) ) {
				$date = date( 'Y-m-d H:i:s', $last_update_date );

				self::set_value( $data_update_insert, $format_data_update_insert, 'last_update_date', $date );
			}


			if ( isset( $item['region'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_region', $item['region'] );
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

			if ( isset( $item['house'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_house', $item['house'] );
			}


			if ( isset( $item['price'] ) ) {

				$price = trim( $item['price'] );

				if ( $price == '1' ) {
					self::wp_cli_log( "Import xml PRICE = 1: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				} else if ( empty( $price ) ) {
					self::wp_cli_log( "Import xml EMPTY PRICE: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				}

				self::set_value( $data_update_insert, $format_data_update_insert, 'price', $item['price'] );
			}

			if ( isset( $item['currency'] ) ) {
				self::set_name_currency( $data_update_insert, $format_data_update_insert, 'currency', $item['currency'] );
			}


			$item['images']['image'] = array_filter( $item['images']['image'] );
			$item['images']['image'] = array_unique( $item['images']['image'] );

			if ( count( $item['images']['image'] ) > 0 ) {
				if ( count( $item['images']['image'] ) === 1 ) {
					$images_str = $item['images']['image'][0];
				} else {
					$images_str = implode( ',', $item['images']['image'] );
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'image', $images_str );

			} else {
				self::wp_cli_log( "Import xml EMPTY GALLERY: $i of $count" );
				unset( $xml_data[ $key ] );
				continue;
			}

			if ( isset( $item['text'] ) ) {
				if ( isset( $item['title'] ) ) {
					$title        = $item['title'];
					$item['text'] = $title . "\r" . $item['text'];
				}

				self::set_value( $data_update_insert, $format_data_update_insert, 'description', $item['text'] );
			}

			if ( isset( $item['total_area'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'area', $item['total_area'] );
			}

			if ( isset( $item['room_count'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'rooms', $item['room_count'] );
			}

			if ( isset( $item['floor'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'floor', $item['floor'] );
			}

			if ( isset( $item['floor_count'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'floors_total', $item['floor_count'] );
			}

			if ( isset( $item['living_area'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'living_area', $item['living_area'] );
			}

			if ( isset( $item['kitchen_area'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'kitchen_area', $item['kitchen_area'] );
			}

			if ( isset( $item['has_balcony'] ) ) {
				if ( $item['has_balcony'] === true ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'balcony', 1 );
				} else {
					self::set_value( $data_update_insert, $format_data_update_insert, 'balcony', (int) $item['has_balcony'] );
				}

			}


//print_r($data_update_insert);
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

	public static function sync_start( $user_id, $sync_id ) {
		global $wpdb;
		$syn_per_records = 10;

		$where = "user_id = $user_id and status in ('not_need_update','update','new') and sync_id = $sync_id";
		$where .= " and post_id = 0";

//		$limit = "limit 50";
//		$offers_count = $wpdb->get_var( "select count(*) from wp_deco_xml_data_offers where $where $limit" );

		$offers_count = $wpdb->get_var( "select count(*) from wp_deco_xml_data_offers where $where $limit" );

		$sync_i     = 0;
		$sync_pages = floor( $offers_count / $syn_per_records );

		$data_for_sync = apply_filters( 'deco_offers_get_terms', array() );
		$data_for_sync = self::get_custom_params( $data_for_sync );

		// Разбили синхрона на части что бы не нагружать сервер
		for ( $page = 0; $page <= $sync_pages; $page ++ ) {
			$offset = $page * $syn_per_records;

			$offers = $wpdb->get_results( "select * from wp_deco_xml_data_offers where $where limit $offset,$syn_per_records" );
			self::sync_pagination( $offers, $user_id, $sync_id, $page, $sync_pages, $sync_i, $offers_count, $data_for_sync );
//			if ( $sync_i >= 4 ) {
//				return;
//			}
		}

	}

}