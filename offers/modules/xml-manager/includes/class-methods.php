<?php

namespace Deco\Bundles\Offers\Modules\XML_Manager\Includes;

class Methods {

	static $xml_data;

	public static function init() {

		if ( is_admin() ) {
			self::$xml_data = array(
				'Aspo_Biz'            => 'realty',
				'Dom_Ria'             => 'realty',
				'Lun_UA'              => 'announcements',
				'Yandex_Nedvizhimost' => 'offer'
			);

			add_action( 'wp_ajax_deco_xml_analysis', __CLASS__ . '::xml_analysis' );
			add_action( 'save_post', __CLASS__ . '::save_custom_fields' );
		}

	}

	public static function save_custom_fields() {
		$prefix              = 'xml_list_';
		$post_id             = (int) $_POST['post_ID'];
		$user_id             = get_post_meta( $post_id, 'xml_list_agency', true ) ?: $_POST['xml_list_agency'];
		$custom_fields_count = isset( $_POST['xml_list_custom_fields_count'] ) ? (int) $_POST['xml_list_custom_fields_count'] : (int) get_post_meta( $post_id, 'xml_list_custom_fields_count', true );

		if ( $custom_fields_count ) {
			global $wpdb;

			if ( $user_id === 0 ) {
				$user_id = '0';
			}

			$sync_id = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}deco_xml_list WHERE post_id = '$post_id' AND user_id = '$user_id'" );

			if ( empty( $sync_id ) ) {
				return;
			}

			$sync_id = $sync_id[0];

			update_post_meta( $post_id, $prefix . 'custom_fields_count', $custom_fields_count );
			for ( $i = 0; $i < $custom_fields_count; $i ++ ) {
				$fields          = array(
					'xml_field'       => $prefix . 'custom_field_' . $i,
					'title_field_ua'  => $prefix . 'custom_field_name_ua_' . $i,
					'title_field_ru'  => $prefix . 'custom_field_name_ru_' . $i,
					'display_on_site' => $prefix . 'custom_field_checkbox_' . $i
				);
				$xml_field_value = $_POST[ $prefix . 'custom_field_' . $i ];
				$field_exists    = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}deco_xml_mapping_fields WHERE xml_field = '$xml_field_value' AND user_id = '$user_id' AND post_id='$post_id'" );
				$field_id        = $field_exists[0]->id;
				foreach ( $fields as $key => $field ) {
					if ( isset( $_POST[ $field ] ) && $_POST[ $field ] !== '' ) {
						$field_value = $_POST[ $field ];
						update_post_meta( $post_id, $field, $field_value );
						if ( $key === 'xml_field' ) {
							if ( $field_id ) {
								$update = $wpdb->update(
									$wpdb->prefix . 'deco_xml_mapping_fields',
									array(
										'post_id'   => $post_id,
										'sync_id'   => $sync_id,
										'xml_field' => $field_value
									),
									array(
										'id' => $field_id
									)
								);
							} else {
								$insert = $wpdb->insert( $wpdb->prefix . 'deco_xml_mapping_fields',
									array(
										'post_id'   => $post_id,
										'sync_id'   => $sync_id,
										'xml_field' => $field_value
									)
								);
							}
						} elseif ( $key === 'display_on_site' ) {
							if ( $field_value === 'on' ) {
								$wpdb->update(
									$wpdb->prefix . 'deco_xml_mapping_fields',
									array(
										'post_id'         => $post_id,
										'sync_id'         => $sync_id,
										'user_id'         => $user_id,
										'display_on_site' => 1
									),
									array(
										'id' => $field_id
									),
									array(
										'%d',
										'%d',
										'%d',
										'%d'
									)
								);
							}
						} else {
							$wpdb->update(
								$wpdb->prefix . 'deco_xml_mapping_fields',
								array(
									'post_id' => $post_id,
									'sync_id' => $sync_id,
									'user_id' => $user_id,
									$key      => $field_value
								),
								array(
									'id' => $field_id
								)
							);
						}
					}
				}
			}
		}
	}

	public static function xml_analysis() {

		$post_id       = $_POST['post_id'];
		$xml_link      = $_POST['xml_link'];
		$feed_standard = $_POST['feed_standard'];

		if ( $xml_link && $feed_standard ) {
			$index = self::$xml_data[ $feed_standard ];

			$xml_data = self::get_xml_data_by_link( $xml_link );
			if ( $feed_standard === 'Lun_UA' ) {
				$xml_data = $xml_data[ $index ]['announcement'];
			} else {
				$xml_data = $xml_data[ $index ];
			}

			$custom_fields = array();
			foreach ( $xml_data as $offer ) {
				foreach ( $offer as $index => $value ) {
					if ( strpos( $index, '@' ) !== false ) {
						continue;
					}
					$custom_fields[] = $index;
				}
			}

			$custom_fields = array_unique( $custom_fields );
			$custom_fields = array_values( $custom_fields );

			wp_send_json_success( $custom_fields );
		}

		wp_send_json_error();

	}

	protected static function get_xml_data_by_link( $xml_link ) {
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

	protected static function objectsIntoArray( $arrObjData, $arrSkipIndices = array() ) {
		$arrData = array();
		$i       = 0;

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
				$i ++;
				if ( $i > 200 ) {
					break;
				}
			}
		}

		return $arrData;

	}

}