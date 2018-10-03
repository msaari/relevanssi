<?php
/**
 * Class ExcerptTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi excerpts and highlights.
 */
class ExcerptTest extends WP_UnitTestCase {

	/**
	 * The excerpt length.
	 *
	 * @var int $excerpt_length
	 */
	public static $excerpt_length;

	/**
	 * Sets up the tests.
	 *
	 * Generates one post with couple of paragraphs of "Lorem Ipsum" as content and
	 * the word "keyword" in the end of the post.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();

		self::$excerpt_length = 30;

		update_option( 'relevanssi_excerpts', 'on' );
		update_option( 'relevanssi_excerpt_length', self::$excerpt_length );
		update_option( 'relevanssi_excerpt_type', 'words' );
		update_option( 'relevanssi_highlight', 'strong' );

		// Truncate the index.
		relevanssi_truncate_index();

		$post_id = self::factory()->post->create();

		$post_content = <<<END
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Ipsum a arcu cursus vitae congue mauris
rhoncus. Vitae suscipit tellus mauris a diam maecenas sed enim ut. At elementum eu
facilisis sed odio morbi quis commodo. Urna et pharetra pharetra massa massa
ultricies mi quis hendrerit. Sed ullamcorper morbi tincidunt ornare massa eget. At
tellus at urna condimentum mattis pellentesque id. Fermentum et sollicitudin ac orci
phasellus egestas tellus rutrum tellus. Nec tincidunt praesent semper feugiat nibh
sed pulvinar proin gravida. Id cursus metus aliquam eleifend mi. Adipiscing diam
donec adipiscing tristique risus. Vel pretium lectus quam id leo. Id nibh tortor id
aliquet lectus proin nibh nisl condimentum. Interdum posuere lorem ipsum dolor.

Purus viverra accumsan in nisl nisi scelerisque eu ultrices vitae. Nulla aliquet
enim tortor at. Massa vitae tortor condimentum lacinia. Sit amet consectetur
adipiscing elit ut aliquam purus. Amet facilisis magna etiam tempor orci eu lobortis.
Molestie a iaculis at erat pellentesque adipiscing commodo elit at. Proin libero nunc
consequat interdum varius sit. Eget nunc lobortis mattis aliquam faucibus purus in
massa. Vehicula ipsum a arcu cursus vitae congue. Accumsan lacus vel facilisis
volutpat est. Keyword ornare massa eget egestas purus viverra accumsan in nisl.
END;
		$args         = array(
			'ID'           => $post_id,
			'post_content' => $post_content,
		);

		wp_update_post( $args );
	}

	/**
	 * Test excerpts.
	 *
	 * Searches for a keyword and checks that the excerpt is as long as is required.
	 *
	 * @return string An excerpt that should have a <strong> highlight in it.
	 */
	public function test_excerpts() {
		global $wpdb, $relevanssi_variables;
		// phpcs:disable WordPress.WP.PreparedSQL

		// Search for "keyword" in posts.
		$args = array(
			's'           => 'keyword',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$post  = $posts[0];

		$words = count( explode( ' ', $post->post_excerpt ) );
		// The excerpt should be as long as we wanted it to be.
		$this->assertEquals( self::$excerpt_length, $words );

		self::$excerpt_length = 50;
		update_option( 'relevanssi_excerpt_length', self::$excerpt_length );
		$new_excerpt = relevanssi_do_excerpt( $post, 'keyword' );

		$words = count( explode( ' ', $new_excerpt ) );
		// The excerpt should still be as long as we wanted it to be.
		$this->assertEquals( self::$excerpt_length, $words );

		return $post->post_excerpt;
	}

	/**
	 * Test ellipsis.
	 */
	public function test_ellipsis() {
		global $wpdb, $relevanssi_variables;
		// phpcs:disable WordPress.WP.PreparedSQL

		// Search for "Lorem ipsum" in posts.
		$args = array(
			's'           => 'Lorem ipsum',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$post  = $posts[0];

		// The search terms are from the beginning, so the excerpt shouldn't start
		// with an ellipsis.
		$excerpt_first_three_letters = substr( $post->post_excerpt, 0, 3 );
		$this->assertNotEquals( '...', $excerpt_first_three_letters, 'Excerpt shouldn\'t start with an ellipsis' );

		// It should end with one, though.
		$excerpt_last_three_letters = substr( $post->post_excerpt, -3, 3 );
		$this->assertEquals( '...', $excerpt_last_three_letters, 'Excerpt should end with an ellipsis.' );

		// Search for "keyword" in posts.
		$args = array(
			's'           => 'keyword',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$post  = $posts[0];

		// Now the excerpt should start/ with an ellipsis.
		$excerpt_first_three_letters = substr( $post->post_excerpt, 0, 3 );
		$this->assertEquals( '...', $excerpt_first_three_letters, 'Excerpt should start with an ellipsis.' );
	}

	/**
	 * Tests whether highlighting works.
	 *
	 * @depends test_excerpts
	 *
	 * @param string $excerpt Excerpt that should have a <strong> highlight in it.
	 */
	public function test_highlighting( string $excerpt ) {
		$highlight_location = strpos( $excerpt, '<strong>' );
		// There should be some highlighting.
		$this->assertNotFalse( $highlight_location );
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
