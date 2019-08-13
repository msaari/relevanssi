<?php
/**
 * /lib/sorting.php
 *
 * Sorting functions.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Gets the next key-direction pair from the orderby array.
 *
 * Fetches a key-direction pair from the orderby array. Converts key names to match
 * the post object parameters when necessary and seeds the random generator, if
 * required.
 *
 * @param array $orderby An array of key-direction pairs.
 *
 * @return array A set of 'key', 'dir' for direction and 'compare' for proper
 * comparison method.
 */
function relevanssi_get_next_key( &$orderby ) {
	if ( ! is_array( $orderby ) || count( $orderby ) < 1 ) {
		// Nothing to see here.
		return array(
			'key'     => null,
			'dir'     => null,
			'compare' => null,
		);
	}

	list( $key ) = array_keys( $orderby );
	$dir         = $orderby[ $key ];
	unset( $orderby[ $key ] );

	$key = strtolower( $key );

	if ( 'rand' === strtolower( $dir ) ) {
		$key = 'rand';
	}

	// Correcting the key for couple of shorthand cases.
	switch ( $key ) {
		case 'title':
			$key = 'post_title';
			break;
		case 'date':
			$key = 'post_date';
			break;
		case 'modified':
			$key = 'post_modified';
			break;
		case 'parent':
			$key = 'post_parent';
			break;
		case 'type':
			$key = 'post_type';
			break;
		case 'name':
			$key = 'post_name';
			break;
		case 'author':
			$key = 'post_author';
			break;
		case 'relevance':
			$key = 'relevance_score';
			break;
	}

	$numeric_keys = array( 'meta_value_num', 'menu_order', 'ID', 'post_parent', 'post_author', 'comment_count', 'relevance_score' );
	$date_keys    = array( 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt' );
	$filter_keys  = array( 'post_type' );

	$compare = 'string';
	if ( in_array( $key, $numeric_keys, true ) ) {
		$compare = 'number';
	} elseif ( in_array( $key, $filter_keys, true ) ) {
		$compare = 'filter';
	} elseif ( in_array( $key, $date_keys, true ) ) {
		$compare = 'date';
	}

	/**
	 * Lets you choose the compare method for fields.
	 *
	 * @param string $compare The compare method, can be 'string', 'number' or
	 * 'date'.
	 * @param string $key     The name of the custom field key.
	 *
	 * @return string The compare method.
	 */
	$compare = apply_filters( 'relevanssi_sort_compare', $compare, $key );
	if ( ! in_array( $compare, array( 'string', 'number', 'date', 'filter' ), true ) ) {
		// Not a valid value, fall back.
		$compare = 'string';
	}

	if ( 'rand(' === substr( $key, 0, 5 ) ) {
		$parts = explode( '(', $key );
		$dir   = intval( trim( str_replace( ')', '', $parts[1] ) ) );
		$key   = 'rand';
	}
	if ( 'rand' === $key ) {
		if ( is_numeric( $dir ) ) {
			// A specific random seed is requested.
			mt_srand( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	} else {
		$dir = strtolower( $dir );
		if ( 'asc' !== $dir ) {
			$dir = 'desc';
		}
	}

	$values = array(
		'key'     => $key,
		'dir'     => $dir,
		'compare' => $compare,
	);
	return $values;
}

/**
 * Gets the values for comparing items for given key.
 *
 * Fetches the key values for the item pair. If random order is required, this
 * function will randomize the order.
 *
 * @global array $relevanssi_meta_query The meta query used for the sorting.
 *
 * @param string $key    The key used.
 * @param object $item_1 The first post object to compare.
 * @param object $item_2 The second post object to compare.
 *
 * @return array Array with the key values: 'key1' and 'key2', respectively.
 */
function relevanssi_get_compare_values( $key, $item_1, $item_2 ) {
	if ( 'rand' === $key ) {
		do {
			$key1 = wp_rand();
			$key2 = wp_rand();
		} while ( $key1 === $key2 );
		$keys = array(
			'key1' => $key1,
			'key2' => $key2,
		);
		return $keys;
	}
	$key1 = '';
	$key2 = '';
	if ( 'meta_value' === $key || 'meta_value_num' === $key ) {
		global $wp_query;
		// Get the name of the field from the global WP_Query.
		$key = $wp_query->query_vars['meta_key'];

		if ( empty( $key ) ) {
			// If empty, try the Relevanssi meta_query.
			global $relevanssi_meta_query;
			foreach ( $relevanssi_meta_query as $meta_row ) {
				// There may be many rows. Choose the one where there's just key
				// and no value.
				if ( ! is_array( $meta_row ) ) {
					continue;
				}
				if ( isset( $meta_row['value'] ) ) {
					continue;
				}
				if ( isset( $meta_row['key'] ) ) {
					$key = $meta_row['key'];
				}
			}

			if ( empty( $key ) ) {
				// The key is not set.
				return array( '', '' );
			}
		}
		$key1 = get_post_meta( $item_1->ID, $key, true );
		if ( empty( $key1 ) ) {
			/**
			 * Adds in a missing sorting value.
			 *
			 * In some cases the sorting method may not have values for all posts
			 * (for example when sorting by 'menu_order'). If you still want to use
			 * a sorting method like this, you can use this function to fill in a
			 * value (in the case of 'menu_order', for example, one could use
			 * PHP_INT_MAX.)
			 *
			 * @param string $key1 The value to filter.
			 * @param string $key  The name of the key.
			 */
			$key1 = apply_filters( 'relevanssi_missing_sort_key', $key1, $key );
		}

		$key2 = get_post_meta( $item_2->ID, $key, true );
		if ( empty( $key2 ) ) {
			/**
			 * Documented in lib/sorting.php.
			 */
			$key2 = apply_filters( 'relevanssi_missing_sort_key', $key2, $key );
		}
	} else {
		global $relevanssi_meta_query;
		if ( isset( $item_1->$key ) ) {
			$key1 = relevanssi_strtolower( $item_1->$key );
		} elseif ( isset( $relevanssi_meta_query[ $key ] ) ) {
			// Named meta queries.
			$key1 = get_post_meta( $item_1->ID, $relevanssi_meta_query[ $key ]['key'], true );
		} else {
			/**
			 * Documented in lib/sorting.php.
			 */
			$key1 = apply_filters( 'relevanssi_missing_sort_key', $key1, $key );
		}
		if ( isset( $item_2->$key ) ) {
			$key2 = relevanssi_strtolower( $item_2->$key );
		} elseif ( isset( $relevanssi_meta_query[ $key ] ) ) {
			// Named meta queries.
			$key2 = get_post_meta( $item_2->ID, $relevanssi_meta_query[ $key ]['key'], true );
		} else {
			/**
			 * Documented in lib/sorting.php.
			 */
			$key2 = apply_filters( 'relevanssi_missing_sort_key', $key2, $key );
		}
	}

	if ( is_array( $key1 ) ) {
		$key1 = relevanssi_flatten_array( $key1 );
	}
	if ( is_array( $key2 ) ) {
		$key2 = relevanssi_flatten_array( $key2 );
	}

	$keys = array(
		'key1' => $key1,
		'key2' => $key2,
	);
	return $keys;
}

/**
 * Compares two values.
 *
 * Compares two sorting keys using date based comparison, string comparison or
 * numeric comparison.
 *
 * @param string $key1 The first key.
 * @param string $key2 The second key.
 * @param string $compare The comparison method; possible values are 'date' for
 * date comparisons and 'string' for string comparison, everything else is
 * considered a numeric comparison.
 *
 * @return int $val Returns < 0 if key1 is less than key2; > 0 if key1 is greater
 * than key2, and 0 if they are equal.
 */
function relevanssi_compare_values( $key1, $key2, $compare ) {
	$val = 0;
	if ( 'date' === $compare ) {
		if ( strtotime( $key1 ) > strtotime( $key2 ) ) {
			$val = 1;
		} elseif ( strtotime( $key1 ) < strtotime( $key2 ) ) {
			$val = -1;
		}
	} elseif ( 'string' === $compare ) {
		$val = relevanssi_mb_strcasecmp( $key1, $key2 );
	} elseif ( 'filter' === $compare ) {
		$val = relevanssi_filter_compare( $key1, $key2 );
	} else {
		if ( $key1 > $key2 ) {
			$val = 1;
		} elseif ( $key1 < $key2 ) {
			$val = -1;
		}
	}
	return $val;
}

/**
 * Compares two values using order array from a filter.
 *
 * Compares two sorting keys using a sorted array that contains value => order pairs.
 * Uses the 'relevanssi_comparison_order' filter to get the sorting guidance array.
 *
 * @param string $key1 The first key.
 * @param string $key2 The second key.
 *
 * @return int $val Returns < 0 if key1 is less than key2; > 0 if key1 is greater
 * than key2, and 0 if they are equal.
 */
function relevanssi_filter_compare( $key1, $key2 ) {
	/**
	 * Provides the sorting order for the filter.
	 *
	 * The array should contain the possible key values as keys and their order in
	 * the values, like this:
	 *
	 * $order = array(
	 *     'post' => 0,
	 *     'page' => 1,
	 *     'book' => 2,
	 * );
	 *
	 * This would sort posts first, pages second, books third. Values that do not
	 * appear in the array are sorted last.
	 *
	 * @param array Sorting guidance array.
	 */
	$order = apply_filters( 'relevanssi_comparison_order', array() );

	// Set the default values so that if the key is not found in the array, it's last.
	$max_key = max( $order );
	$val_1   = $max_key + 1;
	$val_2   = $max_key + 1;

	if ( isset( $order[ $key1 ] ) ) {
		$val_1 = $order[ $key1 ];
	}
	if ( isset( $order[ $key2 ] ) ) {
		$val_2 = $order[ $key2 ];
	}

	return $val_1 - $val_2;
}

/**
 * Compares values using multiple levels of sorting keys.
 *
 * Comparison function for usort() using multiple levels of sorting methods. If one
 * level produces a tie, the sort will get a next level of sorting methods.
 *
 * @global array $relevanssi_keys     An array of sorting keys by level.
 * @global array $relevanssi_dirs     An array of sorting directions by level.
 * @global array $relevanssi_compares An array of comparison methods by level.
 *
 * @param object $a A post object.
 * @param object $b A post object.
 *
 * @return int $val Returns < 0 if a is less than b; > 0 if a is greater
 * than b, and 0 if they are equal.
 */
function relevanssi_cmp_function( $a, $b ) {
	global $relevanssi_keys, $relevanssi_dirs, $relevanssi_compares;

	$level = -1;
	$val   = 0;

	while ( 0 === $val ) {
		$level++;
		if ( ! isset( $relevanssi_keys[ $level ] ) ) {
			// No more levels; we've hit the bedrock.
			$level--;
			break;
		}
		$compare        = $relevanssi_compares[ $level ];
		$compare_values = relevanssi_get_compare_values( $relevanssi_keys[ $level ], $a, $b );
		$val            = relevanssi_compare_values( $compare_values['key1'], $compare_values['key2'], $compare );
	}
	if ( 'desc' === $relevanssi_dirs[ $level ] ) {
		$val = $val * -1;
	}

	return $val;
}

/**
 * Sorts post objects.
 *
 * Sorts post objects using multiple levels of sorting methods. This function was
 * originally written by Matthew Hood and published in the PHP manual comments.
 * The actual sorting is handled by relevanssi_cmp_function().
 *
 * @global array $relevanssi_keys       An array of sorting keys by level.
 * @global array $relevanssi_dirs       An array of sorting directions by level.
 * @global array $relevanssi_compares   An array of comparison methods by level.
 * @global array $relevanssi_meta_query The meta query array.
 *
 * @param array $data       The posts to sort are in $data[0], used as a reference.
 * @param array $orderby    The array of orderby rules with directions.
 * @param array $meta_query The meta query array, in case it's needed for meta
 * query based sorting.
 */
function relevanssi_object_sort( &$data, $orderby, $meta_query ) {
	global $relevanssi_keys, $relevanssi_dirs, $relevanssi_compares, $relevanssi_meta_query;

	$relevanssi_keys       = array();
	$relevanssi_dirs       = array();
	$relevanssi_compares   = array();
	$relevanssi_meta_query = $meta_query; // Store in a global variable to avoid complicated parameter passing.

	do {
		$values = relevanssi_get_next_key( $orderby );
		if ( ! empty( $values['key'] ) ) {
			$relevanssi_keys[]     = $values['key'];
			$relevanssi_dirs[]     = $values['dir'];
			$relevanssi_compares[] = $values['compare'];
		}
	} while ( ! empty( $values['key'] ) );

	usort( $data, 'relevanssi_cmp_function' );
}

/**
 * Sorts strings by length.
 *
 * A sorting function that sorts strings by length. Uses relevanssi_strlen() to
 * count the string length.
 *
 * @param string $a String A.
 * @param string $b String B.
 *
 * @return int Negative value, if string A is longer; zero, if strings are equally
 * long; positive, if string B is longer.
 */
function relevanssi_strlen_sort( $a, $b ) {
	return relevanssi_strlen( $b ) - relevanssi_strlen( $a );
}
