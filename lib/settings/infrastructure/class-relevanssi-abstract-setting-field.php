<?php
/**
 * /lib/settings/infrastructure/class-relevanssi-abstract-setting-field.php
 *
 * Abstract class definition for Relevanssi premium setting fields handlers.
 *
 * @package Relevanssi
 * @link    https://developer.wordpress.org/coding-standards/wordpress-coding-standards/
 */

/**
 * Class Relevanssi_Abstract_Setting_Field
 *
 * Provides base table-row wrapper templates and shared utilities for rendering
 * configuration-driven admin form element objects cleanly.
 */
abstract class Relevanssi_Abstract_Setting_Field implements Relevanssi_Setting_Field_Interface {

	/**
	 * Unique string identifier key mapping to database option keys.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Structured data configuration parameters block array.
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * Relevanssi_Abstract_Setting_Field Constructor.
	 *
	 * @param string $id     Unique option field key string descriptor identifier.
	 * @param array  $config Keyed definitions specifying control constraints.
	 */
	public function __construct( string $id, array $config ) {
		$this->id     = $id;
		$this->config = $config;
	}

	/**
	 * Evaluates whether the UI control row should completely skip rendering workflows.
	 *
	 * @return bool True if allowed to be output to the screen, false otherwise.
	 */
	protected function is_visible(): bool {
		if ( isset( $this->config['visible'] ) && false === $this->config['visible'] ) {
			return false;
		}
		return true;
	}

	/**
	 * Standard structural container loop template building WordPress admin rows.
	 *
	 * @return void Prints out standard form table structural row parts directly.
	 */
	public function render() {
		if ( ! $this->is_visible() ) {
			return;
		}

		$row_classes = array();
		if ( ! empty( $this->config['advanced'] ) && true === $this->config['advanced'] ) {
			$row_classes[] = 'rlv-row-advanced';
		}
		$class_string = ! empty( $row_classes ) ? ' class="' . esc_attr( implode( ' ', $row_classes ) ) . '"' : '';

		$hover_target = '';
		if ( ! empty( $this->config['hover_target'] ) ) {
			$hover_target = ' data-hover-target="' . esc_attr( $this->config['hover_target'] ) . '"';
		}

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<tr id="row_' . esc_attr( $this->id ) . '"' . $class_string . $hover_target . '>';

		echo '<th scope="row">';
		$this->render_label();
		$this->render_tooltip();
		echo '</th>';

		echo '<td>';
		$this->render_input();
		$this->render_notice();
		echo '</td>';

		echo '</tr>';
	}

	/**
	 * Output logic printing sanitized label descriptive headers inside administration views.
	 *
	 * Automatically wraps standard single inputs in a semantic HTML label tag,
	 * while allowing checkboxes and complex group matrices to handle their own layouts.
	 *
	 * @return void Writes sanitized markup straight to the active view stream.
	 */
	protected function render_label() {
		$label = $this->config['label'] ?? '';

		if ( empty( $label ) ) {
			return;
		}

		$advanced_badge = '';
		if ( ! empty( $this->config['advanced'] ) && true === $this->config['advanced'] ) {
			$advanced_badge = sprintf(
				' <span class="rlv-advanced-badge" title="%s">%s</span>',
				esc_attr__( 'Advanced option. Modify only if you know what you are doing.', 'relevanssi' ),
				esc_html__( 'Advanced', 'relevanssi' )
			);
		}

		$single_input_types = array(
			'text',
			'textarea',
			'number',
			'select',
			'media_upload',
		);

		$field_type = $this->config['type'] ?? '';

		if ( in_array( $field_type, $single_input_types, true ) ) {
			$clean_target_id = rtrim( $this->id, '[]' );

			printf(
				'<label for="%1$s">%2$s</label>%3$s',
				esc_attr( $clean_target_id ),
				wp_kses_post( $label ),
				$advanced_badge // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			return;
		}

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_kses_post( $label ) . $advanced_badge;
	}

	/**
	 * Builds and handles interactive contextual HTML-capable tooltips.
	 *
	 * @return void
	 */
	protected function render_tooltip() {
		if ( ! empty( $this->config['tooltip'] ) ) {
			printf(
				' <span class="relevanssi-tooltip" tabindex="0">
                    <span class="dashicons dashicons-info-outline"></span>
                    <span class="relevanssi-tooltip-bubble">%s</span>
                 </span>',
				wp_kses_post( $this->config['tooltip'] )
			);
		}
	}

	/**
	 * Renders warning or informative structural banner rows when present in properties data.
	 *
	 * @return void
	 */
	protected function render_notice() {
		if ( ! empty( $this->config['notice'] ) && is_array( $this->config['notice'] ) ) {
			$notice_type = $this->config['notice']['type'] ?? 'info';
			$role_attr   = ( 'error' === $notice_type ) ? ' role="alert"' : '';

			printf(
				'<div class="relevanssi-notice relevanssi-notice-%s"%s><p>%s</p></div>',
				esc_attr( $notice_type ),
				$role_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				wp_kses_post( $this->config['notice']['text'] )
			);
		}
	}

	/**
	 * Abstract contract forcing child variations to output operational inner content forms.
	 *
	 * @return void
	 */
	abstract protected function render_input();

	/**
	 * Extracts, sanitizes, and updates the database option value from a raw request payload.
	 *
	 * Child variations can override this method entirely to perform composite field parsing
	 * or handle special multi-key database update patterns.
	 *
	 * @param array $request The raw request matrix from the administration page form post.
	 * @return bool True if the database option was successfully updated, false otherwise.
	 */
	public function save( array $request ): bool {
		if ( ! isset( $request[ $this->id ] ) ) {
			return false;
		}

		$raw_value       = $request[ $this->id ];
		$sanitized_value = $this->sanitize( $raw_value );
		$autoload_state  = $this->config['autoload'] ?? true;

		return update_option( $this->id, $sanitized_value, $autoload_state );
	}

	/**
	 * Sanitizes an incoming input parameter value before updating the database record.
	 *
	 * Core fields default to clean plain text field strings. Specialized elements like numbers
	 * or nested data arrays will override this wrapper to execute targeted casting rules.
	 *
	 * @param mixed $value The raw unvalidated input parameter coming from the user request payload.
	 * @return mixed The cleanly processed and safe sanitized variable representation.
	 */
	protected function sanitize( $value ) {
		return sanitize_text_field( wp_unslash( $value ) );
	}
}
