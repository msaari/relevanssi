<?php
/**
 * /lib/compatibility/wp-search-suggest.php
 *
 * WP Search Suggest compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'wpss_search_results', 'relevanssi_wpss_support', 10, 2 );
/**
 * Adds Relevanssi results to WP Search Suggest dropdown.
 *
 * @param array  $title_list  List of post titles.
 * @param object $query The WP_Query object.
 *
 * @return array List of post titles.
 */
function relevanssi_wpss_support( $title_list, $query ) {
	$query = relevanssi_do_query( $query );
	return wp_list_pluck( $query->posts, 'post_title' );
}
