<?php
/**
 * /lib/compatibility/bricks.php
 *
 * Bricks theme compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'bricks/posts/query_vars', 'relevanssi_bricks_enable', 10 );

/**
 * Undocumented function
 *
 * @param array $query_vars The query variables.
 *
 * @return array The query variables with the Relevanssi toggle enabled.
 */
function relevanssi_bricks_enable( $query_vars ) {
	if ( isset( $query_vars['s'] ) ) {
		$query_vars['relevanssi'] = true;
	}
	return $query_vars;
}
