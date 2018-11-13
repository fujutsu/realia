<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class Realia_XML extends \WP_CLI_Command {
	static $table_fields = array();

	public function cron_cli( $args, $assoc_args ) {
		global $wpdb;

		$current_path = ABSPATH;

		// кожну годину
		if ( $assoc_args['param'] === 'list_update' ) {

			// date_default_timezone_set('Europe/Kiev');
			$i            = 0;
			$current_time = date( 'G' );

			$xml_list = $wpdb->get_results(
				" SELECT wp_deco_xml_list_meta.sync_id, update_frequency FROM wp_deco_xml_list_meta
				  LEFT JOIN wp_deco_xml_queue_sync ON wp_deco_xml_list_meta.sync_id = wp_deco_xml_queue_sync.sync_id
				  WHERE NOT wp_deco_xml_list_meta.update_frequency = 0 AND wp_deco_xml_list_meta.is_active = 1 AND wp_deco_xml_queue_sync.sync_id IS NULL",
				ARRAY_A
			);

			if ( $xml_list ) {
				foreach ( $xml_list as $link ) {
					$i ++;
					if ( $current_time % $link['update_frequency'] === 0 ) {
						$sync_id      = $link['sync_id'];
						$update_queue = date( "Y-m-d H:i:s", time() + $i );

						$wpdb->query( "INSERT INTO wp_deco_xml_queue_sync (sync_id, update_queue) VALUES ($sync_id, '$update_queue')" );
					}
				}
			}
		} elseif ( $assoc_args['param'] === 'link_start' ) {

			$sql = "select wp_deco_xml_list.user_id, wp_deco_xml_queue_sync.sync_id from wp_deco_xml_list 
inner join wp_deco_xml_queue_sync ON wp_deco_xml_list.id = wp_deco_xml_queue_sync.sync_id 
where wp_deco_xml_queue_sync.status != 'processing' order by update_queue asc LIMIT 1";

			$last_link = $wpdb->get_row( $sql );

//			print_r( $last_link );

			if ( $last_link ) {
				$wpdb->query( "UPDATE wp_deco_xml_queue_sync SET status = 'processing' WHERE sync_id = {$last_link->sync_id}" );

				$command = "cd $current_path && wp realia_xml import_remove_sync --user_id={$last_link->user_id} --sync_id={$last_link->sync_id} --allow-root";
//				passthru( "( $command & ) >> /dev/null 2>&1" );
				passthru( $command );
			}
		}

		if ( $assoc_args['param'] === 'link_check' ) {

			sleep( 10 );
			$processing_url = $wpdb->get_row( " SELECT id as sync_id, user_id FROM wp_deco_xml_list WHERE status = 'started_sync' AND id IN( SELECT sync_id FROM wp_deco_xml_queue_sync WHERE status = 'processing' )" );

			if ( ! $processing_url ) {
				Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( 'Link checked: 1' ); // запись в лог о аварийной дозагрузке

				return;
			}
			Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( 'Link checked: 2' ); // запись в лог о аварийной дозагрузке
			exec( 'pgrep -f realia_xml', $pids ); // если досинхронизация уже запущена или преждняя еще не завершилась
			sleep( 1 );

			if ( count( $pids ) > 3 ) {
				Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( 'Link checked: 3 | proc: ' . count( $pids ) . print_r( $pids, true ) ); // запись в лог о аварийной дозагрузке

				return;
			}
			Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( 'Link checked: 4 | proc: ' . count( $pids ) . print_r( $pids, true ) ); // запись в лог о аварийной дозагрузке
			$current_posts = $wpdb->get_var( "SELECT COUNT(post_id) FROM wp_deco_xml_data_offers WHERE NOT status = 'delete' AND post_id = 0 AND sync_id = {$processing_url->sync_id}" );

			$check = $wpdb->get_row( "SELECT sync_id, remaining_sync_post FROM sync_check WHERE sync_id = {$processing_url->sync_id} AND remaining_sync_post = {$current_posts}" );

			if ( $check ) {
				$wpdb->query( "UPDATE sync_check SET sync_id = {$processing_url->sync_id}, remaining_sync_post = {$current_posts}" );
				// $wpdb->query( "UPDATE wp_deco_xml_queue_sync SET status = '' WHERE sync_id = {$processing_url->sync_id}" );

				$command = "cd $current_path && wp realia_xml sync_missing_offers --user_id={$processing_url->user_id} --sync_id={$processing_url->sync_id} --allow-root";
				passthru( "( $command & ) >> /dev/null 2>&1" );
				sleep( 1 );
				Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( 'Link checked: sync missing offers' ); // запись в лог о аварийной дозагрузке

			} else {
				$wpdb->query( "UPDATE sync_check SET sync_id = {$processing_url->sync_id}, remaining_sync_post = {$current_posts}" );
				Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( 'Link checked: 5' ); // запись в лог о аварийной дозагрузке
			}
		}
	}

	/** Sync offers */
	public function offers_sync() {
//		do_action( 'deco_offers_xml_sync_offers', 35, 1 );
//		do_action( 'deco_offers_xml_import2', 35, 1 );
//		do_action( 'deco_offers_xml_sync_offers', 235, 39 );
		do_action( 'deco_offers_xml_import_to_table', 335, 52 );
	}

	public function xml_test() {
//		$xml = wp_remote_get( 'http://xn--90asdims9k.xn--j1amh/uk/save/yrl/?type=yrl' );
//		print_r( $xml['body'] );
		$xml_data = apply_filters( 'deco_offers_xml_check_feed', 'http://xn--90asdims9k.xn--j1amh/uk/save/yrl/?type=yrl' );
		print_r( $xml_data );
		echo count( $xml_data['offer'] );


	}

	public function rm() {
		global $wpdb;

		$i     = 0;
		$posts = get_posts(
			array(
				'post_type'      => 'offers',
				'post_status'    => 'any',
				'posts_per_page' => - 1
			)
		);

		$count = count( $posts );


		foreach ( $posts as $key => $post ) {
			$i ++;

			$deco_offer_photo_list = get_post_meta( $post->ID, 'deco_offer_photo_list', true );
			if ( is_array( $deco_offer_photo_list ) && count( $deco_offer_photo_list ) > 0 ) {
				foreach ( $deco_offer_photo_list as $id => $link ) {
					wp_delete_attachment( $id, true );
				}
			}
			unset( $deco_offer_photo_list );

			wp_delete_post( $post->ID, true );
			ep_delete_post( $post->ID );
			unset( $posts[ $key ] );
			$mem = memory_get_peak_usage();
			WP_CLI::log( "Remove offers: $i of $count | mem - $mem" );
		}
	}


	public function cli_rm_xml() {
		global $wpdb;

		$user_id      = 171;
		$sync_id      = 11;
		$current_path = ABSPATH;
		$where        = '';
//		$where .= "user_id = $user_id AND sync_id = $sync_id AND status = 'delete'";
		$where .= "user_id = $user_id AND sync_id = $sync_id";
//		$where .= "user_id = $user_id";


		$sql = "select SQL_CALC_FOUND_ROWS id from wp_deco_xml_data_offers where $where limit 1";
		$wpdb->get_results( $sql );
		$found_posts = $wpdb->get_var( "SELECT FOUND_ROWS()" );


		$posts_per_page = 50;
		$pages          = ceil( $found_posts / $posts_per_page );
		$i              = 0;


		for ( $page = 1; $page <= $pages; $page ++ ) {

			$offset = ( $page - 1 ) * $posts_per_page;

			$sql    = "select id, post_id  from wp_deco_xml_data_offers where $where limit $offset,$posts_per_page";
			$offers = $wpdb->get_results( $sql );

			foreach ( $offers as $key => $post ) {
				$i ++;

				$command = "cd $current_path && wp realia_xml cli_rm_xml_single --id={$post->id} --post_id={$post->post_id}";
				passthru( "( $command & ) >> /dev/null 2>&1" );
//				passthru( $command );

				unset( $offers[ $key ] );
				$mem = memory_get_peak_usage();
				if ( class_exists( 'WP_CLI' ) ) {
					\WP_CLI::log( "Remove posts: $i of $found_posts | page $page of $pages | offer_id {$post->id} | post_id {$post->post_id} | mem - $mem" );
//					sleep( 1 );
				}
			}
		}

	}


	public function cli_rm_xml_single( $args, $assoc_args ) {
		global $wpdb;

		$id      = isset( $assoc_args['id'] ) ? (int) $assoc_args['id'] : 0;
		$post_id = isset( $assoc_args['post_id'] ) ? (int) $assoc_args['post_id'] : 0;

		if ( $id > 0 && $post_id > 0 ) {
			self::rm_xml_single( $id, $post_id );
		}
		$wpdb->query( "delete from wp_deco_xml_data_offers where id = $id" );
		$wpdb->query( "delete from wp_deco_xml_data_offermeta where data_offer_id = $id" );

	}


	public static function rm_xml( $user_id, $sync_id ) {
		global $wpdb;

		$user_id = null !== $user_id ? $user_id : 0;
		$sync_id = null !== $sync_id ? $sync_id : 0;

		$where = '';
//		$where .= "user_id = $user_id AND sync_id = $sync_id AND status = 'delete'";
		$where .= "user_id = $user_id AND sync_id = $sync_id AND status in('delete')";


		$sql = "select SQL_CALC_FOUND_ROWS id from wp_deco_xml_data_offers where $where limit 1";
		$wpdb->get_results( $sql );
		$found_posts = $wpdb->get_var( "SELECT FOUND_ROWS()" );


		$posts_per_page = 50;
		$pages          = ceil( $found_posts / $posts_per_page );
		$i              = 0;


		Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( "Remove start: sync_id $sync_id | remove offers $found_posts" );

		for ( $page = 1; $page <= $pages; $page ++ ) {

			$offset = ( $page - 1 ) * $posts_per_page;

			$sql    = "select id, post_id  from wp_deco_xml_data_offers where $where limit $offset,$posts_per_page";
			$offers = $wpdb->get_results( $sql );

			foreach ( $offers as $key => $post ) {
				$i ++;

				self::rm_xml_single( $post->id, $post->post_id );

				unset( $offers[ $key ] );
				$mem = memory_get_peak_usage();
				Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( "Remove posts: $i of $found_posts | page $page of $pages | offer_id {$post->id} | post_id {$post->post_id} | mem - $mem" );
			}
		}

		$wpdb->query( "delete from wp_deco_xml_data_offers where user_id = $user_id and status = 'delete' and sync_id = $sync_id" );
		Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( "Remove end: sync_id $sync_id " );
	}

	private static function rm_xml_single( $id, $post_id ) {
		global $wpdb;

		if ( $post_id > 0 ) {

//			Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( "Remove photo start: sync_id $sync_id | offer_id {$post->id} | post_id {$post->post_id}" );
			$deco_offer_photo_list = get_post_meta( $post_id, 'deco_offer_photo_list', true );
			if ( is_array( $deco_offer_photo_list ) && count( $deco_offer_photo_list ) > 0 ) {
				foreach ( $deco_offer_photo_list as $photo_id => $link ) {
					wp_delete_attachment( $photo_id, true );
				}
			}
			unset( $deco_offer_photo_list );

//			Deco\Bundles\Offers\Modules\Offers_Xml\Includes\Xml::wp_cli_log( "Remove post start: sync_id $sync_id | offer_id {$post->id} | post_id {$post->post_id}" );
			wp_delete_post( $post_id, true );
			// ep_delete_post( $post->post_id );
			wp_cache_flush();
			$wpdb->query( "update wp_deco_xml_data_offers set post_id = 0 where id = $id" );
			$wpdb->query( "update wp_deco_xml_data_offermeta set post_id = 0 where data_offer_id = $id" );
		}
	}

	public function reset_offers_agents() {
		global $wpdb;

		$offers = $wpdb->get_results( "select ag.* from wp_offers_agents ag where not exists (select post_id from wp_deco_xml_data_offers where post_id = ag.post_id)" );
		$i      = 0;
		$count  = count( $offers );
		foreach ( $offers as $item ) {

			$i ++;
			wp_update_post( array(
				'ID'          => $item->post_id,
				'post_author' => $item->user_id
			) );
			ep_sync_post( $item->post_id );
			wp_cache_flush();
			$mem = memory_get_peak_usage();
			WP_CLI::log( "Remove offers: $i of $count | mem - $mem" );
		}

	}

	public function add_xml( $args, $assoc_args ) {

		$user_id = isset( $assoc_args['user_id'] ) ? intval( $assoc_args['user_id'] ) : 0;
		$sync_id = isset( $assoc_args['sync_id'] ) ? intval( $assoc_args['sync_id'] ) : 0;


		do_action( 'deco_offers_xml_import_to_table', $user_id, $sync_id );

		do_action( 'deco_offers_xml_sync_threads', array(
			'user_id' => $user_id,
			'sync_id' => $sync_id
		) );
	}

	public function import_remove_sync( $args, $assoc_args ) {

		global $wpdb;

		$user_id = isset( $assoc_args['user_id'] ) ? $assoc_args['user_id'] : 0;
		$sync_id = isset( $assoc_args['sync_id'] ) ? $assoc_args['sync_id'] : 0;

		// echo "update wp_deco_xml_list set status = 'started_import', finish_sync = '' where user_id = $user_id and id = $sync_id" . PHP_EOL;
		$wpdb->query( "update wp_deco_xml_list set status = 'started_import', finish_sync = '' where user_id = $user_id and id = $sync_id" );

		// $time_start        = current_time( 'timestamp' );
		// $time_start_format = date( 'Y-m-d H:i:s', $time_start );
		// $wpdb->query( "update wp_deco_xml_list set start_sync = '$time_start_format' where user_id = $user_id and id = $sync_id" );

		do_action( 'deco_offers_xml_import_to_table', $user_id, $sync_id );

		// XML log
		$log_data = $wpdb->get_row( "SELECT * FROM wp_deco_xml_list WHERE id = '$sync_id' AND user_id = '$user_id'" );
		$log_id   = $wpdb->get_var( "SELECT id FROM wp_deco_xml_logger WHERE sync_id = '$sync_id' AND user_id = '$user_id' AND start_sync = '$log_data->start_sync'" );

		$posts_moderation = $wpdb->get_var( "SELECT COUNT(post_status) FROM wp_posts WHERE ID IN(SELECT post_id FROM wp_deco_xml_data_offers WHERE post_id > 0 AND sync_id = $sync_id) AND post_status in ('pending', 'address_check', 'draft')" );
		$posts_publish    = $wpdb->get_var( "SELECT COUNT(post_status) FROM wp_posts WHERE ID IN(SELECT post_id FROM wp_deco_xml_data_offers WHERE post_id > 0 AND sync_id = $sync_id) AND post_status = 'publish' " );

		$wpdb->update( 'wp_deco_xml_logger',
			array(
				'finish_sync' => $log_data->finish_sync,
				'xml_count'   => $log_data->count,
				'published'   => $posts_publish,
				'moderated'   => $posts_moderation,
				'new'         => $log_data->created_counts,
				'deleted'     => $log_data->removed_counts,
			),
			array(
				'id' => $log_id
			),
			array(
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d'
			),
			array(
				'%d'
			)
		);
		// End XML log

		$wpdb->query( "update wp_deco_xml_list set status = 'started_delete' where user_id = $user_id and id = $sync_id" );
		self::rm_xml( $user_id, $sync_id );

		$wpdb->query( "update wp_deco_xml_list set status = 'started_sync' where user_id = $user_id and id = $sync_id" );
		do_action( 'deco_offers_xml_sync_threads', array(
			'user_id' => $user_id,
			'sync_id' => $sync_id
		) );
	}

	public function sync_missing_offers( $args, $assoc_args ) {

		$user_id = isset( $assoc_args['user_id'] ) ? intval( $assoc_args['user_id'] ) : 0;
		$sync_id = isset( $assoc_args['sync_id'] ) ? intval( $assoc_args['sync_id'] ) : 0;

		do_action( 'deco_offers_xml_sync_threads', array(
			'user_id' => $user_id,
			'sync_id' => $sync_id
		) );

	}


	/** Многопоточная синхронизация  */

	// з консолі
	// запуск предопределенного xml
	public function sync_in_threads_mode() {

		do_action( 'deco_offers_xml_sync_threads', array(
			'user_id' => 1,
			'sync_id' => 26
		) );
	}

	// з сайту
	// Запуск цикла синхронизации, в котором запускаются отдельные процессы
	public function sync_start_threads( $args, $assoc_args ) {
		$user_id = isset( $assoc_args['user_id'] ) ? $assoc_args['user_id'] : 0;
		$sync_id = isset( $assoc_args['sync_id'] ) ? $assoc_args['sync_id'] : 0;

		do_action( 'deco_offers_xml_sync_threads', array(
			'user_id' => $user_id,
			'sync_id' => $sync_id
		) );
	}

	// запуск отдельного процесса создания/обновления поста в базе
	public function sync_start_thread_item( $args, $assoc_args ) {

		$id      = isset( $assoc_args['id'] ) ? intval( $assoc_args['id'] ) : 0;
		$user_id = isset( $assoc_args['user_id'] ) ? intval( $assoc_args['user_id'] ) : 0;
		$sync_id = isset( $assoc_args['sync_id'] ) ? intval( $assoc_args['sync_id'] ) : 0;

		do_action( 'deco_offers_xml_sync_pagination_thread', array(
			'id'      => $id,
			'user_id' => $user_id,
			'sync_id' => $sync_id
		) );
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

WP_CLI::add_command( 'realia_xml', 'Realia_XML' );


