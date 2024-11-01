<?php
/**
 * Skybox Checkout - Change Country Widget
 *
 * @class    WC_Widget_Change_Country
 * @author   SkyboxCheckout
 * @category Admin
 * @package  SkyboxCheckout/Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Widget_Change_Country' ) ) {

	define( 'PATH_DIR', plugin_dir_url( __DIR__ ) );

	/**
	 * WC_Widget_Change_Country class.
	 */
	class WC_Widget_Change_Country extends WC_Widget {

		/**
		 * Constructor.
		 */
		public function __construct() {
			//$this->widget_cssclass    = 'woocommerce skybox_checkout_change_country';
			$this->widget_description = __( 'Display SkyBox Change Country.', 'skyboxcheckout' );
			$this->widget_id          = 'woocommerce_skyboxcheckout_change_country';
			$this->widget_name        = __( 'SkyBox Checkout ', 'skyboxcheckout' );
			$this->settings           = array( 
											'title'            => array( 
												'type'  => 'text',
												'std'   => __( 'Change Country', 'skyboxcheckout' ),
												'label' => __( 'Title', 'skyboxcheckout' )
											 ),
											'hide_empty_title' => array( 
												'type'  => 'checkbox',
												'std'   => 1,
												'label' => __( 'Hide Title', 'skyboxcheckout' )
											 )
										 );
			parent::__construct();			
		}

		/**
		 * Output widget.
		 *
		 * @see WP_Widget
		 *
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {
			$hide_empty_title = isset( $instance['hide_empty_title'] ) ? $instance['hide_empty_title'] : $this->settings['hide_empty_title']['std'];

			if ( $hide_empty_title ) {				
				echo $args['before_widget'];
			} else {				
				$this->widget_start( $args, $instance );
			}

			$integration_type = get_option( INTEGRATION_TYPE );
			
			if ( $integration_type == 1 ) {
				echo '<div class="skybox-checkout-change-country"></div>';
			}

			$this->widget_end( $args );
		}
	}
}

function wc_active_change_country_widget() {
	register_widget( 'WC_Widget_Change_Country' );
}
add_action( 'widgets_init', 'wc_active_change_country_widget' );
