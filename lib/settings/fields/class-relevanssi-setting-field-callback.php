<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-callback.php
 *
 * Custom function callback field layout executor engine for Relevanssi settings panels.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Callback
 *
 * Delegates row execution entirely to external functions to accommodate
 * complex multi-row rendering hooks without inheriting default column wrappers.
 */
class Relevanssi_Setting_Field_Callback extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Overrides the primary layout workflow completely.
	 * * Bypasses the abstract field's automatic <tr><th> wrapper to allow
	 * the callback to print its own custom row layout matrices safely.
	 *
	 * @return void Writes layout markup directly to the output buffer.
	 */
	public function render() {
		if ( isset( $this->config['callback'] ) && is_callable( $this->config['callback'] ) ) {
			call_user_func( $this->config['callback'], $this->id, $this->config );
			return;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			printf(
				'<tr class="relevanssi-callback-error">
					<td colspan="2" style="color: #d63638; font-weight: bold;">%s</td>
				</tr>',
				esc_html__( 'Error: Defined settings callback parameter is missing or non-callable.', 'relevanssi' )
			);
		}
	}

	/**
	 * Abstract requirement stub. Unused since render() overrides the pipeline execution.
	 *
	 * @return void
	 */
	protected function render_input() {
		// No-op loop wrapper. Execution is handled safely inside the structural render handler.
	}

	/**
	 * Intercepts standard individual parameter saving logic.
	 *
	 * Complex callback blocks often store multi-layered fields or custom option paths
	 * directly. If an explicit saving routine is configured, we route it; otherwise,
	 * we skip automated basic string option serialization to avoid corrupted settings.
	 *
	 * @param array $request The raw payload array matrix post iteration from admin views.
	 * @return bool True if operations successfully persist metadata variations.
	 */
	public function save( array $request ): bool {
		if ( isset( $this->config['save_callback'] ) && is_callable( $this->config['save_callback'] ) ) {
			return (bool) call_user_func( $this->config['save_callback'], $this->id, $request, $this->config );
		}

		// Return true to prevent throwing validation processing block failures up the engine pipeline.
		return true;
	}
}
