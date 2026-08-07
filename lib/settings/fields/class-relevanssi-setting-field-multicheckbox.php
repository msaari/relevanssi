<?php
/**
 * Multi-checkbox settings field component implementation.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Multicheckbox
 */
class Relevanssi_Setting_Field_Multicheckbox extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Outputs the operational group of array-mapped checkbox layout controls.
	 *
	 * @return void
	 */
	protected function render_input() {
		$current_values = $this->config['value'] ?? array();
		$options        = $this->config['options'] ?? array();

		if ( is_string( $current_values ) ) {
			$current_values = ! empty( $current_values ) ? explode( ',', $current_values ) : array();
		} elseif ( ! is_array( $current_values ) ) {
			$current_values = array( $current_values );
		}

		if ( empty( $options ) ) {
			return;
		}

		$is_disabled   = ! empty( $this->config['disabled'] );
		$disabled_attr = $is_disabled ? 'disabled="disabled"' : '';

		$clean_id = rtrim( $this->id, '[]' );

		echo '<fieldset>';
		printf( '<legend class="screen-reader-text"><span>%s</span></legend>', esc_html( $this->config['label'] ?? '' ) );

		foreach ( $options as $value => $label ) {
			$option_id = sprintf( '%s_%s', $clean_id, $value );
			?>
			<label for="<?php echo esc_attr( $option_id ); ?>" style="display: block; margin-bottom: 8px;">
				<input 
					class="relevanssi-toggle"
					type="checkbox" 
					id="<?php echo esc_attr( $option_id ); ?>" 
					name="<?php echo esc_attr( $clean_id ); ?>[]" 
					value="<?php echo esc_attr( $value ); ?>" 
					<?php checked( in_array( (string) $value, $current_values, true ) ); ?> 
					<?php echo $disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php
		}

		echo '</fieldset>';

		if ( ! empty( $this->config['description'] ) ) {
			printf(
				'<p class="description" style="margin-top: 4px;">%s</p>',
				esc_html( $this->config['description'] )
			);
		}
	}

	/**
	 * Sanitizes a collection array of checked option values.
	 *
	 * @param mixed $value Raw array payload from the user form request.
	 * @return array Cleaned collection parameters array of safe strings.
	 */
	protected function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', array_map( 'wp_unslash', $value ) );
	}

	/**
	 * Extracts, sanitizes, and intelligently maps data storage based on field parameters.
	 *
	 * @param array $request The raw request matrix from the administration page form post.
	 * @return bool True if the database option was successfully updated, false otherwise.
	 */
	public function save( array $request ): bool {
		$clean_id  = rtrim( $this->id, '[]' );
		$raw_value = $request[ $clean_id ] ?? array();

		$sanitized_value = $this->sanitize( $raw_value );

		if ( ! empty( $this->config['option_group'] ) && ! empty( $this->config['option_key'] ) ) {
			$group_name = $this->config['option_group'];
			$sub_key    = $this->config['option_key'];

			$group_options = get_option( $group_name, array() );

			if ( isset( $this->config['serialize'] ) && 'comma' === $this->config['serialize'] ) {
				$group_options[ $sub_key ] = implode( ',', $sanitized_value );
			} else {
				$group_options[ $sub_key ] = $sanitized_value;
			}

			return update_option( $group_name, $group_options );
		}

		$autoload_state = $this->config['autoload'] ?? true;
		return update_option( $clean_id, $sanitized_value, $autoload_state );
	}
}