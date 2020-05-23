<?php
/**
 * /uninstall.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;
if ( ! defined( 'RELEVANSSI_PREMIUM' ) ) {
	define( 'RELEVANSSI_PREMIUM', false );
}
require_once 'lib/uninstall.php';

if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	$blogids    = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$old_blogid = $wpdb->blogid;
	foreach ( $blogids as $uninstall_blog_id ) {
		switch_to_blog( $uninstall_blog_id );
		relevanssi_uninstall_free();
		restore_current_blog();
	}
} else {
	relevanssi_uninstall_free();
}
