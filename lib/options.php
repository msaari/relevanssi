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
 * Checks the option values and updates the options. It's safe to use $request
 * here, check_admin_referer() is done immediately before this function is
 * called.
 *
 * @param array $request The request array from $_REQUEST.
 */
function update_relevanssi_options( array $request ) {
	if ( 'indexing' === $request['tab'] ) {
		relevanssi_turn_off_options(
			$request,
			array(
				'relevanssi_expand_shortcodes',
				'relevanssi_index_author',
				'relevanssi_index_excerpt',
				'relevanssi_index_image_files',
				'relevanssi_seo_noindex',
			)
		);
		relevanssi_update_intval( $request, 'relevanssi_min_word_length', true, 3 );
		do_action( 'relevanssi_indexing_options', $request );
	}

	if ( 'searching' === $request['tab'] ) {
		relevanssi_turn_off_options(
			$request,
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
		relevanssi_update_floatval( $request, 'relevanssi_content_boost', true, 1, true );
		relevanssi_update_floatval( $request, 'relevanssi_title_boost', true, 1, true );
		relevanssi_update_floatval( $request, 'relevanssi_comment_boost', true, 1, true );
	}

	if ( 'logging' === $request['tab'] ) {
		relevanssi_turn_off_options(
			$request,
			array(
				'relevanssi_log_queries',
				'relevanssi_log_queries_with_ip',
			)
		);
	}

	if ( 'excerpts' === $request['tab'] ) {
		relevanssi_turn_off_options(
			$request,
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
		if ( isset( $request['relevanssi_show_matches_text'] ) ) {
			$value = $request['relevanssi_show_matches_text'];
			$value = str_replace( '"', "'", $value );
			update_option( 'relevanssi_show_matches_text', $value );
		}
		relevanssi_update_intval( $request, 'relevanssi_excerpt_length', true, 10 );
	}

	relevanssi_process_weights_and_indexing( $request );
	relevanssi_process_synonym_options( $request );
	relevanssi_process_punctuation_options( $request );
	relevanssi_process_index_fields_option( $request );
	relevanssi_process_trim_logs_option( $request );
	relevanssi_process_cat_option( $request );
	relevanssi_process_excat_option( $request );

	// The values control the autoloading.
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

	if ( isset( $request['relevanssi_exclude_posts'] ) ) {
		$request['relevanssi_exclude_posts'] = trim( $request['relevanssi_exclude_posts'], ' ,' );
	}

	array_walk(
		$options,
		function( $autoload, $option ) use ( $request ) {
			if ( isset( $request[ $option ] ) ) {
				update_option( $option, $request[ $option ], $autoload );
			}
		}
	);

	if ( function_exists( 'relevanssi_update_premium_options' ) ) {
		relevanssi_update_premium_options();
	}
}

/**
 * Fetches option values for variable name options.
 *
 * Goes through all options and picks up all options that have names that
 * contain post types, taxonomies and so on.
 *
 * @param array $request The $request array.
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

	if ( count( $post_type_weights ) > 0 ) {
		$post_type_weights = array_map( 'relevanssi_sanitize_weights', $post_type_weights );
		update_option( 'relevanssi_post_type_weights', $post_type_weights );
	}

	if ( count( $index_post_types ) > 0 ) {
		update_option( 'relevanssi_index_post_types', array_keys( $index_post_types ) );
	}

	if ( 'indexing' === $request['tab'] ) {
		update_option( 'relevanssi_index_taxonomies_list', array_keys( $index_taxonomies_list ), false );
		if ( RELEVANSSI_PREMIUM ) {
			update_option( 'relevanssi_index_terms', array_keys( $index_terms_list ), false );
		}
	}
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

/**
 * Compiles the punctuation settings from the request and updates the option.
 *
 * @param array $request The request array.
 *
 * @return boolean True, if update_option() succeeds, false otherwise.
 */
function relevanssi_process_punctuation_options( array $request ) : bool {
	$relevanssi_punct = array();
	if ( isset( $request['relevanssi_punct_quotes'] ) ) {
		$relevanssi_punct['quotes'] = $request['relevanssi_punct_quotes'];
	}
	if ( isset( $request['relevanssi_punct_hyphens'] ) ) {
		$relevanssi_punct['hyphens'] = $request['relevanssi_punct_hyphens'];
	}
	if ( isset( $request['relevanssi_punct_ampersands'] ) ) {
		$relevanssi_punct['ampersands'] = $request['relevanssi_punct_ampersands'];
	}
	if ( isset( $request['relevanssi_punct_decimals'] ) ) {
		$relevanssi_punct['decimals'] = $request['relevanssi_punct_decimals'];
	}
	if ( ! empty( $relevanssi_punct ) ) {
		return update_option( 'relevanssi_punctuation', $relevanssi_punct );
	}
	return false;
}

/**
 * Updates the synonym option in the current language.
 *
 * @param array $request The request array.
 *
 * @return boolean True, if update_option() succeeds, false otherwise.
 */
function relevanssi_process_synonym_options( array $request ) : bool {
	if ( isset( $request['relevanssi_synonyms'] ) ) {
		$linefeeds = array( "\r\n", "\n", "\r" );
		$value     = str_replace( $linefeeds, ';', $request['relevanssi_synonyms'] );
		$value     = stripslashes( $value );

		$synonym_option   = get_option( 'relevanssi_synonyms', array() );
		$current_language = relevanssi_get_current_language();

		$synonym_option[ $current_language ] = $value;

		return update_option( 'relevanssi_synonyms', $synonym_option );
	}
	return false;
}

/**
 * Updates the index_fields option in the current language.
 *
 * @param array $request The request array.
 *
 * @return boolean True, if update_option() succeeds, false otherwise.
 */
function relevanssi_process_index_fields_option( array $request ) : bool {
	if ( isset( $request['relevanssi_index_fields_select'] ) ) {
		$fields_option = '';
		if ( 'all' === $request['relevanssi_index_fields_select'] ) {
			$fields_option = 'all';
		}
		if ( 'visible' === $request['relevanssi_index_fields_select'] ) {
			$fields_option = 'visible';
		}
		if ( 'some' === $request['relevanssi_index_fields_select'] ) {
			if ( isset( $request['relevanssi_index_fields'] ) ) {
				$fields_option = rtrim( $request['relevanssi_index_fields'], " \t\n\r\0\x0B," );
			}
		}
		return update_option( 'relevanssi_index_fields', $fields_option, false );
	}
	return false;
}

/**
 * Updates the trim_logs option.
 *
 * @param array $request The request array.
 *
 * @return boolean True, if update_option() succeeds, false otherwise.
 */
function relevanssi_process_trim_logs_option( array $request ) : bool {
	if ( isset( $request['relevanssi_trim_logs'] ) ) {
		$trim_logs = $request['relevanssi_trim_logs'];
		if ( ! is_numeric( $trim_logs ) || $trim_logs < 0 ) {
			$trim_logs = 0;
		}
		return update_option( 'relevanssi_trim_logs', $trim_logs );
	}
	return false;
}

/**
 * Updates the cat option.
 *
 * @param array $request The request array.
 *
 * @return boolean True, if update_option() succeeds, false otherwise.
 */
function relevanssi_process_cat_option( array $request ) : bool {
	if ( isset( $request['relevanssi_cat'] ) ) {
		if ( is_array( $request['relevanssi_cat'] ) ) {
			return update_option(
				'relevanssi_cat',
				implode( ',', $request['relevanssi_cat'] )
			);
		}
	} else {
		if ( isset( $request['relevanssi_cat_active'] ) ) {
			return update_option( 'relevanssi_cat', '' );
		}
	}
	return false;
}

/**
 * Updates the excat option.
 *
 * @param array $request The request array.
 *
 * @return boolean True, if update_option() succeeds, false otherwise.
 */
function relevanssi_process_excat_option( array $request ) : bool {
	if ( isset( $request['relevanssi_excat'] ) ) {
		if ( is_array( $request['relevanssi_excat'] ) ) {
			$array_excats = $request['relevanssi_excat'];
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
			return update_option( 'relevanssi_excat', $csv_excats );
		}
	} else {
		if ( isset( $request['relevanssi_excat_active'] ) ) {
			return update_option( 'relevanssi_excat', '' );
		}
	}

	return false;
}
