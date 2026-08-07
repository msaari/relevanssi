<?php
/**
 * Custom fields listing interactive action button control component handler.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Custom_Fields_List
 *
 * Implements interactive DOM button markup injection routines targeting metadata traces.
 */
class Relevanssi_Setting_Field_Custom_Fields_List extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Renders the custom field tracking helper button layout wrapper blocks securely.
	 *
	 * @return void
	 */
	protected function render_input() {
		printf(
			'<button type="button" class="button button-outline" id="list_custom_fields">%s</button>',
			esc_html__( 'Show indexed custom fields', 'relevanssi' )
		);
		?>
		<style type="text/css">
			#relevanssi_custom_field_list {
				margin-top: 14px;
				width: 100%;
				max-width: 445px; /* Capping at pixels breaks the fluid table expansion loop completely */
				box-sizing: border-box;
				overflow-wrap: anywhere;
				white-space: normal;
				background: #f6f7f7;
				padding: 10px 14px;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				font-family: monospace;
				font-size: 12px;
				line-height: 1.6;
				max-height: 200px;
				overflow-y: auto;
			}
			/* Hidden State Guard: Erases the box from view entirely if it contains no text content */
			#relevanssi_custom_field_list:empty {
				display: none !important;
			}
		</style>
		<div id="relevanssi_custom_field_list"></div>
		<?php
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
}
