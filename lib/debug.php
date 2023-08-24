<?php
/**
 * /lib/debug.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'wp', 'relevanssi_debug_post' );

/**
 * Checks if Relevanssi debug mode is enabled.
 *
 * Debug mode is enabled by setting RELEVANSSI_DEBUG to true or with the
 * 'relevanssi_debug' query parameter if the debug mode is allowed from the
 * settings.
 *
 * @return boolean True if debug mode is enabled, false if not.
 */
function relevanssi_is_debug(): bool {
	$debug = false;
	if ( defined( 'RELEVANSSI_DEBUG' ) && RELEVANSSI_DEBUG ) {
		$debug = true;
	}
	if ( isset( $_REQUEST['relevanssi_debug'] ) && 'on' === get_option( 'relevanssi_debugging_mode' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$debug = true;
	}
	return $debug;
}

/**
 * Adds the debug information to the search results.
 *
 * Displays the found posts.
 *
 * @param array $posts The search results.
 */
function relevanssi_debug_posts( $posts ) {
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<h2>Posts</h2>';
	foreach ( $posts as $post ) {
		if ( ! is_object( $post ) ) {
			echo "$post\n";
		} else {
			echo "<p>$post->ID: $post->post_title<br />";
			echo "$post->post_type – $post->post_status – $post->relevance_score<br />";
			property_exists( $post, 'relevanssi_link' ) && print( "relevanssi_link: $post->relevanssi_link<br />" );
			echo 'the_permalink(): ';
			the_permalink( $post->ID );
			echo '<br />get_permalink(): ' . get_permalink( $post );
			echo '</p>';
		}
	}
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Prints out an array in a preformatted block.
 *
 * @param array  $array_value The array to print.
 * @param string $title       The title for the array.
 */
function relevanssi_debug_array( $array_value, $title ) {
	echo '<h2>' . esc_html( $title ) . '</h2>';
	echo '<pre>';
	print_r( $array_value ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	echo '</pre>';
}

/**
 * Prints out a string in a preformatted block.
 *
 * @param string $str   The string to print.
 * @param string $title The title for the string.
 */
function relevanssi_debug_string( $str, $title ) {
	echo '<h2>' . esc_html( $title ) . '</h2>';
	echo '<pre>' . esc_html( $str ) . '</pre>';
}

/**
 * Prints out the Relevanssi debug information for a post.
 *
 * This function is called by the 'wp' action, so it's executed on every page
 * load.
 */
function relevanssi_debug_post() {
	if ( ! is_singular() || ! relevanssi_is_debug() ) {
		return;
	}
	global $post;
	echo '<h1>' . esc_html( $post->post_title ) . ' (' . intval( $post->ID ) . ')</h1>';

	echo '<h2>Index</h2>';
	echo relevanssi_generate_how_relevanssi_sees( $post->ID, true, 'post' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	echo '<h2>Database</h2>';
	echo '<pre>' . relevanssi_generate_db_post_view( $post->ID ) . '</pre>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	relevanssi_debug_array( get_post_meta( $post->ID ), 'Post meta' );

	exit();
}

/**
 * Generates the debugging view for a post.
 *
 * @param int $post_id ID of the post.
 *
 * @return string The debugging view in a div container.
 */
function relevanssi_generate_db_post_view( int $post_id ) {
	global $wpdb;

	$element = '<div id="relevanssi_db_view_container">';

	$post_object = get_post( $post_id );

	if ( ! $post_object ) {
		$element .= '<p>' . esc_html__( 'Post not found', 'relevanssi' ) . '</p>';
		$element .= '</div>';
		return $element;
	}

	$element .= '<p>' . esc_html( $post_object->post_content ) . '</p>';

	$element .= '</div>';
	return $element;
}

/**
 * Prints out the Relevanssi debug information for search settings.
 */
function relevanssi_debug_search_settings() {
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<h2>Relevanssi searching settings</h2>';
	echo '<p>';

	$value = get_option( 'relevanssi_fuzzy' );
	echo "relevanssi_fuzzy: $value<br />";

	$value = get_option( 'relevanssi_implicit_operator' );
	echo "relevanssi_implicit_operator: $value<br />";

	$value = get_option( 'relevanssi_disable_or_fallback' );
	echo "relevanssi_disable_or_fallback: $value<br />";

	$value = get_option( 'relevanssi_throttle' );
	echo "relevanssi_throttle: $value<br />";

	$value = get_option( 'relevanssi_throttle_limit' );
	echo "relevanssi_throttle_limit: $value<br />";

	$value = get_option( 'relevanssi_default_orderby' );
	echo "relevanssi_default_orderby: $value<br />";

	echo '</p>';
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Returns true if RELEVANSSI_DEBUG, WP_DEBUG and WP_DEBUG_DISPLAY are true.
 *
 * @return bool True if debug mode is on.
 */
function relevanssi_log_debug(): bool {
	return defined( 'RELEVANSSI_DEBUG' ) && RELEVANSSI_DEBUG
		&& defined( 'WP_DEBUG' ) && WP_DEBUG
		&& defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
}
