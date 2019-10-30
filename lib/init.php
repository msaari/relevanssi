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
add_action( 'init', 'relevanssi_init' );
add_filter( 'query_vars', 'relevanssi_query_vars' );
add_filter( 'rest_api_init', 'relevanssi_rest_api_disable' );
add_action( 'switch_blog', 'relevanssi_switch_blog', 1, 2 );
add_action( 'admin_init', 'relevanssi_admin_init' );
add_action( 'admin_menu', 'relevanssi_menu' );

// Taking over the search.
add_filter( 'the_posts', 'relevanssi_query', 99, 2 );
add_filter( 'posts_request', 'relevanssi_prevent_default_request', 10, 2 );

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
add_action( 'add_attachment', 'relevanssi_publish', 12 );
add_action( 'edit_attachment', 'relevanssi_insert_edit' );

// When a post status changes, check child posts that inherit their status from parent.
add_action( 'transition_post_status', 'relevanssi_update_child_posts', 99, 3 );

// Relevanssi features.
add_filter( 'relevanssi_remove_punctuation', 'relevanssi_remove_punct' );
add_filter( 'relevanssi_post_ok', 'relevanssi_default_post_ok', 9, 2 );
add_filter( 'relevanssi_query_filter', 'relevanssi_limit_filter' );
add_action( 'relevanssi_trim_logs', 'relevanssi_trim_logs' );
add_action( 'relevanssi_custom_field_value', 'relevanssi_filter_custom_fields', 10, 2 );

// Plugin and theme compatibility.
add_filter( 'relevanssi_pre_excerpt_content', 'relevanssi_remove_page_builder_shortcodes', 9 );

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
 * @global object $wpdb                 The WP database interface.
 */
function relevanssi_init() {
	global $pagenow, $relevanssi_variables, $wpdb;

	$plugin_dir = dirname( plugin_basename( $relevanssi_variables['file'] ) );
	load_plugin_textdomain( 'relevanssi', false, $plugin_dir . '/languages' );
	$on_relevanssi_page = false;
	if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$page = sanitize_file_name( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( plugin_basename( $relevanssi_variables['file'] ) === $page ) {
			$on_relevanssi_page = true;
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

	if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_is_translated_post_type' ) ) {
		require_once 'compatibility/wpml.php';
	}

	if ( class_exists( 'Polylang', false ) ) {
		require_once 'compatibility/polylang.php';
	}

	if ( class_exists( 'WooCommerce', false ) ) {
		require_once 'compatibility/woocommerce.php';
	}

	if ( class_exists( 'acf', false ) ) {
		require_once 'compatibility/acf.php';
	}

	if ( class_exists( 'Obenland_Wp_Search_Suggest', false ) ) {
		require_once 'compatibility/wp-search-suggest.php';
	}

	if ( function_exists( 'do_blocks' ) ) {
		require_once 'compatibility/gutenberg.php';
	}

	if ( defined( 'WPFD_VERSION' ) ) {
		require_once 'compatibility/wp-file-download.php';
	}

	if ( defined( 'WPSEO_FILE' ) ) {
		require_once 'compatibility/yoast-seo.php';
	}

	if ( function_exists( 'seopress_get_toggle_titles_option' ) && '1' === seopress_get_toggle_titles_option() ) {
		require_once 'compatibility/seopress.php';
	}

	if ( function_exists( 'members_content_permissions_enabled' ) ) {
		require_once 'compatibility/members.php';
	}

	if ( defined( 'GROUPS_CORE_VERSION' ) ) {
		require_once 'compatibility/groups.php';
	}

	if ( class_exists( 'MeprUpdateCtrl', false ) && MeprUpdateCtrl::is_activated() ) {
		require_once 'compatibility/memberpress.php';
	}

	if ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
		require_once 'compatibility/simplemembership.php';
	}

	if ( function_exists( 'wp_jv_prg_user_can_see_a_post' ) ) {
		require_once 'compatibility/wpjvpostreadinggroups.php';
	}

	if ( function_exists( 'rcp_user_can_access' ) ) {
		require_once 'compatibility/restrictcontentpro.php';
	}

	// phpcs:disable WordPress.NamingConventions.ValidVariableName
	global $userAccessManager;
	if ( isset( $userAccessManager ) ) {
		require_once 'compatibility/useraccessmanager.php';
	}
	// phpcs:enable WordPress.NamingConventions.ValidVariableName

	if ( function_exists( 'pmpro_has_membership_access' ) ) {
		require_once 'compatibility/paidmembershippro.php';
	}

	// Always required, the functions check if TablePress is active.
	require_once 'compatibility/tablepress.php';

	if ( defined( 'NINJA_TABLES_VERSION' ) ) {
		require_once 'compatibility/ninjatables.php';
	}
}

/**
 * Iniatiates Relevanssi for admin.
 *
 * @global array $relevanssi_variables Global Relevanssi variables array.
 */
function relevanssi_admin_init() {
	global $relevanssi_variables;

	require_once $relevanssi_variables['plugin_dir'] . 'lib/admin-ajax.php';

	add_action( 'admin_enqueue_scripts', 'relevanssi_add_admin_scripts' );
	add_filter( 'plugin_action_links_' . $relevanssi_variables['plugin_basename'], 'relevanssi_action_links' );
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

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

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
	$current_db_version        = get_option( 'relevanssi_db_version' );

	if ( $current_db_version !== $relevanssi_db_version ) {
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
		customfield_detail longtext NOT NULL,
		mysqlcolumn_detail longtext NOT NULL,
		type varchar(210) NOT NULL DEFAULT 'post',
		item bigint(20) NOT NULL DEFAULT '0',
	    UNIQUE KEY doctermitem (doc, term, item)) $charset_collate";

		dbDelta( $sql );

		$sql     = "SHOW INDEX FROM $relevanssi_table";
		$indices = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		$terms_exists                       = false;
		$relevanssi_term_reverse_idx_exists = false;
		$docs_exists                        = false;
		$typeitem_exists                    = false;
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
		}

		if ( ! $terms_exists ) {
			$sql = "CREATE INDEX terms ON $relevanssi_table (term(20))";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
		}

		if ( ! $relevanssi_term_reverse_idx_exists ) {
			$sql = "CREATE INDEX relevanssi_term_reverse_idx ON $relevanssi_table (term_reverse(10))";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
		}

		if ( ! $docs_exists ) {
			$sql = "CREATE INDEX docs ON $relevanssi_table (doc)";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
		}

		if ( ! $typeitem_exists ) {
			$sql = "CREATE INDEX typeitem ON $relevanssi_table (type(190), item)";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery
		}

		$sql = 'CREATE TABLE ' . $relevanssi_stopword_table . " (stopword varchar(50) $charset_collate_bin_column NOT NULL,
	    UNIQUE KEY stopword (stopword)) $charset_collate;";

		dbDelta( $sql );

		$sql = 'CREATE TABLE ' . $relevanssi_log_table . " (id bigint(9) NOT NULL AUTO_INCREMENT,
		query varchar(200) NOT NULL,
		hits mediumint(9) NOT NULL DEFAULT '0',
		time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		user_id bigint(20) NOT NULL DEFAULT '0',
		ip varchar(40) NOT NULL DEFAULT '',
	    UNIQUE KEY id (id)) $charset_collate;";

		dbDelta( $sql );

		$sql     = "SHOW INDEX FROM $relevanssi_log_table";
		$indices = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching

		$query_exists = false;
		foreach ( $indices as $index ) {
			if ( 'query' === $index->Key_name ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
				$query_exists = true;
			}
		}

		if ( ! $query_exists ) {
			$sql = "CREATE INDEX query ON $relevanssi_log_table (query(190))";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		update_option( 'relevanssi_db_version', $relevanssi_db_version );
	}

	if ( $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_stopword_table WHERE 1" ) < 1 ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
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
