<?php
/**
 * SkyboxCheckout Api
 *
 * @class    SBC_API
 * @author   SkyboxCheckout
 * @category Admin
 * @package  SkyboxCheckout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'PATH_DIR', plugin_dir_url( __DIR__ ) );
/**
 * SBC_API class.
 */
class SBC_API {
    /**
	 * Debug variable
	 */
    protected $debug = true;
    
	/**
	 * SBC_Cart constructor.
	 */
	public function __construct() {
        $this->namespace = 'skyboxcheckout/v1';
		$this->rest_base = 'order';
        add_action( 'rest_api_init', array( $this, 'add_endpoints' ), 150, 0 ); 
    }
    
    public function add_endpoints(){
        
        register_rest_route( 'v1', '/get_cart/', array(
            'methods'       => 'GET',
            'callback'      => 'SBC_API::get_cart'
        ));

        register_rest_route( 'v1', '/add_to_cart/', array(
            'methods'       => 'POST',

            'callback'      => 'SBC_API::add_to_cart',
            'args'          => array('cart_id'   => 
             array())
        ));

        register_rest_route( 'v1', '/remove_from_cart/', array(
            'methods'       => 'POST',
            'callback'      => 'SBC_API::remove_from_cart',
            'args'          => array('cart_id'   =>  array())
        ));

        register_rest_route( 'v1', '/remove_many_from_cart/', array(
            'methods'       => 'POST',
            'callback'      => 'SBC_API::remove_many_from_cart',
            'args'          => array('cart_id'   =>  array())
        ));

        register_rest_route( 'v1', '/clear_cart/', array(
            'methods'       => 'GET',
            'callback'      => 'SBC_API::clear_cart',
            'args'          => array('cart_id'   =>  array())
        ));

        register_rest_route( 'v1', '/empty_call/', array(
            'methods'       => 'GET',
            'callback'      => 'SBC_API::empty_call'
        ));

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/', array(
			'methods'       => WP_REST_Server::CREATABLE,
			'callback'      => array( $this, 'create_item' ),
			'show_in_index' => false,
		) );
    }

    public function empty_call($request_data) {

        return new WP_REST_Response( Date('m:s:ms') , 200);
    }
    
    /**
	 * Remove many products from cart
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
    public function remove_many_from_cart($request_data) {
        
        global $woocommerce;

        $parameters = $request_data->get_params();
        $products   = $parameters['variant_ids'];
        $found      = false;
        $debug      = '';
        $status     = 200;

        $WCCartContent = WC()->cart->cart_contents;
        $_pf = new WC_Product_Factory();        

        if ( sizeof( WC()->cart->get_cart() ) > 0 && sizeof($products) > 0) {            
            
            foreach($products as $key => $rmv_product){

                foreach ( $WCCartContent as $wcProductKey =>  $product ) {
                    
                    $WCproduct = $_pf->get_product($product['product_id']);

                    if($WCproduct->is_type( 'simple' ))
                        $product_id       = $product['product_id'];
                    else
                        $product_id       = $product['variation_id'];
                    
                    if ( $product_id == $rmv_product["idVariant"] ) {

                        WC()->cart->remove_cart_item($wcProductKey);
                        $found = true;
                    }    
                }
            }
        } else {
            return new WP_REST_Response( 'empty cart found' , 200);
        }

        return new WP_REST_Response( array('deleted'=>$found) , $status);
    }   

    /**
	 * Clear cart
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
    public function clear_cart($request_data) {

        global $woocommerce;

        $found  = false;
        $status = 200;
        
        if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
            
            foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

                $woocommerce->cart->set_quantity( $cart_item_key, 0, true  );
                            
                $found = true;
            }
        }

        return new WP_REST_Response( array('deleted'=>$found) , $status);
    }

    /**
	 * Remove from cart
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
    public function remove_from_cart($request_data) {

        $parameters = $request_data->get_params();
        $product_id = $parameters['variant_id'];
        $found      = false;
        $status     = 200;

        if ( sizeof( WC()->cart->get_cart() ) > 0 ) {

            foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

                $_product = $values['data'];

                if ( $_product->id == $product_id ) {

                    $found = true;
                    $current_quantity = $values['quantity'];
                    break;                    
                }        
            }

            if ( $found ){
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }

        return new WP_REST_Response( $status == 200 ? '' : $debug , $status);
    }

    /**
	 * Add to cart
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
    public function add_to_cart($request_data) {
        
        $parameters = $request_data->get_params();
        $product_id = $parameters['variant_id'];
        $found      = false;
        $status     = 200;        
        
        if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
            
            $current_quantity = 0;

            foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
                
                $_product = $values['data'];
                
                if ( $_product->id == $product_id ){
                    
                    $found              = true;
                    $current_quantity   = $values['quantity'];
                    break;
                }
            }
            
            // if product not found, add it
            if ( $found ){

                try{

                    WC()->cart->set_quantity($cart_item_key, $parameters['quantity']);
                } catch (Exception $e) {
                    
                    $status = 500; 
                }
            } else {

                try{

                    WC()->cart->add_to_cart( $product_id );
                } catch (Exception $e) {

                    $status = 500;
                }
            }
        } else {
            return new WP_REST_Response( '6' , 200);
            // if no products in cart, add it
            try{
                WC()->cart->add_to_cart( $product_id );
            } catch (Exception $e) {
                $status = 500;
            }

        }        
        
        return new WP_REST_Response( "" , 200);
    }

    /**
	 * Get Cart
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
    public function get_cart($request_data) {
        $response_data = array();
        $data = array();

        $_pf = new WC_Product_Factory();

        foreach (WC()->cart->get_cart() as $wcProductKey =>  $product) {

            $WCproduct      = $_pf->get_product($product['product_id']);
            $WCFinalProduct = $WCproduct;
            $variant_title  = '';
            $variation_id   = $product['product_id'];
            $data           = array();

            $sku        = 'error sku';
            $weight     = 0;
            $variations = array();

            if($WCproduct->is_type( 'simple' )){
                $variant_title  = $WCproduct->get_name();
                $sku            = $WCproduct->get_sku();
                $weight         = ($WCproduct->get_weight() != "") ? floatval($WCproduct->get_weight()) : 0;
            }else{
                $variation_id       = $product['variation_id'];
                $WCVariantProduct   = $_pf->get_product($variation_id);
                $variant_title      = $WCVariantProduct->get_name();
                //GET ALL ATRIBUTES NAMES
                $variations_cart    = $product['variation'];
                $variations         = array();

                foreach ($variations_cart as $key => $variation) {

                    if ( strlen($variation) > 0 ) {

                        try {
                            $variation_name     = $key;
                            $variation_tax_name = str_replace('attribute_','',$variation_name);
                            $variation_name     = str_replace('pa_','',$variation_tax_name);
                            $variation_name     = ( strlen($variation_name) > 0 ) ? $variation_name : "";
                            $variation_slug     = ( strlen($variation) > 0 ) ? ucfirst($variation) : "";

                            $variation_item = array();
                            $variation_item["label"]    = ucfirst($variation_name);
                            $variation_item["value"]    = $variation_slug;
                            $variation_item["id"]       = $variation_tax_name;
                            $variation_item["idValue"]  = $variation_slug;
                            array_push($variations, $variation_item);

                        } catch ( Exception $e ) {
                            
                        }
                    }

                }
                $WCFinalProduct     = $WCVariantProduct;
                $sku                = $WCVariantProduct->get_sku();

                if(($WCVariantProduct->get_weight() != "")){
                    $weight = floatval($WCVariantProduct->get_weight());
                }else{
                    $weight = ($WCproduct->get_weight() != "") ? floatval($WCproduct->get_weight()) : 0;
                }
            }
            
            $data =  array(
                'variant_id'        => $variation_id,
                'sku'               => $sku,
                'product_title'     => $variant_title,
                'category'          => SBC_Utils::get_commodity_product($product['product_id']),
                'price'             => floatval($WCFinalProduct->get_price()),
                'image'             => SBC_Utils::get_product_first_image($WCFinalProduct),
                'weight'            => $weight,
                'weightUnit'        => get_option(WEIGHT_UNIT),                
                'quantity'          => $product['quantity'],
                'variant_title'     => $variant_title,
                'wcProductKey'      => $wcProductKey,
                'product_id'        => $product['product_id'],
                'variation_id'      => $product['variation_id'],
                'width'			    => $WCFinalProduct->get_width(),
                'height'	    	=> $WCFinalProduct->get_height(),
                'length'		    => $WCFinalProduct->get_length(),                
                'options'           => $variations,
            );
            $response_data [] = $data;
        }
        
        return new WP_REST_Response( $response_data , 200);
    }

    /**
	 * Creates a Order.
	 *
	 * @since 0.1.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		$customer       = $request->get_param( 'customer' );
		$cart           = $request->get_param( 'cart' );
		$address        = $this->get_address( $customer );
		$cart_items     = $cart['products'];
		$total          = $cart['total'];
		$total_concepts = $cart['total_concepts'];
		$data           = array();
		$status         = 200;

		try {

			$order = $this->create_order( $address, $cart_items, $total_concepts, $total );
			$data['order_number'] = $order->get_order_number();
			$data = array( 'data' => $data );

		} catch ( \Exception $e ) {

			echo 'Caught exception: ', $e->getMessage(), "\n";
			$errors['message']  = $e->getMessage();
			$status             = 404;
			$data               = array( 'errors' => $errors );
		}

		$response = new WP_REST_Response( $data, $status );

		return $response;
	}

	/**
	 * Create Order
	 *
	 * @param array $address
	 * @param array $cart_items
	 * @param $concepts
	 * @param $total
	 *
	 * @return WC_Order|WP_Error
	 */
    private function create_order( array $address, $cart_items, $concepts, $total )
    {
		global $woocommerce;

		// Now we create the order
		$order = wc_create_order();

		$order->set_address( $address, 'shipping' );
		$order->set_address( $address, 'billing' );
        
        $subtotal = 0;
		foreach ( $cart_items as $key => $item ) {

			$productId  = $item['id'];
			$price      = floatval( $item['price_usd'] );
			$qty        = $item['quantity'];
            $product    = wc_get_product( $productId );
            
			if ( ! is_object( $product ) ) {
				throw new \Exception( 'Product Not found!' );
			}

			$product->set_price( $price );
			$order->add_product( $product, $qty );
			$subtotal += $price * $qty;
		}

		// Set Address
		$order->set_address( $address, 'billing' );
		$order->set_address( $address, 'shipping' );

		// Gateway
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$order->set_payment_method( $available_gateways['skybox'] );

        $fee = $this->get_fee( 'SubTotal', $subtotal );
        $order->add_fee( $fee );

		$fee = $this->get_fee( 'SkyBox Checkout', $concepts );
		$order->add_fee( $fee );

		// $order->calculate_totals();
		$order->set_total( $total );
		$order->update_status( "Completed", 'Imported order', true );

		return $order;
	}

	/**
	 * Returns Shipping Address
	 *
	 * @param array $customer
	 *
	 * @return array
	 */
	private function get_address( array $customer ) {

		$address = array(
			'email'      => $customer['email'],
			'first_name' => $customer['shipping_address']['firstname'],
			'last_name'  => $customer['shipping_address']['lastname'],
			'address_1'  => $customer['shipping_address']['street'],
			'city'       => $customer['shipping_address']['city'],
			'country'    => $customer['shipping_address']['country_id'],
			'state'      => $customer['shipping_address']['region'],
			'postcode'   => $customer['shipping_address']['postcode'],
			'phone'      => $customer['shipping_address']['telephone'],
		);

		return $address;
	}

	private function get_fee( $text, $amount ) {
        
		$fee            = new stdClass();
		$fee->name      = $text;
		$fee->tax_class = '';
		$fee->taxable   = '0';
		$fee->amount    = $amount;
		$fee->tax       = '';
		$fee->tax_data  = array();

		return $fee;
    } 
}

new SBC_API();

?>