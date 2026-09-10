<?php
/**
 * Test double for the WordPress database object used by the Free boundary test.
 *
 * @package Relevanssi
 */

/**
 * Minimal database test double used by the uninstall routine.
 */
class Relevanssi_Free_Boundary_WPDB {
	/**
	 * Database table prefix.
	 *
	 * @var string
	 */
	public $prefix = 'wp_';

	/**
	 * Postmeta table name.
	 *
	 * @var string
	 */
	public $postmeta = 'wp_postmeta';

	/**
	 * Queries recorded by the test double.
	 *
	 * @var array
	 */
	public $queries = array();

	/**
	 * Returns the expected table name for SHOW TABLES checks.
	 *
	 * @param string $query The SQL query.
	 * @return string|null The matching table name, or null.
	 */
	public function get_var( $query ) {
		$tables = array(
			'wp_relevanssi_stopwords' => 'wp_relevanssi_stopwords',
			"LIKE 'wp_relevanssi'"    => 'wp_relevanssi',
			'wp_relevanssi_log'       => 'wp_relevanssi_log',
			'wp_relevanssi_tracking'  => 'wp_relevanssi_tracking',
		);
		foreach ( $tables as $needle => $table ) {
			if ( false !== strpos( $query, $needle ) ) {
				return $table;
			}
		}
		return null;
	}

	/**
	 * Records an SQL query.
	 *
	 * @param string $query The SQL query.
	 * @return bool Always true for the test double.
	 */
	public function query( $query ) {
		$this->queries[] = $query;
		return true;
	}
}
