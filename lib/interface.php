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
	if ( isset( $_REQUEST['relevanssi_content_boost'] ) ) {
		$boost = floatval( $_REQUEST['relevanssi_content_boost'] );
		update_option( 'relevanssi_content_boost', $boost );
	}

	if ( isset( $_REQUEST['relevanssi_title_boost'] ) ) {
		$boost = floatval( $_REQUEST['relevanssi_title_boost'] );
		update_option( 'relevanssi_title_boost', $boost );
	}

	if ( isset( $_REQUEST['relevanssi_comment_boost'] ) ) {
		$boost = floatval( $_REQUEST['relevanssi_comment_boost'] );
		update_option( 'relevanssi_comment_boost', $boost );
	}

	if ( isset( $_REQUEST['relevanssi_min_word_length'] ) ) {
		$value = intval( $_REQUEST['relevanssi_min_word_length'] );
		if ( 0 === $value ) {
			$value = 3;
		}
		update_option( 'relevanssi_min_word_length', $value );
	}

	if ( 'indexing' === $_REQUEST['tab'] ) {
		if ( ! isset( $_REQUEST['relevanssi_index_author'] ) ) {
			$_REQUEST['relevanssi_index_author'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_index_excerpt'] ) ) {
			$_REQUEST['relevanssi_index_excerpt'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_expand_shortcodes'] ) ) {
			$_REQUEST['relevanssi_expand_shortcodes'] = 'off';
		}
	}

	if ( 'searching' === $_REQUEST['tab'] ) {
		if ( ! isset( $_REQUEST['relevanssi_admin_search'] ) ) {
			$_REQUEST['relevanssi_admin_search'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_throttle'] ) ) {
			$_REQUEST['relevanssi_throttle'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_disable_or_fallback'] ) ) {
			$_REQUEST['relevanssi_disable_or_fallback'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_respect_exclude'] ) ) {
			$_REQUEST['relevanssi_respect_exclude'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_wpml_only_current'] ) ) {
			$_REQUEST['relevanssi_wpml_only_current'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_polylang_all_languages'] ) ) {
			$_REQUEST['relevanssi_polylang_all_languages'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_exact_match_bonus'] ) ) {
			$_REQUEST['relevanssi_exact_match_bonus'] = 'off';
		}
	}

	if ( 'logging' === $_REQUEST['tab'] ) {
		if ( ! isset( $_REQUEST['relevanssi_log_queries'] ) ) {
			$_REQUEST['relevanssi_log_queries'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_log_queries_with_ip'] ) ) {
			$_REQUEST['relevanssi_log_queries_with_ip'] = 'off';
		}
	}

	if ( 'excerpts' === $_REQUEST['tab'] ) {
		if ( ! isset( $_REQUEST['relevanssi_excerpts'] ) ) {
			$_REQUEST['relevanssi_excerpts'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_show_matches'] ) ) {
			$_REQUEST['relevanssi_show_matches'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_hilite_title'] ) ) {
			$_REQUEST['relevanssi_hilite_title'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_highlight_docs'] ) ) {
			$_REQUEST['relevanssi_highlight_docs'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_highlight_comments'] ) ) {
			$_REQUEST['relevanssi_highlight_comments'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_excerpt_custom_fields'] ) ) {
			$_REQUEST['relevanssi_excerpt_custom_fields'] = 'off';
		}

		if ( ! isset( $_REQUEST['relevanssi_word_boundaries'] ) ) {
			$_REQUEST['relevanssi_word_boundaries'] = 'off';
		}
	}

	if ( isset( $_REQUEST['relevanssi_excerpt_length'] ) ) {
		$value = intval( $_REQUEST['relevanssi_excerpt_length'] );
		if ( 0 !== $value ) {
			update_option( 'relevanssi_excerpt_length', $value );
		}
	}

	if ( isset( $_REQUEST['relevanssi_synonyms'] ) ) {
		$linefeeds = array( "\r\n", "\n", "\r" );
		$value     = str_replace( $linefeeds, ';', $_REQUEST['relevanssi_synonyms'] );
		$value     = stripslashes( $value );
		update_option( 'relevanssi_synonyms', $value );
	}

	if ( isset( $_REQUEST['relevanssi_show_matches'] ) ) {
		update_option( 'relevanssi_show_matches', $_REQUEST['relevanssi_show_matches'] );
	}
	if ( isset( $_REQUEST['relevanssi_show_matches_text'] ) ) {
		$value = $_REQUEST['relevanssi_show_matches_text'];
		$value = str_replace( '"', "'", $value );
		update_option( 'relevanssi_show_matches_text', $value );
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
		update_option( 'relevanssi_index_taxonomies_list', array_keys( $index_taxonomies_list ) );
		if ( RELEVANSSI_PREMIUM ) {
			update_option( 'relevanssi_index_terms', array_keys( $index_terms_list ) );
		}
	}

	if ( count( $post_type_weights ) > 0 ) {
		update_option( 'relevanssi_post_type_weights', $post_type_weights );
	}

	if ( count( $index_post_types ) > 0 ) {
		update_option( 'relevanssi_index_post_types', array_keys( $index_post_types ) );
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
				$fields_option = $_REQUEST['relevanssi_index_fields'];
			}
		}
		update_option( 'relevanssi_index_fields', $fields_option );
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

	if ( isset( $_REQUEST['relevanssi_admin_search'] ) ) {
		update_option( 'relevanssi_admin_search', $_REQUEST['relevanssi_admin_search'] );
	}
	if ( isset( $_REQUEST['relevanssi_excerpts'] ) ) {
		update_option( 'relevanssi_excerpts', $_REQUEST['relevanssi_excerpts'] );
	}
	if ( isset( $_REQUEST['relevanssi_excerpt_type'] ) ) {
		update_option( 'relevanssi_excerpt_type', $_REQUEST['relevanssi_excerpt_type'] );
	}
	if ( isset( $_REQUEST['relevanssi_excerpt_allowable_tags'] ) ) {
		update_option( 'relevanssi_excerpt_allowable_tags', $_REQUEST['relevanssi_excerpt_allowable_tags'] );
	}
	if ( isset( $_REQUEST['relevanssi_log_queries'] ) ) {
		update_option( 'relevanssi_log_queries', $_REQUEST['relevanssi_log_queries'] );
	}
	if ( isset( $_REQUEST['relevanssi_log_queries_with_ip'] ) ) {
		update_option( 'relevanssi_log_queries_with_ip', $_REQUEST['relevanssi_log_queries_with_ip'] );
	}
	if ( isset( $_REQUEST['relevanssi_highlight'] ) ) {
		update_option( 'relevanssi_highlight', $_REQUEST['relevanssi_highlight'] );
	}
	if ( isset( $_REQUEST['relevanssi_highlight_docs'] ) ) {
		update_option( 'relevanssi_highlight_docs', $_REQUEST['relevanssi_highlight_docs'] );
	}
	if ( isset( $_REQUEST['relevanssi_highlight_comments'] ) ) {
		update_option( 'relevanssi_highlight_comments', $_REQUEST['relevanssi_highlight_comments'] );
	}
	if ( isset( $_REQUEST['relevanssi_txt_col'] ) ) {
		update_option( 'relevanssi_txt_col', $_REQUEST['relevanssi_txt_col'] );
	}
	if ( isset( $_REQUEST['relevanssi_bg_col'] ) ) {
		update_option( 'relevanssi_bg_col', $_REQUEST['relevanssi_bg_col'] );
	}
	if ( isset( $_REQUEST['relevanssi_css'] ) ) {
		update_option( 'relevanssi_css', $_REQUEST['relevanssi_css'] );
	}
	if ( isset( $_REQUEST['relevanssi_class'] ) ) {
		update_option( 'relevanssi_class', $_REQUEST['relevanssi_class'] );
	}
	if ( isset( $_REQUEST['relevanssi_expst'] ) ) {
		update_option( 'relevanssi_exclude_posts', $_REQUEST['relevanssi_expst'] );
	}
	if ( isset( $_REQUEST['relevanssi_hilite_title'] ) ) {
		update_option( 'relevanssi_hilite_title', $_REQUEST['relevanssi_hilite_title'] );
	}
	if ( isset( $_REQUEST['relevanssi_index_comments'] ) ) {
		update_option( 'relevanssi_index_comments', $_REQUEST['relevanssi_index_comments'] );
	}
	if ( isset( $_REQUEST['relevanssi_index_author'] ) ) {
		update_option( 'relevanssi_index_author', $_REQUEST['relevanssi_index_author'] );
	}
	if ( isset( $_REQUEST['relevanssi_index_excerpt'] ) ) {
		update_option( 'relevanssi_index_excerpt', $_REQUEST['relevanssi_index_excerpt'] );
	}
	if ( isset( $_REQUEST['relevanssi_fuzzy'] ) ) {
		update_option( 'relevanssi_fuzzy', $_REQUEST['relevanssi_fuzzy'] );
	}
	if ( isset( $_REQUEST['relevanssi_expand_shortcodes'] ) ) {
		update_option( 'relevanssi_expand_shortcodes', $_REQUEST['relevanssi_expand_shortcodes'] );
	}
	if ( isset( $_REQUEST['relevanssi_implicit_operator'] ) ) {
		update_option( 'relevanssi_implicit_operator', $_REQUEST['relevanssi_implicit_operator'] );
	}
	if ( isset( $_REQUEST['relevanssi_omit_from_logs'] ) ) {
		update_option( 'relevanssi_omit_from_logs', $_REQUEST['relevanssi_omit_from_logs'] );
	}
	if ( isset( $_REQUEST['relevanssi_disable_or_fallback'] ) ) {
		update_option( 'relevanssi_disable_or_fallback', $_REQUEST['relevanssi_disable_or_fallback'] );
	}
	if ( isset( $_REQUEST['relevanssi_respect_exclude'] ) ) {
		update_option( 'relevanssi_respect_exclude', $_REQUEST['relevanssi_respect_exclude'] );
	}
	if ( isset( $_REQUEST['relevanssi_throttle'] ) ) {
		update_option( 'relevanssi_throttle', $_REQUEST['relevanssi_throttle'] );
	}
	if ( isset( $_REQUEST['relevanssi_wpml_only_current'] ) ) {
		update_option( 'relevanssi_wpml_only_current', $_REQUEST['relevanssi_wpml_only_current'] );
	}
	if ( isset( $_REQUEST['relevanssi_polylang_all_languages'] ) ) {
		update_option( 'relevanssi_polylang_all_languages', $_REQUEST['relevanssi_polylang_all_languages'] );
	}
	if ( isset( $_REQUEST['relevanssi_word_boundaries'] ) ) {
		update_option( 'relevanssi_word_boundaries', $_REQUEST['relevanssi_word_boundaries'] );
	}
	if ( isset( $_REQUEST['relevanssi_default_orderby'] ) ) {
		update_option( 'relevanssi_default_orderby', $_REQUEST['relevanssi_default_orderby'] );
	}
	if ( isset( $_REQUEST['relevanssi_excerpt_custom_fields'] ) ) {
		update_option( 'relevanssi_excerpt_custom_fields', $_REQUEST['relevanssi_excerpt_custom_fields'] );
	}
	if ( isset( $_REQUEST['relevanssi_exact_match_bonus'] ) ) {
		update_option( 'relevanssi_exact_match_bonus', $_REQUEST['relevanssi_exact_match_bonus'] );
	}

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
			'<p><label for="relevanssi_reset_code">%s</label></p></form>',
			sprintf(
				// Translators: %1$s is the input field, %2$s is the submit button.
				__( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'To reset the logs, type "reset" into the box here %1$s and click %2$s',
					'relevanssi'
				),
				' <input type="text" id="relevanssi_reset_code" name="relevanssi_reset_code" />',
				' <input type="submit" name="relevanssi_reset" value="Reset" class="button" />'
			)
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
			$query_url        = $url . '/?s=' . $search_parameter;
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
	?>

<h2 class="nav-tab-wrapper">
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=overview" class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Overview', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=indexing" class="nav-tab <?php echo 'indexing' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Indexing', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=attachments" class="nav-tab <?php echo 'attachments' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Attachments', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=searching" class="nav-tab <?php echo 'searching' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Searching', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=logging" class="nav-tab <?php echo 'logging' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logging', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=excerpts" class="nav-tab <?php echo 'excerpts' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Excerpts and highlights', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=synonyms" class="nav-tab <?php echo 'synonyms' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Synonyms', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=stopwords" class="nav-tab <?php echo 'stopwords' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Stopwords', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=redirects" class="nav-tab <?php echo 'redirects' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Redirects', 'relevanssi' ); ?></a>
	<?php if ( RELEVANSSI_PREMIUM ) : ?>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=related" class="nav-tab <?php echo 'related' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Related', 'relevanssi' ); ?></a>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=importexport" class="nav-tab <?php echo 'importexport' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Import / Export options', 'relevanssi' ); ?></a>
	<?php endif; ?>
</h2>

	<?php
	if ( 'overview' === $active_tab ) {
		if ( ! RELEVANSSI_PREMIUM ) {
			$display_save_button = false;
		}
		require_once 'tabs/overview-tab.php';
		relevanssi_overview_tab();
	}
	if ( 'logging' === $active_tab ) {
		require_once 'tabs/logging-tab.php';
		relevanssi_logging_tab();
	}
	if ( 'searching' === $active_tab ) {
		require_once 'tabs/searching-tab.php';
		relevanssi_searching_tab();
	}
	if ( 'excerpts' === $active_tab ) {
		require_once 'tabs/excerpts-tab.php';
		relevanssi_excerpts_tab();
	}
	if ( 'indexing' === $active_tab ) {
		require_once 'tabs/indexing-tab.php';
		relevanssi_indexing_tab();
	}
	if ( 'attachments' === $active_tab ) {
		if ( ! RELEVANSSI_PREMIUM ) {
			$display_save_button = false;
			require_once 'tabs/attachments-tab.php';
			relevanssi_attachments_tab();
		} else {
			require_once dirname( $relevanssi_variables['file'] ) . '/premium/tabs/attachments-tab.php';
			relevanssi_attachments_tab();
		}
	}
	if ( 'synonyms' === $active_tab ) {
		require_once 'tabs/synonyms-tab.php';
		relevanssi_synonyms_tab();
	}
	if ( 'stopwords' === $active_tab ) {
		require_once 'tabs/stopwords-tab.php';
		relevanssi_stopwords_tab();
	}
	if ( 'importexport' === $active_tab ) {
		if ( RELEVANSSI_PREMIUM ) {
			require_once dirname( $relevanssi_variables['file'] ) . '/premium/tabs/import-export-tab.php';
			relevanssi_import_export_tab();
		}
	}
	if ( 'related' === $active_tab ) {
		if ( RELEVANSSI_PREMIUM ) {
			require_once dirname( $relevanssi_variables['file'] ) . '/premium/tabs/related-tab.php';
			relevanssi_related_tab();
		}
	}
	if ( 'redirects' === $active_tab ) {
		if ( ! RELEVANSSI_PREMIUM ) {
			$display_save_button = false;
			require_once 'tabs/redirects-tab.php';
			relevanssi_redirects_tab();
		} else {
			require_once dirname( $relevanssi_variables['file'] ) . '/premium/tabs/redirects-tab.php';
			relevanssi_redirects_tab();
		}
	}

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
