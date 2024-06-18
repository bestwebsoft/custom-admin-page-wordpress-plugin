<?php
/**
 * Displays the content on the plugin settings page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Cstmdmnpg_Settings_Tabs' ) ) {
	/**
	 * Class Cstmdmnpg_Settings_Tabs for Settings
	 */
	class Cstmdmnpg_Settings_Tabs extends Bws_Settings_Tabs {
		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename Plugin basename.
		 */
		public function __construct( $plugin_basename ) {
			global $cstmdmnpg_options, $cstmdmnpg_plugin_info;

			$tabs = array(
				'misc'        => array( 'label' => __( 'Misc', 'custom-admin-page' ) ),
				'custom_code' => array( 'label' => __( 'Custom Code', 'custom-admin-page' ) ),
				'license'     => array( 'label' => __( 'License Key', 'custom-admin-page' ) ),
			);

			parent::__construct(
				array(
					'plugin_basename' => $plugin_basename,
					'plugins_info'    => $cstmdmnpg_plugin_info,
					'prefix'          => 'cstmdmnpg',
					'default_options' => cstmdmnpg_get_options_default(),
					'options'         => $cstmdmnpg_options,
					'tabs'            => $tabs,
					'wp_slug'         => 'custom-admin-page',
					'link_key'        => '23e9c49f512f7a6d0900c5a1503ded4f',
					'link_pn'         => '614',
					'doc_link'        => 'https://bestwebsoft.com/documentation/custom-admin-page/custom-admin-page-user-guide/',
				)
			);
		}

		/**
		 * Save options
		 */
		public function save_options() {}
	}
}
