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
 *
 * @return boolean True if logged, false if not logged.
 */
function relevanssi_update_log( $query, $hits ) {
	if ( empty( $query ) ) {
		return false;
	}

	/**
	 * Filters the current user for logs.
	 *
	 * The current user is checked before logging a query to omit particular users.
	 * You can use this filter to filter out the user.
	 *
	 * @param WP_User The current user object.
	 */
	$user       = apply_filters( 'relevanssi_log_get_user', wp_get_current_user() );
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

	if ( ! relevanssi_is_ok_to_log( $user ) ) {
		return false;
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

		if ( ! $user ) {
			$session_id = md5( $user_agent . round( time() / 600 ) * 600 );
		} else {
			$session_id = md5( $user->ID . round( time() / 600 ) * 600 );
		}

		relevanssi_delete_session_logs( $session_id, $query );

		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . $relevanssi_variables['log_table'] . ' (query, hits, user_id, ip, time, session_id) VALUES (%s, %d, %d, %s, NOW(), %s)', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query,
				intval( $hits ),
				$user->ID,
				$ip,
				$session_id
			)
		);

		return true;
	}
	return false;
}

/**
 * Deletes partial string match log entries from the same session.
 *
 * Deletes all log entries that match the beginning of the current query. This
 * is used to avoid logging partial string matches from live search.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables, used
 * for database table names.
 *
 * @param string $session_id The session ID.
 * @param string $query      The current query.
 */
function relevanssi_delete_session_logs( string $session_id, string $query ) {
	global $wpdb, $relevanssi_variables;

	// Get all log entries with the same session ID.
	$session_queries = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM ' . $relevanssi_variables['log_table'] . ' WHERE session_id = %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$session_id
		)
	);

	if ( $session_queries ) {
		$deleted_entries = array();
		foreach ( $session_queries as $session_query ) {
			// If current query begins with the session query, remove the $session_query.
			if ( $query !== $session_query->query && 0 === relevanssi_stripos( $query, $session_query->query ) ) {
				$deleted_entries[] = $session_query->id;
			}
		}
		if ( $deleted_entries ) {
			$wpdb->query(
				'DELETE FROM ' . $relevanssi_variables['log_table'] . ' WHERE id IN (' . implode( ',', $deleted_entries ) . ')' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}
	}
}

/**
 * Trims Relevanssi log table.
 *
 * Trims Relevanssi log table, using the day interval setting from 'relevanssi_trim_logs'.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables, used for database table names.
 *
 * @return int|bool Number of rows deleted, or false on error.
 */
function relevanssi_trim_logs() {
	global $wpdb, $relevanssi_variables;
	$interval = intval( get_option( 'relevanssi_trim_logs' ) );
	return $wpdb->query(
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
		$time    = $row->time;
		$query   = $row->query;
		$id      = $row->id;
		$ip      = $row->ip;
		$hits    = $row->hits;
		$session = $row->session_id;

		$item_id     = "relevanssi_logged_search-{$id}";
		$group_id    = 'relevanssi_logged_searches';
		$group_label = __( 'Logged searches', 'relevanssi' );
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
			array(
				'name'  => __( 'Session ID', 'relevanssi' ),
				'value' => $session,
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
 * @uses relevanssi_output_exported_log
 *
 * @since 2.2
 */
function relevanssi_export_log() {
	global $wpdb, $relevanssi_variables;

	$data = $wpdb->get_results( 'SELECT * FROM ' . $relevanssi_variables['log_table'], ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	relevanssi_output_exported_log(
		'relevanssi_log.csv',
		$data,
		__( 'No search keywords logged.', 'relevanssi' )
	);
}

/**
 * Prints out the log.
 *
 * Does the exporting work for log exports.
 *
 * @param string $filename The filename to use.
 * @param array  $data     The data to export.
 * @param string $message  The message to print if there is no data.
 */
function relevanssi_output_exported_log( string $filename, array $data, string $message ) {
	$now = gmdate( 'D, d M Y H:i:s' );

	header( 'Expires: Tue, 03 Jul 2001 06:00:00 GMT' );
	header( 'Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate' );
	header( "Last-Modified: {$now} GMT" );
	header( 'Content-Type: application/force-download' );
	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Type: application/download' );
	header( "Content-Disposition: attachment;filename={$filename}" );
	header( 'Content-Transfer-Encoding: binary' );

	ob_start();
	$df = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( empty( $data ) ) {
		fputcsv( $df, array( $message ) );
		die();
	}
	fputcsv( $df, array_keys( reset( $data ) ) );
	foreach ( $data as $row ) {
		fputcsv( $df, $row );
	}
	fclose( $df ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	die();
}

/**
 * Checks if logging the query is ok.
 *
 * Returns false if the user agent is on the blocked bots list or if the
 * current user is on the relevanssi_omit_from_logs option list.
 *
 * @param WP_User $user The current user. If null, gets the value from
 * wp_get_current_user().
 *
 * @return boolean True, if the user is not a bot or not on the omit list.
 */
function relevanssi_is_ok_to_log( $user = null ): bool {
	if ( relevanssi_user_agent_is_bot() ) {
		return false;
	}

	if ( ! $user ) {
		$user = wp_get_current_user();
	}

	if ( 0 !== $user->ID && get_option( 'relevanssi_omit_from_logs' ) ) {
		$omit = explode( ',', get_option( 'relevanssi_omit_from_logs' ) );
		$omit = array_map( 'trim', $omit );
		if ( in_array( strval( $user->ID ), $omit, true ) ) {
			return false;
		}
		if ( in_array( $user->user_login, $omit, true ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Deletes a query from log.
 *
 * @param string $query The query to delete.
 */
function relevanssi_delete_query_from_log( string $query ) {
	global $wpdb, $relevanssi_variables;

	$deleted = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$relevanssi_variables['log_table']} WHERE query = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			stripslashes( $query )
		)
	);

	if ( $deleted ) {
		printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			sprintf(
				// Translators: %s is the stopword.
				esc_html__(
					"The query '%s' deleted from the log.",
					'relevanssi'
				),
				esc_html( stripslashes( $query ) )
			)
		);
	} else {
		printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			sprintf(
				// Translators: %s is the stopword.
				esc_html__(
					"Couldn't remove the query '%s' from the log.",
					'relevanssi'
				),
				esc_html( stripslashes( $query ) )
			)
		);
	}
}
