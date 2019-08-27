<?php
/**
 * /lib/compatibility/wpjvpostreadinggroups.php
 *
 * WP JV Post Reading Groups compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_wpjvpostreadinggroups_compatibility', 10, 2 );

/**
 * Checks whether the user is allowed to see the post.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_wpjvpostreadinggroups_compatibility( $post_ok, $post_id ) {
	$post_ok = wp_jv_prg_user_can_see_a_post( get_current_user_id(), $post_id );

	return $post_ok;
}
