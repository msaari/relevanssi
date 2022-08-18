<?php
/**
 * /lib/compatibility/wp-file-download.php
 *
 * WP File Download compatibility features. Compatibility with WPFD checked for
 * version 4.5.4.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_content_to_index', 'relevanssi_wpfd_content', 10, 2 );
add_action( 'wpfd_file_indexed', 'relevanssi_wpfd_index' );

/**
 * Adds the WPFD indexed content to wpfd_file posts.
 *
 * Fetches the words from wpfd_words. wpfd_index.tid is the post ID, wpfd_index.id is
 * then used to get the wpfd_docs.id, that is used to get the wpfd_vectors.did which
 * can then be used to fetch the correct words from wpfd_words. This function is
 * hooked onto relevanssi_content_to_index filter hook.
 *
 * @param string $content The post content as a string.
 * @param object $post    The post object.
 *
 * @return string The post content with the words added to the end.
 */
function relevanssi_wpfd_content( $content, $post ) {
	$wpfd_search_config = get_option( '_wpfd_global_search_config', null );
	if ( 'wpfd_file' === $post->post_type ) {
		if ( $wpfd_search_config && isset( $wpfd_search_config['plain_text_search'] ) && $wpfd_search_config['plain_text_search'] ) {
			global $wpdb;
			$words    = $wpdb->get_col(
				"SELECT word
				FROM {$wpdb->prefix}wpfd_words, {$wpdb->prefix}wpfd_docs, {$wpdb->prefix}wpfd_index, {$wpdb->prefix}wpfd_vectors " .
				"WHERE {$wpdb->prefix}wpfd_index.tid = {$post->ID} " . // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
				"	AND {$wpdb->prefix}wpfd_docs.index_id = {$wpdb->prefix}wpfd_index.id
					AND {$wpdb->prefix}wpfd_docs.id = {$wpdb->prefix}wpfd_vectors.did
					AND {$wpdb->prefix}wpfd_vectors.wid = {$wpdb->prefix}wpfd_words.id"
			);
			$content .= implode( ' ', $words );
		}
	}
	return $content;
}

/**
 * Runs Relevanssi indexing after WPFD indexing is done.
 *
 * @param int $wpfd_id The WPFD post index.
 */
function relevanssi_wpfd_index( $wpfd_id ) {
	global $wpdb;
	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT tid FROM {$wpdb->prefix}wpfd_index WHERE id=%d", $wpfd_id ) );
	relevanssi_insert_edit( $post_id );
}
