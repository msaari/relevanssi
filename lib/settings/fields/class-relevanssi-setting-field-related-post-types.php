<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-related-post-types.php
 *
 * Handles rendering and interactive scripts bindings processing for post type criteria fields layouts.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Related_Post_Types
 */
class Relevanssi_Setting_Field_Related_Post_Types extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Renders standard checkbox vectors nested within parent visibility layouts wrappers.
	 *
	 * @return void
	 */
	protected function render_input() {
		$meta             = $this->config['meta'] ?? array();
		$value_array      = $meta['value_array'] ?? array();
		$post_types_value = $meta['post_types_value'] ?? 'post';
		$related_opts     = get_option( 'relevanssi_related_settings', array() );
		$disabled_state   = ( 'off' === ( $related_opts['enabled'] ?? 'off' ) ) ? 'disabled="disabled"' : '';
		$matching_checked = 'matching_post_type' === $post_types_value ? 'checked="checked"' : '';

		echo '<fieldset class="relevanssi-settings-fieldset" style="border: none; padding: 0; margin: 0;">';
		printf(
			'<legend class="screen-reader-text" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">%s</legend>',
			esc_html__( 'Related Posts Post Types', 'relevanssi' )
		);

		printf(
			'<p style="margin-bottom: 12px;"><label><input type="checkbox" class="rlv-matching-toggle" name="relevanssi_related_post_types[]" value="matching_post_type" %1$s %2$s /> <strong>%3$s</strong><span class="screen-reader-text"> %4$s</span></label></p>',
			$matching_checked, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$disabled_state, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Matching post type', 'relevanssi' ),
			esc_html__( 'Uncheck this option to choose other post types.', 'relevanssi' )
		);

		foreach ( get_post_types() as $type ) {
			$post_type = get_post_type_object( $type );
			if ( in_array( $type, relevanssi_get_forbidden_post_types(), true ) ) {
				continue;
			}

			$checked      = in_array( $type, $value_array, true ) ? 'checked="checked"' : '';
			$row_disabled = ( ! empty( $matching_checked ) || ! empty( $disabled_state ) ) ? 'disabled="disabled"' : '';
			$locked_class = ! empty( $matching_checked ) ? ' rlv-internally-locked' : '';

			printf(
				'<p style="margin: 4px 0 4px 16px;"><label><input type="checkbox" class="relevanssi-toggle rlv-nonmatching-item%1$s" name="relevanssi_related_post_types[]" value="%2$s" %3$s %4$s> %5$s</label></p>',
				esc_attr( $locked_class ),
				esc_attr( $type ),
				$checked, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$row_disabled, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $post_type->labels->singular_name )
			);
		}

		echo '</fieldset>';

		printf(
			'<p class="description" style="margin-top: 8px;">%s</p>',
			esc_html__( 'The post types to use for related posts. Matching post type means that for each post type, only posts from the same post type are used for related posts.', 'relevanssi' )
		);
	}

	/**
	 * Serializes values clean strings sequences back inside global array models contexts.
	 *
	 * @param array $request Payload payload coming down from input targets views interfaces elements.
	 * @return bool True if successfully synchronized up to option arrays metrics mappings records.
	 */
	public function save( array $request ): bool {
		$settings   = get_option( 'relevanssi_related_settings', array() );
		$post_types = isset( $request['relevanssi_related_post_types'] ) ? (array) $request['relevanssi_related_post_types'] : array();

		$sanitized = array_map( 'sanitize_text_field', $post_types );

		$settings['post_types'] = implode( ',', $sanitized );
		return update_option( 'relevanssi_related_settings', $settings );
	}
}
