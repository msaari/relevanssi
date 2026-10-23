<?php
/**
 * Optional term-balance scoring, disabled until a site opts in.
 *
 * @package Relevanssi
 */

/**
 * Calculates GM/AM, using a neutral ratio when occurrence data is unusable.
 *
 * @param array<int|float|string|null> $counts Occurrences for every lookup term.
 * @return float Balance in [0, 1], or one for unsupported input.
 */
function relevanssi_term_balance_ratio( array $counts ): float {
	if ( count( $counts ) < 2 ) {
		return 1.0;
	}
	foreach ( $counts as $count ) {
		if ( ! is_numeric( $count ) || ! is_finite( (float) $count ) || $count <= 0 ) {
			return 1.0;
		}
	}
	$maximum         = (float) max( $counts );
	$maximum_log     = log( $maximum );
	$normalized_sum  = 0.0;
	$normalized_logs = 0.0;
	foreach ( $counts as $count ) {
		$normalized_sum  += $count / $maximum;
		$normalized_logs += log( (float) $count ) - $maximum_log;
	}
	$term_count = count( $counts );
	// Normalizing before summation and using logs avoids overflow in both means.
	$balance = exp( $normalized_logs / $term_count - log( $normalized_sum / $term_count ) );
	return max( 0.0, min( 1.0, $balance ) );
}

/**
 * Applies a balance penalty without changing result membership or identifiers.
 *
 * The public opt-in filter supplies the power; the calculation has no side effects.
 *
 * @param array<int|string,float>                                                                $weights Document relevance scores.
 * @param array{operator?:string,terms?:string[],term_hits?:array<int|string,array<string,int>>} $context Scoring context.
 * @param int|float                                                                              $power Penalty exponent. Zero disables the adjustment.
 * @return array<int|string,float> Adjusted document scores.
 */
function relevanssi_apply_term_balance( array $weights, array $context, $power = 0.0 ): array {
	if ( ( ! is_int( $power ) && ! is_float( $power ) ) || ! is_finite( (float) $power ) || $power <= 0 ) {
		return $weights;
	}
	if ( 'AND' !== ( $context['operator'] ?? '' )
		|| ! isset( $context['terms'], $context['term_hits'] )
		|| ! is_array( $context['terms'] )
		|| ! is_array( $context['term_hits'] )
		|| count( $context['terms'] ) < 2 ) {
		return $weights;
	}
	foreach ( $weights as $document_id => $weight ) {
		$term_hits = $context['term_hits'][ $document_id ] ?? array();
		if ( ! is_array( $term_hits ) || ! is_numeric( $weight ) || ! is_finite( (float) $weight ) || $weight <= 0 ) {
			continue;
		}
		$counts = array();
		foreach ( $context['terms'] as $term ) {
			$counts[] = $term_hits[ $term ] ?? null;
		}
		$balance = relevanssi_term_balance_ratio( $counts );
		if ( $balance < 1.0 ) {
			$weights[ $document_id ] = $weight * pow( $balance, $power );
		}
	}
	return $weights;
}

/**
 * Adapts the single-site scoring hook to the shared calculation.
 *
 * @param array<int|string,float> $weights Document relevance scores.
 * @param string                  $query   Search query, retained for hook compatibility.
 * @param array                   $context Context from relevanssi_build_scoring_context().
 * @return array<int|string,float> Adjusted scores.
 */
function relevanssi_balance_search_results( array $weights, string $query, array $context = array() ): array {
	/**
	 * Filters the term-balance penalty exponent for this search execution.
	 *
	 * Zero disables term balancing. Positive values penalize uneven term counts
	 * in multi-term AND searches; two applies the squared GM/AM penalty.
	 *
	 * @param float $power   Exponent; zero by default.
	 * @param array $context Effective operator, lookup terms and occurrence counts.
	 */
	return relevanssi_apply_term_balance( $weights, $context, apply_filters( 'relevanssi_term_balance_power', 0.0, $context ) );
}

/**
 * Adapts the per-site scoring hook to the shared calculation.
 *
 * @param array<int|string,float> $weights Document relevance scores within this site.
 * @param int                     $blog_id Site identifier, retained for hook compatibility.
 * @param array                   $context Context from relevanssi_build_scoring_context().
 * @return array<int|string,float> Adjusted scores.
 */
function relevanssi_balance_site_results( array $weights, int $blog_id, array $context = array() ): array {
	return relevanssi_apply_term_balance( $weights, $context, apply_filters( 'relevanssi_term_balance_power', 0.0, $context ) );
}
