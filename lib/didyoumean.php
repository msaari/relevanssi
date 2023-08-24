<?php
/**
 * /lib/didyoumean.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Generates the Did you mean suggestions.
 *
 * A wrapper function that prints out the Did you mean suggestions. If Premium
 * is available, will use relevanssi_premium_didyoumean(), otherwise the
 * relevanssi_simple_didyoumean() is used.
 *
 * @param string  $query   The query.
 * @param string  $pre     Printed out before the suggestion.
 * @param string  $post    Printed out after the suggestion.
 * @param int     $n       Maximum number of search results found for the
 * suggestions to show up. Default 5.
 * @param boolean $echoed  If true, echo out. Default true.
 *
 * @return string|null The suggestion HTML element.
 */
function relevanssi_didyoumean( $query, $pre, $post, $n = 5, $echoed = true ) {
	if ( function_exists( 'relevanssi_premium_didyoumean' ) ) {
		$result = relevanssi_premium_didyoumean( $query, $pre, $post, $n );
	} else {
		$result = relevanssi_simple_didyoumean( $query, $pre, $post, $n );
	}

	if ( $echoed ) {
		echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	return $result;
}

/**
 * Generates the Did you mean suggestions HTML code.
 *
 * Uses relevanssi_simple_generate_suggestion() to come up with a suggestion,
 * then wraps that up with HTML code.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variables.
 * @global object $wp_query             The WP_Query object.
 *
 * @param string $query The query.
 * @param string $pre   Printed out before the suggestion.
 * @param string $post  Printed out after the suggestion.
 * @param int    $n     Maximum number of search results found for the
 * suggestions to show up. Default 5.
 *
 * @return string|null The suggestion HTML code, null if nothing found.
 */
function relevanssi_simple_didyoumean( $query, $pre, $post, $n = 5 ) {
	global $wp_query;

	$total_results = $wp_query->found_posts;

	if ( $total_results > $n ) {
		return null;
	}

	$suggestion = relevanssi_simple_generate_suggestion( $query );

	$result = null;
	if ( $suggestion ) {
		$url = trailingslashit( get_bloginfo( 'url' ) );
		$url = esc_attr(
			add_query_arg(
				array( 's' => rawurlencode( $suggestion ) ),
				$url
			)
		);

		/**
		 * Filters the 'Did you mean' suggestion URL.
		 *
		 * @param string $url        The URL for the suggested search query.
		 * @param string $query      The search query.
		 * @param string $suggestion The suggestion.
		 */
		$url = apply_filters(
			'relevanssi_didyoumean_url',
			$url,
			$query,
			$suggestion
		);

		// Escape the suggestion to avoid XSS attacks.
		$suggestion = htmlspecialchars( $suggestion );

		/**
		 * Filters the complete 'Did you mean' suggestion.
		 *
		 * @param string The suggestion HTML code.
		 */
		$result = apply_filters(
			'relevanssi_didyoumean_suggestion',
			"$pre<a href='$url'>$suggestion</a>$post"
		);
	}

	return $result;
}

/**
 * Generates the 'Did you mean' suggestions. Can be used to correct any queries.
 *
 * Uses the Relevanssi search logs as source material for corrections. If there
 * are no logged search queries, can't do anything.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variables, used
 * for table names.
 *
 * @param string $query The query to correct.
 *
 * @return string Corrected query, empty if nothing found.
 */
function relevanssi_simple_generate_suggestion( $query ) {
	global $wpdb, $relevanssi_variables;

	/**
	 * The minimum limit of occurrances to include a word.
	 *
	 * To save resources, only words with more than this many occurrances are
	 * fed for the spelling corrector. If there are problems with the spelling
	 * corrector, increasing this value may fix those problems.
	 *
	 * @param int $number The number of occurrances must be more than this
	 * value, default 2.
	 */
	$count = apply_filters( 'relevanssi_get_words_having', 2 );
	if ( ! is_numeric( $count ) ) {
		$count = 2;
	}
	$q = 'SELECT query, count(query) as c, AVG(hits) as a FROM '
		. $relevanssi_variables['log_table'] . ' WHERE hits > ' . $count
		. ' GROUP BY query ORDER BY count(query) DESC';
	/**
	 * Filters the MySQL query used to fetch potential suggestions from the log.
	 *
	 * @param string $q MySQL query for fetching the suggestions.
	 */
	$q = apply_filters( 'relevanssi_didyoumean_query', $q );

	$data = get_transient( 'relevanssi_didyoumean_query' );
	if ( empty( $data ) ) {
		$data = $wpdb->get_results( $q ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		set_transient( 'relevanssi_didyoumean_query', $data, MONTH_IN_SECONDS );
	}

	$query            = htmlspecialchars_decode( $query, ENT_QUOTES );
	$tokens           = relevanssi_tokenize( $query, true, -1, 'search_query' );
	$suggestions_made = false;
	$suggestion       = '';

	foreach ( $tokens as $token => $count ) {
		/**
		 * Filters the tokens for Did you mean suggestions.
		 *
		 * You can use this filter hook to modify the tokens before Relevanssi
		 * tries to come up with Did you mean suggestions for them. If you
		 * return an empty string, the token will be skipped and no suggestion
		 * will be made for the token.
		 *
		 * @param string $token An individual word from the search query.
		 *
		 * @return string The token.
		 */
		$token = apply_filters( 'relevanssi_didyoumean_token', trim( $token ) );
		if ( ! $token ) {
			continue;
		}
		$closest  = '';
		$distance = -1;
		foreach ( $data as $row ) {
			if ( $row->c < 2 ) {
				break;
			}

			if ( $token === $row->query ) {
				$closest = '';
				break;
			} elseif ( strlen( $token ) < 255 && strlen( $row->query ) < 255 ) {
				// The levenshtein() function has a max length of 255
				// characters. The function uses strlen(), so we must use
				// too, instead of relevanssi_strlen().
				$lev = levenshtein( $token, $row->query );
				if ( $lev < 3 && ( $lev < $distance || $distance < 0 ) ) {
					if ( $row->a > 0 ) {
						$distance = $lev;
						$closest  = $row->query;
						if ( $lev < 2 ) {
							break; // get the first with distance of 1 and go.
						}
					}
				}
			}
		}
		if ( ! empty( $closest ) ) {
			$query = str_ireplace( $token, $closest, $query, $replacement_count );
			if ( $replacement_count > 0 ) {
				$suggestions_made = true;
			}
		}
	}

	if ( $suggestions_made ) {
		$suggestion = $query;
	}

	return $suggestion;
}
