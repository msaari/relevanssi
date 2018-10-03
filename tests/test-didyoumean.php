<?php
/**
 * Class DidYouMeanTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi Did you mean functions.
 */
class DidYouMeanTest extends WP_UnitTestCase {
	/**
	 * Sets up the tests.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();

		// Truncate the index.
		relevanssi_truncate_index();

		$post_count = 10;
		$post_ids   = self::factory()->post->create_many( $post_count );

		foreach ( $post_ids as $id ) {
			// Source: Early Lives of Charlemagne by Eginhard and the Monk of St Gall.
			$content = 'Then that strict lover of truth bade him come out, and said, “I intend you to have the bishopric; but you must be very careful to spend more and make fuller provision for that same long and unreturning journey both for yourself and for me.';

			// Set the post content.
			$args = array(
				'ID'           => $id,
				'post_content' => $content,
			);
			wp_update_post( $args );
		}

		// Rebuild the index.
		relevanssi_build_index( false, false, 200, false );

		update_option( 'relevanssi_log_queries', 'on' );
		$args  = array(
			's' => 'bishopric',
		);
		$query = new WP_Query();
		$query->parse_query( $args );
		for ( $i = 0; $i < 10; $i++ ) {
			$posts = relevanssi_do_query( $query );
		}
	}

	/**
	 * Test Premium Did you mean.
	 */
	public function test_premium_didyoumean() {
		if ( function_exists( 'relevanssi_premium_generate_suggestion' ) ) {
			$corrected = relevanssi_premium_generate_suggestion( 'carful' );
			$this->assertEquals( 'careful', $corrected, 'Missing letter.' );

			$corrected = relevanssi_premium_generate_suggestion( 'funreturning' );
			$this->assertEquals( 'unreturning', $corrected, 'Additional letter.' );

			$corrected = relevanssi_premium_generate_suggestion( 'profision' );
			$this->assertEquals( 'provision', $corrected, 'Switched letter.' );
		}
	}

	/**
	 * Test simple Did you mean.
	 */
	public function test_simple_didyoumean() {
		$corrected = relevanssi_simple_generate_suggestion( 'bishpric' );
		$this->assertEquals( 'bishopric', $corrected, 'Missing letter.' );

		$corrected = relevanssi_simple_generate_suggestion( 'bishhopric' );
		$this->assertEquals( 'bishopric', $corrected, 'Additional letter.' );

		$corrected = relevanssi_simple_generate_suggestion( 'fishopric' );
		$this->assertEquals( 'bishopric', $corrected, 'Switched letter.' );
	}
}
