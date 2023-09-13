<?php
/**
 * /lib/utils.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Returns a Relevanssi_Taxonomy_Walker instance.
 *
 * Requires the class file and generates a new Relevanssi_Taxonomy_Walker instance.
 *
 * @return object A new Relevanssi_Taxonomy_Walker instance.
 */
function get_relevanssi_taxonomy_walker() {
	require_once 'class-relevanssi-taxonomy-walker.php';
	return new Relevanssi_Taxonomy_Walker();
}

/**
 * Adds apostrophes around a string.
 *
 * @param string $str The string.
 *
 * @return string The string with apostrophes around it.
 */
function relevanssi_add_apostrophes( $str ) {
	return "'" . $str . "'";
}

/**
 * Adds quotes around a string.
 *
 * @param string $str The string.
 *
 * @return string The string with quotes around it.
 */
function relevanssi_add_quotes( $str ) {
	return '"' . $str . '"';
}

/**
 * Wraps the relevanssi_mb_trim() function so that it can be used as a callback
 * for array_walk().
 *
 * @since 2.1.4
 *
 * @see relevanssi_mb_trim.
 *
 * @param string $str String to trim.
 */
function relevanssi_array_walk_trim( string &$str ) {
	$str = relevanssi_mb_trim( $str );
}

/**
 * Converts sums in an array to averages, based on an array containing counts.
 *
 * Both arrays need to have (key, value) pairs with the same keys. The values
 * in $arr are then divided by the matching values in $counts, so when we have
 * sums in $arr and counts in $counts, we end up with averages.
 *
 * @param array $arr    The array with sums, passed as reference.
 * @param array $counts The array with counts.
 */
function relevanssi_average_array( array &$arr, array $counts ) {
	array_walk(
		$arr,
		function ( &$value, $key ) use ( $counts ) {
			$value = round( $value / $counts[ $key ], 2 );
		}
	);
}

/**
 * Returns 'checked' if the option is enabled.
 *
 * @param string $option Value to check.
 *
 * @return string If the option is 'on', returns 'checked', otherwise returns an
 * empty string.
 */
function relevanssi_check( string $option ) {
	$checked = '';
	if ( 'on' === $option ) {
		$checked = 'checked';
	}
	return $checked;
}

/**
 * Closes tags in a bit of HTML code.
 *
 * Used to make sure no tags are left open in excerpts. This method is not
 * foolproof, but it's good enough for now.
 *
 * @param string $html The HTML code to analyze.
 *
 * @return string The HTML code, with tags closed.
 */
function relevanssi_close_tags( string $html ) {
	$result = array();
	preg_match_all(
		'#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU',
		$html,
		$result
	);
	$opened_tags = $result[1];
	preg_match_all( '#</([a-z]+)>#iU', $html, $result );
	$closed_tags = $result[1];
	$len_opened  = count( $opened_tags );
	if ( count( $closed_tags ) === $len_opened ) {
		return $html;
	}
	$opened_tags = array_reverse( $opened_tags );
	for ( $i = 0; $i < $len_opened; $i++ ) {
		if ( ! in_array( $opened_tags[ $i ], $closed_tags, true ) ) {
			$html .= '</' . $opened_tags[ $i ] . '>';
		} else {
			unset(
				$closed_tags[ array_search( $opened_tags[ $i ], $closed_tags, true ) ]
			);
		}
	}
	return $html;
}

/**
 * Counts search term occurrances in the Relevanssi index.
 *
 * @param string $query The search query. Will be split at spaces.
 * @param string $mode  Output mode. Possible values 'array' or 'string'.
 * Default is 'array'.
 *
 * @return array|string An array of search term occurrances, or a string with
 * the number of occurrances.
 */
function relevanssi_count_term_occurrances( string $query, string $mode = 'array' ) {
	global $wpdb, $relevanssi_variables;

	$terms  = explode( ' ', $query );
	$counts = array();

	foreach ( $terms as $term ) {
		$term = trim( $term );
		if ( empty( $term ) ) {
			continue;
		}
		$counts[ $term ] = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT SUM(content + title + comment + tag +
				link + author + category + excerpt + taxonomy + customfield
				+ mysqlcolumn) AS total FROM ' .
				$relevanssi_variables['relevanssi_table'] . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				' WHERE term = %s
				GROUP BY term',
				$term
			)
		);
	}

	if ( 'array' === $mode ) {
		return $counts;
	} elseif ( 'string' === $mode ) {
		$strings = array();
		foreach ( $counts as $term => $count ) {
			$strings[] = "<span class='search_term'>$term</span>: <span class='count'>$count</span>";
		}
		return implode( ', ', $strings );
	}
}

/**
 * Prints out debugging notices.
 *
 * If WP_CLI is available, prints out the debug notice as a WP_CLI::log(),
 * otherwise if debug mode is on, uses error_log(), otherwise just echo.
 *
 * @param string $notice The notice to print out.
 */
function relevanssi_debug_echo( string $notice ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $notice );
	} elseif ( relevanssi_log_debug() ) {
		error_log( 'RELEVANSSI: ' . $notice ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	} else {
		echo esc_html( $notice ) . "\n";
	}
}

/**
 * Runs do_shortcode() on content, but safeguards the global $post to make sure
 * it isn't changed by the shortcodes. If shortcode expansion is disabled in
 * Relevanssi settings, runs strip_shortcodes() on the content.
 *
 * @uses relevanssi_disable_shortcodes() Disables problem shortcodes.
 * @see do_shortcode()                   Expands shortcodes.
 * @see strip_shortcodes()               Strips off shortcodes.
 *
 * @param string $content The content where the shortcodes are expanded.
 *
 * @return string
 */
function relevanssi_do_shortcode( string $content ): string {
	if ( 'on' === get_option( 'relevanssi_expand_shortcodes' ) ) {
		// TablePress support.
		if ( function_exists( 'relevanssi_enable_tablepress_shortcodes' ) ) {
			$tablepress_controller = relevanssi_enable_tablepress_shortcodes();
		}

		relevanssi_disable_shortcodes();

		/**
		 * This needs to be global here, otherwise the safety mechanism doesn't
		 * work correctly.
		 */
		global $post;

		$global_post_before_shortcode = null;
		if ( isset( $post ) ) {
			$global_post_before_shortcode = $post;
		}

		$content = do_shortcode( $content );

		if ( $global_post_before_shortcode ) {
			$post = $global_post_before_shortcode; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		unset( $tablepress_controller );
	} else {
		$content = strip_shortcodes( $content );
	}
	return $content;
}

/**
 * Recursively flattens a multidimensional array to produce a string.
 *
 * @param array $arr The source array.
 *
 * @return string The array contents as a string.
 */
function relevanssi_flatten_array( array $arr ) {
	$return_value = '';
	foreach ( new RecursiveIteratorIterator( new RecursiveArrayIterator( $arr ) ) as $value ) {
		$return_value .= ' ' . $value;
	}
	return trim( $return_value );
}

/**
 * Generates from and to date values from ranges.
 *
 * Possible values in the $request array: 'from' and 'to' for direct dates,
 * 'this_year' for Jan 1st to today, 'this_month' for 1st of month to today,
 * 'last_month' for 1st of previous month to last of previous month,
 * 'this_week' for Monday of this week to today (or Sunday, if the
 * relevanssi_week_starts_on_sunday returns `true`), 'last_week' for the
 * previous week, 'last_30' for from 30 days ago to today, 'last_7' for from
 * 7 days ago to today.
 *
 * @param array  $request The request array where the settings are.
 * @param string $from    The default 'from' date in "Y-m-d" format.
 * @return array The from date in 'from' and the to date in 'to' in "Y-m-d"
 * format.
 */
function relevanssi_from_and_to( array $request, string $from ): array {
	$today      = gmdate( 'Y-m-d' );
	$week_start = 'monday';
	$to         = $today;

	/**
	 * Controls whether the week starts on Sunday or Monday.
	 *
	 * @param boolean If `true`, week starts on Sunday. Default `false`, week
	 * starts on Monday.
	 */
	if ( apply_filters( 'relevanssi_week_starts_on_sunday', false ) ) {
		$week_start = 'sunday';
	}
	if ( ! isset( $request['everything'] ) && isset( $request['from'] ) && $request['from'] > $from ) {
		$from = $request['from'];
	}
	if ( ! isset( $request['everything'] ) && isset( $request['to'] ) && $request['to'] < $today ) {
		$to = $request['to'];
	}
	if ( isset( $request['this_year'] ) ) {
		$from = gmdate( 'Y-m-d', strtotime( 'first day of january this year' ) );
		$to   = gmdate( 'Y-m-d' );
	}
	if ( isset( $request['this_month'] ) ) {
		$from = gmdate( 'Y-m-d', strtotime( 'first day of this month' ) );
		$to   = gmdate( 'Y-m-d' );
	}
	if ( isset( $request['last_month'] ) ) {
		$from = gmdate( 'Y-m-d', strtotime( 'first day of previous month' ) );
		$to   = gmdate( 'Y-m-d', strtotime( 'last day of previous month' ) );
	}
	if ( isset( $request['this_week'] ) ) {
		$from = gmdate( 'Y-m-d', strtotime( 'previous ' . $week_start ) );
		$to   = gmdate( 'Y-m-d' );
	}
	if ( isset( $request['last_week'] ) ) {
		$start = 'sunday' === $week_start ? gmdate( 'w' ) + 7 : gmdate( 'w' ) + 6;
		$end   = 'sunday' === $week_start ? gmdate( 'w' ) + 1 : gmdate( 'w' );
		$from  = gmdate( 'Y-m-d', strtotime( '-' . $start . ' days' ) );
		$to    = gmdate( 'Y-m-d', strtotime( '-' . $end . ' days' ) );
	}
	if ( isset( $request['last_30'] ) ) {
		$from = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$to   = gmdate( 'Y-m-d' );
	}
	if ( isset( $request['last_7'] ) ) {
		$from = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$to   = gmdate( 'Y-m-d' );
	}

	return array(
		'from' => $from,
		'to'   => $to,
	);
}

/**
 * Generates closing tags for an array of tags.
 *
 * @param array $tags Array of tag names.
 *
 * @return array $closing_tags Array of closing tags.
 */
function relevanssi_generate_closing_tags( array $tags ) {
	$closing_tags = array();
	foreach ( $tags as $tag ) {
		$a = str_replace( '<', '</', $tag );
		$b = str_replace( '>', '/>', $tag );

		$closing_tags[] = $a;
		$closing_tags[] = $b;
	}
	return $closing_tags;
}

/**
 * Returns a post object based on ID, **type**id notation or an object.
 *
 * @uses relevanssi_get_post_object() Fetches post objects.
 *
 * @param int|string|WP_Post $source The source identified to parse, either a
 * post ID integer, a **type**id string or a post object.
 *
 * @return array An array containing the actual object in 'object' and the
 * format of the original value in 'format'. The value can be 'object', 'id'
 * or 'id=>parent'.
 */
function relevanssi_get_an_object( $source ) {
	$object = $source;
	$format = 'object';

	if ( ! is_object( $source ) ) {
		// Convert from post ID to post.
		$object = relevanssi_get_post_object( $source );
		$format = 'id';
	} elseif ( isset( $source->type ) ) {
		// Convert from id=>type to post.
		$object = relevanssi_get_post_object( $source->ID );
		$format = 'id=>type';
	} elseif ( ! isset( $source->post_content ) ) {
		// Convert from id=>parent to post.
		$object = relevanssi_get_post_object( $source->ID );
		$format = 'id=>parent';
	}

	return array(
		'object' => $object,
		'format' => $format,
	);
}

/**
 * Returns the attachment filename suffix.
 *
 * Reads the filename from $post->guid and returns the file suffix.
 *
 * @param WP_Post|int $post The post object or post ID.
 * @return string The suffix if it is found, an empty string otherwise.
 */
function relevanssi_get_attachment_suffix( $post ): string {
	if ( ! is_object( $post ) ) {
		$post = relevanssi_get_post( $post );
		if ( is_wp_error( $post ) ) {
			return '';
		}
	}
	if ( 'attachment' !== $post->post_type ) {
		return '';
	}
	list( , $type ) = explode( '.', basename( $post->guid ) );
	return $type;
}

/**
 * Returns the locale or language code.
 *
 * If WPML or Polylang is not available, returns `get_locale()` value. With
 * WPML or Polylang, first this function checks to see if the global $post is
 * set. If it is, the function returns the language of the post, as we're
 * working on a post and need to use the correct language.
 *
 * If the global $post is not set, this function returns for Polylang the
 * results of `pll_current_language()`, for WPML it uses `wpml_current_language`
 * and `wpml_active_languages`.
 *
 * @param boolean $locale If true, return locale; if false, return language
 * code.
 *
 * @return string The locale or the language code.
 */
function relevanssi_get_current_language( bool $locale = true ) {
	$current_language = get_locale();
	if ( ! $locale ) {
		$current_language = substr( $current_language, 0, 2 );
	}
	if ( class_exists( 'Polylang', false ) ) {
		global $post;

		if ( isset( $post ) ) {
			if ( isset( $post->term_id ) && function_exists( 'pll_get_term_language' ) ) {
				$current_language = pll_get_term_language( $post->term_id, $locale ? 'locale' : 'slug' );
			} elseif ( ! isset( $post->user_id ) && function_exists( 'pll_get_post_language' ) ) {
				$current_language = pll_get_post_language( $post->ID, $locale ? 'locale' : 'slug' );
			}
		} elseif ( function_exists( 'pll_current_language' ) ) {
			$pll_language     = pll_current_language( $locale ? 'locale' : 'slug' );
			$current_language = $pll_language ? $pll_language : $current_language;
		}
	}
	if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_is_translated_post_type' ) ) {
		global $post;

		if ( isset( $post ) ) {
			$language_details = array(
				'locale'        => '',
				'language_code' => '',
			);
			if ( isset( $post->term_id ) ) {
				// Terms don't have a locale, just a language code.
				$element       = array(
					'element_id'   => relevanssi_get_term_tax_id( $post->term_id, $post->post_type ),
					'element_type' => $post->post_type,
				);
				$language_code = apply_filters( 'wpml_element_language_code', null, $element );

				$language_details['language_code'] = $language_code;
			} elseif ( ! isset( $post->user_id ) && 'post_type' !== $post->post_type ) {
				// Users don't have language details.
				$language_details = apply_filters( 'wpml_post_language_details', null, $post->ID );
			}
			if ( is_wp_error( $language_details ) ) {
				$current_language = apply_filters( 'wpml_current_language', null );
			} else {
				$current_language = $language_details[ $locale ? 'locale' : 'language_code' ];
			}
		} elseif ( $locale ) {
			$languages = apply_filters( 'wpml_active_languages', null );
			foreach ( $languages as $l ) {
				if ( $l['active'] ) {
					$current_language = $l['default_locale'];
					break;
				}
			}
		} else {
			$current_language = apply_filters( 'wpml_current_language', null );
		}
	}
	return $current_language;
}

/**
 * Gets the permalink to the current post within Loop.
 *
 * Uses get_permalink() to get the permalink, then adds the 'highlight'
 * parameter if necessary using relevanssi_add_highlight().
 *
 * @param int|WP_Post $post Post ID or post object. Default is the global $post.
 *
 * @see get_permalink()
 *
 * @return string The permalink.
 */
function relevanssi_get_permalink( $post = 0 ) {
	/**
	 * Filters the permalink.
	 *
	 * @param string The permalink, generated by get_permalink().
	 */
	$permalink = apply_filters( 'relevanssi_permalink', get_permalink( $post ) );

	return $permalink;
}

/**
 * Replacement for get_post() that uses the Relevanssi post cache.
 *
 * Tries to fetch the post from the Relevanssi post cache. If that doesn't work,
 * gets the post using get_post().
 *
 * @param int|string $post_id The post ID. Usually an integer post ID, but can
 * also be a string (u_<user ID>, p_<post type name> or
 * **<taxonomy>**<term ID>).
 * @param int        $blog_id The blog ID, default -1. If -1, will be replaced
 * with the actual current blog ID from get_current_blog_id().
 *
 * @return object|WP_Error The post object or a WP_Error object if the post
 * doesn't exist.
 */
function relevanssi_get_post( $post_id, int $blog_id = -1 ) {
	if ( -1 === $blog_id ) {
		$blog_id = get_current_blog_id();
	}
	if ( function_exists( 'relevanssi_premium_get_post' ) ) {
		return relevanssi_premium_get_post( $post_id, $blog_id );
	}

	global $relevanssi_post_array;

	$post = null;
	if ( isset( $relevanssi_post_array[ $post_id ] ) ) {
		$post = $relevanssi_post_array[ $post_id ];
	}
	if ( ! $post ) {
		$post = get_post( $post_id );

		$relevanssi_post_array[ $post_id ] = $post;
	}
	if ( ! $post ) {
		$post = new WP_Error( 'post_not_found', __( 'The requested post does not exist.' ) );
	}
	return $post;
}

/**
 * Fetches post meta value for a large group of posts with just one query.
 *
 * This function can be used to reduce the number of database queries. Instead
 * of looping through an array of posts and calling get_post_meta() for each
 * individual post, you can get all the values with this function with just one
 * database query.
 *
 * @param array  $post_ids An array of post IDs.
 * @param string $field    The name of the field.
 *
 * @return array An array of post_id, meta_value pairs.
 */
function relevanssi_get_post_meta_for_all_posts( array $post_ids, string $field ): array {
	global $wpdb;

	$post_ids_string = implode( ',', $post_ids );
	$meta_values     = array();

	if ( $post_ids_string ) {
		$meta_values = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM $wpdb->postmeta
                WHERE meta_key = %s
				AND post_id IN ( $post_ids_string )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
				$field
			)
		);
	}

	$results = array();
	foreach ( $meta_values as $row ) {
		$results[ $row->post_id ] = $row->meta_value;
	}

	return $results;
}

/**
 * Returns an object based on ID.
 *
 * Wrapper to handle non-post cases (terms, user profiles). Regular posts are
 * passed on to relevanssi_get_post().
 *
 * @uses relevanssi_get_post() Used to fetch regular posts.
 *
 * @param int|string $post_id An ID, either an integer post ID or a
 * **type**id string for terms and users.
 *
 * @return WP_Post|WP_Term|WP_User|WP_Error An object, type of which depends on
 * the target object. If relevanssi_get_post() doesn't find the post, this
 * returns a WP_Error.
 */
function relevanssi_get_post_object( $post_id ) {
	$object = null;
	if ( '*' === substr( $post_id, 0, 1 ) ) {
		// Convert from **type**id to a user or a term object.
		$parts = explode( '**', $post_id );
		$type  = $parts[1] ?? null;
		$id    = $parts[2] ?? null;
		if ( $type && $id ) {
			if ( 'user' === $type ) {
				$object = get_user_by( 'id', $id );
			} else {
				$object = get_term( $id, $type );
			}
		}
	} else {
		$object = relevanssi_get_post( $post_id );
	}
	return $object;
}

/**
 * Returns the term taxonomy ID for a term based on term ID.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param int    $term_id  The term ID.
 * @param string $taxonomy The taxonomy.
 *
 * @return int Term taxonomy ID.
 */
function relevanssi_get_term_tax_id( int $term_id, string $taxonomy ) {
	global $wpdb;
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE term_id = %d AND taxonomy = %s",
			$term_id,
			$taxonomy
		)
	);
}

/**
 * Fetches the taxonomy based on term ID.
 *
 * Fetches the taxonomy from wp_term_taxonomy based on term_id.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param int $term_id The term ID.
 *
 * @deprecated Will be removed in future versions.
 *
 * @return string $taxonomy The term taxonomy.
 */
function relevanssi_get_term_taxonomy( int $term_id ) {
	global $wpdb;

	$taxonomy = $wpdb->get_var( $wpdb->prepare( "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $taxonomy;
}

/**
 * Gets a list of tags for post.
 *
 * Replacement for get_the_tags() that does the same, but applies Relevanssi
 * search term highlighting on the results.
 *
 * @uses relevanssi_the_tags() Does the actual work.
 *
 * @param string $before    What is printed before the tags, default ''.
 * @param string $separator The separator between items, default ', '.
 * @param string $after     What is printed after the tags, default ''.
 * @param int    $post_id   The post ID. Default current post ID (in the Loop).
 */
function relevanssi_get_the_tags( string $before = '', string $separator = ', ', string $after = '', int $post_id = 0 ) {
	return relevanssi_the_tags( $before, $separator, $after, false, $post_id );
}

/**
 * Returns the post title with highlighting.
 *
 * Reads the highlighted title from $post->post_highlighted_title. Uses the
 * relevanssi_get_post() to fecth the post.
 *
 * @uses relevanssi_get_post() Fetches post objects.
 *
 * @param int|WP_Post $post The post ID or a post object.
 *
 * @return string The post title with highlights and an empty string, if the
 * post cannot be found.
 */
function relevanssi_get_the_title( $post ) {
	if ( is_numeric( $post ) ) {
		$post = relevanssi_get_post( $post );
	}
	if ( is_wp_error( $post ) ) {
		return '';
	}
	if ( empty( $post->post_highlighted_title ) ) {
		$post->post_highlighted_title = $post->post_title;
	}
	return $post->post_highlighted_title;
}

/**
 * Adds a soft hyphen to a string at every five characters.
 *
 * @param string $str The string to hyphenate.
 *
 * @return string The hyphenated string.
 */
function relevanssi_hyphenate( $str ) {
	$str = preg_replace( '/([^\s]{8})([^\s])/u', '$1&shy;$2', html_entity_decode( $str ) );
	return $str;
}

/**
 * Returns an imploded string if the option exists and is an array, an empty
 * string otherwise.
 *
 * @see implode()
 *
 * @param array  $request An array of option values.
 * @param string $option  The key to check.
 * @param string $glue    The glue string for implode(), default ','.
 *
 * @return string Imploded string or an empty string.
 */
function relevanssi_implode( array $request, string $option, string $glue = ',' ) {
	if ( isset( $request[ $option ] ) && is_array( $request[ $option ] ) ) {
		return implode( $glue, $request[ $option ] );
	}
	return '';
}

/**
 * Increases a value. If it's not set, sets it first to the default value.
 *
 * @param int $value     The value to increase (passed by reference).
 * @param int $increase  The amount to increase the value, default 1.
 * @param int $def_value The default value, default 0.
 */
function relevanssi_increase_value( &$value, $increase = 1, $def_value = 0 ) {
	if ( ! isset( $value ) ) {
		$value = $def_value;
	}
	$value += $increase;
}

/**
 * Returns the intval of the option if it exists, null otherwise.
 *
 * @see intval()
 *
 * @param array  $request An array of option values.
 * @param string $option  The key to check.
 *
 * @return int|null Integer value of the option, or null.
 */
function relevanssi_intval( array $request, string $option ) {
	if ( isset( $request[ $option ] ) ) {
		return intval( $request[ $option ] );
	}
	return null;
}

/**
 * Returns true if the search is from Relevanssi Live Ajax Search.
 *
 * Checks if $wp_query->query_vars['action'] is set to "relevanssi_live_search".
 *
 * @return bool True if the search is from Relevanssi Live Ajax Search, false
 * otherwise.
 */
function relevanssi_is_live_search() {
	global $wp_query;
	$relevanssi_live_search = false;
	if ( isset( $wp_query->query_vars['action'] ) && 'relevanssi_live_search' === $wp_query->query_vars['action'] ) {
		$relevanssi_live_search = true;
	}
	return $relevanssi_live_search;
}

/**
 * Checks if a string is a multiple-word phrase.
 *
 * Replaces hyphens, quotes and ampersands with spaces if necessary based on
 * the Relevanssi advanced indexing settings.
 *
 * @param string $str The string to check.
 *
 * @return boolean True if the string is a multiple-word phrase, false otherwise.
 */
function relevanssi_is_multiple_words( string $str ): bool {
	if ( empty( $str ) ) {
		return false;
	}
	$punctuation = get_option( 'relevanssi_punctuation' );
	if ( 'replace' === $punctuation['hyphens'] ) {
		$str = str_replace(
			array(
				'-',
				'–',
				'—',
			),
			' ',
			$str
		);
	}
	if ( 'replace' === $punctuation['quotes'] ) {
		$str = str_replace(
			array(
				'&#8217;',
				"'",
				'’',
				'‘',
				'”',
				'“',
				'„',
				'´',
				'″',
			),
			' ',
			$str
		);
	}
	if ( 'replace' === $punctuation['ampersands'] ) {
		$str = str_replace(
			array(
				'&#038;',
				'&amp;',
				'&',
			),
			' ',
			$str
		);
	}

	if ( count( explode( ' ', $str ) ) > 1 ) {
		return true;
	}

	return false;
}

/**
 * Launches an asynchronous Ajax action.
 *
 * Makes a wp_remote_post() call with the specific action. Handles nonce
 * verification.
 *
 * @see wp_remove_post()
 * @see wp_create_nonce()
 *
 * @param string $action       The action to trigger (also the name of the
 * nonce).
 * @param array  $payload_args The parameters sent to the action. Defaults to
 * an empty array.
 *
 * @return WP_Error|array The wp_remote_post() response or WP_Error on failure.
 */
function relevanssi_launch_ajax_action( string $action, array $payload_args = array() ) {
	$cookies = array();
	foreach ( $_COOKIE as $name => $value ) {
		$cookies[] = "$name=" . rawurlencode(
			is_array( $value ) ? wp_json_encode( $value ) : $value
		);
	}
	$default_payload = array(
		'action' => $action,
		'_nonce' => wp_create_nonce( $action ),
	);
	$payload         = array_merge( $default_payload, $payload_args );
	$args            = array(
		'timeout'  => 0.01,
		'blocking' => false,
		'body'     => $payload,
		'headers'  => array(
			'cookie' => implode( '; ', $cookies ),
		),
	);
	$url             = admin_url( 'admin-ajax.php' );
	return wp_remote_post( $url, $args );
}

/**
 * Returns a legal value.
 *
 * @param array  $request An array of option values.
 * @param string $option  The key to check.
 * @param array  $values  The legal values.
 * @param string $def_val The default value.
 *
 * @return string|null A legal value or the default value, null if the option
 * isn't set.
 */
function relevanssi_legal_value( array $request, string $option, array $values, string $def_val ) {
	$value = null;
	if ( isset( $request[ $option ] ) ) {
		$value = $def_val;
		if ( in_array( $request[ $option ], $values, true ) ) {
			$value = $request[ $option ];
		}
	}
	return $value;
}

/**
 * Multibyte friendly case-insensitive string comparison.
 *
 * If multibyte string functions are available, do strnatcmp() after using
 * mb_strtoupper() to both strings. Otherwise use strnatcasecmp().
 *
 * @see strnatcasecmp() Falls back to this if multibyte functions are
 * not available.
 * @see strnatcmp()     Used to compare the strings.
 * @see mb_strtoupper() Used to convert strings to uppercase.
 *
 * @param string $str1     First string to compare.
 * @param string $str2     Second string to compare.
 * @param string $encoding The encoding to use, default mb_internal_encoding().
 *
 * @return int $val Returns < 0 if str1 is less than str2; > 0 if str1 is
 * greater than str2, and 0 if they are equal.
 */
function relevanssi_mb_strcasecmp( $str1, $str2, $encoding = '' ): int {
	if ( ! function_exists( 'mb_internal_encoding' ) ) {
		return strnatcasecmp( $str1, $str2 );
	} else {
		if ( empty( $encoding ) ) {
			$encoding = mb_internal_encoding();
		}
		return strnatcmp( mb_strtoupper( $str1, $encoding ), mb_strtoupper( $str2, $encoding ) );
	}
}

/**
 * Multibyte friendly case-insensitive string search.
 *
 * If multibyte string functions are available, do mb_stristr(). Otherwise,
 * do stristr().
 *
 * @see stristr()     Falls back to this if multibyte functions are not
 * available.
 *
 * @param string $haystack The string to search in.
 * @param string $needle   The string to search for.
 * @param string $encoding The encoding to use, default mb_internal_encoding().
 *
 * @return bool True if the needle was found in the haystack, false otherwise.
 */
function relevanssi_mb_stristr( $haystack, $needle, $encoding = '' ): bool {
	if ( ! function_exists( 'mb_internal_encoding' ) ) {
		return stristr( $haystack, $needle );
	} else {
		if ( empty( $encoding ) ) {
			$encoding = mb_internal_encoding();
		}
		return mb_stristr( $haystack, $needle, false, $encoding );
	}
}

/**
 * Trims multibyte strings.
 *
 * Removes the 194+160 non-breakable spaces, removes null bytes and removes
 * whitespace.
 *
 * @param string $str The source string.
 *
 * @return string Trimmed string.
 */
function relevanssi_mb_trim( string $str ) {
	$str = str_replace( chr( 194 ) . chr( 160 ), '', $str );
	$str = str_replace( "\0", '', $str );
	$str = preg_replace( '/(^\s+)|(\s+$)/us', '', $str );
	return $str;
}

/**
 * Returns 'on' if option exists and value is not 'off', otherwise 'off'.
 *
 * @param array  $request An array of option values.
 * @param string $option  The key to check.
 *
 * @return string 'on' or 'off'.
 */
function relevanssi_off_or_on( array $request, string $option ) {
	if ( isset( $request[ $option ] ) && 'off' !== $request[ $option ] ) {
		return 'on';
	}
	return 'off';
}

/**
 * Removes quotes (", ”, “) from a string.
 *
 * @param string $str The string to clean.
 *
 * @return string The cleaned string.
 */
function relevanssi_remove_quotes( string $str ) {
	return str_replace( array( '”', '“', '"' ), '', $str );
}

/**
 * Removes quotes from array keys. Does not keep array values.
 *
 * Used to remove phrase quotes from search term array, which have the format
 * of (term => hits). The number of hits is not needed, so this function
 * discards it as a side effect.
 *
 * @uses relevanssi_remove_quotes() This does the actual work.
 *
 * @param array $arr An array to process.
 *
 * @return array The same array with quotes removed from the keys.
 */
function relevanssi_remove_quotes_from_array_keys( array $arr ) {
	$arr = array_keys( $arr );
	array_walk(
		$arr,
		function ( &$key ) {
			$key = relevanssi_remove_quotes( $key );
		}
	);
	return array_flip( $arr );
}

/**
 * Returns an ID=>parent object from a post (or a term, or a user).
 *
 * @param WP_Post|WP_Term|WP_User $post_object The source object.
 *
 * @return object An object with the attributes ID and post_parent set. For
 * terms and users, ID is the term or user ID and post_parent is 0. For bad
 * inputs, returns 0 and 0.
 */
function relevanssi_return_id_parent( $post_object ) {
	$id_parent_object = new stdClass();

	if ( isset( $post_object->ID ) ) {
		$id_parent_object->ID          = $post_object->ID;
		$id_parent_object->post_parent = $post_object->post_parent;
	} elseif ( isset( $post_object->term_id ) ) {
		$id_parent_object->ID          = $post_object->term_id;
		$id_parent_object->post_parent = 0;
	} elseif ( isset( $post_object->user_id ) ) {
		$id_parent_object->ID          = $post_object->user_id;
		$id_parent_object->post_parent = 0;
	} else {
		$id_parent_object->ID          = 0;
		$id_parent_object->post_parent = 0;
	}

	return $id_parent_object;
}

/**
 * Returns an ID=>type object from a post (or a term, or a user).
 *
 * @param WP_Post|WP_Term|WP_User $post_object The source object.
 *
 * @return object An object with the attributes ID and type set. Type is
 * 'post', 'user', 'term' or 'post_type'. For terms, also fills in 'taxonomy',
 * for post types 'name'.
 */
function relevanssi_return_id_type( $post_object ) {
	$id_type_object = new stdClass();

	if ( isset( $post_object->ID ) ) {
		$id_type_object->ID   = $post_object->ID;
		$id_type_object->type = 'post';
	} elseif ( isset( $post_object->term_id ) ) {
		$id_type_object->ID       = $post_object->term_id;
		$id_type_object->type     = 'term';
		$id_type_object->taxonomy = $post_object->taxonomy;
	} elseif ( isset( $post_object->user_id ) ) {
		$id_type_object->ID   = $post_object->user_id;
		$id_type_object->type = 'user';
	} else {
		$id_type_object->ID          = 0;
		$id_type_object->post_parent = 0;
	}

	return $id_type_object;
}

/**
 * Returns "off".
 *
 * Useful for returning "off" to filters easily.
 *
 * @return string A string with value "off".
 */
function relevanssi_return_off() {
	return 'off';
}

/**
 * Returns "OR".
 *
 * @return string A string with value "OR".
 */
function relevanssi_return_or() {
	return 'OR';
}

/**
 * Gets a post object, returns ID, ID=>parent or the post object.
 *
 * @uses relevanssi_return_id_type()   Used to return ID=>type results.
 * @uses relevanssi_return_id_parent() Used to return ID=>parent results.
 *
 * @param object $post         The post object.
 * @param string $return_value The value to return, possible values are 'id'
 * for returning the ID and 'id=>parent' for returning the ID=>parent object,
 * otherwise the post object is returned.
 *
 * @return int|object|WP_Post The post object in the desired format.
 */
function relevanssi_return_value( $post, string $return_value ) {
	if ( 'id' === $return_value ) {
		return $post->ID;
	} elseif ( 'id=>type' === $return_value ) {
		return relevanssi_return_id_type( $post );
	} elseif ( 'id=>parent' === $return_value ) {
		return relevanssi_return_id_parent( $post );
	}
	return $post;
}

/**
 * Sanitizes hex color strings.
 *
 * A copy of sanitize_hex_color(), because that isn't always available.
 *
 * @param string $color A hex color string to sanitize.
 *
 * @return string Sanitized hex string, or an empty string.
 */
function relevanssi_sanitize_hex_color( string $color ) {
	if ( '' === $color ) {
		return '';
	}

	if ( '#' !== substr( $color, 0, 1 ) ) {
		$color = '#' . $color;
	}

	// 3 or 6 hex digits, or the empty string.
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}

	return '';
}

/**
 * Returns 'selected' if the option matches a value.
 *
 * @param string $option Value to check.
 * @param string $value  The 'selected' value.
 *
 * @return string If the option matches the value, returns 'selected', otherwise
 * returns an empty string.
 */
function relevanssi_select( string $option, string $value ) {
	$selected = '';
	if ( $option === $value ) {
		$selected = 'selected';
	}
	return $selected;
}

/**
 * Strips all tags from content, keeping non-tags that look like tags.
 *
 * Strips content that matches <[!a-zA-Z\/]*> to remove HTML tags and HTML
 * comments, but not things like "<30 grams, 4>1".
 *
 * @param string $content The content.
 *
 * @return string The content with tags stripped.
 */
function relevanssi_strip_all_tags( $content ): string {
	if ( ! is_string( $content ) ) {
		$content = '';
	}
	$content = preg_replace( '/<!--.*?-->/ums', '', $content );
	$content = preg_replace( '/<[!a-zA-Z\/][^>].*?>/ums', ' ', $content );

	return $content ?? '';
}

/**
 * Strips invisible elements from text.
 *
 * Strips <style>, <script>, <object>, <embed>, <applet>, <noscript>, <noembed>,
 * <iframe> and <del> tags and their contents and comments from the text.
 *
 * @param string $text The source text.
 *
 * @return string The processed text.
 */
function relevanssi_strip_invisibles( $text ) {
	if ( ! is_string( $text ) ) {
		$text = strval( $text );
	}
	$text = preg_replace(
		array(
			'@<style[^>]*?>.*?</style>@siu',
			'@<script[^>]*?.*?</script>@siu',
			'@<object[^>]*?.*?</object>@siu',
			'@<embed[^>]*?.*?</embed>@siu',
			'@<applet[^>]*?.*?</applet>@siu',
			'@<noscript[^>]*?.*?</noscript>@siu',
			'@<noembed[^>]*?.*?</noembed>@siu',
			'@<iframe[^>]*?.*?</iframe>@siu',
			'@<del[^>]*?.*?</del>@siu',
			'@<!--.*?-->@siu',
		),
		' ',
		$text
	);
	return $text;
}

/**
 * Strips tags from contents, keeping the allowed tags.
 *
 * The allowable tags are read from the relevanssi_excerpt_allowable_tags
 * option. Relevanssi also adds extra spaces after some tags to make sure words
 * are not stuck together after the tags are removed. The function also removes
 * invisible content.
 *
 * @uses relevanssi_strip_invisibles() Used to remove scripts and other tags.
 * @see  strip_tags()                  Used to remove tags.
 *
 * @param string|null $content The content.
 *
 * @return string The content without tags.
 */
function relevanssi_strip_tags( $content ) {
	if ( ! is_string( $content ) ) {
		$content = strval( $content );
	}
	$content = relevanssi_strip_invisibles( $content );

	$space_tags = array(
		'/(<\/?p.*?>)/',
		'/(<\/?br.*?>)/',
		'/(<\/?h[1-6].*?>)/',
		'/(<\/?div.*?>)/',
		'/(<\/?blockquote.*?>)/',
		'/(<\/?hr.*?>)/',
		'/(<\/?li.*?>)/',
		'/(<img.*?>)/',
		'/(<\/td>)/',
	);

	$content = preg_replace( $space_tags, '$1 ', $content );
	return strip_tags(
		$content,
		get_option( 'relevanssi_excerpt_allowable_tags', '' )
	);
}

/**
 * Returns the position of substring in the string.
 *
 * Uses mb_stripos() if possible, falls back to mb_strpos() and mb_strtoupper()
 * if that cannot be found, and falls back to just strpos() if even that is not
 * possible.
 *
 * @param string $haystack String where to look.
 * @param string $needle   The string to look for.
 * @param int    $offset   Where to start, default 0.
 *
 * @return mixed False, if no result or $offset outside the length of $haystack,
 * otherwise the position (which can be non-false 0!).
 */
function relevanssi_stripos( $haystack, $needle, int $offset = 0 ) {
	if ( ! is_string( $haystack ) ) {
		$haystack = strval( $haystack );
	}
	if ( ! is_string( $needle ) ) {
		$needle = strval( $needle );
	}
	if ( $offset > relevanssi_strlen( $haystack ) ) {
		return false;
	}

	if ( preg_match( '/[\?\*]/', $needle ) ) {
		// There's a ? or an * in the string, which means it's a wildcard search
		// query (a Premium feature) and requires some extra steps.
		$needle_regex = str_replace(
			array( '?', '*' ),
			array( '.', '.*' ),
			preg_quote( $needle, '/' )
		);
		$pos_found    = false;
		while ( ! $pos_found ) {
			preg_match(
				"/$needle_regex/i",
				$haystack,
				$matches,
				PREG_OFFSET_CAPTURE,
				$offset
			);
			/**
			 * This trickery is necessary, because PREG_OFFSET_CAPTURE gives
			 * wrong offsets for multibyte strings. The mb_strlen() gives the
			 * correct offset, the rest of this is because the $offset received
			 * as a parameter can be before the first $position, leading to an
			 * infinite loop.
			 */
			$pos = isset( $matches[0][1] )
				? mb_strlen( substr( $haystack, 0, $matches[0][1] ) )
				: false;
			if ( $pos && $pos > $offset ) {
				$pos_found = true;
			} elseif ( $pos ) {
				++$offset;
			} else {
				$pos_found = true;
			}
		}
	} elseif ( function_exists( 'mb_stripos' ) ) {
		if ( '' === $haystack ) {
			$pos = false;
		} else {
			$pos = mb_stripos( $haystack, $needle, $offset );
		}
	} elseif ( function_exists( 'mb_strpos' ) && function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
		$pos = mb_strpos(
			mb_strtoupper( $haystack ),
			mb_strtoupper( $needle ),
			$offset
		);
	} else {
		$pos = strpos( strtoupper( $haystack ), strtoupper( $needle ), $offset );
	}
	return $pos;
}

/**
 * Returns the length of the string.
 *
 * Uses mb_strlen() if available, otherwise falls back to strlen().
 *
 * @param string $s The string to measure.
 *
 * @return int The length of the string.
 */
function relevanssi_strlen( $s ) {
	if ( ! is_string( $s ) ) {
		$s = strval( $s );
	}
	if ( function_exists( 'mb_strlen' ) ) {
		return mb_strlen( $s );
	}
	return strlen( $s );
}

/**
 * Multibyte friendly strtolower.
 *
 * If multibyte string functions are available, returns mb_strtolower() and
 * falls back to strtolower() if multibyte functions are not available.
 *
 * @param string $str The string to lowercase.
 *
 * @return string $str The string in lowercase.
 */
function relevanssi_strtolower( $str ) {
	if ( ! is_string( $str ) ) {
		$str = strval( $str );
	}
	if ( ! function_exists( 'mb_strtolower' ) ) {
		return strtolower( $str );
	} else {
		return mb_strtolower( $str );
	}
}

/**
 * Multibyte friendly substr.
 *
 * If multibyte string functions are available, returns mb_substr() and falls
 * back to substr() if multibyte functions are not available.
 *
 * @param string   $str The source string.
 * @param int      $start  If start is non-negative, the returned string will
 * start at the start'th position in str, counting from zero. If start is
 * negative, the returned string will start at the start'th character from the
 * end of string.
 * @param int|null $length Maximum number of characters to use from string. If
 * omitted or null is passed, extract all characters to the end of the string.
 *
 * @return string $str The string in lowercase.
 */
function relevanssi_substr( $str, int $start, $length = null ) {
	if ( ! is_string( $str ) ) {
		$str = strval( $str );
	}
	if ( ! function_exists( 'mb_substr' ) ) {
		return substr( $str, $start, $length );
	} else {
		return mb_substr( $str, $start, $length );
	}
}

/**
 * Prints out the post excerpt.
 *
 * Prints out the post excerpt from $post->post_excerpt, unless the post is
 * protected. Only works in the Loop.
 *
 * @see post_password_required() Used to check for password requirements.
 *
 * @global $post The global post object.
 */
function relevanssi_the_excerpt() {
	global $post;
	if ( ! post_password_required( $post ) ) {
		echo '<p>' . $post->post_excerpt . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		esc_html_e( 'There is no excerpt because this is a protected post.', 'relevanssi' );
	}
}

/**
 * Echoes out the permalink to the current post within Loop.
 *
 * Uses get_permalink() to get the permalink, then adds the 'highlight'
 * parameter if necessary using relevanssi_add_highlight(), then echoes it out.
 *
 * @param int|WP_Post $post Post ID or post object. Default is the global $post.
 *
 * @uses relevanssi_get_permalink() Fetches the current post permalink.
 */
function relevanssi_the_permalink( $post = 0 ) {
	echo esc_url( relevanssi_get_permalink( $post ) );
}

/**
 * Prints out a list of tags for post.
 *
 * Replacement for the_tags() that does the same, but applies Relevanssi search term
 * highlighting on the results.
 *
 * @param string  $before    What is printed before the tags, default ''.
 * @param string  $separator The separator between items, default ', '.
 * @param string  $after     What is printed after the tags, default ''.
 * @param boolean $echoed    If true, echo, otherwise return the result. Default true.
 * @param int     $post_id   The post ID. Default current post ID (in the Loop).
 */
function relevanssi_the_tags( string $before = '', string $separator = ', ', string $after = '', bool $echoed = true, int $post_id = 0 ) {
	$tag_list = get_the_tag_list( $before, $separator, $after, $post_id );
	$found    = preg_match_all( '~<a href=".*?" rel="tag">(.*?)</a>~', $tag_list, $matches );
	if ( $found ) {
		$originals   = $matches[0];
		$tag_names   = $matches[1];
		$highlighted = array();

		$count = count( $matches[0] );
		for ( $i = 0; $i < $count; $i++ ) {
			$highlighted_tag_name = relevanssi_highlight_terms( $tag_names[ $i ], get_search_query(), true );
			$highlighted[ $i ]    = str_replace( '>' . $tag_names[ $i ] . '<', '>' . $highlighted_tag_name . '<', $originals[ $i ] );
		}

		$tag_list = str_replace( $originals, $highlighted, $tag_list );
	}

	if ( $echoed ) {
		echo $tag_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $tag_list;
	}
}

/**
 * Prints out post title with highlighting.
 *
 * Uses the global $post object. Reads the highlighted title from
 * $post->post_highlighted_title. This used to accept one parameter, the
 * `$echo` boolean, but in 2.12.3 / 4.10.3 the function signature was matched
 * to copy `the_title()` function in WordPress core. The original behaviour is
 * still supported: `relevanssi_the_title()` without arguments works exactly as
 * before and `relevanssi_the_title( false )` returns the title.
 *
 * @global object $post The global post object.
 *
 * @param boolean|string $before Markup to prepend to the title. Can also be a
 * boolean for whether to echo or return the title.
 * @param string         $after  Markup to append to the title.
 * @param boolean        $echoed   Whether to echo or return the title. Default
 * true for echo.
 *
 * @return void|string Void if $echoed argument is true, current post title with
 * highlights if $echoed is false.
 */
function relevanssi_the_title( $before = true, string $after = '', bool $echoed = true ) {
	if ( true === $before ) {
		$before = '';
		$echoed = true;
	} elseif ( false === $before ) {
		$before = '';
		$echoed = false;
	}
	global $post;
	if ( empty( $post->post_highlighted_title ) ) {
		$post->post_highlighted_title = $post->post_title;
	}
	if ( relevanssi_strlen( $post->post_highlighted_title ) === 0 ) {
		return;
	}
	$title = $before . $post->post_highlighted_title . $after;
	if ( $echoed ) {
		echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $title;
	}
}

/**
 * Turns off options, ie. sets them to "off".
 *
 * If the specified options don't exist in the request array, they are set to
 * "off".
 *
 * @param array $request The _REQUEST array, passed as reference.
 * @param array $options An array of option names.
 */
function relevanssi_turn_off_options( array &$request, array $options ) {
	array_walk(
		$options,
		function ( $option ) use ( &$request ) {
			if ( ! isset( $request[ $option ] ) ) {
				$request[ $option ] = 'off';
			}
		}
	);
}

/**
 * Sets an option after doing floatval.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param boolean $autoload Should the option autoload, default true.
 * @param float   $def_val  The default value if floatval() fails, default 0.
 * @param boolean $positive If true, replace negative values and zeroes with
 * $def_val.
 */
function relevanssi_update_floatval( array $request, string $option, bool $autoload = true, float $def_val = 0, bool $positive = false ) {
	if ( isset( $request[ $option ] ) ) {
		$value = floatval( $request[ $option ] );
		if ( ! $value ) {
			$value = $def_val;
		}
		if ( $positive && $value <= 0 ) {
			$value = $def_val;
		}
		update_option( $option, $value, $autoload );
	}
}

/**
 * Sets an option after doing intval.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param boolean $autoload Should the option autoload, default true.
 * @param int     $def_val  The default value if intval() fails, default 0.
 */
function relevanssi_update_intval( array $request, string $option, bool $autoload = true, int $def_val = 0 ) {
	if ( isset( $request[ $option ] ) ) {
		$value = intval( $request[ $option ] );
		if ( ! $value ) {
			$value = $def_val;
		}
		update_option( $option, $value, $autoload );
	}
}

/**
 * Sets an option with one of the listed legal values.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param array   $values   The legal values.
 * @param string  $def_val  The default value.
 * @param boolean $autoload Should the option autoload, default true.
 */
function relevanssi_update_legal_value( array $request, string $option, array $values, string $def_val, bool $autoload = true ) {
	if ( isset( $request[ $option ] ) ) {
		$value = $def_val;
		if ( in_array( $request[ $option ], $values, true ) ) {
			$value = $request[ $option ];
		}
		update_option( $option, $value, $autoload );
	}
}

/**
 * Sets an on/off option according to the request value.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param boolean $autoload Should the option autoload, default true.
 */
function relevanssi_update_off_or_on( array $request, string $option, bool $autoload = true ) {
	relevanssi_update_legal_value(
		$request,
		$option,
		array( 'off', 'on' ),
		'off',
		$autoload
	);
}

/**
 * Sets an option after sanitizing and unslashing the value.
 *
 * @param array   $request  An array of option values.
 * @param string  $option   The key to check.
 * @param boolean $autoload Should the option autoload, default true.
 */
function relevanssi_update_sanitized( array $request, string $option, bool $autoload = true ) {
	if ( isset( $request[ $option ] ) ) {
		$value = sanitize_text_field( wp_unslash( $request[ $option ] ) );
		update_option( $option, $value, $autoload );
	}
}

/**
 * Returns true if $_SERVER['HTTP_USER_AGENT'] is on the bot block list.
 *
 * Looks for bot user agents in the $_SERVER['HTTP_USER_AGENT'] and returns true
 * if a match is found.
 *
 * @return bool True if $_SERVER['HTTP_USER_AGENT'] is a bot.
 */
function relevanssi_user_agent_is_bot(): bool {
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		/**
		 * Filters the bots Relevanssi should block from search queries.
		 *
		 * Lets you filter the bots that are blocked from Relevanssi search
		 * queries.
		 *
		 * @param array $bots An array of bot user agents.
		 */
		$bots = apply_filters( 'relevanssi_bots_to_block', relevanssi_bot_block_list() );
		foreach ( array_values( $bots ) as $lookfor ) {
			if ( false !== stristr( $_SERVER['HTTP_USER_AGENT'], $lookfor ) ) {
				return true;
			}
		}
	}
	return false;
}
