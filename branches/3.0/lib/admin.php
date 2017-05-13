<?php

function relevanssi_options() {
	$options_txt = __('Relevanssi Search Options', 'relevanssi');

	printf("<div class='wrap'><?php screen_icon(); ?><h2>%s</h2>", $options_txt);
	if (!empty($_REQUEST)) {
		if (isset($_REQUEST['hidesponsor'])) {
			update_option('relevanssi_hidesponsor', 'true');
		}

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

		if (isset($_REQUEST['truncate'])) {
			$clear_all = true;
			relevanssi_truncate_cache($clear_all);
		}
	}
	relevanssi_options_form();
	
	relevanssi_common_words();
	
	echo "<div style='clear:both'></div>";
	
	echo "</div>";
}

function relevanssi_search_stats() {
	$options_txt = __('Relevanssi User Searches', 'relevanssi');

	if (isset($_REQUEST['relevanssi_reset']) and current_user_can('manage_options')) {
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
	$wpdb->query($query);
	
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
		if ($value == 0) $value = 3;
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

	if (!isset($_REQUEST['relevanssi_wpml_only_current'])) {
		$_REQUEST['relevanssi_wpml_only_current'] = "off";
	}

	if (!isset($_REQUEST['relevanssi_word_boundaries'])) {
		$_REQUEST['relevanssi_word_boundaries'] = "off";
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
	if (isset($_REQUEST['relevanssi_wpml_only_current'])) update_option('relevanssi_wpml_only_current', $_REQUEST['relevanssi_wpml_only_current']);
	if (isset($_REQUEST['relevanssi_word_boundaries'])) update_option('relevanssi_word_boundaries', $_REQUEST['relevanssi_word_boundaries']);
	if (isset($_REQUEST['relevanssi_default_orderby'])) update_option('relevanssi_default_orderby', $_REQUEST['relevanssi_default_orderby']);
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
	
	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(1, __("Today and yesterday", 'relevanssi'));
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(7, __("Last 7 days", 'relevanssi'));
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(30, __("Last 30 days", 'relevanssi'));
	echo '</div>';

	echo '<div style="clear: both"></div>';
	
	echo '<h3>' . __("Unsuccessful Queries", 'relevanssi') . '</h3>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(1, __("Today and yesterday", 'relevanssi'), 'bad');
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(7, __("Last 7 days", 'relevanssi'), 'bad');
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%'>";
	relevanssi_date_queries(30, __("Last 30 days", 'relevanssi'), 'bad');
	echo '</div>';

	if ( current_user_can('manage_options') ) {

		echo '<div style="clear: both"></div>';

		echo <<<EOR
<h3>Reset Logs</h3>

<form method="post">
<p>To reset the logs, type 'reset' into the box here <input type="text" name="relevanssi_reset_code" />
and click <input type="submit" name="relevanssi_reset" value="Reset" class="button" /></p>
</form>
EOR;

	}

	echo "</div>";
}

function relevanssi_date_queries($d, $title, $version = 'good') {
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
		echo "<table class='widefat'><thead><tr><th colspan='3'>$title</th></tr></thead><tbody><tr><th>Query</th><th>#</th><th>Hits</th></tr>";
		foreach ($queries as $query) {
			$url = get_bloginfo('url');
			$u_q = urlencode($query->query);
			echo "<tr><td style='padding: 3px 5px'><a href='$url/?s=$u_q'>" . esc_attr( $query->query ) . "</a></td><td style='padding: 3px 5px; text-align: center'>" . $query->cnt . "</td><td style='padding: 3px 5px; text-align: center'>" . $query->hits . "</td></tr>";
		}
		echo "</tbody></table>";
	}
}

function relevanssi_options_form() {
	global $title_boost_default, $tag_boost_default, $comment_boost_default, $wpdb, $relevanssi_table, $relevanssi_cache;
	
	wp_enqueue_style('dashboard');
	wp_print_styles('dashboard');
	wp_enqueue_script('dashboard');
	wp_print_scripts('dashboard');

	$docs_count = $wpdb->get_var("SELECT COUNT(DISTINCT doc) FROM $relevanssi_table");
	$biggest_doc = $wpdb->get_var("SELECT doc FROM $relevanssi_table ORDER BY doc DESC LIMIT 1");
	$cache_count = $wpdb->get_var("SELECT COUNT(tstamp) FROM $relevanssi_cache");
	
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
	$highlight_mark = "";
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
			$highlight_mark = 'selected="selected"';
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
	$index_type_custom = "";
	$index_type_both = "";
	switch ($index_type) {
		case "posts":
			$index_type_posts = 'selected="selected"';
			break;
		case "pages":
			$index_type_pages = 'selected="selected"';
			break;
		case "custom":
			$index_type_custom = 'selected="selected"';
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

	$orderby = get_option('relevanssi_default_orderby');
	$orderby_relevance = ('relevance' == $orderby ? 'selected="selected"' : '');
	$orderby_date = ('post_date' == $orderby ? 'selected="selected"' : '');
	
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
	
	$wpml_only_current = ('on' == get_option('relevanssi_wpml_only_current') ? 'checked="checked"' : ''); 

	$word_boundaries = ('on' == get_option('relevanssi_word_boundaries') ? 'checked="checked"' : ''); 

?>
	
<div class='postbox-container' style='width:70%;'>
	<form method='post'>
	
    <p><a href="#basic"><?php _e("Basic options", "relevanssi"); ?></a> |
	<a href="#logs"><?php _e("Logs", "relevanssi"); ?></a> |
    <a href="#exclusions"><?php _e("Exclusions and restrictions", "relevanssi"); ?></a> |
    <a href="#excerpts"><?php _e("Custom excerpts", "relevanssi"); ?></a> |
    <a href="#highlighting"><?php _e("Highlighting search results", "relevanssi"); ?></a> |
    <a href="#indexing"><?php _e("Indexing options", "relevanssi"); ?></a> |
    <a href="#caching"><?php _e("Caching", "relevanssi"); ?></a> |
    <a href="#synonyms"><?php _e("Synonyms", "relevanssi"); ?></a> |
    <a href="#stopwords"><?php _e("Stopwords", "relevanssi"); ?></a> |
    <a href="#uninstall"><?php _e("Uninstalling", "relevanssi"); ?></a> |
    <strong><a href="http://www.relevanssi.com/buy-premium/?utm_source=plugin&utm_medium=link&utm_campaign=buy"><?php _e('Buy Relevanssi Premium', 'relevanssi'); ?></a></strong>
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
	
	<p><?php _e('These values affect the weights of the documents. These are all multipliers, so 1 means no change in weight, less than 1 means less weight, and more than 1 means more weight. Setting something to zero makes that worthless. For example, if title weight is more than 1, words in titles are more significant than words elsewhere. If title weight is 0, words in titles won\'t make any difference to the search results.', 'relevanssi'); ?></p>
	
	<label for='relevanssi_title_boost'><?php _e('Title weight:', 'relevanssi'); ?> 
	<input type='text' name='relevanssi_title_boost' size='4' value='<?php echo $title_boost ?>' /></label>
	<small><?php printf(__('Default: %s', 'relevanssi'), $title_boost_default); ?></small>
	<br />
	<label for='relevanssi_tag_boost'><?php _e('Tag weight:', 'relevanssi'); ?> 
	<input type='text' name='relevanssi_tag_boost' size='4' value='<?php echo $tag_boost ?>' /></label>
	<small><?php printf(__('Default: %s', 'relevanssi'), $tag_boost_default); ?></small>
	<br />
	<label for='relevanssi_comment_boost'><?php _e('Comment weight:', 'relevanssi'); ?> 
	<input type='text' name='relevanssi_comment_boost' size='4' value='<?php echo $comment_boost ?>' /></label>
	<small><?php printf(__('Default: %s', 'relevanssi'), $comment_boost_default); ?></small>

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

	<label for='relevanssi_default_orderby'><?php _e('Default order for results:', 'relevanssi'); ?>
	<select name='relevanssi_default_orderby'>
	<option value='relevance' <?php echo $orderby_relevance ?>><?php _e("Relevance (highly recommended)", "relevanssi"); ?></option>
	<option value='post_date' <?php echo $orderby_date ?>><?php _e("Post date", "relevanssi"); ?></option>
	</select></label><br />

	<br /><br />

	<label for='relevanssi_fuzzy'><?php _e('When to use fuzzy matching?', 'relevanssi'); ?>
	<select name='relevanssi_fuzzy'>
	<option value='sometimes' <?php echo $fuzzy_sometimes ?>><?php _e("When straight search gets no hits", "relevanssi"); ?></option>
	<option value='always' <?php echo $fuzzy_always ?>><?php _e("Always", "relevanssi"); ?></option>
	<option value='never' <?php echo $fuzzy_never ?>><?php _e("Don't use fuzzy search", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("Straight search matches just the term. Fuzzy search matches everything that begins or ends with the search term.", "relevanssi"); ?></small>

	<?php if (function_exists('icl_object_id')) : ?>
	<h3 id="wpml"><?php _e('WPML compatibility', 'relevanssi'); ?></h3>
	
	<label for='relevanssi_wpml_only_current'><?php _e("Limit results to current language:", "relevanssi"); ?>
	<input type='checkbox' name='relevanssi_wpml_only_current' <?php echo $wpml_only_current ?> /></label>
	<small><?php _e("If this option is checked, Relevanssi will only return results in the current active language. Otherwise results will include posts in every language.", "relevanssi");?></small>
	
	<?php endif; ?>

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
	<option value='mark' <?php echo $highlight_mark ?>>&lt;mark&gt;</option>
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

	<br />
	
	<label for='relevanssi_word_boundaries'><?php _e("Uncheck this if you use non-ASCII characters:", 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_word_boundaries' <?php echo $word_boundaries ?> /></label>
	<small><?php _e("If you use non-ASCII characters (like Cyrillic alphabet) and the highlights don't work, uncheck this option to make highlights work.", "relevanssi"); ?></small>

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
	
	<input type='submit' name='submit' value='<?php _e('Save the options', 'relevanssi'); ?>' class="button button-primary"  />

	<h3 id="indexing"><?php _e('Indexing options', 'relevanssi'); ?></h3>
	
	<label for='relevanssi_index_type'><?php _e("What to include in the index", "relevanssi"); ?>:
	<select name='relevanssi_index_type'>
	<option value='both' <?php echo $index_type_both ?>><?php _e("Everything", "relevanssi"); ?></option>
	<option value='public' <?php echo $index_type_public ?>><?php _e("All public post types", "relevanssi"); ?></option>
	<option value='posts' <?php echo $index_type_posts ?>><?php _e("Posts", "relevanssi"); ?></option>
	<option value='pages' <?php echo $index_type_pages ?>><?php _e("Pages", "relevanssi"); ?></option>
	<option value='custom' <?php echo $index_type_custom ?>><?php _e("Custom, set below", "relevanssi"); ?></option>
	</select></label><br />
	<small><?php _e("This determines which post types are included in the index. Choosing 'everything'
	will include posts, pages and all custom post types. 'All public post types' includes all
	registered post types that don't have the 'exclude_from_search' set to true. This includes post,
	page, and possible custom types. 'All public types' requires at least WP 2.9, otherwise it's the
	same as 'everything'. If you choose 'Custom', only the post types listed below are indexed.
	Note: attachments are covered with a separate option below.", "relevanssi"); ?></small>

	<br /><br />
	
	<label for='relevanssi_custom_types'><?php _e("Custom post types to index", "relevanssi"); ?>:
	<input type='text' name='relevanssi_custom_types' size='30' value='<?php echo $custom_types ?>' /></label><br />
	<small><?php _e("If you don't want to index all custom post types, list here the custom post types
	you want to see indexed. List comma-separated post type names (as used in the database). You can
	also use a hidden field in the search form to restrict the search to a certain post type:
	<code>&lt;input type='hidden' name='post_type' value='comma-separated list of post types'
	/&gt;</code>. If you choose 'All public post types' or 'Everything' above, this option has no
	effect. You can exclude custom post types with the minus notation, for example '-foo,bar,-baz'
	would include 'bar' and exclude 'foo' and 'baz'.", "relevanssi"); ?></small>

	<br /><br />

	<label for='relevanssi_min_word_length'><?php _e("Minimum word length to index", "relevanssi"); ?>:
	<input type='text' name='relevanssi_min_word_length' size='30' value='<?php echo $min_word_length ?>' /></label><br />
	<small><?php _e("Words shorter than this number will not be indexed.", "relevanssi"); ?></small>

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

	<input type='submit' name='index' value='<?php _e("Save indexing options and build the index", 'relevanssi'); ?>' class="button button-primary"  />

	<input type='submit' name='index_extend' value='<?php _e("Continue indexing", 'relevanssi'); ?>'  class="button" />

	<h3 id="caching"><?php _e("Caching", "relevanssi"); ?></h3>

	<p><?php _e("Warning: In many cases caching is not useful, and in some cases can be even harmful. Do not
	activate cache unless you have a good reason to do so.", 'relevanssi'); ?></p>
	
	<label for='relevanssi_enable_cache'><?php _e('Enable result and excerpt caching:', 'relevanssi'); ?>
	<input type='checkbox' name='relevanssi_enable_cache' <?php echo $enable_cache ?> /></label><br />
	<small><?php _e("If checked, Relevanssi will cache search results and post excerpts.", 'relevanssi'); ?></small>

	<br /><br />
	
	<label for='relevanssi_cache_seconds'><?php _e("Cache expire (in seconds):", "relevanssi"); ?>
	<input type='text' name='relevanssi_cache_seconds' size='30' value='<?php echo $cache_seconds ?>' /></label><br />
	<small><?php _e("86400 = day", "relevanssi"); ?></small>

	<br /><br />
	
	<?php _e("Entries in the cache", 'relevanssi'); ?>: <?php echo $cache_count; ?>

	<br /><br />
	
	<input type='submit' name='truncate' value='<?php _e('Clear all caches', 'relevanssi'); ?>' class="button" />

	<h3 id="synonyms"><?php _e("Synonyms", "relevanssi"); ?></h3>
	
	<p><textarea name='relevanssi_synonyms' rows='9' cols='60'><?php echo $synonyms ?></textarea></p>

	<p><small><?php _e("Add synonyms here in 'key = value' format. When searching with the OR operator, any search of 'key' will be expanded to include 'value' as well. Using phrases is possible. The key-value pairs work in one direction only, but you can of course repeat the same pair reversed.", "relevanssi"); ?></small></p>

	<input type='submit' name='submit' value='<?php _e('Save the options', 'relevanssi'); ?>'  class="button"  />

	<h3 id="stopwords"><?php _e("Stopwords", "relevanssi"); ?></h3>
	
	<?php relevanssi_show_stopwords(); ?>
	
	<h3 id="uninstall"><?php _e("Uninstalling the plugin", "relevanssi"); ?></h3>
	
	<p><?php _e("If you want to uninstall the plugin, start by clicking the button below to wipe clean the options and tables created by the plugin, then remove it from the plugins list.", "relevanssi");	 ?></p>
	
	<input type='submit' name='uninstall' value='<?php _e("Remove plugin data", "relevanssi"); ?>'  class="button" />

	</form>
</div>

	<?php

	relevanssi_sidebar();
}

function relevanssi_show_stopwords() {
	global $wpdb, $stopword_table, $wp_version;

	_e("<p>Enter a word here to add it to the list of stopwords. The word will automatically be removed from the index, so re-indexing is not necessary. You can enter many words at the same time, separate words with commas.</p>", 'relevanssi');

?><label for="addstopword"><p><?php _e("Stopword(s) to add: ", 'relevanssi'); ?><textarea name="addstopword" rows="2" cols="40"></textarea>
<input type="submit" value="<?php _e("Add", 'relevanssi'); ?>" class='button' /></p></label>
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
	$exportlist = array();
	foreach ($results as $stopword) {
		$sw = $stopword->stopword; 
		printf('<li style="display: inline;"><input type="submit" name="removestopword" value="%s"/></li>', $sw, $src, $sw);
		array_push($exportlist, $sw);
	}
	echo "</ul>";
	
?>
<p><input type="submit" name="removeallstopwords" value="<?php _e('Remove all stopwords', 'relevanssi'); ?>" class='button' /></p>
<?php

	$exportlist = implode(", ", $exportlist);
	
?>
<p><?php _e("Here's a list of stopwords you can use to export the stopwords to another blog.", "relevanssi"); ?></p>

<textarea name="stopwords" rows="2" cols="40"><?php echo $exportlist; ?></textarea>
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
			<h3 class="hndle"><span>Buy Relevanssi Premium!</span></h3>
			<div class="inside">
<p>Do you want more features? Support Relevanssi development? Get a
better search experience for your users?</p>

<p><strong>Go Premium!</strong> Buy Relevanssi Premium. See <a href="http://www.relevanssi.com/features/?utm_source=plugin&utm_medium=link&utm_campaign=features">feature
comparison</a> and <a href="http://www.relevanssi.com/buy-premium/?utm_source=plugin&utm_medium=link&utm_campaign=license">license prices</a>.</p>
			</div>
		</div>
	</div>

		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
				<h3 class="hndle"><span>Earn money with Relevanssi!</span></h3>
				<div class="inside">
					<p>Relevanssi Premium has an affiliate program.
					Earn 50% commission on Premium licenses you sell!</p>
					
					<p><span style="color: #228B22; font-weight: bold">$25 bonus</span> to all new
					affiliates.</p>
			
					<p><a href="http://www.relevanssi.com/affiliates/?utm_source=plugin&utm_medium=link&utm_campaign=affiliates">More info here</a></p>
				</div>
			</div>
		</div>
		
		<div class="meta-box-sortables" style="min-height: 0">
			<div id="relevanssi_donate" class="postbox">
			<h3 class="hndle"><span>Relevanssi in Facebook</span></h3>
			<div class="inside">
			<div style="float: left; margin-right: 5px"><img src="$facebooklogo" width="45" height="43" alt="Facebook" /></div>
			<p><a href="http://www.facebook.com/relevanssi">Check
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
			- <a href="http://www.relevanssi.com/category/knowledge-base/?utm_source=plugin&utm_medium=link&utm_campaign=kb">Knowledge base</a></p>
			</div>
		</div>
	</div>
	
</div>
</div>
EOH;

}

?>