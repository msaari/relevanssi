<?php
/**
 * Class IndexingTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi indexing.
 */
class IndexingTest extends WP_UnitTestCase {
	/**
	 * Installs Relevanssi.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
	}

	/**
	 * Test indexing process.
	 *
	 * Creates new posts. Relevanssi is active and should index them automatically.
	 * Check if there is correct amount of posts in the index. Then rebuild the
	 * index and see if the total still matches.
	 */
	public function test_indexing() {
		global $wpdb, $relevanssi_variables;
		// phpcs:disable WordPress.WP.PreparedSQL

		// Truncate the index.
		relevanssi_truncate_index();

		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		$post_count       = 10;
		$post_ids         = $this->factory->post->create_many( $post_count );

		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );
		// There should be $post_count posts in the index.
		$this->assertEquals( $post_count, $distinct_docs, 'Post count should be correct.' );

		// This function should be able to count the number of posts.
		$counted_total = relevanssi_count_total_posts();
		$this->assertEquals( $post_count, $counted_total, 'relevanssi_count_total_posts() should return the correct value.' );

		// This function should find 0 missing posts.
		$missing_total = relevanssi_count_missing_posts();
		$this->assertEquals( 0, $missing_total, 'No posts should be missing.' );

		// Truncate the index.
		relevanssi_truncate_index();

		// Now there should be 0 posts in the index.
		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );
		$this->assertEquals( 0, $distinct_docs, 'There should be no posts in the database.' );

		// And $post_count posts should be missing.
		$missing_total = relevanssi_count_missing_posts();
		$this->assertEquals( $post_count, $missing_total, 'Count of missing posts should be correct.' );

		// Rebuild the index.
		relevanssi_build_index( false, false, 200, false );

		// Are the now $post_count posts in the index?
		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );
		$this->assertEquals( $post_count, $distinct_docs, 'There should be correct amount of posts in the index.' );

		// Try deleting a post from index.
		$delete_post_id = array_pop( $post_ids );
		relevanssi_remove_doc( $delete_post_id );

		$post_rows = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $relevanssi_table WHERE doc = %d", $delete_post_id ) );
		$this->assertEquals( 0, $post_rows, 'There should be zero rows for this post.' );

		return $post_ids;
	}

	/**
	 * Tests comment indexing.
	 *
	 * Creates some comments and sees if they get indexed.
	 *
	 * @depends test_indexing
	 *
	 * @param array $post_ids An array of post IDs in the index.
	 */
	public function test_comments( $post_ids ) {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		// It's necessary to hook comment indexing to 'wp_insert_comment'. It's
		// usually hooked to 'comment_post', but that doesn't trigger from the
		// factory.
		add_action( 'wp_insert_comment', 'relevanssi_index_comment' );

		// Enable comment indexing.
		update_option( 'relevanssi_index_comments', 'normal' );

		// Get a post ID and add some comments to it.
		$comment_post_id = array_pop( $post_ids );
		$comment_ids     = $this->factory->comment->create_many( 10, array( 'comment_post_ID' => $comment_post_id ) );

		$comment_rows = $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_table WHERE term='comment'" );
		$this->assertEquals( 1, $comment_rows, 'There should be one post with comments in the index.' );
	}

	/**
	 * Tests a case where same term appears in tag and category.
	 *
	 * Version 2.1.1 was broken this way.
	 *
	 * @depends test_indexing
	 *
	 * @param array $post_ids An array of post IDs in the index.
	 */
	public function test_tag_category( $post_ids ) {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		$post_id    = array_pop( $post_ids );
		$cat_ids    = array();
		$cat_ids[0] = wp_create_category( 'foo' );
		$cat_ids[1] = wp_create_category( 'bar' );
		$cat_ids[2] = wp_create_category( 'baz' );
		wp_set_post_terms( $post_id, array( 'foo', 'bar', 'baz' ), 'post_tag', true );
		wp_set_post_terms( $post_id, $cat_ids, 'category', true );

		update_option( 'relevanssi_index_taxonomies_list', array( 'post_tag', 'category' ) );

		// Rebuild the index. This shouldn't end up in error.
		relevanssi_build_index( false, false, 200, false );

		$foo_rows = $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_table WHERE term='foo'" );
		$this->assertEquals( 1, $foo_rows, 'Term "foo" should be found on one row.' );

		return $post_ids;
	}

	/**
	 * Tests tag and category indexing.
	 *
	 * This was broken in 4.0.7.
	 *
	 * @depends test_tag_category
	 *
	 * @param array $post_ids An array of post IDs in the index.
	 */
	public function test_taxonomy_indexing( $post_ids ) {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		$post_id = array_pop( $post_ids );
		wp_set_post_terms( $post_id, array( 'foobar' ), 'post_tag', true );

		update_option( 'relevanssi_index_taxonomies_list', array( 'post_tag', 'category' ) );

		// Rebuild the index. This shouldn't end up in error.
		relevanssi_build_index( false, false, 200, false );

		$foo_detail = $wpdb->get_var( "SELECT taxonomy_detail FROM $relevanssi_table WHERE term='foobar' AND doc = $post_id" );

		$foo_object = json_decode( $foo_detail );
		$this->assertNotNull( $foo_object, 'JSON needs to decode properly.' );

		$foo_count = $foo_object->post_tag;
		$this->assertEquals( 1, $foo_count, 'Post_tag value should be 1.' );

		$foo_rows = $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_table WHERE term='foobar' AND tag > 0" );
		$this->assertEquals( 1, $foo_rows, 'There should be one row with term "foobar" and "tag" > 0.' );
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
