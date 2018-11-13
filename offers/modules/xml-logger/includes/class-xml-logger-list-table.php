<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class XML_Logger_List_Table extends WP_List_Table {

	private static $count = 0;
	private static $per_page = 20;

	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$custom_per_page = $this->get_items_per_page( 'xml_logs_per_page', self::$per_page );
		$current_page    = $this->get_pagenum();

		self::$count = $custom_per_page * ( $current_page - 1 );

		$data = $this->table_data( $custom_per_page, $current_page );

		$this->set_pagination_args( array(
			'total_items' => $data['total_items'],
			'per_page'    => $custom_per_page
		) );


		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data['results'];
	}

	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since  3.1.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {

		if ( $which == 'top' ) {
			?>
            <form id="posts-filter" method="get">
                <input type="hidden" name="page" value="xml_logger"/>

                <div class="tablenav <?php echo esc_attr( $which ); ?>">

					<?php
					$this->extra_tablenav( $which );
					$this->pagination( $which );
					?>

                    <br class="clear"/>
                </div>
            </form>
			<?php
		}
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since  3.1.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		echo '<div class="alignleft actions">';

		$agency = isset( $_GET['agency'] ) && ! empty( $_GET['agency'] ) ? $_GET['agency'] : '';
		echo '<input type="text" name="agency" placeholder="' . 'Агентство' . '" value="' . $agency . '" class="">';

		submit_button( __( 'Search' ), '', 'filter_action', false, array( 'id' => 'agency-search-submit' ) );
		echo '</div>';

	}

	private function table_data( $per_page = 20, $page_number = 1 ) {

		global $wpdb;

		$offset = ( absint( $page_number ) - 1 ) * $per_page;

		$agency = isset( $_GET['agency'] ) && ! empty( $_GET['agency'] ) ? $_GET['agency'] : '';

		$where = '';

		if ( $agency ) {
			$agencies_search = $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE display_name LIKE '%{$agency}%'" );
			if ( ! empty( $agencies_search ) ) {
				$agencies_ids = implode( ',', $agencies_search );
			}
			$where .= " AND user_id IN ('$agencies_ids')";
		}

		$results = $wpdb->get_results( "SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}deco_xml_logger WHERE 1=1 {$where} ORDER BY id DESC LIMIT {$offset}, {$per_page}" );

		$total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );

		return array(
			'results'     => $results,
			'total_items' => $total_items,
		);
	}

	public function get_columns() {

		$columns = array(
			'user_id'      => 'Агентство',
			'xml_type'     => 'Тип XML',
			'xml_standard' => 'Стандарт XML',
			'start_sync'   => 'Начало синхронизации',
			'finish_sync'  => 'Окончание синхронизации',
			'xml_count'    => 'Всего найдено в XML',
			'published'    => 'Опубликовано',
			'moderated'    => 'На модерации',
			'new'          => 'Новых',
			'deleted'      => 'Удалено',
//			'errors'          => 'Ошибки',
//			'create_datetime' => 'Лог создан',
		);

		return $columns;
	}


	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {

			case 'user_id' :
				$agency = get_userdata( $item->user_id );
				echo $agency->display_name;
				break;

			case 'xml_type' :
				$xml_types = array(
					'standard'               => 'Стандартный',
					'commercial_real_estate' => 'Коммерческая недвижимость'
				);
				echo $xml_types[ $item->xml_type ];
				break;

			case 'xml_standard' :
				$xml_standards = array(
					'Aspo_Biz'            => 'Aspo.Biz',
					'Yandex_Nedvizhimost' => 'Яндекс.Недвижимость',
					'Lun_UA'              => 'Лун',
					'Dom_Ria'             => 'Дом.Риа'
				);
				echo $xml_standards[ $item->xml_standard ];
				break;

			case 'start_sync' :
				echo $item->start_sync;
				break;

			case 'finish_sync':
				echo $item->finish_sync;
				break;

			case 'xml_count' :
				echo $item->xml_count;
				break;

			case 'published' :
				echo $item->published;
				break;

			case 'moderated' :
				echo $item->moderated;
				break;

			case 'new' :
				echo $item->new;
				break;

			case 'deleted':
				echo $item->deleted;
				break;
		}

	}

}