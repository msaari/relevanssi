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
