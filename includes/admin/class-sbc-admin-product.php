<?php
/**
 * WooCommerce Admin Product in WP admin.
 *
 * @author   Skybox Checkout
 * @category Admin
 * @package  Skybox_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SB_Admin_Product' ) ) :

	/**
	 * SB_Admin_Product Class.
	 */
	class SB_Admin_Product {

		const SKYBOX_COMMODITY = '_skybox_commodity';

		protected $_data;

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_custom_general_fields' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'add_custom_general_fields_save' ) );
		}

		/**
		 * Add custom fields.
		 */
		public function add_custom_general_fields() {
			
			$commodities = SBC_Admin_Utils::get_commodities( true );

			if( count( $commodities ) > 0 ) {

				$value = get_post_meta( get_the_ID(), self::SKYBOX_COMMODITY, true );
				
				woocommerce_wp_select(
					array( 
						'id'          => self::SKYBOX_COMMODITY,
						'label'       => __( 'Skybox Commodity', 'skyboxcheckout' ),
						'options'     => $commodities,
						'desc_tip'    => true,
						'description' => __( 'Choose the Skybox Commodity.', 'skyboxcheckout' ),
						'value'		  => '' . $value
					 ) 
				);
			}
		}

		/**
		 * Save custom fields.
		 */
		public function add_custom_general_fields_save( $post_id ) {
			$woocommerce_select = $_POST[ self::SKYBOX_COMMODITY ];
			if ( !is_null( $woocommerce_select ) && $woocommerce_select != 0 ) {
				update_post_meta( $post_id, self::SKYBOX_COMMODITY, esc_attr( $woocommerce_select ) );
			}else{
				$value = get_post_meta( get_the_ID(), self::SKYBOX_COMMODITY, true );
				delete_post_meta( $post_id, self::SKYBOX_COMMODITY, esc_attr( $value ) );
			}
		}

	}
endif;

return new SB_Admin_Product();
