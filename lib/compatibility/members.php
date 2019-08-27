<?php
/**
 * /lib/compatibility/members.php
 *
 * Members compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_members_compatibility', 10, 2 );

/**
 * Checks whether the user is allowed to see the post.
 *
 * Only applies to private posts and only if the "content permissions" feature
 * is enabled.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_members_compatibility( $post_ok, $post_id ) {
	$status = relevanssi_get_post_status( $post_id );

	if ( 'private' === $status ) {
		if ( members_content_permissions_enabled() ) {
			$post_ok = members_can_current_user_view_post( $post_id );
		}
	}

	return $post_ok;
}
