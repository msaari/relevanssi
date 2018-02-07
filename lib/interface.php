<?php

function relevanssi_options() {
	global $relevanssi_variables;
	if (RELEVANSSI_PREMIUM) {
		$options_txt = __('Relevanssi Premium Search Options', 'relevanssi');
	}
	else {
		$options_txt = __('Relevanssi Search Options', 'relevanssi');
	}

	printf("<div class='wrap'><h2>%s</h2>", $options_txt);
	if (!empty($_POST)) {
		if (isset($_REQUEST['submit'])) {
			check_admin_referer(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');
			update_relevanssi_options();
		}

		if (isset($_REQUEST['index'])) {
			check_admin_referer(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');
			update_relevanssi_options();
			relevanssi_build_index();
		}

		if (isset($_REQUEST['index_extend'])) {
			check_admin_referer(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');
			update_relevanssi_options();
			relevanssi_build_index(true);
		}

		if (isset($_REQUEST['import_options'])) {
			if (function_exists('relevanssi_import_options')) {
				check_admin_referer(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');
				$options = $_REQUEST['relevanssi_settings'];
				relevanssi_import_options($options);
			}
		}

		if (isset($_REQUEST['search'])) {
			relevanssi_search($_REQUEST['q']);
		}

		if (isset($_REQUEST['dowhat'])) {
			if ("add_stopword" === $_REQUEST['dowhat']) {
				if (isset($_REQUEST['term'])) {
					check_admin_referer(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');
					relevanssi_add_stopword($_REQUEST['term']);
				}
			}
		}

		if (isset($_REQUEST['addstopword'])) {
			check_admin_referer(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');
			relevanssi_add_stopword($_REQUEST['addstopword']);
		}

		if (isset($_REQUEST['removestopword'])) {
			check_admin_referer(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');
			relevanssi_remove_stopword($_REQUEST['removestopword']);
		}

		if (isset($_REQUEST['removeallstopwords'])) {
			check_admin_referer(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');
			relevanssi_remove_all_stopwords();
		}
	}
	relevanssi_options_form();

	echo "<div style='clear:both'></div>";

	echo "</div>";
}

function relevanssi_search_stats() {
	$relevanssi_hide_branding = get_option( 'relevanssi_hide_branding' );

	if ( 'on' === $relevanssi_hide_branding )
		$options_txt = __('User Searches', 'relevanssi');
	else
		$options_txt = __('Relevanssi User Searches', 'relevanssi');

	if (isset($_REQUEST['relevanssi_reset']) and current_user_can('manage_options')) {
		check_admin_referer('relevanssi_reset_logs', '_relresnonce');
		if (isset($_REQUEST['relevanssi_reset_code'])) {
			if ($_REQUEST['relevanssi_reset_code'] === 'reset') {
				$verbose = true;
				relevanssi_truncate_logs($verbose);
			}
		}
	}

	wp_enqueue_style('dashboard');
	wp_print_styles('dashboard');
	wp_enqueue_script('dashboard');
	wp_print_scripts('dashboard');

	printf("<div class='wrap'><h2>%s</h2>", $options_txt);

	//echo '<div class="postbox-container">';

	if ('on' === get_option('relevanssi_log_queries')) {
		relevanssi_query_log();
	}
	else {
		echo "<p>" . __('Enable query logging to see stats here.', 'relevanssi') . "</p>";
	}

	//echo "</div>";
}

function relevanssi_truncate_logs($verbose = true) {
	global $wpdb, $relevanssi_variables;

	$query = "TRUNCATE " . $relevanssi_variables['log_table'];
	$result = $wpdb->query($query);

	if ($verbose) {
		if ($result !== false) {
			echo "<div id='relevanssi-warning' class='updated fade'>" . __('Logs clear!', 'relevanssi') . "</div>";
		}
		else {
			echo "<div id='relevanssi-warning' class='updated fade'>" . __('Clearing the logs failed.', 'relevanssi') . "</div>";
		}
	}

	return $result;
}

function update_relevanssi_options() {
	if (isset($_REQUEST['relevanssi_content_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_content_boost']);
		update_option('relevanssi_content_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_title_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_title_boost']);
		update_option('relevanssi_title_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_comment_boost'])) {
		$boost = floatval($_REQUEST['relevanssi_comment_boost']);
		update_option('relevanssi_comment_boost', $boost);
	}

	if (isset($_REQUEST['relevanssi_min_word_length'])) {
		$value = intval($_REQUEST['relevanssi_min_word_length']);
		if ($value === 0) $value = 3;
		update_option('relevanssi_min_word_length', $value);
	}

	if ($_REQUEST['tab'] === "indexing") {
		if (!isset($_REQUEST['relevanssi_index_author'])) {
			$_REQUEST['relevanssi_index_author'] = "off";
		}
	
		if (!isset($_REQUEST['relevanssi_index_excerpt'])) {
			$_REQUEST['relevanssi_index_excerpt'] = "off";
		}

		if (!isset($_REQUEST['relevanssi_expand_shortcodes'])) {
			$_REQUEST['relevanssi_expand_shortcodes'] = "off";
		}
	}

	if ($_REQUEST['tab'] === "searching") {
		if (!isset($_REQUEST['relevanssi_admin_search'])) {
			$_REQUEST['relevanssi_admin_search'] = "off";
		}

		if (!isset($_REQUEST['relevanssi_throttle'])) {
			$_REQUEST['relevanssi_throttle'] = "off";
		}

		if (!isset($_REQUEST['relevanssi_disable_or_fallback'])) {
			$_REQUEST['relevanssi_disable_or_fallback'] = "off";
		}

		if (!isset($_REQUEST['relevanssi_respect_exclude'])) {
			$_REQUEST['relevanssi_respect_exclude'] = "off";
		}
	
		if (!isset($_REQUEST['relevanssi_wpml_only_current'])) {
			$_REQUEST['relevanssi_wpml_only_current'] = "off";
		}
	
		if (!isset($_REQUEST['relevanssi_polylang_all_languages'])) {
			$_REQUEST['relevanssi_polylang_all_languages'] = "off";
		}
	}

	if ($_REQUEST['tab'] === "logging") {
		if (!isset($_REQUEST['relevanssi_log_queries'])) {
			$_REQUEST['relevanssi_log_queries'] = "off";
		}
	
		if (!isset($_REQUEST['relevanssi_log_queries_with_ip'])) {
			$_REQUEST['relevanssi_log_queries_with_ip'] = "off";
		}
	}

	if ($_REQUEST['tab'] === "excerpts") {
		if (!isset($_REQUEST['relevanssi_excerpts'])) {
			$_REQUEST['relevanssi_excerpts'] = "off";
		}

		if (!isset($_REQUEST['relevanssi_show_matches'])) {
			$_REQUEST['relevanssi_show_matches'] = "off";
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

		if (!isset($_REQUEST['relevanssi_excerpt_custom_fields'])) {
			$_REQUEST['relevanssi_excerpt_custom_fields'] = "off";
		}

		if (!isset($_REQUEST['relevanssi_word_boundaries'])) {
			$_REQUEST['relevanssi_word_boundaries'] = "off";
		}
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

	$relevanssi_punct = array();
	if (isset($_REQUEST['relevanssi_punct_quotes'])) $relevanssi_punct['quotes'] = $_REQUEST['relevanssi_punct_quotes'];
	if (isset($_REQUEST['relevanssi_punct_hyphens'])) $relevanssi_punct['hyphens'] = $_REQUEST['relevanssi_punct_hyphens'];
	if (isset($_REQUEST['relevanssi_punct_ampersands'])) $relevanssi_punct['ampersands'] = $_REQUEST['relevanssi_punct_ampersands'];
	if (isset($_REQUEST['relevanssi_punct_decimals'])) $relevanssi_punct['decimals'] = $_REQUEST['relevanssi_punct_decimals'];
	if (!empty($relevanssi_punct)) update_option('relevanssi_punctuation', $relevanssi_punct);

	$post_type_weights = array();
	$index_post_types = array();
	$index_taxonomies_list = array();
	$index_terms_list = array();
	foreach ($_REQUEST as $key => $value) {
		if (substr($key, 0, strlen('relevanssi_weight_')) === 'relevanssi_weight_') {
			$type = substr($key, strlen('relevanssi_weight_'));
			$post_type_weights[$type] = $value;
		}
		if (substr($key, 0, strlen('relevanssi_index_type_')) === 'relevanssi_index_type_') {
			$type = substr($key, strlen('relevanssi_index_type_'));
			if ('on' === $value) $index_post_types[$type] = true;
		}
		if (substr($key, 0, strlen('relevanssi_index_taxonomy_')) === 'relevanssi_index_taxonomy_') {
			$type = substr($key, strlen('relevanssi_index_taxonomy_'));
			if ('on' === $value) $index_taxonomies_list[$type] = true;
		}
		if (substr($key, 0, strlen('relevanssi_index_terms_')) === 'relevanssi_index_terms_') {
			$type = substr($key, strlen('relevanssi_index_terms_'));
			if ('on' === $value) $index_terms_list[$type] = true;
		}
	}

	if (count($post_type_weights) > 0) {
		update_option('relevanssi_post_type_weights', $post_type_weights);
	}

	if (count($index_post_types) > 0) {
		update_option('relevanssi_index_post_types', array_keys($index_post_types));
	}

	update_option('relevanssi_index_taxonomies_list', array_keys($index_taxonomies_list));
	if (RELEVANSSI_PREMIUM) update_option('relevanssi_index_terms', array_keys($index_terms_list));

	if (isset($_REQUEST['relevanssi_index_fields_select'])) {
		$fields_option = "";
		if ($_REQUEST['relevanssi_index_fields_select'] === "all") {
			$fields_option = "all";
		}
		if ($_REQUEST['relevanssi_index_fields_select'] === "visible") {
			$fields_option = "visible";
		}
		if ($_REQUEST['relevanssi_index_fields_select'] === "some") {
			if (isset($_REQUEST['relevanssi_index_fields'])) $fields_option = $_REQUEST['relevanssi_index_fields'];
		}
		update_option('relevanssi_index_fields', $fields_option);
	}
	
	if (isset($_REQUEST['relevanssi_trim_logs'])) {
		$trim_logs = $_REQUEST['relevanssi_trim_logs'];
		if (!is_numeric($trim_logs)) $trim_logs = 0;
		if ($trim_logs < 0) $trim_logs = 0;
		update_option('relevanssi_trim_logs', $trim_logs);
	}

	if (isset($_REQUEST['relevanssi_cat'])) {
		if (is_array($_REQUEST['relevanssi_cat'])) {
			$csv_cats = implode(",", $_REQUEST['relevanssi_cat']);
			update_option('relevanssi_cat', $csv_cats);
		}
	} else {
		if (isset($_REQUEST['relevanssi_cat_active'])) {
			update_option('relevanssi_cat', "");
		}
	}

	if (isset($_REQUEST['relevanssi_excat'])) {
		if (is_array($_REQUEST['relevanssi_excat'])) {
			$csv_cats = implode(",", $_REQUEST['relevanssi_excat']);
			update_option('relevanssi_excat', $csv_cats);
		}
	} else {
		if (isset($_REQUEST['relevanssi_excat_active'])) {
			update_option('relevanssi_excat', "");
		}
	}


	if (isset($_REQUEST['relevanssi_admin_search'])) update_option('relevanssi_admin_search', $_REQUEST['relevanssi_admin_search']);
	if (isset($_REQUEST['relevanssi_excerpts'])) update_option('relevanssi_excerpts', $_REQUEST['relevanssi_excerpts']);
	if (isset($_REQUEST['relevanssi_excerpt_type'])) update_option('relevanssi_excerpt_type', $_REQUEST['relevanssi_excerpt_type']);
	if (isset($_REQUEST['relevanssi_excerpt_allowable_tags'])) update_option('relevanssi_excerpt_allowable_tags', $_REQUEST['relevanssi_excerpt_allowable_tags']);
	if (isset($_REQUEST['relevanssi_log_queries'])) update_option('relevanssi_log_queries', $_REQUEST['relevanssi_log_queries']);
	if (isset($_REQUEST['relevanssi_log_queries_with_ip'])) update_option('relevanssi_log_queries_with_ip', $_REQUEST['relevanssi_log_queries_with_ip']);
	if (isset($_REQUEST['relevanssi_highlight'])) update_option('relevanssi_highlight', $_REQUEST['relevanssi_highlight']);
	if (isset($_REQUEST['relevanssi_highlight_docs'])) update_option('relevanssi_highlight_docs', $_REQUEST['relevanssi_highlight_docs']);
	if (isset($_REQUEST['relevanssi_highlight_comments'])) update_option('relevanssi_highlight_comments', $_REQUEST['relevanssi_highlight_comments']);
	if (isset($_REQUEST['relevanssi_txt_col'])) update_option('relevanssi_txt_col', $_REQUEST['relevanssi_txt_col']);
	if (isset($_REQUEST['relevanssi_bg_col'])) update_option('relevanssi_bg_col', $_REQUEST['relevanssi_bg_col']);
	if (isset($_REQUEST['relevanssi_css'])) update_option('relevanssi_css', $_REQUEST['relevanssi_css']);
	if (isset($_REQUEST['relevanssi_class'])) update_option('relevanssi_class', $_REQUEST['relevanssi_class']);
	if (isset($_REQUEST['relevanssi_expst'])) update_option('relevanssi_exclude_posts', $_REQUEST['relevanssi_expst']); 			//added by OdditY
	if (isset($_REQUEST['relevanssi_hilite_title'])) update_option('relevanssi_hilite_title', $_REQUEST['relevanssi_hilite_title']); 	//added by OdditY
	if (isset($_REQUEST['relevanssi_index_comments'])) update_option('relevanssi_index_comments', $_REQUEST['relevanssi_index_comments']); //added by OdditY
	if (isset($_REQUEST['relevanssi_index_author'])) update_option('relevanssi_index_author', $_REQUEST['relevanssi_index_author']);
	if (isset($_REQUEST['relevanssi_index_excerpt'])) update_option('relevanssi_index_excerpt', $_REQUEST['relevanssi_index_excerpt']);
	if (isset($_REQUEST['relevanssi_fuzzy'])) update_option('relevanssi_fuzzy', $_REQUEST['relevanssi_fuzzy']);
	if (isset($_REQUEST['relevanssi_expand_shortcodes'])) update_option('relevanssi_expand_shortcodes', $_REQUEST['relevanssi_expand_shortcodes']);
	if (isset($_REQUEST['relevanssi_implicit_operator'])) update_option('relevanssi_implicit_operator', $_REQUEST['relevanssi_implicit_operator']);
	if (isset($_REQUEST['relevanssi_omit_from_logs'])) update_option('relevanssi_omit_from_logs', $_REQUEST['relevanssi_omit_from_logs']);
	if (isset($_REQUEST['relevanssi_index_limit'])) update_option('relevanssi_index_limit', $_REQUEST['relevanssi_index_limit']);
	if (isset($_REQUEST['relevanssi_disable_or_fallback'])) update_option('relevanssi_disable_or_fallback', $_REQUEST['relevanssi_disable_or_fallback']);
	if (isset($_REQUEST['relevanssi_respect_exclude'])) update_option('relevanssi_respect_exclude', $_REQUEST['relevanssi_respect_exclude']);
	if (isset($_REQUEST['relevanssi_throttle'])) update_option('relevanssi_throttle', $_REQUEST['relevanssi_throttle']);
	if (isset($_REQUEST['relevanssi_wpml_only_current'])) update_option('relevanssi_wpml_only_current', $_REQUEST['relevanssi_wpml_only_current']);
	if (isset($_REQUEST['relevanssi_polylang_all_languages'])) update_option('relevanssi_polylang_all_languages', $_REQUEST['relevanssi_polylang_all_languages']);
	if (isset($_REQUEST['relevanssi_word_boundaries'])) update_option('relevanssi_word_boundaries', $_REQUEST['relevanssi_word_boundaries']);
	if (isset($_REQUEST['relevanssi_default_orderby'])) update_option('relevanssi_default_orderby', $_REQUEST['relevanssi_default_orderby']);
	if (isset($_REQUEST['relevanssi_excerpt_custom_fields'])) update_option('relevanssi_excerpt_custom_fields', $_REQUEST['relevanssi_excerpt_custom_fields']);
	
	if (function_exists('relevanssi_update_premium_options')) {
		relevanssi_update_premium_options();
	}
}

function relevanssi_add_stopword($term) {
	global $wpdb;
	if ('' === $term) return; // do not add empty $term to stopwords - added by renaissancehack

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
			printf(__("<div id='message' class='updated fade'><p>Term '%s' added to stopwords!</p></div>", "relevanssi"), stripslashes($term));
		}
		else {
			printf(__("<div id='message' class='updated fade'><p>Couldn't add term '%s' to stopwords!</p></div>", "relevanssi"), stripslashes($term));
		}
	}
}

function relevanssi_add_single_stopword($term) {
	global $wpdb, $relevanssi_variables;
	if ('' === $term) return;

	$term = stripslashes($term);

	if (method_exists($wpdb, 'esc_like')) {
		$term = esc_sql($wpdb->esc_like($term));
	}
	else {
		// Compatibility for pre-4.0 WordPress
		$term = esc_sql(like_escape($term));
	}

	$q = $wpdb->prepare("INSERT INTO " . $relevanssi_variables['stopword_table'] . " (stopword) VALUES (%s)", $term);
	// Clean: escaped.
	$success = $wpdb->query($q);

	if ($success) {
		// remove from index
		$q = $wpdb->prepare("DELETE FROM " . $relevanssi_variables['relevanssi_table'] . " WHERE term=%s", $term);
		$wpdb->query($q);
		return true;
	}
	else {
		return false;
	}
}

function relevanssi_remove_all_stopwords() {
	global $wpdb, $relevanssi_variables;

	$success = $wpdb->query("TRUNCATE " . $relevanssi_variables['stopword_table']);

	printf(__("<div id='message' class='updated fade'><p>Stopwords removed! Remember to re-index.</p></div>", "relevanssi"), $term);
}

function relevanssi_remove_stopword($term, $verbose = true) {
	global $wpdb, $relevanssi_variables;

	$q = $wpdb->prepare("DELETE FROM " . $relevanssi_variables['stopword_table'] . " WHERE stopword=%s", $term);
	$success = $wpdb->query($q);

	if ($success) {
		if ($verbose) {
			echo "<div id='message' class='updated fade'><p>";
			printf(__("Term '%s' removed from stopwords! Re-index to get it back to index.", "relevanssi"), stripslashes($term));
			echo "</p></div>";
		}
		else {
			return true;
		}
	}
	else {
		if ($verbose) {
			echo "<div id='message' class='updated fade'><p>";
			printf(__("Couldn't remove term '%s' from stopwords!", "relevanssi"), stripslashes($term));
			echo "</p></div>";
		}
		else {
			return false;
		}
	}
}

function relevanssi_common_words($limit = 25, $wp_cli = false) {
	global $wpdb, $relevanssi_variables, $wp_version;

	RELEVANSSI_PREMIUM ? $plugin = 'relevanssi-premium' : $plugin = 'relevanssi';

	if (!is_numeric($limit)) $limit = 25;

	$words = $wpdb->get_results("SELECT COUNT(*) as cnt, term FROM " . $relevanssi_variables['relevanssi_table'] . " GROUP BY term ORDER BY cnt DESC LIMIT $limit");
	// Clean: $limit is numeric.

	if (!$wp_cli) {
		echo "<h2>" . __("25 most common words in the index", 'relevanssi') . "</h2>";
		echo "<p>" . __("These words are excellent stopword material. A word that appears in most of the posts in the database is quite pointless when searching. This is also an easy way to create a completely new stopword list, if one isn't available in your language. Click the icon after the word to add the word to the stopword list. The word will also be removed from the index, so rebuilding the index is not necessary.", 'relevanssi') . "</p>";

?>
<input type="hidden" name="dowhat" value="add_stopword" />
<table class="form-table">
<tr>
	<th scope="row"><?php _e("Stopword Candidates", "relevanssi"); ?></th>
	<td>
<ul>
<?php

		$src = plugins_url('delete.png', $relevanssi_variables['file']);

		foreach ($words as $word) {
			$stop = __('Add to stopwords', 'relevanssi');
			printf('<li>%s (%d) <input style="padding: 0; margin: 0" type="image" src="%s" alt="%s" name="term" value="%s"/></li>', $word->term, $word->cnt, $src, $stop, $word->term);
		}
	?>
	</ul>
	</td>
</tr>
</table>
	<?php

	}
	else {
		// WP CLI gets the list of words
		return $words;
	}
}

function relevanssi_query_log() {
	global $wpdb;

	$days30 = apply_filters('relevanssi_30days', 30);

	echo '<h3>' . __("Total Searches", 'relevanssi') . '</h3>';

	echo "<div style='width: 50%; overflow: auto'>";
	relevanssi_total_queries( __("Totals", 'relevanssi') );
	echo '</div>';

	echo '<div style="clear: both"></div>';

	echo '<h3>' . __("Common Queries", 'relevanssi') . '</h3>';

	$limit = apply_filters('relevanssi_user_searches_limit', 20);
	
	printf("<p>" . __("Here you can see the %d most common user search queries, how many times those queries were made and how many results were found for those queries.", 'relevanssi') . "</p>", $limit);

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries(1, __("Today and yesterday", 'relevanssi'));
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries(7, __("Last 7 days", 'relevanssi'));
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries($days30, sprintf(__("Last %d days", 'relevanssi'), $days30));
	echo '</div>';

	echo '<div style="clear: both"></div>';

	echo '<h3>' . __("Unsuccessful Queries", 'relevanssi') . '</h3>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries(1, __("Today and yesterday", 'relevanssi'), 'bad');
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries(7, __("Last 7 days", 'relevanssi'), 'bad');
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries($days30, sprintf(__("Last %d days", 'relevanssi'), $days30), 'bad');
	echo '</div>';

	if ( current_user_can('manage_options') ) {

		echo '<div style="clear: both"></div>';
		$nonce = wp_nonce_field('relevanssi_reset_logs', '_relresnonce', true, false);
		echo '<h3>' . __('Reset Logs', 'relevanssi') . "</h3>\n";
		echo "<form method='post'>\n$nonce";
		echo "<p>";
		printf(__('To reset the logs, type "reset" into the box here %s and click %s', 'relevanssi'), ' <input type="text" name="relevanssi_reset_code" />', ' <input type="submit" name="relevanssi_reset" value="Reset" class="button" />');
		echo "</p></form>";

	}

	echo "</div>";
}

function relevanssi_total_queries( $title ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	$count = array();

	$count[__('Today and yesterday', 'relevanssi')] = $wpdb->get_var("SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 1;");
	$count[__('Last 7 days', 'relevanssi')] = $wpdb->get_var("SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 7;");
	$count[__('Last 30 days', 'relevanssi')] = $wpdb->get_var("SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 30;");
	$count[__('Forever', 'relevanssi')] = $wpdb->get_var("SELECT COUNT(id) FROM $log_table;");

	echo "<table class='widefat'><thead><tr><th colspan='2'>$title</th></tr></thead><tbody><tr><th>" . __('When', 'relevanssi') . "</th><th style='text-align: center'>" . __('Searches', 'relevanssi') . "</th></tr>";
	foreach ($count as $when => $searches) {
		echo "<tr><td>$when</td><td style='text-align: center'>$searches</td></tr>";
	}
	echo "</tbody></table>";

}

function relevanssi_date_queries($d, $title, $version = 'good') {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	$limit = apply_filters('relevanssi_user_searches_limit', 20);

	if ($version === 'good')
		$queries = $wpdb->get_results("SELECT COUNT(DISTINCT(id)) as cnt, query, hits
		  FROM $log_table
		  WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= $d
		  GROUP BY query
		  ORDER BY cnt DESC
		  LIMIT $limit");

	if ($version === 'bad')
		$queries = $wpdb->get_results("SELECT COUNT(DISTINCT(id)) as cnt, query, hits
		  FROM $log_table
		  WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= $d
		    AND hits = 0
		  GROUP BY query
		  ORDER BY cnt DESC
		  LIMIT $limit");

	if (count($queries) > 0) {
		echo "<table class='widefat'><thead><tr><th colspan='3'>$title</th></tr></thead><tbody><tr><th>" . __('Query', 'relevanssi') . "</th><th style='text-align: center'>#</th><th style='text-align: center'>" . __('Hits', 'relevanssi') . "</th></tr>";
		foreach ($queries as $query) {
			$url = get_bloginfo('url');
			$u_q = urlencode($query->query);
			echo "<tr><td><a href='$url/?s=$u_q'>" . esc_attr($query->query) . "</a></td><td style='padding: 3px 5px; text-align: center'>" . $query->cnt . "</td><td style='padding: 3px 5px; text-align: center'>" . $query->hits . "</td></tr>";
		}
		echo "</tbody></table>";
	}
}

function relevanssi_options_form() {
	global $relevanssi_variables, $wpdb;

	wp_enqueue_style('dashboard');
	wp_print_styles('dashboard');
	wp_enqueue_script('dashboard');
	wp_print_scripts('dashboard');

	$serialize_options = array();

	$content_boost = get_option('relevanssi_content_boost');
	$serialize_options['relevanssi_content_boost'] = $content_boost;
	$title_boost = get_option('relevanssi_title_boost');
	$serialize_options['relevanssi_title_boost'] = $title_boost;
	$comment_boost = get_option('relevanssi_comment_boost');
	$serialize_options['relevanssi_comment_boost'] = $comment_boost;
	$admin_search = get_option('relevanssi_admin_search');
	$serialize_options['relevanssi_admin_search'] = $admin_search;
	if ('on' === $admin_search) {
		$admin_search = 'checked="checked"';
	}
	else {
		$admin_search = '';
	}

	$index_limit = get_option('relevanssi_index_limit');
	$serialize_options['relevanssi_index_limit'] = $index_limit;

	$excerpts = get_option('relevanssi_excerpts');
	$serialize_options['relevanssi_excerpts'] = $excerpts;
	if ('on' === $excerpts) {
		$excerpts = 'checked="checked"';
	}
	else {
		$excerpts = '';
	}

	$excerpt_length = get_option('relevanssi_excerpt_length');
	$serialize_options['relevanssi_excerpt_length'] = $excerpt_length;
	$excerpt_type = get_option('relevanssi_excerpt_type');
	$serialize_options['relevanssi_excerpt_type'] = $excerpt_type;
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
	$excerpt_allowable_tags = get_option('relevanssi_excerpt_allowable_tags');
	$serialize_options['relevanssi_excerpt_allowable_tags'] = $excerpt_allowable_tags;

	$excerpt_custom_fields = ('on' === get_option('relevanssi_excerpt_custom_fields') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_excerpt_custom_fields'] = get_option('relevanssi_excerpt_custom_fields');
	
	$log_queries = get_option('relevanssi_log_queries');
	$serialize_options['relevanssi_log_queries'] = $log_queries;
	if ('on' === $log_queries) {
		$log_queries = 'checked="checked"';
	}
	else {
		$log_queries = '';
	}

	$log_queries_with_ip = get_option('relevanssi_log_queries_with_ip');
	$serialize_options['relevanssi_log_queries_with_ip'] = $log_queries_with_ip;
	if ('on' === $log_queries_with_ip) {
		$log_queries_with_ip = 'checked="checked"';
	}
	else {
		$log_queries_with_ip = '';
	}

	$trim_logs = get_option('relevanssi_trim_logs');
	$serialize_options['relevanssi_trim_logs'] = $trim_logs;

	$hide_branding = get_option('relevanssi_hide_branding');
	$serialize_options['relevanssi_hide_branding'] = $hide_branding;
	if ('on' === $hide_branding) {
		$hide_branding = 'checked="checked"';
	}
	else {
		$hide_branding = '';
	}

	$highlight = get_option('relevanssi_highlight');
	$serialize_options['relevanssi_highlight'] = $highlight;
	$highlight_none = "";
	$highlight_mark = "";
	$highlight_em = "";
	$highlight_strong = "";
	$highlight_col = "";
	$highlight_bgcol = "";
	$highlight_style = "";
	$highlight_class = "";
	$txt_col_display = "class='screen-reader-text'";
	$bg_col_display = "class='screen-reader-text'";
	$css_display = "class='screen-reader-text'";
	$class_display = "class='screen-reader-text'";
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
			$txt_col_display = '';
			break;
		case "bgcol":
			$highlight_bgcol = 'selected="selected"';
			$bg_col_display = '';
			break;
		case "css":
			$highlight_style = 'selected="selected"';
			$css_display = '';
			break;
		case "class":
			$highlight_class = 'selected="selected"';
			$class_display = '';
			break;
	}

	$index_fields = get_option('relevanssi_index_fields');
	$serialize_options['relevanssi_index_fields'] = $index_fields;

	$fields_select_all = "";
	$fields_select_none = "";
	$fields_select_some = "selected='selected'";
	$fields_select_visible = "";
	$original_index_fields = $index_fields;

	if (empty($index_fields)) {
		$fields_select_none = "selected='selected'";
		$fields_select_some = "";
	}
	if ($index_fields === "all") {
		$fields_select_all = "selected='selected'";
		$fields_select_some = "";
		$index_fields = "";
	}
	if ($index_fields === "visible") {
		$fields_select_visible = "selected='selected'";
		$fields_select_some = "";
		$index_fields = "";
	}

	$txt_col = get_option('relevanssi_txt_col');
	if (substr($txt_col, 0, 1) != "#") $txt_col = "#" . $txt_col;
	$txt_col = relevanssi_sanitize_hex_color($txt_col);
	$serialize_options['relevanssi_txt_col'] = $txt_col;
	
	$bg_col = get_option('relevanssi_bg_col');
	if (substr($bg_col, 0, 1) != "#") $bg_col = "#" . $bg_col;
	$bg_col = relevanssi_sanitize_hex_color($bg_col);
	$serialize_options['relevanssi_bg_col'] = $bg_col;

	$css = get_option('relevanssi_css');
	$serialize_options['relevanssi_css'] = $css;
	$class = get_option('relevanssi_class');
	$serialize_options['relevanssi_class'] = $class;

	$cat = get_option('relevanssi_cat');
	$serialize_options['relevanssi_cat'] = $cat;
	$excat = get_option('relevanssi_excat');
	$serialize_options['relevanssi_excat'] = $excat;

	$fuzzy = get_option('relevanssi_fuzzy');
	$serialize_options['relevanssi_fuzzy'] = $fuzzy;
	$fuzzy_sometimes = ('sometimes' === $fuzzy ? 'selected="selected"' : '');
	$fuzzy_always = ('always' === $fuzzy ? 'selected="selected"' : '');
	$fuzzy_never = ('never' === $fuzzy ? 'selected="selected"' : '');

	$implicit = get_option('relevanssi_implicit_operator');
	$serialize_options['relevanssi_implicit_operator'] = $implicit;
	$implicit_and = ('AND' === $implicit ? 'selected="selected"' : '');
	$implicit_or = ('OR' === $implicit ? 'selected="selected"' : '');
	$orfallback_visibility = "class='screen-reader-text'";
	if ($implicit === "AND") $orfallback_visibility = ""; 

	$expand_shortcodes = ('on' === get_option('relevanssi_expand_shortcodes') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_expand_shortcodes'] = get_option('relevanssi_expand_shortcodes');
	$disablefallback = ('on' === get_option('relevanssi_disable_or_fallback') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_disable_or_fallback'] = get_option('relevanssi_disable_or_fallback');

	$throttle = ('on' === get_option('relevanssi_throttle') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_throttle'] = get_option('relevanssi_throttle');

	$throttle_limit = get_option('relevanssi_throttle_limit');
	$serialize_options['relevanssi_throttle_limit'] = $throttle_limit;

	$omit_from_logs	= get_option('relevanssi_omit_from_logs');
	$serialize_options['relevanssi_omit_from_logs'] = $omit_from_logs;

	$synonyms = get_option('relevanssi_synonyms');
	$serialize_options['relevanssi_synonyms'] = $synonyms;
	isset($synonyms) ? $synonyms = str_replace(';', "\n", $synonyms) : $synonyms = "";

	//Added by OdditY ->
	$expst = get_option('relevanssi_exclude_posts');
	$serialize_options['relevanssi_exclude_posts'] = $expst;
	$hititle = ('on' === get_option('relevanssi_hilite_title') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_hilite_title'] = get_option('relevanssi_hilite_title');
	$incom_type = get_option('relevanssi_index_comments');
	$serialize_options['relevanssi_index_comments'] = $incom_type;
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

	$highlight_docs = ('on' === get_option('relevanssi_highlight_docs') ? 'checked="checked"' : '');
	$highlight_coms = ('on' === get_option('relevanssi_highlight_comments') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_highlight_docs'] = get_option('relevanssi_highlight_docs');
	$serialize_options['relevanssi_highlight_comments'] = get_option('relevanssi_highlight_comments');

	$respect_exclude = ('on' === get_option('relevanssi_respect_exclude') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_respect_exclude'] = get_option('relevanssi_respect_exclude');

	$min_word_length = get_option('relevanssi_min_word_length');
	$serialize_options['relevanssi_min_word_length'] = $min_word_length;

	$index_author = ('on' === get_option('relevanssi_index_author') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_index_author'] = get_option('relevanssi_index_author');
	$index_excerpt = ('on' === get_option('relevanssi_index_excerpt') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_index_excerpt'] = get_option('relevanssi_index_excerpt');

	$show_matches = ('on' === get_option('relevanssi_show_matches') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_show_matches'] = get_option('relevanssi_show_matches');
	$show_matches_text = stripslashes(get_option('relevanssi_show_matches_text'));
	$serialize_options['relevanssi_show_matches_text'] = get_option('relevanssi_show_matches_text');

	$wpml_only_current = ('on' === get_option('relevanssi_wpml_only_current') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_wpml_only_current'] = get_option('relevanssi_wpml_only_current');

	$polylang_allow_all = ('on' === get_option('relevanssi_polylang_all_languages') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_polylang_all_languages'] = get_option('relevanssi_polylang_all_languages');
	
	$word_boundaries = ('on' === get_option('relevanssi_word_boundaries') ? 'checked="checked"' : '');
	$serialize_options['relevanssi_word_boundaries'] = get_option('relevanssi_word_boundaries');

	$post_type_weights = get_option('relevanssi_post_type_weights');
	$serialize_options['relevanssi_post_type_weights'] = $post_type_weights;

	$index_post_types = get_option('relevanssi_index_post_types');
	if (empty($index_post_types)) $index_post_types = array();
	$serialize_options['relevanssi_index_post_types'] = $index_post_types;

	$index_taxonomies_list = get_option('relevanssi_index_taxonomies_list');
	if (empty($index_taxonomies_list)) $index_taxonomies_list = array();
	$serialize_options['relevanssi_index_taxonomies_list'] = $index_taxonomies_list;

	$orderby = get_option('relevanssi_default_orderby');
	$serialize_options['relevanssi_default_orderby'] = $orderby;
	$orderby_relevance = ('relevance' === $orderby ? 'selected="selected"' : '');
	$orderby_date = ('post_date' === $orderby ? 'selected="selected"' : '');

	$punctuation = get_option('relevanssi_punctuation');
	$serialize_options['relevanssi_punctuation'] = $punctuation;
	$punct_quotes_remove = "";
	$punct_quotes_replace = "";
	$punct_ampersands_keep = "";
	$punct_ampersands_remove = "";
	$punct_ampersands_replace = "";
	$punct_hyphens_keep = "";
	$punct_hyphens_remove = "";
	$punct_hyphens_replace = "";
	$punct_decimals_keep = "";
	$punct_decimals_remove = "";
	$punct_decimals_replace = "";
	if (isset($punctuation['quotes'])) {
		$quotes = $punctuation['quotes'];
		switch ($quotes) {
			case 'replace':
				$punct_quotes_replace = 'selected="selected"';
				break;
			case 'remove':
				$punct_quotes_remove = 'selected="selected"';
				break;
			default:
				$punct_quotes_remove = 'selected="selected"';
		}
	}
	if (isset($punctuation['decimals'])) {
		$decimals = $punctuation['decimals'];
		switch ($decimals) {
			case 'replace':
				$punct_decimals_replace = 'selected="selected"';
				break;
			case 'remove':
				$punct_decimals_remove = 'selected="selected"';
				break;
			case 'keep':
				$punct_decimals_keep = 'selected="selected"';
				break;
			default:
				$punct_decimals_replace = 'selected="selected"';
		}
	}
	if (isset($punctuation['ampersands'])) {
		$ampersands = $punctuation['ampersands'];
		switch ($ampersands) {
			case 'replace':
				$punct_ampersands_replace = 'selected="selected"';
				break;
			case 'remove':
				$punct_ampersands_remove = 'selected="selected"';
				break;
			case 'keep':
				$punct_ampersands_keep = 'selected="selected"';
				break;
			default:
				$punct_ampersands_replace = 'selected="selected"';
		}
	}
	if (isset($punctuation['hyphens'])) {
		$hyphens = $punctuation['hyphens'];
		switch ($hyphens) {
			case 'replace':
				$punct_hyphens_replace = 'selected="selected"';
				break;
			case 'remove':
				$punct_hyphens_remove = 'selected="selected"';
				break;
			case 'keep':
				$punct_hyphens_keep = 'selected="selected"';
				break;
			default:
				$punct_hyphens_replace = 'selected="selected"';
		}
	}
	if (RELEVANSSI_PREMIUM) {
		$api_key = get_option('relevanssi_api_key');
		$serialize_options['relevanssi_api_key'] = $api_key;

		$link_boost = get_option('relevanssi_link_boost');
		$serialize_options['relevanssi_link_boost'] = $link_boost;

		$intlinks = get_option('relevanssi_internal_links');
		$serialize_options['relevanssi_internal_links'] = $intlinks;
		$intlinks_strip = ('strip' === $intlinks ? 'selected="selected"' : '');
		$intlinks_nostrip = ('nostrip' === $intlinks ? 'selected="selected"' : '');
		$intlinks_noindex = ('noindex' === $intlinks ? 'selected="selected"' : '');

		$highlight_docs_ext = ('on' === get_option('relevanssi_highlight_docs_external') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_highlight_docs_external'] = get_option('relevanssi_highlight_docs_external');

		$thousand_separator = get_option('relevanssi_thousand_separator');
		$serialize_options['relevanssi_thousand_separator'] = $thousand_separator;

		$disable_shortcodes = get_option('relevanssi_disable_shortcodes');
		$serialize_options['relevanssi_disable_shortcodes'] = $disable_shortcodes;

		$index_users = ('on' === get_option('relevanssi_index_users') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_index_users'] = get_option('relevanssi_index_users');

		$index_user_fields = get_option('relevanssi_index_user_fields');
		$serialize_options['relevanssi_index_user_fields'] = $index_user_fields;

		$index_subscribers = ('on' === get_option('relevanssi_index_subscribers') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_index_subscribers'] = get_option('relevanssi_index_subscribers');

		$index_synonyms = ('on' === get_option('relevanssi_index_synonyms') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_index_synonyms'] = get_option('relevanssi_index_synonyms');

		$index_taxonomies = ('on' === get_option('relevanssi_index_taxonomies') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_index_taxonomies'] = get_option('relevanssi_index_taxonomies');

		$index_terms = get_option('relevanssi_index_terms');
		if (empty($index_terms)) $index_terms = array();
		$serialize_options['relevanssi_index_terms'] = $index_terms;

		$hide_post_controls = ('on' === get_option('relevanssi_hide_post_controls') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_hide_post_controls'] = get_option('relevanssi_hide_post_controls');
		$show_post_controls = ('on' === get_option('relevanssi_show_post_controls') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_show_post_controls'] = get_option('relevanssi_show_post_controls');

		$recency_bonus_array = get_option('relevanssi_recency_bonus');
		$serialize_options['recency_bonus'] = $recency_bonus_array;
		$recency_bonus = $recency_bonus_array['bonus'];
		$recency_bonus_days = $recency_bonus_array['days'];

		$searchblogs = get_option('relevanssi_searchblogs');
		$serialize_options['relevanssi_searchblogs'] = $searchblogs;
		$searchblogs_all = ('on' === get_option('relevanssi_searchblogs_all') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_searchblogs_all'] = get_option('searchblogs_all');

		$mysql_columns = get_option('relevanssi_mysql_columns');
		$serialize_options['relevanssi_mysql_columns'] = $mysql_columns;

		$index_pdf_parent = ('on' === get_option('relevanssi_index_pdf_parent') ? 'checked="checked"' : '');
		$serialize_options['relevanssi_index_pdf_parent'] = get_option('relevanssi_index_pdf_parent');
		
		$serialize_options['relevanssi_send_pdf_files'] = get_option('relevanssi_send_pdf_files');
		$serialize_options['relevanssi_read_new_files'] = get_option('relevanssi_read_new_files');
		$serialize_options['relevanssi_link_pdf_files'] = get_option('relevanssi_link_pdf_files');

		$serialized_options = json_encode($serialize_options);
	}

	echo "<div class='postbox-container'>";

	$this_page = "?page=" . plugin_basename($relevanssi_variables['file']);
	echo "<form method='post'>";
	
	wp_nonce_field(plugin_basename($relevanssi_variables['file']), 'relevanssi_options');

	$display_save_button = true;

	$active_tab = "overview";
	if( isset( $_REQUEST[ 'tab' ] ) ) {
    	$active_tab = $_REQUEST[ 'tab' ];
	} // end if

	if ($active_tab === "stopwords") $display_save_button = false;

	echo "<input type='hidden' name='tab' value='$active_tab' />";

?>

<h2 class="nav-tab-wrapper">
    <a href="<?php echo $this_page; ?>&amp;tab=overview" class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>"><?php _e('Overview', 'relevanssi'); ?></a>
    <a href="<?php echo $this_page; ?>&amp;tab=indexing" class="nav-tab <?php echo $active_tab === 'indexing' ? 'nav-tab-active' : ''; ?>"><?php _e('Indexing', 'relevanssi'); ?></a>
    <a href="<?php echo $this_page; ?>&amp;tab=attachments" class="nav-tab <?php echo $active_tab === 'attachments' ? 'nav-tab-active' : ''; ?>"><?php _e('Attachments', 'relevanssi'); ?></a>
    <a href="<?php echo $this_page; ?>&amp;tab=searching" class="nav-tab <?php echo $active_tab === 'searching' ? 'nav-tab-active' : ''; ?>"><?php _e('Searching', 'relevanssi'); ?></a>
    <a href="<?php echo $this_page; ?>&amp;tab=logging" class="nav-tab <?php echo $active_tab === 'logging' ? 'nav-tab-active' : ''; ?>"><?php _e('Logging', 'relevanssi'); ?></a>
    <a href="<?php echo $this_page; ?>&amp;tab=excerpts" class="nav-tab <?php echo $active_tab === 'excerpts' ? 'nav-tab-active' : ''; ?>"><?php _e('Excerpts and highlights', 'relevanssi'); ?></a>
    <a href="<?php echo $this_page; ?>&amp;tab=synonyms" class="nav-tab <?php echo $active_tab === 'synonyms' ? 'nav-tab-active' : ''; ?>"><?php _e('Synonyms', 'relevanssi'); ?></a>
    <a href="<?php echo $this_page; ?>&amp;tab=stopwords" class="nav-tab <?php echo $active_tab === 'stopwords' ? 'nav-tab-active' : ''; ?>"><?php _e('Stopwords', 'relevanssi'); ?></a>
	<?php if (function_exists('relevanssi_form_importexport')) : ?>
    <a href="<?php echo $this_page; ?>&amp;tab=importexport" class="nav-tab <?php echo $active_tab === 'importexport' ? 'nav-tab-active' : ''; ?>"><?php _e('Import / Export options', 'relevanssi'); ?></a>
	<?php endif; ?>
</h2>

<?php /*
    <p><a href="#basic"><?php _e("Basic options", "relevanssi"); ?></a> |
	<a href="#weights"><?php _e("Weights", "relevanssi"); ?></a> |
	<a href="#logs"><?php _e("Logs", "relevanssi"); ?></a> |
    <a href="#exclusions"><?php _e("Exclusions and restrictions", "relevanssi"); ?></a> |
    <a href="#excerpts"><?php _e("Custom excerpts", "relevanssi"); ?></a> |
    <a href="#highlighting"><?php _e("Highlighting search results", "relevanssi"); ?></a> |
    <a href="#indexing"><?php _e("Indexing options", "relevanssi"); ?></a> |
    <a href="#synonyms"><?php _e("Synonyms", "relevanssi"); ?></a> |
    <a href="#stopwords"><?php _e("Stopwords", "relevanssi"); ?></a> |
<?php
	if (RELEVANSSI_PREMIUM) {
    	echo '<a href="#options">' . __("Import/export options", "relevanssi") . '</a>';
    }
    else {
		echo '<strong><a href="https://www.relevanssi.com/buy-premium/?utm_source=plugin&utm_medium=link&utm_campaign=buy">' . __('Buy Relevanssi Premium', 'relevanssi') . '</a></strong>';
    }
?>
    </p>
*/ ?>

	<?php 
		if ($active_tab === "overview") :
			if (!RELEVANSSI_PREMIUM) $display_save_button = false;
	?>

	<h2><?php _e("Welcome to Relevanssi!", "relevanssi"); ?></h2>

	<table class="form-table">
<?php
	if (!is_multisite() && function_exists('relevanssi_form_api_key')) relevanssi_form_api_key($api_key);
?>
<?php
	if (function_exists('relevanssi_form_hide_post_controls')) relevanssi_form_hide_post_controls($hide_post_controls, $show_post_controls);
?>
	<tr>
		<th scope="row"><?php _e("Getting started", "relevanssi"); ?></th>
		<td>
			<p><?php _e("You've already installed Relevanssi. That's a great first step towards good search experience!", "relevanssi"); ?></p>
			<ol>
				<?php if (get_option('relevanssi_indexed') !== 'done') : ?>
				<li><p><?php printf(__("Now, you need an index. Head over to the %s%s%s tab to set up the basic indexing options and to build the index.", "relevanssi"), "<a href='{$this_page}&amp;tab=indexing'>", __("Indexing", "relevanssi"), "</a>"); ?></p>
					<p><?php _e("You need to check at least the following options:", "relevanssi"); ?><br />
				– <?php _e("Make sure the post types you want to include in the index are indexed.", "relevanssi"); ?><br />
				– <?php printf(__("Do you use custom fields to store content you want included? If so, add those too. WooCommerce user? You probably want to include %s.", "relevanssi"), "<code>_sku</code>"); ?></p>
					<p><?php _e("Then just save the options and build the index. First time you have to do it manually, but after that, it's fully automatic: all changes are reflected in the index without reindexing. (That said, it's a good idea to rebuild the index once a year.)", "relevanssi"); ?></p>
				</li>
				<?php else : ?>
				<li><p><?php _e("Great, you already have an index!", "relevanssi"); ?></p></li>
				<?php endif; ?>
				<li>
					<p><?php printf(__("On the %s%s%s tab, choose whether you want the default operator to be AND (less results, but more precise) or OR (more results, less precise).", "relevanssi"), "<a href='{$this_page}&amp;tab=searching'>", __("Searching", "relevanssi"), "</a>"); ?></p>
				</li>
				<li>
					<p><?php printf(__("The next step is the %s%s%s tab, where you can enable the custom excerpts that show the relevant part of post in the search results pages.", "relevanssi"), "<a href='{$this_page}&amp;tab=excerpts'>", __("Excerpts and highlights", "relevanssi"), "</a>"); ?></p>
					<p><?php _e("There are couple of options related to that, so if you want highlighting in the results, you can adjust the styles for that to suit the look of your site.", "relevanssi"); ?></p>
				</li>
				<li>
					<p><?php _e("That's about it! Now you should have Relevanssi up and running. The rest of the options is mostly fine-tuning.", "relevanssi"); ?></p>
				</li>
			</ol>
			<p><?php _e("Relevanssi doesn't have a separate search widget. Instead, Relevanssi uses the default search widget. Any standard search form will do!", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php _e("For more information", "relevanssi"); ?></th>
		<td>
			<p><?php _e("Relevanssi uses the WordPress contextual help. Click 'Help' on the top right corner for more information on many Relevanssi topics.", "relevanssi"); ?></p>
			<p><?php printf(__("%sRelevanssi knowledge base%s has lots of information about advanced Relevanssi use, including plenty of code samples.", "relevanssi"), "<a href='https://www.relevanssi.com/knowledge-base/'>", "</a>"); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php _e('Relevanssi on Facebook', 'relevanssi');?>
		</th>
		<td>
			<p><a href="https://www.facebook.com/relevanssi"><?php _e('Check out the Relevanssi page on Facebook for news and updates about Relevanssi.', 'relevanssi'); ?></a></p>
		</td>
	</tr>
	<?php if (!RELEVANSSI_PREMIUM) : ?>
	<tr>
		<th scope="row">
			<?php _e('Buy Relevanssi Premium', 'relevanssi');?>
		</th>
		<td>
			<p><a href="https://www.relevanssi.com/buy-premium"><?php _e('Buy Relevanssi Premium now', 'relevanssi'); ?></a> – <?php printf(__("use coupon code %s for 20%% discount (valid at least until the end of %s)", "relevanssi"), "<strong>FREE2018</strong>", "2018"); ?></p>
			<p><?php _e("Here are some improvements Relevanssi Premium offers:", "relevanssi"); ?></p>
			<ul class="relevanssi_ul">
				<li><?php _e("PDF content indexing", "relevanssi"); ?></li>
				<li><?php _e("Index and search user profile pages", "relevanssi"); ?></li>
				<li><?php _e("Index and search taxonomy term pages", "relevanssi"); ?></li>
				<li><?php _e("Multisite searches across many subsites", "relevanssi"); ?></li>
				<li><?php _e("WP CLI commands", "relevanssi"); ?></li>
				<li><?php _e("Adjust weights separately for each post type and taxonomy", "relevanssi"); ?></li>
				<li><?php _e("Internal link anchors can be search terms for the target posts", "relevanssi"); ?></li>
				<li><?php _e("Index and search any columns in the wp_posts database", "relevanssi"); ?></li>
				<li><?php _e("Hide Relevanssi branding from the User Searches page on a client installation", "relevanssi"); ?></li>
			</ul>
		</td>
	</tr>
	<?php endif; ?>
	</table>

	<?php endif; // active tab: basic ?>

	<?php if ($active_tab === "logging") : ?>

	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_log_queries'><?php _e("Enable logs", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Keep a log of user queries.", "relevanssi"); ?></legend>
			<label for='relevanssi_log_queries'>
				<input type='checkbox' name='relevanssi_log_queries' id='relevanssi_log_queries' <?php echo $log_queries ?> />
				<?php _e("Keep a log of user queries.", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php global $wpdb; printf(__("If enabled, Relevanssi will log user queries. The logs can be examined under '%s' on the Dashboard admin menu and are stored in the %s database table.", "relevanssi"), __('User searches', 'relevanssi'), $wpdb->prefix . 'relevanssi_log'); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_log_queries_with_ip'><?php _e("Log user IP", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Log the user's IP with the queries.", "relevanssi"); ?></legend>
			<label for='relevanssi_log_queries_with_ip'>
				<input type='checkbox' name='relevanssi_log_queries_with_ip' id='relevanssi_log_queries_with_ip' <?php echo $log_queries_with_ip ?> />
				<?php _e("Log the user's IP with the queries.", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("If enabled, Relevanssi will log user's IP adress with the queries.", "relevanssi"); ?></p>
		</td>
	</tr>	
	<tr>
		<th scope="row">
			<label for='relevanssi_omit_from_logs'><?php _e("Exclude users", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_omit_from_logs' id='relevanssi_omit_from_logs' size='60' value='<?php echo esc_attr($omit_from_logs); ?>' />
			<p class="description"><?php _e("Comma-separated list of numeric user IDs or user login names that will not be logged.", "relevanssi"); ?></p>
		</td>
	</tr>
	<?php if (function_exists('relevanssi_form_hide_branding')) relevanssi_form_hide_branding($hide_branding); ?>
	<tr>
		<th scope="row">
			<label for='relevanssi_trim_logs'><?php _e("Trim logs", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='number' name='relevanssi_trim_logs' id='relevanssi_trim_logs' value='<?php echo $trim_logs; ?>' />
			<?php _e("How many days of logs to keep in the database.", "relevanssi"); ?>
			<p class="description"><?php printf(__(" Set to %d for no trimming.", "relevanssi"), 0); ?></p>
		</td>
	</tr>

	</table>

	<?php endif; // active tag: logging ?>

	<?php if ($active_tab === "searching") :
	$docs_count = $wpdb->get_var("SELECT COUNT(DISTINCT doc) FROM " . $relevanssi_variables['relevanssi_table'] . " WHERE doc != -1");
	?>

	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_implicit_operator'><?php _e("Default operator", "relevanssi"); ?></label>
		</th>
		<td>
			<select name='relevanssi_implicit_operator' id='relevanssi_implicit_operator'>
				<option value='AND' <?php echo $implicit_and ?>><?php _e("AND - require all terms", "relevanssi"); ?></option>
				<option value='OR' <?php echo $implicit_or ?>><?php _e("OR - any term present is enough", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("This setting determines the default operator for the search.", "relevanssi"); ?></p>
			<?php if (RELEVANSSI_PREMIUM) echo "<p class='description'>" . sprintf(__("You can override this setting with the %s query parameter, like this: %s", "relevanssi"), "<code>operator</code>", "http://www.example.com/?s=term&amp;operator=or") . "</p>"; ?>
		</td>
	</tr>	
	<tr id="orfallback" <?php echo $orfallback_visibility; ?>>
		<th scope="row">
			<label for='relevanssi_disable_or_fallback'><?php _e("Fallback to OR", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Disable the OR fallback.", "relevanssi"); ?></legend>
			<label for='relevanssi_disable_or_fallback'>
				<input type='checkbox' name='relevanssi_disable_or_fallback' id='relevanssi_disable_or_fallback' <?php echo $disablefallback ?> />
				<?php _e("Disable the OR fallback.", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("By default, if AND search fails to find any results, Relevanssi will switch the operator to OR and run the search again. You can prevent that by checking this option.", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_default_orderby'><?php _e("Default order", "relevanssi"); ?></label>
		</th>
		<td>
			<select name='relevanssi_default_orderby' id='relevanssi_default_orderby'>
				<option value='relevance' <?php echo $orderby_relevance ?>><?php _e("Relevance (highly recommended)", "relevanssi"); ?></option>
				<option value='post_date' <?php echo $orderby_date ?>><?php _e("Post date", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php printf(__("If you want to override this or use multi-layered ordering (eg. first order by relevance, but sort ties by post title), you can use the %s query variable. See Help for more information.", "relevanssi"), "<code>orderby</code>"); ?></p>
			<?php if (RELEVANSSI_PREMIUM) : ?>
			<p class="description"><?php _e(" If you want date-based results, see the recent post bonus in the Weights section.", "relevanssi"); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_fuzzy'><?php _e("Keyword matching", "relevanssi"); ?></label>
		</th>
		<td>
			<select name='relevanssi_fuzzy' id='relevanssi_fuzzy'>
				<option value='never' <?php echo $fuzzy_never ?>><?php _e("Whole words", "relevanssi"); ?></option>
				<option value='always' <?php echo $fuzzy_always ?>><?php _e("Partial words", "relevanssi"); ?></option>
				<option value='sometimes' <?php echo $fuzzy_sometimes ?>><?php _e("Partial words if no hits for whole words", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("Whole words means Relevanssi only finds posts that include the whole search term.", "relevanssi"); ?></p>
			<p class="description"><?php _e("Partial words also includes cases where the word in the index begins or ends with the search term (searching for 'ana' will match 'anaconda' or 'banana', but not 'banal'). See Help, if you want to make Relevanssi match also inside words.", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php _e("Weights", "relevanssi"); ?>
		</th>
		<td>
			<p class="description"><?php _e("All the weights in the table are multipliers. To increase the weight of an element, use a higher number. To make an element less significant, use a number lower than 1.", "relevanssi"); ?></p>
			<table class="relevanssi-weights-table">
			<thead>
				<tr>
					<th><?php _e('Element', 'relevanssi'); ?></th>
					<th class="col-2"><?php _e('Weight', 'relevanssi'); ?></th>
				</tr>
			</thead>
			<tr>
				<td>
					<?php _e('Content', 'relevanssi'); ?>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_content_boost' id='relevanssi_content_boost' size='4' value='<?php echo $content_boost ?>' />
				</td>
			</tr>
			<tr>
				<td>
					<?php _e('Titles', 'relevanssi'); ?>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_title_boost' id='relevanssi_title_boost' size='4' value='<?php echo $title_boost ?>' />
				</td>
			</tr>
			<?php if (function_exists('relevanssi_form_link_weight')) relevanssi_form_link_weight($link_boost); ?>
			<tr>
				<td>
					<?php _e('Comment text', 'relevanssi'); ?>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_comment_boost' id='relevanssi_comment_boost' size='4' value='<?php echo $comment_boost ?>' />
				</td>
			</tr>
			<?php
				if (function_exists('relevanssi_form_post_type_weights')) relevanssi_form_post_type_weights($post_type_weights);
				if (function_exists('relevanssi_form_taxonomy_weights')) relevanssi_form_taxonomy_weights($post_type_weights);
				if (function_exists('relevanssi_form_tag_weight')) relevanssi_form_tag_weight($post_type_weights);
				if (function_exists('relevanssi_form_recency_weight')) relevanssi_form_recency_weight($recency_bonus);
			?>
			</table>
		</td>
	</tr>	
	<?php if (function_exists('relevanssi_form_recency_cutoff')) relevanssi_form_recency_cutoff($recency_bonus_days); ?>

	<?php if (function_exists('icl_object_id') && !function_exists('pll_get_post')) : ?>
	<tr>
		<th scope="row">
		<label for='relevanssi_wpml_only_current'><?php _e("WPML", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Limit results to current language.", "relevanssi"); ?></legend>
			<label for='relevanssi_wpml_only_current'>
				<input type='checkbox' name='relevanssi_wpml_only_current' id='relevanssi_wpml_only_current' <?php echo $wpml_only_current ?> />
				<?php _e("Limit results to current language.", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("Enabling this option will restrict the results to the currently active language. If the option is disabled, results will include posts in all languages.", "relevanssi"); ?></p>
		</td>
	</tr>
	<?php endif; ?>
	<?php if (function_exists('pll_get_post')) : ?>
	<tr>
		<th scope="row">
		<label for='relevanssi_polylang_all_languages'><?php _e("Polylang", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Allow results from all languages.", "relevanssi"); ?></legend>
			<label for='relevanssi_polylang_all_languages'>
				<input type='checkbox' name='relevanssi_polylang_all_languages' id='relevanssi_polylang_all_languages' <?php echo $polylang_allow_all ?> />
				<?php _e("Allow results from all languages.", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("By default Polylang restricts the search to the current language. Enabling this option will lift this restriction.", "relevanssi"); ?></p>
		</td>
	</tr>
	<?php endif; ?>
	<tr>
		<th scope="row">
		<label for='relevanssi_admin_search'><?php _e("Admin search", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Use Relevanssi for admin searches.", "relevanssi"); ?></legend>
			<label for='relevanssi_admin_search'>
				<input type='checkbox' name='relevanssi_admin_search' id='relevanssi_admin_search' <?php echo $admin_search ?> />
				<?php _e("Use Relevanssi for admin searches.", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("If checked, Relevanssi will be used for searches in the admin interface. The page search doesn't use Relevanssi, because WordPress works like that.", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_respect_exclude'><?php printf(__('Respect %s', 'relevanssi'), 'exclude_from_search' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Respect exclude_from_search for custom post types", "relevanssi"); ?></legend>
			<label for='relevanssi_respect_exclude'>
				<input type='checkbox' name='relevanssi_respect_exclude' id='relevanssi_respect_exclude' <?php echo $respect_exclude ?> />
				<?php printf(__("Respect %s for custom post types", "relevanssi"), '<code>exclude_from_search</code>' ); ?>
			</label>
			<p class="description"><?php _e("If checked, Relevanssi won't display posts of custom post types that have 'exclude_from_search' set to true.", 'relevanssi'); ?></p>
			<?php
				if (!empty($respect_exclude)) {
					$pt_1 = get_post_types(array('exclude_from_search' => '1'));
					$pt_2 = get_post_types(array('exclude_from_search' => true));
					$private_types = array_merge($pt_1, $pt_2);
					$problem_post_types = array_intersect($index_post_types, $private_types);
					if (!empty($problem_post_types)) : ?>
						<p class="description important"><?php _e("You probably should uncheck this option, because you've set Relevanssi to index the following non-public post types:", "relevanssi"); echo " " . implode(", ", $problem_post_types); ?></p>
					<?php endif;
				}
			?>
		</fieldset>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_throttle'><?php _e("Throttle searches", "relevanssi"); ?></label>
		</th>
		<td id="throttlesearches">
		<div id="throttle_disabled" <?php if (!$orderby_date) echo "class='screen-reader-text'" ?>>
		<p class="description"><?php _e("Throttling the search does not work when sorting the posts by date.", 'relevanssi'); ?></p>
		</div>
		<div id="throttle_enabled" <?php if (!$orderby_relevance) echo "class='screen-reader-text'" ?>>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Throttle searches.", "relevanssi"); ?></legend>
			<label for='relevanssi_throttle'>
				<input type='checkbox' name='relevanssi_throttle' id='relevanssi_throttle' <?php echo $throttle ?> />
				<?php _e("Throttle searches.", "relevanssi"); ?>
			</label>
		</fieldset>
		<?php if ($docs_count < 1000) : ?>
			<p class="description important"><?php _e("Your database is so small that you don't need to enable this.", 'relevanssi'); ?></p>
		<?php endif; ?>
		<p class="description"><?php _e("If this option is checked, Relevanssi will limit search results to at most 500 results per term. This will improve performance, but may cause some relevant documents to go unfound. See Help for more details.", 'relevanssi'); ?></p>
		</div>
		</td>
	</tr>	
	<tr>
		<th scope="row">
			<label for='relevanssi_cat'><?php _e('Category restriction', 'relevanssi'); ?></label>
		</th>
		<td>
			<div class="categorydiv" style="max-width: 400px">
			<div class="tabs-panel">
			<ul id="categorychecklist">
			<?php 
				$selected_cats = explode(',', $cat);
				$walker = get_Relevanssi_Taxonomy_Walker();
				$walker->name = "relevanssi_cat";
				wp_terms_checklist(0, array('taxonomy' => 'category', 'selected_cats' => $selected_cats, 'walker' => $walker));
			?>
			</ul>
			<input type="hidden" name="relevanssi_cat_active" value="1" />
			</div>
			</div>
			<p class="description"><?php _e("You can restrict search results to a category for all searches. For restricting on a per-search basis and more options (eg. tag restrictions), see Help.", 'relevanssi'); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_excat'><?php _e('Category exclusion', 'relevanssi'); ?></label>
		</th>
		<td>
			<div class="categorydiv" style="max-width: 400px">
			<div class="tabs-panel">
			<ul id="categorychecklist">
			<?php 
				$selected_cats = explode(',', $excat);
				$walker = get_Relevanssi_Taxonomy_Walker();
				$walker->name = "relevanssi_excat";
				wp_terms_checklist(0, array('taxonomy' => 'category', 'selected_cats' => $selected_cats, 'walker' => $walker));
			?>
			</ul>
			<input type="hidden" name="relevanssi_excat_active" value="1" />
			</div>
			</div>
			<p class="description"><?php _e("Posts in these categories are not included in search results. To exclude the posts completely from the index, see Help.", 'relevanssi'); ?></p>
		</td>
	</tr>
	<tr>
	    <th scope="row">
			<label for='relevanssi_expst'><?php _e('Post exclusion', 'relevanssi'); ?>
		</th>
		<td>
			<input type='text'  name='relevanssi_expst' id='relevanssi_expst' size='60' value='<?php echo esc_attr($expst); ?>' />
			<p class="description"><?php _e("Enter a comma-separated list of post or page ID's to exclude those pages from the search results.", 'relevanssi'); ?></p>
			<?php if (RELEVANSSI_PREMIUM) : ?>
				<p class="description"><?php _e("With Relevanssi Premium, it's better to use the check box on post edit pages. That will remove the posts completely from the index, and will work with multisite searches unlike this setting.", "relevanssi"); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php if (function_exists('relevanssi_form_searchblogs_setting')) relevanssi_form_searchblogs_setting($searchblogs_all, $searchblogs); ?>
	</table>

	<?php endif; // active tab: searching ?>

	<?php if ($active_tab === "excerpts") : ?>

	<h2 id="excerpts"><?php _e("Custom excerpts/snippets", "relevanssi"); ?></h2>

	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_excerpts'><?php _e("Custom search result snippets", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Create custom search result snippets", "relevanssi"); ?></legend>
			<label >
				<input type='checkbox' name='relevanssi_excerpts' id='relevanssi_excerpts' <?php echo $excerpts ?> />
				<?php _e("Create custom search result snippets", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("Only enable this if you actually use the custom excerpts.", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr id="tr_excerpt_length" <?php if (empty($excerpts)) echo "class='relevanssi_disabled'" ?>>
		<th scope="row">
			<label for='relevanssi_excerpt_length'><?php _e("Length of the snippet", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_excerpt_length' id='relevanssi_excerpt_length' size='4' value='<?php echo esc_attr($excerpt_length); ?>' <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
			<select name='relevanssi_excerpt_type' id='relevanssi_excerpt_type' <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>>
				<option value='chars' <?php echo $excerpt_chars ?>><?php _e("characters", "relevanssi"); ?></option>
				<option value='words' <?php echo $excerpt_words ?>><?php _e("words", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("Using words is much faster than characters. Don't use characters, unless you have a really good reason and your posts are short.", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr id="tr_excerpt_allowable_tags" <?php if (empty($excerpts)) echo "class='relevanssi_disabled'" ?>>
		<th scope="row">
			<label for='relevanssi_excerpt_allowable_tags'><?php _e("Allowable tags in excerpts", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_excerpt_allowable_tags' id='relevanssi_excerpt_allowable_tags' size='60' value='<?php echo esc_attr($excerpt_allowable_tags); ?>' <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
			<p class="description"><?php _e("List all tags you want to allow in excerpts. For example: &lt;p&gt;&lt;a&gt;&lt;strong&gt;.", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr id="tr_excerpt_custom_fields" <?php if (empty($excerpts)) echo "class='relevanssi_disabled'"; ?>>
		<th scope="row">
			<label for='relevanssi_excerpt_custom_fields'><?php _e("Use custom fields for excerpts", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Use custom field content for building excerpts", "relevanssi"); ?></legend>
			<label>
				<input type='checkbox' name='relevanssi_excerpt_custom_fields' id='relevanssi_excerpt_custom_fields' <?php echo $excerpt_custom_fields ?> <?php if (empty($excerpts) || empty($original_index_fields)) echo "disabled='disabled'"; ?>/>
				<?php _e("Use custom field content for building excerpts", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("Use the custom fields setting for indexing for excerpt-making as well. Enabling this option will show custom field content in Relevanssi-generated excerpts.", "relevanssi"); ?>
		<?php if (RELEVANSSI_PREMIUM) { _e("Enable this option to use PDF content for excerpts.", "relevanssi"); } ?>
		</p>

		<p class="description"><?php _e("Current custom field setting", 'relevanssi'); ?>: 
		<?php
			if ($original_index_fields === "visible") _e("all visible custom fields", 'relevanssi');
			else if ($original_index_fields === "all") _e("all custom fields", 'relevanssi');
			else if (!empty($original_index_fields)) echo "<code>$original_index_fields</code>";
			else if (RELEVANSSI_PREMIUM) { _e('Just PDF content', 'relevanssi'); } else { _e('None selected', 'relevanssi'); }
		?></p>
		</td>
	</tr>
	</table>

	<h2><?php _e("Search hit highlighting", "relevanssi"); ?></h2>

	<table id="relevanssi_highlighting" class="form-table <?php if (empty($excerpts)) echo "relevanssi_disabled" ?>">
	<tr>
		<th scope="row">
			<label for='relevanssi_highlight'><?php _e("Highlight type", 'relevanssi'); ?></label>
		</th>
		<td>
			<select name='relevanssi_highlight' id='relevanssi_highlight' <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>>
				<option value='no' <?php echo $highlight_none ?>><?php _e('No highlighting', 'relevanssi'); ?></option>
				<option value='mark' <?php echo $highlight_mark ?>>&lt;mark&gt;</option>
				<option value='em' <?php echo $highlight_em ?>>&lt;em&gt;</option>
				<option value='strong' <?php echo $highlight_strong ?>>&lt;strong&gt;</option>
				<option value='col' <?php echo $highlight_col ?>><?php _e('Text color', 'relevanssi'); ?></option>
				<option value='bgcol' <?php echo $highlight_bgcol ?>><?php _e('Background color', 'relevanssi'); ?></option>
				<option value='css' <?php echo $highlight_style ?>><?php _e("CSS Style", 'relevanssi'); ?></option>
				<option value='class' <?php echo $highlight_class ?>><?php _e("CSS Class", 'relevanssi'); ?></option>
			</select>
			<p class="description"><?php _e("Requires custom snippets to work.", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr id="relevanssi_txt_col" <?php echo $txt_col_display; ?>>
		<th scope="row">
			<label for="relevanssi_txt_col"><?php _e("Text color", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_txt_col' id='relevanssi_txt_col' size='7' class="color-field" data-default-color="#ff0000" value='<?php echo esc_attr($txt_col); ?>' <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
		</td>
	</tr>
	<tr id="relevanssi_bg_col" <?php echo $bg_col_display; ?>>
		<th scope="row">
			<label for="relevanssi_bg_col"><?php _e("Background color", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_bg_col' id='relevanssi_bg_col' size='7' class="color-field" data-default-color="#ffaf75" value='<?php echo esc_attr($bg_col); ?>' <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
		</td>
	</tr>
	<tr id="relevanssi_css" <?php echo $css_display; ?>>
		<th scope="row">
			<label for='relevanssi_css'><?php _e("CSS style for highlights", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_css' id='relevanssi_css' size='60' value='<?php echo esc_attr($css); ?>' <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
			<p class="description"><?php printf(__("The highlights will be wrapped in a %s with this CSS in the style parameter.", "relevanssi"), "&lt;span&gt;"); ?></p>
		</td>
	</tr>
	<tr id="relevanssi_class" <?php echo $class_display; ?>>
		<th scope="row">
			<label for='relevanssi_class'><?php _e("CSS class for highlights", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_class' id='relevanssi_class' size='60' value='<?php echo esc_attr($class); ?>' <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
			<p class="description"><?php printf(__("The highlights will be wrapped in a %s with this class.", "relevanssi"), "&lt;span&gt;"); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_hilite_title'><?php _e("Highlight in titles", 'relevanssi'); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Highlight query terms in titles", "relevanssi"); ?></legend>
			<label for='relevanssi_hilite_title'>
				<input type='checkbox' name='relevanssi_hilite_title' id='relevanssi_hilite_title' <?php echo $hititle ?> <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
				<?php _e("Highlight query terms in titles", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php printf(__("Highlights in titles require changes to the search results template. You need to replace %s in the search results template with %s. For more information, see the contextual help.", "relevanssi"), "<code>the_title()</code>", "<code>relevanssi_the_title()</code>"); ?></p>		
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_highlight_docs'><?php _e("Highlight in documents", 'relevanssi'); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Highlight query terms in documents", "relevanssi"); ?></legend>
			<label for='relevanssi_highlight_docs'>
				<input type='checkbox' name='relevanssi_highlight_docs' id='relevanssi_highlight_docs' <?php echo $highlight_docs ?> <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
				<?php _e("Highlight query terms in documents", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php printf(__("Highlights hits when user opens the post from search results. This requires an extra parameter (%s) to the links from the search results pages so in order to get these highlights, you need to use %s or %s to print out the permalinks on the search results templates.", "relevanssi"), "<code>highlight</code>", "<code>relevanssi_get_permalink()</code>", "<code>relevanssi_the_permalink()</code>"); ?></p>
		</td>
	</tr>
	<?php if (function_exists('relevanssi_form_highlight_external')) relevanssi_form_highlight_external($highlight_docs_ext, $excerpts); ?>
	<tr>
		<th scope="row">
			<label for='relevanssi_highlight_comments'><?php _e("Highlight in comments", 'relevanssi'); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Highlight query terms in comments", "relevanssi"); ?></legend>
			<label for='relevanssi_highlight_comments'>
				<input type='checkbox' name='relevanssi_highlight_comments' id='relevanssi_highlight_comments' <?php echo $highlight_coms ?> <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
				<?php _e("Highlight query terms in comments", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("Highlights hits in comments when user opens the post from search results.", "relevanssi"); ?></p>		
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_word_boundaries'><?php _e("Highlighting problems with non-ASCII alphabet?", 'relevanssi'); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Uncheck this if you use non-ASCII characters", "relevanssi"); ?></legend>
			<label for='relevanssi_word_boundaries'>
				<input type='checkbox' name='relevanssi_word_boundaries' id='relevanssi_word_boundaries' <?php echo $word_boundaries ?> <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
				<?php _e("Uncheck this if you use non-ASCII characters", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("If you use non-ASCII characters (like Cyrillic alphabet) and the highlights don't work, unchecking this option may make the highlights work.", "relevanssi"); ?></p>		
		</td>
	</tr>
	</table>

	<h2><?php _e("Breakdown of search results", "relevanssi"); ?></h2>

	<table id="relevanssi_breakdown" class="form-table <?php if (empty($excerpts)) echo "relevanssi_disabled" ?>">
	<tr>
		<th scope="row">
			<label for='relevanssi_show_matches'><?php _e("Breakdown of search hits in excerpts", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Show the breakdown of search hits in the excerpts", "relevanssi"); ?></legend>
			<label for='relevanssi_show_matches'>
				<input type='checkbox' name='relevanssi_show_matches' id='relevanssi_show_matches' <?php echo $show_matches ?> <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>/>
				<?php _e("Show the breakdown of search hits in the excerpts.", "relevanssi"); ?>
			</label>
		</fieldset>
		<p class="description"><?php _e("Requires custom snippets to work.", "relevanssi"); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_show_matches_text'><?php _e("The breakdown format", "relevanssi"); ?></label>
		</th>
		<td>
			<textarea name='relevanssi_show_matches_text' id='relevanssi_show_matches_text' cols="80" rows="4" <?php if (empty($excerpts)) echo "disabled='disabled'"; ?>><?php echo esc_attr($show_matches_text) ?></textarea>
			<p class="description"><?php _e("Use %body%, %title%, %tags% and %comments% to display the number of hits (in different parts of the post), %total% for total hits, %score% to display the document weight and %terms% to show how many hits each search term got.", "relevanssi"); ?></p>
		</td>
	</tr>
	</table>

	<?php endif; // active tab: excerpts & highlights ?>

	<?php if ($active_tab === "indexing") : ?>

	<?php
	
	$docs_count = $wpdb->get_var("SELECT COUNT(DISTINCT doc) FROM " . $relevanssi_variables['relevanssi_table'] . " WHERE doc != -1");
	$terms_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $relevanssi_variables['relevanssi_table']);
	$biggest_doc = $wpdb->get_var("SELECT doc FROM " . $relevanssi_variables['relevanssi_table'] . " ORDER BY doc DESC LIMIT 1");
	
	if (RELEVANSSI_PREMIUM) {
		$user_count = $wpdb->get_var("SELECT COUNT(DISTINCT item) FROM " . $relevanssi_variables['relevanssi_table'] . " WHERE type = 'user'");
		$taxterm_count = $wpdb->get_var("SELECT COUNT(DISTINCT item) FROM " . $relevanssi_variables['relevanssi_table'] . " WHERE (type != 'post' AND type != 'attachment' AND type != 'user')");
	}

	?>
	<div id="indexing_tab">
	
	<table class="form-table">
	<tr>
		<th scope="row">
			<input type='submit' name='submit' value='<?php esc_attr_e('Save the options', 'relevanssi'); ?>' class='button button-primary' /><br /><br />
			<input type="button" id="build_index" name="index" value="<?php esc_attr_e('Build the index', 'relevanssi'); ?>" class='button-primary' /><br /><br />
			<input type="button" id="continue_indexing" name="continue" value="<?php esc_attr_e('Index unindexed posts', 'relevanssi'); ?>" class='button-primary' />
		</th>
		<td>
			<div id='indexing_button_instructions'>
				<p class="description"><?php printf(__("%s empties the existing index and rebuilds it from scratch.", "relevanssi"), "<strong>" . __("Build the index", "relevanssi") . "</strong>");?></p>
				<p class="description"><?php printf(__("%s doesn't empty the index and only indexes those posts that are not indexed. You can use it if you have to interrupt building the index.", "relevanssi"), "<strong>" . __("Index unindexed posts", "relevanssi") . "</strong>");?>
				<?php if (RELEVANSSI_PREMIUM) _e("This doesn't index any taxonomy terms or users.", "relevanssi"); ?></p>
			</div>
			<div id='relevanssi-note' style='display: none'></div>
			<div id='relevanssi-progress' class='rpi-progress'><div class="rpi-indicator"></div></div>
			<div id='relevanssi-timer'><?php _e("Time elapsed", "relevanssi"); ?>: <span id="relevanssi_elapsed">0:00:00</span> | <?php _e("Time remaining", "relevanssi"); ?>: <span id="relevanssi_estimated"><?php _e("some time", "relevanssi"); ?></span></div>
			<textarea id='results' rows='10' cols='80'></textarea>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php _e("State of the index", "relevanssi"); ?></td>
		<td id="stateoftheindex"><p><?php echo $docs_count ?> <?php echo _n("document in the index.", "documents in the index.", $docs_count, "relevanssi"); ?>
	<?php if (RELEVANSSI_PREMIUM) : ?>
		<br /><?php echo $user_count ?> <?php echo _n("user in the index.", "users in the index.", $user_count, "relevanssi"); ?><br />
		<?php echo $taxterm_count ?> <?php echo _n("taxonomy term in the index.", "taxonomy terms in the index.", $taxterm_count, "relevanssi"); ?>
	<?php endif; ?>	
		</p>
		<p><?php echo $terms_count; ?> <?php echo _n("term in the index.", "terms in the index.", $terms_count, "relevanssi"); ?><br />
		<?php echo $biggest_doc ?> <?php _e("is the highest post ID indexed.", "relevanssi"); ?></p>
		</td>
	</tr>
	</table>

<?php
	if (count($index_post_types) < 2) {
		echo "<p><strong>" . __("WARNING: You've chosen no post types to index. Nothing will be indexed. Choose some post types to index.", 'relevanssi') . "</strong></p>";
	}
?>

	<h2 id="indexing"><?php _e('Indexing options', 'relevanssi'); ?></h2>

	<p><?php _e("Any changes to the settings on this page require reindexing before they take effect.", "relevanssi"); ?></p>

	<table class="form-table">
	<tr>
		<th scope="row"><?php _e("Post types", "relevanssi"); ?></th>
		<td>

<table class="widefat" id="index_post_types_table">
	<thead>
		<tr>
			<th><?php _e('Type', 'relevanssi'); ?></th>
			<th><?php _e('Index', 'relevanssi'); ?></th>
			<th><?php _e('Excluded from search?', 'relevanssi'); ?></th>
		</tr>
	</thead>
	<?php
		$pt_1 = get_post_types(array('exclude_from_search' => '0'));
		$pt_2 = get_post_types(array('exclude_from_search' => false));
		$public_types = array_merge($pt_1, $pt_2);
		$post_types = get_post_types();
		foreach ($post_types as $type) {
			if ('nav_menu_item' === $type) continue;
			if ('revision' === $type) continue;
			if (in_array($type, $index_post_types)) {
				$checked = 'checked="checked"';
			}
			else {
				$checked = '';
			}
			$label = sprintf("%s", $type);
			in_array($type, $public_types) ? $public = __('no', 'relevanssi') : $public = __('yes', 'relevanssi');

			echo <<<EOH
	<tr>
		<td>
			$label
		</td>
		<td>
			<input type='checkbox' name='relevanssi_index_type_$type' id='relevanssi_index_type_$type' $checked />
		</td>
		<td>
			$public
		</td>
	</tr>
EOH;
		}
	?>
	<tr style="display:none">
		<td>
			Helpful little control field
		</td>
		<td>
			<input type='checkbox' name='relevanssi_index_type_bogus' id='relevanssi_index_type_bogus' checked="checked" />
		</td>
		<td>
			This is our little secret, just for you and me
		</td>
	</tr>
	</table>
		<p class="description"><?php printf(__("%s includes all attachment types. If you want to index only some attachments, see %sControlling attachment types in the Knowledge base%s.", "relevanssi"), '<code>attachment</code>', '<a href="https://www.relevanssi.com/knowledge-base/controlling-attachment-types-index/">', '</a>'); ?></p>
	</td>
	</tr>

	<tr>
		<th scope="row">
			<?php _e('Taxonomies', 'relevanssi'); ?>
		</th>
		<td>

			<table class="widefat" id="custom_taxonomies_table">
			<thead>
				<tr>
					<th><?php _e('Taxonomy', 'relevanssi'); ?></th>
					<th><?php _e('Index', 'relevanssi'); ?></th>
					<th><?php _e('Public?', 'relevanssi'); ?></th>
				</tr>
			</thead>

	<?php
		$taxos = get_taxonomies('', 'objects');
		foreach ($taxos as $taxonomy) {
			if ($taxonomy->name === 'nav_menu') continue;
			if ($taxonomy->name === 'link_category') continue;
			if (in_array($taxonomy->name, $index_taxonomies_list)) {
				$checked = 'checked="checked"';
			}
			else {
				$checked = '';
			}
			$label = sprintf("%s", $taxonomy->name);
			$taxonomy->public ? $public = __('yes', 'relevanssi') : $public = __('no', 'relevanssi');
			$type = $taxonomy->name;

			echo <<<EOH
	<tr>
		<td>
			$label
		</td>
		<td>
			<input type='checkbox' name='relevanssi_index_taxonomy_$type' id='relevanssi_index_taxonomy_$type' $checked />
		</td>
		<td>
			$public
		</td>
	</tr>
EOH;
		}
	?>
			</table>

			<p class="description"><?php _e('If you check a taxonomy here, the terms for that taxonomy are indexed with the posts. If you for example choose "post_tag", searching for a tag will find all posts that have the tag.', 'relevanssi'); ?>

		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_comments'><?php _e("Comments", "relevanssi"); ?></label>		
		</th>
		<td>
			<select name='relevanssi_index_comments' id='relevanssi_index_comments'>
				<option value='none' <?php echo $incom_type_none ?>><?php _e("none", "relevanssi"); ?></option>
				<option value='normal' <?php echo $incom_type_normal ?>><?php _e("comments", "relevanssi"); ?></option>
				<option value='all' <?php echo $incom_type_all ?>><?php _e("comments and pingbacks", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("If you choose to index comments, you can choose if you want to index just comments, or everything including comments and track- and pingbacks.", 'relevanssi'); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_fields'><?php _e("Custom fields", "relevanssi"); ?></label>
		</th>
		<td>
			<select name='relevanssi_index_fields_select' id='relevanssi_index_fields_select'>
				<option value='none' <?php echo $fields_select_none; ?>><?php _e("none", "relevanssi"); ?></option>
				<option value='all' <?php echo $fields_select_all ?>><?php _e("all", "relevanssi"); ?></option>
				<option value='visible' <?php echo $fields_select_visible ?>><?php _e("visible", "relevanssi"); ?></option>
				<option value='some' <?php echo $fields_select_some ?>><?php _e("some", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("'All' indexes all custom fields for posts.", "relevanssi"); echo "<br/>";
			_e("'Visible' only includes the custom fields that are visible in the user interface (with names that don't start with an underscore).", "relevanssi"); echo "<br />";
			_e("'Some' lets you choose individual custom fields to index.", "relevanssi"); ?></p>
			<div id="index_field_input" <?php if (empty($fields_select_some)) echo 'style="display: none"'; ?>>
				<input type='text' name='relevanssi_index_fields' id='relevanssi_index_fields' size='60' value='<?php echo esc_attr($index_fields) ?>' />
				<p class="description"><?php _e("Enter a comma-separated list of custom fields to include in the index. With Relevanssi Premium, you can also use 'fieldname_%_subfieldname' notation for ACF repeater fields.", "relevanssi"); ?></p>
				<p class="description"><?php _e("You can use 'relevanssi_index_custom_fields' filter hook to adjust which custom fields are indexed.", "relevanssi"); ?></p>
			</div>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
			<p class="description"><?php printf(__("If you want the SKU included, choose %s and enter %s. Also see the contextual help for more details.", "relevanssi"), "'" . __('some', "relevanssi") . "'", '<code>_sku</code>'); ?></p>
			<?php endif; ?>
		</td>
	</tr>

	

	<tr>
		<th scope="row">
			<label for='relevanssi_index_author'><?php _e('Author display names', 'relevanssi'); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Index the post author display name", "relevanssi"); ?></legend>
			<label for='relevanssi_index_author'>
				<input type='checkbox' name='relevanssi_index_author' id='relevanssi_index_author' <?php echo $index_author ?> />
				<?php _e("Index the post author display name", "relevanssi"); ?>
			</label>
			<p class="description"><?php _e("Searching for the post author display name will return posts by that author.", 'relevanssi'); ?></p>
		</fieldset>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_excerpt'><?php _e('Excerpts', 'relevanssi'); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Index the post excerpt", "relevanssi"); ?></legend>
			<label for='relevanssi_index_excerpt'>
				<input type='checkbox' name='relevanssi_index_excerpt' id='relevanssi_index_excerpt' <?php echo $index_excerpt ?> />
				<?php _e("Index the post excerpt", "relevanssi") ?>
			</label>
			<p class="description"><?php _e("Relevanssi will find posts by the content in the excerpt.", 'relevanssi'); ?></p>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
			<p class="description"><?php _e("WooCommerce stores the product short description in the excerpt, so it's a good idea to index excerpts.", "relevanssi"); ?></p>
			<?php endif; ?>
		</fieldset>
		</td>
	</tr>

	</table>

	<h2><?php _e("Shortcodes", "relevanssi"); ?></h2>

	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_expand_shortcodes'><?php _e("Expand shortcodes", "relevanssi"); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php _e("Index the post excerpt", "relevanssi"); ?></legend>
			<label for='relevanssi_expand_shortcodes'>
				<input type='checkbox' name='relevanssi_expand_shortcodes' id='relevanssi_expand_shortcodes' <?php echo $expand_shortcodes ?> />
				<?php _e("Expand shortcodes when indexing", "relevanssi"); ?>
			</label>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
			<p class="description important"><?php _e("WooCommerce has shortcodes that don't work well with Relevanssi. With WooCommerce, make sure the option is disabled.", "relevanssi"); ?></p>
			<?php endif; ?>
			<p class="description"><?php _e("If checked, Relevanssi will expand shortcodes in post content before indexing. Otherwise shortcodes will be stripped.", "relevanssi"); ?></p>
			<p class="description"><?php _e("If you use shortcodes to include dynamic content, Relevanssi will not keep the index updated, the index will reflect the status of the shortcode content at the moment of indexing.", "relevanssi"); ?></p>
		</fieldset>
		</td>
	</tr>

	<?php if (function_exists('relevanssi_form_disable_shortcodes')) relevanssi_form_disable_shortcodes($disable_shortcodes); ?>

	</table>

	<?php if (function_exists('relevanssi_form_index_users')) relevanssi_form_index_users($index_users, $index_subscribers, $index_user_fields); ?>

	<?php if (function_exists('relevanssi_form_index_synonyms')) relevanssi_form_index_synonyms($index_synonyms); ?>

	<?php if (function_exists('relevanssi_form_index_taxonomies')) relevanssi_form_index_taxonomies($index_taxonomies, $index_terms); ?>

	<?php if (function_exists('relevanssi_form_index_pdf_parent')) relevanssi_form_index_pdf_parent($index_pdf_parent, $index_post_types); ?>

	<h2><?php _e("Advanced indexing settings", "relevanssi"); ?></h2>

	<p><button type="button" id="show_advanced_indexing"><?php _e("Show advanced settings", "relevanssi"); ?></button></p>

	<table class="form-table screen-reader-text" id="advanced_indexing">
	<tr>
		<th scope="row">
			<label for='relevanssi_min_word_length'><?php _e("Minimum word length", "relevanssi"); ?></label>
		</th>
		<td>
			<input type='number' name='relevanssi_min_word_length' id='relevanssi_min_word_length' value='<?php echo esc_attr($min_word_length); ?>' />
			<p class="description"><?php _e("Words shorter than this many letters will not be indexed.", "relevanssi"); ?></p>
			<p class="description"><?php printf(__("To enable one-letter searches, you need to add a filter function on the filter hook %s that returns %s.", "relevanssi"), '<code>relevanssi_block_one_letter_searches</code>', '<code>false</code>'); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php _e("Punctuation control"); ?></th>
		<td><p class="description"><?php _e("Here you can adjust how the punctuation is controlled. For more information, see help. Remember that any changes here require reindexing, otherwise searches will fail to find posts they should.", "relevanssi"); ?></p></td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_hyphens'><?php _e("Hyphens and dashes", "relevanssi"); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_hyphens' id='relevanssi_punct_hyphens'>
				<option value='keep' <?php echo $punct_hyphens_keep ?>><?php _e("Keep", "relevanssi"); ?></option>
				<option value='replace' <?php echo $punct_hyphens_replace ?>><?php _e("Replace with spaces", "relevanssi"); ?></option>
				<option value='remove' <?php echo $punct_hyphens_remove ?>><?php _e("Remove", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("How Relevanssi should handle hyphens and dashes (en and em dashes)? Replacing with spaces is generally the best option, but in some cases removing completely is the best option. Keeping them is rarely the best option.", "relevanssi"); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_quotes'><?php _e("Apostrophes and quotes", "relevanssi"); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_quotes' id='relevanssi_punct_quotes'>
				<option value='replace' <?php echo $punct_quotes_replace ?>><?php _e("Replace with spaces", "relevanssi"); ?></option>
				<option value='remove' <?php echo $punct_quotes_remove ?>><?php _e("Remove", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("How Relevanssi should handle apostrophes and quotes? It's not possible to keep them; that would lead to problems. Default behaviour is to replace with spaces, but sometimes removing makes sense.", "relevanssi"); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_ampersands'><?php _e("Ampersands", "relevanssi"); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_ampersands' id='relevanssi_punct_ampersands'>
				<option value='keep' <?php echo $punct_ampersands_keep ?>><?php _e("Keep", "relevanssi"); ?></option>
				<option value='replace' <?php echo $punct_ampersands_replace ?>><?php _e("Replace with spaces", "relevanssi"); ?></option>
				<option value='remove' <?php echo $punct_ampersands_remove ?>><?php _e("Remove", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("How Relevanssi should handle ampersands? Replacing with spaces is generally the best option, but if you talk a lot about D&amp;D, for example, keeping the ampersands is useful.", "relevanssi"); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_decimals'><?php _e("Decimal separators", "relevanssi"); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_decimals' id='relevanssi_punct_decimals'>
				<option value='keep' <?php echo $punct_decimals_keep ?>><?php _e("Keep", "relevanssi"); ?></option>
				<option value='replace' <?php echo $punct_decimals_replace ?>><?php _e("Replace with spaces", "relevanssi"); ?></option>
				<option value='remove' <?php echo $punct_decimals_remove ?>><?php _e("Remove", "relevanssi"); ?></option>
			</select>
			<p class="description"><?php _e("How Relevanssi should handle periods between decimals? Replacing with spaces is the default option, but that often leads to the numbers being removed completely. If you need to search decimal numbers a lot, keep the periods.", "relevanssi"); ?></p>

		</td>
	</tr>
	<?php if (function_exists('relevanssi_form_thousep')) relevanssi_form_thousep($thousand_separator); ?>

	<?php if (function_exists('relevanssi_form_mysql_columns')) relevanssi_form_mysql_columns($mysql_columns); ?>

	<?php if (function_exists('relevanssi_form_internal_links')) relevanssi_form_internal_links($intlinks_noindex, $intlinks_strip, $intlinks_nostrip); ?>

	</table>

	<p><button type="button" style="display: none" id="hide_advanced_indexing"><?php _e("Hide advanced settings", "relevanssi"); ?></button></p>

	</div> <?php // #indexing_tab ?>

	<?php endif; // active tab: indexing ?>

	<?php if ($active_tab === "attachments") : ?>
	
	<?php if (function_exists('relevanssi_form_attachments')) { relevanssi_form_attachments($index_post_types, $index_pdf_parent); }
		else {
			$display_save_button = false; ?>
	
	<h2><?php _e("Indexing attachment content", "relevanssi"); ?></h2>

	<p><?php _e("With Relevanssi Premium, you can index the text contents of PDF attachments. The contents of the attachments are processed on an external service, which makes the feature reliable and light on your own server performance.", "relevanssi"); ?></p>

	<p><?php printf(__("In order to access this and many other delightful Premium features, %s buy Relevanssi Premium here%s.", "relevanssi"), '<a href="https://www.relevanssi.com/buy-premium/">', '</a>'); ?></p>
	
	<?php } ?>

	<?php endif; // active tab: attachments ?>

	<?php if ($active_tab === "synonyms") : ?>

	<h3 id="synonyms"><?php _e("Synonyms", "relevanssi"); ?></h3>

	<table class="form-table">
	<tr>
		<th scope="row">
			<?php _e("Synonyms", "relevanssi"); ?>
		</th>
		<td>
			<p class="description"><?php _e("Add synonyms here to make the searches find better results. If you notice your users frequently misspelling a product name, or for other reasons use many names for one thing, adding synonyms will make the results better.", "relevanssi"); ?></p>
	
			<p class="description"><?php _e("Do not go overboard, though, as too many synonyms can make the search confusing: users understand if a search query doesn't match everything, but they get confused if the searches match to unexpected things.", "relevanssi"); ?></p>
			<br />
			<textarea name='relevanssi_synonyms' id='relevanssi_synonyms' rows='9' cols='60'><?php echo htmlspecialchars($synonyms); ?></textarea>
			
			<p class="description"><?php _e("The format here is <code>key = value</code>. If you add <code>dog = hound</code> to the list of synonyms, searches for <code>dog</code> automatically become a search for <code>dog hound</code> and will thus match to posts that include either <code>dog</code> or <code>hound</code>. This only works in OR searches: in AND searches the synonyms only restrict the search, as now the search only finds posts that contain <strong>both</strong> <code>dog</code> and <code>hound</code>.", "relevanssi"); ?></p>

			<p class="description"><?php _e("The synonyms are one direction only. If you want both directions, add the synonym again, reversed: <code>hound = dog</code>.", "relevanssi"); ?></p>

			<p class="description"><?php _e("It's possible to use phrases for the value, but not for the key. <code>dog = \"great dane\"</code> works, but <code>\"great dane\" = dog</code> doesn't.", "relevanssi"); ?></p>

			<?php if (RELEVANSSI_PREMIUM) : ?>
				<p class="description"><?php _e("If you want to use synonyms in AND searches, enable synonym indexing on the Indexing tab.", "relevanssi"); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	</table>

	<?php endif; // active tab: synonyms ?>

	<?php if ($active_tab === "stopwords") : ?>

	<h3 id="stopwords"><?php _e("Stopwords", "relevanssi"); ?></h3>

	<?php relevanssi_show_stopwords(); ?>

	<?php
	if (apply_filters('relevanssi_display_common_words', true))
		relevanssi_common_words(25);
	?>

	<?php endif; // active tab: stopwords ?>

	<?php if ($active_tab === "importexport") : ?>

	<?php if (function_exists('relevanssi_form_importexport')) :
		relevanssi_form_importexport($serialized_options);
	endif;
	?>

	<?php endif; ?>

	<?php if ($display_save_button) : ?>

	<input type='submit' name='submit' value='<?php esc_attr_e('Save the options', 'relevanssi'); ?>' class='button button-primary' />

	<?php endif; ?>

	</form>
</div>

	<?php

	//relevanssi_sidebar();
}

function relevanssi_show_stopwords() {
	global $wpdb, $relevanssi_variables, $wp_version;

	RELEVANSSI_PREMIUM ? $plugin = 'relevanssi-premium' : $plugin = 'relevanssi';

	echo "<p>";
	_e("Enter a word here to add it to the list of stopwords. The word will automatically be removed from the index, so re-indexing is not necessary. You can enter many words at the same time, separate words with commas.", 'relevanssi');
	echo "</p>";

?>
<table class="form-table">
<tr>
	<th scope="row">
		<label for="addstopword"><p><?php _e("Stopword(s) to add", 'relevanssi'); ?>
	</th>
	<td>
		<textarea name="addstopword" id="addstopword" rows="2" cols="80"></textarea>
		<p><input type="submit" value="<?php esc_attr_e("Add", 'relevanssi'); ?>" class='button' /></p>
	</td>
</tr>
</table>
<p><?php
	_e("Here's a list of stopwords in the database. Click a word to remove it from stopwords. Removing stopwords won't automatically return them to index, so you need to re-index all posts after removing stopwords to get those words back to index.", 'relevanssi');
?></p>

<table class="form-table">
<tr>
	<th scope="row">
		<?php _e("Current stopwords", "relevanssi"); ?>
	</th>
	<td>
<?php

	echo "<ul>";
	$results = $wpdb->get_results("SELECT * FROM " . $relevanssi_variables['stopword_table']);
	$exportlist = array();
	foreach ($results as $stopword) {
		$sw = stripslashes($stopword->stopword);
		printf('<li style="display: inline;"><input type="submit" name="removestopword" value="%s"/></li>', esc_attr($sw));
		array_push($exportlist, $sw);
	}
	echo "</ul>";

	$exportlist = htmlspecialchars(implode(", ", $exportlist));
?>
	<p><input type="submit" id="removeallstopwords" name="removeallstopwords" value="<?php esc_attr_e('Remove all stopwords', 'relevanssi'); ?>" class='button' /></p>
	</td>
</tr>
<tr>
	<th scope="row">
		<?php _e("Exportable list of stopwords", "relevanssi");?>
	</th>
	<td>
		<textarea name="stopwords" id="stopwords" rows="2" cols="80"><?php echo $exportlist; ?></textarea>
		<p class="description"><?php _e("You can copy the list of stopwords here if you want to back up the list, copy it to a different blog or otherwise need the list.", "relevanssi"); ?></p>
	</td>
</tr>
</table>

<?php

}

function relevanssi_admin_help() {
	global $wpdb;

	$screen = get_current_screen();
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-searching',
		'title'    => __( 'Searching', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . sprintf(__("To adjust the post order, you can use the %s query parameter. With %s, you can use multiple layers of different sorting methods. See <a href='%s'>WordPress Codex</a> for more details on using arrays for orderby.", 'relevanssi'), "<code>orderby</code>", "<code>orderby</code>", "https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters") . "</li>" .
			"<li>" . __("Inside-word matching is disabled by default, because it increases garbage results that don't really match the search term. If you want to enable it, add the following function to your theme functions.php:", 'relevanssi') .
			'<pre>add_filter("relevanssi_fuzzy_query", "rlv_partial_inside_words");
function rlv_partial_inside_words($query) {
    return "(term LIKE \'%#term#%\')"; 
}</pre></li>' .
			"<li>" . sprintf(__("In order to adjust the throttle limit, you can use the %s filter hook.", 'relevanssi'), "<code>relevanssi_throttle_limit</code>") .
			'<pre>add_filter("relevanssi_throttle_limit", function( $limit ) { return 200; } );</pre>' .
			"<li>" . __("It's not usually necessary to adjust the limit from 500, but in some cases performance gains can be achieved by setting a lower limit. We don't suggest going under 200, as low values will make the results worse.", "relevanssi") . "</li>" .
			"</ul>",
	));
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-search-restrictions',
		'title'    => __( 'Restrictions', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . __("If you want the general search to target all posts, but have a single search form target only certain posts, you can add a hidden input variable to the search form. ", 'relevanssi') . "</li>" .
			"<li>" . __("For example in order to restrict the search to categories 10, 14 and 17, you could add this to the search form:", 'relevanssi') .
'<pre>&lt;input type="hidden" name="cats" value="10,14,17" /&gt;</pre></li>' .
			"<li>" . __("To restrict the search to posts tagged with alfa AND beta, you could add this to the search form:", 'relevanssi') .
'<pre>&lt;input type="hidden" name="tag" value="alfa+beta" /&gt;</pre></li>' .
			"<li>" . sprintf(__("For all the possible options, see the Codex documentation for %s.", "relevanssi"), '<a href="https://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>') . "</li>" .
			"</ul>",
	));
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-search-exclusions',
		'title'    => __( 'Exclusions', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . sprintf(__("For more exclusion options, see the Codex documentation for %s. For example, to exclude tag ID 10, use", "relevanssi"), '<a href="https://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>') .
			'<pre>&lt;input type="hidden" name="tag__not_in" value="10" /&gt;</pre></li>' .
			"<li>" . sprintf(__("To exclude posts from the index and not just from the search, you can use the %s filter hook. This would not index posts that have a certain taxonomy term:", 'relevanssi'), '<code>relevanssi_do_not_index</code>') .
'<pre>add_filter("relevanssi_do_not_index", "rlv_index_filter", 10, 2);
function rlv_index_filter($block, $post_id) {
	if (has_term("jazz", "genre", $post_id)) $block = true;
	return $block;
}
</pre></li>' . 
			"<li>" . sprintf(__("For more examples, see <a href='%s'>the related knowledge base posts</a>.", "relevanssi"), 'https://www.relevanssi.com/tag/relevanssi_do_not_index/') . "</li>" .
			"</ul>",
	));
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-logging',
		'title'    => __( 'Logs', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . sprintf(__('By default, the User searches page shows 20 most common keywords. In order to see more, you can adjust the value with the %s filter hook, like this:', 'relevanssi'), '<code>relevanssi_user_searches_limit</code>') .
			"<pre>add_filter('relevanssi_user_searches_limit', function() { return 50; });</pre></li>" .
			"<li>" . sprintf(__("The complete logs are stored in the %s database table, where you can access them if you need more information than what the User searches page provides.", "relevanssi"), '<code>' . $wpdb->prefix . "relevanssi_log</code>") . "</li>" .
			"</ul>",
	));
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-excerpts',
		'title'    => __( 'Excerpts', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . __('Building custom excerpts can be slow. If you are not actually using the excerpts, make sure you disable the option.', 'relevanssi') . "</li>" .
			"<li>" . sprintf(__('Custom snippets require that the search results template uses %s to print out the excerpts.', 'relevanssi'), "<code>the_excerpt()</code>") . "</li>" .
			"<li>" . __("Generally, Relevanssi generates the excerpts from post content. If you want to include custom field content in the excerpt-building, this can be done with a simple setting from the excerpt settings.", "relevanssi") . "</li>" .
			"<li>" . sprintf(__("If you want more control over what content Relevanssi uses to create the excerpts, you can use the %s and %s filter hooks to adjust the content.", "relevanssi"), "<code>relevanssi_pre_excerpt_content</code>", "<code>relevanssi_excerpt_content</code>") . "</li>" .
			"<li>" . sprintf(__("Some shortcode do not work well with Relevanssi excerpt-generation. Relevanssi disables some shortcodes automatically to prevent problems. This can be adjusted with the %s filter hook.", "relevanssi"), "<code>relevanssi_disable_shortcodes_excerpt</code>") . "</li>" .
			"<li>" . sprintf(__("If you want Relevanssi to build excerpts faster and don't mind that they may be less than perfect in quality, add a filter that returns true on hook %s.", "relevanssi"), '<code>relevanssi_optimize_excerpts</code>') .
			"<pre>add_filter('relevanssi_optimize_excerpts', '__return_true');</pre></li>" .
			"</ul>",
	));
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-highlights',
		'title'    => __( 'Highlights', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . __("Title highlights don't appear automatically, because that led to problems with highlights appearing in wrong places and messing up navigation menus, for example.", 'relevanssi') . "</li>" .
			"<li>" . sprintf(__("In order to see title highlights from Relevanssi, replace %s in the search results template with %s. It does the same thing, but supports Relevanssi title highlights.", 'relevanssi'), "<code>the_title()</code>", "<code>relevanssi_the_title()</code>") . "</li>" .
			"</ul>",
	));
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-punctuation',
		'title'    => __( 'Punctuation', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . __("Relevanssi removes punctuation. Some punctuation is removed, some replaced with spaces. Advanced indexing settings include some of the more common settings people want to change.", "relevanssi") . "</li>" .
			"<li>" . sprintf(__("For more fine-tuned changes, you can use %s filter hook to adjust what is replaced with what, and %s filter hook to completely override the default punctuation control.", 'relevanssi'), '<code>relevanssi_punctuation_filter</code>', '<code>relevanssi_remove_punctuation</code>') . "</li>" .
			"<li>" . sprintf(__("For more examples, see <a href='%s'>the related knowledge base posts</a>.", "relevanssi"), 'https://www.relevanssi.com/tag/relevanssi_remove_punct/') . "</li>" .
			"</ul>",
	));
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-helpful-shortcodes',
		'title'    => __( 'Helpful shortcodes', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . sprintf(__("If you have content that you don't want indexed, you can wrap that content in a %s shortcode.", 'relevanssi'), '<code>[noindex]</code>') . "</li>" .
			"<li>" . sprintf(__("If you need a search form on some page on your site, you can use the %s shortcode to print out a basic search form.", "relevanssi"), '<code>[searchform]</code>') . "</li>" .
			"<li>" . sprintf(__("If you need to add query variables to the search form, the shortcode takes parameters, which are then printed out as hidden input fields. To get a search form with a post type restriction, you can use %s. To restrict the search to categories 10, 14 and 17, you can use %s and so on.", "relevanssi"), '<code>[searchform post_types="page"]</code>', '<code>[searchform cats="10,14,17"]</code>') . "</li>" .
			"</ul>",
	));
	$screen->add_help_tab( array(
		'id'       => 'relevanssi-title-woocommerce',
		'title'    => __( 'WooCommerce', 'relevanssi' ),
		'content'  => 	"<ul>" . 
			"<li>" . __("If your SKUs include hyphens or other punctuation, do note that Relevanssi replaces most punctuation with spaces. That's going to cause issues with SKU searches.", 'relevanssi') . "</li>" .
			"<li>" . sprintf(__("For more details how to fix that issue, see <a href='%s'>WooCommerce tips in Relevanssi user manual</a>.", 'relevanssi'), "https://www.relevanssi.com/user-manual/woocommerce/") . "</li>" .
			"</ul>",
	));
	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'relevanssi' ) . '</strong></p>' .
		'<p><a href="https://www.relevanssi.com/knowledge-base/" target="_blank">' . __( 'Plugin knowledge base', 'relevanssi' ) . '</a></p>' .
		'<p><a href="https://wordpress.org/tags/relevanssi?forum_id=10" target="_blank">' . __( 'WordPress.org forum', 'relevanssi' ) . '</a></p>'
	);
}

add_action( 'admin_enqueue_scripts', 'relevanssi_add_admin_scripts' );
function relevanssi_add_admin_scripts($hook) {
	global $relevanssi_variables;

	$plugin_dir_url = plugin_dir_url($relevanssi_variables['file']);

	// Only enqueue on Relevanssi pages.
	$acceptable_hooks = array(
		'toplevel_page_relevanssi-premium/relevanssi', 'settings_page_relevanssi-premium/relevanssi',
		'toplevel_page_relevanssi/relevanssi', 'settings_page_relevanssi/relevanssi',
	);
	if (!in_array($hook, $acceptable_hooks)) return;

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'relevanssi_admin_js', $plugin_dir_url . 'lib/admin_scripts.js', array( 'wp-color-picker' ) );
	if (!RELEVANSSI_PREMIUM) wp_enqueue_script( 'relevanssi_admin_js_free', $plugin_dir_url . 'lib/admin_scripts_free.js', array( 'relevanssi_admin_js' ) );
	if (RELEVANSSI_PREMIUM) wp_enqueue_script( 'relevanssi_admin_js_premium', $plugin_dir_url . 'premium/admin_scripts_premium.js', array( 'relevanssi_admin_js' ) );
	wp_enqueue_style( 'relevanssi_admin_css', $plugin_dir_url . 'lib/admin_styles.css' );
	
	$translation = array(
		'confirm' => __('Click OK to copy Relevanssi options to all subsites', 'relevanssi'),
		'confirm_stopwords' => __('Are you sure you want to remove all stopwords?', 'relevanssi'),
		'truncating_index' => __("Wiping out the index...", "relevanssi"),
		'done' => __('Done.', 'relevanssi'),
		'indexing_users' => __('Indexing users...', 'relevanssi'),
		'indexing_taxonomies' => __('Indexing the following taxonomies:', 'relevanssi'),
		'counting_posts' => __('Counting posts...', 'relevanssi'),
		'counting_terms' => __('Counting taxonomy terms...', 'relevanssi'),
		'counting_users' => __('Counting users...', 'relevanssi'),
		'posts_found' => __('posts found.', 'relevanssi'),
		'terms_found' => __('taxonomy terms found.', 'relevanssi'),
		'users_found' => __('users found.', 'relevanssi'),
		'taxonomy_disabled' => __('Taxonomy term indexing is disabled.', "relevanssi"),
		'user_disabled' => __('User indexing is disabled.', "relevanssi"),
		'indexing_complete' => __('Indexing complete.', 'relevanssi'),
		'excluded_posts' => __('posts excluded.', 'relevanssi'),
		'options_changed' => __("Settings have changed, please save the options before indexing.", 'relevanssi'),
		'reload_state' => __("Reload the page to refresh the state of the index.", 'relevanssi'),
		'pdf_reset_confirm' => __("Are you sure you want to delete all PDF content from the index?", 'relevanssi'),
		'pdf_reset_done' => __("Relevanssi PDF data wiped clean. Removed entries: ", 'relevanssi'),
		'hour' => __("hour", "relevanssi"),
		'hours' => __("hours", "relevanssi"),
		'about' => __("about", "relevanssi"),
		'sixty_min' => __("about an hour", "relevanssi"),
		'ninety_min' => __("about an hour and a half", "relevanssi"),
		'minute' => __("minute", "relevanssi"),
		'minutes' => __("minutes", "relevanssi"),
		'underminute' => __("less than a minute", "relevanssi"),
		'notimeremaining' => __("we're done!", "relevanssi"),

	);

	wp_localize_script( 'relevanssi_admin_js', 'relevanssi', $translation );
}

/* Copy of sanitize_hex_color(), because that isn't always available
*/
function relevanssi_sanitize_hex_color($color) {
	if ( '' === $color ) {
		return '';
	}
	 
	// 3 or 6 hex digits, or the empty string.
	if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}
}