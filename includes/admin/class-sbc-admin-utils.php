<?php
/**
 * SCB Admin Utils for WP admin.
 *
 * @author   Skybox Checkout
 * @category Admin
 * @package  Skybox_Checkout
 */

if ( ! defined( 'ABSPATH' ) )
{
	exit;
}

if ( ! class_exists( 'SBC_Admin_Utils' ) ) :

	/**
	 * SB_Admin_Utils Class.
	 */
	class SBC_Admin_Utils
	{

		/**
		 * Returns Commodities Array
		 */
		public static function get_commodities( $add_default_value = false )
		{

			$data = array();
			$commodities = array();			

			try {
				$commodities = SBC_GATEWAY()->get_comodities();

				if ( count( $commodities ) > 0 ) {
					if ( $add_default_value ){
						$data[0] = '-- Select a commodity --';
					}
	
					foreach ( $commodities as $key => $commodity ) {
	
						$key   			= $commodity->Id;
						$value 			= $commodity->Description;
						$data[ $key ] 	= $value;
					}
	
					asort( $data );
				}

			} catch ( \Exception $e ) {
				SBC()->log( 'An error has been occurred trying to get commodities data.' );
				SBC()->log( 'Error: ' . $e->getMessage() );
			}

			return $data;
		}
	}
endif ;
