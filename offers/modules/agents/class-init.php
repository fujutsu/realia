<?php

namespace Deco\Bundles\Offers\Modules\Agents;

class Init {

	static $bundle_uri;
	static $bundle_path;
	static $post_types = array();
	static $table;

	public static function init() {
		global $wpdb;
		self::$table = $wpdb->base_prefix . 'offers_agents';

		self::$bundle_uri  = str_replace( ABSPATH, site_url() . '/', dirname( __FILE__ ) ) . '/';
		self::$bundle_path = dirname( __FILE__ ) . '/';

		self::$post_types = array(
			'offers',
		);

		add_action( 'admin_print_scripts', __CLASS__ . '::admin_scripts' );
		add_action( 'admin_print_styles', __CLASS__ . '::admin_styles' );

		add_action( 'add_meta_boxes', __CLASS__ . '::meta_block' );
		add_action( 'save_post', __CLASS__ . '::save_post', 10 );
		add_action( 'wp_trash_post', __CLASS__ . '::trash_offer', 10, 1 );
		add_action( 'untrash_post', __CLASS__ . '::untrash_offer', 10, 1 );
		add_action( 'delete_post', __CLASS__ . '::delete_offer', 10, 1 );

		add_action( 'wp_ajax_deco_find_agents', __CLASS__ . '::find_users' );

		add_filter( 'deco_get_rieltors_by_agency', __CLASS__ . '::get_realtors_by_agency' );

	}

	public static function admin_scripts() {
		wp_register_script( 'agents-script', self::$bundle_uri . 'assets/js/script.js' );
		wp_enqueue_script( 'agents-script' );
		$global_vars = array_merge(
			array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) )
//				array( 'back_text' => __( 'Back', \Deco_Site::$textdomain ) ),
//				array( 'error_text' => __( 'ERROR, try later', \Deco_Site::$textdomain ) )
		);
		wp_localize_script( 'agents-script', 'deco_agents', $global_vars );
	}

	public static function admin_styles() {
		wp_register_style( 'agents-styles', self::$bundle_uri . 'assets/css/style.css' );
		wp_enqueue_style( 'agents-styles' );
	}

	public static function find_users() {
		global $wpdb;
		$result = array();

		if ( ! empty( $_POST['s'] ) ) {
			$search = isset( $_POST['s'] ) ? $_POST['s'] : '';

			$quer = "SELECT u.ID, u.display_name, u.user_nicename, u.user_login, u.user_email FROM `{$wpdb->base_prefix}users` AS u INNER JOIN `{$wpdb->base_prefix}usermeta` AS um ON u.ID = um.user_id WHERE (((um.meta_key = 'wp_capabilities' AND `meta_value` LIKE '%agent%') OR (um.meta_key = 'wp_capabilities' AND `meta_value` LIKE '%agency%'))  AND (u.user_login LIKE '%" . $search . "%' OR u.user_email LIKE '%" . $search . "%' OR u.user_nicename LIKE '%" . $search . "%' OR u.display_name LIKE '%" . $search . "%' OR (um.meta_key = 'first_name' AND `meta_value` LIKE '%" . $search . "%') OR (um.meta_key = 'last_name' AND `meta_value` LIKE '%$search%'))) GROUP BY u.ID";

			$users = $wpdb->get_results( $quer );

			if ( ! empty( $users ) ) {
				foreach ( $users as $item ) {
					if ( ! empty( $item->display_name ) ) {
						$username = $item->display_name;
					} elseif ( ! empty( $item->user_nicename ) ) {
						$username = $item->user_nicename;
					} else {
						$username = $item->user_login;
					}

					if ( empty( $username ) ) {
						$username = get_user_meta( $item->ID, 'nickname', true );
					}

					if ( strpos( $username, '@' ) ) {
						$username = substr( $username, 0, strpos( $username, '@' ) );
					}

					$result[] = array(
						'ID'     => $item->ID,
						'name'   => $username,
						'avatar' => '<img src="' . get_avatar_url( $item->ID ) . '" width="45" height="45"/>',
//							'email' => $item->user_email
					);
				}
			}
		}

		if ( ! empty( $result ) ) {
			$res['users'] = $result;
		} else {
			$res['status'] = 204;
		}

		echo json_encode( $res );
		die();
	}

	public static function meta_block( $post_type ) {
		if ( in_array( $post_type, self::$post_types ) ) {
			add_meta_box( 'deco_manual_agents', 'Агенты', __CLASS__ . '::manual_agents', $post_type, 'side', 'core' );
		}
	}

	public static function manual_agents( $post ) {

		global $wpdb;
		$users          = $wpdb->get_results( "SELECT oa.user_id, oa.price, u.display_name FROM `" . self::$table . "` AS oa INNER JOIN `{$wpdb->base_prefix}users` AS u ON oa.user_id=u.ID WHERE post_id = {$post->ID}" );
		$deco_users_ids = get_post_meta( $post->ID, 'deco_users_ids', true );
//		$user_ids = json_decode( stripslashes( $deco_users_ids ) );
		?>

		<div>
			<input class="hide-if-js" type="text" name="deco_users_ids" id="deco_users_ids" value='<?php echo $deco_users_ids; ?>'>
			<button id="deco_open_find_agents_button" class="button button-small hide-if-no-js">Добавить агента</button>
			<span class="hide-if-js"></span>
		</div>

		<ul id="ul_agents" class="tagchecklist ui-sortable">
			<?php if ( ! empty( $users ) ) : ?>
				<?php foreach ( $users as $user ) : ?>
					<li data-id="<?php echo $user->user_id; ?>" class="offer-agent">
						<p class="offer-agent-avatar">
							<img src="<?php echo get_avatar_url( $user->user_id ); ?>" width="45" height="45">
						</p>

						<p class="offer-agent-meta">
							<span><?php echo $user->display_name; ?></span>
							<span class="cost"><?php echo $user->price; ?></span>
						</p>

						<p>
							<a class="erase_yyarpp">X</a>
						</p>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>

		<span class="plugins widefat"><a href="javascript:void(0);" id="deco_delete_agents" class="delete hide-if-no-js">
				Очистить список
			</a></span>

		<?php
	}

	public static function save_post( $post_id ) {
		global $wpdb;

		$post_type = isset( $_POST['post_type'] ) ? $_POST['post_type'] : get_post_type( $post_id );

		if ( $post_type == 'offers' ) {

			if ( get_post_status( $post_id ) == 'trash' || ! isset( $_POST ) || empty( $_POST ) ) {
				return;
			}

			$blog_id = get_current_blog_id();

			// Added for bulk actions
			$deco_users_ids = isset( $_POST['deco_users_ids'] ) ? $_POST['deco_users_ids'] : get_post_meta( $post_id, 'deco_users_ids', true );

			// Remove all records by post_id and blog_id (for multisite)
			$wpdb->delete( self::$table,
				array(
					'post_id' => $post_id,
					'blog_id' => $blog_id
				)
			);

			if ( $deco_users_ids ) {
				$user_ids = json_decode( stripslashes( $deco_users_ids ) );
			} else {
				$user_id = get_post_meta( $post_id, 'deco_offer_added_user_id', true );
				if ( intval( $user_id ) ) {

					$cost = isset( $_POST['deco_offer_price'] ) ? $_POST['deco_offer_price'] : get_post_meta( $post_id, 'deco_offer_price', true );

					$cost = str_replace( ',', '.', $cost );
					$cost = is_float( $cost ) ? floatval( $cost ) : $cost;
					$cost = is_int( $cost ) ? intval( $cost ) : $cost;

					$data = new \stdClass();

					$data->id   = $user_id;
					$data->cost = $cost;

					$user_ids[] = $data;

					$deco_users_ids = json_encode( $user_ids );
				}
			}

			if ( ! empty( $user_ids ) ) {
				foreach ( $user_ids as $item ) {
					$user_id   = $item->id;
					$agency_id = intval( get_user_meta( $user_id, 'deco_agency_term_id', true ) );
					$wpdb->insert(
						self::$table,
						array(
							'post_id'   => $post_id,
							'user_id'   => $item->id,
							'price'     => $item->cost,
							'blog_id'   => $blog_id,
							'agency_id' => $agency_id,
							'active'    => 1
						)
					);
				}
				if ( $deco_users_ids ) {
					update_post_meta( $post_id, 'deco_users_ids', $deco_users_ids );
				}

			}

		}

	}

	public static function trash_offer( $post_id ) {
		global $wpdb;
		$blog_id = get_current_blog_id();

		$post_type   = get_post_type( $post_id );
		$post_status = get_post_status( $post_id );

		if ( $post_type == 'offers' && in_array( $post_status, array( 'publish', 'draft', 'future' ) ) ) {
			$wpdb->update( self::$table,
				array(
					'active' => 0,
				),
				array(
					'post_id' => $post_id,
					'blog_id' => $blog_id
				)
			);
		}
	}

	public function untrash_offer( $post_id ) {
		global $post, $wpdb;

		$blog_id = get_current_blog_id();

		if ( empty( $post ) ) {
			$post = get_post( $post_id );
		}

		if ( $post->post_type != 'offers' ) {
			return;
		}

		$post_status = get_post_meta( $post_id, '_wp_trash_meta_status', true );

		if ( $post_status == 'publish' ) {
			$wpdb->update( self::$table,
				array(
					'active' => 1,
				),
				array(
					'post_id' => $post_id,
					'blog_id' => $blog_id
				)
			);
		}
	}

	public function delete_offer( $post_id ) {
		global $post, $wpdb;

		$blog_id = get_current_blog_id();

		if ( empty( $post ) ) {
			$post = get_post( $post_id );
		}

		if ( $post->post_type != 'offers' ) {
			return;
		}

		$wpdb->delete( self::$table,
			array(
				'post_id' => $post_id,
				'blog_id' => $blog_id
			)
		);
	}

	public static function get_realtors_by_agency( $args ) {
		global $wpdb;
		$agency_id = isset( $args['agency_id'] ) ? intval( $args['agency_id'] ) : 0;
		$agents    = count( $args['agents'] ) > 0 ? $args['agents'] : array();
		$is_agency = apply_filters( 'deco_is_user_role_by_id', $agency_id, array( 'agency' ) );

		if ( $is_agency ) {

			$realtors_result = array();
			$realtors        = wp_get_object_terms( $agency_id, 'agency' );

			foreach ( $realtors as $realtor_term_id ) {
				$realtors_users = $wpdb->get_results( "select * from wp_term_relationships where term_taxonomy_id = {$realtor_term_id->term_taxonomy_id} and object_id not in ($agency_id)" );

				foreach ( $realtors_users as $realtors_user_item ) {
					$agent   = array();
					$user_id = $realtors_user_item->object_id;
					$user    = new \TimberUser( $user_id );

					if ( empty( $user->ID ) ) {
						continue;
					}

					$agent['id']   = $user->ID;
					$agent['name'] = $user->display_name;

					if ( ! empty( $agent['name'] ) ) {

						if ( ! empty( $user->deco_agent_phone ) ) {
							$user_phone = $user->deco_agent_phone[0];
						} elseif ( ! empty( $user->billing_phone ) ) {
							$user_phone = $user->billing_phone;
						} else {
							$user_phone = '';
						}

						$agent['phone'] = trim( str_replace( '+38', '', $user_phone ) );

						$agent['avatar_url'] = get_avatar_url( $user->ID );
						if ( $agent['avatar_url'] == get_template_directory_uri() . '/assets/img/defaults/default_avatar.png' && $is_agency ) {
							$deco_agency_logo = get_user_meta( $user->ID, 'deco_agency_logo', true );
							if ( $deco_agency_logo ) {
								$agent['avatar_url'] = $deco_agency_logo;
							}
						}

						$agent['url'] = trailingslashit( home_url( '/agent/' . $user->user_nicename ) );


					}
					$realtors_result[] = $agent;
				}
			}

			if ( count( $realtors_result ) > 0 ) {
				foreach ( $realtors_result as $item ) {
					$agents[] = $item;
				}
			}

			return $agents;
		}

		return $agents;
	}

}