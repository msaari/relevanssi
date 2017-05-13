<?php
/*
Plugin Name: Relevanssi
Plugin URI: http://www.relevanssi.com/
Description: This plugin replaces WordPress search with a relevance-sorting search.
Version: 3.0
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

new Relevanssi;

class Relevanssi {
// For debugging purposes
	//error_reporting(E_ALL);
	//ini_set("display_errors", 1); 
	//define('WP-DEBUG', true);
	global $wpdb;
	//$wpdb->show_errors();

	private $wpSearch_low;
	private $wpSearch_high;
	private $relevanssi_table;
	private $stopword_table;
	private $log_table;
	private $relevanssi_cache;
	private $relevanssi_excerpt_cache;
	private $stopword_list;
	private $title_boost_default;
	private $tag_boost_default;
	private $comment_boost_default;
	private $relevanssi_hits;

	function Relevanssi() {
		$this->__construct();
	}
	
	function __construct() {
		add_action('init', array(&$this, 'init'));
		add_action('admin_init', array(&$this, 'admin_init'));
		
		if (is_admin()) {
			require_once(plugin_dir_path(__FILE__) . 'lib/admin.php'));
		}
	}

	function admin_init() {
		add_action('admin_menu', array( &$this, 'relevanssi_menu' ));

		isset($_POST['index']) ? $index = true : $index = false;
		if (!get_option('relevanssi_indexed') && !$index) {
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
	}
	
	function init(
		register_activation_hook(__FILE__, array( &$this, 'relevanssi_install') );
		add_filter('the_posts', array( &$this, 'relevanssi_query' ));
		add_action('save_post', array( &$this, 'relevanssi_edit' ));				// thanks to Brian D Gajus
		add_action('delete_post', array( &$this, 'relevanssi_delete' ));
		add_action('comment_post', array( &$this, 'relevanssi_comment_index' )); 	//added by OdditY
		add_action('edit_comment', array( &$this, 'relevanssi_comment_edit' )); 		//added by OdditY 
		add_action('delete_comment', array( &$this, 'relevanssi_comment_remove' )); 	//added by OdditY
		// BEGIN added by renaissancehack
		// *_page and *_post hooks do not trigger on attachments
		add_action('delete_attachment', array( &$this, 'relevanssi_delete' ));
		add_action('add_attachment', array( &$this, 'relevanssi_publish' ));
		add_action('edit_attachment', array( &$this, 'relevanssi_edit' ));
		// When a post status changes, check child posts that inherit their status from parent
		add_action('transition_post_status', array( &$this, 'relevanssi_update_child_posts',99,3 ));
		// END added by renaissancehack
		add_filter('relevanssi_hits_filter', array( &$this, 'relevanssi_wpml_filter' ));
		add_filter('relevanssi_remove_punctuation', array( &$this, 'relevanssi_remove_punct' ));
		add_filter('relevanssi_post_ok', array( &$this, 'relevanssi_default_post_ok' ));
		
		load_plugin_textdomain('relevanssi', false, basename(dirname(__FILE__)););

		$this->$wpSearch_low = 0;
		$this->$wpSearch_high = 0;
		$this->$relevanssi_table = $wpdb->prefix . "relevanssi";
		$this->$stopword_table = $wpdb->prefix . "relevanssi_stopwords";
		$this->$log_table = $wpdb->prefix . "relevanssi_log";
		$this->$relevanssi_cache = $wpdb->prefix . "relevanssi_cache";
		$this->$relevanssi_excerpt_cache = $wpdb->prefix . "relevanssi_excerpt_cache";
		$this->$title_boost_default = 5;
		$this->$tag_boost_default = 0.75;
		$this->$comment_boost_default = 0.75;
	
		if (!wp_next_scheduled('relevanssi_truncate_cache')) {
			wp_schedule_event(time(), 'daily', 'relevanssi_truncate_cache');
			add_action('relevanssi_truncate_cache', 'relevanssi_truncate_cache');
		}
	}
	
	function relevanssi_menu() {
		add_options_page(
			'Relevanssi',
			'Relevanssi',
			'manage_options',
			__FILE__,
			'relevanssi_options'
		);
		add_dashboard_page(
			__('User searches', 'relevanssi'),
			__('User searches', 'relevanssi'),
			'edit_pages',
			__FILE__,
			'relevanssi_search_stats'
		);
	}

function relevanssi_truncate_cache($all = false) {
	global $relevanssi_cache, $relevanssi_excerpt_cache, $wpdb;
		
	if ($all) {
		$query = "TRUNCATE TABLE $relevanssi_excerpt_cache";
		$wpdb->query($query);

		$query = "TRUNCATE TABLE $relevanssi_cache";
	}
	else {
		$time = get_option('relevanssi_cache_seconds', 172800);
		$query = "DELETE FROM $relevanssi_cache
			WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(tstamp) > $time";
		// purge all expired cache data
	}
	$wpdb->query($query);
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
		$url = esc_attr(add_query_arg(array(
			's' => urlencode($closest)

			), $url ));
		echo "$pre<a href='$url'>$closest</a>$post";
 	}
 

}

// BEGIN added by renaissancehack
function relevanssi_update_child_posts($new_status, $old_status, $post) {
// called by 'transition_post_status' action hook when a post is edited/published/deleted
//  and calls appropriate indexing function on child posts/attachments
    global $wpdb;

    $index_statuses = array('publish', 'private', 'draft', 'pending', 'future');
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

	$post_status = get_post_status($post);
	
	if ('auto-draft' == $post_status) return;

// BEGIN added by renaissancehack
    //  if post_status is "inherit", get post_status from parent
    if ($post_status == 'inherit') {
        $post_type = $wpdb->get_var("SELECT post_type FROM $wpdb->posts WHERE ID=$post");
    	$post_status = $wpdb->get_var("SELECT p.post_status FROM $wpdb->posts p, $wpdb->posts c WHERE c.ID=$post AND c.post_parent=p.ID");
    }
// END added by renaissancehack

	$index_statuses = array('publish', 'private', 'draft', 'pending', 'future');
	if (!in_array($post_status, $index_statuses)) {
 		// The post isn't supposed to be indexed anymore, remove it from index
 		relevanssi_remove_doc($post);
	}
	else {
		relevanssi_publish($post);
	}
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
	global $relevanssi_publish_doc;
	
	$post_status = get_post_status($post);
	if ('auto-draft' == $post_status) return;

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
	add_option('relevanssi_cache_seconds', '172800');
	add_option('relevanssi_enable_cache', 'off');
	add_option('relevanssi_min_word_length', 3);
	add_option('relevanssi_wpml_only_current', 'on');
	add_option('relevanssi_word_boundaries', 'on');
	add_option('relevanssi_hidesponsor', 'false');
	add_option('relevanssi_default_orderby', 'relevance');
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$charset_collate = '';

	if (!empty($wpdb->charset))
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if (!empty($wpdb->collate))
		$charset_collate .= " COLLATE $wpdb->collate";

	$relevanssi_table = $wpdb->prefix . "relevanssi";
	$stopword_table = $wpdb->prefix . "relevanssi_stopwords";
	$log_table = $wpdb->prefix . "relevanssi_log";
	$relevanssi_cache = $wpdb->prefix . 'relevanssi_cache';
	$relevanssi_excerpt_cache = $wpdb->prefix . 'relevanssi_excerpt_cache';
	
	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_table'") != $relevanssi_table) {
		$sql = "CREATE TABLE " . $relevanssi_table . " (id mediumint(9) NOT NULL AUTO_INCREMENT, "
		. "doc bigint(20) NOT NULL, "
		. "term varchar(50) NOT NULL, "
		. "tf mediumint(9) NOT NULL DEFAULT '0', "
		. "title tinyint(1) NOT NULL, "
	    . "UNIQUE KEY id (id)) $charset_collate;";

		dbDelta($sql);
		
		$sql = "CREATE INDEX terms ON $relevanssi_table (term(20))";
		$wpdb->query($sql);
	}


	if($wpdb->get_var("SHOW TABLES LIKE '$stopword_table'") != $stopword_table) {
		$sql = "CREATE TABLE " . $stopword_table . " (stopword varchar(50) NOT NULL, "
	    . "UNIQUE KEY stopword (stopword)) $charset_collate;";

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
	    . "UNIQUE KEY id (id)) $charset_collate;";

		dbDelta($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_cache'") != $relevanssi_cache) {
		$sql = "CREATE TABLE " . $relevanssi_cache . " (param varchar(32) NOT NULL, "
		. "hits text NOT NULL, "
		. "tstamp timestamp NOT NULL, "
	    . "UNIQUE KEY param (param)) $charset_collate;";

		dbDelta($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_excerpt_cache'") != $relevanssi_excerpt_cache) {
		$sql = "CREATE TABLE " . $relevanssi_excerpt_cache . " (query varchar(100) NOT NULL, "
		. "post mediumint(9) NOT NULL, "
		. "excerpt text NOT NULL, "
	    . "UNIQUE (query, post)) $charset_collate;";

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
	delete_option('relevanssi_wpml_only_current');
	delete_option('relevanssi_word_boundaries');
	delete_option('relevanssi_hidesponsor');
	delete_option('relevanssi_default_orderby');

	wp_clear_scheduled_hook('relevanssi_truncate_cache');

	$relevanssi_table = $wpdb->prefix . "relevanssi";	
	$stopword_table = $wpdb->prefix . "relevanssi_stopwords";
	$log_table = $wpdb->prefix . "relevanssi_log";
	$relevanssi_cache = $wpdb->prefix . 'relevanssi_cache';
	$relevanssi_excerpt_cache = $wpdb->prefix . 'relevanssi_excerpt_cache';
	
	if($wpdb->get_var("SHOW TABLES LIKE '$stopword_table'") == $stopword_table) {
		$sql = "DROP TABLE $stopword_table";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_table'") == $relevanssi_table) {
		$sql = "DROP TABLE $relevanssi_table";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$log_table'") == $log_table) {
		$sql = "DROP TABLE $log_table";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_cache'") == $relevanssi_cache) {
		$sql = "DROP TABLE $relevanssi_cache";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_excerpt_cache'") == $relevanssi_excerpt_cache) {
		$sql = "DROP TABLE $relevanssi_excerpt_cache";
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

add_filter('query_vars', 'relevanssi_query_vars');
function relevanssi_query_vars($qv) {
	$qv[] = 'cats';
	$qv[] = 'post_types';

	return $qv;
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

	$q = trim(stripslashes(mb_strtolower($query->query_vars["s"])));
	
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
	if (isset($query->query_vars["post_types"])) {
		$post_type = $query->query_vars["post_types"];
	}
	
	$author = false;
	if (isset($query->query_vars["author"])) {
		$author = $query->query_vars["author"];
	}

	
	$expids = get_option("relevanssi_exclude_posts");

	if (is_admin()) {
		// in admin search, search everything
		$cat = null;
		$excat = null;
		$expids = null;
		$tag = null;
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
		$params = md5(serialize(array($q, $cat, $excat, $expids, $post_type, $tax, $tax_term, $operator, $tag, $author)));
		$return = relevanssi_fetch_hits($params);
		if (!$return) {
			$return = relevanssi_search($q, $cat, $excat, $expids, $post_type, $tax, $tax_term, $operator, $tag, $author);
			$return_ser = serialize($return);
			relevanssi_store_hits($params, $return_ser);
		}
	}
	else {
		$return = relevanssi_search($q, $cat, $excat, $expids, $post_type, $tax, $tax_term, $operator, $tag, $author);
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
	$wpSearch_high = $wpSearch_low + $query->query_vars["posts_per_page"] - 1;
	if ($wpSearch_high > sizeof($hits)) $wpSearch_high = sizeof($hits) - 1;
	
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

	$wpdb->query("INSERT INTO $relevanssi_excerpt_cache (post, query, excerpt)
		VALUES ($post, '$query', '$excerpt')
		ON DUPLICATE KEY UPDATE excerpt = '$excerpt'");
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
	$wpdb->query("INSERT INTO $relevanssi_cache (param, hits)
		VALUES ('$param', '$data')
		ON DUPLICATE KEY UPDATE hits = '$data'");
}

// thanks to rvencu
function relevanssi_wpml_filter($data) {
	$use_filter = get_option('relevanssi_wpml_only_current');
	if ('on' == $use_filter) {
		//save current blog language
		$lang = get_bloginfo('language');
		$filtered_hits = array();
		foreach ($data[0] as $hit) {
			if (isset($hit->blog_id)) {
				switch_to_blog($hit->blog_id);
			}
			global $sitepress;
			if (function_exists('icl_object_id') && $sitepress->is_translated_post_type($hit->post_type)) {
			    if ($hit->ID == icl_object_id($hit->ID, $hit->post_type,false,ICL_LANGUAGE_CODE))
			        $filtered_hits[] = $hit;
			}
			// if there is no WPML but the target blog has identical language with current blog,
			// we use the hits. Note en-US is not identical to en-GB!
			elseif (get_bloginfo('language') == $lang) {
				$filtered_hits[] = $hit;
			}
			if (isset($hit->blog_id)) {
				restore_current_blog();
			}
		}
		return array($filtered_hits, $data[1]);
	}
	return $data;
}

/**
 * Function by Matthew Hood http://my.php.net/manual/en/function.sort.php#75036
 */
function objectSort(&$data, $key, $dir = 'desc') {
	$dir = strtolower($dir);
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
	isset($data['body_matches'][$hit]) ? $body = $data['body_matches'][$hit] : $body = 0;
	isset($data['title_matches'][$hit]) ? $title = $data['title_matches'][$hit] : $title = 0;
	isset($data['tag_matches'][$hit]) ? $tag = $data['tag_matches'][$hit] : $tag = 0;
	isset($data['comment_matches'][$hit]) ? $comment = $data['comment_matches'][$hit] : $comment = 0;
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
	if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == "Mediapartners-Google")
		return;

	global $wpdb, $log_table;
	
	if (get_option('relevanssi_omit_from_logs')) {
		$user = wp_get_current_user();
		$omit = explode(",", get_option('relevanssi_omit_from_logs'));
		if (in_array($user->ID, $omit)) return;
	}
	
	$q = $wpdb->prepare("INSERT INTO $log_table (query, hits) VALUES (%s, %d)", $query, intval($hits));
	$wpdb->query($q);
}

// This is my own magic working.
function relevanssi_search($q, $cat = NULL, $excat = NULL, $expost = NULL, $post_type = NULL, $taxonomy = NULL, $taxonomy_term = NULL, $operator = "AND", $tag = NULL, $author = NULL) {
	global $relevanssi_table, $wpdb;

	$values_to_filter = array(
		'q' => $q,
		'cat' => $cat,
		'tag' => $tag,
		'excat' => $excat,
		'expost' => $expost,
		'post_type' => $post_type,
		'taxonomy' => $taxonomy,
		'taxonomy_term' => $taxonomy_term,
		'operator' => $operator,
		'author' => $author,
		);
	$filtered_values = apply_filters( 'relevanssi_search_filters', $values_to_filter );
	$q               = $filtered_values['q'];
	$cat             = $filtered_values['cat'];
	$excat           = $filtered_values['excat'];
	$tag             = $filtered_values['tag'];
	$expost          = $filtered_values['expost'];
	$post_type       = $filtered_values['post_type'];
	$taxonomy        = $filtered_values['taxonomy'];
	$taxonomy_term   = $filtered_values['taxonomy_term'];
	$operator        = $filtered_values['operator'];
	$author        	 = $filtered_values['author'];

	$hits = array();
	
	$custom_cat = NULL;
	
	$o_cat = $cat;
	$o_excat = $excat;
	$o_expost = $expost;
	$o_tag = $tag;
	$o_post_type = $post_type;
	$o_taxonomy = $taxonomy;
	$o_taxonomy_term = $taxonomy_term;
	$o_author = $author;
	
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

	if ($author) {
		$author = esc_sql($author);
	}

	if ($tag) {
		$tags = explode(",", $tag);
		$inc_term_tax_ids = array();
		$ex_term_tax_ids = array();
		foreach ($tags as $t_tag) {
			$t_tag = $wpdb->escape($t_tag);
			$term_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy
				WHERE term_id=$t_tag");
			if ($term_tax_id) {
				$inc_term_tax_ids[] = $term_tax_id;
			}
		}
		
		$tag = implode(",", $inc_term_tax_ids);
	}

	if (!empty($taxonomy)) {
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
	if ($tag) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
		    WHERE term_taxonomy_id IN ($tag))";
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
	if ($author) {
		$query_restrictions .= " AND doc IN (SELECT DISTINCT(ID) FROM $wpdb->posts
		    WHERE post_author IN ($author))";
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
		$o_term_cond = "(term LIKE '%#term#' OR term LIKE '#term#%') ";
	}
	else {
		$o_term_cond = " term = '#term#' ";
	}

	$min_word_length = get_option('relevanssi_min_word_length', 3);
	$search_again = false;
	do {
		foreach ($terms as $term) {
			if (strlen($term) < $min_word_length) continue;
			$term = $wpdb->escape(like_escape($term));
			$term_cond = str_replace('#term#', $term, $o_term_cond);		
			
			$query = "SELECT doc, term, tf, title FROM $relevanssi_table WHERE $term_cond $query_restrictions";
			$query = apply_filters('relevanssi_query_filter', $query);
			
			$matches = $wpdb->get_results($query);
			if (count($matches) < 1) {
				continue;
			}
			else {
				$no_matches = false;
			}
			
			relevanssi_populate_array($matches);
			
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
			$tag_boost = floatval(get_option('relevanssi_tag_boost'));
			$comment_boost = floatval(get_option('relevanssi_comment_boost'));
			
			$idf = log($D / (1 + $df));
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
		if (strlen($temp_term) >= $min_word_length)
			array_push($terms_without_stops, $temp_term);
	}
	$total_terms = count($terms_without_stops);

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
			$status = relevanssi_get_post_status($doc);
			$post_ok = true;
			$post_ok = apply_filters('relevanssi_post_ok', $doc);

			if ($post_ok) $hits[intval($i++)] = relevanssi_get_post($doc);
		}
	}

	if (count($hits) < 1) {
		if ($operator == "AND" AND get_option('relevanssi_disable_or_fallback') != 'on') {
			$return = relevanssi_search($q, $o_cat, $o_excat, $o_expost, $o_post_type, $o_taxonomy, $o_taxonomy_term, "OR", $o_tag, $o_author);
			extract($return);
		}
	}

	global $wp;	
	$default_order = get_option('relevanssi_default_orderby', 'relevance');
	isset($wp->query_vars["orderby"]) ? $orderby = $wp->query_vars["orderby"] : $orderby = $default_order;
	isset($wp->query_vars["order"]) ? $order = $wp->query_vars["order"] : $order = 'desc';
	if ($orderby != 'relevance')
		objectSort($hits, $orderby, $order);

	$return = array('hits' => $hits, 'body_matches' => $body_matches, 'title_matches' => $title_matches,
		'tag_matches' => $tag_matches, 'comment_matches' => $comment_matches, 'scores' => $scores,
		'term_hits' => $term_hits, 'query' => $q);

	return $return;
}

function relevanssi_default_post_ok($doc) {
	$post_ok = true;
	$status = relevanssi_get_post_status($doc);
	
	if ('publish' != $status) {
		$post_ok = false;
	}

	if ('private' == $status) {
		$post_ok = false;

		if (function_exists('awp_user_can')) {
			// Role-Scoper
			$current_user = wp_get_current_user();
			$post_ok = awp_user_can('read_post', $doc, $current_user->ID);
		}
		else {
			// Basic WordPress version
			$type = relevanssi_get_post_type($doc);
			$cap = 'read_private_' . $type . 's';
			if (current_user_can($cap)) {
				$post_ok = true;
			}
		}
	}
	
	// only show drafts in admin search
	if (in_array($status, array('draft', 'pending', 'future')) && is_admin()) {
		$post_ok = true;
	}

	if (relevanssi_s2member_level($doc) == 0) $post_ok = false; // not ok with s2member

	return $post_ok;
}

/**
 * Return values:
 *  2: full access to post
 *  1: show title only
 *  0: no access to post
 * -1: s2member not active
 */
function relevanssi_s2member_level($doc) {
	$return = -1;
	if (function_exists('is_permitted_by_s2member')) {
		// s2member
		$alt_view_protect = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["filter_wp_query"];
		
		if (version_compare (WS_PLUGIN__S2MEMBER_VERSION, "110912", ">="))
			$completely_hide_protected_search_results = (in_array ("all", $alt_view_protect) || in_array ("searches", $alt_view_protect)) ? true : false;
		else /* Backward compatibility with versions of s2Member, prior to v110912. */
			$completely_hide_protected_search_results = (strpos ($alt_view_protect, "all") !== false || strpos ($alt_view_protect, "searches") !== false) ? true : false;
		
		if (is_permitted_by_s2member($doc)) {
			// Show title and excerpt, even full content if you like.
			$return = 2;
		}
		else if (!is_permitted_by_s2member($doc) && $completely_hide_protected_search_results === false) {
			// Show title and excerpt. Alt View Protection is NOT enabled for search results. However, do NOT show full content body.
			$return = 1;
		}
		else {
			// Hide this search result completely.
			$return = 0;
		}
	}
	
	return $return;
}

function relevanssi_get_post($id) {
	global $relevanssi_post_array;
	
	if (isset($relevanssi_post_array[$id])) {
		$post = $relevanssi_post_array[$id];
	}
	else {
		$post = get_post($id);
	}
	return $post;
}

function relevanssi_populate_array($matches) {
	global $relevanssi_post_array, $wpdb;
	
	$ids = array();
	foreach ($matches as $match) {
		array_push($ids, $match->doc);
	}
	
	$ids = implode(',', $ids);
	$posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE id IN ($ids)");
	foreach ($posts as $post) {
		$relevanssi_post_array[$post->ID] = $post;
	}
}

function relevanssi_get_post_status($id) {
	global $relevanssi_post_array;
	
	if (isset($relevanssi_post_array[$id])) {
		$status = $relevanssi_post_array[$id]->post_status;
		if ('inherit' == $status) {
			$parent = $relevanssi_post_array[$id]->post_parent;
			$status = relevanssi_get_post_status($parent);
		}
		return $status;
	}
	else {
		return get_post_status($id);
	}
}


function relevanssi_get_post_type($id) {
	global $relevanssi_post_array;
	
	if (isset($relevanssi_post_array[$id])) {
		return $relevanssi_post_array[$id]->post_type;
	}
	else {
		return get_post_type($id);
	}
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

			//added by continent7 to make phrase-search in custom field available
			$query = "SELECT ID
				FROM $wpdb->posts AS p, $wpdb->postmeta AS m
				WHERE p.ID = m.post_id
				AND m.meta_value LIKE '%$phrase%'
				AND p.post_status = 'publish'";

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

	$content = apply_filters('relevanssi_excerpt_content', $content, $post, $query);
	$content = relevanssi_strip_invisibles($content); // removes <script>, <embed> &c with content
	$content = strip_tags($content); // this removes the tags, but leaves the content
	
	$content = preg_replace("/\n\r|\r\n|\n|\r/", " ", $content);
	
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

	if (relevanssi_s2member_level($post->ID) == 1) $excerpt = $post->post_excerpt;

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
			'@<iframe[^>]*?.*?</iframe>@siu',
			'@<del[^>]*?.*?</del>@siu',
        ),
		' ',
        $text );
    return $text;
}

if (get_option('relevanssi_highlight_docs', 'off') != 'off') {
	add_filter('the_content', 'relevanssi_highlight_in_docs', 11);
	if (get_option('relevanssi_hilite_title', 'off') != 'off') {
		add_filter('the_title', 'relevanssi_highlight_in_docs');
	}
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
	if (function_exists('mb_internal_encoding'))
		mb_internal_encoding("UTF-8");
	
	$terms = array_keys(relevanssi_tokenize($query, $remove_stopwords = true));

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

	get_option('relevanssi_word_boundaries', 'on') == 'on' ? $word_boundaries = true : $word_boundaries = false;
	foreach ($terms as $term) {
		if ($word_boundaries) {
			$excerpt = preg_replace("/(\b$term|$term\b)(?!([^<]+)?>)/iu", $start_emp_token . '\\1' . $end_emp_token, $excerpt);
		}
		else {
			$excerpt = preg_replace("/($term)(?!([^<]+)?>)/iu", $start_emp_token . '\\1' . $end_emp_token, $excerpt);
		}
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
	$custom_types = "";	
	switch ($type) {
		case "posts":
			$restriction = " AND (post.post_type = 'post'"; // add table alias to column for modified query - modified by renaissancehack
			break;
		case "pages":
			$restriction = " AND (post.post_type = 'page'"; // add table alias to column for modified query - modified by renaissancehack
			break;
		case "public":
			if (function_exists('get_post_types')) {
				$pt_1 = get_post_types(array('exclude_from_search' => '0'));
				$pt_2 = get_post_types(array('exclude_from_search' => false));
				$custom_types = implode(',', array_merge($pt_1, $pt_2));
				$allow_custom_types = false;
			}
			$restriction = "";
			break;
		case "both": 								// really should be "everything"
			$restriction = "";
			$allow_custom_types = false;
			break;
		case "custom":
			$restriction = "";
			$allow_custom_types = true;
			break;
		default:
			$restriction = "";
	}

	$negative_restriction = "";
	
	if ($allow_custom_types) $custom_types = get_option("relevanssi_custom_types");
	
	if (!empty($custom_types)) {
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
	$size = 0;
	
	if (!$extend) {
		// truncate table first
		$wpdb->query("TRUNCATE TABLE $relevanssi_table");
// BEGIN modified by renaissancehack
//  modified query to get child records that inherit their post_status
        $q = "SELECT *,parent.post_status as post_status
		FROM $wpdb->posts parent, $wpdb->posts post WHERE
        (parent.post_status IN ('publish', 'private', 'draft', 'pending', 'future'))
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
			$size = $limit;
			$limit = " LIMIT $limit";
		}
// BEGIN modified by renaissancehack
//  modified query to get child records that inherit their post_status
        $q = "SELECT *,parent.post_status as post_status
		FROM $wpdb->posts parent, $wpdb->posts post WHERE
        (parent.post_status IN ('publish', 'private', 'draft', 'pending', 'future'))
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
	
    echo '<div id="message" class="updated fade"><p>'
		. __((($size == 0) || (count($content) < $size)) ? "Indexing complete!" : "More to index...", "relevanssi")
		. '</p></div>';
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

/*
	Different cases:

	- 	Build index:
		global $post is NULL, $indexpost is a post object.
		
	-	Update post:
		global $post has the original $post, $indexpost is the ID of revision.
		
	-	Quick edit:
		global $post is an array, $indexpost is the ID of current revision.
*/
function relevanssi_index_doc($indexpost, $remove_first = false, $custom_fields = false, $bypassglobalpost = false) {
	global $wpdb, $relevanssi_table, $post;
	$post_was_null = false;
	$previous_post = NULL;

	if ($bypassglobalpost) {
		// if $bypassglobalpost is set, relevanssi_index_doc() will index the post object or post
		// ID as specified in $indexpost
		isset($post) ?
			$previous_post = $post : $post_was_null = true;
		is_object($indexpost) ?
			$post = $indexpost : $post = get_post($indexpost);
	}
	else {
		// Quick edit has an array in the global $post, so fetch the post ID for the post to edit.
		if (is_array($post)) {
			$post = $post['ID'];
		}
		
		if (!isset($post)) {
			// No $post set, so we need to use $indexpost, if it's a post object
			$post_was_null = true;
			if (is_object($indexpost)) {
				$post = $indexpost;
			}
		}
		else {
			// $post was set, let's grab the previous value in case we need it
			$previous_post = $post;
		}
		
		// At this point we should have something in $post; if not, quit.
		if ($post == NULL) {
			if ($post_was_null) $post = null;
			if ($previous_post) $post = $previous_post;
			return;
		}
		is_object($post) ? $ID = $post->ID : $ID = $post;
	}
	
	// Finally fetch the post again by ID. Complicated, yes, but unless we do this, we might end
	// up indexing the post before the updates come in.
	$post = get_post($ID);

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
				$pt_1 = get_post_types(array('exclude_from_search' => '0'));
				$pt_2 = get_post_types(array('exclude_from_search' => false));
				$public_types = array_merge($pt_1, $pt_2);
				if (in_array($post->post_type, $public_types)) $index_this_post = true;
			}
			else {
				$index_this_post = true;
			}
			break;
		case 'custom':
			if (in_array($post->post_type, $custom_types)) $index_this_post = true;
			break;
		case 'both':
			$index_this_post = true;
			break;
	}

	if ($post->post_type == 'attachment') {
		get_option('relevanssi_index_attachments') == 'on' ?
			$index_this_post = true : $index_this_post = false;
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
	if (!$index_this_post) {
		if ($post_was_null) $post = null;
		if ($previous_post) $post = $previous_post;
		return;
	}

	$n = 0;	
	$min_word_length = get_option('relevanssi_min_word_length', 3);
	$titles = relevanssi_tokenize($post->post_title);

	//Added by OdditY - INDEX COMMENTS of the POST ->
	if ("none" != get_option("relevanssi_index_comments")) {
		$pcoms = relevanssi_get_comments($post->ID);
		if ($pcoms != "") {
			$pcoms = relevanssi_strip_invisibles($pcoms);
			$pcoms = strip_tags($pcoms);
			$pcoms = relevanssi_tokenize($pcoms);		
			if (count($pcoms) > 0) {
				foreach ($pcoms as $pcom => $count) {
					if (strlen($pcom) < $min_word_length) continue;
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
					if (strlen($pcat) < $min_word_length) continue;
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
				$post->post_content .= ' ' . (is_array($value) ? implode(' ', $value) : $value);
			}
		}
	}

	if (isset($post->post_excerpt) && ("on" == get_option("relevanssi_index_excerpt") || "attachment" == $post->post_type)) { // include excerpt for attachments which use post_excerpt for captions - modified by renaissancehack
		$post->post_content .= ' ' . $post->post_excerpt;
	}
	
	$contents = $post->post_content;
	
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

	$contents = relevanssi_strip_invisibles($contents);
	
	$contents = strip_tags($contents);
	$contents = relevanssi_tokenize($contents);
	
	if (count($titles) > 0) {
		foreach ($titles as $title => $count) {
			if (strlen($title) < $min_word_length) continue;
			$n++;
			
			$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
			VALUES ($post->ID, '$title', $count, 1)");
			
			// a slightly clumsy way to handle titles, I'll try to come up with something better
		}
	}
	
	if (count($contents) > 0) {
		foreach ($contents as $content => $count) {
			if (strlen($content) < $min_word_length) continue;
			$n++;
			$wpdb->query("INSERT INTO $relevanssi_table (doc, term, tf, title)
			VALUES ($post->ID, '$content', $count, 0)");
		}
	}

	// Restore the global $post to whatever it was.
	if ($post_was_null) $post = null;
	if ($previous_post) $post = $previous_post;

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
	
	$min_word_length = get_option('relevanssi_min_word_length', 3);
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
				if (strlen($ptag) < $min_word_length) continue;
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
	$tokens = array();
	if (is_array($str)) {
		foreach ($str as $part) {
			$tokens = array_merge($tokens, relevanssi_tokenize($part, $remove_stops));
		}
	}
	if (is_array($str)) return $tokens;

	if (function_exists('mb_internal_encoding')) 
		mb_internal_encoding("UTF-8");

	if ($remove_stops) {
		$stopword_list = relevanssi_fetch_stopwords();
	}
	$str = mb_strtolower(apply_filters('relevanssi_remove_punctuation', $str));

	$tokens = array();

	$t = strtok($str, "\n\t ");
	while ($t !== false) {
		$accept = true;
		if ($remove_stops == false) {
			$accept = true;
		}
		else {
			if (count($stopword_list) > 0) {	//added by OdditY -> got warning when stopwords table was empty
				if (in_array($t, $stopword_list)) {
					$accept = false;
				}
			}
		}
		if ($accept) {
			$t = relevanssi_mb_trim($t);
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

function relevanssi_mb_trim($string) { 
    $string = preg_replace( "/(^\s+)|(\s+$)/us", "", $string ); 
    return $string; 
} 

function relevanssi_remove_punct($a) {
		$a = strip_tags($a);
		$a = stripslashes($a);

		$a = str_replace('&#8217;', '', $a);
		$a = str_replace("'", '', $a);
		$a = str_replace("´", '', $a);
		$a = str_replace("’", '', $a);
		$a = str_replace("‘", '', $a);
		$a = str_replace("„", '', $a);
		$a = str_replace("·", '', $a);
		$a = str_replace("”", '', $a);
		$a = str_replace("“", '', $a);
		$a = str_replace("…", '', $a);
		$a = str_replace("€", '', $a);
		$a = str_replace("&shy;", '', $a);

		$a = str_replace("—", ' ', $a);
		$a = str_replace("–", ' ', $a);
		$a = str_replace("×", ' ', $a);
        $a = preg_replace('/[[:punct:]]+/u', ' ', $a);

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

}
?>