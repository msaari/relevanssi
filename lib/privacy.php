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
add_filter( 'wp_privacy_personal_data_exporters', 'relevanssi_register_exporter', 10 );
add_filter( 'wp_privacy_personal_data_erasers', 'relevanssi_register_eraser', 10 );

/**
 * Registers the Relevanssi privacy policy information.
 *
 * @since 4.0.10
 */
function relevanssi_register_privacy_policy() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}
	$name = 'Relevanssi';
	if ( RELEVANSSI_PREMIUM ) {
		$name .= ' Premium';
	}
	$content = '';
	if ( 'on' === get_option( 'relevanssi_log_queries' ) ) {
		$content = '<h2>' . __( 'What personal data we collect and why we collect it' ) . '</h2>';
		if ( 'on' === get_option( 'relevanssi_log_queries_with_ip' ) ) {
			$content .= '<h3>' . __( 'IP address for searches', 'relevanssi' ) . '</h3>';
			$content .= '<p>' . __( 'All searches performed using the internal site search are logged in the database, including the following information: the search query, the number of hits found, user ID for users who are logged in, date and time and the IP address. The IP address is stored for security and auditing purposes.', 'relevanssi' ) . '</p>';
		} else {
			$content .= '<p>' . __( 'All searches performed using the internal site search are logged in the database, including the following information: the search query, the number of hits found, user ID for users who are logged in and date and time.', 'relevanssi' ) . '</p>';
		}
		$interval = intval( get_option( 'relevanssi_trim_logs' ) );
		$content .= '<h2>' . __( 'How long we retain your data' ) . '</h2>';
		if ( $interval > 0 ) {
			// Translators: %d is the number of days.
			$content .= '<p>' . sprintf( __( 'The search logs are stored for %d days before they are automatically removed.', 'relevanssi' ), $interval ) . '</p>';
		} else {
			$content .= '<p>' . __( 'The search logs are stored indefinitely.', 'relevanssi' ) . '</p>';
		}
	}
	wp_add_privacy_policy_content( $name, $content );
}

/**
 * Registers the Relevanssi data exporter.
 *
 * @since 4.0.10
 *
 * @param array $exporters The exporters array.
 *
 * @return array The exporters array, with Relevanssi added.
 */
function relevanssi_register_exporter( $exporters ) {
	$exporters['relevanssi'] = array(
		'exporter_friendly_name' => __( 'Relevanssi Search Logs' ),
		'callback'               => 'relevanssi_privacy_exporter',
	);
	return $exporters;
}

/**
 * Registers the Relevanssi data eraser.
 *
 * @since 4.0.10
 *
 * @param array $erasers The erasers array.
 *
 * @return array The erasers array, with Relevanssi added.
 */
function relevanssi_register_eraser( $erasers ) {
	$erasers['relevanssi'] = array(
		'eraser_friendly_name' => __( 'Relevanssi Search Logs' ),
		'callback'             => 'relevanssi_privacy_eraser',
	);
	return $erasers;
}

/**
 * Exports the log entries based on user email.
 *
 * @since 4.0.10
 *
 * @param string $email_address The user email address.
 * @param int    $page          The page number, default 1.
 *
 * @return array Two-item array: 'done' is a Boolean that tells if the exporter is
 * done, 'data' contains the actual data.
 */
function relevanssi_privacy_exporter( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		// No user found.
		return array(
			'done' => true,
			'data' => array(),
		);
	} else {
		$result = relevanssi_export_log_data( $user->ID, $page );
		return array(
			'done' => $result['done'],
			'data' => $result['data'],
		);
	}
}

/**
 * Erases the log entries based on user email.
 *
 * @since 4.0.10
 *
 * @param string $email_address The user email address.
 * @param int    $page          The page number, default 1.
 *
 * @return array Four-item array: 'items_removed' is a Boolean that tells if
 * something was removed, 'done' is a Boolean that tells if the eraser is done,
 * 'items_retained' is always false, 'messages' is always an empty array.
 */
function relevanssi_privacy_eraser( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', $email_address );
	if ( ! $user ) {
		// No user found.
		return array(
			'items_removed'  => false,
			'done'           => true,
			'items_retained' => false,
			'messages'       => array(),
		);
	} else {
		$result = relevanssi_erase_log_data( $user->ID, $page );
		return array(
			'items_removed'  => $result['items_removed'],
			'done'           => $result['done'],
			'items_retained' => false,
			'messages'       => array(),
		);
	}
}
