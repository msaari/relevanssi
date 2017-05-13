<?php

/** EXCERPTS **/

function relevanssi_the_excerpt() {
    global $post;
    if (!post_password_required($post)) {
	    echo "<p>" . $post->post_excerpt . "</p>";
	}
	else {
		echo __('There is no excerpt because this is a protected post.');
	}
}

function relevanssi_do_excerpt($t_post, $query) {
	global $post;
	$old_global_post = NULL;
	if ($post != NULL) $old_global_post = $post;
	$post = $t_post;

	$remove_stopwords = false;
	$terms = relevanssi_tokenize($query, $remove_stopwords, -1, false);
	
	$content = apply_filters('relevanssi_pre_excerpt_content', $post->post_content, $post, $query);
	$content = apply_filters('the_content', $content);
	$content = apply_filters('relevanssi_excerpt_content', $content, $post, $query);
	
	$content = relevanssi_strip_invisibles($content); // removes <script>, <embed> &c with content
	$content = preg_replace('/(<\/[^>]+?>)(<[^>\/][^>]*?>)/', '$1 $2', $content); // add spaces between tags to avoid getting words stuck together
	$content = strip_tags($content, get_option('relevanssi_excerpt_allowable_tags', '')); // this removes the tags, but leaves the content
	
	$content = preg_replace("/\n\r|\r\n|\n|\r/", " ", $content);
//	$content = trim(preg_replace("/\s\s+/", " ", $content));
	
	$excerpt_data = relevanssi_create_excerpt($content, $terms, $query);

	if (get_option("relevanssi_index_comments") != 'none') {
		$comment_content = relevanssi_get_comments($post->ID);
		$comment_excerpts = relevanssi_create_excerpt($comment_content, $terms, $query);
		if ($comment_excerpts[1] > $excerpt_data[1]) {
			$excerpt_data = $comment_excerpts;
		}
	}

	if (get_option("relevanssi_index_excerpt") != 'none') {
		$excerpt_content = $post->post_excerpt;
		$excerpt_excerpts = relevanssi_create_excerpt($excerpt_content, $terms, $query);
		if ($excerpt_excerpts[1] > $excerpt_data[1]) {
			$excerpt_data = $excerpt_excerpts;
		}
	}
	
	$start = $excerpt_data[2];

	$excerpt = $excerpt_data[0];	

	// This shuffle with excerpts is done to avoid doubled Read more links in some cases.
	$excerpt_save = $post->post_excerpt;
	$post->post_excerpt = '';
	$excerpt = apply_filters('get_the_excerpt', $excerpt);
	$post->post_excerpt = $excerpt_save;

	$excerpt = trim($excerpt);

	$ellipsis = apply_filters('relevanssi_ellipsis', '...');

	$highlight = get_option('relevanssi_highlight');
	if ("none" != $highlight) {
		if (!is_admin()) {
			$excerpt = relevanssi_highlight_terms($excerpt, $query);
		}
	}

	if (!$start) {
		$excerpt = $ellipsis . $excerpt;
		// do not add three dots to the beginning of the post
	}

	$excerpt = $excerpt . $ellipsis;

	if (relevanssi_s2member_level($post->ID) == 1) $excerpt = $post->post_excerpt;

	if ($old_global_post != NULL) $post = $old_global_post;

	return $excerpt;
}

/**
 * Creates an excerpt from content.
 *
 * @return array - element 0 is the excerpt, element 1 the number of term hits, element 2 is
 * true, if the excerpt is from the start of the content.
 */
function relevanssi_create_excerpt($content, $terms, $query) {
	// If you need to modify these on the go, use 'pre_option_relevanssi_excerpt_length' filter.
	$excerpt_length = get_option("relevanssi_excerpt_length");
	$type = get_option("relevanssi_excerpt_type");

	$best_excerpt_term_hits = -1;
	$excerpt = "";

	$content = " $content";

	$phrases = relevanssi_extract_phrases(stripslashes($query));

	$non_phrase_terms = array();
	foreach ($phrases as $phrase) {
		$phrase_terms = array_keys(relevanssi_tokenize($phrase, $remove_stopwords = false));
		foreach ($terms as $term) {
			if (!in_array($term, $phrase_terms)) {
				$non_phrase_terms[] = $term;
			}
		}
		$terms = $non_phrase_terms;
		$terms[] = $phrase;
	}

	usort($terms, 'relevanssi_strlen_sort');
	
/*
	$highlight = get_option('relevanssi_highlight');
	if ("none" != $highlight) {
		if (!is_admin()) {
			$content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
			// html_entity_decode to avoid highlighting from breaking HTML entities
		}
	}
*/
	
	$start = false;
	if ("chars" == $type) {
		$term_hits = 0;
		foreach (array_keys($terms) as $term) {
			$term = " $term";
			if (function_exists('mb_stripos')) {
				$pos = ("" == $content) ? false : mb_stripos($content, $term);
			}
			else if (function_exists('mb_strpos') && function_exists('mb_strtoupper') && function_exists('mb_substr')) {
				$pos = mb_strpos($content, $term);
				if (false === $pos) {
					$titlecased = mb_strtoupper(mb_substr($term, 0, 1)) . mb_substr($term, 1);
					$pos = mb_strpos($content, $titlecased);
					if (false === $pos) {
						$pos = mb_strpos($content, mb_strtoupper($term));
					}
				}
			}
			else {
				$pos = strpos($content, $term);
				if (false === $pos) {
					$titlecased = strtoupper(substr($term, 0, 1)) . substr($term, 1);
					$pos = strpos($content, $titlecased);
					if (false === $pos) {
						$pos = strpos($content, strtoupper($term));
					}
				}
			}
			
			if (false !== $pos) {
				$term_hits++;
				if ($term_hits > $best_excerpt_term_hits) {
					$best_excerpt_term_hits = $term_hits;
					if ($pos + strlen($term) < $excerpt_length) {
						if (function_exists('mb_substr'))
							$excerpt = mb_substr($content, 0, $excerpt_length);
						else
							$excerpt = substr($content, 0, $excerpt_length);
						$start = true;
					}
					else {
						$half = floor($excerpt_length/2);
						$pos = $pos - $half;
						if (function_exists('mb_substr'))
							$excerpt = mb_substr($content, $pos, $excerpt_length);
						else
							$excerpt = substr($content, $pos, $excerpt_length);
					}
				}
			}
		}
		
		if ("" == $excerpt) {
			if (function_exists('mb_substr'))
				$excerpt = mb_substr($content, 0, $excerpt_length);
			else
				$excerpt = substr($content, 0, $excerpt_length);
			$start = true;
		}
	}
	else {
		$words = explode(' ', $content);
		
		$i = 0;
		
		while ($i < count($words)) {
			if ($i + $excerpt_length > count($words)) {
				$i = count($words) - $excerpt_length;
			}
			$excerpt_slice = array_slice($words, $i, $excerpt_length);
			$excerpt_slice = implode(' ', $excerpt_slice);

			$excerpt_slice = " $excerpt_slice";
			$term_hits = 0;
			foreach (array_keys($terms) as $term) {
				$term = " $term";
				if (function_exists('mb_stripos')) {
					$pos = ("" == $excerpt_slice) ? false : mb_stripos($excerpt_slice, $term);
					// To avoid "empty haystack" warnings
				}
				else if (function_exists('mb_strpos')) {
					$pos = mb_strpos($excerpt_slice, $term);
					if (false === $pos) {
						if (function_exists('mb_strtoupper') && function_exists('mb_strpos') && function_exists('mb_substr')) {
							$titlecased = mb_strtoupper(mb_substr($term, 0, 1)) . mb_substr($term, 1);
							$pos = mb_strpos($excerpt_slice, $titlecased);
							if (false === $pos) {
								$pos = mb_strpos($excerpt_slice, mb_strtoupper($term));
							}
						}
						else {
							$titlecased = strtoupper(substr($term, 0, 1)) . substr($term, 1);
							$pos = strpos($excerpt_slice, $titlecased);
							if (false === $pos) {
								$pos = strpos($excerpt_slice, strtoupper($term));
							}
						}
					}
				}
				else {
					$pos = strpos($excerpt_slice, $term);
					if (false === $pos) {
						$titlecased = strtoupper(substr($term, 0, 1)) . substr($term, 1);
						$pos = strpos($excerpt_slice, $titlecased);
						if (false === $pos) {
							$pos = strpos($excerpt_slice, strtoupper($term));
						}
					}
				}
			
				if (false !== $pos) {
					$term_hits++;
					if (0 == $i) $start = true;
					if ($term_hits > $best_excerpt_term_hits) {
						$best_excerpt_term_hits = $term_hits;
						$excerpt = $excerpt_slice;
					}
				}
			}
			
			$i += $excerpt_length;
		}
		
		if ("" == $excerpt) {
			$excerpt = explode(' ', $content, $excerpt_length);
			array_pop($excerpt);
			$excerpt = implode(' ', $excerpt);
			$start = true;
		}
	}
	
	return array($excerpt, $best_excerpt_term_hits, $start);
}

/** HIGHLIGHTING **/

function relevanssi_highlight_in_docs($content) {
	global $wp_query;
	if (is_singular() && in_the_loop()) {
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referrer = preg_replace('@(http|https)://@', '', stripslashes(urldecode($_SERVER['HTTP_REFERER'])));
			$args     = explode('?', $referrer);
			$query    = array();
	
			if ( count( $args ) > 1 )
				parse_str( $args[1], $query );
	
			if (substr($referrer, 0, strlen($_SERVER['SERVER_NAME'])) == $_SERVER['SERVER_NAME']) {
				// Local search
				if (isset($query['s'])) {
					$content = relevanssi_highlight_terms($content, $query['s']);
				}
			}
			if (function_exists('relevanssi_nonlocal_highlighting')) {
				$content = relevanssi_nonlocal_highlighting($referrer, $content, $query);
			}
		}
	}
	
	return $content;
}

function relevanssi_highlight_terms($excerpt, $query) {
	$type = get_option("relevanssi_highlight");
	if ("none" == $type) {
		return $excerpt;
	}
	
	switch ($type) {
		case "mark":						// thanks to Jeff Byrnes
			$start_emp = "<mark>";
			$end_emp = "</mark>";
			break;
		case "strong":
			$start_emp = "<strong>";
			$end_emp = "</strong>";
			break;
		case "em":
			$start_emp = "<em>";
			$end_emp = "</em>";
			break;
		case "col":
			$col = get_option("relevanssi_txt_col");
			if (!$col) $col = "#ff0000";
			$start_emp = "<span style='color: $col'>";
			$end_emp = "</span>";
			break;
		case "bgcol":
			$col = get_option("relevanssi_bg_col");
			if (!$col) $col = "#ff0000";
			$start_emp = "<span style='background-color: $col'>";
			$end_emp = "</span>";
			break;
		case "css":
			$css = get_option("relevanssi_css");
			if (!$css) $css = "color: #ff0000";
			$start_emp = "<span style='$css'>";
			$end_emp = "</span>";
			break;
		case "class":
			$css = get_option("relevanssi_class");
			if (!$css) $css = "relevanssi-query-term";
			$start_emp = "<span class='$css'>";
			$end_emp = "</span>";
			break;
		default:
			return $excerpt;
	}
	
	$start_emp_token = "**[";
	$end_emp_token = "]**";

	if ( function_exists('mb_internal_encoding') )
		mb_internal_encoding("UTF-8");
	
	$terms = array_keys(relevanssi_tokenize($query, $remove_stopwords = true, $min_word_length = -1));
	
	if (is_array($query)) $query = implode(' ', $query); // just in case
	$phrases = relevanssi_extract_phrases(stripslashes($query));

	$non_phrase_terms = array();
	foreach ($phrases as $phrase) {
		$phrase_terms = array_keys(relevanssi_tokenize($phrase, $remove_stopwords = false));
		foreach ($terms as $term) {
			if (!in_array($term, $phrase_terms)) {
				$non_phrase_terms[] = $term;
			}
		}
		$terms = $non_phrase_terms;
		$terms[] = $phrase;
	}

	usort($terms, 'relevanssi_strlen_sort');

	get_option('relevanssi_word_boundaries', 'on') == 'on' ? $word_boundaries = true : $word_boundaries = false;
	foreach ($terms as $term) {
		$pr_term = relevanssi_replace_punctuation(preg_quote($term, '/'));

		if ($word_boundaries) {
			$excerpt = preg_replace("/(\b$pr_term|$pr_term\b)(?!(^&+)?(;))/iu", $start_emp_token . '\\1' . $end_emp_token, $excerpt);
		}
		else {
			$excerpt = preg_replace("/($pr_term)(?!(^&+)?(;))/iu", $start_emp_token . '\\1' . $end_emp_token, $excerpt);
		}
	
		$preg_start = preg_quote($start_emp_token);
		$preg_end = preg_quote($end_emp_token);

		if (preg_match_all('/<.*>/U', $excerpt, $matches) > 0) {
			// Remove highlights from inside HTML tags
			foreach ($matches as $match) {
				$new_match = str_replace($start_emp_token, '', $match);
				$new_match = str_replace($end_emp_token, '', $new_match);
				$excerpt = str_replace($match, $new_match, $excerpt);
			}
		}

		if (preg_match_all('/<(style|script|object|embed)>.*<\/(style|script|object|embed)>/U', $excerpt, $matches) > 0) {
			// Remove highlights in style, object, embed and script tags
			foreach ($matches as $match) {
				$new_match = str_replace($start_emp_token, '', $match);
				$new_match = str_replace($end_emp_token, '', $new_match);
				$excerpt = str_replace($match, $new_match, $excerpt);
			}
		}
	}
	
	$excerpt = relevanssi_remove_nested_highlights($excerpt, $start_emp_token, $end_emp_token);

/*
	$excerpt = htmlentities($excerpt, ENT_QUOTES, 'UTF-8');
	// return the HTML entities that were stripped before
*/

	$excerpt = str_replace($start_emp_token, $start_emp, $excerpt);
	$excerpt = str_replace($end_emp_token, $end_emp, $excerpt);
	$excerpt = str_replace($end_emp . $start_emp, "", $excerpt);
	if (function_exists('mb_ereg_replace')) {
		$pattern = $end_emp . '\s*' . $start_emp;
		$excerpt = mb_ereg_replace($pattern, " ", $excerpt);
	}

	return $excerpt;
}

function relevanssi_replace_punctuation($a) {
    $a = preg_replace('/[[:punct:]]+/u', '.+?', $a);
    return $a;
}

function relevanssi_remove_nested_highlights($s, $a, $b) {
	$offset = 0;
	$string = "";
	$bits = explode($a, $s);	
	$new_bits = array($bits[0]);
	$in = false;
	for ($i = 1; $i < count($bits); $i++) {
		if ($bits[$i] == '') continue;
		
		if (!$in) {
			array_push($new_bits, $a);
			$in = true;
		}
		if (substr_count($bits[$i], $b) > 0) {
			$in = false;
		}
		if (substr_count($bits[$i], $b) > 1) {
			$more_bits = explode($b, $bits[$i]);
			$j = 0;
			$k = count($more_bits) - 2;
			$whole_bit = "";
			foreach ($more_bits as $bit) {
				$whole_bit .= $bit;
				if ($j == $k) $whole_bit .= $b;
				$j++;
			}
			$bits[$i] = $whole_bit;
		}
		array_push($new_bits, $bits[$i]);
	}
	$whole = implode('', $new_bits);
	
	return $whole;
}

?>