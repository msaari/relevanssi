<?php
/**
 * Color picker settings field component implementation.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Color
 *
 * Renders color field settings components.
 */
class Relevanssi_Setting_Field_Color extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Outputs a text input decorated with WordPress color picker classes
	 * along with a dynamic text preview block.
	 *
	 * @return void
	 */
	protected function render_input() {
		$value         = $this->config['value'] ?? '#000000';
		$default_color = $this->config['default'] ?? '#000000';
		$sample_text   = $this->config['sample_text'] ?? __( 'Search Match Preview', 'relevanssi' );

		echo '<div class="relevanssi-color-picker-wrapper" style="display: flex; align-items: center; gap: 20px;">';

		// 1. Native WP Color Picker Input.
		printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="color-field relevanssi-color-input" data-default-color="%3$s" />',
			esc_attr( $this->id ),
			esc_attr( $value ),
			esc_attr( $default_color )
		);

		// 2. Dynamic Sample Preview Box.
		printf(
			'<div id="preview_%1$s" class="relevanssi-color-preview-sample" style="padding: 4px 8px; border: 1px solid #c3c4c7; border-radius: 4px; font-weight: bold; background: #ffffff; color: #000000;">' .
				'%2$s' .
			'</div>',
			esc_attr( $this->id ),
			esc_html( $sample_text )
		);

		echo '</div>';

		if ( ! empty( $this->config['description'] ) ) {
			printf( '<p class="description" style="margin-top: 4px;">%s</p>', esc_html( $this->config['description'] ) );
		}
	}
}
