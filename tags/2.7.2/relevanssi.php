<?php
/*
Plugin Name: Relevanssi
Plugin URI: http://www.mikkosaari.fi/en/relevanssi-search/
Description: This plugin replaces WordPress search with a relevance-sorting search.
Version: 2.7.2
Author: Mikko Saari
Author URI: http://www.mikkosaari.fi/
*/

/*  Copyright 2011 Mikko Saari  (email: mikko@mikkosaari.fi)

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

// For debugging purposes
//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
//define('WP-DEBUG', true);

register_activation_hook(__FILE__,'relevanssi_install');
add_action('admin_menu', 'relevanssi_menu');
add_filter('the_posts', 'relevanssi_query');
add_filter('post_limits', 'relevanssi_getLimit');
add_action('edit_post', 'relevanssi_edit');
add_action('edit_page', 'relevanssi_edit');
add_action('save_post', 'relevanssi_edit');
add_action('save_post', 'relevanssi_publish');				// thanks to Brian D Gajus
add_action('delete_post', 'relevanssi_delete');
add_action('publish_post', 'relevanssi_publish');
add_action('publish_page', 'relevanssi_publish');
add_action('future_publish_post', 'relevanssi_publish');
add_action('comment_post', 'relevanssi_comment_index'); 	//added by OdditY
add_action('edit_comment', 'relevanssi_comment_edit'); 		//added by OdditY 
add_action('delete_comment', 'relevanssi_comment_remove'); 	//added by OdditY
// BEGIN added by renaissancehack
// *_page and *_post hooks do not trigger on attachments
add_action('delete_attachment', 'relevanssi_delete');
add_action('add_attachment', 'relevanssi_publish');
add_action('edit_attachment', 'relevanssi_edit');
// When a post status changes, check child posts that inherit their status from parent
add_action('transition_post_status', 'relevanssi_update_child_posts',99,3);
// END added by renaissancehack
add_action('init', 'relevanssi_init');
add_filter('relevanssi_hits_filter', 'relevanssi_wpml_filter');

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'relevanssi', 'wp-content/plugins/' . $plugin_dir, $plugin_dir);

global $wpSearch_low;
global $wpSearch_high;
global $relevanssi_table;
global $stopword_table;
global $log_table;
global $relevanssi_cache;
global $relevanssi_excerpt_cache;
global $stopword_list;
global $title_boost_default;
global $tag_boost_default;
global $comment_boost_default;

global $relevanssi_hits;

$wpSearch_low = 0;
$wpSearch_high = 0;
$relevanssi_table = $wpdb->prefix . "relevanssi";
$stopword_table = $wpdb->prefix . "relevanssi_stopwords";
$log_table = $wpdb->prefix . "relevanssi_log";
$relevanssi_cache = $wpdb->prefix . "relevanssi_cache";
$relevanssi_excerpt_cache = $wpdb->prefix . "relevanssi_excerpt_cache";
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
	add_dashboard_page(
		'User searches',
		'User searches',
		'manage_options',
		__FILE__,
		'relevanssi_search_stats'
	);
}

function relevanssi_init() {
	if (!get_option('relevanssi_indexed') && !$_POST['index']) {
		function relevanssi_warning() {
			echo "<div id='relevanssi-warning' class='updated fade'><p><strong>"
			   . sprintf(__('Relevanssi needs attention: Remember to build the index (you can do it at <a href="%1$s">the settings page</a>), otherwise searching won\'t work.'), "options-general.php?page=relevanssi/relevanssi.php")
			   . "</strong></p></div>";
		}
		add_action('admin_notices', 'relevanssi_warning');
	}
	
	if (!function_exists('mb_internal_encoding')) {
		function relevanssi_mb_warning() {
			echo "<div id='relevanssi-warning' class='updated fade'><p><strong>"
			   . "Multibyte string functions are not available. Relevanssi won't work without them."
			   . "Please install (or ask your host to install) the mbstring extension."
			   . "</strong></p></div>";
		}
		add_action('admin_notices', 'relevanssi_mb_warning');
	}
	
	return;
}

function relevanssi_didyoumean($query, $pre, $post, $n = 5) {
	global $wpdb, $log_table, $wp_query;
	
	$total_results = $wp_query->found_posts;	
	
	if ($total_results > $n) return;

	$q = "SELECT query, count(query) as c, AVG(hits) as a FROM $log_table WHERE hits > 1 GROUP BY query ORDER BY count(query) DESC";
	$q = apply_filters('relevanssi_didyoumean_query', $q);

	$data = $wpdb->get_results($q);
		
	$distance = -1;
	$closest = "";
	
	foreach ($data as $row) {
		if ($row->c < 2) break;
		$lev = levenshtein($query, $row->query);
		
		if ($lev < $distance || $distance < 0) {
			if ($row->a > 0) {
				$distance = $lev;
				$closest = $row->query;
				if ($lev == 1) break; // get the first with distance of 1 and go
			}
		}
	}
	
	if ($distance > 0) {
		$url = get_bloginfo('url');
		echo "$pre<a href='$url/?s=$closest'>$closest</a>$post";
	}
}

// BEGIN added by renaissancehack
function relevanssi_update_child_posts($new_status, $old_status, $post) {
// called by 'transition_post_status' action hook when a post is edited/published/deleted
//  and calls appropriate indexing function on child posts/attachments
    global $wpdb;
    $index_statuses = array('publish', 'private');
    if (($new_status == $old_status)
          || (in_array($new_status, $index_statuses) && in_array($old_status, $index_statuses))
          || (in_array($post->post_type, array('attachment', 'revision')))) {
        return;
    }
    $q = "SELECT * FROM $wpdb->posts WHERE post_parent=$post->ID AND post_type!='revision'";
    $child_posts = $wpdb->get_results($q);
    if ($child_posts) {
        if (!in_array($new_status, $index_statuses)) {
            foreach ($child_posts as $post) {
                relevanssi_delete($post->ID);
            }
        } else {
            foreach ($child_posts as $post) {
                relevanssi_publish($post->ID);
            }
        }
    }
}
// END added by renaissancehack

function relevanssi_edit($post) {
	// Check if the post is public
	global $wpdb;

	$post_status = $wpdb->get_var("SELECT post_status FROM $wpdb->posts WHERE ID=$post");
// BEGIN added by renaissancehack
    //  if post_status is "inherit", get post_status from parent
    if ($post_status == 'inherit') {
        $post_type = $wpdb->get_var("SELECT post_type FROM $wpdb->posts WHERE ID=$post");
    	$post_status = $wpdb->get_var("SELECT p.post_status FROM $wpdb->posts p, $wpdb->posts c WHERE c.ID=$post AND c.post_parent=p.ID");
    }
// END added by renaissancehack
	if ($post_status != 'publish') {
		// The post isn't public anymore, remove it from index
		relevanssi_remove_doc($post);
	}
	// No need to do anything else, because if the post is public, it'll trigger
	// publish_post.
// BEGIN added by renaissancehack
    // unless it is an attachment -- then it will not trigger publish_post
    elseif (($post_type == 'attachment') && ($post_status == 'publish')) {
        relevanssi_publish($post);
}
// END added by renaissancehack
}

function relevanssi_purge_excerpt_cache($post) {
	global $wpdb, $relevanssi_excerpt_cache;
	
	$wpdb->query("DELETE FROM $relevanssi_excerpt_cache WHERE post = $post");
}

function relevanssi_delete($post) {
	relevanssi_remove_doc($post);
	relevanssi_purge_excerpt_cache($post);
}

function relevanssi_publish($post) {
	relevanssi_add($post);
}

function relevanssi_add($post) {
	$custom_fields = relevanssi_get_custom_fields();
	relevanssi_index_doc($post, true, $custom_fields);
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
	add_option('relevanssi_highlight_docs', 'off');
	add_option('relevanssi_highlight_comments', 'off');
	add_option('relevanssi_index_comments', 'none');	//added by OdditY
	add_option('relevanssi_include_cats', '');
	add_option('relevanssi_show_matches', '');
	add_option('relevanssi_show_matches_txt', '(Search hits: %body% in body, %title% in title, %tags% in tags, %comments% in comments. Score: %score%)');
	add_option('relevanssi_fuzzy', 'sometimes');
	add_option('relevanssi_indexed', '');
	add_option('relevanssi_expand_shortcodes', 'on');
	add_option('relevanssi_custom_types', '');
	add_option('relevanssi_custom_taxonomies', '');
	add_option('relevanssi_index_author', '');
	add_option('relevanssi_implicit_operator', 'OR');
	add_option('relevanssi_omit_from_logs', '');
	add_option('relevanssi_synonyms', '');
	add_option('relevanssi_index_excerpt', '');
	add_option('relevanssi_index_limit', '500');
	add_option('relevanssi_index_attachments', '');
	add_option('relevanssi_disable_or_fallback', 'off');
	add_option('relevanssi_respect_exclude', 'on');
	add_option('relevanssi_cache_seconds', 172800);
	add_option('relevanssi_enable_cache', 'off');
	add_option('relevanssi_min_word_length', 3);
	
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

	$relevanssi_cache = $wpdb->prefix . 'relevanssi_cache';

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_cache'") != $relevanssi_cache) {
		$sql = "CREATE TABLE " . $relevanssi_cache . " (param varchar(32) NOT NULL, "
		. "hits text NOT NULL, "
		. "tstamp timestamp NOT NULL, "
	    . "UNIQUE KEY param (param));";

		dbDelta($sql);
	}

	$relevanssi_excerpt_cache = $wpdb->prefix . 'relevanssi_excerpt_cache';

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_excerpt_cache'") != $relevanssi_excerpt_cache) {
		$sql = "CREATE TABLE " . $relevanssi_excerpt_cache . " (query varchar(100) NOT NULL, "
		. "post mediumint(9) NOT NULL, "
		. "excerpt text NOT NULL, "
	    . "UNIQUE (query, post));";

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
	delete_option('relevanssi_custom_types');
	delete_option('relevanssi_custom_taxonomies');
	delete_option('relevanssi_index_author');
	delete_option('relevanssi_implicit_operator');
	delete_option('relevanssi_omit_from_logs');
	delete_option('relevanssi_synonyms');
	delete_option('relevanssi_index_excerpt');
	delete_option('relevanssi_highlight_docs');
	delete_option('relevanssi_highlight_comments');
	delete_option('relevanssi_index_limit');
	delete_option('relevanssi_index_attachments');
	delete_option('relevanssi_disable_or_fallback');
	delete_option('relevanssi_respect_exclude');
	delete_option('relevanssi_cache_seconds');
	delete_option('relevanssi_enable_cache');
	delete_option('relevanssi_min_word_length');
	
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
	
	echo '<div id="message" class="updated fade"><p>' . __("Data wiped clean, you can now delete the plugin.", "relevanssi") . '</p></div>';
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

function relevanssi_query($posts, $query = false) {
	$admin_search = get_option('relevanssi_admin_search');
	($admin_search == 'on') ? $admin_search = true : $admin_search = false;

	global $relevanssi_active;
	
	$search_ok = true; 							// we will search!
	if (!is_search()) {
		$search_ok = false;						// no, we can't
	}
	if (is_search() && is_admin()) {
		$search_ok = false; 					// but if this is an admin search, reconsider
		if ($admin_search) $search_ok = true; 	// yes, we can search!
	}
	if ($relevanssi_active) {
		$search_ok = false;						// Relevanssi is already in action
	}

	if ($search_ok) {
		global $wp_query;
		$posts = relevanssi_do_query($wp_query);
	}

	return $posts;
}

function relevanssi_do_query(&$query) {
	// this all is basically lifted from Kenny Katzgrau's wpSearch
	// thanks, Kenny!
	global $wpSearch_low;
	global $wpSearch_high;
	global $relevanssi_active;
	
	$relevanssi_active = true;
	$posts = array();

	$q = trim(stripslashes($query->query_vars["s"]));
	
	$cat = false;
	if (isset($query->query_vars["cat"])) {
		$cat = $query->query_vars["cat"];
	}
	if (!$cat) {
		$cat = get_option('relevanssi_cat');
		if (0 == $cat) {
			$cat = false;
		}
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

	$post_type = false;
	if (isset($query->query_vars["post_type"]) && $query->query_vars["post_type"] != 'any') {
		$post_type = $query->query_vars["post_type"];
	}

	$expids = get_option("relevanssi_exclude_posts");

	if (is_admin()) {
		// in admin search, search everything
		$cat = null;
		$excat = null;
		$expids = null;
		$tax = null;
		$tax_term = null;
	}

	$operator = get_option("relevanssi_implicit_operator");
	
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

	$cache = get_option('relevanssi_enable_cache');
	$cache == 'on' ? $cache = true : $cache = false;
	
	if ($cache) {
		$params = md5(serialize(array($q, $cat, $excat, $expids, $post_type, $tax, $tax_term, $operator)));
		$return = relevanssi_fetch_hits($params);
		if (!$return) {
			$return = relevanssi_search($q, $cat, $excat, $expids, $post_type, $tax, $tax_term, $operator);
			$return_ser = serialize($return);
			relevanssi_store_hits($params, $return_ser);
		}
	}
	else {
		$return = relevanssi_search($q, $cat, $excat, $expids, $post_type, $tax, $tax_term, $operator);
	}

	$hits = $return['hits'];

	$filter_data = array($hits, $q);
	$hits_filters_applied = apply_filters('relevanssi_hits_filter', $filter_data);
	$hits = $hits_filters_applied[0];

	$query->found_posts = sizeof($hits);
	$query->max_num_pages = ceil(sizeof($hits) / $query->query_vars["posts_per_page"]);

	$update_log = get_option('relevanssi_log_queries');
	if ('on' == $update_log) {
		relevanssi_update_log($q, sizeof($hits));
	}
	
	if ($wpSearch_high > sizeof($hits)) $wpSearch_high = sizeof($hits) - 1;
	
	$make_excerpts = get_option('relevanssi_excerpts');

	if ($wpSearch_low == 0 && $wpSearch_high == 0) {
		$wpSearch_high = sizeof($hits) - 1;
	}
	
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
			$post->post_title = strip_tags($post->post_title);
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
	
	return $posts;
}

function relevanssi_fetch_excerpt($post, $query) {
	global $wpdb, $relevanssi_excerpt_cache;

	$query = mysql_real_escape_string($query);	
	$excerpt = $wpdb->get_var("SELECT excerpt FROM $relevanssi_excerpt_cache WHERE post = $post AND query = '$query'");
	
	if (!$excerpt) return null;
	
	return $excerpt;
}

function relevanssi_store_excerpt($post, $query, $excerpt) {
	global $wpdb, $relevanssi_excerpt_cache;
	
	$query = mysql_real_escape_string($query);
	$excerpt = mysql_real_escape_string($excerpt);

	$wpdb->query("INSERT IGNORE INTO $relevanssi_excerpt_cache (post, query, excerpt) VALUES ($post, '$query', '$excerpt')");
}

function relevanssi_fetch_hits($param) {
	global $wpdb, $relevanssi_cache;

	$time = get_option('relevanssi_cache_seconds', 172800);

	$hits = $wpdb->get_var("SELECT hits FROM $relevanssi_cache WHERE param = '$param' AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(tstamp) < $time");
	
	if ($hits) {
		return unserialize($hits);
	}
	else {
		return null;
	}
}

function relevanssi_store_hits($param, $data) {
	global $wpdb, $relevanssi_cache;

	$param = mysql_real_escape_string($param);
	$data = mysql_real_escape_string($data);
	$wpdb->query("INSERT IGNORE INTO $relevanssi_cache (param, hits) VALUES ('$param', '$data')");
}

// thanks to rvencu
function relevanssi_wpml_filter($data) {
	if (function_exists('icl_object_id')) {
		$filtered_hits = array();
    	foreach ($data[0] as $hit) {
        	if ($hit->ID == icl_object_id($hit->ID, $hit->post_type,false,ICL_LANGUAGE_CODE))
                $filtered_hits[] = $hit;
	    }
	    return array($filtered_hits, $data[1]);
	}
	else {
		return $data;
	}
}

/**
 * Function by Matthew Hood http://my.php.net/manual/en/function.sort.php#75036
 */
function objectSort(&$data, $key, $dir = 'desc') {
    for ($i = count($data) - 1; $i >= 0; $i--) {
		$swapped = false;
      	for ($j = 0; $j < $i; $j++) {
      		if ('asc' == $dir) {
	           	if ($data[$j]->$key > $data[$j + 1]->$key) { 
    		        $tmp = $data[$j];
        	        $data[$j] = $data[$j + 1];
            	    $data[$j + 1] = $tmp;
                	$swapped = true;
	           	}
	        }
			else {
	           	if ($data[$j]->$key < $data[$j + 1]->$key) { 
    		        $tmp = $data[$j];
        	        $data[$j] = $data[$j + 1];
            	    $data[$j + 1] = $tmp;
                	$swapped = true;
	           	}
			}
    	}
	    if (!$swapped) return;
    }
}

function relevanssi_show_matches($data, $hit) {
	isset($data['body_matches'][$hit]) ? $body = $data['body_matches'][$hit] : $body = "";
	isset($data['title_matches'][$hit]) ? $title = $data['title_matches'][$hit] : $title = "";
	isset($data['tag_matches'][$hit]) ? $tag = $data['tag_matches'][$hit] : $tag = "";
	isset($data['comment_matches'][$hit]) ? $comment = $data['comment_matches'][$hit] : $comment = "";
	isset($data['scores'][$hit]) ? $score = round($data['scores'][$hit], 2) : $score = 0;
	isset($data['term_hits'][$hit]) ? $term_hits_a = $data['term_hits'][$hit] : $term_hits_a = array();
	arsort($term_hits_a);
	$term_hits = "";
	$total_hits = 0;
	foreach ($term_hits_a as $term => $hits) {
		$term_hits .= " $term: $hits";
		$total_hits += $hits;
	}
	
	$text = get_option('relevanssi_show_matches_text');
	$replace_these = array("%body%", "%title%", "%tags%", "%comments%", "%score%", "%terms%", "%total%");
	$replacements = array($body, $title, $tag, $comment, $score, $term_hits, $total_hits);
	
	$result = " " . str_replace($replace_these, $replacements, $text);
	
	return $result;
}

function relevanssi_update_log($query, $hits) {
	global $wpdb, $log_table;
	
	if (get_option('relevanssi_omit_from_logs')) {
		$user = wp_get_current_user();
		$omit = explode(",", get_option('relevanssi_omit_from_logs'));
		if (in_array($user->ID, $omit)) return;
	}
	
	$q = $wpdb->prepare("INSERT INTO $log_table (query, hits) VALUES (%s, %d)", $query, intval($hits));
	$wpdb->query($q);
}

// This function is from Kenny Katzgrau
function relevanssi_getLimit($limit) {
	global $wpSearch_low;
	global $wpSearch_high;

	if(is_search()) {
		$temp 			= str_replace("LIMIT", "", $limit);
		$temp 			= explode(",", $temp);
		$wpSearch_low 	= intval($temp[0]);
		$wpSearch_high 	= intval($wpSearch_low + intval($temp[1]) - 1);
	}
	
	return $limit;
}

// This is my own magic working.
function relevanssi_search($q, $cat = NULL, $excat = NULL, $expost = NULL, $post_type = NULL, $taxonomy = NULL, $taxonomy_term = NULL, $operator = "AND") {
	global $relevanssi_table, $wpdb;

	$values_to_filter = array(
		'q' => $q,
		'cat' => $cat,
		'excat' => $excat,
		'expost' => $expost,
		'post_type' => $post_type,
		'taxonomy' => $taxonomy,
		'taxonomy_term' => $taxonomy_term,
		'operator' => $operator,
		);
	$filtered_values = apply_filters( 'relevanssi_search_filters', $values_to_filter );
	$q               = $filtered_values['q'];
	$cat             = $filtered_values['cat'];
	$excat           = $filtered_values['excat'];
	$expost          = $filtered_values['expost'];
	$post_type       = $filtered_values['post_type'];
	$taxonomy        = $filtered_values['taxonomy'];
	$taxonomy_term   = $filtered_values['taxonomy_term'];
	$operator        = $filtered_values['operator'];

	$hits = array();
	
	$custom_cat = NULL;
	
	$o_cat = $cat;
	$o_excat = $excat;
	$o_expost = $expost;
	$o_post_type = $post_type;
	$o_taxonomy = $taxonomy;
	$o_taxonomy_term = $taxonomy_term;
	
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

	if (isset($taxonomy)) {
		$term_tax_id = null;
		$term_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->terms
			JOIN $wpdb->term_taxonomy USING(`term_id`)
			WHERE `slug` LIKE '$taxonomy_term' AND `taxonomy` LIKE '$taxonomy'");
		if ($term_tax_id) {
			$taxonomy = $term_tax_id;
		} else {
			$taxonomy = null;
		}
	}

	if (!$post_type && get_option('relevanssi_respect_exclude') == 'on') {
		if (function_exists('get_post_types')) {
			$post_type = implode(',', get_post_types(array('exclude_from_search' => false)));
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
	$term_hits = array();

	$fuzzy = get_option('relevanssi_fuzzy');

	$query_restrictions = "";
	if ($expost) { //added by OdditY
		$query_restrictions .= $postex;
	}
	if ($cat) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($cat))";
	}
	if ($excat) {
		$query_restrictions .= " AND doc NOT IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($excat))";
	}
	if ($post_type) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
			WHERE post_type IN ($post_type))";
	}
	if ($phrases) {
		$query_restrictions .= " AND doc IN ($phrases)";
	}
	if ($custom_cat) {
		$query_restrictions .= " AND doc IN ($custom_cat)";
	}
	if ($taxonomy) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			WHERE term_taxonomy_id IN ($taxonomy))";
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

	foreach ($terms as $term) {
		$term = $wpdb->escape(like_escape($term));
		
		if ("always" == $fuzzy) {
			$term_cond = "(term LIKE '%$term' OR term LIKE '$term%') ";
		}
		else {
			$term_cond = " term = '$term' ";
		}
		
		$query = "SELECT doc, term, tf, title FROM $relevanssi_table WHERE $term_cond $query_restrictions";

		$matches = $wpdb->get_results($query);
		if (count($matches) < 1 && "sometimes" == $fuzzy) {
			$query = "SELECT doc, term, tf, title FROM $relevanssi_table
			WHERE (term LIKE '$term%' OR term LIKE '%$term') $query_restrictions";
			
			$matches = $wpdb->get_results($query);
		}
		
		$total_hits += count($matches);

		$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE $term_cond $query_restrictions";

		$df = $wpdb->get_var($query);

		if ($df < 1 && "sometimes" == $fuzzy) {
			$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table
				WHERE (term LIKE '%$term' OR term LIKE '$term%') $query_restrictions";
		
			$df = $wpdb->get_var($query);
		}
		
		$title_boost = floatval(get_option('relevanssi_title_boost'));
		$tag_boost = floatval(get_option('relevanssi_tag_boost'));
		$comment_boost = floatval(get_option('relevanssi_comment_boost'));
		
		$idf = log($D / (1 + $df));
//		$doc_terms_temp = array();
		foreach ($matches as $match) {
			$weight = $match->tf * $idf;

			if (!isset($term_hits[$match->doc][$term])) {
				$term_hits[$match->doc][$term] = 0;
			}
			
			switch ($match->title) {
				case "1":
					$weight = $weight * $title_boost;
					isset($title_matches[$match->doc]) ? $title_matches[$match->doc] += $match->tf : $title_matches[$match->doc] = $match->tf;
					$term_hits[$match->doc][$term] += $match->tf;
					break;
				case "2":
					$weight = $weight * $tag_boost;
					isset($tag_matches[$match->doc]) ? $tag_matches[$match->doc] += $match->tf : $tag_matches[$match->doc] = $match->tf;
					$term_hits[$match->doc][$term] += $match->tf;
					break;
				case "3":
					$weight = $weight * $comment_boost;
					isset($comment_matches[$match->doc]) ? $comment_matches[$match->doc] += $match->tf : $comment_matches[$match->doc] = $match->tf;
					$term_hits[$match->doc][$term] += $match->tf;
					break;
				default:
					isset($body_matches[$match->doc]) ? $body_matches[$match->doc] += $match->tf : $body_matches[$match->doc] = $match->tf;
					$term_hits[$match->doc][$term] += $match->tf;
			}

			$doc_terms[$match->doc][$term] = true; // count how many terms are matched to a doc
			isset($doc_weight[$match->doc]) ? $doc_weight[$match->doc] += $weight : $doc_weight[$match->doc] = $weight;
			isset($scores[$match->doc]) ? $scores[$match->doc] += $weight : $scores[$match->doc] = $weight;
		}
	}

	$total_terms = count($terms);

	if (isset($doc_weight) && count($doc_weight) > 0) {
		arsort($doc_weight);
		$i = 0;
		foreach ($doc_weight as $doc => $weight) {
			if (count($doc_terms[$doc]) < $total_terms && $operator == "AND") {
				// AND operator in action:
				// doc didn't match all terms, so it's discarded
				continue;
			}
			$status = get_post_status($doc);
			$post_ok = true;
			if ('private' == $status) {
				$post_ok = false;

				if (function_exists('awp_user_can')) {
					// Role-Scoper
					$current_user = wp_get_current_user();
					$post_ok = awp_user_can('read_post', $doc, $current_user->ID);
				}
				else {
					// Basic WordPress version
					$type = get_post_type($doc);
					$cap = 'read_private_' . $type . 's';
					if (current_user_can($cap)) {
						$post_ok = true;
					}
				}
			}
			if ($post_ok) $hits[intval($i++)] = get_post($doc);
		}
	}

	if (count($hits) < 1) {
		if ($operator == "AND" AND get_option('relevanssi_disable_or_fallback') != 'on') {
			$return = relevanssi_search($q, $o_cat, $o_excat, $o_expost, $o_post_type, $o_taxonomy, $o_taxonomy_term, "OR");
			extract($return);
		}
	}

	global $wp;	
	isset($wp->query_vars["orderby"]) ? $orderby = $wp->query_vars["orderby"] : $orderby = 'relevance';
	isset($wp->query_vars["order"]) ? $order = $wp->query_vars["order"] : $order = 'desc';
	if ($orderby != 'relevance')
		objectSort($hits, $orderby, $order);

	$return = array('hits' => $hits, 'body_matches' => $body_matches, 'title_matches' => $title_matches,
		'tag_matches' => $tag_matches, 'comment_matches' => $comment_matches, 'scores' => $scores,
		'term_hits' => $term_hits);

	return $return;
}

/**
 * Extracts phrases from search query
 * Returns an array of phrases
 */
function relevanssi_extract_phrases($q) {
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
		$phrase = mb_substr($q, $start + 1, $end - $start - 1);
		
		$phrases[] = $phrase;
		$pos = $end;
	}
	return $phrases;
}

/* If no phrase hits are made, this function returns false
 * If phrase matches are found, the function presents a comma-separated list of doc id's.
 * If phrase matches are found, but no matching documents, function returns -1.
 */
function relevanssi_recognize_phrases($q) {
	global $wpdb;
	
	$phrases = relevanssi_extract_phrases($q);
	
	if (count($phrases) > 0) {
		$phrase_matches = array();
		foreach ($phrases as $phrase) {
			$phrase = $wpdb->escape($phrase);
			$query = "SELECT ID,post_content,post_title FROM $wpdb->posts 
				WHERE (post_content LIKE '%$phrase%' OR post_title LIKE '%$phrase%')
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

			$query = "SELECT ID FROM $wpdb->posts as p, $wpdb->term_relationships as r, $wpdb->term_taxonomy as s, $wpdb->terms as t
				WHERE r.term_taxonomy_id = s.term_taxonomy_id AND s.term_id = t.term_id AND p.ID = r.object_id
				AND t.name LIKE '%$phrase%' AND p.post_status = 'publish'";

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
	$remove_stopwords = false;
	$terms = relevanssi_tokenize($query, $remove_stopwords);

	$content = apply_filters('the_content', $post->post_content);
	
	$content = relevanssi_strip_invisibles($content); // removes <script>, <embed> &c with content
	if ('on' == get_option('relevanssi_expand_shortcodes')) {
		if (function_exists("do_shortcode")) {
			$content = do_shortcode($content);
		}
	}
	else {
		if (function_exists("strip_shortcodes")) {
			$content = strip_shortcodes($content);
		}
	}
	$content = strip_tags($content); // this removes the tags, but leaves the content
	
	$content = ereg_replace("/\n\r|\r\n|\n|\r/", " ", $content);
	
	$excerpt_data = relevanssi_create_excerpt($content, $terms);
	
	if (get_option("relevanssi_index_comments") != 'none') {
		$comment_content = relevanssi_get_comments($post->ID);
		$comment_excerpts = relevanssi_create_excerpt($comment_content, $terms);
		if ($comment_excerpts[1] > $excerpt_data[1]) {
			$excerpt_data = $comment_excerpts;
		}
	}

	if (get_option("relevanssi_index_excerpt") != 'none') {
		$excerpt_content = $post->post_excerpt;
		$excerpt_excerpts = relevanssi_create_excerpt($excerpt_content, $terms);
		if ($excerpt_excerpts[1] > $excerpt_data[1]) {
			$excerpt_data = $excerpt_excerpts;
		}
	}
	
	$excerpt = $excerpt_data[0];
	$start = $excerpt_data[2];
	
	$content = apply_filters('get_the_excerpt', $content);
	$content = apply_filters('the_excerpt', $content);	

	$highlight = get_option('relevanssi_highlight');
	if ("none" != $highlight) {
		if (!is_admin()) {
			$excerpt = relevanssi_highlight_terms($excerpt, $query);
		}
	}
	
	if (!$start) {
		$excerpt = "..." . $excerpt;
		// do not add three dots to the beginning of the post
	}
	
	$excerpt = $excerpt . "...";

	return $excerpt;
}

/**
 * Creates an excerpt from content.
 *
 * @return array - element 0 is the excerpt, element 1 the number of term hits, element 2 is
 * true, if the excerpt is from the start of the content.
 */
function relevanssi_create_excerpt($content, $terms) {
	$excerpt_length = get_option("relevanssi_excerpt_length");
	$type = get_option("relevanssi_excerpt_type");

	$best_excerpt_term_hits = -1;
	$excerpt = "";

	$content = " $content";	
	$start = false;
	if ("chars" == $type) {
		$term_hits = 0;
		foreach (array_keys($terms) as $term) {
			$term = " $term";
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
				$term_hits++;
				if ($term_hits > $best_excerpt_term_hits) {
					$best_excerpt_term_hits = $term_hits;
					if ($pos + strlen($term) < $excerpt_length) {
						$excerpt = mb_substr($content, 0, $excerpt_length);
						$start = true;
					}
					else {
						$half = floor($excerpt_length/2);
						$pos = $pos - $half;
						$excerpt = mb_substr($content, $pos, $excerpt_length);
					}
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

			$excerpt_slice = " $excerpt_slice";
			$term_hits = 0;
			foreach (array_keys($terms) as $term) {
				$term = " $term";
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

if (get_option('relevanssi_highlight_docs', 'off') != 'off') {
	add_filter('the_content', 'relevanssi_highlight_in_docs', 11);
	add_filter('the_title', 'relevanssi_highlight_in_docs');
}
if (get_option('relevanssi_highlight_comments', 'off') != 'off') {
	add_filter('comment_text', 'relevanssi_highlight_in_docs', 11);
}
function relevanssi_highlight_in_docs($content) {
	if (is_singular()) {
		$referrer = preg_replace('@(http|https)://@', '', stripslashes(urldecode($_SERVER['HTTP_REFERER'])));
		$args     = explode('?', $referrer);
		$query    = array();
	
		if ( count( $args ) > 1 )
			parse_str( $args[1], $query );
	
		if (substr($referrer, 0, strlen($_SERVER['SERVER_NAME'])) == $_SERVER['SERVER_NAME'] && (isset($query['s']) || strpos($referrer, '/search/') !== false)) {
			// Local search
			$content = relevanssi_highlight_terms($content, $query['s']);
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
	
	$start_emp_token = "*[/";
	$end_emp_token = "\]*";
	mb_internal_encoding("UTF-8");
	
	$terms = array_keys(relevanssi_tokenize($query, false));

	$phrases = relevanssi_extract_phrases(stripslashes($query));
	
	$non_phrase_terms = array();
	foreach ($phrases as $phrase) {
		$phrase_terms = array_keys(relevanssi_tokenize($phrase, false));
		foreach ($terms as $term) {
			if (!in_array($term, $phrase_terms)) {
				$non_phrase_terms[] = $term;
			}
		}
		$terms = $non_phrase_terms;
		$terms[] = $phrase;
	}

	usort($terms, 'relevanssi_strlen_sort');

	foreach ($terms as $term) {
		$excerpt = preg_replace("/(\b$term|$term\b)(?!([^<]+)?>)/iu", $start_emp_token . '\\1' . $end_emp_token, $excerpt);
		// thanks to http://pureform.wordpress.com/2008/01/04/matching-a-word-characters-outside-of-html-tags/
	}

	$excerpt = relevanssi_remove_nested_highlights($excerpt, $start_emp_token, $end_emp_token);

	$excerpt = str_replace($start_emp_token, $start_emp, $excerpt);
	$excerpt = str_replace($end_emp_token, $end_emp, $excerpt);
	$excerpt = str_replace($end_emp . $start_emp, "", $excerpt);
	if (function_exists('mb_ereg_replace')) {
		$pattern = $end_emp . '\s*' . $start_emp;
		$excerpt = mb_ereg_replace($pattern, " ", $excerpt);
	}

	return $excerpt;
}

function relevanssi_remove_nested_highlights($s, $a, $b) {
	$offset = 0;
	$string = "";
	$bits = explode($a, $s);
	$new_bits = array($bits[0]);
	for ($i = 1; $i < count($bits); $i++) {
		if ($bits[$i] == '') continue;
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
		$new_bits[] = $bits[$i];
	}
	$whole = implode($a, $new_bits);
	
	return $whole;
}

function relevanssi_strlen_sort($a, $b) {
	return strlen($b) - strlen($a);
}

function relevanssi_get_comments($postID) {	
	global $wpdb;

	$comtype = get_option("relevanssi_index_comments");
	$restriction = "";
	$comment_string = "";
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
		$sql = "SELECT 	comment_content, comment_author
				FROM 	$wpdb->comments
				WHERE 	comment_post_ID = '$postID'
				AND 	comment_approved = '1' 
				".$restriction."
				LIMIT 	$from, $to";		
		$comments = $wpdb->get_results($sql);
		if (sizeof($comments) == 0) break;
		foreach($comments as $comment) {
			$comment_string .= $comment->comment_author . ' ' . $comment->comment_content . ' ';
		}
		$from += $to;
	}
	return $comment_string;
}


function relevanssi_build_index($extend = false) {
	global $wpdb, $relevanssi_table;
	set_time_limit(0);
	
	get_option('relevanssi_index_attachments') == 'on' ? $attachments = '' : $attachments = "AND post.post_type!='attachment'";
	
	$type = get_option("relevanssi_index_type");
	$allow_custom_types = true;
	switch ($type) {
		case "posts":
			$restriction = " AND (post.post_type = 'post'"; // add table alias to column for modified query - modified by renaissancehack
			break;
		case "pages":
			$restriction = " AND (post.post_type = 'page'"; // add table alias to column for modified query - modified by renaissancehack
			break;
		case "public":
			if (function_exists('get_post_types')) {
				$custom_types = implode(',', get_post_types(array('exclude_from_search' => false)));
				$allow_custom_types = false;
			}
			$restriction = "";
			break;
		case "both": 								// really should be "everything"
			$restriction = "";
			$allow_custom_types = true;
			break;
		default:
			$restriction = "";
	}

	$negative_restriction = "";
	
	if ($allow_custom_types) $custom_types = get_option("relevanssi_custom_types");
	
	if ("" != $custom_types) {
		$types = explode(",", $custom_types);
		if ("" == $restriction) {
			$restriction = " AND (";
		}
		else {
			$restriction .= " OR ";
		}
		$i=0;
		foreach ($types as $type) {
			$type = trim($type);
			if (substr($type, 0, 1) == '-') {
				$type = trim($type, '-');
				$negative_restriction .= "AND post.post_type != '$type'";
				$i--;
			}
			else {
				if (0 == $i) {
					$restriction .= " post.post_type = '$type'";  // add table alias to column for modified query - modified by renaissancehack
				}
				else {
					$restriction .= " OR post.post_type = '$type'";  // add table alias to column for modified query - modified by renaissancehack
				}
			}
			$i++;
		}
		$restriction .= ")";
		if ($restriction == " AND ()") $restriction = "";
	}
	elseif ("" != $restriction) {
		$restriction .= ")";
	}

	$n = 0;
	
	if (!$extend) {
		// truncate table first
		$wpdb->query("TRUNCATE TABLE $relevanssi_table");
// BEGIN modified by renaissancehack
//  modified query to get child records that inherit their post_status
        $q = "SELECT *,parent.post_status as post_status
		FROM $wpdb->posts parent, $wpdb->posts post WHERE
        (parent.post_status='publish' OR parent.post_status='private')
        AND (
            (post.post_status='inherit'
            AND post.post_parent=parent.ID)
            OR
            (parent.ID=post.ID)
        )
		AND post.post_type!='nav_menu_item' AND post.post_type!='revision' $attachments $restriction $negative_restriction";
// END modified by renaissancehack
		update_option('relevanssi_index', '');
	}
	else {
		// extending, so no truncate and skip the posts already in the index
		$limit = get_option('relevanssi_index_limit', 200);
		if ($limit > 0) {
			$limit = " LIMIT $limit";
		}
// BEGIN modified by renaissancehack
//  modified query to get child records that inherit their post_status
        $q = "SELECT *,parent.post_status as post_status
		FROM $wpdb->posts parent, $wpdb->posts post WHERE
        (parent.post_status='publish' OR parent.post_status='private')
        AND (
            (post.post_status='inherit'
            AND post.post_parent=parent.ID)
            OR
            (parent.ID=post.ID)
        )
        AND post.post_type!='nav_menu_item' AND post.post_type!='revision' $attachments
		AND post.ID NOT IN (SELECT DISTINCT(doc) FROM $relevanssi_table) $restriction $limit";
// END modified by renaissancehack
	}

	$custom_fields = relevanssi_get_custom_fields();

	$content = $wpdb->get_results($q);
	
	foreach ($content as $post) {
		$n += relevanssi_index_doc($post, false, $custom_fields);
		// n calculates the number of insert queries
	}
	
	echo '<div id="message" class="updated fade"><p>' . __("Indexing complete!", "relevanssi") . '</p></div>';
	update_option('relevanssi_indexed', 'done');
}

function relevanssi_remove_doc($id) {
	global $wpdb, $relevanssi_table;
	
	$q = "DELETE FROM $relevanssi_table WHERE doc=$id";
	$wpdb->query($q);
}

// BEGIN modified by renaissancehack
//  recieve $post argument as $indexpost, so we can make it the $post global.  This will allow shortcodes
//  that need to know what post is calling them to access $post->ID
function relevanssi_index_doc($indexpost, $remove_first = false, $custom_fields = false) {
	global $wpdb, $relevanssi_table, $post;
    $post = $indexpost;
// END modified by renaissancehack
	if (!is_object($post)) {
// BEGIN modified by renaissancehack
//  modified query to get child records that inherit their post_status
		get_option('relevanssi_index_attachments') == 'on' ? $attachments = '' : $attachments = "AND post.post_type!='attachment'";
		$post = $wpdb->get_row("SELECT *,parent.post_status
			FROM $wpdb->posts parent, $wpdb->posts post WHERE
            (parent.post_status='publish' OR parent.post_status='private')
			AND post.ID=$post
            AND (
                (post.post_status='inherit'
                AND post.post_parent=parent.ID)
                OR
                (parent.ID=post.ID)
            )
            AND post.post_type!='nav_menu_item' AND post.post_type!='revision' $attachments");
// END modified by renaissancehack
		if (!$post) {
			// the post isn't public
			return;
		}
	}
	
	$index_type = get_option('relevanssi_index_type');
	$custom_types = explode(",", get_option('relevanssi_custom_types'));
	
	$index_this_post = false;
	switch ($index_type) {
		case 'posts':
			if ("post" == $post->post_type) $index_this_post = true;
			if (in_array($post->post_type, $custom_types)) $index_this_post = true;
			break;
		case 'pages':
			if ("page" == $post->post_type) $index_this_post = true;
			if (in_array($post->post_type, $custom_types)) $index_this_post = true;
			break;
		case 'public';
			if (function_exists('get_post_types')) {
				$public_types = get_post_types(array('exclude_from_search' => false));
				if (in_array($post->post_type, $public_types)) $index_this_post = true;
			}
			else {
				$index_this_post = true;
			}
			break;
		case 'both':
			$index_this_post = true;
			break;
	}
	
	if ($remove_first) {
		// we are updating a post, so remove the old stuff first
		relevanssi_remove_doc($post->ID);
		relevanssi_purge_excerpt_cache($post->ID);
	}

	// This needs to be here, after the call to relevanssi_remove_doc(), because otherwise
	// a post that's in the index but shouldn't be there won't get removed. A remote chance,
	// I mean who ever flips exclude_from_search between true and false once it's set, but
	// I'd like to cover all bases.
	if (!$index_this_post) return;

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


	$taxonomies = array();
	//Added by OdditY - INDEX TAGs of the POST ->
	if ("on" == get_option("relevanssi_include_tags")) {
		array_push($taxonomies, "post_tag");
	} // Added by OdditY END <- 

	$custom_taxos = get_option("relevanssi_custom_taxonomies");
	if ("" != $custom_taxos) {
		$cts = explode(",", $custom_taxos);
		foreach ($cts as $taxon) {
			$taxon = trim($taxon);
			array_push($taxonomies, $taxon);
		}
	}

	// Then process all taxonomies, if any.
	foreach ($taxonomies as $taxonomy) {
		$n += index_taxonomy_terms($post, $taxonomy);
	}
	
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

	// index author
	if ("on" == get_option("relevanssi_index_author")) {
		$auth = $post->post_author;
		$display_name = $wpdb->get_var("SELECT display_name FROM $wpdb->users WHERE ID=$auth");
		$names = relevanssi_tokenize($display_name, false);
		foreach($names as $name => $count) {
			$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
				VALUES ($post->ID, '$name', $count, 5)");
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

	if (isset($post->post_excerpt) && ("on" == get_option("relevanssi_index_excerpt") || "attachment" == $post->post_type)) { // include excerpt for attachments which use post_excerpt for captions - modified by renaissancehack
		$post->post_content .= ' ' . $post->post_excerpt;
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
	
	$min_word_length = get_option('relevanssi_min_word_length', 3);
	
	if (count($contents) > 0) {
		foreach ($contents as $content => $count) {
			if (strlen($content) < $min_word_length) continue;
			$n++;
			$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
			VALUES ($post->ID, '$content', $count, 0)");
		}
	}
	
	return $n;
}

/**
 * Index taxonomy terms for given post and given taxonomy.
 *
 * @since 1.8
 * @param object $post Post object.
 * @param string $taxonomy Taxonomy name.
 * @return int Number of terms indexed.
 */
function index_taxonomy_terms($post = null, $taxonomy = "") {
	global $wpdb, $relevanssi_table;
	
	$n = 0;

	if (null == $post) return 0;
	if ("" == $taxonomy) return 0;
	
	$ptagobj = get_the_terms($post->ID, $taxonomy);
	if ($ptagobj !== FALSE) { 
		$tagstr = "";
		foreach ($ptagobj as $ptag) {
			if (is_object($ptag)) {
				$tagstr .= $ptag->name . ' ';
			}
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

	$tokens = array();

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
		$a = stripslashes($a);

		$a = str_replace('&#8217;', '', $a);
		$a = str_replace("'", '', $a);
		$a = str_replace("´", '', $a);
		$a = str_replace("’", '', $a);
		$a = str_replace("&shy;", '', $a);

		$a = str_replace("—", " ", $a);
        $a = preg_replace('/[[:punct:]]+/u', ' ', $a);
		$a = str_replace("”", " ", $a);

        $a = preg_replace('/[[:space:]]+/', ' ', $a);
		$a = trim($a);
        return $a;
}

function relevanssi_shortcode($atts, $content, $name) {
	global $wpdb;

	extract(shortcode_atts(array('term' => false, 'phrase' => 'not'), $atts));
	
	if ($term != false) {
		$term = urlencode(strtolower($term));
	}
	else {
		$term = urlencode(strip_tags(strtolower($content)));
	}
	
	if ($phrase != 'not') {
		$term = '%22' . $term . '%22';	
	}
	
	$link = get_bloginfo('url') . "/?s=$term";
	
	$pre  = "<a href='$link'>";
	$post = "</a>";

	return $pre . do_shortcode($content) . $post;
}

add_shortcode('search', 'relevanssi_shortcode');


/*****
 * Interface functions
 */

function relevanssi_options() {
	$options_txt = __('Relevanssi Search Options', 'relevanssi');

	printf("<div class='wrap'><h2>%s</h2>", $options_txt);
	if (!empty($_POST) && check_admin_referer('relevanssi_nonce')) {
		if (isset($_REQUEST['submit'])) {
			update_relevanssi_options();
		}
	
		if (isset($_REQUEST['index'])) {
			update_relevanssi_options();
			relevanssi_build_index();
		}
	
		if (isset($_REQUEST['index_extend'])) {
			update_relevanssi_options();
			relevanssi_build_index(true);
		}
		
		if (isset($_REQUEST['search'])) {
			relevanssi_search($_REQUEST['q']);
		}
		
		if (isset($_REQUEST['uninstall'])) {
			relevanssi_uninstall();
		}
		
		if (isset($_REQUEST['dowhat'])) {
			if ("add_stopword" == $_REQUEST['dowhat']) {
				if (isset($_REQUEST['term'])) {
					relevanssi_add_stopword($_REQUEST['term']);
				}
			}
		}
	
		if (isset($_REQUEST['addstopword'])) {
			relevanssi_add_stopword($_REQUEST['addstopword']);
		}
		
		if (isset($_REQUEST['removestopword'])) {
			relevanssi_remove_stopword($_REQUEST['removestopword']);
		}
	
		if (isset($_REQUEST['removeallstopwords'])) {
			relevanssi_remove_all_stopwords();
		}
	}
	relevanssi_options_form();
	
	relevanssi_common_words();
	
	echo "<div style='clear:both'></div>";
	
	echo "</div>";
}

function relevanssi_search_stats() {
	$options_txt = __('Relevanssi User Searches', 'relevanssi');

	if (isset($_REQUEST['relevanssi_reset'])) {
		if (isset($_REQUEST['relevanssi_reset_code'])) {
			if ($_REQUEST['relevanssi_reset_code'] == 'reset') {
				relevanssi_truncate_logs();
			}
		}
	}

	wp_enqueue_style('dashboard');
	wp_print_styles('dashboard');
	wp_enqueue_script('dashboard');
	wp_print_scripts('dashboard');

	printf("<div class='wrap'><h2>%s</h2>", $options_txt);

	echo '<div class="postbox-container" style="width:70%;">';


	if ('on' == get_option('relevanssi_log_queries')) {
		relevanssi_query_log();
	}
	else {
		echo "<p>Enable query logging to see stats here.</p>";
	}
	
	echo "</div>";
	
	relevanssi_sidebar();
}

function relevanssi_truncate_logs() {
	global $wpdb, $log_table;
	
	$query = "TRUNCATE $log_table";
	mysql_query($query);
	
	echo "<div id='relevanssi-warning' class='updated fade'>Logs clear!</div>";
}

function update_relevanssi_options() {
	if (isset($_REQUEST['relevanssi_title_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_title_boost']);
		update_option('relevanssi_title_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_tag_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_tag_boost']);
		update_option('relevanssi_tag_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_comment_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_comment_boost']);
		update_option('relevanssi_comment_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_min_word_length'])) {
		$value = intval($_REQUEST['relevanssi_min_word_length']);
		if ($value == 0) $value = 2;
		update_option('relevanssi_min_word_length', $value);
	}

	if (isset($_REQUEST['relevanssi_cache_seconds'])) {
		$value = intval($_REQUEST['relevanssi_cache_seconds']);
		if ($value == 0) $value = 86400;
		update_option('relevanssi_cache_seconds', $value);
	}
	
	if (!isset($_REQUEST['relevanssi_admin_search'])) {
		$_REQUEST['relevanssi_admin_search'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_excerpts'])) {
		$_REQUEST['relevanssi_excerpts'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_show_matches'])) {
		$_REQUEST['relevanssi_show_matches'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_inccats'])) {
		$_REQUEST['relevanssi_inccats'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_inctags'])) {
		$_REQUEST['relevanssi_inctags'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_index_author'])) {
		$_REQUEST['relevanssi_index_author'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_index_excerpt'])) {
		$_REQUEST['relevanssi_index_excerpt'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_log_queries'])) {
		$_REQUEST['relevanssi_log_queries'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_disable_or_fallback'])) {
		$_REQUEST['relevanssi_disable_or_fallback'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_index_attachments'])) {
		$_REQUEST['relevanssi_index_attachments'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_hilite_title'])) {
		$_REQUEST['relevanssi_hilite_title'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_highlight_docs'])) {
		$_REQUEST['relevanssi_highlight_docs'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_highlight_comments'])) {
		$_REQUEST['relevanssi_highlight_comments'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_expand_shortcodes'])) {
		$_REQUEST['relevanssi_expand_shortcodes'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_enable_cache'])) {
		$_REQUEST['relevanssi_enable_cache'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_respect_exclude'])) {
		$_REQUEST['relevanssi_respect_exclude'] = "off";
	}

	if (isset($_REQUEST['relevanssi_excerpt_length'])) {
		$value = intval($_REQUEST['relevanssi_excerpt_length']);
		if ($value != 0) {
			update_option('relevanssi_excerpt_length', $value);
		}
	}
	
	if (isset($_REQUEST['relevanssi_synonyms'])) {
		$linefeeds = array("\r\n", "\n", "\r");
		$value = str_replace($linefeeds, ";", $_REQUEST['relevanssi_synonyms']);
		$value = stripslashes($value);
		update_option('relevanssi_synonyms', $value);
	}

	if (isset($_REQUEST['relevanssi_show_matches'])) update_option('relevanssi_show_matches', $_REQUEST['relevanssi_show_matches']);
	if (isset($_REQUEST['relevanssi_show_matches_text'])) {
		$value = $_REQUEST['relevanssi_show_matches_text'];
		$value = str_replace('"', "'", $value);
		update_option('relevanssi_show_matches_text', $value);
	}

	if (isset($_REQUEST['relevanssi_admin_search'])) update_option('relevanssi_admin_search', $_REQUEST['relevanssi_admin_search']);
	if (isset($_REQUEST['relevanssi_excerpts'])) update_option('relevanssi_excerpts', $_REQUEST['relevanssi_excerpts']);	
	if (isset($_REQUEST['relevanssi_excerpt_type'])) update_option('relevanssi_excerpt_type', $_REQUEST['relevanssi_excerpt_type']);	
	if (isset($_REQUEST['relevanssi_log_queries'])) update_option('relevanssi_log_queries', $_REQUEST['relevanssi_log_queries']);	
	if (isset($_REQUEST['relevanssi_highlight'])) update_option('relevanssi_highlight', $_REQUEST['relevanssi_highlight']);
	if (isset($_REQUEST['relevanssi_highlight_docs'])) update_option('relevanssi_highlight_docs', $_REQUEST['relevanssi_highlight_docs']);
	if (isset($_REQUEST['relevanssi_highlight_comments'])) update_option('relevanssi_highlight_comments', $_REQUEST['relevanssi_highlight_comments']);
	if (isset($_REQUEST['relevanssi_txt_col'])) update_option('relevanssi_txt_col', $_REQUEST['relevanssi_txt_col']);
	if (isset($_REQUEST['relevanssi_bg_col'])) update_option('relevanssi_bg_col', $_REQUEST['relevanssi_bg_col']);
	if (isset($_REQUEST['relevanssi_css'])) update_option('relevanssi_css', $_REQUEST['relevanssi_css']);
	if (isset($_REQUEST['relevanssi_class'])) update_option('relevanssi_class', $_REQUEST['relevanssi_class']);
	if (isset($_REQUEST['relevanssi_cat'])) update_option('relevanssi_cat', $_REQUEST['relevanssi_cat']);
	if (isset($_REQUEST['relevanssi_excat'])) update_option('relevanssi_excat', $_REQUEST['relevanssi_excat']);
	if (isset($_REQUEST['relevanssi_index_type'])) update_option('relevanssi_index_type', $_REQUEST['relevanssi_index_type']);
	if (isset($_REQUEST['relevanssi_custom_types'])) update_option('relevanssi_custom_types', $_REQUEST['relevanssi_custom_types']);
	if (isset($_REQUEST['relevanssi_custom_taxonomies'])) update_option('relevanssi_custom_taxonomies', $_REQUEST['relevanssi_custom_taxonomies']);
	if (isset($_REQUEST['relevanssi_index_fields'])) update_option('relevanssi_index_fields', $_REQUEST['relevanssi_index_fields']);
	if (isset($_REQUEST['relevanssi_expst'])) update_option('relevanssi_exclude_posts', $_REQUEST['relevanssi_expst']); 			//added by OdditY
	if (isset($_REQUEST['relevanssi_inctags'])) update_option('relevanssi_include_tags', $_REQUEST['relevanssi_inctags']); 			//added by OdditY	
	if (isset($_REQUEST['relevanssi_hilite_title'])) update_option('relevanssi_hilite_title', $_REQUEST['relevanssi_hilite_title']); 	//added by OdditY	
	if (isset($_REQUEST['relevanssi_index_comments'])) update_option('relevanssi_index_comments', $_REQUEST['relevanssi_index_comments']); //added by OdditY	
	if (isset($_REQUEST['relevanssi_inccats'])) update_option('relevanssi_include_cats', $_REQUEST['relevanssi_inccats']);
	if (isset($_REQUEST['relevanssi_index_author'])) update_option('relevanssi_index_author', $_REQUEST['relevanssi_index_author']);
	if (isset($_REQUEST['relevanssi_index_excerpt'])) update_option('relevanssi_index_excerpt', $_REQUEST['relevanssi_index_excerpt']);
	if (isset($_REQUEST['relevanssi_fuzzy'])) update_option('relevanssi_fuzzy', $_REQUEST['relevanssi_fuzzy']);
	if (isset($_REQUEST['relevanssi_expand_shortcodes'])) update_option('relevanssi_expand_shortcodes', $_REQUEST['relevanssi_expand_shortcodes']);
	if (isset($_REQUEST['relevanssi_implicit_operator'])) update_option('relevanssi_implicit_operator', $_REQUEST['relevanssi_implicit_operator']);
	if (isset($_REQUEST['relevanssi_omit_from_logs'])) update_option('relevanssi_omit_from_logs', $_REQUEST['relevanssi_omit_from_logs']);
	if (isset($_REQUEST['relevanssi_index_limit'])) update_option('relevanssi_index_limit', $_REQUEST['relevanssi_index_limit']);
	if (isset($_REQUEST['relevanssi_index_attachments'])) update_option('relevanssi_index_attachments', $_REQUEST['relevanssi_index_attachments']);
	if (isset($_REQUEST['relevanssi_disable_or_fallback'])) update_option('relevanssi_disable_or_fallback', $_REQUEST['relevanssi_disable_or_fallback']);
	if (isset($_REQUEST['relevanssi_respect_exclude'])) update_option('relevanssi_respect_exclude', $_REQUEST['relevanssi_respect_exclude']);
	if (isset($_REQUEST['relevanssi_enable_cache'])) update_option('relevanssi_enable_cache', $_REQUEST['relevanssi_enable_cache']);
}

function relevanssi_add_stopword($term) {
	global $wpdb, $relevanssi_table, $stopword_table;
	if ('' == $term) return; // do not add empty $term to stopwords - added by renaissancehack
	
	$n = 0;
	$s = 0;
	
	$terms = explode(',', $term);
	if (count($terms) > 1) {
		foreach($terms as $term) {
			$n++;
			$term = trim($term);
			$success = relevanssi_add_single_stopword($term);
			if ($success) $s++;
		}
		printf(__("<div id='message' class='updated fade'><p>Successfully added %d/%d terms to stopwords!</p></div>", "relevanssi"), $s, $n);
	}
	else {
		// add to stopwords
		$success = relevanssi_add_single_stopword($term);
		
		if ($success) {
			printf(__("<div id='message' class='updated fade'><p>Term '%s' added to stopwords!</p></div>", "relevanssi"), $term);
		}
		else {
			printf(__("<div id='message' class='updated fade'><p>Couldn't add term '%s' to stopwords!</p></div>", "relevanssi"), $term);
		}
	}
}

function relevanssi_add_single_stopword($term) {
	global $wpdb, $relevanssi_table, $stopword_table;
	if ('' == $term) return;

	$q = $wpdb->prepare("INSERT INTO $stopword_table (stopword) VALUES (%s)", $term);
	$success = $wpdb->query($q);
	
	if ($success) {
		// remove from index
		$q = $wpdb->prepare("DELETE FROM $relevanssi_table WHERE term=%s", $term);
		$wpdb->query($q);
		return true;
	}
	else {
		return false;
	}
}

function relevanssi_remove_all_stopwords() {
	global $wpdb, $stopword_table;
	
	$q = $wpdb->prepare("TRUNCATE $stopword_table");
	$success = $wpdb->query($q);
	
	printf(__("<div id='message' class='updated fade'><p>Stopwords removed! Remember to re-index.</p></div>", "relevanssi"), $term);
}

function relevanssi_remove_stopword($term) {
	global $wpdb, $stopword_table;
	
	$q = $wpdb->prepare("DELETE FROM $stopword_table WHERE stopword = '$term'");
	$success = $wpdb->query($q);
	
	if ($success) {
		printf(__("<div id='message' class='updated fade'><p>Term '%s' removed from stopwords! Re-index to get it back to index.</p></div>", "relevanssi"), $term);
	}
	else {
		printf(__("<div id='message' class='updated fade'><p>Couldn't remove term '%s' from stopwords!</p></div>", "relevanssi"), $term);
	}
}

function relevanssi_common_words() {
	global $wpdb, $relevanssi_table, $wp_version;
	
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

	$lead = __("Here you can see the 20 most common user search queries, how many times those 
		queries were made and how many results were found for those queries.", 'relevanssi');

	echo "<p>$lead</p>";
	
	echo <<<EOR
<h3>Reset logs</h3>

<form method="post">
<p>To reset the logs, type 'reset' into the box here <input type="text" name="relevanssi_reset_code" />
and click <input type="submit" name="relevanssi_reset" value="Reset" />. This will empty the logs.</p>
</form>
EOR;

	echo "<div style='width: 30%; float: left'>";
	echo "<h3>" . __("Today and yesterday", 'relevanssi') . "</h3>";
	relevanssi_date_queries(1);
	echo '</div>';

	echo "<div style='width: 30%; float: left'>";
	echo "<h3>" . __("Last 7 days", 'relevanssi') . "</h3>";
	relevanssi_date_queries(7);
	echo '</div>';

	echo "<div style='width: 30%; float: left'>";
	echo "<h3>" . __("Last 30 days", 'relevanssi') . "</h3>";
	relevanssi_date_queries(30);
	echo '</div>';

	echo '<div style="clear: both"></div>';
	
	echo '<h3>' . __("Unsuccessful queries", 'relevanssi') . '</h3>';

	echo "<div style='width: 30%; float: left'>";
	echo "<h3>" . __("Today and yesterday", 'relevanssi') . "</h3>";
	relevanssi_date_queries(1, 'bad');
	echo '</div>';

	echo "<div style='width: 30%; float: left'>";
	echo "<h3>" . __("Last 7 days", 'relevanssi') . "</h3>";
	relevanssi_date_queries(7, 'bad');
	echo '</div>';

	echo "<div style='width: 30%; float: left'>";
	echo "<h3>" . __("Last 30 days", 'relevanssi') . "</h3>";
	relevanssi_date_queries(30, 'bad');
	echo '</div>';

	echo "</div>";
}

function relevanssi_date_queries($d, $version = 'good') {
	global $wpdb, $log_table;
	
	if ($version == 'good')
		$queries = $wpdb->get_results("SELECT COUNT(DISTINCT(id)) as cnt, query, hits
		  FROM $log_table
		  WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= $d
		  GROUP BY query
		  ORDER BY cnt DESC
		  LIMIT 20");
	
	if ($version == 'bad')
		$queries = $wpdb->get_results("SELECT COUNT(DISTINCT(id)) as cnt, query, hits
		  FROM $log_table
		  WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= $d
		    AND hits = 0
		  GROUP BY query
		  ORDER BY time DESC
		  LIMIT 20");

	if (count($queries) > 0) {
		echo "<table><tr><th>Query</th><th>#</th><th>Hits</th></tr>";
		foreach ($queries as $query) {
			$url = get_bloginfo('url');
			$u_q = urlencode($query->query);
			echo "<tr><td style='padding: 3px 5px'><a href='$url/?s=$u_q'>" . $query->query . "</a></td><td style='padding: 3px 5px; text-align: center'>" . $query->cnt . "</td><td style='padding: 3px 5px; text-align: center'>" . $query->hits . "</td></tr>";
		}
		echo "</table>";
	}
}

function relevanssi_options_form() {
	global $title_boost_default, $tag_boost_default, $comment_boost_default, $wpdb, $relevanssi_table;
	
	wp_enqueue_style('dashboard');
	wp_print_styles('dashboard');
	wp_enqueue_script('dashboard');
	wp_print_scripts('dashboard');
	
	$docs_count = $wpdb->get_var("SELECT COUNT(DISTINCT doc) FROM $relevanssi_table");
	$biggest_doc = $wpdb->get_var("SELECT doc FROM $relevanssi_table ORDER BY doc DESC LIMIT 1");
	
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

	$index_limit = get_option('relevanssi_index_limit');

	$excerpts = get_option('relevanssi_excerpts');
	if ('on' == $excerpts) {
		$excerpts = 'checked="checked"';
	}
	else {
		$excerpts = '';
	}
	
	$excerpt_length = get_option('relevanssi_excerpt_length');
	$excerpt_type = get_option('relevanssi_excerpt_type');
	$excerpt_chars = "";
	$excerpt_words = "";
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
	$highlight_class = "";
	switch ($highlight) {
		case "no":
			$highlight_none = 'selected="selected"';
			break;
		case "mark":
			$highlight_em = 'selected="selected"';
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
	$index_type_public = "";
	$index_type_both = "";
	switch ($index_type) {
		case "posts":
			$index_type_posts = 'selected="selected"';
			break;
		case "pages":
			$index_type_pages = 'selected="selected"';
			break;
		case "public":
			$index_type_public = 'selected="selected"';
			break;
		case "both":
			$index_type_both = 'selected="selected"';
			break;
	}
	
	$custom_types = get_option('relevanssi_custom_types');
	$custom_taxonomies = get_option('relevanssi_custom_taxonomies');
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

	$implicit_and = ('AND' == get_option('relevanssi_implicit_operator') ? 'selected="selected"' : '');
	$implicit_or = ('OR' == get_option('relevanssi_implicit_operator') ? 'selected="selected"' : '');

	$expand_shortcodes = ('on' == get_option('relevanssi_expand_shortcodes') ? 'checked="checked"' : '');
	$disablefallback = ('on' == get_option('relevanssi_disable_or_fallback') ? 'checked="checked"' : '');

	$omit_from_logs	= get_option('relevanssi_omit_from_logs');
	
	$synonyms = get_option('relevanssi_synonyms');
	isset($synonyms) ? $synonyms = str_replace(';', "\n", $synonyms) : $synonyms = "";
	
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

	$highlight_docs = ('on' == get_option('relevanssi_highlight_docs') ? 'checked="checked"' : ''); 
	$highlight_coms = ('on' == get_option('relevanssi_highlight_comments') ? 'checked="checked"' : ''); 

	$respect_exclude = ('on' == get_option('relevanssi_respect_exclude') ? 'checked="checked"' : ''); 

	$enable_cache = ('on' == get_option('relevanssi_enable_cache') ? 'checked="checked"' : ''); 
	$cache_seconds = get_option('relevanssi_cache_seconds');

	$min_word_length = get_option('relevanssi_min_word_length');

	$attachments = ('on' == get_option('relevanssi_index_attachments') ? 'checked="checked"' : ''); 
	
	$inccats = ('on' == get_option('relevanssi_include_cats') ? 'checked="checked"' : ''); 
	$index_author = ('on' == get_option('relevanssi_index_author') ? 'checked="checked"' : ''); 
	$index_excerpt = ('on' == get_option('relevanssi_index_excerpt') ? 'checked="checked"' : ''); 
	
	$show_matches = ('on' == get_option('relevanssi_show_matches') ? 'checked="checked"' : '');
	$show_matches_text = stripslashes(get_option('relevanssi_show_matches_text'));
	
?>
<div class='postbox-container' style='width:70%;'>
	<form method='post'>
	<?php wp_nonce_field('relevanssi_nonce'); ?>
	
    <p><a href="#basic"><?php _e("Basic options", "relevanssi"); ?></a> |
	<a href="#logs"><?php _e("Logs", "relevanssi"); ?></a> |
    <a href="#exclusions"><?php _e("Exclusions and restrictions", "relevanssi"); ?></a> |
    <a href="#excerpts"><?php _e("Custom excerpts", "relevanssi"); ?></a> |
    <a href="#highlighting"><?php _e("Highlighting search results", "relevanssi"); ?></a> |
    <a href="#indexing"><?php _e("Indexing options", "relevanssi"); ?></a> |
    <a href="#caching"><?php _e("Caching", "relevanssi"); ?></a> |
    <a href="#synonyms"><?php _e("Synonyms", "relevanssi"); ?></a> |
    <a href="#stopwords"><?php _e("Stopwords", "relevanssi"); ?></a> |
    <a href="#uninstall"><?php _e("Uninstalling", "relevanssi"); ?></a>
    </p>

	<h3><?php _e('Quick tools', 'relevanssi') ?></h3>
	<p>
	<input type='submit' name='submit' value='<?php _e('Save options', 'relevanssi'); ?>' style="background-color:#007f00; border-color:#5fbf00; border-style:solid; border-width:thick; padding: 5px; color: #fff;" />
	<input type="submit" name="index" value="<?php _e('Build the index', 'relevanssi') ?>" style="background-color:#007f00; border-color:#5fbf00; border-style:solid; border-width:thick; padding: 5px; color: #fff;" />
	<input type="submit" name="index_extend" value="<?php _e('Continue indexing', 'relevanssi') ?>"  style="background-color:#e87000; border-color:#ffbb00; border-style:solid; border-width:thick; padding: 5px; color: #fff;" />, <?php _e('add', 'relevanssi'); ?> <input type="text" size="4" name="relevanssi_index_limit" value="<?php echo $index_limit ?>" /> <?php _e('documents.', 'relevanssi'); ?></p>

	<p><?php _e("Use 'Build the index' to build the index with current <a href='#indexing'>indexing options</a>. If you can't finish indexing with one go, use 'Continue indexing' to finish the job. You can change the number of documents to add until you find the largest amount you can add with one go. See 'State of the Index' below to find out how many documents actually go into the index.", 'relevanssi') ?></p>
	
	<h3><?php _e("State of the Index", "relevanssi"); ?></h3>
	<p>
	<?php _e("Documents in the index", "relevanssi"); ?>: <strong><?php echo $docs_count ?></strong><br />
	<?php _e("Highest post ID indexed", "relevanssi"); ?>: <strong><?php echo $biggest_doc ?></strong>
	</p>
	
	<h3 id="basic"><?php _e("Basic options", "relevanssi"); ?></h3>
	<label for='relevanssi_title_boost'><?php _e('Title boost:', 'relevanssi'); ?> 
	<input type='text' name='relevanssi_title_boost' size='4' value='<?php echo $title_boost ?>' /></label>
	<small><?php printf(__('Default: %d. 0 means titles are ignored, 1 means no boost, more than 1 gives extra value.', 'relevanssi'), $title_boost_default); ?></small>
	<br />
	<label for='relevanssi_tag_boost'><?php _e('Tag boost:', 'relevanssi'); ?> 
	<input type='text' name='relevanssi_tag_boost' size='4' value='<?php echo $tag_boost ?>' /></label>
	<small><?php printf(__('Default: %d. 0 means tags are ignored, 1 means no boost, more than 1 gives extra value.', 'relevanssi'), $tag_boost_default); ?></small>
	<br />
	<label for='relevanssi_comment_boost'><?php _e('Comment boost:', 'relevanssi'); ?> 
	<input type='text' name='relevanssi_comment_boost' size='4' value='<?php echo $comment_boost ?>' /></label>
	<small><?php printf(__('Default: %d. 0 means comments are ignored, 1 means no boost, more than 1 gives extra value.', 'relevanssi'), $comment_boost_default); ?></small>
	<br /><br />
	<label for='relevanssi_admin_search'><?php _e('Use search for admin:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_admin_search' <?php echo $admin_search ?> /></label>
	<small><?php _e('If checked, Relevanssi will be used for searches in the admin interface', 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_implicit_operator'><?php _e("Default operator for the search?", "relevanssi"); ?>
	<select name='relevanssi_implicit_operator'>
	<option value='AND' <?php echo $implicit_and ?>><?php _e("AND - require all terms", "relevanssi"); ?></option>
	<option value='OR' <?php echo $implicit_or ?>><?php _e("OR - any term present is enough", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("If you choose AND and the search finds no matches, it will automatically do an OR search.", "relevanssi"); ?></small>
	
	<br /><br />

	<label for='relevanssi_disable_or_fallback'><?php _e("Disable OR fallback:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_disable_or_fallback' <?php echo $disablefallback ?> /></label>
	<small><?php _e("If you don't want Relevanssi to fall back to OR search when AND search gets no hits, check this option. For most cases, leave this one unchecked.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_fuzzy'><?php _e('When to use fuzzy matching?', 'relevanssi'); ?>
	<select name='relevanssi_fuzzy'>
	<option value='sometimes' <?php echo $fuzzy_sometimes ?>><?php _e("When straight search gets no hits", "relevanssi"); ?></option>
	<option value='always' <?php echo $fuzzy_always ?>><?php _e("Always", "relevanssi"); ?></option>
	<option value='never' <?php echo $fuzzy_never ?>><?php _e("Don't use fuzzy search", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("Straight search matches just the term. Fuzzy search matches everything that begins or ends with the search term.", "relevanssi"); ?></small>

	<h3 id="logs"><?php _e('Logs', 'relevanssi'); ?></h3>
	
	<label for='relevanssi_log_queries'><?php _e("Keep a log of user queries:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_log_queries' <?php echo $log_queries ?> /></label>
	<small><?php _e("If checked, Relevanssi will log user queries. The log appears in 'User searches' on the Dashboard admin menu.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_omit_from_logs'><?php _e("Don't log queries from these users:", "relevanssi"); ?>
	<input type='text' name='relevanssi_omit_from_logs' size='20' value='<?php echo $omit_from_logs ?>' /></label>
	<small><?php _e("Comma-separated list of user ids that will not be logged.", "relevanssi"); ?></small>

	<p><?php _e("If you enable logs, you can see what your users are searching for. Logs are also needed to use the 'Did you mean?' feature. You can prevent your own searches from getting in the logs with the omit feature.", "relevanssi"); ?></p>

	<h3 id="exclusions"><?php _e("Exclusions and restrictions", "relevanssi"); ?></h3>
	
	<label for='relevanssi_cat'><?php _e('Restrict search to these categories and tags:', 'relevanssi'); ?>
	<input type='text' name='relevanssi_cat' size='20' value='<?php echo $cat ?>' /></label><br />
	<small><?php _e("Enter a comma-separated list of category and tag IDs to restrict search to those categories or tags. You can also use <code>&lt;input type='hidden' name='cat' value='list of cats and tags' /&gt;</code> in your search form. The input field will 	overrun this setting.", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_excat'><?php _e('Exclude these categories and tags from search:', 'relevanssi'); ?>
	<input type='text' name='relevanssi_excat' size='20' value='<?php echo $excat ?>' /></label><br />
	<small><?php _e("Enter a comma-separated list of category and tag IDs that are excluded from search results. This only works here, you can't use the input field option (WordPress doesn't pass custom parameters there).", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_excat'><?php _e('Exclude these posts/pages from search:', 'relevanssi'); ?>
	<input type='text' name='relevanssi_expst' size='20' value='<?php echo $expst ?>' /></label><br />
	<small><?php _e("Enter a comma-separated list of post/page IDs that are excluded from search results. This only works here, you can't use the input field option (WordPress doesn't pass custom parameters there).", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_respect_exclude'><?php _e('Respect exclude_from_search for custom post types:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_respect_exclude' <?php echo $respect_exclude ?> /></label><br />
	<small><?php _e("If checked, Relevanssi won't display posts of custom post types that have 'exclude_from_search' set to true. If not checked, Relevanssi will display anything that is indexed.", 'relevanssi'); ?></small>

	<h3 id="excerpts"><?php _e("Custom excerpts/snippets", "relevanssi"); ?></h3>
	
	<label for='relevanssi_excerpts'><?php _e("Create custom search result snippets:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_excerpts' <?php echo $excerpts ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will create excerpts that contain the search term hits. To make them work, make sure your search result template uses the_excerpt() to display post excerpts.", 'relevanssi'); ?></small>
	
	<br /><br />
	
	<label for='relevanssi_excerpt_length'><?php _e("Length of the snippet:", "relevanssi"); ?>
	<input type='text' name='relevanssi_excerpt_length' size='4' value='<?php echo $excerpt_length ?>' /></label>
	<select name='relevanssi_excerpt_type'>
	<option value='chars' <?php echo $excerpt_chars ?>><?php _e("characters", "relevanssi"); ?></option>
	<option value='words' <?php echo $excerpt_words ?>><?php _e("words", "relevanssi"); ?></option>
	</select><br />
	<small><?php _e("This must be an integer.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_show_matches'><?php _e("Show breakdown of search hits in excerpts:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_show_matches' <?php echo $show_matches ?> /></label>
	<small><?php _e("Check this to show more information on where the search hits were made. Requires custom snippets to work.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_show_matches_text'><?php _e("The breakdown format:", "relevanssi"); ?>
	<input type='text' name='relevanssi_show_matches_text' value="<?php echo $show_matches_text ?>" size='20' /></label>
	<small><?php _e("Use %body%, %title%, %tags% and %comments% to display the number of hits (in different parts of the post), %total% for total hits, %score% to display the document weight and %terms% to show how many hits each search term got. No double quotes (\") allowed!", "relevanssi"); ?></small>

	<h3 id="highlighting"><?php _e("Search hit highlighting", "relevanssi"); ?></h3>

	<?php _e("First, choose the type of highlighting used:", "relevanssi"); ?><br />

	<div style='margin-left: 2em'>
	<label for='relevanssi_highlight'><?php _e("Highlight query terms in search results:", 'relevanssi'); ?>
	<select name='relevanssi_highlight'>
	<option value='no' <?php echo $highlight_none ?>><?php _e('No highlighting', 'relevanssi'); ?></option>
	<option value='mark' <?php echo $highlight_em ?>>&lt;mark&gt;</option>
	<option value='em' <?php echo $highlight_em ?>>&lt;em&gt;</option>
	<option value='strong' <?php echo $highlight_strong ?>>&lt;strong&gt;</option>
	<option value='col' <?php echo $highlight_col ?>><?php _e('Text color', 'relevanssi'); ?></option>
	<option value='bgcol' <?php echo $highlight_bgcol ?>><?php _e('Background color', 'relevanssi'); ?></option>
	<option value='css' <?php echo $highlight_style ?>><?php _e("CSS Style", 'relevanssi'); ?></option>
	<option value='class' <?php echo $highlight_class ?>><?php _e("CSS Class", 'relevanssi'); ?></option>
	</select></label>
	<small><?php _e("Highlighting isn't available unless you use custom snippets", 'relevanssi'); ?></small>
	
	<br />

	<label for='relevanssi_hilite_title'><?php _e("Highlight query terms in result titles too:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_hilite_title' <?php echo $hititle ?> /></label>
	<small><?php _e("", 'relevanssi'); ?></small>

	<br />

	<label for='relevanssi_highlight_docs'><?php _e("Highlight query terms in documents:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_highlight_docs' <?php echo $highlight_docs ?> /></label>
	<small><?php _e("Highlights hits when user opens the post from search results. This is based on HTTP referrer, so if that's blocked, there'll be no highlights.", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_highlight_comments'><?php _e("Highlight query terms in comments:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_highlight_comments' <?php echo $highlight_coms ?> /></label>
	<small><?php _e("Highlights hits in comments when user opens the post from search results.", "relevanssi"); ?></small>

	<br /><br />
	</div>
	
	<?php _e("Then adjust the settings for your chosen type:", "relevanssi"); ?><br />

	<div style='margin-left: 2em'>
	
	<label for='relevanssi_txt_col'><?php _e("Text color for highlights:", "relevanssi"); ?>
	<input type='text' name='relevanssi_txt_col' size='7' value='<?php echo $txt_col ?>' /></label>
	<small><?php _e("Use HTML color codes (#rgb or #rrggbb)", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_bg_col'><?php _e("Background color for highlights:", "relevanssi"); ?>
	<input type='text' name='relevanssi_bg_col' size='7' value='<?php echo $bg_col ?>' /></label>
	<small><?php _e("Use HTML color codes (#rgb or #rrggbb)", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_css'><?php _e("CSS style for highlights:", "relevanssi"); ?>
	<input type='text' name='relevanssi_css' size='30' value='<?php echo $css ?>' /></label>
	<small><?php _e("You can use any CSS styling here, style will be inserted with a &lt;span&gt;", "relevanssi"); ?></small>

	<br />
	
	<label for='relevanssi_css'><?php _e("CSS class for highlights:", "relevanssi"); ?>
	<input type='text' name='relevanssi_class' size='10' value='<?php echo $class ?>' /></label>
	<small><?php _e("Name a class here, search results will be wrapped in a &lt;span&gt; with the class", "relevanssi"); ?></small>

	</div>
	
	<br />
	<br />
	
	<input type='submit' name='submit' value='<?php _e('Save the options', 'relevanssi'); ?>' />

	<h3 id="indexing"><?php _e('Indexing options', 'relevanssi'); ?></h3>
	
	<label for='relevanssi_index_type'><?php _e("What to include in the index", "relevanssi"); ?>:
	<select name='relevanssi_index_type'>
	<option value='both' <?php echo $index_type_both ?>><?php _e("Everything", "relevanssi"); ?></option>
	<option value='public' <?php echo $index_type_public ?>><?php _e("All public post types", "relevanssi"); ?></option>
	<option value='posts' <?php echo $index_type_posts ?>><?php _e("Just posts", "relevanssi"); ?></option>
	<option value='pages' <?php echo $index_type_pages ?>><?php _e("Just pages", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("This determines which post types are included in the index. Choosing 'everything' will include posts, pages and all custom post types. 'All public post types' includes all registered post types that don't have the 'exclude_from_search' set to true. This includes post, page, and possible custom types. 'All public types' requires at least WP 2.9, otherwise it's the same as 'everything'. Note: attachments are covered with a separate option below.", "relevanssi"); ?></small>

	<br /><br />
	
	<label for='relevanssi_min_word_length'><?php _e("Minimum word length to index", "relevanssi"); ?>:
	<input type='text' name='relevanssi_min_word_length' size='30' value='<?php echo $min_word_length ?>' /></label><br />
	<small><?php _e("Words shorter than this number will not be indexed.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_custom_types'><?php _e("Custom post types to index", "relevanssi"); ?>:
	<input type='text' name='relevanssi_custom_types' size='30' value='<?php echo $custom_types ?>' /></label><br />
	<small><?php _e("If you don't want to index all custom post types, list here the custom post types you want to see indexed. List comma-separated post type names (as used in the database). You can also use a hidden field in the search form to restrict the search to a certain post type: <code>&lt;input type='hidden' name='post_type' value='comma-separated list of post types' /&gt;</code>. If you choose 'All public post types' or 'Everything' above, this option has no effect. You can exclude custom post types with the minus notation, for example '-foo,bar,-baz' would include 'bar' and exclude 'foo' and 'baz'.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_index_attachments'><?php _e('Index and search your posts\' attachments:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_index_attachments' <?php echo $attachments ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search attachments of your posts (pictures, files and so on). Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_expand_shortcodes'><?php _e("Expand shortcodes in post content:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_expand_shortcodes' <?php echo $expand_shortcodes ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will expand shortcodes in post content before indexing. Otherwise shortcodes will be stripped. If you use shortcodes to include dynamic content, Relevanssi will not keep the index updated, the index will reflect the status of the shortcode content at the moment of indexing.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_inctags'><?php _e('Index and search your posts\' tags:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_inctags' <?php echo $inctags ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search the tags of your posts. Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_inccats'><?php _e('Index and search your posts\' categories:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_inccats' <?php echo $inccats ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search the categories of your posts. Category titles will pass through 'single_cat_title' filter. Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_index_author'><?php _e('Index and search your posts\' authors:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_index_author' <?php echo $index_author ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search the authors of your posts. Author display name will be indexed. Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_index_excerpt'><?php _e('Index and search post excerpts:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_index_excerpt' <?php echo $index_excerpt ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will also index and search the excerpts of your posts.Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />
	
	<label for='relevanssi_index_comments'><?php _e("Index and search these comments:", "relevanssi"); ?>
	<select name='relevanssi_index_comments'>
	<option value='none' <?php echo $incom_type_none ?>><?php _e("none", "relevanssi"); ?></option>
	<option value='normal' <?php echo $incom_type_normal ?>><?php _e("normal", "relevanssi"); ?></option>
	<option value='all' <?php echo $incom_type_all ?>><?php _e("all", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("Relevanssi will index and search ALL (all comments including track- &amp; pingbacks and custom comment types), NONE (no comments) or NORMAL (manually posted comments on your blog).<br />Remember to rebuild the index if you change this option!", 'relevanssi'); ?></small>

	<br /><br />

	<label for='relevanssi_index_fields'><?php _e("Custom fields to index:", "relevanssi"); ?>
	<input type='text' name='relevanssi_index_fields' size='30' value='<?php echo $index_fields ?>' /></label><br />
	<small><?php _e("A comma-separated list of custom field names to include in the index.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_custom_taxonomies'><?php _e("Custom taxonomies to index:", "relevanssi"); ?>
	<input type='text' name='relevanssi_custom_taxonomies' size='30' value='<?php echo $custom_taxonomies ?>' /></label><br />
	<small><?php _e("A comma-separated list of custom taxonomies to include in the index.", "relevanssi"); ?></small>

	<br /><br />

	<input type='submit' name='index' value='<?php _e("Save indexing options and build the index", 'relevanssi'); ?>' />

	<input type='submit' name='index_extend' value='<?php _e("Continue indexing", 'relevanssi'); ?>' />

	<h3 id="caching"><?php _e("Caching", "relevanssi"); ?></h3>

	<label for='relevanssi_enable_cache'><?php _e('Enable result and excerpt caching:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_enable_cache' <?php echo $enable_cache ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will cache search results and post excerpts.", 'relevanssi'); ?></small>

	<br /><br />
	
	<label for='relevanssi_cache_seconds'><?php _e("Cache expire (in seconds):", "relevanssi"); ?>
	<input type='text' name='relevanssi_cache_seconds' size='30' value='<?php echo $cache_seconds ?>' /></label><br />
	<small><?php _e("86400 = day", "relevanssi"); ?></small>

	<h3 id="synonyms"><?php _e("Synonyms", "relevanssi"); ?></h3>
	
	<p><textarea name='relevanssi_synonyms' rows='9' cols='60'><?php echo $synonyms ?></textarea></p>

	<p><small><?php _e("Add synonyms here in 'key = value' format. When searching with the OR operator, any search of 'key' will be expanded to include 'value' as well. Using phrases is possible. The key-value pairs work in one direction only, but you can of course repeat the same pair reversed.", "relevanssi"); ?></small></p>

	<input type='submit' name='submit' value='<?php _e('Save the options', 'relevanssi'); ?>' />

	<h3 id="stopwords"><?php _e("Stopwords", "relevanssi"); ?></h3>
	
	<?php relevanssi_show_stopwords(); ?>
	
	<h3 id="uninstall"><?php _e("Uninstalling the plugin", "relevanssi"); ?></h3>
	
	<p><?php _e("If you want to uninstall the plugin, start by clicking the button below to wipe clean the options and tables created by the plugin, then remove it from the plugins list.", "relevanssi");	 ?></p>
	
	<input type='submit' name='uninstall' value='<?php _e("Remove plugin data", "relevanssi"); ?>' />

	</form>
</div>

	<?php

	relevanssi_sidebar();
}

function relevanssi_show_stopwords() {
	global $wpdb, $stopword_table, $wp_version;

	_e("<p>Enter a word here to add it to the list of stopwords. The word will automatically be removed from the index, so re-indexing is not necessary. You can enter many words at the same time, separate words with commas.</p>", 'relevanssi');

?><label for="addstopword"><p><?php _e("Stopword(s) to add: ", 'relevanssi'); ?><textarea name="addstopword" rows="2" cols="40"></textarea>
<input type="submit" value="<?php _e("Add", 'relevanssi'); ?>" /></p></label> <!-- close <label ...> tag - added by renaissancehack -->
<?php

	_e("<p>Here's a list of stopwords in the database. Click a word to remove it from stopwords. Removing stopwords won't automatically return them to index, so you need to re-index all posts after removing stopwords to get those words back to index.", 'relevanssi');

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
	
	echo "<ul>";
	$results = $wpdb->get_results("SELECT * FROM $stopword_table");
	foreach ($results as $stopword) {
		$sw = $stopword->stopword; 
		printf('<li style="display: inline;"><input type="submit" name="removestopword" value="%s"/></li>', $sw, $src, $sw);
	}
	echo "</ul>";
	
?>
<p><input type="submit" name="removeallstopwords" value="<?php _e('Remove all stopwords', 'relevanssi'); ?>" /></p>
<?php
}

function relevanssi_sidebar() {
	$tweet = 'http://twitter.com/home?status=' . urlencode("I'm using Relevanssi, a better search for WordPress. http://wordpress.org/extend/plugins/relevanssi/ #relevanssi #wordpress");
	if (function_exists("plugins_url")) {
		global $wp_version;
		if (version_compare($wp_version, '2.8dev', '>' )) {
			$facebooklogo = plugins_url('facebooklogo.jpg', __FILE__);
		}
		else {
			$facebooklogo = plugins_url('relevanssi/facebooklogo.jpg');
		}
	}
	else {
		// We can't check, so let's assume something sensible
		$facebooklogo = '/wp-content/plugins/relevanssi/facebooklogo.jpg';
	}

	echo <<<EOH
<div class="postbox-container" style="width:20%; margin-top: 35px; margin-left: 15px;">
	<div class="metabox-holder">	
		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
			<h3 class="hndle"><span>Support Relevanssi!</span></h3>
			<div class="inside">
<p>Is the better search provided by Relevanssi worth something to you?</p>

<p>You can also support Relevanssi by blogging about it.</p>

<p>Even <a href="$tweet" target="_blank">a single tweet</a> will help spread the word!</p>

<div style="text-align:center">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations">
<input type="hidden" name="business" value="mikko@mikkosaari.fi">
<input type="hidden" name="lc" value="US">
<input type="hidden" name="item_name" value="Relevanssi">
<input type="hidden" name="currency_code" value="EUR">
<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div>
			</div>
		</div>
	</div>
	
		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
			<h3 class="hndle"><span>Relevanssi in Facebook!</span></h3>
			<div class="inside">
			<div style="float: left; margin-right: 5px"><img src="$facebooklogo" width="45" height="43" alt="Facebook" /></div>
			<p><a href="http://www.facebook.com/pages/Relevanssi-Better-Search-for-WordPress/139381702780384">Check
			out the Relevanssi page in Facebook</a> for news and updates about your favourite plugin.</p>
			</div>
		</div>
	</div>

		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
			<h3 class="hndle"><span>Help and support</span></h3>
			<div class="inside">
			<p>For Relevanssi support, see:</p>
			
			<p>- <a href="http://wordpress.org/tags/relevanssi?forum_id=10">WordPress.org forum</a><br />
			- <a href="http://www.mikkosaari.fi/en/relevanssi-search/">Plugin home page</a></p>
			</div>
		</div>
	</div>
</div>
</div>
EOH;
}
?>