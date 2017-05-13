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
function relevanssi_search($q, $tax_query = NULL, $relation = NULL, $post_query = array(), $meta_query = array(), $expost = NULL, $post_type = NULL, $operator = "AND", $search_blogs = NULL, $author = NULL, $orderby = NULL, $order = NULL) {
	global $wpdb, $relevanssi_variables;
	$relevanssi_table = $relevanssi_variables['relevanssi_table'];

	$values_to_filter = array(
		'q' => $q,
		'tax_query' => $tax_query,
		'relation' => $relation,
		'post_query' => $post_query,
		'meta_query' => $meta_query,
		'expost' => $expost,
		'post_type' => $post_type,
		'operator' => $operator,
		'search_blogs' => $search_blogs,
		'author' => $author,
		'orderby' => $orderby,
		'order' => $order,
		);
	$filtered_values = apply_filters( 'relevanssi_search_filters', $values_to_filter );
	$q               = $filtered_values['q'];
	$tax_query		 = $filtered_values['tax_query'];
	$post_query		 = $filtered_values['post_query'];
	$meta_query		 = $filtered_values['meta_query'];
	$relation		 = $filtered_values['relation'];
	$expost          = $filtered_values['expost'];
	$post_type       = $filtered_values['post_type'];
	$operator        = $filtered_values['operator'];
	$search_blogs    = $filtered_values['search_blogs'];
	$author	  	     = $filtered_values['author'];
	$orderby  	     = $filtered_values['orderby'];
	$order  	     = $filtered_values['order'];

	$hits = array();

	$o_tax_query = $tax_query;
	$o_relation = $relation;
	$o_post_query = $post_query;
	$o_meta_query = $meta_query;
	$o_expost = $expost;
	$o_post_type = $post_type;
	$o_author = $author;
	$o_operator = $operator;
	$o_search_blogs = $search_blogs;
	$o_orderby = $orderby;
	$o_order = $order;

	$query_restrictions = "";
	if (!isset($relation)) $relation = "or";
	$relation = strtolower($relation);
	$term_tax_id = array();
	$term_tax_ids = array();	
	$not_term_tax_ids = array();	
	$and_term_tax_ids = array();	
	if (is_array($tax_query)) {
		foreach ($tax_query as $row) {
			if ($row['field'] == 'id') {
				$id = $row['terms'];
				$term_id = $id;
				if (is_array($id)) {
					$id = implode(',', $id);
				}
				$term_tax_id = $wpdb->get_col(
					"SELECT term_taxonomy_id
					FROM $wpdb->term_taxonomy
					WHERE term_id IN ($id)");
			}
			if ($row['field'] == 'slug') {
				$slug = $row['terms'];
				if (is_array($slug)) {
					$slugs = array();
					$term_id = array();
					foreach ($slug as $t_slug) {
						$term = get_term_by('slug', $t_slug, $row['taxonomy']);
						$term_id[] = $term->term_id;
						$slugs[] = "'$t_slug'";
					}
					$slug = implode(',', $slugs);
				}
				else {
					$term = get_term_by('slug', $slug, $row['taxonomy']);
					$term_id = $term->term_id;
					$slug = "'$slug'";
				}
				$term_tax_id = $wpdb->get_col(
					"SELECT tt.term_taxonomy_id
					FROM $wpdb->terms AS t, $wpdb->term_taxonomy AS tt
					WHERE tt.term_id = t.term_id AND tt.taxonomy = '" . $row['taxonomy'] . "' AND t.slug IN ($slug)");
			}
			
			if (!isset($row['include_children']) || $row['include_children'] == true) {
				if (!is_array($term_id)) {
					$term_id = array($term_id);
				}
				foreach ($term_id as $t_id) {
					$kids = get_term_children($t_id, $row['taxonomy']);
					foreach ($kids as $kid) {
						$term = get_term_by('id', $kid, $row['taxonomy']);
						$term_tax_id[] = relevanssi_get_term_tax_id('id', $kid, $row['taxonomy']);
					}
				}
			}

			$term_tax_id = array_unique($term_tax_id);
			if (!empty($term_tax_id)) {
				$n = count($term_tax_id);
				$term_tax_id = implode(',', $term_tax_id);
				
				$tq_operator = 'IN';
				if (isset($row['operator'])) $tq_operator = strtoupper($row['operator']);
				if ($tq_operator != 'IN' && $tq_operator != 'NOT IN' && $tq_operator != 'AND') $tq_operator = 'IN';
				if ($relation == 'and') {
					if ($tq_operator == 'AND') {
						$query_restrictions .= " AND doc IN (
							SELECT ID FROM $wpdb->posts WHERE 1=1 
							AND (
								SELECT COUNT(1) 
								FROM $wpdb->_term_relationships 
								WHERE term_taxonomy_id IN ($term_tax_id) 
								AND object_id = $wpdb->posts.ID ) = $n
							)";
					}
					else {
						$query_restrictions .= " AND doc $tq_operator (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
						WHERE term_taxonomy_id IN ($term_tax_id))";
					}
				}
				else {
					if ($tq_operator == 'IN') $term_tax_ids[] = $term_tax_id;
					if ($tq_operator == 'NOT IN') $not_term_tax_ids[] = $term_tax_id;
					if ($tq_operator == 'AND') $and_term_tax_ids[] = $term_tax_id;
				}
			}
			else {
				global $wp_query;
				$wp_query->is_category = false;
			}
		}
		if ($relation == 'or') {
			$term_tax_ids = array_unique($term_tax_ids);
			if (count($term_tax_ids) > 0) {
				$term_tax_ids = implode(',', $term_tax_ids);
				$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			    	WHERE term_taxonomy_id IN ($term_tax_ids))";
			}
			if (count($not_term_tax_ids) > 0) {
				$not_term_tax_ids = implode(',', $not_term_tax_ids);
				$query_restrictions .= " AND doc NOT IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			    	WHERE term_taxonomy_id IN ($not_term_tax_ids))";
			}
			if (count($and_term_tax_ids) > 0) {
				$and_term_tax_ids = implode(',', $and_term_tax_ids);
				$n = count(explode(',', $and_term_tax_ids));
				$query_restrictions .= " AND doc IN (
					SELECT ID FROM $wpdb->posts WHERE 1=1 
					AND (
						SELECT COUNT(1) 
						FROM $wpdb->term_relationships 
						WHERE term_taxonomy_id IN ($and_term_tax_ids) 
						AND object_id = $wpdb->posts.ID ) = $n
					)";
			}
		}
	}
	
	if (is_array($post_query)) {
		if (!empty($post_query['in'])) {
			$posts = implode(',', $post_query['in']);
			$query_restrictions .= " AND doc IN ($posts)";
		}
		if (!empty($post_query['not in'])) {
			$posts = implode(',', $post_query['not in']);
			$query_restrictions .= " AND doc NOT IN ($posts)";
		}
	}

	if (is_array($meta_query)) {
		foreach ($meta_query as $meta) {
			if (!empty($meta['key'])) {
				$key = "meta_key = '" . $meta['key'] . "'";
			}
			else {
				$key = '';
			}
			
			isset($meta['compare']) ? $compare = strtoupper($meta['compare']) : $compare = '=';
			
			if (isset($meta['type'])) {
				if (strtoupper($meta['type']) == 'NUMERIC') $meta['type'] = "SIGNED";
				$meta_value = "CAST(meta_value AS " . $meta['type'] . ")";
			}
			else {
				$meta_value = 'meta_value';
			}

			if ($compare == 'BETWEEN' || $compare == 'NOT BETWEEN') {
				if (!is_array($meta['value'])) continue;
				if (count($meta['value']) < 2) continue;
				$compare == 'BETWEEN' ? $compare = "IN" : $compare = "NOT IN";
				$low_value = $meta['value'][0];
				$high_value = $meta['value'][1];
				!empty($key) ? $and = " AND " : $and = "";
				$query_restrictions .= " AND doc $compare (
					SELECT DISTINCT(post_id) FROM $wpdb->postmeta
					WHERE $key $and $meta_value BETWEEN $low_value AND $high_value)";
			}
			else if ($compare == 'EXISTS' || $compare == 'NOT EXISTS') {
				$compare == 'EXISTS' ? $compare = "IN" : $compare = "NOT IN";
				$query_restrictions .= " AND doc $compare (
					SELECT DISTINCT(post_id) FROM $wpdb->postmeta
					WHERE $key)";
			}
			else if ($compare == 'IN' || $compare == 'NOT IN') {
				if (!is_array($meta['value'])) continue;
				$values = array();
				foreach ($meta['value'] as $value) {
					$values[] = "'$value'";
				}
				$values = implode(',', $values);
				!empty($key) ? $and = " AND " : $and = "";
				$query_restrictions .= " AND doc IN (
					SELECT DISTINCT(post_id) FROM $wpdb->postmeta
					WHERE $key $and $meta_value $compare ($values))";
			}
			else {
				isset($meta['value']) ? $value = " $meta_value " . $meta['compare'] . " '" . $meta['value'] . "' " : $value = '';
				(!empty($key) && !empty($value)) ? $and = " AND " : $and = "";
				if (empty($key) && empty($and) && empty($value)) {
					// do nothing
				}
				else {
					$query_restrictions .= " AND doc IN (
						SELECT DISTINCT(post_id) FROM $wpdb->postmeta
						WHERE $key $and $value)";
				}
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

	$remove_stopwords = true;
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

	if ($expost) { //added by OdditY
		$query_restrictions .= $postex;
	}

	if (function_exists('relevanssi_negatives_positives')) {	
		$query_restrictions .= relevanssi_negatives_positives($negative_terms, $positive_terms, $relevanssi_table);
	}

	if (!empty($author)) {
		$author_in = array();
		$author_not_in = array();
		foreach ($author as $id) {
			if ($id >= 0) {
				$author_in[] = $id;
			}
			else {
				$author_not_in[] = abs($id);
			}
		}
		if (count($author_in) > 0) {
			$authors = implode(',', $author_in);
			$query_restrictions .= " AND doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
			    WHERE post_author IN ($authors))";
		}
		if (count($author_not_in) > 0) {
			$authors = implode(',', $author_not_in);
			$query_restrictions .= " AND doc NOT IN (SELECT DISTINCT(ID) FROM $wpdb->posts
			    WHERE post_author IN ($authors))";
		}
	}
	
	if ($post_type) {
		// the -1 is there to get user profiles and category pages
		$query_restrictions .= " AND ((doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
			WHERE post_type IN ($post_type))) OR (doc = -1))";
	}
	
	if ($phrases) {
		$query_restrictions .= " AND doc IN ($phrases)";
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
		$o_term_cond = apply_filters('relevanssi_fuzzy_query', "(term LIKE '#term#%' OR term_reverse LIKE CONCAT(REVERSE('#term#'), '%')) ");
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

	$title_boost = floatval(get_option('relevanssi_title_boost'));
	$link_boost = floatval(get_option('relevanssi_link_boost'));
	$comment_boost = floatval(get_option('relevanssi_comment_boost'));

	$include_these_posts = array();

	do {
		foreach ($terms as $term) {
			if (strlen($term) < $min_length) continue;
			$term = $wpdb->escape(like_escape($term));
			$term_cond = str_replace('#term#', $term, $o_term_cond);		
			
			!empty($post_type_weights['post_tag']) ? $tag = $post_type_weights['post_tag'] : $tag = $relevanssi_variables['post_type_weight_defaults']['post_tag'];
			!empty($post_type_weights['category']) ? $cat = $post_type_weights['category'] : $cat = $relevanssi_variables['post_type_weight_defaults']['category'];

			$query = "SELECT *, title * $title_boost + content + comment * $comment_boost + tag * $tag + link * $link_boost + author + category * $cat + excerpt + taxonomy + customfield + mysqlcolumn AS tf 
					  FROM $relevanssi_table WHERE $term_cond $query_restrictions";
			$query = apply_filters('relevanssi_query_filter', $query);

			$matches = $wpdb->get_results($query);
			
			if (count($matches) < 1) {
				continue;
			}
			else {
				$no_matches = false;
				if (count($include_these_posts) > 0) {
					$post_ids_to_add = implode(',', array_keys($include_these_posts));
					$query = "SELECT *, title * $title_boost + content + comment * $comment_boost + tag * $tag + link * $link_boost + author + category * $cat + excerpt + taxonomy + customfield + mysqlcolumn AS tf 
						  FROM $relevanssi_table WHERE doc IN ($post_ids_to_add) AND $term_cond";
					$matches_to_add = $wpdb->get_results($query);
					$matches = array_merge($matches, $matches_to_add);
				}
			}
			
			relevanssi_populate_array($matches);
			global $relevanssi_post_types;

			$total_hits += count($matches);
	
			$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE $term_cond $query_restrictions";
			$query = apply_filters('relevanssi_df_query_filter', $query);
	
			$df = $wpdb->get_var($query);
	
			if ($df < 1 && "sometimes" == $fuzzy) {
				$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table
					WHERE (term LIKE '$term%' OR term_reverse LIKE CONCAT(REVERSE('$term), %')) $query_restrictions";
				$query = apply_filters('relevanssi_df_query_filter', $query);
				$df = $wpdb->get_var($query);
			}
			
			$idf = log($D + 1 / (1 + $df));
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
							if ($tax == 'post_tag') {
								$match->tag = $count;
							}
							if (empty($post_type_weights[$tax])) {
								$match->taxonomy_score += $count * 1;
							}
							else {
								$match->taxonomy_score += $count * $post_type_weights[$tax];
							}
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
				$link_matches[$match->doc] = $match->link;
				$tag_matches[$match->doc] = $match->tag;
				$comment_matches[$match->doc] = $match->comment;
	
				isset($relevanssi_post_types[$match->doc]) ? $type = $relevanssi_post_types[$match->doc] : $type = null;
				if (!empty($post_type_weights[$type])) {
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
					$include_these_posts[$match->doc] = true;
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
			
			$hits[intval($i)] = relevanssi_get_post($doc);
			$hits[intval($i)]->relevance_score = round($weight, 2);
			$i++;
		}
	}

	if (count($hits) < 1) {
		if ($operator == "AND" AND get_option('relevanssi_disable_or_fallback') != 'on') {
			$return = relevanssi_search($q, $o_tax_query, $o_relation,
				$o_post_query, $o_meta_query,
				$o_expost, $o_post_type,
				"OR", $o_search_blogs, $o_author);
			extract($return);
		}
	}

	global $wp;	
	$default_order = get_option('relevanssi_default_orderby', 'relevance');
	if (empty($orderby)) $orderby = $default_order;
	// the sorting function checks for non-existing keys, cannot whitelist here

	if (empty($order)) $order = 'desc';
	$order = strtolower($order);
	$order_accepted_values = array('asc', 'desc');
	if (!in_array($order, $order_accepted_values)) $order = 'desc';
	
	if ($orderby != 'relevance')
		relevanssi_object_sort($hits, $orderby, $order);

	$return = array('hits' => $hits, 'body_matches' => $body_matches, 'title_matches' => $title_matches,
		'tag_matches' => $tag_matches, 'comment_matches' => $comment_matches, 'scores' => $scores,
		'term_hits' => $term_hits, 'query' => $q, 'link_matches' => $link_matches);

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
		if (isset($query->query_vars["post_types"]) && $query->query_vars["post_types"] != 'any') {
			$post_type = $query->query_vars["post_types"];
		}

		if (function_exists('relevanssi_search_multi')) {
			$return = relevanssi_search_multi($q, $search_blogs, $post_type);
		}
	}
	else {
		$tax_query = array();
		$tax_query_relation = apply_filters('relevanssi_default_tax_query_relation', 'OR');
		if (isset($query->query_vars['tax_query'])) {
			foreach ($query->query_vars['tax_query'] as $type => $item) {
				if (is_string($type) && $type == 'relation') {
					$tax_query_relation = $item;
				}
				else {
					$tax_query[] = $item;
				}
			}
		}
		else {
			$cat = false;
			if (isset($query->query_vars["cats"])) {
				$cat = $query->query_vars["cats"];
			}
			if (empty($cat)) {
				$cat = get_option('relevanssi_cat');
				if (0 == $cat) {
					$cat = false;
				}
			}
			if ($cat) {
				$cat = explode(',', $cat);
				$tax_query[] = array('taxonomy' => 'category', 'field' => 'id', 'terms' => $cat);
			}
			if (!empty($query->query_vars['category_name']) && empty($query->query_vars['category__in'])) {
				$cat = explode(',', $query->query_vars['category_name']);
				$tax_query[] = array('taxonomy' => 'category', 'field' => 'slug', 'terms' => $cat);
			}
			if (!empty($query->query_vars['category__in'])) {
				$tax_query[] = array('taxonomy' => 'category', 'field' => 'id', 'terms' => $query->query_vars['category__in']);
			}
			if (!empty($query->query_vars['category__not_in'])) {
				$tax_query[] = array('taxonomy' => 'category', 'field' => 'id', 'terms' => $query->query_vars['category__not_in'], 'operator' => 'NOT IN');
			}
			if (!empty($query->query_vars['category__and'])) {
				$tax_query[] = array('taxonomy' => 'category', 'field' => 'id', 'terms' => $query->query_vars['category__and'], 'operator' => 'AND', 'include_children' => false);
			}
			$excat = get_option('relevanssi_excat');
			if (isset($excat) && $excat != 0) {
				$tax_query[] = array('taxonomy' => 'category', 'field' => 'id', 'terms' => $excat, 'operator' => 'NOT IN');
			}

			$tag = false;
			if (isset($query->query_vars["tags"])) {
				$tag = $query->query_vars["tags"];
			}
			if ($tag) {
				if (strpos($tag, '+') !== false) {
					$tag = explode('+', $tag);
					$operator = 'and';
				}
				else {
					$tag = explode(',', $tag);
					$operator = 'or';
				}
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'id', 'terms' => $tag, 'operator' => $operator);
			}
			if (!empty($query->query_vars['tag_id'])) {
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'id', 'terms' => $query->query_vars['tag_id']);
			}
			if (!empty($query->query_vars['tag__in'])) {
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'id', 'terms' => $query->query_vars['tag__in']);
			}
			if (!empty($query->query_vars['tag__not_in'])) {
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'id', 'terms' => $query->query_vars['tag__not_in'], 'operator' => 'NOT IN');
			}
			if (!empty($query->query_vars['tag__and'])) {
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'id', 'terms' => $query->query_vars['tag__and'], 'operator' => 'AND');
			}
			if (!empty($query->query_vars['tag__not_in'])) {
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'id', 'terms' => $query->query_vars['tag__not_in'], 'operator' => 'NOT IN');
			}
			if (!empty($query->query_vars['tag_slug__in'])) {
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'slug', 'terms' => $query->query_vars['tag_slug__in']);
			}
			if (!empty($query->query_vars['tag_slug__not_in'])) {
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'slug', 'terms' => $query->query_vars['tag_slug__not_in'], 'operator' => 'NOT IN');
			}
			if (!empty($query->query_vars['tag_slug__and'])) {
				$tax_query[] = array('taxonomy' => 'post_tag', 'field' => 'slug', 'terms' => $query->query_vars['tag_slug__and'], 'operator' => 'AND');
			}

			if (isset($query->query_vars["taxonomy"])) {
				if (function_exists('relevanssi_process_taxonomies')) {
					$tax_query = relevanssi_process_taxonomies($query->query_vars["taxonomy"], $query->query_vars["term"], $tax_query);
				}
				else {
					if (!empty($query->query_vars["term"])) $term = $query->query_vars["term"];
				
					$tax_query[] = array('taxonomy' => $query->query_vars["taxonomy"], 'field' => 'slug', 'terms' => $term);
				}
			}
		}

		$author = false;
		if (isset($query->query_vars["author"])) {
			$author = explode(',', $query->query_vars["author"]);
		}
		if (!empty($query->query_vars["author_name"])) {
			$author_object = get_user_by('slug', $query->query_vars["author_name"]);
			$author[] = $author_object->ID;
		}
		
		$post_query = array();
		if (!empty($query->query_vars['post__in'])) {
			$post_query = array('in' => $query->query_vars['post__in']);
		}
		if (!empty($query->query_vars['post__not_in'])) {
			$post_query = array('not in' => $query->query_vars['post__not_in']);
		}

		$meta_query = array();
		if (!empty($query->query_vars["meta_query"])) {
			$meta_query = $query->query_vars["meta_query"];
		}
		if (isset($query->query_vars["customfield_key"])) {
			isset($query->query_vars["customfield_value"]) ? $value = $query->query_vars["customfield_value"] : $value = null;
			$meta_query[] = array('key' => $query->query_vars["customfield_key"], 'value' => $value, 'compare' => '=');
		}
		if (!empty($query->query_vars["meta_key"]) ||
			!empty($query->query_vars["meta_value"]) ||
			!empty($query->query_vars["meta_value_num"])) {
			$value = null;
			if (!empty($query->query_vars["meta_value"])) $value = $query->query_vars["meta_value"];
			if (!empty($query->query_vars["meta_value_num"])) $value = $query->query_vars["meta_value_num"];
			!empty($query->query_vars["meta_compare"]) ? $compare = $query->query_vars["meta_compare"] : $compare = '=';
			$meta_query[] = array('key' => $query->query_vars["meta_key"], 'value' => $value, 'compare' => $compare);
		}

	
		$search_blogs = false;
		if (isset($query->query_vars["search_blogs"])) {
			$search_blogs = $query->query_vars["search_blogs"];
		}
	
		$post_type = false;
		if (isset($query->query_vars["post_type"]) && $query->query_vars["post_type"] != 'any') {
			$post_type = $query->query_vars["post_type"];
		}
		if (isset($query->query_vars["post_types"]) && $query->query_vars["post_types"] != 'any') {
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
		
		isset($query->query_vars['orderby']) ? $orderby = $query->query_vars['orderby'] : $orderby = null;
		isset($query->query_vars['order']) ? $order = $query->query_vars['order'] : $order = null;
		
		// Add synonyms
		// This is done here so the new terms will get highlighting
		if ("OR" == $operator) {
			// Synonyms are only used in OR queries
			$synonym_data = get_option('relevanssi_synonyms');
			if ($synonym_data) {
				$synonyms = array();
				if (function_exists('mb_strtolower')) {
					$synonym_data = mb_strtolower($synonym_data);
				}
				else {
					$synonym_data = strtolower($synonym_data);
				}
				$pairs = explode(";", $synonym_data);
				foreach ($pairs as $pair) {
					$parts = explode("=", $pair);
					$key = strval(trim($parts[0]));
					$value = trim($parts[1]);
					$synonyms[$key][$value] = true;
				}
				if (count($synonyms) > 0) {
					$new_terms = array();
					$terms = array_keys(relevanssi_tokenize($q, false)); // remove stopwords is false here
					$terms[] = $q;
					foreach ($terms as $term) {
						if (in_array(strval($term), array_keys($synonyms))) {		// strval, otherwise numbers cause problems
							if (isset($synonyms[strval($term)])) {		// necessary, otherwise terms like "02" can cause problems
								$new_terms = array_merge($new_terms, array_keys($synonyms[strval($term)]));
							}
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
			$params = md5(serialize(array($q, $tax_query, $tax_query_relation, $post_query, $meta_query, $expids, $post_type, $operator, $search_blogs, $author, $orderby, $order)));
			$return = relevanssi_fetch_hits($params);
			if (!$return) {
				$return = relevanssi_search($q, $tax_query, $tax_query_relation, $post_query, $meta_query, $expids, $post_type, $operator, $search_blogs, $author, $orderby, $order);
				$return_ser = serialize($return);
				relevanssi_store_hits($params, $return_ser);
			}
		}
		else {
			$return = relevanssi_search($q,
										$tax_query,
										$tax_query_relation, 
										$post_query,
										$meta_query, 
										$expids,
										$post_type,
										$operator,
										$search_blogs,
										$author,
										$orderby,
										$order);
		}
	}

	isset($return['hits']) ? $hits = $return['hits'] : $hits = array();
	isset($return['query']) ? $q = $return['query'] : $q = "";

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

	if ($query->query_vars['paged'] > 0) {
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

	if (isset($query->query_vars['offset']) && $query->query_vars['offset'] > 0) {
		$wpSearch_high += $query->query_vars['offset'];
		$wpSearch_low += $query->query_vars['offset'];
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
		
		if (isset($return['scores'][$post->ID])) $post->relevance_score = round($return['scores'][$post->ID], 2);
		
		$posts[] = $post;
	}

	$query->posts = $posts;
	$query->post_count = count($posts);
	
	return $posts;
}

function relevanssi_limit_filter($query) {
	if (get_option('relevanssi_throttle', 'on') == 'on') {
		$limit = get_option('relevanssi_throttle_limit', 500);
		if (!is_numeric($limit)) $limit = 500; 		// Backup, if the option is set to something useless.
		if ($limit < 0) $limit = 500;
		return $query . " ORDER BY tf DESC LIMIT $limit";
	}
	else {
		return $query;
	}
}

?>