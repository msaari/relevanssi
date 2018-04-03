<?php
/**
 * /lib/search.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Triggers the Relevanssi search query.
 *
 * Attaches to 'the_posts' filter hook, checks to see if there's a place for a
 * search and runs relevanssi_do_query() if there is. Do not call directly; for
 * direct Relevanssi access, use relevanssi_do_query().
 *
 * @global boolean $relevanssi_active True, if Relevanssi is already running.
 *
 * @param array    $posts An array of post objects.
 * @param WP_Query $query The WP_Query object, default false.
 */
function relevanssi_query( $posts, $query = false ) {
	$admin_search_option = get_option( 'relevanssi_admin_search' );
	$admin_search        = false;
	if ( 'on' === $admin_search_option ) {
		$admin_search = true;
	}

	global $relevanssi_active;

	if ( ! $query ) {
		return $posts;
	}

	$search_ok = true; // We will search!
	if ( ! $query->is_search() ) {
		$search_ok = false; // No, we can't, not a search.
	}
	if ( ! $query->is_main_query() ) {
		$search_ok = false; // No, we can't, not the main query.
	}

	// Uses $wp_query->is_admin instead of is_admin() to help with Ajax queries that
	// use 'admin_ajax' hook (which sets is_admin() to true whether it's an admin search
	// or not.
	if ( $query->is_search() && $query->is_admin ) {
		$search_ok = false; // But if this is an admin search, reconsider.
		if ( $admin_search ) {
			$search_ok = true; // Yes, we can search!
		}
	}

	if ( $query->is_admin && empty( $query->query_vars['s'] ) ) {
		$search_ok = false; // No search term.
	}

	// Disable Relevanssi in the media library search.
	if ( $search_ok ) {
		if ( 'attachment' === $query->query_vars['post_type'] && 'inherit,private' === $query->query_vars['post_status'] ) {
			$search_ok = false;
		}
	}

	/**
	 * Filters whether Relevanssi search can be run or not.
	 *
	 * This can be used to for example activate Relevanssi in cases where there is
	 * no search term available.
	 *
	 * @param boolean  True, if Relevanssi can be allowed to run.
	 * @param WP_Query The current query object.
	 */
	$search_ok = apply_filters( 'relevanssi_search_ok', $search_ok, $query );

	if ( $relevanssi_active ) {
		$search_ok = false; // Relevanssi is already in action.
	}

	if ( $search_ok ) {
		/**
		 * Filters the WP_Query object before Relevanssi.
		 *
		 * Can be used to modify the WP_Query object before Relevanssi sees it.
		 * Fairly close to pre_get_posts, but is often the better idea, because this
		 * only affects Relevanssi searches, and nothing else. Do note that this is
		 * a filter and needs to return the modified query object.
		 *
		 * @param WP_Query The WP_Query object.
		 */
		$query = apply_filters( 'relevanssi_modify_wp_query', $query );
		$posts = relevanssi_do_query( $query );
	}

	return $posts;
}

/**
 * Does the actual searching.
 *
 * This function gets the search arguments, finds posts and returns all the results
 * it finds. If you wish to access Relevanssi directly, use relevanssi_do_query(),
 * which takes a WP_Query object as a parameter, formats the arguments nicely and
 * returns a specified subset of posts. This is for internal use.
 *
 * @global object   $wpdb                  The WordPress database interface.
 * @global array    $relevanssi_variables  The global Relevanssi variables array.
 * @global WP_Query $wp_query              The WP_Query object.
 * @global array    $relevanssi_post_types Cache array for post type values.
 *
 * @param array $args Array of arguments.
 *
 * @return array An array of return values.
 */
function relevanssi_search( $args ) {
	global $wpdb, $relevanssi_variables;
	$relevanssi_table = $relevanssi_variables['relevanssi_table'];

	/**
	 * Filters the search parameters.
	 *
	 * @param array The search parameters.
	 */
	$filtered_args      = apply_filters( 'relevanssi_search_filters', $args );
	$q                  = $filtered_args['q'];
	$tax_query          = $filtered_args['tax_query'];
	$tax_query_relation = $filtered_args['tax_query_relation'];
	$post_query         = $filtered_args['post_query'];
	$parent_query       = $filtered_args['parent_query'];
	$meta_query         = $filtered_args['meta_query'];
	$date_query         = $filtered_args['date_query'];
	$expost             = $filtered_args['expost'];
	$post_type          = $filtered_args['post_type'];
	$post_status        = $filtered_args['post_status'];
	$operator           = $filtered_args['operator'];
	$search_blogs       = $filtered_args['search_blogs'];
	$author             = $filtered_args['author'];
	$orderby            = $filtered_args['orderby'];
	$order              = $filtered_args['order'];
	$fields             = $filtered_args['fields'];
	$sentence           = $filtered_args['sentence'];
	$by_date            = $filtered_args['by_date'];

	$hits               = array();
	$query_restrictions = '';

	if ( ! isset( $tax_query_relation ) ) {
		$tax_query_relation = 'or';
	}
	$tax_query_relation = relevanssi_strtolower( $tax_query_relation );
	$term_tax_id        = array();
	$term_tax_ids       = array();
	$not_term_tax_ids   = array();
	$and_term_tax_ids   = array();

	if ( is_array( $tax_query ) ) {
		$is_sub_row = false;
		foreach ( $tax_query as $row ) {
			if ( isset( $row['terms'] ) ) {
				list( $query_restrictions, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids ) =
				relevanssi_process_tax_query_row( $row, $is_sub_row, $tax_query_relation, $query_restrictions, $tax_query_relation, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids );
			} else {
				$row_tax_query_relation = $tax_query_relation;
				if ( isset( $row['relation'] ) ) {
					$row_tax_query_relation = relevanssi_strtolower( $row['relation'] );
				}
				foreach ( $row as $subrow ) {
					$is_sub_row = true;
					if ( isset( $subrow['terms'] ) ) {
						list( $query_restrictions, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids ) =
						relevanssi_process_tax_query_row( $subrow, $is_sub_row, $tax_query_relation, $query_restrictions, $tax_query_relation, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids );
					}
				}
			}
		}

		if ( 'or' === $tax_query_relation ) {
			$term_tax_ids = array_unique( $term_tax_ids );
			if ( count( $term_tax_ids ) > 0 ) {
				$term_tax_ids        = implode( ',', $term_tax_ids );
				$query_restrictions .= " AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id) FROM $wpdb->term_relationships AS tr WHERE tr.term_taxonomy_id IN ($term_tax_ids))";
				// Clean: all variables are Relevanssi-generated.
			}
			if ( count( $not_term_tax_ids ) > 0 ) {
				$not_term_tax_ids    = implode( ',', $not_term_tax_ids );
				$query_restrictions .= " AND relevanssi.doc NOT IN (SELECT DISTINCT(tr.object_id) FROM $wpdb->term_relationships AS tr WHERE tr.term_taxonomy_id IN ($not_term_tax_ids))";
				// Clean: all variables are Relevanssi-generated.
			}
			if ( count( $and_term_tax_ids ) > 0 ) {
				$and_term_tax_ids    = implode( ',', $and_term_tax_ids );
				$n                   = count( explode( ',', $and_term_tax_ids ) );
				$query_restrictions .= " AND relevanssi.doc IN (
					SELECT ID FROM $wpdb->posts WHERE 1=1
					AND (
						SELECT COUNT(1)
						FROM $wpdb->term_relationships AS tr
						WHERE tr.term_taxonomy_id IN ($and_term_tax_ids)
						AND tr.object_id = $wpdb->posts.ID ) = $n
					)";
				// Clean: all variables are Relevanssi-generated.
			}
		}
	}

	if ( is_array( $post_query ) ) {
		if ( ! empty( $post_query['in'] ) ) {
			$valid_values = array();
			foreach ( $post_query['in'] as $post_in_id ) {
				if ( is_numeric( $post_in_id ) ) {
					$valid_values[] = $post_in_id;
				}
			}
			$posts = implode( ',', $valid_values );
			if ( ! empty( $posts ) ) {
				$query_restrictions .= " AND relevanssi.doc IN ($posts)";
				// Clean: $posts is checked to be integers.
			}
		}
		if ( ! empty( $post_query['not in'] ) ) {
			$valid_values = array();
			foreach ( $post_query['not in'] as $post_not_in_id ) {
				if ( is_numeric( $post_not_in_id ) ) {
					$valid_values[] = $post_not_in_id;
				}
			}
			$posts = implode( ',', $valid_values );
			if ( ! empty( $posts ) ) {
				$query_restrictions .= " AND relevanssi.doc NOT IN ($posts)";
				// Clean: $posts is checked to be integers.
			}
		}
	}

	if ( is_array( $parent_query ) ) {
		if ( ! empty( $parent_query['parent in'] ) ) {
			$valid_values = array();
			foreach ( $parent_query['parent in'] as $post_in_id ) {
				if ( is_numeric( $post_in_id ) ) {
					$valid_values[] = $post_in_id;
				}
			}
			$posts = implode( ',', $valid_values );
			if ( ! empty( $posts ) ) {
				$query_restrictions .= " AND relevanssi.doc IN (SELECT ID FROM $wpdb->posts WHERE post_parent IN ($posts))";
				// Clean: $posts is checked to be integers.
			}
		}
		if ( ! empty( $parent_query['parent not in'] ) ) {
			$valid_values = array();
			foreach ( $parent_query['parent not in'] as $post_not_in_id ) {
				if ( is_numeric( $post_not_in_id ) ) {
					$valid_values[] = $post_not_in_id;
				}
			}
			$posts = implode( ',', $valid_values );
			if ( ! empty( $posts ) ) {
				$query_restrictions .= " AND relevanssi.doc NOT IN (SELECT ID FROM $wpdb->posts WHERE post_parent IN ($posts))";
				// Clean: $posts is checked to be integers.
			}
		}
	}

	if ( is_array( $meta_query ) ) {
		$meta_query_restrictions = '';

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

		$query_restrictions .= $meta_where;
	}

	if ( ! empty( $date_query ) ) {
		if ( is_object( $date_query ) && method_exists( $date_query, 'get_sql' ) ) {
			$sql                 = $date_query->get_sql(); // AND ( the query itself ).
			$query_restrictions .= " AND relevanssi.doc IN ( SELECT DISTINCT(ID) FROM $wpdb->posts WHERE 1 $sql )";
			// Clean: $sql generated by $date_query->get_sql() query.
		}
	}

	// If $post_type is not set, see if there are post types to exclude from the search.
	// If $post_type is set, there's no need to exclude, as we only include.
	$negative_post_type = null;
	if ( ! $post_type ) {
		$negative_post_type = relevanssi_get_negative_post_type();
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

	if ( $post_status ) {
		if ( ! is_array( $post_status ) ) {
			$post_statuses = esc_sql( explode( ',', $post_status ) );
		} else {
			$post_statuses = esc_sql( $post_status );
		}

		$post_status = null;
		if ( count( $post_statuses ) > 0 ) {
			$post_status = "'" . implode( "', '", $post_statuses ) . "'";
		}
	}

	$posts_to_exclude = '';
	if ( ! empty( $expost ) ) {
		$excluded_post_ids = explode( ',', $expost );
		foreach ( $excluded_post_ids as $excluded_post_id ) {
			$exid              = intval( trim( $excluded_post_id, ' -' ) );
			$posts_to_exclude .= " AND relevanssi.doc != $excluded_post_id";
			// Clean: escaped.
		}
		$query_restrictions .= $posts_to_exclude;
	}

	if ( function_exists( 'wp_encode_emoji' ) ) {
		$q = wp_encode_emoji( $q );
	}

	if ( $sentence ) {
		$q = str_replace( '"', '', $q );
		$q = '"' . $q . '"';
	}

	$phrases = relevanssi_recognize_phrases( $q );

	if ( function_exists( 'relevanssi_recognize_negatives' ) ) {
		// Relevanssi Premium supports negative minus operator.
		$negative_terms = relevanssi_recognize_negatives( $q );
	} else {
		$negative_terms = false;
	}

	if ( function_exists( 'relevanssi_recognize_positives' ) ) {
		// Relevanssi Premium supports a plus operator.
		$positive_terms = relevanssi_recognize_positives( $q );
	} else {
		$positive_terms = false;
	}

	/**
	 * Filters whether stopwords are removed from titles.
	 *
	 * @param boolean If true, remove stopwords from titles.
	 */
	$remove_stopwords = apply_filters( 'relevanssi_remove_stopwords_in_titles', true );

	$terms = relevanssi_tokenize( $q, $remove_stopwords );

	if ( count( $terms ) < 1 ) {
		// Tokenizer killed all the search terms.
		return $hits;
	}
	$terms = array_keys( $terms ); // Don't care about tf in query.

	if ( $negative_terms ) {
		$terms = array_diff( $terms, $negative_terms );
	}

	// Go get the count from the options, but run the full query if it's not available.
	$doc_count = get_option( 'relevanssi_doc_count' );
	if ( ! $doc_count || $doc_count < 1 ) {
		$doc_count = $wpdb->get_var( "SELECT COUNT(DISTINCT(relevanssi.doc)) FROM $relevanssi_table AS relevanssi" ); // WPCS: unprepared SQL ok, Relevanssi table name.
		update_option( 'relevanssi_doc_count', $doc_count );
	}

	$total_hits = 0;

	$title_matches    = array();
	$tag_matches      = array();
	$comment_matches  = array();
	$link_matches     = array();
	$body_matches     = array();
	$category_matches = array();
	$taxonomy_matches = array();
	$scores           = array();
	$term_hits        = array();

	$fuzzy = get_option( 'relevanssi_fuzzy' );

	if ( function_exists( 'relevanssi_negatives_positives' ) ) {
		$query_restrictions .= relevanssi_negatives_positives( $negative_terms, $positive_terms, $relevanssi_table );
		// Clean: escaped in the function.
	}

	if ( ! empty( $author ) ) {
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
	}

	if ( $post_type ) {
		// A post type is set: add a restriction.
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
	} else {
		// No regular post types.
		if ( $non_post_post_type ) {
			// But there is a non-post post type restriction.
			$query_restrictions .= " AND (relevanssi.type IN ($non_post_post_type))";
			// Clean: $non_post_post_types is escaped.
		}
	}

	if ( $negative_post_type ) {
		$query_restrictions .= " AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
			WHERE posts.post_type NOT IN ($negative_post_type))) OR (doc = -1))";
		// Clean: $negative_post_type is escaped.
	}

	if ( $post_status ) {
		global $wp_query;
		if ( $wp_query->is_admin ) {
			$query_restrictions .= " AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
				WHERE posts.post_status IN ($post_status))))";
		} else {
			// The -1 is there to get user profiles and category pages.
			$query_restrictions .= " AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
				WHERE posts.post_status IN ($post_status))) OR (doc = -1))";
		}
		// Clean: $post_status is escaped.
	}

	if ( $phrases ) {
		$query_restrictions .= " $phrases";
		// Clean: $phrases is escaped earlier.
	}

	if ( isset( $by_date ) ) {
		$n = $by_date;

		$u = substr( $n, -1, 1 );
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

		$n = preg_replace( '/[hdmyw]/', '', $n );

		if ( is_numeric( $n ) ) {
			$query_restrictions .= " AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM $wpdb->posts AS posts
				WHERE posts.post_date > DATE_SUB(NOW(), INTERVAL $n $unit))";
			// Clean: $n is always numeric, $unit is Relevanssi-generated.
		}
	}

	/**
	 * Filters the query restrictions for the Relevanssi query.
	 *
	 * Equivalent to the 'posts_where' filter.
	 *
	 * @author Charles St-Pierre
	 *
	 * @param string The MySQL code that restricts the query.
	 */
	$query_restrictions = apply_filters( 'relevanssi_where', $query_restrictions );
	$query_join         = '';
	if ( ! empty( $meta_join ) ) {
		$query_join = $meta_join;
	}
	/**
	 * Filters the meta query JOIN for the Relevanssi search query.
	 *
	 * Somewhat equivalent to the 'posts_join' filter.
	 *
	 * @param string The JOINed query.
	 */
	$query_join = apply_filters( 'relevanssi_join', $query_join );

	$no_matches = true;
	if ( 'always' === $fuzzy ) {
		/**
		 * Filters the partial matching search query.
		 *
		 * By default partial matching matches the beginnings and the ends of the
		 * words. If you want it to match inside words, add a function to this
		 * hook that returns '(relevanssi.term LIKE '%#term#%')'.
		 *
		 * @param string The partial matching query.
		 */
		$o_term_cond = apply_filters( 'relevanssi_fuzzy_query', "(relevanssi.term LIKE '#term#%' OR relevanssi.term_reverse LIKE CONCAT(REVERSE('#term#'), '%')) " );
	} else {
		$o_term_cond = " relevanssi.term = '#term#' ";
	}

	if ( count( $terms ) < 1 ) {
		$o_term_cond = ' relevanssi.term = relevanssi.term ';
		$terms[]     = 'term';
	}

	$post_type_weights = get_option( 'relevanssi_post_type_weights' );

	$recency_bonus       = false;
	$recency_cutoff_date = false;
	if ( function_exists( 'relevanssi_get_recency_bonus' ) ) {
		list( $recency_bonus, $recency_cutoff_date ) = relevanssi_get_recency_bonus();
	}

	$exact_match_bonus = false;
	if ( 'on' === get_option( 'relevanssi_exact_match_bonus' ) ) {
		$exact_match_bonus = true;
		/**
		 * Filters the exact match bonus.
		 *
		 * @param array The title bonus under 'title' (default 5) and the content
		 * bonus under 'content' (default 2).
		 */
		$exact_match_boost = apply_filters( 'relevanssi_exact_match_bonus', array(
			'title'   => 5,
			'content' => 2,
		));

	}

	$min_length = get_option( 'relevanssi_min_word_length' );

	$search_again = false;

	$content_boost = floatval( get_option( 'relevanssi_content_boost', 1 ) ); // Default value, because this option was added late.
	$title_boost   = floatval( get_option( 'relevanssi_title_boost' ) );
	$link_boost    = floatval( get_option( 'relevanssi_link_boost' ) );
	$comment_boost = floatval( get_option( 'relevanssi_comment_boost' ) );

	$include_these_posts = array();

	do {
		foreach ( $terms as $term ) {
			$term = trim( $term ); // Numeric search terms will start with a space.
			/**
			 * Allows the use of one letter search terms.
			 *
			 * Return false to allow one letter searches.
			 *
			 * @param boolean True, if search term is one letter long and will be blocked.
			 */
			if ( apply_filters( 'relevanssi_block_one_letter_searches', relevanssi_strlen( $term ) < 2 ) ) {
				continue;
			}
			$term = esc_sql( $term );

			if ( false !== strpos( $o_term_cond, 'LIKE' ) ) {
				$term = $wpdb->esc_like( $term );
			}

			$term_cond = str_replace( '#term#', $term, $o_term_cond );

			$tag = $relevanssi_variables['post_type_weight_defaults']['post_tag'];
			$cat = $relevanssi_variables['post_type_weight_defaults']['category'];
			if ( ! empty( $post_type_weights['post_tag'] ) ) {
				$tag = $post_type_weights['post_tag'];
			}
			if ( ! empty( $post_type_weights['category'] ) ) {
				$cat = $post_type_weights['category'];
			}

			$query = "SELECT DISTINCT(relevanssi.doc), relevanssi.*, relevanssi.title * $title_boost +
				relevanssi.content * $content_boost + relevanssi.comment * $comment_boost +
				relevanssi.tag * $tag + relevanssi.link * $link_boost +
				relevanssi.author + relevanssi.category * $cat + relevanssi.excerpt +
				relevanssi.taxonomy + relevanssi.customfield + relevanssi.mysqlcolumn AS tf
				FROM $relevanssi_table AS relevanssi $query_join WHERE $term_cond $query_restrictions";
			/** Clean: $query_restrictions is escaped, $term_cond is escaped. */

			/**
			 * Filters the Relevanssi MySQL query.
			 *
			 * The last chance to filter the MySQL query before it is run.
			 *
			 * @param string MySQL query for the Relevanssi search.
			 */
			$query   = apply_filters( 'relevanssi_query_filter', $query );
			$matches = $wpdb->get_results( $query ); // WPCS: unprepared SQL ok, the query is thoroughly escaped.

			if ( count( $matches ) < 1 ) {
				continue;
			} else {
				$no_matches = false;
				if ( count( $include_these_posts ) > 0 ) {
					$post_ids_to_add = implode( ',', array_keys( $include_these_posts ) );
					$existing_ids    = array();
					foreach ( $matches as $match ) {
						$existing_ids[] = $match->doc;
					}
					$existing_ids = implode( ',', $existing_ids );
					$query        = "SELECT relevanssi.*, relevanssi.title * $title_boost +
					relevanssi.content + relevanssi.comment * $comment_boost +
					relevanssi.tag * $tag + relevanssi.link * $link_boost +
					relevanssi.author + relevanssi.category * $cat + relevanssi.excerpt +
					relevanssi.taxonomy + relevanssi.customfield + relevanssi.mysqlcolumn AS tf
					FROM $relevanssi_table AS relevanssi WHERE relevanssi.doc IN ($post_ids_to_add)
					AND relevanssi.doc NOT IN ($existing_ids) AND $term_cond";
					// Clean: no unescaped user inputs.
					$matches_to_add = $wpdb->get_results( $query ); // WPCS: unprepared SQL ok.
					$matches        = array_merge( $matches, $matches_to_add );
				}
			}

			relevanssi_populate_array( $matches );
			global $relevanssi_post_types;

			$total_hits += count( $matches );

			$query = "SELECT COUNT(DISTINCT(relevanssi.doc)) FROM $relevanssi_table AS relevanssi
				$query_join WHERE $term_cond $query_restrictions";
			// Clean: $query_restrictions is escaped, $term_cond is escaped.
			/**
			 * Filters the DF query.
			 *
			 * This query is used to calculate the df for the tf * idf calculations.
			 *
			 * @param string MySQL query to filter.
			 */
			$query = apply_filters( 'relevanssi_df_query_filter', $query );

			$df = $wpdb->get_var( $query ); // WPCS: unprepared SQL ok.

			if ( $df < 1 && 'sometimes' === $fuzzy ) {
				$query = "SELECT COUNT(DISTINCT(relevanssi.doc)) FROM $relevanssi_table AS relevanssi
					$query_join WHERE (relevanssi.term LIKE '$term%'
					OR relevanssi.term_reverse LIKE CONCAT(REVERSE('$term), %')) $query_restrictions";
				// Clean: $query_restrictions is escaped, $term is escaped.
				/** Documented in lib/search.php. */
				$query = apply_filters( 'relevanssi_df_query_filter', $query );
				$df    = $wpdb->get_var( $query ); // WPCS: unprepared SQL ok.
			}

			$idf = log( $doc_count + 1 / ( 1 + $df ) );
			$idf = $idf * $idf; // Adjustment to increase the value of IDF.
			if ( $idf < 1 ) {
				$idf = 1;
			}
			foreach ( $matches as $match ) {
				if ( 'user' === $match->type ) {
					$match->doc = 'u_' . $match->item;
				} elseif ( ! in_array( $match->type, array( 'post', 'attachment' ), true ) ) {
					$match->doc = '**' . $match->type . '**' . $match->item;
				}

				if ( isset( $match->taxonomy_detail ) ) {
					$match->taxonomy_score  = 0;
					$match->taxonomy_detail = json_decode( $match->taxonomy_detail );
					if ( is_object( $match->taxonomy_detail ) ) {
						foreach ( $match->taxonomy_detail as $tax => $count ) {
							if ( 'post_tag' === $tax ) {
								$match->tag = $count;
							}
							if ( empty( $post_type_weights[ $tax ] ) ) {
								$match->taxonomy_score += $count * 1;
							} else {
								$match->taxonomy_score += $count * $post_type_weights[ $tax ];
							}
						}
					}
				}

				$match->tf =
					$match->title * $title_boost +
					$match->content * $content_boost +
					$match->comment * $comment_boost +
					$match->link * $link_boost +
					$match->author +
					$match->excerpt +
					$match->taxonomy_score +
					$match->customfield +
					$match->mysqlcolumn;

				$term_hits[ $match->doc ][ $term ] =
					$match->title +
					$match->content +
					$match->comment +
					$match->tag +
					$match->link +
					$match->author +
					$match->category +
					$match->excerpt +
					$match->taxonomy +
					$match->customfield +
					$match->mysqlcolumn;

				$match->weight = $match->tf * $idf;

				if ( $recency_bonus ) {
					$post = relevanssi_get_post( $match->doc );
					if ( strtotime( $post->post_date ) > $recency_cutoff_date ) {
						$match->weight = $match->weight * $recency_bonus;
					}
				}

				if ( $exact_match_bonus ) {
					$post    = relevanssi_get_post( $match->doc );
					$clean_q = str_replace( '"', '', $q );
					if ( stristr( $post->post_title, $clean_q ) !== false ) {
						$match->weight *= $exact_match_boost['title'];
					}
					if ( stristr( $post->post_content, $clean_q ) !== false ) {
						$match->weight *= $exact_match_boost['content'];
					}
				}

				if ( ! isset( $body_matches[ $match->doc ] ) ) {
					$body_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $title_matches[ $match->doc ] ) ) {
					$title_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $link_matches[ $match->doc ] ) ) {
					$link_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $tag_matches[ $match->doc ] ) ) {
					$tag_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $category_matches[ $match->doc ] ) ) {
					$category_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $taxonomy_matches[ $match->doc ] ) ) {
					$taxonomy_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $comment_matches[ $match->doc ] ) ) {
					$comment_matches[ $match->doc ] = 0;
				}
				$body_matches[ $match->doc ]     += $match->content;
				$title_matches[ $match->doc ]    += $match->title;
				$link_matches[ $match->doc ]     += $match->link;
				$tag_matches[ $match->doc ]      += $match->tag;
				$category_matches[ $match->doc ] += $match->category;
				$taxonomy_matches[ $match->doc ] += $match->taxonomy;
				$comment_matches[ $match->doc ]  += $match->comment;

				$type = null;
				if ( isset( $relevanssi_post_types[ $match->doc ] ) ) {
					$type = $relevanssi_post_types[ $match->doc ];
				}
				if ( ! empty( $post_type_weights[ $type ] ) ) {
					$match->weight = $match->weight * $post_type_weights[ $type ];
				}

				/**
				 * Filters the hit.
				 *
				 * This filter hook can be used to adjust the weights of found hits.
				 * Calculate the new weight and set the $match->weight to the new
				 * value.
				 *
				 * @param object $match The match object.
				 * @param int    $idf   The IDF value, if you want to recalculate
				 * TF * IDF values (TF is in $match->tf).
				 * @param string $term  The current search term.
				 */
				$match = apply_filters( 'relevanssi_match', $match, $idf, $term );
				if ( $match->weight <= 0 ) {
					continue; // The filters killed the match.
				}

				$post_ok = true;
				/**
				 * Filters whether the post can be shown to the user.
				 *
				 * This filter hook is used for 'relevanssi_default_post_ok' filter
				 * function which handles private posts and some membership plugins.
				 * If you want to add support for more membership plugins, this is
				 * the filter hook to use.
				 *
				 * @param boolean True, if the post can be shown to the current user.
				 * @param int     The post ID.
				 */
				$post_ok = apply_filters( 'relevanssi_post_ok', $post_ok, $match->doc );

				if ( $post_ok ) {
					$doc_terms[ $match->doc ][ $term ] = true; // Count how many terms are matched to a doc.
					if ( ! isset( $doc_weight[ $match->doc ] ) ) {
						$doc_weight[ $match->doc ] = 0;
					}
					$doc_weight[ $match->doc ] += $match->weight;
					if ( ! isset( $scores[ $match->doc ] ) ) {
						$scores[ $match->doc ] = 0;
					}
					$scores[ $match->doc ] += $match->weight;
					if ( is_numeric( $match->doc ) ) {
						// This is to weed out taxonomies and users (t_XXX, u_XXX).
						$include_these_posts[ $match->doc ] = true;
					}
				}
			}
		}

		if ( ! isset( $doc_weight ) ) {
			$doc_weight = array();
			$no_matches = true;
		}
		if ( $no_matches ) {
			if ( $search_again ) {
				// No hits even with fuzzy search!
				$search_again = false;
			} else {
				if ( 'sometimes' === $fuzzy ) {
					$search_again = true;
					$o_term_cond  = "(term LIKE '%#term#' OR term LIKE '#term#%') ";
				}
			}
		} else {
			$search_again = false;
		}
		$params = array(
			'no_matches'   => $no_matches,
			'doc_weight'   => $doc_weight,
			'terms'        => $terms,
			'o_term_cond'  => $o_term_cond,
			'search_again' => $search_again,
		);
		/**
		 * Filters the parameters for fallback search.
		 *
		 * If you want to make Relevanssi search again with different parameters, you
		 * can use this filter hook to adjust the parameters. Set
		 * $params['search_again'] to true to make Relevanssi do a new search.
		 *
		 * @param array The search parameters.
		 */
		$params       = apply_filters( 'relevanssi_search_again', $params );
		$search_again = $params['search_again'];
		$terms        = $params['terms'];
		$o_term_cond  = $params['o_term_cond'];
		$doc_weight   = $params['doc_weight'];
		$no_matches   = $params['no_matches'];
	} while ( $search_again );

	$strip_stops              = true;
	$temp_terms_without_stops = array_keys( relevanssi_tokenize( implode( ' ', $terms ), $strip_stops ) );
	$terms_without_stops      = array();
	foreach ( $temp_terms_without_stops as $temp_term ) {
		if ( relevanssi_strlen( $temp_term ) >= $min_length ) {
			array_push( $terms_without_stops, $temp_term );
		}
	}
	$total_terms = count( $terms_without_stops );

	if ( isset( $doc_weight ) ) {
		/**
		 * Filters the results Relevanssi finds.
		 *
		 * Often you'll find 'relevanssi_hits_filter' more useful than this, but
		 * sometimes this is the right tool for filtering the results.
		 *
		 * @param array $doc_weight An array of (post ID, weight) pairs.
		 */
		$doc_weight = apply_filters( 'relevanssi_results', $doc_weight );
	}

	if ( isset( $doc_weight ) && count( $doc_weight ) > 0 ) {
		arsort( $doc_weight );
		$i = 0;
		foreach ( $doc_weight as $doc => $weight ) {
			if ( count( $doc_terms[ $doc ] ) < $total_terms && 'AND' === $operator ) {
				// AND operator in action:
				// doc didn't match all terms, so it's discarded.
				continue;
			}

			if ( ! empty( $fields ) ) {
				if ( 'ids' === $fields ) {
					$hits[ intval( $i ) ] = $doc;
				}
				if ( 'id=>parent' === $fields ) {
					$object              = new StdClass();
					$object->ID          = $doc;
					$object->post_parent = wp_get_post_parent_id( $doc );

					$hits[ intval( $i ) ] = $object;
				}
			} else {
				$hits[ intval( $i ) ]                  = relevanssi_get_post( $doc );
				$hits[ intval( $i ) ]->relevance_score = round( $weight, 2 );
			}
			$i++;
		}
	}

	if ( count( $hits ) < 1 ) {
		if ( 'AND' === $operator && 'on' !== get_option( 'relevanssi_disable_or_fallback' ) ) {
			$or_args             = $args;
			$or_args['operator'] = 'OR';
			global $wp_query;
			$wp_query->set( 'operator', 'OR' );

			$or_args['q'] = relevanssi_add_synonyms( $q );
			$return       = relevanssi_search( $or_args );

			$hits             = $return['hits'];
			$body_matches     = $return['body_matches'];
			$title_matches    = $return['title_matches'];
			$tag_matches      = $return['tag_matches'];
			$category_matches = $return['category_matches'];
			$taxonomy_matches = $return['taxonomy_matches'];
			$comment_matches  = $return['comment_matches'];
			$body_matches     = $return['body_matches'];
			$link_matches     = $return['link_matches'];
			$term_hits        = $return['term_hits'];
			$q                = $return['query'];
		}
		$params = array( 'args' => $args );
		/**
		 * Filters the fallback search parameters.
		 *
		 * This filter can be used to implement a fallback search. Take the
		 * parameters, do something with them, then return a proper return value
		 * array in $param['return'].
		 *
		 * @param array Search parameters.
		 */
		$params = apply_filters( 'relevanssi_fallback', $params );
		$args   = $params['args'];
		if ( isset( $params['return'] ) ) {
			$return           = $params['return'];
			$hits             = $return['hits'];
			$body_matches     = $return['body_matches'];
			$title_matches    = $return['title_matches'];
			$tag_matches      = $return['tag_matches'];
			$category_matches = $return['category_matches'];
			$taxonomy_matches = $return['taxonomy_matches'];
			$comment_matches  = $return['comment_matches'];
			$body_matches     = $return['body_matches'];
			$link_matches     = $return['link_matches'];
			$term_hits        = $return['term_hits'];
			$q                = $return['query'];
		}
	}

	$default_order = get_option( 'relevanssi_default_orderby', 'relevance' );
	if ( empty( $orderby ) ) {
		$orderby = $default_order;
	}

	if ( is_array( $orderby ) ) {
		/**
		 * Filters the 'orderby' value just before sorting.
		 *
		 * Relevanssi can use both array orderby ie. array( orderby => order ) with
		 * multiple orderby parameters, or a single pair of orderby and order
		 * parameters. To avoid problems, try sticking to one and don't use this
		 * filter to make surprising changes between different formats.
		 *
		 * @param string The 'orderby' parameter.
		 */
		$orderby = apply_filters( 'relevanssi_orderby', $orderby );
		relevanssi_object_sort( $hits, $orderby );
	} else {
		if ( empty( $order ) ) {
			$order = 'desc';
		}
		$order = strtolower( $order );

		$order_accepted_values = array( 'asc', 'desc' );
		if ( ! in_array( $order, $order_accepted_values, true ) ) {
			$order = 'desc';
		}

		/** Documented in lib/search.php.  */
		$orderby = apply_filters( 'relevanssi_orderby', $orderby );
		/**
		 * Filters the 'order' value just before sorting.
		 *
		 * @param string The 'order' parameter.
		 */
		$order = apply_filters( 'relevanssi_order', $order );

		if ( 'relevance' !== $orderby ) {
			$orderby_array = array( $orderby => $order );
			relevanssi_object_sort( $hits, $orderby_array );
		}
	}
	$return = array(
		'hits'             => $hits,
		'body_matches'     => $body_matches,
		'title_matches'    => $title_matches,
		'tag_matches'      => $tag_matches,
		'category_matches' => $category_matches,
		'taxonomy_matches' => $taxonomy_matches,
		'comment_matches'  => $comment_matches,
		'scores'           => $scores,
		'term_hits'        => $term_hits,
		'query'            => $q,
		'link_matches'     => $link_matches,
	);

	return $return;
}

/**
 * Takes a WP_Query object and runs the search query based on that
 *
 * This function can be used to run Relevanssi searches anywhere. Just create an
 * empty WP_Query object, give it some parameters, make sure 's' is set and contains
 * the search query, then run relevanssi_do_query() on the query object.
 *
 * This function is strongly influenced by Kenny Katzgrau's wpSearch plugin.
 *
 * @global boolean $relevanssi_active If true, Relevanssi is currently doing a
 * search.
 *
 * @param WP_Query $query A WP_Query object, passed as a reference. Relevanssi will
 * put the posts found in $query->posts, and also sets $query->post_count.
 *
 * @return array The found posts, an array of post objects.
 */
function relevanssi_do_query( &$query ) {
	global $relevanssi_active;
	$relevanssi_active = true;

	$posts = array();

	$q = trim( stripslashes( relevanssi_strtolower( $query->query_vars['s'] ) ) );

	$did_multisite_search = false;
	if ( is_multisite() ) {
		$search_multisite = false;
		if ( isset( $query->query_vars['searchblogs'] ) && (string) get_current_blog_id() !== $query->query_vars['searchblogs'] ) {
			$search_multisite = true;
		}

		// Is searching all blogs enabled?
		$searchblogs_all = get_option( 'relevanssi_searchblogs_all', 'off' );
		if ( 'off' === $searchblogs_all ) {
			$searchblogs_all = false;
		}
		if ( ! $search_multisite && $searchblogs_all ) {
			$search_multisite = true;
			$searchblogs      = 'all';
		}

		// Searchblogs is not set from the query variables, check the option.
		$searchblogs_setting = get_option( 'relevanssi_searchblogs' );
		if ( ! $search_multisite && $searchblogs_setting ) {
			$search_multisite = true;
			$searchblogs      = $searchblogs_setting;
		}

		if ( $search_multisite ) {
			$multi_args = array();
			if ( isset( $query->query_vars['searchblogs'] ) ) {
				$multi_args['search_blogs'] = $query->query_vars['searchblogs'];
			} else {
				$multi_args['search_blogs'] = $searchblogs;
			}
			$multi_args['q'] = $q;

			$post_type = false;
			if ( isset( $query->query_vars['post_type'] ) && 'any' !== $query->query_vars['post_type'] ) {
				$multi_args['post_type'] = $query->query_vars['post_type'];
			}
			if ( isset( $query->query_vars['post_types'] ) && 'any' !== $query->query_vars['post_types'] ) {
				$multi_args['post_type'] = $query->query_vars['post_types'];
			}

			if ( isset( $query->query_vars['order'] ) ) {
				$multi_args['order'] = $query->query_vars['order'];
			}
			if ( isset( $query->query_vars['orderby'] ) ) {
				$multi_args['orderby'] = $query->query_vars['orderby'];
			}

			$operator = '';
			if ( function_exists( 'relevanssi_set_operator' ) ) {
				$operator = relevanssi_set_operator( $query );
				$operator = strtoupper( $operator ); // Just in case.
			}
			if ( 'OR' !== $operator && 'AND' !== $operator ) {
				$operator = get_option( 'relevanssi_implicit_operator' );
			}
			$multi_args['operator'] = $operator;

			$meta_query = array();
			if ( ! empty( $query->query_vars['meta_query'] ) ) {
				$meta_query = $query->query_vars['meta_query'];
			}

			if ( isset( $query->query_vars['customfield_key'] ) ) {
				$build_meta_query = array();

				// Use meta key.
				$build_meta_query['key'] = $query->query_vars['customfield_key'];

				/**
				 * Check the value is not empty for ordering purpose,
				 * Set it or not for the current meta query
				 */
				if ( ! empty( $query->query_vars['customfield_value'] ) ) {
					$build_meta_query['value'] = $query->query_vars['customfield_value'];
				}

				// Set the compare.
				$build_meta_query['compare'] = '=';

				$meta_query[] = $build_meta_query;
			}

			if ( ! empty( $query->query_vars['meta_key'] ) || ! empty( $query->query_vars['meta_value'] ) || ! empty( $query->query_vars['meta_value_num'] ) ) {
				$build_meta_query = array();

				// Use meta key.
				$build_meta_query['key'] = $query->query_vars['meta_key'];

				$value = null;
				if ( ! empty( $query->query_vars['meta_value'] ) ) {
					$value = $query->query_vars['meta_value'];
				} elseif ( ! empty( $query->query_vars['meta_value_num'] ) ) {
					$value = $query->query_vars['meta_value_num'];
				}

				/**
				 * Check the meta value, as it could be not set for ordering purpose
				 * set it or not for the current meta query.
				 */
				if ( ! empty( $value ) ) {
					$build_meta_query['value'] = $value;
				}

				// Set meta compare.
				$build_meta_query['compare'] = '=';
				if ( ! empty( $query->query_vars['meta_compare'] ) ) {
					$query->query_vars['meta_compare'];
				}

				$meta_query[] = $build_meta_query;
			}

			$multi_args['meta_query'] = $meta_query;
			if ( function_exists( 'relevanssi_search_multi' ) ) {
				$return = relevanssi_search_multi( $multi_args );
			}
			$did_multisite_search = true;
		}
	}
	if ( ! $did_multisite_search ) {
		$tax_query = array();
		/**
		 * Filters the default tax_query relation.
		 *
		 * @param string The default relation, default 'OR'.
		 */
		$tax_query_relation = apply_filters( 'relevanssi_default_tax_query_relation', 'OR' );
		if ( isset( $query->tax_query ) && empty( $query->tax_query->queries ) ) {
			// Tax query is empty, let's get rid of it.
			$query->tax_query = null;
		}
		if ( isset( $query->query_vars['tax_query'] ) ) {
			// This is user-created tax_query array as described in WP Codex.
			foreach ( $query->query_vars['tax_query'] as $type => $item ) {
				if ( is_string( $type ) && 'relation' === $type ) {
					$tax_query_relation = $item;
				} else {
					$tax_query[] = $item;
				}
			}
		} elseif ( isset( $query->tax_query ) ) {
			// This is the WP-created Tax_Query object, which is different from above.
			foreach ( $query->tax_query as $type => $item ) {
				if ( is_string( $type ) && 'relation' === $type ) {
					$tax_query_relation = $item;
				}
				if ( is_string( $type ) && 'queries' === $type ) {
					foreach ( $item as $tax_query_row ) {
						$tax_query[] = $tax_query_row;
					}
				}
			}
		} else {
			$cat = false;
			if ( isset( $query->query_vars['cats'] ) ) {
				$cat = $query->query_vars['cats'];
			}
			if ( empty( $cat ) ) {
				$cat = get_option( 'relevanssi_cat' );
			}
			if ( $cat ) {
				$cat         = explode( ',', $cat );
				$tax_query[] = array(
					'taxonomy' => 'category',
					'field'    => 'id',
					'terms'    => $cat,
				);
			}
			if ( ! empty( $query->query_vars['category_name'] ) && empty( $query->query_vars['category__in'] ) ) {
				$cat         = explode( ',', $query->query_vars['category_name'] );
				$tax_query[] = array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => $cat,
				);
			}
			if ( ! empty( $query->query_vars['category__in'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'category',
					'field'    => 'id',
					'terms'    => $query->query_vars['category__in'],
				);
			}
			if ( ! empty( $query->query_vars['category__not_in'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'category',
					'field'    => 'id',
					'terms'    => $query->query_vars['category__not_in'],
					'operator' => 'NOT IN',
				);
			}
			if ( ! empty( $query->query_vars['category__and'] ) ) {
				$tax_query[] = array(
					'taxonomy'         => 'category',
					'field'            => 'id',
					'terms'            => $query->query_vars['category__and'],
					'operator'         => 'AND',
					'include_children' => false,
				);
			}
			$excat = get_option( 'relevanssi_excat' );
			if ( ! empty( $excat ) ) {
				$tax_query[] = array(
					'taxonomy' => 'category',
					'field'    => 'id',
					'terms'    => $excat,
					'operator' => 'NOT IN',
				);
			}

			$tag = false;
			if ( ! empty( $query->query_vars['tags'] ) ) {
				$tag = $query->query_vars['tags'];
			}
			if ( $tag ) {
				if ( false !== strpos( $tag, '+' ) ) {
					$tag      = explode( '+', $tag );
					$operator = 'and';
				} else {
					$tag      = explode( ',', $tag );
					$operator = 'or';
				}
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $tag,
					'operator' => $operator,
				);
			}
			if ( ! empty( $query->query_vars['tag_id'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $query->query_vars['tag_id'],
				);
			}
			if ( ! empty( $query->query_vars['tag_id'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $query->query_vars['tag_id'],
				);
			}
			if ( ! empty( $query->query_vars['tag__in'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $query->query_vars['tag__in'],
				);
			}
			if ( ! empty( $query->query_vars['tag__not_in'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $query->query_vars['tag__not_in'],
					'operator' => 'NOT IN',
				);
			}
			if ( ! empty( $query->query_vars['tag__and'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $query->query_vars['tag__and'],
					'operator' => 'AND',
				);
			}
			if ( ! empty( $query->query_vars['tag__not_in'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $query->query_vars['tag__not_in'],
					'operator' => 'NOT IN',
				);
			}
			if ( ! empty( $query->query_vars['tag_slug__in'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => $query->query_vars['tag_slug__in'],
				);
			}
			if ( ! empty( $query->query_vars['tag_slug__not_in'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => $query->query_vars['tag_slug__not_in'],
					'operator' => 'NOT IN',
				);
			}
			if ( ! empty( $query->query_vars['tag_slug__and'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => $query->query_vars['tag_slug__and'],
					'operator' => 'AND',
				);
			}
			$extag = get_option( 'relevanssi_extag' );
			if ( ! empty( $extag ) && '0' !== $extag ) {
				$tax_query[] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $extag,
					'operator' => 'NOT IN',
				);
			}

			if ( isset( $query->query_vars['taxonomy'] ) ) {
				if ( function_exists( 'relevanssi_process_taxonomies' ) ) {
					$tax_query = relevanssi_process_taxonomies( $query->query_vars['taxonomy'], $query->query_vars['term'], $tax_query );
				} else {
					if ( ! empty( $query->query_vars['term'] ) ) {
						$term = $query->query_vars['term'];
					}

					$tax_query[] = array(
						'taxonomy' => $query->query_vars['taxonomy'],
						'field'    => 'slug',
						'terms'    => $term,
					);
				}
			}
		}

		$author = false;
		if ( ! empty( $query->query_vars['author'] ) ) {
			$author = explode( ',', $query->query_vars['author'] );
		}
		if ( ! empty( $query->query_vars['author_name'] ) ) {
			$author_object = get_user_by( 'slug', $query->query_vars['author_name'] );
			$author[]      = $author_object->ID;
		}

		$post_query = array();
		if ( ! empty( $query->query_vars['p'] ) ) {
			$post_query = array( 'in' => array( $query->query_vars['p'] ) );
		}
		if ( ! empty( $query->query_vars['page_id'] ) ) {
			$post_query = array( 'in' => array( $query->query_vars['page_id'] ) );
		}
		if ( ! empty( $query->query_vars['post__in'] ) ) {
			$post_query = array( 'in' => $query->query_vars['post__in'] );
		}
		if ( ! empty( $query->query_vars['post__not_in'] ) ) {
			$post_query = array( 'not in' => $query->query_vars['post__not_in'] );
		}

		$parent_query = array();
		if ( ! empty( $query->query_vars['post_parent'] ) ) {
			$parent_query = array( 'parent in' => array( $query->query_vars['post_parent'] ) );
		}
		if ( ! empty( $query->query_vars['post_parent__in'] ) ) {
			$parent_query = array( 'parent in' => $query->query_vars['post_parent__in'] );
		}
		if ( ! empty( $query->query_vars['post_parent__not_in'] ) ) {
			$parent_query = array( 'parent not in' => $query->query_vars['post_parent__not_in'] );
		}

		/**
		 * Filters the default meta_query relation.
		 *
		 * @param string The meta_query relation, default 'AND'.
		 */
		$meta_query_relation = apply_filters( 'relevanssi_default_meta_query_relation', 'AND' );
		$meta_query          = array();
		if ( ! empty( $query->query_vars['meta_query'] ) ) {
			$meta_query = $query->query_vars['meta_query'];
		}

		if ( isset( $query->query_vars['customfield_key'] ) ) {
			$build_meta_query = array();

			// Use meta key.
			$build_meta_query['key'] = $query->query_vars['customfield_key'];

			/**
			 * Check the value is not empty for ordering purpose,
			 * set it or not for the current meta query.
			 */
			if ( ! empty( $query->query_vars['customfield_value'] ) ) {
				$build_meta_query['value'] = $query->query_vars['customfield_value'];
			}

			// Set the compare.
			$build_meta_query['compare'] = '=';
			$meta_query[]                = $build_meta_query;
		}

		if ( ! empty( $query->query_vars['meta_key'] ) || ! empty( $query->query_vars['meta_value'] ) || ! empty( $query->query_vars['meta_value_num'] ) ) {
			$build_meta_query = array();

			// Use meta key.
			$build_meta_query['key'] = $query->query_vars['meta_key'];

			$value = null;
			if ( ! empty( $query->query_vars['meta_value'] ) ) {
				$value = $query->query_vars['meta_value'];
			} elseif ( ! empty( $query->query_vars['meta_value_num'] ) ) {
				$value = $query->query_vars['meta_value_num'];
			}

			/**
			 * Check the meta value, as it could be not set for ordering purpose.
			 * Set it or not for the current meta query.
			 */
			if ( ! empty( $value ) ) {
				$build_meta_query['value'] = $value;
			}

			// Set meta compare.
			$build_meta_query['compare'] = '=';
			if ( ! empty( $query->query_vars['meta_compare'] ) ) {
				$query->query_vars['meta_compare'];
			}

			$meta_query[] = $build_meta_query;
		}

		$date_query = false;
		if ( ! empty( $query->date_query ) ) {
			if ( is_object( $query->date_query ) && 'WP_Date_Query' === get_class( $query->date_query ) ) {
				$date_query = $query->date_query;
			} else {
				$date_query = new WP_Date_Query( $query->date_query );
			}
		}

		$search_blogs = false;
		if ( isset( $query->query_vars['search_blogs'] ) ) {
			$search_blogs = $query->query_vars['search_blogs'];
		}

		$post_type = false;
		if ( isset( $query->query_vars['post_type'] ) && 'any' !== $query->query_vars['post_type'] ) {
			$post_type = $query->query_vars['post_type'];
		}
		if ( isset( $query->query_vars['post_types'] ) && 'any' !== $query->query_vars['post_types'] ) {
			$post_type = $query->query_vars['post_types'];
		}

		$post_status = false;
		if ( isset( $query->query_vars['post_status'] ) && 'any' !== $query->query_vars['post_status'] ) {
			$post_status = $query->query_vars['post_status'];
		}

		$expost = get_option( 'relevanssi_exclude_posts' );

		// In admin (and when not AJAX), search everything.
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$excat  = null;
			$extag  = null;
			$expost = null;
		}

		$sentence = false;
		if ( isset( $query->query_vars['sentence'] ) && ! empty( $query->query_vars['sentence'] ) ) {
			$sentence = true;
		}

		$operator = '';
		if ( function_exists( 'relevanssi_set_operator' ) ) {
			$operator = relevanssi_set_operator( $query );
			$operator = strtoupper( $operator );
		}
		if ( ! in_array( $operator, array( 'OR', 'AND' ), true ) ) {
			$operator = get_option( 'relevanssi_implicit_operator' );
		}
		$query->query_vars['operator'] = $operator;

		$orderby = null;
		$order   = null;
		if ( isset( $query->query_vars['orderby'] ) ) {
			$orderby = $query->query_vars['orderby'];
		}
		if ( isset( $query->query_vars['order'] ) ) {
			$order = $query->query_vars['order'];
		}

		$fields = '';
		if ( ! empty( $query->query_vars['fields'] ) ) {
			if ( 'ids' === $query->query_vars['fields'] ) {
				$fields = 'ids';
			}
			if ( 'id=>parent' === $query->query_vars['fields'] ) {
				$fields = 'id=>parent';
			}
		}

		$by_date = '';
		if ( ! empty( $query->query_vars['by_date'] ) ) {
			if ( preg_match( '/\d+[hdmyw]/', $query->query_vars['by_date'] ) ) {
				// Accepted format is digits followed by h, d, m, y, or w.
				$by_date = $query->query_vars['by_date'];
			}
		}
		// Add synonyms.
		// This is done here so the new terms will get highlighting.
		if ( 'OR' === $operator ) {
			// Synonyms are only used in OR queries.
			$q = relevanssi_add_synonyms( $q );
		}

		$search_params = array(
			'q'                  => $q,
			'tax_query'          => $tax_query,
			'tax_query_relation' => $tax_query_relation,
			'post_query'         => $post_query,
			'parent_query'       => $parent_query,
			'meta_query'         => $meta_query,
			'date_query'         => $date_query,
			'expost'             => $expost,
			'post_type'          => $post_type,
			'post_status'        => $post_status,
			'operator'           => $operator,
			'search_blogs'       => $search_blogs,
			'author'             => $author,
			'orderby'            => $orderby,
			'order'              => $order,
			'fields'             => $fields,
			'sentence'           => $sentence,
			'by_date'            => $by_date,
		);

		$return = relevanssi_search( $search_params );
	}

	$hits = array();
	if ( isset( $return['hits'] ) ) {
		$hits = $return['hits'];
	}
	$q = '';
	if ( isset( $return['query'] ) ) {
		$q = $return['query'];
	}

	$filter_data = array( $hits, $q );
	/**
	 * Filters the founds results.
	 *
	 * One of the key filters for Relevanssi. If you want to modify the results
	 * Relevanssi finds, use this filter.
	 *
	 * @param array $filter_data The index 0 has an array of post objects found in
	 * the search, index 1 has the search query string.
	 *
	 * @return array The return array composition is the same as the parameter array,
	 * but Relevanssi only uses the index 0.
	 */
	$hits_filters_applied = apply_filters( 'relevanssi_hits_filter', $filter_data );
	// array_values() to make sure the $hits array is indexed in numerical order
	// Manipulating the array with array_unique() for example may mess with that.
	$hits = array_values( $hits_filters_applied[0] );

	$query->found_posts = count( $hits );
	if ( ! isset( $query->query_vars['posts_per_page'] ) || 0 === $query->query_vars['posts_per_page'] ) {
		// Assume something sensible to prevent "division by zero error".
		$query->query_vars['posts_per_page'] = -1;
	}
	if ( -1 === $query->query_vars['posts_per_page'] ) {
		$query->max_num_pages = count( $hits );
	} else {
		$query->max_num_pages = ceil( count( $hits ) / $query->query_vars['posts_per_page'] );
	}

	$update_log = get_option( 'relevanssi_log_queries' );
	if ( 'on' === $update_log ) {
		relevanssi_update_log( $q, count( $hits ) );
	}

	$make_excerpts = get_option( 'relevanssi_excerpts' );
	if ( $query->is_admin ) {
		$make_excerpts = false;
	}

	if ( isset( $query->query_vars['paged'] ) && $query->query_vars['paged'] > 0 ) {
		$search_low_boundary = ( $query->query_vars['paged'] - 1 ) * $query->query_vars['posts_per_page'];
	} else {
		$search_low_boundary = 0;
	}

	if ( ! isset( $query->query_vars['posts_per_page'] ) || -1 === $query->query_vars['posts_per_page'] ) {
		$search_high_boundary = count( $hits );
	} else {
		$search_high_boundary = $search_low_boundary + $query->query_vars['posts_per_page'] - 1;
	}

	if ( isset( $query->query_vars['offset'] ) && $query->query_vars['offset'] > 0 ) {
		$search_high_boundary += $query->query_vars['offset'];
		$search_low_boundary  += $query->query_vars['offset'];
	}

	if ( $search_high_boundary > count( $hits ) ) {
		$search_high_boundary = count( $hits );
	}

	for ( $i = $search_low_boundary; $i <= $search_high_boundary; $i++ ) {
		if ( isset( $hits[ intval( $i ) ] ) ) {
			$post = $hits[ intval( $i ) ];
		} else {
			continue;
		}

		if ( null === $post ) {
			// Sometimes you can get a null object.
			continue;
		}

		if ( 'on' === get_option( 'relevanssi_hilite_title' ) && empty( $fields ) ) {
			if ( function_exists( 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
				$post->post_highlighted_title = strip_tags( qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $post->post_title ) );
			} else {
				$post->post_highlighted_title = strip_tags( $post->post_title );
			}
			$highlight = get_option( 'relevanssi_highlight' );
			if ( 'none' !== $highlight ) {
				if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
					$post->post_highlighted_title = relevanssi_highlight_terms( $post->post_highlighted_title, $q );
				}
			}
		}

		if ( 'on' === $make_excerpts && empty( $fields ) ) {
			$post->original_excerpt = $post->post_excerpt;
			$post->post_excerpt     = relevanssi_do_excerpt( $post, $q );
		}

		if ( 'on' === get_option( 'relevanssi_show_matches' ) && empty( $fields ) ) {
			$post_id = $post->ID;
			if ( 'user' === $post->post_type ) {
				$post_id = 'u_' . $post->user_id;
			} elseif ( isset( $post->term_id ) ) {
				$post_id = '**' . $post->post_type . '**' . $post->term_id;
			}
			$post->post_excerpt .= relevanssi_show_matches( $return, $post_id );
		}

		if ( empty( $fields ) && isset( $return['scores'][ $post->ID ] ) ) {
			$post->relevance_score = round( $return['scores'][ $post->ID ], 2 );
		}

		$posts[] = $post;
	}

	$query->posts      = $posts;
	$query->post_count = count( $posts );

	return $posts;
}

/**
 * Limits the search queries to restrict the number of posts handled.
 *
 * @param string $query The MySQL query.
 *
 * @return string The query with the LIMIT parameter added, if necessary.
 */
function relevanssi_limit_filter( $query ) {
	if ( 'on' === get_option( 'relevanssi_throttle', 'on' ) ) {
		$limit = get_option( 'relevanssi_throttle_limit', 500 );
		if ( ! is_numeric( $limit ) ) {
			$limit = 500;
		}
		if ( $limit < 0 ) {
			$limit = 500;
		}
		return $query . " ORDER BY tf DESC LIMIT $limit";
	} else {
		return $query;
	}
}

/**
 * Fetches the list of post types that are excluded from the search.
 *
 * Figures out the post types that are not included in the search.
 *
 * @global WP_Query $wp_query The global WP_Query object.
 *
 * @return string SQL escaped list of excluded post types.
 */
function relevanssi_get_negative_post_type() {
	global $wp_query;

	$negative_post_type      = null;
	$negative_post_type_list = array();

	if ( isset( $wp_query->query_vars['include_attachments'] ) && in_array( $wp_query->query_vars['include_attachments'], array( '0', 'off', 'false' ), true ) ) {
		$negative_post_type_list[] = 'attachment';
	}

	if ( 'on' === get_option( 'relevanssi_respect_exclude' ) ) {
		// If Relevanssi is set to respect exclude_from_search, find out which
		// post types should be excluded from search.
		$pt_1 = get_post_types( array( 'exclude_from_search' => '1' ) );
		$pt_2 = get_post_types( array( 'exclude_from_search' => true ) );

		$negative_post_type_list = array_merge( $negative_post_type_list, $pt_1, $pt_2 );
	}

	// Post types to exclude.
	if ( count( $negative_post_type_list ) > 0 ) {
		$negative_post_types = esc_sql( array_unique( $negative_post_type_list ) );
		$negative_post_type  = null;
		if ( count( $negative_post_types ) ) {
			$negative_post_type = "'" . implode( "', '", $negative_post_types ) . "'";
		}
	}

	return $negative_post_type;
}

/**
 * Processes one tax_query row.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param array   $row                The tax_query row array.
 * @param boolean $is_sub_row         True if this is a subrow.
 * @param string  $global_relation    The global tax_query relation (AND or OR).
 * @param string  $query_restrictions The MySQL query restriction.
 * @param string  $tax_query_relation The tax_query relation.
 * @param array   $term_tax_ids       Array of term taxonomy IDs.
 * @param array   $not_term_tax_ids   Array of excluded term taxonomy IDs.
 * @param array   $and_term_tax_ids   Array of AND term taxonomy IDs.
 *
 * @return array Returns an array where the first item is the updated
 * $query_restrictions, then $term_tax_ids, $not_term_tax_ids, and $and_term_tax_ids.
 */
function relevanssi_process_tax_query_row( $row, $is_sub_row, $global_relation, $query_restrictions, $tax_query_relation, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids ) {
	global $wpdb;

	$local_term_tax_ids     = array();
	$local_not_term_tax_ids = array();
	$local_and_term_tax_ids = array();

	$using_term_tax_id = false;
	if ( ! isset( $row['field'] ) ) {
		$row['field'] = 'term_id'; // In case 'field' is not set, go with the WP default of 'term_id'.
	}
	if ( 'slug' === $row['field'] ) {
		$slug          = $row['terms'];
		$numeric_slugs = array();
		$slug_in       = null;
		if ( is_array( $slug ) ) {
			$slugs   = array();
			$term_id = array();
			foreach ( $slug as $t_slug ) {
				$term = get_term_by( 'slug', $t_slug, $row['taxonomy'] );
				if ( ! $term && is_numeric( $t_slug ) ) {
					$numeric_slugs[] = "'$t_slug'";
				} else {
					if ( isset( $term->term_id ) ) {
						$t_slug    = sanitize_title( $t_slug );
						$term_id[] = $term->term_id;
						$slugs[]   = "'$t_slug'";
					}
				}
			}
			if ( ! empty( $slugs ) ) {
				$slug_in = implode( ',', $slugs );
			}
		} else {
			$term = get_term_by( 'slug', $slug, $row['taxonomy'], OBJECT );
			if ( ! $term && is_numeric( $slug ) ) {
				$numeric_slugs[] = $slug;
			} else {
				if ( isset( $term->term_id ) ) {
					$slug    = sanitize_title( $slug );
					$term_id = $term->term_id;
					$slug_in = "'$slug'";
				}
			}
		}
		if ( ! empty( $slug_in ) ) {
			$row_taxonomy = sanitize_text_field( $row['taxonomy'] );

			$tt_q = "SELECT tt.term_taxonomy_id
				  	FROM $wpdb->term_taxonomy AS tt
				  	LEFT JOIN $wpdb->terms AS t ON (tt.term_id=t.term_id)
				  	WHERE tt.taxonomy = '$row_taxonomy' AND t.slug IN ($slug_in)";
			// Clean: $row_taxonomy is sanitized, each slug in $slug_in is sanitized.
			$term_tax_id = $wpdb->get_col( $tt_q ); // WPCS: unprepared SQL ok.
		}
		if ( ! empty( $numeric_slugs ) ) {
			$row['field'] = 'term_id';
		}
	}
	if ( 'name' === $row['field'] ) {
		$name          = $row['terms'];
		$numeric_names = array();
		$name_in       = null;
		if ( is_array( $name ) ) {
			$names   = array();
			$term_id = array();
			foreach ( $name as $t_name ) {
				$term = get_term_by( 'name', $t_name, $row['taxonomy'] );
				if ( ! $term && is_numeric( $t_names ) ) {
					$numeric_names[] = "'$t_name'";
				} else {
					if ( isset( $term->term_id ) ) {
						$t_name    = sanitize_title( $t_name );
						$term_id[] = $term->term_id;
						$names[]   = "'$t_name'";
					}
				}
			}
			if ( ! empty( $names ) ) {
				$name_in = implode( ',', $names );
			}
		} else {
			$term = get_term_by( 'name', $name, $row['taxonomy'] );
			if ( ! $term && is_numeric( $name ) ) {
				$numeric_slugs[] = $name;
			} else {
				if ( isset( $term->term_id ) ) {
					$name    = sanitize_title( $name );
					$term_id = $term->term_id;
					$name_in = "'$name'";
				}
			}
		}
		if ( ! empty( $name_in ) ) {
			$row_taxonomy = sanitize_text_field( $row['taxonomy'] );

			$tt_q = "SELECT tt.term_taxonomy_id
				  	FROM $wpdb->term_taxonomy AS tt
				  	LEFT JOIN $wpdb->terms AS t ON (tt.term_id=t.term_id)
				  	WHERE tt.taxonomy = '$row_taxonomy' AND t.name IN ($name_in)";
			// Clean: $row_taxonomy is sanitized, each name in $name_in is sanitized.
			$term_tax_id = $wpdb->get_col( $tt_q ); // WPCS: unprepared SQL ok.
		}
		if ( ! empty( $numeric_names ) ) {
			$row['field'] = 'term_id';
		}
	}
	if ( 'id' === $row['field'] || 'term_id' === $row['field'] ) {
		$id      = $row['terms'];
		$term_id = $id;
		if ( is_array( $id ) ) {
			$numeric_values = array();
			foreach ( $id as $t_id ) {
				if ( is_numeric( $t_id ) ) {
					$numeric_values[] = $t_id;
				}
			}
			$id = implode( ',', $numeric_values );
		}
		$row_taxonomy = sanitize_text_field( $row['taxonomy'] );

		if ( ! empty( $id ) ) {
			$tt_q = "SELECT tt.term_taxonomy_id
			FROM $wpdb->term_taxonomy AS tt
			LEFT JOIN $wpdb->terms AS t ON (tt.term_id=t.term_id)
			WHERE tt.taxonomy = '$row_taxonomy' AND t.term_id IN ($id)";
			// Clean: $row_taxonomy is sanitized, $id is checked to be numeric.
			$id_term_tax_id = $wpdb->get_col( $tt_q ); // WPCS: unprepared SQL ok.
			if ( ! empty( $term_tax_id ) && is_array( $term_tax_id ) ) {
				$term_tax_id = array_unique( array_merge( $term_tax_id, $id_term_tax_id ) );
			} else {
				$term_tax_id = $id_term_tax_id;
			}
		}
	}
	if ( 'term_taxonomy_id' === $row['field'] ) {
		$using_term_tax_id = true;
		$id                = $row['terms'];
		$term_tax_id       = $id;
		if ( is_array( $id ) ) {
			$numeric_values = array();
			foreach ( $id as $t_id ) {
				if ( is_numeric( $t_id ) ) {
					$numeric_values[] = $t_id;
				}
			}
			$term_tax_id = implode( ',', $numeric_values );
		}
	}

	if ( ! isset( $row['include_children'] ) || true === $row['include_children'] ) {
		if ( ! $using_term_tax_id && isset( $term_id ) ) {
			if ( ! is_array( $term_id ) ) {
				$term_id = array( $term_id );
			}
		} else {
			if ( ! is_array( $term_tax_id ) ) {
				$term_tax_id = array( $term_tax_id );
				$term_id     = $term_tax_id;
			}
		}
		if ( empty( $term_tax_id ) ) {
			$term_tax_id = array();
		}
		if ( ! is_array( $term_tax_id ) ) {
			$term_tax_id = array( $term_tax_id );
		}
		if ( isset( $term_id ) && is_array( $term_id ) ) {
			foreach ( $term_id as $t_id ) {
				if ( $using_term_tax_id ) {
					$t_term = get_term_by( 'term_taxonomy_id', $t_id, $row['taxonomy'] );
					$t_id   = $t_term->ID;
				}
				$kids = get_term_children( $t_id, $row['taxonomy'] );
				foreach ( $kids as $kid ) {
					$term            = get_term_by( 'id', $kid, $row['taxonomy'] );
					$kid_term_tax_id = relevanssi_get_term_tax_id( $kid, $row['taxonomy'] );
					$term_tax_id[]   = $kid_term_tax_id;
				}
			}
		}
	}

	$term_tax_id = array_unique( $term_tax_id );
	if ( ! empty( $term_tax_id ) ) {
		$n           = count( $term_tax_id );
		$term_tax_id = implode( ',', $term_tax_id );

		$tq_operator = 'IN'; // Assuming the default operator "IN", unless something else is provided.
		if ( isset( $row['operator'] ) ) {
			$tq_operator = strtoupper( $row['operator'] );
		}
		if ( ! in_array( $tq_operator, array( 'IN', 'NOT IN', 'AND' ), true ) ) {
			$tq_operator = 'IN';
		}
		if ( 'and' === $tax_query_relation ) {
			if ( 'AND' === $tq_operator ) {
				$query_restrictions .= " AND relevanssi.doc IN (
					SELECT ID FROM $wpdb->posts WHERE 1=1
					AND (
						SELECT COUNT(1)
						FROM $wpdb->term_relationships AS tr
						WHERE tr.term_taxonomy_id IN ($term_tax_id)
						AND tr.object_id = $wpdb->posts.ID ) = $n
					)";
				// Clean: $term_tax_id and $n are Relevanssi-generated.
			} else {
				$query_restrictions .= " AND relevanssi.doc $tq_operator (SELECT DISTINCT(tr.object_id) FROM $wpdb->term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($term_tax_id))";
				// Clean: all variables are Relevanssi-generated.
			}
		} else {
			if ( 'IN' === $tq_operator ) {
				$local_term_tax_ids[] = $term_tax_id;
			}
			if ( 'NOT IN' === $tq_operator ) {
				$local_not_term_tax_ids[] = $term_tax_id;
			}
			if ( 'AND' === $tq_operator ) {
				$local_and_term_tax_ids[] = $term_tax_id;
			}
		}
	} else {
		global $wp_query;
		$wp_query->is_category = false;
	}

	if ( $is_sub_row && 'and' === $global_relation && 'or' === $tax_query_relation ) {
		$local_term_tax_ids     = array_unique( $local_term_tax_ids );
		$local_not_term_tax_ids = array_unique( $local_not_term_tax_ids );
		$local_and_term_tax_ids = array_unique( $local_and_term_tax_ids );
		if ( count( $local_term_tax_ids ) > 0 ) {
			$local_term_tax_ids  = implode( ',', $local_term_tax_ids );
			$query_restrictions .= " AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id) FROM $wpdb->term_relationships AS tr
		    	WHERE tr.term_taxonomy_id IN ($local_term_tax_ids))";
			// Clean: all variables are Relevanssi-generated.
		}
		if ( count( $local_not_term_tax_ids ) > 0 ) {
			$local_not_term_tax_ids = implode( ',', $local_not_term_tax_ids );
			$query_restrictions    .= " AND relevanssi.doc NOT IN (SELECT DISTINCT(tr.object_id) FROM $wpdb->term_relationships AS tr
		    	WHERE tr.term_taxonomy_id IN ($local_not_term_tax_ids))";
			// Clean: all variables are Relevanssi-generated.
		}
		if ( count( $local_and_term_tax_ids ) > 0 ) {
			$local_and_term_tax_ids = implode( ',', $local_and_term_tax_ids );
			$n                      = count( explode( ',', $local_and_term_tax_ids ) );
			$query_restrictions    .= " AND relevanssi.doc IN (
				SELECT ID FROM $wpdb->posts WHERE 1=1
				AND (
					SELECT COUNT(1)
					FROM $wpdb->term_relationships AS tr
					WHERE tr.term_taxonomy_id IN ($local_and_term_tax_ids)
					AND tr.object_id = $wpdb->posts.ID ) = $n
				)";
			// Clean: all variables are Relevanssi-generated.
		}
	}

	$copy_term_tax_ids = false;
	if ( ! $is_sub_row ) {
		$copy_term_tax_ids = true;
	}
	if ( $is_sub_row && 'or' === $global_relation ) {
		$copy_term_tax_ids = true;
	}

	if ( $copy_term_tax_ids ) {
		$term_tax_ids     = array_merge( $term_tax_ids, $local_term_tax_ids );
		$not_term_tax_ids = array_merge( $not_term_tax_ids, $local_not_term_tax_ids );
		$and_term_tax_ids = array_merge( $and_term_tax_ids, $local_and_term_tax_ids );
	}

	return array( $query_restrictions, $term_tax_ids, $not_term_tax_ids, $and_term_tax_ids );
}
