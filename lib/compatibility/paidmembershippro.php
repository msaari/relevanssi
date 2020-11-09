<?php
/**
 * /lib/compatibility/paidmembershippro.php
 *
 * Paid Membership Pro compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_paidmembershippro_compatibility', 10, 2 );

/**
 * Checks whether the user is allowed to see the post.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_paidmembershippro_compatibility( $post_ok, $post_id ) {
	$pmpro_active = get_option( 'pmpro_filterqueries', 0 );

	if ( $pmpro_active ) {
		$status = relevanssi_get_post_status( $post_id );

		if ( 'publish' === $status ) {
			// Only apply to published posts, don't apply to drafts.
			$current_user = wp_get_current_user();
			$post_ok      = pmpro_has_membership_access( $post_id, $current_user->ID );
		}
	}

	return $post_ok;
}
