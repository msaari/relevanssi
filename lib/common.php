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
 * Multibyte friendly case-insensitive string comparison.
 *
 * If multibyte string functions are available, do strcmp() after using
 * mb_strtoupper() to both strings. Otherwise use strcasecmp().
 *
 * @param string $str1     First string to compare.
 * @param string $str2     Second string to compare.
 * @param string $encoding The encoding to use. Defaults to mb_internal_encoding().
 *
 * @return int $val Returns < 0 if str1 is less than str2; > 0 if str1 is greater
 * than str2, and 0 if they are equal.
 */
function relevanssi_mb_strcasecmp( $str1, $str2, $encoding = null ) {
	if ( ! function_exists( 'mb_internal_encoding' ) ) {
		return strcasecmp( $str1, $str2 );
	} else {
		if ( null === $encoding ) {
			$encoding = mb_internal_encoding();
		}
		return strcmp( mb_strtoupper( $str1, $encoding ), mb_strtoupper( $str2, $encoding ) );
	}
}

/**
 * Multibyte friendly strtolower.
 *
 * If multibyte string functions are available, returns mb_strtolower() and falls
 * back to strtolower() if multibyte functions are not available.
 *
 * @param string $string The string to lowercase.
 *
 * @return string $string The string in lowercase.
 */
function relevanssi_strtolower( $string ) {
	if ( ! function_exists( 'mb_strtolower' ) ) {
		return strtolower( $string );
	} else {
		return mb_strtolower( $string );
	}
}

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
	$hits = array(
		'body'        => 0,
		'title'       => 0,
		'comment'     => 0,
		'author'      => 0,
		'excerpt'     => 0,
		'customfield' => 0,
		'mysqlcolumn' => 0,
		'taxonomy'    => array(
			'tag'      => 0,
			'category' => 0,
			'taxonomy' => 0,
		),
		'score'       => 0,
		'terms'       => array(),
	);
	if ( isset( $data['body_matches'][ $post->ID ] ) ) {
		$hits['body'] = $data['body_matches'][ $post->ID ];
	}
	if ( isset( $data['title_matches'][ $post->ID ] ) ) {
		$hits['title'] = $data['title_matches'][ $post->ID ];
	}
	if ( isset( $data['tag_matches'][ $post->ID ] ) ) {
		$hits['taxonomy']['tag'] = $data['tag_matches'][ $post->ID ];
	}
	if ( isset( $data['category_matches'][ $post->ID ] ) ) {
		$hits['taxonomy']['category'] = $data['category_matches'][ $post->ID ];
	}
	if ( isset( $data['taxonomy_matches'][ $post->ID ] ) ) {
		$hits['taxonomy']['taxonomy'] = $data['taxonomy_matches'][ $post->ID ];
	}
	if ( isset( $data['comment_matches'][ $post->ID ] ) ) {
		$hits['comment'] = $data['comment_matches'][ $post->ID ];
	}
	if ( isset( $data['author_matches'][ $post->ID ] ) ) {
		$hits['author'] = $data['author_matches'][ $post->ID ];
	}
	if ( isset( $data['excerpt_matches'][ $post->ID ] ) ) {
		$hits['excerpt'] = $data['excerpt_matches'][ $post->ID ];
	}
	if ( isset( $data['customfield_matches'][ $post->ID ] ) ) {
		$hits['customfield'] = $data['customfield_matches'][ $post->ID ];
	}
	if ( isset( $data['mysqlcolumn_matches'][ $post->ID ] ) ) {
		$hits['mysqlcolumn'] = $data['mysqlcolumn_matches'][ $post->ID ];
	}
	if ( isset( $data['scores'][ $post->ID ] ) ) {
		$hits['score'] = round( $data['scores'][ $post->ID ], 2 );
	}
	if ( isset( $data['term_hits'][ $post->ID ] ) ) {
		$hits['terms'] = $data['term_hits'][ $post->ID ];
		arsort( $hits['terms'] );
	}
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
	);
	$result        = ' ' . str_replace( $replace_these, $replacements, $text );

	/**
	 * Filters the search result breakdown.
	 *
	 * If you use the "Show breakdown of search hits in excerpts" option, this
	 * filter lets you modify the breakdown before it is added to the excerpt.
	 *
	 * @param string $result The breakdown.
	 */
	return apply_filters( 'relevanssi_show_matches', $result );
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
 * @global array  $relevanssi_post_types An array of post types, to be used by
 * relevanssi_get_post_type() (again to avoid DB calls).
 * @global object $wpdb                  The WordPress database interface.
 *
 * @param array $matches An array of search matches.
 */
function relevanssi_populate_array( $matches ) {
	global $relevanssi_post_array, $relevanssi_post_types, $wpdb;

	// Doing this makes life faster.
	wp_suspend_cache_addition( true );

	$ids = array();
	foreach ( $matches as $match ) {
		array_push( $ids, $match->doc );
	}

	$ids   = array_keys( array_flip( $ids ) ); // Remove duplicate IDs.
	$ids   = implode( ', ', $ids );
	$posts = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE id IN ( $ids )", OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	foreach ( $posts as $post ) {
		$relevanssi_post_array[ $post->ID ] = $post;
		$relevanssi_post_types[ $post->ID ] = $post->post_type;
	}

	// Re-enable caching.
	wp_suspend_cache_addition( false );
}

/**
 * Fetches the taxonomy based on term ID.
 *
 * Fetches the taxonomy from wp_term_taxonomy based on term_id.
 *
 * @global object $wpdb The WordPress database interface.
 * @param int $term_id The term ID.
 * @deprecated Will be removed in future versions.
 * @return string $taxonomy The term taxonomy.
 */
function relevanssi_get_term_taxonomy( $term_id ) {
	global $wpdb;

	$taxonomy = $wpdb->get_var( $wpdb->prepare( "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $taxonomy;
}

/**
 * Extracts phrases from the search query.
 *
 * Finds all phrases wrapped in quotes from the search query.
 *
 * @param string $query The query.
 *
 * @return array An array of phrases (strings).
 */
function relevanssi_extract_phrases( $query ) {
	$strpos_function = 'strpos';
	if ( function_exists( 'mb_strpos' ) ) {
		$strpos_function = 'mb_strpos';
	}
	$substr_function = 'substr';
	if ( function_exists( 'mb_substr' ) ) {
		$substr_function = 'mb_substr';
	}

	// iOS uses “” as the default quotes, so Relevanssi needs to understand that as
	// well.
	$normalized_query = str_replace( array( '”', '“' ), '"', $query );
	$pos              = call_user_func( $strpos_function, $normalized_query, '"' );

	$phrases = array();
	while ( false !== $pos ) {
		$start = $pos;
		$end   = call_user_func( $strpos_function, $normalized_query, '"', $start + 1 );

		if ( false === $end ) {
			// Just one " in the query.
			$pos = $end;
			continue;
		}
		$phrase = call_user_func( $substr_function, $normalized_query, $start + 1, $end - $start - 1 );
		$phrase = trim( $phrase );

		// Do not count single-word phrases as phrases.
		if ( ! empty( $phrase ) && count( explode( ' ', $phrase ) ) > 1 ) {
			$phrases[] = $phrase;
		}
		$pos = $end;
	}

	return $phrases;
}

/**
 * Generates the MySQL code for restricting the search to phrase hits.
 *
 * This function uses relevanssi_extract_phrases() to figure out the phrases in the
 * search query, then generates MySQL queries to restrict the search to the posts
 * containing those phrases in the title, content, taxonomy terms or meta fields.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param string $search_query The search query.
 * @param string $operator     The search operator (AND or OR).
 *
 * @return string $queries If not phrase hits are found, an empty string; otherwise
 * MySQL queries to restrict the search.
 */
function relevanssi_recognize_phrases( $search_query, $operator = 'AND' ) {
	global $wpdb;

	$phrases = relevanssi_extract_phrases( $search_query );
	$status  = relevanssi_valid_status_array();

	// Add "inherit" to the list of allowed statuses to include attachments.
	if ( ! strstr( $status, 'inherit' ) ) {
		$status .= ",'inherit'";
	}

	$all_queries = array();
	if ( 0 === count( $phrases ) ) {
		return $all_queries;
	}

	foreach ( $phrases as $phrase ) {
		$queries = array();
		$phrase  = $wpdb->esc_like( $phrase );
		$phrase  = str_replace( '‘', '_', $phrase );
		$phrase  = str_replace( '’', '_', $phrase );
		$phrase  = str_replace( "'", '_', $phrase );
		$phrase  = str_replace( '"', '_', $phrase );
		$phrase  = str_replace( '”', '_', $phrase );
		$phrase  = str_replace( '“', '_', $phrase );
		$phrase  = str_replace( '„', '_', $phrase );
		$phrase  = str_replace( '´', '_', $phrase );
		$phrase  = esc_sql( $phrase );

		$excerpt = '';
		if ( 'on' === get_option( 'relevanssi_index_excerpt' ) ) {
			$excerpt = " OR post_excerpt LIKE '%$phrase%'";
		}

		$query = "(SELECT ID FROM $wpdb->posts
			WHERE (post_content LIKE '%$phrase%' OR post_title LIKE '%$phrase%' $excerpt)
			AND post_status IN ($status))";

		$queries[] = $query;

		$taxonomies = get_option( 'relevanssi_index_taxonomies_list', array() );
		if ( $taxonomies ) {
			$taxonomies_escaped = implode( "','", array_map( 'esc_sql', $taxonomies ) );
			$taxonomies_sql     = "AND s.taxonomy IN ('$taxonomies_escaped')";

			$query = "(SELECT ID FROM $wpdb->posts as p, $wpdb->term_relationships as r, $wpdb->term_taxonomy as s, $wpdb->terms as t
				WHERE r.term_taxonomy_id = s.term_taxonomy_id AND s.term_id = t.term_id AND p.ID = r.object_id
				$taxonomies_sql
				AND t.name LIKE '%$phrase%' AND p.post_status IN ($status))";

			$queries[] = $query;
		}

		$custom_fields = relevanssi_get_custom_fields();
		if ( $custom_fields ) {
			$keys = '';

			if ( is_array( $custom_fields ) ) {
				$custom_fields_escaped = implode( "','", array_map( 'esc_sql', $custom_fields ) );
				$keys                  = "AND m.meta_key IN ('$custom_fields_escaped')";
			}

			if ( 'visible' === $custom_fields ) {
				$keys = "AND (m.meta_key NOT LIKE '_%' OR m.meta_key = '_relevanssi_pdf_content')";
			}

			$query = "(SELECT ID
				FROM $wpdb->posts AS p, $wpdb->postmeta AS m
				WHERE p.ID = m.post_id
				$keys
				AND m.meta_value LIKE '%$phrase%'
				AND p.post_status IN ($status))";

			$queries[] = $query;
		}

		if ( 'on' === get_option( 'relevanssi_index_pdf_parent' ) ) {
			$query = "(SELECT parent.ID
			FROM $wpdb->posts AS p, $wpdb->postmeta AS m, $wpdb->posts AS parent
			WHERE p.ID = m.post_id
			AND p.post_parent = parent.ID
			AND m.meta_key = '_relevanssi_pdf_content'
			AND m.meta_value LIKE '%$phrase%'
			AND p.post_status = 'inherit')";

			$queries[] = $query;
		}

		$queries       = implode( ' OR relevanssi.doc IN ', $queries );
		$queries       = "(relevanssi.doc IN $queries)";
		$all_queries[] = $queries;
	}

	$operator = strtoupper( $operator );
	if ( 'AND' !== $operator && 'OR' !== $operator ) {
		$operator = 'AND';
	}

	if ( ! empty( $all_queries ) ) {
		$all_queries = ' AND ( ' . implode( ' ' . $operator . ' ', $all_queries ) . ' ) ';
	}

	return $all_queries;
}

/**
 * Strips invisible elements from text.
 *
 * Strips <style>, <script>, <object>, <embed>, <applet>, <noscript>, <noembed>,
 * <iframe>, and <del> tags and their contents from the text.
 *
 * @param string $text The source text.
 *
 * @return string The processed text.
 */
function relevanssi_strip_invisibles( $text ) {
	$text = preg_replace(
		array(
			'@<style[^>]*?>.*?</style>@siu',
			'@<script[^>]*?.*?</script>@siu',
			'@<object[^>]*?.*?</object>@siu',
			'@<embed[^>]*?.*?</embed>@siu',
			'@<applet[^>]*?.*?</applet>@siu',
			'@<noscript[^>]*?.*?</noscript>@siu',
			'@<noembed[^>]*?.*?</noembed>@siu',
			'@<iframe[^>]*?.*?</iframe>@siu',
			'@<del[^>]*?.*?</del>@siu',
		),
		' ',
		$text
	);
	return $text;
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
			$custom_fields       = explode( ',', $custom_fields );
			$count_custom_fields = count( $custom_fields );
			for ( $i = 0; $i < $count_custom_fields; $i++ ) {
				$custom_fields[ $i ] = trim( $custom_fields[ $i ] );
			}
		}
	} else {
		$custom_fields = false;
	}
	return $custom_fields;
}

/**
 * Trims multibyte strings.
 *
 * Removes the 194+160 non-breakable spaces, removes null bytes and removes whitespace.
 *
 * @param string $string The source string.
 *
 * @return string Trimmed string.
 */
function relevanssi_mb_trim( $string ) {
	$string = str_replace( chr( 194 ) . chr( 160 ), '', $string );
	$string = str_replace( "\0", '', $string );
	$string = preg_replace( '/(^\s+)|(\s+$)/us', '', $string );
	return $string;
}

/**
 * Wraps the relevanssi_mb_trim() function so that it can be used as a callback for
 * array_walk().
 *
 * @since 2.1.4
 *
 * @see relevanssi_mb_trim.
 *
 * @param string $string String to trim.
 */
function relevanssi_array_walk_trim( &$string ) {
	$string = relevanssi_mb_trim( $string );
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
	$a = preg_replace( '/<[^>]*>/', ' ', $a );

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
		'·'                     => '',
		'…'                     => '',
		'€'                     => '',
		'®'                     => '',
		'©'                     => '',
		'™'                     => '',
		'&shy;'                 => '',
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
		if ( isset( $query->query_vars['post_type'] ) && isset( $query->query_vars['post_status'] ) ) {
			if ( 'attachment' === $query->query_vars['post_type'] && 'inherit,private' === $query->query_vars['post_status'] ) {
				// This is a media library search; do not meddle.
				return $request;
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

		if ( $query->is_admin && 'page' === $query->query_vars['post_type'] ) {
			// Relevanssi doesn't work on the page screen, so disable.
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
 * @param boolean|string $remove_stops    If true, stopwords are removed. If 'body',
 *                                        also removes the body stopwords. Default
 *                                        true.
 * @param int            $min_word_length The minimum word length to include.
 *                                        Default -1.
 */
function relevanssi_tokenize( $string, $remove_stops = true, $min_word_length = -1 ) {
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

		if ( RELEVANSSI_PREMIUM ) {
			/**
			 * Fires Premium tokenizer.
			 *
			 * Filters the token through the Relevanssi Premium tokenizer to add some
			 * Premium features to the tokenizing (mostly stemming).
			 *
			 * @param string $token Search query token.
			 */
			$token = apply_filters( 'relevanssi_premium_tokenizer', $token );
		}

		if ( $accept ) {
			$token = relevanssi_mb_trim( $token );
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
		// No hit from the cache; let's add this post to the cache.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$relevanssi_post_array[ $post_id ] = $post;
		return $post->post_status;
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
	if ( isset( $relevanssi_post_array[ $post_id ] ) ) {
		return $relevanssi_post_array[ $post_id ]->post_type;
	} else {
		// No hit from the cache; let's add this post to the cache.
		$post = relevanssi_get_post( $post_id );

		if ( is_wp_error( $post ) ) {
			$post->add_data( 'not_found', "relevanssi_get_post_type() didn't get a post, relevanssi_get_post() returned null." );
			return $post;
		} elseif ( $post ) {
			$relevanssi_post_array[ $post_id ] = $post;
			return $post->post_type;
		} else {
			return new WP_Error( 'not_found', 'Something went wrong.' );
		}
	}
}

/**
 * Prints out a list of tags for post.
 *
 * Replacement for the_tags() that does the same, but applies Relevanssi search term
 * highlighting on the results.
 *
 * @param string  $before    What is printed before the tags, default null.
 * @param string  $separator The separator between items, default ', '.
 * @param string  $after     What is printed after the tags, default ''.
 * @param boolean $echo      If true, echo, otherwise return the result. Default true.
 * @param int     $post_id   The post ID. Default current post ID (in the Loop).
 */
function relevanssi_the_tags( $before = null, $separator = ', ', $after = '', $echo = true, $post_id = null ) {
	$tag_list = get_the_tag_list( $before, $separator, $after, $post_id );
	$found    = preg_match_all( '~<a href=".*?" rel="tag">(.*?)</a>~', $tag_list, $matches );
	if ( $found ) {
		$originals   = $matches[0];
		$tag_names   = $matches[1];
		$highlighted = array();

		$count = count( $matches[0] );
		for ( $i = 0; $i < $count; $i++ ) {
			$highlighted_tag_name = relevanssi_highlight_terms( $tag_names[ $i ], get_search_query(), true );
			$highlighted[ $i ]    = str_replace( '>' . $tag_names[ $i ] . '<', '>' . $highlighted_tag_name . '<', $originals[ $i ] );
		}

		$tag_list = str_replace( $originals, $highlighted, $tag_list );
	}

	if ( $echo ) {
		echo $tag_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $tag_list;
	}
}

/**
 * Gets a list of tags for post.
 *
 * Replacement for get_the_tags() that does the same, but applies Relevanssi search term
 * highlighting on the results.
 *
 * @param string $before    What is printed before the tags, default null.
 * @param string $separator The separator between items, default ', '.
 * @param string $after     What is printed after the tags, default ''.
 * @param int    $post_id   The post ID. Default current post ID (in the Loop).
 */
function relevanssi_get_the_tags( $before = null, $separator = ', ', $after = '', $post_id = null ) {
	return relevanssi_the_tags( $before, $separator, $after, false, $post_id );
}

/**
 * Returns the term taxonomy ID for a term based on term ID.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param int    $term_id  The term ID.
 * @param string $taxonomy The taxonomy.
 *
 * @return int Term taxonomy ID.
 */
function relevanssi_get_term_tax_id( $term_id, $taxonomy ) {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id	FROM $wpdb->term_taxonomy WHERE term_id = %d AND taxonomy = %s", $term_id, $taxonomy ) );
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

	$synonym_data = get_option( 'relevanssi_synonyms' );
	if ( $synonym_data ) {
		$synonyms     = array();
		$synonym_data = relevanssi_strtolower( $synonym_data );
		$pairs        = explode( ';', $synonym_data );

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
			if ( count( $new_terms ) > 0 ) {
				$new_terms = array_unique( $new_terms );
				foreach ( $new_terms as $new_term ) {
					$query .= " $new_term";
				}
			}
		}
	}

	return $query;
}

/**
 * Returns the position of substring in the string.
 *
 * Uses mb_stripos() if possible, falls back to mb_strpos() and mb_strtoupper() if
 * that cannot be found, and falls back to just strpos() if even that is not
 * possible.
 *
 * @param string $haystack String where to look.
 * @param string $needle   The string to look for.
 * @param int    $offset   Where to start, default 0.
 *
 * @return mixed False, if no result or $offset outside the length of $haystack,
 * otherwise the position (which can be non-false 0!).
 */
function relevanssi_stripos( $haystack, $needle, $offset = 0 ) {
	if ( $offset > relevanssi_strlen( $haystack ) ) {
		return false;
	}

	if ( function_exists( 'mb_stripos' ) ) {
		if ( '' === $haystack ) {
			$pos = false;
		} else {
			$pos = mb_stripos( $haystack, $needle, $offset );
		}
	} elseif ( function_exists( 'mb_strpos' ) && function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
		$pos = mb_strpos( mb_strtoupper( $haystack ), mb_strtoupper( $needle ), $offset );
	} else {
		$pos = strpos( strtoupper( $haystack ), strtoupper( $needle ), $offset );
	}
	return $pos;
}

/**
 * Closes tags in a bit of HTML code.
 *
 * Used to make sure no tags are left open in excerpts. This method is not foolproof,
 * but it's good enough for now.
 *
 * @param string $html The HTML code to analyze.
 *
 * @return string The HTML code, with tags closed.
 */
function relevanssi_close_tags( $html ) {
	$result = array();
	preg_match_all( '#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result );
	$opened_tags = $result[1];
	preg_match_all( '#</([a-z]+)>#iU', $html, $result );
	$closed_tags = $result[1];
	$len_opened  = count( $opened_tags );
	if ( count( $closed_tags ) === $len_opened ) {
		return $html;
	}
	$opened_tags = array_reverse( $opened_tags );
	for ( $i = 0; $i < $len_opened; $i++ ) {
		if ( ! in_array( $opened_tags[ $i ], $closed_tags, true ) ) {
			$html .= '</' . $opened_tags[ $i ] . '>';
		} else {
			unset( $closed_tags[ array_search( $opened_tags[ $i ], $closed_tags, true ) ] );
		}
	}
	return $html;
}

/**
 * Prints out post title with highlighting.
 *
 * Uses the global $post object. Reads the highlighted title from
 * $post->post_highlighted_title.
 *
 * @global object $post The global post object.
 *
 * @param boolean $echo If true, echo out the title. Default true.
 *
 * @return string If $echo is false, returns the title with highlights.
 */
function relevanssi_the_title( $echo = true ) {
	global $post;
	if ( empty( $post->post_highlighted_title ) ) {
		$post->post_highlighted_title = $post->post_title;
	}
	if ( $echo ) {
		echo $post->post_highlighted_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	return $post->post_highlighted_title;
}

/**
 * Returns the post title with highlighting.
 *
 * Reads the highlighted title from $post->post_highlighted_title.
 *
 * @param int $post_id The post ID.
 *
 * @return string The post title with highlights.
 */
function relevanssi_get_the_title( $post_id ) {
	$post = relevanssi_get_post( $post_id );
	if ( ! is_object( $post ) ) {
		return null;
	}
	if ( empty( $post->post_highlighted_title ) ) {
		$post->post_highlighted_title = $post->post_title;
	}
	return $post->post_highlighted_title;
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
	update_option( 'relevanssi_doc_count', $doc_count );
	return $doc_count;
}

/**
 * Returns the length of the string.
 *
 * Uses mb_strlen() if available, otherwise falls back to strlen().
 *
 * @param string $s The string to measure.
 *
 * @return int The length of the string.
 */
function relevanssi_strlen( $s ) {
	if ( function_exists( 'mb_strlen' ) ) {
		return mb_strlen( $s );
	}
	return strlen( $s );
}

/**
 * Prints out debugging notices.
 *
 * If WP_CLI is available, prints out the debug notice as a WP_CLI::log(), otherwise
 * just echo.
 *
 * @param string $notice The notice to print out.
 */
function relevanssi_debug_echo( $notice ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $notice );
	} else {
		echo esc_html( $notice ) . "\n";
	}
}

/**
 * Returns a Relevanssi_Taxonomy_Walker instance.
 *
 * Requires the class file and generates a new Relevanssi_Taxonomy_Walker instance.
 *
 * @return object A new Relevanssi_Taxonomy_Walker instance.
 */
function get_relevanssi_taxonomy_walker() {
	require_once 'class-relevanssi-taxonomy-walker.php';
	return new Relevanssi_Taxonomy_Walker();
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
	$relevanssi_variables['stopword_table']   = $wpdb->prefix . 'relevanssi_stopwords';
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
	$highlight_docs = get_option( 'relevanssi_highlight_docs' );
	$query          = get_search_query();
	if ( isset( $highlight_docs ) && 'off' !== $highlight_docs && ! empty( $query ) ) {
		$frontpage_id = intval( get_option( 'page_on_front' ) );
		// We won't add the highlight parameter for the front page, as that will break the link.
		$front_page = false;
		if ( is_object( $link_post ) ) {
			if ( $link_post->ID === $frontpage_id ) {
				$front_page = true;
			}
		} else {
			global $post;
			if ( is_object( $post ) && $post->ID === $frontpage_id ) {
				$front_page = true;
			}
		}
		if ( ! $front_page ) {
			$query     = str_replace( '&quot;', '"', $query );
			$permalink = esc_attr( add_query_arg( array( 'highlight' => rawurlencode( $query ) ), $permalink ) );
		}
	}
	return $permalink;
}

/**
 * Gets the permalink to the current post within Loop.
 *
 * Uses get_permalink() to get the permalink, then adds the 'highlight' parameter
 * if necessary using relevanssi_add_highlight().
 *
 * @return string The permalink.
 */
function relevanssi_get_permalink() {
	/**
	 * Filters the permalink.
	 *
	 * @param string The permalink, generated by get_permalink().
	 */
	$permalink = apply_filters( 'relevanssi_permalink', get_permalink() );
	return $permalink;
}

/**
 * Echoes out the permalink to the current post within Loop.
 *
 * Uses get_permalink() to get the permalink, then adds the 'highlight' parameter
 * if necessary using relevanssi_add_highlight(), then echoes it out.
 */
function relevanssi_the_permalink() {
	echo esc_url( relevanssi_get_permalink() );
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
 * @param object|int $link_post The post to modify, either WP post object or the post
 * ID. If null, use global $post. Defaults null.
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
		$link = $link_post->relevanssi_link;
	}

	if ( is_search() ) {
		$link = relevanssi_add_highlight( $link, $link_post );
	}
	return $link;
}

/**
 * Generates the Did you mean suggestions.
 *
 * A wrapper function that prints out the Did you mean suggestions. If Premium is
 * available, will use relevanssi_premium_didyoumean(), otherwise the
 * relevanssi_simple_didyoumean() is used.
 *
 * @param string  $query The query.
 * @param string  $pre   Printed out before the suggestion.
 * @param string  $post  Printed out after the suggestion.
 * @param int     $n     Maximum number of search results found for the suggestions
 * to show up. Default 5.
 * @param boolean $echo  If true, echo out. Default true.
 *
 * @return string The suggestion HTML element.
 */
function relevanssi_didyoumean( $query, $pre, $post, $n = 5, $echo = true ) {
	if ( function_exists( 'relevanssi_premium_didyoumean' ) ) {
		$result = relevanssi_premium_didyoumean( $query, $pre, $post, $n );
	} else {
		$result = relevanssi_simple_didyoumean( $query, $pre, $post, $n );
	}

	if ( $echo ) {
		echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	return $result;
}

/**
 * Generates the Did you mean suggestions HTML code.
 *
 * Uses relevanssi_simple_generate_suggestion() to come up with a suggestion, then
 * wraps that up with HTML code.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variables.
 * @global object $wp_query             The WP_Query object.
 *
 * @param string $query The query.
 * @param string $pre   Printed out before the suggestion.
 * @param string $post  Printed out after the suggestion.
 * @param int    $n     Maximum number of search results found for the suggestions
 * to show up. Default 5.
 *
 * @return string The suggestion HTML code, null if nothing found.
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
		$url = get_bloginfo( 'url' );
		$url = esc_attr( add_query_arg( array( 's' => rawurlencode( $suggestion ) ), $url ) );

		/**
		 * Filters the 'Did you mean' suggestion URL.
		 *
		 * @param string $url        The URL for the suggested search query.
		 * @param string $query      The search query.
		 * @param string $suggestion The suggestion.
		 */
		$url = apply_filters( 'relevanssi_didyoumean_url', $url, $query, $suggestion );

		// Escape the suggestion to avoid XSS attacks.
		$suggestion = htmlspecialchars( $suggestion );

		/**
		 * Filters the complete 'Did you mean' suggestion.
		 *
		 * @param string The suggestion HTML code.
		 */
		$result = apply_filters( 'relevanssi_didyoumean_suggestion', "$pre<a href='$url'>$suggestion</a>$post" );
	}

	return $result;
}

/**
 * Generates the 'Did you mean' suggestions. Can be used to correct any queries.
 *
 * Uses the Relevanssi search logs as source material for corrections. If there are
 * no logged search queries, can't do anything.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variables, used for
 * table names.
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
	 * To save resources, only words with more than this many occurrances are fed for
	 * the spelling corrector. If there are problems with the spelling corrector,
	 * increasing this value may fix those problems.
	 *
	 * @param int $number The number of occurrances must be more than this value,
	 * default 2.
	 */
	$count = apply_filters( 'relevanssi_get_words_having', 2 );
	if ( ! is_numeric( $count ) ) {
		$count = 2;
	}
	$q = 'SELECT query, count(query) as c, AVG(hits) as a FROM ' . $relevanssi_variables['log_table'] . ' WHERE hits > ' . $count . ' GROUP BY query ORDER BY count(query) DESC';
	$q = apply_filters( 'relevanssi_didyoumean_query', $q );

	$data = get_transient( 'relevanssi_didyoumean_query' );
	if ( empty( $data ) ) {
		$data = $wpdb->get_results( $q ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		set_transient( 'relevanssi_didyoumean_query', $data, 60 * 60 * 24 * 7 );
	}

	$query            = htmlspecialchars_decode( $query, ENT_QUOTES );
	$tokens           = relevanssi_tokenize( $query );
	$suggestions_made = false;
	$suggestion       = '';

	foreach ( $tokens as $token => $count ) {
		$closest  = '';
		$distance = -1;
		foreach ( $data as $row ) {
			if ( $row->c < 2 ) {
				break;
			}

			if ( $token === $row->query ) {
				$closest = '';
				break;
			} else {
				if ( relevanssi_strlen( $token ) < 255 ) {
					// The levenshtein() function has a max length of 255 characters.
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
		}
		if ( ! empty( $closest ) ) {
			$query            = str_ireplace( $token, $closest, $query );
			$suggestions_made = true;
		}
	}

	if ( $suggestions_made ) {
		$suggestion = $query;
	}

	return $suggestion;
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
 * Replacement for get_post() that uses the Relevanssi post cache.
 *
 * Tries to fetch the post from the Relevanssi post cache. If that doesn't work, gets
 * the post using get_post().
 *
 * @param int $post_id The post ID.
 * @param int $blog_id The blog ID, default -1.
 *
 * @return object The post object.
 */
function relevanssi_get_post( $post_id, $blog_id = -1 ) {
	if ( function_exists( 'relevanssi_premium_get_post' ) ) {
		return relevanssi_premium_get_post( $post_id, $blog_id );
	}

	global $relevanssi_post_array;

	$post = null;
	if ( isset( $relevanssi_post_array[ $post_id ] ) ) {
		$post = $relevanssi_post_array[ $post_id ];
	}
	if ( ! $post ) {
		$post = get_post( $post_id );

		$relevanssi_post_array[ $post_id ] = $post;
	}
	return $post;
}

/**
 * Recursively flattens a multidimensional array to produce a string.
 *
 * @param array $array The source array.
 *
 * @return string The array contents as a string.
 */
function relevanssi_flatten_array( array $array ) {
	$return_value = '';
	foreach ( new RecursiveIteratorIterator( new RecursiveArrayIterator( $array ) ) as $value ) {
		$return_value .= ' ' . $value;
	}
	return $return_value;
}

/**
 * Sanitizes hex color strings.
 *
 * A copy of sanitize_hex_color(), because that isn't always available.
 *
 * @param string $color A hex color string to sanitize.
 *
 * @return string Sanitized hex string, or an empty string.
 */
function relevanssi_sanitize_hex_color( $color ) {
	if ( '' === $color ) {
		return '';
	}

	if ( '#' !== substr( $color, 0, 1 ) ) {
		$color = '#' . $color;
	}

	// 3 or 6 hex digits, or the empty string.
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}

	return '';
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
		'shop_order',           // WooCommerce.
		'shop_order_refund',    // WooCommerce.
		'shop_webhook',         // WooCommerce.
		'et_theme_builder',     // Divi.
		'et_template',          // Divi.
		'et_header_layout',     // Divi.
		'et_body_layout',       // Divi.
		'et_footer_layout',     // Divi.
		'wpforms',              // WP Forms.
		'amn_wpforms',          // WP Forms.
		'wpforms_log',          // WP Forms.
		'dlm_download_version', // Download Monitor.
	);
}

/**
 * Returns a list of taxonomies Relevanssi does not want to use.
 *
 * @return array An array of taxonomy names.
 */
function relevanssi_get_forbidden_taxonomies() {
	return array(
		'nav_menu',               // Navigation menus.
		'link_category',          // Link categories.
		'amp_validation_error',   // AMP.
		'product_visibility',     // WooCommerce.
		'wpforms_log_type',       // WP Forms.
	);
}

/**
 * Returns "off".
 *
 * Useful for returning "off" to filters easily.
 *
 * @return string A string with value "off".
 */
function relevanssi_return_off() {
	return 'off';
}

/**
 * Filters out unwanted custom fields.
 *
 * Added to the relevanssi_custom_field_value filter hook.
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
	return $values;
}
