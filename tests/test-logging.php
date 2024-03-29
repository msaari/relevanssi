<?php
/**
 * Class LoggingTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi logging features.
 */
class LoggingTest extends WP_UnitTestCase {
	/**
	 * Sets up the test.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
	}

	/**
	 * Tests relevanssi_update_log().
	 */
	public function test_update_log() {
		$this->assertFalse( relevanssi_update_log( '', 0 ) );

		$_SERVER['HTTP_USER_AGENT'] = 'Mediapartners-Google';
		$this->assertFalse( relevanssi_update_log( 'test', 1 ) );
		$_SERVER['HTTP_USER_AGENT'] = '';

		add_filter( 'relevanssi_log_get_user', array( $this, 'set_user' ) );
		update_option( 'relevanssi_omit_from_logs', '5' );
		$this->assertFalse( relevanssi_update_log( 'test', 1 ) );
		update_option( 'relevanssi_omit_from_logs', 'lucy, mikko, alice, carol' );
		$this->assertFalse( relevanssi_update_log( 'test', 1 ) );
		remove_filter( 'relevanssi_log_get_user', array( $this, 'set_user' ) );

		update_option( 'relevanssi_log_queries_with_ip', 'on' );
		$this->assertTrue( relevanssi_update_log( 'test', 1 ) );
		add_filter( 'relevanssi_ok_to_log', '__return_false' );
		$this->assertFalse( relevanssi_update_log( 'test', 1 ) );
	}

	/**
	 * Tests partial query string removal.
	 *
	 * @group new
	 */
	public function test_session_id() {
		relevanssi_update_log( 'tes', 1 );
		relevanssi_update_log( 'test', 1 );
		relevanssi_update_log( 'testi', 1 );
		relevanssi_update_log( 'testin', 1 );
		relevanssi_update_log( 'testing', 1 );

		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}relevanssi_log WHERE query LIKE 'tes%'" );
		$this->assertEquals( 1, $count );
	}

	/**
	 * Tests relevanssi_trim_logs().
	 */
	public function test_trim_logs() {
		global $wpdb;
		relevanssi_update_log( 'test', 1 );
		relevanssi_update_log( 'test', 2 );
		relevanssi_update_log( 'test', 3 );
		relevanssi_update_log( 'test', 4 );

		$wpdb->update(
			"{$wpdb->prefix}relevanssi_log",
			array(
				'time' => '2019-01-01 01:00:00',
			),
			array(
				'query' => 'test',
			)
		);

		update_option( 'relevanssi_trim_logs', 1 );
		$this->assertEquals( 4, relevanssi_trim_logs() );
	}

	/**
	 * Tests relevanssi_export_log_data().
	 */
	public function test_export_log_data() {
		$user = wp_get_current_user();

		relevanssi_update_log( 'test', 1 );
		relevanssi_update_log( 'test', 2 );
		relevanssi_update_log( 'test', 3 );
		relevanssi_update_log( 'test', 4 );

		$export = relevanssi_export_log_data( $user->ID, 0 );
		$this->assertEquals( true, $export['done'] );
		$this->assertEquals( 4, count( $export['data'] ) );
		$this->assertEquals( 'test', $export['data'][0]['data'][1]['value'] );
	}

	/**
	 * Tests relevanssi_erase_log_data().
	 */
	public function test_erase_log_data() {
		$user = wp_get_current_user();

		relevanssi_update_log( 'test', 1 );
		relevanssi_update_log( 'test', 2 );
		relevanssi_update_log( 'test', 3 );
		relevanssi_update_log( 'test', 4 );

		$erase_results = relevanssi_erase_log_data( $user->ID, 0 );
		$this->assertEquals( true, $erase_results['items_removed'] );
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

	/**
	 * Returns a plain user object.
	 *
	 * @return stdClass An object with ID and user_login set.
	 */
	public static function set_user() {
		$user             = new stdClass();
		$user->ID         = 5;
		$user->user_login = 'mikko';
		return $user;
	}
}
