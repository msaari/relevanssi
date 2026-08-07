<?php
/**
 * Textarea settings field component implementation.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Textarea
 *
 * Renders a standardized multi-line HTML textarea control row wrapper for managing large
 * data sets like custom stopwords catalogs, synonyms mappings, or complex exclusions lists.
 */
class Relevanssi_Setting_Field_Textarea extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Outputs the operational HTML textarea input form control segment.
	 *
	 * @return void
	 */
	protected function render_input() {
		$value       = $this->config['value'] ?? '';
		$placeholder = ! empty( $this->config['placeholder'] ) ? ' placeholder="' . esc_attr( $this->config['placeholder'] ) . '"' : '';

		// Extract dimension limits safely from configuration array blocks, falling back to core standards.
		$rows = isset( $this->config['rows'] ) ? intval( $this->config['rows'] ) : 6;
		$cols = isset( $this->config['cols'] ) ? intval( $this->config['cols'] ) : 50;

		printf(
			'<textarea id="%1$s" name="%1$s" rows="%2$d" cols="%3$d" class="large-text code"%4$s>%5$s</textarea>',
			esc_attr( $this->id ),
			$rows, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$cols, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$placeholder, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_textarea( $value )
		);

		if ( ! empty( $this->config['description'] ) ) {
			printf(
				'<p class="description" style="margin-top: 4px;">%s</p>',
				esc_html( $this->config['description'] )
			);
		}
	}
}
