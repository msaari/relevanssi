<?php

function relevanssi_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	global $wpdb;

	if (is_plugin_active_for_network('relevanssi-premium/relevanssi.php') || is_plugin_active_for_network('relevanssi/relevanssi.php')) {
		switch_to_blog($blog_id);
		_relevanssi_install();
		restore_current_blog();
	}
}

function relevanssi_install($network_wide = false) {
	global $wpdb;

	if ($network_wide) {
		$blogids = $wpdb->get_col($wpdb->prepare("
			SELECT blog_id
			FROM $wpdb->blogs
			WHERE site_id = %d
			AND deleted = 0
			AND spam = 0
		", $wpdb->siteid));

		foreach ($blogids as $blog_id) {
			switch_to_blog($blog_id);
			_relevanssi_install();
			restore_current_blog();
		}

	} else {
		_relevanssi_install();
	}
}

function _relevanssi_install() {
	global $relevanssi_variables;

	add_option('relevanssi_content_boost', $relevanssi_variables['content_boost_default']);
	add_option('relevanssi_title_boost', $relevanssi_variables['title_boost_default']);
	add_option('relevanssi_comment_boost', $relevanssi_variables['comment_boost_default']);
	add_option('relevanssi_index_post_types', array('post', 'page'));
	add_option('relevanssi_admin_search', 'off');
	add_option('relevanssi_highlight', 'strong');
	add_option('relevanssi_txt_col', '#ff0000');
	add_option('relevanssi_bg_col', '#ffaf75');
	add_option('relevanssi_css', 'text-decoration: underline; text-color: #ff0000');
	add_option('relevanssi_class', 'relevanssi-query-term');
	add_option('relevanssi_excerpts', 'on');
	add_option('relevanssi_excerpt_length', '30');
	add_option('relevanssi_excerpt_type', 'words');
	add_option('relevanssi_excerpt_allowable_tags', '');
	add_option('relevanssi_excerpt_custom_fields', 'off');
	add_option('relevanssi_log_queries', 'off');
	add_option('relevanssi_log_queries_with_ip', 'off');
	add_option('relevanssi_cat', '0');
	add_option('relevanssi_excat', '0');
	add_option('relevanssi_extag', '0');
	add_option('relevanssi_index_fields', '');
	add_option('relevanssi_exclude_posts', ''); 		//added by OdditY
	add_option('relevanssi_hilite_title', ''); 			//added by OdditY
	add_option('relevanssi_highlight_docs', 'off');
	add_option('relevanssi_highlight_docs_external', 'off');
	add_option('relevanssi_highlight_comments', 'off');
	add_option('relevanssi_index_comments', 'none');	//added by OdditY
	add_option('relevanssi_show_matches', '');
	add_option('relevanssi_show_matches_text', '(Search hits: %body% in body, %title% in title, %categories% in categories, %tags% in tags, %taxonomies% in other taxonomies, %comments% in comments. Score: %score%)');
	add_option('relevanssi_fuzzy', 'sometimes');
	add_option('relevanssi_indexed', '');
	add_option('relevanssi_expand_shortcodes', 'on');
	add_option('relevanssi_index_author', '');
	add_option('relevanssi_implicit_operator', 'OR');
	add_option('relevanssi_omit_from_logs', '');
	add_option('relevanssi_synonyms', '');
	add_option('relevanssi_index_excerpt', 'off');
	add_option('relevanssi_index_limit', '500');
	add_option('relevanssi_disable_or_fallback', 'off');
	add_option('relevanssi_respect_exclude', 'on');
	add_option('relevanssi_min_word_length', '3');
	add_option('relevanssi_throttle', 'on');
	add_option('relevanssi_throttle_limit', '500');
	add_option('relevanssi_db_version', '0');
	add_option('relevanssi_wpml_only_current', 'on');
	add_option('relevanssi_polylang_all_languages', 'off');
	add_option('relevanssi_word_boundaries', 'on');
	add_option('relevanssi_default_orderby', 'relevance');
	add_option('relevanssi_punctuation', array('quotes' => 'replace', 'hyphens' => 'replace', 'ampersands' => 'replace'));

	if (function_exists('relevanssi_premium_install')) relevanssi_premium_install();

	do_action('relevanssi_update_options');

	relevanssi_create_database_tables($relevanssi_variables['database_version']);
}