<?php
/**
 * Class StopwordTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi stopwords.
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

		$verbose = false;
		$this->assertTrue( relevanssi_remove_all_stopwords( $verbose ) );

		$stopwords = relevanssi_fetch_stopwords();
		$this->assertEquals( array(), $stopwords );

		$word_content_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = %s", 'content' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// Every posts should have the word 'content'.
		$this->assertEquals( self::$post_count, $word_content_count );

		$success = relevanssi_add_stopword( 'content', $verbose );
		// Adding the stopword should be successful.
		$this->assertTrue( $success );

		$stopwords = relevanssi_fetch_stopwords();
		$this->assertEquals( array( 'content' ), $stopwords );

		$success = relevanssi_add_stopword( 'content', $verbose );
		// Adding the stopword again should not be successful.
		$this->assertFalse( $success );

		$word_content_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = %s", 'content' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// No posts should have the word 'content'.
		$this->assertEquals( 0, $word_content_count );

		$success = relevanssi_remove_stopword( 'content', $verbose );
		// Removing the stopword should work.
		$this->assertTrue( $success );
	}

	/**
	 * Test stopword population.
	 */
	public function test_populate_stopwords() {
		add_filter( 'locale', 'return_az' );
		$this->assertEquals( 'no_file', relevanssi_populate_stopwords( false ) );
		remove_filter( 'locale', 'return_az' );

		$this->assertEquals( 'file', relevanssi_populate_stopwords( false ) );

		global $wpdb, $relevanssi_variables;
		$stopword_table = $relevanssi_variables['stopword_table'];
		$wpdb->insert( $stopword_table, array( 'stopword' => 'foo' ), '%s' );
		$wpdb->insert( $stopword_table, array( 'stopword' => 'bar' ), '%s' );
		$wpdb->insert( $stopword_table, array( 'stopword' => 'baz' ), '%s' );

		$this->assertEquals( 'database', relevanssi_populate_stopwords( false ) );
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		require_once dirname( dirname( __FILE__ ) ) . '/lib/uninstall.php';
		RELEVANSSI_PREMIUM && require_once dirname( dirname( __FILE__ ) ) . '/premium/uninstall.php';

		function_exists( 'relevanssi_uninstall' ) && relevanssi_uninstall();
		function_exists( 'relevanssi_uninstall_free' ) && relevanssi_uninstall_free();
	}
}

/**
 * Returns 'az'.
 */
function return_az() {
	return 'az';
}
