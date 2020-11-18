<?php
/**
 * /lib/compatibility/pretty-links.php
 *
 * Pretty Links compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_admin_search_ok', 'relevanssi_pretty_links_ok', 10, 2 );
add_filter( 'relevanssi_prevent_default_request', 'relevanssi_pretty_links_ok', 10, 2 );
add_filter( 'relevanssi_search_ok', 'relevanssi_pretty_links_ok', 10, 2 );

/**
 * Returns false if the query post type is set to 'pretty-link'.
 *
 * @param boolean  $ok    Whether to allow the query.
 * @param WP_Query $query The WP_Query object.
 *
 * @return boolean False if this is a Pretty Links query.
 */
function relevanssi_pretty_links_ok( $ok, $query ) {
	if ( isset( $query->query['post_type'] ) && 'pretty-link' === $query->query['post_type'] ) {
		$ok = false;
	}
	return $ok;
}
