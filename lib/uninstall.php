<?php
/**
 * /lib/uninstall.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Drops the database tables.
 *
 * Drops the Relevanssi database tables
 *
 * @global $wpdb The WordPress database interface.
 */
function relevanssi_drop_database_tables() {
	global $wpdb;

	if ( defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM && ! defined( 'UNINSTALLING_RELEVANSSI_PREMIUM' ) ) {
		// Relevanssi Premium exists, do not drop the tables.
		return;
	}

	wp_clear_scheduled_hook( 'relevanssi_truncate_cache' );

	$relevanssi_table = $wpdb->prefix . 'relevanssi';
	$stopword_table   = $wpdb->prefix . 'relevanssi_stopwords';
	$log_table        = $wpdb->prefix . 'relevanssi_log';

	if ( $wpdb->get_var( "SHOW TABLES LIKE '$stopword_table'" ) === $stopword_table ) { // WPCS: unprepared SQL ok.
		$wpdb->query( "DROP TABLE $stopword_table" ); // WPCS: unprepared SQL ok.
	}

	if ( $wpdb->get_var( "SHOW TABLES LIKE '$relevanssi_table'" ) === $relevanssi_table ) { // WPCS: unprepared SQL ok.
		$wpdb->query( "DROP TABLE $relevanssi_table" ); // WPCS: unprepared SQL ok.
	}

	if ( $wpdb->get_var( "SHOW TABLES LIKE '$log_table'" ) === $log_table ) { // WPCS: unprepared SQL ok.
		$wpdb->query( "DROP TABLE $log_table" ); // WPCS: unprepared SQL ok.
	}
}

/**
 * Uninstalls Relevanssi.
 *
 * Deletes all options and removes database tables.
 *
 * @global object $wpdb The WordPress database interface.
 */
function relevanssi_uninstall_free() {
	delete_option( 'relevanssi_admin_search' );
	delete_option( 'relevanssi_bg_col' );
	delete_option( 'relevanssi_cat' );
	delete_option( 'relevanssi_comment_boost' );
	delete_option( 'relevanssi_css' );
	delete_option( 'relevanssi_class' );
	delete_option( 'relevanssi_content_boost' );
	delete_option( 'relevanssi_db_version' );
	delete_option( 'relevanssi_default_orderby' );
	delete_option( 'relevanssi_disable_or_fallback' );
	delete_option( 'relevanssi_disable_shortcodes' );
	delete_option( 'relevanssi_doc_count' );
	delete_option( 'relevanssi_exact_match_bonus' );
	delete_option( 'relevanssi_excat' );
	delete_option( 'relevanssi_extag' );
	delete_option( 'relevanssi_excerpt_length' );
	delete_option( 'relevanssi_excerpt_type' );
	delete_option( 'relevanssi_excerpt_allowable_tags' );
	delete_option( 'relevanssi_excerpt_custom_fields' );
	delete_option( 'relevanssi_excerpts' );
	delete_option( 'relevanssi_exclude_posts' );
	delete_option( 'relevanssi_expand_shortcodes' );
	delete_option( 'relevanssi_fuzzy' );
	delete_option( 'relevanssi_hide_branding' );
	delete_option( 'relevanssi_highlight_comments' );
	delete_option( 'relevanssi_highlight_docs' );
	delete_option( 'relevanssi_highlight' );
	delete_option( 'relevanssi_hilite_title' );
	delete_option( 'relevanssi_implicit_operator' );
	delete_option( 'relevanssi_index' );
	delete_option( 'relevanssi_index_author' );
	delete_option( 'relevanssi_index_comments' );
	delete_option( 'relevanssi_index_drafts' );
	delete_option( 'relevanssi_index_excerpt' );
	delete_option( 'relevanssi_index_fields' );
	delete_option( 'relevanssi_index_limit' );
	delete_option( 'relevanssi_index_post_types' );
	delete_option( 'relevanssi_index_taxonomies' );
	delete_option( 'relevanssi_index_taxonomies_list' );
	delete_option( 'relevanssi_index_terms' );
	delete_option( 'relevanssi_indexed' );
	delete_option( 'relevanssi_link_boost' );
	delete_option( 'relevanssi_log_queries' );
	delete_option( 'relevanssi_log_queries_with_ip' );
	delete_option( 'relevanssi_min_word_length' );
	delete_option( 'relevanssi_omit_from_logs' );
	delete_option( 'relevanssi_polylang_all_languages' );
	delete_option( 'relevanssi_post_type_weights' );
	delete_option( 'relevanssi_punctuation' );
	delete_option( 'relevanssi_respect_exclude' );
	delete_option( 'relevanssi_show_matches_text' );
	delete_option( 'relevanssi_show_matches' );
	delete_option( 'relevanssi_show_post_controls' );
	delete_option( 'relevanssi_synonyms' );
	delete_option( 'relevanssi_thousand_separator' );
	delete_option( 'relevanssi_throttle' );
	delete_option( 'relevanssi_throttle_limit' );
	delete_option( 'relevanssi_title_boost' );
	delete_option( 'relevanssi_txt_col' );
	delete_option( 'relevanssi_word_boundaries' );
	delete_option( 'relevanssi_wpml_only_current' );

	// Unused options, removed in case they are still left.
	delete_option( 'relevanssi_cache_seconds' );
	delete_option( 'relevanssi_custom_types' );
	delete_option( 'relevanssi_enable_cache' );
	delete_option( 'relevanssi_hidesponsor' );
	delete_option( 'relevanssi_index_attachments' );
	delete_option( 'relevanssi_index_type' );
	delete_option( 'relevanssi_show_matches_txt' );
	delete_option( 'relevanssi_tag_boost' );
	delete_option( 'relevanssi_include_cats' );
	delete_option( 'relevanssi_include_tags' );
	delete_option( 'relevanssi_custom_taxonomies' );
	delete_option( 'relevanssi_taxonomies_to_index' );
	delete_option( 'relevanssi_highlight_docs_external' );

	relevanssi_drop_database_tables();
}
