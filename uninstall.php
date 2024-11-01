<?php
/**
 * SkyboxCheckout Uninstall
 *
 * Uninstalling SkyboxCheckout delete page;
 *
 * @author      WooCommerce
 * @category    Core
 * @package     SkyboxCheckout/Uninstaller
 * @version
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb, $wp_version;

$pages = $wpdb->get_results( "SELECT ID as id FROM {$wpdb->prefix}posts WHERE post_name LIKE '%skbcheckout%' " );
if ( count( $pages ) > 0 ) {
    foreach ( $pages as $page ) {
        $wpdb->query( "DELETE FROM {$wpdb->prefix}posts WHERE `ID`=" . $page->id );
    }
    wp_cache_flush();
}
