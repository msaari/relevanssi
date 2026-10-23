<?php
/**
 * Search-level acceptance tests for the optional recipe.
 *
 * @package Relevanssi
 */

/**
 * Verifies optional ranking through actual WordPress searches.
 *
 * @group searching
 */
class TermBalanceSearchTest extends WP_UnitTestCase {
	/**
	 * Install the schema without relying on another test class.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
		self::disable_recipe();
	}

	/**
	 * Isolate scoring from site configuration.
	 */
	public function setUp() {
		parent::setUp();
		foreach ( array(
			'relevanssi_min_word_length'     => 2,
			'relevanssi_content_boost'       => 1,
			'relevanssi_title_boost'         => 1,
			'relevanssi_implicit_operator'   => 'AND',
			'relevanssi_fuzzy'               => 'never',
			'relevanssi_exact_match_bonus'   => 'off',
			'relevanssi_disable_or_fallback' => 'on',
			'relevanssi_log_queries'         => 'off',
			'relevanssi_excerpts'            => 'off',
			'relevanssi_hilite_title'        => 'off',
			'relevanssi_index_author'        => 'off',
			'relevanssi_post_type_weights'   => array( 'post' => 1 ),
		) as $option => $value ) {
			update_option( $option, $value );
		}
	}

	/**
	 * Never leave an optional ranking callback active for other tests.
	 */
	public function tearDown() {
		self::disable_recipe();
		parent::tearDown();
	}

	/**
	 * Balanced coverage should outrank the motivating incidental matches.
	 *
	 * @dataProvider ranking_examples
	 * @param array $uneven   Uneven occurrence counts.
	 * @param array $balanced Balanced occurrence counts.
	 */
	public function test_recipe_reverses_the_motivating_rankings( array $uneven, array $balanced ) {
		$first  = $this->profile( 'A profile', $uneven );
		$second = $this->profile( 'B profile', $balanced );
		$terms  = implode( ' ', array_keys( $balanced ) );
		$args   = array(
			's'        => $terms,
			'post__in' => array( $first, $second ),
		);
		$before = $this->search( $args );
		$this->assertSame( array( $first, $second ), wp_list_pluck( $before->posts, 'ID' ) );
		$this->enable_recipe();
		$after = $this->search( $args );
		$this->assertSame( array( $second, $first ), wp_list_pluck( $after->posts, 'ID' ) );
		$this->assertEquals( $before->posts[1]->relevance_score, $after->posts[0]->relevance_score, '', 0.01 );
		$this->assertSame( 2, $after->found_posts );
		self::disable_recipe();
		$this->assertSame( array( $first, $second ), wp_list_pluck( $this->search( $args )->posts, 'ID' ) );
	}

	/**
	 * Supplies customer-style distributions for different query lengths.
	 *
	 * @return array Two- and three-term customer-style examples.
	 */
	public function ranking_examples(): array {
		return array(
			array(
				array(
					'ai'     => 1,
					'expert' => 50,
				),
				array(
					'ai'     => 5,
					'expert' => 5,
				),
			),
			array(
				array(
					'ai'         => 1,
					'expert'     => 1,
					'consultant' => 50,
				),
				array(
					'ai'         => 5,
					'expert'     => 5,
					'consultant' => 5,
				),
			),
		);
	}

	/**
	 * Final presentation still owns pagination, explicit ordering and pinning.
	 */
	public function test_pagination_id_results_explicit_sorting_and_pinning() {
		$first  = $this->profile(
			'A profile',
			array(
				'ai'     => 1,
				'expert' => 50,
			)
		);
		$second = $this->profile(
			'B profile',
			array(
				'ai'     => 5,
				'expert' => 5,
			)
		);
		$args   = array(
			's'        => 'ai expert',
			'post__in' => array( $first, $second ),
		);
		$this->enable_recipe();
		$paged = $this->search(
			array_merge(
				$args,
				array(
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			)
		);
		$this->assertSame( array( $second ), $paged->posts );
		$this->assertSame( 2, $paged->found_posts );
		$this->assertSame(
			array( $first ),
			$this->search(
				array_merge(
					$args,
					array(
						'posts_per_page' => 1,
						'paged'          => 2,
						'fields'         => 'ids',
					)
				)
			)->posts
		);
		$this->assertSame(
			array( $first, $second ),
			wp_list_pluck(
				$this->search(
					array_merge(
						$args,
						array(
							'orderby' => 'post_title',
							'order'   => 'asc',
						)
					)
				)->posts,
				'ID'
			)
		);
		update_post_meta( $first, '_relevanssi_pin', 'ai expert' );
		$this->assertSame( $first, $this->search( $args )->posts[0]->ID );
	}

	/**
	 * Synonym-expanded OR queries and recursive OR fallbacks must be untouched.
	 */
	public function test_or_synonyms_and_fallback_are_unchanged() {
		$first = $this->profile(
			'A profile',
			array(
				'ai'     => 1,
				'expert' => 50,
			)
		);
		update_option( 'relevanssi_synonyms', array( relevanssi_get_current_language() => 'specialist=expert' ) );
		$args   = array(
			's'        => 'ai specialist',
			'operator' => 'OR',
			'post__in' => array( $first ),
		);
		$before = $this->search( $args );
		$this->enable_recipe();
		$after = $this->search( $args );
		$this->assertSame( $before->posts[0]->relevance_score, $after->posts[0]->relevance_score );
		update_option( 'relevanssi_disable_or_fallback', 'off' );
		$args['operator'] = 'AND';
		$args['s']        = 'ai expert missingbalanceword';
		$after            = $this->search( $args );
		self::disable_recipe();
		$before = $this->search( $args );
		$this->assertCount( 1, $after->posts );
		$this->assertSame( $before->posts[0]->relevance_score, $after->posts[0]->relevance_score );
	}

	/**
	 * The context must use the tokenizer's stem, not a new interpretation of the query.
	 */
	public function test_stemmed_terms_and_mysql_column_counts() {
		$stemmer = function ( $term ) {
			return 'experts' === $term ? 'expert' : $term;
		};
		add_filter( 'relevanssi_stemmer', $stemmer );
		update_option( 'relevanssi_mysql_columns', 'post_excerpt' );
		$post_id  = self::factory()->post->create(
			array(
				'post_title'   => 'Fixture',
				'post_content' => 'ai ai',
				'post_excerpt' => 'experts experts',
				'post_status'  => 'publish',
			)
		);
		$observed = array();
		$capture  = function ( $weights, $query, $context ) use ( &$observed ) {
			$observed = $context;
			return $weights;
		};
		add_filter( 'relevanssi_results', $capture, 15, 3 );
		try {
			relevanssi_index_doc( $post_id, true );
			$this->enable_recipe();
			$this->search(
				array(
					's'        => 'ai experts experts',
					'post__in' => array( $post_id ),
				)
			);
			$this->assertSame( array( 'ai', 'expert' ), $observed['terms'] );
			$this->assertSame( 2, $observed['term_hits'][ $post_id ]['ai'] );
			$this->assertSame( 2, $observed['term_hits'][ $post_id ]['expert'] );
		} finally {
			remove_filter( 'relevanssi_stemmer', $stemmer );
			remove_filter( 'relevanssi_results', $capture, 15 );
		}
	}

	/**
	 * Creates an indexed profile with controlled occurrence counts.
	 *
	 * @param string            $title  Fixture title.
	 * @param array<string,int> $counts Occurrences in the content.
	 * @return int Post identifier.
	 */
	private function profile( string $title, array $counts ): int {
		$content = '';
		foreach ( $counts as $term => $count ) {
			$content .= str_repeat( $term . ' ', $count ) . 'separator ';
		}
		$post_id = self::factory()->post->create(
			array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
			)
		);
		relevanssi_index_doc( $post_id, true );
		return $post_id;
	}

	/**
	 * Runs the complete search pipeline rather than a scoring approximation.
	 *
	 * @param array $args Search arguments.
	 * @return WP_Query Completed search.
	 */
	private function search( array $args ): WP_Query {
		$query = new WP_Query();
		$query->parse_query(
			array_merge(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'post',
				),
				$args
			)
		);
		relevanssi_do_query( $query );
		return $query;
	}

	/**
	 * Enables exactly the hooks registered by the published snippet.
	 */
	private function enable_recipe() {
		require dirname( __DIR__ ) . '/examples/term-balance.php';
	}

	/**
	 * Restores ordinary scoring for subsequent searches.
	 */
	private static function disable_recipe() {
		remove_all_filters( 'relevanssi_term_balance_power' );
	}
}
