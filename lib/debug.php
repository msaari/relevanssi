<?php
/**
 * /lib/debug.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

 /**
  * Checks if Relevanssi debug mode is enabled.
  *
  * Debug mode is enabled by setting RELEVANSSI_DEBUG to true or with the
  * 'relevanssi_debug' query parameter if the debug mode is allowed from the
  * settings.
  *
  * @return boolean True if debug mode is enabled, false if not.
  */
function relevanssi_is_debug() : bool {
	$debug = false;
	if ( defined( 'RELEVANSSI_DEBUG' ) && RELEVANSSI_DEBUG ) {
		$debug = true;
	}
	if ( isset( $_REQUEST['relevanssi_debug'] ) && 'on' === get_option( 'relevanssi_debugging_mode' ) ) {
		$debug = true;
	}
	return $debug;
}

function relevanssi_debug_posts( $posts ) {
	echo '<h2>Posts</h2>';
	foreach ( $posts as $post ) {
		if ( ! is_object( $post ) ) {
			echo "$post\n";
		} else {
			echo "<p>$post->ID: $post->post_title<br />";
			echo "$post->post_type – $post->post_status – $post->relevance_score<br />";
			echo "relevanssi_link: $post->relevanssi_link<br />";
			echo 'the_permalink(): ';
			the_permalink( $post->ID );
			echo '<br />get_permalink(): ' . get_permalink( $post );
			echo "</p>";
		}
	}
}

function relevanssi_debug_array( $array, $title ) {
	echo '<h2>' . $title . '</h2>';
	echo '<pre>';
	print_r( $array );
	echo '</pre>';
}

function relevanssi_debug_string( $string, $title ) {
	echo '<h2>' . $title . '</h2>';
	echo '<pre>' . $string . '</pre>';
}

add_action( 'wp', 'relevanssi_debug_post' );
function relevanssi_debug_post() {
	if ( ! is_singular() || ! relevanssi_is_debug() ) {
		return;
	}
	global $post;
	echo '<h1>' . $post->post_title . ' (' . $post->ID . ')</h1>';

	echo '<h2>Index</h2>';
	echo relevanssi_generate_how_relevanssi_sees( $post->ID, true, 'post' );

	echo '<h2>Database</h2>';
	echo '<pre>' . relevanssi_generate_db_post_view( $post->ID ) . '</pre>';

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
