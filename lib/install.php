<?php
/**
 * /lib/install.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Installs Relevanssi on a new plugin if Relevanssi is network active.
 *
 * Hooks on to 'wpmu_new_blog' and 'wp_insert_site' action hooks and runs
 * '_relevanssi_install' on the new blog.
 *
 * @param int|object $blog Either the blog ID (if 'wpmu_new_blog') or new site
 * object (if 'wp_insert_site').
 */
function relevanssi_new_blog( $blog ) {
	if ( is_int( $blog ) ) {
		$blog_id = $blog;
	} else {
		$blog_id = $blog->id;
	}

	if ( is_plugin_active_for_network( 'relevanssi-premium/relevanssi.php' ) || is_plugin_active_for_network( 'relevanssi/relevanssi.php' ) ) {
		switch_to_blog( $blog_id );
		_relevanssi_install();
		restore_current_blog();
	}
}

/**
 * Runs _relevanssi_install() on one blog or for the whole network.
 *
 * If Relevanssi is network active, this installs Relevanssi on all blogs in the
 * network, running the _relevanssi_install() function.
 *
 * @param boolean $network_wide If true, install on all sites. Default false.
 */
function relevanssi_install( $network_wide = false ) {
	if ( $network_wide ) {
		$args     = array(
			'spam'     => 0,
			'deleted'  => 0,
			'archived' => 0,
			'fields'   => 'ids',
		);
		$blog_ids = get_sites( $args );

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			_relevanssi_install();
			restore_current_blog();
		}
	} else {
		_relevanssi_install();
	}
}

/**
 * Installs Relevanssi on the blog.
 *
 * Adds Relevanssi options and sets their default values and generates the
 * database tables.
 *
 * @global array $relevanssi_variables The global Relevanssi variables array.
 */
function _relevanssi_install() {
	global $relevanssi_variables;

	add_option( 'relevanssi_admin_search', 'off' );
	add_option( 'relevanssi_bg_col', '#ffaf75' );
	add_option( 'relevanssi_cat', '0' );
	add_option( 'relevanssi_class', 'relevanssi-query-term' );
	add_option( 'relevanssi_comment_boost', $relevanssi_variables['comment_boost_default'] );
	add_option( 'relevanssi_content_boost', $relevanssi_variables['content_boost_default'] );
	add_option( 'relevanssi_css', 'text-decoration: underline; text-color: #ff0000' );
	add_option( 'relevanssi_db_version', '0' );
	add_option( 'relevanssi_default_orderby', 'relevance' );
	add_option( 'relevanssi_disable_or_fallback', 'off' );
	add_option( 'relevanssi_exact_match_bonus', 'on' );
	add_option( 'relevanssi_excat', '0' );
	add_option( 'relevanssi_excerpt_allowable_tags', '' );
	add_option( 'relevanssi_excerpt_custom_fields', 'off' );
	add_option( 'relevanssi_excerpt_length', '30' );
	add_option( 'relevanssi_excerpt_type', 'words' );
	add_option( 'relevanssi_excerpts', 'on' );
	add_option( 'relevanssi_exclude_posts', '' );
	add_option( 'relevanssi_expand_highlights', 'off' );
	add_option( 'relevanssi_expand_shortcodes', 'on' );
	add_option( 'relevanssi_extag', '0' );
	add_option( 'relevanssi_fuzzy', 'always' );
	add_option( 'relevanssi_highlight', 'strong' );
	add_option( 'relevanssi_highlight_comments', 'off' );
	add_option( 'relevanssi_highlight_docs', 'off' );
	add_option( 'relevanssi_hilite_title', '' );
	add_option( 'relevanssi_implicit_operator', 'OR' );
	add_option( 'relevanssi_index_author', '' );
	add_option( 'relevanssi_index_comments', 'none' );
	add_option( 'relevanssi_index_excerpt', 'off' );
	add_option( 'relevanssi_index_fields', '' );
	add_option( 'relevanssi_index_image_files', 'on' );
	add_option( 'relevanssi_index_post_types', array( 'post', 'page' ) );
	add_option( 'relevanssi_index_taxonomies_list', array() );
	add_option( 'relevanssi_indexed', '' );
	add_option( 'relevanssi_log_queries', 'off' );
	add_option( 'relevanssi_log_queries_with_ip', 'off' );
	add_option( 'relevanssi_min_word_length', '3' );
	add_option( 'relevanssi_omit_from_logs', '' );
	add_option( 'relevanssi_polylang_all_languages', 'off' );
	add_option(
		'relevanssi_punctuation',
		array(
			'quotes'     => 'replace',
			'hyphens'    => 'replace',
			'ampersands' => 'replace',
		)
	);
	add_option( 'relevanssi_respect_exclude', 'on' );
	add_option( 'relevanssi_seo_noindex', 'on' );
	add_option( 'relevanssi_show_matches', '' );
	add_option( 'relevanssi_show_matches_text', '(Search hits: %body% in body, %title% in title, %categories% in categories, %tags% in tags, %taxonomies% in other taxonomies, %comments% in comments. Score: %score%)' );
	add_option( 'relevanssi_stopwords', array() );
	add_option( 'relevanssi_synonyms', array() );
	add_option( 'relevanssi_throttle', 'on' );
	add_option( 'relevanssi_throttle_limit', '500' );
	add_option( 'relevanssi_title_boost', $relevanssi_variables['title_boost_default'] );
	add_option( 'relevanssi_txt_col', '#ff0000' );
	add_option( 'relevanssi_wpml_only_current', 'on' );

	if ( function_exists( 'relevanssi_premium_install' ) ) {
		// Do some Relevanssi Premium additions.
		relevanssi_premium_install();
	}

	/**
	 * Runs after Relevanssi options are added in the installation process.
	 *
	 * This action hook can be used to adjust the options to set your own default
	 * settings, for example.
	 */
	do_action( 'relevanssi_update_options' );

	relevanssi_create_database_tables( $relevanssi_variables['database_version'] );
}
