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
 * Adds a stopword to the list of stopwords.
 *
 * @global object $wpdb The WP database interface.
 *
 * @param string  $term    The stopword that is added.
 * @param boolean $verbose If true, print out notice. If false, be silent. Default
 * true.
 *
 * @return boolean True, if success; false otherwise.
 */
function relevanssi_add_stopword( $term, $verbose = true ) {
	global $wpdb;
	if ( empty( $term ) ) {
		return;
	}

	$n = 0;
	$s = 0;

	$terms = explode( ',', $term );
	if ( count( $terms ) > 1 ) {
		foreach ( $terms as $term ) {
			$n++;
			$term    = trim( $term );
			$success = relevanssi_add_single_stopword( $term );
			if ( $success ) {
				$s++;
			}
		}
		if ( $verbose ) {
			// translators: %1$d is the successful entries, %2$d is the total entries.
			printf( "<div id='message' class='updated fade'><p>%s</p></div>", sprintf( esc_html__( 'Successfully added %1$d/%2$d terms to stopwords!', 'relevanssi' ), intval( $s ), intval( $n ) ) );
		}
	} else {
		// Add to stopwords.
		$success = relevanssi_add_single_stopword( $term );

		$term = stripslashes( $term );
		$term = esc_html( $term );
		if ( $verbose ) {
			if ( $success ) {
				// Translators: %s is the stopword.
				printf( "<div id='message' class='updated fade'><p>%s</p></div>", sprintf( esc_html__( "Term '%s' added to stopwords!", 'relevanssi' ), esc_html( stripslashes( $term ) ) ) );
			} else {
				// Translators: %s is the stopword.
				printf( esc_html__( "<div id='message' class='updated fade'><p>Couldn't add term '%s' to stopwords!</p></div>", 'relevanssi' ), esc_html( stripslashes( $term ) ) );
			}
		}
	}

	return $success;
}

/**
 * Adds a single stopword to the stopword table.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables.
 *
 * @param string $term The term to add.
 *
 * @return boolean True if success, false if not.
 */
function relevanssi_add_single_stopword( $term ) {
	global $wpdb, $relevanssi_variables;
	if ( empty( $term ) ) {
		return false;
	}

	$term = stripslashes( $term );
	$term = esc_sql( $wpdb->esc_like( $term ) );

	$success = $wpdb->query( $wpdb->prepare( 'INSERT INTO ' . $relevanssi_variables['stopword_table'] . ' (stopword) VALUES (%s)', $term ) ); // WPCS: unprepared SQL ok, Relevanssi table name.

	if ( $success ) {
		// Remove from index.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE term=%s', $term ) ); // WPCS: unprepared SQL ok, Relevanssi table name.
		return true;
	} else {
		return false;
	}
}

/**
 * Removes all stopwords.
 *
 * Truncates the wp_relevanssi_stopwords database table.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables.
 */
function relevanssi_remove_all_stopwords() {
	global $wpdb, $relevanssi_variables;

	$success = $wpdb->query( 'TRUNCATE ' . $relevanssi_variables['stopword_table'] ); // WPCS: unprepared SQL ok, Relevanssi table name.

	if ( $success ) {
		printf( "<div id='message' class='updated fade'><p>%s</p></div>", esc_html__( 'All stopwords removed! Remember to re-index.', 'relevanssi' ) );
	} else {
		printf( "<div id='message' class='updated fade'><p>%s</p></div>", esc_html__( "There was a problem, and stopwords couldn't be removed.", 'relevanssi' ) );
	}
}

/**
 * Removes a single stopword.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables.
 *
 * @param string  $term    The stopword to remove.
 * @param boolean $verbose If true, print out a notice. Default true.
 *
 * @return boolean True if success, false if not.
 */
function relevanssi_remove_stopword( $term, $verbose = true ) {
	global $wpdb, $relevanssi_variables;

	$success = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $relevanssi_variables['stopword_table'] . ' WHERE stopword=%s', $term ) ); // WPCS: unprepared SQL ok, Relevanssi table name.

	if ( $success ) {
		if ( $verbose ) {
			// Translators: %s is the stopword.
			printf( "<div id='message' class='updated fade'><p>%s</p></div>", sprintf( esc_html__( "Term '%s' removed from stopwords! Re-index to get it back to index.", 'relevanssi' ), esc_html( stripslashes( $term ) ) ) );
		}
		return true;
	} else {
		if ( $verbose ) {
			// Translators: %s is the stopword.
			printf( "<div id='message' class='updated fade'><p>%s</p></div>", sprintf( esc_html__( "Couldn't remove term '%s' from stopwords!", 'relevanssi' ), esc_html( stripslashes( $term ) ) ) );
		}
		return false;
	}
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

	$serialize_options = array();

	$content_boost          = get_option( 'relevanssi_content_boost' );
	$title_boost            = get_option( 'relevanssi_title_boost' );
	$comment_boost          = get_option( 'relevanssi_comment_boost' );
	$admin_search           = get_option( 'relevanssi_admin_search' );
	$index_limit            = get_option( 'relevanssi_index_limit' );
	$excerpts               = get_option( 'relevanssi_excerpts' );
	$excerpt_length         = get_option( 'relevanssi_excerpt_length' );
	$excerpt_type           = get_option( 'relevanssi_excerpt_type' );
	$excerpt_allowable_tags = get_option( 'relevanssi_excerpt_allowable_tags' );
	$excerpt_custom_fields  = get_option( 'relevanssi_excerpt_custom_fields' );
	$log_queries            = get_option( 'relevanssi_log_queries' );
	$log_queries_with_ip    = get_option( 'relevanssi_log_queries_with_ip' );
	$trim_logs              = get_option( 'relevanssi_trim_logs' );
	$hide_branding          = get_option( 'relevanssi_hide_branding' );
	$highlight              = get_option( 'relevanssi_highlight' );
	$index_fields           = get_option( 'relevanssi_index_fields' );
	$txt_col                = get_option( 'relevanssi_txt_col' );
	$bg_col                 = get_option( 'relevanssi_bg_col' );
	$css                    = get_option( 'relevanssi_css' );
	$class                  = get_option( 'relevanssi_class' );
	$cat                    = get_option( 'relevanssi_cat' );
	$excat                  = get_option( 'relevanssi_excat' );
	$fuzzy                  = get_option( 'relevanssi_fuzzy' );
	$implicit               = get_option( 'relevanssi_implicit_operator' );
	$expand_shortcodes      = get_option( 'relevanssi_expand_shortcodes' );
	$disable_or_fallback    = get_option( 'relevanssi_disable_or_fallback' );
	$throttle               = get_option( 'relevanssi_throttle' );
	$throttle_limit         = get_option( 'relevanssi_throttle_limit' );
	$omit_from_logs         = get_option( 'relevanssi_omit_from_logs' );
	$synonyms               = get_option( 'relevanssi_synonyms' );
	$exclude_posts          = get_option( 'relevanssi_exclude_posts' );
	$highlight_title        = get_option( 'relevanssi_hilite_title' );
	$index_comments         = get_option( 'relevanssi_index_comments' );
	$highlight_docs         = get_option( 'relevanssi_highlight_docs' );
	$highlight_coms         = get_option( 'relevanssi_highlight_comments' );
	$respect_exclude        = get_option( 'relevanssi_respect_exclude' );
	$min_word_length        = get_option( 'relevanssi_min_word_length' );
	$index_author           = get_option( 'relevanssi_index_author' );
	$index_excerpt          = get_option( 'relevanssi_index_excerpt' );
	$show_matches           = get_option( 'relevanssi_show_matches' );
	$show_matches_text      = get_option( 'relevanssi_show_matches_text' );
	$wpml_only_current      = get_option( 'relevanssi_wpml_only_current' );
	$polylang_allow_all     = get_option( 'relevanssi_polylang_all_languages' );
	$word_boundaries        = get_option( 'relevanssi_word_boundaries' );
	$post_type_weights      = get_option( 'relevanssi_post_type_weights' );
	$index_post_types       = get_option( 'relevanssi_index_post_types' );
	$index_taxonomies_list  = get_option( 'relevanssi_index_taxonomies_list' );
	$orderby                = get_option( 'relevanssi_default_orderby' );
	$punctuation            = get_option( 'relevanssi_punctuation' );
	$exact_match_bonus      = get_option( 'relevanssi_exact_match_bonus' );

	if ( '#' !== substr( $txt_col, 0, 1 ) ) {
		$txt_col = '#' . $txt_col;
	}
	$txt_col = relevanssi_sanitize_hex_color( $txt_col );
	if ( '#' !== substr( $bg_col, 0, 1 ) ) {
		$bg_col = '#' . $bg_col;
	}
	$bg_col = relevanssi_sanitize_hex_color( $bg_col );

	if ( empty( $index_post_types ) ) {
		$index_post_types = array();
	}
	if ( empty( $index_taxonomies_list ) ) {
		$index_taxonomies_list = array();
	}

	$serialize_options['relevanssi_content_boost']          = $content_boost;
	$serialize_options['relevanssi_title_boost']            = $title_boost;
	$serialize_options['relevanssi_comment_boost']          = $comment_boost;
	$serialize_options['relevanssi_admin_search']           = $admin_search;
	$serialize_options['relevanssi_index_limit']            = $index_limit;
	$serialize_options['relevanssi_excerpts']               = $excerpts;
	$serialize_options['relevanssi_excerpt_length']         = $excerpt_length;
	$serialize_options['relevanssi_excerpt_type']           = $excerpt_type;
	$serialize_options['relevanssi_excerpt_allowable_tags'] = $excerpt_allowable_tags;
	$serialize_options['relevanssi_excerpt_custom_fields']  = $excerpt_custom_fields;
	$serialize_options['relevanssi_log_queries']            = $log_queries;
	$serialize_options['relevanssi_log_queries_with_ip']    = $log_queries_with_ip;
	$serialize_options['relevanssi_trim_logs']              = $trim_logs;
	$serialize_options['relevanssi_hide_branding']          = $hide_branding;
	$serialize_options['relevanssi_highlight']              = $highlight;
	$serialize_options['relevanssi_index_fields']           = $index_fields;
	$serialize_options['relevanssi_txt_col']                = $txt_col;
	$serialize_options['relevanssi_bg_col']                 = $bg_col;
	$serialize_options['relevanssi_css']                    = $css;
	$serialize_options['relevanssi_class']                  = $class;
	$serialize_options['relevanssi_cat']                    = $cat;
	$serialize_options['relevanssi_excat']                  = $excat;
	$serialize_options['relevanssi_fuzzy']                  = $fuzzy;
	$serialize_options['relevanssi_implicit_operator']      = $implicit;
	$serialize_options['relevanssi_expand_shortcodes']      = $expand_shortcodes;
	$serialize_options['relevanssi_disable_or_fallback']    = $disable_or_fallback;
	$serialize_options['relevanssi_throttle']               = $throttle;
	$serialize_options['relevanssi_throttle_limit']         = $throttle_limit;
	$serialize_options['relevanssi_omit_from_logs']         = $omit_from_logs;
	$serialize_options['relevanssi_synonyms']               = $synonyms;
	$serialize_options['relevanssi_exclude_posts']          = $exclude_posts;
	$serialize_options['relevanssi_hilite_title']           = $highlight_title;
	$serialize_options['relevanssi_index_comments']         = $index_comments;
	$serialize_options['relevanssi_highlight_docs']         = $highlight_docs;
	$serialize_options['relevanssi_highlight_comments']     = $highlight_coms;
	$serialize_options['relevanssi_respect_exclude']        = $respect_exclude;
	$serialize_options['relevanssi_min_word_length']        = $min_word_length;
	$serialize_options['relevanssi_index_author']           = $index_author;
	$serialize_options['relevanssi_index_excerpt']          = $index_excerpt;
	$serialize_options['relevanssi_show_matches']           = $show_matches;
	$serialize_options['relevanssi_show_matches_text']      = $show_matches_text;
	$serialize_options['relevanssi_wpml_only_current']      = $wpml_only_current;
	$serialize_options['relevanssi_polylang_all_languages'] = $polylang_allow_all;
	$serialize_options['relevanssi_word_boundaries']        = $word_boundaries;
	$serialize_options['relevanssi_post_type_weights']      = $post_type_weights;
	$serialize_options['relevanssi_index_post_types']       = $index_post_types;
	$serialize_options['relevanssi_index_taxonomies_list']  = $index_taxonomies_list;
	$serialize_options['relevanssi_default_orderby']        = $orderby;
	$serialize_options['relevanssi_punctuation']            = $punctuation;
	$serialize_options['relevanssi_exact_match_bonus']      = $exact_match_bonus;

	$admin_search          = relevanssi_check( $admin_search );
	$excerpts              = relevanssi_check( $excerpts );
	$excerpt_custom_fields = relevanssi_check( $excerpt_custom_fields );
	$log_queries           = relevanssi_check( $log_queries );
	$log_queries_with_ip   = relevanssi_check( $log_queries_with_ip );
	$hide_branding         = relevanssi_check( $hide_branding );
	$expand_shortcodes     = relevanssi_check( $expand_shortcodes );
	$disable_or_fallback   = relevanssi_check( $disable_or_fallback );
	$throttle              = relevanssi_check( $throttle );
	$highlight_title       = relevanssi_check( $highlight_title );
	$highlight_docs        = relevanssi_check( $highlight_docs );
	$highlight_coms        = relevanssi_check( $highlight_coms );
	$respect_exclude       = relevanssi_check( $respect_exclude );
	$index_author          = relevanssi_check( $index_author );
	$index_excerpt         = relevanssi_check( $index_excerpt );
	$show_matches          = relevanssi_check( $show_matches );
	$wpml_only_current     = relevanssi_check( $wpml_only_current );
	$polylang_allow_all    = relevanssi_check( $polylang_allow_all );
	$word_boundaries       = relevanssi_check( $word_boundaries );
	$exact_match_bonus     = relevanssi_check( $exact_match_bonus );

	$excerpt_chars         = relevanssi_select( $excerpt_type, 'chars' );
	$excerpt_words         = relevanssi_select( $excerpt_type, 'words' );
	$fuzzy_sometimes       = relevanssi_select( $fuzzy, 'sometimes' );
	$fuzzy_always          = relevanssi_select( $fuzzy, 'always' );
	$fuzzy_never           = relevanssi_select( $fuzzy, 'never' );
	$highlight_none        = relevanssi_select( $highlight, 'no' );
	$highlight_mark        = relevanssi_select( $highlight, 'mark' );
	$highlight_em          = relevanssi_select( $highlight, 'em' );
	$highlight_strong      = relevanssi_select( $highlight, 'strong' );
	$highlight_col         = relevanssi_select( $highlight, 'col' );
	$highlight_bgcol       = relevanssi_select( $highlight, 'bgcol' );
	$highlight_style       = relevanssi_select( $highlight, 'style' );
	$highlight_class       = relevanssi_select( $highlight, 'class' );
	$implicit_and          = relevanssi_select( $implicit, 'AND' );
	$implicit_or           = relevanssi_select( $implicit, 'OR' );
	$index_comments_all    = relevanssi_select( $index_comments, 'all' );
	$index_comments_normal = relevanssi_select( $index_comments, 'normal' );
	$index_comments_none   = relevanssi_select( $index_comments, 'none' );
	$orderby_relevance     = relevanssi_select( $orderby, 'relevance' );
	$orderby_date          = relevanssi_select( $orderby, 'post_date' );

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

	$orfallback_visibility = 'screen-reader-text';
	if ( 'AND' === $implicit ) {
		$orfallback_visibility = '';
	}

	$fields_select_all     = '';
	$fields_select_none    = '';
	$fields_select_some    = 'selected';
	$fields_select_visible = '';
	$original_index_fields = $index_fields;

	if ( empty( $index_fields ) ) {
		$fields_select_none = 'selected';
		$fields_select_some = '';
	}
	if ( 'all' === $index_fields ) {
		$fields_select_all  = 'selected';
		$fields_select_some = '';
		$index_fields       = '';
	}
	if ( 'visible' === $index_fields ) {
		$fields_select_visible = 'selected';
		$fields_select_some    = '';
		$index_fields          = '';
	}

	if ( isset( $synonyms ) ) {
		$synonyms = str_replace( ';', "\n", $synonyms );
	} else {
		$synonyms = '';
	}

	if ( ! isset( $punctuation['quotes'] ) ) {
		$punctuation['quotes'] = 'replace';
	}
	if ( ! isset( $punctuation['decimals'] ) ) {
		$punctuation['decimals'] = 'remove';
	}
	if ( ! isset( $punctuation['ampersands'] ) ) {
		$punctuation['ampersands'] = 'replace';
	}
	if ( ! isset( $punctuation['hyphens'] ) ) {
		$punctuation['hyphens'] = 'replace';
	}
	$punct_quotes_replace     = relevanssi_select( $punctuation['quotes'], 'replace' );
	$punct_quotes_remove      = relevanssi_select( $punctuation['quotes'], 'remove' );
	$punct_decimals_replace   = relevanssi_select( $punctuation['decimals'], 'replace' );
	$punct_decimals_remove    = relevanssi_select( $punctuation['decimals'], 'remove' );
	$punct_decimals_keep      = relevanssi_select( $punctuation['decimals'], 'keep' );
	$punct_ampersands_replace = relevanssi_select( $punctuation['ampersands'], 'replace' );
	$punct_ampersands_remove  = relevanssi_select( $punctuation['ampersands'], 'remove' );
	$punct_ampersands_keep    = relevanssi_select( $punctuation['ampersands'], 'keep' );
	$punct_hyphens_replace    = relevanssi_select( $punctuation['hyphens'], 'replace' );
	$punct_hyphens_remove     = relevanssi_select( $punctuation['hyphens'], 'remove' );
	$punct_hyphens_keep       = relevanssi_select( $punctuation['hyphens'], 'keep' );

	if ( RELEVANSSI_PREMIUM ) {
		$api_key             = get_option( 'relevanssi_api_key' );
		$link_boost          = get_option( 'relevanssi_link_boost' );
		$internal_links      = get_option( 'relevanssi_internal_links' );
		$highlight_docs_ext  = get_option( 'relevanssi_highlight_docs_external' );
		$thousand_separator  = get_option( 'relevanssi_thousand_separator' );
		$disable_shortcodes  = get_option( 'relevanssi_disable_shortcodes' );
		$index_users         = get_option( 'relevanssi_index_users' );
		$index_user_fields   = get_option( 'relevanssi_index_user_fields' );
		$index_subscribers   = get_option( 'relevanssi_index_subscribers' );
		$index_synonyms      = get_option( 'relevanssi_index_synonyms' );
		$index_taxonomies    = get_option( 'relevanssi_index_taxonomies' );
		$index_terms         = get_option( 'relevanssi_index_terms' );
		$hide_post_controls  = get_option( 'relevanssi_hide_post_controls' );
		$show_post_controls  = get_option( 'relevanssi_show_post_controls' );
		$recency_bonus_array = get_option( 'relevanssi_recency_bonus' );
		$searchblogs         = get_option( 'relevanssi_searchblogs' );
		$searchblogs_all     = get_option( 'relevanssi_searchblogs_all' );
		$mysql_columns       = get_option( 'relevanssi_mysql_columns' );
		$index_pdf_parent    = get_option( 'relevanssi_index_pdf_parent' );
		$server_location     = get_option( 'relevanssi_server_location' );

		if ( empty( $index_terms ) ) {
			$index_terms = array();
		}

		$serialize_options['relevanssi_link_boost']              = $link_boost;
		$serialize_options['relevanssi_api_key']                 = $api_key;
		$serialize_options['relevanssi_internal_links']          = $internal_links;
		$serialize_options['relevanssi_highlight_docs_external'] = $highlight_docs_ext;
		$serialize_options['relevanssi_thousand_separator']      = $thousand_separator;
		$serialize_options['relevanssi_disable_shortcodes']      = $disable_shortcodes;
		$serialize_options['relevanssi_index_users']             = $index_users;
		$serialize_options['relevanssi_index_user_fields']       = $index_user_fields;
		$serialize_options['relevanssi_index_subscribers']       = $index_subscribers;
		$serialize_options['relevanssi_index_synonyms']          = $index_synonyms;
		$serialize_options['relevanssi_index_taxonomies']        = $index_taxonomies;
		$serialize_options['relevanssi_index_terms']             = $index_terms;
		$serialize_options['relevanssi_hide_post_controls']      = $hide_post_controls;
		$serialize_options['relevanssi_show_post_controls']      = $show_post_controls;
		$serialize_options['recency_bonus']                      = $recency_bonus_array;
		$serialize_options['relevanssi_searchblogs']             = $searchblogs;
		$serialize_options['relevanssi_searchblogs_all']         = $searchblogs_all;
		$serialize_options['relevanssi_mysql_columns']           = $mysql_columns;
		$serialize_options['relevanssi_index_pdf_parent']        = $index_pdf_parent;
		$serialize_options['relevanssi_server_location']         = get_option( 'relevanssi_server_location' );
		$serialize_options['relevanssi_send_pdf_files']          = get_option( 'relevanssi_send_pdf_files' );
		$serialize_options['relevanssi_read_new_files']          = get_option( 'relevanssi_read_new_files' );
		$serialize_options['relevanssi_link_pdf_files']          = get_option( 'relevanssi_link_pdf_files' );

		$highlight_docs_ext = relevanssi_check( $highlight_docs_ext );
		$index_users        = relevanssi_check( $index_users );
		$index_subscribers  = relevanssi_check( $index_subscribers );
		$index_synonyms     = relevanssi_check( $index_synonyms );
		$index_taxonomies   = relevanssi_check( $index_taxonomies );
		$hide_post_controls = relevanssi_check( $hide_post_controls );
		$show_post_controls = relevanssi_check( $show_post_controls );
		$searchblogs_all    = relevanssi_check( $searchblogs_all );
		$index_pdf_parent   = relevanssi_check( $index_pdf_parent );

		$internal_links_strip   = relevanssi_select( $internal_links, 'strip' );
		$internal_links_nostrip = relevanssi_select( $internal_links, 'nostrip' );
		$internal_links_noindex = relevanssi_select( $internal_links, 'noindex' );

		$recency_bonus      = $recency_bonus_array['bonus'];
		$recency_bonus_days = $recency_bonus_array['days'];

		$serialized_options = wp_json_encode( $serialize_options );
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

	<?php if ( 'logging' === $active_tab ) : ?>

	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_log_queries'><?php esc_html_e( 'Enable logs', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Keep a log of user queries.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_log_queries'>
				<input type='checkbox' name='relevanssi_log_queries' id='relevanssi_log_queries' <?php echo esc_html( $log_queries ); ?> />
				<?php esc_html_e( 'Keep a log of user queries.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description">
		<?php
		global $wpdb;
		// Translators: %1$s is the name of the "User searches" page, %2$s is the name of the database table.
		printf( esc_html__( "If enabled, Relevanssi will log user queries. The logs can be examined under '%1\$s' on the Dashboard admin menu and are stored in the %2\$s database table.", 'relevanssi' ),
		esc_html__( 'User searches', 'relevanssi' ), esc_html( $wpdb->prefix . 'relevanssi_log' ) );
		?>
		</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_log_queries_with_ip'><?php esc_html_e( 'Log user IP', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( "Log the user's IP with the queries.", 'relevanssi' ); ?></legend>
			<label for='relevanssi_log_queries_with_ip'>
				<input type='checkbox' name='relevanssi_log_queries_with_ip' id='relevanssi_log_queries_with_ip' <?php echo esc_html( $log_queries_with_ip ); ?> />
				<?php esc_html_e( "Log the user's IP with the queries.", 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( "If enabled, Relevanssi will log user's IP adress with the queries. Note that this may be illegal where you live, and in EU will create a person registry that falls under the GDPR.", 'relevanssi' ); ?></p>
		</td>
	</tr>	
	<tr>
		<th scope="row">
			<label for='relevanssi_omit_from_logs'><?php esc_html_e( 'Exclude users', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_omit_from_logs' id='relevanssi_omit_from_logs' size='60' value='<?php echo esc_attr( $omit_from_logs ); ?>' />
			<p class="description"><?php esc_html_e( 'Comma-separated list of numeric user IDs or user login names that will not be logged.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php
	if ( function_exists( 'relevanssi_form_hide_branding' ) ) {
		relevanssi_form_hide_branding( $hide_branding );
	}
	?>
	<tr>
		<th scope="row">
			<label for='relevanssi_trim_logs'><?php esc_html_e( 'Trim logs', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='number' name='relevanssi_trim_logs' id='relevanssi_trim_logs' value='<?php echo esc_attr( $trim_logs ); ?>' />
			<?php esc_html_e( 'How many days of logs to keep in the database.', 'relevanssi' ); ?>
			<?php // Translators: %d is the setting for no trim (probably 0). ?>
			<p class="description"><?php printf( esc_html__( ' Set to %d for no trimming.', 'relevanssi' ), 0 ); ?></p>
		</td>
	</tr>

	</table>

	<?php endif; // Active tag: logging. ?>

	<?php
	if ( 'searching' === $active_tab ) :
		$docs_count = $wpdb->get_var( 'SELECT COUNT(DISTINCT doc) FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE doc != -1' ); // WPCS: unprepared SQL ok, Relevanssi table name.
	?>

	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_implicit_operator'><?php esc_html_e( 'Default operator', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_implicit_operator' id='relevanssi_implicit_operator'>
				<option value='AND' <?php echo esc_html( $implicit_and ); ?>><?php esc_html_e( 'AND - require all terms', 'relevanssi' ); ?></option>
				<option value='OR' <?php echo esc_html( $implicit_or ); ?>><?php esc_html_e( 'OR - any term present is enough', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'This setting determines the default operator for the search.', 'relevanssi' ); ?></p>
	<?php
	if ( RELEVANSSI_PREMIUM ) {
		// Translators: %1$s is the name of the 'operator' query variable, %2$s is an example url.
		echo "<p class='description'>" . sprintf( esc_html__( 'You can override this setting with the %1$s query parameter, like this: %2$s', 'relevanssi' ), '<code>operator</code>', 'http://www.example.com/?s=term&amp;operator=or' ) . '</p>';
	}
	?>
		</td>
	</tr>	
	<tr id="orfallback" class='<?php echo esc_attr( $orfallback_visibility ); ?>'>
		<th scope="row">
			<label for='relevanssi_disable_or_fallback'><?php esc_html_e( 'Fallback to OR', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Disable the OR fallback.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_disable_or_fallback'>
				<input type='checkbox' name='relevanssi_disable_or_fallback' id='relevanssi_disable_or_fallback' <?php echo esc_html( $disable_or_fallback ); ?> />
				<?php esc_html_e( 'Disable the OR fallback.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'By default, if AND search fails to find any results, Relevanssi will switch the operator to OR and run the search again. You can prevent that by checking this option.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_default_orderby'><?php esc_html_e( 'Default order', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_default_orderby' id='relevanssi_default_orderby'>
				<option value='relevance' <?php echo esc_html( $orderby_relevance ); ?>><?php esc_html_e( 'Relevance (highly recommended)', 'relevanssi' ); ?></option>
				<option value='post_date' <?php echo esc_html( $orderby_date ); ?>><?php esc_html_e( 'Post date', 'relevanssi' ); ?></option>
			</select>
			<?php // Translators: name of the query variable. ?>
			<p class="description"><?php printf( esc_html__( 'If you want to override this or use multi-layered ordering (eg. first order by relevance, but sort ties by post title), you can use the %s query variable. See Help for more information.', 'relevanssi' ), '<code>orderby</code>' ); ?></p>
			<?php if ( RELEVANSSI_PREMIUM ) { ?>
			<p class="description"><?php esc_html_e( ' If you want date-based results, see the recent post bonus in the Weights section.', 'relevanssi' ); ?></p>
			<?php } // End if ( RELEVANSSI_PREMIUM ). ?>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_fuzzy'><?php esc_html_e( 'Keyword matching', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_fuzzy' id='relevanssi_fuzzy'>
				<option value='never' <?php echo esc_html( $fuzzy_never ); ?>><?php esc_html_e( 'Whole words', 'relevanssi' ); ?></option>
				<option value='always' <?php echo esc_html( $fuzzy_always ); ?>><?php esc_html_e( 'Partial words', 'relevanssi' ); ?></option>
				<option value='sometimes' <?php echo esc_html( $fuzzy_sometimes ); ?>><?php esc_html_e( 'Partial words if no hits for whole words', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Whole words means Relevanssi only finds posts that include the whole search term.', 'relevanssi' ); ?></p>
			<p class="description"><?php esc_html_e( "Partial words also includes cases where the word in the index begins or ends with the search term (searching for 'ana' will match 'anaconda' or 'banana', but not 'banal'). See Help, if you want to make Relevanssi match also inside words.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Weights', 'relevanssi' ); ?>
		</th>
		<td>
			<p class="description"><?php esc_html_e( 'All the weights in the table are multipliers. To increase the weight of an element, use a higher number. To make an element less significant, use a number lower than 1.', 'relevanssi' ); ?></p>
			<table class="relevanssi-weights-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Element', 'relevanssi' ); ?></th>
					<th class="col-2"><?php esc_html_e( 'Weight', 'relevanssi' ); ?></th>
				</tr>
			</thead>
			<tr>
				<td>
					<?php esc_html_e( 'Content', 'relevanssi' ); ?>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_content_boost' id='relevanssi_content_boost' size='4' value='<?php echo esc_attr( $content_boost ); ?>' />
				</td>
			</tr>
			<tr>
				<td>
					<?php esc_html_e( 'Titles', 'relevanssi' ); ?>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_title_boost' id='relevanssi_title_boost' size='4' value='<?php echo esc_attr( $title_boost ); ?>' />
				</td>
			</tr>
			<?php
			if ( function_exists( 'relevanssi_form_link_weight' ) ) {
				relevanssi_form_link_weight( $link_boost );
			}
			?>
			<tr>
				<td>
					<?php esc_html_e( 'Comment text', 'relevanssi' ); ?>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_comment_boost' id='relevanssi_comment_boost' size='4' value='<?php echo esc_attr( $comment_boost ); ?>' />
				</td>
			</tr>
			<?php
			if ( function_exists( 'relevanssi_form_post_type_weights' ) ) {
				relevanssi_form_post_type_weights( $post_type_weights );
			}
			if ( function_exists( 'relevanssi_form_taxonomy_weights' ) ) {
				relevanssi_form_taxonomy_weights( $post_type_weights );
			} elseif ( function_exists( 'relevanssi_form_tag_weight' ) ) {
				relevanssi_form_tag_weight( $post_type_weights );
			}
			if ( function_exists( 'relevanssi_form_recency_weight' ) ) {
				relevanssi_form_recency_weight( $recency_bonus );
			}
			?>
			</table>
		</td>
	</tr>	
	<?php
	if ( function_exists( 'relevanssi_form_recency_cutoff' ) ) {
		relevanssi_form_recency_cutoff( $recency_bonus_days );
	}
	?>
	<tr>
		<th scope="row">
		<label for='relevanssi_exact_match_bonus'><?php esc_html_e( 'Boost exact matches', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Give boost to exact matches.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_exact_match_bonus'>
				<input type='checkbox' name='relevanssi_exact_match_bonus' id='relevanssi_exact_match_bonus' <?php echo esc_html( $exact_match_bonus ); ?> />
				<?php esc_html_e( 'Give boost to exact matches.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<?php // Translators: %s is the name of the filter hook. ?>
		<p class="description"><?php printf( esc_html__( 'If you enable this option, matches where the search query appears in title or content as a phrase will get a weight boost. To adjust the boost, you can use the %s filter hook. See Help for more details.', 'relevanssi' ), '<code>relevanssi_exact_match_bonus</code>' ); ?></p>
		</td>
	</tr>
	<?php
	if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_get_post' ) ) {
	?>
	<tr>
		<th scope="row">
		<label for='relevanssi_wpml_only_current'><?php esc_html_e( 'WPML', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Limit results to current language.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_wpml_only_current'>
				<input type='checkbox' name='relevanssi_wpml_only_current' id='relevanssi_wpml_only_current' <?php echo esc_html( $wpml_only_current ); ?> />
				<?php esc_html_e( 'Limit results to current language.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Enabling this option will restrict the results to the currently active language. If the option is disabled, results will include posts in all languages.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php } // WPML. ?>
	<?php if ( function_exists( 'pll_get_post' ) ) { ?>
	<tr>
		<th scope="row">
		<label for='relevanssi_polylang_all_languages'><?php esc_html_e( 'Polylang', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Allow results from all languages.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_polylang_all_languages'>
				<input type='checkbox' name='relevanssi_polylang_all_languages' id='relevanssi_polylang_all_languages' <?php echo esc_html( $polylang_allow_all ); ?> />
				<?php esc_html_e( 'Allow results from all languages.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'By default Polylang restricts the search to the current language. Enabling this option will lift this restriction.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php } // Polylang. ?>
	<tr>
		<th scope="row">
		<label for='relevanssi_admin_search'><?php esc_html_e( 'Admin search', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Use Relevanssi for admin searches.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_admin_search'>
				<input type='checkbox' name='relevanssi_admin_search' id='relevanssi_admin_search' <?php echo esc_html( $admin_search ); ?> />
				<?php esc_html_e( 'Use Relevanssi for admin searches.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( "If checked, Relevanssi will be used for searches in the admin interface. The page search doesn't use Relevanssi, because WordPress works like that.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php // Translators: %s is 'exclude_from_search'. ?>
			<label for='relevanssi_respect_exclude'><?php printf( esc_html__( 'Respect %s', 'relevanssi' ), 'exclude_from_search' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Respect exclude_from_search for custom post types', 'relevanssi' ); ?></legend>
			<label for='relevanssi_respect_exclude'>
				<input type='checkbox' name='relevanssi_respect_exclude' id='relevanssi_respect_exclude' <?php echo esc_html( $respect_exclude ); ?> />
				<?php // Translators: %s is 'exclude_from_search'. ?>
				<?php printf( esc_html__( 'Respect %s for custom post types', 'relevanssi' ), '<code>exclude_from_search</code>' ); ?>
			</label>
			<p class="description"><?php esc_html_e( "If checked, Relevanssi won't display posts of custom post types that have 'exclude_from_search' set to true.", 'relevanssi' ); ?></p>
			<?php
			if ( ! empty( $respect_exclude ) ) {
				$pt_1 = get_post_types( array( 'exclude_from_search' => '1' ) );
				$pt_2 = get_post_types( array( 'exclude_from_search' => true ) );

				$private_types      = array_merge( $pt_1, $pt_2 );
				$problem_post_types = array_intersect( $index_post_types, $private_types );
				if ( ! empty( $problem_post_types ) ) {
			?>
					<p class="description important">
					<?php
					esc_html_e( "You probably should uncheck this option, because you've set Relevanssi to index the following non-public post types:", 'relevanssi' );
					echo ' ' . esc_html( implode( ', ', $problem_post_types ) );
					?>
					</p>
				<?php
				}
			}
			?>
		</fieldset>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_throttle'><?php esc_html_e( 'Throttle searches', 'relevanssi' ); ?></label>
		</th>
		<td id="throttlesearches">
		<div id="throttle_disabled"
		<?php
		if ( ! $orderby_date ) {
			echo "class='screen-reader-text'";
		}
		?>
		>
		<p class="description"><?php esc_html_e( 'Throttling the search does not work when sorting the posts by date.', 'relevanssi' ); ?></p>
		</div>
		<div id="throttle_enabled"
		<?php
		if ( ! $orderby_relevance ) {
			echo "class='screen-reader-text'";
		}
		?>
		>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Throttle searches.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_throttle'>
				<input type='checkbox' name='relevanssi_throttle' id='relevanssi_throttle' <?php echo esc_html( $throttle ); ?> />
				<?php esc_html_e( 'Throttle searches.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<?php if ( $docs_count < 1000 ) { ?>
			<p class="description important"><?php esc_html_e( "Your database is so small that you don't need to enable this.", 'relevanssi' ); ?></p>
		<?php } ?>
		<p class="description"><?php esc_html_e( 'If this option is checked, Relevanssi will limit search results to at most 500 results per term. This will improve performance, but may cause some relevant documents to go unfound. See Help for more details.', 'relevanssi' ); ?></p>
		</div>
		</td>
	</tr>	
	<tr>
		<th scope="row">
			<label for='relevanssi_cat'><?php esc_html_e( 'Category restriction', 'relevanssi' ); ?></label>
		</th>
		<td>
			<div class="categorydiv" style="max-width: 400px">
			<div class="tabs-panel">
			<ul id="categorychecklist">
			<?php
				$selected_cats = explode( ',', $cat );
				$walker        = get_relevanssi_taxonomy_walker();
				$walker->name  = 'relevanssi_cat';
				wp_terms_checklist( 0, array(
					'taxonomy'      => 'category',
					'selected_cats' => $selected_cats,
					'walker'        => $walker,
				));
			?>
			</ul>
			<input type="hidden" name="relevanssi_cat_active" value="1" />
			</div>
			</div>
			<p class="description"><?php esc_html_e( 'You can restrict search results to a category for all searches. For restricting on a per-search basis and more options (eg. tag restrictions), see Help.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_excat'><?php esc_html_e( 'Category exclusion', 'relevanssi' ); ?></label>
		</th>
		<td>
			<div class="categorydiv" style="max-width: 400px">
			<div class="tabs-panel">
			<ul id="categorychecklist">
			<?php
				$selected_cats = explode( ',', $excat );
				$walker        = get_relevanssi_taxonomy_walker();
				$walker->name  = 'relevanssi_excat';
				wp_terms_checklist( 0, array(
					'taxonomy'      => 'category',
					'selected_cats' => $selected_cats,
					'walker'        => $walker,
				));
			?>
			</ul>
			<input type="hidden" name="relevanssi_excat_active" value="1" />
			</div>
			</div>
			<p class="description"><?php esc_html_e( 'Posts in these categories are not included in search results. To exclude the posts completely from the index, see Help.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_expst'><?php esc_html_e( 'Post exclusion', 'relevanssi' ); ?>
		</th>
		<td>
			<input type='text'  name='relevanssi_expst' id='relevanssi_expst' size='60' value='<?php echo esc_attr( $exclude_posts ); ?>' />
			<p class="description"><?php esc_html_e( "Enter a comma-separated list of post or page ID's to exclude those pages from the search results.", 'relevanssi' ); ?></p>
			<?php if ( RELEVANSSI_PREMIUM ) { ?>
				<p class="description"><?php esc_html_e( "With Relevanssi Premium, it's better to use the check box on post edit pages. That will remove the posts completely from the index, and will work with multisite searches unlike this setting.", 'relevanssi' ); ?></p>
			<?php } ?>
		</td>
	</tr>
	<?php
	if ( function_exists( 'relevanssi_form_searchblogs_setting' ) ) {
		relevanssi_form_searchblogs_setting( $searchblogs_all, $searchblogs );
	}
	?>
	</table>

	<?php endif; // Active tab: searching. ?>

	<?php if ( 'excerpts' === $active_tab ) : ?>

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
				if ( empty( $excerpts ) || empty( $original_index_fields ) ) {
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
		if ( 'visible' === $original_index_fields ) {
			esc_html_e( 'all visible custom fields', 'relevanssi' );
		} elseif ( 'all' === $original_index_fields ) {
			esc_html_e( 'all custom fields', 'relevanssi' );
		} elseif ( ! empty( $original_index_fields ) ) {
			printf( '<code>%s</code>', esc_html( $original_index_fields ) );
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
	<?php
	if ( function_exists( 'relevanssi_form_highlight_external' ) ) {
		relevanssi_form_highlight_external( $highlight_docs_ext, $excerpts );
	}
	?>
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

	<?php if ( 'indexing' === $active_tab ) : ?>

	<?php

	$docs_count  = $wpdb->get_var( 'SELECT COUNT(DISTINCT doc) FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE doc != -1' ); // WPCS: unprepared SQL ok, Relevanssi table name.
	$terms_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $relevanssi_variables['relevanssi_table'] ); // WPCS: unprepared SQL ok, Relevanssi table name.
	$biggest_doc = $wpdb->get_var( 'SELECT doc FROM ' . $relevanssi_variables['relevanssi_table'] . ' ORDER BY doc DESC LIMIT 1' ); // WPCS: unprepared SQL ok, Relevanssi table name.

	if ( RELEVANSSI_PREMIUM ) {
		$user_count    = $wpdb->get_var( 'SELECT COUNT(DISTINCT item) FROM ' . $relevanssi_variables['relevanssi_table'] . " WHERE type = 'user'" ); // WPCS: unprepared SQL ok, Relevanssi table name.
		$taxterm_count = $wpdb->get_var( 'SELECT COUNT(DISTINCT item) FROM ' . $relevanssi_variables['relevanssi_table'] . " WHERE (type != 'post' AND type != 'attachment' AND type != 'user')" ); // WPCS: unprepared SQL ok, Relevanssi table name.
	}

	?>
	<div id="indexing_tab">

	<table class="form-table">
	<tr>
		<th scope="row">
			<input type='submit' name='submit' value='<?php esc_attr_e( 'Save the options', 'relevanssi' ); ?>' class='button button-primary' /><br /><br />
			<input type="button" id="build_index" name="index" value="<?php esc_attr_e( 'Build the index', 'relevanssi' ); ?>" class='button-primary' /><br /><br />
			<input type="button" id="continue_indexing" name="continue" value="<?php esc_attr_e( 'Index unindexed posts', 'relevanssi' ); ?>" class='button-primary' />
		</th>
		<td>
			<div id='indexing_button_instructions'>
				<?php // Translators: %s is "Build the index". ?>
				<p class="description"><?php printf( esc_html__( '%s empties the existing index and rebuilds it from scratch.', 'relevanssi' ), '<strong>' . esc_html__( 'Build the index', 'relevanssi' ) . '</strong>' ); ?></p>
				<?php // Translators: %s is "Build the index". ?>
				<p class="description"><?php printf( esc_html__( "%s doesn't empty the index and only indexes those posts that are not indexed. You can use it if you have to interrupt building the index.", 'relevanssi' ), '<strong>' . esc_html__( 'Index unindexed posts', 'relevanssi' ) . '</strong>' ); ?>
				<?php
				if ( RELEVANSSI_PREMIUM ) {
					esc_html_e( "This doesn't index any taxonomy terms or users.", 'relevanssi' );
				}
				?>
				</p>
			</div>
			<div id='relevanssi-note' style='display: none'></div>
			<div id='relevanssi-progress' class='rpi-progress'><div class="rpi-indicator"></div></div>
			<div id='relevanssi-timer'><?php esc_html_e( 'Time elapsed', 'relevanssi' ); ?>: <span id="relevanssi_elapsed">0:00:00</span> | <?php esc_html_e( 'Time remaining', 'relevanssi' ); ?>: <span id="relevanssi_estimated"><?php esc_html_e( 'some time', 'relevanssi' ); ?></span></div>
			<textarea id='results' rows='10' cols='80'></textarea>
			<div id='relevanssi-indexing-instructions' style='display: none'><?php esc_html_e( "Indexing should respond quickly. If nothing happens in couple of minutes, it's probably stuck. The most common reasons for indexing issues are incompatible shortcodes, so try disabling the shortcode expansion setting and try again. Also, if you've just updated Relevanssi, doing a hard refresh in your browser will make sure your browser is not trying to use an outdated version of the Relevanssi scripts.", 'relevanssi' ); ?></div>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'State of the index', 'relevanssi' ); ?></td>
		<td id="stateoftheindex"><p><?php echo esc_html( $docs_count ); ?> <?php echo esc_html( _n( 'document in the index.', 'documents in the index.', $docs_count, 'relevanssi' ) ); ?>
	<?php if ( RELEVANSSI_PREMIUM ) : ?>
		<br /><?php echo esc_html( $user_count ); ?> <?php echo esc_html( _n( 'user in the index.', 'users in the index.', $user_count, 'relevanssi' ) ); ?><br />
		<?php echo esc_html( $taxterm_count ); ?> <?php echo esc_html( _n( 'taxonomy term in the index.', 'taxonomy terms in the index.', $taxterm_count, 'relevanssi' ) ); ?>
	<?php endif; ?>	
		</p>
		<p><?php echo esc_html( $terms_count ); ?> <?php echo esc_html( _n( 'term in the index.', 'terms in the index.', $terms_count, 'relevanssi' ) ); ?><br />
		<?php echo esc_html( $biggest_doc ); ?> <?php esc_html_e( 'is the highest post ID indexed.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	</table>

	<?php
	if ( count( $index_post_types ) < 2 ) {
		printf( '<p><strong>%s</strong></p>', esc_html__( "WARNING: You've chosen no post types to index. Nothing will be indexed. Choose some post types to index.", 'relevanssi' ) );
	}
	?>

	<h2 id="indexing"><?php esc_html_e( 'Indexing options', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( 'Any changes to the settings on this page require reindexing before they take effect.', 'relevanssi' ); ?></p>

	<table class="form-table">
	<tr>
		<th scope="row"><?php esc_html_e( 'Post types', 'relevanssi' ); ?></th>
		<td>

<table class="widefat" id="index_post_types_table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Type', 'relevanssi' ); ?></th>
			<th><?php esc_html_e( 'Index', 'relevanssi' ); ?></th>
			<th><?php esc_html_e( 'Excluded from search?', 'relevanssi' ); ?></th>
		</tr>
	</thead>
	<?php
	$pt_1         = get_post_types( array( 'exclude_from_search' => '0' ) );
	$pt_2         = get_post_types( array( 'exclude_from_search' => false ) );
	$public_types = array_merge( $pt_1, $pt_2 );
	$post_types   = get_post_types();
	foreach ( $post_types as $type ) {
		if ( in_array( $type, array( 'nav_menu_item', 'revision' ), true ) ) {
			continue;
		}
		$checked = '';
		if ( in_array( $type, $index_post_types, true ) ) {
			$checked = 'checked="checked"';
		}
		$label                = sprintf( '%s', $type );
		$excluded_from_search = __( 'yes', 'relevanssi' );
		if ( in_array( $type, $public_types, true ) ) {
			$excluded_from_search = __( 'no', 'relevanssi' );
		}
		$name_id = 'relevanssi_index_type_' . $type;
		printf( '<tr><td>%1$s</td><td><input type="checkbox" name="%2$s" id="%2$s" %3$s /></td><td>%4$s</td></tr>',
		esc_html( $label ), esc_attr( $name_id ), esc_html( $checked ), esc_html( $excluded_from_search ) );
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
		<?php // Translators: %1$s is 'attachment', %2$s opens the link, %3$s closes it. ?>
		<p class="description"><?php printf( esc_html__( '%1$s includes all attachment types. If you want to index only some attachments, see %2$sControlling attachment types in the Knowledge base%3$s.', 'relevanssi' ), '<code>attachment</code>', '<a href="https://www.relevanssi.com/knowledge-base/controlling-attachment-types-index/">', '</a>' ); ?></p>
	</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Taxonomies', 'relevanssi' ); ?>
		</th>
		<td>

			<table class="widefat" id="custom_taxonomies_table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Taxonomy', 'relevanssi' ); ?></th>
					<th><?php esc_html_e( 'Index', 'relevanssi' ); ?></th>
					<th><?php esc_html_e( 'Public?', 'relevanssi' ); ?></th>
				</tr>
			</thead>

	<?php
	$taxos = get_taxonomies( '', 'objects' );
	foreach ( $taxos as $taxonomy ) {
		if ( in_array( $taxonomy->name, array( 'nav_menu', 'link_category' ), true ) ) {
			continue;
		}
		$checked = '';
		if ( in_array( $taxonomy->name, $index_taxonomies_list, true ) ) {
			$checked = 'checked="checked"';
		}
		$label  = sprintf( '%s', $taxonomy->name );
		$public = __( 'no', 'relevanssi' );
		if ( $taxonomy->public ) {
			$public = __( 'yes', 'relevanssi' );
		}
		$name_id = 'relevanssi_index_taxonomy_' . $taxonomy->name;
		printf( '<tr><td>%1$s</td><td><input type="checkbox" name="%2$s" id="%2$s" %3$s /></td><td>%4$s</td></tr>',
		esc_html( $label ), esc_attr( $name_id ), esc_html( $checked ), esc_html( $public ) );
	}
	?>
			</table>

			<p class="description"><?php esc_html_e( 'If you check a taxonomy here, the terms for that taxonomy are indexed with the posts. If you for example choose "post_tag", searching for a tag will find all posts that have the tag.', 'relevanssi' ); ?>

		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_comments'><?php esc_html_e( 'Comments', 'relevanssi' ); ?></label>		
		</th>
		<td>
			<select name='relevanssi_index_comments' id='relevanssi_index_comments'>
				<option value='none' <?php echo esc_html( $index_comments_none ); ?>><?php esc_html_e( 'none', 'relevanssi' ); ?></option>
				<option value='normal' <?php echo esc_html( $index_comments_normal ); ?>><?php esc_html_e( 'comments', 'relevanssi' ); ?></option>
				<option value='all' <?php echo esc_html( $index_comments_all ); ?>><?php esc_html_e( 'comments and pingbacks', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'If you choose to index comments, you can choose if you want to index just comments, or everything including comments and track- and pingbacks.', 'relevanssi' ); ?></p>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_fields'><?php esc_html_e( 'Custom fields', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_index_fields_select' id='relevanssi_index_fields_select'>
				<option value='none' <?php echo esc_html( $fields_select_none ); ?>><?php esc_html_e( 'none', 'relevanssi' ); ?></option>
				<option value='all' <?php echo esc_html( $fields_select_all ); ?>><?php esc_html_e( 'all', 'relevanssi' ); ?></option>
				<option value='visible' <?php echo esc_html( $fields_select_visible ); ?>><?php esc_html_e( 'visible', 'relevanssi' ); ?></option>
				<option value='some' <?php echo esc_html( $fields_select_some ); ?>><?php esc_html_e( 'some', 'relevanssi' ); ?></option>
			</select>
			<p class="description">
			<?php
			esc_html_e( "'All' indexes all custom fields for posts.", 'relevanssi' );
			echo '<br/>';
			esc_html_e( "'Visible' only includes the custom fields that are visible in the user interface (with names that don't start with an underscore).", 'relevanssi' );
			echo '<br/>';
			esc_html_e( "'Some' lets you choose individual custom fields to index.", 'relevanssi' );
			?>
			</p>
			<div id="index_field_input"
			<?php
			if ( empty( $fields_select_some ) ) {
				echo 'style="display: none"';
			}
			?>
			>
				<input type='text' name='relevanssi_index_fields' id='relevanssi_index_fields' size='60' value='<?php echo esc_attr( $index_fields ); ?>' />
				<p class="description"><?php esc_html_e( "Enter a comma-separated list of custom fields to include in the index. With Relevanssi Premium, you can also use 'fieldname_%_subfieldname' notation for ACF repeater fields.", 'relevanssi' ); ?></p>
				<p class="description"><?php esc_html_e( "You can use 'relevanssi_index_custom_fields' filter hook to adjust which custom fields are indexed.", 'relevanssi' ); ?></p>
			</div>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
			<?php // Translators: %1$s is the 'some' option and %2$s is '_sku'. ?>
			<p class="description"><?php printf( esc_html__( 'If you want the SKU included, choose %1$s and enter %2$s. Also see the contextual help for more details.', 'relevanssi' ), esc_html( "'" . __( 'some', 'relevanssi' ) . "'" ), '<code>_sku</code>' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_author'><?php esc_html_e( 'Author display names', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Index the post author display name', 'relevanssi' ); ?></legend>
			<label for='relevanssi_index_author'>
				<input type='checkbox' name='relevanssi_index_author' id='relevanssi_index_author' <?php echo esc_html( $index_author ); ?> />
				<?php esc_html_e( 'Index the post author display name', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Searching for the post author display name will return posts by that author.', 'relevanssi' ); ?></p>
		</fieldset>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<label for='relevanssi_index_excerpt'><?php esc_html_e( 'Excerpts', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Index the post excerpt', 'relevanssi' ); ?></legend>
			<label for='relevanssi_index_excerpt'>
				<input type='checkbox' name='relevanssi_index_excerpt' id='relevanssi_index_excerpt' <?php echo esc_html( $index_excerpt ); ?> />
				<?php esc_html_e( 'Index the post excerpt', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Relevanssi will find posts by the content in the excerpt.', 'relevanssi' ); ?></p>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
			<p class="description"><?php esc_html_e( "WooCommerce stores the product short description in the excerpt, so it's a good idea to index excerpts.", 'relevanssi' ); ?></p>
			<?php endif; ?>
		</fieldset>
		</td>
	</tr>

	</table>

	<h2><?php esc_html_e( 'Shortcodes', 'relevanssi' ); ?></h2>

	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_expand_shortcodes'><?php esc_html_e( 'Expand shortcodes', 'relevanssi' ); ?></label>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Index the post excerpt', 'relevanssi' ); ?></legend>
			<label for='relevanssi_expand_shortcodes'>
				<input type='checkbox' name='relevanssi_expand_shortcodes' id='relevanssi_expand_shortcodes' <?php echo esc_html( $expand_shortcodes ); ?> />
				<?php esc_html_e( 'Expand shortcodes when indexing', 'relevanssi' ); ?>
			</label>
			<?php if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) : ?>
			<p class="description important"><?php esc_html_e( "WooCommerce has shortcodes that don't work well with Relevanssi. With WooCommerce, make sure the option is disabled.", 'relevanssi' ); ?></p>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'If checked, Relevanssi will expand shortcodes in post content before indexing. Otherwise shortcodes will be stripped.', 'relevanssi' ); ?></p>
			<p class="description"><?php esc_html_e( 'If you use shortcodes to include dynamic content, Relevanssi will not keep the index updated, the index will reflect the status of the shortcode content at the moment of indexing.', 'relevanssi' ); ?></p>
		</fieldset>
		</td>
	</tr>

	<?php
	if ( function_exists( 'relevanssi_form_disable_shortcodes' ) ) {
		relevanssi_form_disable_shortcodes( $disable_shortcodes );
	}
	?>

	</table>

	<?php
	if ( function_exists( 'relevanssi_form_index_users' ) ) {
		relevanssi_form_index_users( $index_users, $index_subscribers, $index_user_fields );
	}
	if ( function_exists( 'relevanssi_form_index_synonyms' ) ) {
		relevanssi_form_index_synonyms( $index_synonyms );
	}
	if ( function_exists( 'relevanssi_form_index_taxonomies' ) ) {
		relevanssi_form_index_taxonomies( $index_taxonomies, $index_terms );
	}
	if ( function_exists( 'relevanssi_form_index_pdf_parent' ) ) {
		relevanssi_form_index_pdf_parent( $index_pdf_parent, $index_post_types );
	}
	?>

	<h2><?php esc_html_e( 'Advanced indexing settings', 'relevanssi' ); ?></h2>

	<p><button type="button" id="show_advanced_indexing"><?php esc_html_e( 'Show advanced settings', 'relevanssi' ); ?></button></p>

	<table class="form-table screen-reader-text" id="advanced_indexing">
	<tr>
		<th scope="row">
			<label for='relevanssi_min_word_length'><?php esc_html_e( 'Minimum word length', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='number' name='relevanssi_min_word_length' id='relevanssi_min_word_length' value='<?php echo esc_attr( $min_word_length ); ?>' />
			<p class="description"><?php esc_html_e( 'Words shorter than this many letters will not be indexed.', 'relevanssi' ); ?></p>
			<?php // Translators: %1$s is 'relevanssi_block_one_letter_searches' and %2$s is 'false'. ?>
			<p class="description"><?php printf( esc_html__( 'To enable one-letter searches, you need to add a filter function on the filter hook %1$s that returns %2$s.', 'relevanssi' ), '<code>relevanssi_block_one_letter_searches</code>', '<code>false</code>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Punctuation control', 'relevanssi' ); ?></th>
		<td><p class="description"><?php esc_html_e( 'Here you can adjust how the punctuation is controlled. For more information, see help. Remember that any changes here require reindexing, otherwise searches will fail to find posts they should.', 'relevanssi' ); ?></p></td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_hyphens'><?php esc_html_e( 'Hyphens and dashes', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_hyphens' id='relevanssi_punct_hyphens'>
				<option value='keep' <?php echo esc_html( $punct_hyphens_keep ); ?>><?php esc_html_e( 'Keep', 'relevanssi' ); ?></option>
				<option value='replace' <?php echo esc_html( $punct_hyphens_replace ); ?>><?php esc_html_e( 'Replace with spaces', 'relevanssi' ); ?></option>
				<option value='remove' <?php echo esc_html( $punct_hyphens_remove ); ?>><?php esc_html_e( 'Remove', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'How Relevanssi should handle hyphens and dashes (en and em dashes)? Replacing with spaces is generally the best option, but in some cases removing completely is the best option. Keeping them is rarely the best option.', 'relevanssi' ); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_quotes'><?php esc_html_e( 'Apostrophes and quotes', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_quotes' id='relevanssi_punct_quotes'>
				<option value='replace' <?php echo esc_html( $punct_quotes_replace ); ?>><?php esc_html_e( 'Replace with spaces', 'relevanssi' ); ?></option>
				<option value='remove' <?php echo esc_html( $punct_quotes_remove ); ?>><?php esc_html_e( 'Remove', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( "How Relevanssi should handle apostrophes and quotes? It's not possible to keep them; that would lead to problems. Default behaviour is to replace with spaces, but sometimes removing makes sense.", 'relevanssi' ); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_ampersands'><?php esc_html_e( 'Ampersands', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_ampersands' id='relevanssi_punct_ampersands'>
				<option value='keep' <?php echo esc_html( $punct_ampersands_keep ); ?>><?php esc_html_e( 'Keep', 'relevanssi' ); ?></option>
				<option value='replace' <?php echo esc_html( $punct_ampersands_replace ); ?>><?php esc_html_e( 'Replace with spaces', 'relevanssi' ); ?></option>
				<option value='remove' <?php echo esc_html( $punct_ampersands_remove ); ?>><?php esc_html_e( 'Remove', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'How Relevanssi should handle ampersands? Replacing with spaces is generally the best option, but if you talk a lot about D&amp;D, for example, keeping the ampersands is useful.', 'relevanssi' ); ?></p>

		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_punct_decimals'><?php esc_html_e( 'Decimal separators', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_punct_decimals' id='relevanssi_punct_decimals'>
				<option value='keep' <?php echo esc_html( $punct_decimals_keep ); ?>><?php esc_html_e( 'Keep', 'relevanssi' ); ?></option>
				<option value='replace' <?php echo esc_html( $punct_decimals_replace ); ?>><?php esc_html_e( 'Replace with spaces', 'relevanssi' ); ?></option>
				<option value='remove' <?php echo esc_html( $punct_decimals_remove ); ?>><?php esc_html_e( 'Remove', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'How Relevanssi should handle periods between decimals? Replacing with spaces is the default option, but that often leads to the numbers being removed completely. If you need to search decimal numbers a lot, keep the periods.', 'relevanssi' ); ?></p>

		</td>
	</tr>
	<?php
	if ( function_exists( 'relevanssi_form_thousands_separator' ) ) {
		relevanssi_form_thousands_separator( $thousand_separator );
	}
	if ( function_exists( 'relevanssi_form_mysql_columns' ) ) {
		relevanssi_form_mysql_columns( $mysql_columns );
	}
	if ( function_exists( 'relevanssi_form_internal_links' ) ) {
		relevanssi_form_internal_links( $internal_links_noindex, $internal_links_strip, $internal_links_nostrip );
	}
	?>

	</table>

	<p><button type="button" style="display: none" id="hide_advanced_indexing"><?php esc_html_e( 'Hide advanced settings', 'relevanssi' ); ?></button></p>

	</div> <?php // End #indexing_tab. ?>

	<?php endif; // Active tab: indexing. ?>

	<?php if ( 'attachments' === $active_tab ) : ?>

	<?php
	if ( function_exists( 'relevanssi_form_attachments' ) ) {
		relevanssi_form_attachments( $index_post_types, $index_pdf_parent );
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

			<p class="description"><?php esc_html_e( 'The format here is <code>key = value</code>. If you add <code>dog = hound</code> to the list of synonyms, searches for <code>dog</code> automatically become a search for <code>dog hound</code> and will thus match to posts that include either <code>dog</code> or <code>hound</code>. This only works in OR searches: in AND searches the synonyms only restrict the search, as now the search only finds posts that contain <strong>both</strong> <code>dog</code> and <code>hound</code>.', 'relevanssi' ); ?></p>

			<p class="description"><?php esc_html_e( 'The synonyms are one direction only. If you want both directions, add the synonym again, reversed: <code>hound = dog</code>.', 'relevanssi' ); ?></p>

			<p class="description"><?php esc_html_e( "It's possible to use phrases for the value, but not for the key. <code>dog = \"great dane\"</code> works, but <code>\"great dane\" = dog</code> doesn't.", 'relevanssi' ); ?></p>

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
		relevanssi_form_importexport( $serialized_options );
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
			'<li>' . sprintf( __( 'In order to adjust the throttle limit, you can use the %s filter hook.', 'relevanssi' ), '<code>relevanssi_throttle_limit</code>' ) .
			'<pre>add_filter("relevanssi_throttle_limit", function( $limit ) { return 200; } );</pre>' .
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
		'confirm'             => __( 'Click OK to copy Relevanssi options to all subsites', 'relevanssi' ),
		'confirm_stopwords'   => __( 'Are you sure you want to remove all stopwords?', 'relevanssi' ),
		'truncating_index'    => __( 'Wiping out the index...', 'relevanssi' ),
		'done'                => __( 'Done.', 'relevanssi' ),
		'indexing_users'      => __( 'Indexing users...', 'relevanssi' ),
		'indexing_taxonomies' => __( 'Indexing the following taxonomies:', 'relevanssi' ),
		'counting_posts'      => __( 'Counting posts...', 'relevanssi' ),
		'counting_terms'      => __( 'Counting taxonomy terms...', 'relevanssi' ),
		'counting_users'      => __( 'Counting users...', 'relevanssi' ),
		'posts_found'         => __( 'posts found.', 'relevanssi' ),
		'terms_found'         => __( 'taxonomy terms found.', 'relevanssi' ),
		'users_found'         => __( 'users found.', 'relevanssi' ),
		'taxonomy_disabled'   => __( 'Taxonomy term indexing is disabled.', 'relevanssi' ),
		'user_disabled'       => __( 'User indexing is disabled.', 'relevanssi' ),
		'indexing_complete'   => __( 'Indexing complete.', 'relevanssi' ),
		'excluded_posts'      => __( 'posts excluded.', 'relevanssi' ),
		'options_changed'     => __( 'Settings have changed, please save the options before indexing.', 'relevanssi' ),
		'reload_state'        => __( 'Reload the page to refresh the state of the index.', 'relevanssi' ),
		'pdf_reset_confirm'   => __( 'Are you sure you want to delete all attachment content from the index?', 'relevanssi' ),
		'pdf_reset_done'      => __( 'Relevanssi attachment data wiped clean.', 'relevanssi' ),
		'hour'                => __( 'hour', 'relevanssi' ),
		'hours'               => __( 'hours', 'relevanssi' ),
		'about'               => __( 'about', 'relevanssi' ),
		'sixty_min'           => __( 'about an hour', 'relevanssi' ),
		'ninety_min'          => __( 'about an hour and a half', 'relevanssi' ),
		'minute'              => __( 'minute', 'relevanssi' ),
		'minutes'             => __( 'minutes', 'relevanssi' ),
		'underminute'         => __( 'less than a minute', 'relevanssi' ),
		'notimeremaining'     => __( "we're done!", 'relevanssi' ),
	);

	wp_localize_script( 'relevanssi_admin_js', 'relevanssi', $localizations );

	$nonce = array(
		'indexing_nonce' => wp_create_nonce( 'relevanssi_indexing_nonce' ),
	);

	wp_localize_script( 'relevanssi_admin_js', 'nonce', $nonce );

}

/**
 * Prints out the form fields for tag and category weights.
 *
 * @param array $taxonomy_weights The taxonomy weights.
 */
function relevanssi_form_tag_weight( $taxonomy_weights ) {
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
