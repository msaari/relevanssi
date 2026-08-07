<?php
/**
 * Specialized complex widefat post type checklist element engine component mapper.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Post_Types_Table
 *
 * Constructs internal interactive configuration layout tables tracing registered entity elements natively.
 */
class Relevanssi_Setting_Field_Post_Types_Table extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Builds specialized inner tabular fields configurations parsing active parameters structures securely.
	 *
	 * @return void Writes layout markup structures directly to screen buffers.
	 */
	protected function render_input() {
		$index_post_types = $this->config['value'] ?? array();

		if ( ! is_array( $index_post_types ) ) {
			$index_post_types = array();
		}

		if ( ! function_exists( 'get_post_types' ) || ! function_exists( 'relevanssi_get_forbidden_post_types' ) ) {
			return;
		}

		$public_types   = array_merge(
			get_post_types( array( 'exclude_from_search' => '0' ) ),
			get_post_types( array( 'exclude_from_search' => false ) )
		);
		$all_post_types = get_post_types();
		?>
		<fieldset class="relevanssi-settings-fieldset" style="border: none; padding: 0; margin: 0;">
			<legend class="screen-reader-text" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;"><?php esc_html_e( 'Post types to index', 'relevanssi' ); ?></legend>

			<table class="widefat striped content-types-table" id="index_post_types_table" style="width: 100%; max-width: 500px; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: none; table-layout: fixed;">
				<thead>
					<tr>
						<th style="font-weight: 600; padding: 8px 12px; width: 83%;"><?php esc_html_e( 'Type', 'relevanssi' ); ?></th>
						<th style="font-weight: 600; padding: 8px 12px; width: 17%; text-align: center;"><?php esc_html_e( 'Index', 'relevanssi' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $all_post_types as $type ) {
					if ( in_array( $type, relevanssi_get_forbidden_post_types(), true ) ) {
						continue;
					}

					$name_id       = 'relevanssi_index_type_' . $type;
					$is_checked    = in_array( $type, $index_post_types, true );
					$is_non_public = ! in_array( $type, $public_types, true );
					$pt_object     = get_post_type_object( $type );
					$display_label = $pt_object->labels->name ?? $type;
					?>
					<tr>
						<td style="padding: 8px 12px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
							<label for="<?php echo esc_attr( $name_id ); ?>">
								<strong><?php echo esc_html( $display_label ); ?></strong>
							</label>
							<code style="font-size: 11px; margin-left: 6px; color: #646970;">(<?php echo esc_html( $type ); ?>)</code>

							<?php if ( $is_non_public ) : ?>
								<p class="description" style="margin: 4px 0 0 0; color: #646970; display: flex; align-items: center; gap: 4px; font-size: 11px; white-space: normal;">
									<span class="dashicons dashicons-info-outline" style="font-size: 13px; width: 13px; height: 13px; min-width: 13px;"></span>
									<?php esc_html_e( 'Notice: This post type is registered as non-public.', 'relevanssi' ); ?>
								</p>
							<?php endif; ?>
						</td>
						<td style="text-align: center; vertical-align: middle; padding: 8px 12px;">
							<?php
							$aria_label = sprintf(
								// translators: %s is the technical post type name slug identifier (e.g., "post", "page", "product").
								__( 'Index post type %s', 'relevanssi' ),
								$type
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
	 * Aggregates programmatic prefix elements and saves active types arrays.
	 *
	 * @param array $request Matrix structure data coming from post submission streams.
	 * @return bool True if modifications saved updates parameters array safely.
	 */
	public function save( array $request ): bool {
		$index_post_types = array();
		$all_post_types   = get_post_types();
		$autoload_state   = $this->config['autoload'] ?? true;

		foreach ( $all_post_types as $type ) {
			if ( in_array( $type, relevanssi_get_forbidden_post_types(), true ) ) {
				continue;
			}

			// Target key pattern constructed during layout rendering cycle: relevanssi_index_type_{slug}.
			if ( isset( $request[ 'relevanssi_index_type_' . $type ] ) ) {
				$index_post_types[] = sanitize_text_field( $type );
			}
		}

		return update_option( 'relevanssi_index_post_types', $index_post_types, $autoload_state );
	}
}