<?php
/**
 * Tests for the Did You Mean block.
 *
 * @package Relevanssi
 */

/**
 * Test the shared Did You Mean block registration and rendering.
 */
class DidYouMeanBlockTest extends WP_UnitTestCase {
	/**
	 * Misspelled query used by the suggestion fixture.
	 *
	 * @var string
	 */
	const MISSPELLED_QUERY = 'bishpric';

	/**
	 * Corrected query returned by the suggestion fixture.
	 *
	 * @var string
	 */
	const SUGGESTED_QUERY = 'bishopric';

	/**
	 * Original global query object.
	 *
	 * @var WP_Query|null
	 */
	private $original_wp_query;

	/**
	 * Whether the global query existed before the test.
	 *
	 * @var boolean
	 */
	private $had_wp_query;

	/**
	 * Original block support rendering context.
	 *
	 * @var array|null
	 */
	private $original_block_to_render;

	/**
	 * Installs Relevanssi and registers the block for the test class.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();

		$registry = WP_Block_Type_Registry::get_instance();
		if ( ! $registry->is_registered( 'relevanssi/did-you-mean' ) ) {
			relevanssi_register_did_you_mean_block();
		}
	}

	/**
	 * Preserves global state and installs a deterministic suggestion source.
	 */
	public function setUp() {
		parent::setUp();

		$this->had_wp_query      = array_key_exists( 'wp_query', $GLOBALS );
		$this->original_wp_query = $this->had_wp_query ? $GLOBALS['wp_query'] : null;

		if ( class_exists( 'WP_Block_Supports' ) ) {
			$this->original_block_to_render = WP_Block_Supports::$block_to_render;
		}

		delete_transient( 'relevanssi_didyoumean_query' );
		add_filter( 'relevanssi_didyoumean_query', array( $this, 'suggestion_query' ) );
	}

	/**
	 * Restores global state and removes the deterministic suggestion source.
	 */
	public function tearDown() {
		remove_filter( 'relevanssi_didyoumean_query', array( $this, 'suggestion_query' ) );
		delete_transient( 'relevanssi_didyoumean_query' );

		if ( $this->had_wp_query ) {
			$GLOBALS['wp_query'] = $this->original_wp_query;
		} else {
			unset( $GLOBALS['wp_query'] );
		}

		if ( class_exists( 'WP_Block_Supports' ) ) {
			WP_Block_Supports::$block_to_render = $this->original_block_to_render;
		}

		parent::tearDown();
	}

	/**
	 * Tests that WordPress registers the shared block type.
	 */
	public function test_did_you_mean_block_is_registered() {
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'relevanssi/did-you-mean' )
		);
	}

	/**
	 * Tests that registered attributes match the metadata defaults.
	 */
	public function test_did_you_mean_block_default_attributes() {
		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'relevanssi/did-you-mean' );

		$this->assertSame( 'Did you mean:', $block_type->attributes['prefix']['default'] );
		$this->assertSame( '?', $block_type->attributes['suffix']['default'] );
		$this->assertSame( 5, $block_type->attributes['maxResults']['default'] );
	}

	/**
	 * Tests that the generated editor bundle and dependency metadata are wired.
	 */
	public function test_did_you_mean_editor_script_is_registered() {
		$scripts = wp_scripts();
		$handle  = 'relevanssi-did-you-mean-editor';

		$this->assertNotFalse( $scripts->query( $handle, 'registered' ) );
		$this->assertStringEndsWith(
			'/lib/blocks/did-you-mean/editor.js',
			$scripts->registered[ $handle ]->src
		);
		$this->assertContains( 'wp-blocks', $scripts->registered[ $handle ]->deps );
		$this->assertContains( 'wp-block-editor', $scripts->registered[ $handle ]->deps );
		$this->assertFileExists( dirname( __DIR__ ) . '/lib/blocks/did-you-mean/editor.js' );
		$this->assertFileExists( dirname( __DIR__ ) . '/lib/blocks/did-you-mean/editor.asset.php' );
	}

	/**
	 * Tests that a non-search request without a query renders nothing.
	 */
	public function test_render_returns_empty_when_no_search_query() {
		$this->set_search_query( '', 0, false );

		$this->assertSame( '', relevanssi_render_did_you_mean_block( array() ) );
	}

	/**
	 * Tests that an explicitly empty search query renders nothing.
	 */
	public function test_render_returns_empty_when_search_query_is_empty_string() {
		$this->set_search_query( '', 0, true );

		$this->assertSame( '', relevanssi_render_did_you_mean_block( array() ) );
	}

	/**
	 * Tests that a query without a correction renders nothing.
	 */
	public function test_render_returns_empty_when_no_suggestion_available() {
		$this->set_search_query( 'zzqv', 0 );

		$this->assertSame( '', relevanssi_render_did_you_mean_block( array() ) );
	}

	/**
	 * Tests that result counts above the configured threshold render nothing.
	 */
	public function test_render_exits_when_found_posts_exceeds_threshold() {
		$this->set_search_query( self::MISSPELLED_QUERY, 6 );

		$this->assertSame(
			'',
			relevanssi_render_did_you_mean_block( array( 'maxResults' => 5 ) )
		);
	}

	/**
	 * Tests that a result count equal to the threshold renders a suggestion.
	 */
	public function test_render_displays_when_found_posts_equals_threshold() {
		$this->set_search_query( self::MISSPELLED_QUERY, 5 );

		$output = relevanssi_render_did_you_mean_block( array( 'maxResults' => 5 ) );

		$this->assertStringContainsString( '<p>', $output );
		$this->assertStringContainsString( self::SUGGESTED_QUERY, $output );
		$this->assertStringContainsString( '</a>?</p>', $output );
	}

	/**
	 * Tests that a result count below the threshold renders a suggestion.
	 */
	public function test_render_displays_when_found_posts_is_below_threshold() {
		$this->set_search_query( self::MISSPELLED_QUERY, 0 );

		$output = relevanssi_render_did_you_mean_block( array( 'maxResults' => 5 ) );

		$this->assertStringContainsString( self::SUGGESTED_QUERY, $output );
		$this->assertStringContainsString( '<a href=', $output );
	}

	/**
	 * Tests that a manually serialized negative threshold is clamped to zero.
	 */
	public function test_render_clamps_negative_threshold_to_zero() {
		$this->set_search_query( self::MISSPELLED_QUERY, 0 );

		$output = relevanssi_render_did_you_mean_block( array( 'maxResults' => -10 ) );

		$this->assertStringContainsString( self::SUGGESTED_QUERY, $output );
	}

	/**
	 * Tests that a manually serialized high threshold is clamped to fifty.
	 */
	public function test_render_clamps_high_threshold_to_fifty() {
		$this->set_search_query( self::MISSPELLED_QUERY, 51 );

		$this->assertSame(
			'',
			relevanssi_render_did_you_mean_block( array( 'maxResults' => 500 ) )
		);
	}

	/**
	 * Tests that block labels cannot inject executable markup.
	 */
	public function test_render_escapes_malicious_prefix_and_suffix() {
		$this->set_search_query( self::MISSPELLED_QUERY, 0 );

		$output = relevanssi_render_did_you_mean_block(
			array(
				'prefix'     => '<script>alert(1)</script>',
				'suffix'     => '"><img src=x onerror=alert(1)>',
				'maxResults' => 5,
			)
		);

		$this->assertStringNotContainsString( '<script', $output );
		$this->assertStringNotContainsString( '<img', $output );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $output );
		$this->assertStringContainsString( '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;', $output );
	}

	/**
	 * Tests that Gutenberg wrapper classes are applied to the paragraph.
	 */
	public function test_render_applies_wrapper_attributes() {
		$this->set_search_query( self::MISSPELLED_QUERY, 0 );

		$output = render_block(
			array(
				'blockName'    => 'relevanssi/did-you-mean',
				'attrs'        => array(
					'className'  => 'custom-dym-class',
					'maxResults' => 5,
				),
				'innerHTML'    => '',
				'innerBlocks'  => array(),
				'innerContent' => array(),
			)
		);

		$this->assertRegExp(
			'/<p class="[^"]*\bcustom-dym-class\b[^"]*">/',
			$output
		);
	}

	/**
	 * Replaces the suggestion candidate query with deterministic test data.
	 *
	 * @param string $query Original database query.
	 *
	 * @return string Test database query.
	 */
	public function suggestion_query( $query ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Filter callback signature.
		return "SELECT '" . self::SUGGESTED_QUERY . "' AS query, 10 AS c, 10 AS a";
	}

	/**
	 * Sets the global search query used by get_search_query().
	 *
	 * @param string  $query       Search query.
	 * @param int     $found_posts Number of found posts.
	 * @param boolean $is_search   Whether this is a search request.
	 *
	 * @return void
	 */
	private function set_search_query( $query, $found_posts, $is_search = true ) {
		$wp_query = new WP_Query();
		$wp_query->parse_query( array( 's' => $query ) );
		$wp_query->is_search   = $is_search;
		$wp_query->found_posts = $found_posts;

		$GLOBALS['wp_query'] = $wp_query;
	}
}
