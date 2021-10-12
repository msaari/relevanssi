<?php
/**
 * /lib/init.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

// Setup.
add_action( 'init', 'relevanssi_init', 1 );
add_filter( 'query_vars', 'relevanssi_query_vars' );
add_filter( 'rest_api_init', 'relevanssi_rest_api_disable' );
add_action( 'switch_blog', 'relevanssi_switch_blog', 1, 2 );
add_action( 'admin_init', 'relevanssi_admin_init' );
add_action( 'admin_menu', 'relevanssi_menu' );

// Taking over the search.
add_filter( 'posts_pre_query', 'relevanssi_query', 99, 2 );
add_filter( 'posts_request', 'relevanssi_prevent_default_request', 10, 2 );
add_filter( 'relevanssi_search_ok', 'relevanssi_block_on_admin_searches', 10, 2 );
add_filter( 'relevanssi_admin_search_ok', 'relevanssi_block_on_admin_searches', 10, 2 );
add_filter( 'relevanssi_prevent_default_request', 'relevanssi_block_on_admin_searches', 10, 2 );
add_filter( 'relevanssi_search_ok', 'relevanssi_control_media_queries', 11, 2 );

// Post indexing.
add_action( 'wp_insert_post', 'relevanssi_insert_edit', 99, 1 );
add_action( 'delete_post', 'relevanssi_remove_doc' );

// Comment indexing.
add_action( 'comment_post', 'relevanssi_index_comment' );
add_action( 'edit_comment', 'relevanssi_index_comment' );
add_action( 'trashed_comment', 'relevanssi_index_comment' );
add_action( 'deleted_comment', 'relevanssi_index_comment' );

// Attachment indexing.
add_action( 'delete_attachment', 'relevanssi_remove_doc' );
add_action( 'add_attachment', 'relevanssi_insert_edit', 12 );
add_action( 'edit_attachment', 'relevanssi_insert_edit' );

// When a post status changes, check child posts that inherit their status from parent.
add_action( 'transition_post_status', 'relevanssi_update_child_posts', 99, 3 );

// Relevanssi features.
add_filter( 'relevanssi_remove_punctuation', 'relevanssi_remove_punct' );
add_filter( 'relevanssi_post_ok', 'relevanssi_default_post_ok', 9, 2 );
add_filter( 'relevanssi_query_filter', 'relevanssi_limit_filter' );
add_action( 'relevanssi_trim_logs', 'relevanssi_trim_logs' );
add_action( 'relevanssi_update_counts', 'relevanssi_update_counts' );
add_action( 'relevanssi_custom_field_value', 'relevanssi_filter_custom_fields', 10, 2 );

// Excerpts and highlights.
add_action( 'relevanssi_pre_the_content', 'relevanssi_kill_autoembed' );
add_action( 'relevanssi_pre_the_content', 'relevanssi_excerpt_pre_the_content' );
add_action( 'relevanssi_post_the_content', 'relevanssi_excerpt_post_the_content' );

// Page builder shortcodes.
add_filter( 'relevanssi_pre_excerpt_content', 'relevanssi_remove_page_builder_shortcodes', 9 );
add_filter( 'relevanssi_post_content', 'relevanssi_remove_page_builder_shortcodes', 9 );

// Permalink handling.
add_filter( 'the_permalink', 'relevanssi_permalink', 10, 2 );
add_filter( 'post_link', 'relevanssi_permalink', 10, 2 );
add_filter( 'page_link', 'relevanssi_permalink', 10, 2 );
add_filter( 'relevanssi_permalink', 'relevanssi_permalink' );

// Log exports.
add_action( 'plugins_loaded', 'relevanssi_export_log_check' );

global $relevanssi_variables;
register_activation_hook( $relevanssi_variables['file'], 'relevanssi_install' );

/**
 * Initiates Relevanssi.
 *
 * @global string $pagenow              Current admin page.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_init() {
	global $pagenow, $relevanssi_variables;

	$plugin_dir = dirname( plugin_basename( $relevanssi_variables['file'] ) );
	load_plugin_textdomain( 'relevanssi', false, $plugin_dir . '/languages' );
	$on_relevanssi_page = false;
	if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$page = sanitize_file_name( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$base = sanitize_file_name( wp_unslash( plugin_basename( $relevanssi_variables['file'] ) ) );
		if ( $base === $page ) {
			$on_relevanssi_page = true;
		}
	}

	$restriction_notice = relevanssi_check_indexing_restriction();
	if ( $restriction_notice ) {
		if ( 'options-general.php' === $pagenow && $on_relevanssi_page ) {
			if ( 'indexing' === $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				add_action(
					'admin_notices',
					function() use ( $restriction_notice ) {
						echo $restriction_notice; // phpcs:ignore WordPress.Security.EscapeOutput
					}
				);
			}
		}
	}

	if ( 'done' !== get_option( 'relevanssi_indexed' ) ) {
		if ( 'options-general.php' === $pagenow && $on_relevanssi_page ) {
			add_action(
				'admin_notices',
				function() {
					printf(
						"<div id='relevanssi-warning' class='update-nag'><p><strong>%s</strong></p></div>",
						esc_html__( 'You do not have an index! Remember to build the index (click the "Build the index" button), otherwise searching won\'t work.', 'relevanssi' )
					);
				}
			);
		}
	}

	if ( ! function_exists( 'mb_internal_encoding' ) ) {
		/**
		 * Prints out the "Multibyte string functions are not available" warning.
		 */
		function relevanssi_mb_warning() {
			printf( "<div id='relevanssi-warning' class='error'><p><strong>%s</strong></p></div>", esc_html__( 'Multibyte string functions are not available. Relevanssi may not work well without them. Please install (or ask your host to install) the mbstring extension.', 'relevanssi' ) );
		}
		if ( 'options-general.php' === $pagenow && $on_relevanssi_page ) {
			add_action( 'admin_notices', 'relevanssi_mb_warning' );
		}
	}

	if ( 'off' !== get_option( 'relevanssi_highlight_docs', 'off' ) ) {
		add_filter( 'the_content', 'relevanssi_highlight_in_docs', 11 );
	}
	if ( 'off' !== get_option( 'relevanssi_highlight_comments', 'off' ) ) {
		add_filter( 'comment_text', 'relevanssi_highlight_in_docs', 11 );
	}

	if ( get_option( 'relevanssi_trim_logs' ) > 0 ) {
		if ( ! wp_next_scheduled( 'relevanssi_trim_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'relevanssi_trim_logs' );
		}
	} else {
		if ( wp_next_scheduled( 'relevanssi_trim_logs' ) ) {
			wp_clear_scheduled_hook( 'relevanssi_trim_logs' );
		}
	}

	if ( ! wp_next_scheduled( 'relevanssi_update_counts' ) ) {
		wp_schedule_event( time(), 'weekly', 'relevanssi_update_counts' );
	}

	relevanssi_load_compatibility_code();

	if ( ! is_array( get_option( 'relevanssi_stopwords' ) ) ) {
		// Version 2.12 / 4.10 changes stopwords option from a string to an
		// array to support multilingual stopwords. This function converts old
		// style to new style. Remove eventually.
		relevanssi_update_stopwords_setting();
	}

	if ( ! is_array( get_option( 'relevanssi_synonyms' ) ) ) {
		// Version 2.12 / 4.10 changes synonyms option from a string to an
		// array to support multilingual synonyms. This function converts old
		// style to new style. Remove eventually.
		relevanssi_update_synonyms_setting();
	}
}

/**
 * Iniatiates Relevanssi for admin.
 *
 * @global array $relevanssi_variables Global Relevanssi variables array.
 */
function relevanssi_admin_init() {
	global $relevanssi_variables;

	add_action( 'admin_enqueue_scripts', 'relevanssi_add_admin_scripts' );
	add_filter( 'plugin_action_links_' . $relevanssi_variables['plugin_basename'], 'relevanssi_action_links' );

	relevanssi_create_database_tables( $relevanssi_variables['database_version'] );
}

/**
 * Adds the Relevanssi menu items.
 *
 * @global array $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_menu() {
	global $relevanssi_variables;
	$name = 'Relevanssi';
	if ( RELEVANSSI_PREMIUM ) {
		$name = 'Relevanssi Premium';
	}
	$plugin_page = add_options_page(
		$name,
		$name,
		/**
		 * Filters the capability required to access Relevanssi options.
		 *
		 * @param string The capability required. Default 'manage_options'.
		 */
		apply_filters( 'relevanssi_options_capability', 'manage_options' ),
		$relevanssi_variables['file'],
		'relevanssi_options'
	);
	add_dashboard_page(
		__( 'User searches', 'relevanssi' ),
		__( 'User searches', 'relevanssi' ),
		/**
		 * Filters the capability required to access Relevanssi user searches page.
		 *
		 * @param string The capability required. Default 'edit_pages'.
		 */
		apply_filters( 'relevanssi_user_searches_capability', 'edit_pages' ),
		$relevanssi_variables['file'],
		'relevanssi_search_stats'
	);
	add_dashboard_page(
		__( 'Admin search', 'relevanssi' ),
		__( 'Admin search', 'relevanssi' ),
		/**
		 * Filters the capability required to access Relevanssi admin search page.
		 *
		 * @param string The capability required. Default 'edit_posts'.
		 */
		apply_filters( 'relevanssi_admin_search_capability', 'edit_posts' ),
		'relevanssi_admin_search',
		'relevanssi_admin_search_page'
	);
	require_once 'contextual-help.php';
	add_action( 'load-' . $plugin_page, 'relevanssi_admin_help' );
	if ( function_exists( 'relevanssi_premium_plugin_page_actions' ) ) {
		// Loads contextual help and JS for Premium version.
		relevanssi_premium_plugin_page_actions( $plugin_page );
	}
}

/**
 * Introduces the Relevanssi query variables.
 *
 * Adds the Relevanssi query variables (cats, tags, post_types, by_date, and
 * highlight) to the WordPress whitelist of accepted query variables.
 *
 * @param array $qv The query variable list.
 *
 * @return array The query variables.
 */
function relevanssi_query_vars( $qv ) {
	$qv[] = 'cats';
	$qv[] = 'tags';
	$qv[] = 'post_types';
	$qv[] = 'by_date';
	$qv[] = 'highlight';
	$qv[] = 'posts_per_page';
	$qv[] = 'post_parent';
	$qv[] = 'post_status';

	return $qv;
}

/**
 * Creates the Relevanssi database tables.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param int $relevanssi_db_version The Relevanssi database version number.
 */
function relevanssi_create_database_tables( $relevanssi_db_version ) {
	global $wpdb;

	$current_db_version = intval( get_option( 'relevanssi_db_version' ) );

	if ( $current_db_version === $relevanssi_db_version ) {
		return;
	}

	$charset_collate_bin_column = '';
	$charset_collate            = '';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate_bin_column = "CHARACTER SET $wpdb->charset";
		$charset_collate            = "DEFAULT $charset_collate_bin_column";
	}
	if ( strpos( $wpdb->collate, '_' ) > 0 ) {
		$charset_collate_bin_column .= ' COLLATE ' . substr( $wpdb->collate, 0, strpos( $wpdb->collate, '_' ) ) . '_bin';
		$charset_collate            .= " COLLATE $wpdb->collate";
	} else {
		if ( '' === $wpdb->collate && 'utf8' === $wpdb->charset ) {
			$charset_collate_bin_column .= ' COLLATE utf8_bin';
		}
	}

	$relevanssi_table          = $wpdb->prefix . 'relevanssi';
	$relevanssi_stopword_table = $wpdb->prefix . 'relevanssi_stopwords';
	$relevanssi_log_table      = $wpdb->prefix . 'relevanssi_log';

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = 'CREATE TABLE ' . $relevanssi_table . " (doc bigint(20) NOT NULL DEFAULT '0',
	term varchar(50) NOT NULL DEFAULT '0',
	term_reverse varchar(50) NOT NULL DEFAULT '0',
	content mediumint(9) NOT NULL DEFAULT '0',
	title mediumint(9) NOT NULL DEFAULT '0',
	comment mediumint(9) NOT NULL DEFAULT '0',
	tag mediumint(9) NOT NULL DEFAULT '0',
	link mediumint(9) NOT NULL DEFAULT '0',
	author mediumint(9) NOT NULL DEFAULT '0',
	category mediumint(9) NOT NULL DEFAULT '0',
	excerpt mediumint(9) NOT NULL DEFAULT '0',
	taxonomy mediumint(9) NOT NULL DEFAULT '0',
	customfield mediumint(9) NOT NULL DEFAULT '0',
	mysqlcolumn mediumint(9) NOT NULL DEFAULT '0',
	taxonomy_detail longtext NOT NULL,
	customfield_detail longtext NOT NULL DEFAULT '',
	mysqlcolumn_detail longtext NOT NULL DEFAULT '',
	type varchar(210) NOT NULL DEFAULT 'post',
	item bigint(20) NOT NULL DEFAULT '0',
	PRIMARY KEY doctermitem (doc, term, item)) $charset_collate";

	dbDelta( $sql );

	$sql     = "SHOW INDEX FROM $relevanssi_table";
	$indices = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

	$terms_exists                       = false;
	$relevanssi_term_reverse_idx_exists = false;
	$docs_exists                        = false;
	$typeitem_exists                    = false;
	$doctermitem_exists                 = false;
	foreach ( $indices as $index ) {
		if ( 'terms' === $index->Key_name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$terms_exists = true;
		}
		if ( 'relevanssi_term_reverse_idx' === $index->Key_name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$relevanssi_term_reverse_idx_exists = true;
		}
		if ( 'docs' === $index->Key_name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$docs_exists = true;
		}
		if ( 'typeitem' === $index->Key_name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$typeitem_exists = true;
		}
		if ( 'doctermitem' === $index->Key_name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$doctermitem_exists = true;
		}
	}

	if ( ! $terms_exists ) {
		$sql = "CREATE INDEX terms ON $relevanssi_table (term(20))";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
	}

	if ( ! $relevanssi_term_reverse_idx_exists ) {
		$sql = "CREATE INDEX relevanssi_term_reverse_idx ON $relevanssi_table (term_reverse(10))";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
	}

	if ( ! $typeitem_exists ) {
		$sql = "CREATE INDEX typeitem ON $relevanssi_table (type(190), item)";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
	}

	if ( $doctermitem_exists ) {
		$sql = "DROP INDEX doctermitem ON $relevanssi_table";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
	}

	if ( $docs_exists ) { // This index was removed in 4.9.2 / 2.11.2.
		$sql = "DROP INDEX docs ON $relevanssi_table";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
	}

	$sql = 'CREATE TABLE ' . $relevanssi_stopword_table . " (stopword varchar(50) $charset_collate_bin_column NOT NULL,
	PRIMARY KEY stopword (stopword)) $charset_collate;";

	dbDelta( $sql );

	$sql = 'CREATE TABLE ' . $relevanssi_log_table . " (id bigint(9) NOT NULL AUTO_INCREMENT,
	query varchar(200) NOT NULL,
	hits mediumint(9) NOT NULL DEFAULT '0',
	time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	user_id bigint(20) NOT NULL DEFAULT '0',
	ip varchar(40) NOT NULL DEFAULT '',
	PRIMARY KEY id (id)) $charset_collate;";

	dbDelta( $sql );

	$sql     = "SHOW INDEX FROM $relevanssi_log_table";
	$indices = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching

	$query_exists = false;
	$id_exists    = false;
	foreach ( $indices as $index ) {
		if ( 'query' === $index->Key_name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$query_exists = true;
		}
		if ( 'id' === $index->Key_name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$id_exists = true;
		}
	}

	if ( ! $query_exists ) {
		$sql = "CREATE INDEX query ON $relevanssi_log_table (query(190))";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	if ( $id_exists ) {
		$sql = "DROP INDEX id ON $relevanssi_log_table";
		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Allows adding database tables.
	 *
	 * An action hook that runs in the create tables process if the database
	 * version in the options doesn't match the database version in the
	 * code.
	 *
	 * @param string $charset_collate The collation.
	 */
	do_action( 'relevanssi_create_tables', $charset_collate );

	update_option( 'relevanssi_db_version', $relevanssi_db_version );

	$stopwords = relevanssi_fetch_stopwords();
	if ( empty( $stopwords ) ) {
		relevanssi_populate_stopwords();
	}
}

/**
 * Prints out the action links on the Plugins page.
 *
 * Hooked on to the 'plugin_action_links_' filter hook.
 *
 * @param array $links Action links for Relevanssi.
 *
 * @return array Updated action links.
 */
function relevanssi_action_links( $links ) {
	$root = 'relevanssi';
	if ( RELEVANSSI_PREMIUM ) {
		$root = 'relevanssi-premium';
	}
	$relevanssi_links = array(
		'<a href="' . admin_url( 'options-general.php?page=' . $root . '/relevanssi.php' ) . '">' . __( 'Settings', 'relevanssi' ) . '</a>',
	);
	if ( ! RELEVANSSI_PREMIUM ) {
		$relevanssi_links[] = '<a href="https://www.relevanssi.com/buy-premium/">' . __( 'Go Premium!', 'relevanssi' ) . '</a>';
	}
	return array_merge( $relevanssi_links, $links );
}

/**
 * Disables Relevanssi in REST API searches.
 *
 * Relevanssi doesn't work in the REST API context, so disable and allow the
 * default search to work.
 */
function relevanssi_rest_api_disable() {
	remove_filter( 'posts_request', 'relevanssi_prevent_default_request' );
	remove_filter( 'the_posts', 'relevanssi_query', 99 );
}

/**
 * Checks if a log export is requested.
 *
 * If the 'relevanssi_export' query variable is set, a log export has been requested
 * and one will be provided by relevanssi_export_log().
 *
 * @see relevanssi_export_log
 */
function relevanssi_export_log_check() {
	if ( isset( $_REQUEST['relevanssi_export'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification, just checking the parameter exists.
		relevanssi_export_log();
	}
}

/**
 * Loads in the Relevanssi plugin compatibility code.
 */
function relevanssi_load_compatibility_code() {
	class_exists( 'acf', false ) && require_once 'compatibility/acf.php';
	class_exists( 'DGWT_WC_Ajax_Search', false ) && require_once 'compatibility/fibosearch.php';
	class_exists( 'MeprUpdateCtrl', false ) && MeprUpdateCtrl::is_activated() && require_once 'compatibility/memberpress.php';
	class_exists( 'Obenland_Wp_Search_Suggest', false ) && require_once 'compatibility/wp-search-suggest.php';
	class_exists( 'Polylang', false ) && require_once 'compatibility/polylang.php';
	class_exists( 'RankMath', false ) && require_once 'compatibility/rankmath.php';
	class_exists( 'WooCommerce', false ) && require_once 'compatibility/woocommerce.php';
	defined( 'AIOSEO_DIR' ) && require_once 'compatibility/aioseo.php';
	defined( 'AVADA_VERSION' ) && require_once 'compatibility/avada.php';
	defined( 'BRICKS_VERSION' ) && require_once 'compatibility/bricks.php';
	defined( 'CT_VERSION' ) && require_once 'compatibility/oxygen.php';
	defined( 'ELEMENTOR_VERSION' ) && require_once 'compatibility/elementor.php';
	defined( 'GROUPS_CORE_VERSION' ) && require_once 'compatibility/groups.php';
	defined( 'NINJA_TABLES_VERSION' ) && require_once 'compatibility/ninjatables.php';
	defined( 'PRLI_PLUGIN_NAME' ) && require_once 'compatibility/pretty-links.php';
	defined( 'WPM_PRODUCT_GTIN_WC_VERSION' ) && require_once 'compatibility/product-gtin-ean-upc-isbn-for-woocommerce.php';
	defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) && require_once 'compatibility/simplemembership.php';
	defined( 'THE_SEO_FRAMEWORK_VERSION' ) && require_once 'compatibility/seoframework.php';
	defined( 'WPFD_VERSION' ) && require_once 'compatibility/wp-file-download.php';
	defined( 'WPMEM_VERSION' ) && require_once 'compatibility/wp-members.php';
	defined( 'WPSEO_FILE' ) && require_once 'compatibility/yoast-seo.php';
	function_exists( 'do_blocks' ) && require_once 'compatibility/gutenberg.php';
	function_exists( 'icl_object_id' ) && ! function_exists( 'pll_is_translated_post_type' ) && require_once 'compatibility/wpml.php';
	function_exists( 'members_content_permissions_enabled' ) && require_once 'compatibility/members.php';
	function_exists( 'pmpro_has_membership_access' ) && require_once 'compatibility/paidmembershippro.php';
	function_exists( 'rcp_user_can_access' ) && require_once 'compatibility/restrictcontentpro.php';
	function_exists( 'seopress_get_toggle_titles_option' ) && '1' === seopress_get_toggle_titles_option() && require_once 'compatibility/seopress.php';
	function_exists( 'wp_jv_prg_user_can_see_a_post' ) && require_once 'compatibility/wpjvpostreadinggroups.php';

	// phpcs:disable WordPress.NamingConventions.ValidVariableName
	global $userAccessManager;
	isset( $userAccessManager ) && require_once 'compatibility/useraccessmanager.php';
	// phpcs:enable WordPress.NamingConventions.ValidVariableName

	// Always required, the functions check if TablePress is active.
	require_once 'compatibility/tablepress.php';
}
