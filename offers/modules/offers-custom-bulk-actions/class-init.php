<?php

namespace Deco\Bundles\Offers\Modules\Offers_Custom_Bulk_Actions;

class Init {

	public static function static_init() {

		add_filter( 'bulk_actions-edit-offers', __CLASS__ . '::offer_bulk_actions' );

		add_filter( 'handle_bulk_actions-edit-offers', __CLASS__ . '::handle_offer_bulk_actions', 10, 3 );


		//Action for regenerate offer slider meta for single
		add_action( 'deco_regenerate_offer_meta', __CLASS__ . '::regenerate_offer_meta' );

		add_action( 'deco_regenerate_single_offer_gallery', __CLASS__ . '::regenerate_single_offer_gallery' );

	}

	public static function offer_bulk_actions( $bulk_actions ) {

		$bulk_actions['regenerate_offer_meta'] = 'Regenerate offer meta';

		return $bulk_actions;

	}

	public static function handle_offer_bulk_actions( $redirect_to, $action_name, $post_ids ) {

		if ( $action_name == 'regenerate_offer_meta' ) {

			foreach ( $post_ids as $post_id ) {

				self::regenerate_offer_gallery( $post_id );

			}

			return $redirect_to;

		} else {

			return $redirect_to;

		}

	}

	public static function regenerate_offer_meta() {

		$first_posts    = self::get_posts( 1, 1 );
		$posts_per_page = 350;
		$pages          = ceil( $first_posts->found_posts / $posts_per_page );
		$start_time     = current_time( 'mysql' );
		$time_start     = current_time( 'timestamp' );

		$i = 0;
		for ( $page = 1; $page <= $pages; $page ++ ) {
			foreach ( self::get_posts( $page, $posts_per_page )->posts as $post ) {
				$i ++;

				self::regenerate_offer_gallery( $post->ID );

				wp_reset_query();
				wp_reset_postdata();
				wp_cache_flush();
				$GLOBALS['wpdb']->queries = array();
				$time_end                 = current_time( 'timestamp' );
				$time_during              = round( abs( $time_start - $time_end ) / 60, 2 );

				$mem = memory_get_peak_usage();
				WP_CLI::log( "Regenerate offer gallery metadata: $i of {$first_posts->found_posts} | page $page of $pages | start time: $start_time | Time during (min): $time_during | mem - $mem | post_id - {$post->ID}" );
			}
		}
	}

	public static function regenerate_offer_gallery( $post_id ) {

		$photo_list = get_post_meta( $post_id, 'deco_offer_photo_list', true );

		$single_slides = array();

		if ( ! empty( $photo_list ) ) {

			foreach ( $photo_list as $id => $single_photo ) {

				$single_slide      = wp_get_attachment_image_src( $id, 'offer_single_tease' );
				$single_slide_full = wp_get_attachment_image_src( $id, 'full' );

				$single_slides[] = array(
					'src'  => $single_slide[0],
					'full' => $single_slide_full[0],
				);

			}

		}

		update_post_meta( $post_id, 'deco_offer_single_slides', $single_slides );

	}

	private static function get_posts( $page = 1, $posts_per_page ) {
		$posts = new \WP_Query( array(
			'post_type'      => array( 'offers' ),
			'posts_per_page' => $posts_per_page,
			'paged'          => $page,
			'post_status'    => 'publish'
		) );

		return $posts;
	}

	public static function regenerate_single_offer_gallery( $post_id ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$gallery_meta = get_post_meta( $post_id, 'deco_offer_single_slides', true );

		if ( is_string( $gallery_meta ) && strpos( $gallery_meta, '<img' ) !== false ) {

			self::regenerate_offer_gallery( $post_id );

		} elseif ( is_array( $gallery_meta ) ) {

			$incorrect = false;

			//Проверка на старый вариант с относительными путями
			if ( strpos( $gallery_meta[0]['full'], '/home/' ) !== false) {
				$incorrect = true;
			}

			if ( $incorrect == true ) {

				self::regenerate_offer_gallery( $post_id );

			}

		}


	}

}