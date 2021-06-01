<?php
/**
 * /lib/compatibility/elementor.php
 *
 * Elementor page builder compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_search_ok', 'relevanssi_block_elementor_library', 10, 2 );

/**
 * Blocks Relevanssi from interfering with the Elementor Library searches.
 *
 * @param bool     $ok    Should Relevanssi be allowed to process the query.
 * @param WP_Query $query The WP_Query object.
 *
 * @return bool Returns false, if this is an Elementor library search.
 */
function relevanssi_block_elementor_library( bool $ok, WP_Query $query ) : bool {
	if ( 'elementor_library' === $query->query_vars['post_type'] ) {
		$ok = false;
	}
	return $ok;
}
