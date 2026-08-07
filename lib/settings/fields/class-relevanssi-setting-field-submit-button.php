<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-submit-button.php
 *
 * Submit and action button setting field component handler.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Submit_Button
 *
 * Renders a standard WordPress dashboard submission or execution button asset
 * cleanly wrapped inside the abstract table structure.
 */
class Relevanssi_Setting_Field_Submit_Button extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Overrides the standard table row layout wrapper template.
	 * Swaps the empty structural <th> cell for a standard <td> to fix WAVE validation errors.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! $this->is_visible() ) {
			return;
		}

		$hover_target = '';
		if ( ! empty( $this->config['hover_target'] ) ) {
			$hover_target = ' data-hover-target="' . esc_attr( $this->config['hover_target'] ) . '"';
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<tr id="row_' . esc_attr( $this->id ) . '"' . $hover_target . '>';

		echo '<td></td>';

		echo '<td>';
		$this->render_input();
		$this->render_notice();
		echo '</td>';

		echo '</tr>';
	}

	/**
	 * Renders the structural button input markup and inline contextual description text.
	 *
	 * @return void Writes raw markup lines directly to the output buffer.
	 */
	protected function render_input() {
		$button_label = $this->config['button_label'] ?? '';
		$button_type  = $this->config['button_type'] ?? 'secondary';
		$button_name  = $this->config['button_name'] ?? $this->id;
		$description  = $this->config['description'] ?? '';

		// Utilizes native WordPress core UI formatting functions.
		submit_button( esc_html( $button_label ), esc_attr( $button_type ), esc_attr( $button_name ), false );

		if ( ! empty( $description ) ) {
			printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
		}
	}

	/**
	 * Passive execution bypass node. Action invocation controls do not persistently map
	 * single value options across global table loops during standard configuration updates.
	 *
	 * @param array $request Raw admin form submission request data matrix.
	 * @return bool Always returns true to prevent pipeline routing execution freezes.
	 */
	public function save( array $request ): bool {
		return true;
	}
}
