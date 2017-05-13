<?php
/*
Plugin Name: Relevanssi
Plugin URI: http://www.mikkosaari.fi/relevanssi/
Description: This plugin replaces WordPress search with a relevance-sorting search.
Version: 1.4
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
register_deactivation_hook(__FILE__,'unset_relevanssi_options');
add_action('admin_menu', 'relevanssi_menu');
add_filter('posts_where', 'relevanssi_kill');
add_filter('the_posts', 'relevanssi_query');
add_filter('post_limits', 'relevanssi_getLimit');
//add_action('edit_post', 'relevanssi_edit'); // not necessary, publish_post does the job
add_action('delete_post', 'relevanssi_delete');
add_action('publish_post', 'relevanssi_publish');
add_action('publish_page', 'relevanssi_publish');
add_action('future_publish_post', 'relevanssi_publish');

global $wpSearch_low;
global $wpSearch_high;
global $relevanssi_table;
global $stopword_table;
global $log_table;
global $stopword_list;
global $title_boost_default;

$wpSearch_low = 0;
$wpSearch_high = 0;
$relevanssi_table = $wpdb->prefix . "relevanssi";
$stopword_table = $wpdb->prefix . "relevanssi_stopwords";
$log_table = $wpdb->prefix . "relevanssi_log";
$title_boost_default = 5;

function unset_relevanssi_options() {
	delete_option('relevanssi_title_boost');
	delete_option('relevanssi_admin_search');
	delete_option('relevanssi_highlight');
	delete_option('relevanssi_txt_col');
	delete_option('relevanssi_bg_col');
	delete_option('relevanssi_css');
	delete_option('relevanssi_excerpts');
	delete_option('relevanssi_excerpt_length');
	delete_option('relevanssi_excerpt_type');
	delete_option('relevanssi_log_queries');
	delete_option('relevanssi_cat');
}

function relevanssi_menu() {
	add_options_page(
		'Relevanssi',
		'Relevanssi',
		'manage_options',
		__FILE__,
		'relevanssi_options'
	);
}
	
function relevanssi_edit($post) {
	relevanssi_add($post);
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
	global $wpdb, $relevanssi_table, $stopword_table, $title_boost_default;
	
	add_option('relevanssi_title_boost', $title_boost_default);
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
		
		$sql = "ALTER TABLE $relevanssi_table ADD INDEX 'docs' ('doc')";
		$wpdb->query($sql);

		$sql = "ALTER TABLE $relevanssi_table ADD INDEX 'terms' ('term')";
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

		$q = $wp->query_vars["s"];
		$cat = $wp->query_vars["cat"];
		if (!$cat) {
			$cat = get_option('relevanssi_cat');
			if (0 == $cat) {
				$cat = false;
			}
		}

		$hits = relevanssi_search($q, $cat);
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
			if ('on' == $make_excerpts) {			
				$post->post_excerpt = relevanssi_do_excerpt($post, $q);
			}
			$posts[] = $post;
		}
	}
	
	return $posts;
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
function relevanssi_search($q, $cat = false) {
	global $relevanssi_table, $wpdb;

	$hits = array();
	
	if ($cat) {
		$cats = explode(",", $cat);
		$term_tax_ids = array();
		foreach ($cats as $cat) {
			$cat = $wpdb->escape($cat);
			$term_tax_id = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy
				WHERE term_id=$cat");
			if ($term_tax_id) {
				$term_tax_ids[] = $term_tax_id;
			}
		}
		
		$cat = implode(",", $term_tax_ids);
	}

	$remove_stopwords = false;
	$terms = relevanssi_tokenize($q, $remove_stopwords);
	if (count($terms) < 1) {
		// Tokenizer killed all the search terms.
		return $hits;
	}
	$terms = array_keys($terms); // don't care about tf in query

	$D = $wpdb->get_var("SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table");
	
	$total_hits = 0;
	foreach ($terms as $term) {
		$term = $wpdb->escape(like_escape($term));
		$query = "SELECT doc, term, tf, title FROM $relevanssi_table WHERE term = '$term'";
		if ($cat) {
			$query .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			    WHERE term_taxonomy_id IN ($cat))";
		}

		$matches = $wpdb->get_results($query);
		if (count($matches) < 1) {
			$query = "SELECT doc, term, tf, title FROM $relevanssi_table
			WHERE (term LIKE '$term%' OR term LIKE '%$term')";
			if ($cat) {
				$query .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
				    WHERE term_taxonomy_id IN ($cat))";
			}
			
			$matches = $wpdb->get_results($query);
		}
		$total_hits += count($matches);

		$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = '$term'";
		if ($cat) {
			$query .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
			    WHERE term_taxonomy_id IN ($cat))";
		}

		$df = $wpdb->get_var($query);

		if ($df < 1) {
			$query = "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table
				WHERE (term LIKE '%$term' OR term LIKE '$term%')";
			if ($cat) {
				$query .= " AND doc IN (SELECT DISTINCT(object_id) FROM $wpdb->term_relationships
				    WHERE term_taxonomy_id IN ($cat))";
			}
			$df = $wpdb->get_var($query);
		}
		
		$title_boost = intval(get_option('relevanssi_title_boost'));
		foreach ($matches as $match) {
			$tf = $match->tf;
			$idf = log($D / (1 + $df));
			$weight = $tf * $idf;
			if ($match->title) {
				$weight = $weight * $title_boost;
			}
			if (isset($doc_weight[$match->doc])) {
				$doc_weight[$match->doc] += $weight;
			}
			else {
				$doc_weight[$match->doc] = $weight;
			}
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

	return $hits;
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

	$remove_stopwords = true;
	$terms = relevanssi_tokenize($query, $remove_stopwords);

	$content = apply_filters('the_content', $post->post_content);
	$content = relevanssi_strip_invisibles($content); // removes <script>, <embed> &c with content
	$content = strip_tags($content); // this removes the tags, but leaves the content
	$content = strip_shortcodes($content);
	$content = ereg_replace("/\n\r|\r\n|\n|\r/", " ", $content);
	
	$excerpt = "";
	
	if ("chars" == $type) {
		$post_length = strlen($content);
			
		$start = false;
		foreach (array_keys($terms) as $term) {
			if (function_exists('mb_stripos')) {
				$pos = mb_stripos($content, $term);
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
					$pos = mb_stripos($excerpt_slice, $term);
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

	foreach ($terms as $term) {
		$pos = 0;
		$low_excerpt = mb_strtolower($excerpt);
		while ($pos !== false) {
			$pos = mb_strpos($low_excerpt, $term, $pos);
			if ($pos !== false) {
				$excerpt = mb_substr($excerpt, 0, $pos)
						 . $start_emp
						 . mb_substr($excerpt, $pos, mb_strlen($term))
						 . $end_emp
						 . mb_substr($excerpt, $pos + mb_strlen($term));
				$low_excerpt = mb_strtolower($excerpt);
				$pos = $pos + mb_strlen($start_emp) + mb_strlen($end_emp);
			}
		}
	}

	return $excerpt;
}

function relevanssi_build_index($extend = false) {
	global $wpdb, $relevanssi_table;
	
	$n = 0;
	
	if (!$extend) {
		// truncate table first
		$wpdb->query("TRUNCATE TABLE $relevanssi_table");
		$q = "SELECT ID, post_content, post_title
		FROM $wpdb->posts WHERE post_status='publish'";
	}
	else {
		// extending, so no truncate and skip the posts already in the index
		$q = "SELECT ID, post_content, post_title
		FROM $wpdb->posts WHERE post_status='publish' AND
		ID NOT IN (SELECT DISTINCT(doc) FROM $relevanssi_table)";
	}
	
	$content = $wpdb->get_results($q);
	foreach ($content as $post) {
		$n += relevanssi_index_doc($post, false);
		// n calculates the number of insert queries
	}
	
	echo '<div id="message" class="update fade"><p>Indexing complete!</p></div>';
}

function relevanssi_remove_doc($id) {
	global $wpdb, $relevanssi_table;
	
	$q = "DELETE FROM $relevanssi_table WHERE doc=$id";
	$wpdb->query($q);
}

function relevanssi_index_doc($post, $remove_first = false) {
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
	
	$contents = relevanssi_strip_invisibles($post->post_content);
	$contents = strip_tags($contents);
	$contents = strip_shortcodes($contents);
	
	$contents = relevanssi_tokenize($post->post_content);
	
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

function relevanssi_tokenize($str, $remove_stops = true) {
	$stopword_list = relevanssi_fetch_stopwords();

	$str = strtolower(relevanssi_remove_punct($str));
	$t = strtok($str, "\n\t ");
	while ($t !== false) {
		$accept = true;
		if (in_array($t, $stopword_list)) {
			$accept = false;
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
		$a = str_replace("—", " ", $a);
        $a = preg_replace('/[[:punct:]]+/', ' ', $a);
        $a = preg_replace('/[[:space:]]+/', ' ', $a);
		$a = trim($a);
        return $a;
}

// This function is from Kenny Katzgrau
function relevanssi_kill($where) {
	if (is_search() && !is_admin()) {	
		$where = "AND (0 = 1)";
	}
	return $where;
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
		relevanssi_build_index();
	}

	if ($_REQUEST['index_extend']) {
		relevanssi_build_index(true);
	}
	
	if ($_REQUEST['search']) {
		relevanssi_search($_REQUEST['q']);
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
		$boost = intval($_REQUEST['relevanssi_title_boost']);
		if ($boost != 0) {
			update_option('relevanssi_title_boost', $boost);
		}
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
		printf("<div id='message' class='update fade'><p>Term '%s' added to stopwords!</p></div>", $term);
	}
	else {
		printf("<div id='message' class='update fade'><p>Couldn't add term '%s' to stopwords!</p></div>", $term);
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

	if (version_compare($wp_version, '2.8dev', '>' )) {
		$src = plugins_url('delete.png', __FILE__);
	}
	else {
		$src = plugins_url('relevanssi/delete.png');
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
	global $title_boost_default;
	
	$title_boost = get_option('relevanssi_title_boost');
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

	$log_queries = get_option('relevanssi_log_queries');
	if ('on' == $log_queries) {
		$log_queries = 'checked="checked"';
	}
	else {
		$log_queries = '';
	}
	
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
	
	$txt_col = get_option('relevanssi_txt_col');
	$bg_col = get_option('relevanssi_bg_col');
	$css = get_option('relevanssi_css');
	$class = get_option('relevanssi_class');
	
	$cat = get_option('relevanssi_cat');
	
	$title_boost_txt = __('Title boost:', 'relevanssi');
	$title_boost_desc = sprintf(__('This needs to be an integer, default: %d', 'relevanssi'), $title_boost_default);
	$admin_search_txt = __('Use search for admin:', 'relevanssi');
	$admin_search_desc = __('If checked, Relevanssi will be used for searches in the admin interface', 'relevanssi');
	$cat_txt = __('Restrict search to these categories and tags:', 'relevanssi');
	$cat_desc = __("Enter a comma-separated list of category and tag IDs to restrict search to those categories or tags. You can also use <code>&lt;input type='hidden' name='cat' value='list of cats and tags' /&gt;</code> in your search form. The input field will overrun this setting.", 'relevanssi');
	$excerpt_txt = __("Create custom search result snippets:", "relevanssi");
	$excerpt_desc = __("If checked, Relevanssi will create excerpts that contain the search term hits. To make them work, make sure your search result template uses the_excerpt() to display post excerpts.", 'relevanssi');
	$excerpt_length_txt = __("Length of the snippet:", "relevanssi");
	$excerpt_length_desc = __("This must be an integer", "relevanssi");
	$log_queries_txt = __("Keep a log of user queries:", "relevanssi");
	$log_queries_desc = __("If checked, Relevanssi will log user queries.", 'relevanssi');
	$highlight_txt = __("Highlight query terms in search results:", 'relevanssi');
	$highlight_comment = __("Highlighting isn't available unless you use custom snippets", 'relevanssi');
	
	$submit_value = __('Save', 'relevanssi');
	$building_the_index = __('Building the index', 'relevanssi');
	$index_p1 = __("After installing the plugin, you need to build the index. This generally needs to be done once, you don't have to re-index unless something goes wrong. Indexing is a heavy task and might take more time than your servers allow. If the indexing cannot be finished - for example you get a blank screen or something like that after indexing - you can continue indexing from where you left by clicking 'Continue indexing'. Clicking 'Build the index' will delete the old index, so you can't use that.", 'relevanssi');
	$index_p2 = __("So, if you build the index and don't get the 'Indexing complete' in the end, keep on clicking the 'Continue indexing' button until you do. On my blogs, I was able to index ~400 pages on one go, but had to continue indexing twice to index ~950 pages.", 'relevanssi');
	$build_index = __("Build the index", 'relevanssi');
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

	echo <<<EOHTML
	<br />
	<form method="post">
	<label for="relevanssi_title_boost">$title_boost_txt 
	<input type="text" name="relevanssi_title_boost" size="4" value="$title_boost" /></label>
	<small>$title_boost_desc</small>
	<br />
	<label for="relevanssi_admin_search">$admin_search_txt
	<input type="checkbox" name="relevanssi_admin_search" $admin_search" /></label>
	<small>$admin_search_desc</small>

	<br /><br />

	<label for="relevanssi_cat">$cat_txt
	<input type="text" name="relevanssi_cat" size="20" value="$cat" /></label><br />
	<small>$cat_desc</small>

	<br /><br />

	<label for="relevanssi_log_queries">$log_queries_txt
	<input type="checkbox" name="relevanssi_log_queries" $log_queries" /></label>
	<small>$log_queries_desc</small>

	<br /><br />

	<label for="relevanssi_excerpts">$excerpt_txt
	<input type="checkbox" name="relevanssi_excerpts" $excerpts /></label><br />
	<small>$excerpt_desc</small>
	
	<br />
	
	<label for="relevanssi_excerpt_length">$excerpt_length_txt
	<input type="text" name="relevanssi_excerpt_length" size="4" value="$excerpt_length" /></label>
	<select name="relevanssi_excerpt_type">
	<option value="chars" $excerpt_chars">characters</option>
	<option value="words" $excerpt_words">words</option>
	</select><br />
	<small>$excerpt_length_desc</small>
	
	<br /><br />

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
	
	<br />
	<br />
	
	<input type="submit" name="submit" value="$submit_value" />

	<h3>$building_the_index</h3>
	
	<p>$index_p1</p>
	
	<p>$index_p2</p>

	<input type="submit" name="index" value="$build_index" />

	<input type="submit" name="index_extend" value="$continue_index" />

	</form>
EOHTML;
}
?>