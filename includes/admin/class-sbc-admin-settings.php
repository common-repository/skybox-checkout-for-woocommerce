<?php
/**
 * SkyboxCheckout Settings
 *
 * @author   SkyboxCheckout
 * @category Admin
 * @package  SkyboxCheckout/Admin
 */

if ( ! defined( 'ABSPATH' ) )
{
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'SBC_Admin_Settings' ) ) :

	/**
	 * SBC_Settings_SkyboxCheckout.
	 */

	require_once plugin_dir_path( WC_PLUGIN_FILE  ) . '/includes/admin/settings/class-wc-settings-page.php';

	class SBC_Admin_Settings extends WC_Settings_Page
	{

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->id    = 'skyboxcheckout';
			$this->label = __( 'SkyboxCheckout', 'skyboxcheckout' );

			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 21 );
			add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections()
		{
			$sections = array(
				'' => __( 'SkyboxCheckout Options', 'skyboxcheckout' ),
			 );

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output()
		{
			global $current_section;
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save()
		{			
			global $current_section;
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::save_fields( $settings );
			
			SBC_GATEWAY()->connect();
		}

		/**
		 * Get Settings array.
		 *
		 * @param $current_section
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' )
		{

			$settings = apply_filters( 'woocommerce_skyboxcheckout_settings', array( 
				array( 
					'title' => __( 'SkyboxCheckout Settings', 'skyboxcheckout' ),
					'type'  => 'title',
					'id'    => 'skyboxcheckout_merchant_settings'
				 ),

				array( 
					'title'    => __( 'Enabled', 'skyboxcheckout' ),
					'desc'     => __( 'This controls verify is enable', 'skyboxcheckout' ),
					'id'       => 'skyboxcheckout_enabled',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => '1',
					'type'     => 'select',
					'options'  => array( 
						'1' 		=> __( 'Yes', 'skyboxcheckout' ),
						'0' 		=> __( 'No', 'skyboxcheckout' )
					 ),
					'desc_tip' => true,
				 ),

				array( 
					'title'       => __( 'Store ID', 'skyboxcheckout' ),
					'desc'        => __( 'Store ID by Skybox Checkout International.', 'skyboxcheckout' ),
					'id'          => 'skyboxcheckout_store_id',
					'type'        => 'text',
					'placeholder' => '',
					'css'         => 'min-width:350px;',
					'default'     => '',
					'autoload'    => false,
					'desc_tip'    => true
				 ),
				
				array( 
					'title'       => __( 'Merchant Code', 'skyboxcheckout' ),
					'desc'        => __( 'Code Merchant by Skybox Checkout International.', 'skyboxcheckout' ),
					'id'          => 'skyboxcheckout_merchant_id',
					'type'        => 'text',
					'placeholder' => '',
					'css'         => 'min-width:350px;',
					'default'     => '',
					'autoload'    => false,
					'desc_tip'    => true
				 ),

				array( 
					'title'       => __( 'Merchant Key', 'skyboxcheckout' ),
					'desc'        => __( 'Key Merchant by Skybox Checkout International.', 'skyboxcheckout' ),
					'id'          => 'skyboxcheckout_merchant_key',
					'type'        => 'text',
					'placeholder' => '',
					'css'         => 'min-width:350px;',
					'default'     => '',
					'autoload'    => false,
					'desc_tip'    => true
				 ),

				array( 
					'title'    => __( 'Weight Unit', 'skyboxcheckout' ),
					'desc'     => __( 'Source model provider WooCommerce unit of measure values.', 'skyboxcheckout' ),
					'id'       => 'skyboxcheckout_weight_unit',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => 'LBS',
					'type'     => 'select',
					'options'  => array( 
						'LBS'		=> __( 'LBS', 'skyboxcheckout' ),
						'KGS'		=> __( 'KGS', 'skyboxcheckout' )
					 ),
					'desc_tip' => true,
				 ),

				array( 
					'title'    => __( 'Save API Responses', 'skyboxcheckout' ),
					'desc'     => __( 'Source model provider WooCommerce unit of measure values.', 'skyboxcheckout' ),
					'id'       => 'skyboxcheckout_api_response',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'default'  => '0',
					'type'     => 'select',
					'options'  => array( 
						'1' => __( 'Yes', 'skyboxcheckout' ),
						'0' => __( 'No', 'skyboxcheckout' )
					 ),
					'desc_tip' => true,
				 ),

				array( 
					'title'       => __( 'Email', 'skyboxcheckout' ),
					'desc'        => __( 'SkyBox email support.', 'skyboxcheckout' ),
					'id'          => 'skyboxcheckout_skybox_email',
					'type'        => 'email',
					'placeholder' => '',
					'css'         => 'min-width:350px;',
					'default'     => '',
					'autoload'    => false,
					'desc_tip'    => true
				 ),

				array( 
					'title'       => __( 'API Url', 'skyboxcheckout' ),
					'desc'        => __( 'API Url by Skybox Checkout International.', 'skyboxcheckout' ),
					'id'          => 'skyboxcheckout_url_api',
					'type'        => 'text',
					'placeholder' => '',
					'css'         => 'min-width:350px;',
					'default'     => '',
					'autoload'    => false,
					'desc_tip'    => true
				 ),

				array( 
					'title'       => __( 'Client Url', 'skyboxcheckout' ),
					'desc'        => __( 'Client Url by Skybox Checkout International.', 'skyboxcheckout' ),
					'id'          => 'skyboxcheckout_uri_client',
					'type'        => 'text',
					'placeholder' => '',
					'css'         => 'min-width:350px;',
					'default'     => '',
					'autoload'    => false,
					'desc_tip'    => true
				),

				array( 
					'type' => 'sectionend',
					'id'   => 'skyboxcheckout_merchant_settings',
				 ),

			 ) );

			return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
		}
	}

endif;

return new SBC_Admin_Settings();
