<?php

namespace Deco\Bundles\Offers\Modules\XML_Manager\Includes;

class Post_Type {
	private static $post_type = 'xml_list';

	public static function init() {
		if ( is_admin() ) {
			self::register();

			if ( false !== strpos( $_SERVER['REQUEST_URI'], 'post-new.php?post_type=xml_list' ) || ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) === 'xml_list' ) ) {
				add_action( 'admin_head', __CLASS__ . '::css', 99 );
			}
		}
		add_filter( 'manage_edit-' . self::$post_type . '_columns', __CLASS__ . '::add_columns' );
		add_action( 'manage_posts_custom_column', __CLASS__ . '::manage_columns', 10, 2 );
		add_action( 'save_post', __CLASS__ . '::save' );
		add_action( 'shutdown', __CLASS__ . '::shutdown' );
	}

	public static function register() {

		$labels = array(
			'name'               => 'Менеджер XML',
			'singular_name'      => 'Менеджер XML',
			'add_new'            => 'Добавить',
			'add_new_item'       => 'Добавление XML',
			'edit_item'          => 'Редактирование XML',
			'new_item'           => 'Новый XML',
			'view_item'          => '',
			'search_items'       => 'Поиск',
			'not_found'          => 'Ничего не найдено',
			'not_found_in_trash' => 'Ничего не найдено в корзине',
		);

		register_post_type( self::$post_type, array(
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => false,
			'has_archive'       => false,
			'hierarchical'      => false,
			'supports'          => array( '' ),
		) );

	}

	public static function add_columns( $columns ) {

		$new_columns = array(
			'cb'                   => $columns['cb'],
//			'title'                   => $columns['title'],
			'xml_list_agency'      => 'Агенство',
			'xml_list_url'         => 'URL',
			'xml_list_active'      => 'Активно',
			'xml_list_descr'       => 'Описание',
			'xml_list_standard'    => 'Стандарт',
			'xml_list_type'        => 'Тип',
			'xml_list_update_time' => 'Периодичность',
			'xml_list_stats'       => 'Состояние объектов',
			'xml_list_process'     => '',
//			'date'                     => $columns['date'],
		);

		return $new_columns;
	}

	public static function manage_columns( $column, $id ) {
		global $wpdb;
		$post_xml_lit   = get_post( $id );
		$table_xml_list = $wpdb->get_row( "select * from wp_deco_xml_list where post_id = $id" );
		$agency_id      = get_post_meta( $id, 'xml_list_agency', true );
		$user           = get_userdata( $agency_id );

		switch ( $column ) {

			case 'xml_list_agency':
				if ( ! $agency_id && $table_xml_list->feed_standard === 'Aspo_Biz' ) {
					echo '<a href="' . admin_url( 'post.php?post=' . $id . '&action=edit' ) . '">Aspo.Biz</a>';
				} elseif ( $agency_id ) {
					echo '<a href="' . admin_url( 'post.php?post=' . $id . '&action=edit' ) . '">(ID:' . $agency_id . ') ' . $user->display_name . '</a>';
				}
				?>
                <div class="row-actions">
					<span class="edit">
						<a href="<?php echo admin_url( 'post.php?post=' . $id . '&action=edit' ); ?>">Изменить</a>
						<?php if ( $post_xml_lit->post_status === 'publish' ) { ?>
                            |
                            <a style="color: red;" href="<?php echo admin_url( 'edit.php?post_type=xml_list&post_id=' . $id . '&status_xml=disable&nonce=' . wp_create_nonce( 'disable_xml' ) ) ?>" onclick="confirm('Вы подтверждаете выключенияч xml?');">Выключить</a>
						<?php } else { ?>
                            |
                            <a style="color: green;" href="<?php echo admin_url( 'edit.php?post_type=xml_list&post_id=' . $id . '&status_xml=enable&nonce=' . wp_create_nonce( 'enable_xml' ) ); ?>" onclick="confirm('Вы подтверждаете включение xml?');">Включить</a>
						<?php } ?>

                        | <a style="color: green;" href="<?php echo admin_url( 'edit.php?post_type=xml_list&post_id=' . $id . '&update_now=now&nonce=' . wp_create_nonce( 'update_now' ) ); ?>" onclick="confirm('Вы подтверждаете отправку на немедленную синхронизацию?');">Немедленная синхронизация</a>
					</span>
                </div>
				<?php


				break;

			case 'xml_list_descr':
				echo get_post_meta( $id, 'xml_list_descr', true );
				break;
			case 'xml_list_url':
				if ( $table_xml_list->link ) {
					echo '<a href="#" title="Посмотреть XML в окне" onclick="window.open(\'' . $table_xml_list->link . '\', \'XML анегства ' . $user->display_name . '\',\'location=no,width=1200,height=600,scrollbars=yes\'); return false;">' . $table_xml_list->link . '</a>';

				}

				break;
			case 'xml_list_standard':
				$feed_standard['Yandex_Nedvizhimost'] = 'Яндекс.Недвижимость';
				$feed_standard['Lun_UA']              = 'Лун';
				$feed_standard['Dom_Ria']             = 'Дом.Риа';
				$feed_standard['Aspo_Biz']            = 'Aspo.Biz';

				if ( isset( $feed_standard[ $table_xml_list->feed_standard ] ) ) {
					echo $feed_standard[ $table_xml_list->feed_standard ];
				}
				break;
			case 'xml_list_type':
				$feed_type['standard']               = 'Стандартный';
				$feed_type['commercial_real_estate'] = 'Коммерческая недвижимость';

				if ( isset( $feed_type[ $table_xml_list->feed_type ] ) ) {
					echo $feed_type[ $table_xml_list->feed_type ];
				}
				break;
			case 'xml_list_update_time':
				$update_frequency[1]              = 'Раз в час';
				$update_frequency[24]             = 'Раз в сутки';
				$update_frequency[0]              = 'Однократно';
				$table_xml_list->update_frequency = (int) $table_xml_list->update_frequency;
				if ( isset( $update_frequency[ $table_xml_list->update_frequency ] ) ) {
					$update_frequency_name = $update_frequency[ $table_xml_list->update_frequency ];
				}
				echo $update_frequency_name;
				break;
			case 'xml_list_stats':
				if ( $table_xml_list->is_active ) {
					$posts_moderation = $wpdb->get_var( "SELECT COUNT(post_status) FROM wp_posts WHERE ID IN(SELECT post_id FROM wp_deco_xml_data_offers WHERE post_id > 0 AND sync_id = $table_xml_list->id) AND post_status in ('pending', 'address_check', 'draft')" );
					$posts_publish    = $wpdb->get_var( "SELECT COUNT(post_status) FROM wp_posts WHERE ID IN(SELECT post_id FROM wp_deco_xml_data_offers WHERE post_id > 0 AND sync_id = $table_xml_list->id) AND post_status = 'publish' " );

					?>
                    <table>
                        <tr>
                            <td style="font-weight: bold;"><?php echo $table_xml_list->count; ?> всего</td>
                        </tr>
                        <tr>
                            <td style="color: green;"><?php echo $table_xml_list->created_counts; ?> новых</td>
                        </tr>
                        <tr>
                            <td style="color: orange;"><?php echo $posts_moderation; ?> на модерации</td>
                        </tr>
                        <tr>
                            <td style="color: limegreen;"><?php echo $posts_publish; ?> опубликовано</td>
                        </tr>
                        <tr>
                            <td style="color: #0B9CE3"><?php echo $table_xml_list->updated_counts; ?> обновлено</td>
                        </tr>
                        <tr>
                            <td style="color: red;"><?php echo $table_xml_list->removed_counts; ?> удалено</td>
                        </tr>
                    </table>
					<?php
				}
				break;

			case 'xml_list_process':
				if ( $table_xml_list->is_active ) {
					$processing_status = $wpdb->get_var( "SELECT status FROM wp_deco_xml_list WHERE id IN( SELECT sync_id FROM wp_deco_xml_queue_sync WHERE status = 'processing' AND sync_id = $table_xml_list->id )" );

					if ( $processing_status == 'started_import' ) {
						echo 'Импорт...';
					} elseif ( $processing_status == 'started_delete' ) {
						echo 'Удаление...';
					} elseif ( $processing_status == 'started_sync' ) {
						echo 'Синхронизация...';
					}
				}
				break;

			case 'xml_list_active':
				if ( $post_xml_lit->post_status === 'publish' ) {
					echo '<span style="color: green;">Да</span>';
				} else {
					echo '<span style="color: red;">Нет</span>';
				}
				break;
		}
	}

	public static function save( $post_id ) {
		global $wpdb;

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && get_post_type( $post_id ) === 'xml_list' ) {

			$xml_list_agency = (int) $_POST['xml_list_agency'];

			if ( ! $xml_list_agency ) {
				$xml_list_agency          = (int) get_post_meta( $post_id, 'xml_list_agency', true );
				$_POST['xml_list_agency'] = $xml_list_agency;
			}

			if ( ! $xml_list_agency && ! $_POST['xml_list_feed_type'] === 'Aspo_Biz' ) {
				update_post_meta( $post_id, 'xml_list_agency_error', 1 );

				return;
			}
			delete_post_meta( $post_id, 'xml_list_agency_error' );

			$xml_list_link = trim( $_POST['xml_list_link'] );

			$xml_list_active = empty( $_POST['xml_list_active'] ) ? 0 : 1;

			$xml_list_file             = (int) $_POST['xml_list_file'];
			$xml_list_feed_type        = $_POST['xml_list_feed_type'];
			$xml_list_feed_standard    = $_POST['xml_list_feed_standard'];
			$xml_list_update_frequency = $_POST['xml_list_update_frequency'];
//			$xml_list_descr            = $_POST['xml_list_descr'];

			$link = $xml_list_link;

			// Получим загруженный файл xml
			// Файл приоритетней чем линк
			if ( $xml_list_file ) {
				$link = wp_get_attachment_url( $xml_list_file );
			}

			$xml_data                     = apply_filters( 'deco_offers_xml_check_feed', $link );
			$_POST['xml_list_link_error'] = true; // Всегда ошибка линка
			if ( $xml_data ) {
				$_POST['xml_list_link_error'] = false; // Данные успешно преобразованы, xml верный

				if ( $wpdb->get_row( "select id from wp_deco_xml_list where post_id = $post_id" ) ) {
					$wpdb->update(
						'wp_deco_xml_list',
						array(
							'user_id'          => $xml_list_agency,
							'feed_standard'    => $xml_list_feed_standard,
							'feed_type'        => $xml_list_feed_type,
							'link'             => $link,
							'is_active'        => $xml_list_active,
							'is_file'          => empty( $xml_list_file ) ? 0 : 1,
							'update_frequency' => (int) $xml_list_update_frequency,
						),
						array(
							'post_id' => $post_id,
						)
					);
				} else if ( $wpdb->get_row( "select id from wp_deco_xml_list where link = '$link' and user_id = $xml_list_agency" ) ) {
					// привязка к текущему
					$wpdb->update(
						'wp_deco_xml_list',
						array(
							'post_id' => $post_id,
						),
						array(
							'link'    => $xml_list_link,
							'user_id' => $xml_list_agency,
						)
					);
				} else {
					$wpdb->insert(
						'wp_deco_xml_list',
						array(
							'post_id'          => $post_id,
							'user_id'          => $xml_list_agency,
							'feed_standard'    => $xml_list_feed_standard,
							'feed_type'        => $xml_list_feed_type,
							'link'             => $link,
							'create_date'      => current_time( 'mysql' ),
							'is_active'        => $xml_list_active,
							'is_file'          => empty( $xml_list_file ) ? 0 : 1,
							'update_frequency' => (int) $xml_list_update_frequency,
						)
					);
				}
			} else if ( $wpdb->get_row( "select id from wp_deco_xml_list where link = '$link' and user_id = $xml_list_agency" ) ) {
				// привязка к текущему
				$wpdb->update(
					'wp_deco_xml_list',
					array(
						'post_id'          => $post_id,
						'feed_standard'    => $xml_list_feed_standard,
						'feed_type'        => $xml_list_feed_type,
						'link'             => $link,
						'update_frequency' => (int) $xml_list_update_frequency,
					),
					array(
						'link'    => $xml_list_link,
						'user_id' => $xml_list_agency,
					)
				);
			}
		}
	}

	// Ловим на шутдауне пост запрос на сохранение/создание поста
	// проверяем галку активен и в соответствии ставим статус xml
	public static function shutdown() {
		global $wpdb;
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['post_ID'] ) && get_post_type( $_POST['post_ID'] ) === 'xml_list' ) {
			$xml_list_active = empty( $_POST['xml_list_active'] ) ? 0 : 1;
			$post_id         = (int) $_POST['post_ID'];
			$user_id         = (int) $_POST['xml_list_agency'];


			//Ошибочный линк
			$xml_list_link_error = $_POST['xml_list_link_error'];

			// Ставим пост в черновик
			if ( $xml_list_link_error ) {
				$xml_list_active = 0;
				// Пишем об ошибке рядом с полем ввода линка
				update_post_meta( $post_id, 'xml_list_link_error', 1 );
				delete_post_meta( $post_id, 'xml_list_active' );
			} else {
				delete_post_meta( $post_id, 'xml_list_link_error' );
			}

			$data = array(
				'post_status' => 'draft'
			);
			if ( $xml_list_active ) {
				$data = array(
					'post_status' => 'publish'
				);
			}

			if ( $user_id ) {
				$user = get_userdata( $user_id );

				$data['post_title'] = $user->display_name . ' (ID: ' . $user_id . ')';
				$data['post_name']  = sanitize_title( $data['post_title'] ) . $post_id;
			} elseif ( ! $user_id && $_POST['xml_list_feed_standard'] === 'Aspo_Biz' ) {
				$data['post_title'] = 'Aspo.Biz';
				$data['post_name']  = sanitize_title( $data['post_title'] ) . $post_id;
			} else {
				// Если не задано агенство, ставим пост в черновик
				$data = array(
					'post_status' => 'draft'
				);
				delete_post_meta( $post_id, 'xml_list_active' );
			}

			$wpdb->update(
				'wp_posts',
				$data,
				array(
					'ID' => $post_id
				)
			);

		}

	}

	public static function css() {
		?>
        <style>
            #qtranxs-meta-box-lsb,
            #delete-action,
            #submitdiv .hndle,
            #submitdiv .handlediv,
            #submitdiv .misc-pub-post-status,
            #submitdiv #minor-publishing-actions,
            #visibility,
            #timestamp,
            .edit-timestamp,
            #screen-meta-links,
            #message,
            #xml_list_main_data .handlediv {
                display: none;
            }

            #submitdiv {
                position: relative;
                top: 19px;
            }

        </style>

		<?php
	}

}