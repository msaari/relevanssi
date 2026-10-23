<?php
/**
 * Search hook context regressions.
 *
 * @package Relevanssi
 */

/**
 * Ensures scoring context follows the actual search pipeline.
 *
 * @group searching
 */
class ScoringContextSearchTest extends WP_UnitTestCase {
	/**
	 * Each search test class owns its required schema.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
	}

	/**
	 * The existing hook must receive processed terms without breaking old callbacks.
	 */
	public function test_results_hook_exposes_accumulated_counts() {
		update_option( 'relevanssi_fuzzy', 'always' );
		update_option( 'relevanssi_implicit_operator', 'AND' );
		update_option( 'relevanssi_min_word_length', 2 );
		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'Fixture',
				'post_content' => 'qzxalpha qzxalpha omegaqzx expert expert',
				'post_status'  => 'publish',
			)
		);
		relevanssi_index_doc( $post_id, true );
		$observed = array();
		$legacy   = function ( $weights, $query ) use ( &$observed ) {
			$observed['query'] = $query;
			return $weights;
		};
		$capture  = function ( $weights, $query, $context = null ) use ( &$observed ) {
			$observed['context'] = $context;
			return $weights;
		};
		add_filter( 'relevanssi_results', $legacy, 15, 2 );
		add_filter( 'relevanssi_results', $capture, 20, 3 );
		try {
			$query = new WP_Query();
			$query->parse_query(
				array(
					's'              => 'qzx expert qzx -excludedterm',
					'post__in'       => array( $post_id ),
					'posts_per_page' => -1,
				)
			);
			relevanssi_do_query( $query );
			$this->assertSame( 'qzx expert qzx -excludedterm', $observed['query'] );
			$this->assertSame( 'AND', $observed['context']['operator'] );
			$lookup_terms = $observed['context']['terms'];
			sort( $lookup_terms );
			$this->assertSame( array( 'expert', 'qzx' ), $lookup_terms );
			$this->assertSame(
				array(
					'qzx'    => 3,
					'expert' => 2,
				),
				$observed['context']['term_hits'][ $post_id ]
			);
		} finally {
			remove_filter( 'relevanssi_results', $legacy, 15 );
			remove_filter( 'relevanssi_results', $capture, 20 );
		}
	}
}
