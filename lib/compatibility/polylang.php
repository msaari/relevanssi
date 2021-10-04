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
add_filter( 'relevanssi_where', 'relevanssi_polylang_where_include_terms' );
add_filter( 'relevanssi_hits_filter', 'relevanssi_polylang_term_filter' );

/**
 * Removes the Polylang language filters.
 *
 * If the Polylang allow all option ('relevanssi_polylang_all_languages') is
 * enabled this removes the Polylang language filter. By default Polylang
 * filters the languages using a taxonomy query.
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
			if ( isset( $tax_query['taxonomy'] ) && 'language' !== $tax_query['taxonomy'] ) {
				// Not a language tax query.
				$ok_queries[] = $tax_query;
			}
		}
		$query->tax_query->queries = $ok_queries;

		if ( isset( $query->query_vars['tax_query'] ) ) {
			// Tax queries can be here as well, so let's sweep this one too.
			$ok_queries = array();
			foreach ( $query->query_vars['tax_query'] as $tax_query ) {
				if ( isset( $tax_query['taxonomy'] ) ) {
					if ( 'language' !== $tax_query['taxonomy'] ) {
						$ok_queries[] = $tax_query;
					}
				} else {
					// Relation parameter most likely.
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

/**
 * Allows taxonomy terms in language-restricted searches.
 *
 * This is a bit of a hack, where the language taxonomy WHERE clause is modified
 * on the go to allow all posts with the post ID -1 (which means taxonomy terms
 * and users). This may break suddenly in updates, but I haven't come up with a
 * better way so far.
 *
 * @param string $where The WHERE clause to modify.
 *
 * @return string The WHERE clause with additional filtering included.
 *
 * @since 2.1.6
 */
function relevanssi_polylang_where_include_terms( $where ) {
	global $wpdb;

	$current_language = substr( get_locale(), 0, 2 );
	if ( function_exists( 'pll_current_language' ) ) {
		$current_language = pll_current_language();
	}
	$languages   = get_terms( array( 'taxonomy' => 'language' ) );
	$language_id = 0;
	foreach ( $languages as $language ) {
		if (
			! is_wp_error( $language ) &&
			$language instanceof WP_Term &&
			$language->slug === $current_language
			) {
			$language_id = intval( $language->term_id );
			break;
		}
	}
	// Language ID should now have current language ID.
	if ( 0 !== $language_id ) {
		// Do a simple search-and-replace to modify the query.
		$where = preg_replace( '/\s+/', ' ', $where );
		$where = preg_replace( '/\(\s/', '(', $where );
		$where = str_replace(
			"AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id) FROM {$wpdb->prefix}term_relationships AS tr WHERE tr.term_taxonomy_id IN ($language_id))",
			"AND (relevanssi.doc IN ( SELECT DISTINCT(tr.object_id) FROM {$wpdb->prefix}term_relationships AS tr WHERE tr.term_taxonomy_id IN ($language_id)) OR (relevanssi.doc = -1))",
			$where
		);
	}
	return $where;
}

/**
 * Filters out taxonomy terms in the wrong language.
 *
 * If all languages are not allowed, this filter goes through the results and
 * removes the taxonomy terms in the wrong language. This can't be done in the
 * original query because the term language information is slightly hard to
 * find.
 *
 * @param array $hits The found posts are in $hits[0].
 *
 * @return array The $hits array with the unwanted posts removed.
 *
 * @since 2.1.6
 */
function relevanssi_polylang_term_filter( $hits ) {
	$polylang_allow_all = get_option( 'relevanssi_polylang_all_languages' );
	if ( 'on' !== $polylang_allow_all ) {
		$current_language = substr( get_locale(), 0, 2 );
		if ( function_exists( 'pll_current_language' ) ) {
			$current_language = pll_current_language();
		}
		$accepted_hits = array();
		foreach ( $hits[0] as $hit ) {
			$original_hit = $hit;
			if ( is_numeric( $hit ) ) {
				// In case "fields" is set to "ids", fetch the post object we need.
				$original_hit = $hit;
				$hit          = get_post( $hit );
			}
			if ( ! isset( $hit->post_content ) && isset( $hit->ID ) ) {
				// The "fields" is set to "id=>parent".
				$original_hit = $hit;
				$hit          = get_post( $hit->ID );
			}

			if ( isset( $hit->ID ) && -1 === $hit->ID && isset( $hit->term_id ) ) {
				$term_id      = intval( $hit->term_id );
				$translations = pll_get_term_translations( $term_id );
				if (
					isset( $translations[ $current_language ] ) &&
					$translations[ $current_language ] === $term_id
					) {
					$accepted_hits[] = $original_hit;
				}
			} else {
				$accepted_hits[] = $original_hit;
			}
		}
		$hits[0] = $accepted_hits;
	}
	return $hits;
}

/**
 * Returns the term_taxonomy_id matching the Polylang language based on locale.
 *
 * @param string $locale The locale string for the language.
 *
 * @return int The term_taxonomy_id for the language; 0 if nothing is found.
 */
function relevanssi_get_language_term_taxonomy_id( $locale ) {
	global $wpdb, $relevanssi_language_term_ids;

	if ( isset( $relevanssi_language_term_ids[ $locale ] ) ) {
		return $relevanssi_language_term_ids[ $locale ];
	}

	$languages = $wpdb->get_results(
		"SELECT term_taxonomy_id, description FROM $wpdb->term_taxonomy " .
		"WHERE taxonomy = 'language'"
	);
	$term_id   = 0;
	foreach ( $languages as $row ) {
		$description = unserialize( $row->description ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		if ( $description['locale'] === $locale ) {
			$term_id = $row->term_taxonomy_id;
			break;
		}
	}

	$relevanssi_language_term_ids[ $locale ] = $term_id;

	return $term_id;
}
