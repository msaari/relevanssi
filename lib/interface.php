<?php
/**
 * /lib/interface.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Controls the Relevanssi settings page.
 *
 * @global array $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_options() {
	global $relevanssi_variables;
	$options_txt = __( 'Relevanssi Search Options', 'relevanssi' );
	if ( RELEVANSSI_PREMIUM ) {
		$options_txt = __( 'Relevanssi Premium Search Options', 'relevanssi' );
	}

	printf( "<div class='wrap'><h2>%s</h2>", esc_html( $options_txt ) );
	if ( ! empty( $_REQUEST ) ) {
		if ( isset( $_REQUEST['submit'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			update_relevanssi_options();
		}

		if ( isset( $_REQUEST['import_options'] ) ) {
			if ( function_exists( 'relevanssi_import_options' ) ) {
				check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
				$options = $_REQUEST['relevanssi_settings'];
				relevanssi_import_options( $options );
			}
		}

		if ( isset( $_REQUEST['search'] ) ) {
			relevanssi_search( $_REQUEST['q'] );
		}

		if ( isset( $_REQUEST['dowhat'] ) ) {
			if ( 'add_stopword' === $_REQUEST['dowhat'] ) {
				if ( isset( $_REQUEST['term'] ) ) {
					check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
					relevanssi_add_stopword( $_REQUEST['term'] );
				}
				if ( isset( $_REQUEST['body_term'] ) ) {
					check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
					relevanssi_add_body_stopword( $_REQUEST['body_term'] );
				}
			}
		}

		if ( isset( $_REQUEST['addstopword'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_add_stopword( $_REQUEST['addstopword'] );
		}

		if ( isset( $_REQUEST['removestopword'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_remove_stopword( $_REQUEST['removestopword'] );
		}

		if ( isset( $_REQUEST['removeallstopwords'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_remove_all_stopwords();
		}

		if ( isset( $_REQUEST['repopulatestopwords'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			$verbose = true;
			relevanssi_populate_stopwords( $verbose );
		}

		if ( isset( $_REQUEST['addbodystopword'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_add_body_stopword( $_REQUEST['addbodystopword'] );
		}

		if ( isset( $_REQUEST['removebodystopword'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_remove_body_stopword( $_REQUEST['removebodystopword'] );
		}

		if ( isset( $_REQUEST['removeallbodystopwords'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_remove_all_body_stopwords();
		}

		if ( isset( $_REQUEST['update_counts'] ) ) {
			check_admin_referer( 'update_counts' );
			relevanssi_update_counts();
		}
	}

	relevanssi_options_form();

	echo "<div style='clear:both'></div></div>";
}

/**
 * Updates Relevanssi options.
 *
 * Checks the option values and updates the options. It's safe to use $_REQUEST here,
 * check_admin_referer() is done immediately before this function is called.
 */
function update_relevanssi_options() {
	// phpcs:disable WordPress.Security.NonceVerification
	if ( 'indexing' === $_REQUEST['tab'] ) {
		relevanssi_turn_off_options(
			$_REQUEST,
			array(
				'relevanssi_expand_shortcodes',
				'relevanssi_index_author',
				'relevanssi_index_excerpt',
				'relevanssi_index_image_files',
			)
		);
		relevanssi_update_intval( $_REQUEST, 'relevanssi_min_word_length', true, 3 );
	}

	if ( 'searching' === $_REQUEST['tab'] ) {
		relevanssi_turn_off_options(
			$_REQUEST,
			array(
				'relevanssi_admin_search',
				'relevanssi_disable_or_fallback',
				'relevanssi_exact_match_bonus',
				'relevanssi_polylang_all_languages',
				'relevanssi_respect_exclude',
				'relevanssi_throttle',
				'relevanssi_wpml_only_current',
			)
		);
		relevanssi_update_floatval( $_REQUEST, 'relevanssi_content_boost', true, 1 );
		relevanssi_update_floatval( $_REQUEST, 'relevanssi_title_boost', true, 1 );
		relevanssi_update_floatval( $_REQUEST, 'relevanssi_comment_boost', true, 1 );
	}

	if ( 'logging' === $_REQUEST['tab'] ) {
		relevanssi_turn_off_options(
			$_REQUEST,
			array(
				'relevanssi_log_queries',
				'relevanssi_log_queries_with_ip',
			)
		);
	}

	if ( 'excerpts' === $_REQUEST['tab'] ) {
		relevanssi_turn_off_options(
			$_REQUEST,
			array(
				'relevanssi_excerpt_custom_fields',
				'relevanssi_excerpts',
				'relevanssi_expand_highlights',
				'relevanssi_highlight_comments',
				'relevanssi_highlight_docs',
				'relevanssi_hilite_title',
				'relevanssi_show_matches',
			)
		);
		if ( isset( $_REQUEST['relevanssi_show_matches_text'] ) ) {
			$value = $_REQUEST['relevanssi_show_matches_text'];
			$value = str_replace( '"', "'", $value );
			update_option( 'relevanssi_show_matches_text', $value );
		}
	}

	if ( isset( $_REQUEST['relevanssi_synonyms'] ) ) {
		$linefeeds = array( "\r\n", "\n", "\r" );
		$value     = str_replace( $linefeeds, ';', $_REQUEST['relevanssi_synonyms'] );
		$value     = stripslashes( $value );
		update_option( 'relevanssi_synonyms', $value );
	}

	$relevanssi_punct = array();
	if ( isset( $_REQUEST['relevanssi_punct_quotes'] ) ) {
		$relevanssi_punct['quotes'] = $_REQUEST['relevanssi_punct_quotes'];
	}
	if ( isset( $_REQUEST['relevanssi_punct_hyphens'] ) ) {
		$relevanssi_punct['hyphens'] = $_REQUEST['relevanssi_punct_hyphens'];
	}
	if ( isset( $_REQUEST['relevanssi_punct_ampersands'] ) ) {
		$relevanssi_punct['ampersands'] = $_REQUEST['relevanssi_punct_ampersands'];
	}
	if ( isset( $_REQUEST['relevanssi_punct_decimals'] ) ) {
		$relevanssi_punct['decimals'] = $_REQUEST['relevanssi_punct_decimals'];
	}
	if ( ! empty( $relevanssi_punct ) ) {
		update_option( 'relevanssi_punctuation', $relevanssi_punct );
	}

	$post_type_weights     = array();
	$index_post_types      = array();
	$index_taxonomies_list = array();
	$index_terms_list      = array();
	foreach ( $_REQUEST as $key => $value ) {
		if ( empty( $value ) ) {
			$value = 0;
		}

		if ( 'relevanssi_weight_' === substr( $key, 0, strlen( 'relevanssi_weight_' ) ) ) {
			$type                       = substr( $key, strlen( 'relevanssi_weight_' ) );
			$post_type_weights[ $type ] = $value;
		}
		if ( 'relevanssi_taxonomy_weight_' === substr( $key, 0, strlen( 'relevanssi_taxonomy_weight_' ) ) ) {
			$type                       = 'post_tagged_with_' . substr( $key, strlen( 'relevanssi_taxonomy_weight_' ) );
			$post_type_weights[ $type ] = $value;
		}
		if ( 'relevanssi_term_weight_' === substr( $key, 0, strlen( 'relevanssi_term_weight_' ) ) ) {
			$type                       = 'taxonomy_term_' . substr( $key, strlen( 'relevanssi_term_weight_' ) );
			$post_type_weights[ $type ] = $value;
		}
		if ( 'relevanssi_index_type_' === substr( $key, 0, strlen( 'relevanssi_index_type_' ) ) ) {
			$type = substr( $key, strlen( 'relevanssi_index_type_' ) );
			if ( 'on' === $value ) {
				$index_post_types[ $type ] = true;
			}
		}
		if ( 'relevanssi_index_taxonomy_' === substr( $key, 0, strlen( 'relevanssi_index_taxonomy_' ) ) ) {
			$type = substr( $key, strlen( 'relevanssi_index_taxonomy_' ) );
			if ( 'on' === $value ) {
				$index_taxonomies_list[ $type ] = true;
			}
		}
		if ( 'relevanssi_index_terms_' === substr( $key, 0, strlen( 'relevanssi_index_terms_' ) ) ) {
			$type = substr( $key, strlen( 'relevanssi_index_terms_' ) );
			if ( 'on' === $value ) {
				$index_terms_list[ $type ] = true;
			}
		}
	}

	if ( 'indexing' === $_REQUEST['tab'] ) {
		update_option( 'relevanssi_index_taxonomies_list', array_keys( $index_taxonomies_list ), false );
		if ( RELEVANSSI_PREMIUM ) {
			update_option( 'relevanssi_index_terms', array_keys( $index_terms_list ), false );
		}
	}

	if ( count( $post_type_weights ) > 0 ) {
		update_option( 'relevanssi_post_type_weights', $post_type_weights );
	}

	if ( count( $index_post_types ) > 0 ) {
		update_option( 'relevanssi_index_post_types', array_keys( $index_post_types ), false );
	}

	if ( isset( $_REQUEST['relevanssi_index_fields_select'] ) ) {
		$fields_option = '';
		if ( 'all' === $_REQUEST['relevanssi_index_fields_select'] ) {
			$fields_option = 'all';
		}
		if ( 'visible' === $_REQUEST['relevanssi_index_fields_select'] ) {
			$fields_option = 'visible';
		}
		if ( 'some' === $_REQUEST['relevanssi_index_fields_select'] ) {
			if ( isset( $_REQUEST['relevanssi_index_fields'] ) ) {
				$fields_option = rtrim( $_REQUEST['relevanssi_index_fields'], " \t\n\r\0\x0B," );
			}
		}
		update_option( 'relevanssi_index_fields', $fields_option, false );
	}

	if ( isset( $_REQUEST['relevanssi_trim_logs'] ) ) {
		$trim_logs = $_REQUEST['relevanssi_trim_logs'];
		if ( ! is_numeric( $trim_logs ) || $trim_logs < 0 ) {
			$trim_logs = 0;
		}
		update_option( 'relevanssi_trim_logs', $trim_logs );
	}

	if ( isset( $_REQUEST['relevanssi_cat'] ) ) {
		if ( is_array( $_REQUEST['relevanssi_cat'] ) ) {
			$csv_cats = implode( ',', $_REQUEST['relevanssi_cat'] );
			update_option( 'relevanssi_cat', $csv_cats );
		}
	} else {
		if ( isset( $_REQUEST['relevanssi_cat_active'] ) ) {
			update_option( 'relevanssi_cat', '' );
		}
	}

	if ( isset( $_REQUEST['relevanssi_excat'] ) ) {
		if ( is_array( $_REQUEST['relevanssi_excat'] ) ) {
			$array_excats = $_REQUEST['relevanssi_excat'];
			$cat          = get_option( 'relevanssi_cat' );
			if ( $cat ) {
				$array_cats   = explode( ',', $cat );
				$valid_excats = array();
				foreach ( $array_excats as $excat ) {
					if ( ! in_array( $excat, $array_cats, true ) ) {
						$valid_excats[] = $excat;
					}
				}
			} else {
				// No category restrictions, so everything's good.
				$valid_excats = $array_excats;
			}
			$csv_excats = implode( ',', $valid_excats );
			update_option( 'relevanssi_excat', $csv_excats );
		}
	} else {
		if ( isset( $_REQUEST['relevanssi_excat_active'] ) ) {
			update_option( 'relevanssi_excat', '' );
		}
	}

	$options = array(
		'relevanssi_admin_search'           => false,
		'relevanssi_bg_col'                 => true,
		'relevanssi_class'                  => true,
		'relevanssi_css'                    => true,
		'relevanssi_default_orderby'        => true,
		'relevanssi_disable_or_fallback'    => true,
		'relevanssi_exact_match_bonus'      => true,
		'relevanssi_excerpt_allowable_tags' => true,
		'relevanssi_excerpt_custom_fields'  => true,
		'relevanssi_excerpt_type'           => true,
		'relevanssi_excerpts'               => true,
		'relevanssi_expand_shortcodes'      => false,
		'relevanssi_expand_highlights'      => true,
		'relevanssi_expst'                  => true,
		'relevanssi_fuzzy'                  => true,
		'relevanssi_highlight_comments'     => true,
		'relevanssi_highlight_docs'         => true,
		'relevanssi_highlight'              => true,
		'relevanssi_hilite_title'           => true,
		'relevanssi_implicit_operator'      => true,
		'relevanssi_index_author'           => false,
		'relevanssi_index_comments'         => false,
		'relevanssi_index_excerpt'          => false,
		'relevanssi_index_image_files'      => false,
		'relevanssi_log_queries_with_ip'    => true,
		'relevanssi_log_queries'            => true,
		'relevanssi_omit_from_logs'         => true,
		'relevanssi_polylang_all_languages' => true,
		'relevanssi_respect_exclude'        => true,
		'relevanssi_show_matches'           => true,
		'relevanssi_throttle'               => true,
		'relevanssi_txt_col'                => true,
		'relevanssi_wpml_only_current'      => true,
	);

	if ( isset( $_REQUEST['relevanssi_expst'] ) ) {
		$_REQUEST['relevanssi_expst'] = trim( $_REQUEST['relevanssi_expst'], ' ,' );
	}

	array_walk(
		$options,
		function( $autoload, $option ) {
			if ( isset( $_REQUEST[ $option ] ) ) {
				update_option( $option, $_REQUEST[ $option ], $autoload );
			}
		}
	);

	relevanssi_update_intval( $_REQUEST, 'relevanssi_excerpt_length', true, 10 );

	if ( function_exists( 'relevanssi_update_premium_options' ) ) {
		relevanssi_update_premium_options();
	}
	// phpcs:enable
}

/**
 * Prints out the 'User searches' page.
 */
function relevanssi_search_stats() {
	$relevanssi_hide_branding = get_option( 'relevanssi_hide_branding' );

	if ( 'on' === $relevanssi_hide_branding ) {
		$options_txt = __( 'User searches', 'relevanssi' );
	} else {
		$options_txt = __( 'Relevanssi User Searches', 'relevanssi' );
	}

	if ( isset( $_REQUEST['relevanssi_reset'] ) && current_user_can( 'manage_options' ) ) {
		check_admin_referer( 'relevanssi_reset_logs', '_relresnonce' );
		if ( isset( $_REQUEST['relevanssi_reset_code'] ) ) {
			if ( 'reset' === $_REQUEST['relevanssi_reset_code'] ) {
				$verbose = true;
				relevanssi_truncate_logs( $verbose );
			}
		}
	}

	wp_enqueue_style( 'dashboard' );
	wp_print_styles( 'dashboard' );
	wp_enqueue_script( 'dashboard' );
	wp_print_scripts( 'dashboard' );

	printf( "<div class='wrap'><h2>%s</h2>", esc_html( $options_txt ) );

	if ( 'on' === get_option( 'relevanssi_log_queries' ) ) {
		relevanssi_query_log();
	} else {
		printf( '<p>%s</p>', esc_html__( 'Enable query logging to see stats here.', 'relevanssi' ) );
	}
}

/**
 * Prints out the 'Admin search' page.
 */
function relevanssi_admin_search_page() {
	global $relevanssi_variables;

	$relevanssi_hide_branding = get_option( 'relevanssi_hide_branding' );

	$options_txt = __( 'Admin Search', 'relevanssi' );

	wp_enqueue_style( 'dashboard' );
	wp_print_styles( 'dashboard' );
	wp_enqueue_script( 'dashboard' );
	wp_print_scripts( 'dashboard' );

	printf( "<div class='wrap'><h2>%s</h2>", esc_html( $options_txt ) );

	require_once dirname( $relevanssi_variables['file'] ) . '/lib/tabs/search-page.php';
	relevanssi_search_tab();
}

/**
 * Truncates the Relevanssi logs.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param boolean $verbose If true, prints out a notice. Default true.
 *
 * @return boolean True if success, false if failure.
 */
function relevanssi_truncate_logs( $verbose = true ) {
	global $wpdb, $relevanssi_variables;

	$result = $wpdb->query( 'TRUNCATE ' . $relevanssi_variables['log_table'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	if ( $verbose ) {
		if ( false !== $result ) {
			printf( "<div id='relevanssi-warning' class='updated fade'>%s</div>", esc_html__( 'Logs clear!', 'relevanssi' ) );
		} else {
			printf( "<div id='relevanssi-warning' class='updated fade'>%s</div>", esc_html__( 'Clearing the logs failed.', 'relevanssi' ) );
		}
	}

	return $result;
}

/**
 * Shows the query log with the most common queries
 *
 * Uses relevanssi_total_queries() and relevanssi_date_queries() to fetch the data.
 */
function relevanssi_query_log() {
	/**
	 * Adjusts the number of days to show the logs in User searches page.
	 *
	 * @param int Number of days, default 1.
	 */
	$days1 = apply_filters( 'relevanssi_1day', 1 );

	/**
	 * Adjusts the number of days to show the logs in User searches page.
	 *
	 * @param int Number of days, default 7.
	 */
	$days7 = apply_filters( 'relevanssi_7days', 7 );

	/**
	 * Adjusts the number of days to show the logs in User searches page.
	 *
	 * @param int Number of days, default 30.
	 */
	$days30 = apply_filters( 'relevanssi_30days', 30 );

	printf( '<h3>%s</h3>', esc_html__( 'Total Searches', 'relevanssi' ) );

	printf( "<div style='width: 50%%; overflow: auto'>%s</div>", relevanssi_total_queries( __( 'Totals', 'relevanssi' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	echo '<div style="clear: both"></div>';

	printf( '<h3>%s</h3>', esc_html__( 'Common Queries', 'relevanssi' ) );

	/**
	 * Filters the number of rows to show.
	 *
	 * @param int Number of top results to show, default 20.
	 */
	$limit = apply_filters( 'relevanssi_user_searches_limit', 20 );

	// Translators: %d is the number of queries to show.
	printf( '<p>%s</p>', esc_html( sprintf( __( 'Here you can see the %d most common user search queries, how many times those queries were made and how many results were found for those queries.', 'relevanssi' ), $limit ) ) );

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	if ( 1 === $days1 ) {
		relevanssi_date_queries( $days1, __( 'Today and yesterday', 'relevanssi' ) );
	} else {
		// Translators: number of days to show.
		relevanssi_date_queries( $days1, sprintf( __( 'Last %d days', 'relevanssi' ), $days1 ) );
	}
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	// Translators: number of days to show.
	relevanssi_date_queries( $days7, sprintf( __( 'Last %d days', 'relevanssi' ), $days7 ) );
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	// Translators: number of days to show.
	relevanssi_date_queries( $days30, sprintf( __( 'Last %d days', 'relevanssi' ), $days30 ) );
	echo '</div>';

	echo '<div style="clear: both"></div>';

	printf( '<h3>%s</h3>', esc_html__( 'Unsuccessful Queries', 'relevanssi' ) );

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries( 1, __( 'Today and yesterday', 'relevanssi' ), 'bad' );
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries( 7, __( 'Last 7 days', 'relevanssi' ), 'bad' );
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	// Translators: number of days to show.
	relevanssi_date_queries( $days30, sprintf( __( 'Last %d days', 'relevanssi' ), $days30 ), 'bad' );
	echo '</div>';

	if ( current_user_can( 'manage_options' ) ) {

		echo '<div style="clear: both"></div>';
		printf( '<h3>%s</h3>', esc_html__( 'Reset Logs', 'relevanssi' ) );
		print( "<form method='post'>" );
		wp_nonce_field( 'relevanssi_reset_logs', '_relresnonce', true, true );
		printf(
			'<p><label for="relevanssi_reset_code">%s</label>
			<input type="text" id="relevanssi_reset_code" name="relevanssi_reset_code" />
			<input type="submit" name="relevanssi_reset" value="%s" class="button" /></p></form>',
			// Translators: do not translate "reset".
			esc_html__(
				'To reset the logs, type "reset" into the box here and click the Reset button',
				'relevanssi'
			),
			esc_html__( 'Reset', 'relevanssi' )
		);
	}

	echo '</div>';
}

/**
 * Shows the total number of searches on 'User searches' page.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $title The title that is printed out on top of the results.
 */
function relevanssi_total_queries( $title ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	$count  = array();
	$titles = array();

	$titles[0] = __( 'Today and yesterday', 'relevanssi' );
	$titles[1] = __( 'Last 7 days', 'relevanssi' );
	$titles[2] = __( 'Last 30 days', 'relevanssi' );
	$titles[3] = __( 'Forever', 'relevanssi' );

	$count[0] = $wpdb->get_var( "SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 1;" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count[1] = $wpdb->get_var( "SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 7;" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count[2] = $wpdb->get_var( "SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 30;" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count[3] = $wpdb->get_var( "SELECT COUNT(id) FROM $log_table;" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	printf(
		'<table class="widefat"><thead><tr><th colspan="2">%1$s</th></tr></thead><tbody><tr><th>%2$s</th><th style="text-align: center">%3$s</th></tr>',
		esc_html( $title ),
		esc_html__( 'When', 'relevanssi' ),
		esc_html__( 'Searches', 'relevanssi' )
	);

	foreach ( $count as $key => $searches ) {
		$when = $titles[ $key ];
		printf( "<tr><td>%s</td><td style='text-align: center'>%d</td></tr>", esc_html( $when ), intval( $searches ) );
	}
	echo '</tbody></table>';
}

/**
 * Shows the most common search queries on different time periods.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param int    $days    The number of days to show.
 * @param string $title   The title that is printed out on top of the results.
 * @param string $version If 'good', show the searches that found something; if
 * 'bad', show the searches that didn't find anything. Default 'good'.
 */
function relevanssi_date_queries( $days, $title, $version = 'good' ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	/** Documented in lib/interface.php. */
	$limit = apply_filters( 'relevanssi_user_searches_limit', 20 );

	if ( 'good' === $version ) {
		$queries = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT(id)) as cnt, query, hits ' .
				"FROM $log_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= %d
				GROUP BY query
				ORDER BY cnt DESC
				LIMIT %d',
				$days,
				$limit
			)
		);
	}

	if ( 'bad' === $version ) {
		$queries = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT(id)) as cnt, query, hits ' .
				"FROM $log_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= %d AND hits = 0
				GROUP BY query
				ORDER BY cnt DESC
				LIMIT %d',
				$days,
				$limit
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( count( $queries ) > 0 ) {
		printf(
			"<table class='widefat'><thead><tr><th colspan='3'>%s</th></tr></thead><tbody><tr><th>%s</th><th style='text-align: center'>#</th><th style='text-align: center'>%s</th></tr>",
			esc_html( $title ),
			esc_html__( 'Query', 'relevanssi' ),
			esc_html__( 'Hits', 'relevanssi' )
		);
		$url = get_bloginfo( 'url' );
		foreach ( $queries as $query ) {
			$search_parameter = rawurlencode( $query->query );
			/**
			 * Filters the query URL for the user searches page.
			 *
			 * @param string Query URL.
			 */
			$query_url = apply_filters( 'relevanssi_user_searches_query_url', $url . '/?s=' . $search_parameter );
			printf(
				"<tr><td><a href='%s'>%s</a></td><td style='padding: 3px 5px; text-align: center'>%d</td><td style='padding: 3px 5px; text-align: center'>%d</td></tr>",
				esc_attr( $query_url ),
				esc_attr( $query->query ),
				intval( $query->cnt ),
				intval( $query->hits )
			);
		}
		echo '</tbody></table>';
	}
}

/**
 * Returns 'checked' if the option is enabled.
 *
 * @param string $option Value to check.
 *
 * @return string If the option is 'on', returns 'checked', otherwise returns an
 * empty string.
 */
function relevanssi_check( $option ) {
	$checked = '';
	if ( 'on' === $option ) {
		$checked = 'checked';
	}
	return $checked;
}

/**
 * Returns 'selected' if the option matches a value.
 *
 * @param string $option Value to check.
 * @param string $value  The 'selected' value.
 *
 * @return string If the option matches the value, returns 'selected', otherwise
 * returns an empty string.
 */
function relevanssi_select( $option, $value ) {
	$selected = '';
	if ( $option === $value ) {
		$selected = 'selected';
	}
	return $selected;
}

/**
 * Prints out the Relevanssi options form.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_options_form() {
	global $relevanssi_variables, $wpdb;

	wp_enqueue_style( 'dashboard' );
	wp_print_styles( 'dashboard' );
	wp_enqueue_script( 'dashboard' );
	wp_print_scripts( 'dashboard' );

	echo "<div class='postbox-container'>";
	echo "<form method='post'>";

	wp_nonce_field( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );

	$display_save_button = true;

	$active_tab = 'overview';
	if ( isset( $_REQUEST['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$active_tab = $_REQUEST['tab']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	if ( 'stopwords' === $active_tab ) {
		$display_save_button = false;
	}

	printf( "<input type='hidden' name='tab' value='%s' />", esc_attr( $active_tab ) );

	$this_page = '?page=' . plugin_basename( $relevanssi_variables['file'] );

	$tabs = array(
		array(
			'slug'     => 'overview',
			'name'     => __( 'Overview', 'relevanssi' ),
			'require'  => 'tabs/overview-tab.php',
			'callback' => 'relevanssi_overview_tab',
			'save'     => 'premium',
		),
		array(
			'slug'     => 'indexing',
			'name'     => __( 'Indexing', 'relevanssi' ),
			'require'  => 'tabs/indexing-tab.php',
			'callback' => 'relevanssi_indexing_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'attachments',
			'name'     => __( 'Attachments', 'relevanssi' ),
			'require'  => 'tabs/attachments-tab.php',
			'callback' => 'relevanssi_attachments_tab',
			'save'     => false,
		),
		array(
			'slug'     => 'searching',
			'name'     => __( 'Searching', 'relevanssi' ),
			'require'  => 'tabs/searching-tab.php',
			'callback' => 'relevanssi_searching_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'logging',
			'name'     => __( 'Logging', 'relevanssi' ),
			'require'  => 'tabs/logging-tab.php',
			'callback' => 'relevanssi_logging_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'excerpts',
			'name'     => __( 'Excerpts and highlights', 'relevanssi' ),
			'require'  => 'tabs/excerpts-tab.php',
			'callback' => 'relevanssi_excerpts_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'synonyms',
			'name'     => __( 'Synonyms', 'relevanssi' ),
			'require'  => 'tabs/synonyms-tab.php',
			'callback' => 'relevanssi_synonyms_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'stopwords',
			'name'     => __( 'Stopwords', 'relevanssi' ),
			'require'  => 'tabs/stopwords-tab.php',
			'callback' => 'relevanssi_stopwords_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'redirects',
			'name'     => __( 'Redirects', 'relevanssi' ),
			'require'  => 'tabs/redirects-tab.php',
			'callback' => 'relevanssi_redirects_tab',
			'save'     => false,
		),
		array(
			'slug'     => 'debugging',
			'name'     => __( 'Debugging', 'relevanssi' ),
			'require'  => 'tabs/debugging-tab.php',
			'callback' => 'relevanssi_debugging_tab',
			'save'     => false,
		),
	);

	/**
	 * Allows adding new tabs to the Relevanssi menu.
	 *
	 * @param array $tabs An array of arrays defining the tabs.
	 *
	 * @return array Filtered tab array.
	 */
	$tabs = apply_filters( 'relevanssi_tabs', $tabs );
	?>
<h2 class="nav-tab-wrapper">
	<?php
	array_walk(
		$tabs,
		function( $tab ) use ( $this_page, $active_tab ) {
			?>
			<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=<?php echo esc_attr( $tab['slug'] ); ?>"
			class="nav-tab <?php echo esc_attr( $tab['slug'] === $active_tab ? 'nav-tab-active' : '' ); ?>">
			<?php echo esc_html( $tab['name'] ); ?></a>
			<?php
		}
	);
	?>
</h2>

	<?php
	$current_tab = $tabs[ array_search( $active_tab, wp_list_pluck( $tabs, 'slug' ), true ) ];
	if ( ! $current_tab['save'] || ( ! RELEVANSSI_PREMIUM && 'premium' === $current_tab['save'] ) ) {
		$display_save_button = false;
	}
	if ( $current_tab['require'] ) {
		require_once $current_tab['require'];
	}
	call_user_func( $current_tab['callback'] );

	if ( $display_save_button ) :
		?>

	<input type='submit' name='submit' value='<?php esc_attr_e( 'Save the options', 'relevanssi' ); ?>' class='button button-primary' />

	<?php endif; ?>

	</form>
</div>

	<?php
}

/**
 * Adds admin scripts to Relevanssi pages.
 *
 * Hooks to the 'admin_enqueue_scripts' action hook.
 *
 * @global array $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $hook The hook suffix for the current admin page.
 */
function relevanssi_add_admin_scripts( $hook ) {
	global $relevanssi_variables;

	$plugin_dir_url = plugin_dir_url( $relevanssi_variables['file'] );

	// Only enqueue on Relevanssi pages.
	$acceptable_hooks = array(
		'toplevel_page_relevanssi-premium/relevanssi',
		'settings_page_relevanssi-premium/relevanssi',
		'toplevel_page_relevanssi/relevanssi',
		'settings_page_relevanssi/relevanssi',
		'dashboard_page_relevanssi_admin_search',
	);
	if ( ! in_array( $hook, $acceptable_hooks, true ) ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'relevanssi_admin_js', $plugin_dir_url . 'lib/admin_scripts.js', array( 'wp-color-picker' ), $relevanssi_variables['plugin_version'], true );
	if ( ! RELEVANSSI_PREMIUM ) {
		wp_enqueue_script( 'relevanssi_admin_js_free', $plugin_dir_url . 'lib/admin_scripts_free.js', array( 'relevanssi_admin_js' ), $relevanssi_variables['plugin_version'], true );
	}
	if ( RELEVANSSI_PREMIUM ) {
		wp_enqueue_script( 'relevanssi_admin_js_premium', $plugin_dir_url . 'premium/admin_scripts_premium.js', array( 'relevanssi_admin_js' ), $relevanssi_variables['plugin_version'], true );
	}
	wp_enqueue_style( 'relevanssi_admin_css', $plugin_dir_url . 'lib/admin_styles.css', array(), $relevanssi_variables['plugin_version'] );

	$localizations = array(
		'confirm'              => __( 'Click OK to copy Relevanssi options to all subsites', 'relevanssi' ),
		'confirm_stopwords'    => __( 'Are you sure you want to remove all stopwords?', 'relevanssi' ),
		'truncating_index'     => __( 'Wiping out the index...', 'relevanssi' ),
		'done'                 => __( 'Done.', 'relevanssi' ),
		'indexing_users'       => __( 'Indexing users...', 'relevanssi' ),
		'indexing_taxonomies'  => __( 'Indexing the following taxonomies:', 'relevanssi' ),
		'indexing_attachments' => __( 'Indexing attachments...', 'relevanssi' ),
		'counting_posts'       => __( 'Counting posts...', 'relevanssi' ),
		'counting_terms'       => __( 'Counting taxonomy terms...', 'relevanssi' ),
		'counting_users'       => __( 'Counting users...', 'relevanssi' ),
		'counting_attachments' => __( 'Counting attachments...', 'relevanssi' ),
		'posts_found'          => __( 'posts found.', 'relevanssi' ),
		'terms_found'          => __( 'taxonomy terms found.', 'relevanssi' ),
		'users_found'          => __( 'users found.', 'relevanssi' ),
		'attachments_found'    => __( 'attachments found.', 'relevanssi' ),
		'taxonomy_disabled'    => __( 'Taxonomy term indexing is disabled.', 'relevanssi' ),
		'user_disabled'        => __( 'User indexing is disabled.', 'relevanssi' ),
		'indexing_complete'    => __( 'Indexing complete.', 'relevanssi' ),
		'excluded_posts'       => __( 'posts excluded.', 'relevanssi' ),
		'options_changed'      => __( 'Settings have changed, please save the options before indexing.', 'relevanssi' ),
		'reload_state'         => __( 'Reload the page to refresh the state of the index.', 'relevanssi' ),
		'pdf_reset_confirm'    => __( 'Are you sure you want to delete all attachment content from the index?', 'relevanssi' ),
		'pdf_reset_done'       => __( 'Relevanssi attachment data wiped clean.', 'relevanssi' ),
		'pdf_reset_problems'   => __( 'There were problems wiping the Relevanssi attachment data clean.', 'relevanssi' ),
		'hour'                 => __( 'hour', 'relevanssi' ),
		'hours'                => __( 'hours', 'relevanssi' ),
		'about'                => __( 'about', 'relevanssi' ),
		'sixty_min'            => __( 'about an hour', 'relevanssi' ),
		'ninety_min'           => __( 'about an hour and a half', 'relevanssi' ),
		'minute'               => __( 'minute', 'relevanssi' ),
		'minutes'              => __( 'minutes', 'relevanssi' ),
		'underminute'          => __( 'less than a minute', 'relevanssi' ),
		'notimeremaining'      => __( "we're done!", 'relevanssi' ),
	);

	wp_localize_script( 'relevanssi_admin_js', 'relevanssi', $localizations );

	$nonce = array(
		'indexing_nonce'  => wp_create_nonce( 'relevanssi_indexing_nonce' ),
		'searching_nonce' => wp_create_nonce( 'relevanssi_admin_search_nonce' ),
	);

	if ( ! RELEVANSSI_PREMIUM ) {
		wp_localize_script( 'relevanssi_admin_js', 'nonce', $nonce );
	}

	/**
	 * Sets the indexing limit, ie. how many posts are indexed at once.
	 *
	 * Relevanssi starts by indexing this many posts at once. If the process
	 * goes fast enough, Relevanssi will then increase the limit and if the
	 * process is slow, the limit will be decreased. If necessary, you can
	 * use the relevanssi_indexing_adjust filter hook to disable that
	 * adjustment.
	 *
	 * @param int The indexing limit, default 10.
	 */
	$indexing_limit = apply_filters( 'relevanssi_indexing_limit', 10 );

	/**
	 * Sets the indexing adjustment.
	 *
	 * Relevanssi will adjust the number of posts indexed at once to speed
	 * up the process if it goes fast and to slow down, if the posts are
	 * slow to index. You can use this filter to stop that behaviour, making
	 * Relevanssi index posts at constant pace. That's generally slower, but
	 * more reliable.
	 *
	 * @param boolean Should the limit be adjusted, default true.
	 */
	$indexing_adjust = apply_filters( 'relevanssi_indexing_adjust', true );

	wp_localize_script(
		'relevanssi_admin_js',
		'relevanssi_params',
		array(
			'indexing_limit'  => $indexing_limit,
			'indexing_adjust' => $indexing_adjust,
		)
	);
}

/**
 * Prints out the form fields for tag and category weights.
 */
function relevanssi_form_tag_weight() {
	$taxonomy_weights = get_option( 'relevanssi_post_type_weights' );

	$tag_value = 1;
	if ( isset( $taxonomy_weights['post_tag'] ) ) {
		$tag_value = $taxonomy_weights['post_tag'];
	}
	$category_value = 1;
	if ( isset( $taxonomy_weights['category'] ) ) {
		$category_value = $taxonomy_weights['category'];
	}
	?>
<tr>
	<td>
		<?php esc_html_e( 'Tag weight', 'relevanssi' ); ?>
	</td>
	<td class="col-2">
		<input type='text' id='relevanssi_weight_post_tag' name='relevanssi_weight_post_tag' size='4' value='<?php echo esc_attr( $tag_value ); ?>' />
	</td>
</tr>
<tr>
	<td>
		<?php esc_html_e( 'Category weight', 'relevanssi' ); ?>
	</td>
	<td class="col-2">
		<input type='text' id='relevanssi_weight_category' name='relevanssi_weight_category' size='4' value='<?php echo esc_attr( $category_value ); ?>' />
	</td>
</tr>
	<?php
}

/**
 * Turns off options, ie. sets them to "off".
 *
 * If the specified options don't exist in the request array, they are set to
 * "off".
 *
 * @param array $request The _REQUEST array, passed as reference.
 * @param array $options An array of option names.
 */
function relevanssi_turn_off_options( &$request, $options ) {
	array_walk(
		$options,
		function( $option ) use ( &$request ) {
			if ( ! isset( $request[ $option ] ) ) {
				$request[ $option ] = 'off';
			}
		}
	);
}

/**
 * Returns 'on' if option exists and value is not 'off', otherwise 'off'.
 *
 * @param array  $request An array of option values.
 * @param string $option  The key to check.
 *
 * @return string 'on' or 'off'.
 */
function relevanssi_off_or_on( $request, $option ) {
	if ( isset( $request[ $option ] ) && 'off' !== $request[ $option ] ) {
		return 'on';
	}
	return 'off';
}

/**
 * Returns an imploded string if the option exists and is an array, an empty
 * string otherwise.
 *
 * @param array  $request An array of option values.
 * @param string $option  The key to check.
 * @param string $glue    The glue string for implode(), default ','.
 *
 * @return string Imploded string or an empty string.
 */
function relevanssi_implode( $request, $option, $glue = ',' ) {
	if ( isset( $request[ $option ] ) && is_array( $request[ $option ] ) ) {
		return implode( $glue, $request[ $option ] );
	}
	return '';
}

/**
 * Returns the intval of the option if it exists, null otherwise.
 *
 * @param array  $request An array of option values.
 * @param string $option  The key to check.
 *
 * @return int|null Integer value of the option, or null.
 */
function relevanssi_intval( $request, $option ) {
	if ( isset( $request[ $option ] ) ) {
		return intval( $request[ $option ] );
	}
	return null;
}

/**
 * Returns a legal value.
 *
 * @param array  $request  An array of option values.
 * @param string $option   The key to check.
 * @param array  $values   The legal values.
 * @param string $default  The default value.
 *
 * @return string|null A legal value or the default value, null if the option
 * isn't set.
 */
function relevanssi_legal_value( $request, $option, $values, $default ) {
	$value = null;
	if ( isset( $request[ $option ] ) ) {
		$value = $default;
		if ( in_array( $request[ $option ], $values, true ) ) {
			$value = $request[ $option ];
		}
	}
	return $value;
}

/**
 * Sets an on/off option according to the request value.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param boolean $autoload Should the option autoload, default true.
 */
function relevanssi_update_off_or_on( $request, $option, $autoload = true ) {
	relevanssi_update_legal_value(
		$request,
		$option,
		array( 'off', 'on' ),
		'off',
		$autoload
	);
}

/**
 * Sets an option after sanitizing and unslashing the value.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param boolean $autoload Should the option autoload, default true.
 */
function relevanssi_update_sanitized( $request, $option, $autoload = true ) {
	if ( isset( $request[ $option ] ) ) {
		$value = sanitize_text_field( wp_unslash( $request[ $option ] ) );
		update_option( $option, $value, $autoload );
	}
}

/**
 * Sets an option after doing intval.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param boolean $autoload Should the option autoload, default true.
 * @param int     $default  The default value if intval() fails, default 0.
 */
function relevanssi_update_intval( $request, $option, $autoload = true, $default = 0 ) {
	if ( isset( $request[ $option ] ) ) {
		$value = intval( $request[ $option ] );
		if ( ! $value ) {
			$value = $default;
		}
		update_option( $option, $value, $autoload );
	}
}

/**
 * Sets an option after doing floatval.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param boolean $autoload Should the option autoload, default true.
 * @param int     $default  The default value if floatval() fails, default 0.
 */
function relevanssi_update_floatval( $request, $option, $autoload = true, $default = 0 ) {
	if ( isset( $request[ $option ] ) ) {
		$value = floatval( $request[ $option ] );
		if ( ! $value ) {
			$value = $default;
		}
		update_option( $option, $value, $autoload );
	}
}

/**
 * Sets an option with one of the listed legal values.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param array   $values   The legal values.
 * @param string  $default  The default value.
 * @param boolean $autoload Should the option autoload, default true.
 */
function relevanssi_update_legal_value( $request, $option, $values, $default, $autoload = true ) {
	if ( isset( $request[ $option ] ) ) {
		$value = $default;
		if ( in_array( $request[ $option ], $values, true ) ) {
			$value = $request[ $option ];
		}
		update_option( $option, $value, $autoload );
	}
}
