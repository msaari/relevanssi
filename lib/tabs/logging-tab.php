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
 * @global $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_logging_tab() {
	global $wpdb, $relevanssi_variables;

	$log_queries         = get_option( 'relevanssi_log_queries' );
	$log_queries         = relevanssi_check( $log_queries );
	$log_queries_with_ip = get_option( 'relevanssi_log_queries_with_ip' );
	$log_queries_with_ip = relevanssi_check( $log_queries_with_ip );
	$omit_from_logs      = get_option( 'relevanssi_omit_from_logs' );
	$trim_logs           = get_option( 'relevanssi_trim_logs' );

?>
	<table class="form-table">
	<tr>
		<th scope="row">
			<label for='relevanssi_log_queries'><?php esc_html_e( 'Enable logs', 'relevanssi' ); ?></label>
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
		printf( esc_html__( "If enabled, Relevanssi will log user queries. The logs can be examined under '%1\$s' on the Dashboard admin menu and are stored in the %2\$s database table.", 'relevanssi' ),
		esc_html__( 'User searches', 'relevanssi' ), esc_html( $wpdb->prefix . 'relevanssi_log' ) );
		?>
		</p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_log_queries_with_ip'><?php esc_html_e( 'Log user IP', 'relevanssi' ); ?></label>
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
			<?php // Translators: %d is the setting for no trim (probably 0). ?>
			<p class="description"><?php printf( esc_html__( ' Set to %d for no trimming.', 'relevanssi' ), 0 ); ?></p>
		</td>
	</tr>

	</table>
	<?php
}
