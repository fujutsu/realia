<?php

namespace Deco\Bundles\Offers\Modules\Edit_Offers\Includes;

class Edit_Offer_Filters {

	public static function init() {

		add_filter( 'deco_offer_edit_get_parameter_default_values', __CLASS__ . '::get_parameter_values' );

		add_filter( 'deco_offer_edit_get_floor_default', __CLASS__ . '::get_floor_defaults' );

		add_filter( 'deco_offer_edit_get_year_default', __CLASS__ . '::get_year_defaults' );

		add_filter( 'deco_offer_edit_get_metro_default', __CLASS__ . '::get_metro_defaults' );

		add_filter( 'deco_offer_edit_get_gallery_default', __CLASS__ . '::get_gallery_defaults' );

		add_filter( 'deco_offer_edit_get_gallery_item_data', __CLASS__ . '::get_gallery_item_data' );
	}

	public static function get_parameter_values( $args = array() ) {

		$parameter_values = $args['parameter_values'];
		$default_value    = $args['value'];

		$result = array();

		if ( isset( $parameter_values[ $default_value ] ) && ! empty( $parameter_values[ $default_value ] ) ) {
			$result['title']              = $parameter_values[ $default_value ];
			$result['class']              = 'current-value';
			$result['default_item_class'] = '';
		} else {
			$result['title']              = apply_filters( 'deco_i18n_front', 'Select' );
			$result['class']              = 'current-value default';
			$result['default_item_class'] = 'current';
		}

		return $result;

	}

	public static function get_floor_defaults( $floor ) {

		$result = array(
			'class' => 'current-value default'
		);

		if ( ! empty( $floor ) && $floor === 'ground_floor' ) {
			$result['class']              = 'current-value';
			$result['title']              = apply_filters( 'deco_i18n_front', 'Groundfloor' );
			$result['default_item_class'] = '';
		} elseif ( ! empty( $floor ) ) {
			$result['class']              = 'current-value';
			$result['title']              = $floor;
			$result['default_item_class'] = '';
		} else {
			$result['title']              = apply_filters( 'deco_i18n_front', 'Select' );
			$result['default_item_class'] = 'current';
		}

		return $result;

	}

	public static function get_year_defaults( $year ) {

		$result = array();

		if ( $year == 1959 ) {
			$result['class']              = 'current-value';
			$result['title']              = 'До 1960';
			$result['default_item_class'] = '';
		} elseif ( ! empty( $year ) ) {
			$result['class']              = 'current-value';
			$result['title']              = $year;
			$result['default_item_class'] = '';
		} else {
			$result['title']              = apply_filters( 'deco_i18n_front', 'Select' );
			$result['class']              = 'current-value default';
			$result['default_item_class'] = 'current';
		}

		return $result;

	}

	public static function get_metro_defaults( $args = array() ) {

		$branches      = $args['branches'];
		$metro_default = $args['station'];

		$result = array();

		if ( ! empty( $metro_default ) ) {
			foreach ( $branches as $single_branch ) {
				if ( ! empty( $single_branch->stations ) ) {
					foreach ( $single_branch->stations as $single_station ) {
						if ( $single_station->term_id == $metro_default ) {
							$result['title'] = $single_station->name;
						}
					}
				}
			}
			$result['class']              = 'current-value';
			$result['default_item_class'] = '';
		} else {
			$result['title']              = apply_filters( 'deco_i18n_front', 'Select station' );
			$result['class']              = 'current-value default';
			$result['default_item_class'] = 'current';
		}

		return $result;


	}

	public static function get_gallery_defaults( $args = array() ) {

		$gallery = $args['gallery'];

		$result = array();

		if ( is_array( $gallery ) ) {

			foreach ( $gallery as $image_id => $image_url ) {
				$result[] = self::generate_gallery_item( $image_id );
			}

		}

		return $result;

	}

	public static function generate_gallery_item( $image_id ) {
		$images_sizes = array(
			'offer_blog_src'         => 'offer_blog_loop_tease',
			'tooltip_tease_src'      => 'tooltip_tease_on_map',
			'offer_map_marker_src'   => 'offer_map_marker_tease',
			'offer_single_tease_src' => 'offer_single_tease',
			'modal_src'              => 'full',
			'bg'                     => 'add_offer_gallery_tease',
		);

		$result = array(
			'id' => $image_id,
		);

		foreach ( $images_sizes as $key => $value ) {
			$attachment     = wp_get_attachment_image_src( $image_id, $value );
			$result[ $key ] = $attachment[0];
		}

		return $result;
	}

	public static function get_gallery_item_data( $args = array() ) {

		$item_id = empty( $args['item_id'] ) ? '' : $args['item_id'];

		$result = array();

		if ( ! empty( $item_id ) ) {
			$result['file']['id'] = $item_id;

			$image_meta = wp_get_attachment_metadata( $item_id );

			$images_sizes = array(
				'offer_blog_src'         => 'offer_blog_loop_tease',
				'tooltip_tease_src'      => 'tooltip_tease_on_map',
				'offer_map_marker_src'   => 'offer_map_marker_tease',
				'offer_single_tease_src' => 'offer_single_tease',
				'modal_src'              => 'full',
				'bg'                     => 'add_offer_gallery_tease',
			);

			if ( ! empty( $image_meta['file'] ) ) {

				$path_parts = explode( '/', $image_meta['file'] );
				if ( is_array( $path_parts ) ) {

					$full_size_name = array_pop( $path_parts );
					$upload_dir     = wp_upload_dir();
					$path_to_image  = $upload_dir['baseurl'] . '/' . implode( '/', $path_parts ) . '/';

					foreach ( $images_sizes as $key => $value ) {
						if ( $value === 'full' ) {
							$attachment = $path_to_image . $full_size_name;
						} else {
							$attachment = $path_to_image . $image_meta['sizes'][ $value ]['file'];
						}
						$result['file'][ $key ] = $attachment;
					}
				}

			}
		}


		return $result;

	}

}