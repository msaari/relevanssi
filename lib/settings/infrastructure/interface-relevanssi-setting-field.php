<?php
/**
 * /lib/settings/infrastructure/interface-relevanssi-setting-field.php
 *
 * Interface definition for Relevanssi premium setting fields handlers.
 *
 * @package Relevanssi
 */

/**
 * Interface Relevanssi_Setting_Field_Interface
 *
 * Mandates the structural blueprint contracts required for configuration-driven
 * admin form elements inside the Relevanssi settings engine.
 */
interface Relevanssi_Setting_Field_Interface {

	/**
	 * Constructor accepts the field's unique key and configuration metadata.
	 *
	 * @param string $id     Unique option field key string descriptor identifier.
	 * @param array  $config Keyed definitions specifying control constraints.
	 */
	public function __construct( string $id, array $config );

	/**
	 * Responsible for rendering the entire table row wrapper (<tr>, <th>, <td>).
	 *
	 * @return void
	 */
	public function render();

	/**
	 * Extracts, sanitizes, and updates the database option value from a raw request payload.
	 *
	 * @param array $request The raw request matrix from the administration page form post.
	 * @return bool True if the database option was successfully updated, false otherwise.
	 */
	public function save( array $request ): bool;
}
