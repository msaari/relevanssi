<?php
/**
 * Class StopwordTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi stopwords.
 *
 * @group stopwords
 */
class StopwordTest extends WP_UnitTestCase {

	/**
	 * Number of posts created for tests.
	 *
	 * @var int $post_count Number of posts created.
	 */
	public static $post_count;

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

		self::$post_count = 10;
		self::factory()->post->create_many( self::$post_count );
	}

	/**
	 * Test stopwords.
	 */
	public function test_stopwords() {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		$verbose = true;
		ob_start();
		$success = relevanssi_remove_all_stopwords( $verbose );
		$output  = ob_get_clean();

		$this->assertTrue( $success );
		$this->assertContains( 'All stopwords removed!', $output );

		$stopwords = relevanssi_fetch_stopwords();
		$this->assertEquals( array(), $stopwords );

		$word_content_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = %s", 'content' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// Every posts should have the word 'content'.
		$this->assertEquals( self::$post_count, $word_content_count );

		ob_start();
		$success = relevanssi_add_stopword( 'content', $verbose );
		$output  = ob_get_clean();

		// Adding the stopword should be successful.
		$this->assertTrue( $success );
		$this->assertContains( 'Term &#039;content&#039; added to stopwords!', $output );

		$stopwords = relevanssi_fetch_stopwords();
		$this->assertEquals( array( 'content' ), $stopwords );

		ob_start();
		$success = relevanssi_add_stopword( 'content', $verbose );
		$output  = ob_get_clean();
		// Adding the stopword again should not be successful.
		$this->assertFalse( $success );
		$this->assertContains( 'Couldn&#039;t add term &#039;content&#039; to stopwords!', $output );

		$this->assertFalse( relevanssi_add_stopword( '' ) );

		$word_content_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = %s", 'content' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// No posts should have the word 'content'.
		$this->assertEquals( 0, $word_content_count );

		ob_start();
		$success = relevanssi_remove_stopword( 'content', $verbose );
		$output  = ob_get_clean();

		// Removing the stopword should work.
		$this->assertTrue( $success );
		$this->assertContains( 'Term &#039;content&#039; removed from stopwords!', $output );

		ob_start();
		$success = relevanssi_remove_stopword( 'zerbox', $verbose );
		$output  = ob_get_clean();

		$this->assertFalse( $success );
		$this->assertContains( 'Couldn&#039;t remove term &#039;zerbox&#039; from stopwords!', $output );

		ob_start();
		$success = relevanssi_add_stopword( 'alpha,beta,gamma', $verbose );
		$output  = ob_get_clean();

		$this->assertTrue( $success );
		$this->assertContains( 'Successfully added 3/3 terms to stopwords!', $output );
	}

	/**
	 * Test stopword population.
	 */
	public function test_populate_stopwords() {
		add_filter( 'locale', array( $this, 'return_az' ) );
		ob_start();
		$this->assertEquals( 'no_file', relevanssi_populate_stopwords( true ) );
		$output = ob_get_clean();
		$this->assertContains( 'The stopword file for the language &#039;az&#039; doesn&#039;t exist.', $output );
		remove_filter( 'locale', array( $this, 'return_az' ) );

		ob_start();
		$response = relevanssi_populate_stopwords( true );
		$output   = ob_get_clean();

		$this->assertEquals( 'file', $response );
		$this->assertContains( 'Added stopwords from the stopword file.', $output );

		global $wpdb, $relevanssi_variables;
		$stopword_table = $relevanssi_variables['stopword_table'];
		$wpdb->insert( $stopword_table, array( 'stopword' => 'foo' ), '%s' );
		$wpdb->insert( $stopword_table, array( 'stopword' => 'bar' ), '%s' );
		$wpdb->insert( $stopword_table, array( 'stopword' => 'baz' ), '%s' );

		ob_start();
		$response = relevanssi_populate_stopwords( true );
		$output   = ob_get_clean();
		$this->assertEquals( 'database', $response );
		$this->assertContains( 'Added stopwords from the database.', $output );
	}

	/**
	 * Test relevanssi_update_stopwords_setting.
	 */
	public function test_update_stopwords_setting() {
		update_option( 'relevanssi_stopwords', 'foo,bar,baz' );
		relevanssi_update_stopwords_setting();
		$this->assertEquals( array( 'en_US' => 'foo,bar,baz' ), get_option( 'relevanssi_stopwords' ) );

		$object_setting        = new stdClass();
		$object_setting->en_US = 'foo,bar,baz'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		update_option( 'relevanssi_stopwords', $object_setting );
		relevanssi_update_stopwords_setting();
		$this->assertEquals( array( 'en_US' => 'foo,bar,baz' ), get_option( 'relevanssi_stopwords' ) );
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		require_once dirname( __DIR__ ) . '/lib/uninstall.php';
		RELEVANSSI_PREMIUM && require_once dirname( __DIR__ ) . '/premium/uninstall.php';

		function_exists( 'relevanssi_uninstall' ) && relevanssi_uninstall();
		function_exists( 'relevanssi_uninstall_free' ) && relevanssi_uninstall_free();
	}

	/**
	 * Returns 'az'.
	 */
	public static function return_az() {
		return 'az';
	}
}
