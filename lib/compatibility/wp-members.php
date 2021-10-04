<?php
/**
 * /lib/compatibility/wp-members.php
 *
 * WP-Members compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_wpmembers_compatibility', 10, 2 );

/**
 * Checks whether the post type is blocked.
 *
 * Allows all logged-in users to see posts. For non-logged-in users, checks if
 * the post is blocked by the _wpmem_block custom field, or if the post type is
 * blocked in the $wpmem global.
 *
 * @param bool       $post_ok Whether the user is allowed to see the post.
 * @param int|string $post_id The post ID.
 *
 * @return bool
 */
function relevanssi_wpmembers_compatibility( bool $post_ok, $post_id ) : bool {
	global $wpmem;

	if ( is_user_logged_in() ) {
		return $post_ok;
	}

	$post_meta = get_post_meta( $post_id, '_wpmem_block', true );
	$post_type = isset( $wpmem->block[ relevanssi_get_post_type( $post_id ) ] )
		? $wpmem->block[ relevanssi_get_post_type( $post_id ) ]
		: 0;

	if ( '1' === $post_meta ) {
		$post_ok = false;
	} elseif ( '1' === $post_type && '0' !== $post_meta ) {
		$post_ok = false;
	}

	return $post_ok;
}
