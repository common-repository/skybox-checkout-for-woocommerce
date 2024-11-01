<?php
/**
 * SkyboxCheckout Shortcodes
 *
 * @class    SCB_Shortcodes
 * @author   SkyboxCheckout
 * @category Core
 * @package  Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SBC_Shortcodes' ) ) {

	/**
	 * SCB_Shortcodes class.
	 */
	class SBC_Shortcodes {

		/**
		 * Init shortcodes.
		 */
		public static function init() {

			$integration_type = get_option( INTEGRATION_TYPE );

			if ( $integration_type == 1 ){
				add_shortcode( 'skybox_checkout_change_country', __CLASS__ . '::skybox_checkout_change_country_func' );
			}

			$shortcodes = array( 
				'sbc_checkout'         => __CLASS__ . '::checkout',
				'sbc_checkout_success' => __CLASS__ . '::checkout_success'
			);
			
			foreach ( $shortcodes as $shortcode => $function ) {
				add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
			}
		}

		/**
		 * Checkout page shortcode.
		 *
		 * @param mixed $atts
		 *
		 * @return string
		 */
		public static function checkout( $atts ) {
			echo '<div id = "skybox-international-checkout" ></div>';
		}

		/**
		 * Checkout Success page shortcode.
		 *
		 * @param mixed $atts
		 *
		 * @return string
		 */
		public static function checkout_success( $atts ) {			
			echo '<div id = "skybox-international-checkout-invoice" ></div>';
		}

		public static function skybox_checkout_change_country_func() {
			echo '<div class = "skybox-checkout-change-country" ></div>';
		}
		
	}
}
