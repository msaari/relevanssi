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

		// Truncate the index.
		relevanssi_truncate_index();

		self::$post_count = 10;
		$post_ids         = self::factory()->post->create_many( self::$post_count );
	}

	/**
	 * Test stopwords.
	 */
	public function test_stopwords() {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		$word_content_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = %s", 'content' ) );
		// Every posts should have the word 'content'.
		$this->assertEquals( self::$post_count, $word_content_count );

		$success = relevanssi_add_stopword( 'content', false );
		// Adding the stopword should be successful.
		$this->assertTrue( $success );

		$word_content_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table WHERE term = %s", 'content' ) );
		// No posts should have the word 'content'.
		$this->assertEquals( 0, $word_content_count );

		$success = relevanssi_remove_stopword( 'content', false );
		// Removing the stopword should work.
		$this->assertTrue( $success );
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
