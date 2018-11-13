<?php

namespace Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type;

class Aspo_Biz extends \Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml {

	public static function sync_xml( $args ) {

		global $wpdb;

		$user_id   = 0;
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
				'xml_standard'    => 'Aspo_Biz',
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

		$date = $xml_data['@attributes']['date'];
		$date = date( 'Y-m-d H:i:s', strtotime( $date ) );
		$wpdb->query( "update wp_deco_xml_list set last_update = '$date' where user_id = '$user_id' and id = '$sync_id'" );

		$xml_data = $xml_data['realty'];

		$count = count( $xml_data );
		$wpdb->query( "update wp_deco_xml_data_offers set status = 'delete' where user_id = '$user_id' and sync_id = '$sync_id'" );

		$wpdb->query( "update wp_deco_xml_list set count = '$count' where user_id = '$user_id' and id = '$sync_id'" );

		foreach ( $xml_data as $key => $item ) {

			$i ++;
			$data_update_insert        = array();
			$format_data_update_insert = array();

			$internal_id = '';

			if ( isset( $item['id'] ) ) {
				$internal_id = $item['id'];
			}

			if ( $internal_id ) {
				$internal_id_crc32 = crc32( $internal_id );
			}

			if ( ! $internal_id ) {
				self::wp_cli_log( "Import xml: $i of $count | 'internal_id' is null" );
				unset( $xml_data[ $key ] );
				continue;
			}

			$last_update_date = isset( $item['date'] ) ? strtotime( $item['date'] ) : current_time( 'timestamp' );

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

			if ( isset( $item['date'] ) ) {
				$date = date( 'Y-m-d H:i:s', $last_update_date );

				self::set_value( $data_update_insert, $format_data_update_insert, 'creation_date', $date );
				self::set_value( $data_update_insert, $format_data_update_insert, 'last_update_date', $date );
			}

			if ( isset( $item['typeofrealtydetail'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'category', $item['typeofrealtydetail'] );
			} elseif ( ! isset( $item['typeofrealtydetail'] ) && isset( $item['typeofrealty'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'category', $item['typeofrealty'] );
			}

			$item['photos']['photo'] = array_filter( $item['photos']['photo'] );
			$item['photos']['photo'] = array_unique( $item['photos']['photo'] );

			if ( count( $item['photos']['photo'] ) > 0 ) {
				if ( count( $item['photos']['photo'] ) === 1 ) {
					$images_str = $item['photos']['photo'][0];
				} else {
					$images_str = implode( ',', $item['photos']['photo'] );
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'image', $images_str );

			} else {
				self::wp_cli_log( "Import xml EMPTY GALLERY: $i of $count" );
				unset( $xml_data[ $key ] );
				continue;
			}

			if ( isset( $item['region-nameru'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_country', 'Украина' );
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_region', $item['region-nameru'] );
			}

			if ( isset( $item['settle-nameru'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_locality_name', $item['settle-nameru'] );
			}

			if ( isset( $item['admin-district-nameru'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_district_locality', $item['admin-district-nameru'] );
			}

			if ( isset( $item['non-admin-district-nameru'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_microdistrict', $item['non-admin-district-nameru'] );
			}

			if ( isset( $item['street'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_address', $item['street'] );
			}

			if ( isset( $item['house_number'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_house', $item['house_number'] );
			}

			if ( isset( $item['coords_lat'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_latitude', $item['coords_lat'] );
			}

			if ( isset( $item['coords_lng'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'location_longitude', $item['coords_lng'] );
			}

			if ( isset( $item['description'] ) ) {
				if ( isset( $item['title'] ) ) {
					$title               = $item['title'];
					$item['description'] = $title . "\r" . $item['description'];
				}
				$item['description'] = trim( preg_replace( '/\s+/', ' ', $item['description'] ) );

				self::set_value( $data_update_insert, $format_data_update_insert, 'description', $item['description'] );
			}

			if ( isset( $item['property'] ) ) {
				if ( $item['property'] === 'sell' ) {
					$item['property'] = 'продажа';
				} else {
					$item['property'] = 'аренда';
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'type', $item['property'] );
			}

			if ( isset( $item['squarefull'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'area', $item['squarefull'] );
			}

			if ( isset( $item['rooms'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'rooms', $item['rooms'] );
			}

			if ( isset( $item['floor'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'floor', $item['floor'] );
			}

			if ( isset( $item['numberoffloors'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'floors_total', $item['numberoffloors'] );
			}

			if ( isset( $item['squarelive'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'living_area', $item['squarelive'] );
			}

			if ( isset( $item['squarekitchen'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'kitchen_area', $item['squarekitchen'] );
			}

			if ( isset( $item['newbuilding'] ) ) {
				if ( $item['newbuilding'] === 'newbuilding' ) {
					$is_new = '1';
				} else {
					$is_new = '0';
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'is_new', $is_new );
			}

			if ( isset( $item['name'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_name', $item['name'] );
			}

			if ( isset( $item['typeofoffer'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_category', $item['typeofoffer'] );
			}

			if ( isset( $item['cost'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'price', $item['cost'] );
			}

			if ( isset( $item['currency'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'currency', $item['currency'] );
			}

			if ( count( $item['phones']['phone'] ) > 0 ) {
				if ( count( $item['phones']['phone'] ) === 1 ) {
					$phones_str = $item['phone']['phone'][0];
				} else {
					$phones_str = implode( ',', $item['phones']['phone'] );
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_phone', $phones_str );
			}

			if ( isset( $item['paid_services'] ) && count( $item['paid_services'] ) > 0 ) {
				if ( $item['paid_services']['name'] === 'realia_hot' && ! empty( $item['paid_services']['date_end'] ) ) {
					$paid_services = array( 'paid_service' => 'realia_hot', 'date_end' => date( 'Y-m-d H:i:s', strtotime( '-1 month', strtotime( $item['paid_services']['date_end'] ) ) ) );
					$paid_services = maybe_serialize( $paid_services );
					self::set_value( $data_update_insert, $format_data_update_insert, 'payed_adv', $paid_services );
				} elseif ( $item['paid_services']['name'] === 'realia_top' && ! empty( $item['paid_services']['date_end'] ) ) {
					$paid_services = array( 'paid_service' => 'realia_hot', 'date_end' => date( 'Y-m-d H:i:s', strtotime( '-1 month', strtotime( $item['paid_services']['date_end'] ) ) ) );
					$paid_services = maybe_serialize( $paid_services );
					self::set_value( $data_update_insert, $format_data_update_insert, 'payed_adv', $paid_services );
				}
			}

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

		$offers_count = $wpdb->get_var( "select count(*) from wp_deco_xml_data_offers where $where" );

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