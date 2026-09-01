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
	$clear_api_key_tracking_events = function () {
		wp_clear_scheduled_hook( 'relevanssi_premium_track_api_key' );
		wp_clear_scheduled_hook( 'relevanssi_premium_track_network_api_keys' );
	};
	if ( function_exists( 'relevanssi_premium_api_key_tracking_on_cron_site' ) ) {
		relevanssi_premium_api_key_tracking_on_cron_site( $clear_api_key_tracking_events );
	} else {
		$clear_api_key_tracking_events();
	}
}
