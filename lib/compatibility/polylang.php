<?php
/**
 * /lib/compatibility/polylang.php
 *
 * Polylang compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_modify_wp_query', 'relevanssi_polylang_filter' );

/**
 * Removes the Polylang language filters.
 *
 * If the Polylang allow all option is enabled ('relevanssi_polylang_all_languages'),
 * removes the Polylang language filter. By default Polylang filters the languages
 * using a taxonomy query.
 *
 * @param object $query WP_Query object we need to clean up.
 */
function relevanssi_polylang_filter( $query ) {
	$polylang_allow_all = get_option( 'relevanssi_polylang_all_languages' );
	if ( 'on' === $polylang_allow_all ) {
		$ok_queries = array();

		if ( ! isset( $query->tax_query ) ) {
			// No tax query set, backing off.
			return;
		}

		if ( ! isset( $query->tax_query->queries ) || ! is_array( $query->tax_query->queries ) ) {
			// No tax query set, backing off.
			return;
		}

		foreach ( $query->tax_query->queries as $tax_query ) {
			if ( 'language' !== $tax_query['taxonomy'] ) {
				// Not a language tax query.
				$ok_queries[] = $tax_query;
			}
		}
		$query->tax_query->queries = $ok_queries;

		if ( isset( $query->query_vars['tax_query'] ) ) {
			// Tax queries can be here as well, so let's sweep this one too.
			$ok_queries = array();
			foreach ( $query->query_vars['tax_query'] as $tax_query ) {
				if ( 'language' !== $tax_query['taxonomy'] ) {
					$ok_queries[] = $tax_query;
				}
			}
			$query->query_vars['tax_query'] = $ok_queries;
		}

		if ( isset( $query->query_vars['taxonomy'] ) && 'language' === $query->query_vars['taxonomy'] ) {
			// Another way to set the taxonomy.
			unset( $query->query_vars['taxonomy'] );
			unset( $query->query_vars['term'] );
		}
	}

	return $query;
}
