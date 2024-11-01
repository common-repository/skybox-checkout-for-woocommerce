<?php
/**
 * Setup menus in WP admin.
 *
 * @author   SkyboxCheckout
 * @category Admin
 * @package  SkyboxCheckout/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SBC_Admin_Menus' ) ) :

	/**
	 * SB_Admin_Menus Class.
	 */
	class SBC_Admin_Menus {

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			// Add menus
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		}

		/**
		 * Add shortcut menu items.
		 */
		public function admin_menu() {
			
			add_menu_page( __( 'Skybox Checkout', 'skyboxcheckout' ), __( 'Skybox Checkout', 'skyboxcheckout' ), 1,
				'wc-settings&tab=skyboxcheckout', "custom_function", null );
		}

	}
endif;

return new SBC_Admin_Menus();
