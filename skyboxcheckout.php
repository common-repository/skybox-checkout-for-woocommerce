<?php

/**
 * Plugin Name: Skybox Checkout for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woocommerce-skyboxcheckout/
 * Description: An e-commerce toolkit that helps you sell anything. Beautifully.
 * Author: Skybox_Checkout
 * Author URI: http://www.skybox.net/
 * Version: 1.3.1
 * Text Domain: woocommerce-skyboxcheckout
 * License: GPLv2 or later
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$variants_price_printed = array();
$combo_price_writed = false;
$current_product_key = "";

define( 'PATH_DIR', plugin_dir_url( __DIR__ ) );
define( SKYBOX_CHECKOUT, 'skyboxcheckout' );
define( INTEGRATION_TYPE, 'integrationType' );

if ( ! function_exists( 'sbc_is_plugin_active' ) ) {
	/**
	 * sbc_is_plugin_active.
	 *
	 * @since   0.1.0
	 * @return  bool
	 */
	function sbc_is_plugin_active( $plugin ) {
		return ( 
			in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
			( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
		 );
	}
}

// Check if WooCommerce is active
if ( ! sbc_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}
if ( ! function_exists( 'sbc_is_plugin_active' ) ) {
	/**
	 * sbc_is_plugin_active.
	 *
	 * @since   0.1.0
	 * @return  bool
	 */
	function sbc_is_plugin_active( $plugin ) {
		return ( 
			in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
			( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
		);
	}
}

// Check if WooCommerce is active
if ( ! sbc_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}

//Install
add_action( 'init', 'install' );

function install() {
	include_once( 'includes/class-sbc-utils.php' );
	// Create WP Pages
	$pages = array(
		'checkout_page' => array(
			'slug'    => 'skbcheckout-international',
			'title'   => _x( 'SkyBox Checkout International', 'Page title', 'skyboxcheckout' ),
			'content' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'sbc_checkout' ) . ']',
		),
		'success_page'  => array(
			'slug'    => 'skbcheckout-success',
			'title'   => _x( 'SkyBox Checkout International - Success', 'Page title', 'skyboxcheckout' ),
			'content' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'sbc_checkout_success' ) . ']',
		),
	);

	foreach ( $pages as $key => $page ) {
		$page_exist = get_page_by_path( $page['slug'] , OBJECT );

		if ( ! isset($page_exist) ) {
			$page_id = SBC_Utils::create_wp_page( esc_sql( $page['slug'] ), $page['title'], $page['content'] );
		}		
	}

	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
}

//Init the plugin after all plugins to prevent incompatibilities
if ( defined( 'DOING_AJAX' ) ) {	
	init_plugin();
} else {
	add_action( 'plugins_loaded', 'init_plugin' );
}

function init_plugin() {
	if ( ! class_exists( 'Skybox_Checkout' ) ) :

		/**
		 * Skybox_Checkout Class.
		 *
		 * @class Skybox_Checkout
		 * @version 1.2.0
		 */
		final class Skybox_Checkout {

			/**
			 * The single instance of the class.
			 *
			 * @var Skybox_Checkout
			 */
			protected static $_instance = null;

			/**
			 * Session instance.
			 *
			 * @var WC_Session|WC_Session_Handler
			 */
			public $session = null;
			/**
			 * @var bool Is debug enabled
			 */
			public $log_enabled = true;

			/**
			 * @var WC_Logger Logger instance
			 */
			public $log;

			/**
			 * Main Skybox_Checkout Instance.
			 *
			 * Ensures only one instance of Skybox_Checkout is loaded or can be loaded.
			 *
			 * @static
			 * @see SB()
			 * @return Skybox_Checkout - Main instance.
			 */
			public static function instance() {
				if ( is_null( self::$_instance ) && ! ( self::$_instance instanceof Skybox_Checkout ) ) {
					self::$_instance = new Skybox_Checkout();
				}

				return self::$_instance;
			}

			/**
			 * Private clone method to prevent cloning of the instance of the
			 * *Singleton* instance.
			 *
			 * @return void
			 */
			private function __clone() {}

			/**
			 * Private unserialize method to prevent unserializing of the *Singleton*
			 * instance.
			 *
			 * @return void
			 */
			private function __wakeup() {}

			/**
			 * Constructor.
			 */
			public function __construct() {				
				$this->define_constants();
				if( $this->core_includes() ){
					add_action( 'init', array( $this, 'init' ), 0 );					
				}
			}

			/**
			 * Include required core files.
			 */
			private function core_includes() {

				try{
					include_once( 'includes/partials/settings.php' );
					include_once( 'includes/class-sbc-session.php' );
					include_once( 'includes/class-sbc-utils.php' );
				}catch( Exception $e ){
					return false;
				}
				return true;			
			}

			/**
			 * Init WooCommerce when WordPress Initialises.
			 */
			public function init() {
				
				try {
					if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {						
						include_once( 'includes/admin/class-sbc-admin-menus.php' );
						include_once( 'includes/admin/class-sbc-skybox-gateway.php' );
						include_once( 'includes/admin/class-sbc-admin-settings.php' );
						include_once( 'includes/admin/class-sbc-admin-utils.php' );
						include_once( 'includes/admin/class-sbc-admin-product.php' );
						include_once( 'includes/admin/class-sbc-admin-category.php' );
						include_once( 'includes/class-sbc-widget-change-country.php' );
					}else{
						
						if ( ! $this->sbc_plugin_enable() ) {
							return;
						}
						
						if ( get_option( INTEGRATION_TYPE ) ) {
							$this->front_includes();
							SBC_Shortcodes::init();
							add_action( 'wp_head', array( $this, 'sbc_head' ) );
						} else {
							if ( strpos( $_SERVER[REQUEST_URI], "skbcheckout-international" ) != false || strpos( $_SERVER[REQUEST_URI], "skbcheckout-success" ) != false ){
								header( 'Location: '.get_home_url() );
							}
						}
					}
				} catch ( Exception $e ){
					set_option( SKYBOX_ENABLED, false );
					$this->log("[SBC] CRITICAL ERROR");
				}			
			}
			
			public function sbc_head() {
				
				$store_id 		= get_option( STORE_ID );
				$parts 			= explode( "*" , $store_id );
				$ngrokWarning 	= false;

				if( count( $parts ) >1 ){
					//EXTERNAL LINK
					$store_id 		= $parts[0];
					$script_src 	= $parts[1];
					$ngrokWarning	= true;				
				}else{
					$script_src 	= "https://s3.amazonaws.com/sky-sbc-resources/Resources/". $store_id ."/woocommerce.". $store_id .".js";
				}	
				
				//FRONT-END INTEGRATION
				if( $this->sbc_plugin_enable() ){ ?>

					<script>

						<?php global $woocommerce;
							if ( $ngrokWarning ) {
						?>
							console.log('::Warning, using ngrok.');
						<?php }?>

						localStorage.setItem( 'skb-cfg', '{  "merchantId": "<?php echo get_option( MERCHANT_ID ); ?>", ' +
															'"merchantKey": "<?php echo get_option( MERCHANT_KEY ); ?>", ' +	
															'"IdStore": "<?php echo $store_id; ?>", ' +													
															'"weightUnit": "<?php echo get_option( WEIGHT_UNIT ); ?>", ' +
															'"apiUrl": "<?php echo get_option( URL_API ); ?>", ' +
															'"clientUrl": "<?php echo get_option( URL_CLIENT ); ?>", '+
															'"checkoutUrl": "<?php echo '/skbcheckout-international/'; ?>", '+
															'"successUrl": "<?php echo '/skbcheckout-success/'; ?>", '+
															'"baseURL": "<?php echo get_home_url(); ?>", '+
															'"cartUrl": "<?php echo $woocommerce->cart->get_cart_url(); ?>", '+
															'"integrationType": "<?php echo get_option( INTEGRATION_TYPE ); ?>", ' +
															'"PLugin Version": "<?php echo SBC_VERSION; ?>", ' +
															'"js-source": "<?php echo $script_src; ?>" ' +
															'}' );
					</script>
					<?php					
						echo '<script type="text/javascript" src="'. $script_src .'" ansyc></script>'."\n";
					?>
				<?php }
			}

			private function define_constants() {

				// Plugin version.
				if ( ! defined( 'SBC_VERSION' ) ) {
					define( 'SBC_VERSION', '1.3.1' );
				}

				if ( ! defined( 'SBC_ROOT_PATH' ) ) {
					define( 'SBC_ROOT_PATH', plugin_basename( __DIR__ ) );
				}

				if ( ! defined( 'SBC_ROOT_URL' ) ) {
					define( 'SBC_ROOT_URL', plugin_dir_url( __DIR__ ) );
				}
			}

			/**
			 * Load the plugin text domain for translation.
			 */
			private function load_plugin_textdomain() {
				load_plugin_textdomain( SKYBOX_CHECKOUT, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			}

			/**
			 * Include required core files used in admin and on the frontend.
			 */
			private function front_includes() {			
				include_once( 'includes/class-sbc-product.php' );
				include_once( 'includes/class-sbc-cart.php' );			
				include_once( 'includes/class-sbc-optimize-scripts.php' );
				include_once( 'includes/class-sbc-shortcodes.php' );
				include_once( 'includes/class-sbc-widget-change-country.php' );
				include_once( 'includes/class-sbc-api.php' );
			}			

			public function sbc_plugin_enable() {
				return boolval( get_option( SKYBOX_ENABLED ) );
			}		

			/**
			 * Logging method.
			 *
			 * @param string $message
			 */
			public function log( $message ) {
				if ( $this->log_enabled ) {
					if ( empty( $this->log ) ) {
						$this->log = new WC_Logger();
					}
					$this->log->add( SKYBOX_CHECKOUT, $message );
				}
			}

			/**
			 * What type of request is this?
			 *
			 * @param  string $type admin, ajax, cron or frontend.
			 *
			 * @return bool
			 */
			private function is_request( $type ) {
				switch ( $type ) {
					case 'admin' :
						return is_admin();
					case 'ajax' :
						return defined( 'DOING_AJAX' );
					case 'cron' :
						return defined( 'DOING_CRON' );
					case 'frontend' :
						return ! is_admin();
				}
			}

		}

	endif;

	if ( ! function_exists( 'SBC' ) ) {
		/**
		 * The main function for that returns Skybox_Checkout
		 *
		 * The main function responsible for returning the one true Skybox_Checkout
		 * Instance to functions everywhere.
		 *
		 * Use this function like you would a global variable, except without needing
		 * to declare the global.
		 *
		 * Example: <?php $sbc = Skybox_Checkout(); ?>
		 *
		 * @since 0.1
		 * @return object|Skybox_Checkout The one true Skybox_Checkout Instance.
		 */
		function SBC() {
			return Skybox_Checkout::instance();
		}
	}
	// Get SBC Running.
	SBC();
}

