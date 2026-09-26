<?php
/**
 * Tests for the WPML term filter compatibility hooks.
 *
 * @package Relevanssi_Premium
 */

/**
 * Tests that indexing preserves WPML's get_term filter state.
 */
class WPMLTest extends WP_UnitTestCase {
	/**
	 * Loads the compatibility functions and installs a fake SitePress instance.
	 */
	public function setUp() {
		parent::setUp();
		require_once dirname( __DIR__ ) . '/lib/compatibility/wpml.php';
		remove_filter( 'relevanssi_hits_filter', 'relevanssi_wpml_filter', 9 );
		remove_filter( 'relevanssi_tag_before_tokenize', 'relevanssi_wpml_term_fix', 10 );
		$GLOBALS['sitepress'] = new class() {
			/**
			 * Returns the term unchanged.
			 *
			 * @param WP_Term $term The term.
			 * @return WP_Term The term.
			 */
			public function get_term_adjust_id( $term ) {
				return $term;
			}
		};
		add_action( 'relevanssi_pre_index_taxonomies', 'relevanssi_disable_wpml_terms' );
		add_action( 'relevanssi_post_index_taxonomies', 'relevanssi_enable_wpml_terms' );
	}

	/**
	 * Removes the fake callback and SitePress instance.
	 */
	public function tearDown() {
		remove_filter( 'get_term', array( $GLOBALS['sitepress'], 'get_term_adjust_id' ), 1 );
		remove_action( 'relevanssi_pre_index_taxonomies', 'relevanssi_disable_wpml_terms' );
		remove_action( 'relevanssi_post_index_taxonomies', 'relevanssi_enable_wpml_terms' );
		unset( $GLOBALS['sitepress'] );
		parent::tearDown();
	}

	/**
	 * Indexing must not install a callback that was already absent.
	 */
	public function test_taxonomy_indexing_keeps_removed_filter_removed() {
		$callback = array( $GLOBALS['sitepress'], 'get_term_adjust_id' );

		do_action( 'relevanssi_pre_index_taxonomies' );
		$this->assertFalse( has_filter( 'get_term', $callback ) );
		do_action( 'relevanssi_post_index_taxonomies' );

		$this->assertFalse( has_filter( 'get_term', $callback ) );
	}

	/**
	 * Indexing must restore a callback it actually removed.
	 */
	public function test_taxonomy_indexing_restores_existing_filter() {
		$callback = array( $GLOBALS['sitepress'], 'get_term_adjust_id' );
		add_filter( 'get_term', $callback, 1, 1 );

		do_action( 'relevanssi_pre_index_taxonomies' );
		$this->assertFalse( has_filter( 'get_term', $callback ) );
		do_action( 'relevanssi_post_index_taxonomies' );

		$this->assertSame( 1, has_filter( 'get_term', $callback ) );
	}

	/**
	 * Nested indexing must restore the outer operation's original state.
	 */
	public function test_nested_taxonomy_indexing_restores_outer_filter() {
		$callback = array( $GLOBALS['sitepress'], 'get_term_adjust_id' );
		add_filter( 'get_term', $callback, 1, 1 );

		do_action( 'relevanssi_pre_index_taxonomies' );
		do_action( 'relevanssi_pre_index_taxonomies' );
		do_action( 'relevanssi_post_index_taxonomies' );
		$this->assertFalse( has_filter( 'get_term', $callback ) );
		do_action( 'relevanssi_post_index_taxonomies' );

		$this->assertSame( 1, has_filter( 'get_term', $callback ) );
	}

	/**
	 * Tokenizing must not install a callback that was already absent.
	 */
	public function test_term_fix_keeps_removed_filter_removed() {
		$callback = array( $GLOBALS['sitepress'], 'get_term_adjust_id' );
		$term     = $this->factory->term->create_and_get( array( 'taxonomy' => 'category' ) );
		add_filter( 'wpml_post_language_details', array( $this, 'post_language_details' ) );

		$content = relevanssi_wpml_term_fix( '', array( $term ), 'category', 1 );

		$this->assertSame( ' ' . $term->name, $content );
		$this->assertFalse( has_filter( 'get_term', $callback ) );
	}

	/**
	 * Tokenizing must restore a callback it actually removed.
	 */
	public function test_term_fix_restores_existing_filter() {
		$callback = array( $GLOBALS['sitepress'], 'get_term_adjust_id' );
		$term     = $this->factory->term->create_and_get( array( 'taxonomy' => 'category' ) );
		add_filter( 'get_term', $callback, 1, 1 );
		add_filter( 'wpml_post_language_details', array( $this, 'post_language_details' ) );

		$content = relevanssi_wpml_term_fix( '', array( $term ), 'category', 1 );

		$this->assertSame( ' ' . $term->name, $content );
		$this->assertSame( 1, has_filter( 'get_term', $callback ) );
	}

	/**
	 * Term tokenizing during taxonomy indexing must leave the filter disabled.
	 */
	public function test_term_fix_inside_taxonomy_indexing_keeps_filter_disabled() {
		$callback = array( $GLOBALS['sitepress'], 'get_term_adjust_id' );
		$term     = $this->factory->term->create_and_get( array( 'taxonomy' => 'category' ) );
		add_filter( 'get_term', $callback, 1, 1 );
		add_filter( 'wpml_post_language_details', array( $this, 'post_language_details' ) );

		do_action( 'relevanssi_pre_index_taxonomies' );
		relevanssi_wpml_term_fix( '', array( $term ), 'category', 1 );
		$this->assertFalse( has_filter( 'get_term', $callback ) );
		do_action( 'relevanssi_post_index_taxonomies' );

		$this->assertSame( 1, has_filter( 'get_term', $callback ) );
	}

	/**
	 * An exception in the term lookup must still restore the prior callback.
	 */
	public function test_term_fix_restores_filter_after_exception() {
		$callback = array( $GLOBALS['sitepress'], 'get_term_adjust_id' );
		$term     = $this->factory->term->create_and_get( array( 'taxonomy' => 'category' ) );
		add_filter( 'get_term', $callback, 1, 1 );
		add_filter( 'wpml_post_language_details', array( $this, 'post_language_details' ) );
		$throw = function () {
			throw new RuntimeException( 'Term lookup interrupted.' );
		};
		add_filter( 'wpml_object_id', $throw );

		try {
			relevanssi_wpml_term_fix( '', array( $term ), 'category', 1 );
			$this->fail( 'Expected the term lookup to throw.' );
		} catch ( RuntimeException $exception ) {
			$this->assertSame( 'Term lookup interrupted.', $exception->getMessage() );
		} finally {
			remove_filter( 'wpml_object_id', $throw );
		}

		$this->assertSame( 1, has_filter( 'get_term', $callback ) );
	}

	/**
	 * Returns a valid WPML language response.
	 *
	 * @return array Language details.
	 */
	public function post_language_details() {
		return array( 'language_code' => 'nl' );
	}
}
