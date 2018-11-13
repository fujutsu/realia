<?php

namespace Deco\Bundles\Offers\Modules\Offers_Xml\Includes;

class Reports {

	public static function init() {

		add_filter( 'deco_get_offers_aspo_biz_report_xml', __CLASS__ . '::get_offers_aspo_biz_report_xml' );

	}

	public static function get_offers_aspo_biz_report_xml() {

		global $wpdb;

		// Get Aspo.Biz feeds' ids
		$feeds           = $wpdb->get_results( "SELECT id, last_update FROM {$wpdb->prefix}deco_xml_list WHERE feed_standard = 'Aspo_Biz'" );
		$imported_offers = array();

		foreach ( $feeds as $feed ) {
			$imported_offers['date'] = $feed->last_update;

			// Get offers by feed id
			$offers = $wpdb->get_results( "SELECT post_id, internal_id FROM {$wpdb->prefix}deco_xml_data_offers WHERE sync_id = '$feed->id'" );

			foreach ( $offers as $offer ) {

				if ( $offer->post_id == 0 ) {
					continue;
				}
				$destatistics = apply_filters( 'deco_get_post_counters', 0, $offer->post_id );
				$offer_views  = $destatistics['views_counts'];
				$paid_offer   = $wpdb->get_col( "select datetime from {$wpdb->base_prefix}deco_users_offers_promotion where post_id = $offer->post_id and active = 1 limit 1" );
				$offer        = array(
					'post_id'     => $offer->post_id,
					'internal_id' => $offer->internal_id,
					'permalink'   => get_permalink( $offer->post_id ),
					'views'       => $offer_views ?: '0'
				);
				if ( $paid_offer ) {
					$offer['paid_date_end'] = date( 'Y-m-d H:i:s', strtotime( '+1 month', strtotime( $paid_offer[0] ) ) );
				}

				$imported_offers[] = $offer;
				unset( $offer );
			}

			unset( $offers );

		}

		return $imported_offers;

	}

}