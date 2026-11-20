<?php
/**
 * Per-user disclosure preferences for Overview's upgrade panels.
 *
 * @package Relevanssi
 */

add_action( 'wp_ajax_relevanssi_save_upgrade_panel_state', 'relevanssi_save_upgrade_panel_state', 10, 0 );

/**
 * Limits Premium preferences to the eligible campaign, keeping Free panels separate.
 *
 * @param string $panel Panel identifier.
 * @return bool Whether this edition can use the panel.
 */
function relevanssi_upgrade_panel_is_available( string $panel ): bool {
	return ! relevanssi_is_premium() || ( 'black-friday-2026' === $panel && relevanssi_black_friday_customer_eligible() );
}

/**
 * Returns the panel identifiers and their server-owned metadata keys.
 *
 * @return array Panel metadata keys.
 */
function relevanssi_upgrade_panel_preferences(): array {
	return array(
		'features'          => 'relevanssi_upgrade_features_collapsed',
		'offer'             => 'relevanssi_upgrade_offer_collapsed',
		'black-friday-2026' => 'relevanssi_black_friday_2026_dismissed',
	);
}

/**
 * Checks the current user's preference for a known panel.
 *
 * @param string $panel Panel identifier.
 * @return bool Whether its content should start collapsed.
 */
function relevanssi_upgrade_panel_is_collapsed( string $panel ): bool {
	$preferences = relevanssi_upgrade_panel_preferences();
	return isset( $preferences[ $panel ] ) && '1' === get_user_meta( get_current_user_id(), $preferences[ $panel ], true );
}

/**
 * Prints disclosure attributes and loads local preference handling.
 *
 * @param string $panel Panel identifier.
 */
function relevanssi_upgrade_panel_attributes( string $panel ) {
	global $relevanssi_variables;

	if ( ! relevanssi_upgrade_panel_is_collapsed( $panel ) ) {
		echo ' open';
	}
	if ( ! current_user_can( 'manage_options' ) || ! relevanssi_upgrade_panel_is_available( $panel ) ) {
		return;
	}
	wp_enqueue_script( 'relevanssi_upgrade_panels', plugin_dir_url( $relevanssi_variables['file'] ) . 'lib/upgrade-panels.js', array(), $relevanssi_variables['plugin_version'] . '-' . substr( hash_file( 'sha256', __DIR__ . '/upgrade-panels.js' ), 0, 12 ), true );
	// Preserve the settings page's host and port so the AJAX request keeps its login cookies.
	printf(
		' data-relevanssi-upgrade-panel="%s" data-state-url="%s" data-state-nonce="%s" data-save-error="%s"',
		esc_attr( $panel ),
		esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
		esc_attr( wp_create_nonce( 'relevanssi_upgrade_panel_state' ) ),
		esc_attr__( 'Your display preference could not be saved. Please try again.', 'relevanssi' )
	);
}

/** Saves an allowlisted panel preference for the current administrator. */
function relevanssi_save_upgrade_panel_state() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( null, 403 );
	}
	if ( ! check_ajax_referer( 'relevanssi_upgrade_panel_state', 'security', false ) ) {
		wp_send_json_error( null, 403 );
	}
	$panel       = isset( $_POST['panel'] ) && is_string( $_POST['panel'] ) ? wp_unslash( $_POST['panel'] ) : '';
	$state       = isset( $_POST['state'] ) && is_string( $_POST['state'] ) ? wp_unslash( $_POST['state'] ) : '';
	$preferences = relevanssi_upgrade_panel_preferences();
	if ( ! isset( $preferences[ $panel ] ) || ! in_array( $state, array( 'collapsed', 'expanded' ), true ) ) {
		wp_send_json_error( null, 400 );
	}
	if ( ! relevanssi_upgrade_panel_is_available( $panel ) ) {
		wp_send_json_error( null, 403 );
	}
	$user_id = get_current_user_id();
	$key     = $preferences[ $panel ];
	$value   = 'collapsed' === $state ? '1' : '0';
	if ( get_user_meta( $user_id, $key, true ) !== $value ) {
		update_user_meta( $user_id, $key, $value );
	}
	// An unchanged value and a failed write both return false from update_user_meta().
	if ( get_user_meta( $user_id, $key, true ) !== $value ) {
		wp_send_json_error( null, 500 );
	}
	wp_send_json_success();
}
