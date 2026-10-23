<?php
/**
 * Unit tests for built-in opt-in term balance.
 *
 * @package Relevanssi
 */

/**
 * Exercises the balance calculation independently of the search pipeline.
 *
 * @group searching
 */
class TermBalanceTest extends PHPUnit\Framework\TestCase {
	/**
	 * Both hooks must honor the same contextual opt-in and revert when removed.
	 */
	public function test_power_filter_controls_both_scoring_hooks() {
		$weights      = array( 42 => 51.0 );
		$context      = array(
			'operator'  => 'AND',
			'terms'     => array( 'ai', 'expert' ),
			'term_hits' => array(
				42 => array(
					'ai'     => 1,
					'expert' => 50,
				),
			),
		);
		$power_filter = function ( $power, $actual_context ) use ( $context ) {
			$this->assertSame( 0.0, $power );
			$this->assertSame( $context, $actual_context );
			return 1.0;
		};
		$this->assertSame( $weights, relevanssi_balance_search_results( $weights, 'ai expert', $context ) );
		add_filter( 'relevanssi_term_balance_power', $power_filter, 10, 2 );
		try {
			$this->assertEquals( 2 * sqrt( 50 ), relevanssi_balance_search_results( $weights, 'ai expert', $context )[42], '', 1e-12 );
			$this->assertEquals( 2 * sqrt( 50 ), relevanssi_balance_site_results( $weights, 2, $context )[42], '', 1e-12 );
		} finally {
			remove_filter( 'relevanssi_term_balance_power', $power_filter );
		}
		$this->assertSame( $weights, relevanssi_balance_site_results( $weights, 2, $context ) );
	}


	/**
	 * Balance must depend on distribution rather than term order or scale.
	 *
	 * @dataProvider occurrence_distributions
	 * @param array $counts   Occurrence counts.
	 * @param float $expected Expected GM/AM.
	 */
	public function test_balance_ratio( array $counts, float $expected ) {
		$this->assertEquals( $expected, relevanssi_term_balance_ratio( $counts ), '', 1e-12 );
	}

	/**
	 * Supplies representative balanced and skewed distributions.
	 *
	 * @return array Distributions with independently calculated expectations.
	 */
	public function occurrence_distributions(): array {
		return array(
			'equal'            => array( array( 5, 5, 5 ), 1.0 ),
			'two terms'        => array( array( 1, 50 ), sqrt( 50 ) / 25.5 ),
			'three terms'      => array( array( 1, 1, 50 ), pow( 50, 1 / 3 ) / ( 52 / 3 ) ),
			'permuted'         => array( array( 50, 1, 1 ), pow( 50, 1 / 3 ) / ( 52 / 3 ) ),
			'scaled'           => array( array( 100, 5000 ), sqrt( 50 ) / 25.5 ),
			'large counts'     => array( array( 1e300, 1e300, 1e300 ), 1.0 ),
			'large unequal'    => array( array( 1e299, 5e300 ), sqrt( 50 ) / 25.5 ),
			'database strings' => array( array( '1', '50' ), sqrt( 50 ) / 25.5 ),
		);
	}

	/**
	 * The penalty retains identifiers, invalid documents and equal-count scores.
	 */
	public function test_penalty_preserves_membership_and_changes_only_valid_scores() {
		$weights = array(
			42    => 51.0,
			'u_7' => 10.0,
			99    => 20.0,
			100   => 9.0,
		);
		$context = array(
			'operator'  => 'AND',
			'terms'     => array( 'ai', 'expert' ),
			'term_hits' => array(
				42    => array(
					'ai'     => 1,
					'expert' => 50,
				),
				'u_7' => array(
					'ai'     => 5,
					'expert' => 5,
				),
				99    => array(
					'ai'     => 0,
					'expert' => 20,
				),
			),
		);
		$actual  = relevanssi_apply_term_balance( $weights, $context, 2.0 );
		$this->assertSame( $weights, relevanssi_apply_term_balance( $weights, $context ) );
		$this->assertSame( array_keys( $weights ), array_keys( $actual ) );
		$this->assertEquals( 200 / 51, $actual[42], '', 1e-12 );
		$this->assertSame( 10.0, $actual['u_7'] );
		$this->assertSame( 20.0, $actual[99] );
		$this->assertSame( 9.0, $actual[100] );
		$this->assertSame( $weights, relevanssi_apply_term_balance( $weights, $context, 0 ) );
	}

	/**
	 * Incomplete counts must not penalize a document accidentally.
	 *
	 * @dataProvider invalid_counts
	 * @param array $counts Invalid occurrence vector.
	 */
	public function test_invalid_counts_are_neutral( array $counts ) {
		$this->assertSame( 1.0, relevanssi_term_balance_ratio( $counts ) );
	}

	/**
	 * Supplies unusable occurrence data.
	 *
	 * @return array Invalid vectors and unsupported term counts.
	 */
	public function invalid_counts(): array {
		return array(
			array( array() ),
			array( array( 5 ) ),
			array( array( 0, 5 ) ),
			array( array( -1, 5 ) ),
			array( array( null, 5 ) ),
			array( array( 'invalid', 5 ) ),
			array( array( INF, 5 ) ),
			array( array( NAN, 5 ) ),
			array( array( true, 5 ) ),
		);
	}

	/**
	 * Misconfiguration must not change ranking.
	 *
	 * @dataProvider invalid_powers
	 * @param int|float|string|null $power Invalid configuration.
	 */
	public function test_invalid_power_is_neutral( $power ) {
		$weights = array( 42 => 51.0 );
		$context = array(
			'operator'  => 'AND',
			'terms'     => array( 'ai', 'expert' ),
			'term_hits' => array(
				42 => array(
					'ai'     => 1,
					'expert' => 50,
				),
			),
		);
		$this->assertSame( $weights, relevanssi_apply_term_balance( $weights, $context, $power ) );
	}

	/**
	 * Supplies unsupported penalty settings.
	 *
	 * @return array Invalid powers.
	 */
	public function invalid_powers(): array {
		return array( array( -1 ), array( INF ), array( NAN ), array( 'invalid' ), array( null ) );
	}

	/**
	 * OR, old hook versions and searches without multiple terms remain unchanged.
	 */
	public function test_unsupported_searches_are_neutral() {
		$weights = array( 42 => 51.0 );
		foreach ( array(
			array(),
			array(
				'operator' => 'OR',
				'terms'    => array( 'ai', 'expert' ),
			),
			array(
				'operator' => 'AND',
				'terms'    => array( 'ai' ),
			),
		) as $context ) {
			$this->assertSame( $weights, relevanssi_apply_term_balance( $weights, $context, 2.0 ) );
		}
		$this->assertSame( $weights, relevanssi_balance_search_results( $weights, 'ai expert' ) );
		$this->assertSame( $weights, relevanssi_balance_site_results( $weights, 2 ) );
	}
}
