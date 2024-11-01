<?php
/**
 * SkyboxCheckout Admin
 *
 * @class    SBC_Product
 * @package  SkyboxCheckout
 * @copyright   Copyright (c) 2017, Skybox Checkout Inc
 * @author   SkyboxCheckout
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * SBC_Product_Calculate class.
 */
class SBC_Product {

	protected $integration_type;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->integration_type = get_option( INTEGRATION_TYPE );
			
		if ( $this->integration_type == 1 && ! SBC_Utils::in_checkout_native_page() ) {
			
			add_action( 'woocommerce_get_price_html', array( $this, 'sbc_get_price_html' ), 140, 1);
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'sbc_add_label_add_button_ini' ) );
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'sbc_add_label_add_button_end' ) );
		}
	}

	/**
	 * @param $price
	 *
	 * @return array
	 */
	public function sbc_get_price_html( $price ) {

		global $variants_price_printed;

		/** @var WC_Product $product */
		global $product;

		if( is_null( $product ) ){
			$product = wc_get_product( get_the_ID() );
		}

		if( is_null( $product ) ){
			return;
		}

		$skb_node_prices = '';

		try{

			if ( in_array( $product->get_id(), $variants_price_printed ) ) {
				return;
			}
			$variants_price_printed[] = $product->get_id();

			//getting dimensions
			$dimensions 	= [];
			$dimensions[]	= $product->get_width();
			$dimensions[]	= $product->get_height();
			$dimensions[]	= $product->get_length();

			$skb_node_prices .= SBC_Utils::get_price_html( $product->get_id(), $dimensions);

			if( ! is_shop() ) {				
				if( $product->is_type( 'variable' ) ) {
					$_pf = new WC_Product_Factory();

					try{
						$childs = $product->get_children();
					}catch( Exception $e ){
						$childs = array();
					}

					foreach ( $childs as $ChildKey => $child ) {

						$variation      		= $_pf->get_product( $child );
						$attributes     		= $variation->get_variation_attributes();
						$additional_attribute 	= 'additional_attribute = "';

						foreach ( $attributes as $key => $attribute ) {
							$additional_attribute .= $key . ':' . $attribute . ',';
						}
						$additional_attribute .= '-';
						$additional_attribute = str_replace( ',-' , '"', $additional_attribute );

						$skb_node_prices .= SBC_Utils::get_price_html( $variation->get_id(), $dimensions, $product->get_id(), true, $additional_attribute );
					}
				}
			}
			
			$price = str_replace( 'class="woocommerce-Price-amount', 'class="skbx-price-store woocommerce-Price-amount', $price );
			$price .= $skb_node_prices;
			
		}catch(Exception $e){
			SBC()->log();
			SBC()->log("[SBC] Error : " . $e->getMessage());
			SBC()->log("[SBC] File  : class-sbc-product.php ");
			SBC()->log("[SBC] Method: sbc_get_price_html() ");
			SBC()->log("[SBC] msg	: sbc_get_price_html() ");
			SBC()->log();
		}
		return $price;
		
	}

	/**
	 * Add SkyboxCheckout html tags to add_to_cart button
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function sbc_add_label_add_button_ini()
	{
		ob_start();
	}

	public function sbc_add_label_add_button_end()
	{
		$php_buffer = ob_get_contents();
		ob_end_clean();

		$php_buffer = str_replace('class="single_add_to_cart_button', 'class="Sky--btn-add single_add_to_cart_button', $php_buffer);

		echo $php_buffer;
	}
}

new SBC_Product();