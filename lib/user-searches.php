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

	printf( "<div class='wrap' id='indexing_tab_consolidated'><h2>%s</h2>", esc_html( $options_txt ) );

	$premium_screens_displayed =
		function_exists( 'relevanssi_handle_insights_screens' )
		? relevanssi_handle_insights_screens( $_REQUEST )
		: false;

	if ( ! $premium_screens_displayed ) {
		if ( 'on' === get_option( 'relevanssi_log_queries' ) ) {
			relevanssi_query_log();
		} else {
			printf( '<div class="relevanssi-notice relevanssi-notice-info"><p>%s</p></div>', esc_html__( 'Enable query logging to see stats here.', 'relevanssi' ) );
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

	$source = '';
	if ( function_exists( 'relevanssi_validate_source' ) ) {
		$source = relevanssi_validate_source( $_REQUEST['source'] ?? '' );
	}

	$data_query = 'SELECT LEFT( `time`, 10 ) as `day`, count(*) as `count` ' .
		"FROM {$relevanssi_variables['log_table']} ";
	if ( $source ) {
		$data_query .= "WHERE source = '$source' ";
	}
	$data_query .= 'GROUP BY LEFT( `time`, 10 )';
	$data        = $wpdb->get_results( $data_query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

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

	$source_select = '';
	if ( function_exists( 'relevanssi_generate_source_select' ) ) {
		$source_select = relevanssi_generate_source_select( $source );
		// No user input here.
	}
	?>

	<div class="relevanssi-dashboard-layout">
		<div class="relevanssi-card">
			<h2><?php esc_html_e( 'Log Filter Settings', 'relevanssi' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'relevanssi_user_searches', '_relevanssi_nonce', true, true ); ?>
				<div class="relevanssi-settings-row" style="margin-bottom: 0;">
					<div class="relevanssi-settings-content">
						<table class="form-table" role="presentation" style="margin: 0;">
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Date Range Selection', 'relevanssi' ); ?></label></th>
								<td>
									<div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
										<span><?php echo esc_html__( 'From:', 'relevanssi' ); ?></span>
										<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" />
										<span><?php echo esc_html__( 'To:', 'relevanssi' ); ?></span>
										<input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" />
										<input type="submit" class="button button-primary" value="<?php echo esc_attr( __( 'Filter', 'relevanssi' ) ); ?>" />
									</div>
								</td>
							</tr>
							<?php if ( ! empty( $source_select ) ) : ?>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Filter by Source', 'relevanssi' ); ?></label></th>
									<td>
										<?php echo $source_select; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</td>
								</tr>
							<?php endif; ?>
						</table>
					</div>
					<div class="relevanssi-settings-sidebar" style="position: static;">
						<label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #646970;"><?php esc_html_e( 'Quick Presets', 'relevanssi' ); ?></label>
						<div class="relevanssi-action-group" style="margin-bottom: 0;">
							<input type="submit" class="button" value="<?php echo esc_attr( __( 'Year so far', 'relevanssi' ) ); ?>" name="this_year" />
							<input type="submit" class="button" value="<?php echo esc_attr( __( 'This month', 'relevanssi' ) ); ?>" name="this_month" />
							<input type="submit" class="button" value="<?php echo esc_attr( __( 'Last month', 'relevanssi' ) ); ?>" name="last_month" />
							<input type="submit" class="button" value="<?php echo esc_attr( __( '30 days', 'relevanssi' ) ); ?>" name="last_30" />
							<input type="submit" class="button" value="<?php echo esc_attr( __( 'This week', 'relevanssi' ) ); ?>" name="this_week" />
							<input type="submit" class="button" value="<?php echo esc_attr( __( 'Last week', 'relevanssi' ) ); ?>" name="last_week" />
							<input type="submit" class="button" value="<?php echo esc_attr( __( '7 days', 'relevanssi' ) ); ?>" name="last_7" />
							<input type="submit" class="button" value="<?php echo esc_attr( __( 'All history', 'relevanssi' ) ); ?>" name="everything" />
						</div>
					</div>
				</div>
			</form>
		</div>

		<div class="relevanssi-card">
			<h2><?php esc_html_e( 'Search Traffic Frequency', 'relevanssi' ); ?></h2>
			<div style="margin-top: 16px;">
				<?php
				relevanssi_create_line_chart(
					$labels,
					array(
						__( '# of Searches', 'relevanssi' ) => $values,
					)
				);
				?>
			</div>
		</div>

		<?php $total_queries = relevanssi_total_queries( $from, $to, $source ); ?>

		<div class="relevanssi-settings-row">
			<div class="relevanssi-settings-content">
				<div class="relevanssi-index-grid">
					<div class="relevanssi-card">
						<h2><?php esc_html_e( 'Successful searches', 'relevanssi' ); ?></h2>
						<p class="description" style="margin-bottom: 16px;"><?php esc_html_e( '"Hits" is the average hits this search query has found.', 'relevanssi' ); ?></p>
						<?php
						if ( ! function_exists( 'relevanssi_get_query_clicks' ) ) {
							echo '<div class="relevanssi-notice"><p>' . esc_html__( 'In order to see the clicks, you need Relevanssi Premium.', 'relevanssi' ) . '</p></div>';
						} elseif ( 'on' !== get_option( 'relevanssi_click_tracking' ) ) {
							echo '<div class="relevanssi-notice"><p>' . esc_html__( 'In order to see the clicks, you need to enable click tracking. Click tracking is not currently enabled, and you\'re not collecting new clicks.', 'relevanssi' ) . '</p></div>';
						}
						relevanssi_date_queries( $from, $to, 'good', $source );
						?>
					</div>
					<div class="relevanssi-card">
						<h2><?php esc_html_e( 'Unsuccessful searches', 'relevanssi' ); ?></h2>
						<p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'These queries have found no results.', 'relevanssi' ); ?></p>
						<?php relevanssi_date_queries( $from, $to, 'bad', $source ); ?>
					</div>
				</div>
			</div>

			<div class="relevanssi-settings-sidebar" style="display: flex; flex-direction: column; gap: 24px;">
				<div class="relevanssi-card">
					<h2><?php esc_html_e( 'Performance Metrics', 'relevanssi' ); ?></h2>
					<div class="relevanssi-metrics">
						<div class="metric">
							<span class="metric-label"><?php esc_html_e( 'Total searches', 'relevanssi' ); ?></span>
							<span class="metric-number"><?php echo intval( $total_queries ); ?></span>
						</div>
						<div class="metric">
							<span class="metric-label"><?php esc_html_e( 'Searches that found nothing', 'relevanssi' ); ?></span>
							<span class="metric-number" style="color: #d63638;"><?php echo intval( relevanssi_nothing_found_queries( $from, $to, $source ) ); ?></span>
						</div>
						<?php
						if ( function_exists( 'relevanssi_user_searches_clicks' ) ) {
							relevanssi_user_searches_clicks( $from, $to, $total_queries, $source );
						}
						?>
					</div>
				</div>

				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<div class="relevanssi-card" style="border-top-color: #d63638;">
						<h2><?php esc_html_e( 'Reset Logs', 'relevanssi' ); ?></h2>
						<form method="post">
							<?php
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
							?>
							<div class="relevanssi-notice relevanssi-notice-warning" style="margin-top: 0;">
								<p style="font-size: 12.5px; line-height: 1.5;"><label for="relevanssi_reset_code"><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label></p>
							</div>
							<div style="display: flex; gap: 8px; margin-top: 12px;">
								<input type="text" id="relevanssi_reset_code" name="relevanssi_reset_code" style="flex-grow: 1; max-width: none;" placeholder="reset" />
								<input type="submit" name="relevanssi_reset" value="<?php echo esc_attr( __( 'Reset', 'relevanssi' ) ); ?>" class="button button-outline" style="border-color: #d63638; color: #d63638;" />
							</div>
						</form>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	echo '</div>';
}

/**
 * Shows the total number of searches on 'User searches' page.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $from   The start date.
 * @param string $to     The end date.
 * @param string $source The search source.
 *
 * @return int The number of searches.
 */
function relevanssi_total_queries( string $from, string $to, string $source ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	if ( ! $source ) {
		$query = $wpdb->prepare(
			"SELECT COUNT(id) FROM $log_table " // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			. 'WHERE time >= %s
            AND time <= %s',
			$from . ' 00:00:00',
			$to . ' 23:59:59'
		);
	} else {
		$query = $wpdb->prepare(
			"SELECT COUNT(id) FROM $log_table " // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			. 'WHERE time >= %s
            AND time <= %s
            AND source = %s',
			$from . ' 00:00:00',
			$to . ' 23:59:59',
			$source
		);
	}

	$count = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return $count;
}

/**
 * Shows the total number of searches on 'User searches' page.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $from   The start date.
 * @param string $to     The end date.
 * @param string $source The search source.
 */
function relevanssi_nothing_found_queries( string $from, string $to, string $source ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	if ( ! $source ) {
		$query = $wpdb->prepare(
			"SELECT COUNT(id) FROM $log_table " // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			. 'WHERE time >= %s
            AND time <= %s
            AND hits = 0',
			$from . ' 00:00:00',
			$to . ' 23:59:59'
		);
	} else {
		$query = $wpdb->prepare(
			"SELECT COUNT(id) FROM $log_table " // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			. 'WHERE time >= %s
            AND time <= %s
            AND hits = 0
            AND source = %s',
			$from . ' 00:00:00',
			$to . ' 23:59:59',
			$source
		);
	}

	$count = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return $count;
}

/**
 * Shows the most common search queries on different time periods.
 *
 * @global object $wpdb                The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $from    The beginning date.
 * @param string $to      The ending date.
 * @param string $version If 'good', show the searches that found something; if
 * 'bad', show the searches that didn't find anything. Default 'good'.
 * @param string $source  The source identifier, default ''.
 */
function relevanssi_date_queries( string $from, string $to, string $version = 'good', string $source = '' ) {
	global $wpdb, $relevanssi_variables;
	$log_table = $relevanssi_variables['log_table'];

	/**
	 * Filters the number of most common queries to show.
	 *
	 * @param int $limit The number of most common queries to show, default 100.
	 */
	$limit = apply_filters( 'relevanssi_user_searches_limit', 100 );

	$queries = array();
	if ( 'good' === $version ) {
		if ( ! $source ) {
			$query = $wpdb->prepare(
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
			);
		} else {
			$query = $wpdb->prepare(
				'SELECT COUNT(DISTINCT(id)) as cnt, query, AVG(hits) AS hits ' .
				"FROM $log_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'WHERE time >= %s
                AND time <= %s
                AND hits > 0
                AND source = %s
                GROUP BY query
                ORDER BY cnt DESC
                LIMIT %d',
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$source,
				$limit
			);
		}
		$queries = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( 'bad' === $version ) {
		if ( ! $source ) {
			$query = $wpdb->prepare(
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
			);
		} else {
			$query = $wpdb->prepare(
				'SELECT COUNT(DISTINCT(id)) as cnt, query, hits ' .
				"FROM $log_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'WHERE time >= %s
                AND time <= %s
                AND hits = 0
                AND source = %s
                GROUP BY query
                ORDER BY cnt DESC
                LIMIT %d',
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$source,
				$limit
			);
		}
		$queries = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( count( $queries ) > 0 ) {
		if ( 'good' === $version ) {
			printf(
				"<table class='widefat striped' style='border: none; box-shadow: none;'>
                        <thead>
                            <tr>
                                <th>%s</th>
                                <th style='text-align: center; width: 50px;'>#</th>
                                <th style='text-align: center; width: 60px;'>%s</th>
                                <th style='text-align: center; width: 60px;'>%s</th>
                            </tr>
                        </thead>
                        <tbody>",
				esc_html__( 'Query', 'relevanssi' ),
				esc_html__( 'Hits', 'relevanssi' ),
				esc_html__( 'Clicks', 'relevanssi' )
			);
		} else {
			printf(
				"<table class='widefat striped' style='border: none; box-shadow: none;'>
                        <thead>
                            <tr>
                                <th>%s</th>
                                <th style='text-align: center; width: 50px;'>#</th>
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
			 * @param string $url Query URL.
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
                        <td style='vertical-align: middle;'>%s <a href='%s' target='_blank' rel='noopener noreferrer' style='color: #646970; margin-left: 4px;'><span class='dashicons dashicons-external' style='font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;'></span></a></td>
                        <td style='text-align: center; vertical-align: middle; font-weight: 600;'>%d</td>
                        <td style='text-align: center; vertical-align: middle; color: #646970;'>%d</td>
                        <td style='text-align: center; vertical-align: middle;'>%s</td>
                    </tr>",
					$query_link, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_url( $query_url ),
					intval( $query->cnt ),
					intval( $query->hits ),
					$clicks // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			} else {
				printf(
					"<tr>
                        <td style='vertical-align: middle;'>%s <a href='%s' target='_blank' rel='noopener noreferrer' style='color: #646970; margin-left: 4px;'><span class='dashicons dashicons-external' style='font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;'></span></a></td>
                        <td style='text-align: center; vertical-align: middle; font-weight: 600;'>%d</td>
                    </tr>",
					$query_link,  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_url( $query_url ),
					intval( $query->cnt )
				);
			}
		}
		echo '</tbody></table>';
	} else {
		printf( '<p class="description" style="padding: 12px 4px; margin: 0;">%s</p>', esc_html__( 'No logged queries detected for this timeframe.', 'relevanssi' ) );
	}
}
