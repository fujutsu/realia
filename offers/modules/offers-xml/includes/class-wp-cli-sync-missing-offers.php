<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class Sync_Missing_Offers extends \WP_CLI_Command{

	public function add_offer_categories(){

		global $wpdb;

		$sql = "SELECT wp_posts.ID 
FROM wp_posts 
LEFT JOIN wp_term_relationships 
ON (wp_posts.ID = wp_term_relationships.object_id) 
WHERE ( wp_term_relationships.term_taxonomy_id IN (29,30) 
AND wp_posts.ID 
NOT IN ( SELECT object_id FROM wp_term_relationships WHERE term_taxonomy_id IN (15,16,17,18,19) ) ) 
AND wp_posts.post_type = 'offers'
 AND ((wp_posts.post_status = 'publish')) 
GROUP BY wp_posts.ID ORDER BY wp_posts.menu_order, wp_posts.post_date DESC LIMIT 0, 150";

		$ids = $wpdb->get_results($sql,ARRAY_A);

		$ids_to_update = array();

		if(!empty($ids)){
			foreach ($ids as $single_id){
				$ids_to_update[] = $single_id['ID'];
			}
		}

		$args = array(
			'post_type'   => array( 'offers' ),
			'post_status' => 'publish',
			'post__in' => $ids_to_update,
		);

		$current_path = ABSPATH;

		$first_posts    = self::get_posts( 1, 1, $args );
		$posts_per_page = 50;
		$pages          = ceil( $first_posts->found_posts / $posts_per_page );
		$start_time     = current_time( 'mysql' );
		$time_start     = current_time( 'timestamp' );
		$i              = 0;

		for ( $page = 1; $page <= $pages; $page ++ ) {
			foreach ( self::get_posts( $page, $posts_per_page, $args )->posts as $post ) {
				$i ++;

				$command = "cd $current_path && wp deco_sync_offers_without_category resave_category_item --id={$post->ID} --allow-root";
				passthru( "( $command & ) >> /dev/null 2>&1" );
				//				passthru( $command );


				$time_end    = current_time( 'timestamp' );
				$time_during = round( abs( $time_start - $time_end ) / 60, 2 );

				$mem = memory_get_peak_usage();

				WP_CLI::log( "Resave offers Category: $i of {$first_posts->found_posts} | page $page of $pages | start time: $start_time | Time during (min): $time_during | mem - $mem" );

			}
		}
	}

	public function resave_category_item( $args, $assoc_args ) {
		global $wpdb;

		$id           = $assoc_args['id'];
		$prefix       = 'deco_offer_';
		$yoast_prefix = '_yoast_wpseo_primary_';

		$item    = $wpdb->get_row( "select * from wp_deco_xml_data_offers where post_id = $id" );
		$post_id = $id;

		$data_for_sync['type_sell_term'] = get_term_by( 'slug', 'prodazha', 'deal_type' );
		$data_for_sync['type_rent_term'] = get_term_by( 'slug', 'arenda', 'deal_type' );

		$data_for_sync['category_house']                         = get_term_by( 'slug', 'domov', 'offer_category' );
		$data_for_sync['category_apartment']                     = get_term_by( 'slug', 'kvartir', 'offer_category' );
		$data_for_sync['category_commercial-real-estate']        = get_term_by( 'slug', 'kommercheskoj-nedvizhimosti', 'offer_category' );
		$data_for_sync['category_land_plot']                     = get_term_by( 'slug', 'zemelnyh-uchastkov', 'offer_category' );
		$data_for_sync['offer_type_house']                       = get_term_by( 'slug', 'house', 'offer_type' );
		$data_for_sync['offer_type_dacha']                       = get_term_by( 'slug', 'dacha', 'offer_type' );
		$data_for_sync['offer_type_apartment']                   = get_term_by( 'slug', 'apartment', 'offer_type' );
		$data_for_sync['offer_type_land_plot']                   = get_term_by( 'slug', 'lot', 'offer_type' );
		$data_for_sync['offer_type_dachnyj_uchastok']            = get_term_by( 'slug', 'dachnyj-uchastok', 'offer_type' );
		$data_for_sync['offer_type_ofisnoe-pomeshhenie']         = get_term_by( 'slug', 'ofisnoe-pomeshhenie', 'offer_type' );
		$data_for_sync['offer_type_ofisnoe-zdanie']              = get_term_by( 'slug', 'ofisnoe-zdanie', 'offer_type' );
		$data_for_sync['offer_type_kafe-bar-restoran']           = get_term_by( 'slug', 'kafe-bar-restoran', 'offer_type' );
		$data_for_sync['offer_type_torgovye-ploshhadi']          = get_term_by( 'slug', 'torgovye-ploshhadi', 'offer_type' );
		$data_for_sync['offer_type_skladskoe-pomeshhenie']       = get_term_by( 'slug', 'skladskoe-pomeshhenie', 'offer_type' );
		$data_for_sync['offer_type_obekt-sfery-uslug']           = get_term_by( 'slug', 'obekt-sfery-uslug', 'offer_type' );
		$data_for_sync['offer_type_kommercheskogo-naznacheniya'] = get_term_by( 'slug', 'kommercheskogo-naznacheniya', 'offer_type' );
		$data_for_sync['category_garazhej-i-parkingov']          = get_term_by( 'slug', 'garazhej-i-parkingov', 'offer_category' );
		$data_for_sync['offer_type_mesto-na-parkovke']           = get_term_by( 'slug', 'mesto-na-parkovke', 'offer_type' );

		$data_for_sync['option_qtranslate_term_name'] = get_option( 'qtranslate_term_name' );

		$data = array();

		if ( $item->type == 'продажа' ) {
			$data['deal_type'] = $data_for_sync['type_sell_term'];
		} else {
			$data['deal_type'] = $data_for_sync['type_rent_term'];
		}

		$category = trim( $item->category );

		// =============================================================================
		if ( $category == 'дача' ) {
			$data['offer_category'] = $data_for_sync['category_house'];
			$data['offer_type']     = $data_for_sync['offer_type_dacha'];

		}
		if ( in_array( $category, array( 'дом', 'cottage', 'Частный дом' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_house'];
			$data['offer_type']     = $data_for_sync['offer_type_house'];
		}

		// =============================================================================

		if ( in_array( $category, array( 'квартира', 'Квартиры', 'Квартира' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_apartment'];
			$data['offer_type']     = $data_for_sync['offer_type_apartment'];
		}
		// =============================================================================
		if ( in_array( $category, array( 'земельный участок', 'участок' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_land_plot'];
			$data['offer_type']     = $data_for_sync['offer_type_land_plot'];

		}

		if ( in_array( $category, array( 'Дома и земля' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_land_plot'];
			$data['offer_type']     = $data_for_sync['offer_type_dachnyj_uchastok'];
		}


		if ( in_array( $category, array( 'участок для строительства коммерческих объектов' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_land_plot'];
			$data['offer_type']     = $data_for_sync['offer_type_kommercheskogo-naznacheniya'];
		}
		// =============================================================================
		if ( in_array( $category, array( 'commercial_real_estate' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-pomeshhenie'];

		}

		if ( in_array( $category, array( 'Бизнес-центр' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-zdanie'];
		}

		if ( in_array( $category, array( 'здание' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-zdanie'];

		}


		if ( in_array( $category, array( 'Кафе/Ресторан' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_kafe-bar-restoran'];
		}

		if ( in_array( $category, array( 'Магазин', 'торговая площадка', 'Торговое место' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_torgovye-ploshhadi'];

		}

		if ( in_array( $category, array( 'Офис', 'офис' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-pomeshhenie'];

		}

		if ( in_array( $category, array( 'помещение свободного назначения' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_ofisnoe-pomeshhenie'];

		}


		if ( in_array( $category, array( 'Складской комплекс' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_skladskoe-pomeshhenie'];

		}

		if ( in_array( $category, array( 'Спортивный зал', 'Спортивный комплекс' ) ) ) {
			$data['offer_category'] = $data_for_sync['category_commercial-real-estate'];
			$data['offer_type']     = $data_for_sync['offer_type_obekt-sfery-uslug'];

		}

		//		if ( false !== mb_strpos( $data['content'], 'Паркоместо' ) || false !== mb_strpos( $data['content'], 'паркинге' ) ) {
		//			$data['offer_category'] = $data_for_sync['category_garazhej-i-parkingov'];
		//			$data['offer_type']     = $data_for_sync['offer_type_mesto-na-parkovke'];
		//		}


		/* Taxonomies */
		if ( $data['deal_type'] ) {
			wp_set_post_terms( $post_id, array( $data['deal_type']->term_id ), 'deal_type' );
			update_post_meta( $post_id, $yoast_prefix . 'deal_type', $data['deal_type']->term_id );
		}

		if ( $data['offer_category'] ) {
			wp_set_post_terms( $post_id, array( $data['offer_category']->term_id ), 'offer_category' );
			update_post_meta( $post_id, $yoast_prefix . 'offer_category', $data['offer_category']->term_id );
		}

		do_action( 'save_post', $post_id );


	}

	private static function get_posts( $page = 1, $posts_per_page, $args ) {
		if ( empty( $args['p'] ) ) {
			$args['posts_per_page'] = $posts_per_page;
			$args['paged']          = $page;
		}

		$posts = new WP_Query( $args );

		return $posts;
	}

}

WP_CLI::add_command( 'deco_sync_offers_without_category', 'Sync_Missing_Offers' );