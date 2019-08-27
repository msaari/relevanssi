<?php
/**
 * /lib/compatibility/useraccessmanager.php
 *
 * User Access Manager compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_ok', 'relevanssi_useraccessmanager_compatibility', 10, 2 );

/**
 * Checks whether the user is allowed to see the post.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_useraccessmanager_compatibility( $post_ok, $post_id ) {
	// phpcs:disable WordPress.NamingConventions.ValidVariableName
	global $userAccessManager;
	$type    = relevanssi_get_post_type( $post_id );
	$post_ok = $userAccessManager->getAccessHandler()->checkObjectAccess( $type, $post_id );
	// phpcs:enable WordPress.NamingConventions.ValidVariableName

	return $post_ok;
}
