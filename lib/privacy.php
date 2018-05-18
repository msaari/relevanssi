<?php
/**
 * /lib/privacy.php
 *
 * Privacy policy features.
 *
 * @since 4.0.10
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'admin_init', 'relevanssi_register_privacy_policy' );

/**
 * Registers the Relevanssi privacy policy information.
 *
 * @since 4.0.10
 */
function relevanssi_register_privacy_policy() {
	$name = 'Relevanssi';
	if ( RELEVANSSI_PREMIUM ) {
		$name .= ' Premium';
	}
	if ( 'on' === get_option( 'relevanssi_log_queries' ) ) {
		$content = '<h2>' . __( 'What personal data we collect and why we collect it' ) . '</h2>';
		if ( 'on' === get_option( 'relevanssi_log_queries_with_ip' ) ) {
			$content .= '<h3>' . __( 'IP address for searches', 'relevanssi' ) . '</h3>';
			$content .= '<p>' . __( "All searches performed using the internal site search are logged in the database, including the following information: the search query, the number of hits found, date and time and the IP address. The IP address is stored for security and auditing purposes. There's no way to connect the IP address to a particular WordPress user profile.", 'relevanssi' ) . '</p>';
		} else {
			$content .= '<p>' . __( "All searches performed using the internal site search are logged in the database, but no personal information is stored in the logs. There's no way to connect to searches to particular users.", 'relevanssi' ) . '</p>';
		}
		$interval = intval( get_option( 'relevanssi_trim_logs' ) );
		$content .= '<h2>' . __( 'How long we retain your data' ) . '</h2>';
		if ( $interval > 0 ) {
			// Translators: %d is the number of days.
			$content .= '<p>' . sprintf( __( 'The search logs are stored for %d days before they are automatically removed.', 'relevanssi' ), $interval ) . '</p>';
		} else {
			$content .= '<p>' . sprintf( __( 'The search logs are stored indefinitely.', 'relevanssi' ), $interval ) . '</p>';
		}
	}
	wp_add_privacy_policy_content( $name, $content );
}
