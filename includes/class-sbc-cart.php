<?php
/**
 * SkyboxCheckout Cart
 *
 * @class    SBC_Cart
 * @author   SkyboxCheckout
 * @category Admin
 * @package  SkyboxCheckout/Cart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * SBC_Cart class.
 */
class SBC_Cart {

	protected $integration_type;
	protected $_allocation_allow;

	/**
	 * SBC_Cart constructor.
	 *
	 */
	public function __construct() {

		$this->integration_type = get_option( INTEGRATION_TYPE );

		if ( ! SBC_Utils::in_checkout_native_page() ) {
			
			add_filter( 'woocommerce_cart_item_price', array( $this, 'modify_item_price_from_cart' ), 150, 3 );
			add_action( 'woocommerce_cart_product_subtotal', array( $this,'modify_item_subtotal_from_cart' ), 10, 3 );
			add_action( 'woocommerce_mini_cart_contents', array( $this, 'add_summary_mini_cart' ), 160, 0 );
			add_action( 'woocommerce_after_mini_cart', array( $this, 'rewrite_class_checkout_button' ), 160, 0 );
			add_action( 'woocommerce_before_cart_totals', array( $this,'insert_summary_table_cart_ini' ), 10, 0 );
			add_action( 'woocommerce_after_cart_totals', array( $this,'insert_summary_table_cart_end' ), 10, 0 );
			add_action( 'woocommerce_widget_shopping_cart_buttons', array( $this,'add_checkout_button_mini_cart' ), 10, 1 );
			add_action( 'woocommerce_cart_contents', array( $this,'add_class_coupons_init' ), 10, 1 );
			add_action( 'woocommerce_cart_coupon', array( $this,'add_class_coupons_end' ), 10, 1 );
		}
			
		
	}

	/**
	 * Start php buffer
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function insert_summary_table_cart_ini()	{

		ob_start();
	}

	/**
	 * Insert Skybox Summary on cart
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function insert_summary_table_cart_end()	{

		$php_buffer = ob_get_contents();
		ob_end_clean();
		
		$skbx_html_button 	= '';
		$skbx_html_summary	= '';

		if ( $this->integration_type == 1 ) {

			$skbx_html_summary .= '<div class="skbx-price">';
			$skbx_html_summary .= '<div class="skbx-loader-subtotal"></div>';
			$skbx_html_summary .= '<div align="right" class="international-checkout"></div>';
			$skbx_html_summary .= '</div>';
		}

		if ( $this->integration_type == 1 ) {

			$php_buffer = str_replace( 'class="woocommerce-Price-amount', ' class="woocommerce-Price-amount skbx-price-store ', $php_buffer );
		}

		$skbx_html_button = '<div class="skybox-checkout-payment-btn"></div>';
		$php_buffer = str_replace( '<div class="wc-proceed-to-checkout', $skbx_html_summary . $skbx_html_button . '<div class="wc-proceed-to-checkout', $php_buffer );

		if ( $this->integration_type == 1 ) {

			$php_buffer = str_replace( '<div class="wc-proceed-to-checkout', '<div class="wc-proceed-to-checkout skbx-price-store', $php_buffer );
			$php_buffer = str_replace( 'class="shop_table shop_table_responsive', ' class="shop_table shop_table_responsive skbx-price-store ', $php_buffer );
		}
		
		echo $php_buffer;
	}

	/**
	 * Insert Skybox Summary on mini cart
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function add_summary_mini_cart()	{

		if ( $this->integration_type == 1 ){
			$summary_html = '<div class="skbx-price">';
			$summary_html .= '<div align="right" class="international-checkout"></div>';
			$summary_html .= '</div>';
			echo $summary_html;
		}

		ob_start();
	}

	/**
	 * Add SkyboxCheckout class to checkout button
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function rewrite_class_checkout_button()	{
		$php_buffer = ob_get_contents();
		ob_end_clean();

		if ( $this->integration_type == 1 ){
			$php_buffer = str_replace( 'class="woocommerce-mini-cart__total total"', 'class="woocommerce-mini-cart__total total skbx-price-store"', $php_buffer );
			$php_buffer = str_replace( 'class="button checkout wc-forward"', ' class="button checkout wc-forward skbx-price-store"', $php_buffer );
		}
		echo $php_buffer;
	}

	/**
	 * Add SkyboxCheckout html nodes to prices on mini cart
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function modify_item_price_from_cart( $price, $cart_item, $cart_item_key ) {
		global $current_product_key;
		$current_product_key = $cart_item_key;

		if ( $this->integration_type == 1 ) {

			$price = str_replace( 'class="woocommerce-Price-amount', ' class="skbx-price-store woocommerce-Price-amount', $price );
			return "<div class='skbx-loader-cart-". $cart_item_key ."'></div><span class='sky--Price-". $cart_item_key ."'></span>".$price;

		} else {

			return $price;
		}			
	}

	/**
	 * Add SkyboxCheckout html tags to subtotal on cart
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function modify_item_subtotal_from_cart( $product_subtotal, $product, $quantity ) {
		global $current_product_key;
		
		if ( $this->integration_type == 1 ) {

			$product_subtotal = str_replace( 'class="woocommerce-Price-amount', ' class="woocommerce-Price-amount skbx-price-store ', $product_subtotal );
			return "<div class='skbx-loader-cart-". $current_product_key ."'></div><span class='sky--Total-". $current_product_key ."'></span>".$product_subtotal;
		} else {

			return $product_subtotal;
		}
		
	}	

	/**
	 * Add SkyboxCheckout html tags to subtotal on mini cart
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function add_checkout_button_mini_cart()	{
		$skbx_html_button .= '<div class="skybox-checkout-payment-btn"></div>';
		echo $skbx_html_button;
	}

	/**
	 * Add SkyboxCheckout html tags to coupon on cart
	 *
	 * @since 0.1.0
	 * @access public
	 */
	public function add_class_coupons_init()	{
		ob_start();
	}

	public function add_class_coupons_end() {
		$php_buffer = ob_get_contents();
		ob_end_clean();		

		if ( $this->integration_type == 1 ) {
			$php_buffer = str_replace( 'class="checkout_coupon', ' class="checkout_coupon skbx-price-store', $php_buffer );
		}
		echo $php_buffer;
	}
}

new SBC_Cart();
