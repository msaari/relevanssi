<?php
/**
 * /lib/compatibility/restrictcontentpro.php
 *
 * Restrict Content Pro compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_restrictcontentpro_compatibility', 10, 2 );

/**
 * Checks whether the user is allowed to see the post.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_restrictcontentpro_compatibility( $post_ok, $post_id ) {
	if ( ! $post_ok ) {
		return $post_ok;
	}

	$post_ok = rcp_user_can_access( get_current_user_id(), $post_id );

	return $post_ok;
}
