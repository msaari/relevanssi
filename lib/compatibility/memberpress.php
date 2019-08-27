<?php
/**
 * /lib/compatibility/memberpress.php
 *
 * Memberpress compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_memberpress_compatibility', 10, 2 );

/**
 * Checks whether the user is allowed to see the post.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_memberpress_compatibility( $post_ok, $post_id ) {
	$post = get_post( $post_id );
	if ( MeprRule::is_locked( $post ) ) {
		$post_ok = false;
	}

	return $post_ok;
}
