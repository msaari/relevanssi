<?php
/**
 * /lib/compatibility/simplemembership.php
 *
 * Simple Membership compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_simplemembership_compatibility', 10, 2 );

/**
 * Checks whether the user is allowed to see the post.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_simplemembership_compatibility( $post_ok, $post_id ) {
	$access_ctrl = SwpmAccessControl::get_instance();
	$post        = get_post( $post_id );
	$post_ok     = $access_ctrl->can_i_read_post( $post );

	return $post_ok;
}
