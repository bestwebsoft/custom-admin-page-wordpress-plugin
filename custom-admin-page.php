<?php
/**
Plugin Name: Custom Admin Page by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/custom-admin-page/
Description: Add unlimited custom pages to WordPress admin dashboard.
Author: BestWebSoft
Text Domain: custom-admin-page
Domain Path: /languages
Version: 1.0.7
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
 */

/**
	© Copyright 2021  BestWebSoft  ( https://support.bestwebsoft.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See theА
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! function_exists( 'cstmdmnpg_add_pages' ) ) {
	/**
	 * Create pages for the plugin
	 */
	function cstmdmnpg_add_pages() {
		global $wpdb, $submenu, $cstmdmnpg_plugin_info, $wp_version;

		$settings = add_submenu_page(
			'edit.php?post_type=bws-admin_page',
			__( 'Custom Admin Page Settings', 'custom-admin-page' ),
			__( 'Settings', 'custom-admin-page' ),
			'manage_options',
			'custom-admin-page.php',
			'cstmdmnpg_settings_page'
		);

		add_submenu_page(
			'edit.php?post_type=bws-admin_page',
			'BWS Panel',
			'BWS Panel',
			'manage_options',
			'cstmdmnpg-bws-panel',
			'bws_add_menu_render'
		);

		if ( isset( $submenu['edit.php?post_type=bws-admin_page'] ) ) {
			$submenu['edit.php?post_type=bws-admin_page'][] = array(
				'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', 'custom-admin-page' ) . '</span>',
				'manage_options',
				'https://bestwebsoft.com/products/wordpress/plugins/custom-admin-page/?k=23e9c49f512f7a6d0900c5a1503ded4f&pn=614&v=' . $cstmdmnpg_plugin_info['Version'] . '&wp_v=' . $wp_version,
			);
		}

		add_action( 'load-' . $settings, 'cstmdmnpg_add_help_tab' );
		add_action( 'load-post-new.php', 'cstmdmnpg_add_help_tab' );
		add_action( 'load-post.php', 'cstmdmnpg_add_help_tab' );
		add_action( 'load-edit.php', 'cstmdmnpg_add_help_tab' );

		$statuses = array( 'publish' );
		if ( current_user_can( 'read_private_posts' ) ) {
			$statuses[] = 'private';
		}

		$pages = get_posts(
			array(
				'post_type'   => 'bws-admin_page',
				'post_status' => $statuses,
				'numberposts' => -1,
			)
		);

		if ( ! empty( $pages ) ) {
			/* first - add parent page, than - add_submenu_page */
			foreach ( $pages as $page ) {

				if ( empty( $page->post_title ) ) {
					$page->post_title = sprintf( '(%s)', __( 'no title', 'custom-admin-page' ) );
				}

				$post_meta = get_post_meta( $page->ID, $page->post_type, true );

				if ( ! empty( $post_meta ) ) {
					if ( empty( $post_meta['parent'] ) || $post_meta['parent'] === $page->post_title ) {
						if ( filter_var( $post_meta['icon'], FILTER_VALIDATE_URL ) ) {
							$icon = $post_meta['icon'] . '" style="max-width: 20px; max-height: 20px;';
						} else {
							$icon = $post_meta['icon'];
						}

						if ( '' === $post_meta['order'] ) {
							$post_meta['order'] = null;
						}
						if ( is_numeric( $post_meta['capability'] ) && in_array( intval( $post_meta['capability'] ), range( 0, 10 ) ) ) {
							add_menu_page( $page->post_title, $page->post_title, 'level_' . $post_meta['capability'], $page->post_name, 'cstmdmnpg_page_content', $icon, $post_meta['order'] );
						} else {
							add_menu_page( $page->post_title, $page->post_title, $post_meta['capability'], $page->post_name, 'cstmdmnpg_page_content', $icon, $post_meta['order'] );
						}
					}
				}
			}
			foreach ( $pages as $page ) {

				if ( empty( $page->post_title ) ) {
					$page->post_title = sprintf( '(%s)', __( 'no title', 'custom-admin-page' ) );
				}

				$post_meta = get_post_meta( $page->ID, $page->post_type, true );

				if ( ! empty( $post_meta ) ) {
					if ( ! empty( $post_meta['parent'] ) && $post_meta['parent'] !== $page->post_title ) {
						if ( is_numeric( $post_meta['capability'] ) && in_array( intval( $post_meta['capability'] ), range( 0, 10 ) ) ) {
							add_submenu_page( $post_meta['parent'], $page->post_title, $page->post_title, 'level_' . $post_meta['capability'], $page->post_name, 'cstmdmnpg_page_content', $post_meta['order'] );
						} else {
							add_submenu_page( $post_meta['parent'], $page->post_title, $page->post_title, $post_meta['capability'], $page->post_name, 'cstmdmnpg_page_content', $post_meta['order'] );
						}
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_transfer_data' ) ) {
	/**
	 * Function for transfer data
	 *
	 * @param string $table_name Table name.
	 */
	function cstmdmnpg_transfer_data( $table_name ) {
		global $wpdb;

		$pages = $wpdb->get_results( 'SELECT * FROM `' . $table_name . '`', ARRAY_A );

		if ( ! empty( $pages ) ) {

			$page_arr = array(
				'post_title'   => '',
				'post_name'    => '',
				'post_content' => '',
				'post_type'    => 'bws-admin_page',
				'post_status'  => '',
			);

			foreach ( $pages as $page ) {

				$page_arr['post_title'] = $page['page_title'];

				if ( cstmdmnpg_is_slug_exist( $page['page_slug'] ) ) {
					$i                 = 1;
					$page['page_slug'] = $page['page_slug'] . '-' . $i;
					while ( cstmdmnpg_is_slug_exist( $page['page_slug'] ) ) {
						$page['page_slug'] = str_replace( $i - 1, $i, $page['page_slug'] );
						++$i;
					}
				}
				$page_arr['post_name'] = $page['page_slug'];

				$page_arr['post_content'] = $page['page_content'];
				$page_arr['post_status']  = 0 === absint( $page['page_status'] ) ? 'publish' : 'trash';
				$page_id                  = wp_insert_post( $page_arr );

				$data = array(
					'capability' => $page['capability'],
					'parent'     => $page['parent_page'],
					'order'      => $page['position'],
					'icon'       => $page['icon'],
				);

				update_post_meta( $page_id, 'bws-admin_page', $data );
			}
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_is_slug_exist' ) ) {
	/**
	 * Check is new page slug exists
	 *
	 * @param string $slug New page slug.
	 */
	function cstmdmnpg_is_slug_exist( $slug ) {
		$post = array(
			'name'        => $slug,
			'post_type'   => 'bws-admin_page',
			'numberposts' => 1,
		);

		return (bool) get_posts( $post );
	}
}

if ( ! function_exists( 'cstmdmnpg_plugins_loaded' ) ) {
	/**
	 * Internationalization
	 */
	function cstmdmnpg_plugins_loaded() {
		load_plugin_textdomain( 'custom-admin-page', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'cstmdmnpg_init' ) ) {
	/**
	 * Init plugin
	 */
	function cstmdmnpg_init() {
		global $cstmdmnpg_plugin_info;

		require_once __DIR__ . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $cstmdmnpg_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$cstmdmnpg_plugin_info = get_plugin_data( __DIR__ . '/custom-admin-page.php' );
		}

		/* Function check if plugin is compatible with current WP version */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $cstmdmnpg_plugin_info, '4.5' );

		/* Register custom post type */
		register_post_type(
			'bws-admin_page',
			array(
				'labels'               => array(
					'menu_name'          => __( 'Custom Admin Page', 'custom-admin-page' ),
					'name'               => __( 'Custom Admin Page', 'custom-admin-page' ),
					'singular_name'      => __( 'Custom Admin Page', 'custom-admin-page' ),
					'all_items'          => __( 'Admin Pages', 'custom-admin-page' ),
					'add_new'            => __( 'Add New', 'custom-admin-page' ),
					'add_new_item'       => __( 'Add New Admin Page', 'custom-admin-page' ),
					'edit_item'          => __( 'Edit Admin Page', 'custom-admin-page' ),
					'search_items'       => __( 'Search pages', 'custom-admin-page' ),
					'not_found'          => __( 'No page found', 'custom-admin-page' ),
					'not_found_in_trash' => __( 'No page found in Trash', 'custom-admin-page' ),
					'item_published'     => __( 'Admin Page published', 'custom-admin-page' ),
					'item_updated'       => __( 'Admin Page updated', 'custom-admin-page' ),
				),
				'supports'             => array(
					'title',
					'editor',
				),
				'public'               => true,
				'publicly_queryable'   => false,
				'show_in_rest'         => true,
				'register_meta_box_cb' => 'cstmdmnpg_add_meta_boxes',
			)
		);

		/* Get/Register and check settings for plugin */
		if ( isset( $_GET['post_type'] ) && 'bws-admin_page' === $_GET['post_type'] ) {
			cstmdmnpg_settings();
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_is_our_cpt' ) ) {
	/**
	 * Checks wether or not current page
	 * - edit.php
	 * - post.php
	 * - post-new.php
	 *
	 * For bws-admin_page
	 */
	function cstmdmnpg_is_our_cpt() {
		return ( isset( $_GET['post_type'] ) && 'bws-admin_page' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) || ( isset( $_GET['post'] ) && 'bws-admin_page' === get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) );
	}
}

if ( ! function_exists( 'cstmdmnpg_admin_init' ) ) {
	/**
	 * Admin init
	 */
	function cstmdmnpg_admin_init() {
		global $pagenow, $bws_plugin_info, $cstmdmnpg_plugin_info, $cstmdmnpg_options;

		/* Turning WPBakery front editor off on our CPT */
		if ( function_exists( 'vc_disable_frontend' ) ) {
			vc_disable_frontend();
		}

		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array(
				'id'      => '614',
				'version' => $cstmdmnpg_plugin_info['Version'],
			);
		}

		if ( 'plugins.php' === $pagenow ) {
			if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
				cstmdmnpg_settings();
				bws_plugin_banner_go_pro( $cstmdmnpg_options, $cstmdmnpg_plugin_info, 'cstmdmnpg', 'custom-admin-page', 'f70bb9152d792af52f023f902590145e', '614', 'custom-admin-page' );
			}
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_plugin_activate' ) ) {
	/**
	 * Function for activation
	 */
	function cstmdmnpg_plugin_activate() {
		/* registering uninstall hook */
		if ( is_multisite() ) {
			switch_to_blog( 1 );
			register_uninstall_hook( __FILE__, 'cstmdmnpg_uninstall' );
			restore_current_blog();
		} else {
			register_uninstall_hook( __FILE__, 'cstmdmnpg_uninstall' );
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_settings' ) ) {
	/**
	 * Register settings function
	 */
	function cstmdmnpg_settings() {
		global $cstmdmnpg_options, $cstmdmnpg_plugin_info, $wpdb;

		/* install the option defaults */
		if ( ! get_option( 'cstmdmnpg_options' ) ) {
			$options_default = cstmdmnpg_get_options_default();
			add_option( 'cstmdmnpg_options', $options_default );
		}
		$cstmdmnpg_options = get_option( 'cstmdmnpg_options' );

		if ( ! isset( $cstmdmnpg_options['plugin_option_version'] ) || $cstmdmnpg_options['plugin_option_version'] !== $cstmdmnpg_plugin_info['Version'] ) {
			$options_default                           = cstmdmnpg_get_options_default();
			$cstmdmnpg_options['hide_premium_options'] = array();
			$cstmdmnpg_options                         = array_merge( $cstmdmnpg_options, $options_default );
			$update_option                             = true;
		}

		if ( isset( $update_option ) ) {
			update_option( 'cstmdmnpg_options', $cstmdmnpg_options );
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_get_options_default' ) ) {
	/**
	 * Get Plugin default options
	 */
	function cstmdmnpg_get_options_default() {
		global $cstmdmnpg_plugin_info;

		$default_options = array(
			'plugin_option_version'   => $cstmdmnpg_plugin_info['Version'],
			'display_settings_notice' => 1,
			'suggest_feature_banner'  => 1,
		);

		return $default_options;
	}
}


if ( ! function_exists( 'cstmdmnpg_settings_page' ) ) {
	/**
	 * Display settings page
	 */
	function cstmdmnpg_settings_page() {
		if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
			require_once __DIR__ . '/bws_menu/class-bws-settings.php';
		}
		require_once __DIR__ . '/includes/class-cstmdmnpg-settings.php';
		$page = new Cstmdmnpg_Settings_Tabs( plugin_basename( __FILE__ ) );
		if ( method_exists( $page, 'add_request_feature' ) ) {
			$page->add_request_feature();
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Custom Admin Page Settings', 'custom-admin-page' ); ?></h1>
			<?php $page->display_content(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'cstmdmnpg_custom_columns' ) ) {
	/**
	 * Add new columns
	 *
	 * @param array $columns Default columns.
	 * @return array $columns Updated columns.
	 */
	function cstmdmnpg_custom_columns( $columns ) {

		$new_columns = array(
			'capability' => __( 'Capability', 'custom-admin-page' ),
			'parent'     => __( 'Parent', 'custom-admin-page' ),
		);

		unset( $columns['date'] );

		$filtered_columns = array_merge( $columns, $new_columns );

		return $filtered_columns;
	}
}

if ( ! function_exists( 'cstmdmnpg_custom_columns_content' ) ) {
	/**
	 * Add content for new columns
	 *
	 * @param string $column Current columns=.
	 */
	function cstmdmnpg_custom_columns_content( $column ) {
		global $post, $menu;

		switch ( $column ) {
			case 'capability':
				$capability = get_post_meta( $post->ID, $post->post_type, true );

				echo ( ! empty( $capability ) ? esc_html( $capability['capability'] ) : '' );
				break;

			case 'parent':
				$parent = get_post_meta( $post->ID, $post->post_type, true );
				if ( ! empty( $parent['parent'] ) ) {
					foreach ( $menu as $menu_slug ) {
						if ( $parent['parent'] === $menu_slug[2] ) {
							$parent = $menu_slug[0];
							break;
						}
					}
				}

				echo ( ! empty( $parent ) && ! is_array( $parent ) ? esc_html( $parent ) : '' );
				break;

		}
	}
}

if ( ! function_exists( 'cstmdmnpg_custom_columns_sortable' ) ) {
	/**
	 * Add sortable for new columns
	 *
	 * @param array $columns Default columns.
	 */
	function cstmdmnpg_custom_columns_sortable( $columns ) {

		/* Comment if you need these columns to be sortable and write the functionality to make them sort right */
		unset( $columns['capability'], $columns['parent'] );

		return $columns;
	}
}

if ( ! function_exists( 'cstmdmnpg_add_meta_boxes' ) ) {
	/**
	 * Add custom fields
	 */
	function cstmdmnpg_add_meta_boxes() {
		add_meta_box( 'page_attributes', __( 'Page Attributes', 'custom-admin-page' ), 'cstmdmnpg_post_custom_box', 'bws-admin_page', 'normal', 'low' );
	}
}

if ( ! function_exists( 'cstmdmnpg_post_custom_box' ) ) {
	/**
	 * Add new metabox for post
	 */
	function cstmdmnpg_post_custom_box() {
		global $post, $menu, $cstmdmnpg_options, $cstmdmnpg_plugin_info, $wp_version;

		$page_title = get_the_title( $post->ID );
		$post_meta  = get_post_meta( $post->ID, $post->post_type, true );

		if ( ! bws_hide_premium_options_check( $cstmdmnpg_options ) ) {
			?>
			<div class="bws_pro_version_bloc">
				<div class="bws_pro_version_table_bloc">
					<div class="bws_table_bg"></div>
					<table class="form-table bws_pro_version">
						<tr>
							<th><?php esc_html_e( 'Page Slug', 'custom-admin-page' ); ?></th>
							<td>
								<input disabled="disabled" type="text" name="cstmdmnpg_page_slug" value="<?php echo ! empty( $post->post_name ) ? esc_html( $post->post_name ) : ''; ?>" />
							</td>
						</tr>
					</table>
				</div>
				<div class="bws_pro_version_tooltip">
					<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/custom-admin-page/?k=23e9c49f512f7a6d0900c5a1503ded4f&amp;pn=614&amp;v=<?php echo esc_attr( $cstmdmnpg_plugin_info['Version'] ); ?>&amp;wp_v=<?php echo esc_attr( $wp_version ); ?>" target="_blank" title="Custom Admin Page Pro">
						<?php esc_html_e( 'Upgrade to Pro', 'custom-admin-page' ); ?>
					</a>
					<div class="clear"></div>
				</div>
			</div>
			<?php
		}
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Capability', 'custom-admin-page' ); ?> *</th>
				<td>
					<table>
						<tr>
							<td style="padding: 0;">
								<fieldset>
									<label>
										<input checked="checked" id="cstmdmnpg_capability_level" class="bws_option_affect" data-affect-show=".cstmdmnpg_to_level" data-affect-hide=".cstmdmnpg_to_capability" type="radio" name="cstmdmnpg_capability_type" value="level" <?php checked( isset( $post_meta['capability'] ) && is_numeric( $post_meta['capability'] ), true ); ?> />
										<?php esc_html_e( 'Level', 'custom-admin-page' ); ?>
									</label><br>
									<div class="cstmdmnpg_to_level">
										<select name="cstmdmnpg_capability_level">
											<?php for ( $i = 0; $i <= 10; $i++ ) { ?>
												<option value="<?php echo esc_attr( $i ); ?>" <?php selected( isset( $post_meta['capability'] ) && absint( $post_meta['capability'] ) === $i, true ); ?>><?php echo esc_html( $i ); ?></option>
											<?php } ?>
										</select>
										<span class="bws_info"><?php esc_html_e( 'Level', 'custom-admin-page' ); ?> <a href="https://codex.wordpress.org/User_Levels#User_Level_Capabilities" target="_blank"><?php esc_html_e( 'Learn More', 'custom-admin-page' ); ?></a></span><br />
										<span class="bws_info"><?php echo esc_html__( 'The capability level required for this menu to be displayed to the user.', 'custom-admin-page' ); ?></span>
									</div>
								</fieldset>
							</td>
						</tr>
						<tr>
							<td style="padding: 0;">
								<fieldset>
									<label for="cstmdmnpg_capability_type">
										<input id="cstmdmnpg_capability_type" class="bws_option_affect" data-affect-show=".cstmdmnpg_to_capability" data-affect-hide=".cstmdmnpg_to_level" type="radio" name="cstmdmnpg_capability_type" value="name" <?php checked( isset( $post_meta['capability'] ) && ! is_numeric( $post_meta['capability'] ), true ); ?> />
										<?php esc_html_e( 'Capability', 'custom-admin-page' ); ?>
									</label><br>
									<div class="cstmdmnpg_to_capability">
										<select name="cstmdmnpg_capability">
											<?php
											$wp_roles = new WP_Roles();
											$roles    = $wp_roles->roles;

											foreach ( $roles['administrator']['capabilities'] as $role => $bool ) {
												?>
												<option value="<?php echo esc_attr( $role ); ?>" <?php selected( isset( $post_meta['capability'] ) && $post_meta['capability'] === $role, true ); ?>><?php echo esc_html( $role ); ?></option>
											<?php } ?>
										</select><br />
										<span class="bws_info"><?php esc_html_e( 'Capability', 'custom-admin-page' ); ?> <a href="https://wordpress.org/support/article/roles-and-capabilities/#capabilities" target="_blank"><?php esc_html_e( 'Learn More', 'custom-admin-page' ); ?></a></span><br />
									</div>
								</fieldset>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Parent', 'custom-admin-page' ); ?></th>
				<td>
					<select name="cstmdmnpg_parent" style="max-width:100%;">
						<option value="">( <?php esc_html_e( 'Parent Page - None', 'custom-admin-page' ); ?> )</option>
						<?php
						foreach ( $menu as $menu_slug ) {
							if ( '' !== $menu_slug[0] && $menu_slug[0] !== $page_title ) {
								?>
								<option style="word-break: break-all;" value="<?php echo esc_attr( $menu_slug[2] ); ?>" <?php selected( isset( $post_meta['parent'] ) && $menu_slug[2] === $post_meta['parent'], true ); ?>><?php echo esc_html( $menu_slug[0] ); ?></option>
								<?php
							}
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<?php esc_html_e( 'Order', 'custom-admin-page' ); ?>
				</th>
				<td>
					<input type="number"  min="0" max="10000" name="cstmdmnpg_position" value="<?php echo isset( $post_meta['order'] ) ? esc_attr( $post_meta['order'] ) : ''; ?>" /><br />
					<span class="bws_info"><?php echo esc_html__( 'The order in the menu where this page will appear.', 'custom-admin-page' ) . ' (' . esc_html__( 'Optional', 'custom-admin-page' ) . ')'; ?></span>
				</td>
			</tr>
			<tr id="cstmdmnpg_icon_to_page">
				<th>
					<?php esc_html_e( 'Icon', 'custom-admin-page' ); ?>
				</th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="cstmdmnpg_icon_image" value="none" 
							<?php
							if ( ! empty( $post_meta['icon_name'] ) ) {
								checked( $post_meta['icon_name'], 'none' );
							} elseif ( empty( $post_meta ) ) {
								echo 'checked="checked"';
							}
							?>
							/>
							<?php esc_html_e( 'None', 'custom-admin-page' ); ?>
						</label><br>
						<label>
							<input type="radio" name="cstmdmnpg_icon_image" value="svg" 
							<?php
							if ( ! empty( $post_meta['icon_name'] ) ) {
								checked( $post_meta['icon_name'], 'svg' );}
							?>
							/>
							<?php esc_html_e( 'SVG Code', 'custom-admin-page' ); ?>
						</label><br>
						<div class="cstmdmnpg_to_svg_input">
							<input class="cstmdmnpg-image-url" type="text" name="cstmdmnpg_svg" value="<?php echo isset( $post_meta['icon'] ) && 'svg' === $post_meta['icon_name'] ? esc_url( $post_meta['icon'] ) : ''; ?>" /><br />
							<span class="bws_info"><?php printf( esc_html__( 'Enter a base64-encoded SVG using a data URI, which will be colored to match the color scheme. This should begin with %s.', 'custom-admin-page' ), "<strong>'data:image/svg+xml;base64,'</strong>" ); ?></span><br />
						</div>
						<label>
							<input type="radio" name="cstmdmnpg_icon_image" value="image" 
							<?php
							if ( ! empty( $post_meta['icon_name'] ) ) {
								checked( $post_meta['icon_name'], 'image' );}
							?>
							/>
							<?php esc_html_e( 'Image', 'custom-admin-page' ); ?>
						</label><br>
						<div class="cstmdmnpg_to_image_input">
							<input class="cstmdmnpg-image-url" type="text" name="cstmdmnpg_image" value="<?php echo isset( $post_meta['icon'] ) && 'image' === $post_meta['icon_name'] ? esc_url( $post_meta['icon'] ) : ''; ?>" />
							<input class="button-secondary cstmdmnpg-upload-image hide-if-no-js" type="button" value="<?php echo isset( $post_meta['icon'] ) && ! empty( $post_meta['icon_name'] ) && 'image' !== $post_meta['icon_name'] ? esc_html__( 'Add Image', 'custom-admin-page' ) : esc_html__( 'Change Image', 'custom-admin-page' ); ?>"/><br />
						</div>
						<label>
							<input type="radio" name="cstmdmnpg_icon_image" value="dashicons"
							<?php
							if ( ! empty( $post_meta['icon_name'] ) ) {
								checked( $post_meta['icon_name'], 'dashicons' );
							}
							?>
							/>
							<?php esc_html_e( 'Dashicon', 'custom-admin-page' ); ?>
						</label>
						<div class="cstmdmnpg_to_dashicon_input">
							<input class="cstmdmnpg-image-url" type="text" name="cstmdmnpg_dashicons" value="<?php echo isset( $post_meta['icon'] ) && 'dashicons' === $post_meta['icon_name'] ? esc_url( $post_meta['icon'] ) : ''; ?>" /><br />
							<span class="bws_info"><?php printf( esc_html__( 'Enter the name of the Dashicons helper class to use a font icon, e.g. %s.', 'custom-admin-page' ), "<strong>'dashicons-chart-pie'</strong>" ); ?></span>
						</div>
					</fieldset>
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'cstmdmnpg_custom_box_action', 'cstmdmnpg_custom_box_field' ); ?>
		<?php
	}
}

if ( ! function_exists( 'cstmdmnpg_save_custom_fields' ) ) {
	/**
	 * Save custom field data when creating/updating posts
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 */
	function cstmdmnpg_save_custom_fields( $post_id, $post ) {

		/* Don't store custom data twice */
		if ( 'bws-admin_page' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
			return;
		}

		/* Verify this came from the our screen and with proper authorization, because save_post can be triggered at other times */
		if ( ! current_user_can( 'edit_page', $post->ID ) ) {
			return $post->ID;
		}

		if ( ! isset(
			$_POST['cstmdmnpg_capability_type'],
			$_POST['cstmdmnpg_capability'],
			$_POST['cstmdmnpg_capability_level'],
			$_POST['cstmdmnpg_parent'],
			$_POST['cstmdmnpg_icon_image'],
			$_POST['cstmdmnpg_svg'],
			$_POST['cstmdmnpg_image'],
			$_POST['cstmdmnpg_dashicons'],
			$_POST['cstmdmnpg_position']
		) ) {
			return;
		}

		if ( ! empty( $_POST['cstmdmnpg_custom_box_field'] ) && check_admin_referer( 'cstmdmnpg_custom_box_action', 'cstmdmnpg_custom_box_field' ) ) {

			$is_builder = preg_match( '/(\[vc_)|(\[et_)|(<!-- wp:)/', $post->post_content, $match );

			if ( 'name' === sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_capability_type'] ) ) ) {
				$capability = sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_capability'] ) );
			} else {
				$capability = intval( $_POST['cstmdmnpg_capability_level'] );
			}

			$parent = sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_parent'] ) );
			$order  = intval( $_POST['cstmdmnpg_position'] );

			if ( 'svg' === $_POST['cstmdmnpg_icon_image'] && ! empty( $_POST['cstmdmnpg_svg'] ) ) {
				$icon      = sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_svg'] ) );
				$icon_name = sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_icon_image'] ) );
			} elseif ( 'image' === $_POST['cstmdmnpg_icon_image'] && ! empty( $_POST['cstmdmnpg_image'] ) ) {
				$icon      = sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_image'] ) );
				$icon_name = sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_icon_image'] ) );
			} elseif ( 'dashicons' === $_POST['cstmdmnpg_icon_image'] && ! empty( $_POST['cstmdmnpg_dashicons'] ) ) {
				$icon      = sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_dashicons'] ) );
				$icon_name = sanitize_text_field( wp_unslash( $_POST['cstmdmnpg_icon_image'] ) );
			} else {
				$icon      = 'none';
				$icon_name = 'none';
			}

			$data = array(
				'capability' => $capability,
				'parent'     => $parent,
				'order'      => $order,
				'icon'       => $icon,
				'icon_name'  => $icon_name,
				'isBuilder'  => $is_builder,
			);

			update_post_meta( $post->ID, 'bws-admin_page', $data );
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_page_content' ) ) {
	/**
	 * Display custom page content
	 */
	function cstmdmnpg_page_content() {
		global $post;

		if ( isset( $_REQUEST['page'] ) ) {
			$post = get_page_by_path( sanitize_title( wp_unslash( $_REQUEST['page'] ) ), OBJECT, 'bws-admin_page' );

			if ( ! empty( $post ) ) {
				if ( post_password_required( $post->ID ) ) {
					echo get_the_password_form( $post->ID );
					return;
				}
				$divi_theme_active  = defined( 'ET_BUILDER_THEME' );
				$divi_plugin_active = function_exists( 'et_is_builder_plugin_active' ) && et_is_builder_plugin_active();
				?>
							
				<div class="cstmdmnpg_wrap">
					<?php
					if ( $divi_theme_active || $divi_plugin_active ) {
						echo cstmdmnpg_add_divi_wrap( $post->post_content );
					} else {
						wp_enqueue_style( 'wp-block-library', get_site_url() . '/wp-includes/css/dist/block-library/style.min.css' );
						echo apply_filters( 'the_content', wp_unslash( $post->post_content ) );
					}
					?>
				</div>
				<?php
			}
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_add_divi_wrap' ) ) {
	/**
	 * Display divi content
	 *
	 * @param string $content Content for page.
	 */
	function cstmdmnpg_add_divi_wrap( $content ) {
		$wrap  = '<div id="et-boc">';
		$wrap .= '<div id="et_builder_outer_content" class="et_builder_outer_content">';
		$wrap .= '<div class="et_builder_inner_content et_pb_gutters3">';

		$wrap .= apply_filters( 'the_content', wp_unslash( $content ) );

		$wrap .= '</div></div></div>';

		return $wrap;
	}
}

if ( ! function_exists( 'cstmdmnpg_page_builder_support' ) ) {
	/**
	 * Display Vc_Base content
	 */
	function cstmdmnpg_page_builder_support() {
		global $wpdb;

		if ( isset( $_GET['page'] ) ) {
			$page_id = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT `ID`
					FROM `' . $wpdb->posts . '`
					WHERE `post_type` = "bws-admin_page"
						AND `post_name` = %s',
					sanitize_text_field( wp_unslash( $_GET['page'] ) )
				)
			);

			if ( $page_id ) {

				if ( class_exists( 'Vc_Base' ) && class_exists( 'WPBMap' ) ) {
					WPBMap::addAllMappedShortcodes();

					$WPB = new Vc_Base();
					$WPB->frontCss();
					$WPB->addFrontCss();
					$WPB->addNoScript();
					$WPB->frontJsRegister();
					$WPB->fixPContent();
				}

				if ( defined( 'ET_BUILDER_DIR' ) ) {

					require_once ET_BUILDER_DIR . 'class-et-builder-element.php';
					require_once ET_BUILDER_DIR . 'ab-testing.php';
					do_action( 'et_builder_framework_loaded' );

					et_builder_init_global_settings();
					et_builder_add_main_elements();

					if ( defined( 'ET_BUILDER_THEME' ) ) {
						et_divi_load_scripts_styles();
					} else {
						wp_enqueue_script( 'divi-builder-custom-script', ET_BUILDER_PLUGIN_URI . '/js/divi-builder.min.js', array( 'jquery' ), ET_BUILDER_VERSION, true );
					}
					et_builder_load_modules_styles();
					_action_et_pb_box_shadow_overlay();
				}
			}
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_enable_classic_editor' ) ) {
	/**
	 * Enable Divi Classic Builder on our CPT, because Divi does not have backend editor compatible with gutenberg
	 *
	 * @param bool $enable Flag for display Divi Classic Builder.
	 * @return bool Flag for display Divi Classic Builder.
	 */
	function cstmdmnpg_enable_classic_editor( $enable ) {
		if ( cstmdmnpg_is_our_cpt() ) {
			return true;
		} else {
			return $enable;
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_divi_disable_bfb' ) ) {
	/**
	 * Disable Divi Classic Builder on
	 *
	 * @param bool $enabled Flag for display Divi Classic Builder.
	 * @return bool Flag for display Divi Classic Builder.
	 */
	function cstmdmnpg_divi_disable_bfb( $enabled ) {
		if ( cstmdmnpg_is_our_cpt() ) {
			return false;
		} else {
			return $enabled;
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_divi_should_load_framework' ) ) {
	/**
	 * Enable Divi Load framework
	 *
	 * @param bool $should_load Flag for display Divi Classic Builder.
	 * @return bool Flag for display Divi Classic Builder.
	 */
	function cstmdmnpg_divi_should_load_framework( $should_load ) {
		if ( cstmdmnpg_is_our_cpt() ) {
			return true;
		} else {
			return $should_load;
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_divi_change_link' ) ) {
	/**
	 * Change Divi Link
	 *
	 * @param array  $actions Actions array.
	 * @param object $post Post object.
	 * @return array $actions Updated array.
	 */
	function cstmdmnpg_divi_change_link( $actions, $post ) {

		if ( cstmdmnpg_is_our_cpt() && isset( $actions['divi'] ) ) {
			unset( $actions['divi'] );
		}

		return $actions;
	}
}

/* Enable Divi Builder on our CPT */
if ( ! function_exists( 'cstmdmnpg_divi_add_post_type' ) ) {
	/**
	 * Enable Divi Builder on our CPT
	 *
	 * @param array $post_types Post types array.
	 * @return array $post_types Post types array.
	 */
	function cstmdmnpg_divi_add_post_type( $post_types ) {
		array_push( $post_types, 'bws-admin_page' );

		return $post_types;
	}
}

if ( ! function_exists( 'cstmdmnpg_status_change' ) ) {
	/**
	 * Change status for post
	 *
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param object $post Post object.
	 */
	function cstmdmnpg_status_change( $new_status, $old_status, $post ) {
		global $wpdb;

		if ( 'publish' !== $new_status ) {
			return;
		}

		if ( 'bws-admin_page' !== $post->post_type || '' === $post->post_content || 'trash' === $post->post_status ) {
			return;
		}

		$builder_page = preg_match( '/(\[vc_)|(\[et_)|(<!-- wp:)/', $post->post_content, $match );

		if ( ! $builder_page ) {
			return;
		}

		$pages = $wpdb->get_results(
			'SELECT `ID`
			FROM `' . $wpdb->prefix . 'posts`
			WHERE `post_type` = "bws-admin_page"
				AND `post_status` = "publish"
				AND `ID` != %d',
			$post->ID
		);

		$built_pages = 0;

		foreach ( $pages as $page ) {
			$post_meta    = get_post_meta( $page->ID, $page->post_type, true );
			$built_pages += isset( $post_meta['isBuilder'] ) ? $post_meta['isBuilder'] : 0;
		}

		if ( $built_pages >= 3 && $builder_page ) {

			/* update the post to change post status */
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'draft',
				)
			);

			/* Show admin notice for Gutenberg (shows only 'Publishing failde' and throws and error in console) */
			add_settings_error(
				'page_builder_error',
				esc_attr( 'settings_updated' ),
				__( 'You can add only 3 admin pages in free plugin version.', 'custom-admin-page' ),
				'error'
			);

			/* Show admin notice */
			add_filter( 'redirect_post_location', 'cstmdmnpg_add_notice_query_var', 99 );
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_add_notice_query_var' ) ) {
	/**
	 * Change status for post
	 *
	 * @param string $location Location.
	 * @return string New location.
	 */
	function cstmdmnpg_add_notice_query_var( $location ) {
		remove_filter( 'redirect_post_location', 'cstmdmnpg_add_notice_query_var', 99 );

		return add_query_arg( array( 'builder_page' => '1' ), $location );
	}
}

if ( ! function_exists( 'cstmdmnpg_remove_published_notice' ) ) {
	/**
	 * Change status for post
	 *
	 * @param array $messages Remove message if builder_page.
	 * @return array $messages Updated messages.
	 */
	function cstmdmnpg_remove_published_notice( $messages ) {
		if ( isset( $_GET['builder_page'] ) ) {
			$messages['post'][6] = '';
		}

		return $messages;
	}
}

if ( ! function_exists( 'cstmdmnpg_admin_notices' ) ) {
	/**
	 * Add admin notice
	 */
	function cstmdmnpg_admin_notices() {
		$is_our_page              = cstmdmnpg_is_our_cpt();
		$is_needed_plugins_active = is_plugin_active( 'divi-builder/divi-builder.php' ) || is_plugin_active( 'js_composer/js_composer.php' ) || defined( 'ET_BUILDER_THEME' );

		if ( $is_our_page && $is_needed_plugins_active ) {
			?>
			<noscript><div class="error below-h2"><p><strong><?php esc_html_e( 'Please, enable JavaScript in Your browser.', 'custom-admin-page' ); ?></strong></p></div></noscript>
			<?php
		}

		if ( ! isset( $_GET['builder_page'] ) ) {
			return;
		}

		$message = __( 'You can add only 3 admin pages in free plugin version.', 'custom-admin-page' );

		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}

if ( ! function_exists( 'cstmdmnpg_notice_change' ) ) {
	/**
	 * Change admin notice
	 *
	 * @param array $messages Remove message if builder_page.
	 * @return array $messages Updated messages.
	 */
	function cstmdmnpg_notice_change( $messages ) {
		if ( cstmdmnpg_is_our_cpt() ) {
			$max = count( $messages['post'] );

			for ( $i = 0; $i < $max; $i++ ) {
				$messages['post'][ $i ] = str_replace( 'Post', __( 'Admin Page', 'custom-admin-page' ), $messages['post'][ $i ] );
			}
		}

		return $messages;
	}
}

if ( ! function_exists( 'cstmdmnpg_dequeue_styles' ) ) {
	/**
	 * Deregister css for admin page
	 */
	function cstmdmnpg_dequeue_styles() {
		if ( cstmdmnpg_is_our_cpt() ) {
			wp_dequeue_style( 'et_pb_admin_date_css' );
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_admin_head' ) ) {
	/**
	 * Enqueue script and styles
	 */
	function cstmdmnpg_admin_head() {
		global $cstmdmnpg_plugin_info;
		wp_enqueue_style( 'cstmdmnpg-stylesheet', plugins_url( 'css/style.css', __FILE__ ), array(), $cstmdmnpg_plugin_info['Version'] );

		if ( cstmdmnpg_is_our_cpt() ) {

			wp_enqueue_script( 'cstmdmnpg-script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), $cstmdmnpg_plugin_info['Version'], true );

			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();

			$script_vars = array(
				'changeImageLabel' => __( 'Change Image', 'custom-admin-page' ),
				'ok'               => __( 'OK', 'custom-admin-page' ),
				'cancel'           => __( 'Cancel', 'custom-admin-page' ),
				'ajax_nonce'       => wp_create_nonce( 'cstmdmnpg_ajax_nonce' ),
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
			);
			wp_localize_script( 'cstmdmnpg-script', 'cstmdmnpgScriptVars', $script_vars );
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_plugin_banner' ) ) {
	/**
	 * Add admin notice
	 */
	function cstmdmnpg_plugin_banner() {
		global $hook_suffix, $cstmdmnpg_plugin_info;
		if ( 'plugins.php' === $hook_suffix ) {
			bws_plugin_banner_to_settings( $cstmdmnpg_plugin_info, 'cstmdmnpg_options', 'custom-admin-page', 'edit.php?post_type=bws-admin_page&page=custom-admin-page.php', 'edit.php?post_type=bws-admin_page' );
		} elseif ( isset( $GLOBALS['post_type'] ) && 'bws-admin_page' === $GLOBALS['post_type'] ) {
			bws_plugin_suggest_feature_banner( $cstmdmnpg_plugin_info, 'cstmdmnpg_options', 'custom-admin-page' );
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_action_links' ) ) {
	/**
	 * Function to add action links to the plugin menu
	 *
	 * @param array $links All links.
	 * @param file  $file File name.
	 * @return array
	 */
	function cstmdmnpg_action_links( $links, $file ) {
		if ( ! is_network_admin() && plugin_basename( __FILE__ ) === $file ) {
			$settings_link = '<a href="edit.php?post_type=bws-admin_page&page=custom-admin-page.php">' . __( 'Settings', 'custom-admin-page' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

if ( ! function_exists( 'cstmdmnpg_links' ) ) {
	/**
	 * Function to add links to the plugin description on the plugins page
	 *
	 * @param array $links All links.
	 * @param file  $file File name.
	 * @return array
	 */
	function cstmdmnpg_links( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			if ( ! is_network_admin() ) {
				$links[] = '<a href="edit.php?post_type=bws-admin_page&page=custom-admin-page.php">' . __( 'Settings', 'custom-admin-page' ) . '</a>';
			}
			$links[] = '<a href="https://bestwebsoft.com/products/wordpress/plugins/custom-admin-page/" target="_blank">' . __( 'FAQ', 'custom-admin-page' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'custom-admin-page' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'cstmdmnpg_add_help_tab' ) ) {
	/**
	 * Add help tab
	 */
	function cstmdmnpg_add_help_tab() {
		if ( cstmdmnpg_is_our_cpt() ) {
			$screen = get_current_screen();

			$args = array(
				'id'      => 'cstmdmnpg',
				'section' => '',
			);
			bws_help_tab( $screen, $args );
		}
	}
}

if ( ! function_exists( 'cstmdmnpg_uninstall' ) ) {
	/**
	 * Deleting plugin options on uninstalling
	 */
	function cstmdmnpg_uninstall() {
		global $wpdb;
		$pro_exist = array_key_exists( 'custom-admin-page-pro/custom-admin-page-pro.php', get_plugins() );
		if ( ! $pro_exist ) {
			$wpdb->delete(
				$wpdb->prefix . 'posts',
				array(
					'post_type' => 'bws-admin_page',
				)
			);

			delete_option( 'cstmdmnpg_options' );
		}

		require_once __DIR__ . '/bws_menu/bws_include.php';
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

register_activation_hook( __FILE__, 'cstmdmnpg_plugin_activate' );
/* Initialization plugin*/
add_action( 'init', 'cstmdmnpg_init' );
add_action( 'plugins_loaded', 'cstmdmnpg_plugins_loaded' );
add_action( 'admin_init', 'cstmdmnpg_admin_init' );
/* Adding 'BWS Plugins' admin menu */
add_action( 'admin_menu', 'cstmdmnpg_add_pages', 9 );
add_action( 'admin_enqueue_scripts', 'cstmdmnpg_admin_head' );
/* Adding columns to table list */
add_filter( 'manage_bws-admin_page_posts_columns', 'cstmdmnpg_custom_columns' );
add_filter( 'manage_edit-bws-admin_page_sortable_columns', 'cstmdmnpg_custom_columns_sortable' );
add_action( 'manage_bws-admin_page_posts_custom_column', 'cstmdmnpg_custom_columns_content' );
/* Adding Page builders support */
add_action( 'admin_enqueue_scripts', 'cstmdmnpg_page_builder_support' );
add_action( 'admin_print_styles', 'cstmdmnpg_dequeue_styles' );
add_filter( 'et_builder_post_types', 'cstmdmnpg_divi_add_post_type' );
add_filter( 'et_builder_enable_classic_editor', 'cstmdmnpg_enable_classic_editor' );
add_filter( 'et_builder_bfb_enabled', 'cstmdmnpg_divi_disable_bfb', 11 );
add_filter( 'et_builder_should_load_framework', 'cstmdmnpg_divi_should_load_framework' );
/* Builder built page limit */
add_action( 'transition_post_status', 'cstmdmnpg_status_change', 10, 3 );
add_filter( 'post_updated_messages', 'cstmdmnpg_remove_published_notice' );
add_action( 'admin_notices', 'cstmdmnpg_admin_notices' );
/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'cstmdmnpg_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'cstmdmnpg_links', 10, 2 );

add_filter( 'post_updated_messages', 'cstmdmnpg_notice_change' );

add_action( 'save_post', 'cstmdmnpg_save_custom_fields', 1, 2 );

add_action( 'admin_notices', 'cstmdmnpg_plugin_banner' );
