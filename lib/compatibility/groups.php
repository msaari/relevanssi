<?php
/**
 * /lib/compatibility/groups.php
 *
 * Groups compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_groups_compatibility', 10, 2 );

/**
 * Checks whether the user is allowed to see the post.
 *
 * Only applies to published posts.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_groups_compatibility( $post_ok, $post_id ) {
	$status = relevanssi_get_post_status( $post_id );

	if ( 'publish' === $status ) {
		// Only apply to published posts, don't apply to drafts.
		$current_user = wp_get_current_user();
		$post_ok      = Groups_Post_Access::user_can_read_post( $post_id, $current_user->ID );
	}

	return $post_ok;
}
