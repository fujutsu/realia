<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class Archive_Offers_WP_CLI extends \WP_CLI_Command {

	public function archive_offers() {
		do_action( 'deco_archive_offers' );
	}

	public function remove_archived_offers() {
		do_action( 'deco_remove_archived_offers' );
	}

	public function remove_deleted_offers() {
		do_action( 'deco_remove_deleted_offers' );
	}

}

\WP_CLI::add_command( 'realia_archive_offers', 'Archive_Offers_WP_CLI' );