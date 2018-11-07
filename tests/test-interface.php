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

		// Truncate the index.
		relevanssi_truncate_index();
	}

	/**
	 * Test loading the options page. This doesn't include any assertions, but should
	 * pass without an error.
	 */
	public function test_options() {
		ob_start();

		$_REQUEST['tab'] = 'overview';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'indexing';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'attachments';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'searching';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'logging';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'excerpts';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'overview';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'synonyms';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'stopwords';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'importexport';
		relevanssi_options_form();

		$_REQUEST['tab'] = 'search';
		relevanssi_options_form();

		relevanssi_query_log();

		ob_end_clean();
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		if ( function_exists( 'relevanssi_uninstall' ) ) {
			relevanssi_uninstall();
		}
		if ( function_exists( 'relevanssi_uninstall_free' ) ) {
			relevanssi_uninstall_free();
		}
	}
}
