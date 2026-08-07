<?php
/**
 * Binary toggle checkbox setting element option handler.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Checkbox
 *
 * Renders individual inline settings fields components.
 */
class Relevanssi_Setting_Field_Checkbox extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Generates standard single checkbox form selectors strings with inline HTML description support.
	 *
	 * @return void Writes safe markup structures directly to the screen buffers.
	 */
	protected function render_input() {
		$value       = $this->config['value'] ?? '';
		$description = $this->config['description'] ?? '';
		$is_checked  = ( 'on' === $value || true === $value || 1 === (int) $value );

		if ( ! empty( $description ) ) {
			printf(
				'<label for="%1$s"><input class="relevanssi-toggle" type="checkbox" name="%1$s" id="%1$s"%2$s /> %3$s</label>',
				esc_attr( $this->id ),
				checked( $is_checked, true, false ),
				wp_kses_post( $description )
			);
		} else {
			printf(
				'<input class="relevanssi-toggle" type="checkbox" name="%1$s" id="%1$s"%2$s />',
				esc_attr( $this->id ),
				checked( $is_checked, true, false )
			);
		}
	}

	/**
	 * Extracts, sanitizes, and updates the binary toggle state.
	 * Overrides the abstract method because browsers completely omit
	 * unchecked checkbox keys from request payloads.
	 *
	 * @param array $request The raw request matrix from the administration page form post.
	 * @return bool True if the database option was successfully updated, false otherwise.
	 */
	public function save( array $request ): bool {
		// If the key is present, it's on. If it's missing, the user turned it off.
		$value          = isset( $request[ $this->id ] ) ? 'on' : 'off';
		$autoload_state = $this->config['autoload'] ?? true;

		return update_option( $this->id, $value, $autoload_state );
	}
}
