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
 * Prints out the post excerpt.
 *
 * Prints out the post excerpt from $post->post_excerpt, unless the post is
 * protected. Only works in the Loop.
 *
 * @global $post The global post object.
 */
function relevanssi_the_excerpt() {
	global $post;
	if ( ! post_password_required( $post ) ) {
		echo '<p>' . $post->post_excerpt . '</p>'; // WPCS: XSS ok.
	} else {
		echo esc_html__( 'There is no excerpt because this is a protected post.' );
	}
}

/**
 * Generates an excerpt for a post.
 *
 * @global $post The global post object.
 *
 * @param object $t_post The post object.
 * @param string $query  The search query.
 *
 * @return string The created excerpt.
 */
function relevanssi_do_excerpt( $t_post, $query ) {
	global $post;

	// Back up the global post object, and replace it with the post we're working on.
	$old_global_post = null;
	if ( null !== $post ) {
		$old_global_post = $post;
	}
	$post = $t_post; // WPCS: override ok, must do because shortcodes etc. expect it.

	$remove_stopwords = true;

	/**
	 * Filters the search query before excerpt-building.
	 *
	 * Allows filtering the search query before generating an excerpt. This can
	 * useful if you modifications to the search query, and it may help when working
	 * with stemming.
	 *
	 * @param string $query The search query.
	 */
	$query = apply_filters( 'relevanssi_excerpt_query', $query );

	// Minimum word length is -1, we don't care about it right now.
	$terms = relevanssi_tokenize( $query, $remove_stopwords, -1 );

	// These shortcodes cause problems with Relevanssi excerpts.
	$problem_shortcodes = array( 'layerslider', 'responsive-flipbook', 'breadcrumb', 'robogallery', 'gravityview' );
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
	foreach ( $problem_shortcodes as $shortcode ) {
		remove_shortcode( $shortcode );
	}

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

	// Add the custom field content.
	if ( 'on' === get_option( 'relevanssi_excerpt_custom_fields' ) ) {
		$content .= relevanssi_get_custom_field_content( $post->ID );
	}

	// Autoembed discovery can really slow down excerpt-building.
	relevanssi_kill_autoembed();

	// This will print out the attachment file name in front of the excerpt, and we
	// don't want that.
	remove_filter( 'the_content', 'prepend_attachment' );

	/** This filter is documented in wp-includes/post-template.php */
	$content = apply_filters( 'the_content', $content );

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

	// Removes <script>, <embed> &c with content.
	$content = relevanssi_strip_invisibles( $content );

	// Add spaces between tags to avoid getting words stuck together.
	$content = preg_replace( '/(<\/[^>]+?>)(<[^>\/][^>]*?>)/', '$1 $2', $content );

	// This removes the tags, but leaves the content.
	$content = strip_tags( $content, get_option( 'relevanssi_excerpt_allowable_tags', '' ) );

	// Replace linefeeds and carriage returns with spaces.
	$content = preg_replace( "/\n\r|\r\n|\n|\r/", ' ', $content );

	if ( 'OR' === get_option( 'relevanssi_implicit_operator' ) || 'on' === get_option( 'relevanssi_index_synonyms' ) ) {
		$query = relevanssi_add_synonyms( $query );
	}

	// Find the appropriate spot from the post.
	$excerpt_data = relevanssi_create_excerpt( $content, $terms, $query );

	if ( 'none' !== get_option( 'relevanssi_index_comments' ) ) {
		// Use comment content as source material for excerpts.
		$comment_content = relevanssi_get_comments( $post->ID );
		$comment_content = preg_replace( '/(<\/[^>]+?>)(<[^>\/][^>]*?>)/', '$1 $2', $comment_content );
		$comment_content = strip_tags( $comment_content, get_option( 'relevanssi_excerpt_allowable_tags', '' ) );
		if ( ! empty( $comment_content ) ) {
			$comment_excerpts = relevanssi_create_excerpt( $comment_content, $terms, $query );
			if ( $comment_excerpts[1] > $excerpt_data[1] ) {
				// The excerpt created from comments is better than the one created from post data.
				$excerpt_data = $comment_excerpts;
			}
		}
	}

	if ( 'off' !== get_option( 'relevanssi_index_excerpt' ) ) {
		$excerpt_content = $post->post_excerpt;
		$excerpt_content = strip_tags( $excerpt_content, get_option( 'relevanssi_excerpt_allowable_tags', '' ) );

		if ( ! empty( $excerpt_content ) ) {
			$excerpt_excerpts = relevanssi_create_excerpt( $excerpt_content, $terms, $query );
			if ( $excerpt_excerpts[1] > $excerpt_data[1] ) {
				// The excerpt created from post excerpt is the best we found so far.
				$excerpt_data = $excerpt_excerpts;
			}
		}
	}

	$excerpt = $excerpt_data[0];
	$excerpt = trim( $excerpt );
	/**
	 * Filters the excerpt.
	 *
	 * Filters the post excerpt generated by Relevanssi before the highlighting is
	 * applied.
	 *
	 * @param string $excerpt The excerpt.
	 */
	$excerpt = apply_filters( 'relevanssi_excerpt', $excerpt );

	$whole_post_excerpted = false;
	if ( $excerpt === $post->post_content ) {
		$whole_post_excerpted = true;
	}

	if ( empty( $excerpt ) && ! empty( $post->post_excerpt ) ) {
		$excerpt = $post->post_excerpt;
		$excerpt = strip_tags( $excerpt, get_option( 'relevanssi_excerpt_allowable_tags', '' ) );
	}

	/**
	 * Filters the ellipsis Relevanssi uses in excerpts.
	 *
	 * @param string $ellipsis Default '...'.
	*/
	$ellipsis = apply_filters( 'relevanssi_ellipsis', '...' );

	$highlight = get_option( 'relevanssi_highlight' );
	if ( 'none' !== $highlight ) {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$excerpt = relevanssi_highlight_terms( $excerpt, $query );
		}
	}

	$excerpt = relevanssi_close_tags( $excerpt );

	$excerpt_is_from_beginning_of_the_post = $excerpt_data[2];
	if ( ! $whole_post_excerpted ) {
		if ( ! $excerpt_is_from_beginning_of_the_post && ! empty( $excerpt ) ) {
			$excerpt = $ellipsis . $excerpt;
		}

		if ( ! empty( $excerpt ) ) {
			$excerpt = $excerpt . $ellipsis;
		}
	}

	if ( null !== $old_global_post ) {
		$post = $old_global_post; // WPCS: override ok, returning the overridden value.
	}

	return $excerpt;
}

/**
 * Creates an excerpt from content.
 *
 * @param string $content The content.
 * @param array  $terms   The search terms, tokenized.
 * @param string $query   The search query.
 *
 * @return array Element 0 is the excerpt, element 1 the number of term hits, element 2 is
 * true, if the excerpt is from the start of the content.
 */
function relevanssi_create_excerpt( $content, $terms, $query ) {
	// If you need to modify these on the go, use 'pre_option_relevanssi_excerpt_length'
	// and 'pre_option_relevanssi_excerpt_type' filters.
	$excerpt_length = get_option( 'relevanssi_excerpt_length' );
	$type           = get_option( 'relevanssi_excerpt_type' );

	$best_excerpt_term_hits = -1;

	$excerpt = '';
	$content = ' ' . preg_replace( '/\s+/u', ' ', $content );

	// Finds all the phrases in the query.
	$phrases = relevanssi_extract_phrases( stripslashes( $query ) );

	/**
	 * This process generates an array of terms, which has single terms and all the
	 * phrases.
	 */
	$remove_stopwords = false;
	$non_phrase_terms = array();
	foreach ( $phrases as $phrase ) {
		$phrase_terms = array_keys( relevanssi_tokenize( $phrase, $remove_stopwords ) );
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

	$start = false;
	if ( 'chars' === $type ) {
		/**
		 * Character-based excerpts use the relevanssi_extract_relevant() to figure out
		 * the best part of the post to use.
		 */
		$prev_count = floor( $excerpt_length / 2 );

		list( $excerpt, $best_excerpt_term_hits, $start ) = relevanssi_extract_relevant( array_keys( $terms ), $content, $excerpt_length, $prev_count );
	} else {
		/**
		 * Word-based excerpts split the content in an array of individual words and
		 * takes slices.
		 */
		$words       = array_filter( explode( ' ', $content ) );
		$i           = 0;
		$tries       = 0;
		$count_words = count( $words );
		while ( $i < $count_words ) {
			if ( $i + $excerpt_length > $count_words ) {
				$i = $count_words - $excerpt_length;
				if ( $i < 0 ) {
					$i = 0;
				}
			}

			$excerpt_slice = array_slice( $words, $i, $excerpt_length );
			$excerpt_slice = ' ' . implode( ' ', $excerpt_slice );

			$term_hits     = 0;
			$count_matches = relevanssi_count_matches( array_keys( $terms ), $excerpt_slice );
			if ( $count_matches > 0 ) {
				$tries++;
			}
			if ( $count_matches > 0 && $count_matches > $best_excerpt_term_hits ) {
				$best_excerpt_term_hits = $count_matches;
				$excerpt                = $excerpt_slice;
			}

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

			$i += $excerpt_length;
		}

		if ( '' === $excerpt ) {
			// Nothing found, take the beginning of the post.
			$excerpt = explode( ' ', $content, $excerpt_length );
			array_pop( $excerpt );
			$excerpt = implode( ' ', $excerpt );
			$start   = true;
		}
	}

	return array( $excerpt, $best_excerpt_term_hits, $start );
}

/**
 * Manages the highlighting in documents.
 *
 * Uses relevanssi_highlight_terms() and relevanssi_nonlocal_highlighting() to do
 * the highlighting. Attached to 'the_content' and 'comment_text' filter hooks.
 *
 * @global object $wp_query The global WP_Query object.
 *
 * @param string $content The content to highlight.
 *
 * @return string The content with highlights.
 */
function relevanssi_highlight_in_docs( $content ) {
	global $wp_query;
	if ( is_singular() && is_main_query() ) {
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

		if ( function_exists( 'relevanssi_nonlocal_highlighting' ) ) {
			$content = relevanssi_nonlocal_highlighting( $content );
		}
	}

	return $content;
}

/**
 * Adds highlighting to content.
 *
 * Adds highlighting to content based on Relevanssi highlighting settings (if you
 * want to override the settings, 'pre_option_relevanssi_highlight' filter hook
 * is your friend).
 *
 * @param string  $content The content to highlight.
 * @param string  $query   The search query.
 * @param boolean $in_docs Are we highlighting post content? Default false.
 *
 * @return string The $content with highlighting.
 */
function relevanssi_highlight_terms( $content, $query, $in_docs = false ) {
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

	$start_emp_token = '**{}[';
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

	$remove_stopwords = true;
	$terms            = array_keys( relevanssi_tokenize( $query, $remove_stopwords, $min_word_length ) );

	if ( is_array( $query ) ) {
		$query = implode( ' ', $query );
	}
	$phrases = relevanssi_extract_phrases( stripslashes( $query ) );

	$remove_stopwords = false;
	$non_phrase_terms = array();
	foreach ( $phrases as $phrase ) {
		$phrase_terms = array_keys( relevanssi_tokenize( $phrase, $remove_stopwords ) );
		foreach ( $terms as $term ) {
			if ( ! in_array( $term, $phrase_terms, true ) ) {
				$non_phrase_terms[] = $term;
			}
		}
		$terms   = $non_phrase_terms;
		$terms[] = $phrase;
	}

	usort( $terms, 'relevanssi_strlen_sort' );

	$word_boundaries = false;
	if ( 'on' === get_option( 'relevanssi_word_boundaries', 'on' ) ) {
		$word_boundaries = true;
	}

	foreach ( $terms as $term ) {
		$pr_term = preg_quote( $term, '/' );
		$pr_term = relevanssi_add_accent_variations( $pr_term );

		$undecoded_content = $content;
		$content           = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );

		if ( $word_boundaries ) {
			$regex = "/(\b$pr_term\b)/iu";
			if ( 'none' !== get_option( 'relevanssi_fuzzy' ) ) {
				$regex = "/(\b$pr_term|$pr_term\b)/iu";
			}

			$content = preg_replace( $regex, $start_emp_token . '\\1' . $end_emp_token, $content );
			if ( empty( $content ) ) {
				$content = preg_replace( $regex, $start_emp_token . '\\1' . $end_emp_token, $undecoded_content );
			}
		} else {
			$content = preg_replace( "/($pr_term)/iu", $start_emp_token . '\\1' . $end_emp_token, $content );
			if ( empty( $content ) ) {
				$content = preg_replace( "/($pr_term)/iu", $start_emp_token . '\\1' . $end_emp_token, $undecoded_content );
			}
		}

		$preg_start = preg_quote( $start_emp_token );
		$preg_end   = preg_quote( $end_emp_token );

		if ( preg_match_all( '/<.*>/U', $content, $matches ) > 0 ) {
			// Remove highlights from inside HTML tags.
			foreach ( $matches as $match ) {
				$new_match = str_replace( $start_emp_token, '', $match );
				$new_match = str_replace( $end_emp_token, '', $new_match );
				$content   = str_replace( $match, $new_match, $content );
			}
		}

		if ( preg_match_all( '/&.*;/U', $content, $matches ) > 0 ) {
			// Remove highlights from inside HTML entities.
			foreach ( $matches as $match ) {
				$new_match = str_replace( $start_emp_token, '', $match );
				$new_match = str_replace( $end_emp_token, '', $new_match );
				$content   = str_replace( $match, $new_match, $content );
			}
		}

		if ( preg_match_all( '/<(style|script|object|embed)>.*<\/(style|script|object|embed)>/U', $content, $matches ) > 0 ) {
			// Remove highlights in style, object, embed and script tags.
			foreach ( $matches as $match ) {
				$new_match = str_replace( $start_emp_token, '', $match );
				$new_match = str_replace( $end_emp_token, '', $new_match );
				$content   = str_replace( $match, $new_match, $content );
			}
		}
	}

	$content = relevanssi_remove_nested_highlights( $content, $start_emp_token, $end_emp_token );
	$content = relevanssi_fix_entities( $content, $in_docs );

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
 * For excerpts, runs htmlentities() on the excerpt, then converts the allowed tags
 * back into tags.
 *
 * @param string  $excerpt The excerpt to fix.
 * @param boolean $in_docs If true, we are manipulating post content, and need to
 * work in a different fashion.
 *
 * @return string The $excerpt with entities fixed.
 */
function relevanssi_fix_entities( $excerpt, $in_docs ) {
	if ( ! $in_docs ) {
		// For excerpts, use htmlentities().
		$excerpt = htmlentities( $excerpt, ENT_NOQUOTES, 'UTF-8' );

		// Except for allowed tags, which are turned back into tags.
		$tags = get_option( 'relevanssi_excerpt_allowable_tags', '' );
		$tags = trim( str_replace( '<', ' <', $tags ) );
		$tags = explode( ' ', $tags );

		$closing_tags = relevanssi_generate_closing_tags( $tags );

		$tags_entitied = htmlentities( implode( ' ', $tags ), ENT_NOQUOTES, 'UTF-8' );
		$tags_entitied = explode( ' ', $tags_entitied );

		$closing_tags_entitied = htmlentities( implode( ' ', $closing_tags ), ENT_NOQUOTES, 'UTF-8' );
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
			$pattern = '~' . preg_quote( $tag ) . '~';

			$closing_tags_entitied_regexped[] = $pattern;
		}

		$tags          = array_merge( $tags, $closing_tags );
		$tags_entitied = array_merge( $tags_entitied_regexped, $closing_tags_entitied_regexped );

		$excerpt = preg_replace( $tags_entitied, $tags, $excerpt );

		// In case there are attributes. This is the easiest solution, as
		// using quotes and apostrophes un-entitied can't really break
		// anything.
		$excerpt = str_replace( '&quot;', '"', $excerpt );
		$excerpt = str_replace( '&#039;', "'", $excerpt );
	} else {
		// Running htmlentities() for whole posts tends to ruin things.
		// However, we want to run htmlentities() for anything inside
		// <pre> and <code> tags.
		$excerpt = relevanssi_entities_inside( $excerpt, 'code' );
		$excerpt = relevanssi_entities_inside( $excerpt, 'pre' );
	}
	return $excerpt;
}

/**
 * Runs htmlentities() for content inside specified tags.
 *
 * @param string $content The content.
 * @param string $tag     The tag.
 *
 * @return string $content The content with HTML code inside the $tag tags
 * ran through htmlentities().
 */
function relevanssi_entities_inside( $content, $tag ) {
	$hits = preg_match_all( '/<' . $tag . '>(.*?)<\/' . $tag . '>/im', $content, $matches );
	if ( $hits > 0 ) {
		$replacements = array();
		foreach ( $matches[1] as $match ) {
			if ( ! empty( $match ) ) {
				$replacements[] = '<xxx' . $tag . '>' . htmlentities( $match, ENT_QUOTES, 'UTF-8' ) . '</xxx' . $tag . '>';
			}
		}
		if ( ! empty( $replacements ) ) {
			$count_replacements = count( $replacements );
			for ( $i = 0; $i < $count_replacements; $i++ ) {
				$patterns[] = '/<' . $tag . '>(.*?)<\/' . $tag . '>/im';
			}
			$content = preg_replace( $patterns, $replacements, $content, 1 );
		}
		$content = str_replace( 'xxx' . $tag, $tag, $content );
	}
	return $content;
}

/**
 * Generates closing tags for an array of tags.
 *
 * @param array $tags Array of tag names.
 *
 * @return array $closing_tags Array of closing tags.
 */
function relevanssi_generate_closing_tags( $tags ) {
	$closing_tags = array();
	foreach ( $tags as $tag ) {
		$a = str_replace( '<', '</', $tag );
		$b = str_replace( '>', '/>', $tag );

		$closing_tags[] = $a;
		$closing_tags[] = $b;
	}
	return $closing_tags;
}

/**
 * Removes nested highlights from a string.
 *
 * If there are highlights within highlights in a string, this function will clean
 * out the nested highlights, leaving just the outmost highlight tokens.
 *
 * @param string $string The content.
 * @param string $begin  The beginning highlight token.
 * @param string $end    The ending highlight token.
 *
 * @return string The string with nested highlights cleaned out.
 */
function relevanssi_remove_nested_highlights( $string, $begin, $end ) {
	$offset     = 0;
	$bits       = explode( $begin, $string );
	$new_bits   = array( $bits[0] );
	$count_bits = count( $bits );
	$in         = false;
	for ( $i = 1; $i < $count_bits; $i++ ) {
		if ( '' === $bits[ $i ] ) {
			continue;
		}

		if ( ! $in ) {
			array_push( $new_bits, $begin );
			$in = true;
		}
		if ( substr_count( $bits[ $i ], $end ) > 0 ) {
			$in = false;
		}
		if ( substr_count( $bits[ $i ], $end ) > 1 ) {
			$more_bits = explode( $end, $bits[ $i ] );
			$j         = 0;
			$k         = count( $more_bits ) - 2;
			$whole_bit = '';
			foreach ( $more_bits as $bit ) {
				$whole_bit .= $bit;
				if ( $j === $k ) {
					$whole_bit .= $end;
				}
				$j++;
			}
			$bits[ $i ] = $whole_bit;
		}
		array_push( $new_bits, $bits[ $i ] );
	}
	$whole = implode( '', $new_bits );

	return $whole;
}

/**
 * Finds the  locations of each word.
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
		$word_slice = relevanssi_strtolower( $words[ $t ], 'UTF-8' );
		$lines      = explode( $word_slice, $lowercase_text );
		if ( count( $lines ) > 1 ) {
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
	if ( $count < 0 ) {
		$count = 0;
	}

	return $count;
}

/**
 * Works out which is the most relevant portion to display.
 *
 * This is done by looping over each match and finding the smallest distance
 * between two found strings. The idea being that the closer the terms are the better
 * match the snippet would be. When checking for matches we only change the location
 * if there is a better match. The only exception is where we have only two matches
 * in which case we just take the first as will be equally distant.
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

	$startpos = 0;
	if ( $startpos > $prevcount ) {
		$startpos - $prevcount;
	}

	return $startpos;
}

/**
 * Extracts relevant part of the full text.
 *
 * Finds the part of full text with as many relevant words as possible. 1/6 ratio on
 * prevcount tends to work pretty well and puts the terms in the middle of the
 * excerpt.
 *
 * @author Ben Boyter
 *
 * @param array  $words          An array of relevant words.
 * @param string $fulltext       The source text.
 * @param int    $excerpt_length The length of the excerpt, default 300 characters.
 * @param int    $prevcount      How much text include before the words, default 50
 * characters.
 *
 * @return array The excerpt, number of words in the excerpt, true if it's the start
 * of the $fulltext.
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
		$startpos = $startpos - ( $text_length - $startpos ) / 2;
	}

	$substr = 'substr';
	if ( function_exists( 'mb_substr' ) ) {
		$substr = 'mb_substr';
	}
	$strrpos = 'strrpos';
	if ( function_exists( 'mb_strrpos' ) ) {
		$strrpos = 'mb_strrpos';
	}

	$excerpt = call_user_func( $substr, $fulltext, $startpos, $excerpt_length );

	// Check to ensure we don't snip the last word if that's the match.
	if ( $startpos + $excerpt_length < $text_length ) {
		$excerpt = call_user_func( $substr, $excerpt, 0, call_user_func( $strrpos, $excerpt, ' ' ) ); // Remove last word.
	}

	$start = false;
	if ( 0 === $startpos ) {
		$start = true;
	}

	$besthits = count( relevanssi_extract_locations( $words, $excerpt ) );

	return array( $excerpt, $besthits, $start );
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
	$replacement_arrays = apply_filters('relevanssi_accents_replacement_arrays', array(
		'from' => array( 'a', 'c', 'e', 'i', 'o', 'u', 'n', 'ss' ),
		'to'   => array( '(a|á|à|â)', '(c|ç)', '(e|é|è|ê|ë)', '(i|í|ì|î|ï)', '(o|ó|ò|ô|õ)', '(u|ú|ù|ü|û)', '(n|ñ)', '(ss|ß)' ),
	));

	$word = str_ireplace( $replacement_arrays['from'], $replacement_arrays['to'], $word );

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
	$custom_field_content     = '';
	$remove_underscore_fields = false;

	$custom_fields = relevanssi_get_custom_fields();
	if ( isset( $custom_fields ) && 'all' === $custom_fields ) {
		$custom_fields = get_post_custom_keys( $post_id );
	}
	if ( isset( $custom_fields ) && 'visible' === $custom_fields ) {
		$custom_fields            = get_post_custom_keys( $post_id );
		$remove_underscore_fields = true;
	}
	/* Documented in lib/indexing.php. */
	$custom_fields = apply_filters( 'relevanssi_index_custom_fields', $custom_fields );

	if ( function_exists( 'relevanssi_get_child_pdf_content' ) ) {
		$custom_field_content .= ' ' . relevanssi_get_child_pdf_content( $post_id );
	}

	if ( is_array( $custom_fields ) ) {
		$custom_fields = array_unique( $custom_fields ); // No reason to index duplicates.

		$repeater_fields = array();
		if ( function_exists( 'relevanssi_add_repeater_fields' ) ) {
			relevanssi_add_repeater_fields( $custom_fields, $post_id );
		}

		foreach ( $custom_fields as $field ) {
			if ( $remove_underscore_fields ) {
				if ( '_' === substr( $field, 0, 1 ) ) {
					continue;
				}
			}
			/* Documented in lib/indexing.php. */
			$values = apply_filters( 'relevanssi_custom_field_value', get_post_meta( $post_id, $field, false ), $field, $post_id );
			if ( '' === $values ) {
				continue;
			}
			foreach ( $values as $value ) {
				// Quick hack : allow indexing of PODS relationship custom fields. @author TMV.
				if ( is_array( $value ) && isset( $value['post_title'] ) ) {
					$value = $value['post_title'];
				}

				// Flatten other array data.
				if ( is_array( $value ) ) {
					$value = implode( ' ', $value );
				}
				$custom_field_content .= ' ' . $value;
			}
		}
	}
	/**
	 * Filters the custom field content for excerpt use.
	 *
	 * @param string $custom_field_content Custom field content for excerpts.
	 */
	return apply_filters( 'relevanssi_excerpt_custom_field_content', $custom_field_content );
}

/**
 * Removes page builder short codes from content.
 *
 * Page builder shortcodes cause problems in excerpts. This function cleans them
 * out.
 *
 * @param string $content The content to clean.
 *
 * @return string The content without page builder shortcodes.
 */
function relevanssi_remove_page_builder_shortcodes( $content ) {
	/**
	 * Filters the page builder shortcode.
	 *
	 * @param array An array of page builder shortcode regexes.
	 */
	$search_array = apply_filters('relevanssi_page_builder_shortcodes', array(
		// Remove content.
		'/\[et_pb_code.*?\].*\[\/et_pb_code\]/',
		'/\[et_pb_sidebar.*?\].*\[\/et_pb_sidebar\]/',
		'/\[vc_raw_html.*?\].*\[\/vc_raw_html\]/',
		// Remove only the tags.
		'/\[\/?et_pb.*?\]/',
		'/\[\/?vc.*?\]/',
		'/\[\/?mk.*?\]/',
		'/\[\/?cs_.*?\]/',
		'/\[\/?av_.*?\]/',
		'/\[\/?fusion_.*?\]/',
		// Max Mega Menu doesn't work in excerpts.
		'/\[maxmegamenu.*?\]/',
	));
	$content = preg_replace( $search_array, '', $content );
	return $content;
}

/**
 * Kills the autoembed filter hook on 'the_content'.
 *
 * @global array $wp_filter The global filter array.
 *
 * It's an object hook, so this isn't as simple as doing remove_filter(). This
 * needs to be done, because autoembed discovery can take a very, very long time.
 */
function relevanssi_kill_autoembed() {
	global $wp_filter;
	if ( isset( $wp_filter['the_content']->callbacks ) ) {
		foreach ( $wp_filter['the_content']->callbacks as $priority => $bucket ) {
			foreach ( $bucket as $key => $value ) {
				if ( 'autoembed' === substr( $key, -9 ) ) {
					unset( $wp_filter['the_content']->callbacks[ $priority ][ $key ] );
				}
			}
		}
	}
}
