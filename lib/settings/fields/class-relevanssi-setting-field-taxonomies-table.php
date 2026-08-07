<?php
/**
 * Taxonomies listing data tracking element component for premium settings.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Taxonomies_Table
 *
 * Converts active systems categories objects lists directly to checkboxes grids templates.
 */
class Relevanssi_Setting_Field_Taxonomies_Table extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Iterates over properties outputting operational tracking checkboxes states blocks safely.
	 *
	 * @return void Writes layout components straight to the output buffer stream.
	 */
	protected function render_input() {
		$index_taxonomies_list = get_option( 'relevanssi_index_taxonomies_list', array() );

		if ( ! is_array( $index_taxonomies_list ) ) {
			$index_taxonomies_list = array();
		}

		if ( ! function_exists( 'get_taxonomies' ) || ! function_exists( 'relevanssi_get_forbidden_taxonomies' ) ) {
			return;
		}
		?>
		<fieldset class="relevanssi-settings-fieldset" style="border: none; padding: 0; margin: 0;">
			<legend class="screen-reader-text" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;"><?php esc_html_e( 'Taxonomies to index', 'relevanssi' ); ?></legend>

			<table class="widefat striped content-types-table" id="custom_taxonomies_table" style="width: 100%; max-width: 500px; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: none; table-layout: fixed;">
				<thead>
					<tr>
						<th style="font-weight: 600; padding: 8px 12px; width: 83%;"><?php esc_html_e( 'Taxonomy', 'relevanssi' ); ?></th>
						<th style="font-weight: 600; padding: 8px 12px; width: 17%; text-align: center;"><?php esc_html_e( 'Index', 'relevanssi' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$taxos = get_taxonomies( '', 'objects' );
				foreach ( $taxos as $taxonomy ) {
					if ( in_array( $taxonomy->name, relevanssi_get_forbidden_taxonomies(), true ) ) {
						continue;
					}

					$name_id    = 'relevanssi_index_taxonomy_' . $taxonomy->name;
					$is_checked = in_array( $taxonomy->name, $index_taxonomies_list, true );
					?>
					<tr>
						<td style="padding: 8px 12px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
							<label for="<?php echo esc_attr( $name_id ); ?>">
								<strong><?php echo esc_html( $taxonomy->labels->name ?? $taxonomy->name ); ?></strong>
							</label>
							<code style="font-size: 11px; margin-left: 6px; color: #646970;">(<?php echo esc_html( $taxonomy->name ); ?>)</code>

							<?php if ( ! $taxonomy->public ) : ?>
								<p class="description" style="margin: 4px 0 0 0; color: #646970; display: flex; align-items: center; gap: 4px; font-size: 11px; white-space: normal;">
									<span class="dashicons dashicons-info-outline" style="font-size: 13px; width: 13px; height: 13px; min-width: 13px;"></span>
									<?php esc_html_e( 'Private taxonomy. Content will be searchable within its assigned posts.', 'relevanssi' ); ?>
								</p>
							<?php endif; ?>
						</td>
						<td style="text-align: center; vertical-align: middle; padding: 8px 12px;">
							<?php
							$aria_label = sprintf(
								// translators: %s is the technical taxonomy slug or identifier name (e.g., "category", "post_tag").
								__( 'Index taxonomy %s', 'relevanssi' ),
								$taxonomy->name
							);
							?>
							<input class="relevanssi-toggle" type="checkbox" name="<?php echo esc_attr( $name_id ); ?>" id="<?php echo esc_attr( $name_id ); ?>" value="1" <?php checked( $is_checked ); ?> aria-label="<?php echo esc_attr( $aria_label ); ?>" />
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</fieldset>
		<?php
	}

	/**
	 * Aggregates taxonomy option tracking choices saving array definitions cleanly.
	 *
	 * @param array $request POST request matrix data parameters cluster block rows.
	 * @return bool True if options successfully update entries matrices array.
	 */
	public function save( array $request ): bool {
		if ( ! isset( $request['rlv_tab'] ) || 'indexing' !== $request['rlv_tab'] ) {
			return false;
		}

		$index_taxonomies = array();
		$taxos            = get_taxonomies( '', 'objects' );
		$autoload_state   = $this->config['autoload'] ?? true;

		foreach ( $taxos as $taxonomy ) {
			if ( in_array( $taxonomy->name, relevanssi_get_forbidden_taxonomies(), true ) ) {
				continue;
			}

			// Target key pattern matched against render layouts configurations: relevanssi_index_taxonomy_{name}.
			if ( isset( $request[ 'relevanssi_index_taxonomy_' . $taxonomy->name ] ) ) {
				$index_taxonomies[] = sanitize_text_field( $taxonomy->name );
			}
		}

		return update_option( 'relevanssi_index_taxonomies_list', $index_taxonomies, $autoload_state );
	}
}