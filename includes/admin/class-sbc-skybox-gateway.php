<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class SBC_Gateway 
{
	/**
	 * The single instance of the class.
	 *
	 * @var SBC_Gateway
	 */
	protected static $_instance = null;

	/**
	 * @var WC_Logger Logger instance
	 */
	public $log;

	private $log_enabled = false;
	
	private $_token = null;

	private $_integration_type = 0;

	private $_comodities = null;

	private $_error_msg = '';

	private $merchant_id;

	private $merchant_key;

	private $connection_enable = false;

	/**
	 * Init parameters connection
	 */
	private function ini_parameters() {

		$this->log("[SBC] MERCHANT_ID  : " . get_option( MERCHANT_ID ));
		$this->log("[SBC] MERCHANT_KEY : " . get_option( MERCHANT_KEY ));

		$this->merchant_id  = get_option( MERCHANT_ID );
		$this->merchant_key = get_option( MERCHANT_KEY );
	}

	/**
	 * Get the Skybox token
	 */
	private function get_token() {
		try {
			$url		= get_option( URL_API ) . "/authenticate";
			$url		= str_replace( "//", "/", $url );
			$headers	= ["Content-Type: application/json"];
			$body 		= [
				"Merchant" => [
					"Id"	=> $this->merchant_id,
					"Key" 	=> $this->merchant_key
				]
			];
			
			$response	= $this->execute($url, $headers, $body);
			
			if( ! is_null( $response ) ) {
				$this->_token 				= $response->Data->Token;
				$this->connection_enable 	= true;
			}

		} catch(Exception $e) {
			$this->connection_enable = false;
		}

		$this->log( "[SBC] get_token() -- connection_enable -- " . print_r( $this->connection_enable , 1 ) );
		$this->log( "[SBC] get_token() -- _integration_type -- " . print_r( $this->_integration_type , 1 ) );
	}

	/**
	 * Get the Skybox Cart
	 */
	private function get_cart() {
		if ( $this->connection_enable ) {
			try {
				$url		= get_option( URL_API ) . "/cart";
				$url		= str_replace( "//", "/", $url );
				$headers 		= [
					"Content-Type:application/json",
					"Authorization:" . $this->_token,
					"X-Skybox-Merchant-Id:" . $this->merchant_id
				];

				$remote_addr			= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
				$http_accept_language 	= isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'en-US,en;q=0.8';
				$userAgentDefault 		= 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';
				$userAgent            	= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : $userAgentDefault;
				
				$body 		= [
					"MerchantId"	=> $this->merchant_id,
					"CartId"		=> null,
					"Customer"		=> [
						"Ip"			=> [
							'Local' 		=> $this->getUserIP(),
							'Remote' 		=> $remote_addr,
							'Proxy' 		=> ''
						],
						'Browser' 	=> [
							'Agent' 		=> $userAgent,
							'Languages' 	=> $http_accept_language 
						],
					]
				];

				$response	= $this->execute( $url, $headers, $body );

				if( ! is_null( $response ) ) {
					$this->_integration_type = $response->Data->IntegrationType;					
					return true;
				} else {					
					$this->_integration_type = 0;
					return false;					
				}
			} catch ( Exception $e ) {				
				$this->_integration_type = 0;
			}

		}else{			
			$this->_integration_type = 0;
			return false;
		}
		$this->log( "[SBC] get_cart() -- connection_enable -- " . print_r( $this->connection_enable , 1 ) );
		$this->log( "[SBC] get_cart() -- _integration_type -- " . print_r( $this->_integration_type , 1 ) );
	}

	/**
	 * Return the Skybox Commodities
	 */
	public function get_comodities() {		
		if ( ! is_null( $this->_comodities ) ) {
			return $this->_comodities;
		}

		$this->connect();

		if( $this->connection_enable ) {
			try {
				$url			= get_option( URL_API ) . "/commodities";
				$url			= str_replace( "//", "/", $url );
				$headers 		= [
					"Content-Type:application/json",
					"Authorization:" . $this->_token,
					"X-Skybox-Merchant-Id:" . $this->merchant_id
				];
				$body 		= [];

				$response	= $this->execute($url, $headers, $body, 'GET');

				if( ! is_null( $response ) ) {
					$this->_comodities = $response->Data->Commodities;
					return $this->_comodities;
				} else {
					return array();
				}
			} catch ( Exception $e ) {
				return array();
			}
		} else {
			return array();
		}
	}

	/**
     * Returns Customer IP Address
     *
     * @return mixed
     */
    public function getUserIP()
    {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }

	/**
     * Call the methods to connect
     *
     * @return mixed
     */
	public function connect() {
		if ( get_option( SKYBOX_ENABLED ) ) {
			$this->ini_parameters();
			$this->get_token();
			if ( ! $this->get_cart() ) {
				$this->connection_enable 	= false;
			}
		} else {
			$this->_error_msg 			= ' SkyboxCheckout is disable.';
			$this->connection_enable 	= false;
		}
		
		$this->send_admin_message();
	}
	
	/**
     * Print the admin message after connection
     *
     */
	public function send_admin_message() {
		if( $this->connection_enable && $this->_integration_type != 0) {
			update_option( INTEGRATION_TYPE, $this->get_integration_type(), true );
			update_option( SKYBOX_ENABLED, true );
			add_action( 'admin_notices', array( $this, 'gateway_message_successful' ) );
		}else{
			update_option( INTEGRATION_TYPE, 0, true );
			update_option( SKYBOX_ENABLED, false );
			add_action( 'admin_notices', array( $this, 'gateway_message_error' ) );
		}		
		
	}

	public function gateway_message_error( $msg ) {
		$class = 'notice notice-error';
		$message = __( 'SkyboxCheckout connection error.' . $this->_error_msg , 'sample-text-domain' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
	}

	public function gateway_message_successful( $msg ) {
		$class = 'notice notice-info';
		$message = __( 'SkyboxCheckout for woocommerce is connected', 'sample-text-domain' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
	}

	/**
     * Return the connection status
	 * 
     * @return bool
     */
	public function connection_enable(){
		return $this->connection_enable;
	}

	/**
     * Return the integration Type
	 * 
     * @return int
     */
	public function get_integration_type() {
		return $this->_integration_type;
	}

	/**
     * Return the Skybox api response
	 * 
     * @return object
     */
	public function execute( $url, $headers, $body, $method = 'POST') {
		$curl 		= curl_init();
		$response 	= null;
		
		$this->log( "" );
		$this->log( "*************[SBC] execute()*************" );

        try {
            curl_setopt( $curl, CURLOPT_URL, $url );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $curl, CURLOPT_HEADER, true);
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

			$this->log( "[SBC] " . $method . " - url   : " . print_r( $url , 1 ) );
			$this->log( "[SBC] " . $method . " - header: " . print_r( $headers , 1 ) );

			switch (strtoupper($method)) {
				case 'POST':
					curl_setopt( $curl, CURLOPT_POST, true);
					curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $body ) );
					$this->log( "[SBC] " . $method . " - body  : " . print_r($body , 1) );
					break;
				default:
					# code...
					break;
			}

			$responseBody	= curl_exec( $curl );
			$header_size 	= curl_getinfo( $curl, CURLINFO_HEADER_SIZE );
			$responseBody	= substr( $responseBody, $header_size );
			$response 		= json_decode( $responseBody );
			$httpcode		= curl_getinfo( $curl, CURLINFO_HTTP_CODE );

			$this->log( "[SBC] " . $method . " - code_res: " . $httpcode );
			$this->log( "[SBC] " . $method . " - response: " . print_r( $responseBody, 1) );

			switch (strtoupper($httpcode)) {
				case 200:
					$this->connection_enable 	= true;
					break;
				case 404:
					$this->connection_enable 	= false;
					$this->_error_msg			= 'Please check the API Url.';
					$response 					= null;
					break;
				case 401:					
					$this->connection_enable 	= false;
					$this->get_msg_error( $response->Errors[0]->Message );
					$response 					= null;
					break;
				case 500:
					$this->connection_enable 	= false;
					$this->_error_msg			= 'Server bussy, please try again.';
					$response 					= null;
					break;
			}
			
        }
        catch(Exception $ex) {
			
			$this->log( "[SBC] " . $method . " - Exception: " . print_r( $ex , 1 ) );
			$this->connection_enable	= false;
			$response 					= null;
        }
        finally {
            curl_close( $curl );
		}
		
		return $response;
		
	}

	public function get_msg_error( $GCODE ) {
		switch ( $GCODE ) {
			case 'GCODE_MERCHANT_ID_INVALID':				
				$this->_error_msg = ' Please, check the Merchant Code.';
				break;
			case 'GCODE_MERCHANT_KEY_INVALID':
				$this->_error_msg = ' Please, check the Merchant Key.';
				break;
		}
	}

	public function log( $message ) {
		
		if ( $this->log_enabled ) {
			
			if ( empty( $this->log ) ) {
				
				$this->log = new WC_Logger();
			}
			$this->log->log( '', $message );
		}
	}

	public static function instance() {
		
		if ( is_null( self::$_instance ) && ! ( self::$_instance instanceof SBC_Gateway ) ) {
			self::$_instance = new SBC_Gateway();
		}

		return self::$_instance;
	}
}

if ( ! function_exists( 'SBC_GATEWAY' ) ) {
	/**
	 * @since 0.1
	 * @return object|SBC_Gateway The one true SBC_Gateway Instance.
	 */
	function SBC_GATEWAY() {		
		return SBC_Gateway::instance();
	}
}

// Get SBC Running.
SBC_GATEWAY();

