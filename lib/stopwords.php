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
 * @param boolean $verbose If true, output results. Default false.
 *
 * @return string Result: 'database' for reading from database, 'file' for
 * reading from file, 'no_file' for non-existing file, 'file_error' for file
 * with non-acceptable data.
 */
function relevanssi_populate_stopwords( $verbose = false ) {
	global $relevanssi_variables, $wpdb;

	$stopword_table       = $relevanssi_variables['stopword_table'];
	$stopwords_from_table = $wpdb->get_col( "SELECT * FROM $stopword_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( count( $stopwords_from_table ) > 1 ) {
		array_walk( $stopwords_from_table, 'relevanssi_add_single_stopword' );
		$verbose && printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			esc_html__( 'Added stopwords from the database.', 'relevanssi' )
		);
		return 'database';
	}

	$lang          = get_locale();
	$stopword_file = $relevanssi_variables['plugin_dir']
					. 'stopwords/stopwords.' . $lang;

	if ( ! file_exists( $stopword_file ) ) {
		$verbose && printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			sprintf(
				// Translators: %s is the language code.
				esc_html__(
					"The stopword file for the language '%s' doesn't exist.",
					'relevanssi'
				),
				esc_html( $lang )
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
 * Fetches the list of stopwords.
 *
 * Gets the list of stopwords from the relevanssi_stopwords option.
 *
 * @return array An array of stopwords.
 */
function relevanssi_fetch_stopwords() {
	$stopwords     = get_option( 'relevanssi_stopwords', '' );
	$stopword_list = $stopwords ? explode( ',', $stopwords ) : array();

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

	$term      = stripslashes( relevanssi_strtolower( $term ) );
	$stopwords = get_option( 'relevanssi_stopwords', '' );

	$stopwords_array = explode( ',', $stopwords );
	if ( in_array( $term, $stopwords_array, true ) ) {
		return false;
	}

	$stopwords_array[] = $term;
	$success           = update_option(
		'relevanssi_stopwords',
		implode( ',', array_filter( $stopwords_array ) )
	);

	if ( ! $success ) {
		return false;
	}

	global $wpdb, $relevanssi_variables;

	// Remove from index.
	$wpdb->query(
		$wpdb->prepare(
			'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			' WHERE term=%s',
			$term
		)
	);

	return true;
}

/**
 * Removes all stopwords.
 *
 * Empties the relevanssi_stopwords option.
 *
 * @param boolean $verbose If true, print out notice. Default true.
 *
 * @return boolean True, if able to remove the options.
 */
function relevanssi_remove_all_stopwords( $verbose = true ) {
	$success = update_option( 'relevanssi_stopwords', '' );

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
	$stopwords = get_option( 'relevanssi_stopwords', '' );
	$success   = false;

	$term = stripslashes( $term );

	$stopwords_array = explode( ',', $stopwords );
	if ( is_array( $stopwords_array ) ) {
		$stopwords_array = array_filter(
			$stopwords_array,
			function( $stopword ) use ( $term ) {
				return $stopword !== $term;
			}
		);

		$stopwords = implode( ',', $stopwords_array );
		$success   = update_option( 'relevanssi_stopwords', $stopwords );
	}

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
