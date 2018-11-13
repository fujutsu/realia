<?php

namespace Deco\Bundles\Offers;

class Init {

	public static function static_init() {
		add_action( 'delete_post', __CLASS__ . '::delete_offers', 10 );
		add_filter( 'deco_get_offer_filling_percentage', __CLASS__ . '::get_offer_filling_percentage', 10 );
		Modules\Archive_Offers\Init::init();
		Modules\Offers_Xml\Init::init();
		Modules\Offers_Custom_Bulk_Actions\Init::static_init();
		Modules\Agents\Init::init();
		Modules\Import_User_Offers\Init::init();
		Modules\Edit_Offers\Init::init();
		Modules\Add_Offer\Init::init();
		Modules\Post_Type\Init::init();
		Modules\XML_Manager\Init::init();
		Modules\XML_Logger\Init::init();

		Modules\Export_Offers_Xml\Init::init();
	}

	public static function delete_offers( $post_id ) {
		global $wpdb;

		if ( get_post_type( $post_id ) === 'offers' ) {
			$blog_id = get_current_blog_id();

			$wpdb->query( "delete from {$wpdb->base_prefix}offers_agents where post_id = $post_id and blog_id = $blog_id" );
			$wpdb->query( "delete from {$wpdb->base_prefix}favorites where post_id = $post_id and blog_id = $blog_id" );
		}
	}

	public static function get_offer_filling_percentage( $args ) {
		global $wpdb;

		$post_id = $args['post_id'];
		if ( empty( $post_id ) ) {
			return 0;
		}

	}


}