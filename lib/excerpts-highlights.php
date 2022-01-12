<?php
/**
 * /lib/excerpts-highlights.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Generates an excerpt for a post.
 *
 * Takes the excerpt length and type as parameters. These can be omitted, in
 * which case the values are taken from the 'relevanssi_excerpt_length' and
 * 'relevanssi_excerpt_type' options respectively.
 *
 * @global $post The global post object.
 *
 * @param object $t_post         The post object.
 * @param string $query          The search query.
 * @param int    $excerpt_length The length of the excerpt, default null.
 * @param string $excerpt_type   Either 'chars' or 'words', default null.
 *
 * @return string The created excerpt.
 */
function relevanssi_do_excerpt( $t_post, $query, $excerpt_length = null, $excerpt_type = null ) {
	global $post;

	if ( ! $excerpt_length ) {
		$excerpt_length = get_option( 'relevanssi_excerpt_length' );
	}
	if ( ! $excerpt_type ) {
		$excerpt_type = get_option( 'relevanssi_excerpt_type' );
	}

	// Back up the global post object, and replace it with the post we're working on.
	$old_global_post = null;
	if ( null !== $post ) {
		$old_global_post = $post;
	}
	/**
	 * Allows filtering the indexed post before building an excerpt from it.
	 *
	 * @param object $post The post object.
	 */
	$post = apply_filters( 'relevanssi_post_to_excerpt', $t_post ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	$remove_stopwords = 'body';

	/**
	 * Filters the search query before excerpt-building.
	 *
	 * Allows filtering the search query before generating an excerpt. This can
	 * useful if you make modifications to the search query, and it may also
	 * help when working with stemming.
	 *
	 * @param string $query The search query.
	 */
	$query = apply_filters( 'relevanssi_excerpt_query', $query );

	$min_word_length = 2;
	/**
	 * Allows creating one-letter highlights.
	 *
	 * @param boolean Set to true to enable one-letter highlights.
	 */
	if ( apply_filters( 'relevanssi_allow_one_letter_highlights', false ) ) {
		$min_word_length = 1;
	}

	$terms = relevanssi_tokenize( $query, $remove_stopwords, $min_word_length, 'search_query' );

	if ( is_array( $query ) ) {
		$untokenized_terms = array_filter( $query );
	} else {
		$untokenized_terms = array_filter( explode( ' ', $query ) );
	}
	$untokenized_terms = array_map(
		function( $term ) {
			if ( is_numeric( $term ) ) {
				$term = " $term";
			}
			return $term;
		},
		$untokenized_terms
	);

	$untokenized_terms = array_flip(
		relevanssi_remove_stopwords_from_array( $untokenized_terms )
	);
	$terms             = array_merge( $untokenized_terms, $terms );

	// These shortcodes cause problems with Relevanssi excerpts.
	$problem_shortcodes = array( 'layerslider', 'responsive-flipbook', 'breadcrumb', 'robogallery', 'gravityview', 'wp_show_posts' );
	/**
	 * Filters the excerpt-building problem shortcodes.
	 *
	 * Some shortcodes cause problems in Relevanssi excerpt-building. These
	 * shortcodes are disabled before building the excerpt. This filter allows
	 * modifying the list of shortcodes.
	 *
	 * @param array $problem_shortcodes Array of problematic shortcode names.
	 */
	$problem_shortcodes = apply_filters( 'relevanssi_disable_shortcodes_excerpt', $problem_shortcodes );
	array_walk( $problem_shortcodes, 'remove_shortcode' );

	/**
	 * Filters the post content before 'the_content'.
	 *
	 * Filters the post content in excerpt building process before 'the_content'
	 * filter is applied.
	 *
	 * @param string $content The post content.
	 * @param object $post    The post object.
	 * @param string $query   The search query.
	 */
	$content = apply_filters( 'relevanssi_pre_excerpt_content', $post->post_content, $post, $query );

	$pattern = get_shortcode_regex( $problem_shortcodes );
	$content = preg_replace_callback( "/$pattern/", 'strip_shortcode_tag', $content );

	// Add the custom field content.
	if ( 'on' === get_option( 'relevanssi_excerpt_custom_fields' ) ) {
		$content .= relevanssi_get_custom_field_content( $post->ID );
	}

	/**
	 * Runs before Relevanssi excerpt building applies `the_content`.
	 */
	do_action( 'relevanssi_pre_the_content' );

	/** This filter is documented in wp-includes/post-template.php */
	$content = apply_filters( 'the_content', $content );

	/**
	 * Runs after Relevanssi excerpt building applies `the_content`.
	 */
	do_action( 'relevanssi_post_the_content' );

	/**
	 * Filters the post content after 'the_content'.
	 *
	 * Filters the post content in excerpt building process after 'the_content'
	 * filter is applied.
	 *
	 * @param string $content The post content.
	 * @param object $post    The post object.
	 * @param string $query   The search query.
	 */
	$content = apply_filters( 'relevanssi_excerpt_content', $content, $post, $query );
	$content = relevanssi_strip_tags( $content );

	// Replace linefeeds and carriage returns with spaces.
	$content = preg_replace( "/\n\r|\r\n|\n|\r/", ' ', $content );

	if ( 'OR' === get_option( 'relevanssi_implicit_operator' ) || 'on' === get_option( 'relevanssi_index_synonyms' ) ) {
		$query = relevanssi_add_synonyms( $query );
	}

	// Find the appropriate spot from the post.
	$excerpts = relevanssi_create_excerpts( $content, $terms, $query, $excerpt_length, $excerpt_type );
	if ( function_exists( 'relevanssi_add_source_to_excerpts' ) ) {
		relevanssi_add_source_to_excerpts( $excerpts, 'content' );
	}

	$comment_excerpts = array();
	if ( 'none' !== get_option( 'relevanssi_index_comments' ) ) {
		$comment_content = relevanssi_strip_tags( relevanssi_get_comments( $post->ID ) );
		if ( ! empty( $comment_content ) ) {
			$comment_excerpts = relevanssi_create_excerpts(
				$comment_content,
				$terms,
				$query,
				$excerpt_length,
				$excerpt_type
			);
			if ( function_exists( 'relevanssi_add_source_to_excerpts' ) ) {
				relevanssi_add_source_to_excerpts( $comment_excerpts, 'comments' );
				$comment_excerpts = array_filter(
					$comment_excerpts,
					function( $excerpt ) {
						return $excerpt['hits'];
					}
				);
			} elseif ( is_array( $comment_excerpts ) ) {
				if ( $comment_excerpts[0]['hits'] > $excerpts[0]['hits'] ) {
					$excerpts = $comment_excerpts;
				}
			}
		}
	}

	$excerpt_excerpts = array();
	if ( 'off' !== get_option( 'relevanssi_index_excerpt' ) ) {
		$excerpt_content = relevanssi_strip_tags( $post->post_excerpt );
		if ( ! empty( $excerpt_content ) ) {
			$excerpt_excerpts = relevanssi_create_excerpts(
				$excerpt_content,
				$terms,
				$query,
				$excerpt_length,
				$excerpt_type
			);
			if ( function_exists( 'relevanssi_add_source_to_excerpts' ) ) {
				relevanssi_add_source_to_excerpts( $excerpt_excerpts, 'excerpt' );
				$excerpt_excerpts = array_filter(
					$excerpt_excerpts,
					function( $excerpt ) {
						return $excerpt['hits'];
					}
				);
			} elseif ( is_array( $excerpt_excerpts ) ) {
				if ( $excerpt_excerpts[0]['hits'] > $excerpts[0]['hits'] ) {
					$excerpts = $excerpt_excerpts;
				}
			}
		}
	}

	if ( function_exists( 'relevanssi_combine_excerpts' ) ) {
		$excerpts = relevanssi_combine_excerpts(
			$post->ID,
			$excerpts,
			$comment_excerpts,
			$excerpt_excerpts
		);
	}

	/**
	 * Filters the ellipsis Relevanssi uses in excerpts.
	 *
	 * @param string $ellipsis Default '...'.
	*/
	$ellipsis  = apply_filters( 'relevanssi_ellipsis', '...' );
	$highlight = get_option( 'relevanssi_highlight' );

	$excerpt_text = '';

	foreach ( $excerpts as $excerpt ) {
		$whole_post_excerpted = false;
		if ( $excerpt['text'] === $post->post_content ) {
			$whole_post_excerpted = true;
		}

		/**
		 * Filters excerpt text.
		 *
		 * Filters the individual excerpt part text (full excerpt in the free
		 * version) before highlighting and ellipsis addition.
		 *
		 * @param string The excerpt text.
		 * @param int    The post ID.
		 *
		 * @return string
		 */
		$excerpt['text'] = apply_filters( 'relevanssi_excerpt', $excerpt['text'], $post->ID );

		if ( 'none' !== $highlight ) {
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$excerpt['text'] = relevanssi_highlight_terms( $excerpt['text'], $query );
			}
		}

		if ( ! empty( $excerpt['text'] ) ) {
			$excerpt['text'] = relevanssi_close_tags( $excerpt['text'] );
		}

		if ( ! $whole_post_excerpted ) {
			if ( ! $excerpt['start'] && ! empty( $excerpt['text'] ) ) {
				$excerpt['text'] = $ellipsis . $excerpt['text'];
			}

			if ( ! empty( $excerpt['text'] ) ) {
				$excerpt['text'] = $excerpt['text'] . $ellipsis;
			}
		}

		/**
		 * Filters individual excerpt parts.
		 *
		 * Filters the individual excerpt parts (full excerpt in the free
		 * version) after highlighting, ellipsis and the wrapping span tag have
		 * been added.
		 *
		 * @param string The excerpt text.
		 * @param array  The excerpt array (keys 'text', 'start', 'source',
		 * 'hits').
		 * @param int    The post ID.
		 *
		 * @return string
		 */
		$excerpt_text .= apply_filters(
			'relevanssi_excerpt_part',
			'<span class="excerpt_part">' . $excerpt['text'] . '</span>',
			$excerpt,
			$post->ID
		);
	}

	if ( null !== $old_global_post ) {
		$post = $old_global_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	return $excerpt_text;
}

/**
 * Creates an excerpt from content.
 *
 * This is provided for backwards compatibility. The new version of the function
 * supports the Premium capability to return multiple excerpts. Since that
 * changes the return value of the function, this function is provided to
 * return the original return value.
 *
 * @uses relevanssi_create_excerpts()
 *
 * @param string $content        The content.
 * @param array  $terms          The search terms, tokenized.
 * @param string $query          The search query.
 * @param int    $excerpt_length The length of the excerpt, default 30.
 * @param string $excerpt_type   Either 'chars' or 'words', default 'words'.
 *
 * @return array Element 0 is the excerpt, element 1 the number of term hits,
 * element 2 is true, if the excerpt is from the start of the content.
 */
function relevanssi_create_excerpt( $content, $terms, $query, $excerpt_length = 30, $excerpt_type = 'words' ) {
	$excerpts = relevanssi_create_excerpts( $content, $terms, $query, $excerpt_length, $excerpt_type );
	usort(
		$excerpts,
		function( $a, $b ) {
			return $b['hits'] - $a['hits'];
		}
	);

	$excerpt = array(
		0 => $excerpts[0]['text'],
		1 => $excerpts[0]['hits'],
		2 => $excerpts[0]['start'],
	);
	return $excerpt;
}

/**
 * Creates an excerpt from content.
 *
 * Relevanssi Premium has the capability to generate multiple excerpts from one
 * post. While the free version only generates one excerpt per post, this
 * function supports the multiple excerpt behaviour by returning an array of
 * excerpts, even though just one excerpt is returned.
 *
 * @see relevanssi_create_excerpt()
 *
 * @param string $content        The content.
 * @param array  $terms          The search terms, tokenized.
 * @param string $query          The search query.
 * @param int    $excerpt_length The length of the excerpt, default 30.
 * @param string $excerpt_type   Either 'chars' or 'words', default 'words'.
 *
 * @return array An array of excerpts. In each excerpt, there are following
 * parts: 'text' has the excerpt text, 'hits' the number of keyword matches in
 * the excerpt, 'start' is true if the excerpt is from the beginning of the
 * content.
 */
function relevanssi_create_excerpts( $content, $terms, $query, $excerpt_length = 30, $excerpt_type = 'words' ) {
	$content = preg_replace( '/\s+/u', ' ', $content );
	if ( ' ' !== relevanssi_substr( $content, 0, 1 ) ) {
		$content = ' ' . $content;
	}
	$content = html_entity_decode( $content );
	// Finds all the phrases in the query.
	$phrases = relevanssi_extract_phrases( stripslashes( $query ) );
	$terms   = relevanssi_remove_quotes_from_array_keys( $terms );

	/**
	 * This process generates an array of terms, which has single terms and all the
	 * phrases.
	 */
	$remove_stopwords = false;
	$non_phrase_terms = array();
	foreach ( $phrases as $phrase ) {
		$phrase_terms = array_keys( relevanssi_tokenize( $phrase, $remove_stopwords, -1, 'search_query' ) );
		foreach ( array_keys( $terms ) as $term ) { // array_keys(), because tokenized terms have the term as key.
			if ( ! in_array( $term, $phrase_terms, true ) ) {
				$non_phrase_terms[ $term ] = true;
			}
		}

		$terms            = $non_phrase_terms;
		$terms[ $phrase ] = true;
	}

	// Sort the longest search terms first, because those are generally more significant.
	uksort( $terms, 'relevanssi_strlen_sort' );

	$excerpts = array();
	$start    = false;
	if ( 'chars' === $excerpt_type ) {
		$prev_count = floor( $excerpt_length / 6 );

		list( $excerpt_text, $best_excerpt_term_hits, $start ) =
		relevanssi_extract_relevant(
			array_keys( $terms ),
			$content,
			$excerpt_length + 1, // There's one space in the beginning of the content.
			$prev_count
		);
		$excerpt    = array(
			'text'  => $excerpt_text,
			'hits'  => $best_excerpt_term_hits,
			'start' => $start,
		);
		$excerpts[] = $excerpt;
	} else {
		if ( function_exists( 'relevanssi_extract_multiple_excerpts' ) && get_option( 'relevanssi_max_excerpts', 1 ) > 1 ) {
			$excerpts = relevanssi_extract_multiple_excerpts(
				array_keys( $terms ),
				$content,
				$excerpt_length
			);
		} else {
			list( $excerpt_text, $best_excerpt_term_hits, $start ) =
			relevanssi_extract_relevant_words(
				array_keys( $terms ),
				$content,
				$excerpt_length
			);
			$excerpt    = array(
				'text'  => $excerpt_text,
				'hits'  => $best_excerpt_term_hits,
				'start' => $start,
			);
			$excerpts[] = $excerpt;
		}
	}

	return $excerpts;
}

/**
 * Manages the highlighting in documents.
 *
 * Uses relevanssi_highlight_terms() to do the highlighting. Attached to
 * 'the_content' and 'comment_text' filter hooks.
 *
 * @global object  $wp_query               The global WP_Query object.
 * @global boolean $relevanssi_test_enable If true, this is a test.
 *
 * @param string $content The content to highlight.
 *
 * @return string The content with highlights.
 */
function relevanssi_highlight_in_docs( $content ) {
	global $wp_query, $relevanssi_test_enable;
	if ( ( is_singular() && is_main_query() ) || $relevanssi_test_enable ) {
		if ( isset( $wp_query->query_vars['highlight'] ) ) {
			// Local search.
			$query   = relevanssi_add_synonyms( $wp_query->query_vars['highlight'] );
			$in_docs = true;

			$highlighted_content = relevanssi_highlight_terms( $content, $query, $in_docs );
			if ( ! empty( $highlighted_content ) ) {
				// Sometimes the content comes back empty; until I figure out why, this tries to be a solution.
				$content = $highlighted_content;
			}
		}
	}

	return $content;
}

/**
 * Adds highlighting to content.
 *
 * Adds highlighting to content based on Relevanssi highlighting settings (if
 * you want to override the settings, 'pre_option_relevanssi_highlight' filter
 * hook is your friend).
 *
 * @param string       $content          The content to highlight.
 * @param string|array $query            The search query (should be a string,
 * can also be an array of string).
 * @param boolean      $convert_entities Are we highlighting post content?
 * Default false.
 *
 * @return string The $content with highlighting.
 */
function relevanssi_highlight_terms( $content, $query, $convert_entities = false ) {
	$type = get_option( 'relevanssi_highlight' );
	if ( 'none' === $type ) {
		return $content;
	}

	switch ( $type ) {
		case 'mark':
			$start_emp = '<mark>';
			$end_emp   = '</mark>';
			break;
		case 'strong':
			$start_emp = '<strong>';
			$end_emp   = '</strong>';
			break;
		case 'em':
			$start_emp = '<em>';
			$end_emp   = '</em>';
			break;
		case 'col':
			$col = get_option( 'relevanssi_txt_col' );
			if ( ! $col ) {
				$col = '#ff0000';
			}
			$start_emp = "<span style='color: $col'>";
			$end_emp   = '</span>';
			break;
		case 'bgcol':
			$col = get_option( 'relevanssi_bg_col' );
			if ( ! $col ) {
				$col = '#ff0000';
			}
			$start_emp = "<span style='background-color: $col'>";
			$end_emp   = '</span>';
			break;
		case 'css':
			$css = get_option( 'relevanssi_css' );
			if ( ! $css ) {
				$css = 'color: #ff0000';
			}
			$start_emp = "<span style='$css'>";
			$end_emp   = '</span>';
			break;
		case 'class':
			$css = get_option( 'relevanssi_class' );
			if ( ! $css ) {
				$css = 'relevanssi-query-term';
			}
			$start_emp = "<span class='$css'>";
			$end_emp   = '</span>';
			break;
		default:
			return $content;
	}

	$start_emp_token = '**{[';
	$end_emp_token   = ']}**';

	if ( function_exists( 'mb_internal_encoding' ) ) {
		mb_internal_encoding( 'UTF-8' );
	}

	/**
	 * Runs before tokenizing the terms in highlighting.
	 */
	do_action( 'relevanssi_highlight_tokenize' );

	// Setting min_word_length to 2, in order to avoid 1-letter highlights.
	$min_word_length = 2;
	/**
	 * Allows creating one-letter highlights.
	 *
	 * @param boolean Set to true to enable one-letter highlights.
	 */
	if ( apply_filters( 'relevanssi_allow_one_letter_highlights', false ) ) {
		$min_word_length = 1;
	}

	$remove_stopwords = 'body';
	$terms            = array_keys(
		relevanssi_tokenize(
			$query,
			$remove_stopwords,
			$min_word_length,
			'search_query'
		)
	);

	if ( ! is_array( $query ) ) {
		$query = explode( ' ', relevanssi_strtolower( $query ) );
	}

	$body_stopwords = function_exists( 'relevanssi_fetch_body_stopwords' )
		? relevanssi_fetch_body_stopwords()
		: array();

	$untokenized_terms = array_filter(
		$query,
		function( $value ) use ( $min_word_length, $body_stopwords ) {
			if ( in_array( $value, $body_stopwords, true ) ) {
				return false;
			}
			if ( relevanssi_strlen( $value ) > $min_word_length ) {
				return true;
			}
			return false;
		}
	);

	$terms = array_unique( array_merge( $untokenized_terms, $terms ) );
	array_walk( $terms, 'relevanssi_array_walk_trim' ); // Numeric search terms begin with a space.

	if ( is_array( $query ) ) {
		$query = implode( ' ', $query );
	}
	$phrases = relevanssi_extract_phrases( stripslashes( $query ) );

	$remove_stopwords = false;
	$non_phrase_terms = array();
	foreach ( $phrases as $phrase ) {
		$phrase_terms = array_keys( relevanssi_tokenize( $phrase, $remove_stopwords, -1, 'search_query' ) );
		foreach ( $terms as $term ) {
			if ( ! in_array( $term, $phrase_terms, true ) ) {
				$non_phrase_terms[] = $term;
			}
		}
		$terms   = $non_phrase_terms;
		$terms[] = $phrase;
	}

	usort( $terms, 'relevanssi_strlen_sort' );

	$content = strtr( $content, array( "\xC2\xAD" => '' ) );
	$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
	if ( ! $convert_entities ) {
		$content = str_replace( "\n", ' ', $content );
	}

	foreach ( $terms as $term ) {
		$pr_term = preg_quote( $term, '/' );
		$pr_term = relevanssi_add_accent_variations( $pr_term );

		// Support for wildcard matching (a Premium feature).
		$pr_term = str_replace(
			array( '\*', '\?' ),
			array( '\S*', '.' ),
			$pr_term
		);

		$regex       = "/([\W])($pr_term)([\W])/iu";
		$three_parts = true;

		if ( 'never' !== get_option( 'relevanssi_fuzzy' ) ) {
			$regex       = "/([\W]{$pr_term}|{$pr_term}[\W])/iu";
			$three_parts = false;
		}

		if ( 'on' === get_option( 'relevanssi_expand_highlights' ) ) {
			$regex       = "/([\w]*{$pr_term}[\W]|[\W]{$pr_term}[\w]*)/iu";
			$three_parts = false;
		}

		if ( $three_parts ) {
			$replace = '\\1' . $start_emp_token . '\\2' . $end_emp_token . '\\3';
		} else {
			$replace = $start_emp_token . '\\1' . $end_emp_token;
		}

		// Add an extra space so that the regex that looks for a non-word
		// character after the search term will find one, even if the word is
		// at the end of the content (especially in titles).
		$content .= ' ';

		$content = trim(
			preg_replace(
				$regex,
				$replace,
				' ' . $content
			)
		);
		/**
		 * The method here leaves extra spaces or HTML tag closing brackets
		 * inside the highlighting. That is cleaned away here.
		 */
		$replace_regex = '/(.)(' . preg_quote( $start_emp_token, '/' ) . ')(>|\s)/iu';
		$content       = preg_replace( $replace_regex, '\1\3\2', $content );

		$replace_regex = '/^(' . preg_quote( $start_emp_token, '/' ) . ')\s/iu';
		$content       = preg_replace( $replace_regex, '\1', $content );

		$replace_regex = '/(\s)(' . preg_quote( $end_emp_token, '/' ) . ')(.)/iu';
		$content       = preg_replace( $replace_regex, '\2\1\3', $content );

		$replace_regex = '/\s(' . preg_quote( $end_emp_token, '/' ) . ')/iu';
		$content       = preg_replace( $replace_regex, '\1', $content );

		// The starting tokens can get interlaced this way, let's unknot them.
		$content = str_replace(
			substr( $start_emp_token, 0, -1 ) . $start_emp_token . substr( $start_emp_token, -1, 1 ),
			$start_emp_token . $start_emp_token,
			$content
		);

		if ( preg_match_all( '/<.*>/Us', $content, $matches ) > 0 ) {
			// Remove highlights from inside HTML tags.
			foreach ( $matches as $match ) {
				$new_match = str_replace( $start_emp_token, '', $match );
				$new_match = str_replace( $end_emp_token, '', $new_match );
				$content   = str_replace( $match, $new_match, $content );
			}
		}

		$start_quoted = preg_quote( $start_emp_token, '/' );
		$end_quoted   = preg_quote( $end_emp_token, '/' );
		if (
			preg_match_all(
				'/&' . $start_quoted . '([a-z0-9]+|#[0-9]{1,6}|#x[0-9a-fA-F]{1,6})' . $end_quoted . ';/U',
				$content,
				$matches
			) > 0 ) {
			// Remove highlights from inside HTML entities.
			foreach ( $matches as $match ) {
				$new_match = str_replace( $start_emp_token, '', $match );
				$new_match = str_replace( $end_emp_token, '', $new_match );
				$content   = str_replace( $match, $new_match, $content );
			}
		}

		if ( preg_match_all( '/<(style|script|object|embed|pre|code).*<\/(style|script|object|embed|pre|code)>/Us', $content, $matches ) > 0 ) {
			// Remove highlights in style, object, embed, script and pre tags.
			foreach ( $matches as $match ) {
				$new_match = str_replace( $start_emp_token, '', $match );
				$new_match = str_replace( $end_emp_token, '', $new_match );
				$content   = str_replace( $match, $new_match, $content );
			}
		}
	}

	$content = relevanssi_remove_nested_highlights( $content, $start_emp_token, $end_emp_token );
	$content = relevanssi_fix_entities( $content, $convert_entities );

	/**
	 * Allows cleaning unwanted highlights.
	 *
	 * This filter lets you clean unwanted highlights, for example from within
	 * <pre> tags. To remove a highlight, remove the matching starting and
	 * ending tokens from the $content string.
	 *
	 * @param string $content         The highlighted content.
	 * @param string $start_emp_token A token that signifies the start of a
	 * highlight.
	 * @param string $end_emp_token   A token that signifies the end of a
	 * highlight.
	 *
	 * @return string The highlighted content.
	 */
	$content = apply_filters(
		'relevanssi_clean_excerpt',
		$content,
		$start_emp_token,
		$end_emp_token
	);

	$content = str_replace( $start_emp_token, $start_emp, $content );
	$content = str_replace( $end_emp_token, $end_emp, $content );
	$content = str_replace( $end_emp . $start_emp, '', $content );
	if ( function_exists( 'mb_ereg_replace' ) ) {
		$pattern = $end_emp . '\s*' . $start_emp;
		$content = mb_ereg_replace( $pattern, ' ', $content );
	}

	return $content;
}

/**
 * Fixes problems with entities.
 *
 * For excerpts, runs htmlentities() on the excerpt, then converts the allowed
 * tags back into tags.
 *
 * @param string  $excerpt The excerpt to fix.
 * @param boolean $in_docs If true, we are manipulating post content, and need
 * to work in a different fashion.
 *
 * @return string The $excerpt with entities fixed.
 */
function relevanssi_fix_entities( $excerpt, $in_docs ) {
	if ( ! $in_docs ) {
		// For excerpts, use htmlentities() to convert.
		$excerpt = htmlentities( $excerpt, ENT_NOQUOTES, 'UTF-8' );

		// Except for allowed tags, which are turned back into tags.
		$tags = get_option( 'relevanssi_excerpt_allowable_tags', '' );
		$tags = trim( str_replace( '<', ' <', $tags ) );
		$tags = explode( ' ', $tags );

		$closing_tags = relevanssi_generate_closing_tags( $tags );

		$tags_entitied = htmlentities(
			implode(
				' ',
				$tags
			),
			ENT_NOQUOTES,
			'UTF-8'
		);
		$tags_entitied = explode( ' ', $tags_entitied );

		$closing_tags_entitied = htmlentities(
			implode(
				' ',
				$closing_tags
			),
			ENT_NOQUOTES,
			'UTF-8'
		);
		$closing_tags_entitied = explode( ' ', $closing_tags_entitied );

		$tags_entitied_regexped = array();

		$i = 0;
		foreach ( $tags_entitied as $tag ) {
			$tag     = str_replace( '&gt;', '(.*?)&gt;', $tag );
			$pattern = "~$tag~";

			$tags_entitied_regexped[] = $pattern;

			$matching_tag = $tags[ $i ];
			$matching_tag = str_replace( '>', '\1>', $matching_tag );
			$tags[ $i ]   = $matching_tag;
			$i++;
		}

		$closing_tags_entitied_regexped = array();
		foreach ( $closing_tags_entitied as $tag ) {
			$pattern = '~' . preg_quote( $tag, '~' ) . '~';

			$closing_tags_entitied_regexped[] = $pattern;
		}

		$tags          = array_merge( $tags, $closing_tags );
		$tags_entitied = array_merge(
			$tags_entitied_regexped,
			$closing_tags_entitied_regexped
		);

		$excerpt = preg_replace( $tags_entitied, $tags, $excerpt );

		// In case there are attributes. This is the easiest solution, as
		// using quotes and apostrophes un-entitied can't really break
		// anything.
		$excerpt = str_replace( '&quot;', '"', $excerpt );
		$excerpt = str_replace( '&#039;', "'", $excerpt );
	} else {
		// Running htmlentities() for whole posts tends to ruin things.
		// However, we may want to run htmlentities() for anything inside
		// <pre> and <code> tags.
		/**
		 * Choose whether htmlentities() is run inside <pre> tags or not. If
		 * your pages have HTML code inside <pre> tags, set this to false.
		 *
		 * @param boolean If true, htmlentities() will be used inside <pre>
		 * tags.
		 */
		if ( apply_filters( 'relevanssi_entities_inside_pre', true ) ) {
			$excerpt = relevanssi_entities_inside( $excerpt, 'pre' );
		}
		/**
		 * Choose whether htmlentities() is run inside <code> tags or not. If
		 * your pages have HTML code inside <code> tags, set this to false.
		 *
		 * @param boolean If true, htmlentities() will be used inside <code>
		 * tags.
		 */
		if ( apply_filters( 'relevanssi_entities_inside_code', true ) ) {
			$excerpt = relevanssi_entities_inside( $excerpt, 'code' );
		}
	}
	return $excerpt;
}

/**
 * Runs htmlentities() for content inside specified tags.
 *
 * @param string $content The content.
 * @param string $tag     The tag.
 *
 * @return string $content The content with HTML code inside the $tag tags
 * ran through htmlentities().
 */
function relevanssi_entities_inside( $content, $tag ) {
	$hits = preg_match_all( '/<' . $tag . '.*?>(.*?)<\/' . $tag . '>/ims', $content, $matches );
	if ( $hits > 0 ) {
		$replacements = array();
		foreach ( $matches[1] as $match ) {
			if ( ! empty( $match ) ) {
				$replacements[] = '<xxx' . $tag . '\1>'
					. htmlentities( $match, ENT_QUOTES, 'UTF-8' )
					. '</xxx' . $tag . '>';
			}
		}
		if ( ! empty( $replacements ) ) {
			$count_replacements = count( $replacements );
			for ( $i = 0; $i < $count_replacements; $i++ ) {
				$patterns[] = '/<' . $tag . '(.*?)>(.*?)<\/' . $tag . '>/ims';
			}
			$content = preg_replace( $patterns, $replacements, $content, 1 );
		}
		$content = str_replace( 'xxx' . $tag, $tag, $content );
	}
	return $content;
}

/**
 * Removes nested highlights from a string.
 *
 * If there are highlights within highlights in a string, this function will
 * clean out the nested highlights, leaving just the outmost highlight tokens.
 *
 * @param string $string The content.
 * @param string $begin  The beginning highlight token.
 * @param string $end    The ending highlight token.
 *
 * @return string The string with nested highlights cleaned out.
 */
function relevanssi_remove_nested_highlights( $string, $begin, $end ) {
	$bits       = explode( $begin, $string );
	$new_bits   = array( $bits[0] );
	$count_bits = count( $bits );
	$depth      = -1;
	for ( $i = 1; $i < $count_bits; $i++ ) {
		$depth++;
		if ( 0 === $depth ) {
			$new_bits[] = $begin;
		}
		if ( empty( $bits[ $i ] ) ) {
			continue;
		}
		$end_count = substr_count( $bits[ $i ], $end );
		if ( $end_count ) {
			if ( substr_count( $bits[ $i ], $end ) < $depth ) {
				$new_bits[] = str_replace( $end, '', $bits[ $i ], $count );
				$depth     -= $count;
			} elseif ( substr_count( $bits[ $i ], $end ) >= $depth ) {
				$end_p      = preg_quote( $end, '#' );
				$new_bits[] = preg_replace( '#' . $end_p . '#', '', $bits[ $i ], $depth );
				$depth      = -1;
			}
		} else {
			$new_bits[] = $bits[ $i ];
		}
	}
	return join( '', $new_bits );
}

/**
 * Finds the locations of each word.
 *
 * Originally lifted from http://www.boyter.org/2013/04/building-a-search-result-extract-generator-in-php/
 * Finds the location of each word in the fulltext.
 *
 * @author Ben Boyter
 *
 * @param array  $words    An array of words to locate.
 * @param string $fulltext The fulltext where to find them.
 *
 * @return array Array of locations.
 */
function relevanssi_extract_locations( $words, $fulltext ) {
	$locations = array();
	foreach ( $words as $word ) {
		$count_locations = 0;
		$wordlen         = relevanssi_strlen( $word );
		$loc             = relevanssi_stripos( $fulltext, $word, 0 );
		while ( false !== $loc ) {
			$locations[] = $loc;
			$loc         = relevanssi_stripos( $fulltext, $word, $loc + $wordlen );
			$count_locations++;
			/**
			 * Optimizes the excerpt creation.
			 *
			 * @param boolean If true, stop looking after ten locations are found.
			 */
			if ( apply_filters( 'relevanssi_optimize_excerpts', false ) ) {
				// If more than ten locations are found, quit: there's probably a
				// good one in there, and this saves plenty of time.
				if ( $count_locations > 10 ) {
					break;
				}
			}
		}
	}

	$locations = array_unique( $locations );
	sort( $locations );

	return $locations;
}

/**
 * Counts how many times the words appear in the text.
 *
 * @param array  $words         An array of words.
 * @param string $complete_text The text where to count the words.
 *
 * @return int Number of times the words appear in the text.
 */
function relevanssi_count_matches( $words, $complete_text ) {
	$count          = 0;
	$lowercase_text = relevanssi_strtolower( $complete_text, 'UTF-8' );
	$text           = '';

	$count_words = count( $words );
	for ( $t = 0; $t < $count_words; $t++ ) {
		$word_slice = relevanssi_strtolower(
			relevanssi_add_accent_variations(
				preg_quote(
					$words[ $t ],
					'/'
				)
			),
			'UTF-8'
		);
		// Support for wildcard matching (a Premium feature).
		$word_slice = str_replace(
			array( '\*', '\?' ),
			array( '\S*', '.' ),
			$word_slice
		);

		if ( 'never' !== get_option( 'relevanssi_fuzzy' ) ) {
			$regex = "/[\W]{$word_slice}|{$word_slice}[\W]/iu";
		} else {
			$regex = "/[\W]{$word_slice}[\W]/iu";
		}

		$lines = preg_split( $regex, $lowercase_text );
		if ( $lines && count( $lines ) > 1 ) {
			$count_lines = count( $lines );
			for ( $tt = 0; $tt < $count_lines; $tt++ ) {
				if ( $tt < ( count( $lines ) - 1 ) ) {
					$text = $text . $lines[ $tt ] . '=***=';
				} else {
					$text = $text . $lines[ $tt ];
				}
			}
		}
	}

	$lines = explode( '=***=', $text );
	$count = count( $lines ) - 1;

	return $count;
}

/**
 * Works out which is the most relevant portion to display.
 *
 * This is done by looping over each match and finding the smallest distance
 * between two found strings. The idea being that the closer the terms are the
 * better match the snippet would be. When checking for matches we only change
 * the location if there is a better match. The only exception is where we have
 * only two matches in which case we just take the first as will be equally
 * distant.
 *
 * @author Ben Boyter
 *
 * @param array $locations Locations of the words.
 * @param int   $prevcount How much text to include before the location.
 *
 * @return int Starting position for the snippet.
 */
function relevanssi_determine_snip_location( $locations, $prevcount ) {
	if ( ! is_array( $locations ) || empty( $locations ) ) {
		return 0;
	}

	// If we only have 1 match we dont actually do the for loop so set to the first.
	$startpos     = $locations[0];
	$loc_count    = count( $locations );
	$smallestdiff = PHP_INT_MAX;

	// If we only have 2 skip as its probably equally relevant.
	if ( $loc_count > 2 ) {
		// Skip the first as we check 1 behind.
		for ( $i = 1; $i < $loc_count; $i++ ) {
			if ( $i === $loc_count - 1 ) { // At the end.
				$diff = $locations[ $i ] - $locations[ $i - 1 ];
			} else {
				$diff = $locations[ $i + 1 ] - $locations[ $i ];
			}

			if ( $smallestdiff > $diff ) {
				$smallestdiff = $diff;
				$startpos     = $locations[ $i ];
			}
		}
	}

	if ( $startpos > $prevcount ) {
		$startpos = $startpos - $prevcount;
	} else {
		$startpos = 0;
	}

	return $startpos;
}

/**
 * Extracts relevant part of the full text.
 *
 * Finds the part of full text with as many relevant words as possible. 1/6
 * ratio on prevcount tends to work pretty well and puts the terms in the middle
 * of the excerpt.
 *
 * Source: https://boyter.org/2013/04/building-a-search-result-extract-generator-in-php/
 *
 * @author Ben Boyter
 *
 * @param array  $words          An array of relevant words.
 * @param string $fulltext       The source text.
 * @param int    $excerpt_length The length of the excerpt, default 300
 * characters.
 * @param int    $prevcount      How much text include before the words, default
 * 50 characters.
 *
 * @return array The excerpt, number of words in the excerpt, true if it's the
 * start of the $fulltext.
 */
function relevanssi_extract_relevant( $words, $fulltext, $excerpt_length = 300, $prevcount = 50 ) {
	$text_length = relevanssi_strlen( $fulltext );

	if ( $text_length <= $excerpt_length ) {
		return array( $fulltext, 1, 0 );
	}

	$locations = relevanssi_extract_locations( $words, $fulltext );
	$startpos  = relevanssi_determine_snip_location( $locations, $prevcount );

	// If we are going to snip too much...
	if ( $text_length - $startpos < $excerpt_length ) {
		$startpos -= ( $text_length - $startpos ) / 2;
	}

	$substr = function_exists( 'mb_substr' ) ? 'mb_substr' : 'substr';

	$excerpt = call_user_func( $substr, $fulltext, $startpos, $excerpt_length );

	$start = false;
	if ( 0 === $startpos ) {
		$start = true;
	}

	$besthits = count( relevanssi_extract_locations( $words, $excerpt ) );

	return array( $excerpt, $besthits, $start );
}

/**
 * Extracts relevant words of the full text.
 *
 * Finds the part of full text with as many relevant words as possible. If the
 * excerpt length parameter is less than 1, the function will immediately
 * return an empty excerpt in order to avoid an endless loop.
 *
 * @param array  $terms          An array of relevant words.
 * @param string $content        The source text.
 * @param int    $excerpt_length The length of the excerpt, default 30 words.
 *
 * @return array The excerpt, number of words in the excerpt, true if it's the
 * start of the $fulltext.
 */
function relevanssi_extract_relevant_words( $terms, $content, $excerpt_length = 30 ) {
	if ( $excerpt_length < 1 ) {
		return array( '', 0, false );
	}

	$words       = array_filter( explode( ' ', $content ) );
	$offset      = 0;
	$tries       = 0;
	$excerpt     = '';
	$count_words = count( $words );
	$start       = false;
	$gap         = 0;

	$best_excerpt_term_hits = -1;

	$excerpt_candidates = $count_words / $excerpt_length;
	if ( $excerpt_candidates > 200 ) {
		/**
		 * Adjusts the gap between excerpt candidates.
		 *
		 * The default value for the gap is number of words / 200 minus the
		 * excerpt length, which means Relevanssi tries to create 200 excerpts.
		 *
		 * @param int The gap between excerpt candidates.
		 * @param int $count_words    The number of words in the content.
		 * @param int $excerpt_length The length of the excerpt.
		 */
		$gap = apply_filters(
			'relevanssi_excerpt_gap',
			floor( $count_words / 200 - $excerpt_length ),
			$count_words,
			$excerpt_length
		);
	}

	while ( $offset < $count_words ) {
		if ( $offset + $excerpt_length > $count_words ) {
			$offset = $count_words - $excerpt_length;
			if ( $offset < 0 ) {
				$offset = 0;
			}
		}
		$excerpt_slice = array_slice( $words, $offset, $excerpt_length );
		$excerpt_slice = ' ' . implode( ' ', $excerpt_slice );
		$count_matches = relevanssi_count_matches( $terms, $excerpt_slice );
		if ( $count_matches > 0 && $count_matches > $best_excerpt_term_hits ) {
			$best_excerpt_term_hits = $count_matches;
			$excerpt                = $excerpt_slice;
			if ( 0 === $offset ) {
				$start = true;
			} else {
				$start = false;
			}
		}
		$tries++;

		/**
		 * Enables the excerpt optimization.
		 *
		 * If your posts are very long, building excerpts can be really slow.
		 * To speed up the process, you can enable optimization, which means
		 * Relevanssi only creates 50 excerpt candidates.
		 *
		 * @param boolean Return true to enable optimization, default false.
		 */
		if ( apply_filters( 'relevanssi_optimize_excerpts', false ) ) {
			if ( $tries > 50 ) {
				// An optimization trick: try only 50 times.
				break;
			}
		}

		$offset += $excerpt_length + $gap;
	}

	if ( '' === $excerpt && $gap > 0 ) {
		$result = relevanssi_get_first_match( $words, $terms, $excerpt_length );

		$excerpt                = $result['excerpt'];
		$start                  = $result['start'];
		$best_excerpt_term_hits = $result['best_excerpt_term_hits'];
	}

	if ( '' === $excerpt ) {
		/**
		 * Nothing found, take the beginning of the post. +2, because the first
		 * index is an empty space and the last index is the rest of the post.
		 */
		$excerpt = explode( ' ', $content, $excerpt_length + 2 );
		array_pop( $excerpt );
		$excerpt = implode( ' ', $excerpt );
		$start   = true;
	}

	return array( trim( $excerpt ), $best_excerpt_term_hits, $start );
}

/**
 * Finds the first match in the content.
 *
 * Looks for search terms in the post content and stops immediately when the
 * first match is found. Then an excerpt is returned where the match is in the
 * middle of the excerpt.
 *
 * @param array $words          An array of words to look in.
 * @param array $terms          An array of search terms to look for.
 * @param int   $excerpt_length The length of the excerpt.
 *
 * @return array The found excerpt in 'excerpt', a boolean in 'start' that's
 * true if the excerpt was from the start of the content and the number of
 * matches found in the excerpt in 'best_excerpt_term_hits'.
 */
function relevanssi_get_first_match( array $words, array $terms, int $excerpt_length ) {
	$offset                 = 0;
	$excerpt                = '';
	$start                  = false;
	$best_excerpt_term_hits = 0;

	foreach ( $words as $word ) {
		if ( in_array( $word, $terms, true ) ) {
			$offset = floor( $offset - $excerpt_length / 2 );
			if ( $offset < 0 ) {
				$offset = 0;
			}
			$excerpt_slice = array_slice( $words, $offset, $excerpt_length );
			$excerpt       = ' ' . implode( ' ', $excerpt_slice );
			$start         = $offset ? false : true;
			$count_matches = relevanssi_count_matches( $terms, $excerpt );

			$best_excerpt_term_hits = $count_matches;
			break;
		}
		$offset++;
	}

	return array(
		'excerpt'                => $excerpt,
		'start'                  => $start,
		'best_excerpt_term_hits' => $best_excerpt_term_hits,
	);
}

/**
 * Adds accented variations to letters.
 *
 * In order to have non-accented letters in search terms match the accented terms in
 * full text, this function adds accent variations to the search terms.
 *
 * @param string $word The word to manipulate.
 *
 * @return string The word with accent variations.
 */
function relevanssi_add_accent_variations( $word ) {
	/**
	 * Filters the accent replacement array.
	 *
	 * @param array Array of replacements. 'from' has the source characters, 'to' the replacements.
	 */
	$replacement_arrays = apply_filters(
		'relevanssi_accents_replacement_arrays',
		array(
			'from'    => array( 'a', 'c', 'e', 'i', 'o', 'u', 'n' ),
			'to'      => array( '(?:a|á|à|â)', '(?:c|ç)', '(?:e|é|è|ê|ë)', '(?:i|í|ì|î|ï)', '(?:o|ó|ò|ô|õ)', '(?:u|ú|ù|ü|û)', '(?:n|ñ)' ),
			'from_re' => array( "/(s)('|’)?$/", "/[^\(\|:]('|’)/" ),
			'to_re'   => array( "(?:(?:'|’)?\\1|\\1(?:'|’)?)", "?('|’)?" ),
		)
	);

	$len        = relevanssi_strlen( $word );
	$word_array = array();
	$escaped    = false;
	for ( $i = 0; $i < $len; $i++ ) {
		$char = relevanssi_substr( $word, $i, 1 );
		if ( '\\' === $char && ! $escaped ) {
			$escaped = true;
			continue;
		}
		if ( $escaped ) {
			$escaped = false;
			$char    = '\\' . $char;
		}
		$word_array[] = $char;
	}
	$word = implode( '-?', $word_array );
	$word = str_ireplace( $replacement_arrays['from'], $replacement_arrays['to'], $word );
	$word = preg_replace( $replacement_arrays['from_re'], $replacement_arrays['to_re'], $word );

	return $word;
}

/**
 * Fetches the custom field content for a post.
 *
 * @param int $post_id The post ID.
 *
 * @return string The custom field content.
 */
function relevanssi_get_custom_field_content( $post_id ) {
	$custom_field_content = '';

	$custom_fields = relevanssi_generate_list_of_custom_fields( $post_id );

	if ( function_exists( 'relevanssi_get_child_pdf_content' ) ) {
		$custom_field_content .= ' ' . relevanssi_get_child_pdf_content( $post_id );
	}

	foreach ( $custom_fields as $field ) {
		/* Documented in lib/indexing.php. */
		$values = apply_filters(
			'relevanssi_custom_field_value',
			get_post_meta(
				$post_id,
				$field,
				false
			),
			$field,
			$post_id
		);
		if ( empty( $values ) || ! is_array( $values ) ) {
			continue;
		}
		foreach ( $values as $value ) {
			// Quick hack : allow indexing of PODS relationship custom fields. @author TMV.
			if ( is_array( $value ) && isset( $value['post_title'] ) ) {
				$value = $value['post_title'];
			}

			// Flatten other array data.
			if ( is_array( $value ) ) {
				$value_as_string = '';
				array_walk_recursive(
					$value,
					function( $val ) use ( &$value_as_string ) {
						if ( is_string( $val ) ) {
							// Sometimes this can be something weird.
							$value_as_string .= ' ' . $val;
						}
					}
				);
				$value = $value_as_string;
			}
			$custom_field_content .= ' ' . $value;
		}
	}
	/**
	 * Filters the custom field content for excerpt use.
	 *
	 * @param string $custom_field_content Custom field content for excerpts.
	 * @param int    $post_id              The post ID.
	 * @param array  $custom_fields        The list of custom field names.
	 */
	return apply_filters(
		'relevanssi_excerpt_custom_field_content',
		$custom_field_content,
		$post_id,
		$custom_fields
	);
}

/**
 * Kills the autoembed filter hook on 'the_content'.
 *
 * @global array $wp_filter The global filter array.
 *
 * It's an object hook, so this isn't as simple as doing remove_filter(). This
 * needs to be done, because autoembed discovery can take a very, very long
 * time.
 */
function relevanssi_kill_autoembed() {
	global $wp_filter;
	if ( isset( $wp_filter['the_content']->callbacks ) ) {
		foreach ( $wp_filter['the_content']->callbacks as $priority => $bucket ) {
			foreach ( array_keys( $bucket ) as $key ) {
				if ( 'autoembed' === substr( $key, -9 ) ) {
					unset( $wp_filter['the_content']->callbacks[ $priority ][ $key ] );
				}
			}
		}
	}
}

/**
 * Adjusts things before `the_content` is applied in excerpt-building.
 *
 * Removes the `prepend_attachment` filter hook and enables the `noindex`
 * shortcode.
 */
function relevanssi_excerpt_pre_the_content() {
	// This will print out the attachment file name in front of the excerpt, and we
	// don't want that.
	remove_filter( 'the_content', 'prepend_attachment' );

	remove_shortcode( 'noindex' );
	add_shortcode( 'noindex', 'relevanssi_noindex_shortcode_indexing' );
}

/**
 * Adjusts things after `the_content` is applied in excerpt-building.
 *
 * Reapplies the `prepend_attachment` filter hook and disables the `noindex`
 * shortcode.
 */
function relevanssi_excerpt_post_the_content() {
	add_filter( 'the_content', 'prepend_attachment' );

	remove_shortcode( 'noindex' );
	add_shortcode( 'noindex', 'relevanssi_noindex_shortcode' );
}

/**
 * Adds a highlighted title in the post object in $post->post_highlighted_title.
 *
 * @param WP_Post $post  The post object (passed as reference).
 * @param string  $query The search query.
 *
 * @uses relevanssi_highlight_terms
 */
function relevanssi_highlight_post_title( &$post, $query ) {
	$post->post_highlighted_title = wp_strip_all_tags( $post->post_title );
	$highlight                    = get_option( 'relevanssi_highlight' );
	if ( 'none' !== $highlight ) {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$q_for_highlight = 'on' === get_option( 'relevanssi_index_synonyms', 'off' )
			? relevanssi_add_synonyms( $query )
			: $query;

			$post->post_highlighted_title = relevanssi_highlight_terms(
				$post->post_highlighted_title,
				$q_for_highlight
			);
		}
	}
}

/**
 * Replaces $post->post_excerpt with the Relevanssi-generated excerpt and puts
 * the original excerpt in $post->original_excerpt.
 *
 * @param WP_Post $post           The post object (passed as reference).
 * @param string  $query          The search query.
 *
 * @uses relevanssi_do_excerpt
 */
function relevanssi_add_excerpt( &$post, $query ) {
	if ( isset( $post->blog_id ) ) {
		switch_to_blog( $post->blog_id );
	}
	$post->original_excerpt = $post->post_excerpt;
	/**
	 * Filters whether an excerpt should be added to a post or not.
	 *
	 * If this filter hook returns false, Relevanssi does not create an excerpt
	 * for the post. The original excerpt is still copied to
	 * $post->original_excerpt.
	 *
	 * @param boolean If true, create an excerpt. Default true.
	 * @param WP_Post $post  The post object.
	 * @param string  $query The search query.
	 */
	if ( apply_filters( 'relevanssi_excerpt_post', true, $post, $query ) ) {
		$excerpt_length     = get_option( 'relevanssi_excerpt_length' );
		$excerpt_type       = get_option( 'relevanssi_excerpt_type' );
		$post->post_excerpt = relevanssi_do_excerpt(
			$post,
			$query,
			$excerpt_length,
			$excerpt_type
		);
	}
	if ( isset( $post->blog_id ) ) {
		restore_current_blog();
	}
}
