<?php
/**
 * /lib/tabs/logging-tab.php
 *
 * Prints out the Logging tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the logging tab in Relevanssi settings.
 *
 * @global $wpdb                 The WordPress database interface.
 */
function relevanssi_logging_tab() {
	global $wpdb;

	$log_queries         = get_option( 'relevanssi_log_queries' );
	$log_queries         = relevanssi_check( $log_queries );
	$log_queries_with_ip = get_option( 'relevanssi_log_queries_with_ip' );
	$log_queries_with_ip = relevanssi_check( $log_queries_with_ip );
	$omit_from_logs      = get_option( 'relevanssi_omit_from_logs' );
	$trim_logs           = get_option( 'relevanssi_trim_logs' );

	?>
	<table class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Enable logs', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Keep a log of user queries.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_log_queries'>
				<input type='checkbox' name='relevanssi_log_queries' id='relevanssi_log_queries' <?php echo esc_html( $log_queries ); ?> />
				<?php esc_html_e( 'Keep a log of user queries.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description">
		<?php
		// Translators: %1$s is the name of the "User searches" page, %2$s is the name of the database table.
		printf(
			esc_html__( "If enabled, Relevanssi will log user queries. The logs can be examined under '%1\$s' on the Dashboard admin menu and are stored in the %2\$s database table.", 'relevanssi' ),
			esc_html__( 'User searches', 'relevanssi' ),
			esc_html( $wpdb->prefix . 'relevanssi_log' )
		);
		?>
		</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Log user IP', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( "Log the user's IP with the queries.", 'relevanssi' ); ?></legend>
			<label for='relevanssi_log_queries_with_ip'>
				<input type='checkbox' name='relevanssi_log_queries_with_ip' id='relevanssi_log_queries_with_ip' <?php echo esc_html( $log_queries_with_ip ); ?> />
				<?php esc_html_e( "Log the user's IP with the queries.", 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( "If enabled, Relevanssi will log user's IP adress with the queries. Note that this may be illegal where you live, and in EU will create a person registry that falls under the GDPR.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_omit_from_logs'><?php esc_html_e( 'Exclude users', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_omit_from_logs' id='relevanssi_omit_from_logs' size='60' value='<?php echo esc_attr( $omit_from_logs ); ?>' />
			<p class="description"><?php esc_html_e( 'Comma-separated list of numeric user IDs or user login names that will not be logged.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php
	if ( function_exists( 'relevanssi_form_hide_branding' ) ) {
		relevanssi_form_hide_branding();
	}
	?>
	<tr>
		<th scope="row">
			<label for='relevanssi_trim_logs'><?php esc_html_e( 'Trim logs', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='number' name='relevanssi_trim_logs' id='relevanssi_trim_logs' value='<?php echo esc_attr( $trim_logs ); ?>' />
			<?php esc_html_e( 'How many days of logs to keep in the database.', 'relevanssi' ); ?>
			<?php
			if ( '0' === $trim_logs ) {
				echo '<p class="description">';
				esc_html_e( "Big log database table will eventually start to slow down the search, so it's a good idea to use some level of automatic log trimming.", 'relevanssi' );
				echo '</p>';
			} else {
				echo '<p class="description">';
				// Translators: %d is the setting for no trim (probably 0).
				printf( esc_html__( 'Set to %d for no trimming.', 'relevanssi' ), 0 );
				echo '</p>';
			}
			?>
		</td>
	</tr>

	<tr>
		<th scope="row">
			<?php esc_html_e( 'Export logs', 'relevanssi' ); ?>
		</th>
		<td>
			<?php submit_button( __( 'Export the log as a CSV file', 'relevanssi' ), 'secondary', 'relevanssi_export' ); ?>
			<p class="description"><?php esc_html_e( 'Push the button to export the search log as a CSV file.', 'relevanssi' ); ?></p>
		</td>
	</tr>

	</table>
	<?php

	if ( function_exists( 'relevanssi_click_tracking_interface' ) ) {
		relevanssi_click_tracking_interface();
	} else {
		?>
		<h3><?php esc_html_e( 'Click tracking', 'relevanssi' ); ?></h3>
		<p><?php esc_html_e( 'Relevanssi Premium has a click tracking feature where you can track which posts are clicked from the search results. That way you can tell what is your most interesting content and how the search is actually used to access posts.', 'relevanssi' ); ?></p>
		<?php
	}
}
