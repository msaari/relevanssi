<?php
/**
 * Class InitTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi init functions pages.
 */
class InitTest extends WP_UnitTestCase {
	/**
	 * Sets up the test.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
	}

	/**
	 * Tests that the admin init function installs the required filters.
	 *
	 * @global array $relevanssi_variables
	 */
	public function test_admin_init() {
		global $relevanssi_variables;

		relevanssi_admin_init();
		$this->assertNotFalse( has_filter( 'admin_enqueue_scripts', 'relevanssi_add_admin_scripts' ) );
		$this->assertNotFalse(
			has_filter(
				'plugin_action_links_' . $relevanssi_variables['plugin_basename'],
				'relevanssi_action_links'
			)
		);
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		require_once dirname( __DIR__ ) . '/lib/uninstall.php';
		if ( RELEVANSSI_PREMIUM ) {
			require_once dirname( __DIR__ ) . '/premium/uninstall.php';
		}

		if ( function_exists( 'relevanssi_uninstall' ) ) {
			relevanssi_uninstall();
		}
		if ( function_exists( 'relevanssi_uninstall_free' ) ) {
			relevanssi_uninstall_free();
		}
	}
}
