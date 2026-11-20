<?php
/**
 * Campaign test bootstrap.
 *
 * Set RELEVANSSI_TEST_FREE_PACKAGE to the generated Free package directory.
 * WP_TESTS_INSTALLATION must use a disposable test database.
 *
 * @package Relevanssi
 */

/** Loads Composer before the disposable WordPress test installation. */
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

\rask\WpTestFramework\Framework::load();

$relevanssi_test_package = getenv( 'RELEVANSSI_TEST_FREE_PACKAGE' );
if ( ! $relevanssi_test_package ) {
	$relevanssi_test_package = dirname( __DIR__ );
}
require_once $relevanssi_test_package . '/relevanssi.php';
