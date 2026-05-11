<?php
/**
 * /lib/deactivate.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * This function clears the scheduled tasks on plugin deactivation.
 */
function relevanssi_deactivate() {
	wp_clear_scheduled_hook( 'relevanssi_update_counts' );
	wp_clear_scheduled_hook( 'relevanssi_trim_logs' );
	wp_clear_scheduled_hook( 'relevanssi_trim_click_logs' );
}
