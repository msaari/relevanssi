<?php
/**
 * /lib/compatibility/jetsmartfilters.php
 *
 * JetSmartFilters compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'pre_get_posts', 'relevanssi_jetsmartfilters', 9999 );

/**
 * Makes JetSmartFilters use posts from Relevanssi.
 *
 * @param WP_Query $wp_query The wp_query object.
 */
function relevanssi_jetsmartfilters( $wp_query ) {
	if (
		! isset( $wp_query->query['jet_smart_filters'] )
		|| empty( $wp_query->query['s'] )
	) {
		return;
	}

	$args = array(
		's'              => $wp_query->query['s'],
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'relevanssi'     => true,
	);

	$relevanssi_query = new WP_Query( $args );

	$results = ! empty( $relevanssi_query->posts )
		? $relevanssi_query->posts
		: array( 0 );

	$wp_query->set( 'post__in', $results );
	$wp_query->set( 'post_type', 'any' );
	$wp_query->set( 'post_status', 'any' );
	$wp_query->set( 'orderby', 'post__in' );
	$wp_query->set( 'order', 'DESC' );
	$wp_query->set( 's', false );
}
