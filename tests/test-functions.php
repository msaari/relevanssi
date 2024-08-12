<?php
/**
 * Class FunctionTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi functions.
 *
 * @group functions
 */
class FunctionTest extends WP_UnitTestCase {

	/**
	 * Installs Relevanssi.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
	}

	/**
	 * Test phrase recognition.
	 */
	public function test_phrase_recognition() {
		update_option( 'relevanssi_index_excerpt', 'off' );

		$search_query   = 'Search query "this is a phrase"';
		$phrase_queries = relevanssi_recognize_phrases( $search_query );

		// $phrase_queries should not be empty.
		$this->assertNotEmpty( $phrase_queries );
		$this->assertFalse( stripos( $phrase_queries['and'], 'post_excerpt' ) );
		$this->assertNotEmpty( $phrase_queries['or']['this is a phrase'] );

		update_option( 'relevanssi_index_excerpt', 'on' );

		$phrase_queries = relevanssi_recognize_phrases( $search_query, 'BOGUS_OPERATOR' );

		$this->assertNotFalse( stripos( $phrase_queries['and'], 'post_excerpt' ) );

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
			'quotes'     => 'remove',
			'ampersands' => 'keep',
			'decimals'   => 'keep',
		);
		update_option( 'relevanssi_punctuation', $options );

		add_filter(
			'relevanssi_punctuation_filter',
			function ( $punctuation_array ) {
				$punctuation_array['p'] = 'tr';
				return $punctuation_array;
			}
		);

		$string_post = relevanssi_remove_punct( $string );
		$this->assertEquals( 'strass 1.50 a b&c', $string_post );

		$this->assertEquals( '', relevanssi_remove_punct( new StdClass() ) );

		$options = array(
			'hyphens'    => 'remove',
			'quotes'     => 'remove',
			'ampersands' => 'remove',
			'decimals'   => 'remove',
		);
		update_option( 'relevanssi_punctuation', $options );

		$this->assertEquals( '', relevanssi_remove_punct( '-"&' ) );

		$options = array(
			'hyphens'    => 'keep',
			'quotes'     => 'remove',
			'ampersands' => 'remove',
			'decimals'   => 'remove',
		);
		update_option( 'relevanssi_punctuation', $options );

		$this->assertEquals( '-–—', relevanssi_remove_punct( '-–—' ) );
	}

	/**
	 * Test synonyms.
	 */
	public function test_synonyms() {
		$synonyms = 'dog = hound;0123 = numbers;  dog=cat   ;cat=dog';
		update_option( 'relevanssi_synonyms', array( relevanssi_get_current_language() => $synonyms ) );
		$query_pre  = 'dog 0123 numbers cat';
		$query_post = relevanssi_add_synonyms( $query_pre );
		$this->assertEquals( 'dog 0123 numbers cat hound cat numbers dog', $query_post );
	}

	/**
	 * Test relevanssi_taxonomy_score().
	 */
	public function test_taxonomy_score() {
		$post_type_weights = array(
			'post_tagged_with_category' => 10,
		);

		$match                  = new StdClass();
		$match->taxonomy_detail = '{"category":1}';

		relevanssi_taxonomy_score( $match, $post_type_weights );
		$this->assertEquals( 10, $match->taxonomy_score );

		$post_type_weights      = array();
		$match                  = new StdClass();
		$match->taxonomy_detail = '{"category":1}';

		relevanssi_taxonomy_score( $match, $post_type_weights );
		$this->assertEquals( 1, $match->taxonomy_score );
	}

	/**
	 * Test relevanssi_generate_term_where().
	 */
	public function test_generate_term_where() {
		$term = 'tavernen';
		update_option( 'relevanssi_fuzzy', 'always' );

		$this->assertEquals(
			"(relevanssi.term LIKE 'tavernen%' OR relevanssi.term_reverse LIKE CONCAT(REVERSE('tavernen'), '%')) ",
			relevanssi_generate_term_where( $term )
		);

		$term = ' 1234';
		update_option( 'relevanssi_fuzzy', 'never' );

		$this->assertEquals(
			" relevanssi.term = '1234' ",
			relevanssi_generate_term_where( $term )
		);

		$term = 'a';
		update_option( 'relevanssi_fuzzy', 'always' );

		$this->assertEquals(
			null,
			relevanssi_generate_term_where( $term )
		);

		add_filter(
			'relevanssi_block_one_letter_searches',
			function () {
				return false;
			}
		);
		$this->assertEquals( " relevanssi.term = 'a' ", relevanssi_generate_term_where( $term ) );
	}

	/**
	 * Test relevanssi_get_negative_post_type().
	 */
	public function test_get_negative_post_type() {
		update_option( 'relevanssi_index_post_types', array( 'revision', 'custom_css', 'attachment' ) );
		update_option( 'relevanssi_respect_exclude', 'on' );
		$this->assertEquals(
			"'attachment', 'revision', 'custom_css'",
			relevanssi_get_negative_post_type( 'off' ),
			'With respect_exclude on and revision and custom_css included in index, this should return attachment, revision and custom_css.'
		);
		$this->assertEquals(
			"'revision', 'custom_css'",
			relevanssi_get_negative_post_type( 'include_attachments' ),
			'With respect_exclude on and attachments included in search, this should return revision and custom_css.'
		);

		set_current_screen( 'edit-post' );
		$this->assertEquals(
			"'attachment'",
			relevanssi_get_negative_post_type( 'off' ),
			'When in admin and respect_exclude on and attachments not included in search, this should return attachment.'
		);
		$this->assertEmpty(
			relevanssi_get_negative_post_type( 'include_attachments' ),
			'When in admin and respect_exclude on and attachments included in search, this should return nothing.'
		);

		unset( $GLOBALS['current_screen'] );
		update_option( 'relevanssi_respect_exclude', 'off' );
		$this->assertEquals(
			"'attachment'",
			relevanssi_get_negative_post_type( 'off' ),
			'With respect_exclude off and attachments not included in search, this should return attachment.'
		);
		$this->assertEmpty(
			relevanssi_get_negative_post_type( 'include_attachments' ),
			'With respect_exclude off and attachments included in search, this should return nothing.'
		);
	}

	/**
	 * Test relevanssi_limit_filter().
	 */
	public function test_limit_filter() {
		update_option( 'relevanssi_throttle', 'on' );
		update_option( 'relevanssi_throttle_limit', 42 );
		$this->assertEquals(
			' ORDER BY tf DESC LIMIT 42',
			relevanssi_limit_filter( '' ),
			'Limit should be set.'
		);

		update_option( 'relevanssi_throttle', 'off' );
		update_option( 'relevanssi_throttle_limit', 42 );
		$this->assertEquals(
			' ORDER BY tf DESC',
			relevanssi_limit_filter( '' ),
			'No limit when the throttle is off.'
		);

		update_option( 'relevanssi_throttle', 'on' );
		update_option( 'relevanssi_throttle_limit', -21 );
		$this->assertEquals(
			' ORDER BY tf DESC LIMIT 500',
			relevanssi_limit_filter( '' ),
			'Value less than zero should become 500.'
		);

		update_option( 'relevanssi_throttle', 'on' );
		update_option( 'relevanssi_throttle_limit', 'string value' );
		$this->assertEquals(
			' ORDER BY tf DESC LIMIT 500',
			relevanssi_limit_filter( '' ),
			'Non-numeric value should become 500.'
		);
	}

	/**
	 * Test relevanssi_process_post_query().
	 */
	public function test_process_post_query() {
		$this->assertEquals(
			' AND relevanssi.doc NOT IN (4,5,6) AND relevanssi.doc IN (1,2,3)',
			relevanssi_process_post_query(
				array(
					'in'     => array( 1, 2, 3 ),
					'not in' => array( 4, 5, 6 ),
				)
			)
		);

		$this->assertEquals(
			' AND relevanssi.doc NOT IN (1,2,3)',
			relevanssi_process_post_query(
				array(
					'in'     => array( 1, 2, 3 ),
					'not in' => array( 1, 2, 3 ),
				)
			)
		);
	}

	/**
	 * Test relevanssi_process_parent_query().
	 */
	public function test_process_parent_query() {
		global $wpdb;

		$this->assertEquals(
			" AND relevanssi.doc NOT IN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_parent IN (4,5,6)) AND relevanssi.doc IN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_parent IN (1,2,3))",
			relevanssi_process_parent_query(
				array(
					'parent in'     => array( 1, 2, 3 ),
					'parent not in' => array( 4, 5, 6 ),
				)
			)
		);

		$this->assertEquals(
			" AND relevanssi.doc NOT IN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_parent IN (1,2,3))",
			relevanssi_process_parent_query(
				array(
					'parent in'     => array( 1, 2, 3 ),
					'parent not in' => array( 1, 2, 3 ),
				)
			)
		);
	}

	/**
	 * Test relevanssi_process_meta_query().
	 */
	public function test_process_meta_query() {
		global $wpdb;

		$meta_query = array(
			array(
				'key'   => 'color',
				'value' => 'red',
			),
		);

		$processed_meta = relevanssi_process_meta_query( $meta_query );
		$this->assertDiscardWhitespace(
			"AND (( {$wpdb->prefix}postmeta.meta_key = 'color' AND {$wpdb->prefix}postmeta.meta_value = 'red' ))",
			$processed_meta['where']
		);
		$this->assertDiscardWhitespace(
			"INNER JOIN {$wpdb->prefix}postmeta ON (relevanssi.doc={$wpdb->prefix}postmeta.post_id)",
			$processed_meta['join']
		);

		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'     => 'color',
				'value'   => 'blue',
				'compare' => 'NOT LIKE',
			),
			array(
				'key'     => 'price',
				'value'   => array( 20, 100 ),
				'type'    => 'numeric',
				'compare' => 'BETWEEN',
			),
		);

		$processed_meta = relevanssi_process_meta_query( $meta_query );
		$this->assertDiscardWhitespace(
			"AND (({$wpdb->prefix}postmeta.meta_key='color'
			 AND {$wpdb->prefix}postmeta.meta_value NOT LIKE '%blue%')
			 OR (mt1.meta_key='price' AND CAST(mt1.meta_value ASSIGNED) BETWEEN '20' AND '100' ))",
			$wpdb->remove_placeholder_escape( $processed_meta['where'] )
		);
		$this->assertDiscardWhitespace(
			"INNER JOIN {$wpdb->prefix}postmeta ON (relevanssi.doc={$wpdb->prefix}postmeta.post_id)
			 INNER JOIN {$wpdb->prefix}postmeta AS mt1 ON (relevanssi.doc=mt1.post_id)",
			$processed_meta['join']
		);

		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'     => 'color',
				'value'   => 'orange',
				'compare' => '=',
			),
			array(
				'relation' => 'AND',
				array(
					'key'     => 'color',
					'value'   => 'red',
					'compare' => '=',
				),
				array(
					'key'     => 'size',
					'value'   => 'small',
					'compare' => '=',
				),
			),
		);

		$processed_meta = relevanssi_process_meta_query( $meta_query );
		$this->assertDiscardWhitespace(
			"AND (({$wpdb->prefix}postmeta.meta_key='color'
			 AND {$wpdb->prefix}postmeta.meta_value='orange')
			 OR ((mt1.meta_key='color' AND mt1.meta_value='red')
			 AND (mt2.meta_key='size' AND mt2.meta_value='small')))",
			$wpdb->remove_placeholder_escape( $processed_meta['where'] )
		);
		$this->assertDiscardWhitespace(
			"INNER JOIN {$wpdb->prefix}postmeta ON (relevanssi.doc={$wpdb->prefix}postmeta.post_id)
			 INNER JOIN {$wpdb->prefix}postmetaASmt1 ON (relevanssi.doc=mt1.post_id)
			 INNER JOIN {$wpdb->prefix}postmetaASmt2 ON (relevanssi.doc=mt2.post_id)",
			$processed_meta['join']
		);

		$meta_query = array(
			array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => 1,
				'operator' => 'EXISTS',
			),
			array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => 'games',
				'operator' => 'NOT EXISTS',
			),
		);

		$processed_meta = relevanssi_process_meta_query( $meta_query );
		$this->assertEmpty( $processed_meta['where'] );
		$this->assertEmpty( $processed_meta['join'] );
	}

	/**
	 * Test relevanssi_process_date_query().
	 */
	public function test_process_date_query() {
		global $wpdb;

		$date_query = array(
			array(
				'year'  => 2012,
				'month' => 12,
				'day'   => 12,
			),
		);

		$this->assertDiscardWhitespace(
			" AND relevanssi.doc IN ( SELECT DISTINCT(ID) FROM {$wpdb->prefix}posts WHERE 1  AND (
				( YEAR( {$wpdb->prefix}posts.post_date ) = 2012 AND MONTH( {$wpdb->prefix}posts.post_date ) = 12 AND DAYOFMONTH( wptests_posts.post_date ) = 12 )
			  ) )",
			relevanssi_process_date_query( new WP_Date_Query( $date_query ) )
		);

		$date_query = array(
			array(
				'hour'    => 9,
				'compare' => '>=',
			),
			array(
				'hour'    => 17,
				'compare' => '<=',
			),
			array(
				'dayofweek' => array( 2, 6 ),
				'compare'   => 'BETWEEN',
			),
		);

		$this->assertDiscardWhitespace(
			"AND relevanssi.doc IN (SELECT DISTINCT(ID) FROM {$wpdb->prefix}posts WHERE 1
			AND (HOUR({$wpdb->prefix}posts.post_date) >= 9
			AND HOUR ({$wpdb->prefix}posts.post_date) <= 17
			AND DAYOFWEEK({$wpdb->prefix}posts.post_date) BETWEEN 2 AND 6))",
			relevanssi_process_date_query( new WP_Date_Query( $date_query ) )
		);
	}

	/**
	 * Test relevanssi_process_expost().
	 */
	public function test_process_expost() {
		$this->assertEquals(
			' AND relevanssi.doc NOT IN (1,2,3,4)',
			relevanssi_process_expost( '1,2,3,4' )
		);
	}

	/**
	 * Test relevanssi_process_author().
	 */
	public function test_process_author() {
		global $wpdb;
		$this->assertDiscardWhitespace(
			"AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
		 	WHERE posts.post_author IN (1,2))
			AND relevanssi.doc NOT IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_author IN (3,4))",
			relevanssi_process_author( array( '1', '2', '-3', '-4' ) )
		);
	}

	/**
	 * Test relevanssi_process_by_date().
	 */
	public function test_process_by_date() {
		global $wpdb;

		$this->assertDiscardWhitespace(
			"AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_date > DATE_SUB(NOW(), INTERVAL 24 HOUR))",
			relevanssi_process_by_date( '24h' )
		);

		$this->assertDiscardWhitespace(
			"AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_date > DATE_SUB(NOW(), INTERVAL 7 DAY))",
			relevanssi_process_by_date( '7d' )
		);

		$this->assertDiscardWhitespace(
			"AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_date > DATE_SUB(NOW(), INTERVAL 2 WEEK))",
			relevanssi_process_by_date( '2w' )
		);

		$this->assertDiscardWhitespace(
			"AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_date > DATE_SUB(NOW(), INTERVAL 6 MONTH))",
			relevanssi_process_by_date( '6m' )
		);

		$this->assertDiscardWhitespace(
			"AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_date > DATE_SUB(NOW(), INTERVAL 12 YEAR))",
			relevanssi_process_by_date( '12y' )
		);

		$this->assertDiscardWhitespace(
			"AND relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_date > DATE_SUB(NOW(), INTERVAL 42 DAY))",
			relevanssi_process_by_date( '42' )
		);
	}

	/**
	 * Test relevanssi_get_recency_bonus().
	 */
	public function test_get_recency_bonus() {
		if ( RELEVANSSI_PREMIUM ) {
			$bonus_before_test = get_option( 'relevanssi_recency_bonus' );

			$days = 42;

			$bonus['bonus'] = '4.2';
			$bonus['days']  = $days;
			update_option( 'relevanssi_recency_bonus', $bonus );

			$bonus_value = relevanssi_get_recency_bonus();
			$this->assertEquals(
				4.2,
				$bonus_value['bonus'],
				'Recency bonus value should be correct.'
			);
			$this->assertEquals(
				time() - DAY_IN_SECONDS * $days,
				$bonus_value['cutoff'],
				'Recency bonus value should be correct.'
			);

			update_option( 'relevanssi_recency_bonus', $bonus_before_test );
		} else {
			$this->assertTrue( true );
		}
	}

	/**
	 * Test relevanssi_process_post_type().
	 */
	public function test_process_post_type() {
		global $wpdb;

		$is_admin            = false;
		$include_attachments = false;

		$this->assertDiscardWhitespace(
			"AND (relevanssi.doc IN (
			SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_type IN ('post', 'page', 'attachment')
			))",
			relevanssi_process_post_type(
				'post,page,attachment',
				$is_admin,
				$include_attachments
			),
			'Should produce correct MySQL for a string parameter.'
		);

		$this->assertDiscardWhitespace(
			"AND (relevanssi.doc IN (
			SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
			WHERE posts.post_type IN ('post', 'page', 'attachment')
			))",
			relevanssi_process_post_type(
				array( 'post', 'page', 'attachment' ),
				$is_admin,
				$include_attachments
			),
			'Should produce correct MySQL for an array parameter.'
		);

		if ( RELEVANSSI_PREMIUM ) {
			$this->assertDiscardWhitespace(
				"AND (relevanssi.doc IN (
				SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts
				WHERE posts.post_type IN ('post', 'page', 'attachment'))
				OR (relevanssi.type IN ('user')))",
				relevanssi_process_post_type(
					array( 'post', 'page', 'attachment', 'user' ),
					$is_admin,
					$include_attachments
				),
				'Should produce correct MySQL for "user" parameter.'
			);
		}
	}

	/**
	 * Test getting custom fields
	 */
	public function test_get_custom_fields() {
		update_option( 'relevanssi_index_fields', 'all' );
		$this->assertEquals( 'all', relevanssi_get_custom_fields() );

		update_option( 'relevanssi_index_fields', 'foo, bar,baz, bum' );
		$this->assertEquals(
			array( 'foo', 'bar', 'baz', 'bum' ),
			relevanssi_get_custom_fields()
		);
	}

	/**
	 * Test Yoast SEO indexing filter.
	 */
	public function test_wordpress_seo_indexing() {
		$post_id = self::factory()->post->create();
		require_once dirname( __DIR__ ) . '/lib/compatibility/yoast-seo.php';
		$this->assertFalse( relevanssi_yoast_noindex( false, $post_id ) );
		update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', '1' );
		$this->assertEquals( 'Yoast SEO', relevanssi_yoast_noindex( false, $post_id ) );
		update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', '2' );
		$this->assertFalse( relevanssi_yoast_noindex( false, $post_id ) );
	}

	/**
	 * Test post status processing.
	 */
	public function test_post_status_processing() {
		global $wpdb, $relevanssi_admin_test;

		$this->assertDiscardWhitespace(
			"AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts	WHERE posts.post_status IN ('publish', 'pending'))) OR (doc = -1))",
			relevanssi_process_post_status( array( 'publish', 'pending' ) )
		);

		$this->assertDiscardWhitespace(
			"AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts	WHERE posts.post_status IN ('pending'))) OR (doc = -1))",
			relevanssi_process_post_status( 'pending' )
		);

		$relevanssi_admin_test = true;

		$this->assertDiscardWhitespace(
			"AND ((relevanssi.doc IN (SELECT DISTINCT(posts.ID) FROM {$wpdb->prefix}posts AS posts	WHERE posts.post_status IN ('publish', 'pending'))))",
			relevanssi_process_post_status( array( 'publish', 'pending' ) )
		);

		$relevanssi_admin_test = false;
	}

	/**
	 * Test phrase synonyms.
	 */
	public function test_phrase_synonyms() {
		$language              = relevanssi_get_current_language();
		$synonyms[ $language ] = 'campbell=roborowski;mouse=mice';
		update_option( 'relevanssi_synonyms', $synonyms );

		add_filter( 'relevanssi_phrase_synonyms', '__return_true' );

		$query = '"campbell hamster"';
		$this->assertEquals(
			'"campbell hamster" "roborowski hamster"',
			relevanssi_add_synonyms( $query )
		);

		$query = '"campbellian hamster"';
		$this->assertEquals(
			'"campbellian hamster"',
			relevanssi_add_synonyms( $query )
		);

		$query = 'mouse "campbell hamster"';
		$this->assertEquals(
			'mouse mice "campbell hamster" "roborowski hamster"',
			relevanssi_add_synonyms( $query )
		);

		remove_filter( 'relevanssi_phrase_synonyms', '__return_true' );
		add_filter( 'relevanssi_phrase_synonyms', '__return_false' );

		$query = '"campbell hamster"';
		$this->assertEquals(
			'"campbell hamster"',
			relevanssi_add_synonyms( $query )
		);

		$query = 'mouse "campbell hamster"';
		$this->assertEquals(
			'mouse mice "campbell hamster"',
			relevanssi_add_synonyms( $query )
		);
	}

	/**
	 * Test utility functions.
	 */
	public function test_utility_functions() {
		$this->assertEquals( "'string'", relevanssi_add_apostrophes( 'string' ) );
		$this->assertEquals( '"string"', relevanssi_add_quotes( 'string' ) );

		$sums   = array( 10, 20, 30, 40 );
		$counts = array( 1, 2, 3, 4 );
		relevanssi_average_array( $sums, $counts );
		$this->assertEquals( array( 10, 10, 10, 10 ), $sums );

		$request['some_option'] = '10';
		$this->assertEquals( 10, relevanssi_intval( $request, 'some_option' ) );
		$this->assertEquals( null, relevanssi_intval( $request, 'none_option' ) );

		$request['array_option'] = array( 'a', 'b', 'c', 'd' );
		$this->assertEquals( 'a,b,c,d', relevanssi_implode( $request, 'array_option', ',' ) );
		$this->assertEquals( '', relevanssi_implode( $request, 'some_option', ',' ) );

		$this->assertEquals( '10', relevanssi_legal_value( $request, 'some_option', array( '10' ), 'a' ) );
		$this->assertEquals( 'a', relevanssi_legal_value( $request, 'some_option', array( '20' ), 'a' ) );
		$this->assertEquals( null, relevanssi_legal_value( $request, 'none_option', array( '10' ), 'a' ) );

		$request['on_off'] = 'on';
		$this->assertEquals( 'on', relevanssi_off_or_on( $request, 'on_off' ) );
		$this->assertEquals( 'on', relevanssi_off_or_on( $request, 'some_option' ) );
		$this->assertEquals( 'off', relevanssi_off_or_on( $request, 'none_option' ) );

		$options = array( 'enabled' => 'on' );
		$target  = array(
			'enabled'  => 'on',
			'disabled' => 'off',
			'another'  => 'off',
		);
		relevanssi_turn_off_options( $options, array( 'disabled', 'another' ) );
		$this->assertEquals( $target, $options );

		$this->assertEquals( 'off', relevanssi_return_off() );

		$this->assertEquals( '', relevanssi_sanitize_hex_color( '' ) );
		$this->assertEquals( '#242424', relevanssi_sanitize_hex_color( '242424' ) );
		$this->assertEquals( '', relevanssi_sanitize_hex_color( '242424242424' ) );

		$this->assertEquals( '', relevanssi_strip_all_tags( array() ) );
		$this->assertEquals( '10', relevanssi_strip_invisibles( 10 ) );
		$this->assertEquals( '10', relevanssi_strip_tags( 10 ) );
		$this->assertEquals( 2, relevanssi_strlen( 10 ) );
		$this->assertEquals( '10', relevanssi_substr( 100, 0, 2 ) );

		$request['float_option'] = '4.2';
		relevanssi_update_floatval( $request, 'float_option', true, 0, true );
		$this->assertEquals( 4.2, get_option( 'float_option' ) );

		$request['float_option'] = '-4.2';
		relevanssi_update_floatval( $request, 'float_option', true, 3, true );
		$this->assertEquals( 3, get_option( 'float_option' ) );

		$request['float_option'] = array();
		relevanssi_update_floatval( $request, 'float_option', true, 3, true );
		$this->assertEquals( 3, get_option( 'float_option' ) );

		$request['int_option'] = '42';
		relevanssi_update_intval( $request, 'int_option', true, 3, true );
		$this->assertEquals( 42, get_option( 'int_option' ) );

		$request['int_option'] = array();
		relevanssi_update_intval( $request, 'int_option', true, 3, true );
		$this->assertEquals( 3, get_option( 'int_option' ) );

		$request['legal_option'] = 'bar';
		relevanssi_update_legal_value( $request, 'legal_option', array( 'foo', 'bar', 'baz' ), 'foo', true );
		$this->assertEquals( 'bar', get_option( 'legal_option' ) );

		$request['legal_option'] = array();
		relevanssi_update_legal_value( $request, 'legal_option', array( 'foo', 'bar', 'baz' ), 'foo', true );
		$this->assertEquals( 'foo', get_option( 'legal_option' ) );

		$request['onoff_option'] = 'on';
		relevanssi_update_off_or_on( $request, 'onoff_option', true );
		$this->assertEquals( 'on', get_option( 'onoff_option' ) );

		$request['onoff_option'] = array();
		relevanssi_update_off_or_on( $request, 'onoff_option', true );
		$this->assertEquals( 'off', get_option( 'onoff_option' ) );

		$request['sanitized_option'] = '<h2>Test string</h2>';
		relevanssi_update_sanitized( $request, 'sanitized_option', true );
		$this->assertEquals( 'Test string', get_option( 'sanitized_option' ) );
	}

	/**
	 * Test relevanssi_close_tags.
	 */
	public function test_relevanssi_close_tags() {
		$html = '<meta><p>This is a <b>test</b>.';
		$this->assertEquals(
			'<meta><p>This is a <b>test</b>.</p>',
			relevanssi_close_tags( $html )
		);
	}

	/**
	 * Test relevanssi_from_and_to.
	 */
	public function test_relevanssi_from_and_to() {
		$request = array(
			'from' => '2022-01-01',
			'to'   => '2022-01-09',
		);

		$this->assertEquals(
			array(
				'from' => '2022-01-01',
				'to'   => '2022-01-09',
			),
			relevanssi_from_and_to( $request, '2021-12-31' )
		);

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( 'first day of january this year' ) ),
				'to'   => gmdate( 'Y-m-d' ),
			),
			relevanssi_from_and_to( array( 'this_year' => true ), '' )
		);

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( 'first day of this month' ) ),
				'to'   => gmdate( 'Y-m-d' ),
			),
			relevanssi_from_and_to( array( 'this_month' => true ), '' )
		);

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( 'first day of previous month' ) ),
				'to'   => gmdate( 'Y-m-d', strtotime( 'last day of previous month' ) ),
			),
			relevanssi_from_and_to( array( 'last_month' => true ), '' )
		);

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( 'previous monday' ) ),
				'to'   => gmdate( 'Y-m-d' ),
			),
			relevanssi_from_and_to( array( 'this_week' => true ), '' )
		);

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( '-' . ( gmdate( 'w' ) + 6 ) . ' days' ) ),
				'to'   => gmdate( 'Y-m-d', strtotime( '-' . gmdate( 'w' ) . ' days' ) ),
			),
			relevanssi_from_and_to( array( 'last_week' => true ), '' )
		);

		add_filter( 'relevanssi_week_starts_on_sunday', '__return_true' );

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( 'previous sunday' ) ),
				'to'   => gmdate( 'Y-m-d' ),
			),
			relevanssi_from_and_to( array( 'this_week' => true ), '' )
		);

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( '-' . ( gmdate( 'w' ) + 7 ) . ' days' ) ),
				'to'   => gmdate( 'Y-m-d', strtotime( '-' . ( gmdate( 'w' ) + 1 ) . ' days' ) ),
			),
			relevanssi_from_and_to( array( 'last_week' => true ), '' )
		);

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
				'to'   => gmdate( 'Y-m-d' ),
			),
			relevanssi_from_and_to( array( 'last_30' => true ), '' )
		);

		$this->assertEquals(
			array(
				'from' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
				'to'   => gmdate( 'Y-m-d' ),
			),
			relevanssi_from_and_to( array( 'last_7' => true ), '' )
		);
	}

	/**
	 * Test functions from common.php.
	 */
	public function test_relevanssi_common() {
		global $post, $relevanssi_variables;

		$post_id = $this->factory->post->create();

		$this->assertFalse( relevanssi_is_front_page_id( $post_id ) );
		$post = get_post( $post_id );
		$this->assertFalse( relevanssi_is_front_page_id() );

		update_option( 'page_on_front', $post_id );
		$this->assertTrue( relevanssi_is_front_page_id( $post_id ) );
		$this->assertTrue( relevanssi_is_front_page_id() );

		$this->assertEquals(
			array(
				$relevanssi_variables['relevanssi_table'],
				$relevanssi_variables['stopword_table'],
				$relevanssi_variables['log_table'],
			),
			relevanssi_wpmu_drop( array() )
		);
	}

	/**
	 * Test relevanssi_prevent_default_request().
	 */
	public function test_relevanssi_prevent_default_request() {
		$request = 'request';
		$query   = new WP_Query();

		update_option( 'relevanssi_index_post_types', array( 'post', 'attachment' ) );
		update_option( 'relevanssi_index_image_files', 'on' );

		$args = array(
			's'           => 'search',
			'post_type'   => 'attachment',
			'post_status' => 'inherit,private',
		);
		$query->parse_query( $args );

		$this->assertContains(
			'WHERE 1=2',
			relevanssi_prevent_default_request( $request, $query )
		);

		update_option( 'relevanssi_index_image_files', 'off' );

		$this->assertEquals(
			$request,
			relevanssi_prevent_default_request( $request, $query )
		);

		$args = array(
			's'         => 'search',
			'post_type' => array( 'topic', 'reply' ),
		);
		$query->parse_query( $args );

		$this->assertEquals(
			$request,
			relevanssi_prevent_default_request( $request, $query )
		);

		$args = array(
			's'         => 'search',
			'post_type' => 'topic',
		);
		$query->parse_query( $args );

		$this->assertEquals(
			$request,
			relevanssi_prevent_default_request( $request, $query )
		);

		$args = array(
			's'         => 'search',
			'post_type' => 'post',
		);
		$query->parse_query( $args );

		$_REQUEST['action'] = 'acf_action';

		$this->assertEquals(
			$request,
			relevanssi_prevent_default_request( $request, $query )
		);

		$args = array(
			's'         => 'search',
			'post_type' => 'post',
			'action'    => 'acf_action',
		);
		$query->parse_query( $args );

		unset( $_REQUEST['action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->assertEquals(
			$request,
			relevanssi_prevent_default_request( $request, $query )
		);

		add_filter( 'relevanssi_prevent_default_request', '__return_true' );

		$args = array(
			's'         => '',
			'post_type' => 'post',
		);
		$query->parse_query( $args );
		$query->is_search = true;

		$this->assertEquals(
			$request,
			relevanssi_prevent_default_request( $request, $query )
		);

		add_filter( 'relevanssi_prevent_default_request', '__return_true' );

		$args = array(
			's'         => 'search',
			'post_type' => 'post',
		);
		$query->parse_query( $args );

		$this->assertContains(
			'WHERE 1=2',
			relevanssi_prevent_default_request( $request, $query )
		);

		remove_filter( 'relevanssi_prevent_default_request', '__return_true' );

		update_option( 'relevanssi_admin_search', 'on' );

		$args = array(
			's'         => 'search',
			'post_type' => 'post',
		);
		$query->parse_query( $args );
		$screen                    = WP_Screen::get( 'edit-post' );
		$GLOBALS['current_screen'] = $screen;

		$this->assertContains(
			'WHERE 1=2',
			relevanssi_prevent_default_request( $request, $query )
		);

		$args = array(
			's'         => 'search',
			'post_type' => 'post',
			'fields'    => 'id=>parent',
		);
		$query->parse_query( $args );
		$query->is_admin = true;

		$this->assertEquals(
			$request,
			relevanssi_prevent_default_request( $request, $query )
		);

		$args = array(
			's'         => 'search',
			'post_type' => 'post',
		);
		$query->parse_query( $args );
		$query->is_admin = true;
		define( 'DOING_AJAX', true );

		$this->assertEquals(
			$request,
			relevanssi_prevent_default_request( $request, $query )
		);
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
