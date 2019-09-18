<?php
/**
 * Class SearchingTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi searching.
 *
 * @group searching
 */
class SearchingTest extends WP_UnitTestCase {
	/**
	 * Number of posts generated.
	 *
	 * @var int self::$post_count
	 */
	public static $post_count;

	/**
	 * IDs of the posts generated.
	 *
	 * @var array self::$post_ids
	 */
	public static $post_ids;

	/**
	 * IDs of the categories generated.
	 *
	 * @var array self::$cat_ids
	 */
	public static $cat_ids;

	/**
	 * IDs of the tags generated.
	 *
	 * @var array self::$tag_ids
	 */
	public static $tag_ids;

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
	 * The main author ID for the posts.
	 *
	 * @var int $post_author_id
	 */
	public static $post_author_id;

	/**
	 * The secondary author ID for the posts.
	 *
	 * @var int $other_author_id
	 */
	public static $other_author_id;

	/**
	 * Sets up the index.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();

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
		update_option( 'relevanssi_show_matches', 'on' );
		update_option( 'relevanssi_hilite_title', 'off' );
		update_option( 'relevanssi_excerpts', false );
		update_option(
			'relevanssi_post_type_weights',
			array(
				'post'                      => 1,
				'post_tagged_with_category' => 1,
				'post_tagged_with_post_tag' => 1,
				'taxonomy_term_category'    => 1,
				'taxonomy_term_post_tag'    => 1,
			)
		);
		update_option( 'relevanssi_disable_or_fallback', 'on' );

		$cat_ids       = array();
		$cat_ids[0]    = wp_create_category( 'cat_foo_cat' );
		$cat_ids[1]    = wp_create_category( 'cat_bar_cat' );
		$cat_ids[2]    = wp_create_category( 'cat_baz_cat' );
		self::$cat_ids = $cat_ids;

		self::$post_count = 10;
		$post_ids         = self::factory()->post->create_many( self::$post_count );
		self::$post_ids   = $post_ids;

		self::$user_count = 10;
		$user_ids         = self::factory()->user->create_many( self::$user_count );

		self::$post_author_id  = $user_ids[0];
		self::$other_author_id = $user_ids[1];

		$counter                = 0;
		self::$visible          = 0;
		self::$and_matches      = 0;
		self::$taxonomy_matches = 0;
		foreach ( $post_ids as $id ) {
			if ( 0 === $counter ) {
				// Make one post contain a test phrase.
				$post_content = "words Mikko's test phrase content";
				$args         = array(
					'ID'           => $id,
					'post_content' => $post_content,
				);
				wp_update_post( $args );
			}
			if ( 1 === $counter ) {
				// Make another post contain a different test phrase.
				$post_content = "words Mikko's faktojen maailma content";
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

			$tags          = get_terms( 'post_tag', array( 'hide_empty' => false ) );
			self::$tag_ids = array_map(
				function ( $tag ) {
						return $tag->term_id;
				},
				$tags
			);

			$title = substr( md5( wp_rand() ), 0, 7 );

			$post_date = date( 'Y-m-d', time() - ( $counter * MONTH_IN_SECONDS ) );

			$author_id = self::$post_author_id;
			if ( $counter < 1 ) {
				$author_id = self::$other_author_id;
			}

			// Set the post author and title.
			$args = array(
				'ID'          => $id,
				'post_author' => $author_id,
				'post_title'  => $title,
				'post_date'   => $post_date,
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
			'ID'           => self::$post_author_id,
			'display_name' => 'displayname user',
			'description'  => 'displayname displayname displayname displayname displayname',
		);
		wp_update_user( $args );

		$user_object = get_user_by( 'ID', self::$post_author_id );
		$user_object->set_role( 'editor' );

		$user_object = get_user_by( 'ID', self::$other_author_id );
		$user_object->set_role( 'author' );

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
		$relevanssi_log = $relevanssi_variables['log_table'];
		// phpcs:disable WordPress.WP.PreparedSQL

		// Search for "content" in posts.
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		// Set this to 0 to see if it gets counted correctly.
		update_option( 'relevanssi_doc_count', 0 );

		list( 'query' => $query, 'posts' => $posts ) = self::results_from_args( $args );

		// These should both match the number of posts in the index.
		$this->assertEquals( self::$post_count, $query->found_posts );
		$this->assertEquals( self::$post_count, count( $posts ) );

		// Check that log is stored correctly.
		$hits = $wpdb->get_var( $wpdb->prepare( "SELECT hits FROM $relevanssi_log WHERE query = %s", 'content' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

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

		$query = self::results_from_args( $args )['query'];
		// This should find all the posts.
		$this->assertEquals( self::$post_count, $query->found_posts );

		update_option( 'relevanssi_fuzzy', 'always' );
		// Search for "conte" in posts. With the fuzzy search set to "always",
		// this should find all posts.
		$args = array(
			's'           => 'conte',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = self::results_from_args( $args )['query'];
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

		$query = self::results_from_args( $args )['query'];
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

		$query = self::results_from_args( $args )['query'];
		// This should match the posts with a visible custom field.
		$this->assertEquals( self::$visible, $query->found_posts );
	}

	/**
	 * Tests author search.
	 *
	 * Should find posts by the author name.
	 */
	public function test_author_name_search() {
		// Search for "displayname" to find authors.
		$args = array(
			's'           => 'displayname',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = self::results_from_args( $args )['query'];
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
		update_option( 'relevanssi_implicit_operator', 'AND' );

		// Search for "cat dog" with AND enabled.
		$args = array(
			's'           => 'cat dog',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = self::results_from_args( $args )['query'];
		// This should find the posts with both words.
		$this->assertEquals( self::$and_matches, $query->found_posts );

		update_option( 'relevanssi_throttle', 'on' );
		add_filter(
			'pre_option_relevanssi_throttle_limit',
			function( $limit ) {
				return 2;
			}
		);

		// Search for "cat dog" with AND enabled.
		$args = array(
			's'           => 'content user displayname',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = self::results_from_args( $args )['query'];
		// This should find the posts with both words.
		$this->assertEquals( 2, $query->found_posts );
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

		$posts = self::results_from_args( $args )['posts'];

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

		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
			'orderby'     => 'post_title',
			'order'       => 'chaotic', // Should fix this to 'desc'.
		);

		$posts = self::results_from_args( $args )['posts'];

		// Get the titles of the posts found, sort them in alphabetical order.
		$titles = array();
		foreach ( $posts as $post ) {
			$titles[] = $post->post_title;
		}
		rsort( $titles );

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

		$query = self::results_from_args( $args )['query'];
		// This should find all the posts, except the excluded post.
		$this->assertEquals( self::$post_count - $excluded_posts, $query->found_posts );

		// Now add a comma in the end. This shouldn't break anything.
		update_option( 'relevanssi_exclude_posts', $exclude_id_option . ',' );

		$query = self::results_from_args( $args )['query'];
		// This should find all the posts, except the excluded post.
		$this->assertEquals( self::$post_count - $excluded_posts, $query->found_posts );

		// Make the setting a string. This should have no effect.
		update_option( 'relevanssi_exclude_posts', 'start' );

		$query = self::results_from_args( $args )['query'];
		// This should find all the posts.
		$this->assertEquals( self::$post_count, $query->found_posts );
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

		$posts = self::results_from_args( $args )['posts'];
		$this->assertEquals(
			self::$taxonomy_matches,
			count( $posts ),
			'Tag search should find correct number of posts.'
		);

		// Search for "cat_bar_cat".
		$args = array(
			's'           => 'cat_bar_cat',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertEquals(
			self::$taxonomy_matches,
			count( $posts ),
			'Category search should find correct number of posts.'
		);
	}

	/**
	 * Tests phrase searching.
	 *
	 * Uses both quotes for phrases and the "sentence" parameter.
	 */
	public function test_phrase_search() {

		// Search for "test phrase" as a phrase.
		$args = array(
			's'           => '"test phrase"',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = self::results_from_args( $args )['query'];
		// This should find one post.
		$this->assertEquals(
			1,
			$query->found_posts,
			"Searching for phrases isn't working."
		);

		// Curly quotes should work as well.
		$args = array(
			's'           => '“test phrase”',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = self::results_from_args( $args )['query'];
		// This should find one post.
		$this->assertEquals(
			1,
			$query->found_posts,
			"Searching for phrases with curly quotes isn't working."
		);

		// Search for "test phrase" with the "sentence" parameter.
		$args = array(
			's'           => 'test phrase',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
			'sentence'    => '1',
		);

		$query = self::results_from_args( $args )['query'];
		// This should find one post.
		$this->assertEquals(
			1,
			$query->found_posts,
			"Searching for phrases with sentence isn't working."
		);

		// AND search for two phrases should only find posts
		// with both phrases.
		$args = array(
			's'           => '"test phrase" "faktojen maailma"',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = self::results_from_args( $args )['query'];
		// This should find nothing.
		$this->assertEquals(
			0,
			$query->found_posts,
			"The AND search for phrases doesn't work as expected."
		);

		update_option( 'relevanssi_implicit_operator', 'OR' );

		// AND search for two phrases should only find posts
		// with both phrases.
		$args = array(
			's'           => '"test phrase" "faktojen maailma"',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = self::results_from_args( $args )['query'];
		// This should find two posts.
		$this->assertEquals(
			2,
			$query->found_posts,
			"The OR search for phrases doesn't work as expected."
		);

		update_option( 'relevanssi_implicit_operator', 'AND' );
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

		$query = self::results_from_args( $args )['query'];
		// This should find one post.
		$this->assertEquals(
			1,
			$query->found_posts,
			"Searching for apostrophe-containing phrases isn't working."
		);
	}

	/**
	 * Tests post parent searching.
	 *
	 * Should find posts without a post parent. Setting the post_parent__not_in to 0
	 * did not work in 2.1.7.
	 */
	public function test_post_parent_search() {
		$args = array(
			's'                   => 'content',
			'post_type'           => 'page',
			'numberposts'         => -1,
			'post_status'         => 'publish',
			'post_parent__not_in' => array( 0 ),
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			1,
			$query->found_posts,
			'Searching with post_parent__not_in 0 is not working.'
		);

		$args = array(
			's'               => 'content',
			'post_type'       => 'page',
			'numberposts'     => -1,
			'post_status'     => 'publish',
			'post_parent__in' => array( 0 ),
		);

		$query = self::results_from_args( $args )['query'];
		// This should find one post.
		$this->assertEquals(
			1,
			$query->found_posts,
			'Searching with post_parent__in 0 is not working.'
		);

		$args = array(
			's'           => 'content',
			'post_type'   => 'page',
			'numberposts' => -1,
			'post_status' => 'publish',
			'post_parent' => 0,
		);

		$query = self::results_from_args( $args )['query'];
		// This should find one post.
		$this->assertEquals(
			1,
			$query->found_posts,
			'Searching with post_parent 0 is not working.'
		);
	}

	/**
	 * Tests the by_date parameter.
	 *
	 * The test posts are generated with dates starting from current time and each
	 * post is dated one month earlier than the last.
	 */
	public function test_by_date_search() {
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'by_date'     => '1w',
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			1,
			$query->found_posts,
			'Searching with by_date 1w should find one post.'
		);
	}

	/**
	 * Tests the Date_Query parameter.
	 *
	 * The test posts are generated with dates starting from current time and each
	 * post is dated one month earlier than the last.
	 */
	public function test_date_query_search() {
		$date_query = array(
			'year' => date( 'Y' ),
			'week' => date( 'W' ),
		);

		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'date_query'  => $date_query,
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			1,
			$query->found_posts,
			'Searching with date query for posts published this week should find one post.'
		);
	}

	/**
	 * Tests the date parameters.
	 *
	 * The test posts are generated with dates starting from current time and each
	 * post is dated one month earlier than the last.
	 */
	public function test_date_parameter_search() {
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'year'        => date( 'Y' ),
			'w'           => date( 'W' ),
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			1,
			$query->found_posts,
			'Searching with date parameters for posts published this week should find one post.'
		);
	}

	/**
	 * Tests the author parameter.
	 *
	 * The test posts are generated with one post having self::$other_author_id as
	 * the author, while the rest have self::$post_author:id.
	 */
	public function test_author_parameter_search() {
		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'author'      => self::$other_author_id,
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			1,
			$query->found_posts,
			'Searching with author parameter should find one post.'
		);

		$negative_author = self::$other_author_id * -1;

		$args = array(
			's'           => 'content',
			'post_type'   => 'post',
			'numberposts' => -1,
			'author'      => "$negative_author,word",
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			9,
			$query->found_posts,
			'Searching with a negative author parameter should find one post.'
		);
	}

	/**
	 * Test searching for private posts.
	 *
	 * Checks that editors can see all private posts and that authors can see their
	 * own private posts (this didn't work before 2.3.0).
	 */
	public function test_private_search() {
		$private_count    = 4;
		$private_post_ids = self::factory()->post->create_many( $private_count );
		$author_count     = 0;

		$counter = 0;
		foreach ( $private_post_ids as $private_post_id ) {
			// Distribute the private posts between the two author profiles.
			$author_id = self::$post_author_id;
			if ( $counter > 1 ) {
				$author_id = self::$other_author_id;
				$author_count++;
			}
			$args = array(
				'ID'          => $private_post_id,
				'post_status' => 'private',
				'post_author' => $author_id,
			);
			wp_update_post( $args );
			relevanssi_index_doc( $private_post_id, false, false, true, false );
			$counter++;
		}

		wp_set_current_user( self::$post_author_id );

		$args = array(
			's'           => 'content',
			'post_status' => array( 'private' ),
			'numberposts' => -1,
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			$private_count,
			$query->found_posts,
			'Editor should see all private posts.'
		);

		wp_set_current_user( self::$other_author_id );

		$args = array(
			's'           => 'content',
			'post_status' => array( 'private' ),
			'numberposts' => -1,
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			$author_count,
			$query->found_posts,
			'Author should see their own private posts.'
		);

		foreach ( $private_post_ids as $private_post_id ) {
			wp_delete_post( $private_post_id, true );
		}
	}

	/**
	 * Test termless search.
	 */
	public function test_termless_search() {
		$args = array(
			's' => 'hereupon formerly by bill', // All stopwords.
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals( 0, $query->found_posts, 'Nothing should be found.' );

		$args = array(
			's' => 'q', // One-letter search term.
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals( 0, $query->found_posts, 'Nothing should be found.' );

		$args = array(
			's'         => '',
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'terms'    => 'baz',
					'field'    => 'slug',
					'taxonomy' => 'post_tag',
				),
			),
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals(
			self::$taxonomy_matches,
			$query->found_posts,
			'Right number of posts should be found.'
		);
	}

	/**
	 * Test 'paged' and 'offset'.
	 */
	public function test_paged_offset() {
		$args = array(
			's'              => 'content',
			'paged'          => 2,
			'posts_per_page' => 4,
		);

		list( 'posts' => $posts_1, 'query' => $query ) = self::results_from_args( $args );
		$this->assertEquals( 4, $query->post_count, 'Should return 4 posts.' );

		$args = array(
			's'              => 'content',
			'paged'          => 2,
			'posts_per_page' => 4,
			'offset'         => 1,
		);

		$posts_2 = self::results_from_args( $args )['posts'];
		$this->assertNotEquals(
			$posts_1,
			$posts_2,
			'Should return different set of posts.'
		);
		$this->assertEquals(
			$posts_1[1],
			$posts_2[0],
			'$posts_1[1] and $posts_2[0] should be the same because offset is 1.'
		);

		$posts_per_page = self::$post_count - 2;

		$args = array(
			's'              => 'content',
			'paged'          => 2,
			'posts_per_page' => $posts_per_page,
			'post_type'      => 'post',
		);

		$query = self::results_from_args( $args )['query'];
		$this->assertEquals( self::$post_count - $posts_per_page, $query->post_count );
	}

	/**
	 * Test relevanssi_query().
	 */
	public function test_relevanssi_query() {
		global $wp_the_query;

		$expected_array = array( 'untouched' );

		$actual_array = relevanssi_query( $expected_array, false );
		$this->assertEquals( $expected_array, $actual_array );

		$query            = new WP_Query();
		$query->is_search = false;
		$actual_array     = relevanssi_query( $expected_array, $query );
		$this->assertEquals( $expected_array, $actual_array );

		$query            = new WP_Query();
		$query->is_search = true;
		$query->is_admin  = true;
		$query->set( 's', '' );
		$wp_the_query = $query;
		$actual_array = relevanssi_query( $expected_array, $query );
		$this->assertEquals( $expected_array, $actual_array );

		$query            = new WP_Query();
		$query->is_search = true;
		$query->is_admin  = true;
		update_option( 'relevanssi_admin_search', 'on' );
		$query->set( 's', '' );
		$wp_the_query = $query;
		$actual_array = relevanssi_query( $expected_array, $query );
		$this->assertEquals( $expected_array, $actual_array );

		$query            = new WP_Query();
		$query->is_search = true;
		$query->set( 'post_type', 'attachment' );
		$query->set( 'post_status', 'inherit,private' );
		$query->set( 's', 'term' );
		$wp_the_query = $query;
		$actual_array = relevanssi_query( $expected_array, $query );
		$this->assertEquals( $expected_array, $actual_array );

		global $relevanssi_active;
		$relevanssi_active = true;
		$query             = new WP_Query();
		$query->is_search  = true;
		$query->set( 'post_type', 'post' );
		$query->set( 'post_status', 'publish' );
		$query->set( 's', 'term' );
		$wp_the_query = $query;
		$actual_array = relevanssi_query( $expected_array, $query );
		$this->assertEquals( $expected_array, $actual_array );
		$relevanssi_active = false;

		$query            = new WP_Query();
		$query->is_search = true;
		$query->set( 'post_type', 'post' );
		$query->set( 'post_status', 'publish' );
		$query->set( 's', 'term' );
		$wp_the_query = $query;
		$actual_array = relevanssi_query( $expected_array, $query );
		$this->assertEquals( array(), $actual_array );
	}

	/**
	 * Test killing everything with a relevanssi_match filter.
	 */
	public function test_relevanssi_match_killer() {
		add_filter(
			'relevanssi_match',
			function ( $match ) {
				$match->weight = 0;
				return $match;
			}
		);

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
		);

		$query = self::results_from_args( $args )['query'];

		$this->assertEquals( 0, $query->post_count, 'Should find nothing.' );
	}

	/**
	 * Test doing a fallback search.
	 */
	public function test_fallback_search() {
		add_filter(
			'relevanssi_fallback',
			function ( $params ) {
				$id               = self::$post_ids[0];
				$return           = array(
					'hits'             => array( get_post( $id ) ),
					'body_matches'     => array( $id => 1 ),
					'title_matches'    => array( $id => 0 ),
					'tag_matches'      => array( $id => 0 ),
					'category_matches' => array( $id => 0 ),
					'taxonomy_matches' => array( $id => 0 ),
					'comment_matches'  => array( $id => 0 ),
					'link_matches'     => array( $id => 0 ),
					'term_hits'        => array(
						$id =>
							array(
								'content' => 1,
							),
					),
					'query'            => $params['args']['q'],
				);
				$params['return'] = $return;
				return $params;
			}
		);

		$args = array(
			's'              => 'thisfindsnoresults',
			'posts_per_page' => -1,
		);

		$query = self::results_from_args( $args )['query'];

		$this->assertEquals( 1, $query->post_count, 'Should return one post.' );
	}

	/**
	 * Test category parameters.
	 */
	public function test_category_parameters() {
		$cat_ids  = self::$cat_ids;
		$category = get_term_by( 'id', $cat_ids[0], 'category' );

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'cats'           => $category->term_id,
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::all_posts_have_category( $posts, $category->term_id ),
			'There should only be posts from the right category.'
		);

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'category_name'  => $category->slug,
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::all_posts_have_category( $posts, $category->term_id ),
			'There should only be posts from the right category.'
		);

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'category__in'   => array( $category->term_id ),
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::all_posts_have_category( $posts, $category->term_id ),
			'There should only be posts from the right category.'
		);

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'category__and'  => array( $category->term_id, $cat_ids[1] ),
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::all_posts_have_category( $posts, $category->term_id )
			&& self::all_posts_have_category( $posts, $cat_ids[1] ),
			'There should only be posts from the right category.'
		);

		$args = array(
			's'                => 'content',
			'posts_per_page'   => -1,
			'category__not_in' => array( $category->term_id ),
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::no_posts_have_category( $posts, $category->term_id ),
			'There should not be posts from the restricted category.'
		);

		update_option( 'relevanssi_excat', (string) $category->term_id );

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::no_posts_have_category( $posts, $category->term_id ),
			'There should not be posts from the restricted category.'
		);

		global $relevanssi_test_admin;
		$relevanssi_test_admin = true;

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertFalse(
			self::no_posts_have_category( $posts, $category->term_id ),
			'There should be posts from the restricted category.'
		);

		$relevanssi_test_admin = false;

		update_option( 'relevanssi_excat', '' );
	}

	/**
	 * Test tag parameters.
	 */
	public function test_tag_parameters() {
		$tag_ids = self::$tag_ids;
		$tag     = get_term_by( 'id', $tag_ids[0], 'post_tag' );

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'tags'           => $tag->term_id,
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::all_posts_have_tag( $posts, $tag->term_id ),
			'There should only be posts from the right tag.'
		);

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'tag_id'         => $tag->term_id,
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::all_posts_have_tag( $posts, $tag->term_id ),
			'There should only be posts from the right tag.'
		);

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'tag__in'        => array( $tag->term_id ),
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::all_posts_have_tag( $posts, $tag->term_id ),
			'There should only be posts from the right tag.'
		);

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'tag__and'       => array( $tag->term_id, $tag_ids[1] ),
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::all_posts_have_tag( $posts, $tag->term_id )
			&& self::all_posts_have_tag( $posts, $tag_ids[1] ),
			'There should only be posts from the right tag.'
		);

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'tag__not_in'    => array( $tag->term_id ),
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::no_posts_have_tag( $posts, $tag->term_id ),
			'There should not be posts from the restricted tag.'
		);

		update_option( 'relevanssi_extag', (string) $tag->term_id );

		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertTrue(
			self::no_posts_have_tag( $posts, $tag->term_id ),
			'There should not be posts from the restricted tag.'
		);

		update_option( 'relevanssi_extag', '' );
	}

	/**
	 * Tests EXISTS meta_query.
	 *
	 * @group meta
	 */
	public function test_exists_meta_query() {
		$args = array(
			's'              => 'content',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_invisible',
					'compare' => 'EXISTS',
				),
			),
		);

		$posts = self::results_from_args( $args )['posts'];
		$this->assertEquals( count( $posts ), 5, 'There should be five posts found with the meta query.' );
	}

	/**
	 * Checks that the search parameters are interpreted correctly.
	 */
	public function test_compile_search_args() {
		$args = array(
			's'    => 'content',
			'cats' => '1,2,3',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy' => 'category',
				'field'    => 'id',
				'terms'    => array( 1, 2, 3 ),
			),
			$search_params['tax_query'][0],
			'cats is not interpreted correctly.'
		);

		update_option( 'relevanssi_excat', '4' );
		$args = array(
			's' => 'content',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy' => 'category',
				'field'    => 'id',
				'terms'    => '4',
				'operator' => 'NOT IN',
			),
			$search_params['tax_query'][0],
			'relevanssi_excat is not interpreted correctly.'
		);
		update_option( 'relevanssi_excat', '' );

		$args = array(
			's'             => 'content',
			'category_name' => 'Felix',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'category',
				'field'            => 'slug',
				'terms'            => array( 'Felix' ),
				'operator'         => 'IN',
				'include_children' => true,
			),
			$search_params['tax_query'][0],
			'category_name is not interpreted correctly.'
		);

		$args = array(
			's'            => 'content',
			'category__in' => array( 1, 2, 3 ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'category',
				'field'            => 'term_id',
				'terms'            => array( 1, 2, 3 ),
				'operator'         => 'IN',
				'include_children' => false,
			),
			$search_params['tax_query'][0],
			'category__in is not interpreted correctly.'
		);

		$args = array(
			's'                => 'content',
			'category__not_in' => array( 1, 2, 3 ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'category',
				'field'            => 'term_id',
				'terms'            => array( 1, 2, 3 ),
				'operator'         => 'NOT IN',
				'include_children' => false,
			),
			$search_params['tax_query'][0],
			'category__not_in is not interpreted correctly.'
		);

		$args = array(
			's'             => 'content',
			'category__and' => array( 1, 2, 3 ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'category',
				'field'            => 'term_id',
				'terms'            => array( 1, 2, 3 ),
				'operator'         => 'AND',
				'include_children' => false,
			),
			$search_params['tax_query'][0],
			'category__and is not interpreted correctly.'
		);

		$args = array(
			's'    => 'content',
			'tags' => '1,2',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'id',
				'terms'    => array( 1, 2 ),
				'operator' => 'OR',
			),
			$search_params['tax_query'][0],
			'tags is not interpreted correctly.'
		);

		$args = array(
			's'    => 'content',
			'tags' => '1+2',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'id',
				'terms'    => array( 1, 2 ),
				'operator' => 'AND',
			),
			$search_params['tax_query'][0],
			'tags is not interpreted correctly.'
		);

		update_option( 'relevanssi_extag', '1' );
		$args = array(
			's' => 'content',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'id',
				'terms'    => '1',
				'operator' => 'NOT IN',
			),
			$search_params['tax_query'][0],
			'relevanssi_extag is not interpreted correctly.'
		);
		update_option( 'relevanssi_extag', '' );

		$args = array(
			's'      => 'content',
			'tag_id' => 42,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'post_tag',
				'field'            => 'term_id',
				'terms'            => array( 42 ),
				'include_children' => true,
				'operator'         => 'IN',
			),
			$search_params['tax_query'][0],
			'tag_id is not interpreted correctly.'
		);

		$args = array(
			's'       => 'content',
			'tag__in' => 42,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'post_tag',
				'field'            => 'term_id',
				'terms'            => array( 42 ),
				'include_children' => true,
				'operator'         => 'IN',
			),
			$search_params['tax_query'][0],
			'tag__in is not interpreted correctly.'
		);

		$args = array(
			's'           => 'content',
			'tag__not_in' => 42,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'post_tag',
				'field'            => 'term_id',
				'terms'            => array( 42 ),
				'include_children' => true,
				'operator'         => 'NOT IN',
			),
			$search_params['tax_query'][0],
			'tag__not_in is not interpreted correctly.'
		);

		$args = array(
			's'        => 'content',
			'tag__and' => array( 1, 2, 3 ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'post_tag',
				'field'            => 'term_id',
				'terms'            => array( 1, 2, 3 ),
				'include_children' => true,
				'operator'         => 'AND',
			),
			$search_params['tax_query'][0],
			'tag__and is not interpreted correctly.'
		);

		$args = array(
			's'            => 'content',
			'tag_slug__in' => 'chickpea',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'post_tag',
				'field'            => 'slug',
				'terms'            => array( 'chickpea' ),
				'include_children' => true,
				'operator'         => 'IN',
			),
			$search_params['tax_query'][0],
			'tag_slug__in is not interpreted correctly.'
		);

		$args = array(
			's'                => 'content',
			'tag_slug__not_in' => 'limabean',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => 'limabean',
				'operator' => 'NOT IN',
			),
			$search_params['tax_query'][0],
			'tag_slug__not_in is not interpreted correctly.'
		);

		$args = array(
			's'             => 'content',
			'tag_slug__and' => array( 'chickpea', 'kidney_bean' ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'post_tag',
				'field'            => 'slug',
				'terms'            => array( 'chickpea', 'kidney_bean' ),
				'include_children' => true,
				'operator'         => 'AND',
			),
			$search_params['tax_query'][0],
			'tag_slug__and is not interpreted correctly.'
		);

		$args = array(
			's'        => 'content',
			'taxonomy' => 'post_tag',
			'term'     => 'chickpea',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'taxonomy'         => 'post_tag',
				'field'            => 'slug',
				'terms'            => array( 'chickpea' ),
				'include_children' => true,
				'operator'         => 'IN',
			),
			$search_params['tax_query'][0],
			'taxonomy is not interpreted correctly.'
		);

		$args = array(
			's'      => 'content',
			'author' => '1,2,3',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( 1, 2, 3 ),
			$search_params['author'],
			'author is not interpreted correctly.'
		);

		$user = get_user_by( 'id', self::$post_author_id );
		$args = array(
			's'           => 'content',
			'author_name' => $user->user_nicename,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( self::$post_author_id ),
			$search_params['author'],
			'author_name is not interpreted correctly.'
		);

		$args = array(
			's' => 'content',
			'p' => 123,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( 'in' => array( 123 ) ),
			$search_params['post_query'],
			'p is not interpreted correctly.'
		);

		$args = array(
			's'       => 'content',
			'page_id' => 123,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( 'in' => array( 123 ) ),
			$search_params['post_query'],
			'page_id is not interpreted correctly.'
		);

		$args = array(
			's'        => 'content',
			'post__in' => array( 1, 2, 3 ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( 'in' => array( 1, 2, 3 ) ),
			$search_params['post_query'],
			'post__in is not interpreted correctly.'
		);

		$args = array(
			's'            => 'content',
			'post__not_in' => array( 1, 2, 3 ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( 'not in' => array( 1, 2, 3 ) ),
			$search_params['post_query'],
			'post__not_in is not interpreted correctly.'
		);

		$args = array(
			's'           => 'content',
			'post_parent' => 123,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( 'parent in' => array( 123 ) ),
			$search_params['parent_query'],
			'post_parent is not interpreted correctly.'
		);

		$args = array(
			's'               => 'content',
			'post_parent__in' => array( 1, 2, 3 ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( 'parent in' => array( 1, 2, 3 ) ),
			$search_params['parent_query'],
			'post_parent__in is not interpreted correctly.'
		);

		$args = array(
			's'                   => 'content',
			'post_parent__not_in' => array( 1, 2, 3 ),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array( 'parent not in' => array( 1, 2, 3 ) ),
			$search_params['parent_query'],
			'post_parent__not_in is not interpreted correctly.'
		);

		$args = array(
			's'                 => 'content',
			'customfield_key'   => 'customfield',
			'customfield_value' => 'meta_value',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'key'     => 'customfield',
				'value'   => 'meta_value',
				'compare' => '=',
			),
			$search_params['meta_query'][0],
			'customfield_key is not interpreted correctly.'
		);

		$args = array(
			's'            => 'content',
			'meta_key'     => 'customfield',
			'meta_value'   => 'meta_value',
			'meta_compare' => '!=',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'key'     => 'customfield',
				'value'   => 'meta_value',
				'compare' => '!=',
			),
			$search_params['meta_query'][0],
			'meta_key is not interpreted correctly.'
		);

		$args = array(
			's'              => 'content',
			'meta_key'       => 'customfield',
			'meta_value_num' => 3,
			'meta_compare'   => '!=',
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			array(
				'key'     => 'customfield',
				'value'   => 3,
				'compare' => '!=',
			),
			$search_params['meta_query'][0],
			'meta_val_num is not interpreted correctly.'
		);

		$args = array(
			's'          => 'content',
			'date_query' => array(
				'after'  => array(
					'year'  => 2019,
					'month' => 1,
					'day'   => 1,
				),
				'before' => array(
					'year'  => 2019,
					'month' => 12,
					'day'   => 31,
				),
			),
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$date_query = new WP_Date_Query(
			array(
				'after'  => array(
					'year'  => 2019,
					'month' => 1,
					'day'   => 1,
				),
				'before' => array(
					'year'  => 2019,
					'month' => 12,
					'day'   => 31,
				),
			)
		);

		$this->assertEquals(
			$date_query,
			$search_params['date_query'],
			'date_query is not interpreted correctly.'
		);

		$args = array(
			's' => 'content',
		);

		$query->parse_query( $args );
		$query->date_query = $date_query;
		$search_params     = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			$date_query,
			$search_params['date_query'],
			'date_query is not interpreted correctly.'
		);

		$args = array(
			's'        => 'content',
			'year'     => 2019,
			'monthnum' => 6,
			'day'      => 15,
			'hour'     => 12,
			'minute'   => 30,
			'second'   => 45,
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$date_query = new WP_Date_Query(
			array(
				'year'   => 2019,
				'month'  => 6,
				'day'    => 15,
				'hour'   => 12,
				'minute' => 30,
				'second' => 45,
			)
		);
		$this->assertEquals(
			$date_query,
			$search_params['date_query'],
			'date_query is not interpreted correctly.'
		);

		$args = array(
			's'    => 'content',
			'year' => 2019,
			'w'    => 45,
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$date_query = new WP_Date_Query(
			array(
				'year' => 2019,
				'week' => 45,
			)
		);
		$this->assertEquals(
			$date_query,
			$search_params['date_query'],
			'date_query is not interpreted correctly.'
		);

		$args = array(
			's' => 'content',
			'm' => 201910,
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$date_query = new WP_Date_Query(
			array(
				'year'  => 2019,
				'month' => 10,
			)
		);
		$this->assertEquals(
			$date_query,
			$search_params['date_query'],
			'date_query is not interpreted correctly.'
		);

		if ( RELEVANSSI_PREMIUM ) {
			$args = array(
				's'        => 'content',
				'operator' => 'or',
			);

			$query->parse_query( $args );
			$search_params = relevanssi_compile_search_args( $query, $args['s'] );

			$this->assertEquals(
				'OR',
				$search_params['operator'],
				'operator is not interpreted correctly.'
			);

			update_option( 'relevanssi_implicit_operator', 'AND' );
			$args = array(
				's'        => 'content',
				'operator' => 'smooth',
			);

			$query->parse_query( $args );
			$search_params = relevanssi_compile_search_args( $query, $args['s'] );

			$this->assertEquals(
				'AND',
				$search_params['operator'],
				'operator is not interpreted correctly.'
			);
		}

		$args = array(
			's'                       => 'content',
			'relevanssi_admin_search' => true,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			true,
			$search_params['admin_search'],
			'relevanssi_admin_search is not interpreted correctly.'
		);

		$args = array(
			's'                   => 'content',
			'include_attachments' => true,
		);

		$query->parse_query( $args );
		$search_params = relevanssi_compile_search_args( $query, $args['s'] );

		$this->assertEquals(
			true,
			$search_params['include_attachments'],
			'include_attachments is not interpreted correctly.'
		);

	}

	/**
	 * Returns true if no posts have the particular tag.
	 *
	 * @param array $posts An array containing post objects.
	 * @param int   $tag   A tag ID.
	 *
	 * @return boolean True, if no posts have that tag.
	 */
	private function no_posts_have_tag( $posts, $tag ) {
		return self::no_posts_have_taxonomy( $posts, $tag, 'post_tag' );
	}

	/**
	 * Returns true if all posts have the particular tag.
	 *
	 * @param array $posts An array containing post objects.
	 * @param int   $tag   A tag ID.
	 *
	 * @return boolean True, if no posts have that tag.
	 */
	private function all_posts_have_tag( $posts, $tag ) {
		return self::all_posts_have_taxonomy( $posts, $tag, 'post_tag' );
	}

	/**
	 * Returns true if no posts have the particular category.
	 *
	 * @param array $posts    An array containing post objects.
	 * @param int   $category A category ID.
	 *
	 * @return boolean True, if no posts have that category.
	 */
	private function no_posts_have_category( $posts, $category ) {
		return self::no_posts_have_taxonomy( $posts, $category, 'category' );
	}

	/**
	 * Returns true if all posts have the particular category.
	 *
	 * @param array $posts    An array containing post objects.
	 * @param int   $category A category ID.
	 *
	 * @return boolean True, if no posts have that category.
	 */
	private function all_posts_have_category( $posts, $category ) {
		return self::all_posts_have_taxonomy( $posts, $category, 'category' );
	}

	/**
	 * Returns true if no posts have the particular taxonomy term.
	 *
	 * @param array  $posts    An array containing post objects.
	 * @param int    $term_id  A term ID.
	 * @param string $taxonomy The name of the taxonomy.
	 *
	 * @return boolean True, if no posts have that term.
	 */
	private function no_posts_have_taxonomy( $posts, $term_id, $taxonomy ) {
		return ! array_reduce(
			$posts,
			function( $value, $post ) use ( $term_id, $taxonomy ) {
				return $value || has_term( $term_id, $taxonomy, $post );
			},
			false
		);
	}

	/**
	 * Returns true if all posts have the particular taxonomy term.
	 *
	 * @param array  $posts    An array containing post objects.
	 * @param int    $term_id  A term ID.
	 * @param string $taxonomy The name of the taxonomy.
	 *
	 * @return boolean True, if no posts have that term.
	 */
	private function all_posts_have_taxonomy( $posts, $term_id, $taxonomy ) {
		return array_reduce(
			$posts,
			function( $value, $post ) use ( $term_id, $taxonomy ) {
				return $value && has_term( $term_id, $taxonomy, $post );
			},
			true
		);
	}

	/**
	 * Helper function that creates a WP_Query, parses the args and runs Relevanssi.
	 *
	 * @param array $args The query arguments.
	 *
	 * @return array An array containing the posts Relevanssi found and the query.
	 */
	private function results_from_args( $args ) {
		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		return array(
			'posts' => $posts,
			'query' => $query,
		);
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
