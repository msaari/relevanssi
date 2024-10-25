<?php
/**
 * /lib/compatibility/translatepress.php
 *
 * Translatepress compatibility features.
 *
 * @package Relevanssi
 * @author  Janning Quint - Die Neudenker (dieneudenker.de)
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'relevanssi_create_tables', 'relevanssi_translatepress_create_tables' );
add_filter( 'relevanssi_after_index_doc_n', 'relevanssi_translatepress_index_translated_posts', 10 , 6 );
add_action( 'relevanssi_init', 'relevanssi_translatepress_modify_relevanssi_variables' );
add_filter( 'relevanssi_excerpt_content', 'relevanssi_translatepress_translate_string' );
add_filter( 'relevanssi_truncate_index', 'relevanssi_translatepress_truncate_index' );

add_filter( 'relevanssi_indexing_limit', function() { return 1; } ); // Reducing indexing limit to 1 as trp translation requires a lot of memory
add_filter( 'relevanssi_indexing_adjust', function() { return false; } ); // prevent index adjusting due to memory limit issues

add_action( 'trp_save_editor_translations_gettext_strings', 'relevanssi_translatepress_index_should_be_built' );
add_action( 'trp_save_editor_translations_regular_strings', 'relevanssi_translatepress_index_should_be_built' ); 
add_filter( 'trp_extra_sanitize_settings', 'relevanssi_translatepress_trp_settings_changed' );

add_filter( 'query', 'relevanssi_translatepress_check_wpdb_query_for_trp_changes' );

/**
 * Generates tablenames for indexing translatepress translated pages
 */
function relevanssi_translatepress_get_relevanssi_tablename_for_language($language_slug) {
	global $wpdb, $relevanssi_variables;
	$trp_settings = \TRP_Translate_Press::get_trp_instance()->get_component( 'settings' )->get_settings();

	if($language_slug === $trp_settings['default-language']) {
		return $wpdb->prefix . 'relevanssi';
	} else {
		return $wpdb->prefix . 'relevanssi_trp_' . $language_slug;
	}
}


/**
 * Mofifies the global relevanssi tablename variable on every frontend init to match the correct table dependend on the current trp language
 */
function relevanssi_translatepress_modify_relevanssi_variables() {
	global $relevanssi_variables, $TRP_LANGUAGE;
	$trp_settings = \TRP_Translate_Press::get_trp_instance()->get_component( 'settings' )->get_settings();
	if(!is_admin() && ($TRP_LANGUAGE !== $trp_settings['default-language'])) {
		$relevanssi_variables['relevanssi_table'] = relevanssi_translatepress_get_relevanssi_tablename_for_language($TRP_LANGUAGE);
	}
}


/**
 * Creates tables in the database for indexing translatepress translated pages.
 * A separate table is used for each language as the translated pages have the same post IDs as the non-translated pages
 * Hooked to relevanssi_create_tables
 * Also fired when translatepress settings change
 */
function relevanssi_translatepress_create_tables($charset_collate = null, $trp_settings = null) {
	global $wpdb, $relevanssi_variables;

	if(!$trp_settings) {
		$trp_settings = \TRP_Translate_Press::get_trp_instance()->get_component( 'settings' )->get_settings();
	}

	$default_language = $trp_settings['default-language'];
	$translation_languages = $trp_settings['translation-languages'];

	foreach ($translation_languages as $key => $language_slug) {
		if($language_slug === $default_language) continue;

		$translated_relevanssi_table_name = relevanssi_translatepress_get_relevanssi_tablename_for_language($language_slug);
		
		$sql = 'CREATE TABLE ' . $translated_relevanssi_table_name . " LIKE " . $relevanssi_variables['relevanssi_table'];

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
		\dbDelta( $sql );

	}

}


/**
 * Builts the index of translated pages
 * Hooked to filter relevanssi_after_index_doc_n
 * Uses the relevanssi indexing function by switching the temporarly switching the TRP language and modifying the global relevanssi tablename variable
 * The function runs on manual index building as well as after relevanssis save post hooks
 */
function relevanssi_translatepress_index_translated_posts( $n, $index_post, $remove_first, $custom_fields, $bypass_global_post, $debug ) {

	global 	$doing_relevanssi_translatepress_index_translated_posts, 
			$relevanssi_variables;

	if($doing_relevanssi_translatepress_index_translated_posts) return $n;

	$doing_relevanssi_translatepress_index_translated_posts = true;

	$trp_settings = \TRP_Translate_Press::get_trp_instance()->get_component( 'settings' )->get_settings();
	$default_language = $trp_settings['default-language'];
	$translation_languages = $trp_settings['translation-languages'];

	$relevanssi_variables_orig = $relevanssi_variables;

	add_filter( 'relevanssi_post_to_index', 'relevanssi_translatepress_translate_post' );

	foreach ($translation_languages as $key => $language_slug) {

		if($language_slug === $default_language) continue;

		$relevanssi_variables['relevanssi_table'] = relevanssi_translatepress_get_relevanssi_tablename_for_language($language_slug);
		trp_switch_language($language_slug);

		$n += relevanssi_index_doc($index_post, $remove_first, $custom_fields, $bypass_global_post, $debug);

		trp_restore_language();
		$relevanssi_variables = $relevanssi_variables_orig;
	}

	remove_filter( 'relevanssi_post_to_index', 'relevanssi_translatepress_translate_post' );

	$doing_relevanssi_translatepress_index_translated_posts = false;

	return $n;
}


/**
 * Filters the post content with the translated content
 * Used on the relevanssi_post_to_index filter hook during the built index process
 */
function relevanssi_translatepress_translate_post($post) {
	$post->post_content = trp_translate($post->post_content);
	// $post->post_title = trp_translate($post->post_title); //Timeout????
	return $post;
}


/**
 * String translation hooked to relevanssi_excerpt_content
 */
function relevanssi_translatepress_translate_string($string) {
	return trp_translate($string);
}


/**
 * Truncates all translation index tables
 * Hooked to relevanssi_truncate_index
 */
function relevanssi_translatepress_truncate_index($query_return) {
	global $wpdb;

	$trp_settings = \TRP_Translate_Press::get_trp_instance()->get_component( 'settings' )->get_settings();
	$default_language = $trp_settings['default-language'];
	$translation_languages = $trp_settings['translation-languages'];

	foreach ($translation_languages as $key => $language_slug) {
		if($language_slug === $default_language) continue;

		$relevanssi_table = relevanssi_translatepress_get_relevanssi_tablename_for_language($language_slug);

		$query_return = $query_return && $wpdb->query( "TRUNCATE TABLE $relevanssi_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
	
	return $query_return;
}


/**
 * If translatepress translations are modified we set the relevanssi_indexed option to empty to notify the user that the index must be rebuilt
 * This is the only solution so far as trp offers no hook which gives information about which site is affected by new translations
 * Hooked to trp manual translation changed hooks trp_save_editor_translations_gettext_strings and trp_save_editor_translations_regular_strings
 * Fired by relevanssi_translatepress_trp_settings_changed
 * Fired by relevanssi_translatepress_check_wpdb_query_for_trp_changes
 * ToDo: Add cronjob indexing to relevanssi when relevanssi_indexed is not 'done'
 */
function relevanssi_translatepress_index_should_be_built() {
	update_option( 'relevanssi_indexed', '' );
}


/**
 * Recreates tables and sets "index should be built" after translatepress settings are changed
 * Hooked to trp_extra_sanitize_settings
 */
function relevanssi_translatepress_trp_settings_changed($settings) {
	relevanssi_translatepress_create_tables(trp_settings: $settings); // We don't know which settings have changed, so we'll recreate all trp index tables
	relevanssi_translatepress_index_should_be_built();
	return $settings;
}


/**
 * Checks for wpdb INSERT queries on all tables starting with wp-prefix and trp_ to get all translatepress database modification
 * Fires relevanssi_translatepress_index_should_be_built on every query
 * This is the only solution so far to get a hint on trp machine translated translation updates as trp offers no hooks for that
 * Hooked to wordpress query filter
 */
function relevanssi_translatepress_check_wpdb_query_for_trp_changes($query) {
	global $wpdb;
	if(strpos($query,"INSERT INTO `". $wpdb->prefix . 'trp_') !== false) {
		relevanssi_translatepress_index_should_be_built();
	}
	return $query;
}