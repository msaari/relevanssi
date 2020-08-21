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
	if ( empty( $query ) ) {
		return;
	}

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
			$wpdb->prepare(
				'INSERT INTO ' . $relevanssi_variables['log_table'] . ' (query, hits, user_id, ip, time) VALUES (%s, %d, %d, %s, NOW())', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query,
				intval( $hits ),
				$user->ID,
				$ip
			)
		);
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
		$wpdb->prepare(
			'DELETE FROM ' . $relevanssi_variables['log_table'] . ' WHERE time < TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$interval
		)
	);
}

/**
 * Generates the user export data.
 *
 * @since 4.0.10
 *
 * @param int $user_id The user ID to export.
 * @param int $page    Paging to avoid time outs.
 *
 * @return array Two-item array: 'done' is a Boolean that tells if the exporter is
 * done, 'data' contains the actual data.
 */
function relevanssi_export_log_data( $user_id, $page ) {
	global $wpdb, $relevanssi_variables;

	$page = (int) $page;
	if ( $page < 1 ) {
		$page = 1;
	}
	$limit    = 500;
	$offset   = $limit * ( $page - 1 );
	$log_data = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM ' . $relevanssi_variables['log_table'] . ' WHERE user_id = %d LIMIT %d OFFSET %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id,
			$limit,
			$offset
		)
	);

	$export_items = array();

	foreach ( $log_data as $row ) {
		$time  = $row->time;
		$query = $row->query;
		$id    = $row->id;
		$ip    = $row->ip;
		$hits  = $row->hits;

		$item_id     = "relevanssi_logged_search-{$id}";
		$group_id    = 'relevanssi_logged_searches';
		$group_label = __( 'Logged seaches', 'relevanssi' );
		$data        = array(
			array(
				'name'  => __( 'Time', 'relevanssi' ),
				'value' => $time,
			),
			array(
				'name'  => __( 'Query', 'relevanssi' ),
				'value' => $query,
			),
			array(
				'name'  => __( 'Hits found', 'relevanssi' ),
				'value' => $hits,
			),
			array(
				'name'  => __( 'IP address', 'relevanssi' ),
				'value' => $ip,
			),
		);

		$export_items[] = array(
			'group_id'    => $group_id,
			'group_label' => $group_label,
			'item_id'     => $item_id,
			'data'        => $data,
		);
	}

	$done = false;
	if ( count( $log_data ) < $limit ) {
		$done = true;
	}

	return array(
		'done' => $done,
		'data' => $export_items,
	);
}

/**
 * Erases the user log data.
 *
 * @since 4.0.10
 *
 * @param int $user_id The user ID to erase.
 * @param int $page    Paging to avoid time outs.
 *
 * @return array Four-item array: 'items_removed' is a Boolean that tells if
 * something was removed, 'done' is a Boolean that tells if the eraser is done,
 * 'items_retained' is always false, 'messages' is always an empty array.
 */
function relevanssi_erase_log_data( $user_id, $page ) {
	global $wpdb, $relevanssi_variables;

	$page = (int) $page;
	if ( $page < 1 ) {
		$page = 1;
	}
	$limit        = 500;
	$rows_removed = $wpdb->query(
		$wpdb->prepare(
			'DELETE FROM ' . $relevanssi_variables['log_table'] . ' WHERE user_id = %d LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id,
			$limit
		)
	);

	$done = false;
	if ( $rows_removed < $limit ) {
		$done = true;
	}
	$items_removed = false;
	if ( $rows_removed > 0 ) {
		$items_removed = true;
	}

	return array(
		'items_removed'  => $items_removed,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => $done,
	);
}

/**
 * Prints out the Relevanssi log as a CSV file.
 *
 * Exports the whole Relevanssi search log as a CSV file.
 *
 * @since 2.2
 */
function relevanssi_export_log() {
	global $wpdb, $relevanssi_variables;

	$now      = gmdate( 'D, d M Y H:i:s' );
	$filename = 'relevanssi_log.csv';

	header( 'Expires: Tue, 03 Jul 2001 06:00:00 GMT' );
	header( 'Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate' );
	header( "Last-Modified: {$now} GMT" );
	header( 'Content-Type: application/force-download' );
	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Type: application/download' );
	header( "Content-Disposition: attachment;filename={$filename}" );
	header( 'Content-Transfer-Encoding: binary' );

	$data = $wpdb->get_results( 'SELECT * FROM ' . $relevanssi_variables['log_table'], ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	ob_start();
	$df = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $df, array_keys( reset( $data ) ) );
	foreach ( $data as $row ) {
		fputcsv( $df, $row );
	}
	fclose( $df ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	die();
}
