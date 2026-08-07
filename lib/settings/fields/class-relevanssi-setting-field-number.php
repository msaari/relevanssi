<?php
/**
 * Number settings field component implementation.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Number
 *
 * Renders a standardized HTML5 number input row wrapper supporting dynamic size profiling and unit labels.
 */
class Relevanssi_Setting_Field_Number extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Outputs the operational HTML numeric input form control segment.
	 *
	 * @return void
	 */
	protected function render_input() {
		$value       = $this->config['value'] ?? 0;
		$placeholder = ! empty( $this->config['placeholder'] ) ? ' placeholder="' . esc_attr( $this->config['placeholder'] ) . '"' : '';

		$size       = $this->config['size'] ?? 'small';
		$size_class = 'relevanssi-input-' . ( 'medium' === $size ? 'medium' : 'small' );

		$min  = isset( $this->config['min'] ) ? ' min="' . intval( $this->config['min'] ) . '"' : '';
		$max  = isset( $this->config['max'] ) ? ' max="' . intval( $this->config['max'] ) . '"' : '';
		$step = isset( $this->config['step'] ) ? ' step="' . floatval( $this->config['step'] ) . '"' : '';

		printf(
			'<input type="number" id="%1$s" name="%1$s" value="%2$s" class="%3$s"%4$s%5$s%6$s%7$s />',
			esc_attr( $this->id ),
			esc_attr( $value ),
			esc_attr( $size_class ),
			$placeholder, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$min,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$max,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$step         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		if ( ! empty( $this->config['unit'] ) ) {
			printf(
				' <span class="relevanssi-input-unit" style="margin-left: 6px; font-size: 13px; color: #2c3338; vertical-align: middle;">%s</span>',
				esc_html( $this->config['unit'] )
			);
		}

		if ( ! empty( $this->config['description'] ) ) {
			printf(
				'<p class="description" style="margin-top: 4px;">%s</p>',
				esc_html( $this->config['description'] )
			);
		}
	}

	/**
	 * Sanitizes numerical parameters enforcing explicit data type boundaries.
	 *
	 * @param mixed $value The unvalidated string integer value directly from the user request.
	 * @return int Pure integer cast representation safely confined inside layout rules bounds.
	 */
	protected function sanitize( $value ) {
		$value = intval( $value );

		if ( isset( $this->config['min'] ) ) {
			$value = max( $value, intval( $this->config['min'] ) );
		}

		if ( isset( $this->config['max'] ) ) {
			$value = min( $value, intval( $this->config['max'] ) );
		}

		return $value;
	}
}
