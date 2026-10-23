<?php
/**
 * Scoring hook context regressions.
 *
 * @package Relevanssi
 */

/**
 * Ensures scoring data retains its document and term identities.
 *
 * @group searching
 */
class ScoringContextTest extends PHPUnit\Framework\TestCase {
	/**
	 * Context identifiers must match the scores, including non-post objects.
	 */
	public function test_context_preserves_document_keys_and_lookup_terms() {
		$weights = array(
			42    => 51.0,
			'u_7' => 10.0,
		);
		$counts  = array(
			42    => array(
				'ai'     => 1,
				'expert' => 50,
			),
			'u_7' => array(
				'ai'     => 5,
				'expert' => 5,
			),
		);
		$this->assertSame(
			array(
				'operator'  => 'AND',
				'terms'     => array( 'ai', 'expert' ),
				'term_hits' => $counts,
			),
			relevanssi_build_scoring_context( $weights, $counts, array( 'ai', 'expert', 'ai' ), 'AND' )
		);
	}

	/**
	 * Composite storage keys must never mix counts from different sites.
	 */
	public function test_multisite_context_uses_only_the_current_sites_counts() {
		$weights = array( 42 => 10.0 );
		$counts  = array(
			'2|42' => array( 'ai' => 1 ),
			'3|42' => array( 'ai' => 5 ),
		);
		foreach ( array(
			2 => 1,
			3 => 5,
		) as $blog_id => $expected ) {
			$context = relevanssi_build_scoring_context( $weights, $counts, array( 'ai' ), 'AND', $blog_id );
			$this->assertSame( array( 42 => array( 'ai' => $expected ) ), $context['term_hits'] );
		}
		$this->assertSame( array(), relevanssi_build_scoring_context( array( 99 => 1 ), $counts, array(), 'OR' )['term_hits'] );
	}
}
