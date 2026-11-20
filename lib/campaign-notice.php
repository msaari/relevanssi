<?php
/**
 * Limited campaign notice outside the plugin's own settings.
 *
 * @package Relevanssi
 */

add_action( 'admin_notices', 'relevanssi_render_campaign_notice', 10, 0 );
add_action( 'wp_ajax_relevanssi_dismiss_campaign_notice', 'relevanssi_dismiss_campaign_notice', 10, 0 );

/**
 * Checks the campaign, placement and current user's permanent notice preference.
 *
 * @param int|null $instant Unix timestamp; defaults to the current instant.
 * @return bool Whether to display the notice.
 */
function relevanssi_campaign_notice_is_visible( ?int $instant = null ): bool {
	if ( relevanssi_is_premium() || ! is_admin() || is_network_admin() || is_user_admin() || wp_doing_ajax() || ! current_user_can( 'manage_options' ) || ! relevanssi_black_friday_is_active( $instant ) ) {
		return false;
	}
	$screen = get_current_screen();
	// Limit advertising to landing screens, away from editing and update workflows.
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) || ! relevanssi_black_friday_customer_eligible() ) {
		return false;
	}
	$campaign = relevanssi_black_friday_campaign();
	return '1' !== get_user_meta( get_current_user_id(), $campaign['notice_dismissed_key'], true );
}

/**
 * Prints a standard notice and loads its local dismissal handling only when shown.
 *
 * @param int|null $instant Unix timestamp; defaults to the current instant.
 */
function relevanssi_render_campaign_notice( ?int $instant = null ) {
	if ( ! relevanssi_campaign_notice_is_visible( $instant ) ) {
		return;
	}
	global $relevanssi_variables;
	$campaign = relevanssi_black_friday_campaign();
	wp_enqueue_script( 'relevanssi_campaign_notice', plugin_dir_url( $relevanssi_variables['file'] ) . 'lib/campaign-notice.js', array(), $relevanssi_variables['plugin_version'], true );
	wp_enqueue_style( 'relevanssi_campaign_notice', plugin_dir_url( $relevanssi_variables['file'] ) . 'lib/campaign-notice.css', array(), $relevanssi_variables['plugin_version'] );
	// Core's is-dismissible removes notices before persistence has succeeded.
	?>
	<div id="relevanssi-campaign-notice" class="notice notice-info" style="position: relative; padding-right: 38px;"
		data-dismiss-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( 'relevanssi_dismiss_campaign_notice' ) ); ?>"
		data-dismiss-error="<?php esc_attr_e( 'Your dismissal could not be saved. Please try again.', 'relevanssi' ); ?>">
		<div class="relevanssi-campaign-notice-layout">
			<img src="<?php echo esc_url( plugin_dir_url( $relevanssi_variables['file'] ) . 'images/relevanssi-campaign-logo.png' ); ?>" width="36" height="49" alt="" aria-hidden="true" />
			<div>
				<p class="relevanssi-campaign-notice-heading"><?php echo wp_kses_post( relevanssi_black_friday_heading() ); ?></p>
				<p><?php echo wp_kses_post( relevanssi_black_friday_description( $campaign ) ); ?></p>
				<p><a href="<?php echo esc_url( $campaign['url'] ); ?>"><?php esc_html_e( 'Get Relevanssi Premium', 'relevanssi' ); ?></a></p>
			</div>
		</div>
		<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice for the rest of the campaign.', 'relevanssi' ); ?></span></button>
		<p role="alert" hidden></p>
	</div>
	<?php
}

/** Persists the current user's notice dismissal without allowing a client reset. */
function relevanssi_dismiss_campaign_notice() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! relevanssi_black_friday_customer_eligible() ) {
		wp_send_json_error( null, 403 );
	}
	if ( ! isset( $_REQUEST['security'] ) || ! is_string( $_REQUEST['security'] ) || ! check_ajax_referer( 'relevanssi_dismiss_campaign_notice', 'security', false ) ) {
		wp_send_json_error( null, 403 );
	}
	$campaign = relevanssi_black_friday_campaign();
	$user_id  = get_current_user_id();
	$key      = $campaign['notice_dismissed_key'];
	if ( '1' !== get_user_meta( $user_id, $key, true ) ) {
		update_user_meta( $user_id, $key, '1' );
	}
	// Re-read because update_user_meta() also returns false for unchanged values.
	if ( '1' !== get_user_meta( $user_id, $key, true ) ) {
		wp_send_json_error( null, 500 );
	}
	wp_send_json_success();
}
