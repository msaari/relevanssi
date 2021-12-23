<?php
/**
 * /lib/common.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Adds the search result match breakdown to the post object.
 *
 * Reads in the number of matches and stores it in the relevanssi_hits filed
 * of the post object. The post object is passed as a reference and modified
 * on the fly.
 *
 * @param object $post The post object, passed as a reference.
 * @param array  $data The source data.
 */
function relevanssi_add_matches( &$post, $data ) {
	$hits['body']                 = $data['body_matches'][ $post->ID ] ?? 0;
	$hits['title']                = $data['title_matches'][ $post->ID ] ?? 0;
	$hits['taxonomy']['tag']      = $data['tag_matches'][ $post->ID ] ?? 0;
	$hits['taxonomy']['category'] = $data['category_matches'][ $post->ID ] ?? 0;
	$hits['taxonomy']['taxonomy'] = $data['taxonomy_matches'][ $post->ID ] ?? 0;
	$hits['comment']              = $data['comment_matches'][ $post->ID ] ?? 0;
	$hits['author']               = $data['author_matches'][ $post->ID ] ?? 0;
	$hits['excerpt']              = $data['excerpt_matches'][ $post->ID ] ?? 0;
	$hits['customfield']          = $data['customfield_matches'][ $post->ID ] ?? 0;
	$hits['mysqlcolumn']          = $data['mysqlcolumn_matches'][ $post->ID ] ?? 0;
	$hits['score']                = isset( $data['doc_weights'][ $post->ID ] ) ? round( $data['doc_weights'][ $post->ID ], 2 ) : 0;
	$hits['terms']                = $data['term_hits'][ $post->ID ] ?? array();
	$hits['missing_terms']        = $data['missing_terms'][ $post->ID ] ?? array();

	arsort( $hits['terms'] );

	$post->relevanssi_hits = $hits;
}

/**
 * Generates the search result breakdown added to the search results.
 *
 * Gets the source data from the post object and then replaces the placeholders
 * in the breakdown template with the data.
 *
 * @param object $post The post object.
 *
 * @return string The search results breakdown for the post.
 */
function relevanssi_show_matches( $post ) {
	$term_hits  = '';
	$total_hits = 0;
	foreach ( $post->relevanssi_hits['terms'] as $term => $hits ) {
		$term_hits  .= " $term: $hits";
		$total_hits += $hits;
	}

	$text          = stripslashes( get_option( 'relevanssi_show_matches_text' ) );
	$missing_terms = strstr( $text, '%missing%' ) !== false ? relevanssi_generate_missing_terms_list( $post ) : '';
	$replace_these = array(
		'%body%',
		'%title%',
		'%tags%',
		'%categories%',
		'%taxonomies%',
		'%comments%',
		'%customfields%',
		'%author%',
		'%excerpt%',
		'%mysqlcolumns%',
		'%score%',
		'%terms%',
		'%total%',
		'%missing%',
	);
	$replacements  = array(
		$post->relevanssi_hits['body'],
		$post->relevanssi_hits['title'],
		$post->relevanssi_hits['taxonomy']['tag'],
		$post->relevanssi_hits['taxonomy']['category'],
		$post->relevanssi_hits['taxonomy']['taxonomy'],
		$post->relevanssi_hits['comment'],
		$post->relevanssi_hits['customfield'],
		$post->relevanssi_hits['author'],
		$post->relevanssi_hits['excerpt'],
		$post->relevanssi_hits['mysqlcolumn'],
		$post->relevanssi_hits['score'],
		$term_hits,
		$total_hits,
		$missing_terms,
	);
	$result        = ' ' . str_replace( $replace_these, $replacements, $text );

	/**
	 * Filters the search result breakdown.
	 *
	 * If you use the "Show breakdown of search hits in excerpts" option, this
	 * filter lets you modify the breakdown before it is added to the excerpt.
	 *
	 * @param string $result The breakdown.
	 * @param object $post   The post object
	 */
	return apply_filters( 'relevanssi_show_matches', $result, $post );
}

/**
 * Generates the "Missing:" element for the search results breakdown.
 *
 * @param WP_Post $post The post object, which should have the missing terms in
 * $post->relevanssi_hits['missing_terms'].
 *
 * @return string The missing terms.
 */
function relevanssi_generate_missing_terms_list( $post ) {
	$missing_terms = '';
	if ( ! empty( $post->relevanssi_hits['missing_terms'] ) ) {
		$missing_terms_list = implode(
			' ',
			array_map(
				function ( $term ) {
					/**
					 * Determines the tag used for missing terms, default <s>.
					 *
					 * @param string The tag, without angle brackets. Default 's'.
					 */
					$tag = apply_filters( 'relevanssi_missing_terms_tag', 's' );
					return $tag ? "<$tag>$term</$tag>" : $term;
				},
				$post->relevanssi_hits['missing_terms']
			)
		);
		$missing_terms      = sprintf(
			/**
			 * Filters the template for showing missing terms. Make sure you
			 * include the '%s', as that is where the missing terms will be
			 * inserted.
			 *
			 * @param string The template.
			 */
			apply_filters(
				'relevanssi_missing_terms_template',
				'<span class="missing_terms">' . __( 'Missing', 'relevanssi' ) . ': %s</span>'
			),
			$missing_terms_list
		);
	}
	if (
		1 === count( $post->relevanssi_hits['missing_terms'] )
		&& function_exists( 'relevanssi_add_must_have' )
		) {
		$missing_terms .= relevanssi_add_must_have( $post );
	}
	return $missing_terms;
}

/**
 * Checks whether the user is allowed to see the post.
 *
 * The default behaviour on 'relevanssi_post_ok' filter hook. Do note that while
 * this function takes $post_ok as a parameter, it actually doesn't care much
 * about the previous value, and will instead overwrite it. If you want to make
 * sure your value is preserved, either disable this default function, or run
 * your function on a later priority (this defaults to 10).
 *
 * Includes support for various membership plugins. Currently supports Members,
 * Groups, Simple Membership and s2member.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_default_post_ok( $post_ok, $post_id ) {
	$status = relevanssi_get_post_status( $post_id );

	// If it's not public, don't show.
	if ( 'publish' !== $status ) {
		$post_ok = false;
	}

	// Let's look a bit closer at private posts.
	if ( 'private' === $status ) {
		$post_ok = false;

		$type = relevanssi_get_post_type( $post_id );
		if ( isset( $GLOBALS['wp_post_types'][ $type ]->cap->read_private_posts ) ) {
			$cap = $GLOBALS['wp_post_types'][ $type ]->cap->read_private_posts;
		} else {
			// Just guessing here.
			$cap = 'read_private_' . $type . 's';
		}
		if ( current_user_can( $cap ) ) {
			// Current user has the required capabilities and can see the page.
			$post_ok = true;
		}
		$current_user = wp_get_current_user();
		if ( ! $post_ok && $current_user->ID > 0 ) {
			$post = relevanssi_get_post( $post_id );
			if ( $current_user->ID === (int) $post->post_author ) {
				// Allow authors to see their own private posts.
				$post_ok = true;
			}
		}
	}

	if ( in_array(
		$status,
		/**
		 * Filters statuses allowed in admin searches.
		 *
		 * By default, admin searches may show posts that have 'draft',
		 * 'pending' and 'future' status (in addition to 'publish' and
		 * 'private'). If you use custom statuses and want them included in the
		 * admin search, you can add the statuses using this filter.
		 *
		 * @param array $statuses Array of statuses to accept.
		 */
		apply_filters( 'relevanssi_valid_admin_status', array( 'draft', 'pending', 'future' ) ),
		true
	)
	&& is_admin() ) {
		// Only show drafts, pending and future posts in admin search.
		$post_ok = true;
	}

	return $post_ok;
}

/**
 * Populates the Relevanssi post array.
 *
 * This is a caching mechanism to reduce the number of database queries required.
 * This function fetches all post data for the matches found using single MySQL
 * query, instead of doing up to 500 separate get_post() queries.
 *
 * @global array  $relevanssi_post_array An array of fetched posts.
 * @global object $wpdb                  The WordPress database interface.
 *
 * @param array $matches An array of search matches.
 * @param int   $blog_id The blog ID for multisite searches. Default -1.
 */
function relevanssi_populate_array( $matches, $blog_id = -1 ) {
	global $relevanssi_post_array, $wpdb;

	if ( -1 === $blog_id ) {
		$blog_id = get_current_blog_id();
	}

	// Doing this makes life faster.
	wp_suspend_cache_addition( true );

	$ids = array();
	foreach ( $matches as $match ) {
		$cache_id = $blog_id . '|' . $match->doc;
		if ( $match->doc > 0 && ! isset( $relevanssi_post_array[ $cache_id ] ) ) {
			$ids[] = $match->doc;
		}
	}

	$ids = array_keys( array_flip( $ids ) ); // Remove duplicate IDs.
	do {
		$hundred_ids = array_splice( $ids, 0, 100 );
		$id_list     = implode( ', ', $hundred_ids );
		if ( ! empty( $id_list ) ) {
			$posts = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE id IN ( $id_list )", OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			foreach ( $posts as $post ) {
				$cache_id = $blog_id . '|' . $post->ID;

				$relevanssi_post_array[ $cache_id ] = $post;
			}
		}
	} while ( $ids );

	// Re-enable caching.
	wp_suspend_cache_addition( false );
}

/**
 * Returns the custom fields to index.
 *
 * Returns a list of custom fields to index, based on the custom field indexing
 * setting.
 *
 * @return array|string An array of custom fields to index, or 'all' or 'visible'.
 */
function relevanssi_get_custom_fields() {
	$custom_fields = get_option( 'relevanssi_index_fields' );
	if ( $custom_fields ) {
		if ( 'all' === $custom_fields ) {
			return $custom_fields;
		} elseif ( 'visible' === $custom_fields ) {
			return $custom_fields;
		} else {
			$custom_fields_raw = explode( ',', $custom_fields );
			$custom_fields     = false;
			if ( is_array( $custom_fields_raw ) ) {
				$custom_fields = array_filter( array_map( 'trim', $custom_fields_raw ) );
			}
		}
	} else {
		$custom_fields = false;
	}
	return $custom_fields;
}

/**
 * Removes punctuation from a string.
 *
 * This function removes some punctuation and replaces some punctuation with spaces.
 * This can partly be controlled from Relevanssi settings: see Advanced Indexing
 * Settings on the Indexing tab. This function runs on the
 * 'relevanssi_remove_punctuation' filter hook and can be disabled, if necessary.
 *
 * @param string $a The source string.
 *
 * @return string The string without punctuation.
 */
function relevanssi_remove_punct( $a ) {
	if ( ! is_string( $a ) ) {
		// In case something sends a non-string here.
		return '';
	}

	$a = preg_replace( '/&lt;(\d|\s)/', '\1', $a );
	$a = html_entity_decode( $a, ENT_QUOTES );
	$a = relevanssi_strip_all_tags( $a );

	$punct_options = get_option( 'relevanssi_punctuation' );

	$hyphen_replacement = ' ';
	$endash_replacement = ' ';
	$emdash_replacement = ' ';
	if ( isset( $punct_options['hyphens'] ) && 'remove' === $punct_options['hyphens'] ) {
		$hyphen_replacement = '';
		$endash_replacement = '';
		$emdash_replacement = '';
	}
	if ( isset( $punct_options['hyphens'] ) && 'keep' === $punct_options['hyphens'] ) {
		$hyphen_replacement = 'HYPHENTAIKASANA';
		$endash_replacement = 'ENDASHTAIKASANA';
		$emdash_replacement = 'EMDASHTAIKASANA';
	}

	$quote_replacement = ' ';
	if ( isset( $punct_options['quotes'] ) && 'remove' === $punct_options['quotes'] ) {
		$quote_replacement = '';
	}

	$ampersand_replacement = ' ';
	if ( isset( $punct_options['ampersands'] ) && 'remove' === $punct_options['ampersands'] ) {
		$ampersand_replacement = '';
	}
	if ( isset( $punct_options['ampersands'] ) && 'keep' === $punct_options['ampersands'] ) {
		$ampersand_replacement = 'AMPERSANDTAIKASANA';
	}

	$decimal_replacement = ' ';
	if ( isset( $punct_options['decimals'] ) && 'remove' === $punct_options['decimals'] ) {
		$decimal_replacement = '';
	}
	if ( isset( $punct_options['decimals'] ) && 'keep' === $punct_options['decimals'] ) {
		$decimal_replacement = 'DESIMAALITAIKASANA';
	}

	$replacement_array = array(
		'ß'                     => 'ss',
		'ı'                     => 'i',
		'₂'                     => '2',
		'·'                     => '',
		'…'                     => '',
		'€'                     => '',
		'®'                     => '',
		'©'                     => '',
		'™'                     => '',
		'&shy;'                 => '',
		"\xC2\xAD"              => '',
		'&nbsp;'                => ' ',
		chr( 194 ) . chr( 160 ) => ' ',
		'×'                     => ' ',
		'&#8217;'               => $quote_replacement,
		"'"                     => $quote_replacement,
		'’'                     => $quote_replacement,
		'‘'                     => $quote_replacement,
		'”'                     => $quote_replacement,
		'“'                     => $quote_replacement,
		'„'                     => $quote_replacement,
		'´'                     => $quote_replacement,
		'″'                     => $quote_replacement,
		'-'                     => $hyphen_replacement,
		'–'                     => $endash_replacement,
		'—'                     => $emdash_replacement,
		'&#038;'                => $ampersand_replacement,
		'&amp;'                 => $ampersand_replacement,
		'&'                     => $ampersand_replacement,
	);

	/**
	 * Filters the punctuation replacement array.
	 *
	 * This filter can be used to alter the way some of the most common punctuation
	 * is handled by Relevanssi.
	 *
	 * @param array $replacement_array The array of punctuation and the replacements.
	 */
	$replacement_array = apply_filters( 'relevanssi_punctuation_filter', $replacement_array );

	$a = preg_replace( '/\.(\d)/', $decimal_replacement . '\1', $a );

	$a = str_replace( "\r", ' ', $a );
	$a = str_replace( "\n", ' ', $a );
	$a = str_replace( "\t", ' ', $a );

	$a = stripslashes( $a );

	$a = str_replace( array_keys( $replacement_array ), array_values( $replacement_array ), $a );
	/**
	 * Filters the default punctuation replacement value.
	 *
	 * By default Relevanssi replaces unspecified punctuation with spaces. This
	 * filter can be used to change that behaviour.
	 *
	 * @param string $replacement The replacement value, default ' '.
	 */
	$a = preg_replace( '/[[:punct:]]+/u', apply_filters( 'relevanssi_default_punctuation_replacement', ' ' ), $a );
	$a = preg_replace( '/[[:space:]]+/', ' ', $a );

	$a = str_replace( 'AMPERSANDTAIKASANA', '&', $a );
	$a = str_replace( 'HYPHENTAIKASANA', '-', $a );
	$a = str_replace( 'ENDASHTAIKASANA', '–', $a );
	$a = str_replace( 'EMDASHTAIKASANA', '—', $a );
	$a = str_replace( 'DESIMAALITAIKASANA', '.', $a );

	$a = trim( $a );

	return $a;
}

/**
 * Prevents the default search from running.
 *
 * When Relevanssi is active, this function prevents the default search from running,
 * in order to save resources. There are some exceptions, where we don't want
 * Relevanssi to meddle.
 *
 * This function originally created by John Blackbourne.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param string $request The MySQL query for the search.
 * @param object $query   The WP_Query object.
 */
function relevanssi_prevent_default_request( $request, $query ) {
	if ( $query->is_search ) {
		$indexed_post_types = array_flip(
			get_option( 'relevanssi_index_post_types', array() )
		);
		$images_indexed     = get_option( 'relevanssi_index_image_files', 'off' );
		if ( false === isset( $indexed_post_types['attachment'] ) || 'off' === $images_indexed ) {
			if ( isset( $query->query_vars['post_type'] ) && isset( $query->query_vars['post_status'] ) ) {
				if ( 'attachment' === $query->query_vars['post_type'] && 'inherit,private' === $query->query_vars['post_status'] ) {
					// This is a media library search; do not meddle.
					return $request;
				}
			}
		}

		if ( in_array( $query->query_vars['post_type'], array( 'topic', 'reply' ), true ) ) {
			// This is a BBPress search; do not meddle.
			return $request;
		}
		if ( is_array( $query->query_vars['post_type'] ) ) {
			if ( in_array( 'topic', $query->query_vars['post_type'], true ) || in_array( 'reply', $query->query_vars['post_type'], true ) ) {
				// This is a BBPress search; do not meddle.
				return $request;
			}
		}

		if ( isset( $_REQUEST['action'] ) && 'acf' === substr( $_REQUEST['action'], 0, 3 ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// ACF stuff, do not touch (eg. a relationship field search).
			return $request;
		}
		if ( isset( $query->query_vars['action'] ) && 'acf' === substr( $query->query_vars['action'], 0, 3 ) ) {
			// ACF stuff, do not touch (eg. a relationship field search).
			return $request;
		}

		$admin_search_ok = true;
		/**
		 * Filters the admin search.
		 *
		 * If this filter returns 'false', Relevanssi will be disabled.
		 *
		 * @param boolean $admin_search_ok Is admin search allowed.
		 * @param object  $query           The WP_Query object.
		 */
		$admin_search_ok = apply_filters( 'relevanssi_admin_search_ok', $admin_search_ok, $query );

		$prevent = true;
		/**
		 * Filters whether the default request is blocked or not.
		 *
		 * If this filter returns 'false', the default search request will not be
		 * blocked.
		 *
		 * @param boolean $prevent Should the request be prevented.
		 * @param object  $query   The WP_Query object.
		 */
		$prevent = apply_filters( 'relevanssi_prevent_default_request', $prevent, $query );

		if ( empty( $query->query_vars['s'] ) ) {
			$prevent         = false;
			$admin_search_ok = false;
		}

		if ( $query->is_admin && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$prevent         = false;
			$admin_search_ok = false;
		}

		if ( $query->is_admin && isset( $query->query['fields'] ) && 'id=>parent' === $query->query['fields'] ) {
			// Relevanssi doesn't work on hierarchical post type admin screens,
			// so disable.
			$prevent         = false;
			$admin_search_ok = false;
		}

		global $wpdb;

		if ( ! is_admin() && $prevent ) {
			$request = "SELECT * FROM $wpdb->posts WHERE 1=2";
		} elseif ( 'on' === get_option( 'relevanssi_admin_search' ) && $admin_search_ok ) {
			$request = "SELECT * FROM $wpdb->posts WHERE 1=2";
		}
	}
	return $request;
}

/**
 * Tokenizes strings.
 *
 * Tokenizes strings, removes punctuation, converts to lowercase and removes
 * stopwords. The function accepts both strings and arrays of strings as
 * source material. If the parameter is an array of string, each string is
 * tokenized separately and the resulting tokens are combined into one array.
 *
 * @param string|array   $string          The string, or an array of strings, to
 *                                        tokenize.
 * @param boolean|string $remove_stops    If true, stopwords are removed. If
 * 'body', also removes the body stopwords. Default true.
 * @param int            $min_word_length The minimum word length to include.
 * Default -1.
 * @param string         $context         The context for tokenization, can be
 * 'indexing' or 'search_query'.
 *
 * @return int[] An array of tokens as the keys and their frequency as the
 * value.
 */
function relevanssi_tokenize( $string, $remove_stops = true, int $min_word_length = -1, $context = 'indexing' ) : array {
	if ( ! $string || ( ! is_string( $string ) && ! is_array( $string ) ) ) {
		return array();
	}

	$phrase_words = array();
	if ( RELEVANSSI_PREMIUM && 'search_query' === $context ) {
		$string_for_phrases = is_array( $string ) ? implode( ' ', $string ) : $string;
		$phrases            = relevanssi_extract_phrases( $string_for_phrases );
		$phrase_words       = array();
		foreach ( $phrases as $phrase ) {
			$phrase_words = array_merge( $phrase_words, explode( ' ', $phrase ) );
		}
	}

	$tokens = array();
	if ( is_array( $string ) ) {
		// If we get an array, tokenize each string in the array.
		foreach ( $string as $substring ) {
			if ( is_string( $substring ) ) {
				$tokens = array_merge( $tokens, relevanssi_tokenize( $substring, $remove_stops, $min_word_length ) );
			}
		}

		// And we're done!
		return $tokens;
	}

	if ( function_exists( 'mb_internal_encoding' ) ) {
		mb_internal_encoding( 'UTF-8' );
	}

	$stopword_list = array();
	if ( $remove_stops ) {
		$stopword_list = relevanssi_fetch_stopwords();
	}
	if ( 'body' === $remove_stops && function_exists( 'relevanssi_fetch_body_stopwords' ) ) {
		$stopword_list = array_merge( $stopword_list, relevanssi_fetch_body_stopwords() );
	}

	/**
	 * Disables stopwords completely.
	 *
	 * @param boolean If true, stopwords are not used. Default false.
	 */
	if ( apply_filters( 'relevanssi_disable_stopwords', false ) ) {
		$stopword_list = array();
	}

	if ( function_exists( 'relevanssi_apply_thousands_separator' ) ) {
		// A Premium feature.
		$string = relevanssi_apply_thousands_separator( $string );
	}

	/**
	 * Removes punctuation from the string.
	 *
	 * The default function on this filter is relevanssi_remove_punct(), which
	 * removes some punctuation and replaces some with spaces.
	 *
	 * @param string $string String with punctuation.
	 */
	$string = apply_filters( 'relevanssi_remove_punctuation', $string );

	$string = relevanssi_strtolower( $string );

	$token = strtok( $string, "\n\t " );
	while ( false !== $token ) {
		$token  = strval( $token );
		$accept = true;

		if ( relevanssi_strlen( $token ) < $min_word_length ) {
			$token = strtok( "\n\t  " );
			continue;
		}
		if ( $remove_stops && in_array( $token, $stopword_list, true ) ) {
			$accept = false;
		}

		if ( RELEVANSSI_PREMIUM && ! in_array( $token, $phrase_words, true ) ) {
			/**
			 * Fires Premium tokenizer.
			 *
			 * Filters the token through the Relevanssi Premium tokenizer to add
			 * some Premium features to the tokenizing (mostly stemming).
			 *
			 * @param string $token   Search query token.
			 * @param string $context The context for tokenization, can be
			 * 'indexing' or 'search_query'.
			 */
			$token = apply_filters( 'relevanssi_premium_tokenizer', $token, $context );
		}

		if ( $accept ) {
			$token = relevanssi_mb_trim( $token );

			/**
			 * This explode is done so that a stemmer can return both the
			 * original term and the stemmed term and both can be indexed.
			 */
			$token_array = explode( ' ', $token );
			foreach ( $token_array as $token ) {
				if ( is_numeric( $token ) ) {
					// $token ends up as an array index, and numbers don't work there.
					$token = " $token";
				}
				if ( ! isset( $tokens[ $token ] ) ) {
					$tokens[ $token ] = 1;
				} else {
					$tokens[ $token ]++;
				}
			}
		}

		$token = strtok( "\n\t " );
	}

	return $tokens;
}

/**
 * Returns the post status from post ID.
 *
 * Returns the post status. This replacement for get_post_status() can handle user
 * profiles and taxonomy terms (both always return 'publish'). The status is read
 * from the Relevanssi caching mechanism to avoid unnecessary database calls, and
 * if nothing else works, this function falls back to get_post_status().
 *
 * @global array $relevanssi_post_array The Relevanssi post cache array.
 *
 * @param string $post_id The post ID.
 *
 * @return string The post status.
 */
function relevanssi_get_post_status( $post_id ) {
	global $relevanssi_post_array;
	$type = substr( $post_id, 0, 2 );
	if ( '**' === $type || 'u_' === $type || 'p_' === $type ) {
		// Taxonomy term or user (a Premium feature).
		return 'publish';
	}

	$original_id = $post_id;
	$blog_id     = -1;
	if ( is_multisite() ) {
		$blog_id = get_current_blog_id();
		$post_id = $blog_id . '|' . $post_id;
	}
	if ( isset( $relevanssi_post_array[ $post_id ] ) ) {
		$status = $relevanssi_post_array[ $post_id ]->post_status;
		if ( 'inherit' === $status ) {
			// Attachment, let's see what the parent says.
			$parent = $relevanssi_post_array[ $post_id ]->post_parent;
			if ( ! $parent ) {
				// Attachment without a parent, let's assume it's public.
				$status = 'publish';
			} else {
				$status = relevanssi_get_post_status( $parent );
			}
		}
		return $status;
	} else {
		// No hit from the cache; let's fetch.
		$post = relevanssi_get_post( $original_id, $blog_id );

		if ( is_wp_error( $post ) ) {
			$post->add_data(
				'not_found',
				"relevanssi_get_post_status() didn't get a post, relevanssi_get_post() returned null."
			);
			return $post;
		} elseif ( $post ) {
			if ( 'inherit' === $post->post_status ) {
				// Attachment, let's see what the parent says.
				$parent = $relevanssi_post_array[ $post_id ]->post_parent ?? null;
				if ( ! $parent ) {
					// Attachment without a parent, let's assume it's public.
					$status = 'publish';
				} else {
					$status = relevanssi_get_post_status( $parent );
				}
			} else {
				$status = $post->post_status;
			}
			return $status;
		} else {
			return new WP_Error( 'not_found', 'Something went wrong.' );
		}
	}
}

/**
 * Returns the post type.
 *
 * Replacement for get_post_type() that uses the Relevanssi post cache to reduce the
 * number of database calls required.
 *
 * @global array $relevanssi_post_array The Relevanssi post cache array.
 *
 * @param int $post_id The post ID.
 *
 * @return string The post type.
 */
function relevanssi_get_post_type( $post_id ) {
	global $relevanssi_post_array;

	$original_id = $post_id;
	$blog_id     = get_current_blog_id();
	$post_id     = $blog_id . '|' . $post_id;

	if ( isset( $relevanssi_post_array[ $post_id ] ) ) {
		return $relevanssi_post_array[ $post_id ]->post_type;
	} else {
		// No hit from the cache; let's fetch.
		$post = relevanssi_get_post( $original_id, $blog_id );

		if ( is_wp_error( $post ) ) {
			$post->add_data(
				'not_found',
				"relevanssi_get_post_type() didn't get a post, relevanssi_get_post() returned null."
			);
			return $post;
		} elseif ( $post ) {
			return $post->post_type;
		} else {
			return new WP_Error( 'not_found', 'Something went wrong.' );
		}
	}
}

/**
 * Adds synonyms to a search query.
 *
 * Takes a search query and adds synonyms to it.
 *
 * @param string $query The source query.
 *
 * @return string The query with synonyms added.
 */
function relevanssi_add_synonyms( $query ) {
	if ( empty( $query ) ) {
		return $query;
	}

	$current_language = relevanssi_get_current_language();
	$synonym_data     = get_option( 'relevanssi_synonyms', array() );
	$synonym_list     = isset( $synonym_data[ $current_language ] ) ? $synonym_data[ $current_language ] : '';
	if ( $synonym_list ) {
		$synonyms     = array();
		$synonym_list = relevanssi_strtolower( $synonym_list );
		$pairs        = explode( ';', $synonym_list );

		foreach ( $pairs as $pair ) {
			if ( empty( $pair ) ) {
				// Skip empty rows.
				continue;
			}

			$parts = explode( '=', $pair );

			if ( count( $parts ) < 2 ) {
				continue;
			}

			$key   = strval( trim( $parts[0] ) );
			$value = trim( $parts[1] );

			if ( is_numeric( $key ) ) {
				$key = " $key";
			}
			$synonyms[ $key ][ $value ] = true;
		}

		if ( count( $synonyms ) > 0 ) {
			$query       = str_replace( array( '”', '“' ), '"', $query );
			$phrases     = relevanssi_extract_phrases( $query );
			$new_phrases = array();
			/**
			 * Controls how synonyms are handled when they appear inside
			 * phrases.
			 *
			 * @param bool If true, synonyms inside phrases create new phrases.
			 * If false, synonyms inside phrases are ignored.
			 */
			if ( apply_filters( 'relevanssi_phrase_synonyms', true ) ) {
				foreach ( $phrases as $phrase ) {
					$new_phrases[] = $phrase;
					$words         = explode( ' ', $phrase );
					foreach ( array_keys( $synonyms ) as $synonym_source ) {
						if ( in_array( $synonym_source, $words, true ) ) {
							foreach ( array_keys( $synonyms[ $synonym_source ] ) as $synonym_replacement ) {
								$new_phrases[] = str_replace( $synonym_source, $synonym_replacement, $phrase );
							}
						}
					}
				}
			} else {
				$new_phrases = $phrases;
			}

			$query = trim(
				str_replace(
					array_map( 'relevanssi_add_quotes', $phrases ),
					'',
					$query
				)
			);

			$new_terms = array();
			$terms     = array_keys( relevanssi_tokenize( $query, false ) ); // Remove stopwords is false here.
			if ( ! in_array( $query, $terms, true ) ) {
				// Include the whole query in the terms, unless it's not there already.
				$terms[] = $query;
			}

			foreach ( $terms as $term ) {
				$term = trim( $term );
				if ( is_numeric( $term ) ) {
					$term = " $term";
				}
				if ( in_array( $term, array_keys( $synonyms ), true ) ) { // Strval(), otherwise numbers cause problems.
					if ( isset( $synonyms[ strval( $term ) ] ) ) { // Necessary, otherwise terms like "02" can cause problems.
						$new_terms = array_merge( $new_terms, array_keys( $synonyms[ strval( $term ) ] ) );
					}
				}
			}
			if ( count( $new_phrases ) > 0 ) {
				$new_terms = array_merge(
					$new_terms,
					array_map( 'relevanssi_add_quotes', $new_phrases )
				);
			}
			if ( count( $new_terms ) > 0 ) {
				$new_terms = array_unique( $new_terms );
				foreach ( $new_terms as $new_term ) {
					$query .= " $new_term";
				}
			}
		}
	}

	return trim( $query );
}

/**
 * Updates the 'relevanssi_doc_count' option.
 *
 * The 'relevanssi_doc_count' option contains the number of documents in the
 * Relevanssi index. This function calculates the value and stores it in the
 * option.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variable, used for table names.
 * @return int    The doc count.
 */
function relevanssi_update_doc_count() {
	global $wpdb, $relevanssi_variables;
	$doc_count = $wpdb->get_var( 'SELECT COUNT(DISTINCT(doc)) FROM ' . $relevanssi_variables['relevanssi_table'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	update_option( 'relevanssi_doc_count', is_null( $doc_count ) ? 0 : $doc_count );

	return $doc_count;
}

/**
 * Launches an asynchronous action to update the doc count and other counts.
 *
 * This function should be used instead of relevanssi_update_doc_count().
 */
function relevanssi_async_update_doc_count() {
	relevanssi_launch_ajax_action( 'relevanssi_update_counts' );
}


/**
 * Adjusts Relevanssi variables when switch_blog() happens.
 *
 * This function attaches to the 'switch_blog' action hook and adjusts the table
 * names in the global $relevanssi_variables array to match the new blog.
 *
 * @global array  $relevanssi_variables The global Relevanssi variables.
 * @global object $wpdb                 The WordPress database interface.
 *
 * @author Teemu Muikku
 *
 * @param int $new_blog  The new blog ID.
 * @param int $prev_blog The old blog ID.
 */
function relevanssi_switch_blog( $new_blog, $prev_blog ) {
	global $relevanssi_variables, $wpdb;

	if ( ! isset( $relevanssi_variables ) || ! isset( $relevanssi_variables['relevanssi_table'] ) ) {
		return;
	}

	$relevanssi_variables['relevanssi_table'] = $wpdb->prefix . 'relevanssi';
	$relevanssi_variables['log_table']        = $wpdb->prefix . 'relevanssi_log';
}

/**
 * Adds a highlight parameter to the permalink.
 *
 * Relevanssi requires a 'highligh' parameter to the permalinks in order to have
 * working highlights. This function adds the highlight. The function doesn't add
 * the parameter to the links pointing at the front page, because if we do that,
 * the link won't point to the front page anymore, but instead points to the blog
 * page.
 *
 * @global object $post The global post object.
 *
 * @param string $permalink The link to patch.
 * @param object $link_post The post object for the current link, global $post if
 * the parameter is set to null. Default null.
 *
 * @return string The link with the parameter added.
 */
function relevanssi_add_highlight( $permalink, $link_post = null ) {
	$highlight_docs = get_option( 'relevanssi_highlight_docs', 'off' );
	$query          = get_search_query();
	if ( isset( $highlight_docs ) && 'off' !== $highlight_docs && ! empty( $query ) ) {
		if ( ! relevanssi_is_front_page_id( isset( $link_post->ID ) ?? null ) ) {
			$query     = str_replace( '&quot;', '"', $query );
			$permalink = esc_attr( add_query_arg( array( 'highlight' => rawurlencode( $query ) ), $permalink ) );
		}
	}
	return $permalink;
}

/**
 * Checks if a post ID is the front page ID.
 *
 * Gets the front page ID from the `page_on_front` option and checks the given
 * ID against that.
 *
 * @param integer $post_id The post ID to check. If null, checks the global
 * $post ID. Default null.
 * @return boolean True if the post ID or global $post matches the front page.
 */
function relevanssi_is_front_page_id( int $post_id = null ) : bool {
	$frontpage_id = intval( get_option( 'page_on_front' ) );
	if ( $post_id === $frontpage_id ) {
		return true;
	} elseif ( isset( $post_id ) ) {
		return false;
	}

	global $post;
	if ( is_object( $post ) && $post->ID === $frontpage_id ) {
		return true;
	}
	return false;
}

/**
 * Adjusts the permalink to use the Relevanssi-generated link.
 *
 * This function is used to filter 'the_permalink', 'post_link' and
 * 'relevanssi_permalink'. It changes the permalink to point to
 * $post->relevanssi_link, if that exists. This means the permalinks to
 * user profiles and taxonomy terms work. This function also adds the
 * 'highlight' parameter to the URL.
 *
 * @global object $post The global post object.
 *
 * @param string     $link      The link to adjust.
 * @param object|int $link_post The post to modify, either WP post object or the
 * post ID. If null, use global $post. Defaults null.
 *
 * @return string The modified link.
 */
function relevanssi_permalink( $link, $link_post = null ) {
	if ( null === $link_post ) {
		global $post;
		$link_post = $post;
	} elseif ( is_int( $link_post ) ) {
		$link_post = get_post( $link_post );
	}
	// Using property_exists() to avoid troubles from magic variables.
	if ( is_object( $link_post ) && property_exists( $link_post, 'relevanssi_link' ) ) {
		// $link_post->relevanssi_link can still be false.
		if ( ! empty( $link_post->relevanssi_link ) ) {
			$link = $link_post->relevanssi_link;
		}
	}

	if ( is_search() && is_object( $link_post ) && property_exists( $link_post, 'relevance_score' ) ) {
		$link = relevanssi_add_highlight( $link, $link_post );
	}

	if ( function_exists( 'relevanssi_add_tracking' ) ) {
		$link = relevanssi_add_tracking( $link, $link_post );
	}

	return $link;
}

/**
 * Instructs a multisite installation to drop the tables.
 *
 * Attaches to 'wpmu_drop_tables' and adds the Relevanssi tables to the list of
 * tables to drop.
 *
 * @global array  $relevanssi_variables The Relevanssi global variables, used for
 * table names.
 *
 * @param array $tables The list of tables to drop.
 *
 * @return array Table list, with Relevanssi tables included.
 */
function relevanssi_wpmu_drop( $tables ) {
	global $relevanssi_variables;
	$tables[] = $relevanssi_variables['relevanssi_table'];
	$tables[] = $relevanssi_variables['stopword_table'];
	$tables[] = $relevanssi_variables['log_table'];
	return $tables;
}

/**
 * Displays the list of most common words in the index.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables.
 *
 * @param int     $limit  How many words to display, default 25.
 * @param boolean $wp_cli If true, return just a list of words. If false, print out
 * HTML code.
 *
 * @return array A list of words, if $wp_cli is true.
 */
function relevanssi_common_words( $limit = 25, $wp_cli = false ) {
	global $wpdb, $relevanssi_variables;

	if ( ! is_numeric( $limit ) ) {
		$limit = 25;
	}

	$words = $wpdb->get_results( 'SELECT COUNT(*) as cnt, term FROM ' . $relevanssi_variables['relevanssi_table'] . " GROUP BY term ORDER BY cnt DESC LIMIT $limit" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

	if ( ! $wp_cli ) {
		printf( '<h2>%s</h2>', esc_html__( '25 most common words in the index', 'relevanssi' ) );
		printf( '<p>%s</p>', esc_html__( "These words are excellent stopword material. A word that appears in most of the posts in the database is quite pointless when searching. This is also an easy way to create a completely new stopword list, if one isn't available in your language. Click the word to add the word to the stopword list. The word will also be removed from the index, so rebuilding the index is not necessary.", 'relevanssi' ) );

		?>
<input type="hidden" name="dowhat" value="add_stopword" />
<table class="form-table">
<tr>
	<th scope="row"><?php esc_html_e( 'Stopword Candidates', 'relevanssi' ); ?></th>
	<td>
<ul>
		<?php
		foreach ( $words as $word ) {
			$stop = __( 'Add to stopwords', 'relevanssi' );
			printf( '<li>%1$s (%2$d) <button name="term" value="%1$s" />%3$s</button>', esc_attr( $word->term ), esc_html( $word->cnt ), esc_html( $stop ) );
			if ( RELEVANSSI_PREMIUM ) {
				$body = __( 'Add to content stopwords', 'relevanssi' );
				printf( ' <button name="body_term" value="%1$s" />%3$s</button>', esc_attr( $word->term ), esc_html( $word->cnt ), esc_html( $body ) );
			}
			echo '</li>';
		}
		?>
	</ul>
	</td>
</tr>
</table>
		<?php
	}

	return $words;
}

/**
 * Returns a list of post types Relevanssi does not want to use.
 *
 * @return array An array of post type names.
 */
function relevanssi_get_forbidden_post_types() {
	return array(
		'nav_menu_item',        // Navigation menu items.
		'revision',             // Never index revisions.
		'acf',                  // Advanced Custom Fields.
		'acf-field',            // Advanced Custom Fields.
		'acf-field-group',      // Advanced Custom Fields.
		'oembed_cache',         // Mysterious caches.
		'customize_changeset',  // Customizer change sets.
		'user_request',         // User data request.
		'custom_css',           // Custom CSS data.
		'cpt_staff_lst_item',   // Staff List.
		'cpt_staff_lst',        // Staff List.
		'wp_block',             // Gutenberg block.
		'amp_validated_url',    // AMP.
		'jp_pay_order',         // Jetpack.
		'jp_pay_product',       // Jetpack.
		'jp_mem_plan',          // Jetpack.
		'tablepress_table',     // TablePress.
		'ninja-table',          // Ninja Tables.
		'shop_coupon',          // WooCommerce.
		'shop_order',           // WooCommerce.
		'shop_order_refund',    // WooCommerce.
		'wc_order_status',      // WooCommerce.
		'wc_order_email',       // WooCommerce.
		'shop_webhook',         // WooCommerce.
		'woo_product_tab',      // Woo Product Tab.
		'et_theme_builder',     // Divi.
		'et_template',          // Divi.
		'et_header_layout',     // Divi.
		'et_body_layout',       // Divi.
		'et_footer_layout',     // Divi.
		'wpforms',              // WP Forms.
		'amn_wpforms',          // WP Forms.
		'wpforms_log',          // WP Forms.
		'dlm_download_version', // Download Monitor.
		'wpcf7_contact_form',   // WP Contact Form 7.
		'amn_exact-metrics',    // Google Analytics Dashboard.
		'edd_commission',       // Easy Digital Downloads.
		'edd_payment',          // Easy Digital Downloads.
		'edd_discount',         // Easy Digital Downloads.
		'eddpointslog',         // Easy Digital Downloads.
		'edd_log',              // Easy Digital Downloads.
		'edd-zapier-sub',       // Easy Digital Downloads.
		'pys_event',            // Pixel Your Site.
		'wp-types-group',       // WP Types.
		'wp-types-term-group',  // WP Types.
		'wp-types-user-group',  // WP Types.
		'vc_grid_item',         // Visual Composer.
		'bigcommerce_task',     // BigCommerce.
		'slides',               // Qoda slides.
		'carousels',            // Qoda carousels.
		'pretty-link',          // Pretty Links.
		'fusion_tb_layout',     // Fusion Builder.
		'fusion_tb_section',    // Fusion Builder.
		'fusion_form',          // Fusion Builder.
		'fusion_icons',         // Fusion Builder.
		'fusion_template',      // Fusion Builder.
		'fusion_element',       // Fusion Builder.
		'acfe-dbt',             // ACF Extended.
		'acfe-form',            // ACF Extended.
		'acfe-dop',             // ACF Extended.
		'acfe-dpt',             // ACF Extended.
		'acfe-dt',              // ACF Extended.
		'um_form',              // Ultimate Member.
		'um_directory',         // Ultimate Member.
		'mailpoet_page',        // Mailpoet Page.
		'mc4wp_form',           // MailChimp.
		'elementor_font',       // Elementor.
		'elementor_icons',      // Elementor.
		'elementor_library',    // Elementor.
		'elementor_snippet',    // Elementor.
		'wffn_landing',         // WooFunnel.
		'wffn_ty',              // WooFunnel.
		'wffn_optin',           // WooFunnel.
		'wffn_oty',             // WooFunnel.
		'wp_template',          // Block templates.
		'memberpressrule',      // Memberpress.
		'memberpresscoupon',    // Memberpress.
		'fl-builder-template',  // Beaver Builder.
		'itsec-dashboard',      // iThemes Security.
		'itsec-dash-card',      // iThemes Security.
	);
}

/**
 * Returns a list of taxonomies Relevanssi does not want to use.
 *
 * @return array An array of taxonomy names.
 */
function relevanssi_get_forbidden_taxonomies() {
	return array(
		'nav_menu',                     // Navigation menus.
		'link_category',                // Link categories.
		'amp_validation_error',         // AMP.
		'product_visibility',           // WooCommerce.
		'wpforms_log_type',             // WP Forms.
		'amp_template',                 // AMP.
		'edd_commission_status',        // Easy Digital Downloads.
		'edd_log_type',                 // Easy Digital Downloads.
		'elementor_library_type',       // Elementor.
		'elementor_library_category',   // Elementor.
		'elementor_font_type',          // Elementor.
		'wp_theme',                     // WordPress themes.
		'fl-builder-template-category', // Beaver Builder.
		'fl-builder-template-type',     // Beaver Builder.
	);
}

/**
 * Filters out unwanted custom fields.
 *
 * Added to the relevanssi_custom_field_value filter hook. This function removes
 * visible custom fields that are known to contain unwanted content and also
 * removes ACF meta fields (fields where content begins with `field_`).
 *
 * @see relevanssi_index_custom_fields()
 *
 * @param array  $values The custom field values.
 * @param string $field  The custom field name.
 *
 * @return array Empty array for unwanted custom fields.
 */
function relevanssi_filter_custom_fields( $values, $field ) {
	$unwanted_custom_fields = array(
		'classic-editor-remember' => true,
		'php_everywhere_code'     => true,
	);
	if ( isset( $unwanted_custom_fields[ $field ] ) ) {
		$values = array();
	}

	if ( ! $values ) {
		return $values;
	}

	$values = array_map(
		function( $value ) {
			if ( is_string( $value ) && 'field_' === substr( $value, 0, 6 ) ) {
				return '';
			}
			return $value;
		},
		$values
	);

	return $values;
}

/**
 * Removes page builder short codes from content.
 *
 * Page builder shortcodes cause problems in excerpts and add junk to posts in
 * indexing. This function cleans them out.
 *
 * @param string $content The content to clean.
 *
 * @return string The content without page builder shortcodes.
 */
function relevanssi_remove_page_builder_shortcodes( $content ) {
	$context = current_filter();
	/**
	 * Filters the page builder shortcode.
	 *
	 * @param array  An array of page builder shortcode regexes.
	 * @param string Context, ie. the current filter hook, if you want your
	 * changes to only count for indexing or for excerpts. In indexing, this
	 * is 'relevanssi_post_content', for excerpts it's
	 * 'relevanssi_pre_excerpt_content'.
	 */
	$search_array = apply_filters(
		'relevanssi_page_builder_shortcodes',
		array(
			// Remove content.
			'/\[et_pb_code.*?\].*\[\/et_pb_code\]/im',
			'/\[et_pb_sidebar.*?\].*\[\/et_pb_sidebar\]/im',
			'/\[et_pb_fullwidth_slider.*?\].*\[\/et_pb_fullwidth_slider\]/im',
			'/\[et_pb_fullwidth_code.*?\].*\[\/et_pb_fullwidth_code\]/im',
			'/\[vc_raw_html.*?\].*\[\/vc_raw_html\]/im',
			'/\[fusion_imageframe.*?\].*\[\/fusion_imageframe\]/im',
			'/\[fusion_code.*?\].*\[\/fusion_code\]/im',
			// Remove only the tags.
			'/\[\/?et_pb.*?\]/im',
			'/\[\/?vc.*?\]/im',
			'/\[\/?mk.*?\]/im',
			'/\[\/?cs_.*?\]/im',
			'/\[\/?av_.*?\]/im',
			'/\[\/?fusion_.*?\]/im',
			'/\[maxmegamenu.*?\]/im',
			'/\[ai1ec.*?\]/im',
			'/\[eme_.*?\]/im',
			'/\[layerslider.*?\]/im',
			// Divi garbage.
			'/@ET-DC@.*?@/im',
		),
		$context
	);
	$content      = preg_replace( $search_array, ' ', $content );
	return $content;
}

/**
 * Blocks Relevanssi from the admin searches on specific post types.
 *
 * This function is added to relevanssi_search_ok, relevanssi_admin_search_ok,
 * and relevanssi_prevent_default_request hooks. When a search is made with
 * one of the listed post types, these filters will get a false response, which
 * means Relevanssi won't search and won't block the default request.
 *
 * @see relevanssi_prevent_default_request
 * @see relevanssi_search
 *
 * @param boolean  $allow Should the admin search be allowed.
 * @param WP_Query $query The query object.
 */
function relevanssi_block_on_admin_searches( $allow, $query ) {
	$blocked_post_types = array(
		'rc_blocks', // Reusable Content Blocks.
	);
	/**
	 * Filters the post types that are blocked in the admin search.
	 *
	 * In some cases you may want to enable Relevanssi in the admin backend,
	 * but don't want Relevanssi to search certain post types. To block
	 * Relevanssi from a specific post type, add the post type to this filter.
	 *
	 * @param array List of post types Relevanssi shouldn't try searching.
	 */
	$blocked_post_types = apply_filters(
		'relevanssi_admin_search_blocked_post_types',
		$blocked_post_types
	);
	if (
		isset( $query->query_vars['post_type'] ) &&
		in_array( $query->query_vars['post_type'], $blocked_post_types, true )
		) {
		$allow = false;
	}
	return $allow;
}

/**
 * Checks if user has relevanssi_indexing_restriction filter functions in use.
 *
 * Temporary check for the changes in the relevanssi_indexing_restriction filter
 * in 2.8/4.7. Remove eventually. The function runs all non-Relevanssi filters
 * on relevanssi_indexing_restriction and reports all that return a string.
 *
 * @see relevanssi_init()
 *
 * @return string The notice, if there's something to complain about, empty
 * string otherwise.
 */
function relevanssi_check_indexing_restriction() {
	$notice = '';
	if ( has_filter( 'relevanssi_indexing_restriction' ) ) {
		global $wp_filter;
		$callbacks = array_flip(
			array_keys(
				array_merge(
					array(),
					...$wp_filter['relevanssi_indexing_restriction']->callbacks
				)
			)
		);
		if ( isset( $callbacks['relevanssi_yoast_exclude'] ) ) {
			unset( $callbacks['relevanssi_yoast_exclude'] );
		}
		if ( isset( $callbacks['relevanssi_seopress_exclude'] ) ) {
			unset( $callbacks['relevanssi_seopress_exclude'] );
		}
		if ( isset( $callbacks['relevanssi_woocommerce_restriction'] ) ) {
			unset( $callbacks['relevanssi_woocommerce_restriction'] );
		}
		if ( ! empty( $callbacks ) ) {
			$returns_string = array();
			foreach ( array_keys( $callbacks ) as $callback ) {
				$return = call_user_func(
					$callback,
					array(
						'mysql'  => '',
						'reason' => '',
					)
				);
				if ( is_string( $return ) ) {
					$returns_string[] = '<code>' . $callback . '</code>';
				}
			}
			if ( $returns_string ) {
				$list_of_callbacks = implode( ', ', $returns_string );
				$notice            = <<<EOH
<div id="relevanssi-indexing_restriction-warning" class="notice notice-warn">
<p>The filter hook <code>relevanssi_indexing_restriction</code> was changed
recently. <a href="https://www.relevanssi.com/knowledge-base/controlling-attachment-types-index/">More
information can be found here</a>. You're using the filter, so make sure your
filter functions have been updated. Check these functions that return wrong
format: $list_of_callbacks.</p></div>
EOH;
			}
		}
	}
	return $notice;
}

/**
 * Fetches the data and generates the HTML for the "How Relevanssi sees this
 * post".
 *
 * @param int     $post_id The post ID.
 * @param boolean $display If false, add "display: none" style to the element.
 * @param string  $type    One of 'post', 'term' or 'user'. Default 'post'.
 *
 * @return string The HTML code for the "How Relevanssi sees this post".
 */
function relevanssi_generate_how_relevanssi_sees( $post_id, $display = true, $type = 'post' ) {
	$style = '';
	if ( ! $display ) {
		$style = 'style="display: none"';
	}

	$element = '<div id="relevanssi_sees_container" ' . $style . '>';

	$data = relevanssi_fetch_sees_data( $post_id, $type );

	if ( empty( $data['terms_list'] ) && empty( $data['reason'] ) ) {
		$element .= '<p>'
			// Translators: %d is the post ID.
			. sprintf( __( 'Nothing found for ID %d.', 'relevanssi' ), $post_id )
			. '</p>';
		$element .= '</div>';
		return $element;
	}

	if ( ! empty( $data['reason'] ) ) {
		$element .= '<h3>' . esc_html__( 'Possible reasons this post is not indexed', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['reason'] ) . '</p>';
	}
	if ( ! empty( $data['title'] ) ) {
		$element .= '<h3>' . esc_html__( 'The title', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['title'] ) . '</p>';
	}
	if ( ! empty( $data['content'] ) ) {
		$element .= '<h3>' . esc_html__( 'The content', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['content'] ) . '</p>';
	}
	if ( ! empty( $data['comment'] ) ) {
		$element .= '<h3>' . esc_html__( 'Comments', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['comment'] ) . '</p>';
	}
	if ( ! empty( $data['tag'] ) ) {
		$element .= '<h3>' . esc_html__( 'Tags', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['tag'] ) . '</p>';
	}
	if ( ! empty( $data['category'] ) ) {
		$element .= '<h3>' . esc_html__( 'Categories', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['category'] ) . '</p>';
	}
	if ( ! empty( $data['taxonomy'] ) ) {
		$element .= '<h3>' . esc_html__( 'Other taxonomies', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['taxonomy'] ) . '</p>';
	}
	if ( ! empty( $data['link'] ) ) {
		$element .= '<h3>' . esc_html__( 'Links', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['link'] ) . '</p>';
	}
	if ( ! empty( $data['author'] ) ) {
		$element .= '<h3>' . esc_html__( 'Authors', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['author'] ) . '</p>';
	}
	if ( ! empty( $data['excerpt'] ) ) {
		$element .= '<h3>' . esc_html__( 'Excerpt', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['excerpt'] ) . '</p>';
	}
	if ( ! empty( $data['customfield'] ) ) {
		$element .= '<h3>' . esc_html__( 'Custom fields', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['customfield'] ) . '</p>';
	}
	if ( ! empty( $data['mysql'] ) ) {
		$element .= '<h3>' . esc_html__( 'MySQL content', 'relevanssi' ) . '</h3>';
		$element .= '<p>' . esc_html( $data['mysql'] ) . '</p>';
	}
	$element .= '</div>';
	return $element;
}

/**
 * Fetches the Relevanssi indexing data for a post.
 *
 * @param int    $post_id The post ID.
 * @param string $type    One of 'post', 'term', or 'user'. Default 'post'.
 *
 * @global array  $relevanssi_variables The Relevanssi global variables array,
 * used for the database table name.
 * @global object $wpdb                 The WordPress database interface.
 *
 * @return array The indexed terms for various parts of the post in an
 * associative array.
 */
function relevanssi_fetch_sees_data( $post_id, $type = 'post' ) {
	global $wpdb, $relevanssi_variables;

	if ( 'post' === $type ) {
		$query = $wpdb->prepare(
			'SELECT * FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE doc = %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			$post_id
		);
	}
	if ( 'term' === $type ) {
		$query = $wpdb->prepare(
			'SELECT * FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE type NOT IN ("post", "user") AND item = %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			$post_id
		);
	}
	if ( 'user' === $type ) {
		$query = $wpdb->prepare(
			'SELECT * FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE type = "user" AND item = %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			$post_id
		);
	}

	$terms_list = $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

	$terms['content']     = array();
	$terms['title']       = array();
	$terms['comment']     = array();
	$terms['tag']         = array();
	$terms['link']        = array();
	$terms['author']      = array();
	$terms['category']    = array();
	$terms['excerpt']     = array();
	$terms['taxonomy']    = array();
	$terms['customfield'] = array();
	$terms['mysql']       = array();

	foreach ( $terms_list as $row ) {
		if ( $row->content > 0 ) {
			$terms['content'][] = $row->term;
		}
		if ( $row->title > 0 ) {
			$terms['title'][] = $row->term;
		}
		if ( $row->comment > 0 ) {
			$terms['comment'][] = $row->term;
		}
		if ( $row->tag > 0 ) {
			$terms['tag'][] = $row->term;
		}
		if ( $row->link > 0 ) {
			$terms['link'][] = $row->term;
		}
		if ( $row->author > 0 ) {
			$terms['author'][] = $row->term;
		}
		if ( $row->category > 0 ) {
			$terms['category'][] = $row->term;
		}
		if ( $row->excerpt > 0 ) {
			$terms['excerpt'][] = $row->term;
		}
		if ( $row->taxonomy > 0 ) {
			$terms['taxonomy'][] = $row->term;
		}
		if ( $row->customfield > 0 ) {
			$terms['customfield'][] = $row->term;
		}
		if ( $row->mysqlcolumn > 0 ) {
			$terms['mysql'][] = $row->term;
		}
	}

	$reason = get_post_meta( $post_id, '_relevanssi_noindex_reason', true );

	return array(
		'content'     => implode( ' ', $terms['content'] ),
		'title'       => implode( ' ', $terms['title'] ),
		'comment'     => implode( ' ', $terms['comment'] ),
		'tag'         => implode( ' ', $terms['tag'] ),
		'link'        => implode( ' ', $terms['link'] ),
		'author'      => implode( ' ', $terms['author'] ),
		'category'    => implode( ' ', $terms['category'] ),
		'excerpt'     => implode( ' ', $terms['excerpt'] ),
		'taxonomy'    => implode( ' ', $terms['taxonomy'] ),
		'customfield' => implode( ' ', $terms['customfield'] ),
		'mysql'       => implode( ' ', $terms['mysql'] ),
		'reason'      => $reason,
		'terms_list'  => $terms_list,
	);
}

/**
 * Generates a list of custom fields for a post.
 *
 * Starts from the custom field setting, expands "all" or "visible" if
 * necessary, makes sure "_relevanssi_pdf_content" is not removed, applies the
 * 'relevanssi_index_custom_fields' filter and 'relevanssi_add_repeater_fields'
 * function.
 *
 * @param int          $post_id       The post ID.
 * @param array|string $custom_fields An array of custom field names, or "all"
 * or "visible". If null, uses relevanssi_get_custom_fields().
 *
 * @return array An array of custom field names.
 */
function relevanssi_generate_list_of_custom_fields( $post_id, $custom_fields = null ) {
	if ( ! $custom_fields ) {
		$custom_fields = relevanssi_get_custom_fields();
	}
	$remove_underscore_fields = 'visible' === $custom_fields ? true : false;
	if ( 'all' === $custom_fields || 'visible' === $custom_fields ) {
		$custom_fields = get_post_custom_keys( $post_id );
	}

	if ( ! is_array( $custom_fields ) ) {
		$custom_fields = array();
	}

	$custom_fields = array_unique( $custom_fields );
	if ( $remove_underscore_fields ) {
		$custom_fields = array_filter(
			$custom_fields,
			function( $field ) {
				if ( '_relevanssi_pdf_content' === $field || '_' !== substr( $field, 0, 1 ) ) {
					return $field;
				}
			}
		);
	}

	// Premium includes some support for ACF repeater fields.
	if ( function_exists( 'relevanssi_add_repeater_fields' ) ) {
		relevanssi_add_repeater_fields( $custom_fields, $post_id );
	}

	/**
	 * Filters the list of custom fields to index before indexing.
	 *
	 * @param array $custom_fields List of custom field names.
	 * @param int   $post_id      The post ID.
	 */
	$custom_fields = apply_filters( 'relevanssi_index_custom_fields', $custom_fields, $post_id );
	if ( ! is_array( $custom_fields ) ) {
		return array();
	}
	$custom_fields = array_filter( $custom_fields );

	return $custom_fields;
}

/**
 * Updates the relevanssi_synonyms setting from a simple string to an array
 * that is required for multilingual synonyms.
 */
function relevanssi_update_synonyms_setting() {
	$synonyms = get_option( 'relevanssi_synonyms' );
	if ( is_object( $synonyms ) ) {
		$array_synonyms = (array) $synonyms;
		update_option( 'relevanssi_synonyms', $array_synonyms );
		return;
	}

	$current_language = relevanssi_get_current_language();

	$array_synonyms[ $current_language ] = $synonyms;
	update_option( 'relevanssi_synonyms', $array_synonyms );
}

/**
 * Replaces synonyms in an array with their original counterparts.
 *
 * If there's a synonym "dog=hound", and the array of terms contains "hound",
 * it will be replaced with "dog". If there are multiple matches, all
 * replacements will happen.
 *
 * @param array $terms An array of words.
 *
 * @return array An array of words with backwards synonym replacement.
 */
function relevanssi_replace_synonyms_in_terms( array $terms ) : array {
	$all_synonyms = get_option( 'relevanssi_synonyms', array() );
	$synonyms     = explode( ';', $all_synonyms[ relevanssi_get_current_language() ] ?? '' );

	return array_map(
		function ( $term ) use ( $synonyms ) {
			$new_term = array();
			foreach ( $synonyms as $pair ) {
				list( $key, $value ) = explode( '=', $pair );
				if ( $value === $term ) {
					$new_term[] = $key;
				}
			}
			if ( ! empty( $new_term ) ) {
				$term = implode( ' ', $new_term );
			}
			return $term;
		},
		$terms
	);
}

/**
 * Replaces stemmed words in an array with their original counterparts.
 *
 * @param array $terms     An array of words where to replace.
 * @param array $all_terms An array of all words to stem. Default $terms.
 *
 * @return array An array of words with stemmed words replaced with their
 * originals.
 */
function relevanssi_replace_stems_in_terms( array $terms, array $all_terms = null ) : array {
	if ( ! $all_terms ) {
		$all_terms = $terms;
	}
	$term_for_stem = array();
	foreach ( $all_terms as $term ) {
		$term_and_stem = relevanssi_tokenize( $term, false, -1 );
		foreach ( array_keys( $term_and_stem ) as $word ) {
			if ( $word === $term ) {
				continue;
			}
			$term_for_stem[ $word ] = $term;
		}
	}

	if ( empty( $term_for_stem ) ) {
		return $terms;
	}

	return array_unique(
		array_map(
			function ( $term ) use ( $term_for_stem ) {
				return $term_for_stem[ $term ] ?? $term;
			},
			$terms
		)
	);
}

/**
 * Returns an array of bot user agents for Relevanssi to block.
 *
 * The bot user agent is the value and a human-readable name (not used for
 * anything) is in the index. This same list is used for different contexts,
 * and there are separate filters for modifying the list in various contexts.
 *
 * @return array An array of name => user-agent pairs.
 */
function relevanssi_bot_block_list() : array {
	$bots = array(
		'Google Mediapartners' => 'Mediapartners-Google',
		'GoogleBot'            => 'Googlebot',
		'Bing'                 => 'Bingbot',
		'Yahoo'                => 'Slurp',
		'DuckDuckGo'           => 'DuckDuckBot',
		'Baidu'                => 'Baiduspider',
		'Yandex'               => 'YandexBot',
		'Sogou'                => 'Sogou',
		'Exalead'              => 'Exabot',
		'Majestic'             => 'MJ12Bot',
		'Ahrefs'               => 'AhrefsBot',
	);
	return $bots;
}
