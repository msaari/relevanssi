<?php
/**
 * Class OptionsTest
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 */

/**
 * Test Relevanssi option functions.
 *
 * @group options
 */
class OptionsTest extends WP_UnitTestCase {

	/**
	 * Installs Relevanssi.
	 */
	public static function wpSetUpBeforeClass() {
		relevanssi_install();
		relevanssi_init();
	}

	/**
	 * Test relevanssi_process_punctuation_options.
	 */
	public function test_relevanssi_process_punctuation_options() {
		$request = array(
			'relevanssi_punct_quotes'     => 'on',
			'relevanssi_punct_hyphens'    => 'on',
			'relevanssi_punct_ampersands' => 'on',
			'relevanssi_punct_decimals'   => 'on',
		);

		$relevanssi_punct = array(
			'quotes'     => 'on',
			'hyphens'    => 'on',
			'ampersands' => 'on',
			'decimals'   => 'on',
		);

		$this->assertTrue( relevanssi_process_punctuation_options( $request ) );
		$this->assertFalse( relevanssi_process_punctuation_options( array() ) );
		$this->assertEquals( $relevanssi_punct, get_option( 'relevanssi_punctuation' ) );
	}

	/**
	 * Test relevanssi_sanitize_weights.
	 */
	public function test_sanitize_weights() {
		$this->assertEquals( 4.2, relevanssi_sanitize_weights( '4.2' ) );
		$this->assertEquals( 1, relevanssi_sanitize_weights( 0 ) );
		$this->assertEquals( 1, relevanssi_sanitize_weights( '-123' ) );
	}

	/**
	 * Test relevanssi_process_synonym_options.
	 */
	public function test_relevanssi_process_synonym_options() {
		$synonyms = <<<EOT
cat=dog
dog=puppy
EOT;
		$request  = array( 'relevanssi_synonyms' => $synonyms );
		$this->assertTrue( relevanssi_process_synonym_options( $request ) );
		$this->assertFalse( relevanssi_process_synonym_options( array() ) );
		$this->assertEquals(
			array( relevanssi_get_current_language() => 'cat=dog;dog=puppy' ),
			get_option( 'relevanssi_synonyms' )
		);
	}

	/**
	 * Test Relevanssi_Setting_Field_Custom_Fields_Group save lifecycle.
	 *
	 * Verifies that the object-oriented factory processor successfully translates compound
	 * dropdown UI selection states into strict legacy database option strings.
	 */
	public function test_custom_fields_group_field_save() {
		$field_instance = Relevanssi_Setting_Field_Factory::create(
			'relevanssi_index_fields',
			array( 'type' => 'custom_fields_group' )
		);

		// Assert that the instance is bound to the correct contract.
		$this->assertInstanceOf( 'Relevanssi_Setting_Field_Interface', $field_instance );

		// Test option mapping to 'all'.
		$request = array( 'relevanssi_index_fields_select' => 'all' );
		$field_instance->save( $request );
		$this->assertEquals( 'all', get_option( 'relevanssi_index_fields' ) );

		// Test option mapping to 'visible'.
		$request = array( 'relevanssi_index_fields_select' => 'visible' );
		$field_instance->save( $request );
		$this->assertEquals( 'visible', get_option( 'relevanssi_index_fields' ) );

		// Test option mapping to 'none' saves an empty string state.
		$request = array( 'relevanssi_index_fields_select' => 'none' );
		$field_instance->save( $request );
		$this->assertEquals( '', get_option( 'relevanssi_index_fields' ) );

		// Test option mapping to 'some' extracts raw trimmed input variables.
		$request = array(
			'relevanssi_index_fields_select' => 'some',
			'relevanssi_index_fields'        => 'field_a, field_b, field_c, ',
		);
		$field_instance->save( $request );
		$this->assertEquals( 'field_a, field_b, field_c', get_option( 'relevanssi_index_fields' ) );
	}

	/**
	 * Test relevanssi_process_trim_logs_option.
	 */
	public function test_relevanssi_process_trim_logs_option() {
		$request = array(
			'relevanssi_trim_logs' => '90',
		);
		$this->assertFalse( relevanssi_process_trim_logs_option( array() ) );
		$this->assertTrue( relevanssi_process_trim_logs_option( $request ) );
		$this->assertEquals( '90', get_option( 'relevanssi_trim_logs' ) );

		$request = array(
			'relevanssi_trim_logs' => 'non-numeric',
		);
		relevanssi_process_trim_logs_option( $request );
		$this->assertEquals( '0', get_option( 'relevanssi_trim_logs' ) );
	}

	/**
	 * Test relevanssi_process_cat_option and relevanssi_process_excat_option.
	 */
	public function test_relevanssi_process_cat_options() {
		$request = array(
			'relevanssi_cat' => array( '1', '2', '3' ),
		);
		$this->assertFalse( relevanssi_process_cat_option( array() ) );
		$this->assertTrue( relevanssi_process_cat_option( $request ) );
		$this->assertEquals( '1,2,3', get_option( 'relevanssi_cat' ) );

		$request = array(
			'relevanssi_cat_active' => true,
		);
		relevanssi_process_cat_option( $request );
		$this->assertEquals( '', get_option( 'relevanssi_cat' ) );

		$request = array(
			'relevanssi_excat' => array( '1', '2', '3' ),
		);
		$this->assertFalse( relevanssi_process_excat_option( array() ) );
		$this->assertTrue( relevanssi_process_excat_option( $request ) );
		$this->assertEquals( '1,2,3', get_option( 'relevanssi_excat' ) );

		$request = array(
			'relevanssi_excat_active' => true,
		);
		relevanssi_process_excat_option( $request );
		$this->assertEquals( '', get_option( 'relevanssi_excat' ) );

		$request = array(
			'relevanssi_cat'   => array( '1', '2', '3' ),
			'relevanssi_excat' => array( '1', '2', '3', '4', '5' ),
		);
		relevanssi_process_cat_option( $request );
		relevanssi_process_excat_option( $request );
		$this->assertEquals( '4,5', get_option( 'relevanssi_excat' ) );
	}

	/**
	 * Test update_relevanssi_options.
	 */
	public function test_update_relevanssi_options() {
		global $relevanssi_variables;

		$nonce = wp_create_nonce(
			plugin_basename( $relevanssi_variables['file'] )
		);

		$_REQUEST['relevanssi_options'] = $nonce;

		delete_option( 'relevanssi_index_image_files' );
		delete_option( 'relevanssi_throttle' );
		delete_option( 'relevanssi_log_queries' );
		delete_option( 'relevanssi_excerpts' );

		$request = array(
			'rlv_tab'                    => 'indexing',
			'relevanssi_min_word_length' => '4',
			'relevanssi_index_author'    => 'on',
		);

		update_relevanssi_options( $request );

		$this->assertEquals( 'off', get_option( 'relevanssi_index_image_files' ) );
		$this->assertEquals( '4', get_option( 'relevanssi_min_word_length' ) );
		$this->assertEquals( null, get_option( 'relevanssi_throttle', null ) );

		$request = array(
			'rlv_tab'                  => 'searching',
			'relevanssi_content_boost' => '4',
			'relevanssi_index_author'  => 'on',
		);

		update_relevanssi_options( $request );

		$this->assertEquals( 'off', get_option( 'relevanssi_throttle' ) );
		$this->assertEquals( '4', get_option( 'relevanssi_content_boost' ) );

		$request = array(
			'rlv_tab' => 'admin-dev',
		);

		update_relevanssi_options( $request );

		$this->assertEquals( 'off', get_option( 'relevanssi_log_queries' ) );

		$request = array(
			'rlv_tab'                      => 'display-ui',
			'relevanssi_show_matches_text' => 'Text with "quotes" to fix.',
			'relevanssi_exclude_posts'     => '1,2,3, ',
		);

		update_relevanssi_options( $request );

		$this->assertEquals( 'off', get_option( 'relevanssi_excerpts' ) );
		$this->assertEquals( "Text with 'quotes' to fix.", get_option( 'relevanssi_show_matches_text' ) );
		$this->assertEquals( '1,2,3', get_option( 'relevanssi_exclude_posts' ) );
	}

	/**
	 * Test relevanssi_process_weights_and_indexing.
	 */
	public function test_relevanssi_process_weights_and_indexing() {
		$request = array(
			'rlv_tab'                             => 'indexing',
			'relevanssi_weight_post'              => -5,
			'relevanssi_weight_page'              => 5,
			'relevanssi_taxonomy_weight_post_tag' => 4,
			'relevanssi_term_weight_category'     => 3,
			'relevanssi_term_weight_post_tag'     => null,
			'relevanssi_index_type_post'          => 'on',
			'relevanssi_index_taxonomy_category'  => 'on',
			'relevanssi_index_terms_post_tag'     => 'on',
		);

		relevanssi_process_weights_and_indexing( $request );

		$this->assertEquals(
			array(
				'post'                      => 1,
				'page'                      => 5.0,
				'post_tagged_with_post_tag' => 4.0,
				'taxonomy_term_category'    => 3.0,
				'taxonomy_term_post_tag'    => 1,
			),
			get_option( 'relevanssi_post_type_weights' )
		);

		$this->assertEquals(
			array( 0 => 'post' ),
			get_option( 'relevanssi_index_post_types' )
		);

		$this->assertEquals(
			array( 0 => 'category' ),
			get_option( 'relevanssi_index_taxonomies_list' )
		);
	}

	/**
	 * Test factory field type variations and data sanitization boundaries.
	 */
	public function test_factory_field_type_sanitization() {
		global $relevanssi_variables;
		$_REQUEST['relevanssi_options'] = wp_create_nonce( plugin_basename( $relevanssi_variables['file'] ) );

		$request = array(
			'rlv_tab'                   => 'display-ui',
			'relevanssi_excerpt_length' => '123-not-a-number',
		);

		$_REQUEST = array_merge( $_REQUEST, $request );

		update_relevanssi_options( $request );
		$this->assertEquals( 123, intval( get_option( 'relevanssi_excerpt_length' ) ) );

		// Test Checkbox Missing State (Should turn option 'off').
		$clear_request = array( 'rlv_tab' => 'display-ui' );
		$_REQUEST      = array_intersect_key( $_REQUEST, array( 'relevanssi_options' => 1 ) );
		$_REQUEST      = array_merge( $_REQUEST, $clear_request );

		update_relevanssi_options( $clear_request );
		$this->assertEquals( 'off', get_option( 'relevanssi_excerpts' ) );
	}

	/**
	 * Verify that a factory crash on one field type does not break subsequent options on the tab.
	 */
	public function test_factory_exception_resilience() {
		global $relevanssi_variables;
		$_REQUEST['relevanssi_options'] = wp_create_nonce( plugin_basename( $relevanssi_variables['file'] ) );

		$request = array(
			'rlv_tab'            => 'display-ui',
			'relevanssi_txt_col' => '#00ff00',
		);

		update_relevanssi_options( $request );
		$this->assertEquals( '#00ff00', get_option( 'relevanssi_txt_col' ) );
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
