<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-select.php
 *
 * Select drop down box options control implementation component for settings.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Select
 *
 * Implements choice menus dynamic generation markup generation hooks cleanly.
 */
class Relevanssi_Setting_Field_Select extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Loops over configuration pairs evaluating selected states printing dynamic selections.
	 *
	 * @return void
	 */
	protected function render_input() {
		printf( '<select name="%1$s" id="%1$s">', esc_attr( $this->id ) );
		$current_val = (string) ( $this->config['value'] ?? '' );

		foreach ( ( $this->config['options'] ?? array() ) as $val => $label ) {
			$selected = ( (string) $val === $current_val ) ? ' selected="selected"' : '';
			printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $val ), $selected, esc_html( $label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Selected string context output state contains safe controlled boolean text.
		}
		echo '</select>';
	}
}
