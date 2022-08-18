<?php
/**
 * /lib/compatibility/tablepress.php
 *
 * TablePress compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Enables TablePress shortcodes for Relevanssi indexing.
 *
 * @return null|object The TablePress controller.
 */
function relevanssi_enable_tablepress_shortcodes() {
	$my_tablepress_controller = null;
	if ( defined( 'TABLEPRESS_ABSPATH' ) ) {
		if ( ! isset( TablePress::$model_options ) ) {
			include_once TABLEPRESS_ABSPATH . 'classes/class-model.php';
			include_once TABLEPRESS_ABSPATH . 'models/model-options.php';
			TablePress::$model_options = new TablePress_Options_Model();
		}
		$my_tablepress_controller = TablePress::load_controller( 'frontend' );
		$my_tablepress_controller->init_shortcodes();
	}
	return $my_tablepress_controller;
}

add_filter( 'relevanssi_post_content', 'relevanssi_table_filter' );

/**
 * Replaces the [table_filter] shortcodes with [table].
 *
 * The shortcode filter extension adds a [table_filter] shortcode which is not
 * compatible with Relevanssi. This function switches those to the normal
 * [table] shortcode which works better.
 *
 * @param string $content The post content.
 *
 * @return string The fixed post content.
 */
function relevanssi_table_filter( $content ) {
	$content = str_replace( '[table_filter', '[table', $content );
	return $content;
}
