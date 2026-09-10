<?php
/**
 * Regression tests for the Free/Premium package boundary.
 *
 * Usage: php tests/verify-free-package.php <free-package-directory>
 *
 * @package Relevanssi
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open, WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone CLI regression test; WordPress is not loaded.

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

/**
 * Stops the test process with an error.
 *
 * @param string $message The failure message.
 */
function relevanssi_free_boundary_fail( $message ) {
	fwrite( STDERR, "ERROR: $message\n" );
	exit( 1 );
}

/**
 * Runs one scenario in a fresh PHP process.
 *
 * Fresh processes are required because PHP constants and functions cannot be
 * undefined between scenarios.
 *
 * @param string $scenario    The scenario name.
 * @param string $package_root The staged Free package directory.
 */
function relevanssi_free_boundary_run_scenario( $scenario, $package_root ) {
	$command     = implode(
		' ',
		array(
			escapeshellarg( PHP_BINARY ),
			escapeshellarg( __FILE__ ),
			escapeshellarg( '--scenario=' . $scenario ),
			escapeshellarg( $package_root ),
		)
	);
	$descriptors = array(
		0 => array( 'pipe', 'r' ),
		1 => array( 'pipe', 'w' ),
		2 => array( 'pipe', 'w' ),
	);
	$process     = proc_open( $command, $descriptors, $pipes );
	if ( ! is_resource( $process ) ) {
		relevanssi_free_boundary_fail( "Could not start scenario '$scenario'." );
	}

	fclose( $pipes[0] );
	$stdout = stream_get_contents( $pipes[1] );
	$stderr = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	$status = proc_close( $process );

	if ( 0 !== $status ) {
		relevanssi_free_boundary_fail( "Scenario '$scenario' failed:\n$stdout$stderr" );
	}
}

$scenario     = isset( $argv[1] ) && 0 === strpos( $argv[1], '--scenario=' ) ? substr( $argv[1], 11 ) : '';
$package_arg  = '' !== $scenario ? ( $argv[2] ?? '' ) : ( $argv[1] ?? '' );
$package_root = realpath( $package_arg );

if ( '' !== $scenario ) {
	if ( false === $package_root || ! is_dir( $package_root ) ) {
		relevanssi_free_boundary_fail( 'The Free package directory was not found.' );
	}

	define( 'RELEVANSSI_PREMIUM', true );
	if ( 'constant-tampering' !== $scenario ) {
		/**
		 * Marks the separately loaded Premium implementation.
		 */
		function relevanssi_premium_init() {}
	}
	if ( 'premium-uninstall' === $scenario ) {
		define( 'UNINSTALLING_RELEVANSSI_PREMIUM', true );
	}

	require_once $package_root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'common.php';
	if ( 'constant-tampering' === $scenario ) {
		if ( relevanssi_is_premium() ) {
			relevanssi_free_boundary_fail( 'The Free constant enabled Premium detection.' );
		}
	} elseif ( ! relevanssi_is_premium() ) {
		relevanssi_free_boundary_fail( 'The Premium implementation marker was not detected.' );
	}

	$deleted_options = array();
	/**
	 * Records option deletions made by the uninstall routine.
	 *
	 * @param string $option The option name.
	 * @return bool Always true for the test double.
	 */
	function delete_option( $option ) {
		global $deleted_options;
		$deleted_options[] = $option;
		return true;
	}

	require_once __DIR__ . DIRECTORY_SEPARATOR . 'class-relevanssi-free-boundary-wpdb.php';

	$wpdb = new Relevanssi_Free_Boundary_WPDB();
	require_once $package_root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'uninstall.php';
	relevanssi_drop_database_tables();
	$direct_drop_queries = array_filter(
		$wpdb->queries,
		function ( $query ) {
			return false !== strpos( $query, 'DROP TABLE' );
		}
	);
	$expected_cleanup    = in_array( $scenario, array( 'constant-tampering', 'premium-uninstall' ), true );
	if ( $expected_cleanup && 4 !== count( $direct_drop_queries ) ) {
		relevanssi_free_boundary_fail( 'The shared table drop was incorrectly blocked.' );
	}
	if ( ! $expected_cleanup && ( ! empty( $direct_drop_queries ) || ! empty( $wpdb->queries ) ) ) {
		relevanssi_free_boundary_fail( 'The shared table drop ran while Premium was loaded.' );
	}
	$wpdb->queries = array();

	relevanssi_uninstall_free();

	$drop_queries = array_filter(
		$wpdb->queries,
		function ( $query ) {
			return false !== strpos( $query, 'DROP TABLE' );
		}
	);
	$did_cleanup  = ! empty( $deleted_options ) && 4 === count( $drop_queries );

	if ( $expected_cleanup && ! $did_cleanup ) {
		relevanssi_free_boundary_fail( 'Expected shared Free cleanup did not run.' );
	}
	if ( ! $expected_cleanup && ( ! empty( $deleted_options ) || ! empty( $wpdb->queries ) ) ) {
		relevanssi_free_boundary_fail( 'Free cleanup ran while Premium was loaded.' );
	}

	echo ucfirst( str_replace( '-', ' ', $scenario ) ) . " scenario passed.\n";
	exit( 0 );
}

if ( 2 !== count( $argv ) || false === $package_root || ! is_dir( $package_root ) ) {
	fwrite( STDERR, "Usage: php tests/verify-free-package.php <free-package-directory>\n" );
	exit( 2 );
}

foreach ( array( 'constant-tampering', 'premium-loaded', 'premium-uninstall' ) as $scenario_name ) {
	relevanssi_free_boundary_run_scenario( $scenario_name, $package_root );
}

fwrite( STDOUT, "Free package regression tests passed: $package_root\n" );
