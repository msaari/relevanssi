<?php
/**
 * Sub section header element generator structure control module.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Subheader
 *
 * Handles formatting for wide full-span partition headers splitting settings tables logically.
 */
class Relevanssi_Setting_Field_Subheader extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Overrides base rendering rule configuration to spanning text elements safely over standard table columns.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! $this->is_visible() ) {
			return;
		}

		printf(
			'<tr id="row_%1$s" class="relevanssi-subsection-header"><td colspan="2"><h3>%2$s</h3><p class="description">%3$s</p></td></tr>',
			esc_attr( $this->id ),
			esc_html( $this->config['title'] ?? '' ),
			wp_kses_post( $this->config['description'] ?? '' )
		);
	}

	/**
	 * Passive no-op saving override for purely structural or execution nodes.
	 *
	 * @param array $request Raw admin form submission request data matrix.
	 * @return bool Always true to prevent pipeline validation failures.
	 */
	public function save( array $request ): bool {
		return true;
	}

	/**
	 * Stub interface integration avoiding execution requirements.
	 *
	 * @return void
	 */
	protected function render_input() {}
}
