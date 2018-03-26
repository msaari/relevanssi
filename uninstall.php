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
define( 'RELEVANSSI_PREMIUM', false );
require_once 'lib/uninstall.php';

if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	$blogids    = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$old_blogid = $wpdb->blogid;
	foreach ( $blogids as $blog_id ) {
		switch_to_blog( $blog_id );
		relevanssi_uninstall_free();
	}
	switch_to_blog( $old_blogid );
} else {
	relevanssi_uninstall_free();
}
