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
		relevanssi_init();

		self::$excerpt_length = 30;

		update_option( 'relevanssi_excerpts', 'on' );
		update_option( 'relevanssi_excerpt_length', self::$excerpt_length );
		update_option( 'relevanssi_excerpt_type', 'words' );
		update_option( 'relevanssi_highlight', 'strong' );
		update_option( 'relevanssi_excerpt_custom_fields', 'on' );
		update_option( 'relevanssi_minimum_word_length', 3 );

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
sed pulvinar proin gravida. Id cursus metus' aliquam eleifend mi. Adipiscing diam
donec adipiscing tristique risu's. Vel pretium lectus quam id leo. Id nibh tortor id
aliquet lectus proin nibh nisl condimentum. Interdum posuere lorem ipsum dolor.

Purus viverra accumsan in nisl nisi scelerisque eu ultrices vitae. Nulla aliquet
enim tortor at. Massa vitae tortor condimentum lacinia. Sit amet consectetur
adipiscing elit ut aliquam purus. Amet facilisis magna etiam tempor orci eu lobortis.
Molestie a iaculis at erat pellentesque adipiscing commodo elit at. Proin libero nunc
consequat interdum varius sit. Eget nunc lobortis mattis aliquam faucibus purus in
massa. Vehicula ipsum a arcu cursus vitae congue. Accumsan lacus vel facilisis
volutpat est. Keyword ornare massa eget egestas purus viverra accumsan in nisl.
O'connell.
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

		$words = count( explode( ' ', wp_strip_all_tags( $post->post_excerpt ) ) );
		$this->assertEquals(
			self::$excerpt_length,
			$words,
			'Excerpt length is not correct.'
		);

		self::$excerpt_length = 50;
		update_option( 'relevanssi_excerpt_length', self::$excerpt_length );
		$new_excerpt = relevanssi_do_excerpt( $post, 'keyword' );

		$words = count( explode( ' ', wp_strip_all_tags( $new_excerpt ) ) );
		$this->assertEquals(
			self::$excerpt_length,
			$words,
			'Excerpt length is not correct.'
		);

		return $post->post_excerpt;
	}

	/**
	 * Test ellipsis.
	 */
	public function test_ellipsis() {
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
		$excerpt_first_three_letters = substr( wp_strip_all_tags( $post->post_excerpt ), 0, 3 );
		$this->assertNotEquals(
			'...',
			$excerpt_first_three_letters,
			'Excerpt shouldn\'t start with an ellipsis'
		);

		// It should end with one, though.
		$excerpt_last_three_letters = substr( wp_strip_all_tags( $post->post_excerpt ), -3, 3 );
		$this->assertEquals(
			'...',
			$excerpt_last_three_letters,
			'Excerpt should end with an ellipsis.'
		);

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
		$excerpt_first_three_letters = substr( wp_strip_all_tags( $post->post_excerpt ), 0, 3 );
		$this->assertEquals( '...', $excerpt_first_three_letters, 'Excerpt should start with an ellipsis.' );
	}

	/**
	 * Tests that excerpt creation and highlights work for apostrophes and for
	 * words ending in s and 's and s'.
	 */
	public function test_apostrophes() {
		$query = new WP_Query();

		$args = array(
			's'           => "o'connell",
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$post  = $posts[0];

		$word_location = stripos( $post->post_excerpt, '<strong>o&rsquo;connell</strong>' );
		$this->assertNotFalse( $word_location );

		$args = array(
			's'           => "risu's",
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$post  = $posts[0];

		$word_location = stripos( $post->post_excerpt, '<strong>risu&rsquo;s</strong>' );
		$this->assertNotFalse( $word_location );

		$args = array(
			's'           => "metus'",
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts = relevanssi_do_query( $query );
		$post  = $posts[0];

		$word_location = stripos( $post->post_excerpt, '<strong>metus&rsquo;</strong>' );
		$this->assertNotFalse( $word_location );
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
	 * Test relevanssi_the_tags().
	 */
	public function test_relevanssi_the_tags() {
		$post_id = self::factory()->post->create();
		wp_set_post_terms( $post_id, array( 'foo', 'bar', 'baz' ), 'post_tag', true );

		add_filter(
			'get_search_query',
			function() {
				return 'foo bar';
			}
		);
		$this->assertEquals(
			'Tags: <a href="http://example.org/?tag=bar" rel="tag"><strong>bar</strong></a>, <a href="http://example.org/?tag=baz" rel="tag">baz</a>, <a href="http://example.org/?tag=foo" rel="tag"><strong>foo</strong></a>',
			relevanssi_the_tags( 'Tags: ', ', ', '', false, $post_id )
		);
	}

	/**
	 * Test excerpts in admin.
	 */
	public function test_excerpts_admin() {
		// Search for "keyword" in posts.
		$args = array(
			's'           => 'keyword',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query = new WP_Query();
		$query->parse_query( $args );
		$posts         = relevanssi_do_query( $query );
		$post          = $posts[0];
		$first_excerpt = $post->post_excerpt;

		global $relevanssi_test_admin;
		$relevanssi_test_admin = true;

		// Search for "keyword" in posts.
		$args = array(
			's'           => 'keyword',
			'post_type'   => 'post',
			'numberposts' => -1,
			'post_status' => 'publish',
		);

		$query->parse_query( $args );
		$posts          = relevanssi_do_query( $query );
		$post           = $posts[0];
		$second_excerpt = $post->post_excerpt;

		$this->assertNotEquals(
			$first_excerpt,
			$second_excerpt,
			'Admin excerpt should be different from front end excerpt.'
		);

		$relevanssi_test_admin = false;
	}

	/**
	 * Test relevanssi_count_matches.
	 */
	public function test_count_matches() {
		$text = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
Amet luctus venenatis lectus magna fringilla. Volutpat maecenas volutpat
blandit aliquam etiam erat velit scelerisque in. Egestas egestas fringilla
phasellus faucibus scelerisque eleifend. Sagittis orci a scelerisque purus
semper eget duis. Nulla pharetra diam sit amet nisl suscipit. Sed adipiscing
diam donec adipiscing tristique risus nec feugiat in. Fusce ut placerat orci
nulla. Pharetra vel turpis nunc eget lorem dolor. Tristique senectus et netus
et malesuada.
EOT;

		$this->assertEquals(
			3,
			relevanssi_count_matches( array( 'fringilla', 'sagittis' ), $text ),
			"relevanssi_count_matches() isn't counting correctly"
		);

		update_option( 'relevanssi_fuzzy', 'never' );
		$this->assertEquals(
			0,
			relevanssi_count_matches( array( 'fringil', 'sagit' ), $text ),
			"relevanssi_count_matches() isn't handling partial matches correctly"
		);

		update_option( 'relevanssi_fuzzy', 'always' );
		$this->assertEquals(
			3,
			relevanssi_count_matches( array( 'fringil', 'sagit' ), $text ),
			"relevanssi_count_matches() isn't doing fuzzy matching correctly"
		);
	}

	/**
	 * Test relevanssi_extract_locations.
	 */
	public function test_extract_locations() {
		$text = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
Amet luctus venenatis lectus magna fringilla. Volutpat maecenas volutpat
blandit aliquam etiam erat velit scelerisque in. Egestas egestas fringilla
phasellus faucibus scelerisque eleifend. Sagittis orci a scelerisque purus
semper eget duis. Nulla pharetra diam sit amet nisl suscipit. Sed adipiscing
diam donec adipiscing tristique risus nec feugiat in. Fusce ut placerat orci
nulla. Pharetra vel turpis nunc eget lorem dolor. Tristique senectus et netus
et malesuada.
EOT;

		$this->assertEquals(
			array( 345, 448, 499 ),
			relevanssi_extract_locations( array( 'fringilla', 'sagittis' ), $text ),
			"relevanssi_extract_locations() isn't counting correctly"
		);

		$this->assertEquals(
			77,
			count( relevanssi_extract_locations( array( 'e' ), $text ) ),
			"relevanssi_extract_locations() isn't counting correctly"
		);

		add_filter( 'relevanssi_optimize_excerpts', '__return_true' );
		$this->assertEquals(
			11,
			count( relevanssi_extract_locations( array( 'e' ), $text ) ),
			"relevanssi_extract_locations() isn't optimizing correctly"
		);
		add_filter( 'relevanssi_optimize_excerpts', '__return_false' );
	}

	/**
	 * Test relevanssi_determine_snip_location.
	 */
	public function test_determine_snip_location() {
		$this->assertEquals(
			428,
			relevanssi_determine_snip_location( array( 345, 448, 499 ), 20 ),
			"relevanssi_determine_snip_location() isn't counting correctly"
		);

		$this->assertEquals(
			0,
			relevanssi_determine_snip_location( array(), 20 ),
			"relevanssi_determine_snip_location() isn't handling error cases correctly"
		);

		$this->assertEquals(
			0,
			relevanssi_determine_snip_location( array( 345, 448, 499 ), 449 ),
			"relevanssi_determine_snip_location() isn't counting correctly"
		);
	}

	/**
	 * Test relevanssi_extract_relevant.
	 */
	public function test_extract_relevant() {
		$text = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
Amet luctus venenatis lectus magna fringilla. Volutpat maecenas volutpat
blandit aliquam etiam erat velit scelerisque in. Egestas egestas fringilla
phasellus faucibus scelerisque eleifend. Sagittis orci a scelerisque purus
semper eget duis. Nulla pharetra diam sit amet nisl suscipit. Sed adipiscing
diam donec adipiscing tristique risus nec feugiat in. Fusce ut placerat orci
nulla. Pharetra vel turpis nunc eget lorem dolor. Tristique senectus et netus
et malesuada.
EOT;

		$excerpt_response = relevanssi_extract_relevant(
			array( 'fringilla', 'sagittis' ),
			$text,
			300,
			50
		);
		$this->assertTrue(
			strlen( $excerpt_response[0] ) <= 300,
			'relevanssi_extract_relevant() gets the excerpt length wrong'
		);
		$this->assertEquals(
			2,
			$excerpt_response[1],
			'relevanssi_extract_relevant() gets the excerpt hit count wrong'
		);
		$this->assertFalse(
			$excerpt_response[2],
			'relevanssi_extract_relevant() reports the excerpt start wrong'
		);

		$fulltext_excerpt = relevanssi_extract_relevant(
			array( 'fringilla' ),
			$text,
			1000,
			50
		);
		$this->assertEquals(
			$text,
			$fulltext_excerpt[0],
			"relevanssi_extract_relevant() doesn't generate the fulltext excerpt right"
		);

		$excerpt_response = relevanssi_extract_relevant(
			array( 'consectetur' ),
			$text,
			300,
			50
		);
		$this->assertTrue(
			strlen( $excerpt_response[0] ) <= 300,
			'relevanssi_extract_relevant() gets the excerpt length wrong'
		);
		$this->assertTrue(
			$excerpt_response[2],
			'relevanssi_extract_relevant() reports the excerpt start wrong'
		);

		$excerpt_response = relevanssi_extract_relevant(
			array( 'malesuada' ),
			$text,
			50,
			5
		);
		$this->assertTrue(
			strlen( $excerpt_response[0] ) <= 50,
			'relevanssi_extract_relevant() gets the excerpt length wrong'
		);
	}

	/**
	 * Tests relevanssi_get_custom_field_content.
	 */
	public function test_get_custom_field_content() {
		$post_id = $this->factory->post->create();

		update_post_meta( $post_id, 'visiblefield', 'test value' );
		update_post_meta( $post_id, '_invisiblefield', 'invisible' );
		update_post_meta( $post_id, 'pods_field', array( 'post_title' => 'pods' ) );
		update_post_meta( $post_id, 'array_field', array( 'random_key' => 'arrayvalue' ) );

		update_option( 'relevanssi_index_fields', 'all' );
		$this->assertNotEquals(
			false,
			strpos( relevanssi_get_custom_field_content( $post_id ), 'test value' ),
			"relevanssi_get_custom_field_content() doesn't return correct content for 'all'"
		);
		$this->assertNotEquals(
			false,
			strpos( relevanssi_get_custom_field_content( $post_id ), 'pods' ),
			"relevanssi_get_custom_field_content() doesn't return correct content for 'all'"
		);
		$this->assertNotEquals(
			false,
			strpos( relevanssi_get_custom_field_content( $post_id ), 'arrayvalue' ),
			"relevanssi_get_custom_field_content() doesn't return correct content for 'all'"
		);

		update_option( 'relevanssi_index_fields', 'visible' );
		$this->assertNotEquals(
			false,
			strpos( relevanssi_get_custom_field_content( $post_id ), 'test value' ),
			"relevanssi_get_custom_field_content() doesn't return correct content for 'visible'"
		);
		$this->assertFalse(
			strpos( relevanssi_get_custom_field_content( $post_id ), 'invisible' ),
			"relevanssi_get_custom_field_content() doesn't return correct content for 'visible'"
		);

		add_filter( 'relevanssi_custom_field_value', '__return_empty_array' );
		$this->assertEquals(
			'',
			strpos( relevanssi_get_custom_field_content( $post_id ), 'test value' ),
			"relevanssi_get_custom_field_content() doesn't return correct content for empty custom fields"
		);
	}

	/**
	 * Test relevanssi_the_excerpt.
	 */
	public function test_the_excerpt() {
		global $post;
		$post_id = $this->factory->post->create();
		$post    = get_post( $post_id );

		$post->post_excerpt = 'Lorem ipsum.';
		ob_start();
		relevanssi_the_excerpt();
		$excerpt = ob_get_clean();
		$this->assertEquals(
			'<p>Lorem ipsum.</p>',
			$excerpt,
			"relevanssi_the_excerpt() doesn't return correct results."
		);

		$post->post_password = 'Password';
		ob_start();
		relevanssi_the_excerpt();
		$excerpt = ob_get_clean();
		$this->assertEquals(
			'There is no excerpt because this is a protected post.',
			$excerpt,
			"relevanssi_the_excerpt() doesn't return correct results for password protected posts."
		);

	}

	/**
	 * Test relevanssi_remove_nested_highlights.
	 */
	public function test_remove_nested_highlights() {
		$begin    = '(';
		$end      = ')';
		$string_1 = 'Lorem ipsum (dolor sit ((amet), consectetur (adipiscing elit), sed do eiusmod) tempor incididunt) ut labore et dolore magna aliqua.';
		$string_2 = 'Lorem ipsum (dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt) ut labore et dolore magna aliqua.';
		$this->assertEquals(
			$string_2,
			relevanssi_remove_nested_highlights( $string_1, $begin, $end ),
			"relevanssi_remove_nested_highlights() doesn't strip nested highlights correctly."
		);

		$string_1 = 'Lorem ipsum (dolor sit), sed do (eiusmod incididunt) ut.';
		$string_2 = 'Lorem ipsum (dolor sit), sed do (eiusmod incididunt) ut.';
		$this->assertEquals(
			$string_2,
			relevanssi_remove_nested_highlights( $string_1, $begin, $end ),
			"relevanssi_remove_nested_highlights() doesn't handle non-nested highlights correctly."
		);

		$string_1 = 'Lorem ipsum (dolor sit), sed (eiusmod (incididunt)) ut.';
		$string_2 = 'Lorem ipsum (dolor sit), sed (eiusmod incididunt) ut.';
		$this->assertEquals(
			$string_2,
			relevanssi_remove_nested_highlights( $string_1, $begin, $end ),
			"relevanssi_remove_nested_highlights() doesn't handle non-nested highlights correctly."
		);
	}

	/**
	 * Test relevanssi_entities_inside.
	 */
	public function test_entities_inside() {
		$string_1 = 'Lorem ipsum & dolor sit <pre>amet & dolor < 13</pre>';
		$string_2 = 'Lorem ipsum & dolor sit <pre>amet &amp; dolor &lt; 13</pre>';
		$this->assertEquals(
			$string_2,
			relevanssi_entities_inside( $string_1, 'pre' ),
			"relevanssi_entities_inside() doesn't handle the entities correctly."
		);
	}

	/**
	 * Test relevanssi_fix_entities.
	 */
	public function test_fix_entities() {
		$excerpt = <<<EOT
<b>bold text</b> and some maths: x < 10 and y > 10.
EOT;

		$this->assertEquals(
			'&lt;b&gt;bold text&lt;/b&gt; and some maths: x &lt; 10 and y &gt; 10.',
			relevanssi_fix_entities( $excerpt, false ),
			"relevanssi_fix_entities() doesn't convert all entities."
		);

		update_option( 'relevanssi_excerpt_allowable_tags', '<b>' );
		$this->assertEquals(
			'<b>bold text</b> and some maths: x &lt; 10 and y &gt; 10.',
			relevanssi_fix_entities( $excerpt, false ),
			"relevanssi_fix_entities() doesn't protect allowed tags."
		);

		$this->assertEquals(
			'<b>bold text</b> and some maths: x < 10 and y > 10.',
			relevanssi_fix_entities( $excerpt, true ),
			"relevanssi_fix_entities() doesn't respect the in_docs parameter."
		);

		add_filter( 'relevanssi_entities_inside_pre', '__return_true' );
		add_filter( 'relevanssi_entities_inside_code', '__return_true' );

		$excerpt = <<<EOT
<code><b>bold text</b></code> and some maths: <pre>x < 10 and y > 10</pre>.
EOT;

		$this->assertEquals(
			'<code>&lt;b&gt;bold text&lt;/b&gt;</code> and some maths: <pre>x &lt; 10 and y &gt; 10</pre>.',
			relevanssi_fix_entities( $excerpt, true ),
			"relevanssi_fix_entities() doesn't handle code and pre with the in_docs parameter."
		);
	}

	/**
	 * Test relevanssi_highlight_terms().
	 */
	public function test_highlight_terms() {
		$content = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
temporincididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
EOT;
		$query   = array( 'massa', 'tempor' );
		update_option( 'relevanssi_highlight', 'mark' );
		update_option( 'relevanssi_fuzzy', 'never' );
		$excerpt = relevanssi_highlight_terms( $content, $query, false );

		$highlighted = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
temporincididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In <mark>massa tempor</mark> nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
EOT;

		$this->assertEquals(
			str_replace( "\n", ' ', $highlighted ),
			str_replace( "\n", ' ', $excerpt ),
			'relevanssi_highlight_term() mark test failed.'
		);

		$query = array( 'faucibus' );
		update_option( 'relevanssi_highlight', 'mark' );
		update_option( 'relevanssi_fuzzy', 'never' );
		$excerpt = relevanssi_highlight_terms( $content, $query, false );

		$highlighted = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
temporincididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum <mark>faucibus</mark> vitae aliquet nec ullamcorper.
EOT;

		$this->assertEquals(
			str_replace( "\n", ' ', $highlighted ),
			str_replace( "\n", ' ', $excerpt ),
			'relevanssi_highlight_term() mark test failed.'
		);

		$query = array( 'massa', 'tempor' );
		update_option( 'relevanssi_fuzzy', 'always' );
		$excerpt = relevanssi_highlight_terms( $content, $query, false );

		$highlighted = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
<mark>tempor</mark>incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In <mark>massa tempor</mark> nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
EOT;

		$this->assertEquals(
			str_replace( "\n", ' ', $highlighted ),
			str_replace( "\n", ' ', $excerpt ),
			'relevanssi_highlight_term() fuzzy test failed.'
		);

		update_option( 'relevanssi_fuzzy', 'never' );
		delete_option( 'relevanssi_txt_col' );
		delete_option( 'relevanssi_bg_col' );
		delete_option( 'relevanssi_css' );
		delete_option( 'relevanssi_class' );
		$types = array(
			'none'   => array(
				'begin' => '',
				'end'   => '',
			),
			'untype' => array(
				'begin' => '',
				'end'   => '',
			),
			'strong' => array(
				'begin' => '<strong>',
				'end'   => '</strong>',
			),
			'em'     => array(
				'begin' => '<em>',
				'end'   => '</em>',
			),
			'col'    => array(
				'begin' => "<span style='color: #ff0000'>",
				'end'   => '</span>',
			),
			'bgcol'  => array(
				'begin' => "<span style='background-color: #ff0000'>",
				'end'   => '</span>',
			),
			'css'    => array(
				'begin' => "<span style='color: #ff0000'>",
				'end'   => '</span>',
			),
			'class'  => array(
				'begin' => "<span class='relevanssi-query-term'>",
				'end'   => '</span>',
			),
		);

		foreach ( $types as $type => $properties ) {
			update_option( 'relevanssi_highlight', $type );
			$excerpt = relevanssi_highlight_terms( $content, $query, false );

			$highlighted = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
temporincididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In {$properties['begin']}massa tempor{$properties['end']} nec
feugiat. Nunc aliquet bibendum enim facilisis gravida. Nisl nunc mi ipsum
faucibus vitae aliquet nec ullamcorper.
EOT;

			$this->assertEquals(
				str_replace( "\n", ' ', $highlighted ),
				str_replace( "\n", ' ', $excerpt ),
				'relevanssi_highlight_term() ' . $type . ' test failed.'
			);
		}

		update_option( 'relevanssi_highlight', 'mark' );
		add_filter( 'relevanssi_allow_one_letter_highlights', '__return_false' );

		$content = 'A e i o u';
		$query   = 'i';
		$excerpt = relevanssi_highlight_terms( $content, $query, false );
		$this->assertEquals(
			$content,
			$excerpt,
			"relevanssi_highlight_term() one-letter highlights appear when they shouldn't."
		);

		remove_filter( 'relevanssi_allow_one_letter_highlights', '__return_false' );
		add_filter( 'relevanssi_allow_one_letter_highlights', '__return_true' );
		$excerpt = relevanssi_highlight_terms( $content, $query, false );
		$this->assertEquals(
			'A e <mark>i</mark> o u',
			$excerpt,
			"relevanssi_highlight_term() one-letter highlights don't appear when they should."
		);

		$content = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
tempor incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
EOT;

		$query   = '"massa tempor" feugiat';
		$excerpt = relevanssi_highlight_terms( $content, $query, false );

		$highlighted = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
tempor incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In <mark>massa tempor</mark> nec <mark>feugiat</mark>.
Nunc aliquet bibendum enim facilisis gravida. Nisl nunc mi ipsum faucibus vitae
aliquet nec ullamcorper.
EOT;

		$this->assertEquals(
			str_replace( "\n", ' ', $highlighted ),
			str_replace( "\n", ' ', $excerpt ),
			'relevanssi_highlight_term() phrase test failed.'
		);

		$content = <<<EOT
tempor incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. &tempor;
<pre>In massa tempor nec feugiat.</pre>
EOT;

		$query   = 'tempor';
		$excerpt = relevanssi_highlight_terms( $content, $query, true );

		$highlighted = <<<EOT
<mark>tempor</mark> incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. &tempor;
<pre>In massa tempor nec feugiat.</pre>
EOT;

		$this->assertEquals(
			str_replace( "\n", ' ', $highlighted ),
			str_replace( "\n", ' ', $excerpt ),
			'relevanssi_highlight_term() entity & tag test failed.'
		);
	}

	/**
	 * Test relevanssi_create_excerpt().
	 */
	public function test_create_excerpt() {
		$content = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
Amet luctus venenatis lectus magna fringilla. Volutpat maecenas volutpat
blandit aliquam etiam erat velit scelerisque in. Egestas egestas fringilla
phasellus faucibus scelerisque eleifend. Sagittis orci a scelerisque purus
semper eget duis. Nulla pharetra diam sit amet nisl suscipit. Sed adipiscing
diam donec adipiscing tristique risus nec feugiat in. Fusce ut placerat orci
nulla. Pharetra vel turpis nunc eget lorem dolor. Tristique senectus et netus
et malesuada.
EOT;
		$content = str_replace( "\n", ' ', $content );

		$query = 'sclerisque "amet nisl"';
		$terms = relevanssi_tokenize( $query, true );

		$length = 30;
		$type   = 'words';

		$response = relevanssi_create_excerpt( $content, $terms, $query, $length, $type );
		$this->assertEquals(
			30,
			count( explode( ' ', trim( $response[0] ) ) ),
			'relevanssi_create_excerpt() returns wrong word excerpt length.'
		);

		$length = 300;

		$response = relevanssi_create_excerpt( $content, $terms, $query, $length, $type );
		$this->assertEquals(
			count( explode( ' ', trim( $content ) ) ),
			count( explode( ' ', trim( $response[0] ) ) ),
			'relevanssi_create_excerpt() returns full content excerpt wrong.'
		);

		$content = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
Amet luctus venenatis lectus magna fringilla. Volutpat maecenas volutpat
blandit aliquam etiam erat velit scelerisque in. Egestas egestas fringilla
phasellus faucibus scelerisque eleifend. Sagittis orci a scelerisque purus
semper eget duis. Nulla pharetra diam sit amet nisl suscipit. Sed adipiscing
diam donec adipiscing tristique risus nec feugiat in. Fusce ut placerat orci
nulla. Pharetra vel turpis nunc eget lorem dolor. Tristique senectus et netus
et malesuada.

Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
incididunt ut labore et dolore magna aliqua. Dolor sed viverra ipsum nunc
aliquet bibendum enim. In massa tempor nec feugiat. Nunc aliquet bibendum enim
facilisis gravida. Nisl nunc mi ipsum faucibus vitae aliquet nec ullamcorper.
Amet luctus venenatis lectus magna fringilla. Volutpat maecenas volutpat
blandit aliquam etiam erat velit scelerisque in. Egestas egestas fringilla
phasellus faucibus scelerisque eleifend. Sagittis orci a scelerisque purus
semper eget duis. Nulla pharetra diam sit amet nisl suscipit. Sed adipiscing
diam donec adipiscing tristique risus nec feugiat in. Fusce ut placerat orci
nulla. Pharetra vel turpis nunc eget lorem dolor dolor dolor. Tristique senectus et netus
et malesuada.
EOT;

		$content = str_replace( "\n", ' ', $content );
		$query   = 'dolor';
		$terms   = relevanssi_tokenize( $query, true );

		$length = 3;
		$type   = 'words';
		add_filter( 'relevanssi_optimize_excerpts', '__return_false' );

		$response = relevanssi_create_excerpt( $content, $terms, $query, $length, $type );
		$this->assertEquals(
			'dolor dolor dolor.',
			trim( $response[0] ),
			'relevanssi_create_excerpt() fails when optimizing is disabled.'
		);

		remove_filter( 'relevanssi_optimize_excerpts', '__return_false' );
		add_filter( 'relevanssi_optimize_excerpts', '__return_true' );

		$response = relevanssi_create_excerpt( $content, $terms, $query, $length, $type );
		$this->assertEquals(
			'Lorem ipsum dolor',
			trim( $response[0] ),
			'relevanssi_create_excerpt() fails when optimizing is enabled.'
		);

		remove_filter( 'relevanssi_optimize_excerpts', '__return_true' );

		$query = 'nonexisting';
		$terms = relevanssi_tokenize( $query, true );

		$length = 5;

		$response = relevanssi_create_excerpt( $content, $terms, $query, $length, $type );
		$this->assertEquals(
			'Lorem ipsum dolor sit amet,',
			trim( $response[0] ),
			'relevanssi_create_excerpt() fails when search term is not found.'
		);

		$length = 100;
		$type   = 'chars';

		$response = relevanssi_create_excerpt( $content, $terms, $query, $length, $type );
		$this->assertTrue(
			strlen( trim( $response[0] ) ) <= 100,
			'relevanssi_create_excerpt() returns wrong character excerpt length.'
		);
	}

	/**
	 * Test relevanssi_do_excerpt()
	 */
	public function test_do_excerpt() {
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
END;

		$post_excerpt = <<<END
Purus viverra accumsan in nisl nisi scelerisque eu ultrices vitae. Nulla aliquet
enim tortor at. Massa vitae tortor condimentum lacinia. Sit amet consectetur
adipiscing elit ut aliquam purus. Amet facilisis magna etiam tempor orci eu lobortis.
Molestie a iaculis at erat pellentesque adipiscing commodo elit at. Proin libero nunc
consequat interdum varius sit. Eget nunc lobortis mattis aliquam faucibus purus in
massa. Vehicula ipsum a arcu cursus vitae congue. Accumsan lacus vel facilisis
volutpat est. Keyword ornare massa eget egestas purus viverra accumsan in nisl.
END;

		$args = array(
			'ID'           => $post_id,
			'post_content' => str_replace( "\n", ' ', $post_content ),
			'post_excerpt' => str_replace( "\n", ' ', $post_excerpt ),
		);
		wp_update_post( $args );
		$post = get_post( $post_id );

		$comment_content = <<<END
But I must explain to you how all this mistaken idea of denouncing pleasure and
praising pain was born and I will give you a complete account of the system,
and expound the actual teachings of the great explorer of the truth, the
master-builder of human happiness.
END;

		$args = array(
			'comment_post_ID' => $post_id,
			'comment_content' => str_replace( "\n", ' ', $comment_content ),
		);
		wp_insert_comment( $args );

		update_option( 'relevanssi_excerpt_type', 'words' );
		update_option( 'relevanssi_excerpt_length', '10' );
		$excerpt = relevanssi_do_excerpt( $post, 'quis hendrerit' );
		$this->assertEquals(
			'<span class="excerpt_part">...massa massa ultricies mi <strong>quis hendrerit</strong>. Sed ullamcorper morbi tincidunt...</span>',
			$excerpt,
			"relevanssi_do_excerpt() doesn't work correctly."
		);

		update_option( 'relevanssi_excerpt_length', '500' );
		update_option( 'relevanssi_highlight', 'none' );
		$excerpt = relevanssi_do_excerpt( $post, 'quis hendrerit' );
		$this->assertEquals(
			'<span class="excerpt_part">' . $post->post_content . '</span>',
			$excerpt,
			"relevanssi_do_excerpt() doesn't do whole post excerpts correctly."
		);

		update_option( 'relevanssi_highlight', 'strong' );
		update_option( 'relevanssi_excerpt_length', '10' );
		update_option( 'relevanssi_index_comments', 'on' );
		$excerpt = relevanssi_do_excerpt( $post, 'praising pain' );
		$this->assertEquals(
			'<span class="excerpt_part">...idea of denouncing pleasure and <strong>praising pain</strong> was born and...</span>',
			$excerpt,
			"relevanssi_do_excerpt() doesn't excerpt comment content correctly."
		);

		update_option( 'relevanssi_index_excerpt', 'on' );
		$excerpt = relevanssi_do_excerpt( $post, 'condimentum lacinia' );
		$this->assertEquals(
			'<span class="excerpt_part">...Nulla aliquet enim tortor at. Massa vitae tortor <strong>condimentum lacinia</strong>....</span>',
			$excerpt,
			"relevanssi_do_excerpt() doesn't excerpt post_excerpt correctly."
		);

		update_option( 'relevanssi_excerpt_type', 'chars' );
		update_option( 'relevanssi_index_comments', 'none' );
		update_option( 'relevanssi_index_excerpt', 'off' );
		$excerpt = relevanssi_do_excerpt( $post, 'nonexistingwords' );
		$this->assertEquals(
			'<span class="excerpt_part">Lorem ipsu...</span>',
			$excerpt,
			"relevanssi_do_excerpt() doesn't do empty excerpt correctly."
		);
	}

	/**
	 * Test relevanssi_highlight_in_docs().
	 */
	public function test_highlight_in_docs() {
		global $wp_query, $relevanssi_test_enable;
		$relevanssi_test_enable = true;

		$content = 'Lorem ipsum dolor sit amet, ipsumen consectetur adipiscing elit.';

		$wp_query->query_vars['highlight'] = 'ipsum';

		update_option( 'relevanssi_fuzzy', 'never' );
		$highlighted_content = relevanssi_highlight_in_docs( $content );
		$this->assertEquals(
			'Lorem <strong>ipsum</strong> dolor sit amet, ipsumen consectetur adipiscing elit.',
			$highlighted_content,
			"relevanssi_highlight_in_docs() doesn't highlight correctly."
		);

		update_option( 'relevanssi_synonyms', 'ipsum=dolor' );
		$highlighted_content = relevanssi_highlight_in_docs( $content );
		$this->assertEquals(
			'Lorem <strong>ipsum dolor</strong> sit amet, ipsumen consectetur adipiscing elit.',
			$highlighted_content,
			"relevanssi_highlight_in_docs() doesn't highlight correctly with synonyms."
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
