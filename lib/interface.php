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
	// phpcs:disable WordPress.CSRF.NonceVerification
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
		if ( 'relevanssi_weight_' === substr( $key, 0, strlen( 'relevanssi_weight_' ) ) ) {
			$type                       = substr( $key, strlen( 'relevanssi_weight_' ) );
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
			$csv_cats = implode( ',', $_REQUEST['relevanssi_excat'] );
			update_option( 'relevanssi_excat', $csv_cats );
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
	if ( isset( $_REQUEST['relevanssi_index_limit'] ) ) {
		update_option( 'relevanssi_index_limit', $_REQUEST['relevanssi_index_limit'] );
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

	$result = $wpdb->query( 'TRUNCATE ' . $relevanssi_variables['log_table'] ); // WPCS: unprepared SQL ok.

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
 * Displays the list of most common words in the index.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables.
 *
 * @param int     $limit  How many words to display, default 25.
 * @param boolean $wp_cli If true, return just a list of words. If false, print out
 * HTML code.
 *
 * @return array A list of words, if $wp_cli is true.
 */
function relevanssi_common_words( $limit = 25, $wp_cli = false ) {
	global $wpdb, $relevanssi_variables;

	$plugin = 'relevanssi';
	if ( RELEVANSSI_PREMIUM ) {
		$plugin = 'relevanssi-premium';
	}

	if ( ! is_numeric( $limit ) ) {
		$limit = 25;
	}

	$words = $wpdb->get_results( 'SELECT COUNT(*) as cnt, term FROM ' . $relevanssi_variables['relevanssi_table'] . " GROUP BY term ORDER BY cnt DESC LIMIT $limit" ); // WPCS: unprepared sql ok, Relevanssi table name and $limit is numeric.

	if ( ! $wp_cli ) {
		printf( '<h2>%s</h2>', esc_html__( '25 most common words in the index', 'relevanssi' ) );
		printf( '<p>%s</p>', esc_html__( "These words are excellent stopword material. A word that appears in most of the posts in the database is quite pointless when searching. This is also an easy way to create a completely new stopword list, if one isn't available in your language. Click the icon after the word to add the word to the stopword list. The word will also be removed from the index, so rebuilding the index is not necessary.", 'relevanssi' ) );

?>
<input type="hidden" name="dowhat" value="add_stopword" />
<table class="form-table">
<tr>
	<th scope="row"><?php esc_html_e( 'Stopword Candidates', 'relevanssi' ); ?></th>
	<td>
<ul>
	<?php
	$src = plugins_url( 'delete.png', $relevanssi_variables['file'] );

	foreach ( $words as $word ) {
		$stop = __( 'Add to stopwords', 'relevanssi' );
		printf( '<li>%1$s (%2$d) <input style="padding: 0; margin: 0" type="image" src="%3$s" alt="%4$s" name="term" value="%5$s"/></li>', esc_html( $word->term ), esc_html( $word->cnt ), esc_attr( $src ), esc_attr( $stop ), esc_attr( $word->term ) );
	}
	?>
	</ul>
	</td>
</tr>
</table>
	<?php

	}

	return $words;
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
	 * @param int Number of days, default 30.
	 */
	$days30 = apply_filters( 'relevanssi_30days', 30 );

	printf( '<h3>%s</h3>', esc_html__( 'Total Searches', 'relevanssi' ) );

	printf( "<div style='width: 50%%; overflow: auto'>%s</div>", relevanssi_total_queries( __( 'Totals', 'relevanssi' ) ) ); // WPCS: XSS ok, already escaped by relevanssi_total_queries().

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
	relevanssi_date_queries( 1, __( 'Today and yesterday', 'relevanssi' ) );
	echo '</div>';

	echo "<div style='width: 30%; float: left; margin-right: 2%; overflow: auto'>";
	relevanssi_date_queries( 7, __( 'Last 7 days', 'relevanssi' ) );
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
		// Translators: %1$s is the input field, %2$s is the submit button.
		printf( '<p>%s</p></form>', sprintf( __( 'To reset the logs, type "reset" into the box here %1$s and click %2$s', 'relevanssi' ), ' <input type="text" name="relevanssi_reset_code" />', ' <input type="submit" name="relevanssi_reset" value="Reset" class="button" />' ) ); // WPCS: XSS ok.

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

	$count[0] = $wpdb->get_var( "SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 1;" ); // WPCS: unprepared SQL ok, Relevanssi table name.
	$count[1] = $wpdb->get_var( "SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 7;" ); // WPCS: unprepared SQL ok, Relevanssi table name.
	$count[2] = $wpdb->get_var( "SELECT COUNT(id) FROM $log_table WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= 30;" ); // WPCS: unprepared SQL ok, Relevanssi table name.
	$count[3] = $wpdb->get_var( "SELECT COUNT(id) FROM $log_table;" ); // WPCS: unprepared SQL ok, Relevanssi table name.

	printf( '<table class="widefat"><thead><tr><th colspan="2">%1$s</th></tr></thead><tbody><tr><th>%2$s</th><th style="text-align: center">%3$s</th></tr>',
	esc_html( $title ), esc_html__( 'When', 'relevanssi' ), esc_html__( 'Searches', 'relevanssi' ) );

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
				"SELECT COUNT(DISTINCT(id)) as cnt, query, hits
				FROM $log_table
				WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= %d
				GROUP BY query
				ORDER BY cnt DESC
				LIMIT %d",
				$days, $limit
			)
		); // WPCS: unprepared SQL ok, Relevanssi table name.
	}

	if ( 'bad' === $version ) {
		$queries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT(id)) as cnt, query, hits
				FROM $log_table
				WHERE TIMESTAMPDIFF(DAY, time, NOW()) <= %d AND hits = 0
				GROUP BY query
				ORDER BY cnt DESC
				LIMIT %d",
				$days, $limit
			)
		); // WPCS: unprepared SQL ok, Relevanssi table name.
	}

	if ( count( $queries ) > 0 ) {
		printf( "<table class='widefat'><thead><tr><th colspan='3'>%s</th></tr></thead><tbody><tr><th>%s</th><th style='text-align: center'>#</th><th style='text-align: center'>%s</th></tr>",
		esc_html( $title ), esc_html__( 'Query', 'relevanssi' ), esc_html__( 'Hits', 'relevanssi' ) );
		$url = get_bloginfo( 'url' );
		foreach ( $queries as $query ) {
			$search_parameter = rawurlencode( $query->query );
			$query_url        = $url . '/?s=' . $search_parameter;
			printf( "<tr><td><a href='%s'>%s</a></td><td style='padding: 3px 5px; text-align: center'>%d</td><td style='padding: 3px 5px; text-align: center'>%d</td></tr>",
			esc_attr( $query_url ), esc_attr( $query->query ), intval( $query->cnt ), intval( $query->hits ) );
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

	$excerpts               = get_option( 'relevanssi_excerpts' );
	$excerpt_length         = get_option( 'relevanssi_excerpt_length' );
	$excerpt_type           = get_option( 'relevanssi_excerpt_type' );
	$excerpt_allowable_tags = get_option( 'relevanssi_excerpt_allowable_tags' );
	$excerpt_custom_fields  = get_option( 'relevanssi_excerpt_custom_fields' );
	$highlight              = get_option( 'relevanssi_highlight' );
	$txt_col                = get_option( 'relevanssi_txt_col' );
	$bg_col                 = get_option( 'relevanssi_bg_col' );
	$css                    = get_option( 'relevanssi_css' );
	$class                  = get_option( 'relevanssi_class' );
	$synonyms               = get_option( 'relevanssi_synonyms' );
	$highlight_title        = get_option( 'relevanssi_hilite_title' );
	$index_comments         = get_option( 'relevanssi_index_comments' );
	$highlight_docs         = get_option( 'relevanssi_highlight_docs' );
	$highlight_coms         = get_option( 'relevanssi_highlight_comments' );
	$show_matches           = get_option( 'relevanssi_show_matches' );
	$show_matches_text      = get_option( 'relevanssi_show_matches_text' );
	$word_boundaries        = get_option( 'relevanssi_word_boundaries' );
	$punctuation            = get_option( 'relevanssi_punctuation' );

	if ( '#' !== substr( $txt_col, 0, 1 ) ) {
		$txt_col = '#' . $txt_col;
	}
	$txt_col = relevanssi_sanitize_hex_color( $txt_col );
	if ( '#' !== substr( $bg_col, 0, 1 ) ) {
		$bg_col = '#' . $bg_col;
	}
	$bg_col = relevanssi_sanitize_hex_color( $bg_col );

	$excerpts              = relevanssi_check( $excerpts );
	$excerpt_custom_fields = relevanssi_check( $excerpt_custom_fields );
	$highlight_title       = relevanssi_check( $highlight_title );
	$highlight_docs        = relevanssi_check( $highlight_docs );
	$highlight_coms        = relevanssi_check( $highlight_coms );
	$show_matches          = relevanssi_check( $show_matches );
	$word_boundaries       = relevanssi_check( $word_boundaries );
	$excerpt_chars         = relevanssi_select( $excerpt_type, 'chars' );
	$excerpt_words         = relevanssi_select( $excerpt_type, 'words' );
	$highlight_none        = relevanssi_select( $highlight, 'no' );
	$highlight_mark        = relevanssi_select( $highlight, 'mark' );
	$highlight_em          = relevanssi_select( $highlight, 'em' );
	$highlight_strong      = relevanssi_select( $highlight, 'strong' );
	$highlight_col         = relevanssi_select( $highlight, 'col' );
	$highlight_bgcol       = relevanssi_select( $highlight, 'bgcol' );
	$highlight_style       = relevanssi_select( $highlight, 'style' );
	$highlight_class       = relevanssi_select( $highlight, 'class' );

	$show_matches_text = stripslashes( $show_matches_text );

	$txt_col_display = 'screen-reader-text';
	$bg_col_display  = 'screen-reader-text';
	$css_display     = 'screen-reader-text';
	$class_display   = 'screen-reader-text';

	if ( 'col' === $highlight ) {
		$txt_col_display = '';
	}
	if ( 'bgcol' === $highlight ) {
		$bg_col_display = '';
	}
	if ( 'style' === $highlight ) {
		$css_display = '';
	}
	if ( 'class' === $highlight ) {
		$class_display = '';
	}

	if ( isset( $synonyms ) ) {
		$synonyms = str_replace( ';', "\n", $synonyms );
	} else {
		$synonyms = '';
	}

	if ( RELEVANSSI_PREMIUM ) {
		$api_key            = get_option( 'relevanssi_api_key' );
		$disable_shortcodes = get_option( 'relevanssi_disable_shortcodes' );
		$hide_post_controls = get_option( 'relevanssi_hide_post_controls' );
		$server_location    = get_option( 'relevanssi_server_location' );
		$show_post_controls = get_option( 'relevanssi_show_post_controls' );
		$thousand_separator = get_option( 'relevanssi_thousand_separator' );

		$hide_post_controls = relevanssi_check( $hide_post_controls );
		$show_post_controls = relevanssi_check( $show_post_controls );
	}

	echo "<div class='postbox-container'>";
	echo "<form method='post'>";

	wp_nonce_field( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );

	$display_save_button = true;

	$active_tab = 'overview';
	if ( isset( $_REQUEST['tab'] ) ) { // WPCS: CSRF ok.
		$active_tab = $_REQUEST['tab']; // WPCS: CSRF ok. The value is printed once, but there it is escaped.
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
	<?php if ( function_exists( 'relevanssi_form_importexport' ) ) : ?>
	<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=importexport" class="nav-tab <?php echo 'importexport' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Import / Export options', 'relevanssi' ); ?></a>
	<?php endif; ?>
</h2>

<?php
if ( 'overview' === $active_tab ) :
	if ( ! RELEVANSSI_PREMIUM ) {
		$display_save_button = false;
	}
?>

	<h2><?php esc_html_e( 'Welcome to Relevanssi!', 'relevanssi' ); ?></h2>

	<table class="form-table">
<?php
if ( ! is_multisite() && function_exists( 'relevanssi_form_api_key' ) ) {
	relevanssi_form_api_key( $api_key );
}
if ( function_exists( 'relevanssi_form_hide_post_controls' ) ) {
	relevanssi_form_hide_post_controls( $hide_post_controls, $show_post_controls );
}
?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Getting started', 'relevanssi' ); ?></th>
		<td>
			<p><?php esc_html_e( "You've already installed Relevanssi. That's a great first step towards good search experience!", 'relevanssi' ); ?></p>
			<ol>
				<?php if ( 'done' !== get_option( 'relevanssi_indexed' ) ) : ?>
				<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
				<li><p><?php printf( esc_html__( 'Now, you need an index. Head over to the %1$s%2$s%3$s tab to set up the basic indexing options and to build the index.', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;tab=indexing'>", esc_html__( 'Indexing', 'relevanssi' ), '</a>' ); ?></p>
					<p><?php esc_html_e( 'You need to check at least the following options:', 'relevanssi' ); ?><br />
				– <?php esc_html_e( 'Make sure the post types you want to include in the index are indexed.', 'relevanssi' ); ?><br />
				<?php // Translators: %s is '_sku'. ?>
				– <?php printf( esc_html__( 'Do you use custom fields to store content you want included? If so, add those too. WooCommerce user? You probably want to include %s.', 'relevanssi' ), '<code>_sku</code>' ); ?></p>
					<p><?php esc_html_e( "Then just save the options and build the index. First time you have to do it manually, but after that, it's fully automatic: all changes are reflected in the index without reindexing. (That said, it's a good idea to rebuild the index once a year.)", 'relevanssi' ); ?></p>
				</li>
				<?php else : ?>
				<li><p><?php esc_html_e( 'Great, you already have an index!', 'relevanssi' ); ?></p></li>
				<?php endif; ?>
				<li>
					<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
					<p><?php printf( esc_html__( 'On the %1$s%2$s%3$s tab, choose whether you want the default operator to be AND (less results, but more precise) or OR (more results, less precise).', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;tab=searching'>", esc_html__( 'Searching', 'relevanssi' ), '</a>' ); ?></p>
				</li>
				<li>
				<?php // Translators: %1$s opens the link, %2$s is the anchor text, %3$s closes the link. ?>
					<p><?php printf( esc_html__( 'The next step is the %1$s%2$s%3$s tab, where you can enable the custom excerpts that show the relevant part of post in the search results pages.', 'relevanssi' ), "<a href='" . esc_attr( $this_page ) . "&amp;tab=excerpts'>", esc_html__( 'Excerpts and highlights', 'relevanssi' ), '</a>' ); ?></p>
					<p><?php esc_html_e( 'There are couple of options related to that, so if you want highlighting in the results, you can adjust the styles for that to suit the look of your site.', 'relevanssi' ); ?></p>
				</li>
				<li>
					<p><?php esc_html_e( "That's about it! Now you should have Relevanssi up and running. The rest of the options is mostly fine-tuning.", 'relevanssi' ); ?></p>
				</li>
			</ol>
			<p><?php esc_html_e( "Relevanssi doesn't have a separate search widget. Instead, Relevanssi uses the default search widget. Any standard search form will do!", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'For more information', 'relevanssi' ); ?></th>
		<td>
			<p><?php esc_html_e( "Relevanssi uses the WordPress contextual help. Click 'Help' on the top right corner for more information on many Relevanssi topics.", 'relevanssi' ); ?></p>
			<?php // Translators: %1$s opens the link, %2$s closes the link. ?>
			<p><?php printf( esc_html__( '%1$sRelevanssi knowledge base%2$s has lots of information about advanced Relevanssi use, including plenty of code samples.', 'relevanssi' ), "<a href='https://www.relevanssi.com/knowledge-base/'>", '</a>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Relevanssi on Facebook', 'relevanssi' ); ?>
		</th>
		<td>
			<p><a href="https://www.facebook.com/relevanssi"><?php esc_html_e( 'Check out the Relevanssi page on Facebook for news and updates about Relevanssi.', 'relevanssi' ); ?></a></p>
		</td>
	</tr>
	<?php if ( ! RELEVANSSI_PREMIUM ) { ?>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Buy Relevanssi Premium', 'relevanssi' ); ?>
		</th>
		<td>
			<p><a href="https://www.relevanssi.com/buy-premium"><?php esc_html_e( 'Buy Relevanssi Premium now', 'relevanssi' ); ?></a> –
			<?php // Translators: %1$s is the coupon code, %2$s is the year it expires. ?>
			<?php printf( esc_html__( 'use coupon code %1$s for 20%% discount (valid at least until the end of %2$s)', 'relevanssi' ), '<strong>FREE2018</strong>', '2018' ); ?></p>
			<p><?php esc_html_e( 'Here are some improvements Relevanssi Premium offers:', 'relevanssi' ); ?></p>
			<ul class="relevanssi_ul">
				<li><?php esc_html_e( 'PDF content indexing', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Index and search user profile pages', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Index and search taxonomy term pages', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Multisite searches across many subsites', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'WP CLI commands', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Adjust weights separately for each post type and taxonomy', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Internal link anchors can be search terms for the target posts', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Index and search any columns in the wp_posts database', 'relevanssi' ); ?></li>
				<li><?php esc_html_e( 'Hide Relevanssi branding from the User Searches page on a client installation', 'relevanssi' ); ?></li>
			</ul>
		</td>
	</tr>
	<?php } // End if ( ! RELEVANSSI_PREMIUM ). ?>
	</table>

	<?php endif; // Active tab: basic. ?>

	<?php
	if ( 'logging' === $active_tab ) {
		require_once 'tabs/logging-tab.php';
		relevanssi_logging_tab();
	}
	if ( 'searching' === $active_tab ) {
		require_once 'tabs/searching-tab.php';
		relevanssi_searching_tab();
	}
	?>

	<?php
	if ( 'excerpts' === $active_tab ) :
		$index_fields = get_option( 'relevanssi_index_fields' );
	?>

	<h2 id="excerpts"><?php esc_html_e( 'Custom excerpts/snippets', 'relevanssi' ); ?></h2>

	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_excerpts'><?php esc_html_e( 'Custom search result snippets', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Create custom search result snippets', 'relevanssi' ); ?></legend>
			<label >
				<input type='checkbox' name='relevanssi_excerpts' id='relevanssi_excerpts' <?php echo esc_html( $excerpts ); ?> />
				<?php esc_html_e( 'Create custom search result snippets', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Only enable this if you actually use the custom excerpts.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="tr_excerpt_length"
	<?php
	if ( empty( $excerpts ) ) {
		echo "class='relevanssi_disabled'";
	}
	?>
	>
		<th scope="row">
			<label for='relevanssi_excerpt_length'><?php esc_html_e( 'Length of the snippet', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_excerpt_length' id='relevanssi_excerpt_length' size='4' value='<?php echo esc_attr( $excerpt_length ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
			<select name='relevanssi_excerpt_type' id='relevanssi_excerpt_type'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			>
				<option value='chars' <?php echo esc_html( $excerpt_chars ); ?>><?php esc_html_e( 'characters', 'relevanssi' ); ?></option>
				<option value='words' <?php echo esc_html( $excerpt_words ); ?>><?php esc_html_e( 'words', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( "Using words is much faster than characters. Don't use characters, unless you have a really good reason and your posts are short.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="tr_excerpt_allowable_tags"
		<?php
		if ( empty( $excerpts ) ) {
			echo "class='relevanssi_disabled'";
		}
		?>
		>
		<th scope="row">
			<label for='relevanssi_excerpt_allowable_tags'><?php esc_html_e( 'Allowable tags in excerpts', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_excerpt_allowable_tags' id='relevanssi_excerpt_allowable_tags' size='60' value='<?php echo esc_attr( $excerpt_allowable_tags ); ?>' 
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
			<p class="description"><?php esc_html_e( 'List all tags you want to allow in excerpts. For example: &lt;p&gt;&lt;a&gt;&lt;strong&gt;.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="tr_excerpt_custom_fields"
		<?php
		if ( empty( $excerpts ) ) {
			echo "class='relevanssi_disabled'";
		}
		?>
		>
		<th scope="row">
			<label for='relevanssi_excerpt_custom_fields'><?php esc_html_e( 'Use custom fields for excerpts', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Use custom field content for building excerpts', 'relevanssi' ); ?></legend>
			<label>
				<input type='checkbox' name='relevanssi_excerpt_custom_fields' id='relevanssi_excerpt_custom_fields' <?php echo esc_html( $excerpt_custom_fields ); ?>
				<?php
				if ( empty( $excerpts ) || empty( $index_fields ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Use custom field content for building excerpts', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Use the custom fields setting for indexing for excerpt-making as well. Enabling this option will show custom field content in Relevanssi-generated excerpts.', 'relevanssi' ); ?>
		<?php
		if ( RELEVANSSI_PREMIUM ) {
			esc_html_e( 'Enable this option to use PDF content for excerpts.', 'relevanssi' );
		}
		?>
		</p>

		<p class="description"><?php esc_html_e( 'Current custom field setting', 'relevanssi' ); ?>: 
		<?php
		if ( 'visible' === $index_fields ) {
			esc_html_e( 'all visible custom fields', 'relevanssi' );
		} elseif ( 'all' === $index_fields ) {
			esc_html_e( 'all custom fields', 'relevanssi' );
		} elseif ( ! empty( $index_fields ) ) {
			printf( '<code>%s</code>', esc_html( $index_fields ) );
		} elseif ( RELEVANSSI_PREMIUM ) {
			esc_html_e( 'Just PDF content', 'relevanssi' );
		} else {
			esc_html_e( 'None selected', 'relevanssi' );
		}
		?>
		</p>
		</td>
	</tr>
	</table>

	<h2><?php esc_html_e( 'Search hit highlighting', 'relevanssi' ); ?></h2>

	<table id="relevanssi_highlighting" class="form-table
	<?php
	if ( empty( $excerpts ) ) {
		echo 'relevanssi_disabled';
	}
	?>
	">
	<tr>
		<th scope="row">
			<label for='relevanssi_highlight'><?php esc_html_e( 'Highlight type', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_highlight' id='relevanssi_highlight'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			>
				<option value='no' <?php echo esc_html( $highlight_none ); ?>><?php esc_html_e( 'No highlighting', 'relevanssi' ); ?></option>
				<option value='mark' <?php echo esc_html( $highlight_mark ); ?>>&lt;mark&gt;</option>
				<option value='em' <?php echo esc_html( $highlight_em ); ?>>&lt;em&gt;</option>
				<option value='strong' <?php echo esc_html( $highlight_strong ); ?>>&lt;strong&gt;</option>
				<option value='col' <?php echo esc_html( $highlight_col ); ?>><?php esc_html_e( 'Text color', 'relevanssi' ); ?></option>
				<option value='bgcol' <?php echo esc_html( $highlight_bgcol ); ?>><?php esc_html_e( 'Background color', 'relevanssi' ); ?></option>
				<option value='css' <?php echo esc_html( $highlight_style ); ?>><?php esc_html_e( 'CSS Style', 'relevanssi' ); ?></option>
				<option value='class' <?php echo esc_html( $highlight_class ); ?>><?php esc_html_e( 'CSS Class', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Requires custom snippets to work.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="relevanssi_txt_col" class='<?php echo esc_attr( $txt_col_display ); ?>'>
		<th scope="row">
			<label for="relevanssi_txt_col"><?php esc_html_e( 'Text color', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_txt_col' id='relevanssi_txt_col' size='7' class="color-field" data-default-color="#ff0000" value='<?php echo esc_attr( $txt_col ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
		</td>
	</tr>
	<tr id="relevanssi_bg_col" class=' <?php echo esc_attr( $bg_col_display ); ?>'>
		<th scope="row">
			<label for="relevanssi_bg_col"><?php esc_html_e( 'Background color', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_bg_col' id='relevanssi_bg_col' size='7' class="color-field" data-default-color="#ffaf75" value='<?php echo esc_attr( $bg_col ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
		</td>
	</tr>
	<tr id="relevanssi_css" class=' <?php echo esc_attr( $css_display ); ?>'>
		<th scope="row">
			<label for='relevanssi_css'><?php esc_html_e( 'CSS style for highlights', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_css' id='relevanssi_css' size='60' value='<?php echo esc_attr( $css ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
			<?php // Translators: %s is a <span> tag. ?>
			<p class="description"><?php printf( esc_html__( 'The highlights will be wrapped in a %s with this CSS in the style parameter.', 'relevanssi' ), '&lt;span&gt;' ); ?></p>
		</td>
	</tr>
	<tr id="relevanssi_class" class=' <?php echo esc_attr( $class_display ); ?>'>
		<th scope="row">
			<label for='relevanssi_class'><?php esc_html_e( 'CSS class for highlights', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_class' id='relevanssi_class' size='60' value='<?php echo esc_attr( $class ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
			<?php // Translators: %s is a <span> tag. ?>
			<p class="description"><?php printf( esc_html__( 'The highlights will be wrapped in a %s with this class.', 'relevanssi' ), '&lt;span&gt;' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_hilite_title'><?php esc_html_e( 'Highlight in titles', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Highlight query terms in titles', 'relevanssi' ); ?></legend>
			<label for='relevanssi_hilite_title'>
				<input type='checkbox' name='relevanssi_hilite_title' id='relevanssi_hilite_title' <?php echo esc_html( $highlight_title ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Highlight query terms in titles', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<?php // Translators: %1$s is 'the_title()', %2$s is 'relevanssi_the_title()'. ?>
		<p class="description"><?php printf( esc_html__( 'Highlights in titles require changes to the search results template. You need to replace %1$s in the search results template with %2$s. For more information, see the contextual help.', 'relevanssi' ), '<code>the_title()</code>', '<code>relevanssi_the_title()</code>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_highlight_docs'><?php esc_html_e( 'Highlight in documents', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Highlight query terms in documents', 'relevanssi' ); ?></legend>
			<label for='relevanssi_highlight_docs'>
				<input type='checkbox' name='relevanssi_highlight_docs' id='relevanssi_highlight_docs' <?php echo esc_html( $highlight_docs ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Highlight query terms in documents', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<?php // Translators: %s is 'highlight'. ?>
		<p class="description"><?php printf( esc_html__( 'Highlights hits when user opens the post from search results. This requires an extra parameter (%s) to the links from the search results pages, which Relevanssi should add automatically.', 'relevanssi' ), '<code>highlight</code>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_highlight_comments'><?php esc_html_e( 'Highlight in comments', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Highlight query terms in comments', 'relevanssi' ); ?></legend>
			<label for='relevanssi_highlight_comments'>
				<input type='checkbox' name='relevanssi_highlight_comments' id='relevanssi_highlight_comments' <?php echo esc_html( $highlight_coms ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Highlight query terms in comments', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Highlights hits in comments when user opens the post from search results.', 'relevanssi' ); ?></p>		
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_word_boundaries'><?php esc_html_e( 'Highlighting problems with non-ASCII alphabet?', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Uncheck this if you use non-ASCII characters', 'relevanssi' ); ?></legend>
			<label for='relevanssi_word_boundaries'>
				<input type='checkbox' name='relevanssi_word_boundaries' id='relevanssi_word_boundaries' <?php echo esc_html( $word_boundaries ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Uncheck this if you use non-ASCII characters', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( "If you use non-ASCII characters (like Cyrillic alphabet) and the highlights don't work, unchecking this option may make the highlights work.", 'relevanssi' ); ?></p>		
		</td>
	</tr>
	</table>

	<h2><?php esc_html_e( 'Breakdown of search results', 'relevanssi' ); ?></h2>

	<table id="relevanssi_breakdown" class="form-table
	<?php
	if ( empty( $excerpts ) ) {
		echo 'relevanssi_disabled';
	}
	?>
	">
	<tr>
		<th scope="row">
			<label for='relevanssi_show_matches'><?php esc_html_e( 'Breakdown of search hits in excerpts', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Show the breakdown of search hits in the excerpts', 'relevanssi' ); ?></legend>
			<label for='relevanssi_show_matches'>
				<input type='checkbox' name='relevanssi_show_matches' id='relevanssi_show_matches' <?php echo esc_html( $show_matches ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Show the breakdown of search hits in the excerpts.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Requires custom snippets to work.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_show_matches_text'><?php esc_html_e( 'The breakdown format', 'relevanssi' ); ?></label>
		</th>
		<td>
			<textarea name='relevanssi_show_matches_text' id='relevanssi_show_matches_text' cols="80" rows="4"
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			><?php echo esc_attr( $show_matches_text ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Use %body%, %title%, %tags% and %comments% to display the number of hits (in different parts of the post), %total% for total hits, %score% to display the document weight and %terms% to show how many hits each search term got.', 'relevanssi' ); /* phpcs:ignore WordPress.WP.I18n */ ?></p>
		</td>
	</tr>
	</table>

	<?php endif; // Active tab: excerpts & highlights. ?>

	<?php
	if ( 'indexing' === $active_tab ) {
		require_once 'tabs/indexing-tab.php';
		relevanssi_indexing_tab();
	}
	?>

	<?php if ( 'attachments' === $active_tab ) : ?>

	<?php
	if ( function_exists( 'relevanssi_form_attachments' ) ) {
		relevanssi_form_attachments();
	} else {
		$display_save_button = false;
	?>

	<h2><?php esc_html_e( 'Indexing attachment content', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( 'With Relevanssi Premium, you can index the text contents of attachments (PDFs, Word documents, Open Office documents and many other types). The contents of the attachments are processed on an external service, which makes the feature reliable and light on your own server performance.', 'relevanssi' ); ?></p>
	<?php // Translators: %1$s starts the link, %2$s closes it. ?>
	<p><?php printf( esc_html__( 'In order to access this and many other delightful Premium features, %1$sbuy Relevanssi Premium here%2$s.', 'relevanssi' ), '<a href="https://www.relevanssi.com/buy-premium/">', '</a>' ); ?></p>

	<?php } ?>

	<?php endif; // Active tab: attachments. ?>

	<?php if ( 'synonyms' === $active_tab ) : ?>

	<h3 id="synonyms"><?php esc_html_e( 'Synonyms', 'relevanssi' ); ?></h3>

	<table class="form-table">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Synonyms', 'relevanssi' ); ?>
		</th>
		<td>
			<p class="description"><?php esc_html_e( 'Add synonyms here to make the searches find better results. If you notice your users frequently misspelling a product name, or for other reasons use many names for one thing, adding synonyms will make the results better.', 'relevanssi' ); ?></p>

			<p class="description"><?php esc_html_e( "Do not go overboard, though, as too many synonyms can make the search confusing: users understand if a search query doesn't match everything, but they get confused if the searches match to unexpected things.", 'relevanssi' ); ?></p>
			<br />
			<textarea name='relevanssi_synonyms' id='relevanssi_synonyms' rows='9' cols='60'><?php echo esc_textarea( htmlspecialchars( $synonyms ) ); ?></textarea>

			<p class="description"><?php _e( 'The format here is <code>key = value</code>. If you add <code>dog = hound</code> to the list of synonyms, searches for <code>dog</code> automatically become a search for <code>dog hound</code> and will thus match to posts that include either <code>dog</code> or <code>hound</code>. This only works in OR searches: in AND searches the synonyms only restrict the search, as now the search only finds posts that contain <strong>both</strong> <code>dog</code> and <code>hound</code>.', 'relevanssi' ); // WPCS: XSS ok. ?></p>

			<p class="description"><?php _e( 'The synonyms are one direction only. If you want both directions, add the synonym again, reversed: <code>hound = dog</code>.', 'relevanssi' ); // WPCS: XSS ok. ?></p>

			<p class="description"><?php _e( "It's possible to use phrases for the value, but not for the key. <code>dog = \"great dane\"</code> works, but <code>\"great dane\" = dog</code> doesn't.", 'relevanssi' ); // WPCS: XSS ok. ?></p>

			<?php if ( RELEVANSSI_PREMIUM ) : ?>
				<p class="description"><?php esc_html_e( 'If you want to use synonyms in AND searches, enable synonym indexing on the Indexing tab.', 'relevanssi' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	</table>

	<?php endif; // Active tab: synonyms. ?>

	<?php if ( 'stopwords' === $active_tab ) : ?>

	<h3 id="stopwords"><?php esc_html_e( 'Stopwords', 'relevanssi' ); ?></h3>

	<?php relevanssi_show_stopwords(); ?>

	<?php
	/**
	 * Filters whether the common words list is displayed or not.
	 *
	 * The list of 25 most common words is displayed by default, but if the index is
	 * big, displaying the list can take a long time. This filter can be used to
	 * turn the list off.
	 *
	 * @param boolean If true, show the list; if false, don't show it.
	 */
	if ( apply_filters( 'relevanssi_display_common_words', true ) ) {
		relevanssi_common_words( 25 );
	}
	?>

	<?php endif; // Active tab: stopwords. ?>

	<?php if ( 'importexport' === $active_tab ) : ?>

	<?php
	if ( function_exists( 'relevanssi_form_importexport' ) ) {
		relevanssi_form_importexport();
	}
	?>

	<?php endif; ?>

	<?php if ( $display_save_button ) : ?>

	<input type='submit' name='submit' value='<?php esc_attr_e( 'Save the options', 'relevanssi' ); ?>' class='button button-primary' />

	<?php endif; ?>

	</form>
</div>

	<?php
}

/**
 * Displays a list of stopwords.
 *
 * Displays the list of stopwords and gives the controls for adding new stopwords.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_show_stopwords() {
	global $wpdb, $relevanssi_variables;

	$plugin = 'relevanssi';
	if ( RELEVANSSI_PREMIUM ) {
		$plugin = 'relevanssi-premium';
	}

	printf( '<p>%s</p>', esc_html__( 'Enter a word here to add it to the list of stopwords. The word will automatically be removed from the index, so re-indexing is not necessary. You can enter many words at the same time, separate words with commas.', 'relevanssi' ) );
?>
<table class="form-table">
<tr>
	<th scope="row">
		<label for="addstopword"><p><?php esc_html_e( 'Stopword(s) to add', 'relevanssi' ); ?>
	</th>
	<td>
		<textarea name="addstopword" id="addstopword" rows="2" cols="80"></textarea>
		<p><input type="submit" value="<?php esc_attr_e( 'Add', 'relevanssi' ); ?>" class='button' /></p>
	</td>
</tr>
</table>
<p><?php esc_html_e( "Here's a list of stopwords in the database. Click a word to remove it from stopwords. Removing stopwords won't automatically return them to index, so you need to re-index all posts after removing stopwords to get those words back to index.", 'relevanssi' ); ?></p>

<table class="form-table">
<tr>
	<th scope="row">
		<?php esc_html_e( 'Current stopwords', 'relevanssi' ); ?>
	</th>
	<td>
	<?php
	echo '<ul>';
	$results    = $wpdb->get_results( 'SELECT * FROM ' . $relevanssi_variables['stopword_table'] ); // WPCS: unprepared SQL ok, Relevanssi table name.
	$exportlist = array();
	foreach ( $results as $stopword ) {
		$sw = stripslashes( $stopword->stopword );
		printf( '<li style="display: inline;"><input type="submit" name="removestopword" value="%s"/></li>', esc_attr( $sw ) );
		array_push( $exportlist, $sw );
	}
	echo '</ul>';

	$exportlist = htmlspecialchars( implode( ', ', $exportlist ) );
?>
	<p><input type="submit" id="removeallstopwords" name="removeallstopwords" value="<?php esc_attr_e( 'Remove all stopwords', 'relevanssi' ); ?>" class='button' /></p>
	</td>
</tr>
<tr>
	<th scope="row">
		<?php esc_html_e( 'Exportable list of stopwords', 'relevanssi' ); ?>
	</th>
	<td>
		<textarea name="stopwords" id="stopwords" rows="2" cols="80"><?php echo esc_textarea( $exportlist ); ?></textarea>
		<p class="description"><?php esc_html_e( 'You can copy the list of stopwords here if you want to back up the list, copy it to a different blog or otherwise need the list.', 'relevanssi' ); ?></p>
	</td>
</tr>
</table>

<?php
}

/**
 * Displays the contextual help menu.
 *
 * @global object $wpdb The WP database interface.
 */
function relevanssi_admin_help() {
	global $wpdb;

	$screen = get_current_screen();
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-searching',
		'title'   => __( 'Searching', 'relevanssi' ),
		'content' => '<ul>' .
			// Translators: %1$s is 'orderby', %2$s is the Codex page URL.
			'<li>' . sprintf( __( "To adjust the post order, you can use the %1\$s query parameter. With %1\$s, you can use multiple layers of different sorting methods. See <a href='%2\$s'>WordPress Codex</a> for more details on using arrays for orderby.", 'relevanssi' ), '<code>orderby</code>', 'https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters' ) . '</li>' .
			'<li>' . __( "Inside-word matching is disabled by default, because it increases garbage results that don't really match the search term. If you want to enable it, add the following function to your theme functions.php:", 'relevanssi' ) .
			'<pre>add_filter( \'relevanssi_fuzzy_query\', \'rlv_partial_inside_words\' );
function rlv_partial_inside_words( $query ) {
    return "(term LIKE \'%#term#%\')"; 
}</pre></li>' .
			// Translators: %s is 'relevanssi_throttle_limit'.
			'<li>' . sprintf( __( 'In order to adjust the throttle limit, you can use the %s filter hook.', 'relevanssi' ), '<code>pre_option_relevanssi_throttle_limit</code>' ) .
			'<pre>add_filter( \'pre_option_relevanssi_throttle_limit\', function( $limit ) { return 200; } );</pre>' .
			'<li>' . __( "It's not usually necessary to adjust the limit from 500, but in some cases performance gains can be achieved by setting a lower limit. We don't suggest going under 200, as low values will make the results worse.", 'relevanssi' ) . '</li>' .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-search-restrictions',
		'title'   => __( 'Restrictions', 'relevanssi' ),
		'content' => '<ul>' .
			'<li>' . __( 'If you want the general search to target all posts, but have a single search form target only certain posts, you can add a hidden input variable to the search form. ', 'relevanssi' ) . '</li>' .
			'<li>' . __( 'For example in order to restrict the search to categories 10, 14 and 17, you could add this to the search form:', 'relevanssi' ) .
			'<pre>&lt;input type="hidden" name="cats" value="10,14,17" /&gt;</pre></li>' .
			'<li>' . __( 'To restrict the search to posts tagged with alfa AND beta, you could add this to the search form:', 'relevanssi' ) .
			'<pre>&lt;input type="hidden" name="tag" value="alfa+beta" /&gt;</pre></li>' .
			// Translators: %s is the link to the Codex page.
			'<li>' . sprintf( __( 'For all the possible options, see the Codex documentation for %s.', 'relevanssi' ), '<a href="https://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>' ) . '</li>' .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-search-exclusions',
		'title'   => __( 'Exclusions', 'relevanssi' ),
		'content' => '<ul>' .
			// Translators: %s is the link to the Codex page.
			'<li>' . sprintf( __( 'For more exclusion options, see the Codex documentation for %s. For example, to exclude tag ID 10, use', 'relevanssi' ), '<a href="https://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>' ) .
			'<pre>&lt;input type="hidden" name="tag__not_in" value="10" /&gt;</pre></li>' .
			// Translators: %s is 'relevanssi_do_not_index'.
			'<li>' . sprintf( __( 'To exclude posts from the index and not just from the search, you can use the %s filter hook. This would not index posts that have a certain taxonomy term:', 'relevanssi' ), '<code>relevanssi_do_not_index</code>' ) .
			'<pre>add_filter( \'relevanssi_do_not_index\', \'rlv_index_filter\', 10, 2 );
function rlv_index_filter( $block, $post_id ) {
	if ( has_term( \'jazz\', \'genre\', $post_id ) ) {
		$block = true;
	}
	return $block;
}
</pre></li>' .
			// Translators: %s is a link to the Relevanssi knowledge base.
			'<li>' . sprintf( __( "For more examples, see <a href='%s'>the related knowledge base posts</a>.", 'relevanssi' ), 'https://www.relevanssi.com/tag/relevanssi_do_not_index/' ) . '</li>' .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-logging',
		'title'   => __( 'Logs', 'relevanssi' ),
		'content' => '<ul>' .
			// Translators: %s is 'relevanssi_user_searches_limit'.
			'<li>' . sprintf( __( 'By default, the User searches page shows 20 most common keywords. In order to see more, you can adjust the value with the %s filter hook, like this:', 'relevanssi' ), '<code>relevanssi_user_searches_limit</code>' ) .
			"<pre>add_filter( 'relevanssi_user_searches_limit', function() { return 50; } );</pre></li>" .
			// Translators: %s is the name of the database table.
			'<li>' . sprintf( __( 'The complete logs are stored in the %s database table, where you can access them if you need more information than what the User searches page provides.', 'relevanssi' ), '<code>' . $wpdb->prefix . 'relevanssi_log</code>' ) . '</li>' .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-excerpts',
		'title'   => __( 'Excerpts', 'relevanssi' ),
		'content' => '<ul>' .
			'<li>' . __( 'Building custom excerpts can be slow. If you are not actually using the excerpts, make sure you disable the option.', 'relevanssi' ) . '</li>' .
			// Translators: %s is 'the_excerpt()'.
			'<li>' . sprintf( __( 'Custom snippets require that the search results template uses %s to print out the excerpts.', 'relevanssi' ), '<code>the_excerpt()</code>' ) . '</li>' .
			'<li>' . __( 'Generally, Relevanssi generates the excerpts from post content. If you want to include custom field content in the excerpt-building, this can be done with a simple setting from the excerpt settings.', 'relevanssi' ) . '</li>' .
			// Translators: %1$s is 'relevanssi_pre_excerpt_content', %2$s is 'relevanssi_excerpt_content'.
			'<li>' . sprintf( __( 'If you want more control over what content Relevanssi uses to create the excerpts, you can use the %1$s and %2$s filter hooks to adjust the content.', 'relevanssi' ), '<code>relevanssi_pre_excerpt_content</code>', '<code>relevanssi_excerpt_content</code>' ) . '</li>' .
			// Translators: %s is 'relevanssi_disable_shortcodes_excerpt'.
			'<li>' . sprintf( __( 'Some shortcode do not work well with Relevanssi excerpt-generation. Relevanssi disables some shortcodes automatically to prevent problems. This can be adjusted with the %s filter hook.', 'relevanssi' ), '<code>relevanssi_disable_shortcodes_excerpt</code>' ) . '</li>' .
			// Translators: %s is 'relevanssi_optimize_excerpts'.
			'<li>' . sprintf( __( "If you want Relevanssi to build excerpts faster and don't mind that they may be less than perfect in quality, add a filter that returns true on hook %s.", 'relevanssi' ), '<code>relevanssi_optimize_excerpts</code>' ) .
			"<pre>add_filter( 'relevanssi_optimize_excerpts', '__return_true' );</pre></li>" .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-highlights',
		'title'   => __( 'Highlights', 'relevanssi' ),
		'content' => '<ul>' .
			'<li>' . __( "Title highlights don't appear automatically, because that led to problems with highlights appearing in wrong places and messing up navigation menus, for example.", 'relevanssi' ) . '</li>' .
			// Translators: %1$s is 'the_title()', %2$s is 'relevanssi_the_title()'.
			'<li>' . sprintf( __( 'In order to see title highlights from Relevanssi, replace %1$s in the search results template with %2$s. It does the same thing, but supports Relevanssi title highlights.', 'relevanssi' ), '<code>the_title()</code>', '<code>relevanssi_the_title()</code>' ) . '</li>' .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-punctuation',
		'title'   => __( 'Punctuation', 'relevanssi' ),
		'content' => '<ul>' .
			'<li>' . __( 'Relevanssi removes punctuation. Some punctuation is removed, some replaced with spaces. Advanced indexing settings include some of the more common settings people want to change.', 'relevanssi' ) . '</li>' .
			// Translators: %1$s is 'relevanssi_punctuation_filter', %2$s is 'relevanssi_remove_punctuation'.
			'<li>' . sprintf( __( 'For more fine-tuned changes, you can use %1$s filter hook to adjust what is replaced with what, and %2$s filter hook to completely override the default punctuation control.', 'relevanssi' ), '<code>relevanssi_punctuation_filter</code>', '<code>relevanssi_remove_punctuation</code>' ) . '</li>' .
			// Translators: %s is the URL to the Knowledge Base entry.
			'<li>' . sprintf( __( "For more examples, see <a href='%s'>the related knowledge base posts</a>.", 'relevanssi' ), 'https://www.relevanssi.com/tag/relevanssi_remove_punct/' ) . '</li>' .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-helpful-shortcodes',
		'title'   => __( 'Helpful shortcodes', 'relevanssi' ),
		'content' => '<ul>' .
			// Translators: %s is '[noindex]'.
			'<li>' . sprintf( __( "If you have content that you don't want indexed, you can wrap that content in a %s shortcode.", 'relevanssi' ), '<code>[noindex]</code>' ) . '</li>' .
			// Translators: %s is '[searchform]'.
			'<li>' . sprintf( __( 'If you need a search form on some page on your site, you can use the %s shortcode to print out a basic search form.', 'relevanssi' ), '<code>[searchform]</code>' ) . '</li>' .
			// Translators: %1$s is '[searchform post_types="page"]', %2$s is '[searchform cats="10,14,17"]'.
			'<li>' . sprintf( __( 'If you need to add query variables to the search form, the shortcode takes parameters, which are then printed out as hidden input fields. To get a search form with a post type restriction, you can use %1$s. To restrict the search to categories 10, 14 and 17, you can use %2$s and so on.', 'relevanssi' ), '<code>[searchform post_types="page"]</code>', '<code>[searchform cats="10,14,17"]</code>' ) . '</li>' .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-title-woocommerce',
		'title'   => __( 'WooCommerce', 'relevanssi' ),
		'content' => '<ul>' .
			'<li>' . __( "If your SKUs include hyphens or other punctuation, do note that Relevanssi replaces most punctuation with spaces. That's going to cause issues with SKU searches.", 'relevanssi' ) . '</li>' .
			// Translators: %s is the Knowledge Base URL.
			'<li>' . sprintf( __( "For more details how to fix that issue, see <a href='%s'>WooCommerce tips in Relevanssi user manual</a>.", 'relevanssi' ), 'https://www.relevanssi.com/user-manual/woocommerce/' ) . '</li>' .
			'</ul>',
	));
	$screen->add_help_tab( array(
		'id'      => 'relevanssi-exact-match',
		'title'   => __( 'Exact match bonus', 'relevanssi' ),
		'content' => '<ul>' .
			// Translators: %s is the name of the filter hook.
			'<li>' . sprintf( __( 'To adjust the amount of the exact match bonus, you can use the %s filter hook. It works like this:', 'relevanssi' ), '<code>relevanssi_exact_match_bonus</code>' ) .
			"<pre>add_filter( 'relevanssi_exact_match_bonus', 'rlv_adjust_bonus' );
function rlv_adjust_bonus( \$bonus ) {
	return array( 'title' => 10, 'content' => 5 );
}</li>" .
			// Translators: %1$s is the title weight and %2$s is the content weight.
			'<li>' . sprintf( esc_html__( 'The default values are %1$s for titles and %2$s for content.', 'relevanssi' ), '<code>5</code>', '<code>2</code>' ) . '</ul>',
	));
	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'relevanssi' ) . '</strong></p>' .
		'<p><a href="https://www.relevanssi.com/knowledge-base/" target="_blank">' . __( 'Plugin knowledge base', 'relevanssi' ) . '</a></p>' .
		'<p><a href="https://wordpress.org/tags/relevanssi?forum_id=10" target="_blank">' . __( 'WordPress.org forum', 'relevanssi' ) . '</a></p>'
	);
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
	);
	if ( ! in_array( $hook, $acceptable_hooks, true ) ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'relevanssi_admin_js', $plugin_dir_url . 'lib/admin_scripts.js', array( 'wp-color-picker' ) );
	if ( ! RELEVANSSI_PREMIUM ) {
		wp_enqueue_script( 'relevanssi_admin_js_free', $plugin_dir_url . 'lib/admin_scripts_free.js', array( 'relevanssi_admin_js' ) );
	}
	if ( RELEVANSSI_PREMIUM ) {
		wp_enqueue_script( 'relevanssi_admin_js_premium', $plugin_dir_url . 'premium/admin_scripts_premium.js', array( 'relevanssi_admin_js' ) );
	}
	wp_enqueue_style( 'relevanssi_admin_css', $plugin_dir_url . 'lib/admin_styles.css' );

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
		'indexing_nonce' => wp_create_nonce( 'relevanssi_indexing_nonce' ),
	);

	wp_localize_script( 'relevanssi_admin_js', 'nonce', $nonce );

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
