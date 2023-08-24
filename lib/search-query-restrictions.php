<?php
/**
 * /lib/search-query-restrictions.php
 *
 * Responsible for converting query parameters to MySQL query restrictions.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Processes the arguments to create the query restrictions.
 *
 * All individual parts are tested.
 *
 * @param array $args The query arguments.
 *
 * @return array An array containing `query_restriction` and `query_join`.
 */
function relevanssi_process_query_args( $args ) {
	$query_restrictions = '';
	$query_join         = '';
	$query              = '';
	$query_no_synonyms  = '';

	$phrase_query_restrictions = array(
		'and' => '',
		'or'  => array(),
	);

	if ( function_exists( 'wp_encode_emoji' ) ) {
		$query             = wp_encode_emoji( $args['q'] );
		$query_no_synonyms = wp_encode_emoji( $args['q_no_synonyms'] );
	}

	if ( $args['sentence'] ) {
		$query = relevanssi_remove_quotes( $query );
		$query = '"' . $query . '"';
	}

	if ( is_array( $args['tax_query'] ) ) {
		$query_restrictions .= relevanssi_process_tax_query( $args['tax_query_relation'], $args['tax_query'] );
	}

	if ( is_array( $args['post_query'] ) ) {
		$query_restrictions .= relevanssi_process_post_query( $args['post_query'] );
	}

	if ( is_array( $args['parent_query'] ) ) {
		$query_restrictions .= relevanssi_process_parent_query( $args['parent_query'] );
	}

	if ( is_array( $args['meta_query'] ) ) {
		$processed_meta      = relevanssi_process_meta_query( $args['meta_query'] );
		$query_restrictions .= $processed_meta['where'];
		$query_join         .= $processed_meta['join'];
	}

	if ( $args['date_query'] instanceof WP_Date_Query ) {
		$query_restrictions .= relevanssi_process_date_query( $args['date_query'] );
	}

	if ( $args['expost'] ) {
		$query_restrictions .= relevanssi_process_expost( $args['expost'] );
	}

	if ( $args['author'] ) {
		$query_restrictions .= relevanssi_process_author( $args['author'] );
	}

	if ( $args['by_date'] ) {
		$query_restrictions .= relevanssi_process_by_date( $args['by_date'] );
	}

	$phrases = relevanssi_recognize_phrases( $query, $args['operator'] );
	if ( $phrases ) {
		$phrase_query_restrictions = $phrases;
	}

	$query_restrictions .= relevanssi_process_post_type(
		$args['post_type'],
		$args['admin_search'],
		$args['include_attachments']
	);

	if ( $args['post_status'] ) {
		$query_restrictions .= relevanssi_process_post_status( $args['post_status'] );
	}

	return array(
		'query_restrictions' => $query_restrictions,
		'query_join'         => $query_join,
		'query_query'        => $query,
		'query_no_synonyms'  => $query_no_synonyms,
		'phrase_queries'     => $phrase_query_restrictions,
	);
}

/**
 * Processes the 'in' and 'not in' parameters to MySQL query restrictions.
 *
 * Checks that the parameters are integers and formulates a MySQL query
 * restriction from them. If the same posts are both included and excluded,
 * exclusion will take precedence.
 *
 * Tested.
 *
 * @param array $post_query An array where included posts are in
 * $post_query['in'] and excluded posts are in $post_query['not in'].
 *
 * @return string MySQL query restrictions matching the array.
 */
function relevanssi_process_post_query( $post_query ) {
	$query_restrictions   = '';
	$valid_exclude_values = array();
	if ( ! empty( $post_query['not in'] ) ) {
		foreach ( $post_query['not in'] as $post_not_in_id ) {
			if ( is_numeric( $post_not_in_id ) ) {
				$valid_exclude_values[] = $post_not_in_id;
			}
		}
		$posts = implode( ',', $valid_exclude_values );
		if ( ! empty( $posts ) ) {
			$query_restrictions .= " AND relevanssi.doc NOT IN ($posts)";
			// Clean: $posts is checked to be integers.
		}
	}
	if ( ! empty( $post_query['in'] ) ) {
		$valid_values = array();
		foreach ( $post_query['in'] as $post_in_id ) {
			if ( is_numeric( $post_in_id ) ) {
				$valid_values[] = $post_in_id;
			}
		}
		// If same values appear in both arrays, exclusion will override inclusion.
		$valid_values = array_diff( $valid_values, $valid_exclude_values );
		$posts        = implode( ',', $valid_values );
		if ( ! empty( $posts ) ) {
			$query_restrictions .= " AND relevanssi.doc IN ($posts)";
			// Clean: $posts is checked to be integers.
		}
	}

	/**
	 * Filters the MySQL query for restricting the search by post parameters.
	 *
	 * @param string $query_restrictions The MySQL query.
	 * @param array  $post_query         The post query parameters.
	 *
	 * @return string The MySQL query.
	 */
	return apply_filters( 'relevanssi_post_query_filter', $query_restrictions, $post_query );
}

/**
 * Processes the 'parent in' and 'parent not in' parameters to MySQL query
 * restrictions.
 *
 * Checks that the parameters are integers and formulates a MySQL query restriction
 * from them. If the same posts are both included and excluded, exclusion will take
 * precedence.
 *
 * Tested.
 *
 * @param array $parent_query An array where included posts are in
 * $post_query['parent in'] and excluded posts are in $post_query['parent not in'].
 *
 * @return string MySQL query restrictions matching the array.
 */
function relevanssi_process_parent_query( $parent_query ) {
	global $wpdb;

	$query_restrictions   = '';
	$valid_exclude_values = array();
	if ( isset( $parent_query['parent not in'] ) ) {
		foreach ( $parent_query['parent not in'] as $post_not_in_id ) {
			if ( is_int( $post_not_in_id ) ) {
				$valid_exclude_values[] = $post_not_in_id;
			}
		}
		$posts = implode( ',', $valid_exclude_values );
		if ( isset( $posts ) ) {
			$query_restrictions .= " AND relevanssi.doc NOT IN (SELECT ID FROM $wpdb->posts WHERE post_parent IN ($posts))";
			// Clean: $posts is checked to be integers.
		}
	}
	if ( isset( $parent_query['parent in'] ) ) {
		$valid_values = array();
		foreach ( $parent_query['parent in'] as $post_in_id ) {
			if ( is_int( $post_in_id ) ) {
				$valid_values[] = $post_in_id;
			}
		}
		$valid_values = array_diff( $valid_values, $valid_exclude_values );
		$posts        = implode( ',', $valid_values );
		if ( strlen( $posts ) > 0 ) {
			$query_restrictions .= " AND relevanssi.doc IN (SELECT ID FROM $wpdb->posts WHERE post_parent IN ($posts))";
			// Clean: $posts is checked to be integers.
		}
	}

	/**
	 * Filters the MySQL query for restricting the search by the post parent.
	 *
	 * @param string $query_restrictions The MySQL query.
	 * @param array  $parent_query       The parent query parameters.
	 *
	 * @return string The MySQL query.
	 */
	return apply_filters( 'relevanssi_parent_query_filter', $query_restrictions, $parent_query );
}

/**
 * Processes the meta query parameter to MySQL query restrictions.
 *
 * Uses the WP_Meta_Query object to parse the query variables to create the MySQL
 * JOIN and WHERE clauses.
 *
 * Tested.
 *
 * @see WP_Meta_Query
 *
 * @param array $meta_query A meta query array.
 *
 * @return array Index 'where' is the WHERE, index 'join' is the JOIN.
 */
function relevanssi_process_meta_query( $meta_query ) {
	$mq_vars = array( 'meta_query' => $meta_query );

	$mq = new WP_Meta_Query();
	$mq->parse_query_vars( $mq_vars );
	$meta_sql   = $mq->get_sql( 'post', 'relevanssi', 'doc' );
	$meta_join  = '';
	$meta_where = '';
	if ( $meta_sql ) {
		$meta_join  = $meta_sql['join'];
		$meta_where = $meta_sql['where'];
	}

	return array(
		'where' => $meta_where,
		'join'  => $meta_join,
	);
}

/**
 * Processes the date query parameter to MySQL query restrictions.
 *
 * Uses the WP_Date_Query object to parse the query variables to create the
 * MySQL WHERE clause. By default using a date query will block taxonomy terms
 * and user profiles from the search (because they don't have a post ID and
 * also don't have date information associated with them). If you want to keep
 * the user profiles and taxonomy terms in the search, set the filter hook
 * `relevanssi_date_query_non_posts` to return true.
 *
 * @see WP_Date_Query
 *
 * @global object $wpdb The WP database interface.
 *
 * @param WP_Date_Query $date_query A date query object.
 *
 * @return string The MySQL query restriction.
 */
function relevanssi_process_date_query( $date_query ) {
	global $wpdb;

	$query_restrictions = '';
	if ( method_exists( $date_query, 'get_sql' ) ) {
		$sql   = $date_query->get_sql(); // Format: AND (the query).
		$query = " relevanssi.doc IN (
			SELECT DISTINCT(ID) FROM $wpdb->posts WHERE 1 $sql )
		";
		/**
		 * If true, include non-posts (users, terms) in searches with a date
		 * query filter.
		 *
		 * @param boolean Allow non-posts? Default false.
		 */
		if ( apply_filters( 'relevanssi_date_query_non_posts', false ) ) {
			$query_restrictions = " AND ( $query OR relevanssi.doc = -1 ) ";
			// Clean: $sql generated by $date_query->get_sql() query.
		} else {
			$query_restrictions = " AND $query ";
			// Clean: $sql generated by $date_query->get_sql() query.
		}
	}

	/**
	 * Filters the MySQL query for restricting the search by the date_query.
	 *
	 * @param string        $query_restrictions The MySQL query.
	 * @param WP_Date_Query $date_query         The date_query object.
	 *
	 * @return string The MySQL query.
	 */
	return apply_filters( 'relevanssi_date_query_filter', $query_restrictions, $date_query );
}

/**
 * Processes the post exclusion parameter to MySQL query restrictions.
 *
 * Takes a comma-separated list of post ID numbers and creates a MySQL query
 * restriction from them.
 *
 * @param string $expost The post IDs to exclude, comma-separated.
 *
 * @return string The MySQL query restriction.
 */
function relevanssi_process_expost( $expost ) {
	$posts_to_exclude            = '';
	$excluded_post_ids_unchecked = explode( ',', trim( $expost, ' ,' ) );
	$excluded_post_ids           = array();
	foreach ( $excluded_post_ids_unchecked as $excluded_post_id ) {
		$excluded_post_ids[] = intval( trim( $excluded_post_id, ' -' ) );
	}
	$excluded_post_ids_string = implode( ',', $excluded_post_ids );
	$posts_to_exclude        .= " AND relevanssi.doc NOT IN ($excluded_post_ids_string)";
	// Clean: escaped.
	return $posts_to_exclude;
}

/**
 * Processes the author parameter to MySQL query restrictions.
 *
 * Takes an array of author ID numbers and creates the MySQL query restriction code
 * from them. Negative values are counted as exclusion and positive values as
 * inclusion.
 *
 * Tested.
 *
 * @global object $wpdb The WP database interface.
 *
 * @param array $author An array of authors. Positive values are inclusion,
 * negative values are exclusion.
 *
 * @return string The MySQL query restriction.
 */
function relevanssi_process_author( $author ) {
	global $wpdb;

	$query_restrictions = '';

	$author_in     = array();
	$author_not_in = array();
	foreach ( $author as $id ) {
		if ( ! is_numeric( $id ) ) {
			continue;
		}
		if ( $id > 0 ) {
			$author_in[] = $id;
		} else {
			$author_not_in[] = abs( $id );
		}
	}
	if ( count( $author_in ) > 0 ) {
		$authors             = implode( ',', $author_in );
		$query_restrictions .= " AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
			WHERE posts.post_author IN ($authors))";
		// Clean: $authors is always just numbers.
	}
	if ( count( $author_not_in ) > 0 ) {
		$authors             = implode( ',', $author_not_in );
		$query_restrictions .= " AND relevanssi.doc NOT IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
			WHERE posts.post_author IN ($authors))";
		// Clean: $authors is always just numbers.
	}

	/**
	 * Filters the MySQL query for restricting the search by the post author.
	 *
	 * @param string $query_restrictions The MySQL query.
	 * @param array  $author             An array of author IDs.
	 *
	 * @return string The MySQL query.
	 */
	return apply_filters( 'relevanssi_author_query_filter', $query_restrictions, $author );
}

/**
 * Processes the by_date parameter to MySQL query restrictions.
 *
 * The by_date parameter is a simple data parameter in the format '24h', that is a
 * number followed by an unit (h, d, m, y, or w).
 *
 * Tested.
 *
 * @global object $wpdb The WP database interface.
 *
 * @param string $by_date The date parameter.
 *
 * @return string The MySQL query restriction.
 */
function relevanssi_process_by_date( $by_date ) {
	global $wpdb;
	$query_restrictions = '';

	$u = substr( $by_date, -1, 1 );
	switch ( $u ) {
		case 'h':
			$unit = 'HOUR';
			break;
		case 'd':
			$unit = 'DAY';
			break;
		case 'm':
			$unit = 'MONTH';
			break;
		case 'y':
			$unit = 'YEAR';
			break;
		case 'w':
			$unit = 'WEEK';
			break;
		default:
			$unit = 'DAY';
	}

	$n = preg_replace( '/[hdmyw]/', '', $by_date );

	if ( is_numeric( $n ) ) {
		$query_restrictions .= " AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
			WHERE posts.post_date > DATE_SUB(NOW(), INTERVAL $n $unit))";
		// Clean: $n is always numeric, $unit is Relevanssi-generated.
	}

	/**
	 * Filters the MySQL query for restricting the search by the by_date.
	 *
	 * @param string $query_restrictions The MySQL query.
	 * @param string $by_date            The by_date parameter.
	 *
	 * @return string The MySQL query.
	 */
	return apply_filters( 'relevanssi_by_date_query_filter', $query_restrictions, $by_date );
}

/**
 * Extracts the post types from a comma-separated list or an array.
 *
 * Handles the non-post post types as well (user, taxonomies, etc.) and escapes the
 * post types for SQL injections.
 *
 * Tested.
 *
 * @param string|array $post_type           An array or a comma-separated list of
 * post types.
 * @param boolean      $admin_search        True if this is an admin search.
 * @param boolean      $include_attachments True if attachments are allowed in the
 * search.
 *
 * @global object $wpdb The WP database interface.
 *
 * @return array Array containing the 'post_type' and 'non_post_post_type' (which
 * defaults to null).
 */
function relevanssi_process_post_type( $post_type, $admin_search, $include_attachments ) {
	global $wpdb;

	// If $post_type is not set, see if there are post types to exclude from the search.
	// If $post_type is set, there's no need to exclude, as we only include.
	$negative_post_type = null;
	if ( ! $post_type && ! $admin_search ) {
		$negative_post_type = relevanssi_get_negative_post_type( $include_attachments );
	}

	$non_post_post_type        = null;
	$non_post_post_types_array = array();
	if ( function_exists( 'relevanssi_get_non_post_post_types' ) ) {
		// Relevanssi Premium includes post types which are not actually posts.
		$non_post_post_types_array = relevanssi_get_non_post_post_types();
	}

	if ( $post_type ) {
		if ( ! is_array( $post_type ) ) {
			$post_types = explode( ',', $post_type );
		} else {
			$post_types = $post_type;
		}

		// This array will contain all regular post types involved in the search parameters.
		$post_post_types = array_diff( $post_types, $non_post_post_types_array );

		// This array has the non-post post types involved.
		$non_post_post_types = array_intersect( $post_types, $non_post_post_types_array );

		// Escape both for SQL queries, just in case.
		$non_post_post_types = esc_sql( $non_post_post_types );
		$post_types          = esc_sql( $post_post_types );

		// Implode to a parameter string, or set to null if empty.
		$non_post_post_type = null;
		if ( count( $non_post_post_types ) > 0 ) {
			$non_post_post_type = "'" . implode( "', '", $non_post_post_types ) . "'";
		}
		$post_type = null;
		if ( count( $post_types ) > 0 ) {
			$post_type = "'" . implode( "', '", $post_types ) . "'";
		}
	}

	$query_restrictions = '';

	if ( $post_type ) {
		$restriction = " AND (
			relevanssi.doc IN (
				SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
				WHERE posts.post_type IN ($post_type)
			) *np*
		)"; // Clean: $post_type is escaped.

		// There are post types involved that are taxonomies or users, so can't
		// match to wp_posts. Add a relevanssi.type restriction.
		if ( $non_post_post_type ) {
			$restriction = str_replace( '*np*', "OR (relevanssi.type IN ($non_post_post_type))", $restriction );
			// Clean: $non_post_post_types is escaped.
		} else {
			// No non-post post types, so remove the placeholder.
			$restriction = str_replace( '*np*', '', $restriction );
		}
		$query_restrictions .= $restriction;
	} elseif ( $non_post_post_type ) { // No regular post types.
		// But there is a non-post post type restriction.
		$query_restrictions .= " AND (relevanssi.type IN ($non_post_post_type))";
		// Clean: $non_post_post_types is escaped.
	}

	if ( $negative_post_type ) {
		$query_restrictions .= " AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
			WHERE posts.post_type NOT IN ($negative_post_type))) OR (doc = -1))";
		// Clean: $negative_post_type is escaped.
	}

	/**
	 * Filters the MySQL query for restricting the search by the post type.
	 *
	 * @param string       $query_restrictions  The MySQL query.
	 * @param string|array $post_type           The post type(s).
	 * @param boolean      $include_attachments True if attachments are allowed.
	 * @param boolean      $admin_search        True if this is an admin search.
	 *
	 * @return string The MySQL query.
	 */
	return apply_filters( 'relevanssi_post_type_query_filter', $query_restrictions, $post_type, $include_attachments, $admin_search );
}

/**
 * Processes the post status parameter.
 *
 * Takes the post status parameter and creates a MySQL query restriction from it.
 * Checks if this is in admin context: if the query isn't, there's a catch added to
 * capture user profiles and taxonomy terms.
 *
 * @param string $post_status A post status string.
 *
 * @global WP_Query $wp_query              The WP Query object.
 * @global object   $wpdb                  The WP database interface.
 * @global boolean  $relevanssi_admin_test If true, an admin search. for tests.
 *
 * @return string The MySQL query restriction.
 */
function relevanssi_process_post_status( $post_status ) {
	global $wp_query, $wpdb, $relevanssi_admin_test;
	$query_restrictions = '';

	if ( ! is_array( $post_status ) ) {
		$post_statuses = esc_sql( explode( ',', $post_status ) );
	} else {
		$post_statuses = esc_sql( $post_status );
	}

	$escaped_post_status = '';
	if ( count( $post_statuses ) > 0 ) {
		$escaped_post_status = "'" . implode( "', '", $post_statuses ) . "'";
	}

	if ( $escaped_post_status ) {
		$block_non_post_results = false;
		if ( $wp_query->is_admin || $relevanssi_admin_test ) {
			$block_non_post_results = true;
		}
		if ( $wp_query->is_admin && isset( $wp_query->query_vars['action'] ) && 'relevanssi_live_search' === $wp_query->query_vars['action'] ) {
			$block_non_post_results = false;
		}
		if ( $block_non_post_results ) {
			$query_restrictions .= " AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
				WHERE posts.post_status IN ($escaped_post_status))))";
		} else {
			// The -1 is there to get user profiles and category pages.
			$query_restrictions .= " AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
				WHERE posts.post_status IN ($escaped_post_status))) OR (doc = -1))";
		}
	}

	/**
	 * Filters the MySQL query for restricting the search by the post status.
	 *
	 * @param string $query_restrictions The MySQL query.
	 * @param string $post_status        The post status(es).
	 *
	 * @return string The MySQL query.
	 */
	return apply_filters( 'relevanssi_post_status_query_filter', $query_restrictions, $post_status );
}

/**
 * Adds phrase restrictions to the query.
 *
 * For OR searches, adds the phrases only for matching terms that are in the
 * phrases, achieving the OR search effect for phrases: posts without the phrase
 * but with another search term are not excluded from the search. In AND
 * searches, all search terms must match to documents containing the phrase.
 *
 * @param string $query_restrictions The MySQL query restriction for the search.
 * @param array  $phrase_queries     The phrase queries - 'and' contains the
 * main query, while 'or' has the phrase-specific queries.
 * @param string $term               The current search term.
 * @param string $operator           AND or OR.
 *
 * @return string The query restrictions with the phrase restrictions added.
 */
function relevanssi_add_phrase_restrictions( $query_restrictions, $phrase_queries, $term, $operator ) {
	if ( 'OR' === $operator ) {
		$or_queries = array_filter(
			$phrase_queries['or'],
			function ( $terms ) use ( $term ) {
				return relevanssi_stripos( $terms, $term ) !== false;
			},
			ARRAY_FILTER_USE_KEY
		);
		if ( $or_queries ) {
			$query_restrictions .= ' AND ( '
				. implode( ' OR ', array_values( $or_queries ) )
				. ' ) ';
		}
	} else {
		$query_restrictions .= $phrase_queries['and'];
	}
	return $query_restrictions;
}
