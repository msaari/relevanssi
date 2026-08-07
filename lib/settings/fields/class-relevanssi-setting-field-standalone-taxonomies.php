<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-standalone-taxonomies.php
 *
 * Implements configuration-driven independent taxonomy term archive indexing configurations.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 */

/**
 * Class Relevanssi_Setting_Field_Standalone_Taxonomies
 *
 * Manages the data tracks for enabling standalone archive term lookups along with
 * selective indexing matrices across all active taxonomy spaces.
 */
class Relevanssi_Setting_Field_Standalone_Taxonomies extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Render the internal taxonomy switch and selection table grid with optimized spacing distribution.
	 *
	 * @return void Writes safe markup layout straight to output buffers.
	 */
	protected function render_input() {
		$index_taxonomies       = get_option( 'relevanssi_index_taxonomies', 'off' );
		$index_these_taxonomies = get_option( 'relevanssi_index_terms', array() );

		if ( ! is_array( $index_these_taxonomies ) ) {
			$index_these_taxonomies = array();
		}

		if ( ! function_exists( 'get_taxonomies' ) || ! function_exists( 'relevanssi_get_forbidden_taxonomies' ) ) {
			return;
		}

		$is_active = ( 'on' === $index_taxonomies );
		?>
		<div class="relevanssi-standalone-taxonomies-wrapper">
			<label for="relevanssi_index_taxonomies_toggle" style="display: block; margin-bottom: 14px; font-weight: 600;">
				<input class="relevanssi-toggle" type="checkbox" name="relevanssi_index_taxonomies" id="relevanssi_index_taxonomies_toggle" value="on" <?php checked( $index_taxonomies, 'on' ); ?> />
				<?php esc_html_e( 'Index standalone archive terms', 'relevanssi' ); ?>
			</label>

			<div id="wrapper_standalone_taxonomies_table" class="<?php echo $is_active ? '' : 'rlv-js-hidden'; ?>" style="transition: all 0.2s ease-in-out;">
				<table class="widefat striped content-types-table" style="width: 100%; max-width: 550px; margin-top: 12px; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: none; table-layout: fixed;">
					<thead>
						<tr>
							<th style="font-weight: 600; padding: 8px 12px; width: 70%;"><?php esc_html_e( 'Taxonomy', 'relevanssi' ); ?></th>
							<th style="font-weight: 600; padding: 8px 12px; width: 15%; text-align: center;"><?php esc_html_e( 'Public?', 'relevanssi' ); ?></th>
							<th style="font-weight: 600; padding: 8px 12px; width: 15%; text-align: center;"><?php esc_html_e( 'Index', 'relevanssi' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					$taxos = get_taxonomies( '', 'objects' );
					foreach ( $taxos as $taxonomy ) {
						if ( in_array( $taxonomy->name, relevanssi_get_forbidden_taxonomies(), true ) ) {
							continue;
						}

						$is_public       = ! empty( $taxonomy->public );
						$public_label    = $is_public ? __( 'yes', 'relevanssi' ) : __( 'no', 'relevanssi' );
						$public_badge_cl = $is_public ? 'color: #008a20; font-weight: 600;' : 'color: #646970;';
						?>
						<tr>
							<td style="padding: 8px 12px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
								<label for="relevanssi_index_terms_<?php echo esc_attr( $taxonomy->name ); ?>"><strong><?php echo esc_html( $taxonomy->labels->name ?? $taxonomy->name ); ?></strong></label>
								<code style="font-size: 11px; margin-left: 6px; color: #646970;">(<?php echo esc_html( $taxonomy->name ); ?>)</code>
							</td>
							<td style="text-align: center; vertical-align: middle; padding: 8px 12px; font-size: 12px; <?php echo esc_attr( $public_badge_cl ); ?>">
								<?php echo esc_html( $public_label ); ?>
							</td>
							<td style="text-align: center; vertical-align: middle; padding: 8px 12px;">
								<input class="relevanssi-toggle" type="checkbox" id="relevanssi_index_terms_<?php echo esc_attr( $taxonomy->name ); ?>" name="relevanssi_index_terms_<?php echo esc_attr( $taxonomy->name ); ?>" value="1" <?php checked( in_array( $taxonomy->name, $index_these_taxonomies, true ) ); ?> />
							</td>
						</tr>
						<?php
					}
					?>
					</tbody>
				</table>
			</div>
		</div>

		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				const masterToggle = document.getElementById('relevanssi_index_taxonomies_toggle');
				const tableWrapper = document.getElementById('wrapper_standalone_taxonomies_table');

				function toggleTaxonomyTableVisibility() {
					if (!masterToggle || !tableWrapper) return;
					if (masterToggle.checked) {
						tableWrapper.classList.remove('rlv-js-hidden');
					} else {
						tableWrapper.classList.add('rlv-js-hidden');
					}
				}

				if (masterToggle) {
					masterToggle.addEventListener('change', toggleTaxonomyTableVisibility);
				}
			});
		</script>
		<?php
	}

	/**
	 * Intercepts request payloads to serialize master switches and targeted taxonomy maps.
	 *
	 * @param array $request Raw incoming administrative form POST array metrics.
	 * @return bool True if options successfully update inside database states.
	 */
	public function save( array $request ): bool {
		// 1. Process master option tracking switch toggles
		$master_state   = isset( $request['relevanssi_index_taxonomies'] ) ? 'on' : 'off';
		$autoload_state = $this->config['autoload'] ?? true;
		update_option( 'relevanssi_index_taxonomies', $master_state, $autoload_state );

		// 2. Loop active global states to reconstruct explicit term indexing arrays
		$taxonomies          = get_taxonomies( '', 'objects' );
		$selected_taxonomies = array();

		foreach ( $taxonomies as $taxonomy ) {
			if ( in_array( $taxonomy->name, relevanssi_get_forbidden_taxonomies(), true ) ) {
				continue;
			}
			if ( isset( $request[ 'relevanssi_index_terms_' . $taxonomy->name ] ) ) {
				$selected_taxonomies[] = $taxonomy->name;
			}
		}

		return update_option( 'relevanssi_index_terms', $selected_taxonomies, $autoload_state );
	}
}