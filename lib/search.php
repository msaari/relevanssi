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
		$search_ok           = false; // But if this is an admin search, reconsider.
		$admin_search_option = get_option( 'relevanssi_admin_search' );
		if ( 'on' === $admin_search_option ) {
			$search_ok = true; // Yes, we can search!
		}
	}

	if ( $query->is_admin && empty( $query->query_vars['s'] ) ) {
		$search_ok = false; // No search term.
	}

	if ( $query->get( 'relevanssi' ) ) {
		$search_ok = true; // Manual override, always search.
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
 *
 * @param array $args Array of arguments.
 *
 * @return array An array of return values.
 */
function relevanssi_search( $args ) {
	global $wpdb;

	/**
	 * Filters the search parameters.
	 *
	 * @param array The search parameters.
	 */
	$filtered_args = apply_filters( 'relevanssi_search_filters', $args );
	$meta_query    = $filtered_args['meta_query'];
	$operator      = $filtered_args['operator'];
	$orderby       = $filtered_args['orderby'];
	$order         = $filtered_args['order'];
	$fields        = $filtered_args['fields'];

	$hits = array();

	$query_data         = relevanssi_process_query_args( $filtered_args );
	$query_restrictions = $query_data['query_restrictions'];
	$query_join         = $query_data['query_join'];
	$q                  = $query_data['query_query'];
	$q_no_synonyms      = $query_data['query_no_synonyms'];
	$phrase_queries     = $query_data['phrase_queries'];

	$min_length = get_option( 'relevanssi_min_word_length' );

	/**
	 * Filters whether stopwords are removed from titles.
	 *
	 * @param boolean If true, remove stopwords from titles.
	 */
	$remove_stopwords = apply_filters( 'relevanssi_remove_stopwords_in_titles', true );

	$terms['terms'] = array_keys( relevanssi_tokenize( $q, $remove_stopwords, $min_length, 'search_query' ) );

	$terms['original_terms'] = $q_no_synonyms !== $q
		? array_keys( relevanssi_tokenize( $q_no_synonyms, $remove_stopwords, $min_length, 'search_query' ) )
		: $terms['terms'];

	if ( has_filter( 'relevanssi_stemmer' ) ) {
		do_action( 'relevanssi_disable_stemmer' );
		$terms['original_terms'] = array_keys( relevanssi_tokenize( $q_no_synonyms, $remove_stopwords, $min_length, 'search_query' ) );
		do_action( 'relevanssi_enable_stemmer' );
	}

	if ( function_exists( 'relevanssi_process_terms' ) ) {
		$process_terms_results = relevanssi_process_terms( $terms['terms'], $q );
		$query_restrictions   .= $process_terms_results['query_restrictions'];
		$terms['terms']        = $process_terms_results['terms'];
	}

	$no_terms = false;
	if ( count( $terms['terms'] ) < 1 && empty( $q ) ) {
		$no_terms       = true;
		$terms['terms'] = array( 'term' );
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
	if ( ! $query_restrictions ) {
		$query_restrictions = '';
	}

	/**
	 * Filters the meta query JOIN for the Relevanssi search query.
	 *
	 * Somewhat equivalent to the 'posts_join' filter.
	 *
	 * @param string The JOINed query.
	 */
	$query_join = apply_filters( 'relevanssi_join', $query_join );

	// Get the count from the options.
	$doc_count = get_option( 'relevanssi_doc_count', 0 );
	if ( ! $doc_count || $doc_count < 1 ) {
		$doc_count = relevanssi_update_doc_count();
		if ( ! $doc_count || $doc_count < 1 ) {
			// No value available for some reason, use a random value.
			$doc_count = 100;
		}
	}

	$match_arrays        = relevanssi_initialize_match_arrays();
	$term_hits           = array();
	$include_these_posts = array();
	$include_these_items = array();
	$doc_weight          = array();
	$total_hits          = 0;
	$no_matches          = true;
	$search_again        = false;
	$post_type_weights   = get_option( 'relevanssi_post_type_weights' );
	$fuzzy               = get_option( 'relevanssi_fuzzy' );

	do {
		$df_counts = relevanssi_generate_df_counts(
			$terms['terms'],
			array(
				'no_terms'           => $no_terms,
				'operator'           => $operator,
				'phrase_queries'     => $phrase_queries,
				'query_join'         => $query_join,
				'query_restrictions' => $query_restrictions,
				'search_again'       => $search_again,
			)
		);

		foreach ( $df_counts as $term => $df ) {
			$this_query_restrictions = relevanssi_add_phrase_restrictions(
				$query_restrictions,
				$phrase_queries,
				$term,
				$operator
			);

			$query   = relevanssi_generate_search_query( $term, $search_again, $no_terms, $query_join, $this_query_restrictions );
			$matches = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

			if ( count( $matches ) < 1 ) {
				continue;
			}

			$no_matches = false;
			relevanssi_add_include_matches(
				$matches,
				array(
					'posts' => $include_these_posts,
					'items' => $include_these_items,
				),
				array(
					'term'         => $term,
					'search_again' => $search_again,
					'no_terms'     => $no_terms,
				)
			);

			relevanssi_populate_array( $matches );

			$total_hits += count( $matches );

			$idf = log( $doc_count + 1 / ( 1 + $df ) );
			$idf = $idf * $idf; // Adjustment to increase the value of IDF.
			if ( $idf < 1 ) {
				$idf = 1;
			}

			foreach ( $matches as $match ) {
				$match->doc    = relevanssi_adjust_match_doc( $match );
				$match->tf     = relevanssi_calculate_tf( $match, $post_type_weights );
				$match->weight = relevanssi_calculate_weight( $match, $idf, $post_type_weights, $q );

				/**
				 * Filters the Relevanssi post matches.
				 *
				 * This powerful filter lets you modify the $match objects,
				 * which are used to calculate the weight of the documents. The
				 * object has attributes which contain the number of hits in
				 * different categories.
				 *
				 * Post ID is $match->doc, term frequency (TF) is
				 * $match->tf and the total weight is in $match->weight. The
				 * filter is also passed $idf, which is the inverse document
				 * frequency (IDF). The weight is calculated as TF * IDF, which
				 * means you may need the IDF, if you wish to recalculate the
				 * weight for some reason. The third parameter, $term, contains
				 * the search term.
				 *
				 * @param object $match The match object, with includes all
				 * the different categories of post matches.
				 * @param int    $idf   The inverse document frequency, in
				 * case you want to recalculate TF * IDF weights.
				 * @param string $term  The search term.
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
					relevanssi_update_term_hits( $term_hits, $match_arrays, $match, $term );

					$doc_terms[ $match->doc ][ $term ] = true; // Count how many terms are matched to a doc.
					if ( ! isset( $doc_weight[ $match->doc ] ) ) {
						$doc_weight[ $match->doc ] = 0;
					}
					$doc_weight[ $match->doc ] += $match->weight;
					// For AND searches, add the posts to the $include_these lists, so that
					// nothing is missed.
					if ( is_numeric( $match->doc ) && 'AND' === $operator ) {
						// This is to weed out taxonomies and users (t_XXX, u_XXX).
						$include_these_posts[ $match->doc ] = true;
					} elseif ( 0 !== intval( $match->item ) && 'AND' === $operator ) {
						$include_these_items[ $match->item ] = true;
					}
				}
			}
		}

		if ( $search_again ) {
			$search_again = false;
		} elseif ( $no_matches && ! $search_again && 'sometimes' === $fuzzy ) {
			$search_again = true;
		}

		$params = array(
			'doc_weight'         => $doc_weight,
			'no_matches'         => $no_matches,
			'operator'           => $operator,
			'phrase_queries'     => $phrase_queries,
			'query_join'         => $query_join,
			'query_restrictions' => $query_restrictions,
			'search_again'       => $search_again,
			'terms'              => $terms,
		);
		/**
		 * Filters the parameters for fallback search.
		 *
		 * If you want to make Relevanssi search again with different
		 * parameters, you can use this filter hook to adjust the parameters.
		 * Set $params['search_again'] to true to make Relevanssi do a new search.
		 *
		 * @param array The search parameters.
		 */
		$params             = apply_filters( 'relevanssi_search_again', $params );
		$doc_weight         = $params['doc_weight'];
		$no_matches         = $params['no_matches'];
		$operator           = $params['operator'];
		$phrase_queries     = $params['phrase_queries'];
		$query_join         = $params['query_join'];
		$query_restrictions = $params['query_restrictions'];
		$search_again       = $params['search_again'];
		$terms              = $params['terms'];
	} while ( $search_again );

	if ( ! $remove_stopwords ) {
		$strip_stops       = true;
		$terms['no_stops'] = array_keys( relevanssi_tokenize( implode( ' ', $terms['terms'] ), $strip_stops, $min_length, 'search_query' ) );

		if ( $q !== $q_no_synonyms ) {
			$terms['original_terms_no_stops'] = array_keys( relevanssi_tokenize( implode( ' ', $terms['original_terms'] ), $strip_stops, $min_length, 'search_query' ) );
		} else {
			$terms['original_terms_no_stops'] = $terms['no_stops'];
		}

		if ( has_filter( 'relevanssi_stemmer' ) ) {
			do_action( 'relevanssi_disable_stemmer' );
			$terms['original_terms_no_stops'] = array_keys( relevanssi_tokenize( implode( ' ', $terms['original_terms'] ), $strip_stops, $min_length, 'search_query' ) );
			do_action( 'relevanssi_enable_stemmer' );
		} else {
			$terms['original_terms_no_stops'] = $terms['no_stops'];
		}
	} else {
		$terms['no_stops']                = $terms['terms'];
		$terms['original_terms_no_stops'] = $terms['original_terms'];
	}
	$total_terms = count( $terms['original_terms_no_stops'] );

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

	$missing_terms = array();

	if ( isset( $doc_weight ) && count( $doc_weight ) > 0 ) {
		arsort( $doc_weight );
		$i = 0;
		foreach ( $doc_weight as $doc => $weight ) {
			if ( count( $doc_terms[ $doc ] ) < $total_terms && 'AND' === $operator ) {
				// AND operator in action:
				// doc didn't match all terms, so it's discarded.
				continue;
			}
			$doc_terms_for_doc = array_keys( $doc_terms[ $doc ] );
			$original_terms    = array_values( $terms['original_terms_no_stops'] );
			if ( count( $doc_terms[ $doc ] ) < $total_terms ) {
				if ( $q !== $q_no_synonyms ) {
					$missing_terms[ $doc ] = array_diff(
						$original_terms,
						relevanssi_replace_synonyms_in_terms( $doc_terms_for_doc )
					);
					if ( count( $missing_terms[ $doc ] ) + count( relevanssi_replace_stems_in_terms( $doc_terms_for_doc ) ) !== count( $terms['original_terms'] ) ) {
						$missing_terms[ $doc ] = array_diff(
							$original_terms,
							$doc_terms_for_doc
						);
					}
				} else {
					$missing_terms[ $doc ] = array_diff(
						$original_terms,
						$doc_terms_for_doc
					);
				}
			}

			if ( ! empty( $fields ) ) {
				if ( 'ids' === $fields ) {
					$hits[ intval( $i ) ] = $doc;
				}
				if ( 'id=>parent' === $fields ) {
					$hits[ intval( $i ) ] = relevanssi_generate_post_parent( $doc );
				}
				if ( 'id=>type' === $fields ) {
					$hits[ intval( $i ) ] = relevanssi_generate_id_type( $doc );
				}
			} else {
				$hits[ intval( $i ) ]                  = relevanssi_get_post( $doc );
				$hits[ intval( $i ) ]->relevance_score = round( $weight, 2 );

				if ( isset( $missing_terms[ $doc ] ) ) {
					$hits[ intval( $i ) ]->missing_terms = $missing_terms[ $doc ];
				}
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

			$or_args['q_no_synonyms'] = $q;
			$or_args['q']             = relevanssi_add_synonyms( $q );
			$return                   = relevanssi_search( $or_args );

			$hits                        = $return['hits'];
			$match_arrays['body']        = $return['body_matches'];
			$match_arrays['title']       = $return['title_matches'];
			$match_arrays['tag']         = $return['tag_matches'];
			$match_arrays['category']    = $return['category_matches'];
			$match_arrays['taxonomy']    = $return['taxonomy_matches'];
			$match_arrays['comment']     = $return['comment_matches'];
			$match_arrays['link']        = $return['link_matches'];
			$match_arrays['author']      = $return['author_matches'];
			$match_arrays['customfield'] = $return['customfield_matches'];
			$match_arrays['mysqlcolumn'] = $return['mysqlcolumn_matches'];
			$match_arrays['excerpt']     = $return['excerpt_matches'];
			$term_hits                   = $return['term_hits'];
			$doc_weight                  = $return['doc_weights'];
			$q                           = $return['query'];
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
			$return                      = $params['return'];
			$hits                        = $return['hits'];
			$match_arrays['body']        = $return['body_matches'];
			$match_arrays['title']       = $return['title_matches'];
			$match_arrays['tag']         = $return['tag_matches'];
			$match_arrays['category']    = $return['category_matches'];
			$match_arrays['taxonomy']    = $return['taxonomy_matches'];
			$match_arrays['comment']     = $return['comment_matches'];
			$match_arrays['link']        = $return['link_matches'];
			$match_arrays['author']      = $return['author_matches'];
			$match_arrays['customfield'] = $return['customfield_matches'];
			$match_arrays['mysqlcolumn'] = $return['mysqlcolumn_matches'];
			$match_arrays['excerpt']     = $return['excerpt_matches'];
			$term_hits                   = $return['term_hits'];
			$doc_weight                  = $return['doc_weights'];
			$q                           = $return['query'];
		}
	}

	relevanssi_sort_results( $hits, $orderby, $order, $meta_query );

	$return = array(
		'hits'                => $hits,
		'body_matches'        => $match_arrays['body'],
		'title_matches'       => $match_arrays['title'],
		'tag_matches'         => $match_arrays['tag'],
		'category_matches'    => $match_arrays['category'],
		'comment_matches'     => $match_arrays['comment'],
		'taxonomy_matches'    => $match_arrays['taxonomy'],
		'link_matches'        => $match_arrays['link'],
		'customfield_matches' => $match_arrays['customfield'],
		'mysqlcolumn_matches' => $match_arrays['mysqlcolumn'],
		'author_matches'      => $match_arrays['author'],
		'excerpt_matches'     => $match_arrays['excerpt'],
		'term_hits'           => $term_hits,
		'query'               => $q,
		'doc_weights'         => $doc_weight,
		'query_no_synonyms'   => $q_no_synonyms,
		'missing_terms'       => $missing_terms,
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
 * @global boolean $relevanssi_active     If true, Relevanssi is currently
 * doing a search.
 * @global boolean $relevanssi_test_admin If true, assume this is an admin
 * search (because we can't adjust WP_ADMIN constant).
 *
 * @param WP_Query $query A WP_Query object, passed as a reference. Relevanssi will
 * put the posts found in $query->posts, and also sets $query->post_count.
 *
 * @return array The found posts, an array of post objects.
 */
function relevanssi_do_query( &$query ) {
	global $relevanssi_active, $relevanssi_test_admin;
	$relevanssi_active = true;

	$posts = array();

	$q = trim( stripslashes( relevanssi_strtolower( $query->query_vars['s'] ) ) );

	$did_multisite_search = false;
	if ( is_multisite() ) {
		if ( function_exists( 'relevanssi_is_multisite_search' ) ) {
			$searchblogs = relevanssi_is_multisite_search( $query );
			if ( $searchblogs ) {
				if ( function_exists( 'relevanssi_compile_multi_args' )
					&& function_exists( 'relevanssi_search_multi' ) ) {
					$multi_args = relevanssi_compile_multi_args( $query, $searchblogs, $q );
					$return     = relevanssi_search_multi( $multi_args );
				}
				$did_multisite_search = true;
			}
		}
	}
	$search_params = array();
	if ( ! $did_multisite_search ) {
		$search_params = relevanssi_compile_search_args( $query, $q );
		$return        = relevanssi_search( $search_params );
	}

	$hits          = $return['hits'] ?? array();
	$q             = $return['query'] ?? '';
	$q_no_synonyms = $return['query_no_synonyms'] ?? '';

	$filter_data = array( $hits, $q );
	/**
	 * Filters the founds results.
	 *
	 * One of the key filters for Relevanssi. If you want to modify the results
	 * Relevanssi finds, use this filter.
	 *
	 * @param array $filter_data The index 0 has an array of post objects (or
	 * post IDs, or parent=>ID pairs, depending on the `fields` parameter) found
	 * in the search, index 1 has the search query string.
	 *
	 * @return array The return array composition is the same as the parameter
	 * array, but Relevanssi only uses the index 0.
	 */
	$hits_filters_applied = apply_filters( 'relevanssi_hits_filter', $filter_data );
	// array_values() to make sure the $hits array is indexed in numerical order
	// Manipulating the array with array_unique() for example may mess with that.
	$hits = array_values( $hits_filters_applied[0] );

	$hits_count         = count( $hits );
	$query->found_posts = $hits_count;
	if ( ! isset( $query->query_vars['posts_per_page'] ) || 0 === $query->query_vars['posts_per_page'] ) {
		// Assume something sensible to prevent "division by zero error".
		$query->query_vars['posts_per_page'] = -1;
	}
	if ( -1 === $query->query_vars['posts_per_page'] ) {
		$query->max_num_pages = 1;
	} else {
		$query->max_num_pages = ceil( $hits_count / $query->query_vars['posts_per_page'] );
	}

	$update_log = get_option( 'relevanssi_log_queries' );
	if ( 'on' === $update_log ) {
		/**
		 * Filters the query.
		 *
		 * By default, Relevanssi logs the original query without the added
		 * synonyms. This filter hook gets the query with the synonyms added as
		 * a second parameter, so if you wish, you can log the query with the
		 * synonyms added.
		 *
		 * @param string   $q_no_synonyms The query string without synonyms.
		 * @param string   $q             The query string with synonyms.
		 * @param WP_Query $query         The WP_Query that triggered the
		 * logging.
		 */
		$query_string = apply_filters(
			'relevanssi_log_query',
			$q_no_synonyms,
			$q,
			$query
		);
		relevanssi_update_log( $query_string, $hits_count );
	}

	$make_excerpts = 'on' === get_option( 'relevanssi_excerpts' ) ? true : false;
	if ( $relevanssi_test_admin || ( $query->is_admin && ! defined( 'DOING_AJAX' ) ) ) {
		$make_excerpts = false;
	}

	list( $search_low_boundary, $search_high_boundary ) = relevanssi_get_boundaries( $query );

	$highlight_title = 'on' === get_option( 'relevanssi_hilite_title' ) ? true : false;
	$show_matches    = 'on' === get_option( 'relevanssi_show_matches' ) ? true : false;
	$return_posts    = empty( $search_params['fields'] );

	$hits_to_show = array_slice( $hits, $search_low_boundary, $search_high_boundary - $search_low_boundary + 1 );
	/**
	 * Filters the displayed hits.
	 *
	 * Similar to 'relevanssi_hits_filter', but only filters the posts that
	 * are displayed on the search results page. Don't make big changes here.
	 *
	 * @param array    $hits_to_show An array of post objects.
	 * @param WP_Query $query        The WP Query object.
	 *
	 * @return array An array of post objects.
	 */
	foreach ( apply_filters( 'relevanssi_hits_to_show', $hits_to_show, $query ) as $post ) {
		if ( $highlight_title && $return_posts ) {
			relevanssi_highlight_post_title( $post, $q );
		}
		if ( $make_excerpts && $return_posts ) {
			relevanssi_add_excerpt( $post, $q );
		}
		if ( $return_posts ) {
			relevanssi_add_matches( $post, $return );
		}
		if ( $show_matches && $return_posts ) {
			$post->post_excerpt .= relevanssi_show_matches( $post );
		}

		$posts[] = $post;
	}

	$query->posts      = $posts;
	$query->post_count = count( $posts );

	/**
	 * If true, Relevanssi adds a list of all post IDs found in the query
	 * object in $query->relevanssi_all_results.
	 *
	 * @param boolean If true, enable the feature. Default false.
	 */
	if ( apply_filters( 'relevanssi_add_all_results', false ) ) {
		$query->relevanssi_all_results = wp_list_pluck( $hits, 'ID' );
	}

	$relevanssi_active = false;

	return $posts;
}

/**
 * Limits the search queries to restrict the number of posts handled.
 *
 * Tested.
 *
 * @param string $query The MySQL query.
 *
 * @return string The query with the LIMIT parameter added, if necessary.
 */
function relevanssi_limit_filter( $query ) {
	$termless_search = strstr( $query, 'relevanssi.term = relevanssi.term' );

	if ( $termless_search || 'on' === get_option( 'relevanssi_throttle', 'on' ) ) {
		$limit = get_option( 'relevanssi_throttle_limit', 500 );
		if ( ! is_numeric( $limit ) ) {
			$limit = 500;
		}
		if ( $limit < 0 ) {
			$limit = 500;
		}
		if ( $termless_search ) {
			$query = $query . " GROUP BY doc, item, type ORDER BY doc ASC LIMIT $limit";
		} else {
			$query = $query . " ORDER BY tf DESC LIMIT $limit";
		}
	}
	return $query;
}

/**
 * Fetches the list of post types that are excluded from the search.
 *
 * Figures out the post types that are not included in the search. Only includes
 * the post types that are actually indexed.
 *
 * @param string $include_attachments Whether to include attachments or not.
 *
 * @return string SQL escaped list of excluded post types.
 */
function relevanssi_get_negative_post_type( $include_attachments ) {
	$negative_post_type      = null;
	$negative_post_type_list = array();

	if ( isset( $include_attachments ) && in_array( $include_attachments, array( '0', 'off', 'false', false ), true ) ) {
		$negative_post_type_list[] = 'attachment';
	}

	$front_end = true;
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		$front_end = false;
	}
	if ( 'on' === get_option( 'relevanssi_respect_exclude' ) && $front_end ) {
		// If Relevanssi is set to respect exclude_from_search, find out which
		// post types should be excluded from search.
		$pt_1 = get_post_types( array( 'exclude_from_search' => '1' ) );
		$pt_2 = get_post_types( array( 'exclude_from_search' => true ) );

		$negative_post_type_list = array_merge( $negative_post_type_list, $pt_1, $pt_2 );
	}

	$indexed_post_types      = get_option( 'relevanssi_index_post_types', array() );
	$negative_post_type_list = array_intersect(
		$negative_post_type_list,
		$indexed_post_types
	);

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
 * Generates the WHERE condition for terms.
 *
 * Trims the term, escapes it and places it in the template.
 *
 * Tested.
 *
 * @param string  $term            The search term.
 * @param boolean $force_fuzzy     If true, force fuzzy search. Default false.
 * @param boolean $no_terms        If true, no search term is used. Default false.
 * @param string  $option_override If set, won't read the value from the
 * 'relevanssi_fuzzy' option but will use this instead. Used in multisite searching.
 * Default null.
 *
 * @return string The template with the term in place.
 */
function relevanssi_generate_term_where( $term, $force_fuzzy = false, $no_terms = false, $option_override = null ) {
	global $wpdb;

	$fuzzy = get_option( 'relevanssi_fuzzy' );
	if ( $option_override &&
	in_array( $option_override, array( 'always', 'sometimes', 'never' ), true ) ) {
		$fuzzy = $option_override;
	}

	/**
	 * Filters the partial matching search query.
	 *
	 * By default partial matching matches the beginnings and the ends of the
	 * words. If you want it to match inside words, add a function to this
	 * hook that returns '(relevanssi.term LIKE '%#term#%')'.
	 *
	 * @param string The partial matching query.
	 * @param string $term The search term.
	 */
	$fuzzy_query = apply_filters(
		'relevanssi_fuzzy_query',
		"(relevanssi.term LIKE '#term#%' OR relevanssi.term_reverse LIKE CONCAT(REVERSE('#term#'), '%')) ",
		$term
	);
	$basic_query = " relevanssi.term = '#term#' ";

	if ( 'always' === $fuzzy || $force_fuzzy ) {
		$term_where_template = $fuzzy_query;
	} else {
		$term_where_template = $basic_query;
	}
	if ( $no_terms ) {
		$term_where_template = ' relevanssi.term = relevanssi.term ';
	}

	$term = trim( $term ); // Numeric search terms will start with a space.

	if ( relevanssi_strlen( $term ) < 2 ) {
		/**
		 * Allows the use of one letter search terms.
		 *
		 * Return false to allow one letter searches.
		 *
		 * @param boolean True, if search term is one letter long and will be blocked.
		 */
		if ( apply_filters( 'relevanssi_block_one_letter_searches', true ) ) {
			return null;
		}
		// No fuzzy matching for one-letter search terms.
		$term_where_template = $basic_query;
	}

	$term = esc_sql( $term );

	if ( false !== strpos( $term_where_template, 'LIKE' ) ) {
		$term = $wpdb->esc_like( $term );
	}

	$term_where = str_replace( '#term#', $term, $term_where_template );

	/**
	 * Filters the term WHERE condition for the Relevanssi MySQL query.
	 *
	 * @param string $term_where The WHERE condition for the terms.
	 * @param string $term       The search term.
	 */
	return apply_filters( 'relevanssi_term_where', $term_where, $term );
}

/**
 * Counts the taxonomy score for a match.
 *
 * Uses the taxonomy_detail object to count the taxonomy score for a match.
 * If there's a taxonomy weight in $post_type_weights, that is used, otherwise
 * assume weight 1.
 *
 * Tested.
 *
 * @since 2.1.5
 *
 * @param object $match             The match object, used as a reference.
 * @param array  $post_type_weights The post type and taxonomy weights array.
 */
function relevanssi_taxonomy_score( &$match, $post_type_weights ) {
	$match->taxonomy_score  = 0;
	$match->taxonomy_detail = json_decode( $match->taxonomy_detail );
	if ( is_object( $match->taxonomy_detail ) ) {
		foreach ( $match->taxonomy_detail as $tax => $count ) {
			if ( empty( $post_type_weights[ 'post_tagged_with_' . $tax ] ) ) {
				$match->taxonomy_score += $count * 1;
			} else {
				$match->taxonomy_score += $count * $post_type_weights[ 'post_tagged_with_' . $tax ];
			}
		}
	}
}

/**
 * Collects the search parameters from the WP_Query object.
 *
 * @global boolean $relevanssi_test_admin If true, assume this is an admin
 * search.
 *
 * @param object $query The WP Query object used as a source.
 * @param string $q     The search query.
 *
 * @return array The search parameters.
 */
function relevanssi_compile_search_args( $query, $q ) {
	global $relevanssi_test_admin;

	$search_params = relevanssi_compile_common_args( $query );

	$tax_query = array();
	/**
	 * Filters the default tax_query relation.
	 *
	 * @param string The default relation, default 'AND'.
	 */
	$tax_query_relation = apply_filters( 'relevanssi_default_tax_query_relation', 'AND' );
	$terms_found        = false;
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
					if ( isset( $tax_query_row['terms'] ) ) {
						$terms_found = true;
					}
					$tax_query[] = $tax_query_row;
				}
			}
		}
	}
	if ( ! $terms_found ) {
		$cat = false;
		if ( isset( $query->query_vars['cats'] ) ) {
			$cat = $query->query_vars['cats'];
			if ( is_array( $cat ) ) {
				$cat = implode( ',', $cat );
			}
		}
		if ( empty( $cat ) ) {
			$cat = get_option( 'relevanssi_cat' );
		}
		if ( $cat ) {
			$cat         = explode( ',', $cat );
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $cat,
				'operator' => 'IN',
			);
		}
		$excat = get_option( 'relevanssi_excat' );

		if ( $relevanssi_test_admin || ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) {
			$excat = null;
		}

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
			if ( is_array( $tag ) ) {
				$tag = implode( ',', $tag );
			}
			if ( false !== strpos( $tag, '+' ) ) {
				$tag      = explode( '+', $tag );
				$operator = 'AND';
			} else {
				$tag      = explode( ',', $tag );
				$operator = 'OR';
			}
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'id',
				'terms'    => $tag,
				'operator' => $operator,
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
		$extag = get_option( 'relevanssi_extag' );
		if ( ! empty( $extag ) && '0' !== $extag ) {
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'id',
				'terms'    => $extag,
				'operator' => 'NOT IN',
			);
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
	if ( isset( $query->query_vars['p'] ) && $query->query_vars['p'] ) {
		$post_query = array( 'in' => array( $query->query_vars['p'] ) );
	}
	if ( isset( $query->query_vars['page_id'] ) && $query->query_vars['page_id'] ) {
		$post_query = array( 'in' => array( $query->query_vars['page_id'] ) );
	}
	if ( isset( $query->query_vars['post__in'] ) && is_array( $query->query_vars['post__in'] ) && ! empty( $query->query_vars['post__in'] ) ) {
		$post_query = array( 'in' => $query->query_vars['post__in'] );
	}
	if ( isset( $query->query_vars['post__not_in'] ) && is_array( $query->query_vars['post__not_in'] ) && ! empty( $query->query_vars['post__not_in'] ) ) {
		$post_query = array( 'not in' => $query->query_vars['post__not_in'] );
	}

	$parent_query = array();
	if ( isset( $query->query_vars['post_parent'] ) && '' !== $query->query_vars['post_parent'] ) {
		$parent_query = array( 'parent in' => array( (int) $query->query_vars['post_parent'] ) );
	}
	if ( isset( $query->query_vars['post_parent__in'] ) && is_array( $query->query_vars['post_parent__in'] ) && ! empty( $query->query_vars['post_parent__in'] ) ) {
		$parent_query = array( 'parent in' => $query->query_vars['post_parent__in'] );
	}
	if ( isset( $query->query_vars['post_parent__not_in'] ) && is_array( $query->query_vars['post_parent__not_in'] ) && ! empty( $query->query_vars['post_parent__not_in'] ) ) {
		$parent_query = array( 'parent not in' => $query->query_vars['post_parent__not_in'] );
	}

	$expost = get_option( 'relevanssi_exclude_posts' );
	if ( $relevanssi_test_admin || ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) {
		$expost = null;
	}

	$fields = '';
	if ( ! empty( $query->query_vars['fields'] ) ) {
		if ( 'ids' === $query->query_vars['fields'] ) {
			$fields = 'ids';
		}
		if ( 'id=>parent' === $query->query_vars['fields'] ) {
			$fields = 'id=>parent';
		}
		if ( 'id=>type' === $query->query_vars['fields'] ) {
			$fields = 'id=>type';
		}
	}

	if ( function_exists( 'relevanssi_extract_specifier' ) ) {
		$q = relevanssi_extract_specifier( $q );
	}

	// Add synonyms.
	// This is done here so the new terms will get highlighting.
	$q_no_synonyms = $q;
	if ( 'OR' === $search_params['operator'] ) {
		// Synonyms are only used in OR queries.
		$q = relevanssi_add_synonyms( $q );
	}

	$query->query_vars['operator'] = $search_params['operator'];

	$search_params = array_merge(
		$search_params,
		array(
			'q'                  => $q,
			'q_no_synonyms'      => $q_no_synonyms,
			'tax_query'          => $tax_query,
			'tax_query_relation' => $tax_query_relation,
			'post_query'         => $post_query,
			'parent_query'       => $parent_query,
			'expost'             => $expost,
			'author'             => $author,
			'fields'             => $fields,
		)
	);

	/**
	 * Filters the Relevanssi search parameters after compiling.
	 *
	 * Relevanssi picks up the search parameters from the WP_Query query
	 * variables and collects them in an array you can filter here.
	 *
	 * @param array    $search_params The search parameters.
	 * @param WP_Query $query         The full WP_Query object.
	 *
	 * @return array The filtered parameters.
	 */
	$search_params = apply_filters(
		'relevanssi_search_params',
		$search_params,
		$query
	);

	return $search_params;
}

/**
 * Generates a WP_Date_Query from the query date variables.
 *
 * First checks $query->date_query, if that doesn't exist then looks at the
 * other date parameters to construct a date query.
 *
 * @param WP_Query $query The query object.
 *
 * @return WP_Date_Query|boolean The date query object or false, if no date
 * parameters can be parsed.
 */
function relevanssi_wp_date_query_from_query_vars( $query ) {
	$date_query = false;
	if ( ! empty( $query->date_query ) ) {
		if ( is_object( $query->date_query ) && 'WP_Date_Query' === get_class( $query->date_query ) ) {
			$date_query = $query->date_query;
		} else {
			$date_query = new WP_Date_Query( $query->date_query );
		}
	} elseif ( ! empty( $query->query_vars['date_query'] ) ) {
		// The official date query is in $query->date_query, but this allows
		// users to set the date query from query variables.
		$date_query = new WP_Date_Query( $query->query_vars['date_query'] );
	}

	if ( ! $date_query ) {
		$date_query = array();
		if ( ! empty( $query->query_vars['year'] ) ) {
			$date_query['year'] = intval( $query->query_vars['year'] );
		}
		if ( ! empty( $query->query_vars['monthnum'] ) ) {
			$date_query['month'] = intval( $query->query_vars['monthnum'] );
		}
		if ( ! empty( $query->query_vars['w'] ) ) {
			$date_query['week'] = intval( $query->query_vars['w'] );
		}
		if ( ! empty( $query->query_vars['day'] ) ) {
			$date_query['day'] = intval( $query->query_vars['day'] );
		}
		if ( ! empty( $query->query_vars['hour'] ) ) {
			$date_query['hour'] = intval( $query->query_vars['hour'] );
		}
		if ( ! empty( $query->query_vars['minute'] ) ) {
			$date_query['minute'] = intval( $query->query_vars['minute'] );
		}
		if ( ! empty( $query->query_vars['second'] ) ) {
			$date_query['second'] = intval( $query->query_vars['second'] );
		}
		if ( ! empty( $query->query_vars['m'] ) ) {
			if ( 6 === strlen( $query->query_vars['m'] ) ) {
				$date_query['year']  = intval( substr( $query->query_vars['m'], 0, 4 ) );
				$date_query['month'] = intval( substr( $query->query_vars['m'], -2, 2 ) );
			}
		}
		if ( ! empty( $date_query ) ) {
			$date_query = new WP_Date_Query( $date_query );
		} else {
			$date_query = false;
		}
	}
	return $date_query;
}

/**
 * Generates a meta_query array from the query meta variables.
 *
 * First checks $query->meta_query, if that doesn't exist then looks at the
 * other meta query and custom field parameters to construct a meta query.
 *
 * @param WP_Query $query The query object.
 *
 * @return array|boolean The meta query object or false, if no meta query
 * parameters can be parsed.
 */
function relevanssi_meta_query_from_query_vars( $query ) {
	$meta_query = false;
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
			$build_meta_query['compare'] = $query->query_vars['meta_compare'];
		}

		$meta_query[] = $build_meta_query;
	}
	return $meta_query;
}

/**
 * Checks whether Relevanssi can do a media search.
 *
 * Relevanssi does not work with the grid view of Media Gallery. This function
 * will disable Relevanssi a) if Relevanssi is not set to index attachments,
 * b) if Relevanssi is not set to index image attachments and c) if the Media
 * Library is in grid mode. Any of these will inactivate Relevanssi in the
 * Media Library search.
 *
 * @param boolean  $search_ok If true, allow the search.
 * @param WP_Query $query     The query object.
 *
 * @return boolean If true, allow the search.
 */
function relevanssi_control_media_queries( bool $search_ok, WP_Query $query ) : bool {
	if ( ! $search_ok ) {
		// Something else has already disabled the search, this won't enable.
		return $search_ok;
	}
	if ( ! isset( $query->query_vars['post_type'] ) || ! isset( $query->query_vars['post_status'] ) ) {
		// Not a Media Library search.
		return $search_ok;
	}
	if (
		'attachment' !== $query->query_vars['post_type'] &&
		'inherit,private' !== $query->query_vars['post_status']
	) {
		// Not a Media Library search.
		return $search_ok;
	}
	$indexed_post_types = array_flip(
		get_option( 'relevanssi_index_post_types', array() )
	);
	$images_indexed     = get_option( 'relevanssi_index_image_files', 'off' );
	if ( false === isset( $indexed_post_types['attachment'] ) || 'off' === $images_indexed ) {
		// Attachments or images are not indexed, disable.
		$search_ok = false;
	}

	if ( ! isset( $_REQUEST['mode'] ) || 'list' !== $_REQUEST['mode'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		// Grid view, disable.
		$search_ok = false;
	}

	return $search_ok;
}

/**
 * Calculates the TF value.
 *
 * @param stdClass $match             The match object.
 * @param array    $post_type_weights An array of post type weights.
 *
 * @return float The TF value.
 */
function relevanssi_calculate_tf( $match, $post_type_weights ) {
	$content_boost = floatval( get_option( 'relevanssi_content_boost', 1 ) );
	$title_boost   = floatval( get_option( 'relevanssi_title_boost' ) );
	$link_boost    = floatval( get_option( 'relevanssi_link_boost' ) );
	$comment_boost = floatval( get_option( 'relevanssi_comment_boost' ) );

	if ( ! empty( $match->taxonomy_detail ) ) {
		relevanssi_taxonomy_score( $match, $post_type_weights );
	} else {
		$tag_weight = 1;
		if ( isset( $post_type_weights['post_tagged_with_post_tag'] ) && is_numeric( $post_type_weights['post_tagged_with_post_tag'] ) ) {
			$tag_weight = $post_type_weights['post_tagged_with_post_tag'];
		}

		$category_weight = 1;
		if ( isset( $post_type_weights['post_tagged_with_category'] ) && is_numeric( $post_type_weights['post_tagged_with_category'] ) ) {
			$category_weight = $post_type_weights['post_tagged_with_category'];
		}

		$taxonomy_weight = 1;

		$match->taxonomy_score =
			$match->tag * $tag_weight +
			$match->category * $category_weight +
			$match->taxonomy * $taxonomy_weight;
	}

	$tf =
		$match->title * $title_boost +
		$match->content * $content_boost +
		$match->comment * $comment_boost +
		$match->link * $link_boost +
		$match->author +
		$match->excerpt +
		$match->taxonomy_score +
		$match->customfield +
		$match->mysqlcolumn;

	return $tf;
}

/**
 * Calculates the match weight based on TF, IDF and bonus multipliers.
 *
 * @param stdClass $match             The match object.
 * @param float    $idf               The inverse document frequency.
 * @param array    $post_type_weights The post type weights.
 * @param string   $query             The search query.
 *
 * @return float The weight.
 */
function relevanssi_calculate_weight( $match, $idf, $post_type_weights, $query ) {
	if ( $idf < 1 ) {
		$idf = 1;
	}
	$weight = $match->tf * $idf;

	$type = relevanssi_get_post_type( $match->doc );
	if ( ! is_wp_error( $type ) && ! empty( $post_type_weights[ $type ] ) ) {
		$weight = $weight * $post_type_weights[ $type ];
	}

	/* Weight boost for taxonomy terms based on taxonomy. */
	if ( ! empty( $post_type_weights[ 'taxonomy_term_' . $match->type ] ) ) {
		$weight = $weight * $post_type_weights[ 'taxonomy_term_' . $match->type ];
	}

	if ( function_exists( 'relevanssi_get_recency_bonus' ) ) {
		$recency_details     = relevanssi_get_recency_bonus();
		$recency_bonus       = $recency_details['bonus'];
		$recency_cutoff_date = $recency_details['cutoff'];
		if ( $recency_bonus ) {
			$post = relevanssi_get_post( $match->doc );
			if ( strtotime( $post->post_date ) > $recency_cutoff_date ) {
				$weight = $weight * $recency_bonus;
			}
		}
	}

	if ( $query && 'on' === get_option( 'relevanssi_exact_match_bonus' ) ) {
		/**
		 * Filters the exact match bonus.
		 *
		 * @param array The title bonus under 'title' (default 5) and the content
		 * bonus under 'content' (default 2).
		 */
		$exact_match_boost = apply_filters(
			'relevanssi_exact_match_bonus',
			array(
				'title'   => 5,
				'content' => 2,
			)
		);

		$post        = relevanssi_get_post( $match->doc );
		$clean_query = str_replace( '"', '', $query );
		if ( stristr( $post->post_title, $clean_query ) !== false ) {
			$weight *= $exact_match_boost['title'];
		}
		if ( stristr( $post->post_content, $clean_query ) !== false ) {
			$weight *= $exact_match_boost['content'];
		}
	}

	return $weight;
}

/**
 * Updates the $term_hits array used for showing how many hits were found for
 * each term.
 *
 * @param array    $term_hits    The term hits array (passed as reference).
 * @param array    $match_arrays The matches array (passed as reference).
 * @param stdClass $match        The match object.
 * @param string   $term         The search term.
 */
function relevanssi_update_term_hits( &$term_hits, &$match_arrays, $match, $term ) {
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
		$match->customfield;

	relevanssi_increase_value( $match_arrays['body'][ $match->doc ], $match->content );
	relevanssi_increase_value( $match_arrays['title'][ $match->doc ], $match->title );
	relevanssi_increase_value( $match_arrays['link'][ $match->doc ], $match->link );
	relevanssi_increase_value( $match_arrays['tag'][ $match->doc ], $match->tag );
	relevanssi_increase_value( $match_arrays['category'][ $match->doc ], $match->category );
	relevanssi_increase_value( $match_arrays['taxonomy'][ $match->doc ], $match->taxonomy );
	relevanssi_increase_value( $match_arrays['comment'][ $match->doc ], $match->comment );
	relevanssi_increase_value( $match_arrays['customfield'][ $match->doc ], $match->customfield );
	relevanssi_increase_value( $match_arrays['author'][ $match->doc ], $match->author );
	relevanssi_increase_value( $match_arrays['excerpt'][ $match->doc ], $match->excerpt );
	relevanssi_increase_value( $match_arrays['mysqlcolumn'][ $match->doc ], $match->mysqlcolumn );
}

/**
 * Increases a value. If it's not set, sets it first to the default value.
 *
 * @param int $value    The value to increase (passed by reference).
 * @param int $increase The amount to increase the value, default 1.
 * @param int $default  The default value, default 0.
 */
function relevanssi_increase_value( &$value, $increase = 1, $default = 0 ) {
	if ( ! isset( $value ) ) {
		$value = $default;
	}
	$value += $increase;
}

/**
 * Initializes the matches array with empty arrays.
 *
 * @return array An array of empty arrays.
 */
function relevanssi_initialize_match_arrays() {
	return array(
		'author'      => array(),
		'body'        => array(),
		'category'    => array(),
		'comment'     => array(),
		'customfield' => array(),
		'excerpt'     => array(),
		'link'        => array(),
		'mysqlcolumn' => array(),
		'tag'         => array(),
		'taxonomy'    => array(),
		'title'       => array(),
	);
}

/**
 * Calculates the DF counts for each term.
 *
 * @param array $terms The list of terms.
 * @param array $args  The rest of the parameters: bool 'no_terms' for whether
 * there's a search term or not; string 'operator' for the search operator,
 * array 'phrase_queries' for the phrase queries, string 'query_join' for the
 * MySQL query JOIN value, string 'query_restrictions' for the MySQL query
 * restrictions, bool 'search_again' to tell if this is a redone search.
 *
 * @return array An array of DF values for each term.
 */
function relevanssi_generate_df_counts( array $terms, array $args ) : array {
	global $wpdb, $relevanssi_variables;
	$relevanssi_table = $relevanssi_variables['relevanssi_table'];

	$fuzzy = get_option( 'relevanssi_fuzzy' );

	$df_counts = array();
	foreach ( $terms as $term ) {
		$term_cond = relevanssi_generate_term_where( $term, $args['search_again'], $args['no_terms'] );
		if ( null === $term_cond ) {
			continue;
		}

		$this_query_restrictions = relevanssi_add_phrase_restrictions(
			$args['query_restrictions'],
			$args['phrase_queries'],
			$term,
			$args['operator']
		);

		$query = "SELECT COUNT(DISTINCT(relevanssi.doc)) FROM $relevanssi_table AS relevanssi
			{$args['query_join']} WHERE $term_cond $this_query_restrictions";
		// Clean: $this_query_restrictions is escaped, $term_cond is escaped.
		/**
		 * Filters the DF query.
		 *
		 * This query is used to calculate the df for the tf * idf calculations.
		 *
		 * @param string MySQL query to filter.
		 */
		$query = apply_filters( 'relevanssi_df_query_filter', $query );

		$df = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $df < 1 && 'sometimes' === $fuzzy ) {
			$term_cond = relevanssi_generate_term_where( $term, true, $args['no_terms'] );
			$query     = "
			SELECT COUNT(DISTINCT(relevanssi.doc))
				FROM $relevanssi_table AS relevanssi
				{$args['query_join']} WHERE $term_cond {$args['query_restrictions']}";
			// Clean: $query_restrictions is escaped, $term is escaped.
			/** Documented in lib/search.php. */
			$query = apply_filters( 'relevanssi_df_query_filter', $query );
			$df    = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$df_counts[ $term ] = $df;
	}

	// Sort the terms in ascending DF order, so that rarest terms are searched
	// for first. This is to make sure the throttle doesn't cut off posts with
	// rare search terms.
	asort( $df_counts );

	return $df_counts;
}

/**
 * Sorts the results Relevanssi finds.
 *
 * @param array        $hits       The results array (passed as reference).
 * @param string|array $orderby    The orderby parameter, accepts both string
 * and array format.
 * @param string       $order      Either 'asc' or 'desc'.
 * @param array        $meta_query The meta query parameters.
 */
function relevanssi_sort_results( &$hits, $orderby, $order, $meta_query ) {
	if ( empty( $orderby ) ) {
		$orderby = get_option( 'relevanssi_default_orderby', 'relevance' );
	}

	if ( is_array( $orderby ) ) {
		/**
		 * Filters the orderby parameter before Relevanssi sorts posts.
		 *
		 * @param array|string $orderby The orderby parameter, accepts both
		 * string and array format.
		 */
		$orderby = apply_filters( 'relevanssi_orderby', $orderby );
		relevanssi_object_sort( $hits, $orderby, $meta_query );
	} else {
		if ( empty( $order ) ) {
			$order = 'desc';
		}

		$order                 = strtolower( $order );
		$order_accepted_values = array( 'asc', 'desc' );
		if ( ! in_array( $order, $order_accepted_values, true ) ) {
			$order = 'desc';
		}
		/**
		 * This filter is documented in lib/search.php.
		 */
		$orderby = apply_filters( 'relevanssi_orderby', $orderby );

		if ( is_array( $orderby ) ) {
			relevanssi_object_sort( $hits, $orderby, $meta_query );
		} else {
			/**
			 * Filters the order parameter before Relevanssi sorts posts.
			 *
			 * @param string $order The order parameter, either 'asc' or 'desc'.
			 * Default 'desc'.
			 */
			$order = apply_filters( 'relevanssi_order', $order );

			if ( 'relevance' !== $orderby ) {
				// Results are by default sorted by relevance, so no need to sort
				// for that.
				$orderby_array = array( $orderby => $order );
				relevanssi_object_sort( $hits, $orderby_array, $meta_query );
			}
		}
	}
}

/**
 * Adjusts the $match->doc ID in case of users, post type archives and
 * taxonomy terms.
 *
 * @param stdClass $match The match object.
 *
 * @return int|string The doc ID, modified if necessary.
 */
function relevanssi_adjust_match_doc( $match ) {
	$doc = $match->doc;
	if ( 'user' === $match->type ) {
		$doc = 'u_' . $match->item;
	} elseif ( 'post_type' === $match->type ) {
		$doc = 'p_' . $match->item;
	} elseif ( ! in_array( $match->type, array( 'post', 'attachment' ), true ) ) {
		$doc = '**' . $match->type . '**' . $match->item;
	}
	return $doc;
}

/**
 * Generates the MySQL search query.
 *
 * @param string $term               The search term.
 * @param bool   $search_again       If true, this is a repeat search (partial matching).
 * @param bool   $no_terms           If true, no search term is used.
 * @param string $query_join         The MySQL JOIN clause, default empty string.
 * @param string $query_restrictions The MySQL query restrictions, default empty string.
 *
 * @return string The MySQL search query.
 */
function relevanssi_generate_search_query( string $term, bool $search_again,
bool $no_terms, string $query_join = '', string $query_restrictions = '' ) : string {
	global $relevanssi_variables;
	$relevanssi_table = $relevanssi_variables['relevanssi_table'];

	if ( $no_terms ) {
		$query = "SELECT DISTINCT(relevanssi.doc), 1 AS term, 1 AS term_reverse,
		1 AS content, 1 AS title, 1 AS comment, 1 AS tag, 1 AS link, 1 AS
		author, 1 AS category, 1 AS excerpt, 1 AS taxonomy, 1 AS customfield,
		1 AS mysqlcolumn, 1 AS taxonomy_detail, 1 AS customfield_detail, 1 AS
		mysqlcolumn_detail, type, item, 1 AS tf
		FROM $relevanssi_table AS relevanssi $query_join
		WHERE relevanssi.term = relevanssi.term $query_restrictions";
	} else {
		$term_cond = relevanssi_generate_term_where( $term, $search_again, $no_terms, get_option( 'relevanssi_fuzzy' ) );

		$content_boost = floatval( get_option( 'relevanssi_content_boost', 1 ) );
		$title_boost   = floatval( get_option( 'relevanssi_title_boost' ) );
		$link_boost    = floatval( get_option( 'relevanssi_link_boost' ) );
		$comment_boost = floatval( get_option( 'relevanssi_comment_boost' ) );

		$tag = ! empty( $post_type_weights['post_tag'] ) ? $post_type_weights['post_tag'] : $relevanssi_variables['post_type_weight_defaults']['post_tag'];
		$cat = ! empty( $post_type_weights['category'] ) ? $post_type_weights['category'] : $relevanssi_variables['post_type_weight_defaults']['category'];

		// Clean: $term is escaped, as are $query_restrictions.
		$query = "SELECT DISTINCT(relevanssi.doc), relevanssi.*, relevanssi.title * $title_boost +
		relevanssi.content * $content_boost + relevanssi.comment * $comment_boost +
		relevanssi.tag * $tag + relevanssi.link * $link_boost +
		relevanssi.author + relevanssi.category * $cat + relevanssi.excerpt +
		relevanssi.taxonomy + relevanssi.customfield + relevanssi.mysqlcolumn AS tf
		FROM $relevanssi_table AS relevanssi $query_join WHERE $term_cond $query_restrictions";
	}

	/**
	 * Filters the Relevanssi search query.
	 *
	 * @param string $query The Relevanssi search MySQL query.
	 */
	return apply_filters( 'relevanssi_query_filter', $query );
}

/**
 * Compiles search arguments that are shared between single site search and
 * multisite search.
 *
 * @param WP_Query $query The WP_Query that has the parameters.
 *
 * @return array The compiled search parameters.
 */
function relevanssi_compile_common_args( $query ) {
	$admin_search        = isset( $query->query_vars['relevanssi_admin_search'] ) ? true : false;
	$include_attachments = $query->query_vars['include_attachments'] ?? '';

	$by_date = '';
	if ( ! empty( $query->query_vars['by_date'] ) ) {
		if ( preg_match( '/\d+[hdmyw]/', $query->query_vars['by_date'] ) ) {
			// Accepted format is digits followed by h, d, m, y, or w.
			$by_date = $query->query_vars['by_date'];
		}
	}

	$order   = $query->query_vars['order'] ?? null;
	$orderby = $query->query_vars['orderby'] ?? null;

	$operator = '';
	if ( function_exists( 'relevanssi_set_operator' ) ) {
		$operator = relevanssi_set_operator( $query );
		$operator = strtoupper( $operator );
	}
	if ( ! in_array( $operator, array( 'OR', 'AND' ), true ) ) {
		$operator = get_option( 'relevanssi_implicit_operator' );
	}

	$sentence = false;
	if ( isset( $query->query_vars['sentence'] ) && ! empty( $query->query_vars['sentence'] ) ) {
		$sentence = true;
	}

	$meta_query = relevanssi_meta_query_from_query_vars( $query );
	$date_query = relevanssi_wp_date_query_from_query_vars( $query );

	$post_type = false;
	if ( isset( $query->query_vars['post_type'] ) && is_array( $query->query_vars['post_type'] ) ) {
		$query->query_vars['post_type'] = implode( ',', $query->query_vars['post_type'] );
	}
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

	return array(
		'orderby'             => $orderby,
		'order'               => $order,
		'operator'            => $operator,
		'admin_search'        => $admin_search,
		'include_attachments' => $include_attachments,
		'by_date'             => $by_date,
		'sentence'            => $sentence,
		'meta_query'          => $meta_query,
		'date_query'          => $date_query,
		'post_type'           => $post_type,
		'post_status'         => $post_status,
	);
}

/**
 * Adds posts to the matches list from the other term queries.
 *
 * Without this functionality, AND searches would not return all posts. If a
 * post appears within the best results for one word, but not for another word
 * even though the word appears in the post (because of throttling), the post
 * would be excluded. This functionality makes sure it is included.
 *
 * @param array $matches The found posts array.
 * @param array $include The posts to include.
 * @param array $params  Search parameters.
 */
function relevanssi_add_include_matches( array &$matches, array $include, array $params ) {
	if ( count( $include['posts'] ) < 1 && count( $include['items'] ) < 1 ) {
		return;
	}

	global $wpdb, $relevanssi_variables;
	$relevanssi_table = $relevanssi_variables['relevanssi_table'];

	$term_cond     = relevanssi_generate_term_where( $params['term'], $params['search_again'], $params['no_terms'] );
	$content_boost = floatval( get_option( 'relevanssi_content_boost', 1 ) ); // Default value, because this option was added late.
	$title_boost   = floatval( get_option( 'relevanssi_title_boost' ) );
	$link_boost    = floatval( get_option( 'relevanssi_link_boost' ) );
	$comment_boost = floatval( get_option( 'relevanssi_comment_boost' ) );
	$tag           = $relevanssi_variables['post_type_weight_defaults']['post_tag'];
	$cat           = $relevanssi_variables['post_type_weight_defaults']['category'];

	if ( ! empty( $post_type_weights['post_tagged_with_post_tag'] ) ) {
		$tag = $post_type_weights['post_tagged_with_post_tag'];
	}
	if ( ! empty( $post_type_weights['post_tagged_with_category'] ) ) {
		$cat = $post_type_weights['post_tagged_with_category'];
	}

	if ( count( $include['posts'] ) > 0 ) {
		$existing_ids = array();
		foreach ( $matches as $match ) {
			$existing_ids[] = $match->doc;
		}
		$existing_ids   = array_keys( array_flip( $existing_ids ) );
		$added_post_ids = array_diff( array_keys( $include['posts'] ), $existing_ids );
		if ( count( $added_post_ids ) > 0 ) {
			$offset       = 0;
			$slice_length = 20;
			$total_ids    = count( $added_post_ids );
			do {
				$current_slice   = array_slice( $added_post_ids, $offset, $slice_length );
				$post_ids_to_add = implode( ',', $current_slice );
				if ( ! empty( $post_ids_to_add ) ) {
					$query = "SELECT relevanssi.*, relevanssi.title * $title_boost +
					relevanssi.content * $content_boost + relevanssi.comment * $comment_boost +
					relevanssi.tag * $tag + relevanssi.link * $link_boost +
					relevanssi.author + relevanssi.category * $cat + relevanssi.excerpt +
					relevanssi.taxonomy + relevanssi.customfield + relevanssi.mysqlcolumn AS tf
					FROM $relevanssi_table AS relevanssi WHERE relevanssi.doc IN ($post_ids_to_add)
					AND $term_cond";

					// Clean: no unescaped user inputs.
					$matches_to_add = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$matches        = array_merge( $matches, $matches_to_add );
				}
				$offset += $slice_length;
			} while ( $offset <= $total_ids );
		}
	}
	if ( count( $include['items'] ) > 0 ) {
		$existing_items = array();
		foreach ( $matches as $match ) {
			if ( 0 !== intval( $match->item ) ) {
				$existing_items[] = $match->item;
			}
		}
		$existing_items = array_keys( array_flip( $existing_items ) );
		$items_to_add   = implode( ',', array_diff( array_keys( $include['items'] ), $existing_items ) );

		if ( ! empty( $items_to_add ) ) {
			$query = "SELECT relevanssi.*, relevanssi.title * $title_boost +
			relevanssi.content * $content_boost + relevanssi.comment * $comment_boost +
			relevanssi.tag * $tag + relevanssi.link * $link_boost +
			relevanssi.author + relevanssi.category * $cat + relevanssi.excerpt +
			relevanssi.taxonomy + relevanssi.customfield + relevanssi.mysqlcolumn AS tf
			FROM $relevanssi_table AS relevanssi WHERE relevanssi.item IN ($items_to_add)
			AND $term_cond";

			// Clean: no unescaped user inputs.
			$matches_to_add = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$matches        = array_merge( $matches, $matches_to_add );
		}
	}
}

/**
 * Figures out the low and high boundaries for the search query.
 *
 * The low boundary defaults to 0. If the search is paged, the low boundary is
 * calculated from the page number and posts_per_page value.
 *
 * The high boundary defaults to the low boundary + post_per_page, but if no
 * posts_per_page is set or it's -1, the high boundary is the number of posts
 * found. Also if the high boundary is higher than the number of posts found,
 * it's set there.
 *
 * If an offset is defined, both boundaries are offset with the value.
 *
 * @param WP_Query $query The WP Query object.
 *
 * @return array An array with the low boundary first, the high boundary second.
 */
function relevanssi_get_boundaries( $query ) : array {
	$hits_count = $query->found_posts;

	if ( isset( $query->query_vars['paged'] ) && $query->query_vars['paged'] > 0 ) {
		$search_low_boundary = ( $query->query_vars['paged'] - 1 ) * $query->query_vars['posts_per_page'];
	} else {
		$search_low_boundary = 0;
	}

	if ( ! isset( $query->query_vars['posts_per_page'] ) || -1 === $query->query_vars['posts_per_page'] ) {
		$search_high_boundary = $hits_count;
	} else {
		$search_high_boundary = $search_low_boundary + $query->query_vars['posts_per_page'] - 1;
	}

	if ( isset( $query->query_vars['offset'] ) && $query->query_vars['offset'] > 0 ) {
		$search_high_boundary += $query->query_vars['offset'];
		$search_low_boundary  += $query->query_vars['offset'];
	}

	if ( $search_high_boundary > $hits_count ) {
		$search_high_boundary = $hits_count;
	}

	return array( $search_low_boundary, $search_high_boundary );
}

/**
 * Returns a ID=>parent object from post ID.
 *
 * @param int $post_id The post ID.
 *
 * @return object An object with the post ID in ->ID and post parent in
 * ->post_parent.
 */
function relevanssi_generate_post_parent( int $post_id ) {
	$object              = new StdClass();
	$object->ID          = $post_id;
	$object->post_parent = wp_get_post_parent_id( $post_id );
	return $object;
}

/**
 * Returns a ID=>type object from post ID.
 *
 * @param string $post_id The post ID.
 *
 * @return object An object with the post ID in ->ID, object type in ->type and
 * (possibly) term taxonomy in ->taxonomy and post type name in ->name.
 */
function relevanssi_generate_id_type( string $post_id ) {
	$object = new StdClass();
	if ( 'u_' === substr( $post_id, 0, 2 ) ) {
		$object->ID   = intval( substr( $post_id, 2 ) );
		$object->type = 'user';
	} elseif ( '**' === substr( $post_id, 0, 2 ) ) {
		list( , $taxonomy, $id ) = explode( '**', $post_id );
		$object->ID              = $id;
		$object->type            = 'term';
		$object->taxonomy        = $taxonomy;
	} elseif ( 'p_' === substr( $post_id, 0, 2 ) ) {
		$object->ID   = intval( substr( $post_id, 2 ) );
		$object->type = 'post_type';
		$object->name = relevanssi_get_post_type_by_id( $object->ID );
	} else {
		$object->ID   = $post_id;
		$object->type = 'post';
	}
	return $object;
}
