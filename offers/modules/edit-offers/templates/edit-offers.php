<?php
if ( ! is_user_logged_in() ) {
	wp_redirect( '/' );
	die();
}

global $wpdb, $wp_query;
$context = \Timber\Timber::get_context();

set_query_var( 'is_edit_offer_page', 1 );

$context['wp_title'] = apply_filters( 'deco_i18n_front', 'Edit offer' );

$context['deal_type'] = get_terms( array( 'taxonomy' => 'deal_type', 'hide_empty' => false ) );
//$context['offer_category'] = get_terms( array( 'taxonomy' => 'offer_category', 'hide_empty' => false ) );
$context['offer_type'] = get_terms( array( 'taxonomy' => 'offer_type', 'hide_empty' => false ) );

$offer_to_edit = \Timber\Timber::get_post( false, '\Deco\Entities\Story_Post' );

if ( ! is_wp_error( $context['deal_type'] ) ) {
	foreach ( $context['deal_type'] as $deal_type_key => $deal_type_value ) {

		if ( $deal_type_value->term_id === $offer_to_edit->terms( 'deal_type' )[0]->term_id ) {
			$context['deal_type'][ $deal_type_key ]->checked       = 'checked';
			$context['deal_type'][ $deal_type_key ]->checked_class = 'radio-checked-default';
		}

	}
}

$offer_category = $offer_to_edit->terms( 'offer_category' );

if ( ! empty( $offer_category ) ) {
	$offer_category                            = $offer_category[0];
	$offer_to_edit->deco_edited_offer_category = $offer_category;

	$category_name = str_replace( '-', '_', $offer_category->name );

	$offer_args = array();

	//$context['edit_offer_fields'] = apply_filters('deco_edited_offer_fields_'.$category_name,$offer_args);

	$type = $offer_category->slug;

	if ( in_array( $type, array( 'kvartir', 'domov', 'kommercheskoj-nedvizhimosti', 'garazhej-i-parkingov' ) ) ) {
		// Паркинг
		$context['parking']             = get_terms( array(
			'taxonomy'   => 'parking',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->parking_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['parking'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_parking
		) );


		/** Metro */
		$metro_list = wp_get_object_terms( $offer_to_edit->ID, 'metro' );
		foreach ( $metro_list as $metro_item ) {

			$color_class      = 'icon-subway--green';
			$metro_term_ids[] = $metro_item->term_id;
			$color_way_type   = get_term_meta( $metro_item->parent, 'deco_metro_branch', true );
			if ( $color_way_type ) {
				if ( $color_way_type === 'red' ) {
					$color_class = 'icon-subway--red';
				} else if ( $color_way_type === 'blue' ) {
					$color_class = 'icon-subway--blue';
				} else {
					$color_class = 'icon-subway--green';
				}
			}

			$metro_items[] = array(
				'color_class' => $color_class,
				'name'        => $metro_item->name
			);
		}
		if ( ! empty( $metro_items ) ) {
			$context['metro_list']          = $metro_items;
			$context['hide_metro_selector'] = 'yes';
		}

		$context['metro'] = Timber\Timber::compile( array( 'parts/add-offers-add-bids/metro-near.twig' ), $context );
		/** END Metro */


		$year_now    = date( 'Y' );
		$years_built = array();
		for ( $i = $year_now; $i >= 1960; $i -- ) {
			$years_built[ $i ] = $i;
		}
		$years_built[1959] = 'До 1960';

		$context['year_built']             = $years_built;
		$offer_to_edit->year_built_default = apply_filters( 'deco_offer_edit_get_year_default', $offer_to_edit->deco_offer_year_built );
	}

	if ( in_array( $type, array( 'kvartir', 'domov', 'kommercheskoj-nedvizhimosti' ) ) ) {
		// Новострой, вторичный рынок
		$context['code'] = get_terms( array( 'taxonomy' => 'code', 'hide_empty' => false ) );

		// Количество комнат
		$context['count_rooms'] = get_terms( array(
			'taxonomy'   => 'count_rooms',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );

		$offer_to_edit->rooms_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['count_rooms'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_count_rooms
		) );

		// Ремонт
		$context['repair']             = get_terms( array(
			'taxonomy'   => 'repair',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->repair_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['repair'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_repair
		) );

		// Тип стен
		$context['wall_type']             = get_terms( array(
			'taxonomy'   => 'wall_type',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->wall_type_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['wall_type'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_wall_type
		) );

		// Отопление
		$context['heating']             = get_terms( array(
			'taxonomy'   => 'heating',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->heating_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['heating'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_heating
		) );

		$floors = array();
		for ( $i = 1; $i <= 100; $i ++ ) {
			$floors[ $i ] = $i;
		}
		$context['floors'] = $floors;

		$offer_to_edit->floors_default = apply_filters( 'deco_offer_edit_get_floor_default', $offer_to_edit->deco_offer_floors );
		$offer_to_edit->floor_default  = apply_filters( 'deco_offer_edit_get_floor_default', $offer_to_edit->deco_offer_floor );

		$house_floors = array();
		for ( $j = 1; $j <= 5; $j ++ ) {
			$house_floors[ $j ] = $j;
		}
		$context['house_floors'] = $house_floors;
	}

	if ( in_array( $type, array( 'domov', 'kommercheskoj-nedvizhimosti', 'zemelnyh-uchastkov' ) ) ) {
		// Канализация
		$context['sewerage']             = get_terms( array(
			'taxonomy'   => 'sewerage',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->sewerage_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['sewerage'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_sewerage
		) );
	}

	if ( in_array( $type, array( 'kvartir', 'domov' ) ) ) {
		// Количество санузлов
		$context['count_bathrooms']             = get_terms( array(
			'taxonomy'   => 'count_bathrooms',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->count_bathrooms_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['count_bathrooms'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_count_bathrooms
		) );

		// Класс жилья
		$context['property_classes']             = get_terms( array(
			'taxonomy'   => 'property_classes',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->property_classes_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['property_classes'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_property_classes
		) );

		// Тип оплаты
		$context['type_payment']             = get_terms( array(
			'taxonomy'   => 'type_payment',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->type_payment_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['type_payment'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_type_payment
		) );

		// Тип предложения
		$context['type_proposition']             = get_terms( array(
			'taxonomy'   => 'type_proposition',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->type_proposition_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['type_proposition'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_type_proposition
		) );

		// Удобства
		$context['comfort']             = get_terms( array(
			'taxonomy'   => 'comfort',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->comfort_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['comfort'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_comfort
		) );
	}

	if ( in_array( $type, array( 'domov', 'kommercheskoj-nedvizhimosti' ) ) ) {
		// Тип окон
		$context['window_type']             = get_terms( array(
			'taxonomy'   => 'window_type',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->window_type_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['window_type'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_window_type
		) );
	}

	if ( in_array( $type, array( 'domov', 'zemelnyh-uchastkov' ) ) ) {
		//Водопровод
		$context['plumbing']             = get_terms( array(
			'taxonomy'   => 'plumbing',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->plumbing_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['plumbing'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_plumbing
		) );
	}

	if ( in_array( $type, array( 'zemelnyh-uchastkov', 'garazhej-i-parkingov' ) ) ) {
		//Электричество
		$context['electricity']             = get_terms( array(
			'taxonomy'   => 'electricity',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->electricity_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['electricity'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_electricity
		) );
	}

	if ( $type === 'kommercheskoj-nedvizhimosti' ) {
		// Класс помещение
		$context['lodge_class'] = get_terms( array(
			'taxonomy'   => 'lodge_class',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );

		$offer_to_edit->lodge_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['lodge_class'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_lodge_class
		) );

		// Тип здания
		$context['building_type'] = get_terms( array(
			'taxonomy'   => 'building_type',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );

		$offer_to_edit->building_type_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['building_type'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_building_type
		) );

		// Наличие кухни
		//$context['kitchen'] = get_terms( array( 'taxonomy' => 'kitchen', 'hide_empty' => false ) );
	}

	if ( $type === 'zemelnyh-uchastkov' ) {
		// Целевое назначение
		$context['intended_purpose']             = get_terms( array(
			'taxonomy'   => 'intended_purpose',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->intended_purpose_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['intended_purpose'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_intended_purpose
		) );

		// Газопровод
		$context['gasmain']             = get_terms( array(
			'taxonomy'   => 'gasmain',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->gasmain_default = apply_filters( 'deco_offer_edit_get_parameter_default_values', array(
			'parameter_values' => $context['gasmain'],
			'value'            => $offer_to_edit->_yoast_wpseo_primary_gasmain
		) );

		//Локация
		$context['location']             = get_terms( array(
			'taxonomy'   => 'location',
			'hide_empty' => false,
			'fields'     => 'id=>name'
		) );
		$offer_to_edit->location_default = wp_get_post_terms( $offer_to_edit->ID, 'location', array( 'fields' => 'ids' ) );
	}

	$context['ifs'] = get_terms( array(
		'taxonomy'   => 'ifs',
		'meta_key'   => 'deco_ifs_cat_' . $offer_category->term_id,
		'meta_value' => $offer_category->term_id,
		'hide_empty' => false
	) );

	$offer_to_edit->ifs = wp_get_post_terms( $offer_to_edit->ID, 'ifs', array( "fields" => "ids" ) );
}

$offer_type = get_term( $offer_to_edit->_yoast_wpseo_primary_offer_type, 'offer_type' );
if ( ! is_wp_error( $offer_type ) ) {
	$offer_to_edit->deco_edited_offer_type = $offer_type;
}

$category_fields = apply_filters( 'deco_edited_offer_fields', '' );

$lang                             = qtranxf_getLanguage();
$offer_to_edit->location_locality = get_post_meta( $offer_to_edit->ID, 'location_locality_' . $lang, true );
$offer_to_edit->location_street   = get_post_meta( $offer_to_edit->ID, 'location_street_' . $lang, true );

if ( isset( $offer_to_edit->deco_offer_square ) && $offer_to_edit->deco_offer_square != 0 ) {
	$offer_to_edit->area_price = number_format( (float) $offer_to_edit->deco_offer_price / (float) $offer_to_edit->deco_offer_square, 2, '.', ' ' );
}

if ( isset( $offer_to_edit->deco_offer_photo_list ) && ! empty( $offer_to_edit->deco_offer_photo_list ) ) {
	$offer_to_edit->default_gallery = apply_filters( 'deco_offer_edit_get_gallery_default', [ 'gallery' => $offer_to_edit->deco_offer_photo_list ] );
}
if ( isset( $_COOKIE['deco_add_offer_gallery_images'] ) && ! empty( $_COOKIE['deco_add_offer_gallery_images'] ) ) {

	$gallery_from_cookie = explode( ',', $_COOKIE['deco_add_offer_gallery_images'] );
	if ( ! empty( $gallery_from_cookie ) && is_array( $gallery_from_cookie ) ) {
		$gallery_items = array();
		foreach ( $gallery_from_cookie as $single_item ) {
			$item = apply_filters( 'deco_offer_edit_get_gallery_item_data', array( 'item_id' => $single_item ) );
			if ( ! empty( $item['file'] ) ) {
				$gallery_items[] = $item['file'];
			}
		}
		if ( ! empty( $gallery_items ) ) {
			$offer_to_edit->default_gallery = $gallery_items;
		}
	}

}

if ( is_array( $offer_to_edit->location_house ) && ! empty( $offer_to_edit->location_house[0] ) ) {
	$offer_to_edit->location_house = $offer_to_edit->location_house[0];
}

$context['offer_to_edit'] = $offer_to_edit;

$slug = $context['offer_to_edit']->deco_edited_offer_category->slug;

$context['add_offer_right_widgets']  = is_active_sidebar( 'single_add_offer_right' ) ? Timber::get_widgets( 'single_add_offer_right' ) : null;
$context['add_offer_bottom_widgets'] = is_active_sidebar( 'single_add_offer_bottom' ) ? Timber::get_widgets( 'single_add_offer_bottom' ) : null;

$template_path = 'bundles/edit-offers/offer-categories/edit-offer-%type%.twig';
//$result['html']   = \Timber::compile( str_replace( '%type%', $slug, $template_path ), $context );

Timber::render( str_replace( '%type%', $slug, $template_path ), $context );
