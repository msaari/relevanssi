<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-related-keywords.php
 *
 * Handles rendering and saving for the complex Related Posts Keyword Sources and Restrictions matrix.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Related_Keywords
 */
class Relevanssi_Setting_Field_Related_Keywords extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Renders the matrix table for keyword sources and taxonomy limits.
	 *
	 * @return void Direct visual layout serialization string stream.
	 */
	protected function render_input() {
		$meta            = $this->config['meta'] ?? array();
		$keyword_sources = $meta['keyword_sources'] ?? array();
		$restrict_taxos  = $meta['restrict_taxos'] ?? array();
		$related_opts    = get_option( 'relevanssi_related_settings', array() );
		$disabled_state  = ( 'off' === ( $related_opts['enabled'] ?? 'off' ) ) ? 'disabled="disabled"' : '';

		// Synthesize the core "Title" pseudo-object to trace matching fields matching constraints.
		$title_object               = new stdClass();
		$title_object->name         = 'title';
		$title_object->labels       = new stdClass();
		$title_object->labels->name = __( 'Title', 'relevanssi' );

		$taxos = get_taxonomies( '', 'objects' );
		array_unshift( $taxos, $title_object );

		$taxonomies_list          = array_flip( get_option( 'relevanssi_index_taxonomies_list', array() ) );
		$taxonomies_list['title'] = true;

		$not_indexed = array();

		echo '<fieldset class="relevanssi-settings-fieldset" style="border: none; padding: 0; margin: 0;">';
		printf(
			'<legend class="screen-reader-text" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">%s</legend>',
			esc_html__( 'Related Posts Keyword Sources and Restrictions', 'relevanssi' )
		);

		echo '<div class="relevanssi-options-grid">';

		foreach ( $taxos as $taxonomy ) {
			if ( in_array( $taxonomy->name, relevanssi_get_forbidden_taxonomies(), true ) ) {
				continue;
			}
			if ( ! isset( $taxonomies_list[ $taxonomy->name ] ) ) {
				$not_indexed[] = $taxonomy->labels->name ?? $taxonomy->name;
				continue;
			}

			$checked          = in_array( $taxonomy->name, $keyword_sources, true ) ? 'checked="checked"' : '';
			$restrict_checked = in_array( $taxonomy->name, $restrict_taxos, true ) ? 'checked="checked"' : '';

			printf(
				'<div class="grid-row"><div class="grid-label"><label><input class="relevanssi-toggle" type="checkbox" name="relevanssi_related_keyword[]" %1$s value="%2$s" %4$s/> %3$s</label></div><div class="grid-field">',
				$checked, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_attr( $taxonomy->name ),
				esc_html( $taxonomy->labels->name ),
				$disabled_state // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);

			if ( 'title' !== $taxonomy->name ) {
				printf(
					'<label><input type="checkbox" name="relevanssi_related_restrict[]" %1$s value="%2$s" %3$s/> %4$s %5$s (<code>%2$s</code>)</label>',
					$restrict_checked, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_attr( $taxonomy->name ),
					$disabled_state, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html__( 'Restrict to taxonomy ', 'relevanssi' ),
					esc_html( $taxonomy->labels->name )
				);
			}
			echo '</div></div>';
		}
		echo '</div>';

		echo '</fieldset>';

		if ( ! empty( $not_indexed ) ) {
			printf(
				'<div class="relevanssi-notice relevanssi-notice-warning" style="margin-top: 16px; margin-bottom: 16px;"><p><strong>%1$s</strong> %2$s.</p></div>',
				esc_html__( "These taxonomies are missing here, because Relevanssi isn't set to index them:", 'relevanssi' ),
				esc_html( implode( ', ', $not_indexed ) )
			);
		}
		printf(
			'<p class="description" style="margin-top: 8px;">%s</p>',
			esc_html__( "The sources Relevanssi uses for related post keywords. Keywords from these sources are then used to search the Relevanssi index to find related posts. Make sure you choose something, otherwise you won't see results or will see random results. In addition of these sources, you can also define your own keywords for each post from the post edit screen.", 'relevanssi' )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'If you choose to restrict to the taxonomy, those keywords will only match in the same category. For example restricted category search terms will only match to category, not to post content. This may lead to better precision, depending on how the taxonomy terms are used.', 'relevanssi' )
		);
	}

	/**
	 * Intercepts and parses submitted multi-dimensional fields matrices back to Relevanssi core array records.
	 *
	 * @param array $request Raw input parameters bundle array matrix from the post layout payload.
	 * @return bool True if options collection changes successfully persist inside the database option block.
	 */
	public function save( array $request ): bool {
		$settings = get_option( 'relevanssi_related_settings', array() );

		$keywords     = isset( $request['relevanssi_related_keyword'] ) ? (array) $request['relevanssi_related_keyword'] : array();
		$restrictions = isset( $request['relevanssi_related_restrict'] ) ? (array) $request['relevanssi_related_restrict'] : array();

		$sanitized_keywords     = array_map( 'sanitize_text_field', $keywords );
		$sanitized_restrictions = array_map( 'sanitize_text_field', $restrictions );

		$settings['keyword']  = implode( ',', $sanitized_keywords );
		$settings['restrict'] = implode( ',', $sanitized_restrictions );

		return update_option( 'relevanssi_related_settings', $settings );
	}
}
