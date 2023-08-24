<?php
/**
 * Class SortingTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi sorting functions.
 */
class SortingTest extends WP_UnitTestCase {
	/**
	 * Sets up the test.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
	}

	/**
	 * Tests relevanssi_get_next_key().
	 */
	public function test_get_next_key() {
		$orderby = array(
			'rand'           => 123456,
			'post_type'      => 'rand',
			'rand(123)'      => 'asc',
			'title'          => 'asc',
			'date'           => 'desc',
			'modified'       => 'asc',
			'parent'         => 'desc',
			'type'           => 'asc',
			'name'           => 'desc',
			'author'         => 'asc',
			'relevance'      => 'desc',
			'meta_value_num' => 'asc',
		);

		$empty = array();
		$this->assertEquals(
			array(
				'key'     => null,
				'dir'     => null,
				'compare' => null,
			),
			relevanssi_get_next_key( $empty )
		);

		$this->assertEquals(
			array(
				'key'     => 'rand',
				'dir'     => 123456,
				'compare' => 'string',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'rand',
				'dir'     => 'rand',
				'compare' => 'string',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'rand',
				'dir'     => 123,
				'compare' => 'string',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'post_title',
				'dir'     => 'asc',
				'compare' => 'string',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'post_date',
				'dir'     => 'desc',
				'compare' => 'date',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'post_modified',
				'dir'     => 'asc',
				'compare' => 'date',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'post_parent',
				'dir'     => 'desc',
				'compare' => 'number',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'post_type',
				'dir'     => 'asc',
				'compare' => 'filter',
			),
			relevanssi_get_next_key( $orderby )
		);

		add_filter( 'relevanssi_sort_compare', '__return_true' );
		$this->assertEquals(
			array(
				'key'     => 'post_name',
				'dir'     => 'desc',
				'compare' => 'string',
			),
			relevanssi_get_next_key( $orderby )
		);
		remove_filter( 'relevanssi_sort_compare', '__return_true' );

		$this->assertEquals(
			array(
				'key'     => 'post_author',
				'dir'     => 'asc',
				'compare' => 'number',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'relevance_score',
				'dir'     => 'desc',
				'compare' => 'number',
			),
			relevanssi_get_next_key( $orderby )
		);

		$this->assertEquals(
			array(
				'key'     => 'meta_value_num',
				'dir'     => 'asc',
				'compare' => 'number',
			),
			relevanssi_get_next_key( $orderby )
		);
	}

	/**
	 * Tests relevanssi_get_compare_values().
	 */
	public function test_get_compare_values() {
		$keys = relevanssi_get_compare_values( 'rand', true, false );
		$this->assertTrue(
			is_numeric( $keys['key1'] ) &&
			is_numeric( $keys['key2'] ) &&
			$keys['key1'] !== $keys['key2']
		);

		global $wp_query, $relevanssi_meta_query;
		$wp_query->query_vars['meta_key'] = '';
		$relevanssi_meta_query            = array();

		$keys = relevanssi_get_compare_values( 'meta_value', true, false );
		$this->assertEquals(
			array( '', '' ),
			$keys
		);

		$relevanssi_meta_query = array(
			'relation' => 'OR',
			array(
				'key'   => 'not_this',
				'value' => 'this has a value',
			),
			array(
				'key' => 'sorting_key',
			),
		);

		$post_ids = self::factory()->post->create_many( 2 );
		$post_1   = get_post( $post_ids[0] );
		$post_2   = get_post( $post_ids[1] );

		add_filter( 'relevanssi_missing_sort_key', array( $this, 'return_sort_key' ), 10, 2 );
		$keys = relevanssi_get_compare_values( 'meta_value', $post_1, $post_2 );
		$this->assertEquals(
			array(
				'key1' => 'foo',
				'key2' => 'foo',
			),
			$keys
		);

		update_post_meta( $post_1->ID, 'custom_field_name', 'first_post_value' );
		update_post_meta( $post_2->ID, 'other_custom_field', 'second_post_value' );
		$relevanssi_meta_query['custom_field_name']['key'] = 'other_custom_field';

		$keys = relevanssi_get_compare_values( 'custom_field_name', $post_1, $post_2 );
		$this->assertEquals(
			array(
				'key1' => 'first_post_value',
				'key2' => 'second_post_value',
			),
			$keys
		);

		delete_post_meta( $post_1->ID, 'custom_field_name', 'first_post_value' );
		update_post_meta( $post_1->ID, 'other_custom_field', 'first_post_other_value' );

		$keys = relevanssi_get_compare_values( 'custom_field_name', $post_1, $post_2 );
		$this->assertEquals(
			array(
				'key1' => 'first_post_other_value',
				'key2' => 'second_post_value',
			),
			$keys
		);

		unset( $relevanssi_meta_query['custom_field_name'] );

		update_post_meta( $post_1->ID, 'custom_field_name', 'first_post_value' );

		$keys = relevanssi_get_compare_values( 'custom_field_name', $post_1, $post_2 );
		$this->assertEquals(
			array(
				'key1' => 'first_post_value',
				'key2' => 'foo',
			),
			$keys
		);

		remove_filter( 'relevanssi_missing_sort_key', array( $this, 'return_sort_key' ), 10, 2 );
		add_filter( 'relevanssi_missing_sort_key', array( $this, 'return_sort_key_array' ), 10, 2 );

		update_post_meta( $post_2->ID, 'custom_field_name', 'second_post_value' );
		delete_post_meta( $post_1->ID, 'custom_field_name' );

		$keys = relevanssi_get_compare_values( 'custom_field_name', $post_1, $post_2 );
		$this->assertEquals(
			array(
				'key1' => 'foo',
				'key2' => 'second_post_value',
			),
			$keys
		);

		delete_post_meta( $post_2->ID, 'custom_field_name' );

		$keys = relevanssi_get_compare_values( 'custom_field_name', $post_1, $post_2 );
		$this->assertEquals(
			array(
				'key1' => 'foo',
				'key2' => 'foo',
			),
			$keys
		);
	}

	/**
	 * Tests relevanssi_compare_values() and relevanssi_filter_compare().
	 */
	public function test_compare_values_filter_compare() {
		$this->assertEquals( 1, relevanssi_compare_values( '2020-09-28', '2001-09-28', 'date' ) );
		$this->assertEquals( -1, relevanssi_compare_values( '2020-09-28', '2021-09-28', 'date' ) );
		$this->assertEquals( 0, relevanssi_compare_values( '2020-09-28', '2020-09-28', 'date' ) );

		$this->assertEquals( 1, relevanssi_compare_values( '2', '1', 'number' ) );
		$this->assertEquals( -1, relevanssi_compare_values( '1', '2', 'number' ) );
		$this->assertEquals( 0, relevanssi_compare_values( '3', '3', 'number' ) );

		add_filter( 'relevanssi_comparison_order', array( $this, 'return_filter_array' ) );

		$this->assertEquals( 2, relevanssi_compare_values( 'book', 'post', 'filter' ) );
		$this->assertEquals( -1, relevanssi_compare_values( 'post', 'page', 'filter' ) );
		$this->assertEquals( 0, relevanssi_compare_values( 'page', 'page', 'filter' ) );
	}

	/**
	 * Tests relevanssi_cmp_function().
	 *
	 * This is tested elsewhere enough, here we only need to test that the
	 * int parameters work.
	 */
	public function test_cmp_function() {
		$post_ids = self::factory()->post->create_many( 2 );
		$post_1   = $post_ids[0];
		$post_2   = $post_ids[1];

		wp_insert_post(
			array(
				'ID'         => $post_1,
				'post_title' => 'Aardvark',
			)
		);

		wp_insert_post(
			array(
				'ID'         => $post_2,
				'post_title' => 'Bemböle',
			)
		);

		global $relevanssi_keys, $relevanssi_dirs, $relevanssi_compares;

		$relevanssi_keys     = array( 'post_title' );
		$relevanssi_dirs     = array( 'asc' );
		$relevanssi_compares = array( 'string' );

		$this->assertEquals( -1, relevanssi_cmp_function( $post_1, $post_2 ) );
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		require_once dirname( __DIR__ ) . '/lib/uninstall.php';
		if ( RELEVANSSI_PREMIUM ) {
			require_once dirname( __DIR__ ) . '/premium/uninstall.php';
		}

		if ( function_exists( 'relevanssi_uninstall' ) ) {
			relevanssi_uninstall();
		}
		if ( function_exists( 'relevanssi_uninstall_free' ) ) {
			relevanssi_uninstall_free();
		}
	}

	/**
	 * Returns 'foo'.
	 *
	 * @param string $value Not used.
	 * @param string $key   Not used.
	 */
	public static function return_sort_key( $value, $key ) {
		return 'foo';
	}

	/**
	 * Returns 'foo' in an array.
	 *
	 * @param string $value Not used.
	 * @param string $key   Not used.
	 */
	public static function return_sort_key_array( $value, $key ) {
		return array( 'foo' );
	}

	/**
	 * Returns filter array where post = 0, page = 1 and book = 2.
	 *
	 * @param array $order Ordering array.
	 */
	public static function return_filter_array( array $order ) {
		$order = array(
			'post' => 0,
			'page' => 1,
			'book' => 2,
		);
		return $order;
	}
}
