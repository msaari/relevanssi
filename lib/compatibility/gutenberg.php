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

/**
 * Registers rest_after_insert_{post_type} actions for all indexed post types.
 *
 * Runs on `admin_init` action hook and registers the function
 * `relevanssi_save_gutenberg_postdata` for all indexed post types.
 *
 * @see relevanssi_save_gutenberg_postdata
 */
function relevanssi_register_gutenberg_actions() {
	if ( ! RELEVANSSI_PREMIUM ) {
		return;
	}
	$index_post_types = get_option( 'relevanssi_index_post_types', array() );
	array_walk(
		$index_post_types,
		function ( $post_type ) {
			if ( 'bogus' !== $post_type ) {
				add_action(
					'rest_after_insert_' . $post_type,
					'relevanssi_save_gutenberg_postdata'
				);
			}

		}
	);
}

add_filter( 'relevanssi_post_content', 'relevanssi_gutenberg_block_rendering', 10, 2 );

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
 * @param string $content     The post content.
 * @param object $post_object The post object.
 *
 * @return string The post content with the rendered content added.
 */
function relevanssi_gutenberg_block_rendering( $content, $post_object ) {
	/**
	 * Filters whether the blocks are rendered or not.
	 *
	 * If this filter returns false, the blocks in this post are not rendered,
	 * and the post content is returned as such.
	 *
	 * @param boolean If true, render the blocks. Default true.
	 * @param object  The post object.
	 */
	if ( ! apply_filters( 'relevanssi_render_blocks', true, $post_object ) ) {
		return $content;
	}
	$blocks = parse_blocks( $content );
	$output = '';

	foreach ( $blocks as $block ) {
		/**
		 * Filters the Gutenberg block before it is rendered.
		 *
		 * If the block is non-empty after the filter and it's className
		 * parameter is not 'relevanssi_noindex', it will be passed on to the
		 * render_block() function for rendering.
		 *
		 * @see render_block
		 *
		 * @param array $block The Gutenberg block element.
		 */
		$block = apply_filters( 'relevanssi_block_to_render', $block );

		if ( ! $block ) {
			continue;
		}

		if (
			! isset( $block['attrs']['className'] )
			|| false === strstr( $block['attrs']['className'], 'relevanssi_noindex' )
			) {
			/**
			 * Filters the Gutenberg block after it is rendered.
			 *
			 * The value is the output from render_block( $block ). Feel free to
			 * modify it as you wish.
			 *
			 * @see render_block
			 *
			 * @param string The rendered block content.
			 * @param array  $block The Gutenberg block being rendered.
			 *
			 * @return string The filtered block content.
			 */
			$output .= apply_filters( 'relevanssi_rendered_block', render_block( $block ), $block );
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
