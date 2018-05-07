<?php
/**
 * /lib/log.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Adds the search query to the log.
 *
 * Logs the search query, trying to avoid bots.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables, used for database table names.
 *
 * @param string $query The search query.
 * @param int    $hits  The number of hits found.
 */
function relevanssi_update_log( $query, $hits ) {
	// Bot filter, by Justin_K.
	// See: http://wordpress.org/support/topic/bot-logging-problem-w-tested-solution.
	$user_agent = '';
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$bots       = array( 'Google' => 'Mediapartners-Google' );

		/**
		 * Filters the bots Relevanssi should block from logs.
		 *
		 * Lets you filter the bots that are blocked from Relevanssi logs.
		 *
		 * @param array $bots An array of bot user agents.
		 */
		$bots = apply_filters( 'relevanssi_bots_to_not_log', $bots );
		foreach ( $bots as $name => $lookfor ) {
			if ( false !== stristr( $user_agent, $lookfor ) ) {
				return;
			}
		}
	}

	/**
	 * Filters the current user for logs.
	 *
	 * The current user is checked before logging a query to omit particular users.
	 * You can use this filter to filter out the user.
	 *
	 * @param object The current user object.
	 */
	$user = apply_filters( 'relevanssi_log_get_user', wp_get_current_user() );
	if ( 0 !== $user->ID && get_option( 'relevanssi_omit_from_logs' ) ) {
		$omit = explode( ',', get_option( 'relevanssi_omit_from_logs' ) );
		if ( in_array( strval( $user->ID ), $omit, true ) ) {
			return;
		}
		if ( in_array( $user->user_login, $omit, true ) ) {
			return;
		}
	}

	$ip = '';
	if ( 'on' === get_option( 'relevanssi_log_queries_with_ip' ) ) {
		/**
		 * Filters the IP address of the searcher.
		 *
		 * Relevanssi may store the IP address of the searches in the logs. If the
		 * setting is enabled, this filter can be used to filter out the IP address
		 * before the log entry is made.
		 *
		 * Do note that storing the IP address may be illegal or get you in GDPR
		 * trouble.
		 *
		 * @param string $ip The IP address, from $_SERVER['REMOTE_ADDR'].
		 */
		$ip = apply_filters( 'relevanssi_remote_addr', $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Filters whether a query should be logged or not.
	 *
	 * This filter can used to determine whether a query should be logged or not.
	 *
	 * @param boolean $ok_to_log  Can the query be logged.
	 * @param string  $query      The actual query string.
	 * @param int     $hits       The number of hits found.
	 * @param string  $user_agent The user agent that made the search.
	 * @param string  $ip         The IP address the search came from (or empty).
	 */
	$ok_to_log = apply_filters( 'relevanssi_ok_to_log', true, $query, $hits, $user_agent, $ip );
	if ( $ok_to_log ) {
		global $wpdb, $relevanssi_variables;

		$wpdb->query(
			$wpdb->prepare( 'INSERT INTO ' . $relevanssi_variables['log_table'] . ' (query, hits, user_id, ip, time) VALUES (%s, %d, %d, %s, NOW())',
			$query, intval( $hits ), $user->ID, $ip )
		); // WPCS: unprepared SQL ok, Relevanssi database table name.
	}
}

/**
 * Trims Relevanssi log table.
 *
 * Trims Relevanssi log table, using the day interval setting from 'relevanssi_trim_logs'.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables, used for database table names.
 */
function relevanssi_trim_logs() {
	global $wpdb, $relevanssi_variables;
	$interval = intval( get_option( 'relevanssi_trim_logs' ) );
	$wpdb->query(
		$wpdb->prepare( 'DELETE FROM ' . $relevanssi_variables['log_table'] . ' WHERE time < TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))',
		$interval )
	); // WPCS: unprepared SQL ok, Relevanssi database table name.
}
