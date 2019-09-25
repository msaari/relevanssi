<?php
/**
 * /lib/compatibility/gutenberg.php
 *
 * Gutenberg compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

if ( RELEVANSSI_PREMIUM ) {
	// Gutenberg causes duplicate postmeta to appear in posts. This will remove the
	// extras when a post is saved.
	add_action( 'save_post', 'relevanssi_remove_duplicate_postmeta', 100 );
}

add_filter( 'relevanssi_post_content', 'relevanssi_gutenberg_block_rendering', 10 );

/**
 * Renders Gutenberg blocks.
 *
 * Renders all sorts of Gutenberg blocks, including reusable blocks and ACF
 * blocks. Also enables basic Gutenberg deindexing: you can add an extra CSS
 * class 'relevanssi_noindex' to a block to stop it from being indexed by
 * Relevanssi. This function is essentially the same as core do_blocks().
 *
 * @see do_blocks()
 *
 * @param string $content The post content.
 *
 * @return string The post content with the rendered content added.
 */
function relevanssi_gutenberg_block_rendering( $content ) {
	$blocks = parse_blocks( $content );
	$output = '';

	foreach ( $blocks as $block ) {
		if ( ! isset( $block['attrs']['className'] ) || strstr( $block['attrs']['className'], 'relevanssi_noindex' ) === false ) {
			$output .= render_block( $block );
		}
	}

	// If there are blocks in this content, we shouldn't run wpautop() on it later.
	$priority = has_filter( 'the_content', 'wpautop' );
	if ( false !== $priority && doing_filter( 'the_content' ) && has_blocks( $content ) ) {
		remove_filter( 'the_content', 'wpautop', $priority );
		add_filter( 'the_content', '_restore_wpautop_hook', $priority + 1 );
	}

	return $output;
}
