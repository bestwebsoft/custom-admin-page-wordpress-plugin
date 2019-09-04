<?php
/*
Plugin Name: Custom Admin Page by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/custom-admin-page/
Description: Add unlimited custom pages to WordPress admin dashboard.
Author: BestWebSoft
Text Domain: custom-admin-page
Domain Path: /languages
Version: 0.1.8
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2019  BestWebSoft  ( https://support.bestwebsoft.com )

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

/**
 * Add our own menu
 */
if ( ! function_exists( 'cstmdmnpg_add_pages' ) ) {
	function cstmdmnpg_add_pages() {
		global $wpdb;
		bws_general_menu();
		$settings = add_submenu_page( 'bws_panel', __( 'Custom Admin Page Settings', 'custom-admin-page' ), 'Custom Admin Page', 'manage_options', 'custom-admin-page.php', 'cstmdmnpg_settings_page' );

		if ( ! function_exists( 'cstmdmnpg_screen_options' ) ) {
			require_once( dirname( __FILE__ ) . '/includes/pages.php' );
		}
		add_action( 'load-' . $settings, 'cstmdmnpg_add_tabs' );

		$pages = $wpdb->get_results( "SELECT * FROM `" . $wpdb->prefix . "cstmdmnpg_pages` WHERE `page_status`=0", ARRAY_A );
		if ( ! empty( $pages ) ){
			foreach ( $pages as $page ) {
				if ( ! empty( $page['parent_page'] ) && $page['parent_page'] != $page['page_title'] ) {
					if ( is_numeric( $page['capability'] ) && in_array( intval( $page['capability'] ), range( 0, 10 ) ) ) {
						add_submenu_page( $page['parent_page'], $page['page_title'], $page['page_title'], 'level_' . $page['capability'], $page['page_slug'], 'cstmdmnpg_page_content' );
					} else {
						add_submenu_page( $page['parent_page'], $page['page_title'], $page['page_title'], $page['capability'], $page['page_slug'], 'cstmdmnpg_page_content' );
					}
				} else {
					if ( ! empty( $page['icon'] ) ) {
						if ( filter_var( $page['icon'], FILTER_VALIDATE_URL ) ) {
							$icon = $page['icon'] . '" style="max-width: 20px; max-height: 20px;';
						} else {
							$icon = $page['icon'];
						}
					} else {
						$icon = '';
					}
					if ( is_numeric( $page['capability'] ) && in_array( intval( $page['capability'] ), range( 0, 10 ) ) ) {
						add_menu_page( $page['page_title'], $page['page_title'], 'level_' . $page['capability'], $page['page_slug'], 'cstmdmnpg_page_content', $icon, $page['position'] );
					} else {
						add_menu_page( $page['page_title'], $page['page_title'], $page['capability'], $page['page_slug'], 'cstmdmnpg_page_content', $icon, $page['position'] );
					}
				}
			}
		}
	}
}
if ( ! function_exists( 'cstmdmnpg_plugins_loaded' ) ) {
	function cstmdmnpg_plugins_loaded() {
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'custom-admin-page', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/**
 * Init plugin
 */
if ( ! function_exists ( 'cstmdmnpg_init' ) ) {
	function cstmdmnpg_init() {
		global $cstmdmnpg_plugin_info;

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $cstmdmnpg_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$cstmdmnpg_plugin_info = get_plugin_data( dirname( __FILE__ ) . '/custom-admin-page.php' );
		}

		/* Function check if plugin is compatible with current WP version */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $cstmdmnpg_plugin_info, '3.9' );

		/* Get/Register and check settings for plugin */
		if ( isset( $_GET['page'] ) && 'custom-admin-page.php' == $_GET['page'] ) {
			cstmdmnpg_settings();
		}
	}
}

/**
 *
 */
if ( ! function_exists( 'cstmdmnpg_admin_init' ) ) {
	function cstmdmnpg_admin_init() {
		global $bws_plugin_info, $cstmdmnpg_plugin_info;
		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array( 'id' => '614', 'version' => $cstmdmnpg_plugin_info["Version"] );
		}
	}
}

/**
* Performed at activation.
* @return void
*/
if ( ! function_exists( 'cstmdmnpg_create_table' ) ) {
	function cstmdmnpg_create_table() {
		global $wpdb;

		if ( ! $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}cstmdmnpg_pages'" ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$sql_query =
				"CREATE TABLE `{$wpdb->prefix}cstmdmnpg_pages` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`page_title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				`page_slug` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				`page_content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				`capability` VARCHAR( 255 ) NOT NULL DEFAULT '0',
				`parent_page` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
				`icon` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
				`position` TINYINT DEFAULT NULL,
				`page_status` INT( 1 ) NOT NULL DEFAULT '0',
				PRIMARY KEY ( `id` )
				) DEFAULT CHARSET=utf8;";
			dbDelta( $sql_query );
		}

		register_uninstall_hook( __FILE__, 'cstmdmnpg_uninstall' );
	}
}

/**
 * Register settings for plugin
 *
 */
if ( ! function_exists( 'cstmdmnpg_settings' ) ) {
	function cstmdmnpg_settings() {
		global $cstmdmnpg_options, $cstmdmnpg_plugin_info, $wpdb;
		$db_version = '1.1';

		$cstmdmnpg_options_defaults = array(
			'plugin_option_version'		=> $cstmdmnpg_plugin_info["Version"],
			'suggest_feature_banner'	=> 1
		);

		/* install the option defaults */
		if ( ! get_option( 'cstmdmnpg_options' ) ) {
			add_option( 'cstmdmnpg_options', $cstmdmnpg_options_defaults );
		}
		$cstmdmnpg_options = get_option( 'cstmdmnpg_options' );

		if ( ! isset( $cstmdmnpg_options['plugin_option_version'] ) || $cstmdmnpg_options['plugin_option_version'] != $cstmdmnpg_plugin_info["Version"] ) {
			/**
			 * @deprecated
			 * @since 0.1.6
			 * @todo remove after 27.01.2019
			 */
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}cstmdmnpg_pages` MODIFY icon TEXT" );
			/* @todo end */
			$cstmdmnpg_options = array_merge( $cstmdmnpg_options_defaults, $cstmdmnpg_options );
			$update_option = true;
		}

		if ( ! isset( $cstmdmnpg_options['plugin_db_version'] ) || ( isset( $cstmdmnpg_options['plugin_db_version'] ) && $cstmdmnpg_options['plugin_db_version'] != $db_version ) ) {
			cstmdmnpg_create_table();
			$cstmdmnpg_options['plugin_db_version'] = $db_version;
			$update_option = true;
		}

		if ( isset( $update_option ) ) {
			update_option( 'cstmdmnpg_options', $cstmdmnpg_options );
		}
	}
}

/**
 * Add admin page
 */
if ( ! function_exists ( 'cstmdmnpg_settings_page' ) ) {
	function cstmdmnpg_settings_page () {
		global $cstmdmnpg_plugin_info; ?>
		<div class="wrap">
			<h1>Custom Admin Page <a href="<?php echo wp_nonce_url( '?page=custom-admin-page.php&cstmdmnpg_tab_action=new', 'custom-admin-page-new' ); ?>" class="add-new-h2 cstmdmnpg_add_new_button"><?php _e( 'Add New Page', 'custom-admin-page' ); ?></a></h1>
			<noscript>
            	<div class="error below-h2">
                	<p><strong><?php _e( 'WARNING', 'custom-admin-page' ); ?>
                            :</strong> <?php _e( 'The plugin works correctly only if JavaScript is enabled.', 'custom-admin-page' ); ?>
                	</p>
            	</div>
        	</noscript>
			<?php if ( ! function_exists( 'cstmdmnpg_display_pages' ) ) {
				require_once( dirname( __FILE__ ) . '/pages.php' );
			}

			cstmdmnpg_display_pages();
			bws_plugin_reviews_block( $cstmdmnpg_plugin_info['Name'], 'custom-admin-page' ); ?>
		</div>
	<?php }
}

if ( ! function_exists ( 'cstmdmnpg_page_content' ) ) {
	function cstmdmnpg_page_content () {
		global $wpdb;
		if ( isset( $_REQUEST['page'] ) ) {
			$page_content = $wpdb->get_var( $wpdb->prepare( "SELECT `page_content` FROM `" . $wpdb->prefix . "cstmdmnpg_pages` WHERE `page_status` = 0 AND `page_slug` = %s", $_REQUEST['page'] ) );
			if ( ! empty( $page_content ) ) { ?>
				<div class="wrap">
					<?php echo apply_filters( 'the_content', wp_unslash( $page_content ) ); ?>
				</div>
			<?php }
		}
	}
}

/* Add stylesheets */
if ( ! function_exists ( 'cstmdmnpg_admin_head' ) ) {
	function cstmdmnpg_admin_head() {
		wp_enqueue_style( 'cstmdmnpg-stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
		if ( isset( $_GET['page'] ) && 'custom-admin-page.php' == $_GET['page'] ) {

			wp_enqueue_script( 'cstmdmnpg-script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ) );

			bws_enqueue_settings_scripts();

			$script_vars = array(
				'changeImageLabel'	=> __( 'Change Image', 'custom-admin-page' ),
				'ok'				=> __( 'OK', 'custom-admin-page' ),
				'cancel'			=> __( 'Cancel', 'custom-admin-page' ),
				'ajax_nonce'		=> wp_create_nonce( 'cstmdmnpg_ajax_nonce' )
			);
			wp_localize_script( 'cstmdmnpg-script', 'cstmdmnpgScriptVars', $script_vars );
		}
	}
}

/**
 * Ajax handler to retrieve a sample permalink.
 */
if ( ! function_exists( 'wp_ajax_cstmdmnpg_sample_permalink' ) ) {
	function wp_ajax_cstmdmnpg_sample_permalink() {
		check_ajax_referer( 'cstmdmnpg_ajax_nonce', 'nonce' );
		$title = isset( $_POST['new_title'] )? $_POST['new_title'] : '';
		$slug = isset( $_POST['new_slug'] )? sanitize_title( $_POST['new_slug'] ) : '';
		$page_id = isset( $_POST['page_id'] )? sanitize_title( $_POST['page_id'] ) : 0;
		$page_parent = isset( $_POST['parent_slug'] )? esc_attr( $_POST['parent_slug'] ) : 0;

		if ( empty( $slug ) ) {
			$slug = ! empty( $title ) ? sanitize_title( $title ) : 'cstmdmnpg-page-' . $page_id;
		}

		$url = ( ( ! empty( $page_parent ) && in_array( preg_replace( "/( \?.* )$/", "", $page_parent ), array( 'index.php', 'edit.php', 'upload.php', 'link-manager.php', 'edit-comments.php', 'themes.php', 'plugins.php', 'users.php', 'tools.php', 'options-general.php' ) ) ) ? $page_parent : 'admin.php' ) . ( ( stripos( $page_parent, '?' ) ) ? '&' : '?' ) . 'page=';
		?>
		<strong><?php _e( 'Permalink', 'custom-admin-page' ); ?>:</strong>
		<span id="sample-permalink"><?php echo self_admin_url( $url ); ?><span id="editable-post-name"><?php echo $slug; ?></span></span>
		‎<span id="edit-slug-buttons"><button type="button" class="edit-slug button button-small hide-if-no-js" aria-label="<?php _e( 'Edit permalink', 'custom-admin-page' ); ?>"><?php _e( 'Edit', 'custom-admin-page' ); ?></button></span>
		<span id="editable-post-name-full"><?php echo $slug; ?></span>
		<?php die();
	}
}

if ( ! function_exists ( 'cstmdmnpg_plugin_banner' ) ) {
	function cstmdmnpg_plugin_banner() {
		global $hook_suffix, $cstmdmnpg_plugin_info;
		if ( 'plugins.php' == $hook_suffix ) {
			bws_plugin_banner_to_settings( $cstmdmnpg_plugin_info, 'cstmdmnpg_options', 'custom-admin-page', 'admin.php?page=custom-admin-page.php' );
		} elseif ( isset( $_REQUEST['page'] ) && 'custom-admin-page.php' == $_REQUEST['page'] ) {
			bws_plugin_suggest_feature_banner( $cstmdmnpg_plugin_info, 'cstmdmnpg_options', 'custom-admin-page' );
		}
	}
}

/* Add links */
if ( ! function_exists( 'cstmdmnpg_action_links' ) ) {
	function cstmdmnpg_action_links( $links, $file ) {
		if ( ! is_network_admin() && $file == plugin_basename( __FILE__ ) ) {
			$settings_link = '<a href="admin.php?page=custom-admin-page.php">' . __( 'Settings', 'custom-admin-page' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

/* Add links */
if ( ! function_exists( 'cstmdmnpg_links' ) ) {
	function cstmdmnpg_links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			if ( ! is_network_admin() ) {
				$links[]='<a href="admin.php?page=custom-admin-page.php">' . __( 'Settings', 'custom-admin-page' ) . '</a>';
			}
			$links[] = '<a href="https://bestwebsoft.com/products/wordpress/plugins/custom-admin-page/" target="_blank">' . __( 'FAQ', 'custom-admin-page' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'custom-admin-page' ) . '</a>';
		}
		return $links;
	}
}

/* Deleting plugin options on uninstalling */
if ( ! function_exists( 'cstmdmnpg_uninstall' ) ) {
	function cstmdmnpg_uninstall() {
		global $wpdb;
		if ( is_multisite() ) {
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			$old_blog = $wpdb->blogid;
			$tables = '';
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				delete_option( 'cstmdmnpg_options' );
				if ( ! empty( $tables ) )
					$tables .= ', ';
				$tables .= '`' . $wpdb->prefix . 'cstmdmnpg_pages`';
			}
			$wpdb->query( "DROP TABLE IF EXISTS " . $tables . ";" );
			switch_to_blog( $old_blog );
			restore_current_blog();
		} else {
			delete_option( 'cstmdmnpg_options' );
			$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "cstmdmnpg_pages`;" );
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

register_activation_hook( __FILE__, 'cstmdmnpg_create_table' );
/* Initialization plugin*/
add_action( 'init', 'cstmdmnpg_init' );
add_action( 'plugins_loaded', 'cstmdmnpg_plugins_loaded' );
add_action( 'admin_init', 'cstmdmnpg_admin_init' );
/* Adding 'BWS Plugins' admin menu */
add_action( 'admin_menu', 'cstmdmnpg_add_pages' );
add_action( 'admin_enqueue_scripts', 'cstmdmnpg_admin_head' );
/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'cstmdmnpg_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'cstmdmnpg_links', 10, 2 );

add_action( 'admin_notices', 'cstmdmnpg_plugin_banner' );

add_action( 'wp_ajax_cstmdmnpg-sample-permalink', 'wp_ajax_cstmdmnpg_sample_permalink' );
