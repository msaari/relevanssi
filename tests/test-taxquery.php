<?php
/**
 * Class TaxQueryTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi tax_quer handling.
 *
 * @group tax_query
 */
class TaxQueryTest extends WP_UnitTestCase {
	/**
	 * Books category ID.
	 *
	 * @var int self::$books_id
	 */
	public static $books_id;

	/**
	 * Games category ID.
	 *
	 * @var int self::$games_id
	 */
	public static $games_id;

	/**
	 * Puzzles child category ID.
	 *
	 * @var int self::$puzzles_id
	 */
	public static $puzzles_id;

	/**
	 * Blue tag ID.
	 *
	 * @var int self::$blue_id
	 */
	public static $blue_id;

	/**
	 * Yellow tag ID.
	 *
	 * @var int self::$yellow_id
	 */
	public static $yellow_id;

	/**
	 * Installs Relevanssi.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();

		self::$books_id = wp_create_category( 'Books and periodicals' );
		self::$games_id = wp_create_category( 'games' );

		self::$puzzles_id = wp_create_category( 'puzzles', self::$games_id );

		$blue_tag        = wp_create_tag( 'blue' );
		$yellow_tag      = wp_create_tag( 'yellow' );
		self::$blue_id   = $blue_tag['term_id'];
		self::$yellow_id = $yellow_tag['term_id'];
	}

	/**
	 * Test basic tax query.
	 */
	public function test_basic_tax_query() {
		global $wpdb;

		$books_id   = self::$books_id;
		$games_id   = self::$games_id;
		$puzzles_id = self::$puzzles_id;

		$tax_query = array(
			array(
				'taxonomy' => 'category',
				'field'    => 'id', // Should be corrected to 'term_id'.
				'terms'    => array( $books_id, $games_id ),
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
			FROM {$wpdb->prefix}term_relationships AS tr
			WHERE tr.term_taxonomy_id IN ($books_id,$games_id,$puzzles_id))
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( '', $tax_query )
		);
	}

	/**
	 * Test parallel AND queries.
	 */
	public function test_parallel_and_queries() {
		global $wpdb;

		$books_id = self::$books_id;
		$games_id = self::$games_id;

		$tax_query = array(
			array(
				'taxonomy' => 'category',
				'terms'    => $books_id,
				// No "field" parameter, Relevanssi should assume "term_id".
			),
			array(
				'taxonomy'         => 'category',
				'field'            => 'slug',
				'terms'            => $games_id,
				'operator'         => 'NOT IN',
				'include_children' => false,
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($books_id))
			AND relevanssi.doc NOT IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($games_id))
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test EXISTS queries.
	 */
	public function test_exists_queries() {
		global $wpdb;

		$tax_query = array(
			array(
				'taxonomy' => 'category',
				'operator' => 'EXISTS',
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr, {$wpdb->prefix}term_taxonomy AS tt
				WHERE tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.taxonomy='category')
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);

		global $wpdb;

		$tax_query = array(
			array(
				'taxonomy' => 'category',
				'operator' => 'EXISTS',
			),
			array(
				'taxonomy' => 'post_tag',
				'operator' => 'NOT EXISTS',
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr, {$wpdb->prefix}term_taxonomy AS tt
				WHERE tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.taxonomy='category')
			AND relevanssi.doc NOT IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr, {$wpdb->prefix}term_taxonomy AS tt
				WHERE tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.taxonomy='post_tag')
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test nested AND queries.
	 */
	public function test_nested_and_queries() {
		global $wpdb;

		$books_id  = self::$books_id;
		$games_id  = self::$games_id;
		$yellow_id = self::$yellow_id;

		$tax_query = array(
			array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => array( $books_id ),
			),
			array(
				'relation' => 'AND',
				array(
					'taxonomy'         => 'category',
					'field'            => 'term_taxonomy_id',
					'terms'            => $games_id,
					'include_children' => false,
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'name',
					'terms'    => array( 'yellow' ),
				),
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($books_id))
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($games_id))
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($yellow_id))
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test parallel OR queries.
	 */
	public function test_parallel_or_queries() {
		global $wpdb;

		$books_id  = self::$books_id;
		$games_id  = self::$games_id;
		$yellow_id = self::$yellow_id;
		$blue_id   = self::$blue_id;

		$tax_query = array(
			'relation' => 'or',
			array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => 'books-and-periodicals',
			),
			array(
				'taxonomy'         => 'category',
				'field'            => 'slug',
				'terms'            => 'games',
				'operator'         => 'NOT IN',
				'include_children' => false,
			),
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => array( 'blue', 'yellow' ),
				'operator' => 'AND',
			),
		);

		$target_query_restriction = <<<END
		AND (
			relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
			FROM {$wpdb->prefix}term_relationships AS tr
			WHERE tr.term_taxonomy_id IN({$books_id}))
		OR relevanssi.doc NOT IN (SELECT DISTINCT(tr.object_id)
			FROM {$wpdb->prefix}term_relationships AS tr
			WHERE tr.term_taxonomy_id IN ({$games_id}))
		OR relevanssi.doc IN (SELECT ID
			FROM {$wpdb->prefix}posts WHERE 1=1
			AND (SELECT COUNT(1) FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ({$blue_id},{$yellow_id})
				AND tr.object_id={$wpdb->prefix}posts.ID) = 2))
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'or', $tax_query )
		);
	}

	/**
	 * Test nested OR query.
	 */
	public function test_nested_or_query() {
		global $wpdb;

		$books_id  = self::$books_id;
		$games_id  = self::$games_id;
		$yellow_id = self::$yellow_id;

		$tax_query = array(
			array(
				'taxonomy' => 'category',
				'field'    => 'name',
				'terms'    => 'Books and periodicals',
			),
			array(
				'relation' => 'OR',
				array(
					'taxonomy'         => 'category',
					'field'            => 'slug',
					'terms'            => 'games',
					'include_children' => false,
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => 'yellow',
				),
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ({$books_id}))
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ({$games_id},{$yellow_id}))
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test nested OR query with AND and NOT.
	 */
	public function test_nested_or_query_and_not() {
		global $wpdb;

		$books_id  = self::$books_id;
		$games_id  = self::$games_id;
		$yellow_id = self::$yellow_id;
		$blue_id   = self::$blue_id;

		$tax_query = array(
			array(
				'taxonomy' => 'category',
				'field'    => 'name',
				'terms'    => 'Books and periodicals',
			),
			array(
				'relation' => 'OR',
				array(
					'taxonomy'         => 'category',
					'field'            => 'slug',
					'terms'            => 'games',
					'operator'         => 'NOT IN',
					'include_children' => false,
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => array( 'yellow', 'blue' ),
					'operator' => 'AND',
				),
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($books_id))
			AND (
				relevanssi.doc NOT IN (SELECT DISTINCT(tr.object_id)
					FROM {$wpdb->prefix}term_relationships AS tr
					WHERE tr.term_taxonomy_id IN ($games_id))
				OR relevanssi.doc IN (
					SELECT ID FROM {$wpdb->prefix}posts WHERE 1=1
					AND (
						SELECT COUNT(1) FROM {$wpdb->prefix}term_relationships AS tr
						WHERE tr.term_taxonomy_id IN ($blue_id,$yellow_id)
						AND tr.object_id={$wpdb->prefix}posts.ID
					) = 2
				)
			)
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test parallel NOT EXISTS query.
	 */
	public function test_parallel_not_exists() {
		global $wpdb;

		$games_id = self::$games_id;

		$tax_query = array(
			'relation' => 'OR',
			array(
				'taxonomy'         => 'category',
				'field'            => 'term_taxonomy_id',
				'terms'            => $games_id,
				'include_children' => false,
			),
			array(
				'taxonomy' => 'category',
				'operator' => 'NOT EXISTS',
			),
		);

		$target_query_restriction = <<<END
			AND (
				relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
					FROM {$wpdb->prefix}term_relationships AS tr
					WHERE tr.term_taxonomy_id IN ({$games_id}))
				OR relevanssi.doc NOT IN (SELECT DISTINCT(tr.object_id)
					FROM {$wpdb->prefix}term_relationships AS tr,
					{$wpdb->prefix}term_taxonomy AS tt
					WHERE tr.term_taxonomy_id=tt.term_taxonomy_id
					AND tt.taxonomy='category')
				)
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'or', $tax_query )
		);
	}


	/**
	 * Test name with numeric parameter.
	 */
	public function test_name_with_numeric() {
		global $wpdb;

		$books_id  = self::$books_id;
		$games_id  = self::$games_id;
		$yellow_id = self::$yellow_id;

		$tax_query = array(
			array(
				'taxonomy' => 'category',
				'field'    => 'name',
				'terms'    => $books_id,
			),
			array(
				'relation' => 'AND',
				array(
					'taxonomy'         => 'category',
					'field'            => 'term_taxonomy_id',
					'terms'            => $games_id,
					'operator'         => 'SMOOTH', // This should be fixed to "IN".
					'include_children' => false,
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'name',
					'terms'    => array( $yellow_id ),
				),
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($books_id))
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($games_id))
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($yellow_id))
END;
		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test AND operator.
	 */
	public function test_and_operator() {
		global $wpdb;

		$books_id  = self::$books_id;
		$yellow_id = self::$yellow_id;
		$blue_id   = self::$blue_id;

		$tax_query = array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => 'books-and-periodicals',
				'operator' => 'NOT IN',
			),
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => array( 'yellow', 'blue' ),
				'operator' => 'AND',
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc NOT IN (
				SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($books_id))
			AND relevanssi.doc IN (
				SELECT ID FROM {$wpdb->prefix}posts
				WHERE 1=1 AND (
					SELECT COUNT(1)
					FROM {$wpdb->prefix}term_relationships AS tr
					WHERE tr.term_taxonomy_id IN ($blue_id,$yellow_id)
					AND tr.object_id={$wpdb->prefix}posts.ID
				) = 2
			)
END;

		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test children and field=term_taxonomy_id with an array.
	 */
	public function test_term_tax_id_array() {
		global $wpdb;

		$games_id   = self::$games_id;
		$puzzles_id = self::$puzzles_id;

		$tax_query = array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'category',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( $games_id ),
				'operator' => 'IN',
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (
				SELECT DISTINCT (tr.object_id)
					FROM {$wpdb->prefix}term_relationships AS tr
					WHERE tr.term_taxonomy_id IN ($games_id, $puzzles_id)
			)
END;

		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test that an empty tax query returns an empty string.
	 */
	public function test_empty_tax_query() {
		$tax_query = array(
			'relation' => 'AND',
			array(
				'relation' => 'AND',
			),
			array(
				'relation' => 'OR',
			),
		);
		$this->assertEquals(
			'',
			relevanssi_process_tax_query( 'and', $tax_query )
		);
	}

	/**
	 * Test a mixture of AND and OR.
	 */
	public function test_and_or_mixture() {
		global $wpdb;

		$books_id  = self::$books_id;
		$games_id  = self::$games_id;
		$yellow_id = self::$yellow_id;

		$tax_query = array(
			'relation' => 'OR',
			array(
				'taxonomy' => 'category',
				'field'    => 'name',
				'terms'    => 'Books and periodicals',
			),
			array(
				'relation' => 'AND',
				array(
					'taxonomy'         => 'category',
					'field'            => 'slug',
					'terms'            => 'games',
					'include_children' => false,
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => 'yellow',
				),
			),
		);

		$target_query_restriction = <<<END
			AND (
				relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
					FROM {$wpdb->prefix}term_relationships AS tr
					WHERE tr.term_taxonomy_id IN ($books_id)
				) OR relevanssi.doc IN (SELECT ID
					FROM {$wpdb->prefix}posts
					WHERE 1=1 AND (SELECT COUNT(1)
						FROM {$wpdb->prefix}term_relationships AS tr
						WHERE tr.term_taxonomy_id IN ($games_id,$yellow_id)
						AND tr.object_id={$wpdb->prefix}posts.ID)
					=2
				)
			)
END;

		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'or', $tax_query )
		);

		$tax_query = array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'category',
				'field'    => 'name',
				'terms'    => 'Books and periodicals',
			),
			array(
				'relation' => 'OR',
				array(
					'taxonomy'         => 'category',
					'field'            => 'slug',
					'terms'            => 'games',
					'include_children' => false,
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => 'yellow',
				),
			),
		);

		$target_query_restriction = <<<END
			AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($books_id)
			) AND relevanssi.doc IN (SELECT DISTINCT(tr.object_id)
				FROM {$wpdb->prefix}term_relationships AS tr
				WHERE tr.term_taxonomy_id IN ($games_id,$yellow_id)
			)
END;

		$this->assertDiscardWhitespace(
			$target_query_restriction,
			relevanssi_process_tax_query( 'and', $tax_query )
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
