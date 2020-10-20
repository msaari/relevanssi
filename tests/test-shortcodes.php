<?php
/**
 * Class ShortcodeTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi shortcodes.
 */
class ShortcodeTest extends WP_UnitTestCase {
	/**
	 * Sets up the test.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
	}

	/**
	 * Tests relevanssi_shortcode().
	 */
	public function test_shortcode() {
		$this->assertDiscardWhitespace(
			"<a rel='nofollow' href='http://example.org/?s=tomato'>tomato</a>",
			relevanssi_shortcode( array(), 'tomato' )
		);

		$this->assertDiscardWhitespace(
			"<a rel='nofollow' href='http://example.org/?s=%22tomato%20sauce%22'>tomato</a>",
			relevanssi_shortcode(
				array(
					'term'   => 'tomato sauce',
					'phrase' => true,
				),
				'tomato'
			)
		);
	}

	/**
	 * Tests noindex shortcode.
	 */
	public function test_noindexing() {
		$this->assertEquals( 'tomato', relevanssi_noindex_shortcode( array(), 'tomato' ) );
		$this->assertEquals( '', relevanssi_noindex_shortcode_indexing( array(), 'tomato' ) );
	}

	/**
	 * Tests search form shortcode.
	 */
	public function test_search_form() {
		add_filter( 'get_search_form', 'base_search_form' );

		$cat_ids    = array();
		$cat_ids[0] = wp_create_category( 'foo' );
		$post_ids   = $this->factory->post->create_many( 3 );

		$cats = wp_set_post_terms( $post_ids[0], $cat_ids, 'category', true );
		$tags = wp_set_post_terms( $post_ids[0], array( 'bar' ), 'post_tag', true );

		$this->assertDiscardWhitespace(
			'<form role="search" method="get" id="searchform" class="searchform" action="http://example.org/">
				<div>
					<label class="screen-reader-text" for="s">Search for:</label>
					<input type="text" value="" name="s" id="s" />
					<input type="submit" id="searchsubmit" value="Search"/>
				</div>
				<select name=\'cat\' id=\'cat\' class=\'postform\'>
					<option value=\'-1\'>None</option>
					<option class="level-0" value="1">Uncategorized</option>
					<option class="level-0" value="' . $cats[0] . '">foo</option>
				</select>
				<input type=\'hidden\' name=\'field\' value=\'value\'/>
			</form>',
			relevanssi_search_form(
				array(
					'dropdown' => 'category',
					'field'    => 'value',
				)
			)
		);

		$this->assertDiscardWhitespace(
			'<form role="search" method="get" id="searchform" class="searchform" action="http://example.org/">
				<div>
					<label class="screen-reader-text" for="s">Search for:</label>
					<input type="text" value="" name="s" id="s" />
					<input type="submit" id="searchsubmit" value="Search"/>
				</div>
				<select name=\'tag\' id=\'tag\' class=\'postform\'>
					<option value=\'-1\'>None</option>
					<option class="level-0" value="' . $tags[0] . '">bar</option>
				</select>
				<input type=\'hidden\' name=\'field\' value=\'value\'/>
			</form>',
			relevanssi_search_form(
				array(
					'dropdown' => 'post_tag',
					'field'    => 'value',
				)
			)
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

/**
 * Returns a base search form.
 */
function base_search_form() {
	return '<form role="search" method="get" id="searchform" class="searchform" action="http://example.org/">
		<div>
			<label class="screen-reader-text" for="s">Search for:</label>
			<input type="text" value="" name="s" id="s" />
			<input type="submit" id="searchsubmit" value="Search"/>
		</div>
	</form>';
}
