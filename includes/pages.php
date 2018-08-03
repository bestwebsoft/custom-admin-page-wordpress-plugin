<?php
/**
 * Display 'Pages', 'Add page', 'Edit page' pages
 * @subpackage Custom Admin Page
 * @since 0.1
 */

/**
 * Create class Cstmdmnpg_Pages_List
 * for displaying page with document pages
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
if ( ! class_exists( 'Cstmdmnpg_Pages_List' ) ) {
	class Cstmdmnpg_Pages_List extends WP_List_Table {
		/**
		* Constructor of class
		*/
		function __construct() {
			parent::__construct( array(
				'singular'	=> __( 'page', 'custom-admin-page' ),
				'plural'	=> __( 'pages', 'custom-admin-page' ),
				'ajax'		=> true,
				)
			);
		}
		/**
		* Function to prepare data before display
		* @return void
		*/
		function prepare_items() {
			$columns			= $this->get_columns();
			$hidden				= array();
			$sortable			= $this->get_sortable_columns();
			$this->items		= $this->pages_list();
			$per_page			= $this->get_items_per_page( 'cstmdmnpg_per_page', 20 );
			$current_page		= $this->get_pagenum();
			$total_items		= $this->items_count();
			$this->set_pagination_args( array(
					'total_items'	=> $total_items,
					'per_page'		=> $per_page,
				)
			);
		}
		/**
		* Function to show message if not pages found
		* @return void
		*/
		function no_items() {
			$message = isset( $_REQUEST['status'] ) && 'trash' == $_REQUEST['status'] ? __( 'No pages found in Trash.', 'custom-admin-page' ) : __( 'No pages yet.', 'custom-admin-page' ); ?>
			<p><?php echo $message; ?></p>
		<?php }
		/**
		 * Get a list of columns.
		 * @return array list of columns and titles
		 */
		function get_columns() {
			$columns = array(
				'cb'			=> '<input type="checkbox" />',
				'page_title'	=> __( 'Title', 'custom-admin-page' ),
				'capability'	=> __( 'Capability', 'custom-admin-page' ),
				'parent'		=> __( 'Parent', 'custom-admin-page' ),
			);
			return $columns;
		}
		/**
		 * Get a list of sortable columns.
		 * @return array list of sortable columns
		 */
		function get_sortable_columns() {
			$sortable_columns = array(
				'page_title'	=> array( 'page_title', false ),
				'capability'	=> array( 'capability', false ),
				'parent'		=> array( 'capability', false ),
			);
			return $sortable_columns;
		}
		/**
		 * Function to add action links to drop down menu before and after pages list
		 * @return array of actions
		 */
		function get_bulk_actions() {
			$actions = array();
			if ( isset( $_REQUEST['status'] ) ) {
				$actions['delete'] = __( 'Delete Permanently', 'custom-admin-page' );
				$actions['restore'] = __( 'Restore', 'custom-admin-page' );
			} else {
				$actions['trash'] = __( 'Trash', 'custom-admin-page' );
			}
			return $actions;
		}
		/**
		 * Fires when the default column output is displayed for a single row.
		 * @param      string    $column_name      The custom column's name.
		 * @param      array     $item             The cuurrent letter data.
		 * @return    void
		 */
		function column_default( $item, $column_name ) {
			global $menu;
			switch ( $column_name ) {
				case 'parent':
					if ( ! empty( $item['parent_page'] ) ) {
						foreach ( $menu as $menu_slug ) {
							if ( '' != $menu_slug[0] && $menu_slug[2] == $item['parent_page'] )
								return $menu_slug[0];
						}
						return $item['parent_page'];
					}
					return;
				case 'page_title':
					if ( isset( $_REQUEST['s'] ) && ( ! empty( $_REQUEST['s'] ) ) && '1' == $item['page_status'] ) {
						$page_title .= ' - ' . __( 'in trash', 'custom-admin-page' );
					}
					return $page_title;
				case 'capability':
					return $item[ $column_name ];
				default:
					return print_r( $item, true ) ;
			}
		}
		/**
		 * Function to add column of checboxes
		 * @param     array     $item        The cuurrent letter data.
		 * @return    string                  with html-structure of <input type=['checkbox']>
		 */
		function column_cb( $item ) {
			return sprintf( '<input id="cb_%1s" type="checkbox" name="cstmdmnpg_page_id[]" value="%2s" />', $item['id'], $item['id'] );
		}
		/**
		 * Function to add action links to title column depenting on status page
		 * @param    array     $item           The current letter data.
		 * @return   string                     with action links
		 */
		function column_page_title( $item ) {
			$status = isset( $_REQUEST['status'] ) ? '&status=' . $_REQUEST['status'] : '';
			$actions = array();
			if ( ! isset( $_REQUEST['status'] ) ) {
				$actions['edit'] = '<a href="' . wp_nonce_url( '?page=custom-admin-page.php&cstmdmnpg_tab_action=edit&cstmdmnpg_page_id=' . $item['id'], 'custom-admin-page-edit' . $item['id'] ) . '">' . __( 'Edit', 'custom-admin-page' ) . '</a>';
				$actions['trash'] = '<a class="submitdelete" href="' . wp_nonce_url( '?page=custom-admin-page.php&cstmdmnpg_tab_action=trash&cstmdmnpg_page_id=' . $item['id'], 'custom-admin-page-trash' . $item['id'] ) . '">' . __( 'Trash', 'custom-admin-page' ) . '</a>';
			} else {
				$actions['delete'] = '<a class="submitdelete" href="' . wp_nonce_url( '?page=custom-admin-page.php&cstmdmnpg_tab_action=delete&cstmdmnpg_page_id=' . $item['id'] . $status, 'custom-admin-page-delete' . $item['id'] ) . '">' . __( 'Delete Permanently', 'custom-admin-page' ) . '</a>';
				$actions['restore'] = '<a href="' . wp_nonce_url( '?page=custom-admin-page.php&cstmdmnpg_tab_action=restore&cstmdmnpg_page_id=' . $item['id'] . $status, 'custom-admin-page-restore' . $item['id'] ) . '">' . __( 'Restore', 'custom-admin-page' ) . '</a>';
			}
			return sprintf( '%1$s %2$s', $item['page_title'], $this->row_actions( $actions ) );
		}
		/**
		* Function to add filters below and above letters list
		* @return array $status_links
		*/
		function get_views() {
			global $wpdb;
			$status_links = array();
			$all_count = $trash_count = 0;
			/* get count of pages by status */
			$filters_count = $wpdb->get_results (
				"SELECT COUNT( `id` ) AS `all`,
					( SELECT COUNT( `id` ) FROM `" . $wpdb->prefix . "cstmdmnpg_pages` WHERE `page_status`=1 ) AS `trash`
				FROM `" . $wpdb->prefix . "cstmdmnpg_pages` WHERE `page_status`=0;"
			);
			foreach ( $filters_count as $count ) {
				$all_count = empty( $count->all ) ? 0 : $count->all;
				$trash_count = empty( $count->trash ) ? 0 : $count->trash;
			}
			/* get class for action links */
			$all_class = isset( $_REQUEST['status'] ) ? '' : 'class="current" ';
			$trash_class = isset( $_REQUEST['status'] ) && "trash" == $_REQUEST['status'] ? 'class="current" ': '';
			/* get array with action links */
			$status_links['all'] = '<a ' . $all_class . 'href="?page=custom-admin-page.php">' . __( 'All', 'custom-admin-page' ) . '<span class="count"> ( ' . $all_count . ' )</span></a>';
			$status_links['trash'] = '<a ' . $trash_class . 'href="?page=custom-admin-page.php&status=trash">' . __( 'Trash', 'custom-admin-page' ) . '<span class="count"> ( ' . $trash_count . ' )</span></a>';
			return $status_links;
		}

		/**
		 * Function to get pages list
		 * @return array list of letters
		 */
		function pages_list() {
			global $wpdb;

			$per_page = $this->get_items_per_page( 'cstmdmnpg_per_page', 20 );
			$paged = ( isset( $_REQUEST['paged'] ) && 1 < intval( $_REQUEST['paged'] ) ) ? $per_page * ( absint( intval( $_REQUEST['paged'] ) - 1 ) ) : 0;
			$order_by = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array( 'page_title', 'capability', 'parent' ) ) ) ? $_REQUEST['orderby'] : 'page_title';
			$order = ( isset( $_REQUEST['order'] ) && 'DESC' == $_REQUEST['order'] ) ? $_REQUEST['order'] : 'ASC';

			$sql_query = "SELECT * FROM `" . $wpdb->prefix . "cstmdmnpg_pages` ";
			if ( isset( $_REQUEST['s'] ) && ( ! empty( $_REQUEST['s'] ) ) )
				$sql_query .= "WHERE `page_title` LIKE '%" . esc_html( trim( $_REQUEST['s'] ) ) . "%'";
			elseif ( isset( $_REQUEST['status'] ) && 'trash' == $_REQUEST['status'] )
				$sql_query .= "WHERE `page_status`=1";
			else
				$sql_query .= "WHERE `page_status`=0";
			$sql_query .= " ORDER BY " . $order_by . " " . $order . " LIMIT " . $per_page . " OFFSET " . $paged . ";";

			$pages_list = $wpdb->get_results( $sql_query, ARRAY_A );

			return $pages_list;
		}

		/**
		 * Function to get number of pages lists
		 * @return sting pages lists number
		 */
		protected function items_count() {
			global $wpdb;
			$sql_query = "SELECT COUNT( `id` ) FROM `" . $wpdb->prefix . "cstmdmnpg_pages`";
			if ( isset( $_REQUEST['s'] ) && ( ! empty( $_REQUEST['s'] ) ) )
				$sql_query .= "WHERE `page_title` LIKE '%" . esc_html( trim( $_REQUEST['s'] ) ) . "%'";
			elseif ( isset( $_REQUEST['status'] ) && 'trash' == $_REQUEST['status'] )
				$sql_query .= "WHERE `page_status`=1;";
			else
				$sql_query .= "WHERE `page_status`=0;";
			$items_count = $wpdb->get_var( $sql_query );
			return $items_count;
		}
	}
} /* end of class definition */
/**
 * Add screen options and initialize instance of class Cstmdmnpg_Pages_List
 * @return void
 */
if ( ! function_exists( 'cstmdmnpg_screen_options' ) ) {
	function cstmdmnpg_screen_options() {
		global $cstmdmnpg_pages_list;
		$args = array(
			'label'		=> __( 'Pages per page', 'custom-admin-page' ),
			'default'	=> 20,
			'option'	=> 'cstmdmnpg_per_page'
		);
		add_screen_option( 'per_page', $args );
		$cstmdmnpg_pages_list = new Cstmdmnpg_Pages_List();
	}
}

if ( ! function_exists( 'cstmdmnpg_help_tab' ) ) {
	function cstmdmnpg_help_tab() {
		$screen = get_current_screen();

		$args = array(
			'id'		=> 'cstmdmnpg',
			'section'	=> ''
		);
		bws_help_tab( $screen, $args );

	}
}

if ( ! function_exists( 'cstmdmnpg_add_tabs' ) ) {
	function cstmdmnpg_add_tabs() {
		cstmdmnpg_help_tab();
		/* display screen options on 'Pages' page */
		cstmdmnpg_screen_options();
	}
}

/**
 *
 * @return    array      $action_message
 */
if ( ! function_exists( 'cstmdmnpg_handle_action' ) ) {
	function cstmdmnpg_handle_action() {
		$action_message = array(
			'done'	=> '',
			'error'	=> '',
			'id'	=> '',
		);
		/* Get necessary action */
		/* action links */
		$tab_action = isset( $_REQUEST['cstmdmnpg_tab_action'] ) && ( ! in_array( $_REQUEST['cstmdmnpg_tab_action'], array( 'new', 'edit' ) ) ) ? $_REQUEST['cstmdmnpg_tab_action'] : false;
		/* bulk actions */
		$action = isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'trash', 'delete', 'restore' ) ) ? $_POST['action'] : false;
		if ( ! $action ) {
			$action = isset( $_POST['action2'] ) && in_array( $_POST['action2'], array( 'trash', 'delete', 'restore' ) ) ? $_POST['action2'] : false;
		}

		$page_id = empty( $_REQUEST['cstmdmnpg_page_id'] ) ? 0 : $_REQUEST['cstmdmnpg_page_id'];
		if ( ! $action ) {
			$action = $tab_action;
		}
		if ( $action && in_array( $action, array( 'trash', 'restore', 'delete' ) ) && ! is_array( $page_id ) ) {
			$nonce_action = 'custom-admin-page-' . $action . $page_id;
			$nonce_query_arg = '_wpnonce';
		} else {
			$nonce_action = 'custom-admin-page/pages.php';
			$nonce_query_arg = 'cstmdmnpg_nonce_name';
		}
		if ( $action && check_admin_referer( $nonce_action, $nonce_query_arg ) ) {
			global $wpdb;
			$list_messages = array(
				'error'			=> __( 'Some errors occurred', 'custom-admin-page' ),
				'add'			=> __( 'Page has been saved. Refresh the page to see the changes.', 'custom-admin-page' ),
				'update'		=> __( 'Page has been updated.', 'custom-admin-page' ),
				'trash'			=> __( 'Selected pages were moved to the trash.', 'custom-admin-page' ),
				'restore'		=> __( 'Selected pages were restored from the trash.', 'custom-admin-page' ),
				'delete'		=> __( 'Selected pages were deleted.', 'custom-admin-page' ),
				'empty_id'		=> __( 'No pages were selected.', 'custom-admin-page' ),
				'existing_name'	=> __( 'Such name already exists', 'custom-admin-page' )
			);

			if ( 0 === $page_id && 'add' != $action ) {
				$action_message['error'] = $list_messages['empty_id'];
			} else {
				if ( 'add' == $action || 'update' == $action ) {
					$page_title = ! empty( $_REQUEST['cstmdmnpg_page_title'] ) ? stripslashes( esc_html( trim( $_REQUEST['cstmdmnpg_page_title'] ) ) ) : '';
					$page_content = ! empty( $_REQUEST['cstmdmnpg_content'] ) ? stripslashes( $_REQUEST['cstmdmnpg_content'] ) : '';
					if ( ! get_magic_quotes_gpc() ) {
						$page_title = addslashes( $page_title );
						$page_content = addslashes( $page_content );
					}
					$page_slug = ! empty( $_REQUEST['cstmdmnpg_page_slug'] ) ? sanitize_title( $_REQUEST['cstmdmnpg_page_slug'] ) : '';
					if ( empty( $page_slug ) && ! empty( $page_title ) ) {
						$page_slug = sanitize_title( $page_title );
					}

					$page_parent = ! empty( $_REQUEST['cstmdmnpg_parent'] ) ? stripslashes( $_REQUEST['cstmdmnpg_parent'] ) : NULL;
					$icon = ! empty( $_REQUEST['cstmdmnpg_icon'] ) ? stripslashes( $_REQUEST['cstmdmnpg_icon'] ) : '';

					if ( ! empty( $_REQUEST['cstmdmnpg_capability_type'] ) && 'level' == $_REQUEST['cstmdmnpg_capability_type'] ) {
						$capability = ! empty( $_REQUEST['cstmdmnpg_capability_level'] ) ? intval( $_REQUEST['cstmdmnpg_capability_level'] ) : 0;
					}
					else
						$capability = ! empty( $_REQUEST['cstmdmnpg_capability'] ) ? trim( esc_attr( $_REQUEST['cstmdmnpg_capability'] ) ) : 'read';
					$position = intval( $_REQUEST['cstmdmnpg_position'] ) >= 0 ? intval( $_REQUEST['cstmdmnpg_position'] ) : NULL;
				}

				/* for bulk actions */
				if ( is_array( $page_id ) ) {
					$page_id = implode( ',', $page_id );
					$value = " IN ( " . $page_id . " )";
				} else {
					$value = "=" . $page_id;
				}
				switch ( $action ) {
					case 'add':
						$existing_title = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}cstmdmnpg_pages` WHERE `page_title` = '{$page_title}'" );
						if ( ! $existing_title ) {
							if ( ! empty( $page_slug ) && '' != $page_title ) {
								$wpdb->insert(
									$wpdb->prefix . 'cstmdmnpg_pages',
									array(
										'page_title'	=> $page_title,
										'page_content'	=> $page_content,
										'page_status'	=> 0,
										'page_slug'		=> $page_slug,
										'capability'	=> $capability,
										'position'		=> $position,
										'parent_page'	=> $page_parent,
										'icon'			=> $icon,
									)
								);
								$page_id = $wpdb->last_error ? 0 : $wpdb->insert_id;

							} elseif ( '' != $page_title ) {
								$wpdb->insert(
									$wpdb->prefix . 'cstmdmnpg_pages',
									array(
										'page_title'	=> $page_title,
										'page_status'	=> 0,
										'page_content'	=> $page_content,
										'capability'	=> $capability,
										'position'		=> $position,
										'parent_page'	=> $page_parent,
										'icon'			=> $icon,
									)
								);
								$page_id = $wpdb->last_error ? 0 : $wpdb->insert_id;

								$page_slug = 'cstmdmnpg-page-' . $page_id;
								$wpdb->update(
									$wpdb->prefix . 'cstmdmnpg_pages',
									array(
										'page_slug' => $page_slug,
									),
									array( 'id' => $page_id )
								);
							}
						} else {
							$action_message['error'] = $list_messages['existing_name'];
						}
						break;
					case 'update':

						$existing_title = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}cstmdmnpg_pages` WHERE `page_title` = '{$page_title}'" );
						foreach ( $existing_title as $key => $value ) {
							if ( $page_id == $existing_title[ $key ]->id ) {
								$page_id = $existing_title[ $key ]->id;
								break;
							}
						}
						if ( ! $existing_title || $page_id ) {
							if ( empty( $page_slug ) )
								$page_slug = 'cstmdmnpg-page-' . $page_id;
							if ( $page_title ) {
								$wpdb->update(
									$wpdb->prefix . 'cstmdmnpg_pages',
									array(
										'page_title'	=> $page_title,
										'page_content'	=> $page_content,
										'page_slug'		=> $page_slug,
										'capability'	=> $capability,
										'position'		=> $position,
										'parent_page'	=> $page_parent,
										'icon'			=> $icon,
									),
									array( 'id' => $page_id )
								);
							}
						} else {
							$action_message['error'] = $list_messages['existing_name'];
						}
						break;
					case 'trash':
					case 'restore':
						if ( 'trash' == $action ) {
							$old_status = 0;
							$new_status = 1;
						} else {
							$old_status = 1;
							$new_status = 0;
						}
						$result = $wpdb->query( "UPDATE `" . $wpdb->prefix . "cstmdmnpg_pages` SET `page_status`=replace( page_status, " . $old_status . ", " . $new_status . " ) WHERE `id`" . $value );
						break;
					case 'delete':
						global $cstmdmnpg_options;
						$result = $wpdb->query( "DELETE FROM `" . $wpdb->prefix . "cstmdmnpg_pages` WHERE `id`" . $value );
						$ids = is_array( $_REQUEST['cstmdmnpg_page_id'] ) ? $_REQUEST['cstmdmnpg_page_id'] : array( $_REQUEST['cstmdmnpg_page_id'] );
						if ( isset( $cstmdmnpg_options['page_for_pdf'] ) && in_array( $cstmdmnpg_options['page_for_pdf'], $ids ) ) {
							$cstmdmnpg_options['page_for_pdf'] = 0;
						}
						if ( isset( $cstmdmnpg_options['page_for_print'] ) && in_array( $cstmdmnpg_options['page_for_print'], $ids ) ) {
							$cstmdmnpg_options['page_for_print'] = 0;
						}
						update_option( 'cstmdmnpg_options', $cstmdmnpg_options );
						break;
					case 'edit':
					case 'new':
					default:
						break;
				}
			}
			if ( $wpdb->last_error ) {
				$action_message['error'] = $list_messages['error'];
			} else {
				$action_message['done'] = $list_messages[ $action ];
				$action_message['id'] = $page_id;
			}
		}
		return $action_message;
	}
}

/**
 * Display pages list
 * @return void
 */
if ( ! function_exists( 'cstmdmnpg_display_pages' ) ) {
	function cstmdmnpg_display_pages() {
		global $wpdb, $cstmdmnpg_pages_list, $menu, $page_slug, $page_title, $display_empty_page;
		$message = $error = '';
		$action_message = cstmdmnpg_handle_action();
		$error = isset( $action_message['error'] ) && ( ! empty( $action_message['error'] ) ) ? $action_message['error'] : '';
		if ( ! $error ) {
			$message = isset( $action_message['done'] ) && ( ! empty( $action_message['done'] ) ) ? $action_message['done'] : '';
		}
		if( isset( $_REQUEST['cstmdmnpg_tab_action'] ) ) {
			$display_empty_page = false;
		} ?>
		<div class="updated fade below-h2"<?php if ( empty( $message ) ) echo " style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
		<div class="error below-h2"<?php if ( empty( $error ) ) echo " style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
		<form id="cstmdmnpg_page_form" method="post">
			<?php if ( isset( $_REQUEST['cstmdmnpg_tab_action'] ) && in_array( $_REQUEST['cstmdmnpg_tab_action'], array( 'new', 'add', 'update', 'edit' ) ) ) {
				switch ( $_REQUEST['cstmdmnpg_tab_action'] ) {
					case 'add': /* display content of new page after inserting in database */
					case 'update': /* display content of new page after updating */
						check_admin_referer( 'custom-admin-page/pages.php', 'cstmdmnpg_nonce_name' );
						$title			= __( 'Edit page', 'custom-admin-page' );
						$button_title	= __( 'Update page', 'custom-admin-page' );
						$tab_action		= 'update';
						$page_id		= $action_message['id'];
						$page_title		= ! empty( $_REQUEST['cstmdmnpg_page_title'] ) ? stripslashes( esc_html( $_REQUEST['cstmdmnpg_page_title'] ) ) : '';
						$page_slug		= ! empty( $_REQUEST['cstmdmnpg_page_slug'] ) ? sanitize_title( $_REQUEST['cstmdmnpg_page_slug'] ) : '';
						$page_content	= ! empty( $_REQUEST['cstmdmnpg_content'] ) ? stripslashes( $_REQUEST['cstmdmnpg_content'] ) : '';
						$page_parent	= ! empty( $_REQUEST['cstmdmnpg_parent'] ) ? stripslashes( $_REQUEST['cstmdmnpg_parent'] ) : NULL;
						$icon			= ! empty( $_REQUEST['cstmdmnpg_icon'] ) ? stripslashes( $_REQUEST['cstmdmnpg_icon'] ) : '';
						if ( ! empty( $_REQUEST['cstmdmnpg_capability_type'] ) && 'level' == $_REQUEST['cstmdmnpg_capability_type'] ) {
							$capability = ! empty( $_REQUEST['cstmdmnpg_capability_level'] ) ? intval( $_REQUEST['cstmdmnpg_capability_level'] ) : 0;
						} else {
							$capability = ! empty( $_REQUEST['cstmdmnpg_capability'] ) ? trim( esc_attr( $_REQUEST['cstmdmnpg_capability'] ) ) : 'read';
						}
						$position = intval( $_REQUEST['cstmdmnpg_position'] ) >= 0 ? intval( $_REQUEST['cstmdmnpg_position'] ) : NULL;

						if ( empty( $page_slug ) && empty( $action_message['error'] ) ) {
							$page_slug = ! empty( $page_title ) ? sanitize_title( $page_title ) : 'cstmdmnpg-page-' . $page_id;
						}
						break;
					case 'edit': /* display content of page if we go from 'pages list'-page */
						$page_id = ( ! isset( $_REQUEST['cstmdmnpg_page_id'] ) ) ? 0 : intval( $_REQUEST['cstmdmnpg_page_id'] );
						check_admin_referer( 'custom-admin-page-edit' . $page_id );
						if ( empty( $page_id ) || 0 === $page_id ) {
							$display_empty_page = true;
						} else {
							$page_data = $wpdb->get_row( 'SELECT * FROM `' . $wpdb->prefix . 'cstmdmnpg_pages` WHERE `id`=' . $page_id, ARRAY_A );
							if ( ! empty( $page_data ) ) {
								$page_title		= $page_data['page_title'];
								$page_content	= $page_data['page_content'];
								$page_slug		= $page_data['page_slug'];
								$icon			= $page_data['icon'];
								$page_parent	= $page_data['parent_page'];
								$capability		= $page_data['capability'];
								$position		= $page_data['position'];
								$title			= __( 'Edit page', 'custom-admin-page' );
								$button_title	= __( 'Update page', 'custom-admin-page' );
								$tab_action		= 'update';
							} else {
								$display_empty_page = true;
							}
						}
						break;
					case 'new': /* display empty form */
						check_admin_referer( 'custom-admin-page-new' );
						$display_empty_page = true;
						break;
					default: /* display empty form */
						check_admin_referer( plugin_basename( __FILE__ ), 'cstmdmnpg_nonce_name' );
						$display_empty_page = true;
						break;
				}
				if ( isset( $_GET['cstmdmnpg_tab_action'] ) && 'new' == $_GET['cstmdmnpg_tab_action'] && "No pages were selected." == ( $action_message['error'] || "Such name already exists" == $action_message['error'] ) ) {
					$tab_action = 'new';
					$display_empty_page = true;
				} 
				if ( $display_empty_page ) {
					$title			= __( 'Title', 'custom-admin-page' );
					$button_title	= __( 'Save', 'custom-admin-page' );
					$tab_action		= 'add';
					$page_id		= 0;
					$page_title = $page_slug = $page_content = $icon = $position = '';
				}
				if ( "Such name already exists" == $action_message["error"] ) {
					$button_title = __( 'Save', 'custom-admin-page' ); 
				} ?>
				<h2><?php echo $title; ?></h2>
				<div id="titlediv">
					<div id="titlewrap">
						<input type="text" name="cstmdmnpg_page_title" size="30" value="<?php echo $page_title; ?>" id="title" placeholder="<?php _e( 'Enter Page Title', 'custom-admin-page' ); ?>" required/>
					</div>
					<?php if ( ! empty( $page_slug ) ) {
						$url = ( ( ! empty( $page_parent ) && in_array( preg_replace( "/(\?.*)$/", "", $page_parent ), array( 'index.php', 'edit.php', 'upload.php', 'link-manager.php', 'edit-comments.php', 'themes.php', 'plugins.php', 'users.php', 'tools.php', 'options-general.php' ) ) ) ? $page_parent : 'admin.php' ) . ( ( stripos( $page_parent, '?' ) ) ? '&' : '?' ) . 'page='; ?>
						<div class="inside">
							<div id="edit-slug-box" class="hide-if-no-js">
								<strong><?php _e( 'Permalink', 'custom-admin-page' ); ?>:</strong>
								<span id="sample-permalink"><a href="<?php echo self_admin_url( $url . $page_slug ); ?>"><?php echo self_admin_url( $url ); ?><span id="editable-post-name"><?php echo $page_slug; ?></span></a></span>
								‎<span id="edit-slug-buttons"><button type="button" class="edit-slug button button-small hide-if-no-js" aria-label="<?php _e( 'Edit permalink', 'custom-admin-page' ); ?>"><?php _e( 'Edit', 'custom-admin-page' ); ?></button></span>
								<span id="editable-post-name-full"><?php echo $page_slug; ?></span>
							</div>
						</div>
						<label class="screen-reader-text" for="post_name"><?php _e( 'Slug', 'custom-admin-page' ) ?></label>
						<input name="cstmdmnpg_page_slug" type="hidden" id="post_name" value="<?php echo esc_attr( $page_slug ); ?>" />
					<?php } ?>
				</div><!-- /titlediv -->
				<h3><span><?php _e( 'Content', 'custom-admin-page' ); ?></span></h3>
				<div class="postarea wp-editor-expand">
					<?php if ( function_exists( 'wp_editor' ) ) {
						$settings = array(
								'wpautop'		=> 1,
								'media_buttons'	=> 1,
								'textarea_name'	=> 'cstmdmnpg_content',
								'textarea_rows'	=> 5,
								'tabindex'		=> null,
								'editor_css'	=> '<style>.mce-content-body { width: 100%; max-width: 100%; background: red;}</style>',
								'editor_class'	=> 'cstmdmnpg_content',
								'teeny'			=> 0,
								'dfw'			=> 0,
								'tinymce'		=> 1,
								'quicktags'		=> 1
							);
						wp_editor( wp_unslash( $page_content ), 'cstmdmnpg_content', $settings );
					} else { ?>
						<textarea class="cstmdmnpg_content_area" rows="5" autocomplete="off" cols="40" name="cstmdmnpg_content" id="cstmdmnpg_content"><?php echo wp_unslash( $page_content ); ?></textarea>
					<?php } ?>
				</div>
				<h3><span><?php _e( 'Page Attributes', 'custom-admin-page' ); ?></span></h3>
				<div class="postbox">
					<div class="inside">
						<table class="form-table">
							<tr>
								<th><?php _e( 'Capability', 'custom-admin-page' ); ?> *<?php echo bws_add_help_box( __( 'The capability level required for this menu to be displayed to the user.', 'custom-admin-page' ) ); ?></th>
								<td>
									<table>
										<tr>
											<td style="padding: 0;">
												<fieldset>
													<label>
														<input checked = "checked" id="cstmdmnpg_capability_level" type="radio" name="cstmdmnpg_capability_type" value="level" <?php if ( isset( $capability ) && is_numeric( $capability ) ) echo "checked" ; ?>/>
														<?php _e( 'Level', 'custom-admin-page' ); ?>
													</label>
												<fieldset>
											</td>
											<td style="padding: 0;">
												<select name="cstmdmnpg_capability_level">
													<?php for ( $i=0; $i<=10; $i++ ) { ?>
														<option value="<?php echo $i; ?>" <?php if ( isset( $capability ) && $capability == $i ) echo 'selected '; ?>><?php echo $i; ?></option>
													<?php } ?>
												</select>
												<span class="bws_info"><?php _e( 'see', 'custom-admin-page' ); ?> <a href="https://codex.wordpress.org/Roles_and_Capabilities#User_Levels" target="_blank"><?php _e( 'Levels', 'custom-admin-page' ); ?></a></span>
											</td>
										</tr>
										<tr>
											<td style="padding: 0;">
												<fieldset>
													<label for="cstmdmnpg_capability_type">
														<input id="cstmdmnpg_capability" type="radio" name="cstmdmnpg_capability_type" value="name" <?php if ( isset( $capability ) && ! is_numeric( $capability ) ) echo 'checked '; ?>/>
														<?php _e( 'Capability', 'custom-admin-page' ); ?>
													</label>
												</fieldset>
											</td>
											<td style="padding: 0;">
												<input type="text" id="cstmdmnpg_capability_type" name="cstmdmnpg_capability" value="<?php if ( isset( $capability ) && ! is_numeric( $capability ) ) echo $capability; ?>" />
												<span class="bws_info"><?php _e( 'see', 'custom-admin-page' ); ?> <a href="https://codex.wordpress.org/Roles_and_Capabilities#Capabilities" target="_blank"><?php _e( 'Capabilities', 'custom-admin-page' ); ?></a></span>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Parent', 'custom-admin-page' ); ?></th>
								<td>
									<select name="cstmdmnpg_parent" style="max-width:100%;">
										<option value="">( <?php _e( 'no parent', 'custom-admin-page' ); ?> )</option>
										<?php foreach ( $menu as $menu_slug ) {
											if ( '' != $menu_slug[0] && $menu_slug[0] != $page_title ) { ?>
												<option style="word-break: break-all;" value="<?php echo $menu_slug[2]; ?>" <?php if ( ! empty( $page_parent ) && $menu_slug[2] == $page_parent ) echo 'selected';?>><?php echo $menu_slug[0]; ?></option>
											<?php }
										} ?>
									</select>
								</td>
							</tr>
							<tr>
								<th>
									<?php _e( 'Order', 'custom-admin-page' );
									echo bws_add_help_box( __( 'The order in the menu where this page will appear.', 'custom-admin-page' ) . ' ( ' . __( 'Optional', 'custom-admin-page' ) . ' )' ); ?>
								</th>
								<td>
									<input type="number" min="1" max="10000" name="cstmdmnpg_position" value="<?php if ( $position ) echo $position; ?>" />
								</td>
							</tr>
							<tr>
								<th>
									<?php _e( 'Icon', 'custom-admin-page' );
									echo bws_add_help_box(
										__( 'Icon URL for this menu.', 'custom-admin-page' ) . ' ( ' . __( 'Optional', 'custom-admin-page' ) . ' )
										<ul>
											<li>* ' . sprintf( __( 'Enter a base64-encoded SVG using a data URI, which will be colored to match the color scheme. This should begin with %s.', 'custom-admin-page' ), "<strong>'data:image/svg+xml;base64,'</strong>" ) . '</li>
											<li>* ' . sprintf( __( 'Enter the name of the Dashicons helper class to use a font icon, e.g. %s.', 'custom-admin-page' ), "<strong>'dashicons-chart-pie'</strong>" ) . '</li>
											<li>* ' . sprintf( __( 'Enter %s to leave div.wp-menu-image empty so the icon can be added via CSS.', 'custom-admin-page' ), "<strong>'none'</strong>" ) . '</li>
										</ul>'
									); ?>
								</th>
								<td>
									<fieldset>
										<input class="cstmdmnpg-image-url" type="text" name="cstmdmnpg_icon" value="<?php echo $icon; ?>" />
										<input class="button-secondary cstmdmnpg-upload-image hide-if-no-js" type="button" value="<?php echo ( empty( $icon ) ) ? __( 'Add Image', 'custom-admin-page' ) : __( 'Change Image', 'custom-admin-page' ); ?>"/>
									</fieldset>
								</td>
							</tr>
						</table>
						<div class="cstmdmnpg_icon">
							<div>

							</div>
						</div>
					</div>
				</div>
				<p>
					<input name="cstmdmnpg_page_submit" type="submit" class="button-primary" value="<?php echo $button_title; ?>" />
					<input type="hidden" name="cstmdmnpg_tab_action" value="<?php echo $tab_action; ?>" />
					<input type="hidden" name="cstmdmnpg_page_id" value="<?php echo $page_id; ?>" />
				</p>
			<?php } else {
				if ( isset( $_REQUEST['s'] ) && ( ! empty( $_REQUEST['s'] ) ) ) {
					echo '<div class="subtitle">' . sprintf( __( 'Search results for &#8220;%s&#8221;', 'custom-admin-page' ), wp_html_excerpt( esc_html( wp_unslash( $_REQUEST['s'] ) ), 50, '&hellip;' ) ) . '</div>';
				}
				echo '<h2 class="screen-reader-text">' . __( 'Page filter', 'custom-admin-page' ) . '</h2>';
				$cstmdmnpg_pages_list->views();
				$cstmdmnpg_pages_list->prepare_items();
				$cstmdmnpg_pages_list->search_box( __( 'Search', 'custom-admin-page' ), 'cstmdmnpg' );
				$cstmdmnpg_pages_list->current_action();
				$cstmdmnpg_pages_list->display(); ?>
			<?php }
			wp_nonce_field( 'custom-admin-page/pages.php', 'cstmdmnpg_nonce_name' ); ?>
		</form>
	<?php }
}

/**
 * Save screen option for pages list
 */
if ( ! function_exists( 'cstmdmnpg_set_screen_option' ) ) {
	function cstmdmnpg_set_screen_option( $status, $option, $value ) {
		return $value;
	}
}

add_filter( 'set-screen-option', 'cstmdmnpg_set_screen_option', 10, 3 );