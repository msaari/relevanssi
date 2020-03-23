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
		relevanssi_init();
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

		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertEquals( 0, $distinct_docs, 'There should be no posts in the database.' );

		// And $post_count posts should be missing.
		$missing_total = relevanssi_count_missing_posts();
		$this->assertEquals( $post_count, $missing_total, 'Count of missing posts should be correct.' );

		// Rebuild the index.
		relevanssi_build_index( false, false, 200, false );

		// Are the now $post_count posts in the index?
		$distinct_docs = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertEquals( $post_count, $distinct_docs, 'There should be correct amount of posts in the index.' );

		// Try deleting a post from index.
		$delete_post_id = array_pop( $post_ids );
		relevanssi_remove_doc( $delete_post_id );

		$post_rows = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $relevanssi_table WHERE doc = %d", $delete_post_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
		$this->factory->comment->create_many( 10, array( 'comment_post_ID' => $comment_post_id ) );

		$comment_rows = $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_table WHERE term='comment'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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

		$foo_rows = $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_table WHERE term='foo'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertEquals( 1, $foo_rows, 'Term "foo" should be found on one row.' );

		update_option( 'relevanssi_index_taxonomies_list', array() );

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

		$foo_detail = $wpdb->get_var( "SELECT taxonomy_detail FROM $relevanssi_table WHERE term='foobar' AND doc = $post_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$foo_object = json_decode( $foo_detail );
		$this->assertNotNull( $foo_object, 'JSON needs to decode properly.' );

		$foo_count = $foo_object->post_tag;
		$this->assertEquals( 1, $foo_count, 'Post_tag value should be 1.' );

		$foo_rows = $wpdb->get_var( "SELECT COUNT(*) FROM $relevanssi_table WHERE term='foobar' AND tag > 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertEquals( 1, $foo_rows, 'There should be one row with term "foobar" and "tag" > 0.' );

		update_option( 'relevanssi_index_taxonomies_list', array() );
	}

	/**
	 * Tests relevanssi_post_type_restriction.
	 */
	public function test_post_type_restriction() {
		update_option( 'relevanssi_index_post_types', '' );
		$restriction = relevanssi_post_type_restriction();
		$this->assertEquals(
			" AND post.post_type IN ('no_post_types_chosen_so_index_no_posts') ",
			$restriction,
			"relevanssi_post_type_restriction() doesn't work with empty option."
		);

		update_option( 'relevanssi_index_post_types', array( 'post', 'page', 'bogus' ) );
		$restriction = relevanssi_post_type_restriction();
		$this->assertEquals(
			" AND post.post_type IN ('post', 'page') ",
			$restriction,
			"relevanssi_post_type_restriction() doesn't work with usual settings."
		);
	}

	/**
	 * Helper for testing relevanssi_valid_status_array.
	 */
	public static function valid_status_array_filter() {
		return array( 'publish', 'draft' );
	}

	/**
	 * Tests relevanssi_valid_status_array.
	 */
	public function test_valid_status_array() {
		add_filter( 'relevanssi_valid_status', '__return_empty_string' );
		$this->assertEquals(
			"'publish', 'draft', 'private', 'pending', 'future'",
			relevanssi_valid_status_array(),
			"relevanssi_valid_status_array() doesn't handle broken inputs correctly."
		);

		add_filter( 'relevanssi_valid_status', array( $this, 'valid_status_array_filter' ) );
		$this->assertEquals(
			"'publish','draft'",
			relevanssi_valid_status_array(),
			"relevanssi_valid_status_array() doesn't work correctly."
		);
		remove_filter( 'relevanssi_valid_status', array( $this, 'valid_status_array_filter' ) );
	}

	/**
	 * Tests relevanssi_index_taxonomy_terms.
	 */
	public function test_index_taxonomy_terms() {
		register_taxonomy(
			'genre',
			'post',
			array(
				'label'        => 'Genre',
				'rewrite'      => array( 'slug' => 'genre' ),
				'hierarchical' => false,
			)
		);

		$post_id = $this->factory->post->create();
		$data    = array();

		ob_start();
		$n = relevanssi_index_taxonomy_terms( $data, null, 'post_tag', true );
		$this->assertEquals(
			0,
			$n,
			"relevanssi_index_taxonomy_terms() doesn't handle null post parameter correctly."
		);
		$n = relevanssi_index_taxonomy_terms( $data, $post_id, '', true );
		$this->assertEquals(
			0,
			$n,
			"relevanssi_index_taxonomy_terms() doesn't handle empty taxonomy parameter correctly."
		);
		ob_end_clean();

		$cat_ids    = array();
		$cat_ids[0] = wp_create_category( 'foo' );
		$cat_ids[1] = wp_create_category( 'bar' );
		$cat_ids[2] = wp_create_category( 'baz' );
		wp_set_post_terms( $post_id, $cat_ids, 'category', true );
		wp_set_post_terms( $post_id, array( 'foo', 'bar', 'baz' ), 'post_tag', true );
		wp_set_post_terms( $post_id, array( 'scifi', 'fantasy', 'detective' ), 'genre', true );
		$tag_array['tag']                    = 1;
		$tag_array['taxonomy_detail']        = '{"post_tag":1}';
		$cat_array['category']               = 1;
		$cat_array['taxonomy_detail']        = '{"category":1}';
		$genre_array_pre['taxonomy']         = 1;
		$genre_array_pre['taxonomy_detail']  = '{"genre":1}';
		$genre_array_post['taxonomy']        = 2;
		$genre_array_post['taxonomy_detail'] = '{"genre":2}';

		ob_start();
		$n      = relevanssi_index_taxonomy_terms( $data, $post_id, 'post_tag', true );
		$output = ob_get_clean();

		$this->assertEquals(
			3,
			$n,
			"relevanssi_index_taxonomy_terms() doesn't handle tags correctly."
		);
		$this->assertEquals(
			array(
				'bar' => $tag_array,
				'baz' => $tag_array,
				'foo' => $tag_array,
			),
			$data,
			"relevanssi_index_taxonomy_terms() doesn't handle tags correctly."
		);
		$this->assertContains(
			'Taxonomy term content for post_tag: bar baz foo',
			$output,
			"relevanssi_index_taxonomy_terms() doesn't handle tags correctly."
		);

		$data = array();
		ob_start();
		$n      = relevanssi_index_taxonomy_terms( $data, $post_id, 'category', true );
		$output = ob_get_clean();

		$this->assertEquals(
			4,
			$n,
			"relevanssi_index_taxonomy_terms() doesn't handle cats correctly."
		);
		$this->assertEquals(
			array(
				'bar'           => $cat_array,
				'baz'           => $cat_array,
				'foo'           => $cat_array,
				'uncategorized' => $cat_array,
			),
			$data,
			"relevanssi_index_taxonomy_terms() doesn't handle cats correctly."
		);
		$this->assertContains(
			'Taxonomy term content for category: bar baz foo Uncategorized',
			$output,
			"relevanssi_index_taxonomy_terms() doesn't handle cats correctly."
		);

		$data = array(
			'scifi'     => $genre_array_pre,
			'fantasy'   => $genre_array_pre,
			'detective' => $genre_array_pre,
		);
		ob_start();
		$n      = relevanssi_index_taxonomy_terms( $data, $post_id, 'genre', true );
		$output = ob_get_clean();
		$this->assertEquals(
			3,
			$n,
			"relevanssi_index_taxonomy_terms() doesn't handle custom taxonomy correctly."
		);
		$this->assertEquals(
			array(
				'scifi'     => $genre_array_post,
				'fantasy'   => $genre_array_post,
				'detective' => $genre_array_post,
			),
			$data,
			"relevanssi_index_taxonomy_terms() doesn't handle custom taxonomy correctly."
		);
		$this->assertContains(
			'Taxonomy term content for genre: detective fantasy scifi',
			$output,
			"relevanssi_index_taxonomy_terms() doesn't handle custom taxonomy correctly."
		);
	}

	/**
	 * Test relevanssi_update_child_posts().
	 */
	public function test_update_child_posts() {
		$this->assertNull(
			relevanssi_update_child_posts( 'whatever', 'whatever', null ),
			"relevanssi_update_child_posts() can't handle null posts properly"
		);

		$post_id = $this->factory->post->create();
		$post    = get_post( $post_id );

		$did_nothing = array(
			'removed' => 0,
			'indexed' => 0,
		);
		$this->assertEquals(
			$did_nothing,
			relevanssi_update_child_posts( 'whatever', 'whatever', $post ),
			"relevanssi_update_child_posts() shouldn't do anything if the statuses are the same."
		);

		$this->assertEquals(
			$did_nothing,
			relevanssi_update_child_posts( 'publish', 'draft', $post ),
			"relevanssi_update_child_posts() shouldn't do anything if the statuses are both indexed."
		);

		$attachment_id = $this->factory->post->create(
			array(
				'post_type'   => 'attachment',
				'post_parent' => $post_id,
			)
		);
		$attachment    = get_post( $attachment_id );

		$this->assertEquals(
			$did_nothing,
			relevanssi_update_child_posts( 'publish', 'secret', $attachment ),
			"relevanssi_update_child_posts() shouldn't do anything if the post is an attachment."
		);

		update_option( 'relevanssi_index_post_types', array( 'post', 'page', 'attachment' ) );
		$public_to_secret = array(
			'removed' => 1,
			'indexed' => 0,
		);
		$this->assertEquals(
			$public_to_secret,
			relevanssi_update_child_posts( 'secret', 'publish', $post ),
			"relevanssi_update_child_posts() isn't switching from public to secret correctly."
		);

		$secret_to_public = array(
			'removed' => 0,
			'indexed' => 1,
		);
		$this->assertEquals(
			$secret_to_public,
			relevanssi_update_child_posts( 'publish', 'secret', $post ),
			"relevanssi_update_child_posts() isn't switching from secret to public correctly."
		);
	}

	/**
	 * Tests relevanssi_build_index().
	 */
	public function test_build_index() {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];

		$this->delete_all_posts();

		$this->factory->post->create_many( 100 );

		$return = relevanssi_build_index( false, null, 1, false );

		$this->assertEquals(
			array( false, 1 ),
			$return,
			"The return array isn't correct."
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$posts = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );
		$this->assertEquals( 1, $posts, 'Only one post should be indexed.' );

		$return = relevanssi_build_index( true, null, 2, false );

		$this->assertEquals(
			array( false, 2 ),
			$return,
			"The return array isn't correct."
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$posts = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );
		$this->assertEquals( 3, $posts, 'Only three posts should be indexed.' );

		$return = relevanssi_build_index( 3, null, 3, false );

		$this->assertEquals(
			array( false, 3 ),
			$return,
			"The return array isn't correct."
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$posts = $wpdb->get_var( "SELECT COUNT(DISTINCT(doc)) FROM $relevanssi_table" );
		$this->assertEquals( 6, $posts, 'Only six posts should be indexed.' );

		$ajax_return = relevanssi_build_index( false, null, 101, true );
		$this->assertEquals(
			array(
				'indexing_complete' => true,
				'indexed'           => 100,
			),
			$ajax_return,
			"The return array isn't correct."
		);
	}

	/**
	 * Tests relevanssi_indexing_query_args().
	 */
	public function test_indexing_query_args() {
		$return = relevanssi_indexing_query_args( false, 10 );
		$this->assertEquals(
			array(
				'limit'  => ' LIMIT 10',
				'extend' => false,
				'size'   => 10,
			),
			$return,
			"Testing relevanssi_indexing_query_args( false, 10 ) doesn't return correct value."
		);

		$return = relevanssi_indexing_query_args( true, null );
		$this->assertEquals(
			array(
				'limit'  => ' LIMIT 200',
				'extend' => true,
				'size'   => 200,
			),
			$return,
			"Testing relevanssi_indexing_query_args( true, null ) doesn't return correct value."
		);

		$return = relevanssi_indexing_query_args( 42, null );
		$this->assertEquals(
			array(
				'limit'  => ' LIMIT 200 OFFSET 42',
				'extend' => false,
				'size'   => 200,
			),
			$return,
			"Testing relevanssi_indexing_query_args( 42, null ) doesn't return correct value."
		);
	}

	/**
	 * Tests relevanssi_index_doc().
	 */
	public function test_index_doc() {
		// phpcs:disable WordPress.Security.NonceVerification

		$this->delete_all_posts();

		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		$arg = array(
			'ID'          => $post_id,
			'post_author' => 1,
		);
		wp_update_post( $arg );

		update_option( 'relevanssi_index_comments', 'all' );
		update_option( 'relevanssi_index_author', 'on' );
		update_option( 'relevanssi_index_taxonomies_list', array() );

		global $_REQUEST;
		$_REQUEST['contact-form-id'] = 1;

		$return = relevanssi_index_doc( null, true, false, true, true );
		$this->assertEquals(
			-1,
			$return,
			"relevanssi_index_doc() doesn't stop when contact-form-id is set."
		);

		unset( $_REQUEST['contact-form-id'] );

		$return = relevanssi_index_doc( null, true, false, true, true );
		$this->assertEquals(
			-1,
			$return,
			"relevanssi_index_doc() doesn't fail correctly."
		);

		global $post;
		$post = get_post( $post_id );

		update_option( 'relevanssi_min_word_length', 4 );
		ob_start();
		$return = relevanssi_index_doc( $post_id, true, false, true, true );
		$output = ob_get_clean();

		$this->assertEquals(
			7,
			$return,
			"relevanssi_index_doc() can't index a post correctly."
		);
		$this->assertContains(
			'Removed the post from the index.',
			$output,
			"relevanssi_index_doc() doesn't remove the post."
		);
		$this->assertContains(
			'Comment content',
			$output,
			"relevanssi_index_doc() doesn't index comment content."
		);
		$this->assertContains(
			'Final indexing query',
			$output,
			"relevanssi_index_doc() doesn't display the final indexing query."
		);
		update_option( 'relevanssi_min_word_length', 3 );

		if ( RELEVANSSI_PREMIUM ) {
			update_post_meta( $post_id, '_relevanssi_hide_post', 'on' );
			ob_start();
			$return = relevanssi_index_doc( $post_id, true, false, true, true );
			$output = ob_get_clean();
			$this->assertEquals(
				'hide',
				$return,
				"relevanssi_index_doc() doesn't handle _relevanssi_hide_post correctly."
			);
			$this->assertContains(
				'relevanssi_hide_post() returned true.',
				$output,
				"Output for hiding posts doesn't work."
			);
			delete_post_meta( $post_id, '_relevanssi_hide_post' );
		}

		add_filter( 'relevanssi_do_not_index', '__return_true' );

		ob_start();
		$return = relevanssi_index_doc( $post_id, true, false, true, true );
		$output = ob_get_clean();
		$this->assertEquals(
			'donotindex',
			$return,
			"relevanssi_index_doc() doesn't handle relevanssi_do_not_index correctly."
		);
		$this->assertContains(
			'relevanssi_do_not_index says exclude, because: Blocked by a filter function',
			$output,
			"Output for relevanssi_do_not_index doesn't work."
		);

		remove_filter( 'relevanssi_do_not_index', '__return_true' );

	}

	/**
	 * Tests relevanssi_index_comments().
	 */
	public function test_index_comments() {
		$this->delete_all_posts();

		update_option( 'relevanssi_index_comments', 'all' );
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );

		ob_start();
		$data   = array();
		$n      = relevanssi_index_comments( $data, $post_id, 4, true );
		$output = ob_get_clean();

		$this->assertEquals(
			array(
				'commenter' => array( 'comment' => 1 ),
				'comment'   => array( 'comment' => 1 ),
			),
			$data,
			"relevanssi_index_comments() doesn't handle tags correctly."
		);
		$this->assertEquals(
			2,
			$n,
			"relevanssi_index_comments() doesn't count correctly."
		);
		$this->assertContains(
			'Comment content',
			$output,
			"relevanssi_index_comments() doesn't debug comment content correctly."
		);
		$this->assertContains(
			'This is a comment',
			$output,
			"relevanssi_index_comments() doesn't debug comment content correctly."
		);

		update_option( 'relevanssi_index_comments', 'none' );
	}

	/**
	 * Tests relevanssi_index_author().
	 */
	public function test_index_author() {
		$this->delete_all_posts();

		$post_id = $this->factory->post->create();
		$arg     = array(
			'ID'          => $post_id,
			'post_author' => 1,
		);
		wp_update_post( $arg );

		ob_start();
		$post   = get_post( $post_id );
		$data   = array();
		$n      = relevanssi_index_author( $data, $post->post_author, 3, true );
		$output = ob_get_clean();

		$this->assertEquals(
			1,
			$n,
			"relevanssi_index_author() doesn't count correctly."
		);
		$this->assertEquals(
			array(
				'admin' => array( 'author' => 1 ),
			),
			$data,
			"relevanssi_index_author() doesn't handle tags correctly."
		);
		$this->assertContains(
			'Indexing post author as: admin',
			$output,
			"relevanssi_index_author() doesn't debug comment content correctly."
		);

		update_option( 'relevanssi_index_author', 'none' );
	}

	/**
	 * Tests relevanssi_index_custom_fields().
	 */
	public function test_index_custom_fields() {
		$this->delete_all_posts();

		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, 'visible', 'foo' );
		update_post_meta( $post_id, '_invisible', 'bar' );
		update_post_meta( $post_id, 'array_field', array( 'key' => 'value' ) );

		ob_start();
		$data   = array();
		$n      = relevanssi_index_custom_fields( $data, $post_id, 'all', 3, true );
		$output = ob_get_clean();

		$result_array = array(
			'foo'   => array( 'customfield' => 1 ),
			'bar'   => array( 'customfield' => 1 ),
			'value' => array( 'customfield' => 1 ),
		);
		if ( RELEVANSSI_PREMIUM ) {
			$result_array['foo']['customfield_detail']   = '{"visible":1}';
			$result_array['bar']['customfield_detail']   = '{"_invisible":1}';
			$result_array['value']['customfield_detail'] = '{"array_field":1}';
		}

		$this->assertEquals(
			$result_array,
			$data,
			"relevanssi_index_custom_fields() doesn't produce correct results."
		);
		$this->assertEquals(
			3,
			$n,
			"relevanssi_index_custom_fields() doesn't count correctly."
		);
		$this->assertContains(
			'_invisible',
			$output,
			"relevanssi_index_custom_fields() doesn't print right debugging info."
		);
		$this->assertContains(
			'Custom fields to index',
			$output,
			"relevanssi_index_custom_fields() doesn't print right debugging info."
		);

		ob_start();
		$data   = array();
		$n      = relevanssi_index_custom_fields( $data, $post_id, 'visible', 3, true );
		$output = ob_get_clean();

		$result_array = array(
			'foo'   => array( 'customfield' => 1 ),
			'value' => array( 'customfield' => 1 ),
		);
		if ( RELEVANSSI_PREMIUM ) {
			$result_array['foo']['customfield_detail']   = '{"visible":1}';
			$result_array['value']['customfield_detail'] = '{"array_field":1}';
		}

		$this->assertEquals(
			$result_array,
			$data,
			"relevanssi_index_custom_fields() doesn't produce correct results for visible fields."
		);
		$this->assertEquals(
			2,
			$n,
			"relevanssi_index_custom_fields() doesn't count correctly."
		);
		$this->assertNotContains(
			'_invisible',
			$output,
			"relevanssi_index_custom_fields() doesn't print right debugging info."
		);

		add_filter( 'relevanssi_index_custom_fields', '__return_false' );
		$this->assertEquals(
			0,
			relevanssi_index_custom_fields( $data, $post_id, 'visible', 3, true ),
			"relevanssi_index_custom_fields() doesn't handle broken filter correctly."
		);
		remove_filter( 'relevanssi_index_custom_fields', '__return_false' );

		$data = array();
		update_post_meta( $post_id, 'pods', array( 'post_title' => 'pods' ) );

		ob_start();
		$data   = array();
		$n      = relevanssi_index_custom_fields( $data, $post_id, array( 'pods' ), 3, true );
		$output = ob_get_clean();

		$result_array = array(
			'pods' => array( 'customfield' => 1 ),
		);
		if ( RELEVANSSI_PREMIUM ) {
			$result_array['pods']['customfield_detail'] = '{"pods":1}';
		}

		$this->assertEquals(
			$result_array,
			$data,
			"relevanssi_index_custom_fields() doesn't handle PODS fields correctly."
		);
	}

	/**
	 * Tests relevanssi_index_excerpt().
	 */
	public function test_index_excerpt() {
		$this->delete_all_posts();

		$post_id = $this->factory->post->create();
		$arg     = array(
			'ID'           => $post_id,
			'post_excerpt' => 'foo bar baz',
		);
		wp_update_post( $arg );
		$post = get_post( $post_id );

		ob_start();
		$data   = array();
		$n      = relevanssi_index_excerpt( $data, $post->post_excerpt, 3, true );
		$output = ob_get_clean();

		$result_array = array(
			'foo' => array( 'excerpt' => 1 ),
			'bar' => array( 'excerpt' => 1 ),
			'baz' => array( 'excerpt' => 1 ),
		);

		$this->assertEquals(
			$result_array,
			$data,
			"relevanssi_index_excerpt() doesn't produce correct results."
		);
		$this->assertEquals(
			3,
			$n,
			"relevanssi_index_excerpt() doesn't count correctly."
		);
		$this->assertContains(
			'Indexing post excerpt: foo bar baz',
			$output,
			"relevanssi_index_excerpt() doesn't print out correct debug output"
		);
	}

	/**
	 * Tests relevanssi_index_title().
	 */
	public function test_index_title() {
		$this->delete_all_posts();

		$post_id          = $this->factory->post->create();
		$post             = get_post( $post_id );
		$post->post_title = 'foo bar baz';

		ob_start();
		$data   = array();
		$n      = relevanssi_index_title( $data, $post, 3, true );
		$output = ob_get_clean();

		$result_array = array(
			'foo' => array( 'title' => 1 ),
			'bar' => array( 'title' => 1 ),
			'baz' => array( 'title' => 1 ),
		);

		$this->assertEquals(
			$result_array,
			$data,
			"relevanssi_index_title() doesn't produce correct results."
		);
		$this->assertEquals(
			3,
			$n,
			"relevanssi_index_title() doesn't count correctly."
		);
		$this->assertContains(
			'Title, tokenized: foo bar baz',
			$output,
			"relevanssi_index_title() doesn't print out correct debug output"
		);

		add_filter( 'relevanssi_index_titles', '__return_false' );
		$this->assertEquals(
			0,
			relevanssi_index_title( $data, $post, 3, false ),
			"relevanssi_index_title() doesn't handle relevanssi_index_titles filter correctly."
		);
		remove_filter( 'relevanssi_index_titles', '__return_false' );

		$post->post_title = '';

		$this->assertEquals(
			0,
			relevanssi_index_title( $data, $post, 3, false ),
			"relevanssi_index_title() doesn't handle an empty title."
		);
	}

	/**
	 * Tests relevanssi_index_content().
	 */
	public function test_index_content() {
		$this->delete_all_posts();

		$post_id            = $this->factory->post->create();
		$post               = get_post( $post_id );
		$post->post_content = 'foo bar baz';

		ob_start();
		$data   = array();
		$n      = relevanssi_index_content( $data, $post, 3, true );
		$output = ob_get_clean();

		update_option( 'relevanssi_expand_shortcodes', 'off' );

		$result_array = array(
			'foo' => array( 'content' => 1 ),
			'bar' => array( 'content' => 1 ),
			'baz' => array( 'content' => 1 ),
		);

		$this->assertEquals(
			$result_array,
			$data,
			"relevanssi_index_content() doesn't produce correct results."
		);
		$this->assertEquals(
			3,
			$n,
			"relevanssi_index_content() doesn't count correctly."
		);
		$this->assertContains(
			'foo bar baz',
			$output,
			"relevanssi_index_content() doesn't print out correct debug output"
		);
		$this->assertNotContains(
			'testing',
			$output,
			"relevanssi_index_content() is expanding shortcodes when it shouldn't."
		);

		add_filter( 'relevanssi_index_content', '__return_false' );
		$this->assertEquals(
			0,
			relevanssi_index_content( $data, $post, 3, false ),
			"relevanssi_index_content() doesn't handle relevanssi_index_content filter correctly."
		);
		remove_filter( 'relevanssi_index_content', '__return_false' );

		$post->post_content = '';

		$this->assertEquals(
			0,
			relevanssi_index_content( $data, $post, 3, false ),
			"relevanssi_index_content() doesn't handle empty content."
		);

		update_option( 'relevanssi_expand_shortcodes', 'on' );
		add_shortcode(
			'testcode',
			function() {
				return 'testing';
			}
		);

		$post->post_content = '[testcode]';

		ob_start();
		$data   = array();
		$n      = relevanssi_index_content( $data, $post, 3, true );
		$output = ob_get_clean();

		$this->assertContains(
			'testing',
			$output,
			"relevanssi_index_content() doesn't expand shortcodes correctly."
		);

		add_filter(
			'relevanssi_content_to_index',
			function() {
				return 'filtered';
			},
			56
		);

		ob_start();
		$data   = array();
		$n      = relevanssi_index_content( $data, $post, 3, true );
		$output = ob_get_clean();

		$this->assertContains(
			'filtered',
			$output,
			"relevanssi_index_content() doesn't handle relevanssi_content_to_index correctly."
		);

		global $wp_filter;
		unset( $wp_filter['relevanssi_content_to_index']->callbacks[56] );
	}

	/**
	 * Tests relevanssi_publish().
	 */
	public function test_publish() {
		update_option( 'relevanssi_index_taxonomies_list', array() );

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'auto-draft',
				'post_status' => 'auto-draft',
			)
		);
		$this->assertEquals(
			'auto-draft',
			relevanssi_publish( $post_id, false ),
			"relevanssi_publish() doesn't recognise auto-drafts correctly."
		);

		wp_insert_post(
			array(
				'ID'          => $post_id,
				'post_title'  => 'auto-draft',
				'post_status' => 'draft',
			)
		);
		$this->assertEquals(
			2,
			relevanssi_publish( $post_id, false ),
			"relevanssi_publish() doesn't produce the correct result for valid post."
		);

		relevanssi_remove_doc( $post_id );
		wp_delete_post( $post_id );
	}

	/**
	 * Tests relevanssi_insert_edit().
	 */
	public function test_insert_edit() {
		update_option( 'relevanssi_index_taxonomies_list', array() );

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'auto-draft',
				'post_status' => 'auto-draft',
			)
		);
		$this->assertEquals(
			'auto-draft',
			relevanssi_insert_edit( $post_id ),
			"relevanssi_insert_edit() doesn't recognise auto-drafts correctly."
		);

		add_filter( 'relevanssi_indexing_restriction', 'insert_edit_test_filter' );

		wp_insert_post(
			array(
				'ID'          => $post_id,
				'post_title'  => 'auto-draft',
				'post_status' => 'draft',
			)
		);
		$this->assertEquals(
			'removed',
			relevanssi_insert_edit( $post_id ),
			"relevanssi_insert_edit() doesn't handle indexing restriction filter correctly."
		);

		remove_filter( 'relevanssi_indexing_restriction', 'insert_edit_test_filter' );

		$this->assertEquals(
			2,
			relevanssi_insert_edit( $post_id ),
			"relevanssi_insert_edit() doesn't produce the correct result for valid post."
		);

		relevanssi_remove_doc( $post_id );
		wp_delete_post( $post_id );
	}

	/**
	 * Tests relevanssi_generate_indexing_query().
	 */
	public function test_generate_indexing_query() {
		global $wpdb;

		$query = relevanssi_generate_indexing_query( 'publish', false, 'AND restriction=true', '' );
		$this->assertDiscardWhitespace(
			$query,
			"SELECT post.ID FROM {$wpdb->prefix}posts post
			LEFT JOIN {$wpdb->prefix}posts parent ON (post.post_parent=parent.ID)
			WHERE (post.post_status IN (publish)
			OR (post.post_status='inherit' AND(
					(parent.ID is not null AND (parent.post_status IN (publish)))
					OR (post.post_parent=0)
				)
			)) AND post.ID NOT IN (SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_relevanssi_hide_post' AND meta_value = 'on')
		 	AND restriction=true ORDER BY post.ID DESC"
		);

		$query = relevanssi_generate_indexing_query( 'publish', true, '', 'LIMIT 3' );
		$this->assertDiscardWhitespace(
			$query,
			"SELECT post.ID FROM {$wpdb->prefix}posts post
			LEFT JOIN {$wpdb->prefix}posts parent ON (post.post_parent=parent.ID)
			LEFT JOIN {$wpdb->prefix}relevanssi r ON (post.ID=r.doc)
			WHERE r.doc is null AND (post.post_status IN (publish)
			OR (post.post_status='inherit' AND(
				(parent.ID is not null AND (parent.post_status IN (publish)))
				OR (post.post_parent=0)
				)
			)) AND post.ID NOT IN (SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key='_relevanssi_hide_post' AND meta_value='on')
			ORDER BY post.ID DESC LIMIT 3"
		);

		update_option( 'relevanssi_internal_links', 'target' );

		$query = relevanssi_generate_indexing_query( 'publish', true, '', '' );
		$this->assertDiscardWhitespace(
			$query,
			"SELECT post.ID FROM {$wpdb->prefix}posts post
			LEFT JOIN {$wpdb->prefix}posts parent ON (post.post_parent=parent.ID)
			LEFT JOIN {$wpdb->prefix}relevanssi r ON (post.ID=r.doc)
			WHERE (r.doc is null OR r.doc NOT IN (SELECT DISTINCT(doc) FROM {$wpdb->prefix}relevanssi WHERE link = 0))
			AND (post.post_status IN (publish)
			OR (post.post_status='inherit' AND(
				(parent.ID is not null AND (parent.post_status IN (publish)))
				OR (post.post_parent=0)
				)
			)) AND post.ID NOT IN (SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key='_relevanssi_hide_post' AND meta_value='on')
			ORDER BY post.ID DESC"
		);

		update_option( 'relevanssi_internal_links', 'noindex' );
	}

	/**
	 * Tests relevanssi_index_comment, relevanssi_comment_edit and
	 * relevanssi_comment_update.
	 */
	public function test_relevanssi_index_comment() {
		update_option( 'relevanssi_index_comments', 'all' );
		update_option( 'relevanssi_index_taxonomies_list', array() );

		$this->assertEquals(
			'nocommentfound',
			relevanssi_index_comment( 123456 ),
			"relevanssi_index_comment doesn't return the correct response for adding a non-existing comment ID."
		);

		$post_data    = array(
			'post_title'   => 'title',
			'post_content' => 'content',
			'post_status'  => 'publish',
		);
		$parent_post  = wp_insert_post( $post_data );
		$comment_data = array(
			'comment_content' => 'comment',
			'comment_post_ID' => $parent_post,
		);
		$comment_id   = wp_insert_comment( $comment_data );

		$this->assertEquals(
			3,
			relevanssi_index_comment( $comment_id ),
			"relevanssi_index_comment doesn't return correct result for indexing a comment."
		);

		$comment_data['comment_ID']      = $comment_id;
		$comment_data['comment_content'] = 'content schmontent';

		wp_update_comment( $comment_data );

		$this->assertEquals(
			4,
			relevanssi_index_comment( $comment_id ),
			"relevanssi_index_comment doesn't return correct result for indexing an edited comment."
		);

		$comment_data['comment_approved'] = 'trash';
		wp_update_comment( $comment_data );

		$this->assertEquals(
			'donotindex',
			relevanssi_index_comment( $comment_id ),
			"relevanssi_index_comment doesn't return correct result for a trashed comment."
		);

		update_option( 'relevanssi_index_comments', 'normal' );

		$pingback_data = array(
			'comment_type'    => 'pingback',
			'comment_post_ID' => $parent_post,
		);
		$pingback_id   = wp_insert_comment( $pingback_data );

		$this->assertEquals(
			'donotindex',
			relevanssi_index_comment( $pingback_id, 'add' ),
			"relevanssi_index_comment doesn't block pingback when it should."
		);

		update_option( 'relevanssi_index_comments', 'none' );

		$this->assertEquals(
			'donotindex',
			relevanssi_index_comment( 123456, 'add' ),
			"relevanssi_index_comment doesn't return the correct response for a disabled comment indexing."
		);

		wp_delete_comment( $pingback_id );
		wp_delete_comment( $comment_id );
		wp_delete_post( $parent_post );
	}

	/**
	 * Tests the relevanssi_get_comments() function.
	 */
	public function test_get_comments() {
		update_option( 'relevanssi_index_comments', 'all' );

		$post_data    = array(
			'post_title'   => 'title',
			'post_content' => 'content',
			'post_status'  => 'publish',
		);
		$parent_post  = wp_insert_post( $post_data );
		$comment_data = array(
			'comment_content' => 'comment',
			'comment_post_ID' => $parent_post,
		);
		wp_insert_comment( $comment_data );

		$this->assertEquals(
			'  comment',
			relevanssi_get_comments( $parent_post ),
			"relevanssi_get_comments() doesn't return the correct result."
		);

		add_filter( 'relevanssi_index_comments_exclude', '__return_true' );

		$this->assertEquals(
			'',
			relevanssi_get_comments( $parent_post ),
			"relevanssi_get_comments() doesn't apply the exclusion filter correctly."
		);

		remove_filter( 'relevanssi_index_comments_exclude', '__return_true' );

		update_option( 'relevanssi_index_comments', 'none' );

		$this->assertEquals(
			'',
			relevanssi_get_comments( $parent_post ),
			"relevanssi_get_comments() doesn't return correct results for disabled comment indexing."
		);
	}

	/**
	 * Tests the relevanssi_no_image_attachments function and feature.
	 */
	public function test_no_image_attachments() {
		$this->delete_all_posts();

		$image_attachment = array(
			'post_title'     => 'cat gif',
			'post_mime_type' => 'image/gif',
			'post_type'      => 'attachment',
			'post_status'    => 'publish',
		);
		wp_insert_post( $image_attachment );

		$pdf_attachment = array(
			'post_title'     => 'cat pdf',
			'post_mime_type' => 'application/pdf',
			'post_type'      => 'attachment',
			'post_status'    => 'publish',
		);
		wp_insert_post( $pdf_attachment );

		update_option( 'relevanssi_index_post_types', array( 'attachment' ) );

		update_option( 'relevanssi_index_image_files', 'off' );
		$return = relevanssi_build_index( false, null, null, true );
		$this->assertEquals(
			1,
			$return['indexed'],
			"With image files excluded, the number of posts indexed isn't correct."
		);

		update_option( 'relevanssi_index_image_files', 'on' );
		$return = relevanssi_build_index( false, null, null, true );
		$this->assertEquals(
			2,
			$return['indexed'],
			"With image files included, the number of posts indexed isn't correct."
		);
	}

	/**
	 * Helper function that deletes all the posts in the database.
	 */
	private function delete_all_posts() {
		$post_ids = get_posts( array( 'numberposts' => -1 ) );
		foreach ( $post_ids as $post ) {
			wp_delete_post( $post->ID );
		}
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		require_once dirname( dirname( __FILE__ ) ) . '/lib/uninstall.php';
		if ( RELEVANSSI_PREMIUM ) {
			require_once dirname( dirname( __FILE__ ) ) . '/premium/uninstall.php';
		}

		if ( function_exists( 'relevanssi_uninstall' ) ) {
			relevanssi_uninstall();
		}
		if ( function_exists( 'relevanssi_uninstall_free' ) ) {
			relevanssi_uninstall_free();
		}
	}
}

/**
 * Helper function for insert_edit test.
 */
function insert_edit_test_filter() {
	return " AND post_title != 'auto-draft' ";
}
