<?php
/*
Plugin Name: Relevanssi
Plugin URI: http://www.mikkosaari.fi/relevanssi/
Description: This plugin replaces WordPress search with a relevance-sorting search.
Version: 1.7.3
Author: Mikko Saari
Author URI: http://www.mikkosaari.fi/
*/

/*  Copyright 2009 Mikko Saari  (email: mikko@mikkosaari.fi)

    This file is part of Relevanssi, a search plugin for WordPress.

    Relevanssi is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Relevanssi is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Relevanssi.  If not, see <http://www.gnu.org/licenses/>.
*/

register_activation_hook(__FILE__,'relevanssi_install');
add_action('admin_menu', 'relevanssi_menu');
add_filter('the_posts', 'relevanssi_query');
add_filter('post_limits', 'relevanssi_getLimit');
add_action('edit_post', 'relevanssi_edit');
add_action('edit_page', 'relevanssi_edit');
add_action('delete_post', 'relevanssi_delete');
add_action('publish_post', 'relevanssi_publish');
add_action('publish_page', 'relevanssi_publish');
add_action('future_publish_post', 'relevanssi_publish');
add_action('comment_post', 'relevanssi_comment_index'); 	//added by OdditY
add_action('edit_comment', 'relevanssi_comment_edit'); 		//added by OdditY 
add_action('delete_comment', 'relevanssi_comment_remove'); 	//added by OdditY
add_action('init', 'relevanssi_init');

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'relevanssi', 'wp-content/plugins/' . $plugin_dir, $plugin_dir);

global $wpSearch_low;
global $wpSearch_high;
global $relevanssi_table;
global $stopword_table;
global $log_table;
global $stopword_list;
global $title_boost_default;
global $tag_boost_default;
global $comment_boost_default;

$wpSearch_low = 0;
$wpSearch_high = 0;
$relevanssi_table = $wpdb->prefix . "relevanssi";
$stopword_table = $wpdb->prefix . "relevanssi_stopwords";
$log_table = $wpdb->prefix . "relevanssi_log";
$title_boost_default = 5;
$tag_boost_default = 0.75;
$comment_boost_default = 0.75;

function relevanssi_menu() {
	add_options_page(
		'Relevanssi',
		'Relevanssi',
		'manage_options',
		__FILE__,
		'relevanssi_options'
	);
}

function relevanssi_init() {
	if (!get_option('relevanssi_indexed') && !$_POST['index']) {
		function relevanssi_warning() {
			echo "<div id='relevanssi-warning' class='updated fade'><p><strong>"
			   . sprintf(__('Relevanssi needs attention: Remember to build the index (you can do it at <a href="%1$s">the settings page</a>), otherwise searching won\'t work.'), "options-general.php?page=relevanssi/relevanssi.php")
			   . "</strong>"."</p></div>";
		}
		add_action('admin_notices', 'relevanssi_warning');
		return;
	}
}

function relevanssi_edit($post) {
	// Check if the post is public
	global $wpdb;
	$post_status = $wpdb->get_var("SELECT post_status FROM $wpdb->posts WHERE ID=$post");
	if ($post_status != 'publish') {
		// The post isn't public anymore, remove it from index
		relevanssi_remove_doc($post);
	}
	// No need to do anything else, because if the post is public, it'll trigger publish_post.
}

function relevanssi_delete($post) {
	relevanssi_remove_doc($post);
}

function relevanssi_publish($post) {
	relevanssi_add($post);
}

function relevanssi_add($post) {
	relevanssi_index_doc($post, true);
}

function relevanssi_install() {
	global $wpdb, $relevanssi_table, $stopword_table, $title_boost_default,
	$tag_boost_default, $comment_boost_default;
	
	add_option('relevanssi_title_boost', $title_boost_default);
	add_option('relevanssi_tag_boost', $tag_boost_default);
	add_option('relevanssi_comment_boost', $comment_boost_default);
	add_option('relevanssi_admin_search', 'off');
	add_option('relevanssi_highlight', 'strong');
	add_option('relevanssi_txt_col', '#ff0000');
	add_option('relevanssi_bg_col', '#ffaf75');
	add_option('relevanssi_css', 'text-decoration: underline; text-color: #ff0000');
	add_option('relevanssi_class', 'relevanssi-query-term');
	add_option('relevanssi_excerpts', 'on');
	add_option('relevanssi_excerpt_length', '450');
	add_option('relevanssi_excerpt_type', 'chars');
	add_option('relevanssi_log_queries', 'off');
	add_option('relevanssi_cat', '0');
	add_option('relevanssi_excat', '0');
	add_option('relevanssi_index_type', 'both');
	add_option('relevanssi_index_fields', '');
	add_option('relevanssi_exclude_posts', ''); 		//added by OdditY
	add_option('relevanssi_include_tags', 'on');		//added by OdditY	
	add_option('relevanssi_hilite_title', ''); 			//added by OdditY	
	add_option('relevanssi_index_comments', 'none');	//added by OdditY
	add_option('relevanssi_include_cats', '');
	add_option('relevanssi_show_matches', '');
	add_option('relevanssi_show_matches_txt', '(Search hits: %body% in body, %title% in title, %tags% in tags, %comments% in comments. Score: %score%)');
	add_option('relevanssi_fuzzy', 'sometimes');
	add_option('relevanssi_indexed', '');
	add_option('relevanssi_expand_shortcodes', 'on');
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$relevanssi_table = $wpdb->prefix . "relevanssi";
	$stopword_table = $wpdb->prefix . "relevanssi_stopwords";
	$log_table = $wpdb->prefix . "relevanssi_log";
	
	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_table'") != $relevanssi_table) {
		$sql = "CREATE TABLE " . $relevanssi_table . " (id mediumint(9) NOT NULL AUTO_INCREMENT, "
		. "doc bigint(20) NOT NULL, "
		. "term varchar(50) NOT NULL, "
		. "tf mediumint(9) NOT NULL, "
		. "title tinyint(1) NOT NULL, "
	    . "UNIQUE KEY id (id));";

		dbDelta($sql);
		
		$sql = "ALTER TABLE $relevanssi_table ADD INDEX (doc)";
		$wpdb->query($sql);

		$sql = "ALTER TABLE $relevanssi_table ADD INDEX (term)";
		$wpdb->query($sql);
	}


	if($wpdb->get_var("SHOW TABLES LIKE '$stopword_table'") != $stopword_table) {
		$sql = "CREATE TABLE " . $stopword_table . " (stopword varchar(50) NOT NULL, "
	    . "UNIQUE KEY stopword (stopword));";

		dbDelta($sql);
	}
	
	if ($wpdb->get_var("SELECT COUNT(*) FROM $stopword_table WHERE 1") < 1) {
		relevanssi_populate_stopwords();
	}


	if($wpdb->get_var("SHOW TABLES LIKE '$log_table'") != $log_table) {
		$sql = "CREATE TABLE " . $log_table . " (id mediumint(9) NOT NULL AUTO_INCREMENT, "
		. "query varchar(200) NOT NULL, "
		. "hits mediumint(9) NOT NULL, "
		. "time timestamp NOT NULL, "
	    . "UNIQUE KEY id (id));";

		dbDelta($sql);
	}
}

if (function_exists('register_uninstall_hook')) {
	register_uninstall_hook(__FILE__, 'relevanssi_uninstall');
	// this doesn't seem to work
}

function relevanssi_uninstall() {
	global $wpdb, $relevanssi_table, $log_table, $stopword_table;

	delete_option('relevanssi_title_boost');
	delete_option('relevanssi_tag_boost');
	delete_option('relevanssi_comment_boost');
	delete_option('relevanssi_admin_search');
	delete_option('relevanssi_highlight');
	delete_option('relevanssi_txt_col');
	delete_option('relevanssi_bg_col');
	delete_option('relevanssi_css');
	delete_option('relevanssi_excerpts');
	delete_option('relevanssi_excerpt_length');
	delete_option('relevanssi_excerpt_type');
	delete_option('relevanssi_log_queries');
	delete_option('relevanssi_excat');
	delete_option('relevanssi_cat');
	delete_option('relevanssi_index_type');
	delete_option('revelanssi_index_fields');
	delete_option('relevanssi_exclude_posts'); 	//added by OdditY
	delete_option('relevanssi_include_tags'); 	//added by OdditY	
	delete_option('relevanssi_hilite_title'); 	//added by OdditY 
	delete_option('relevanssi_index_comments');	//added by OdditY
	delete_option('relevanssi_include_cats');
	delete_option('relevanssi_show_matches');
	delete_option('relevanssi_show_matches_text');
	delete_option('relevanssi_fuzzy');
	delete_option('relevanssi_indexed');
	delete_option('relevanssi_expand_shortcodes');
	
	$sql = "DROP TABLE $stopword_table";
	$wpdb->query($sql);

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_table'") == $relevanssi_table) {
		$sql = "DROP TABLE $relevanssi_table";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$log_table'") == $log_table) {
		$sql = "DROP TABLE $log_table";
		$wpdb->query($sql);
	}
	
	echo '<div id="message" class="update fade"><p>' . __("Data wiped clean, you can now delete the plugin.", "relevanssi") . '</p></div>';
}

//Added by OdditY -> 
function relevanssi_comment_edit($comID) {
	relevanssi_comment_index($comID,$action="update");
}

function relevanssi_comment_remove($comID) {
	relevanssi_comment_index($comID,$action="remove");
}

function relevanssi_comment_index($comID,$action="add") {
	global $wpdb;
	$comtype = get_option("relevanssi_index_comments");
	switch ($comtype) {
		case "all": 
			// all (incl. customs, track-&pingbacks)
			break;
		case "normal": 
			// normal (excl. customs, track-&pingbacks)
			$restriction=" AND comment_type='' ";
			break;
		default:
			// none (don't index)
			return ;
	}
	switch ($action) {
		case "update": 
			//(update) comment status changed:
			$cpostID = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID='$comID'".$restriction);
			break;
		case "remove": 
			//(remove) approved comment will be deleted (if not approved, its not in index):
			$cpostID = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID='$comID' AND comment_approved='1'".$restriction);
			if($cpostID!=NULL) {
				//empty comment_content & reindex, then let WP delete the empty comment
				$wpdb->query("UPDATE $wpdb->comments SET comment_content='' WHERE comment_ID='$comID'");
			}				
			break;
		default:
			// (add) new comment:
			$cpostID = $wpdb->get_var("SELECT comment_post_ID FROM $wpdb->comments WHERE comment_ID='$comID' AND comment_approved='1'".$restriction);
			break;
	}
	if($cpostID!=NULL) relevanssi_publish($cpostID);	
}
//Added by OdditY END <-


function relevanssi_populate_stopwords() {
	global $wpdb, $stopword_table;

	include('stopwords');

	if (is_array($stopwords) && count($stopwords) > 0) {
		foreach ($stopwords as $word) {
			$q = $wpdb->prepare("INSERT IGNORE INTO $stopword_table (stopword) VALUES (%s)", trim($word));
			$wpdb->query($q);
		}
	}
}

function relevanssi_fetch_stopwords() {
	global $wpdb, $stopword_list, $stopword_table;
	
	if (count($stopword_list) < 1) {
		$results = $wpdb->get_results("SELECT stopword FROM $stopword_table");
		foreach ($results as $word) {
			$stopword_list[] = $word->stopword;
		}
	}
	
	return $stopword_list;
}

function relevanssi_query($posts) {
	$admin_search = get_option('relevanssi_admin_search');
	($admin_search == 'on') ? $admin_search = true : $admin_search = false;
	
	$search_ok = true; 							// we will search!
	if (is_search() && is_admin()) {
		$search_ok = false; 					// but if this is an admin search, reconsider
		if ($admin_search) $search_ok = true; 	// yes, we can search!
	}
	
	global $relevanssi_active;

	if (is_search() && $search_ok && !$relevanssi_active) {
		// this all is basically lifted from Kenny Katzgrau's wpSearch
		// thanks, Kenny!
		global $wp;
		global $wpSearch_low;
		global $wpSearch_high;
		global $wp_query;

		$relevanssi_active = true;

		$posts = array();

		$q = stripslashes($wp->query_vars["s"]);
		$cat = $wp->query_vars["cat"];
		if (!$cat) {
			$cat = get_option('relevanssi_cat');
			if (0 == $cat) {
				$cat = false;
			}
		}

		if (!$excat) {
			$excat = get_option('relevanssi_excat');
			if (0 == $excat) {
				$excat = false;
			}
		}

		$expids = get_option("relevanssi_exclude_posts");

		if (is_admin()) {
			// in admin search, search everything
			$cat = null;
			$excat = null;
			$expids = null;
		}

		$return = relevanssi_search($q, $cat, $excat, $expids);
		$hits = $return[0];
		$wp_query->found_posts = sizeof($hits);
		$wp_query->max_num_pages = ceil(sizeof($hits) / $wp_query->query_vars["posts_per_page"]);

		$update_log = get_option('relevanssi_log_queries');
		if ('on' == $update_log) {
			relevanssi_update_log($q, sizeof($hits));
		}
		
		if ($wpSearch_high > sizeof($hits)) $wpSearch_high = sizeof($hits) - 1;
		
		$make_excerpts = get_option('relevanssi_excerpts');
		
		for ($i = $wpSearch_low; $i <= $wpSearch_high; $i++) {
			$hit = $hits[intval($i)];
			$post = get_post($hit, OBJECT);
			
			//Added by OdditY - Highlight Result Title too -> 
			if("on" == get_option('relevanssi_hilite_title')){
				$post->post_title = strip_tags($post->post_title);
				if ("none" != $highlight) {
					$t = explode(" ", $q);				
					$post->post_title = relevanssi_highlight_terms($post->post_title, $t);
				}
			}
			// OdditY end <-			
			
			if ('on' == $make_excerpts) {			
				$post->post_excerpt = relevanssi_do_excerpt($post, $q);
				if ('on' == get_option('relevanssi_show_matches')) {
					$post->post_excerpt .= relevanssi_show_matches($return, $hit);
				}
			}
			$posts[] = $post;
		}
	}
	
	return $posts;
}

function relevanssi_show_matches($data, $hit) {
	$body = $data[1][$hit];
	$title = $data[2][$hit];
	$tag = $data[3][$hit];
	$comment = $data[4][$hit];
	$score = round($data[5][$hit], 2);
	
	$text = get_option('relevanssi_show_matches_text');
	$replace_these = array("%body%", "%title%", "%tags%", "%comments%", "%score%");
	$replacements = array($body, $title, $tag, $comment, $score);
	
	$result = " " . str_replace($replace_these, $replacements, $text);
	
	return $result;
}

function relevanssi_update_log($query, $hits) {
	global $wpdb, $log_table;
	
	$q = $wpdb->prepare("INSERT INTO $log_table (query, hits) VALUES (%s, %d)", $query, intval($hits));
	$wpdb->query($q);
}

// This function is from Kenny Katzgrau
function relevanssi_getLimit($limit) {
	global $wpSearch_low;
	global $wpSearch_high;

	if(is_search()) {
		$temp 			= str_replace("LIMIT", "", $limit);
		$temp 			= split(",", $temp);
		$wpSearch_low 	= intval($temp[0]);
		$wpSearch_high 	= intval($wpSearch_low + intval($temp[1]) - 1);
	}
	
	return $limit;
}

// This is my own magic working.
function relevanssi_search($q, $cat = NULL, $excat = NULL, $expost = NULL) {
	global $relevanssi_table, $wpdb;

	$hits = array();

	if ("custom" == $cat) {
		$custom_field = "custom";
		$post_ids = array();
		$results = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='$custom_field'");
		foreach ($results as $row) {
			$post_ids[] = $row->post_id;
		}
		$custom_cat = implode(",", $post_ids);
		$cat = "";
	}
	else if ($cat) {
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
			}
		}
		
		$cat = implode(",", $inc_term_tax_ids);
		$excat_temp = implode(",", $ex_term_tax_ids);
	}

	if ($excat) {
		$excats = explode(",", $excat);
		$term_tax_ids = array();
		foreach ($excats as $t_cat) {
			$t_cat = $wpdb->escape($t_cat);
			$term_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy
				WHERE term_id=$t_cat");
			if ($term_tax_id) {
				$term_tax_ids[] = $term_tax_id;
			}
		}
		
		$excat = implode(",", $term_tax_ids);
	}

	if ($excat_temp) {
		$excat .= $excat_temp;
	}

	//Added by OdditY:
	//Exclude Post_IDs (Pages) for non-admin search ->
	if ($expost) {
		if ($expost != "") {
			$aexpids = explode(",",$expost);
			foreach ($aexpids as $exid){
				$postex .= " AND doc !='$exid'";
			}
		}	
	}
	// <- OdditY End

	$remove_stopwords = false;
	$phrases = relevanssi_recognize_phrases($q);
	
	$terms = relevanssi_tokenize($q, $remove_stopwords);
	if (count($terms) < 1) {
		// Tokenizer killed all the search terms.
		return $hits;
	}
	$terms = array_keys($terms); // don't care about tf in query

	$D = $wpdb->get_var("SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table");
	
	$total_hits = 0;
		
	$title_matches = array();
	$tag_matches = array();
	$comment_matches = array();
	$body_matches = array();
	$scores = array();

	$fuzzy = get_option('relevanssi_fuzzy');

	foreach ($terms as $term) {
		$term = $wpdb->escape(like_escape($term));
		
		if ("always" == $fuzzy) {
			$term_cond = "(term LIKE '%$term' OR term LIKE '$term%') ";
		}
		else {
			$term_cond = " term = '$term' ";
		}
		
		$query = "SELECT doc, term, tf, title FROM $relevanssi_table WHERE $term_cond";

		if ($expost) { //added by OdditY
			$query .= $postex;
		}

		if ($cat) {
			$query .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			    WHERE term_taxonomy_id IN ($cat))";
		}

		if ($excat) {
			$query .= " AND doc NOT IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			    WHERE term_taxonomy_id IN ($excat))";
		}

		if ($phrases) {
			$query .= " AND doc IN ($phrases)";
		}

		if ($custom_cat) {
			$query .= " AND doc IN ($custom_cat)";
		}

		$matches = $wpdb->get_results($query);
		if (count($matches) < 1 && "sometimes" == $fuzzy) {
			$query = "SELECT doc, term, tf, title FROM $relevanssi_table
			WHERE (term LIKE '$term%' OR term LIKE '%$term')";
			
			if ($expost) { //added by OdditY
				$query .= $postex;
			}
			
			if ($cat) {
				$query .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
				    WHERE term_taxonomy_id IN ($cat))";
			}

			if ($excat) {
				$query .= " AND doc NOT IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
				    WHERE term_taxonomy_id IN ($excat))";
			}

			if ($phrases) {
				$query .= " AND doc IN ($phrases)";
			}

			if ($custom_cat) {
				$query .= " AND doc IN ($custom_cat)";
			}
			
			$matches = $wpdb->get_results($query);
		}
		
		$total_hits += count($matches);

		$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE $term_cond";
		
		if ($expost) { //added by OdditY
			$query .= $postex;
		}
		
		
		if ($cat) {
			$query .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			    WHERE term_taxonomy_id IN ($cat))";
		}
		
		if ($excat) {
			$query .= " AND doc NOT IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			    WHERE term_taxonomy_id IN ($excat))";
		}

		if ($phrases) {
			$query .= " AND doc IN ($phrases)";
		}

		if ($custom_cat) {
			$query .= " AND doc IN ($custom_cat)";
		}


		$df = $wpdb->get_var($query);

		if ($df < 1 && "sometimes" == $fuzzy) {
			$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table
				WHERE (term LIKE '%$term' OR term LIKE '$term%')";
				
			if ($expost) { //added by OdditY
				$query .= $postex;
			}
				
			if ($cat) {
				$query .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
				    WHERE term_taxonomy_id IN ($cat))";
			}
			if ($excat) {
				$query .= " AND doc NOT IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
				    WHERE term_taxonomy_id IN ($excat))";
			}
			
			if ($phrases) {
				$query .= " AND doc IN ($phrases)";
			}

			if ($custom_cat) {
				$query .= " AND doc IN ($custom_cat)";
			}
			
			$df = $wpdb->get_var($query);
		}
		
		$title_boost = floatval(get_option('relevanssi_title_boost'));
		$tag_boost = floatval(get_option('relevanssi_tag_boost'));
		$comment_boost = floatval(get_option('relevanssi_comment_boost'));
		
		foreach ($matches as $match) {
			$tf = $match->tf;
			$idf = log($D / (1 + $df));
			$weight = $tf * $idf;
			switch ($match->title) {
				case "1":
					$weight = $weight * $title_boost;
					isset($title_matches[$match->doc]) ? $title_matches[$match->doc] += $match->tf : $title_matches[$match->doc] = $match->tf;
					break;
				case "2":
					$weight = $weight * $tag_boost;
					isset($tag_matches[$match->doc]) ? $tag_matches[$match->doc] += $match->tf : $tag_matches[$match->doc] = $match->tf;
					break;
				case "3":
					$weight = $weight * $comment_boost;
					isset($comment_matches[$match->doc]) ? $comment_matches[$match->doc] += $match->tf : $comment_matches[$match->doc] = $match->tf;
					break;
				default:
					isset($body_matches[$match->doc]) ? $body_matches[$match->doc] += $match->tf : $body_matches[$match->doc] = $match->tf;
			}
			if (isset($doc_weight[$match->doc])) {
				$doc_weight[$match->doc] += $weight;
			}
			else {
				$doc_weight[$match->doc] = $weight;
			}
			
			isset($scores[$match->doc]) ? $scores[$match->doc] += $weight : $scores[$match->doc] = $weight;
		}
	}

	if (count($doc_weight) > 0) {
		arsort($doc_weight);
		$i = 0;
		foreach ($doc_weight as $doc => $weight) {
			$hits[intval($i)] = $doc;
			$i++;
		}
	}

	$return = array($hits, $body_matches, $title_matches, $tag_matches, $comment_matches, $scores);

	return $return;
}

/* If no phrase hits are made, this function returns false
 * If phrase matches are found, the function presents a comma-separated list of doc id's.
 * If phrase matches are found, but no matching documents, function returns -1.
 */
function relevanssi_recognize_phrases($q) {
	global $wpdb;
	
	$pos = mb_strpos($q, '"');
	
	$phrases = array();
	while ($pos !== false) {
		$start = $pos;
		$end = mb_strpos($q, '"', $start + 1);
		
		if ($end === false) {
			// just one " in the query
			$pos = $end;
			continue;
		}
		$phrase = mb_substr($q, $start + 1, $end-$start - 1);
		
		$phrases[] = $phrase;
		$pos = $end;
	}

	if (count($phrases) > 0) {
		$phrase_matches = array();
		foreach ($phrases as $phrase) {
			$phrase = $wpdb->escape($phrase);
			$query = "SELECT ID,post_content FROM $wpdb->posts
				WHERE post_content LIKE '%$phrase%'
				AND post_status = 'publish'";
			$docs = $wpdb->get_results($query);
			if (is_array($docs)) {
				foreach ($docs as $doc) {
					if (!isset($phrase_matches[$phrase])) {
						$phrase_matches[$phrase] = array();
					}
					$phrase_matches[$phrase][] = $doc->ID;
				}
			}
		}
		
		if (count($phrase_matches) < 1) {
			$phrases = "-1";
		}
		else {
			// Complicated mess, but necessary...
			$i = 0;
			$phms = array();
			foreach ($phrase_matches as $phm) {
				$phms[$i++] = $phm;
			}
			
			$phrases = $phms[0];
			if ($i > 1) {
				for ($i = 1; $i < count($phms); $i++) {
					$phrases =  array_intersect($phrases, $phms[$i]);
				}
			}
			
			if (count($phrases) < 1) {
				$phrases = "-1";
			}
			else {
				$phrases = implode(",", $phrases);
			}
		}
	}
	else {
		$phrases = false;
	}
	
	return $phrases;
}

function relevanssi_the_excerpt() {
    global $post;
    if (!post_password_required($post)) {
	    echo "<p>" . $post->post_excerpt . "</p>";
	}
	else {
		echo __('There is no excerpt because this is a protected post.');
	}
}

function relevanssi_do_excerpt($post, $query) {
	$excerpt_length = get_option("relevanssi_excerpt_length");
	$type = get_option("relevanssi_excerpt_type");

	$remove_stopwords = false;
	$terms = relevanssi_tokenize($query, $remove_stopwords);

	$content = apply_filters('the_content', $post->post_content);
	$content = relevanssi_strip_invisibles($content); // removes <script>, <embed> &c with content
	if ('on' == get_option('relevanssi_expand_shortcodes')) {
		if (function_exists("do_shortcodes")) {
			$content = do_shortcodes($content);
		}
	}
	else {
		if (function_exists("strip_shortcodes")) {
			$content = strip_shortcodes($content);
		}
	}
	$content = strip_tags($content); // this removes the tags, but leaves the content
	
	$content = ereg_replace("/\n\r|\r\n|\n|\r/", " ", $content);
	
	$excerpt = "";
	
	if ("chars" == $type) {
		$start = false;
		foreach (array_keys($terms) as $term) {
			if (function_exists('mb_stripos')) {
				$pos = ("" == $content) ? false : mb_stripos($content, $term);
			}
			else {
				$pos = mb_strpos($content, $term);
				if (false === $pos) {
					$titlecased = mb_strtoupper(mb_substr($term, 0, 1)) . mb_substr($term, 1);
					$pos = mb_strpos($content, $titlecased);
					if (false === $pos) {
						$pos = mb_strpos($content, mb_strtoupper($term));
					}
				}
			}
			
			if (false !== $pos) {
				if ($pos + strlen($term) < $excerpt_length) {
					$excerpt = mb_substr($content, 0, $excerpt_length);
					$start = true;
					break;
				}
				else {
					$half = floor($excerpt_length/2);
					$pos = $pos - $half;
					$excerpt = mb_substr($content, $pos, $excerpt_length);
					break;
				}
			}
		}
		
		if ("" == $excerpt) {
			$excerpt = mb_substr($content, 0, $excerpt_length);
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

			foreach (array_keys($terms) as $term) {
				if (function_exists('mb_stripos')) {
					$pos = ("" == $excerpt_slice) ? false : mb_stripos($excerpt_slice, $term);
					// To avoid "empty haystack" warnings
				}
				else {
					$pos = mb_strpos($excerpt_slice, $term);
					if (false === $pos) {
						$titlecased = mb_strtoupper(mb_substr($term, 0, 1)) . mb_substr($term, 1);
						$pos = mb_strpos($excerpt_slice, $titlecased);
						if (false === $pos) {
							$pos = mb_strpos($excerpt_slice, mb_strtoupper($term));
						}
					}
				}
				
				if (false !== $pos) {
					if (0 == $i) $start = true;
					$excerpt = $excerpt_slice;
					break;
				}
			}
			
			if ("" != $excerpt) break;
			
			$i += $excerpt_length;
		}
		
		if ("" == $excerpt) {
			$excerpt = explode(' ', $content, $excerpt_length);
			array_pop($excerpt);
			$excerpt = implode(' ', $excerpt);
			$start = true;
		}
	}
	
	$content = apply_filters('get_the_excerpt', $content);
	$content = apply_filters('the_excerpt', $content);	

	$highlight = get_option('relevanssi_highlight');
	if ("none" != $highlight) {
		$excerpt = relevanssi_highlight_terms($excerpt, array_keys($terms));
	}
	
	if (!$start) {
		$excerpt = "..." . $excerpt;
		// do not add three dots to the beginning of the post
	}
	
	$excerpt = $excerpt . "...";

	return $excerpt;
}

// found here: http://forums.digitalpoint.com/showthread.php?t=1106745
function relevanssi_strip_invisibles($text) {
    $text = preg_replace(
        array(
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
        ),
        array(
            ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ),
        $text );
    return $text;
}

function relevanssi_highlight_terms($excerpt, $terms) {
	$type = get_option("relevanssi_highlight");
	if ("none" == $type) {
		return $excerpt;
	}
	
	switch ($type) {
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
	
	$start_emp_token = "*[/";
	$end_emp_token = "\]*";
	mb_internal_encoding("UTF-8");
	
	foreach ($terms as $term) {
		$pos = 0;
		$low_excerpt = mb_strtolower($excerpt);
		while ($pos !== false) {
			$pos = mb_strpos($low_excerpt, $term, $pos);
			if ($pos !== false) {
				$excerpt = mb_substr($excerpt, 0, $pos)
						 . $start_emp_token
						 . mb_substr($excerpt, $pos, mb_strlen($term))
						 . $end_emp_token
						 . mb_substr($excerpt, $pos + mb_strlen($term));
				$low_excerpt = mb_strtolower($excerpt);
				$pos = $pos + mb_strlen($start_emp_token) + mb_strlen($end_emp_token);
			}
		}
	}

	$excerpt = str_replace($start_emp_token, $start_emp, $excerpt);
	$excerpt = str_replace($end_emp_token, $end_emp, $excerpt);
	$excerpt = str_replace($end_emp . $start_emp, "", $excerpt);

	return $excerpt;
}

function relevanssi_get_comments($postID) {	
	global $wpdb;

	$comtype = get_option("relevanssi_index_comments");
	switch ($comtype) {
		case "all": 
			// all (incl. customs, track- & pingbacks)
			break;
		case "normal": 
			// normal (excl. customs, track- & pingbacks)
			$restriction=" AND comment_type='' ";
			break;
		default:
			// none (don't index)
			return "";
	}

	$to = 20;
	$from = 0;

	while ( true ) {
		$sql = "SELECT 	comment_content
				FROM 	$wpdb->comments
				WHERE 	comment_post_ID = '$postID'
				AND 	comment_approved = '1' 
				".$restriction."
				LIMIT 	$from, $to";		
		$comments = $wpdb->get_results($sql);
		if (sizeof($comments) == 0) break;
		foreach($comments as $comment) {
			$comment_string .= $comment->comment_content . ' ';
		}
		$from += $to;
	}
	return $comment_string;
}


function relevanssi_build_index($extend = false) {
	global $wpdb, $relevanssi_table;
	
	$type = get_option("relevanssi_index_type");
	switch ($type) {
		case "posts":
			$restriction = " AND post_type = 'post'";
			break;
		case "pages":
			$restriction = " AND post_type = 'page'";
			break;
		case "both":
		default:
			$restriction = "";
	}

	$n = 0;
	
	if (!$extend) {
		// truncate table first
		$wpdb->query("TRUNCATE TABLE $relevanssi_table");
		$q = "SELECT ID, post_content, post_title
		FROM $wpdb->posts WHERE post_status='publish'" . $restriction;
		update_option('relevanssi_index', '');
	}
	else {
		// extending, so no truncate and skip the posts already in the index
		$q = "SELECT ID, post_content, post_title
		FROM $wpdb->posts WHERE post_status='publish' AND
		ID NOT IN (SELECT DISTINCT(doc) FROM $relevanssi_table)" . $restriction;
	}

	$custom_fields = relevanssi_get_custom_fields();
	
	$content = $wpdb->get_results($q);
	foreach ($content as $post) {
		$n += relevanssi_index_doc($post, false, $custom_fields);
		// n calculates the number of insert queries
	}
	
	echo '<div id="message" class="update fade"><p>' . __("Indexing complete!", "relevanssi") . '</p></div>';
	update_option('relevanssi_indexed', 'done');
}

function relevanssi_remove_doc($id) {
	global $wpdb, $relevanssi_table;
	
	$q = "DELETE FROM $relevanssi_table WHERE doc=$id";
	$wpdb->query($q);
}

function relevanssi_index_doc($post, $remove_first = false, $custom_fields = false) {
	global $wpdb, $relevanssi_table;

	if (!is_object($post)) {
		$post = $wpdb->get_row("SELECT ID, post_content, post_title
			FROM $wpdb->posts WHERE post_status='publish' AND ID=$post");
		if (!$post) {
			// the post isn't public
			return;
		}
	}

	if ($remove_first) {
		// we are updating a post, so remove the old stuff first
		relevanssi_remove_doc($post->ID);
	}

	$n = 0;	
	$titles = relevanssi_tokenize($post->post_title);
	

	//Added by OdditY - INDEX COMMENTS of the POST ->
	if ("none" != get_option("relevanssi_index_comments")) {
		$pcoms = relevanssi_get_comments($post->ID);
		if( $pcoms != "" ){
			$pcoms = relevanssi_strip_invisibles($pcoms);
			$pcoms = strip_tags($pcoms);
			$pcoms = relevanssi_tokenize($pcoms);		
			if (count($pcoms) > 0) {
				foreach ($pcoms as $pcom => $count) {
					if (strlen($pcom) < 2) continue;
					$n++;
					$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
					VALUES ($post->ID, '$pcom', $count, 3)");
				}
			}				
		}
	} //Added by OdditY END <-


	//Added by OdditY - INDEX TAGs of the POST ->
	if ("on" == get_option("relevanssi_include_tags")) {
		$ptagobj = get_the_terms($post->ID,'post_tag');
		if($ptagobj !== FALSE) { 
			foreach($ptagobj as $ptag) {
				$tagstr .= $ptag->name . ' ';
			}		
			$tagstr = trim($tagstr);
			$ptags = relevanssi_tokenize($tagstr);		
			if (count($ptags) > 0) {
				foreach ($ptags as $ptag => $count) {
					if (strlen($ptag) < 2) continue;
					$n++;
					$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
					VALUES ($post->ID, '$ptag', $count, 2)");
				}
			}	
		}
	} // Added by OdditY END <- 

	// index categories
	if ("on" == get_option("relevanssi_include_cats")) {
		$post_categories = get_the_category($post->ID);
		if (is_array($post_categories)) {
			foreach ($post_categories as $p_cat) {
				$cat_name = apply_filters("single_cat_title", $p_cat->cat_name);
				$cat_tokens = relevanssi_tokenize($cat_name);
				foreach ($cat_tokens as $pcat => $count) {
					if (strlen($pcat) < 2) continue;
					$n++;
					$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
					VALUES ($post->ID, '$pcat', $count, 4)");
				}
			}
		}
	}


	if ($custom_fields) {
		foreach ($custom_fields as $field) {
			$values = get_post_meta($post->ID, $field, false);
			if ("" == $values) continue;
			foreach ($values as $value) {
				// Custom field values are simply tacked to the end of the post content
				$post->post_content .= ' ' . $value;
			}
		}
	}
	
	$contents = relevanssi_strip_invisibles($post->post_content);
	
	if ('on' == get_option('relevanssi_expand_shortcodes')) {
		if (function_exists("do_shortcode")) {
			$contents = do_shortcode($contents);
		}
	}
	else {
		if (function_exists("strip_shortcodes")) {
			// WP 2.5 doesn't have the function
			$contents = strip_shortcodes($contents);
		}
	}
	
	$contents = strip_tags($contents);
	$contents = relevanssi_tokenize($contents);
	
	if (count($titles) > 0) {
		foreach ($titles as $title => $count) {
			if (strlen($title) < 2) continue;
			$n++;
			
			$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
			VALUES ($post->ID, '$title', $count, 1)");
			
			// a slightly clumsy way to handle titles, I'll try to come up with something better
		}
	}
	if (count($contents) > 0) {
		foreach ($contents as $content => $count) {
			if (strlen($content) < 2) continue;
			$n++;
			$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
			VALUES ($post->ID, '$content', $count, 0)");
		}
	}
	
	return $n;
}

function relevanssi_get_custom_fields() {
	$custom_fields = get_option("relevanssi_index_fields");
	if ($custom_fields) {
		$custom_fields = explode(",", $custom_fields);
		for ($i = 0; $i < count($custom_fields); $i++) {
			$custom_fields[$i] = trim($custom_fields[$i]);
		}
	}
	else {
		$custom_fields = false;
	}
	return $custom_fields;
}

function relevanssi_tokenize($str, $remove_stops = true) {
	mb_internal_encoding("UTF-8");

	$stopword_list = relevanssi_fetch_stopwords();
	$str = mb_strtolower(relevanssi_remove_punct($str));

	$t = strtok($str, "\n\t ");
	while ($t !== false) {
		$accept = true;
		if (count($stopword_list) > 0) {	//added by OdditY -> got warning when stopwords table was empty
			if (in_array($t, $stopword_list)) {
				$accept = false;
			}
		}
		if ($remove_stops == false) {
			$accept = true;
		}
		if ($accept) {
			if (!isset($tokens[$t])) {
				$tokens[$t] = 1;
			}
			else {
				$tokens[$t]++;
			}
		}
		$t = strtok("\n\t ");
	}

	return $tokens;
}

function relevanssi_remove_punct($a) {
		$a = strip_tags($a);

		$a = str_replace("'", '', $a);
		$a = str_replace("´", '', $a);
		$a = str_replace("’", '', $a);

		$a = str_replace("—", " ", $a);
        $a = mb_ereg_replace('[[:punct:]]+', ' ', $a);
		$a = str_replace("”", " ", $a);

        $a = preg_replace('/[[:space:]]+/', ' ', $a);
		$a = trim($a);
        return $a;
}

/*****
 * Interface functions
 */

function relevanssi_options() {
	$options_txt = __('Relevanssi Search Options', 'relevanssi');

	printf("<div class='wrap'><h2>%s</h2>", $options_txt);

	if ($_REQUEST['submit']) {
		update_relevanssi_options();
	}

	if ($_REQUEST['index']) {
		update_option('relevanssi_index_type', $_REQUEST['relevanssi_index_type']);
		update_option('relevanssi_index_fields', $_REQUEST['relevanssi_index_fields']);
		update_option('relevanssi_index_comments', $_REQUEST['relevanssi_index_comments']);
		update_option('relevanssi_inctags', $_REQUEST['relevanssi_inctags']);
		update_option('relevanssi_inccats', $_REQUEST['relevanssi_inccats']);
		update_option('relevanssi_expand_shortcodes', $_REQUEST['relevanssi_expand_shortcodes']);
		relevanssi_build_index();
	}

	if ($_REQUEST['index_extend']) {
		relevanssi_build_index(true);
	}
	
	if ($_REQUEST['search']) {
		relevanssi_search($_REQUEST['q']);
	}
	
	if ($_REQUEST['uninstall']) {
		relevanssi_uninstall();
	}
	
	if ("add_stopword" == $_REQUEST['dowhat']) {
		relevanssi_add_stopword($_REQUEST['term']);
	}
	
	relevanssi_options_form();
	
	relevanssi_common_words();
	
	if ('on' == get_option('relevanssi_log_queries')) {
		relevanssi_query_log();
	}
	
	echo "<div style='clear:both'></div>";
	
	echo "</div>";
}

function update_relevanssi_options() {
	if ($_REQUEST['relevanssi_title_boost']) {
		$boost = floatval($_REQUEST['relevanssi_title_boost']);
		update_option('relevanssi_title_boost', $boost);
	}

	if ($_REQUEST['relevanssi_tag_boost']) {
		$boost = floatval($_REQUEST['relevanssi_tag_boost']);
		update_option('relevanssi_tag_boost', $boost);
	}

	if ($_REQUEST['relevanssi_comment_boost']) {
		$boost = floatval($_REQUEST['relevanssi_comment_boost']);
		update_option('relevanssi_comment_boost', $boost);
	}
	
	if (!$_REQUEST['relevanssi_admin_search']) {
		$_REQUEST['relevanssi_admin_search'] = "off";
	}

	if (!$_REQUEST['relevanssi_excerpts']) {
		$_REQUEST['relevanssi_excerpts'] = "off";
	}

	if (!$_REQUEST['relevanssi_log_queries']) {
		$_REQUEST['relevanssi_log_queries'] = "off";
	}

	if (!$_REQUEST['relevanssi_expand_shortcodes']) {
		$_REQUEST['relevanssi_expand_shortcodes'] = "off";
	}

	if ($_REQUEST['relevanssi_excerpt_length']) {
		$value = intval($_REQUEST['relevanssi_excerpt_length']);
		if ($value != 0) {
			update_option('relevanssi_excerpt_length', $value);
		}
	}

	update_option('relevanssi_admin_search', $_REQUEST['relevanssi_admin_search']);
	update_option('relevanssi_excerpts', $_REQUEST['relevanssi_excerpts']);	
	update_option('relevanssi_excerpt_type', $_REQUEST['relevanssi_excerpt_type']);	
	update_option('relevanssi_log_queries', $_REQUEST['relevanssi_log_queries']);	
	update_option('relevanssi_highlight', $_REQUEST['relevanssi_highlight']);
	
	update_option('relevanssi_txt_col', $_REQUEST['relevanssi_txt_col']);
	update_option('relevanssi_bg_col', $_REQUEST['relevanssi_bg_col']);
	update_option('relevanssi_css', $_REQUEST['relevanssi_css']);
	update_option('relevanssi_class', $_REQUEST['relevanssi_class']);
	update_option('relevanssi_cat', $_REQUEST['relevanssi_cat']);
	update_option('relevanssi_excat', $_REQUEST['relevanssi_excat']);
	update_option('relevanssi_index_type', $_REQUEST['relevanssi_index_type']);
	update_option('relevanssi_index_fields', $_REQUEST['relevanssi_index_fields']);
	update_option('relevanssi_exclude_posts', $_REQUEST['relevanssi_expst']); 			//added by OdditY
	update_option('relevanssi_include_tags', $_REQUEST['relevanssi_inctags']); 			//added by OdditY	
	update_option('relevanssi_hilite_title', $_REQUEST['relevanssi_hilite_title']); 	//added by OdditY	
	update_option('relevanssi_index_comments', $_REQUEST['relevanssi_index_comments']); //added by OdditY	
	update_option('relevanssi_include_cats', $_REQUEST['relevanssi_inccats']);

	update_option('relevanssi_show_matches', $_REQUEST['relevanssi_show_matches']);
	update_option('relevanssi_show_matches_text', $_REQUEST['relevanssi_show_matches_text']);
	update_option('relevanssi_fuzzy', $_REQUEST['relevanssi_fuzzy']);
	update_option('relevanssi_expand_shortcodes', $_REQUEST['relevanssi_expand_shortcodes']);
}

function relevanssi_add_stopword($term) {
	global $wpdb, $relevanssi_table, $stopword_table;
	
	// add to stopwords
	$q = $wpdb->prepare("INSERT INTO $stopword_table (stopword) VALUES (%s)", $term);
	$success = $wpdb->query($q);
	
	if ($success) {
		// remove from index
		$q = $wpdb->prepare("DELETE FROM $relevanssi_table WHERE term=%s", $term);
		$wpdb->query($q);
		printf(__("<div id='message' class='update fade'><p>Term '%s' added to stopwords!</p></div>", "relevanssi"), $term);
	}
	else {
		printf(__("<div id='message' class='update fade'><p>Couldn't add term '%s' to stopwords!</p></div>", "relevanssi"), $term);
	}
}

function relevanssi_common_words() {
	global $wpdb, $relevanssi_table;
	
	echo "<div style='float:left; width: 45%'>";
	
	echo "<h3>" . __("25 most common words in the index", 'relevanssi') . "</h3>";
	
	echo "<p>" . __("These words are excellent stopword material. A word that appears in most of the posts in the database is quite pointless when searching. This is also an easy way to create a completely new stopword list, if one isn't available in your language. Click the icon after the word to add the word to the stopword list. The word will also be removed from the index, so rebuilding the index is not necessary.", 'relevanssi') . "</p>";
	
	$words = $wpdb->get_results("SELECT COUNT(DISTINCT(doc)) as cnt, term
		FROM $relevanssi_table GROUP BY term ORDER BY cnt DESC LIMIT 25");

	echo '<form method="post">';
	echo '<input type="hidden" name="dowhat" value="add_stopword" />';
	echo "<ul>\n";

	if (function_exists("plugins_url")) {
		if (version_compare($wp_version, '2.8dev', '>' )) {
			$src = plugins_url('delete.png', __FILE__);
		}
		else {
			$src = plugins_url('relevanssi/delete.png');
		}
	}
	else {
		// We can't check, so let's assume something sensible
		$src = '/wp-content/plugins/relevanssi/delete.png';
	}
	
	foreach ($words as $word) {
		$stop = __('Add to stopwords', 'relevanssi');
		printf('<li>%s (%d) <input style="padding: 0; margin: 0" type="image" src="%s" alt="%s" name="term" value="%s"/></li>', $word->term, $word->cnt, $src, $stop, $word->term);
	}
	echo "</ul>\n</form>";
	
	echo "</div>";
}

function relevanssi_query_log() {
	global $log_table, $wpdb;

	echo "<div style='float:right; width: 45%'>";
	
	echo "<h3>" . __("25 most popular queries", 'relevanssi') . "</h3>";
	
	$queries = $wpdb->get_results("SELECT COUNT(DISTINCT(id)) as cnt, query
	  FROM $log_table GROUP BY query ORDER BY cnt DESC LIMIT 25");
	if (count($queries) > 0) {
		echo "<ul>";
		foreach ($queries as $query) {
			echo "<li>" . $query->query . " (" . $query->cnt . ")</li>";
		}
		echo "</ul>";
	}

	echo "<h3>" . __("Recent queries that got 0 hits", 'relevanssi') . "</h3>";
	
	$queries = $wpdb->get_results("SELECT DISTINCT(query)
	  FROM $log_table WHERE hits = 0 ORDER BY time DESC LIMIT 25");
	if (count($queries) > 0) {
		echo "<ul>";
		foreach ($queries as $query) {
			echo "<li>" . $query->query . "</li>";
		}
		echo "</ul>";
	}

	echo "</div>";

}

function relevanssi_options_form() {
	global $title_boost_default, $tag_boost_default, $comment_boost_default;
	
	$title_boost = get_option('relevanssi_title_boost');
	$tag_boost = get_option('relevanssi_tag_boost');
	$comment_boost = get_option('relevanssi_comment_boost');
	$admin_search = get_option('relevanssi_admin_search');
	if ('on' == $admin_search) {
		$admin_search = 'checked="checked"';
	}
	else {
		$admin_search = '';
	}

	$excerpts = get_option('relevanssi_excerpts');
	if ('on' == $excerpts) {
		$excerpts = 'checked="checked"';
	}
	else {
		$excerpts = '';
	}
	
	$excerpt_length = get_option('relevanssi_excerpt_length');
	$excerpt_type = get_option('relevanssi_excerpt_type');
	switch ($excerpt_type) {
		case "chars":
			$excerpt_chars = 'selected="selected"';
			break;
		case "words":
			$excerpt_words = 'selected="selected"';
			break;
	}

	$log_queries = ('on' == get_option('relevanssi_log_queries') ? 'checked="checked"' : '');
	
	$highlight = get_option('relevanssi_highlight');
	$highlight_none = "";
	$highlight_em = "";
	$highlight_strong = "";
	$highlight_col = "";
	$highlight_bgcol = "";
	$highlight_style = "";
	switch ($highlight) {
		case "no":
			$highlight_none = 'selected="selected"';
			break;
		case "em":
			$highlight_em = 'selected="selected"';
			break;
		case "strong":
			$highlight_strong = 'selected="selected"';
			break;
		case "col":
			$highlight_col = 'selected="selected"';
			break;
		case "bgcol":
			$highlight_bgcol = 'selected="selected"';
			break;
		case "css":
			$highlight_style = 'selected="selected"';
			break;
		case "class":
			$highlight_class = 'selected="selected"';
			break;
	}

	$index_type = get_option('relevanssi_index_type');
	$index_type_posts = "";
	$index_type_pages = "";
	$index_type_both = "";
	switch ($index_type) {
		case "posts":
			$index_type_posts = 'selected="selected"';
			break;
		case "pages":
			$index_type_pages = 'selected="selected"';
			break;
		case "both":
			$index_type_both = 'selected="selected"';
			break;
	}
	
	$index_fields = get_option('relevanssi_index_fields');
	
	$txt_col = get_option('relevanssi_txt_col');
	$bg_col = get_option('relevanssi_bg_col');
	$css = get_option('relevanssi_css');
	$class = get_option('relevanssi_class');
	
	$cat = get_option('relevanssi_cat');
	$excat = get_option('relevanssi_excat');
	
	$fuzzy_sometimes = ('sometimes' == get_option('relevanssi_fuzzy') ? 'selected="selected"' : '');
	$fuzzy_always = ('always' == get_option('relevanssi_fuzzy') ? 'selected="selected"' : '');
	$fuzzy_never = ('never' == get_option('relevanssi_fuzzy') ? 'selected="selected"' : '');

	$expand_shortcodes = ('on' == get_option('relevanssi_expand_shortcodes') ? 'checked="checked"' : '');

	
	//Added by OdditY ->
	$expst = get_option('relevanssi_exclude_posts'); 
	$inctags = ('on' == get_option('relevanssi_include_tags') ? 'checked="checked"' : ''); 
	$hititle = ('on' == get_option('relevanssi_hilite_title') ? 'checked="checked"' : ''); 
	$incom_type = get_option('relevanssi_index_comments');
	$incom_type_all = "";
	$incom_type_normal = "";
	$incom_type_none = "";
	switch ($incom_type) {
		case "all":
			$incom_type_all = 'selected="selected"';
			break;
		case "normal":
			$incom_type_normal = 'selected="selected"';
			break;
		case "none":
			$incom_type_none = 'selected="selected"';
			break;
	}//added by OdditY END <-
	
	$inccats = ('on' == get_option('relevanssi_include_cats') ? 'checked="checked"' : ''); 
	
	$show_matches = ('on' == get_option('relevanssi_show_matches') ? 'checked="checked"' : '');
	$show_matches_text = get_option('relevanssi_show_matches_text');
	
	$title_boost_txt = __('Title boost:', 'relevanssi');
	$title_boost_desc = sprintf(__('Default: %d. 0 means titles are ignored, 1 means no boost, more than 1 gives extra value.', 'relevanssi'), $title_boost_default);
	$tag_boost_txt = __('Tag boost:', 'relevanssi');
	$tag_boost_desc = sprintf(__('Default: %d. 0 means tags are ignored, 1 means no boost, more than 1 gives extra value.', 'relevanssi'), $tag_boost_default);
	$comment_boost_txt = __('Comment boost:', 'relevanssi');
	$comment_boost_desc = sprintf(__('Default: %d. 0 means comments are ignored, 1 means no boost, more than 1 gives extra value.', 'relevanssi'), $comment_boost_default);
	$admin_search_txt = __('Use search for admin:', 'relevanssi');
	$admin_search_desc = __('If checked, Relevanssi will be used for searches in the admin interface', 'relevanssi');
	$cat_txt = __('Restrict search to these categories and tags:', 'relevanssi');
	$cat_desc = __("Enter a comma-separated list of category and tag IDs to restrict search to those categories or tags. You can also use <code>&lt;input type='hidden' name='cat' value='list of cats and tags' /&gt;</code> in your search form. The input field will overrun this setting.", 'relevanssi');
	$excat_txt = __('Exclude these categories and tags from search:', 'relevanssi');
	$excat_desc = __("Enter a comma-separated list of category and tag IDs that are excluded from search results. This only works here, you can't use the input field option (WordPress doesn't pass custom parameters there).", 'relevanssi');
	$exclusions = __("Exclusions and restrictions", "relevanssi");
	
	//Added by OdditY ->
	$expst_txt = __('Exclude these posts/pages from search:', 'relevanssi');
	$expst_desc = __("Enter a comma-separated list of post/page IDs that are excluded from search results. This only works here, you can't use the input field option (WordPress doesn't pass custom parameters there).", 'relevanssi');
	$inctags_txt = __('Index and search your posts\' tags:', 'relevanssi');
	$inctags_desc = __("If checked, Relevanssi will also index and search the tags of your posts. Remember to rebuild the index if you change this option!", 'relevanssi');
	$incom_type_txt = __("Index and search these comments:", "relevanssi");
	$incom_type_desc = __("Relevanssi will index and search ALL (all comments including track- &amp; pingbacks and custom comment types), NONE (no comments) or NORMAL (manually posted comments on your blog).<br />Remember to rebuild the index if you change this option!", 'relevanssi');
	$incom_all_txt = __("all", "relevanssi");
	$incom_normal_txt = __("normal", "relevanssi");
	$incom_none_txt = __("none", "relevanssi");
	//added by OdditY END <-

	$inccats_txt = __('Index and search your posts\' categories:', 'relevanssi');
	$inccats_desc = __("If checked, Relevanssi will also index and search the categories of your posts. Category titles will pass through 'single_cat_title' filter. Remember to rebuild the index if you change this option!", 'relevanssi');
	
	$excerpts_title = __("Custom excerpts/snippets", "relevanssi");
	$excerpt_txt = __("Create custom search result snippets:", "relevanssi");
	$excerpt_desc = __("If checked, Relevanssi will create excerpts that contain the search term hits. To make them work, make sure your search result template uses the_excerpt() to display post excerpts.", 'relevanssi');
	$excerpt_length_txt = __("Length of the snippet:", "relevanssi");
	$excerpt_length_desc = __("This must be an integer.", "relevanssi");
	$words = __("words", "relevanssi");
	$chars = __("characters", "relevanssi");
	$log_queries_txt = __("Keep a log of user queries:", "relevanssi");
	$log_queries_desc = __("If checked, Relevanssi will log user queries.", 'relevanssi');
	$highlighting = __("Search hit highlighting", "relevanssi");
	$highlight_instr_1 = __("First, choose the type of highlighting used:", "relevanssi");
	$highlight_instr_2 = __("Then adjust the settings for your chosen type:", "relevanssi");
	$highlight_txt = __("Highlight query terms in search results:", 'relevanssi');
	$highlight_comment = __("Highlighting isn't available unless you use custom snippets", 'relevanssi');
	$hititle_txt = __("Highlight query terms in result titles too:", 'relevanssi');
	$hititle_desc = __("", 'relevanssi');	
	
	$submit_value = __('Save the options', 'relevanssi');
	$building_the_index = __('Building the index and indexing options', 'relevanssi');
	$index_p1 = __("After installing the plugin, you need to build the index. This generally needs to be done once, you don't have to re-index unless something goes wrong. Indexing is a heavy task and might take more time than your servers allow. If the indexing cannot be finished - for example you get a blank screen or something like that after indexing - you can continue indexing from where you left by clicking 'Continue indexing'. Clicking 'Build the index' will delete the old index, so you can't use that.", 'relevanssi');
	$index_p2 = __("So, if you build the index and don't get the 'Indexing complete' in the end, keep on clicking the 'Continue indexing' button until you do. On my blogs, I was able to index ~400 pages on one go, but had to continue indexing twice to index ~950 pages.", 'relevanssi');
	$build_index = __("Save indexing options and build the index", 'relevanssi');
	$continue_index = __("Continue indexing", 'relevanssi');
	$no_highlight_txt = __("No highlighting", 'relevanssi');
	$txt_col_txt = __("Text color", 'relevanssi');
	$bg_col_txt = __("Background color", 'relevanssi');
	$css_txt = __("CSS Style", 'relevanssi');
	$class_txt = __("CSS Class", 'relevanssi');
	
	$txt_col_choice_txt = __("Text color for highlights:", "relevanssi");
	$bg_col_choice_txt = __("Background color for highlights:", "relevanssi");
	$css_choice_txt = __("CSS style for highlights:", "relevanssi");
	$class_choice_txt = __("CSS class for highlights:", "relevanssi");

	$txt_col_choice_desc = __("Use HTML color codes (#rgb or #rrggbb)", "relevanssi");
	$bg_col_choice_desc = __("Use HTML color codes (#rgb or #rrggbb)", "relevanssi");
	$css_choice_desc = __("You can use any CSS styling here, style will be inserted with a &lt;span&gt;", "relevanssi"); 
	$class_choice_desc = __("Name a class here, search results will be wrapped in a &lt;span&gt; with the class", "relevanssi"); 

	$index_type_txt = __("What to include in the index", "relevanssi");
	$index_both_txt = __("Everything", "relevanssi");
	$index_posts_txt = __("Just posts", "relevanssi");
	$index_pages_txt = __("Just pages", "relevanssi");

	$index_fields_txt = __("Custom fields to index:", "relevanssi");
	$index_fields_desc = __("A comma-separated list of custom field names to include in the index.", "relevanssi");

	$show_matches_txt = __("Show breakdown of search hits in excerpts:", "relevanssi");
	$show_matches_desc = __("Check this to show more information on where the search hits were made. Requires custom snippets to work.", "relevanssi");
	$show_matches_text_txt = __("The breakdown format:", "relevanssi");
	$show_matches_text_desc = __("Use %body%, %title%, %tags%, %comments% and %score% to display the number of hits and the document weight.", "relevanssi");
	
	$fuzzy_txt = __("When to use fuzzy matching?", "relevanssi");
	$fuzzy_sometimes_txt = __("When straight search gets no hits", "relevanssi");
	$fuzzy_always_txt = __("Always", "relevanssi");
	$fuzzy_never_txt = __("Don't use fuzzy search", "relevanssi");
	$fuzzy_desc = __("Straight search matches just the term. Fuzzy search matches everything that begins or ends with the search term.", "relevanssi");
	
	$expand_shortcodes_txt = __("Expand shortcodes in post content:", "relevanssi");
	$expand_shortcodes_desc = __("If checked, Relevanssi will expand shortcodes in post content before indexing. Otherwise shortcodes will be stripped. If you use shortcodes to include dynamic content, Relevanssi will not keep the index updated, the index will reflect the status of the shortcode content at the moment of indexing.", "relevanssi");
	
	$uninstall_title = __("Uninstall", "relevanssi");
	$uninstall_txt = __("If you want to uninstall the plugin, start by clicking the button below to wipe clean the options and tables created by the plugin, then remove it from the plugins list.", "relevanssi");	
	$uninstall_button = __("Remove plugin data", "relevanssi");

	echo <<<EOHTML
	<br />
	
<div style="float: right; border: thin solid #111; padding: 10px; width: 200px">
<h3>Support Relevanssi!</h3>
<p>How valuable is the improved search for your WordPress site? Relevanssi is and will
be free, but consider supporting the author if Relevanssi made your web site better.</p>

<p>Relevanssi is written by <strong>Mikko Saari</strong>, a Finnish WordPress nerd, SEO grunt and board
game geek. Any money received will be used for the good of Mikko, his wife and two
hungry kids.</p>

<div style="text-align:center">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations">
<input type="hidden" name="business" value="msaari@iki.fi">
<input type="hidden" name="lc" value="US">
<input type="hidden" name="item_name" value="Relevanssi">
<input type="hidden" name="currency_code" value="EUR">
<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div>

<p>If you don't like donating money, please consider blogging about Relevanssi with a link
to the <a href="http://www.mikkosaari.fi/relevanssi/">plugin page</a>. Your users won't know
you're using Relevanssi, all they see is better search results. Please let them know what
makes the search better - you'll help them and you'll help me.</p>

<p>Whatever you do, thanks for using Relevanssi!</p>

<p>&mdash; Mikko</p>
</div>
	
	<form method="post">
	<label for="relevanssi_title_boost">$title_boost_txt 
	<input type="text" name="relevanssi_title_boost" size="4" value="$title_boost" /></label>
	<small>$title_boost_desc</small>
	<br />
	<label for="relevanssi_tag_boost">$tag_boost_txt 
	<input type="text" name="relevanssi_tag_boost" size="4" value="$tag_boost" /></label>
	<small>$tag_boost_desc</small>
	<br />
	<label for="relevanssi_comment_boost">$comment_boost_txt 
	<input type="text" name="relevanssi_comment_boost" size="4" value="$comment_boost" /></label>
	<small>$comment_boost_desc</small>
	<br /><br />
	<label for="relevanssi_admin_search">$admin_search_txt
	<input type="checkbox" name="relevanssi_admin_search" $admin_search /></label>
	<small>$admin_search_desc</small>

	<br /><br />

	<label for="relevanssi_fuzzy">$fuzzy_txt
	<select name="relevanssi_fuzzy">
	<option value="sometimes" $fuzzy_sometimes">$fuzzy_sometimes_txt</option>
	<option value="always" $fuzzy_always">$fuzzy_always_txt</option>
	<option value="never" $fuzzy_never">$fuzzy_never_txt</option>
	</select><br />
	<small>$fuzzy_desc</small>

	<br /><br />

	<label for="relevanssi_log_queries">$log_queries_txt
	<input type="checkbox" name="relevanssi_log_queries" $log_queries /></label>
	<small>$log_queries_desc</small>

	<h3>$exclusions</h3>
	
	<label for="relevanssi_cat">$cat_txt
	<input type="text" name="relevanssi_cat" size="20" value="$cat" /></label><br />
	<small>$cat_desc</small>

	<br />

	<label for="relevanssi_excat">$excat_txt
	<input type="text" name="relevanssi_excat" size="20" value="$excat" /></label><br />
	<small>$excat_desc</small>

	<br />

	<label for="relevanssi_excat">$expst_txt
	<input type="text" name="relevanssi_expst" size="20" value="$expst" /></label><br />
	<small>$expst_desc</small>

	<h3>$excerpts_title</h3>
	
	<label for="relevanssi_excerpts">$excerpt_txt
	<input type="checkbox" name="relevanssi_excerpts" $excerpts /></label><br />
	<small>$excerpt_desc</small>
	
	<br />
	
	<label for="relevanssi_excerpt_length">$excerpt_length_txt
	<input type="text" name="relevanssi_excerpt_length" size="4" value="$excerpt_length" /></label>
	<select name="relevanssi_excerpt_type">
	<option value="chars" $excerpt_chars">$chars</option>
	<option value="words" $excerpt_words">$words</option>
	</select><br />
	<small>$excerpt_length_desc</small>

	<br />

	<label for="relevanssi_show_matches">$show_matches_txt
	<input type="checkbox" name="relevanssi_show_matches" $show_matches /></label>
	<small>$show_matches_desc</small>

	<br />

	<label for="relevanssi_show_matches_text">$show_matches_text_txt
	<input type="text" name="relevanssi_show_matches_text" value="$show_matches_text" size="20" /></label>
	<small>$show_matches_text_desc</small>

	<h3>$highlighting</h3>

	$highlight_instr_1<br />

	<div style="margin-left: 2em">
	<label for="relevanssi_highlight">$highlight_txt
	<select name="relevanssi_highlight">
	<option value="no" $highlight_none>$no_highlight_txt</option>
	<option value="em" $highlight_em>&lt;em&gt;</option>
	<option value="strong" $highlight_strong>&lt;strong&gt;</option>
	<option value="col" $highlight_col>$txt_col_txt</option>
	<option value="bgcol" $highlight_bgcol>$bg_col_txt</option>
	<option value="css" $highlight_style>$css_txt</option>
	<option value="class" $highlight_class>$class_txt</option>
	</select></label>
	<small>$highlight_comment</small>
	
	<br />

	<label for="relevanssi_hilite_title">$hititle_txt
	<input type="checkbox" name="relevanssi_hilite_title" $hititle /></label>
	<small>$hititle_desc</small>

	<br /><br />
	</div>
	
	$highlight_instr_2<br />

	<div style="margin-left: 2em">
	
	<label for="relevanssi_txt_col">$txt_col_choice_txt
	<input type="text" name="relevanssi_txt_col" size="7" value="$txt_col" /></label>
	<small>$txt_col_choice_desc</small>

	<br />
	
	<label for="relevanssi_bg_col">$bg_col_choice_txt
	<input type="text" name="relevanssi_bg_col" size="7" value="$bg_col" /></label>
	<small>$bg_col_choice_desc</small>

	<br />
	
	<label for="relevanssi_css">$css_choice_txt
	<input type="text" name="relevanssi_css" size="30" value="$css" /></label>
	<small>$css_choice_desc</small>

	<br />
	
	<label for="relevanssi_css">$class_choice_txt
	<input type="text" name="relevanssi_class" size="10" value="$class" /></label>
	<small>$class_choice_desc</small>

	</div>
	
	<br />
	<br />
	
	<input type="submit" name="submit" value="$submit_value" />

	<h3>$building_the_index</h3>
	
	<p>$index_p1</p>
	
	<p>$index_p2</p>

	<label for="relevanssi_index_type">$index_type_txt:
	<select name="relevanssi_index_type">
	<option value="both" $index_type_both>$index_both_txt</option>
	<option value="posts" $index_type_posts>$index_posts_txt</option>
	<option value="pages" $index_type_pages>$index_pages_txt</option>
	</select></label>
	<small>$index_type_comment</small>

	<br /><br />

	<label for="relevanssi_expand_shortcodes">$expand_shortcodes_txt
	<input type="checkbox" name="relevanssi_expand_shortcodes" $expand_shortcodes /></label><br />
	<small>$expand_shortcodes_desc</small>

	<br /><br />

	<label for="relevanssi_inctags">$inctags_txt
	<input type="checkbox" name="relevanssi_inctags" $inctags /></label><br />
	<small>$inctags_desc</small>

	<br />

	<label for="relevanssi_inccats">$inccats_txt
	<input type="checkbox" name="relevanssi_inccats" $inccats /></label><br />
	<small>$inccats_desc</small>

	<br />
	
	<label for="relevanssi_index_comments">$incom_type_txt
	<select name="relevanssi_index_comments">
	<option value="none" $incom_type_none>$incom_none_txt</option>
	<option value="normal" $incom_type_normal>$incom_normal_txt</option>
	<option value="all" $incom_type_all>$incom_all_txt</option>
	</select></label><br />
	<small>$incom_type_desc</small>

	<br /><br />

	<label for="relevanssi_index_fields">$index_fields_txt
	<input type="text" name="relevanssi_index_fields" size="30" value="$index_fields" /></label><br />
	<small>$index_fields_desc</small>

	<br /><br />

	<input type="submit" name="index" value="$build_index" />

	<input type="submit" name="index_extend" value="$continue_index" />

	<h3>$uninstall_title</h3>
	
	<p>$uninstall_txt</p>
	
	<input type="submit" name="uninstall" value="$uninstall_button" />

	</form>
EOHTML;
}
?>