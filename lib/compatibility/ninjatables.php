<?php
/**
 * /lib/compatibility/ninjatables.php
 *
 * Ninja Tables compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_post_content', 'relevanssi_index_ninja_tables' );

/**
 * Indexes Ninja Tables table contents.
 *
 * Uses regular expression matching to find all the Ninja Tables shortcodes in
 * the post content and then uses relevanssi_index_ninja_table() to convert the
 * tables into strings.
 *
 * @uses $wpdb WordPress database abstraction.
 * @see relevanssi_index_ninja_table()
 *
 * @param string $content The post content.
 *
 * @return string Post content with the Ninja Tables data.
 */
function relevanssi_index_ninja_tables( $content ) {
	$m = preg_match_all(
		'/.*\[ninja_tables.*?id=["\'](\d+)["\'].*?\]/im',
		$content,
		$matches,
		PREG_PATTERN_ORDER
	);
	if ( ! $m ) {
		return $content;
	}
	foreach ( $matches[1] as $table_id ) {
		$content .= ' ' . relevanssi_index_ninja_table( $table_id );
	}

	return $content;
}

/**
 * Creates a string containing a Ninja Table table contents.
 *
 * The string contains the caption and the values from each row. The table
 * title and description are also included, if they are set visible on the
 * frontend.
 *
 * @uses $wpdb WordPress database abstraction.
 *
 * @param int $table_id The table ID.
 *
 * @return string The table content as a string.
 */
function relevanssi_index_ninja_table( $table_id ) {
	global $wpdb;
	$table_post     = get_post( $table_id );
	$table_settings = get_post_meta( $table_id, '_ninja_table_settings', true );
	$table_contents = '';

	if ( isset( $table_settings['show_description'] ) && '1' === $table_settings['show_description'] ) {
		$table_contents .= ' ' . $table_post->post_content;
	}
	if ( isset( $table_settings['show_title'] ) && '1' === $table_settings['show_title'] ) {
		$table_contents .= ' ' . $table_post->post_title;
	}
	$table_contents .= ' ' . get_post_meta( $table_id, '_ninja_table_caption', true );

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT value FROM {$wpdb->prefix}ninja_table_items WHERE table_id=%d",
			$table_id
		)
	);
	foreach ( $rows as $row ) {
		$table_contents .= ' ' . implode( ' ', array_values( get_object_vars( json_decode( $row->value ) ) ) );
	}

	return $table_contents;
}
