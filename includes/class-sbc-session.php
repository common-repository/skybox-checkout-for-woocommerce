<?php
/**
 * Skybox_Checkout Session
 *
 * This is a wrapper class for WP_Session / PHP $_SESSION and handles the storage of cart items, purchase sessions, etc
 *
 * @package    Skybox_Checkout
 * @subpackage SBC_Session
 * @copyright   Copyright ( c ) 2017, Skybox Checkout Inc
 * @since       0.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SBC_Session Class
 *
 * @since 0.1.0
 */
class SBC_Session {

	const GROUP_MAIN = 'SBC';

	/**
	 * SBC_Session register.
	 */
	public static function register() {
		session_start();
		if ( ! isset( $_SESSION[ self::GROUP_MAIN ] ) ) {
			$_SESSION[ self::GROUP_MAIN ] = array();
		}
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public static function set( $key, $value ) {
		$_SESSION[ self::GROUP_MAIN ][ $key ] = $value;
	}

	/**
	 * @param $key
	 *
	 * @return $mixed
	 */
	public static function get( $key ) {
		$value = null;

		if ( isset( $_SESSION[ self::GROUP_MAIN ][ $key ] ) ) {
			$value = $_SESSION[ self::GROUP_MAIN ][ $key ];
		}

		return $value;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 */
	public static function has( $key ) {
		$exists = false;

		if ( isset( $_SESSION[ self::GROUP_MAIN ][ $key ] ) ) {
			$exists = true;
		}

		return $exists;
	}

	/**
	 * @param $key
	 */
	public static function forget( $key ) {
		if ( isset( $_SESSION[ self::GROUP_MAIN ][ $key ] ) ) {
			unset( $_SESSION[ self::GROUP_MAIN ][ $key ] );
		}
	}

	/**
	 *
	 */
	public static function destroy() {
		if ( isset( $_SESSION[ self::GROUP_MAIN ] ) ) {
			$_SESSION[ self::GROUP_MAIN ] = array();
		}
	}

	/**
	 * @return mixed
	 */
	public static function all() {
		return $_SESSION[ self::GROUP_MAIN ];
	}
}

SBC_Session::register();
