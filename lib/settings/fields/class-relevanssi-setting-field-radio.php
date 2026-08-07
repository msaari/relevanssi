<?php
/**
 * Radio button group settings field component implementation.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Radio
 *
 * Iterates through a flat options matrix payload to render a standard stacked
 * architectural group of operational WordPress radio button selection controls.
 */
class Relevanssi_Setting_Field_Radio extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Outputs the operational HTML radio buttons selection group segment.
	 *
	 * @return void
	 */
	protected function render_input() {
		$current_value = $this->config['value'] ?? '';
		$options       = $this->config['options'] ?? array();

		if ( empty( $options ) ) {
			return;
		}

		echo '<fieldset>';
		printf( '<legend class="screen-reader-text"><span>%s</span></legend>', esc_html( $this->config['label'] ?? '' ) );

		foreach ( $options as $value => $label ) {
			$option_id = sprintf( '%s_%s', $this->id, $value );
			?>
			<label for="<?php echo esc_attr( $option_id ); ?>" style="display: block; margin-bottom: 8px;">
				<input 
					type="radio" 
					id="<?php echo esc_attr( $option_id ); ?>" 
					name="<?php echo esc_attr( $this->id ); ?>" 
					value="<?php echo esc_attr( $value ); ?>" 
					<?php checked( $current_value, $value ); ?> 
				/>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php
		}

		echo '</fieldset>';

		if ( ! empty( $this->config['description'] ) ) {
			printf(
				'<p class="description" style="margin-top: 4px;">%s</p>',
				esc_html( $this->config['description'] )
			);
		}
	}
}
