<?php

namespace Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Feed_Type;

class Yandex_Nedvizhimost extends \Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml {

	public static function sync_xml( $args ) {
		global $wpdb;

		$user_id   = $args['user_id'];
		$xml_link  = $args['xml_link'];
		$sync_id   = $args['sync_id'];
		$feed_type = $args['feed_type'];


		// Получение xml по разным условиям
		$xml_data_result = self::get_xml_data_by_link( $xml_link );

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
				'xml_standard'    => 'Yandex_Nedvizhimost',
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

		$xml_data = array();

		if ( isset( $xml_data_result['offer'][0] ) ) {
			$xml_data = $xml_data_result['offer'];
		} else {
			$xml_data[] = $xml_data_result['offer'];
		}
		$count = count( $xml_data );

		if ( ! $count ) {
			self::wp_cli_log( "Sync xml corrupted: sync_id $sync_id" );

			return;
		}

		$wpdb->query( "update wp_deco_xml_data_offers set status = 'delete' where user_id = $user_id and sync_id = $sync_id" );
		$wpdb->query( "update wp_deco_xml_list set count = $count where user_id = $user_id and id = $sync_id" );


		foreach ( $xml_data as $key => $item ) {
			$i ++;
			$data_update_insert        = array();
			$format_data_update_insert = array();

			// echo 111 . PHP_EOL;

			$internal_id = '';


			if ( isset( $item['@attributes']['internal-id'] ) ) {
				$internal_id = $item['@attributes']['internal-id'];
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


			//====== URL start ======//
			// @todo убрано по таску
			// https://basecamp.com/1880665/projects/13172634/todos/345586181
			if ( isset( $item['url'] ) ) {
				$item['url'] = trim( $item['url'] );
//				if ( empty( $item['url'] ) ) {
//					self::wp_cli_log( "Import xml EMPTY Url: $i of $count | mem - $mem" );
//					unset( $xml_data[ $key ] );
//					continue;
//				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'url', $item['url'] );
			}
// else {
//				self::wp_cli_log( "Import xml NO Url: $i of $count" );
//				unset( $xml_data[ $key ] );
//				continue;
//			}

			$last_update_date = isset( $item['last-update-date'] ) ? strtotime( $item['last-update-date'] ) : current_time( 'timestamp' );
			// $post         = $wpdb->get_row( "select last_update_date from wp_deco_xml_data_offers where internal_id = $internal_id and user_id = $user_id and sync_id = $sync_id" );


//			$offer_hash = self::hash_offer( $item );
			// echo $offer_hash . ' ';

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


			//====================================//
			//===== Перевірка полів (початок) ====//
			//====================================//

			// Загальні обов'язкові поля для всіх категорій (початок)

			//====== Internal_id ======//
			if ( $internal_id ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'internal_id', $internal_id );
				self::set_value( $data_update_insert, $format_data_update_insert, 'internal_id_crc32', $internal_id_crc32 );
			}


			//====== Type ======//
			if ( isset( $item['type'] ) ) {

				$item['type'] = trim( $item['type'] );
				if ( empty( $item['type'] ) or ! in_array( $item['type'], array( 'продаж', 'продажа', 'аренда' ) ) ) {
					self::wp_cli_log( "Import xml EMPTY Type: $i of $count | mem - $mem" );
					unset( $xml_data[ $key ] );
					continue;
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'type', $item['type'] );
			} else {
				self::wp_cli_log( "Import xml NO Type $i of $count | mem - $mem" );
				unset( $xml_data[ $key ] );
				continue;
			}


			//====== Category start ======//
			if ( isset( $item['category'] ) ) {

				if ( $feed_type === 'commercial_real_estate' ) {
					$item['category'] = $feed_type;
				} else {
					$item['category'] = trim( $item['category'] );
					if ( empty( $item['category'] ) or ! in_array( $item['category'], array(
							'квартира',
							'Квартира',
							'дом',
							'Дом'
						) ) ) {
						self::wp_cli_log( "Import xml EMPTY Category: $i of $count | mem - $mem" );
						unset( $xml_data[ $key ] );
						continue;
					}
				}


				self::set_value( $data_update_insert, $format_data_update_insert, 'category', $item['category'] );
			} else {
				self::wp_cli_log( "Import xml NO Category $i of $count | mem - $mem" );
				unset( $xml_data[ $key ] );
				continue;
			}


			//====== Location start ======//
			if ( isset( $item['location'] ) and ! empty( $item['location'] ) ) {
				if ( isset( $item['location']['country'] ) ) {

					if ( $item['location']['country'] === 'Россия' ) {
						$item['location']['country'] = 'Украина';
					}

					self::set_value( $data_update_insert, $format_data_update_insert, 'location_country', $item['location']['country'] );
				}
				if ( isset( $item['location']['region'] ) ) {
					if ( ! empty( $item['location']['region'] ) ) {
						self::set_value( $data_update_insert, $format_data_update_insert, 'location_region', $item['location']['region'] );
					} else {
						self::wp_cli_log( "Import xml EMPTY Location (region): $i of $count | mem - $mem" );
						unset( $xml_data[ $key ] );
						continue;
					}
				}
				if ( isset( $item['location']['locality-name'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'location_locality_name', $item['location']['locality-name'] );
				}

				//district//

				//new
				if ( isset( $item['location']['sub-locality-name'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'location_district_locality', $item['location']['sub-locality-name'] );
				}

				if ( isset( $item['location']['address'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'location_address', $item['location']['address'] );
				}

				if ( isset( $item['location']['latitude'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'location_latitude', $item['location']['latitude'] );
				}

				if ( isset( $item['location']['longitude'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'location_longitude', $item['location']['longitude'] );
				}
			} else {
				self::wp_cli_log( "Import xml NO Location: $i of $count | mem - $mem" );
				unset( $xml_data[ $key ] );
				continue;
			}


			//====== Price start ======//
			if ( isset( $item['price'] ) ) {

				if ( ! isset( $item['price']['value'] ) or ! isset( $item['price']['currency'] ) ) {
					self::wp_cli_log( "Import xml NO price or currency: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				}

				$price = trim( $item['price']['value'] );

				if ( $price == '1' ) {
					self::wp_cli_log( "Import xml PRICE = 1: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				} else if ( empty( $price ) ) {
					self::wp_cli_log( "Import xml EMPTY PRICE: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				}

				$currency = trim( $item['price']['currency'] );

				if ( ! in_array( $currency, array( 'USD', 'UAH', 'EUR' ) ) ) {
					self::wp_cli_log( "Import xml WRONG CURRENCY: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				} else if ( empty( $currency ) ) {
					self::wp_cli_log( "Import xml EMPTY CURRENCY: $i of $count" );
					unset( $xml_data[ $key ] );
					continue;
				}
				// period - чи має бути?
				self::set_value( $data_update_insert, $format_data_update_insert, 'price', $item['price']['value'] );
				self::set_name_currency( $data_update_insert, $format_data_update_insert, 'currency', $item['price']['currency'] );
			} else {
				self::wp_cli_log( "Import xml NO Price: $i of $count | mem - $mem" );
				unset( $xml_data[ $key ] );
				continue;
			}


			//====== Description start ======//
			if ( isset( $item['description'] ) ) {
				$item['description'] = trim( $item['description'] );
				if ( empty( $item['description'] ) ) {
					self::wp_cli_log( "Import xml EMPTY Description: $i of $count | mem - $mem" );
					unset( $xml_data[ $key ] );
					continue;
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'description', $item['description'] );
			} else {
				self::wp_cli_log( "Import xml NO Description: $i of $count | mem - $mem" );
				unset( $xml_data[ $key ] );
				continue;
			}

			//====== Images start ======//
			$item['image'] = array_filter( $item['image'] );
			$item['image'] = array_unique( $item['image'] );

			if ( count( $item['image'] ) > 0 ) {
				if ( count( $item['image'] ) == 1 ) {
					$images_str = $item['image'][0];
				} else {
					$images_str = implode( ',', $item['image'] );
				}
				self::set_value( $data_update_insert, $format_data_update_insert, 'image', $images_str );
			} else {
				self::wp_cli_log( "Import xml EMPTY GALLERY: $i of $count" );
				unset( $xml_data[ $key ] );
				continue;
			}


			// Обов'язкові поля для всіх категорій (кінець)


			//====== Area ======//
			if ( isset( $item['area']['value'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'area', $item['area']['value'] );
			}

			//====== Property type ======//
			if ( isset( $item['property-type'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'property_type', $item['property-type'] );
			}

			//====== Creation date ======//
			if ( isset( $item['creation-date'] ) ) {
				$date = date( 'Y-m-d H:i:s', strtotime( $item['creation-date'] ) );
				self::set_value( $data_update_insert, $format_data_update_insert, 'creation_date', $date );
			}

			//====== Last update date ======//
			if ( isset( $item['last-update-date'] ) ) {
				$date = date( 'Y-m-d H:i:s', $last_update_date );
				self::set_value( $data_update_insert, $format_data_update_insert, 'last_update_date', $date );
			}

			//====== Expire date ======//
			if ( isset( $item['expire-date'] ) ) {
				$date = date( 'Y-m-d H:i:s', strtotime( $item['expire-date'] ) );
				self::set_value( $data_update_insert, $format_data_update_insert, 'expire_date', $date );
			}

			//====== Living_area ======//
			if ( isset( $item['living-space'] ) ) {
				if ( isset( $item['living-space']['value'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'living_area', $item['living-space']['value'] );
				}
			}

			//====== Kitchen_area ======//
			if ( isset( $item['kitchen-space'] ) ) {
				if ( isset( $item['kitchen-space']['value'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'kitchen_area', $item['kitchen-space']['value'] );
				}
			}


			//====== sales-agent ======//
			if ( isset( $item['sales-agent'] ) ) {
				if ( isset( $item['sales-agent']['name'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_name', $item['sales-agent']['name'] );
				}

				if ( isset( $item['sales-agent']['phone'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_phone', implode( ',', $item['sales-agent']['phone'] ) );
				}

				if ( isset( $item['sales-agent']['category'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_category', $item['sales-agent']['category'] );
				} elseif ( isset( $item['sales-agent']['organization'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_category', $item['sales-agent']['organization'] );
				}

				if ( isset( $item['sales-agent']['url'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_url', $item['sales-agent']['url'] );
				} elseif ( isset( $item['sales-agent']['email'] ) ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'sales_agent_url', $item['sales-agent']['email'] );
				}
			}


			//====== custom-param ======//
			if ( isset( $item['custom-param'] ) ) {
				foreach ( $item['custom-param'] as $custom_param ) {
					$field_custom_param = str_replace( '-', '_', $custom_param['name'] );
					if ( isset( self::$table_fields[ $field_custom_param ] ) ) {
						self::set_value( $data_update_insert, $format_data_update_insert, $field_custom_param, $custom_param['value'] );
					}
				}
			}


			//====== quality ======//
			if ( isset( $item['quality'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'quality', $item['quality'] );
			}

			//====== payed-adv ======//
			if ( isset( $item['payed-adv'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'payed_adv', $item['payed-adv'] );
			}

			//====== manually-added ======//
			if ( isset( $item['manually-added'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'manually_added', $item['manually-added'] );
			}


			//====== Rooms ======//
			if ( isset( $item['rooms'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'rooms', $item['rooms'] );
			}

			//====== ceiling-height ======//
			if ( isset( $item['ceiling-height'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'ceiling_height', $item['ceiling-height'] );
			}

			//====== floor ======//
			if ( isset( $item['floor'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'floor', $item['floor'] );
			}

			//====== floors-total ======//
			if ( isset( $item['floors-total'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'floors_total', $item['floors-total'] );
			}

			//====== balcony ======//
			if ( isset( $item['balcony'] ) ) {
				if ( boolval( $item['balcony'] ) == true ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'balcony', $item['balcony'] );
				}
			}

			//====== lift ======//
			if ( isset( $item['lift'] ) ) {
				self::set_value( $data_update_insert, $format_data_update_insert, 'lift', $item['lift'] );
			}

			//====== new-flat ======//
			if ( isset( $item['new-flat'] ) ) {
				if ( boolval( $item['new-flat'] ) == true ) {
					self::set_value( $data_update_insert, $format_data_update_insert, 'is_new', $item['new-flat'] );
				}
			}


			// echo $item['lift'] . PHP_EOL;
			// print_r($data_update_insert);
			// print_r($item);
			// exit;


			//====================================//
			//===== Перевірка полів (кінець) =====//
			//====================================//

			//set hash
//			self::set_value( $data_update_insert, $format_data_update_insert, 'hash', $offer_hash );

			if ( $post ) {

				$updated ++;
				$data_update_insert['status']          = 'update';
				$data_update_insert['update_datetime'] = current_time( 'mysql' );
				$format_data_update_insert[]           = '%s';
				$wpdb->update(
					'wp_deco_xml_data_offers',
					$data_update_insert,
					array(
						'internal_id' => $internal_id,
						'user_id'     => $user_id,
						'sync_id'     => $sync_id,
					)
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

				}
			} else {
				$created ++;
				$data_update_insert['user_id']         = $user_id;
				$data_update_insert['sync_id']         = $sync_id;
				$data_update_insert['status']          = 'new';
				$data_update_insert['update_datetime'] = current_time( 'mysql' );
				$result                                = $wpdb->insert(
					'wp_deco_xml_data_offers',
					$data_update_insert
				);

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

//				self::offers_sync( $user_id, $sync_id, $wpdb->insert_id );
			}

//			print_r( $data_update_insert );
//			print_r( $format_data_update_insert );
//			print_r( $item );
//			die();

			unset( $xml_data[ $key ] );

			// print_r($xml_data);
//			echo 'end1' . PHP_EOL;

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
		$syn_per_records = 50;

		$where = "user_id = $user_id and status in ('not_need_update','new','update') and sync_id = $sync_id and post_id = 0";

//		/** 1 */ //done
//		$where .= " and type = 'продаж'";
//		$where .= " and category = 'квартира'";
//		/** end 1 */

//		/** 2 */ // done
//		$where .= " and type = 'продаж'";
//		$where .= " and category = 'дом'";
//		/** end 2 */

//		/** 3 */ // done
//		$where .= " and type = 'продаж'";
//		$where .= " and category = 'участок'";
//		/** end 3 */

//		/** 4 */ // done
//		$where .= " and type = 'аренда'";
//		$where .= " and category = 'квартира'";
//		/** end 4 */

//		/** 5 */ // done
//		$where .= " and type = 'аренда'";
//		$where .= " and category = 'дом'";
//		/** end 5 */

//		/** 6 */ done
		//		$where .= " and type = 'аренда'";
//		$where .= " and category = 'участок'";
//		/** end 6 */


//		$limit = "limit 83";
//		$offers_count = $wpdb->get_var( "select count(*) from wp_deco_xml_data_offers where $where $limit" );

		$offers_count = $wpdb->get_results( "select id from wp_deco_xml_data_offers where $where $limit" );
		$offers_count = count( $offers_count );


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

