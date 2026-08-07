<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-category-checklist.php
 *
 * Implements the configuration-driven category inclusion/exclusion checklist field wrapper.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 */

/**
 * Class Relevanssi_Setting_Field_Category_Checklist
 *
 * Handles rendering of interactive category term checklists and maps submitted term
 * arrays into comma-separated string structures inside the database.
 */
class Relevanssi_Setting_Field_Category_Checklist extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Render the taxonomy term checklist container block.
	 *
	 * @return void Writes layout elements straight onto the active administration view.
	 */
	protected function render_input() {
		$selected_value = $this->config['value'] ?? '';
		$selected_cats  = ! empty( $selected_value ) ? explode( ',', $selected_value ) : array();

		// Dynamically establish targeting parameters based on the unique field identifier.
		$is_exclusion = ( 'relevanssi_excat' === $this->id );
		$checklist_id = $is_exclusion ? 'category_exclusion_checklist' : 'category_inclusion_checklist';
		$active_flag  = $is_exclusion ? 'relevanssi_excat_active' : 'relevanssi_cat_active';
		$walker_name  = $this->id;

		if ( ! function_exists( 'get_relevanssi_taxonomy_walker' ) || ! function_exists( 'wp_terms_checklist' ) ) {
			return;
		}

		$walker       = get_relevanssi_taxonomy_walker();
		$walker->name = $walker_name;
		?>
		<div class="categorydiv" style="max-width: 400px;">
			<div class="tabs-panel" style="max-height: 200px; overflow-y: auto; border: 1px solid #dcdcde; padding: 12px; background: #fff; border-radius: 4px;">
				<fieldset>
					<legend class="screen-reader-text" style="display: none;"><?php echo wp_kses_post( $this->config['label'] ?? '' ); ?></legend>
					<ul id="<?php echo esc_attr( $checklist_id ); ?>">
						<?php
						wp_terms_checklist(
							0,
							array(
								'taxonomy'      => 'category',
								'selected_cats' => $selected_cats,
								'walker'        => $walker,
							)
						);
						?>
					</ul>
				</fieldset>
				<input type="hidden" name="<?php echo esc_attr( $active_flag ); ?>" value="1" />
			</div>
		</div>
		<?php
	}

	/**
	 * Intercepts, cleanses, and serializes posted category arrays into comma-separated option strings.
	 *
	 * @param array $request Raw incoming administrative form POST array data parameters.
	 * @return bool True if options updating processes passed validation arrays successfully.
	 */
	public function save( array $request ): bool {
		$active_flag = ( 'relevanssi_excat' === $this->id ) ? 'relevanssi_excat_active' : 'relevanssi_cat_active';

		// If the hidden presence flag is missing, this block wasn't even present on screen.
		if ( ! isset( $request[ $active_flag ] ) ) {
			return false;
		}

		// WordPress checkbox groups submit as an array of selected IDs or are completely absent if none are checked.
		$posted_terms   = $request[ $this->id ] ?? array();
		$sanitized_val  = is_array( $posted_terms ) ? implode( ',', array_map( 'intval', $posted_terms ) ) : '';
		$autoload_state = $this->config['autoload'] ?? true;

		return update_option( $this->id, $sanitized_val, $autoload_state );
	}
}
