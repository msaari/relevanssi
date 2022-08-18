<?php
/**
 * /lib/user-searches.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the 'User searches' page.
 */
function relevanssi_search_stats() {
	$relevanssi_hide_branding = get_option( 'relevanssi_hide_branding' );

	if ( 'on' === $relevanssi_hide_branding ) {
		$options_txt = __( 'User searches', 'relevanssi' );
	} else {
		$options_txt = __( 'Relevanssi User Searches', 'relevanssi' );
	}

	if ( isset( $_REQUEST['relevanssi_reset'] ) && current_user_can( 'manage_options' ) ) {
		check_admin_referer( 'relevanssi_reset_logs', '_relresnonce' );
		if ( isset( $_REQUEST['relevanssi_reset_code'] ) ) {
			if ( 'reset' === $_REQUEST['relevanssi_reset_code'] ) {
				$verbose = true;
				relevanssi_truncate_logs( $verbose );
			}
		}
	}

	printf( "<div class='wrap'><h2>%s</h2>", esc_html( $options_txt ) );

	$premium_screens_displayed =
		function_exists( 'relevanssi_handle_insights_screens' )
		? relevanssi_handle_insights_screens( $_REQUEST )
		: false;

	if ( ! $premium_screens_displayed ) {
		if ( 'on' === get_option( 'relevanssi_log_queries' ) ) {
			relevanssi_query_log();
		} else {
			printf( '<p>%s</p>', esc_html__( 'Enable query logging to see stats here.', 'relevanssi' ) );
		}
	}
}

/**
 * Shows the query log with the most common queries
 *
 * Uses relevanssi_total_queries() and relevanssi_date_queries() to fetch the data.
 */
function relevanssi_query_log() {
	global $wpdb, $relevanssi_variables;
	$data = $wpdb->get_results(
		'SELECT LEFT( `time`, 10 ) as `day`, count(*) as `count` ' .
		"FROM {$relevanssi_variables['log_table']} " . // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		'GROUP BY LEFT( `time`, 10 )'
	);

	$labels = array();
	$values = array();
	$from   = gmdate( 'Y-m-d' );
	foreach ( $data as $point ) {
		if ( $point->day < $from ) {
			$from = $point->day;
		}
	}

	wp_verify_nonce( '_relevanssi_nonce', 'relevanssi_user_searches' );

	$from_and_to = relevanssi_from_and_to( $_REQUEST, $from );
	$to          = $from_and_to['to'];
	$from        = $from_and_to['from'];

	foreach ( $data as $point ) {
		if ( $point->day >= $from && $point->day <= $to ) {
			$labels[] = gmdate( 'M j', strtotime( $point->day ) );
			$values[] = $point->count;
		}
	}

	?>
	<form method="post" style="background: white; padding: 10px; margin-top: 20px;">
	<?php
		wp_nonce_field( 'relevanssi_user_searches', '_relevanssi_nonce', true, true );
	?>
	<div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 20px">
		<div>
			<?php echo esc_html__( 'From:', 'relevanssi' ); ?> <input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" />
			<?php echo esc_html__( 'To:', 'relevanssi' ); ?> <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" />
			<input type="submit" value="<?php echo esc_attr( __( 'Filter', 'relevanssi' ) ); ?>" /></p>
		</div>
		<div>
			<input type="submit" value="<?php echo esc_attr( __( 'Year so far', 'relevanssi' ) ); ?>" name="this_year" style="margin-bottom: 10px" />
			<input type="submit" value="<?php echo esc_attr( __( 'This month', 'relevanssi' ) ); ?>" name="this_month" />
			<input type="submit" value="<?php echo esc_attr( __( 'Last month', 'relevanssi' ) ); ?>" name="last_month" />
			<input type="submit" value="<?php echo esc_attr( __( '30 days', 'relevanssi' ) ); ?>" name="last_30" />
			<input type="submit" value="<?php echo esc_attr( __( 'This week', 'relevanssi' ) ); ?>" name="this_week" />
			<input type="submit" value="<?php echo esc_attr( __( 'Last week', 'relevanssi' ) ); ?>" name="last_week" />
			<input type="submit" value="<?php echo esc_attr( __( '7 days', 'relevanssi' ) ); ?>" name="last_7" />
			<input type="submit" value="<?php echo esc_attr( __( 'All history', 'relevanssi' ) ); ?>" name="everything" />
		</div>
	</div>
	</form>
	<?php

	relevanssi_create_line_chart(
		$labels,
		array(
			__( '# of Searches', 'relevanssi' ) => $values,
		)
	);

	$total_queries = relevanssi_total_queries( $from, $to );
	?>
	<div style="background: white; padding: 10px; display: grid; grid-template-columns: 1fr 2fr 2fr; grid-gap: 20px; margin-top: 20px">
		<div>
			<div style="margin-bottom: 20px"><?php esc_html_e( 'Total searches', 'relevanssi' ); ?>
				<span style="display: block; font-size: 42px; font-weight: bolder; line-height: 50px">
					<?php echo intval( $total_queries ); ?>
				</span>
			</div>
			<div style="margin-bottom: 20px"><?php esc_html_e( 'Searches that found nothing', 'relevanssi' ); ?>
				<span style="display: block; font-size: 42px; font-weight: bolder; line-height: 50px">
					<?php echo intval( relevanssi_nothing_found_queries( $from, $to ) ); ?>
				</span>
			</div>
			<?php
			if ( function_exists( 'relevanssi_user_searches_clicks' ) ) {
				relevanssi_user_searches_clicks( $from, $to, $total_queries );
			}
			?>
		</div>
		<div>
			<h3><?php esc_html_e( 'Successful searches', 'relevanssi' ); ?></h3>
			<p><?php esc_html_e( '"Hits" is the average hits this search query has found.', 'relevanssi' ); ?></p>
			<?php
			if ( ! function_exists( 'relevanssi_get_query_clicks' ) ) {
				?>
				<p><?php esc_html_e( 'In order to see the clicks, you need Relevanssi Premium.', 'relevanssi' ); ?></p>
				<?php
			} elseif ( 'on' !== get_option( 'relevanssi_click_tracking' ) ) {
				?>
				<p><?php esc_html_e( 'In order to see the clicks, you need to enable click tracking. Click tracking is not currently enabled, and you\'re not collecting new clicks.', 'relevanssi' ); ?></p>
				<?php
			}
			relevanssi_date_queries( $from, $to, 'good' );
			?>
		</div>
		<div>
			<h3><?php esc_html_e( 'Unsuccessful searches', 'relevanssi' ); ?></h3>
			<p><?php esc_html_e( 'These queries have found no results.', 'relevanssi' ); ?></p>
			<?php relevanssi_date_queries( $from, $to, 'bad' ); ?>
		</div>
	</div>
	<?php

	if ( current_user_can( 'manage_options' ) ) {

		echo '<div style="clear: both"></div>';
		printf( '<h3>%s</h3>', esc_html__( 'Reset Logs', 'relevanssi' ) );
		print( "<form method='post'>" );
		wp_nonce_field( 'relevanssi_reset_logs', '_relresnonce', true, true );

		// Translators: do not translate "reset".
		$message = esc_html__(
			'To reset the logs, type "reset" into the box here and click the Reset button',
			'relevanssi'
		);

		if ( RELEVANSSI_PREMIUM ) {
			// Translators: do not translate "reset".
			$message = esc_html__(
				'To reset the logs, type "reset" into the box here and click the Reset button. This will reset both the search log and the click tracking log.',
				'relevanssi'
			);
		}

		printf(
			'<p><label for="relevanssi_reset_code">%s</label>
			<input type="text" id="relevanssi_reset_code" name="relevanssi_reset_code" />
			<input type="submit" name="relevanssi_reset" value="%s" class="button" /></p></form>',
			$message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped.
			esc_html__( 'Reset', 'relevanssi' )
		);
	}

	echo '</div>';
}

/**
 * Shows the total number of searches on 'User searches' page.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $from The start date.
 * @param string $to   The end date.
 *
 * @return int The number of searches.
 */
function relevanssi_total_queries( string $from, string $to ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(id) FROM $log_table " // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			. 'WHERE time >= %s
			AND time <= %s',
			$from . ' 00:00:00',
			$to . ' 23:59:59'
		)
	);

	return $count;
}

/**
 * Shows the total number of searches on 'User searches' page.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $from The start date.
 * @param string $to   The end date.
 */
function relevanssi_nothing_found_queries( string $from, string $to ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(id) FROM $log_table " // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			. 'WHERE time >= %s
			AND time <= %s
			AND hits = 0',
			$from . ' 00:00:00',
			$to . ' 23:59:59'
		)
	);

	return $count;
}

/**
 * Shows the most common search queries on different time periods.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $from    The beginning date.
 * @param string $to      The ending date.
 * @param string $version If 'good', show the searches that found something; if
 * 'bad', show the searches that didn't find anything. Default 'good'.
 */
function relevanssi_date_queries( string $from, string $to, string $version = 'good' ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	/**
	 * Filters the number of most common queries to show.
	 *
	 * @param int The number of most common queries to show, default 100.
	 */
	$limit = apply_filters( 'relevanssi_user_searches_limit', 100 );

	if ( 'good' === $version ) {
		$queries = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT(id)) as cnt, query, AVG(hits) AS hits ' .
				"FROM $log_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'WHERE time >= %s
				AND time <= %s
				AND hits > 0
				GROUP BY query
				ORDER BY cnt DESC
				LIMIT %d',
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$limit
			)
		);
	}

	if ( 'bad' === $version ) {
		$queries = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT(id)) as cnt, query, hits ' .
				"FROM $log_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'WHERE time >= %s
				AND time <= %s
				AND hits = 0
				GROUP BY query
				ORDER BY cnt DESC
				LIMIT %d',
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$limit
			)
		);
	}

	if ( count( $queries ) > 0 ) {
		if ( 'good' === $version ) {
			printf(
				"<table class='widefat' style='border: none'>
						<thead>
							<tr>
								<th>%s</th>
								<th style='text-align: center'>#</th>
								<th style='text-align: center'>%s</th>
								<th style='text-align: center'>%s</th>
							</tr>
						</thead>
						<tbody>",
				esc_html__( 'Query', 'relevanssi' ),
				esc_html__( 'Hits', 'relevanssi' ),
				esc_html__( 'Clicks', 'relevanssi' )
			);
		} else {
			printf(
				"<table class='widefat' style='border: none'>
						<thead>
							<tr>
								<th>%s</th>
								<th style='text-align: center'>#</th>
							</tr>
						</thead>
						<tbody>",
				esc_html__( 'Query', 'relevanssi' )
			);
		}
		$url = get_bloginfo( 'url' );
		foreach ( $queries as $query ) {
			if ( 'good' === $version && function_exists( 'relevanssi_get_query_clicks' ) ) {
				$clicks = intval( relevanssi_get_query_clicks( $query->query ) );
			} else {
				$clicks = '-';
			}
			$search_parameter = rawurlencode( $query->query );
			/**
			 * Filters the query URL for the user searches page.
			 *
			 * @param string Query URL.
			 */
			$query_url = apply_filters( 'relevanssi_user_searches_query_url', $url . '/?s=' . $search_parameter );

			if ( function_exists( 'relevanssi_insights_link' ) ) {
				$query_link = relevanssi_insights_link( $query );
			} else {
				$query_link = wp_kses( relevanssi_hyphenate( $query->query ), 'strip' );
			}

			if ( 'good' === $version ) {
				printf(
					"<tr>
						<td>%s <a href='%s'><span class='dashicons dashicons-external'></span></a></td>
						<td style='padding: 3px 5px; text-align: center'>%d</td>
						<td style='padding: 3px 5px; text-align: center'>%d</td>
						<td style='padding: 3px 5px; text-align: center'>%s</td>
					</tr>",
					$query_link, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_attr( $query_url ),
					intval( $query->cnt ),
					intval( $query->hits ),
					$clicks // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			} else {
				printf(
					"<tr>
						<td>%s <a href='%s'><span class='dashicons dashicons-external'></span></a></td>
						<td style='padding: 3px 5px; text-align: center'>%d</td>
					</tr>",
					$query_link,  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_attr( $query_url ),
					intval( $query->cnt )
				);
			}
		}
		echo '</tbody></table>';
	}
}
