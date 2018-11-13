<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class Realia_Export_XML extends \WP_CLI_Command {

	public function export_xml_mitula() {
		do_action( 'deco_export_xml_mitula' );
	}

	public function export_xml_lun() {
		do_action( 'deco_export_xml_lun' );
	}

}

\WP_CLI::add_command( 'realia_export_xml', 'Realia_Export_XML' );