<?php
/**
 * /lib/compatibility/elementor.php
 *
 * Elementor page builder compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_search_ok', 'relevanssi_block_elementor_library', 10, 2 );
add_action( 'relevanssi_pre_the_content', 'relevanssi_block_elementor_add_script_enqueue_action' );
add_action( 'relevanssi_post_the_content', 'relevanssi_block_elementor_remove_script_enqueue_action' );

/**
 * Blocks Relevanssi from interfering with the Elementor Library searches.
 *
 * @param bool     $ok    Should Relevanssi be allowed to process the query.
 * @param WP_Query $query The WP_Query object.
 *
 * @return bool Returns false, if this is an Elementor library search.
 */
function relevanssi_block_elementor_library( bool $ok, WP_Query $query ): bool {
	if ( 'elementor_library' === $query->query_vars['post_type'] ) {
		$ok = false;
	}
	return $ok;
}

/**
 * We need to prevent elementor from enqueuing custom widget scripts to early when relevanssi is running the page builder for creating excerpts. 
 * Otherwise it will break the elementor-frontend scripts.
 */
function relevanssi_block_elementor_add_script_enqueue_action() {
	add_action( 'elementor/frontend/after_render', 'relevanssi_block_elementor_deqeue_elementor_widget_scripts' );
}

function relevanssi_block_elementor_remove_script_enqueue_action() {
	remove_action( 'elementor/frontend/after_render', 'relevanssi_block_elementor_deqeue_elementor_widget_scripts' );
}

/**
 * As elementor provides no hook or filter to disable script enqueing in Element_Base->print_element() we are dequeuing the scripts right after they are enqueued too early
 */
function relevanssi_block_elementor_deqeue_elementor_widget_scripts($widget) {
	foreach ( $widget->get_script_depends() as $script ) {
		wp_dequeue_script( $script );
	}
}