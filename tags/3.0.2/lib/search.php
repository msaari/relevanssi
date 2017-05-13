<?php

function relevanssi_query($posts, $query = false) {
	$admin_search = get_option('relevanssi_admin_search');
	($admin_search == 'on') ? $admin_search = true : $admin_search = false;

	global $relevanssi_active;
	global $wp_query;

	$search_ok = true; 							// we will search!
	if (!is_search()) {
		$search_ok = false;						// no, we can't
	}
	
	// Uses $wp_query->is_admin instead of is_admin() to help with Ajax queries that
	// use 'admin_ajax' hook (which sets is_admin() to true whether it's an admin search
	// or not.
	if (is_search() && $wp_query->is_admin) {
		$search_ok = false; 					// but if this is an admin search, reconsider
		if ($admin_search) $search_ok = true; 	// yes, we can search!
	}

	// Disable search in media library search
	if ($search_ok) {
		if ($wp_query->query_vars['post_type'] == 'attachment' && $wp_query->query_vars['post_status'] == 'inherit,private') {
			$search_ok = false;
		}
	}

	$search_ok = apply_filters('relevanssi_search_ok', $search_ok);
	
	if ($relevanssi_active) {
		$search_ok = false;						// Relevanssi is already in action
	}

	if ($search_ok) {
		$wp_query = apply_filters('relevanssi_modify_wp_query', $wp_query);
		$posts = relevanssi_do_query($wp_query);
	}

	return $posts;
}

// This is my own magic working.
function relevanssi_search($q, $cat = NULL, $excat = NULL, $tag = NULL, $expost = NULL, $post_type = NULL, $taxonomy = NULL, $taxonomy_term = NULL, $operator = "AND", $search_blogs = NULL, $customfield_key = NULL, $customfield_value = NULL, $author = NULL) {
	global $wpdb, $relevanssi_variables;
	$relevanssi_table = $relevanssi_variables['relevanssi_table'];

	$values_to_filter = array(
		'q' => $q,
		'cat' => $cat,
		'excat' => $excat,
		'tag' => $tag,
		'expost' => $expost,
		'post_type' => $post_type,
		'taxonomy' => $taxonomy,
		'taxonomy_term' => $taxonomy_term,
		'operator' => $operator,
		'search_blogs' => $search_blogs,
		'customfield_key' => $customfield_key,
		'customfield_value' => $customfield_value,
		'author' => $author,
		);
	$filtered_values = apply_filters( 'relevanssi_search_filters', $values_to_filter );
	$q               = $filtered_values['q'];
	$cat             = $filtered_values['cat'];
	$tag             = $filtered_values['tag'];
	$excat           = $filtered_values['excat'];
	$expost          = $filtered_values['expost'];
	$post_type       = $filtered_values['post_type'];
	$taxonomy        = $filtered_values['taxonomy'];
	$taxonomy_term   = $filtered_values['taxonomy_term'];
	$operator        = $filtered_values['operator'];
	$search_blogs    = $filtered_values['search_blogs'];
	$customfield_key = $filtered_values['customfield_key'];
	$customfield_value = $filtered_values['customfield_value'];
	$author	  	     = $filtered_values['author'];

	$hits = array();

	$o_cat = $cat;
	$o_excat = $excat;
	$o_tag = $tag;
	$o_expost = $expost;
	$o_post_type = $post_type;
	$o_taxonomy = $taxonomy;
	$o_taxonomy_term = $taxonomy_term;
	$o_customfield_key = $customfield_key;
	$o_customfield_value = $customfield_value;
	$o_author = $author;

	if (function_exists('relevanssi_process_customfield')) {
		$customfield = relevanssi_process_customfield($customfield_key, $customfield_value);
	}
	else {
		$customfield = false;
	}
	
	if ($cat) {
		$cats = explode(",", $cat);
		$inc_term_tax_ids = array();
		$ex_term_tax_ids = array();
		foreach ($cats as $t_cat) {
			$exclude = false;
			if ($t_cat < 0) {
				// Negative category, ie. exclusion
				$exclude = true;
				$t_cat = substr($t_cat, 1); // strip the - sign.
			}
			$t_cat = $wpdb->escape($t_cat);
			$term_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy
				WHERE term_id=$t_cat");
			if ($term_tax_id) {
				$exclude ? $ex_term_tax_ids[] = $term_tax_id : $inc_term_tax_ids[] = $term_tax_id;
				$children = get_term_children($term_tax_id, 'category');
				if (is_array($children)) {
					foreach ($children as $child) {
						$exclude ? $ex_term_tax_ids[] = $child : $inc_term_tax_ids[] = $child;
					}
				}
			}
		}
		
		$cat = implode(",", $inc_term_tax_ids);
		$excat_temp = implode(",", $ex_term_tax_ids);
	}

	if ($excat) {
		$excats = explode(",", $excat);
		$term_tax_ids = array();
		foreach ($excats as $t_cat) {
			$t_cat = $wpdb->escape(trim($t_cat, ' -'));
			$term_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy
				WHERE term_id=$t_cat");
			if ($term_tax_id) {
				$term_tax_ids[] = $term_tax_id;
			}
		}
		
		$excat = implode(",", $term_tax_ids);
	}

	if (isset($excat_temp)) {
		$excat .= $excat_temp;
	}

	if ($tag) {
		$tags = explode(",", $tag);
		$inc_term_tax_ids = array();
		$ex_term_tax_ids = array();
		foreach ($tags as $t_tag) {
			$t_tag = $wpdb->escape($t_tag);
			$term_tax_id = $wpdb->get_var("
				SELECT term_taxonomy_id
					FROM $wpdb->term_taxonomy as a, $wpdb->terms as b
					WHERE a.term_id = b.term_id AND
						(a.term_id='$t_tag' OR b.name LIKE '$t_tag')");

			if ($term_tax_id) {
				$inc_term_tax_ids[] = $term_tax_id;
			}
		}
		
		$tag = implode(",", $inc_term_tax_ids);
	}
	
	if ($author) {
		$author = esc_sql($author);
	}

	if (!empty($taxonomy)) {
		if (function_exists('relevanssi_process_taxonomies')) {
			$taxonomy = relevanssi_process_taxonomies($taxonomy, $taxonomy_term);
		}
		else {
			$term_tax_id = null;
			$term_tax_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM $wpdb->terms
				JOIN $wpdb->term_taxonomy USING(`term_id`)
					WHERE `slug` LIKE %s AND `taxonomy` LIKE %s", "%$taxonomy_term%", $taxonomy));
			if ($term_tax_id) {
				$taxonomy = $term_tax_id;
			} else {
				$taxonomy = null;
			}
		}
	}

	if (!$post_type && get_option('relevanssi_respect_exclude') == 'on') {
		if (function_exists('get_post_types')) {
			$pt_1 = get_post_types(array('exclude_from_search' => '0'));
			$pt_2 = get_post_types(array('exclude_from_search' => false));
			$post_type = implode(',', array_merge($pt_1, $pt_2));
		}
	}
	
	if ($post_type) {
		if (!is_array($post_type)) {
			$post_types = explode(',', $post_type);
		}
		else {
			$post_types = $post_type;
		}
		$pt_array = array();
		foreach ($post_types as $pt) {
			$pt = "'" . trim(mysql_real_escape_string($pt)) . "'";
			array_push($pt_array, $pt);
		}
		$post_type = implode(",", $pt_array);
	}

	//Added by OdditY:
	//Exclude Post_IDs (Pages) for non-admin search ->
	$postex = '';
	if ($expost) {
		if ($expost != "") {
			$aexpids = explode(",",$expost);
			foreach ($aexpids as $exid){
				$exid = $wpdb->escape(trim($exid, ' -'));
				$postex .= " AND doc !='$exid'";
			}
		}	
	}
	// <- OdditY End

	$remove_stopwords = false;
	$phrases = relevanssi_recognize_phrases($q);

	if (function_exists('relevanssi_recognize_negatives')) {
		$negative_terms = relevanssi_recognize_negatives($q);
	}
	else {
		$negative_terms = false;
	}
	
	if (function_exists('relevanssi_recognize_positives')) {
		$positive_terms = relevanssi_recognize_positives($q);
	}
	else {
		$positive_terms = false;
	}

	$terms = relevanssi_tokenize($q, $remove_stopwords);
	if (count($terms) < 1) {
		// Tokenizer killed all the search terms.
		return $hits;
	}
	$terms = array_keys($terms); // don't care about tf in query

	if ($negative_terms) {	
		$terms = array_diff($terms, $negative_terms);
		if (count($terms) < 1) {
			return $hits;
		}
	}
	
	$D = $wpdb->get_var("SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table");
	
	$total_hits = 0;
		
	$title_matches = array();
	$tag_matches = array();
	$comment_matches = array();
	$link_matches = array();
	$body_matches = array();
	$scores = array();
	$term_hits = array();

	$fuzzy = get_option('relevanssi_fuzzy');

	$query_restrictions = "";
	if ($expost) { //added by OdditY
		$query_restrictions .= $postex;
	}

	if (function_exists('relevanssi_negatives_positives')) {	
		$query_restrictions .= relevanssi_negatives_positives($negative_terms, $positive_terms, $relevanssi_table);
	}
	
	if ($cat) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($cat))";
	}
	if ($excat) {
		$query_restrictions .= " AND doc NOT IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($excat))";
	}
	if ($tag) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($tag))";
	}
	if ($author) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
		    WHERE post_author IN ($author))";
	}
	if ($post_type) {
		// the -1 is there to get user profiles and category pages
		$query_restrictions .= " AND ((doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
			WHERE post_type IN ($post_type))) OR (doc = -1))";
	}
	if ($phrases) {
		$query_restrictions .= " AND doc IN ($phrases)";
	}
	if ($customfield) {
		$query_restrictions .= " AND doc IN ($customfield)";
	}
	if (is_array($taxonomy)) {
		foreach ($taxonomy as $tax) {
			$taxonomy_in = implode(',',$tax);
			$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
				WHERE term_taxonomy_id IN ($taxonomy_in))";
		}
	}

	if (isset($_REQUEST['by_date'])) {
		$n = $_REQUEST['by_date'];

		$u = substr($n, -1, 1);
		switch ($u) {
			case 'h':
				$unit = "HOUR";
				break;
			case 'd':
				$unit = "DAY";
				break;
			case 'm':
				$unit = "MONTH";
				break;
			case 'y':
				$unit = "YEAR";
				break;
			case 'w':
				$unit = "WEEK";
				break;
			default:
				$unit = "DAY";
		}

		$n = preg_replace('/[hdmyw]/', '', $n);

		if (is_numeric($n)) {
			$query_restrictions .= " AND doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
				WHERE post_date > DATE_SUB(NOW(), INTERVAL $n $unit))";
		}
	}

	$query_restrictions = apply_filters('relevanssi_where', $query_restrictions); // Charles St-Pierre

	$no_matches = true;
	if ("always" == $fuzzy) {
		$o_term_cond = apply_filters('relevanssi_fuzzy_query', "(term LIKE '%#term#' OR term LIKE '#term#%') ");
	}
	else {
		$o_term_cond = " term = '#term#' ";
	}

	$post_type_weights = get_option('relevanssi_post_type_weights');
	if (function_exists('relevanssi_get_recency_bonus')) {
		list($recency_bonus, $recency_cutoff_date) = relevanssi_get_recency_bonus();
	}
	else {
		$recency_bonus = false;
		$recency_cutoff_date = false;
	}
	$min_length = get_option('relevanssi_min_word_length');
	
	$search_again = false;
	do {
		foreach ($terms as $term) {
			if (strlen($term) < $min_length) continue;
			$term = $wpdb->escape(like_escape($term));
			$term_cond = str_replace('#term#', $term, $o_term_cond);		
			
			$query = "SELECT *, title + content + comment + tag + link + author + category + excerpt + taxonomy + customfield + mysqlcolumn AS tf 
					  FROM $relevanssi_table WHERE $term_cond $query_restrictions";
			$query = apply_filters('relevanssi_query_filter', $query);

			$matches = $wpdb->get_results($query);
			if (count($matches) < 1) {
				continue;
			}
			else {
				$no_matches = false;
			}
			
			relevanssi_populate_array($matches);
			global $relevanssi_post_types;

			$total_hits += count($matches);
	
			$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE $term_cond $query_restrictions";
			$query = apply_filters('relevanssi_df_query_filter', $query);
	
			$df = $wpdb->get_var($query);
	
			if ($df < 1 && "sometimes" == $fuzzy) {
				$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table
					WHERE (term LIKE '%$term' OR term LIKE '$term%') $query_restrictions";
				$query = apply_filters('relevanssi_df_query_filter', $query);
				$df = $wpdb->get_var($query);
			}
			
			$title_boost = floatval(get_option('relevanssi_title_boost'));
			$link_boost = floatval(get_option('relevanssi_link_boost'));
			$comment_boost = floatval(get_option('relevanssi_comment_boost'));
			
			$idf = log($D / (1 + $df));
			$idf = $idf * $idf;
			foreach ($matches as $match) {
				if ('user' == $match->type) {
					$match->doc = 'u_' . $match->item;
				}

				if ('taxonomy' == $match->type) {
					$match->doc = 't_' . $match->item;
				}

				if (isset($match->taxonomy_detail)) {
					$match->taxonomy_score = 0;
					$match->taxonomy_detail = unserialize($match->taxonomy_detail);
					if (is_array($match->taxonomy_detail)) {
						foreach ($match->taxonomy_detail as $tax => $count) {
							$match->taxonomy_score += $count * $post_type_weights[$tax];
						}
					}
				}
				
				$match->tf =
					$match->title * $title_boost +
					$match->content +
					$match->comment * $comment_boost +
					$match->link * $link_boost +
					$match->author +
					$match->excerpt +
					$match->taxonomy_score +
					$match->customfield +
					$match->mysqlcolumn;

				$term_hits[$match->doc][$term] =
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
				
				if ($recency_bonus) {
					$post = relevanssi_get_post($match->doc);
					if (strtotime($post->post_date) > $recency_cutoff_date)
						$match->weight = $match->weight * $recency_bonus['bonus'];
				}

				$body_matches[$match->doc] = $match->content;
				$title_matches[$match->doc] = $match->title;
				$tag_matches[$match->doc] = $match->tag;
				$comment_matches[$match->doc] = $match->comment;
	
				$type = $relevanssi_post_types[$match->doc];
				if (isset($post_type_weights[$type])) {
					$match->weight = $match->weight * $post_type_weights[$type];
				}

				$match = apply_filters('relevanssi_match', $match, $idf);

				if ($match->weight == 0) continue; // the filters killed the match

				$post_ok = true;
				$post_ok = apply_filters('relevanssi_post_ok', $post_ok, $match->doc);
				
				if ($post_ok) {
					$doc_terms[$match->doc][$term] = true; // count how many terms are matched to a doc
					isset($doc_weight[$match->doc]) ? $doc_weight[$match->doc] += $match->weight : $doc_weight[$match->doc] = $match->weight;
					isset($scores[$match->doc]) ? $scores[$match->doc] += $match->weight : $scores[$match->doc] = $match->weight;
				}
			}
		}

		if (!isset($doc_weight)) $no_matches = true;

		if ($no_matches) {
			if ($search_again) {
				// no hits even with fuzzy search!
				$search_again = false;
			}
			else {
				if ("sometimes" == $fuzzy) {
					$search_again = true;
					$o_term_cond = "(term LIKE '%#term#' OR term LIKE '#term#%') ";
				}
			}
		}
		else {
			$search_again = false;
		}
	} while ($search_again);
	
	$strip_stops = true;
	$temp_terms_without_stops = array_keys(relevanssi_tokenize(implode(' ', $terms), $strip_stops));
	$terms_without_stops = array();
	foreach ($temp_terms_without_stops as $temp_term) {
		if (strlen($temp_term) >= $min_length)
			array_push($terms_without_stops, $temp_term);
	}
	$total_terms = count($terms_without_stops);

	if (isset($doc_weight))
		$doc_weight = apply_filters('relevanssi_results', $doc_weight);

	if (isset($doc_weight) && count($doc_weight) > 0) {
		arsort($doc_weight);
		$i = 0;
		foreach ($doc_weight as $doc => $weight) {
			if (count($doc_terms[$doc]) < $total_terms && $operator == "AND") {
				// AND operator in action:
				// doc didn't match all terms, so it's discarded
				continue;
			}
			
			$hits[intval($i++)] = relevanssi_get_post($doc);
		}
	}

	if (count($hits) < 1) {
		if ($operator == "AND" AND get_option('relevanssi_disable_or_fallback') != 'on') {
			$return = relevanssi_search($q, $o_cat, $o_excat, $o_tag, $o_expost, $o_post_type, $o_taxonomy, $o_taxonomy_term, "OR", $search_blogs, $o_customfield_key, $o_customfield_value);
			extract($return);
		}
	}

	global $wp;	
	$default_order = get_option('relevanssi_default_orderby', 'relevance');
	isset($wp->query_vars["orderby"]) ? $orderby = $wp->query_vars["orderby"] : $orderby = $default_order;
	isset($wp->query_vars["order"]) ? $order = $wp->query_vars["order"] : $order = 'desc';
	if ($orderby != 'relevance')
		relevanssi_object_sort($hits, $orderby, $order);

	$return = array('hits' => $hits, 'body_matches' => $body_matches, 'title_matches' => $title_matches,
		'tag_matches' => $tag_matches, 'comment_matches' => $comment_matches, 'scores' => $scores,
		'term_hits' => $term_hits, 'query' => $q);

	return $return;
}

function relevanssi_do_query(&$query) {
	// this all is basically lifted from Kenny Katzgrau's wpSearch
	// thanks, Kenny!
	global $relevanssi_active;

	$relevanssi_active = true;
	$posts = array();

	if ( function_exists( 'mb_strtolower' ) )
		$q = trim(stripslashes(mb_strtolower($query->query_vars["s"])));
	else
		$q = trim(stripslashes(strtolower($query->query_vars["s"])));

	$cache = get_option('relevanssi_enable_cache');
	$cache == 'on' ? $cache = true : $cache = false;

	if (isset($query->query_vars['searchblogs'])) {
		$search_blogs = $query->query_vars['searchblogs'];

		$post_type = false;
		if (isset($query->query_vars["post_type"]) && $query->query_vars["post_type"] != 'any') {
			$post_type = $query->query_vars["post_type"];
		}
		if (isset($query->query_vars["post_types"])) {
			$post_type = $query->query_vars["post_types"];
		}

		if (function_exists('relevanssi_search_multi')) {
			$return = relevanssi_search_multi($q, $search_blogs, $post_type);
		}
	}
	else {
		$cat = false;
		if (isset($query->query_vars["cat"])) {
			$cat = $query->query_vars["cat"];
		}
		if (isset($query->query_vars["cats"])) {
			$cat = $query->query_vars["cats"];
		}
		if (!$cat) {
			$cat = get_option('relevanssi_cat');
			if (0 == $cat) {
				$cat = false;
			}
		}

		$tag = false;
		if (isset($query->query_vars["tag"])) {
			$tag = $query->query_vars["tag"];
		}
		if (isset($query->query_vars["tags"])) {
			$tag = $query->query_vars["tags"];
		}

		$author = false;
		if (isset($query->query_vars["author"])) {
			$author = $query->query_vars["author"];
		}

		$customfield_key = false;
		if (isset($query->query_vars["customfield_key"])) {
			$customfield_key = $query->query_vars["customfield_key"];
		}
		$customfield_value = false;
		if (isset($query->query_vars["customfield_value"])) {
			$customfield_value = $query->query_vars["customfield_value"];
		}
	
		$tax = false;
		$tax_term = false;
		if (isset($query->query_vars["taxonomy"])) {
			$tax = $query->query_vars["taxonomy"];
			$tax_term = $query->query_vars["term"];
		}
		
		if (!isset($excat)) {
			$excat = get_option('relevanssi_excat');
			if (0 == $excat) {
				$excat = false;
			}
		}
	
		$search_blogs = false;
		if (isset($query->query_vars["search_blogs"])) {
			$search_blogs = $query->query_vars["search_blogs"];
		}
	
		$post_type = false;
		if (isset($query->query_vars["post_type"]) && $query->query_vars["post_type"] != 'any') {
			$post_type = $query->query_vars["post_type"];
		}
		if (isset($query->query_vars["post_types"])) {
			$post_type = $query->query_vars["post_types"];
		}
	
		$expids = get_option("relevanssi_exclude_posts");
	
		if (is_admin()) {
			// in admin search, search everything
			$excat = null;
			$expids = null;
		}

		$operator = "";
		if (function_exists('relevanssi_set_operator')) {
			$operator = relevanssi_set_operator($query);
			$operator = strtoupper($operator);	// just in case
		}
		if ($operator != "OR" && $operator != "AND") $operator = get_option("relevanssi_implicit_operator");
		
		// Add synonyms
		// This is done here so the new terms will get highlighting
		if ("OR" == $operator) {
			// Synonyms are only used in OR queries
			$synonym_data = get_option('relevanssi_synonyms');
			if ($synonym_data) {
				$synonyms = array();
				$pairs = explode(";", $synonym_data);
				foreach ($pairs as $pair) {
					$parts = explode("=", $pair);
					$key = trim($parts[0]);
					$value = trim($parts[1]);
					$synonyms[$key][$value] = true;
				}
				if (count($synonyms) > 0) {
					$new_terms = array();
					$terms = array_keys(relevanssi_tokenize($q, false)); // remove stopwords is false here
					foreach ($terms as $term) {
						if (in_array($term, array_keys($synonyms))) {
							$new_terms = array_merge($new_terms, array_keys($synonyms[$term]));
						}
					}
					if (count($new_terms) > 0) {
						foreach ($new_terms as $new_term) {
							$q .= " $new_term";
						}
					}
				}
			}
		}
	
		if ($cache) {
			$params = md5(serialize(array($q, $cat, $excat, $tag, $expids, $post_type, $tax, $tax_term, $operator, $search_blogs, $customfield_key, $customfield_value, $author)));
			$return = relevanssi_fetch_hits($params);
			if (!$return) {
				$return = relevanssi_search($q, $cat, $excat, $tag, $expids, $post_type, $tax, $tax_term, $operator, $search_blogs, $customfield_key, $customfield_value, $author);
				$return_ser = serialize($return);
				relevanssi_store_hits($params, $return_ser);
			}
		}
		else {
			$return = relevanssi_search($q,
										$cat, $excat,
										$tag,
										$expids,
										$post_type,
										$tax, $tax_term,
										$operator,
										$search_blogs,
										$customfield_key,
										$customfield_value,
										$author);
		}
	}

	$hits = $return['hits'];
	$q = $return['query'];

	$filter_data = array($hits, $q);
	$hits_filters_applied = apply_filters('relevanssi_hits_filter', $filter_data);
	$hits = $hits_filters_applied[0];

	$query->found_posts = sizeof($hits);
	$query->max_num_pages = ceil(sizeof($hits) / $query->query_vars["posts_per_page"]);

	$update_log = get_option('relevanssi_log_queries');
	if ('on' == $update_log) {
		relevanssi_update_log($q, sizeof($hits));
	}

	$make_excerpts = get_option('relevanssi_excerpts');

	if (is_paged()) {
		$wpSearch_low = ($query->query_vars['paged'] - 1) * $query->query_vars["posts_per_page"];
	}
	else {
		$wpSearch_low = 0;
	}

	if ($query->query_vars["posts_per_page"] == -1) {
		$wpSearch_high = sizeof($hits);
	}
	else {
		$wpSearch_high = $wpSearch_low + $query->query_vars["posts_per_page"] - 1;
	}
	if ($wpSearch_high > sizeof($hits)) $wpSearch_high = sizeof($hits);

	for ($i = $wpSearch_low; $i <= $wpSearch_high; $i++) {
		if (isset($hits[intval($i)])) {
			$post = $hits[intval($i)];
		}
		else {
			continue;
		}

		if ($post == NULL) {
			// apparently sometimes you can get a null object
			continue;
		}
		
		//Added by OdditY - Highlight Result Title too -> 
		if("on" == get_option('relevanssi_hilite_title')){
			if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
				$post->post_title = strip_tags(qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($post->post_title));
			}
			else {
				$post->post_title = strip_tags($post->post_title);
			}
			$highlight = get_option('relevanssi_highlight');
			if ("none" != $highlight) {
				if (!is_admin()) {
					$post->post_title = relevanssi_highlight_terms($post->post_title, $q);
				}
			}
		}
		// OdditY end <-			
		
		if ('on' == $make_excerpts) {			
			if ($cache) {
				$post->post_excerpt = relevanssi_fetch_excerpt($post->ID, $q);
				if ($post->post_excerpt == null) {
					$post->post_excerpt = relevanssi_do_excerpt($post, $q);
					relevanssi_store_excerpt($post->ID, $q, $post->post_excerpt);
				}
			}
			else {
				$post->post_excerpt = relevanssi_do_excerpt($post, $q);
			}
			
			if ('on' == get_option('relevanssi_show_matches')) {
				$post->post_excerpt .= relevanssi_show_matches($return, $post->ID);
			}
		}
		
		$post->relevance_score = round($return['scores'][$post->ID], 2);
		
		$posts[] = $post;
	}

	$query->posts = $posts;
	$query->post_count = count($posts);
	
	return $posts;
}

function relevanssi_limit_filter($query) {
	if (get_option('relevanssi_throttle', 'on') == 'on') {
		$limit = get_option('relevanssi_throttle_limit', 500);
		return $query . " ORDER BY tf DESC LIMIT $limit";
	}
	else {
		return $query;
	}
}

?>