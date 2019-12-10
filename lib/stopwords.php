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
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_populate_stopwords() {
	global $relevanssi_variables, $wpdb;

	$stopword_table       = $relevanssi_variables['stopword_table'];
	$stopwords_from_table = $wpdb->get_col( "SELECT * FROM $stopword_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( count( $stopwords_from_table ) > 1 ) {
		array_walk( $stopwords_from_table, 'relevanssi_add_single_stopword' );
	} else {
		$lang = get_option( 'WPLANG', 'en_US' );

		if ( file_exists( $relevanssi_variables['plugin_dir'] . 'stopwords/stopwords.' . $lang ) ) {
			include $relevanssi_variables['plugin_dir'] . 'stopwords/stopwords.' . $lang;

			if ( is_array( $stopwords ) ) {
				array_walk( $stopwords, 'relevanssi_add_single_stopword' );
			}
		}
	}
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
	$stopword_list = explode( ',', $stopwords );

	return $stopword_list;
}

/**
 * Adds a stopword to the list of stopwords.
 *
 * @param string  $term    The stopword that is added.
 * @param boolean $verbose If true, print out notice. If false, be silent. Default
 * true.
 *
 * @return boolean True, if success; false otherwise.
 */
function relevanssi_add_stopword( $term, $verbose = true ) {
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
				printf( "<div id='message' class='updated fade'><p>%s</p></div>", sprintf( esc_html__( "Couldn't add term '%s' to stopwords!", 'relevanssi' ), esc_html( stripslashes( $term ) ) ) );
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
	if ( empty( $term ) ) {
		return false;
	}

	$term = stripslashes( relevanssi_strtolower( $term ) );

	$stopwords = get_option( 'relevanssi_stopwords', '' );
	if ( ! empty( $stopwords ) ) {
		$stopwords .= ',';

	}
	$stopwords .= $term;
	$success    = update_option( 'relevanssi_stopwords', $stopwords );

	if ( ! $success ) {
		return false;
	}

	global $wpdb, $relevanssi_variables;

	// Remove from index.
	$wpdb->query(
		$wpdb->prepare(
			'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE term=%s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$term
		)
	);

	return true;
}

/**
 * Removes all stopwords.
 *
 * Empties the relevanssi_stopwords option.
 */
function relevanssi_remove_all_stopwords() {
	$success = update_option( 'relevanssi_stopwords', '' );

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
	$stopwords = get_option( 'relevanssi_stopwords', '' );
	$success   = false;

	$stopwords_array = explode( ',', $stopwords );
	if ( is_array( $stopwords_array ) ) {
		$stopwords_array = array_filter(
			$stopwords_array,
			function( $v ) use ( $term ) {
				return $v !== $term;
			}
		);

		$stopwords = implode( ',', $stopwords_array );
		$success   = update_option( 'relevanssi_stopwords', $stopwords );
	}

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
