<?php
/**
 * Class DidYouMeanTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi Did you mean functions.
 *
 */
class DidYouMeanTest extends WP_UnitTestCase {
	/**
	 * Sets up the tests.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();

		// Truncate the index.
		relevanssi_truncate_index();

		$post_count = 10;
		$post_ids   = self::factory()->post->create_many( $post_count );

		// Source: Early Lives of Charlemagne by Eginhard and the Monk of St Gall.
		$content = 'Then that strict lover of truth bade him come out, and said, “I intend you to have the bishopric; but you must be very careful to spend more and make fuller provision for that same long and unreturning journey both for yourself and for me.';

		/**
		 * Update the relevanssi_words option with the words, because it's now
		 * updated as an ajax action and that doesn't work in the testing
		 * context.
		 *
		 * @since 2.5.0
		 */
		$content_tokenized = relevanssi_tokenize( $content, true, -1, 'indexing' );
		update_option(
			'relevanssi_words',
			array(
				'words'  => $content_tokenized,
				'expire' => time() + 10000,
			)
		);

		foreach ( $post_ids as $id ) {

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
			relevanssi_do_query( $query );
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
}
