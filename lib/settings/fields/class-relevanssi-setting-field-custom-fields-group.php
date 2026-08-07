<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-custom-fields-group.php
 *
 * Specialized compound field class managing data updates for the custom fields index configurations.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Custom_Fields_Group
 *
 * Manages the specific, coupled relationship between the layout selector dropdown controls
 * and the raw text values expected by the database option storage keys.
 */
class Relevanssi_Setting_Field_Custom_Fields_Group extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Outputs nothing directly since this acts as a virtual processing manager engine.
	 *
	 * @return void
	 */
	protected function render_input() {
		// This block is left blank intentionally as its component sub-fields handle rendering outputs.
	}

	/**
	 * Overrides the standard saving lifecycle to execute conditional value mutations.
	 *
	 * Ensures the custom selector choices ("all", "visible", "none") scale correctly and write
	 * the exact format required by the core indexing parser back to the primary data string option.
	 *
	 * @param array $request The raw request matrix from the administration page form post.
	 * @return bool True if the database option was successfully updated, false otherwise.
	 */
	public function save( array $request ): bool {
		if ( ! isset( $request['relevanssi_index_fields_select'] ) ) {
			return false;
		}

		$select_mode = sanitize_text_field( wp_unslash( $request['relevanssi_index_fields_select'] ) );

		if ( in_array( $select_mode, array( 'all', 'visible' ), true ) ) {
			return update_option( 'relevanssi_index_fields', $select_mode, false );
		}

		if ( 'none' === $select_mode ) {
			return update_option( 'relevanssi_index_fields', '', false );
		}

		if ( 'some' === $select_mode && isset( $request['relevanssi_index_fields'] ) ) {
			$raw_text     = $request['relevanssi_index_fields'];
			$cleaned_text = rtrim( $raw_text, " \t\n\r\0\x0B," );

			if ( empty( $cleaned_text ) ) {
				set_transient( 'relevanssi_fields_error_' . get_current_user_id(), 'empty_some', 30 );
				return false;
			}

			return update_option( 'relevanssi_index_fields', sanitize_text_field( wp_unslash( $cleaned_text ) ), false );
		}

		return false;
	}
}
