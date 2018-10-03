<?php
/**
 * Class FunctionTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi functions.
 */
class FunctionTest extends WP_UnitTestCase {
	/**
	 * Installs Relevanssi.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
	}

	/**
	 * Test phrase recognition.
	 */
	public function test_phrase_recognition() {
		$search_query   = 'Search query "this is a phrase"';
		$phrase_queries = relevanssi_recognize_phrases( $search_query );

		// $phrase_queries should not be empty.
		$this->assertNotEmpty( $phrase_queries );

		$search_query   = 'Search query "notaphrase"';
		$phrase_queries = relevanssi_recognize_phrases( $search_query );

		// $phrase_queries should be empty: one-word phrase is not a phrase.
		$this->assertEmpty( $phrase_queries );
	}

	/**
	 * Test punctuation removal.
	 */
	public function test_punctuation_removal() {
		$string  = 'spaß 1.50 € a–b&c "®"';
		$options = array(
			'hyphens'    => 'replace',
			'quote'      => 'remove',
			'ampersands' => 'keep',
			'decimals'   => 'keep',
		);
		update_option( 'relevanssi_punctuation', $options );

		add_filter( 'relevanssi_punctuation_filter', function( $array ) {
			$array['p'] = 'tr';
			return $array;
		});

		$string_post = relevanssi_remove_punct( $string );
		$this->assertEquals( 'strass 1.50 a b&c', $string_post );
	}

	/**
	 * Test synonyms.
	 */
	public function test_synonyms() {
		$synonyms = 'dog = hound;0123 = numbers;  dog=cat   ;cat=dog';
		update_option( 'relevanssi_synonyms', $synonyms );
		$query_pre  = 'dog 0123 numbers cat';
		$query_post = relevanssi_add_synonyms( $query_pre );
		$this->assertEquals( 'dog 0123 numbers cat hound cat numbers dog', $query_post );
	}

	/**
	 * Uninstalls Relevanssi.
	 */
	public static function wpTearDownAfterClass() {
		if ( function_exists( 'relevanssi_uninstall' ) ) {
			relevanssi_uninstall();
		}
		if ( function_exists( 'relevanssi_uninstall_free' ) ) {
			relevanssi_uninstall_free();
		}
	}
}
