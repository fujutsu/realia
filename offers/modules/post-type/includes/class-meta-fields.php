<?php

namespace Deco\Bundles\Offers\Modules\Post_Type\Includes;

use Deco\Helpers;

class Meta_Fields {

	static $prefix = 'deco_';
	private static $post_type = 'offers';

	public static function init() {
		add_filter( 'cmb2_admin_init', __CLASS__ . '::init_meta_boxes' );
		add_filter( 'cmb2_row_classes', __CLASS__ . '::add_meta_classes', 10, 2 );
	}

	public static function init_meta_boxes() {

		if ( ! Helpers::is_edit_page( null, self::$post_type ) ) {
			return;
		}

		// Photogallery
		/* @var $cmb_lead \CMB2 */
		$cmb_gallery = new_cmb2_box( array(
			'id'           => self::$prefix . 'offer_gallery',
			/*'title'        => __( 'Gallery', \Deco_Site::$textdomain ),*/
			'title'        => 'Галерея',
			'object_types' => array( self::$post_type ),
			'context'      => 'side', //  'normal', 'advanced', or 'side'
			'priority'     => 'core',  //  'high', 'core', 'default' or 'low'
			'show_names'   => true,
		) );

		$cmb_gallery->add_field( array(
			'name' => '',
			'id'   => self::$prefix . 'offer_photo_list',
			'type' => 'file_list',
		) );

		// Offer main data
		/* @var $cmb_lead \CMB2 */
		$cmb_maindata = new_cmb2_box( array(
			'id'           => self::$prefix . 'offer_main_data',
			'title'        => 'Основные данные',
			'object_types' => array( self::$post_type ),
			'context'      => 'normal', //  'normal', 'advanced', or 'side'
			'priority'     => 'high',  //  'high', 'core', 'default' or 'low'
			'show_names'   => true,
		) );

		$cmb_maindata->add_field( array(
			'name' => 'Цена',
			'id'   => self::$prefix . 'offer_price',
			'type' => 'text',
		) );

		/*		$cmb_maindata->add_field( array(
					'name' => 'Возможен кредит',
					'id'   => self::$prefix . 'offer_credit',
					'type' => 'checkbox',
				) );

				$cmb_maindata->add_field( array(
					'name' => 'В рассрочку',
					'id'   => self::$prefix . 'offer_bargain',
					'type' => 'checkbox',
				) );

				$cmb_maindata->add_field( array(
					'name' => 'Без комиссии',
					'id'   => self::$prefix . 'offer_no_commission',
					'type' => 'checkbox',
				) );*/

		/*		$cmb_maindata->add_field( array(
					'name' => 'Элитная недвижимость',
					'id'   => self::$prefix . 'offer_elite_real_estate',
					'type' => 'checkbox',
				) );*/

		/*		$cmb_maindata->add_field( array(
					'name' => 'Эксклюзив',
					'id'   => self::$prefix . 'offer_exclusive',
					'type' => 'checkbox',
				) );*/

		$cmb_maindata->add_field( array(
			'name' => 'Без мебели',
			'id'   => self::$prefix . 'offer_without_furniture',
			'type' => 'checkbox',
		) );

		$cmb_maindata->add_field( array(
			'name' => 'Отопление индивидуальное',
			'id'   => self::$prefix . 'offer_heating_individual',
			'type' => 'checkbox',
		) );

		$cmb_maindata->add_field( array(
			'name' => 'Отапливаемый',
			'id'   => self::$prefix . 'offer_garage_heating',
			'type' => 'checkbox',
		) );

		$cmb_maindata->add_field( array(
			'name' => 'Под СТО',
			'id'   => self::$prefix . 'offer_service_station',
			'type' => 'checkbox',
		) );

		$cmb_maindata->add_field( array(
			'name' => 'Ремонтная яма',
			'id'   => self::$prefix . 'offer_repair_pit',
			'type' => 'checkbox',
		) );

		$cmb_maindata->add_field( array(
			'name'       => 'Машиномест',
			'id'         => self::$prefix . 'offer_car_places',
			'type'       => 'text',
			'attributes' => array(
				'type'    => 'number',
				'pattern' => '\d*',
			),
		) );

		$cmb_maindata->add_field( array(
			'name'   => 'Площадь участка' . ' (<span class="square-units"><span class="square-units__ha">соток</span><span class="square-units__m">м<sup class="area_of_apartments_sup">2</sup></span></span>)',
			'id'     => self::$prefix . 'offer_square',
			'type'   => 'text',
			'before' => '<style>
							.area_of_apartments_sup {
									vertical-align: super;
									font-size: smaller;
							}
						</style>'
		) );

		$cmb_maindata->add_field( array(
			'name'   => 'Доступная площадь (<span class="square-units__m">м<sup class="available_area_of_apartments_sup">2</sup></span>) (для коммерческой недвижимости)',
			'id'     => self::$prefix . 'offer_available_square',
			'type'   => 'text',
			'before' => '<style>
							.available_area_of_apartments_sup {
									vertical-align: super;
									font-size: smaller;
							}
						</style>'
		) );

		$cmb_maindata->add_field( array(
			'name'            => 'Жилая площадь' . ' (м<sup class="area_of_apartments_sup">2</sup>)',
			'id'              => self::$prefix . 'offer_living_area',
			'type'            => 'text',
			'attributes'      => array(
				'type'    => 'number',
				'pattern' => '\d*',
			),
			'sanitization_cb' => 'absint',
			'escape_cb'       => 'absint',
			'before'          => '<style>
							.area_of_apartments_sup {
									vertical-align: super;
									font-size: smaller;
							}
						</style>'
		) );

		$cmb_maindata->add_field( array(
			'name'            => 'Площадь кухни' . ' (м<sup class="area_of_apartments_sup">2</sup>)',
			'id'              => self::$prefix . 'offer_kitchen_area',
			'type'            => 'text',
			'attributes'      => array(
				'type'    => 'number',
				'pattern' => '\d*',
			),
			'sanitization_cb' => 'absint',
			'escape_cb'       => 'absint',
			'before'          => '<style>
							.area_of_apartments_sup {
									vertical-align: super;
									font-size: smaller;
							}
						</style>'
		) );

		$cmb_maindata->add_field( array(
			'name'        => 'Площадь участка' . ' (соток)',
			'description' => 'Например, 2.8',
			'id'          => self::$prefix . 'offer_land_area',
			'type'        => 'text',
			'before'      => '<style>
							.area_of_apartments_sup {
									vertical-align: super;
									font-size: smaller;
							}
						</style>'
		) );

		$floor                 = array();
		$floor[0]              = 'Выбрать ...';
		$floor['ground_floor'] = 'Цокольный';
		for ( $i = 1; $i <= 100; $i ++ ) {
			$floor[ $i ] = $i;
		}

		$cmb_maindata->add_field( array(
			'name'    => 'Этаж',
			'id'      => self::$prefix . 'offer_floor',
			'type'    => 'select',
			'options' => $floor,
		) );

		unset( $floor['ground_floor'] );
		$cmb_maindata->add_field( array(
			'name'    => 'Этажность',
			'id'      => self::$prefix . 'offer_floors',
			'type'    => 'select',
			'options' => $floor,
		) );

		$year_now       = date( 'Y' );
		$years_built    = array();
		$years_built[0] = 'Выбрать ...';
		for ( $i = $year_now; $i >= 1960; $i -- ) {
			$years_built[ $i ] = $i;
		}
		$years_built[1959] = 'До 1960';

		$cmb_maindata->add_field( array(
			'id'      => self::$prefix . 'offer_year_built',
			'name'    => 'Год постройки',
			'type'    => 'select',
			'options' => $years_built,
		) );

		$cmb_maindata->add_field( array(
			'name'        => 'Высота потолка' . ' (м)',
			'description' => 'Например, 2.8',
			'id'          => self::$prefix . 'offer_ceiling_height',
			'type'        => 'text',
		) );

		$cmb_maindata->add_field( array(
			'name' => 'Удаленность от города' . ' (км)',
			'id'   => self::$prefix . 'offer_distance',
			'type' => 'text',
		) );

		$cmb_maindata->add_field( array(
			'name' => 'Кадастровый номер',
			'id'   => self::$prefix . 'offer_cadastral_number',
			'type' => 'text',
		) );

		$user_added = get_post_meta( $_GET['post'], 'deco_offer_added_user_id', true );

		if ( $user_added ) {
			$cmb_par_post = new_cmb2_box( array(
				'id'           => self::$prefix . 'user_added',
				'title'        => 'Пользователь, добавивший заявку',
				'object_types' => array( self::$post_type ),
				'context'      => 'side', //  'normal', 'advanced', or 'side'
				'priority'     => 'core',  //  'high', 'core', 'default' or 'low'
				'show_names'   => true,
			) );

			$user_data = get_user_by( 'id', $user_added );

			$cmb_par_post->add_field( array(
				'name'        => '<a href="' . admin_url() . 'user-edit.php?user_id=' . $user_added . '" target="_blank">' . $user_data->display_name . '</a>',
				'description' => $user_data->user_email,
				'id'          => self::$prefix . 'user_added_data',
				'type'        => 'title',
			) );
		}

	}

	public static function add_meta_classes( $classes, $box ) {
		if ( substr_count( $classes, 'cmb2-id-deco-offer-square' ) ) {

			$classes .= ' js-hiding-row kvartir domov kommercheskoj-nedvizhimosti garazhej-i-parkingov zemelnyh-uchastkov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-no-commission' ) ) {

			$classes .= ' js-hiding-row kvartir domov kommercheskoj-nedvizhimosti zemelnyh-uchastkov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-elite-real-estate' ) ) {

			$classes .= ' js-hiding-row kvartir';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-exclusive' ) ) {

			$classes .= ' js-hiding-row domov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-heating-individual' ) ) {

			$classes .= ' js-hiding-row kvartir';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-garage-heating' ) ) {

			$classes .= ' js-hiding-row garazhej-i-parkingov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-service-station' ) ) {

			$classes .= ' js-hiding-row garazhej-i-parkingov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-repair-pit' ) ) {

			$classes .= ' js-hiding-row garazhej-i-parkingov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-car-places' ) ) {

			$classes .= ' js-hiding-row garazhej-i-parkingov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-living-area' ) ) {

			$classes .= ' js-hiding-row kvartir domov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-kitchen-area' ) ) {

			$classes .= ' js-hiding-row kvartir domov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-land-area' ) ) {

			$classes .= ' js-hiding-row domov kommercheskoj-nedvizhimosti';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-land-plot-area' ) ) {

			$classes .= ' js-hiding-row zemelnyh-uchastkov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-floors' ) ) {

			$classes .= ' js-hiding-row kvartir domov kommercheskoj-nedvizhimosti';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-floor' ) ) {

			$classes .= ' js-hiding-row kvartir kommercheskoj-nedvizhimosti';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-year-built' ) ) {

			$classes .= ' js-hiding-row kvartir domov kommercheskoj-nedvizhimosti garazhej-i-parkingov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-ceiling-height' ) ) {

			$classes .= ' js-hiding-row kvartir domov kommercheskoj-nedvizhimosti garazhej-i-parkingov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-distance' ) ) {

			$classes .= ' js-hiding-row domov kommercheskoj-nedvizhimosti zemelnyh-uchastkov';

		} elseif ( substr_count( $classes, 'cmb2-id-deco-offer-cadastral-number' ) ) {

			$classes .= ' js-hiding-row zemelnyh-uchastkov';

		}

		return $classes;
	}
}