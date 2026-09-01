<?php
/**
 * Tests for modal search forms.
 *
 * @package Relevanssi_Premium
 */

/**
 * Test Relevanssi modal search forms.
 */
class ModalSearchTest extends WP_UnitTestCase {
	/**
	 * Provides predictable search form markup.
	 */
	public function setUp() {
		parent::setUp();
		add_filter( 'get_search_form', array( $this, 'base_search_form' ) );
	}

	/**
	 * Removes the search form filter.
	 */
	public function tearDown() {
		remove_filter( 'get_search_form', array( $this, 'base_search_form' ) );
		unset( $GLOBALS['relevanssi_modal_search_menu_dialog'] );
		parent::tearDown();
	}

	/**
	 * Tests the shortcode accessibility markup and form arguments.
	 */
	public function test_modal_search_shortcode() {
		$html = do_shortcode( '[relevanssi_modal_search id="catalog-search" label="Find products" modal_label="Search the catalog" post_types="product"]' );

		$this->assertStringContainsString( 'data-relevanssi-modal-search="catalog-search"', $html );
		$this->assertStringContainsString( 'aria-haspopup="dialog"', $html );
		$this->assertStringContainsString( '<dialog id="catalog-search"', $html );
		$this->assertStringContainsString( 'aria-labelledby="catalog-search-title"', $html );
		$this->assertStringContainsString( 'Search the catalog', $html );
		$this->assertStringContainsString( "name='post_types' value='product'", $html );
	}

	/**
	 * Tests icon-only triggers retain an accessible name.
	 */
	public function test_icon_trigger_has_accessible_label() {
		$html = relevanssi_get_modal_search(
			array(
				'id'    => 'icon-search',
				'label' => 'Open search',
				'icon'  => true,
			)
		);

		$this->assertStringContainsString( '<span class="screen-reader-text">Open search</span>', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
	}

	/**
	 * Tests shortcode boolean values.
	 */
	public function test_shortcode_boolean_values() {
		$html = do_shortcode( '[relevanssi_modal_search id="text-search" icon="false" trigger="true"]' );

		$this->assertStringNotContainsString( 'relevanssi-modal-search__trigger--icon', $html );
		$this->assertStringContainsString( 'data-relevanssi-modal-search="text-search"', $html );
	}

	/**
	 * Tests modal filter controls use self-contained, accessible markup.
	 */
	public function test_modal_filter_controls_are_structured() {
		wp_insert_term( 'Modal category', 'category' );
		wp_insert_term( 'Modal tag', 'post_tag' );

		$html = relevanssi_get_modal_search(
			array(
				'checklist'       => 'category',
				'dropdown_1'      => 'post_tag',
				'post_type_boxes' => '*post,page',
			)
		);

		$this->assertStringContainsString( 'class="relevanssi-search-form__checklist relevanssi-search-form__checklist--category"', $html );
		$this->assertStringContainsString( '<ul class="categorychecklist form-no-clear">', $html );
		$this->assertStringContainsString( '<legend>Filter by Categories</legend>', $html );
		$this->assertStringContainsString( 'class="relevanssi-search-form__field"', $html );
		$this->assertStringContainsString( 'Filter by Tags', $html );
		$this->assertStringContainsString( 'class="post_types relevanssi-search-form__post-types"', $html );
		$this->assertStringContainsString( '<legend>Post types</legend>', $html );
		$this->assertStringContainsString( 'relevanssi-search-form__choice', $html );
	}

	/**
	 * Tests theme button classes and their sanitization.
	 */
	public function test_theme_button_classes() {
		$html = do_shortcode( '[relevanssi_modal_search id="theme-search" button_class="theme-button button--search"]' );

		$this->assertStringContainsString( 'relevanssi-modal-search__trigger wp-element-button theme-button button--search', $html );
		$this->assertStringNotContainsString( "name='button_class'", $html );
	}

	/**
	 * Tests stylesheet preloading for modal shortcodes in post content.
	 */
	public function test_modal_shortcode_preloads_stylesheet() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '[relevanssi_modal_search]',
			)
		);

		$this->go_to( get_permalink( $post_id ) );
		relevanssi_preload_modal_search_style();

		$this->assertTrue( wp_style_is( 'relevanssi-modal-search', 'enqueued' ) );
		wp_dequeue_style( 'relevanssi-modal-search' );
	}

	/**
	 * Tests menu-link detection.
	 */
	public function test_menu_link_detection() {
		$item      = new stdClass();
		$item->url = '#relevanssi-modal-search';
		$items     = array( $item );

		$this->assertSame( $items, relevanssi_detect_modal_search_menu_link( $items ) );
		$this->assertTrue( $GLOBALS['relevanssi_modal_search_menu_dialog'] );
	}

	/**
	 * Returns a base search form.
	 *
	 * @return string Search form markup.
	 */
	public function base_search_form() {
		return '<form role="search" method="get" class="search-form" action="http://example.org/"><label><span class="screen-reader-text">Search for:</span><input type="search" name="s" /></label><button type="submit">Search</button></form>';
	}
}
