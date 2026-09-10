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
	 * Sanitizes multiline text while preserving the breakdown template tags.
	 *
	 * @param mixed $value The raw unvalidated string input from the form post.
	 * @return string Cleaned multiline string safe for database inclusion.
	 */
	protected function sanitize( $value ) {
		$value = wp_unslash( $value );

		if ( 'relevanssi_show_matches_text' !== $this->id ) {
			return sanitize_textarea_field( $value );
		}

		$breakdown_tags = array(
			'%body%',
			'%title%',
			'%tags%',
			'%categories%',
			'%taxonomies%',
			'%comments%',
			'%customfields%',
			'%author%',
			'%excerpt%',
			'%mysqlcolumns%',
			'%score%',
			'%terms%',
			'%total%',
			'%missing%',
		);
		$protected_tags = array();

		foreach ( $breakdown_tags as $index => $breakdown_tag ) {
			$protected_tag                    = sprintf( 'RELEVANSSI_BREAKDOWN_TAG_%d', $index );
			$value                            = str_replace( $breakdown_tag, $protected_tag, $value );
			$protected_tags[ $protected_tag ] = $breakdown_tag;
		}

		$value = sanitize_textarea_field( $value );

		return strtr( $value, $protected_tags );
	}

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
