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

	/**
	 * Filters whether stopwords are removed from titles.
	 *
	 * @param boolean If true, remove stopwords from titles.
	 */
	$remove_stopwords = apply_filters( 'relevanssi_remove_stopwords_in_titles', true );

	$terms = relevanssi_tokenize( $q, $remove_stopwords );
	$terms = array_keys( $terms ); // Don't care about tf in query.

	if ( function_exists( 'relevanssi_process_terms' ) ) {
		$process_terms_results = relevanssi_process_terms( $terms, $q );
		$query_restrictions   .= $process_terms_results['query_restrictions'];
		$terms                 = $process_terms_results['terms'];
	}

	$no_terms = false;
	if ( count( $terms ) < 1 && empty( $q ) ) {
		$no_terms = true;
		$terms[]  = 'term';
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

	/**
	 * Filters the meta query JOIN for the Relevanssi search query.
	 *
	 * Somewhat equivalent to the 'posts_join' filter.
	 *
	 * @param string The JOINed query.
	 */
	$query_join = apply_filters( 'relevanssi_join', $query_join );

	// Go get the count from the options, but run the full query if it's not available.
	$doc_count = get_option( 'relevanssi_doc_count' );
	if ( ! $doc_count || $doc_count < 1 ) {
		$doc_count = relevanssi_update_doc_count();
	}

	$total_hits = 0;

	$title_matches       = array();
	$tag_matches         = array();
	$comment_matches     = array();
	$link_matches        = array();
	$body_matches        = array();
	$category_matches    = array();
	$taxonomy_matches    = array();
	$customfield_matches = array();
	$mysqlcolumn_matches = array();
	$author_matches      = array();
	$excerpt_matches     = array();
	$scores              = array();
	$term_hits           = array();

	$fuzzy = get_option( 'relevanssi_fuzzy' );

	$no_matches = true;

	$post_type_weights = get_option( 'relevanssi_post_type_weights' );

	$recency_bonus       = false;
	$recency_cutoff_date = false;
	if ( function_exists( 'relevanssi_get_recency_bonus' ) ) {
		$recency_details     = relevanssi_get_recency_bonus();
		$recency_bonus       = $recency_details['bonus'];
		$recency_cutoff_date = $recency_details['cutoff'];
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
		$exact_match_boost = apply_filters(
			'relevanssi_exact_match_bonus',
			array(
				'title'   => 5,
				'content' => 2,
			)
		);

	}

	$min_length = get_option( 'relevanssi_min_word_length' );

	$search_again = false;

	$content_boost = floatval( get_option( 'relevanssi_content_boost', 1 ) ); // Default value, because this option was added late.
	$title_boost   = floatval( get_option( 'relevanssi_title_boost' ) );
	$link_boost    = floatval( get_option( 'relevanssi_link_boost' ) );
	$comment_boost = floatval( get_option( 'relevanssi_comment_boost' ) );

	$tag = $relevanssi_variables['post_type_weight_defaults']['post_tag'];
	$cat = $relevanssi_variables['post_type_weight_defaults']['category'];

	if ( ! empty( $post_type_weights['post_tagged_with_post_tag'] ) ) {
		$tag = $post_type_weights['post_tagged_with_post_tag'];
	}
	if ( ! empty( $post_type_weights['post_tagged_with_category'] ) ) {
		$cat = $post_type_weights['post_tagged_with_category'];
	}

	// Legacy code, improvement introduced in 2.1.8, remove at some point.
	// phpcs:ignore Squiz.Commenting.InlineComment
	// @codeCoverageIgnoreStart
	if ( ! empty( $post_type_weights['post_tag'] ) ) {
		$tag = $post_type_weights['post_tag'];
	}
	if ( ! empty( $post_type_weights['category'] ) ) {
		$cat = $post_type_weights['category'];
	}
	// @codeCoverageIgnoreEnd
	/* End legacy code. */

	$include_these_posts = array();
	$include_these_items = array();
	$df_counts           = array();
	$doc_weight          = array();

	do {
		foreach ( $terms as $term ) {
			$term_cond = relevanssi_generate_term_where( $term, $search_again, $no_terms );
			if ( null === $term_cond ) {
				continue;
			}
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

			$df = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( $df < 1 && 'sometimes' === $fuzzy ) {
				$term_cond = relevanssi_generate_term_where( $term, true, $no_terms );
				$query     = "
				SELECT COUNT(DISTINCT(relevanssi.doc))
					FROM $relevanssi_table AS relevanssi
					$query_join WHERE $term_cond $query_restrictions";
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

		foreach ( $df_counts as $term => $df ) {
			$term_cond = relevanssi_generate_term_where( $term, $search_again, $no_terms );

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
			$matches = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( count( $matches ) < 1 ) {
				continue;
			} else {
				$no_matches = false;
				if ( count( $include_these_posts ) > 0 ) {
					$existing_ids = array();
					foreach ( $matches as $match ) {
						$existing_ids[] = $match->doc;
					}
					$existing_ids   = array_keys( array_flip( $existing_ids ) );
					$added_post_ids = array_diff( array_keys( $include_these_posts ), $existing_ids );
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
				if ( count( $include_these_items ) > 0 ) {
					$existing_items = array();
					foreach ( $matches as $match ) {
						if ( 0 !== intval( $match->item ) ) {
							$existing_items[] = $match->item;
						}
					}
					$existing_items = array_keys( array_flip( $existing_items ) );
					$items_to_add   = implode( ',', array_diff( array_keys( $include_these_items ), $existing_items ) );

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

			relevanssi_populate_array( $matches );
			global $relevanssi_post_types;

			$total_hits += count( $matches );

			$idf = log( $doc_count + 1 / ( 1 + $df ) );
			$idf = $idf * $idf; // Adjustment to increase the value of IDF.
			if ( $idf < 1 ) {
				$idf = 1;
			}

			foreach ( $matches as $match ) {
				if ( 'user' === $match->type ) {
					$match->doc = 'u_' . $match->item;
				} elseif ( 'post_type' === $match->type ) {
					$match->doc = 'p_' . $match->item;
				} elseif ( ! in_array( $match->type, array( 'post', 'attachment' ), true ) ) {
					$match->doc = '**' . $match->type . '**' . $match->item;
				}

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

					// Legacy code from 2.1.8. Remove at some point.
					// phpcs:ignore Squiz.Commenting.InlineComment
					// @codeCoverageIgnoreStart
					if ( isset( $post_type_weights['post_tag'] ) && is_numeric( $post_type_weights['post_tag'] ) ) {
						$tag_weight = $post_type_weights['post_tag'];
					}

					$category_weight = 1;
					if ( isset( $post_type_weights['category'] ) && is_numeric( $post_type_weights['category'] ) ) {
						$category_weight = $post_type_weights['category'];
					}
					// @codeCoverageIgnoreEnd
					/* End legacy code. */

					$taxonomy_weight = 1;

					$match->taxonomy_score =
						$match->tag * $tag_weight +
						$match->category * $category_weight +
						$match->taxonomy * $taxonomy_weight;
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
					$clean_q = str_replace( array( '"', '”', '“' ), '', $q_no_synonyms );
					if ( $post && $clean_q ) {
						if ( stristr( $post->post_title, $clean_q ) !== false ) {
							$match->weight *= $exact_match_boost['title'];
						}
						if ( stristr( $post->post_content, $clean_q ) !== false ) {
							$match->weight *= $exact_match_boost['content'];
						}
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
				if ( ! isset( $customfield_matches[ $match->doc ] ) ) {
					$customfield_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $author_matches[ $match->doc ] ) ) {
					$author_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $excerpt_matches[ $match->doc ] ) ) {
					$excerpt_matches[ $match->doc ] = 0;
				}
				if ( ! isset( $mysqlcolumn_matches[ $match->doc ] ) ) {
					$mysqlcolumn_matches[ $match->doc ] = 0;
				}
				$body_matches[ $match->doc ]        += $match->content;
				$title_matches[ $match->doc ]       += $match->title;
				$link_matches[ $match->doc ]        += $match->link;
				$tag_matches[ $match->doc ]         += $match->tag;
				$category_matches[ $match->doc ]    += $match->category;
				$taxonomy_matches[ $match->doc ]    += $match->taxonomy;
				$comment_matches[ $match->doc ]     += $match->comment;
				$customfield_matches[ $match->doc ] += $match->customfield;
				$author_matches[ $match->doc ]      += $match->author;
				$excerpt_matches[ $match->doc ]     += $match->excerpt;
				$mysqlcolumn_matches[ $match->doc ] += $match->mysqlcolumn;

				/* Post type weights. */
				$type = null;
				if ( isset( $relevanssi_post_types[ $match->doc ] ) ) {
					$type = $relevanssi_post_types[ $match->doc ];
				}
				if ( ! empty( $post_type_weights[ $type ] ) ) {
					$match->weight = $match->weight * $post_type_weights[ $type ];
				}

				/* Weight boost for taxonomy terms based on taxonomy. */
				if ( ! empty( $post_type_weights[ 'taxonomy_term_' . $match->type ] ) ) {
					$match->weight = $match->weight * $post_type_weights[ 'taxonomy_term_' . $match->type ];
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

		if ( $no_matches ) {
			if ( $search_again ) {
				// No hits even with fuzzy search!
				$search_again = false;
			} else {
				if ( 'sometimes' === $fuzzy ) {
					$search_again = true;
				}
			}
		} else {
			$search_again = false;
		}
		$params = array(
			'no_matches'   => $no_matches,
			'doc_weight'   => $doc_weight,
			'terms'        => $terms,
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

			$or_args['q_no_synonyms'] = $q;
			$or_args['q']             = relevanssi_add_synonyms( $q );
			$return                   = relevanssi_search( $or_args );

			$hits                = $return['hits'];
			$body_matches        = $return['body_matches'];
			$title_matches       = $return['title_matches'];
			$tag_matches         = $return['tag_matches'];
			$category_matches    = $return['category_matches'];
			$taxonomy_matches    = $return['taxonomy_matches'];
			$comment_matches     = $return['comment_matches'];
			$link_matches        = $return['link_matches'];
			$author_matches      = $return['author_matches'];
			$customfield_matches = $return['customfield_matches'];
			$mysqlcolumn_matches = $return['mysqlcolumn_matches'];
			$excerpt_matches     = $return['excerpt_matches'];
			$term_hits           = $return['term_hits'];
			$q                   = $return['query'];
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
			$return              = $params['return'];
			$hits                = $return['hits'];
			$body_matches        = $return['body_matches'];
			$title_matches       = $return['title_matches'];
			$tag_matches         = $return['tag_matches'];
			$category_matches    = $return['category_matches'];
			$taxonomy_matches    = $return['taxonomy_matches'];
			$comment_matches     = $return['comment_matches'];
			$link_matches        = $return['link_matches'];
			$author_matches      = $return['author_matches'];
			$customfield_matches = $return['customfield_matches'];
			$mysqlcolumn_matches = $return['mysqlcolumn_matches'];
			$excerpt_matches     = $return['excerpt_matches'];
			$term_hits           = $return['term_hits'];
			$q                   = $return['query'];
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
		relevanssi_object_sort( $hits, $orderby, $meta_query );
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
			relevanssi_object_sort( $hits, $orderby_array, $meta_query );
		}
	}
	$return = array(
		'hits'                => $hits,
		'body_matches'        => $body_matches,
		'title_matches'       => $title_matches,
		'tag_matches'         => $tag_matches,
		'category_matches'    => $category_matches,
		'comment_matches'     => $comment_matches,
		'taxonomy_matches'    => $taxonomy_matches,
		'link_matches'        => $link_matches,
		'customfield_matches' => $customfield_matches,
		'mysqlcolumn_matches' => $mysqlcolumn_matches,
		'author_matches'      => $author_matches,
		'excerpt_matches'     => $excerpt_matches,
		'scores'              => $scores,
		'term_hits'           => $term_hits,
		'query'               => $q,
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

	$hits_count         = count( $hits );
	$query->found_posts = $hits_count;
	if ( ! isset( $query->query_vars['posts_per_page'] ) || 0 === $query->query_vars['posts_per_page'] ) {
		// Assume something sensible to prevent "division by zero error".
		$query->query_vars['posts_per_page'] = -1;
	}
	if ( -1 === $query->query_vars['posts_per_page'] ) {
		$query->max_num_pages = $hits_count;
	} else {
		$query->max_num_pages = ceil( $hits_count / $query->query_vars['posts_per_page'] );
	}

	$update_log = get_option( 'relevanssi_log_queries' );
	if ( 'on' === $update_log ) {
		relevanssi_update_log( $q, $hits_count );
	}

	$make_excerpts = get_option( 'relevanssi_excerpts' );
	if ( $relevanssi_test_admin || ( $query->is_admin && ! defined( 'DOING_AJAX' ) ) ) {
		$make_excerpts = false;
	}

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

	for ( $i = $search_low_boundary; $i <= $search_high_boundary; $i++ ) {
		if ( isset( $hits[ intval( $i ) ] ) ) {
			$post = $hits[ intval( $i ) ];
		} else {
			continue;
		}

		if ( null === $post ) {
			// @codeCoverageIgnoreStart
			// Sometimes you can get a null object.
			continue;
			// @codeCoverageIgnoreEnd
		}

		if ( 'on' === get_option( 'relevanssi_hilite_title' ) && empty( $search_params['fields'] ) ) {
			$post->post_highlighted_title = wp_strip_all_tags( $post->post_title );
			$highlight                    = get_option( 'relevanssi_highlight' );
			if ( 'none' !== $highlight ) {
				if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
					$post->post_highlighted_title = relevanssi_highlight_terms( $post->post_highlighted_title, $q );
				}
			}
		}

		if ( 'on' === $make_excerpts && empty( $search_params['fields'] ) ) {
			if ( isset( $post->blog_id ) ) {
				switch_to_blog( $post->blog_id );
			}
			$post->original_excerpt = $post->post_excerpt;
			$post->post_excerpt     = relevanssi_do_excerpt( $post, $q );
			if ( isset( $post->blog_id ) ) {
				restore_current_blog();
			}
		}
		if ( empty( $search_params['fields'] ) ) {
			relevanssi_add_matches( $post, $return );
		}
		if ( 'on' === get_option( 'relevanssi_show_matches' ) && empty( $search_params['fields'] ) ) {
			$post_id = $post->ID;
			if ( 'user' === $post->post_type ) {
				$post_id = 'u_' . $post->user_id;
			} elseif ( 'post_type' === $post->post_type ) {
				$post_id = 'p_' . $post->ID;
			} elseif ( isset( $post->term_id ) ) {
				$post_id = '**' . $post->post_type . '**' . $post->term_id;
			}
			if ( isset( $post->blog_id ) ) {
				$post_id = $post->blog_id . '|' . $post->ID;
			}
			$post->post_excerpt .= relevanssi_show_matches( $post );
		}

		if ( empty( $search_params['fields'] ) ) {
			$post_id = $post->ID;
			if ( isset( $post->blog_id ) ) {
				$post_id = $post->blog_id . '|' . $post->ID;
			}
			if ( isset( $return['scores'][ $post_id ] ) ) {
				$post->relevance_score = round( $return['scores'][ $post_id ], 2 );
			}
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
 * Tested.
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
 * Tested.
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
	 */
	$fuzzy_query = apply_filters(
		'relevanssi_fuzzy_query',
		"(relevanssi.term LIKE '#term#%' OR relevanssi.term_reverse LIKE CONCAT(REVERSE('#term#'), '%')) "
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

	return $term_where;
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
				if ( ! empty( $post_type_weights[ $tax ] ) ) { // Legacy code, needed for 2.1.8, remove later.
					// phpcs:ignore Squiz.Commenting.InlineComment
					// @codeCoverageIgnoreStart
					$match->taxonomy_score += $count * $post_type_weights[ $tax ];
					// @codeCoverageIgnoreEnd
				} else {
					$match->taxonomy_score += $count * 1;
				}
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

	$tax_query = array();
	/**
	 * Filters the default tax_query relation.
	 *
	 * @param string The default relation, default 'AND'.
	 */
	$tax_query_relation = apply_filters( 'relevanssi_default_tax_query_relation', 'AND' );
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
	if ( isset( $query->query_vars['post_parent'] ) ) {
		$parent_query = array( 'parent in' => array( $query->query_vars['post_parent'] ) );
	}
	if ( isset( $query->query_vars['post_parent__in'] ) && is_array( $query->query_vars['post_parent__in'] ) && ! empty( $query->query_vars['post_parent__in'] ) ) {
		$parent_query = array( 'parent in' => $query->query_vars['post_parent__in'] );
	}
	if ( isset( $query->query_vars['post_parent__not_in'] ) && is_array( $query->query_vars['post_parent__not_in'] ) && ! empty( $query->query_vars['post_parent__not_in'] ) ) {
		$parent_query = array( 'parent not in' => $query->query_vars['post_parent__not_in'] );
	}

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
	if ( $relevanssi_test_admin || ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) {
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

	$admin_search = false;
	if ( isset( $query->query_vars['relevanssi_admin_search'] ) ) {
		$admin_search = true;
	}

	$include_attachments = '';
	if ( isset( $query->query_vars['include_attachments'] ) ) {
		$include_attachments = $query->query_vars['include_attachments'];
	}

	if ( function_exists( 'relevanssi_extract_specifier' ) ) {
		$q = relevanssi_extract_specifier( $q );
	}

	// Add synonyms.
	// This is done here so the new terms will get highlighting.
	$q_no_synonyms = $q;
	if ( 'OR' === $operator ) {
		// Synonyms are only used in OR queries.
		$q = relevanssi_add_synonyms( $q );
	}

	$search_params = array(
		'q'                   => $q,
		'q_no_synonyms'       => $q_no_synonyms,
		'tax_query'           => $tax_query,
		'tax_query_relation'  => $tax_query_relation,
		'post_query'          => $post_query,
		'parent_query'        => $parent_query,
		'meta_query'          => $meta_query,
		'date_query'          => $date_query,
		'expost'              => $expost,
		'post_type'           => $post_type,
		'post_status'         => $post_status,
		'operator'            => $operator,
		'author'              => $author,
		'orderby'             => $orderby,
		'order'               => $order,
		'fields'              => $fields,
		'sentence'            => $sentence,
		'by_date'             => $by_date,
		'admin_search'        => $admin_search,
		'include_attachments' => $include_attachments,
		'meta_query'          => $meta_query,
	);

	return $search_params;
}
