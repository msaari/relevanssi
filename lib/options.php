<?php
/**
 * /lib/options.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Updates Relevanssi options.
 *
 * Checks the option values and updates the options. It's safe to use $_REQUEST
 * here, check_admin_referer() is done immediately before this function is
 * called.
 */
function update_relevanssi_options() {
	// phpcs:disable WordPress.Security.NonceVerification
	$data                  = relevanssi_process_weights_and_indexing( $_REQUEST );
	$post_type_weights     = $data['post_type_weights'];
	$index_post_types      = $data['index_post_types'];
	$index_taxonomies_list = $data['index_taxonomies_list'];
	$index_terms_list      = $data['index_terms_list'];

	if ( 'indexing' === $_REQUEST['tab'] ) {
		relevanssi_turn_off_options(
			$_REQUEST,
			array(
				'relevanssi_expand_shortcodes',
				'relevanssi_index_author',
				'relevanssi_index_excerpt',
				'relevanssi_index_image_files',
				'relevanssi_seo_noindex',
			)
		);
		relevanssi_update_intval( $_REQUEST, 'relevanssi_min_word_length', true, 3 );
		update_option( 'relevanssi_index_taxonomies_list', array_keys( $index_taxonomies_list ), false );
		if ( RELEVANSSI_PREMIUM ) {
			update_option( 'relevanssi_index_terms', array_keys( $index_terms_list ), false );
		}
		do_action( 'relevanssi_indexing_options', $_REQUEST );
	}

	if ( 'searching' === $_REQUEST['tab'] ) {
		relevanssi_turn_off_options(
			$_REQUEST,
			array(
				'relevanssi_admin_search',
				'relevanssi_disable_or_fallback',
				'relevanssi_exact_match_bonus',
				'relevanssi_polylang_all_languages',
				'relevanssi_respect_exclude',
				'relevanssi_throttle',
				'relevanssi_wpml_only_current',
			)
		);
		relevanssi_update_floatval( $_REQUEST, 'relevanssi_content_boost', true, 1, true );
		relevanssi_update_floatval( $_REQUEST, 'relevanssi_title_boost', true, 1, true );
		relevanssi_update_floatval( $_REQUEST, 'relevanssi_comment_boost', true, 1, true );
	}

	if ( 'logging' === $_REQUEST['tab'] ) {
		relevanssi_turn_off_options(
			$_REQUEST,
			array(
				'relevanssi_log_queries',
				'relevanssi_log_queries_with_ip',
			)
		);
	}

	if ( 'excerpts' === $_REQUEST['tab'] ) {
		relevanssi_turn_off_options(
			$_REQUEST,
			array(
				'relevanssi_excerpt_custom_fields',
				'relevanssi_excerpts',
				'relevanssi_expand_highlights',
				'relevanssi_highlight_comments',
				'relevanssi_highlight_docs',
				'relevanssi_hilite_title',
				'relevanssi_show_matches',
			)
		);
		if ( isset( $_REQUEST['relevanssi_show_matches_text'] ) ) {
			$value = $_REQUEST['relevanssi_show_matches_text'];
			$value = str_replace( '"', "'", $value );
			update_option( 'relevanssi_show_matches_text', $value );
		}
	}

	if ( isset( $_REQUEST['relevanssi_synonyms'] ) ) {
		$linefeeds = array( "\r\n", "\n", "\r" );
		$value     = str_replace( $linefeeds, ';', $_REQUEST['relevanssi_synonyms'] );
		$value     = stripslashes( $value );

		$synonym_option   = get_option( 'relevanssi_synonyms', array() );
		$current_language = relevanssi_get_current_language();

		$synonym_option[ $current_language ] = $value;

		update_option( 'relevanssi_synonyms', $synonym_option );
	}

	$relevanssi_punct = array();
	if ( isset( $_REQUEST['relevanssi_punct_quotes'] ) ) {
		$relevanssi_punct['quotes'] = $_REQUEST['relevanssi_punct_quotes'];
	}
	if ( isset( $_REQUEST['relevanssi_punct_hyphens'] ) ) {
		$relevanssi_punct['hyphens'] = $_REQUEST['relevanssi_punct_hyphens'];
	}
	if ( isset( $_REQUEST['relevanssi_punct_ampersands'] ) ) {
		$relevanssi_punct['ampersands'] = $_REQUEST['relevanssi_punct_ampersands'];
	}
	if ( isset( $_REQUEST['relevanssi_punct_decimals'] ) ) {
		$relevanssi_punct['decimals'] = $_REQUEST['relevanssi_punct_decimals'];
	}
	if ( ! empty( $relevanssi_punct ) ) {
		update_option( 'relevanssi_punctuation', $relevanssi_punct );
	}

	if ( count( $post_type_weights ) > 0 ) {
		update_option( 'relevanssi_post_type_weights', $post_type_weights );
	}

	if ( count( $index_post_types ) > 0 ) {
		update_option( 'relevanssi_index_post_types', array_keys( $index_post_types ) );
	}

	if ( isset( $_REQUEST['relevanssi_index_fields_select'] ) ) {
		$fields_option = '';
		if ( 'all' === $_REQUEST['relevanssi_index_fields_select'] ) {
			$fields_option = 'all';
		}
		if ( 'visible' === $_REQUEST['relevanssi_index_fields_select'] ) {
			$fields_option = 'visible';
		}
		if ( 'some' === $_REQUEST['relevanssi_index_fields_select'] ) {
			if ( isset( $_REQUEST['relevanssi_index_fields'] ) ) {
				$fields_option = rtrim( $_REQUEST['relevanssi_index_fields'], " \t\n\r\0\x0B," );
			}
		}
		update_option( 'relevanssi_index_fields', $fields_option, false );
	}

	if ( isset( $_REQUEST['relevanssi_trim_logs'] ) ) {
		$trim_logs = $_REQUEST['relevanssi_trim_logs'];
		if ( ! is_numeric( $trim_logs ) || $trim_logs < 0 ) {
			$trim_logs = 0;
		}
		update_option( 'relevanssi_trim_logs', $trim_logs );
	}

	if ( isset( $_REQUEST['relevanssi_cat'] ) ) {
		if ( is_array( $_REQUEST['relevanssi_cat'] ) ) {
			$csv_cats = implode( ',', $_REQUEST['relevanssi_cat'] );
			update_option( 'relevanssi_cat', $csv_cats );
		}
	} else {
		if ( isset( $_REQUEST['relevanssi_cat_active'] ) ) {
			update_option( 'relevanssi_cat', '' );
		}
	}

	if ( isset( $_REQUEST['relevanssi_excat'] ) ) {
		if ( is_array( $_REQUEST['relevanssi_excat'] ) ) {
			$array_excats = $_REQUEST['relevanssi_excat'];
			$cat          = get_option( 'relevanssi_cat' );
			if ( $cat ) {
				$array_cats   = explode( ',', $cat );
				$valid_excats = array();
				foreach ( $array_excats as $excat ) {
					if ( ! in_array( $excat, $array_cats, true ) ) {
						$valid_excats[] = $excat;
					}
				}
			} else {
				// No category restrictions, so everything's good.
				$valid_excats = $array_excats;
			}
			$csv_excats = implode( ',', $valid_excats );
			update_option( 'relevanssi_excat', $csv_excats );
		}
	} else {
		if ( isset( $_REQUEST['relevanssi_excat_active'] ) ) {
			update_option( 'relevanssi_excat', '' );
		}
	}

	$options = array(
		'relevanssi_admin_search'           => false,
		'relevanssi_bg_col'                 => true,
		'relevanssi_class'                  => true,
		'relevanssi_css'                    => true,
		'relevanssi_default_orderby'        => true,
		'relevanssi_disable_or_fallback'    => true,
		'relevanssi_exact_match_bonus'      => true,
		'relevanssi_excerpt_allowable_tags' => true,
		'relevanssi_excerpt_custom_fields'  => true,
		'relevanssi_excerpt_type'           => true,
		'relevanssi_excerpts'               => true,
		'relevanssi_exclude_posts'          => true,
		'relevanssi_expand_shortcodes'      => false,
		'relevanssi_expand_highlights'      => true,
		'relevanssi_fuzzy'                  => true,
		'relevanssi_highlight_comments'     => true,
		'relevanssi_highlight_docs'         => true,
		'relevanssi_highlight'              => true,
		'relevanssi_hilite_title'           => true,
		'relevanssi_implicit_operator'      => true,
		'relevanssi_index_author'           => false,
		'relevanssi_index_comments'         => false,
		'relevanssi_index_excerpt'          => false,
		'relevanssi_index_image_files'      => true,
		'relevanssi_log_queries_with_ip'    => true,
		'relevanssi_log_queries'            => true,
		'relevanssi_omit_from_logs'         => true,
		'relevanssi_polylang_all_languages' => true,
		'relevanssi_respect_exclude'        => true,
		'relevanssi_show_matches'           => true,
		'relevanssi_throttle'               => true,
		'relevanssi_txt_col'                => true,
		'relevanssi_wpml_only_current'      => true,
	);

	if ( isset( $_REQUEST['relevanssi_exclude_posts'] ) ) {
		$_REQUEST['relevanssi_exclude_posts'] = trim( $_REQUEST['relevanssi_exclude_posts'], ' ,' );
	}

	array_walk(
		$options,
		function( $autoload, $option ) {
			if ( isset( $_REQUEST[ $option ] ) ) {
				update_option( $option, $_REQUEST[ $option ], $autoload );
			}
		}
	);

	relevanssi_update_intval( $_REQUEST, 'relevanssi_excerpt_length', true, 10 );

	if ( function_exists( 'relevanssi_update_premium_options' ) ) {
		relevanssi_update_premium_options();
	}
	// phpcs:enable
}

/**
 * Fetches option values for variable name options.
 *
 * Goes through all options and picks up all options that have names that
 * contain post types, taxonomies and so on.
 *
 * @param array $request The $_REQUEST array.
 *
 * @return array Four arrays containing the required data.
 */
function relevanssi_process_weights_and_indexing( $request ) {
	$post_type_weights     = array();
	$index_post_types      = array();
	$index_taxonomies_list = array();
	$index_terms_list      = array();
	foreach ( $request as $key => $value ) {
		if ( empty( $value ) ) {
			$value = 0;
		}

		if ( 'relevanssi_weight_' === substr( $key, 0, strlen( 'relevanssi_weight_' ) ) ) {
			$type                       = substr( $key, strlen( 'relevanssi_weight_' ) );
			$post_type_weights[ $type ] = $value;
		}
		if ( 'relevanssi_taxonomy_weight_' === substr( $key, 0, strlen( 'relevanssi_taxonomy_weight_' ) ) ) {
			$type                       = 'post_tagged_with_' . substr( $key, strlen( 'relevanssi_taxonomy_weight_' ) );
			$post_type_weights[ $type ] = $value;
		}
		if ( 'relevanssi_term_weight_' === substr( $key, 0, strlen( 'relevanssi_term_weight_' ) ) ) {
			$type                       = 'taxonomy_term_' . substr( $key, strlen( 'relevanssi_term_weight_' ) );
			$post_type_weights[ $type ] = $value;
		}
		if ( 'relevanssi_index_type_' === substr( $key, 0, strlen( 'relevanssi_index_type_' ) ) ) {
			$type = substr( $key, strlen( 'relevanssi_index_type_' ) );
			if ( 'on' === $value ) {
				$index_post_types[ $type ] = true;
			}
		}
		if ( 'relevanssi_index_taxonomy_' === substr( $key, 0, strlen( 'relevanssi_index_taxonomy_' ) ) ) {
			$type = substr( $key, strlen( 'relevanssi_index_taxonomy_' ) );
			if ( 'on' === $value ) {
				$index_taxonomies_list[ $type ] = true;
			}
		}
		if ( 'relevanssi_index_terms_' === substr( $key, 0, strlen( 'relevanssi_index_terms_' ) ) ) {
			$type = substr( $key, strlen( 'relevanssi_index_terms_' ) );
			if ( 'on' === $value ) {
				$index_terms_list[ $type ] = true;
			}
		}
	}

	$post_type_weights = array_map( 'relevanssi_sanitize_weights', $post_type_weights );

	return array(
		'post_type_weights'     => $post_type_weights,
		'index_post_types'      => $index_post_types,
		'index_taxonomies_list' => $index_taxonomies_list,
		'index_terms_list'      => $index_terms_list,
	);
}

/**
 * Takes a value, converts it to float and if it's negative or zero, sets it
 * to 1.
 *
 * @param mixed $weight The weight value, which can be anything user enters.
 *
 * @return float The float value of the weight.
 */
function relevanssi_sanitize_weights( $weight ) {
	$weight = floatval( $weight );
	if ( $weight <= 0 ) {
		$weight = 1;
	}
	return $weight;
}
