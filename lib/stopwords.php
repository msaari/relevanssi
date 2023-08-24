<?php
/**
 * /lib/stopwords.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Reads automatically the correct stopwords for the current language set in
 * WPLANG.
 *
 * The stopwords are first read from the wp_relevanssi_stopwords database table
 * (which is where they were stored before they were moved to an option), but
 * if the table is empty (as it will be in new installations), the stopwords are
 * read from the stopword file for the current language (defaulting to en_US).
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param boolean $verbose        If true, output results. Default false.
 * @param string  $stopword_table Name of the stopword table to use. Default
 * empty, which means the default table.
 *
 * @return string Result: 'database' for reading from database, 'file' for
 * reading from file, 'no_file' for non-existing file, 'file_error' for file
 * with non-acceptable data.
 */
function relevanssi_populate_stopwords( $verbose = false, string $stopword_table = '' ) {
	global $relevanssi_variables, $wpdb;

	if ( empty( $stopword_table ) ) {
		$stopword_table = $relevanssi_variables['stopword_table'];
	}
	$stopwords_from_table = $wpdb->get_col( "SELECT * FROM $stopword_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( count( $stopwords_from_table ) > 1 ) {
		array_walk( $stopwords_from_table, 'relevanssi_add_single_stopword' );
		$verbose && printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			esc_html__( 'Added stopwords from the database.', 'relevanssi' )
		);
		return 'database';
	}

	$language      = relevanssi_get_current_language();
	$stopword_file = $relevanssi_variables['plugin_dir']
					. 'stopwords/stopwords.' . $language;

	if ( ! file_exists( $stopword_file ) ) {
		$verbose && printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			sprintf(
				// Translators: %s is the language code.
				esc_html__(
					"The stopword file for the language '%s' doesn't exist.",
					'relevanssi'
				),
				esc_html( $language )
			)
		);
		return 'no_file';
	}

	$stopwords = array();
	include $stopword_file; // Contains the stopwords in the $stopwords array.

	if ( ! is_array( $stopwords ) ) {
		$verbose && printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			esc_html__(
				"Couldn't read the stopwords from the file.",
				'relevanssi'
			)
		);
		return 'file_error';
	}

	array_walk( $stopwords, 'relevanssi_add_single_stopword' );
	$verbose && printf(
		"<div id='message' class='updated fade'><p>%s</p></div>",
		esc_html__( 'Added stopwords from the stopword file.', 'relevanssi' )
	);

	return 'file';
}

/**
 * Fetches the list of stopwords in the current language.
 *
 * Gets the list of stopwords from the relevanssi_stopwords option using the
 * current language.
 *
 * @return array An array of stopwords;  if nothing is found, returns an empty
 * array.
 */
function relevanssi_fetch_stopwords() {
	$current_language = relevanssi_get_current_language();
	$stopwords_array  = get_option( 'relevanssi_stopwords', array() );
	$stopwords        = isset( $stopwords_array[ $current_language ] ) ? $stopwords_array[ $current_language ] : '';
	$stopword_list    = $stopwords ? explode( ',', $stopwords ) : array();

	return $stopword_list;
}

/**
 * Adds a stopword to the list of stopwords.
 *
 * @param string  $term    The stopword that is added.
 * @param boolean $verbose If true, print out notices. Default true.
 *
 * @return boolean True, if success; false otherwise.
 */
function relevanssi_add_stopword( $term, $verbose = true ) {
	if ( empty( $term ) ) {
		return false;
	}

	$total_stopwords    = 0;
	$successfully_added = 0;

	$terms = explode( ',', $term );
	if ( count( $terms ) > 1 ) {
		$total_stopwords    = count( $terms );
		$successfully_added = array_reduce(
			$terms,
			function ( $counter, $term ) {
				$success = relevanssi_add_single_stopword( trim( $term ) );
				$success && $counter++;
				return $counter;
			},
			0
		);

		$verbose &&
			printf(
				"<div id='message' class='updated fade'><p>%s</p></div>",
				sprintf(
					// translators: %1$d is the successful entries, %2$d is the total entries.
					esc_html__(
						'Successfully added %1$d/%2$d terms to stopwords!',
						'relevanssi'
					),
					intval( $successfully_added ),
					intval( $total_stopwords )
				)
			);
		return boolval( $successfully_added );
	}

	// Add to stopwords.
	$success = relevanssi_add_single_stopword( $term );

	$term = esc_html( $term );

	$verbose && $success && printf(
		"<div id='message' class='updated fade'><p>%s</p></div>",
		sprintf(
			// Translators: %s is the stopword.
			esc_html__( "Term '%s' added to stopwords!", 'relevanssi' ),
			esc_html( stripslashes( $term ) )
		)
	);

	$verbose && ! $success && printf(
		"<div id='message' class='updated fade'><p>%s</p></div>",
		sprintf(
			// Translators: %s is the stopword.
			esc_html__( "Couldn't add term '%s' to stopwords!", 'relevanssi' ),
			esc_html( stripslashes( $term ) )
		)
	);

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
	if ( empty( $term ) ) {
		return false;
	}

	$stopwords = relevanssi_fetch_stopwords();
	$term      = stripslashes( relevanssi_strtolower( $term ) );

	if ( in_array( $term, $stopwords, true ) ) {
		return false;
	}

	$stopwords[] = $term;

	$success = relevanssi_update_stopwords( $stopwords );

	if ( ! $success ) {
		return false;
	}

	relevanssi_delete_term_from_all_posts( $term );

	return true;
}

/**
 * Updates the current language stopwords in the stopwords option.
 *
 * Fetches the stopwords option, replaces the current language stopwords with
 * the parameter array and updates the option.
 *
 * @param array $stopwords An array of stopwords.
 *
 * @return boolean The return value from update_option().
 */
function relevanssi_update_stopwords( $stopwords ) {
	$current_language = relevanssi_get_current_language();
	$stopwords_option = get_option( 'relevanssi_stopwords', array() );

	$stopwords_option[ $current_language ] = implode( ',', array_filter( $stopwords ) );
	return update_option(
		'relevanssi_stopwords',
		$stopwords_option
	);
}

/**
 * Deletes a term from all posts in the database, language considered.
 *
 * If Polylang or WPML are used, deletes the term only from the posts matching
 * the current language.
 *
 * @param string $term The term to delete.
 */
function relevanssi_delete_term_from_all_posts( $term ) {
	global $wpdb, $relevanssi_variables;

	if ( function_exists( 'pll_languages_list' ) ) {
		$term_id = relevanssi_get_language_term_taxonomy_id(
			relevanssi_get_current_language()
		);

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM {$relevanssi_variables['relevanssi_table']}
				WHERE term=%s
				AND doc IN (
					SELECT object_id
					FROM $wpdb->term_relationships
					WHERE term_taxonomy_id = %d
				)",
				$term,
				$term_id
			)
		);

		return;
	}

	if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_is_translated_post_type' ) ) {
		$language = relevanssi_get_current_language( false );
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM {$relevanssi_variables['relevanssi_table']}
				WHERE term=%s
				AND doc IN (
					SELECT DISTINCT(element_id)
					FROM {$wpdb->prefix}icl_translations
					WHERE language_code = %s
				)",
				$term,
				$language
			)
		);

		return;
	}

	// No language defined, just remove from the index.
	$wpdb->query(
		$wpdb->prepare(
			'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			' WHERE term=%s',
			$term
		)
	);
}

/**
 * Removes all stopwords in specific language.
 *
 * Empties the relevanssi_stopwords option for particular language.
 *
 * @param boolean $verbose  If true, print out notice. Default true.
 * @param string  $language The language code of stopwords. If empty, removes
 * the stopwords for the current language.
 *
 * @return boolean True, if able to remove the options.
 */
function relevanssi_remove_all_stopwords( $verbose = true, $language = false ) {
	if ( ! $language ) {
		$language = relevanssi_get_current_language();
	}

	$stopwords = get_option( 'relevanssi_stopwords', array() );
	unset( $stopwords[ $language ] );
	$success = update_option( 'relevanssi_stopwords', $stopwords );

	$verbose && $success && printf(
		"<div id='message' class='updated fade'><p>%s</p></div>",
		esc_html__(
			'All stopwords removed! Remember to re-index.',
			'relevanssi'
		)
	);

	$verbose && ! $success && printf(
		"<div id='message' class='updated fade'><p>%s</p></div>",
		esc_html__(
			"There was a problem, and stopwords couldn't be removed.",
			'relevanssi'
		)
	);

	return $success;
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
	$stopwords = relevanssi_fetch_stopwords();
	$term      = stripslashes( $term );
	$stopwords = array_filter(
		$stopwords,
		function ( $stopword ) use ( $term ) {
			return $stopword !== $term;
		}
	);

	$success = relevanssi_update_stopwords( $stopwords );

	$verbose && $success &&
		printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			sprintf(
				// Translators: %s is the stopword.
				esc_html__(
					"Term '%s' removed from stopwords! Re-index to get it back to index.",
					'relevanssi'
				),
				esc_html( stripslashes( $term ) )
			)
		);

	$verbose && ! $success &&
		printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			sprintf(
				// Translators: %s is the stopword.
				esc_html__(
					"Couldn't remove term '%s' from stopwords!",
					'relevanssi'
				),
				esc_html( stripslashes( $term ) )
			)
		);

	return $success;
}

/**
 * Helper function to remove stopwords from an array.
 *
 * Removes all stopwords from an array of terms. If body stopwords are
 * available, those will also be removed. The terms must be in the array values.
 *
 * @param array $terms An array of terms to clean out.
 *
 * @return array An array of terms with stopwords removed.
 */
function relevanssi_remove_stopwords_from_array( $terms ) {
	$stopword_list = relevanssi_fetch_stopwords();
	if ( function_exists( 'relevanssi_fetch_body_stopwords' ) ) {
		$stopword_list = array_merge( $stopword_list, relevanssi_fetch_body_stopwords() );
	}
	$terms_without_stops = array_diff( $terms, $stopword_list );
	return $terms_without_stops;
}

/**
 * Updates the relevanssi_stopwords setting from a simple string to an array
 * that is required for multilingual stopwords.
 */
function relevanssi_update_stopwords_setting() {
	$stopwords = get_option( 'relevanssi_stopwords' );
	if ( is_object( $stopwords ) ) {
		$array_stopwords = (array) $stopwords;
		update_option( 'relevanssi_stopwords', $array_stopwords );
		return;
	}

	$current_language = relevanssi_get_current_language();

	$array_stopwords[ $current_language ] = $stopwords;
	update_option( 'relevanssi_stopwords', $array_stopwords );
}
