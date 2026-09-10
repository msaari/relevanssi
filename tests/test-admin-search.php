<?php
/**
 * Tests for the Admin Search Tool.
 *
 * @package Relevanssi_Premium
 */

/**
 * Test Admin Search Tool diagnostics.
 */
class AdminSearchTest extends WP_UnitTestCase {
	/**
	 * Tests that query capture records both query types without modifying SQL.
	 */
	public function test_query_capture_records_both_query_types() {
		relevanssi_admin_search_start_query_capture();

		$df_query     = 'SELECT COUNT(*) FROM wp_relevanssi';
		$result_query = 'SELECT * FROM wp_relevanssi';

		$filtered_df_query     = apply_filters( 'relevanssi_df_query_filter', $df_query );
		$filtered_result_query = apply_filters( 'relevanssi_query_filter', $result_query );

		$this->assertSame( $df_query, $filtered_df_query );
		$this->assertStringStartsWith( $result_query, $filtered_result_query );

		$queries = relevanssi_admin_search_stop_query_capture();

		$this->assertSame(
			array(
				array(
					'type' => 'df',
					'sql'  => $filtered_df_query,
				),
				array(
					'type' => 'result',
					'sql'  => $filtered_result_query,
				),
			),
			$queries
		);
		$this->assertFalse( has_filter( 'relevanssi_df_query_filter', 'relevanssi_admin_search_capture_df_query' ) );
		$this->assertFalse( has_filter( 'relevanssi_query_filter', 'relevanssi_admin_search_capture_result_query' ) );
	}

	/**
	 * Tests that the SQL report is flat, compact and escaped.
	 */
	public function test_sql_query_report_is_flat_and_escaped() {
		$report = relevanssi_admin_search_format_sql_queries(
			array(
				array(
					'type' => 'df',
					'sql'  => 'SELECT COUNT(*) WHERE term = "<word>"',
				),
				array(
					'type' => 'result',
					'sql'  => 'SELECT * WHERE term = "search"',
				),
			)
		);

		$this->assertStringContainsString( '<details class="relevanssi-card"', $report );
		$this->assertSame( 1, substr_count( $report, '<details' ) );
		$this->assertStringContainsString( '1 frequency query', $report );
		$this->assertStringContainsString( '1 search query', $report );
		$this->assertStringContainsString( 'Query 1', $report );
		$this->assertStringContainsString( 'Document frequency', $report );
		$this->assertStringContainsString( 'Query 2', $report );
		$this->assertStringContainsString( 'Search results', $report );
		$this->assertStringNotContainsString( '<ol', $report );
		$this->assertStringContainsString( '&lt;word&gt;', $report );
		$this->assertStringNotContainsString( '<word>', $report );
	}
}
