<?php
/**
 * /lib/settings/class-relevanssi-settings-renderer.php
 *
 * Renderer engine definition for Relevanssi premium setting forms layout.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Settings_Renderer
 *
 * Processes configuration tables data matrices and converts them safely to structural
 * WordPress dashboard settings control screens components.
 */
class Relevanssi_Settings_Renderer {

	/**
	 * Main loop generating standardized WordPress admin form markup tables blocks.
	 *
	 * @param array $fields Managed cluster grouping individual option key data sets.
	 *
	 * @return void Prints tabular elements structural containers directly.
	 */
	public static function render_table( array $fields ) {
		echo '<table class="form-table" role="presentation">';

		foreach ( $fields as $id => $config ) {
			try {
				$field_instance = Relevanssi_Setting_Field_Factory::create( $id, $config );
				$field_instance->render();
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					printf( '<tr class="error"><td colspan="2">%s</td></tr>', esc_html( $e->getMessage() ) );
				}
			}
		}

		echo '</table>';
	}

	/**
	 * Iterates over a structural matrix block printing standard dashboard context summaries.
	 *
	 * @param array $config_array Array metadata payload configurations to map against descriptions.
	 *
	 * @return void Formats list tags wrapping sidebar contextual explanations out directly.
	 */
	public static function render_sidebar_list( array $config_array ) {
		if ( empty( $config_array ) ) {
			return;
		}

		foreach ( $config_array as $key => $field ) {
			if ( isset( $field['visible'] ) && false === $field['visible'] ) {
				continue;
			}
			if ( empty( $field['sidebar_title'] ) ) {
				continue;
			}

			$hover_target = $field['hover_target'] ?? 'sb-' . $key;
			$description  = $field['sidebar_desc'] ?? '';
			?>
			<li id="<?php echo esc_attr( $hover_target ); ?>">
				<strong><?php echo esc_html( $field['sidebar_title'] ); ?></strong> 
				<?php echo wp_kses_post( $description ); ?>
			</li>
			<?php
		}
	}
}