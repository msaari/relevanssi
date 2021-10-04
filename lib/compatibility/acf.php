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
	if ( isset( $_REQUEST['action'] ) && 'acf' === substr( $_REQUEST['action'], 0, 3 ) ) { // phpcs:ignore WordPress.Security.NonceVerification
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
 *
 * @return int Number of tokens indexed.
 */
function relevanssi_index_acf( &$insert_data, $post_id, $field_name, $field_value ) {
	if ( ! is_admin() ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php'; // Otherwise is_plugin_active() will cause a fatal error.
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		return 0;
	}
	if ( ! is_plugin_active( 'advanced-custom-fields/acf.php' ) && ! is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
		return 0;
	}
	if ( ! function_exists( 'get_field_object' ) ) {
		return 0; // ACF is active, but not loaded.
	}

	$field_object = get_field_object( $field_name, $post_id );
	if ( ! isset( $field_object['choices'] ) ) {
		return 0; // Not a "select" field.
	}
	if ( is_array( $field_value ) ) {
		return 0; // Not handled (currently).
	}
	if ( ! isset( $field_object['choices'][ $field_value ] ) ) {
		return 0; // Value does not exist.
	}

	$n = 0;

	/**
	 * Filters the field value before it is used to save the insert data.
	 *
	 * The value is used as an array key, so it needs to be an integer or a
	 * string. If your custom field values are arrays or objects, use this
	 * filter hook to convert them into strings.
	 *
	 * @param mixed  $field_content The ACF field value.
	 * @param string $field_name    The ACF field name.
	 * @param int    $post_id       The post ID.
	 *
	 * @return string|int The field value.
	 */
	$value = apply_filters(
		'relevanssi_acf_field_value',
		$field_object['choices'][ $field_value ],
		$field_name,
		$post_id
	);

	if ( $value && ( is_integer( $value ) || is_string( $value ) ) ) {
		$min_word_length = get_option( 'relevanssi_min_word_length', 3 );

		/** This filter is documented in lib/indexing.php */
		$value_tokens = apply_filters( 'relevanssi_indexing_tokens', relevanssi_tokenize( $value, true, $min_word_length, 'indexing' ), 'custom_field' );
		foreach ( $value_tokens as $token => $count ) {
			$n++;
			if ( ! isset( $insert_data[ $token ]['customfield'] ) ) {
				$insert_data[ $token ]['customfield'] = 0;
			}
			$insert_data[ $token ]['customfield'] += $count;

			// Premium indexes more detail about custom fields.
			if ( function_exists( 'relevanssi_customfield_detail' ) ) {
				$insert_data = relevanssi_customfield_detail( $insert_data, $token, $count, $field_name );
			}
		}
	}

	return $n;
}
