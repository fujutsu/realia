<?php

namespace Deco\Bundles\Offers\Modules\Archive_Offers\Includes;

class Methods {

	public static function init() {

		add_action( 'deco_archive_offers', __CLASS__ . '::archive_offers' );
		add_action( 'deco_remove_archived_offers', __CLASS__ . '::remove_archived_offers' );
		add_action( 'deco_remove_deleted_offers', __CLASS__ . '::remove_deleted_offers' );

	}

	public static function archive_offers() {

		global $wpdb;

		$table = $wpdb->get_blog_prefix() . 'archive_offers';

		$query       = self::get_offers_for_archive();
		$posts_count = $query->found_posts;

		if ( $posts_count !== 0 ) {

			$posts_per_page = 50;
			$pages          = ceil( $posts_count / $posts_per_page );
			$now            = time();
			$start_time     = current_time( 'mysql' );
			$time_start     = current_time( 'timestamp' );

			$i = 0;

			for ( $page = 0; $page <= $pages - 1; $page ++ ) {

				$offset = $page * $posts_per_page;

				$query = self::get_offers_for_archive( $posts_per_page, $offset );

				$posts = $query->posts;

				foreach ( $posts as $post ) {

					$i ++;
					$posts_table = $wpdb->prefix . 'posts';
					$data_to_db  = array(
						'post_status' => 'archive'
					);
					$where       = array(
						'ID' => $post
					);

//					$update = wp_update_post( array( 'ID' => $post, 'post_status' => 'archive' ) );
					$update = $wpdb->update( $posts_table, $data_to_db, $where );
					if ( $update !== false ) {
						$post_obj = get_post( $post );

						$address_linked = self::get_address_link( $post );
						$redirect_link  = $address_linked;

						$link_from = get_permalink( $post );
						$link_from = str_replace( '/ua/', '/', $link_from );
						$link_from = strtolower( $link_from );

						$link_from_arr = parse_url( $link_from );
						$link_from     = $link_from_arr['path'];

						$link_to = str_replace( '/ua/', '/', $redirect_link );
						$link_to = strtolower( $link_to );

						$link_to_arr = parse_url( $link_to );
						$link_to     = $link_to_arr['path'];

						$post_id_exists = $wpdb->get_col( "SELECT id FROM $table WHERE post_id = '$post'" );
						if ( ! empty( $post_id_exists ) ) {
							$data_to_db = array(
								'post_date'                     => $post_obj->post_modified,
								'post_archive_status'           => 'archive',
								'post_archive_date'             => date( 'Y-m-d H:i:s', $now ),
								'post_redirect_link_from'       => $link_from,
								'post_redirect_link_from_crc32' => crc32( $link_from ),
								'post_redirect_link_to'         => $link_to,
								'post_redirect_link_to_crc32'   => crc32( $link_to ),
							);

							$where = array(
								'post_id' => $post,
							);

							$update = $wpdb->update( $table, $data_to_db, $where );
						} else {
							$data_to_db = array(
								'post_id'                       => $post,
								'post_date'                     => $post_obj->post_modified,
								'post_archive_status'           => 'archive',
								'post_archive_date'             => date( 'Y-m-d H:i:s', $now ),
								'post_redirect_link_from'       => $link_from,
								'post_redirect_link_from_crc32' => crc32( $link_from ),
								'post_redirect_link_to'         => $link_to,
								'post_redirect_link_to_crc32'   => crc32( $link_to ),
							);

							$insert = $wpdb->insert( $table, $data_to_db );
						}

						$time_end    = current_time( 'timestamp' );
						$time_during = round( abs( $time_start - $time_end ) / 60, 2 );
						$mem         = memory_get_peak_usage();

						WP_CLI::log( 'Archived ' . $i . ' offers of ' . $posts_count . ' | Start time: ' . $start_time . ' | Time during (min): ' . $time_during . ' | Mem - ' . $mem );
					} else {
						$time_end    = current_time( 'timestamp' );
						$time_during = round( abs( $time_start - $time_end ) / 60, 2 );
						$mem         = memory_get_peak_usage();

						WP_CLI::log( 'Failed to archive - ' . $i . ' offers of ' . $posts_count . ' | Start time: ' . $start_time . ' | Time during (min): ' . $time_during . ' | Mem - ' . $mem );
					}
				}
			}

		}

	}

	public static function remove_archived_offers() {

		global $wpdb;

		$table = $wpdb->get_blog_prefix() . 'archive_offers';

		$result      = self::get_offers_for_delete();
		$posts_count = $result['count'];

		if ( $posts_count !== 0 ) {

			$posts_per_page = 50;
			$pages          = ceil( $posts_count / $posts_per_page );
			$now            = time();
			$start_time     = current_time( 'mysql' );
			$time_start     = current_time( 'timestamp' );

			$i = 0;

			for ( $page = 0; $page <= $pages - 1; $page ++ ) {

				$offset = $page * $posts_per_page;

				$query = self::get_offers_for_delete( $posts_per_page, $offset );

				$posts = $query['posts'];

				foreach ( $posts as $post ) {
					$i ++;
					if ( get_post_status( $post ) === 'publish' ) {
						continue;
					}
					$delete = wp_delete_post( $post, true );

					if ( $delete !== false ) {
						$data_to_db = array(
							'post_archive_status' => 'delete',
							'post_remove_date'    => date( 'Y-m-d H:i:s', $now ),
						);

						$where = array(
							'post_id' => $post,
						);

						$wpdb->update( $table, $data_to_db, $where );
						$time_end    = current_time( 'timestamp' );
						$time_during = round( abs( $time_start - $time_end ) / 60, 2 );
						$mem         = memory_get_peak_usage();

						WP_CLI::log( 'Deleted ' . $i . ' offers of ' . $posts_count . ' | Start time: ' . $start_time . ' | Time during (min): ' . $time_during . ' | Mem - ' . $mem );
					} else {
						$time_end    = current_time( 'timestamp' );
						$time_during = round( abs( $time_start - $time_end ) / 60, 2 );
						$mem         = memory_get_peak_usage();

						WP_CLI::log( 'Failed to delete - ' . $i . ' offers of ' . $posts_count . ' | Start time: ' . $start_time . ' | Time during (min): ' . $time_during . ' | Mem - ' . $mem );
					}

				}
			}

		}

	}

	public static function remove_deleted_offers() {

		global $wpdb;

		$table = $wpdb->get_blog_prefix() . 'archive_offers';

		$result      = self::get_redirects_to_remove();
		$posts_count = $result['count'];

		if ( $posts_count !== 0 ) {

			$posts_per_page = 50;
			$pages          = ceil( $posts_count / $posts_per_page );
			$start_time     = current_time( 'mysql' );
			$time_start     = current_time( 'timestamp' );

			$i = 0;

			for ( $page = 0; $page <= $pages - 1; $page ++ ) {

				$offset = $page * $posts_per_page;

				$query = self::get_redirects_to_remove( $posts_per_page, $offset );

				$posts = $query['posts'];

				foreach ( $posts as $post ) {
					$i ++;

					$where = array(
						'post_id' => $post,
					);

					$delete = $wpdb->delete( $table, $where );
					if ( $delete !== false ) {
						$time_end    = current_time( 'timestamp' );
						$time_during = round( abs( $time_start - $time_end ) / 60, 2 );
						$mem         = memory_get_peak_usage();

						WP_CLI::log( 'Removed ' . $i . ' offer redirects of ' . $posts_count . ' | Start time: ' . $start_time . ' | Time during (min): ' . $time_during . ' | Mem - ' . $mem );
					} else {
						$time_end    = current_time( 'timestamp' );
						$time_during = round( abs( $time_start - $time_end ) / 60, 2 );
						$mem         = memory_get_peak_usage();

						WP_CLI::log( 'Failed to remove - ' . $i . ' offer redirects of ' . $posts_count . ' | Start time: ' . $start_time . ' | Time during (min): ' . $time_during . ' | Mem - ' . $mem );
					}

				}
			}

		}

	}

	public static function get_offers_for_archive( $posts_per_page = '', $offset = '' ) {

		if ( $posts_per_page === '' ) {
			$posts_per_page = 1;
		}

		if ( $offset === '' ) {
			$offset = 0;
		}

		$args = array(
			'post_type'      => 'offers',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => $posts_per_page,
			'offset'         => $offset,
			'date_query'     => array(
				array(
					'column' => 'post_modified',
					'before' => date( 'Y-m-d', strtotime( '-1 month' ) ),
				)
			),
			'order'          => 'DESC'
		);

		$offers_query = new \WP_Query( $args );

		return $offers_query;

	}

	public static function get_offers_for_delete( $posts_per_page = '', $offset = '' ) {

		global $wpdb;

		if ( $posts_per_page === '' ) {
			$posts_per_page = 1;
		}

		if ( $offset === '' ) {
			$offset = 0;
		}

		$table = $wpdb->get_blog_prefix() . 'archive_offers';

		$date = date( 'Y-m-d H:i:s', strtotime( '-2 month' ) );

		$sql = "SELECT SQL_CALC_FOUND_ROWS post_id FROM $table 
WHERE post_archive_date <= '$date' AND post_archive_status = 'archive'
ORDER BY post_archive_date DESC 
LIMIT $offset, $posts_per_page";

		$result['posts'] = $wpdb->get_col( $sql );
		$result['count'] = $wpdb->get_var( "SELECT FOUND_ROWS()" );

		return $result;

	}

	public static function get_redirects_to_remove( $posts_per_page = '', $offset = '' ) {

		global $wpdb;

		if ( $posts_per_page === '' ) {
			$posts_per_page = 1;
		}

		if ( $offset === '' ) {
			$offset = 0;
		}

		$table = $wpdb->get_blog_prefix() . 'archive_offers';

		$date = date( 'Y-m-d H:i:s', strtotime( '-2 month' ) );

		$sql = "SELECT SQL_CALC_FOUND_ROWS post_id FROM $table 
WHERE post_remove_date <= '$date' AND post_archive_status = 'delete'
ORDER BY post_remove_date DESC 
LIMIT $offset, $posts_per_page";

		$result['posts'] = $wpdb->get_col( $sql );
		$result['count'] = $wpdb->get_var( "SELECT FOUND_ROWS()" );

		return $result;

	}

	public static function get_address( $post_id ) {
		global $wpdb;

		$lang = qtranxf_getLanguage();

		$address_region_id            = get_post_meta( $post_id, 'address_region_id', true );
		$address_locality_id          = get_post_meta( $post_id, 'address_locality_id', true );
		$address_district_locality_id = get_post_meta( $post_id, 'address_district_locality_id', true );
		$address_street_id            = get_post_meta( $post_id, 'address_street_id', true );
		$address_house_number         = get_post_meta( $post_id, 'address_house_number', true );
		$location_street_custom       = get_post_meta( $post_id, 'location_street_custom', true );

		$address_region_name            = '';
		$address_district_region_name   = '';
		$address_locality_name          = '';
		$address_district_locality_name = '';
		$address_street_name            = '';

		if ( $address_street_id ) {

//			if ( ! isset( self::$address_exists['street'][ $address_street_id ] ) ) {
			$address = apply_filters( 'deco_address_get_data_by_id', 'wp_address_street', $address_street_id );
//				self::$address_exists['street'][ $address_street_id ] = $address;
//			} else {
//				$address = self::$address_exists['street'][ $address_street_id ];
//			}

			$prefix                  = 'street';
			$region_field            = $prefix . '_region_title_' . $lang;
			$district_region_field   = $prefix . '_district_region_title_' . $lang;
			$locality_field          = $prefix . '_locality_title_' . $lang;
			$district_locality_field = $prefix . '_district_locality_title_' . $lang;
			$street_field            = $prefix . '_title_' . $lang;

			$address_region_name            = $address->$region_field;
			$address_district_region_name   = $address->$district_region_field;
			$address_locality_name          = $address->$locality_field;
			$address_district_locality_name = $address->$district_locality_field;
			$address_street_name            = $address->$street_field;

			$address_region_slug            = $prefix . '_region_slug';
			$address_district_region_slug   = $prefix . '_district_region_slug';
			$address_locality_slug          = $prefix . '_locality_slug';
			$address_district_locality_slug = $prefix . '_district_locality_slug';
			$address_street_slug            = $prefix . '_slug';


			$address_region_slug            = $address->$address_region_slug;
			$address_district_region_slug   = $address->$address_district_region_slug;
			$address_locality_slug          = $address->$address_locality_slug;
			$address_district_locality_slug = $address->$address_district_locality_slug;
			$address_street_slug            = $address->$address_street_slug;

			if ( empty( $address_locality_slug ) ) {
				$locality = $wpdb->get_row( "select * from wp_address_locality where id = $address_locality_id" );

				$locality_title                = 'title_' . $lang;
				$address_locality_name         = $locality->$locality_title;
				$address->$locality_field      = $address_locality_name;
				$address_locality_slug         = $locality->slug;
				$address->street_locality_slug = $address_locality_slug;
			}


			// quick dirty fix
			if ( $lang === 'ua' ) {
				$address_street_name = 'вул. ' . trim( str_replace( 'ул.', '', str_replace( 'вул.', '', $address_street_name ) ) );
			}
		} else if ( $address_district_locality_id ) {
			$address = apply_filters( 'deco_address_get_data_by_id', 'wp_address_district_locality', $address_district_locality_id );

			$prefix                  = 'sublocality';
			$region_field            = $prefix . '_region_title_' . $lang;
			$district_region_field   = $prefix . '_district_region_title_' . $lang;
			$locality_field          = $prefix . '_locality_title_' . $lang;
			$district_locality_field = $prefix . '_title_' . $lang;

			$address_region_name            = $address->$region_field;
			$address_district_region_name   = $address->$district_region_field;
			$address_locality_name          = $address->$locality_field;
			$address_district_locality_name = $address->$district_locality_field;


			$address_region_slug            = $prefix . '_region_slug';
			$address_district_region_slug   = $prefix . '_district_region_slug';
			$address_locality_slug          = $prefix . '_locality_slug';
			$address_district_locality_slug = $prefix . '_district_locality_slug';

			$address_region_slug            = $address->$address_region_slug;
			$address_district_region_slug   = $address->$address_district_region_slug;
			$address_locality_slug          = $address->$address_locality_slug;
			$address_district_locality_slug = $address->$address_district_locality_slug;
			$address_street_slug            = '';

		} else if ( $address_locality_id ) {

			$prefix  = 'locality';
			$address = apply_filters( 'deco_address_get_data_by_id', 'wp_address_locality', $address_locality_id );

			$region_field          = $prefix . '_region_title_' . $lang;
			$district_region_field = $prefix . '_district_region_title_' . $lang;
			$locality_field        = $prefix . '_title_' . $lang;

			$address_region_name          = $address->$region_field;
			$address_district_region_name = $address->$district_region_field;
			$address_locality_name        = $address->$locality_field;


			$address_region_slug          = $prefix . '_region_slug';
			$address_district_region_slug = $prefix . '_district_region_slug';
			$address_locality_slug        = $prefix . '_slug';

			$address_region_slug            = $address->$address_region_slug;
			$address_district_region_slug   = $address->$address_district_region_slug;
			$address_locality_slug          = $address->$address_locality_slug;
			$address_district_locality_slug = '';
			$address_street_slug            = '';

		} else if ( $address_region_id ) {

			$prefix = 'region';

			$address = apply_filters( 'deco_address_get_data_by_id', 'wp_address_region', $address_region_id );

			$region_field        = $prefix . '_title_' . $lang;
			$address_region_name = $address->$region_field;

			$address_region_slug            = $prefix . '_region_slug';
			$address_region_slug            = $address->$address_region_slug;
			$address_district_region_slug   = '';
			$address_locality_slug          = '';
			$address_district_locality_slug = '';
			$address_street_slug            = '';

		}

		return array(
			'address_region_id'              => $address_region_id,
			'address_region_name'            => $address_region_name,
			'address_region_slug'            => $address_region_slug,
			'address_district_region_id'     => $address_district_region_id,
			'address_district_region_name'   => $address_district_region_name,
			'address_district_region_slug'   => $address_district_region_slug,
			'address_locality_id'            => $address_locality_id,
			'address_locality_name'          => $address_locality_name,
			'address_locality_slug'          => $address_locality_slug,
			'address_district_locality_id'   => $address_district_locality_id,
			'address_district_locality_name' => $address_district_locality_name,
			'address_district_locality_slug' => $address_district_locality_slug,
			'address_microdistrict'          => '',
			'address_microdistrict_slug'     => '',
			'metro'                          => '',
			'metro_slug'                     => '',
			'address_street_id'              => $address_street_id,
			'address_street_name'            => $address_street_name,
			'address_street_slug'            => $address_street_slug,
			'location_street_custom'         => $location_street_custom,
			'address_house_number'           => $address_house_number,
			'address_response'               => $address,
		);
	}

	public static function get_address_link( $post_id ) {
		$address = self::get_address( $post_id );

		$deal_type      = get_the_terms( $post_id, 'deal_type' );
		$offer_category = get_the_terms( $post_id, 'offer_category' );

		$home_url        = home_url();
		$offer_type_link = '/' . $deal_type[0]->slug . '-' . str_replace( '-', '_', $offer_category[0]->slug ) . '/';

		$link_for_obl = '';

		if ( ! empty( $address['address_region_slug'] ) ) {
			$link_for_obl = $home_url . $offer_type_link . 'oblast-' . $address['address_region_slug'] . '/';
		}

		$link_for_district_region   = '';
		$link_for_district_locality = '';

		if ( ! empty( $address['address_locality_slug'] ) ) {
			if ( $address['address_region_id'] === 1 ) {
				$link_for_np                = $home_url . $offer_type_link . 'np-' . $address['address_locality_slug'] . '/';
				$link_for_district_locality = $link_for_np . 'rn-' . $address['address_district_locality_slug'] . '/';
			} else {
				$link_for_np = $link_for_obl . 'rayon-' . $address['address_district_region_slug'] . '/np-' . $address['address_locality_slug'] . '/';
			}
		} else {
			$link_for_np = $home_url . $offer_type_link;
		}

		return $link_for_np;
	}

}