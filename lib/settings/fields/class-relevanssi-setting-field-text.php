<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-text.php
 *
 * Text field object engine component for Relevanssi settings layouts.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Text
 *
 * Manages standard textual input element markup creation logic sequences.
 */
class Relevanssi_Setting_Field_Text extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Overrides standard layout wrapper output generating deep semantic html label properties.
	 *
	 * @return void Prints elements natively.
	 */
	protected function render_label() {
		printf( '<label for="%s">%s</label>', esc_attr( $this->id ), esc_html( $this->config['label'] ?? '' ) );
	}

	/**
	 * Generates a standard text input field element wrapped securely.
	 *
	 * @return void Handles template echoing internally.
	 */
	protected function render_input() {
		$value       = $this->config['value'] ?? '';
		$placeholder = isset( $this->config['placeholder'] ) ? ' placeholder="' . esc_attr( $this->config['placeholder'] ) . '"' : '';

		printf(
			'<input type="text" name="%1$s" id="%1$s" class="regular-text"%2$s value="%3$s" />',
			esc_attr( $this->id ),
			$placeholder, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Secure attribute structure evaluated inside assignment block safely.
			esc_attr( $value )
		);
	}

	/**
	 * Sanitizes plain text input strings.
	 * Intercepts the allowable tags option key to prevent WordPress from
	 * stripping out the user's custom HTML tag parameters configurations.
	 *
	 * @param mixed $value The raw unvalidated string input from the form post.
	 * @return string Cleaned string parameters safe for database inclusion.
	 */
	protected function sanitize( $value ) {
		$value = wp_unslash( $value );

		if ( 'relevanssi_excerpt_allowable_tags' === $this->id ) {
			// Reapply legacy extraction rules: strip spaces/slashes and deduplicate brackets.
			$value = str_replace( array( ' ', '/' ), '', $value );
			return implode( '>', array_unique( explode( '>', $value ) ) );
		}

		return sanitize_text_field( $value );
	}
}
