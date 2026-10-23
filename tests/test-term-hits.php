<?php
/**
 * Regression tests for search term occurrence counts.
 *
 * @package Relevanssi
 */

/**
 * Tests occurrence counts independently of database row ordering.
 *
 * @group searching
 */
class TermHitsTest extends PHPUnit\Framework\TestCase {
	/**
	 * Partial variants must accumulate under the original search term.
	 *
	 * @dataProvider partial_match_orders
	 * @param array $counts Occurrence counts in database row order.
	 */
	public function test_partial_matches_accumulate( array $counts ) {
		$term_hits    = array();
		$match_arrays = relevanssi_initialize_match_arrays();
		foreach ( $counts as $count ) {
			$match       = $this->match( 42, array( 'content' => $count ) );
			$match->term = 100 === $count ? 'foobar' : 'fooqux';
			relevanssi_update_term_hits( $term_hits, $match_arrays, $match, 'foo' );
		}
		$this->assertSame( array( 42 => array( 'foo' => 101 ) ), $term_hits );
		$this->assertSame( 101, $match_arrays['body'][42] );
	}

	/**
	 * Provides both orders of the reported 100-plus-one regression.
	 *
	 * @return array Database row orders.
	 */
	public function partial_match_orders(): array {
		return array(
			'rare variant last'   => array( array( 100, 1 ) ),
			'common variant last' => array( array( 1, 100 ) ),
		);
	}

	/**
	 * Every existing field contributes its unweighted occurrences.
	 */
	public function test_all_fields_accumulate() {
		$term_hits     = array();
		$match_arrays  = relevanssi_initialize_match_arrays();
		$fields        = array(
			'title'       => '1',
			'content'     => '2',
			'comment'     => '3',
			'tag'         => '4',
			'link'        => '5',
			'author'      => '6',
			'category'    => '7',
			'excerpt'     => '8',
			'taxonomy'    => '9',
			'customfield' => '10',
			'mysqlcolumn' => '11',
		);
		$match         = $this->match( 42, $fields );
		$match->tf     = 999;
		$match->weight = 9999;

		relevanssi_update_term_hits( $term_hits, $match_arrays, $match, 'foo' );
		$this->assertSame( 66, $term_hits[42]['foo'] );
		relevanssi_update_term_hits( $term_hits, $match_arrays, $match, 'foo' );
		$this->assertSame( 132, $term_hits[42]['foo'] );
		$this->assertSame( 4, $match_arrays['body'][42] );
		$this->assertSame( 2, $match_arrays['title'][42] );
	}

	/**
	 * Site IDs must isolate otherwise identical document IDs.
	 */
	public function test_multisite_counts_remain_independent() {
		$term_hits    = array();
		$match_arrays = relevanssi_initialize_match_arrays();
		relevanssi_update_term_hits( $term_hits, $match_arrays, $this->match( 42, array( 'content' => 100 ) ), 'foo', 2 );
		relevanssi_update_term_hits( $term_hits, $match_arrays, $this->match( 42, array( 'content' => 1 ) ), 'foo', 2 );
		relevanssi_update_term_hits( $term_hits, $match_arrays, $this->match( 42, array( 'content' => 7 ) ), 'foo', 3 );
		$this->assertSame(
			array(
				'2|42' => array( 'foo' => 101 ),
				'3|42' => array( 'foo' => 7 ),
			),
			$term_hits
		);
		$this->assertSame( 101, $match_arrays['body']['2|42'] );
	}

	/**
	 * Creates an index row with all required count fields.
	 *
	 * @param int|string $document_id Document identifier.
	 * @param array      $counts      Nonzero field counts.
	 * @return stdClass The index row.
	 */
	private function match( $document_id, array $counts ): stdClass {
		return (object) array_merge(
			array(
				'doc'         => $document_id,
				'title'       => 0,
				'content'     => 0,
				'comment'     => 0,
				'tag'         => 0,
				'link'        => 0,
				'author'      => 0,
				'category'    => 0,
				'excerpt'     => 0,
				'taxonomy'    => 0,
				'customfield' => 0,
				'mysqlcolumn' => 0,
			),
			$counts
		);
	}
}
