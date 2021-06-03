<?php
/**
 * /lib/compatibility/fibosearch.php
 *
 * Fibo Search compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'dgwt/wcas/search_query/args', 'relevanssi_enable_relevanssi_in_fibo' );

/**
 * Adds the 'relevanssi' parameter to the Fibo Search.
 *
 * Uses the dgwt/wcas/search_query_args filter hook to modify the search query.
 *
 * @params array $args The search arguments.
 *
 * @return array
 */
function relevanssi_enable_relevanssi_in_fibo( $args ) {
	$args['relevanssi'] = true;
	return $args;
}
