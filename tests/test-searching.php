<?php
/**
 * Class SearchingTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi searching.
 */
class SearchingTest extends WP_UnitTestCase {
	/**
	 * Number of posts generated.
	 *
	 * @var int self::$post_count
	 */
	public static $post_count;

	/**
	 * Number of users generated.
	 *
	 * @var int $this->user_count
	 */
	public static $user_count;

	/**
	 * Number of posts with visible custom fields.
	 *
	 * @var int $visible
	 */
	public static $visible;

	/**
	 * Number of posts that should get an AND match.
	 *
	 * @var int $and_matches
	 */
	public static $and_matches;

	/**
	 * Number of posts that have taxonomy terms.
	 *
	 * @var int $taxonomy_matches
	 */
	public static $taxonomy_matches;

	/**
	 * Sets up the index.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();

		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		// Truncate the index.
		relevanssi_truncate_index();

		// Set up some Relevanssi settings so we know what to expect.
		update_option( 'relevanssi_index_fields', 'visible' );
		update_option( 'relevanssi_index_users', 'on' );
		update_option( 'relevanssi_index_subscribers', 'on' );
		update_option( 'relevanssi_index_author', 'on' );
		update_option( 'relevanssi_implicit_operator', 'AND' );
		update_option( 'relevanssi_fuzzy', 'sometimes' );
		update_option( 'relevanssi_log_queries', 'on' );
		update_option( 'relevanssi_index_taxonomies_list', array( 'post_tag', 'category' ) );

		$cat_ids    = array();
		$cat_ids[0] = wp_create_category( 'cat_foo_cat' );
		$cat_ids[1] = wp_create_category( 'cat_bar_cat' );
		$cat_ids[2] = wp_create_category( 'cat_baz_cat' );

		self::$post_count = 10;
		$post_ids         = self::factory()->post->create_many( self::$post_count );

		self::$user_count = 10;
		$user_ids         = self::factory()->user->create_many( self::$user_count );

		$post_author_id = $user_ids[0];

		$counter                = 0;
		self::$visible          = 0;
		self::$and_matches      = 0;
		self::$taxonomy_matches = 0;
		foreach ( $post_ids as $id ) {
			if ( $counter < 1 ) {
				// Make one post contain a test phrase.
				$post_content = "words Mikko's test phrase content";
				$args         = array(
					'ID'           => $id,
					'post_content' => $post_content,
				);
				wp_update_post( $args );
			}
			// Make three posts have the phrase 'displayname user' in post content.
			if ( $counter < 3 ) {
				$post     = get_post( $id );
				$content  = $post->post_content;
				$content .= ' displayname user';
				$args     = array(
					'ID'           => $id,
					'post_content' => $content,
				);
				wp_update_post( $args );
			}
			// Make five posts have the word 'buzzword' in a visible custom field and
			// rest of the posts have it in an invisible custom field. Five posts will
			// also get tags and categories 'foo', 'bar', and 'baz'.
			if ( $counter < 5 ) {
				update_post_meta( $id, '_invisible', 'buzzword' );
				update_post_meta( $id, 'keywords', 'cat dog' );
				wp_set_post_terms( $id, array( 'foo', 'bar', 'baz' ), 'post_tag', true );
				wp_set_post_terms( $id, $cat_ids, 'category', true );
				self::$and_matches++;
				self::$taxonomy_matches++;
			} else {
				update_post_meta( $id, 'visible', 'buzzword' );
				self::$visible++;
				update_post_meta( $id, 'keywords', 'cat' );
			}

			$title = substr( md5( rand() ), 0, 7 );

			// Set the post author and title.
			$args = array(
				'ID'          => $id,
				'post_author' => $post_author_id,
				'post_title'  => $title,
			);
			wp_update_post( $args );

			$counter++;
		}

		// Create two pages, make one parent of the other.
		$page_ids  = self::factory()->post->create_many( 2, array( 'post_type' => 'page' ) );
		$child_id  = array_pop( $page_ids );
		$parent_id = array_pop( $page_ids );

		$args = array(
			'ID'          => $child_id,
			'post_parent' => $parent_id,
		);
		wp_update_post( $args );

		// Name the post author 'displayname user'.
		$args = array(
			'ID'           => $post_author_id,
			'display_name' => 'displayname user',
			'description'  => 'displayname displayname displayname displayname displayname',
		);
		wp_update_user( $args );

		// Rebuild the index.
		relevanssi_build_index( false, false, 200, false );
	}

	/**
	 * Test searching process.
	 *
	 * Creates some posts, tries to find them.
	 */
	public function test_searching() {
		global $wpdb, $relevanssi_variables;
		$relevanssi_table = $relevanssi_variables['relevanssi_table'];
		$relevanssi_log   = $relevanssi_variables['log_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		// Search for "content" in posts.
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// These should both match the number of posts in the index.
		$this->assertEquals( self::$post_count, $query->found_posts );
		$this->assertEquals( self::$post_count, count( $posts ) );

		// Check that log is stored correctly.
		$hits = $wpdb->get_var( $wpdb->prepare( "SELECT hits FROM $relevanssi_log WHERE query = %s", 'content' ) );

		// The log should show self::$post_count hits.
		$this->assertEquals( self::$post_count, $hits );
	}

	/**
	 * Tests partial matching.
	 *
	 * Tries fuzzy searching, then disables that.
	 */
	public function test_partial_matching() {
		// Search for "conte" in posts. With the fuzzy search set to "sometimes",
		// this should find all posts.
		$args = array(
			's'           => 'conte',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all the posts.
		$this->assertEquals( self::$post_count, $query->found_posts );

		update_option( 'relevanssi_fuzzy', 'never' );
		// Search for "conte" in posts. With the fuzzy search set to "never",
		// this should find nothing.
		$args = array(
			's'           => 'conte',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find nothing.
		$this->assertEquals( 0, $query->found_posts );
	}

	/**
	 * Tests custom field searching.
	 *
	 * Should find content that is in a visible custom field.
	 */
	public function test_custom_fields() {
		// Search for "buzzword" to match custom fields.
		$args = array(
			's'           => 'buzzword',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should match the posts with a visible custom field.
		$this->assertEquals( self::$visible, $query->found_posts );
	}

	/**
	 * Tests user search.
	 *
	 * Should find user profiles.
	 */
	public function test_user_search() {
		if ( RELEVANSSI_PREMIUM ) {
			// Search for "user" to find users.
			$args = array(
				's'           => 'user',
				'post_type'   => 'user',
				'numberposts' => -1,
				'post_status' => 'publish',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			// This should match the number of users.
			$this->assertEquals( self::$user_count, $query->found_posts );
		}
	}

	/**
	 * Tests author search.
	 *
	 * Should find posts by the author name.
	 */
	public function test_author_search() {
		// Search for "displayname" to find authors.
		$args = array(
			's'           => 'displayname',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all posts.
		$this->assertEquals( self::$post_count, $query->found_posts );
	}

	/**
	 * Tests AND and OR search.
	 *
	 * The operator default is AND. Test that, then switch to OR and see if
	 * the results still make sense.
	 */
	public function test_operators() {
		// Search for "cat dog" with AND enabled.
		$args = array(
			's'           => 'cat dog',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find the posts with both words.
		$this->assertEquals( self::$and_matches, $query->found_posts );

		if ( RELEVANSSI_PREMIUM ) {
			// Search for "cat dog" with the OR operator.
			$args = array(
				's'           => 'cat dog',
				'post_type'   => 'post',
				'numberposts' => -1,
				'post_status' => 'publish',
				'operator'    => 'OR',
			);

			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			// This should find all posts.
			$this->assertEquals( self::$post_count, $query->found_posts );
		}
	}

	/**
	 * Tests sorting.
	 *
	 * Fetches posts with two layers of sorting: first by relevance, then by post
	 * title. All posts are equally relevant, so the order should be by title.
	 */
	public function test_sorting() {
		// Search for "content" and get some alphabetical ordering. Check the
		// two-level sorting at the same time; all posts should be equally good for
		// relevance for "content", so it should fall back to the alphabetical.
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
			'orderby'     => array(
				'relevance'  => 'desc',
				'post_title' => 'asc',
			),
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// Get the titles of the posts found, sort them in alphabetical order.
		$titles = array();
		foreach ( $posts as $post ) {
			$titles[] = $post->post_title;
		}
		sort( $titles );

		$first_post       = $posts[0];
		$first_post_title = $titles[0];

		// First post title should match the first title in alpha order.
		$this->assertEquals( $first_post_title, $first_post->post_title );
	}

	/**
	 * Tests post exclusion setting.
	 *
	 * Tests that post exclusion doesn't break the search, if there's a comma in the
	 * end of the setting. This was a bug in 2.1.3.
	 */
	public function test_post_exclusion() {
		// Search for "content".
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$post_ids    = get_posts( array_merge( $args, array( 'fields' => 'ids' ) ) );
		$exclude_ids = array();

		$exclude_ids[] = array_shift( $post_ids );
		$exclude_ids[] = array_shift( $post_ids );

		$excluded_posts    = count( $exclude_ids );
		$exclude_id_option = implode( ',', $exclude_ids );

		update_option( 'relevanssi_exclude_posts', $exclude_id_option );

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all the posts, except the excluded post.
		$this->assertEquals( self::$post_count - $excluded_posts, $query->found_posts );

		// Now add a comma in the end. This shouldn't break anything.
		update_option( 'relevanssi_exclude_posts', $exclude_id_option . ',' );

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all the posts, except the excluded post.
		$this->assertEquals( self::$post_count - $excluded_posts, $query->found_posts );

		// Make the setting a string. This should have no effect.
		update_option( 'relevanssi_exclude_posts', 'start' );

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find all the posts.
		$this->assertEquals( self::$post_count, $query->found_posts );
	}

	/**
	 * Test post pinning for single keyword and "pin for all".
	 */
	public function test_pinning() {
		if ( RELEVANSSI_PREMIUM ) {
			// Search for "buzzword".
			$args = array(
				's'           => 'buzzword',
				'post_type'   => 'post',
				'numberposts' => -1,
				'post_status' => 'publish',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			$buzzword_posts = array();
			foreach ( $posts as $post ) {
				array_push( $buzzword_posts, $post->ID );
			}

			$args = array(
				'post__not_in' => $buzzword_posts,
				'numberposts'  => -1,
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'fields'       => 'ids',
			);

			// These posts don't have "buzzword".
			$non_buzzword_posts = get_posts( $args );

			// Let's pin one of those for "buzzword".
			$pinned_post_id = array_shift( $non_buzzword_posts );
			update_post_meta( $pinned_post_id, '_relevanssi_pin', 'buzzword' );

			// Reindex the post.
			relevanssi_index_doc( $pinned_post_id, true, relevanssi_get_custom_fields(), true );

			// Search for "buzzword".
			$args = array(
				's'           => 'buzzword',
				'post_type'   => 'post',
				'numberposts' => -1,
				'post_status' => 'publish',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			$first_result = array_shift( $posts );

			$this->assertEquals( $pinned_post_id, $first_result->ID );

			// Then unpin.
			delete_post_meta( $pinned_post_id, '_relevanssi_pin' );
			relevanssi_index_doc( $pinned_post_id, true, relevanssi_get_custom_fields(), true );

			// Let's take another post and pin it for all keywords.
			$pinned_for_all_post_id = array_shift( $non_buzzword_posts );
			update_post_meta( $pinned_for_all_post_id, '_relevanssi_pin_for_all', 'on' );
			update_post_meta( $pinned_for_all_post_id, 'visible', 'buzzword' );
			relevanssi_index_doc( $pinned_for_all_post_id, true, relevanssi_get_custom_fields(), true );

			// Search for "buzzword".
			$args = array(
				's'           => 'buzzword',
				'post_type'   => 'post',
				'numberposts' => -1,
				'post_status' => 'publish',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			$first_result = array_shift( $posts );

			$this->assertEquals( $pinned_for_all_post_id, $first_result->ID );

			// Search for "buzzword" using "fields" set to "ids". This should find
			// the post and not get an error.
			$args = array(
				's'           => 'buzzword',
				'post_type'   => 'post',
				'numberposts' => -1,
				'post_status' => 'publish',
				'fields'      => 'ids',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			$first_result = array_shift( $posts );

			// Since we're looking for "ids", we get an ID.
			$this->assertEquals( $pinned_for_all_post_id, $first_result );

			// Search for "buzzword" using "fields" set to "id=>parent". This should
			// find the post and not get an error.
			$args = array(
				's'           => 'buzzword',
				'post_type'   => 'post',
				'numberposts' => -1,
				'post_status' => 'publish',
				'fields'      => 'id=>parent',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			$first_result = array_shift( $posts );

			$this->assertEquals( $pinned_for_all_post_id, $first_result->ID );
		}
	}

	/**
	 * Test searching for category and tag names.
	 */
	public function test_tags_categories() {
		// Search for "baz".
		$args = array(
			's'           => 'baz',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$this->assertEquals( self::$taxonomy_matches, count( $posts ), 'Tag search should find correct number of posts.' );

		// Search for "cat_bar_cat".
		$args = array(
			's'           => 'cat_bar_cat',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$this->assertEquals( self::$taxonomy_matches, count( $posts ), 'Category search should find correct number of posts.' );
	}

	/**
	 * Tests phrase search with apostrophes.
	 *
	 * Should find posts with a phrase where the phrase contains an apostrophe (this
	 * did not work in 2.1.7).
	 */
	public function test_apostrophe_phrase_search() {
		// Search for "mikko's test phrase" as a phrase.
		$args = array(
			's'           => '"Mikko\'s test phrase"',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find one post.
		$this->assertEquals( 1, $query->found_posts, "Searching for apostrophe-containing phrases isn't working." );
	}

	/**
	 * Tests post parent searching.
	 *
	 * Should find posts without a post parent. Setting the post_parent__not_in to 0
	 * did not work in 2.1.7.
	 *
	 * @group failing
	 */
	public function test_post_parent_search() {
		global $wpdb;

		$args = array(
			's'                   => 'content',
			'post_type'           => 'page',
			'numberposts'         => -1,
			'post_status'         => 'publish',
			'post_parent__not_in' => array( 0 ),
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		// This should find one post.
		$this->assertEquals( 1, $query->found_posts, 'Searching with post_parent__not_in 0 is not working.' );

		$args = array(
			's'               => 'content',
			'post_type'       => 'page',
			'numberposts'     => -1,
			'post_status'     => 'publish',
			'post_parent__in' => array( 0 ),
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find one post.
		$this->assertEquals( 1, $query->found_posts, 'Searching with post_parent__in 0 is not working.' );

		$args = array(
			's'           => 'content',
			'post_type'   => 'page',
			'numberposts' => -1,
			'post_status' => 'publish',
			'post_parent' => 0,
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );

		// This should find one post.
		$this->assertEquals( 1, $query->found_posts, 'Searching with post_parent 0 is not working.' );
	}

	/**
	 * Tests user searching with a really tight throttle.
	 *
	 * If throttle is very tight, user profiles should still be included. This didn't
	 * work properly pre 2.2.
	 */
	public function test_user_throttle() {
		if ( RELEVANSSI_PREMIUM ) {
			add_filter( 'pre_option_relevanssi_throttle_limit', function( $limit ) {
				return 2;
			} );

			$args = array(
				's'           => 'displayname user',
				'numberposts' => -1,
				'post_type'   => 'any',
			);

			$query = new WP_Query();
			$query->parse_query( $args );
			$posts = relevanssi_do_query( $query );

			$is_user_found = false;
			foreach ( $posts as $post ) {
				if ( 'user' === $post->post_type ) {
					$is_user_found = true;
					break;
				}
			}
			$this->assertTrue( $is_user_found, 'User profile should be found in search.' );
		}
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
