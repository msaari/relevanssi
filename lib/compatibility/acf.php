<?php
/**
 * /lib/compatibility/acf.php
 *
 * Advanced Custom Fields compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_search_ok', 'relevanssi_acf_relationship_fields' );

/**
 * Disables Relevanssi in the ACF Relationship field post search.
 *
 * We don't want to use Relevanssi on the ACF Relationship field post searches, so
 * this function disables it (on the 'relevanssi_search_ok' hook).
 *
 * @param boolean $search_ok Block the search or not.
 *
 * @return boolean False, if this is an ACF Relationship field search, pass the
 * parameter unchanged otherwise.
 */
function relevanssi_acf_relationship_fields( $search_ok ) {
	if ( isset( $_REQUEST['action'] ) && 'acf' === substr( $_REQUEST['action'], 0, 3 ) ) { // WPCS: CSRF ok.
		$search_ok = false;
	}
	return $search_ok;
}

/**
 * Indexes the human-readable value of "choice" options list from ACF.
 *
 * @author Droz Raphaël
 *
 * @param array  $insert_data The insert data array.
 * @param int    $post_id     The post ID.
 * @param string $field_name  Name of the field.
 * @param string $field_value The field value.
 */
function relevanssi_index_acf( &$insert_data, $post_id, $field_name, $field_value ) {
	if ( ! is_admin() ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php'; // Otherwise is_plugin_active() will cause a fatal error.
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		return;
	}
	if ( ! is_plugin_active( 'advanced-custom-fields/acf.php' ) && ! is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
		return;
	}
	if ( ! function_exists( 'get_field_object' ) ) {
		return; // ACF is active, but not loaded.
	}

	$field_object = get_field_object( $field_name, $post_id );
	if ( ! isset( $field_object['choices'] ) ) {
		return; // Not a "select" field.
	}
	if ( is_array( $field_value ) ) {
		return; // Not handled (currently).
	}
	if ( ! isset( $field_object['choices'][ $field_value ] ) ) {
		return; // Value does not exist.
	}

	$value = $field_object['choices'][ $field_value ];
	if ( $value ) {
		if ( ! isset( $insert_data[ $value ]['customfield'] ) ) {
			$insert_data[ $value ]['customfield'] = 0;
		}
		$insert_data[ $value ]['customfield']++;
	}
}
