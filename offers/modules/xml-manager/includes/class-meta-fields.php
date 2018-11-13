<?php

namespace Deco\Bundles\Offers\Modules\XML_Manager\Includes;

use Deco\Helpers;

use \Cmb2Grid\Grid\Cmb2Grid as Cmb2GridHandler;

class Meta_Fields {

	static $prefix = 'xml_list_';
	private static $post_type = 'xml_list';

	public static function init() {
		add_filter( 'cmb2_admin_init', __CLASS__ . '::init_meta_boxes' );
	}

	public static function init_meta_boxes() {
		global $wpdb;

		if ( ! Helpers::is_edit_page( null, self::$post_type ) ) {
			return;
		}

		$post_id = empty( $_GET['post'] ) ? 0 : (int) $_GET['post'];

		$active_attributes = array();

		if ( $post_id ) {
			$xml_row = $wpdb->get_row( "select * from wp_deco_xml_list where post_id = $post_id" );
			if ( $xml_row->is_active === 1 ) {
				$active_attributes['checked'] = 'checked';
			}

		}

		// Offer main data
		/* @var $cmb_lead \CMB2 */
		$cmb_maindata = new_cmb2_box( array(
			'id'           => self::$prefix . 'main_data',
			'title'        => 'Параметры XML',
			'object_types' => array( self::$post_type ),
			'context'      => 'normal', //  'normal', 'advanced', or 'side'
			'priority'     => 'high',  //  'high', 'core', 'default' or 'low'
			'show_names'   => true,
		) );

		$cmb_maindata->add_field( array(
			'name'        => 'Активен',
			'id'          => self::$prefix . 'active',
			'description' => '',
			'type'        => 'checkbox',
			'attributes'  => $active_attributes,
			'value'       => 1,
		) );

		$users = get_users( array(
			'role' => 'agency',
		) );

		$agency    = array();
		$agency[0] = 'Выбрать...';
		foreach ( $users as $user ) {
			$agency[ $user->ID ] = $user->display_name . " (ID: $user->ID)";
		}

		$agency_attribute      = array(
			'data-conditional-id'    => self::$prefix . 'feed_standard',
			'data-conditional-value' => wp_json_encode( array( 'Yandex_Nedvizhimost', 'Lun_UA', 'Dom_Ria' ) ),
		);
		$xml_list_agency_error = '';

		if ( $post_id ) {
			$agency_id             = get_post_meta( $post_id, 'xml_list_agency', true );
			$xml_list_agency_error = get_post_meta( $post_id, 'xml_list_agency_error', true );
//			if ( $agency_id ) {
//				$agency_attribute['onmousedown'] = 'window.focus();';
//
//			}

			if ( $xml_list_agency_error ) {
				$xml_list_agency_error = '<p style="color: red;">Не задано Агенство!</p>';
			}

		}


		if ( $agency_id ) {
			$user = get_userdata( $agency_id );
			$cmb_maindata->add_field( array(
				'name' => 'Агенство&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $user->display_name . ' (ID: ' . $agency_id . ')',
//				'desc' => 'Изменение Агенства не доступно!',
				'type' => 'title',
				'id'   => 'xml_list_agency_display_name'
			) );
		} else {
			$cmb_maindata->add_field( array(
				'name'       => 'Агенство',
				'id'         => self::$prefix . 'agency',
				'type'       => 'select',
				'attributes' => $agency_attribute,
				'options'    => $agency,
				'after'      => $xml_list_agency_error,
			) );
		}


		$xml_list_link_error = '';
		if ( $post_id ) {
			$xml_list_link_error = get_post_meta( $post_id, 'xml_list_link_error', true );
			if ( $xml_list_link_error ) {
				$xml_list_link_error = '<p style="color: red;">Ссылка не прошла валидацию! XML деактивирован.</p>';
			}
		}


		$cmb_maindata->add_field( array(
			'name'        => 'Ссылка',
			'id'          => self::$prefix . 'link',
			'description' => '',
			'type'        => 'textarea',
			'attributes'  => array(
				'rows'  => '2',
				'style' => 'width: 900px;'
			),
			'after'       => $xml_list_link_error,
		) );

		$cmb_maindata->add_field( array(
			'name' => 'или Файл',
			'id'   => self::$prefix . 'file',
			'type' => 'file_list',
		) );

		$feed_type                           = array();
		$feed_type['standard']               = 'Стандартный';
		$feed_type['commercial_real_estate'] = 'Коммерческая недвижимость';

		$cmb_maindata->add_field( array(
			'name'    => 'Тип',
			'id'      => self::$prefix . 'feed_type',
			'type'    => 'select',
			'options' => $feed_type,
		) );

		$feed_standard                        = array();
		$feed_standard['Yandex_Nedvizhimost'] = 'Яндекс.Недвижимость';
		$feed_standard['Lun_UA']              = 'Лун';
		$feed_standard['Dom_Ria']             = 'Дом.Риа';
		$feed_standard['Aspo_Biz']            = 'Aspo.Biz';

		$cmb_maindata->add_field( array(
			'name'    => 'Стандарт',
			'id'      => self::$prefix . 'feed_standard',
			'type'    => 'select',
			'options' => $feed_standard,
		) );

		$update_frequency       = array();
		$update_frequency['1']  = 'Раз в час';
		$update_frequency['24'] = 'Раз в сутки';
		$update_frequency['0']  = 'Однократно';

		$cmb_maindata->add_field( array(
			'name'    => 'Периодичность обновления',
			'id'      => self::$prefix . 'update_frequency',
			'type'    => 'select',
			'options' => $update_frequency,
		) );

		$cmb_maindata->add_field( array(
			'name' => 'Описание',
			'id'   => self::$prefix . 'descr',
			'type' => 'textarea',
		) );

		if ( $agency_id || get_post_meta( $post_id, 'xml_list_feed_standard', true ) === 'Aspo_Biz' ) {
			$cmb_maindata->add_field( array(
				'name' => 'Произвольные поля',
				'id'   => self::$prefix . 'custom_fields',
				'type' => 'title',
			) );

			$custom_field = $cmb_maindata->add_field( array(
				'name' => 'Ключ поля',
				'id'   => self::$prefix . 'custom_field',
				'type' => 'title',
			) );

			$custom_field_name_ua = $cmb_maindata->add_field( array(
				'name' => 'Название (UA)',
				'id'   => self::$prefix . 'custom_field_name_ua',
				'type' => 'title',
			) );

			$custom_field_name_ru = $cmb_maindata->add_field( array(
				'name' => 'Название (RU)',
				'id'   => self::$prefix . 'custom_field_name_ru',
				'type' => 'title',
			) );

			$custom_field_checkbox = $cmb_maindata->add_field( array(
				'name' => 'Показ на сайте',
				'id'   => self::$prefix . 'custom_field_checkbox',
				'type' => 'title',
			) );

			if ( class_exists( '\Cmb2Grid\Grid\Cmb2Grid' ) ) {
				$cmb_maindata_cmb2Grid      = new Cmb2GridHandler( $cmb_maindata );
				$cmb_maindata_custom_fields = $cmb_maindata_cmb2Grid->addRow();
				$cmb_maindata_custom_fields->addColumns( array(
					array( $custom_field, 'class' => 'col-md-3' ),
					array( $custom_field_name_ua, 'class' => 'col-md-4' ),
					array( $custom_field_name_ru, 'class' => 'col-md-4' ),
					array( $custom_field_checkbox, 'class' => 'col-md-1' ),
				) );
			}

			$custom_fields_count = (int) get_post_meta( $post_id, 'xml_list_custom_fields_count', true );
			if ( $custom_fields_count !== false ) {
				for ( $i = 0; $i < $custom_fields_count; $i ++ ) {
					$custom_field = $cmb_maindata->add_field( array(
						'name'       => '',
						'id'         => self::$prefix . 'custom_field_' . $i,
						'type'       => 'text',
						'attributes' => array(
							'readonly' => 'readonly'
						)
					) );

					$custom_field_name_ua = $cmb_maindata->add_field( array(
						'name' => '',
						'id'   => self::$prefix . 'custom_field_name_ua_' . $i,
						'type' => 'text',
					) );

					$custom_field_name_ru = $cmb_maindata->add_field( array(
						'name' => '',
						'id'   => self::$prefix . 'custom_field_name_ru_' . $i,
						'type' => 'text',
					) );

					$custom_field_checkbox = $cmb_maindata->add_field( array(
						'name' => '',
						'id'   => self::$prefix . 'custom_field_checkbox_' . $i,
						'type' => 'checkbox'
					) );

					if ( class_exists( '\Cmb2Grid\Grid\Cmb2Grid' ) ) {
						$cmb_maindata_custom_fields = $cmb_maindata_cmb2Grid->addRow();
						$cmb_maindata_custom_fields->addColumns( array(
							array( $custom_field, 'class' => 'col-md-3' ),
							array( $custom_field_name_ua, 'class' => 'col-md-4' ),
							array( $custom_field_name_ru, 'class' => 'col-md-4' ),
							array( $custom_field_checkbox, 'class' => 'col-md-1' ),
						) );
					}
				}
			}

			if ( empty( get_post_meta( $post_id, 'xml_list_custom_fields_count', true ) ) ) {
				$cmb_maindata->add_field( array(
					'name'  => '',
					'id'    => self::$prefix . 'xml_analysis',
					'type'  => 'title',
					'after' => '<a class="button button-primary button-large xml-analysis">Анализ XML</a>'
				) );
			}
		}

	}
}