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
 * Reads automatically the correct stopwords for the current language set in WPLANG.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_populate_stopwords() {
	global $wpdb, $relevanssi_variables;

	$lang = get_option( 'WPLANG' );
	if ( empty( $lang ) && defined( 'WPLANG' ) && '' !== WPLANG ) {
		$lang = WPLANG;
	}
	if ( empty( $lang ) ) {
		$lang = 'en_US';
	}

	if ( file_exists( $relevanssi_variables['plugin_dir'] . 'stopwords/stopwords.' . $lang ) ) {
		include $relevanssi_variables['plugin_dir'] . 'stopwords/stopwords.' . $lang;

		if ( is_array( $stopwords ) && count( $stopwords ) > 0 ) {
			foreach ( $stopwords as $word ) {
				$wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO ' . $relevanssi_variables['stopword_table'] . ' (stopword) VALUES (%s)', trim( $word ) ) ); // WPCS: unprepared SQL ok.
			}
		}
	}
}

/**
 * Fetches the list of stopwords.
 *
 * Gets the list of stopwords from $relevanssi_variables, but if it's empty, fills
 * the array from the database table.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @return array An array of stopwords.
 */
function relevanssi_fetch_stopwords() {
	global $wpdb, $relevanssi_variables;

	if ( ! isset( $relevanssi_variables['stopword_list'] ) ) {
		$relevanssi_variables['stopword_list'] = array();
	}

	if ( count( $relevanssi_variables['stopword_list'] ) < 1 ) {
		$results = $wpdb->get_results( 'SELECT stopword FROM ' . $relevanssi_variables['stopword_table'] ); // WPCS: unprepared SQL ok.
		foreach ( $results as $word ) {
			$relevanssi_variables['stopword_list'][] = $word->stopword;
		}
	}

	return $relevanssi_variables['stopword_list'];
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
	global $wpdb, $relevanssi_variables;
	if ( empty( $term ) ) {
		return false;
	}

	$term = stripslashes( $term );
	$term = esc_sql( $wpdb->esc_like( $term ) );

	$success = $wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO ' . $relevanssi_variables['stopword_table'] . ' (stopword) VALUES (%s)', $term ) ); // WPCS: unprepared SQL ok, Relevanssi table name.

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
