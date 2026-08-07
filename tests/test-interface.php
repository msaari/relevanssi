<?php
/**
 * Class InterfaceTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi options pages.
 */
class InterfaceTest extends WP_UnitTestCase {
	/**
	 * Sets up the test.
	 *
	 * Creates 10 posts, with the default content. Assumes the posts will have the
	 * word "content" in the content.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();

		// Truncate the index.
		relevanssi_truncate_index();
	}

	/**
	 * Test loading the options page tabs layout rendering engine.
	 *
	 * This verifies that our custom Factory instantiation and Template-Method rendering loops
	 * run from start to finish without triggering syntax exceptions or runtime crashes.
	 */
	public function test_options() {
		ob_start();

		$tabs = array(
			'overview',
			'indexing',
			'attachments',
			'searching',
			'logging',
			'excerpts',
			'synonyms',
			'stopwords',
			'redirects',
		);

		foreach ( $tabs as $tab ) {
			$_REQUEST['rlv_tab'] = $tab;
			relevanssi_options_form();
		}

		relevanssi_query_log();

		$this->assertTrue( true );
		ob_end_clean();
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
