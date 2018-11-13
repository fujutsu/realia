<?php

namespace Deco\Bundles\Offers\Modules\Archive_Offers\Includes;

class Custom_Table {

	static $db_version = 1;

	public static function init() {

		add_action( 'init', __CLASS__ . '::maybe_create_table' );

	}

	public static function maybe_create_table() {
		$installed_db_version = get_option( 'archive_offers_db_version', 0 );
		if ( version_compare( self::$db_version, $installed_db_version, '>' ) ) {
			self::upgrade();
		}
	}

	public static function upgrade() {
		global $wpdb;
		$table_name      = $wpdb->get_blog_prefix() . 'archive_offers';
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			$sql = "CREATE TABLE {$table_name} (
			id int(11) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			post_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			post_archive_status varchar(20) NOT NULL,
			post_archive_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			post_remove_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			post_redirect_link_from varchar(255) NOT NULL,
			post_redirect_link_from_crc32 bigint(22) DEFAULT '0',
			post_redirect_link_to varchar(255) NOT NULL,
			post_redirect_link_to_crc32 bigint(22) DEFAULT '0',
			PRIMARY KEY (id),
			UNIQUE KEY uniq_url (post_redirect_link_from_crc32,post_redirect_link_to_crc32),
			KEY post_redirect_link_from_crc32 (post_redirect_link_from_crc32),
			KEY post_redirect_link_from (post_redirect_link_from(191)),
			KEY post_redirect_link_to (post_redirect_link_to(191))
		) {$charset_collate};";

			dbDelta( $sql );
			update_option( 'archive_offers_db_version', 1 );
		}
	}

}