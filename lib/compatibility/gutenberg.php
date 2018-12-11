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

add_filter( 'relevanssi_post_content', 'relevanssi_gutenberg_block_rendering', 10, 2 );

/**
 * Renders Gutenberg reusable blocks.
 *
 * Gutenberg Reusable Blocks appear as comments in the post content. This function
 * picks up the comments and renders the blocks.
 *
 * @param string $content The post content.
 * @param object $post    The post object.
 *
 * @return string The post content with the rendered content added.
 */
function relevanssi_gutenberg_block_rendering( $content, $post ) {
	return do_blocks( $content );
}
