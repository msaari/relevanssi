<?php
/**
 * /lib/common.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Filters posts based on WPML language.
 *
 * Attaches to 'relevanssi_hits_filter' to restrict WPML searches to the current
 * language. Whether this filter is used or not depends on the option
 * 'relevanssi_wpml_only_current'. Thanks to rvencu for the initial code.
 *
 * @global object $sitepress The WPML global object.
 *
 * @param array $data Index 0 has the array of results, index 1 has the search query.
 *
 * @return array $data The whole parameter array, with the filtered posts in the index 0.
 */
function relevanssi_wpml_filter( $data ) {
	$filter_enabled = get_option( 'relevanssi_wpml_only_current' );
	if ( 'on' === $filter_enabled ) {
		$current_blog_language = get_bloginfo( 'language' );
		$filtered_hits         = array();
		foreach ( $data[0] as $hit ) {
			if ( is_integer( $hit ) ) {
				// In case "fields" is set to "ids", fetch the post object we need.
				$hit = get_post( $hit );
			}

			if ( isset( $hit->blog_id ) ) {
				// This is a multisite search.
				switch_to_blog( $hit->blog_id );
				if ( function_exists( 'icl_object_id' ) ) {
					// Reset the WPML cache when blog is switched, otherwise WPML
					// will be confused.
					global $wpml_post_translations;
					$wpml_post_translations->reload();
				}
			}

			global $sitepress;

			// Check if WPML is used.
			if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_is_translated_post_type' ) ) {
				if ( $sitepress->is_translated_post_type( $hit->post_type ) ) {
					$id = apply_filters( 'wpml_object_id', $hit->ID, $hit->post_type, false );
					// This is a post in a translated post type.
					if ( intval( $hit->ID ) === $id ) {
						// The post exists in the current language, and can be included.
						$filtered_hits[] = $hit;
					}
				} else {
					// This is not a translated post type, so include all posts.
					$filtered_hits[] = $hit;
				}
			} elseif ( get_bloginfo( 'language' ) === $current_blog_language ) {
				// If there is no WPML but the target blog has identical language with current blog,
				// we use the hits. Note en-US is not identical to en-GB!
				$filtered_hits[] = $hit;
			}

			if ( isset( $hit->blog_id ) ) {
				restore_current_blog();
			}
		}

		return array( $filtered_hits, $data[1] );
	}

	return $data;
}

/**
 * Removes the Polylang language filters.
 *
 * If the Polylang allow all option is enabled ('relevanssi_polylang_all_languages'),
 * removes the Polylang language filter. By default Polylang filters the languages
 * using a taxonomy query.
 *
 * @param object $query WP_Query object we need to clean up.
 */
function relevanssi_polylang_filter( $query ) {
	$polylang_allow_all = get_option( 'relevanssi_polylang_all_languages' );
	if ( 'on' === $polylang_allow_all ) {
		$ok_queries = array();

		if ( ! isset( $query->tax_query ) ) {
			// No tax query set, backing off.
			return;
		}

		if ( ! isset( $query->tax_query->queries ) || ! is_array( $query->tax_query->queries ) ) {
			// No tax query set, backing off.
			return;
		}

		foreach ( $query->tax_query->queries as $tax_query ) {
			if ( 'language' !== $tax_query['taxonomy'] ) {
				// Not a language tax query.
				$ok_queries[] = $tax_query;
			}
		}
		$query->tax_query->queries = $ok_queries;

		if ( isset( $query->query_vars['tax_query'] ) ) {
			// Tax queries can be here as well, so let's sweep this one too.
			$ok_queries = array();
			foreach ( $query->query_vars['tax_query'] as $tax_query ) {
				if ( 'language' !== $tax_query['taxonomy'] ) {
					$ok_queries[] = $tax_query;
				}
			}
			$query->query_vars['tax_query'] = $ok_queries;
		}

		if ( isset( $query->query_vars['taxonomy'] ) && 'language' === $query->query_vars['taxonomy'] ) {
			// Another way to set the taxonomy.
			unset( $query->query_vars['taxonomy'] );
			unset( $query->query_vars['term'] );
		}
	}

	return $query;
}

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

	$numeric_keys = array( 'menu_order', 'ID', 'post_parent', 'post_author', 'comment_count', 'relevance_score' );
	$date_keys    = array( 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt' );

	$compare = 'string';
	if ( in_array( $key, $numeric_keys, true ) ) {
		$compare = 'number';
	} elseif ( in_array( $key, $date_keys, true ) ) {
		$compare = 'date';
	}

	if ( 'rand' === $key ) {
		if ( is_numeric( $dir ) ) {
			// A specific random seed is requested.
			mt_srand( $dir );
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
 * @global object $wp_query The global WP_Query object.
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
			$key1 = rand();
			$key2 = rand();
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
		if ( ! isset( $key ) ) {
			// The key is not set.
			return array( '', '' );
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
			 * Documented in lib/common.php.
			 */
			$key2 = apply_filters( 'relevanssi_missing_sort_key', $key2, $key );
		}
	} else {
		if ( isset( $item_1->$key ) ) {
			$key1 = relevanssi_strtolower( $item_1->$key );
		} else {
			/**
			 * Documented in lib/common.php.
			 */
			$key1 = apply_filters( 'relevanssi_missing_sort_key', $key1, $key );
		}
		if ( isset( $item_2->$key ) ) {
			$key2 = relevanssi_strtolower( $item_2->$key );
		} else {
			/**
			 * Documented in lib/common.php.
			 */
			$key2 = apply_filters( 'relevanssi_missing_sort_key', $key2, $key );
		}
	}

	$keys = array(
		'key1' => $key1,
		'key2' => $key2,
	);
	return $keys;
}

/**
 * Compares to values.
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
 * Multibyte friendly case-insensitive string comparison.
 *
 * If multibyte string functions are available, do strcmp() after using
 * mb_strtoupper() to both strings. Otherwise use strcasecmp().
 *
 * @param string $str1     First string to compare.
 * @param string $str2     Second string to compare.
 * @param string $encoding The encoding to use. Defaults to mb_internal_encoding().
 *
 * @return int $val Returns < 0 if str1 is less than str2; > 0 if str1 is greater
 * than str2, and 0 if they are equal.
 */
function relevanssi_mb_strcasecmp( $str1, $str2, $encoding = null ) {
	if ( ! function_exists( 'mb_internal_encoding' ) ) {
		return strcasecmp( $str1, $str2 );
	} else {
		if ( null === $encoding ) {
			$encoding = mb_internal_encoding();
		}
		return strcmp( mb_strtoupper( $str1, $encoding ), mb_strtoupper( $str2, $encoding ) );
	}
}

/**
 * Multibyte friendly strtolower.
 *
 * If multibyte string functions are available, returns mb_strtolower() and falls
 * back to strtolower() if multibyte functions are not available.
 *
 * @param string $string The string to lowercase.
 *
 * @return string $string The string in lowercase.
 */
function relevanssi_strtolower( $string ) {
	if ( ! function_exists( 'mb_strtolower' ) ) {
		return strtolower( $string );
	} else {
		return mb_strtolower( $string );
	}
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
 * @global array $relevanssi_keys     An array of sorting keys by level.
 * @global array $relevanssi_dirs     An array of sorting directions by level.
 * @global array $relevanssi_compares An array of comparison methods by level.
 *
 * @param array $data    The posts to sort are in $data[0], used as a reference.
 * @param array $orderby The array of orderby rules with directions.
 */
function relevanssi_object_sort( &$data, $orderby ) {
	global $relevanssi_keys, $relevanssi_dirs, $relevanssi_compares;

	$relevanssi_keys     = array();
	$relevanssi_dirs     = array();
	$relevanssi_compares = array();

	do {
		$values = relevanssi_get_next_key( $orderby );
		if ( ! empty( $values['key'] ) ) {
			$relevanssi_keys[]     = $values['key'];
			$relevanssi_dirs[]     = $values['dir'];
			$relevanssi_compares[] = $values['compare'];
		}
	} while ( ! empty( $values['key'] ) );

	$primary_key = $relevanssi_keys[0];
	if ( ! isset( $data[0]->$primary_key ) ) {
		// Trying to sort by a non-existent key.
		return;
	}

	usort( $data, 'relevanssi_cmp_function' );
}

/**
 * Generates the search result breakdown added to the search results.
 *
 * Gets the source data, generates numbers of it and then replaces the placeholders
 * in the breakdown template with the data.
 *
 * @param array $data The source data.
 * @param int   $hit  The post ID.
 *
 * @return string The search results breakdown for the post.
 */
function relevanssi_show_matches( $data, $hit ) {
	if ( isset( $data['body_matches'][ $hit ] ) ) {
		$body = $data['body_matches'][ $hit ];
	} else {
		$body = 0;
	}
	if ( isset( $data['title_matches'][ $hit ] ) ) {
		$title = $data['title_matches'][ $hit ];
	} else {
		$title = 0;
	}
	if ( isset( $data['tag_matches'][ $hit ] ) ) {
		$tag = $data['tag_matches'][ $hit ];
	} else {
		$tag = 0;
	}
	if ( isset( $data['category_matches'][ $hit ] ) ) {
		$category = $data['category_matches'][ $hit ];
	} else {
		$category = 0;
	}
	if ( isset( $data['taxonomy_matches'][ $hit ] ) ) {
		$taxonomy = $data['taxonomy_matches'][ $hit ];
	} else {
		$taxonomy = 0;
	}
	if ( isset( $data['comment_matches'][ $hit ] ) ) {
		$comment = $data['comment_matches'][ $hit ];
	} else {
		$comment = 0;
	}
	if ( isset( $data['scores'][ $hit ] ) ) {
		$score = round( $data['scores'][ $hit ], 2 );
	} else {
		$score = 0;
	}
	if ( isset( $data['term_hits'][ $hit ] ) ) {
		$term_hits_array = $data['term_hits'][ $hit ];
		arsort( $term_hits_array );
	} else {
		$term_hits_array = array();
	}

	$term_hits  = '';
	$total_hits = 0;
	foreach ( $term_hits_array as $term => $hits ) {
		$term_hits  .= " $term: $hits";
		$total_hits += $hits;
	}

	$text          = stripslashes( get_option( 'relevanssi_show_matches_text' ) );
	$replace_these = array( '%body%', '%title%', '%tags%', '%categories%', '%taxonomies%', '%comments%', '%score%', '%terms%', '%total%' );
	$replacements  = array( $body, $title, $tag, $category, $taxonomy, $comment, $score, $term_hits, $total_hits );
	$result        = ' ' . str_replace( $replace_these, $replacements, $text );

	/**
	 * Filters the search result breakdown.
	 *
	 * If you use the "Show breakdown of search hits in excerpts" option, this
	 * filter lets you modify the breakdown before it is added to the excerpt.
	 *
	 * @param string $result The breakdown.
	 */
	return apply_filters( 'relevanssi_show_matches', $result );
}

/**
 * Adds the search query to the log.
 *
 * Logs the search query, trying to avoid bots.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables, used for database table names.
 *
 * @param string $query The search query.
 * @param int    $hits  The number of hits found.
 */
function relevanssi_update_log( $query, $hits ) {
	// Bot filter, by Justin_K.
	// See: http://wordpress.org/support/topic/bot-logging-problem-w-tested-solution.
	$user_agent = '';
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$bots       = array( 'Google' => 'Mediapartners-Google' );

		/**
		 * Filters the bots Relevanssi should block from logs.
		 *
		 * Lets you filter the bots that are blocked from Relevanssi logs.
		 *
		 * @param array $bots An array of bot user agents.
		 */
		$bots = apply_filters( 'relevanssi_bots_to_not_log', $bots );
		foreach ( $bots as $name => $lookfor ) {
			if ( false !== stristr( $user_agent, $lookfor ) ) {
				return;
			}
		}
	}

	/**
	 * Filters the current user for logs.
	 *
	 * The current user is checked before logging a query to omit particular users.
	 * You can use this filter to filter out the user.
	 *
	 * @param object The current user object.
	 */
	$user = apply_filters( 'relevanssi_log_get_user', wp_get_current_user() );
	if ( 0 !== $user->ID && get_option( 'relevanssi_omit_from_logs' ) ) {
		$omit = explode( ',', get_option( 'relevanssi_omit_from_logs' ) );
		if ( in_array( strval( $user->ID ), $omit, true ) ) {
			return;
		}
		if ( in_array( $user->user_login, $omit, true ) ) {
			return;
		}
	}

	$ip = '';
	if ( 'on' === get_option( 'relevanssi_log_queries_with_ip' ) ) {
		/**
		 * Filters the IP address of the searcher.
		 *
		 * Relevanssi may store the IP address of the searches in the logs. If the
		 * setting is enabled, this filter can be used to filter out the IP address
		 * before the log entry is made.
		 *
		 * Do note that storing the IP address may be illegal or get you in GDPR
		 * trouble.
		 *
		 * @param string $ip The IP address, from $_SERVER['REMOTE_ADDR'].
		 */
		$ip = apply_filters( 'relevanssi_remote_addr', $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Filters whether a query should be logged or not.
	 *
	 * This filter can used to determine whether a query should be logged or not.
	 *
	 * @param boolean $ok_to_log  Can the query be logged.
	 * @param string  $query      The actual query string.
	 * @param int     $hits       The number of hits found.
	 * @param string  $user_agent The user agent that made the search.
	 * @param string  $ip         The IP address the search came from (or empty).
	 */
	$ok_to_log = apply_filters( 'relevanssi_ok_to_log', true, $query, $hits, $user_agent, $ip );
	if ( $ok_to_log ) {
		global $wpdb, $relevanssi_variables;

		$wpdb->query(
			$wpdb->prepare( 'INSERT INTO ' . $relevanssi_variables['log_table'] . ' (query, hits, user_id, ip, time) VALUES (%s, %d, %d, %s, NOW())',
			$query, intval( $hits ), $user->ID, $ip )
		); // WPCS: unprepared SQL ok, Relevanssi database table name.
	}
}

/**
 * Trims Relevanssi log table.
 *
 * Trims Relevanssi log table, using the day interval setting from 'relevanssi_trim_logs'.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables, used for database table names.
 */
function relevanssi_trim_logs() {
	global $wpdb, $relevanssi_variables;
	$interval = intval( get_option( 'relevanssi_trim_logs' ) );
	$wpdb->query(
		$wpdb->prepare( 'DELETE FROM ' . $relevanssi_variables['log_table'] . ' WHERE time < TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))',
		$interval )
	); // WPCS: unprepared SQL ok, Relevanssi database table name.
}

/**
 * Checks whether the user is allowed to see the post.
 *
 * The default behaviour on 'relevanssi_post_ok' filter hook. Do note that while
 * this function takes $post_ok as a parameter, it actually doesn't care much
 * about the previous value, and will instead overwrite it. If you want to make
 * sure your value is preserved, either disable this default function, or run
 * your function on a later priority (this defaults to 10).
 *
 * Includes support for various membership plugins. Currently supports Members,
 * Groups, Simple Membership and s2member.
 *
 * @param boolean $post_ok Can the post be shown to the user.
 * @param int     $post_id The post ID.
 *
 * @return boolean $post_ok True if the user is allowed to see the post,
 * otherwise false.
 */
function relevanssi_default_post_ok( $post_ok, $post_id ) {
	$status = relevanssi_get_post_status( $post_id );

	// If it's not public, don't show.
	if ( 'publish' !== $status ) {
		$post_ok = false;
	}

	// Let's look a bit closer at private posts.
	if ( 'private' === $status ) {
		$post_ok = false;

		$type = relevanssi_get_post_type( $post_id );
		if ( isset( $GLOBALS['wp_post_types'][ $type ]->cap->read_private_posts ) ) {
			$cap = $GLOBALS['wp_post_types'][ $type ]->cap->read_private_posts;
		} else {
			// Just guessing here.
			$cap = 'read_private_' . $type . 's';
		}
		if ( current_user_can( $cap ) ) {
			// Current user has the required capabilities and can see the page.
			$post_ok = true;
		}

		if ( function_exists( 'members_can_current_user_view_post' ) ) {
			// Members.
			$post_ok = members_can_current_user_view_post( $post_id );
		}
	}

	if ( defined( 'GROUPS_CORE_VERSION' ) ) {
		// Groups.
		$current_user = wp_get_current_user();
		$post_ok      = Groups_Post_Access::user_can_read_post( $post_id, $current_user->ID );
	}
	if ( class_exists( 'MeprUpdateCtrl' ) && MeprUpdateCtrl::is_activated() ) {
		// Memberpress.
		$post    = get_post( $post_id );
		$post_ok = ! MeprRule::is_locked( $post );
	}
	if ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
		// Simple Membership.
		$logged_in = SwpmMemberUtils::is_member_logged_in();
		if ( ! $logged_in ) {
			$post_ok = false;
		} else {
			$access_ctrl = SwpmAccessControl::get_instance();
			$post_ok     = $access_ctrl->can_i_read_post( $post_id );
		}
	}

	/**
	 * Filters statuses allowed in admin searches.
	 *
	 * By default, admin searches may show posts that have 'draft', 'pending' and
	 * 'future' status (in addition to 'publish' and 'private'). If you use custom
	 * statuses and want them included in the admin search, you can add the statuses
	 * using this filter.
	 *
	 * @param array $statuses Array of statuses to accept.
	 */
	if ( in_array( $status, apply_filters( 'relevanssi_valid_admin_status', array( 'draft', 'pending', 'future' ) ), true ) && is_admin() ) {
		// Only show drafts, pending and future posts in admin search.
		$post_ok = true;
	}

	return $post_ok;
}

/**
 * Populates the Relevanssi post array.
 *
 * This is a caching mechanism to reduce the number of database queries required.
 * This function fetches all post data for the matches found using single MySQL
 * query, instead of doing up to 500 separate get_post() queries.
 *
 * @global array  $relevanssi_post_array An array of fetched posts.
 * @global array  $relevanssi_post_types An array of post types, to be used by
 * relevanssi_get_post_type() (again to avoid DB calls).
 * @global object $wpdb                  The WordPress database interface.
 *
 * @param array $matches An array of search matches.
 */
function relevanssi_populate_array( $matches ) {
	global $relevanssi_post_array, $relevanssi_post_types, $wpdb;

	// Doing this makes life faster.
	wp_suspend_cache_addition( true );

	$ids = array();
	foreach ( $matches as $match ) {
		array_push( $ids, $match->doc );
	}

	$ids   = implode( ',', $ids );
	$posts = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE id IN ($ids)" ); // WPCS: unprepared SQL ok, no user-generated inputs.

	foreach ( $posts as $post ) {
		$relevanssi_post_array[ $post->ID ] = $post;
		$relevanssi_post_types[ $post->ID ] = $post->post_type;
	}

	// Re-enable caching.
	wp_suspend_cache_addition( false );
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
 * @return string $taxonomy The term taxonomy.
 */
function relevanssi_get_term_taxonomy( $term_id ) {
	global $wpdb;
	$taxonomy = $wpdb->get_var( $wpdb->prepare( "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d", $term_id ) ); // WPCS: Unprepared SQL ok, database table name.
	return $taxonomy;
}

/**
 * Extracts phrases from the search query.
 *
 * Finds all phrases wrapped in quotes from the search query.
 *
 * @param string $query The query.
 *
 * @return array An array of phrases (strings).
 */
function relevanssi_extract_phrases( $query ) {
	$strpos_function = 'strpos';
	if ( function_exists( 'mb_strpos' ) ) {
		$strpos_function = 'mb_strpos';
	}
	$substr_function = 'substr';
	if ( function_exists( 'mb_substr' ) ) {
		$substr_function = 'mb_substr';
	}

	$pos = call_user_func( $strpos_function, $query, '"' );

	$phrases = array();
	while ( false !== $pos ) {
		$start = $pos;
		$end   = call_user_func( $strpos_function, $query, '"', $start + 1 );

		if ( false === $end ) {
			// Just one " in the query.
			$pos = $end;
			continue;
		}
		$phrase = call_user_func( $substr_function, $query, $start + 1, $end - $start - 1 );
		$phrase = trim( $phrase );

		// Do not count single-word phrases as phrases.
		if ( ! empty( $phrase ) && str_word_count( $phrase ) > 1 ) {
			$phrases[] = $phrase;
		}
		$pos = $end;
	}

	return $phrases;
}

/**
 * Generates the MySQL code for restricting the search to phrase hits.
 *
 * This function uses relevanssi_extract_phrases() to figure out the phrases in the
 * search query, then generates MySQL queries to restrict the search to the posts
 * containing those phrases in the title, content, taxonomy terms or meta fields.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param string $search_query The search query.
 *
 * @return string $queries If not phrase hits are found, an empty string; otherwise
 * MySQL queries to restrict the search.
 */
function relevanssi_recognize_phrases( $search_query ) {
	global $wpdb;

	$phrases = relevanssi_extract_phrases( $search_query );

	$all_queries = array();
	if ( count( $phrases ) > 0 ) {
		foreach ( $phrases as $phrase ) {
			$queries = array();
			$phrase  = str_replace( '‘', '_', $phrase );
			$phrase  = str_replace( '’', '_', $phrase );
			$phrase  = str_replace( "'", '_', $phrase );
			$phrase  = str_replace( '"', '_', $phrase );
			$phrase  = str_replace( '”', '_', $phrase );
			$phrase  = str_replace( '“', '_', $phrase );
			$phrase  = str_replace( '„', '_', $phrase );
			$phrase  = str_replace( '´', '_', $phrase );
			$phrase  = $wpdb->esc_like( $phrase );
			$phrase  = esc_sql( $phrase );
			$excerpt = '';
			if ( 'on' === get_option( 'relevanssi_index_excerpt' ) ) {
				$excerpt = " OR post_excerpt LIKE '%$phrase%'";
			}

			$query = "(SELECT ID FROM $wpdb->posts
				WHERE (post_content LIKE '%$phrase%' OR post_title LIKE '%$phrase%' $excerpt)
				AND post_status IN ('publish', 'draft', 'private', 'pending', 'future', 'inherit'))";

			$queries[] = $query;

			$query = "(SELECT ID FROM $wpdb->posts as p, $wpdb->term_relationships as r, $wpdb->term_taxonomy as s, $wpdb->terms as t
				WHERE r.term_taxonomy_id = s.term_taxonomy_id AND s.term_id = t.term_id AND p.ID = r.object_id
				AND t.name LIKE '%$phrase%' AND p.post_status IN ('publish', 'draft', 'private', 'pending', 'future', 'inherit'))";

			$queries[] = $query;

			$query = "(SELECT ID
              FROM $wpdb->posts AS p, $wpdb->postmeta AS m
              WHERE p.ID = m.post_id
              AND m.meta_value LIKE '%$phrase%'
              AND p.post_status IN ('publish', 'draft', 'private', 'pending', 'future', 'inherit'))";

			$queries[] = $query;

			$queries       = implode( ' OR relevanssi.doc IN ', $queries );
			$queries       = "AND (relevanssi.doc IN $queries)";
			$all_queries[] = $queries;
		}
	} else {
		$phrases = '';
	}

	$all_queries = implode( ' ', $all_queries );

	return $all_queries;
}

/**
 * Strips invisible elements from text.
 *
 * Strips <style>, <script>, <object>, <embed>, <applet>, <noscript>, <noembed>,
 * <iframe>, and <del> tags and their contents from the text.
 *
 * @param string $text The source text.
 *
 * @return string The processed text.
 */
function relevanssi_strip_invisibles( $text ) {
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
		),
	' ', $text );
	return $text;
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

/**
 * Returns the custom fields to index.
 *
 * Returns a list of custom fields to index, based on the custom field indexing
 * setting.
 *
 * @return array|string An array of custom fields to index, or 'all' or 'visible'.
 */
function relevanssi_get_custom_fields() {
	$custom_fields = get_option( 'relevanssi_index_fields' );
	if ( $custom_fields ) {
		if ( 'all' === $custom_fields ) {
			return $custom_fields;
		} elseif ( 'visible' === $custom_fields ) {
			return $custom_fields;
		} else {
			$custom_fields       = explode( ',', $custom_fields );
			$count_custom_fields = count( $custom_fields );
			for ( $i = 0; $i < $count_custom_fields; $i++ ) {
				$custom_fields[ $i ] = trim( $custom_fields[ $i ] );
			}
		}
	} else {
		$custom_fields = false;
	}
	return $custom_fields;
}

/**
 * Trims multibyte strings.
 *
 * Removes the 194+160 non-breakable spaces and removes whitespace.
 *
 * @param string $string The source string.
 *
 * @return string Trimmed string.
 */
function relevanssi_mb_trim( $string ) {
	$string = str_replace( chr( 194 ) . chr( 160 ), '', $string );
	$string = preg_replace( '/(^\s+)|(\s+$)/us', '', $string );
	return $string;
}

/**
 * Removes punctuation from a string.
 *
 * This function removes some punctuation and replaces some punctuation with spaces.
 * This can partly be controlled from Relevanssi settings: see Advanced Indexing
 * Settings on the Indexing tab. This function runs on the
 * 'relevanssi_remove_punctuation' filter hook and can be disabled, if necessary.
 *
 * @param string $a The source string.
 *
 * @return string The string without punctuation.
 */
function relevanssi_remove_punct( $a ) {
	if ( ! is_string( $a ) ) {
		// In case something sends a non-string here.
		return '';
	}

	$a = preg_replace( '/<[^>]*>/', ' ', $a );

	$punct_options = get_option( 'relevanssi_punctuation' );

	$hyphen_replacement = ' ';
	$endash_replacement = ' ';
	$emdash_replacement = ' ';
	if ( isset( $punct_options['hyphens'] ) && 'remove' === $punct_options['hyphens'] ) {
		$hyphen_replacement = '';
		$endash_replacement = '';
		$emdash_replacement = '';
	}
	if ( isset( $punct_options['hyphens'] ) && 'keep' === $punct_options['hyphens'] ) {
		$hyphen_replacement = 'HYPHENTAIKASANA';
		$endash_replacement = 'ENDASHTAIKASANA';
		$emdash_replacement = 'EMDASHTAIKASANA';
	}

	$quote_replacement = ' ';
	if ( isset( $punct_options['quote'] ) && 'remove' === $punct_options['quote'] ) {
		$quote_replacement = '';
	}

	$ampersand_replacement = ' ';
	if ( isset( $punct_options['ampersands'] ) && 'remove' === $punct_options['ampersands'] ) {
		$ampersand_replacement = '';
	}
	if ( isset( $punct_options['ampersands'] ) && 'keep' === $punct_options['ampersands'] ) {
		$ampersand_replacement = 'AMPERSANDTAIKASANA';
	}

	$decimal_replacement = ' ';
	if ( isset( $punct_options['decimals'] ) && 'remove' === $punct_options['decimals'] ) {
		$decimal_replacement = '';
	}
	if ( isset( $punct_options['decimals'] ) && 'keep' === $punct_options['decimals'] ) {
		$decimal_replacement = 'DESIMAALITAIKASANA';
	}

	$replacement_array = array(
		'ß'                     => 'ss',
		'·'                     => '',
		'…'                     => '',
		'€'                     => '',
		'®'                     => '',
		'©'                     => '',
		'&shy;'                 => '',
		'&nbsp;'                => ' ',
		'&#8217;'               => ' ',
		chr( 194 ) . chr( 160 ) => ' ',
		'×'                     => ' ',
		"'"                     => $quote_replacement,
		'’'                     => $quote_replacement,
		'‘'                     => $quote_replacement,
		'”'                     => $quote_replacement,
		'“'                     => $quote_replacement,
		'„'                     => $quote_replacement,
		'´'                     => $quote_replacement,
		'-'                     => $hyphen_replacement,
		'–'                     => $endash_replacement,
		'—'                     => $emdash_replacement,
		'&#038;'                => $ampersand_replacement,
		'&amp;'                 => $ampersand_replacement,
		'&'                     => $ampersand_replacement,
	);

	/**
	 * Filters the punctuation replacement array.
	 *
	 * This filter can be used to alter the way some of the most common punctuation
	 * is handled by Relevanssi.
	 *
	 * @param array $replacement_array The array of punctuation and the replacements.
	 */
	$replacement_array = apply_filters( 'relevanssi_punctuation_filter', $replacement_array );

	$a = preg_replace( '/\.(\d)/', $decimal_replacement . '\1', $a );

	$a = str_replace( "\r", ' ', $a );
	$a = str_replace( "\n", ' ', $a );
	$a = str_replace( "\t", ' ', $a );

	$a = stripslashes( $a );

	$a = str_replace( array_keys( $replacement_array ), array_values( $replacement_array ), $a );
	/**
	 * Filters the default punctuation replacement value.
	 *
	 * By default Relevanssi replaces unspecified punctuation with spaces. This
	 * filter can be used to change that behaviour.
	 *
	 * @param string $replacement The replacement value, default ' '.
	 */
	$a = preg_replace( '/[[:punct:]]+/u', apply_filters( 'relevanssi_default_punctuation_replacement', ' ' ), $a );
	$a = preg_replace( '/[[:space:]]+/', ' ', $a );

	$a = str_replace( 'AMPERSANDTAIKASANA', '&', $a );
	$a = str_replace( 'HYPHENTAIKASANA', '-', $a );
	$a = str_replace( 'ENDASHTAIKASANA', '–', $a );
	$a = str_replace( 'EMDASHTAIKASANA', '—', $a );
	$a = str_replace( 'DESIMAALITAIKASANA', '.', $a );

	$a = trim( $a );

	return $a;
}


/**
 * Prevents the default search from running.
 *
 * When Relevanssi is active, this function prevents the default search from running,
 * in order to save resources. There are some exceptions, where we don't want
 * Relevanssi to meddle.
 *
 * This function originally created by John Blackbourne.
 *
 * @global object $wpdb The WordPress database interface.
 *
 * @param string $request The MySQL query for the search.
 * @param object $query   The WP_Query object.
 */
function relevanssi_prevent_default_request( $request, $query ) {
	if ( $query->is_search ) {
		if ( isset( $query->query_vars['post_type'] ) && isset( $query->query_vars['post_status'] ) ) {
			if ( 'attachment' === $query->query_vars['post_type'] && 'inherit,private' === $query->query_vars['post_status'] ) {
				// This is a media library search; do not meddle.
				return $request;
			}
		}

		if ( in_array( $query->query_vars['post_type'], array( 'topic', 'reply' ), true ) ) {
			// This is a BBPress search; do not meddle.
			return $request;
		}
		if ( is_array( $query->query_vars['post_type'] ) ) {
			if ( in_array( 'topic', $query->query_vars['post_type'], true ) || in_array( 'reply', $query->query_vars['post_type'], true ) ) {
				// This is a BBPress search; do not meddle.
				return $request;
			}
		}

		if ( isset( $_REQUEST['action'] ) && 'acf' === substr( $_REQUEST['action'], 0, 3 ) ) { // WPCS: CSRF ok.
			// ACF stuff, do not touch (eg. a relationship field search).
			return $request;
		}
		if ( isset( $query->query_vars['action'] ) && 'acf' === substr( $query->query_vars['action'], 0, 3 ) ) {
			// ACF stuff, do not touch (eg. a relationship field search).
			return $request;
		}

		$admin_search_ok = true;
		/**
		 * Filters the admin search.
		 *
		 * If this filter returns 'false', Relevanssi will be disabled.
		 *
		 * @param boolean $admin_search_ok Is admin search allowed.
		 * @param object  $query           The WP_Query object.
		 */
		$admin_search_ok = apply_filters( 'relevanssi_admin_search_ok', $admin_search_ok, $query );

		$prevent = true;
		/**
		 * Filters whether the default request is blocked or not.
		 *
		 * If this filter returns 'false', the default search request will not be
		 * blocked.
		 *
		 * @param boolean $prevent Should the request be prevented.
		 * @param object  $query   The WP_Query object.
		 */
		$prevent = apply_filters( 'relevanssi_prevent_default_request', $prevent, $query );

		if ( empty( $query->query_vars['s'] ) ) {
			$prevent         = false;
			$admin_search_ok = false;
		}

		if ( $query->is_admin && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$prevent         = false;
			$admin_search_ok = false;
		}

		if ( $query->is_admin && 'page' === $query->query_vars['post_type'] ) {
			// Relevanssi doesn't work on the page screen, so disable.
			$prevent         = false;
			$admin_search_ok = false;
		}

		global $wpdb;

		if ( ! is_admin() && $prevent ) {
			$request = "SELECT * FROM $wpdb->posts WHERE 1=2";
		} elseif ( 'on' === get_option( 'relevanssi_admin_search' ) && $admin_search_ok ) {
			$request = "SELECT * FROM $wpdb->posts WHERE 1=2";
		}
	}
	return $request;
}

/**
 * Disables Relevanssi in the ACF Relationship field post search.
 *
 * We don't want to use Relevanssi on the ACF Relationship field post searches, so
 * this function disables it (on the 'relevanssi_search_ok' hook).
 *
 * @param boolean $search_ok Block the search or not.
 *
 * @return boolean False, if this is an ACF Relationship field search, pass the
 * parameter unchanged otherwise.
 */
function relevanssi_acf_relationship_fields( $search_ok ) {
	if ( isset( $_REQUEST['action'] ) && 'acf' === substr( $_REQUEST['action'], 0, 3 ) ) { // WPCS: CSRF ok.
		$search_ok = false;
	}
	return $search_ok;
}

/**
 * Tokenizes strings.
 *
 * Tokenizes strings, removes punctuation, converts to lowercase and removes
 * stopwords. The function accepts both strings and arrays of strings as
 * source material. If the parameter is an array of string, each string is
 * tokenized separately and the resulting tokens are combined into one array.
 *
 * @param string|array $string          The string, or an array of strings, to tokenize.
 * @param boolean      $remove_stops    If true, stopwords are removed. Default true.
 * @param int          $min_word_length The minimum word length to include. Default -1.
 */
function relevanssi_tokenize( $string, $remove_stops = true, $min_word_length = -1 ) {
	$tokens = array();
	if ( is_array( $string ) ) {
		// If we get an array, tokenize each string in the array.
		foreach ( $string as $substring ) {
			if ( is_string( $substring ) ) {
				$tokens = array_merge( $tokens, relevanssi_tokenize( $substring, $remove_stops, $min_word_length ) );
			}
		}

		// And we're done!
		return $tokens;
	}

	if ( function_exists( 'mb_internal_encoding' ) ) {
		mb_internal_encoding( 'UTF-8' );
	}

	$stopword_list = array();
	if ( $remove_stops ) {
		$stopword_list = relevanssi_fetch_stopwords();
	}

	if ( function_exists( 'relevanssi_apply_thousands_separator' ) ) {
		// A Premium feature.
		$string = relevanssi_apply_thousands_separator( $string );
	}

	/**
	 * Removes punctuation from the string.
	 *
	 * The default function on this filter is relevanssi_remove_punct(), which
	 * removes some punctuation and replaces some with spaces.
	 *
	 * @param string $string String with punctuation.
	 */
	$string = apply_filters( 'relevanssi_remove_punctuation', $string );

	$string = relevanssi_strtolower( $string );

	$token = strtok( $string, "\n\t " );
	while ( false !== $token ) {
		$token  = strval( $token );
		$accept = true;

		if ( relevanssi_strlen( $token ) < $min_word_length ) {
			$token = strtok( "\n\t  " );
			continue;
		}
		if ( $remove_stops && in_array( $token, $stopword_list, true ) ) {
			$accept = false;
		}

		if ( RELEVANSSI_PREMIUM ) {
			/**
			 * Fires Premium tokenizer.
			 *
			 * Filters the token through the Relevanssi Premium tokenizer to add some
			 * Premium features to the tokenizing (mostly stemming).
			 *
			 * @param string $token Search query token.
			 */
			$token = apply_filters( 'relevanssi_premium_tokenizer', $token );
		}

		if ( $accept ) {
			$token = relevanssi_mb_trim( $token );
			if ( is_numeric( $token ) ) {
				// $token ends up as an array index, and numbers don't work there.
				$token = " $token";
			}
			if ( ! isset( $tokens[ $token ] ) ) {
				$tokens[ $token ] = 1;
			} else {
				$tokens[ $token ]++;
			}
		}

		$token = strtok( "\n\t " );
	}

	return $tokens;
}

/**
 * Returns the post status from post ID.
 *
 * Returns the post status. This replacement for get_post_status() can handle user
 * profiles and taxonomy terms (both always return 'publish'). The status is read
 * from the Relevanssi caching mechanism to avoid unnecessary database calls, and
 * if nothing else works, this function falls back to get_post_status().
 *
 * @global array $relevanssi_post_array The Relevanssi post cache array.
 *
 * @param string $post_id The post ID.
 *
 * @return string The post status.
 */
function relevanssi_get_post_status( $post_id ) {
	global $relevanssi_post_array;
	$type = substr( $post_id, 0, 2 );
	if ( '**' === $type || 'u_' === $type ) {
		// Taxonomy term or user (a Premium feature).
		return 'publish';
	}

	if ( isset( $relevanssi_post_array[ $post_id ] ) ) {
		$status = $relevanssi_post_array[ $post_id ]->post_status;
		if ( 'inherit' === $status ) {
			// Attachment, let's see what the parent says.
			$parent = $relevanssi_post_array[ $post_id ]->post_parent;
			if ( ! $parent ) {
				// Attachment without a parent, let's assume it's public.
				$status = 'publish';
			} else {
				$status = relevanssi_get_post_status( $parent );
			}
		}
		return $status;
	} else {
		// No hit from the cache; let's add this post to the cache.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$relevanssi_post_array[ $post_id ] = $post;
		return $post->post_status;
	}
}

/**
 * Returns the post type.
 *
 * Replacement for get_post_type() that uses the Relevanssi post cache to reduce the
 * number of database calls required.
 *
 * @global array $relevanssi_post_array The Relevanssi post cache array.
 *
 * @param int $post_id The post ID.
 *
 * @return string The post type.
 */
function relevanssi_get_post_type( $post_id ) {
	global $relevanssi_post_array;

	if ( isset( $relevanssi_post_array[ $post_id ] ) ) {
		return $relevanssi_post_array[ $post_id ]->post_type;
	} else {
		// No hit from the cache; let's add this post to the cache.
		$post = get_post( $post_id );

		$relevanssi_post_array[ $post_id ] = $post;

		return $post->post_type;
	}
}

/**
 * Prints out a list of tags for post.
 *
 * Replacement for the_tags() that does the same, but applies Relevanssi search term
 * highlighting on the results.
 *
 * @param string  $before    What is printed before the tags, default null.
 * @param string  $separator The separator between items, default ', '.
 * @param string  $after     What is printed after the tags, default ''.
 * @param boolean $echo      If true, echo, otherwise return the result. Default true.
 */
function relevanssi_the_tags( $before = null, $separator = ', ', $after = '', $echo = true ) {
	$tags = relevanssi_highlight_terms( get_the_tag_list( $before, $separator, $after ), get_search_query() );
	if ( $echo ) {
		echo $tags; // WPCS: XSS ok. All content is already escaped by WP.
	} else {
		return $tags;
	}
}

/**
 * Gets a list of tags for post.
 *
 * Replacement for get_the_tags() that does the same, but applies Relevanssi search term
 * highlighting on the results.
 *
 * @param string $before    What is printed before the tags, default null.
 * @param string $separator The separator between items, default ', '.
 * @param string $after     What is printed after the tags, default ''.
 */
function relevanssi_get_the_tags( $before = null, $separator = ', ', $after = '' ) {
	return relevanssi_the_tags( $before, $sep, $after, false );
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
function relevanssi_get_term_tax_id( $term_id, $taxonomy ) {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id	FROM $wpdb->term_taxonomy WHERE term_id = %d AND taxonomy = %s", $term_id, $taxonomy ) );
}

/**
 * Adds synonyms to a search query.
 *
 * Takes a search query and adds synonyms to it.
 *
 * @param string $query The source query.
 *
 * @return string The query with synonyms added.
 */
function relevanssi_add_synonyms( $query ) {
	if ( empty( $query ) ) {
		return $query;
	}

	$synonym_data = get_option( 'relevanssi_synonyms' );
	if ( $synonym_data ) {
		$synonyms     = array();
		$synonym_data = relevanssi_strtolower( $synonym_data );
		$pairs        = explode( ';', $synonym_data );

		foreach ( $pairs as $pair ) {
			if ( empty( $pair ) ) {
				// Skip empty rows.
				continue;
			}
			$parts = explode( '=', $pair );
			$key   = strval( trim( $parts[0] ) );
			$value = trim( $parts[1] );

			$synonyms[ $key ][ $value ] = true;
		}

		if ( count( $synonyms ) > 0 ) {
			$new_terms = array();
			$terms     = array_keys( relevanssi_tokenize( $query, false ) ); // Remove stopwords is false here.
			if ( ! in_array( $query, $terms, true ) ) {
				// Include the whole query in the terms, unless it's not there already.
				$terms[] = $query;
			}

			foreach ( $terms as $term ) {
				$term = trim( $term );
				if ( in_array( strval( $term ), array_keys( $synonyms ), true ) ) { // Strval(), otherwise numbers cause problems.
					if ( isset( $synonyms[ strval( $term ) ] ) ) { // Necessary, otherwise terms like "02" can cause problems.
						$new_terms = array_merge( $new_terms, array_keys( $synonyms[ strval( $term ) ] ) );
					}
				}
			}
			if ( count( $new_terms ) > 0 ) {
				foreach ( $new_terms as $new_term ) {
					$query .= " $new_term";
				}
			}
		}
	}
	return $query;
}

/**
 * Returns the position of substring in the string.
 *
 * Uses mb_stripos() if possible, falls back to mb_strpos() and mb_strtoupper() if
 * that cannot be found, and falls back to just strpos() if even that is not
 * possible.
 *
 * @param string $haystack String where to look.
 * @param string $needle   The string to look for.
 * @param int    $offset   Where to start, default 0.
 *
 * @return mixed False, if no result or $offset outside the length of $haystack,
 * otherwise the position (which can be non-false 0!).
 */
function relevanssi_stripos( $haystack, $needle, $offset = 0 ) {
	if ( $offset > relevanssi_strlen( $haystack ) ) {
		return false;
	}

	if ( function_exists( 'mb_stripos' ) ) {
		if ( '' === $haystack ) {
			$pos = false;
		} else {
			$pos = mb_stripos( $haystack, $needle, $offset );
		}
	} elseif ( function_exists( 'mb_strpos' ) && function_exists( 'mb_strtoupper' ) && function_exists( 'mb_substr' ) ) {
		$pos = mb_strpos( mb_strtoupper( $haystack ), mb_strtoupper( $needle ), $offset );
	} else {
		$pos = strpos( strtoupper( $haystack ), strtoupper( $needle ), $offset );
	}
	return $pos;
}

/**
 * Closes tags in a bit of HTML code.
 *
 * Used to make sure no tags are left open in excerpts. This method is not foolproof,
 * but it's good enough for now.
 *
 * @param string $html The HTML code to analyze.
 *
 * @return string The HTML code, with tags closed.
 */
function relevanssi_close_tags( $html ) {
	preg_match_all( '#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result );
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
			unset( $closed_tags[ array_search( $opened_tags[ $i ], $closed_tags, true ) ] );
		}
	}
	return $html;
}

/**
 * Prints out post title with highlighting.
 *
 * Uses the global $post object. Reads the highlighted title from
 * $post->post_highlighted_title.
 *
 * @global object $post The global post object.
 *
 * @param boolean $echo If true, echo out the title. Default true.
 *
 * @return string If $echo is false, returns the title with highlights.
 */
function relevanssi_the_title( $echo = true ) {
	global $post;
	if ( empty( $post->post_highlighted_title ) ) {
		$post->post_highlighted_title = $post->post_title;
	}
	if ( $echo ) {
		echo $post->post_highlighted_title; // WPCS: XSS ok, $post->post_highlighted_title is generated by Relevanssi.
	}
	return $post->post_highlighted_title;
}

/**
 * Returns the post title with highlighting.
 *
 * Reads the highlighted title from $post->post_highlighted_title.
 *
 * @param int $post_id The post ID.
 *
 * @return string The post title with highlights.
 */
function relevanssi_get_the_title( $post_id ) {
	$post = relevanssi_get_post( $post_id );
	if ( ! is_object( $post ) ) {
		return null;
	}
	if ( empty( $post->post_highlighted_title ) ) {
		$post->post_highlighted_title = $post->post_title;
	}
	return $post->post_highlighted_title;
}

/**
 * Updates the 'relevanssi_doc_count' option.
 *
 * The 'relevanssi_doc_count' option contains the number of documents in the
 * Relevanssi index. This function calculates the value and stores it in the
 * option.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variable, used for table names.
 */
function relevanssi_update_doc_count() {
	global $wpdb, $relevanssi_variables;
	$doc_count = $wpdb->get_var( 'SELECT COUNT(DISTINCT(relevanssi.doc)) FROM ' . $relevanssi_variables['relevanssi_table'] . ' AS relevanssi' ); // WPCS: unprepared SQL ok, Relevanssi table name.
	update_option( 'relevanssi_doc_count', $doc_count );
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
	if ( function_exists( 'mb_strlen' ) ) {
		return mb_strlen( $s );
	}
	return strlen( $s );
}

/**
 * Prints out debugging notices.
 *
 * If WP_CLI is available, prints out the debug notice as a WP_CLI::log(), otherwise
 * just echo.
 *
 * @param string $notice The notice to print out.
 */
function relevanssi_debug_echo( $notice ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $notice );
	} else {
		echo esc_html( $notice ) . "\n";
	}
}

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
 * Adjusts Relevanssi variables when switch_blog() happens.
 *
 * This function attaches to the 'switch_blog' action hook and adjusts the table
 * names in the global $relevanssi_variables array to match the new blog.
 *
 * @global array  $relevanssi_variables The global Relevanssi variables.
 * @global object $wpdb                 The WordPress database interface.
 *
 * @author Teemu Muikku
 *
 * @param int $new_blog  The new blog ID.
 * @param int $prev_blog The old blog ID.
 */
function relevanssi_switch_blog( $new_blog, $prev_blog ) {
	global $relevanssi_variables, $wpdb;

	if ( ! isset( $relevanssi_variables ) || ! isset( $relevanssi_variables['relevanssi_table'] ) ) {
		return;
	}

	$relevanssi_variables['relevanssi_table'] = $wpdb->prefix . 'relevanssi';
	$relevanssi_variables['stopword_table']   = $wpdb->prefix . 'relevanssi_stopwords';
	$relevanssi_variables['log_table']        = $wpdb->prefix . 'relevanssi_log';
}

/**
 * Adds a highlight parameter to the permalink.
 *
 * Relevanssi requires a 'highligh' parameter to the permalinks in order to have
 * working highlights. This function adds the highlight. The function doesn't add
 * the parameter to the links pointing at the front page, because if we do that,
 * the link won't point to the front page anymore, but instead points to the blog
 * page.
 *
 * @global object $post The global post object.
 *
 * @param string $permalink The link to patch.
 *
 * @return string The link with the parameter added.
 */
function relevanssi_add_highlight( $permalink ) {
	$highlight_docs = get_option( 'relevanssi_highlight_docs' );
	$query          = get_search_query();
	if ( isset( $highlight_docs ) && 'off' !== $highlight_docs && ! empty( $query ) ) {
		global $post;
		$frontpage_id = get_option( 'page_on_front' );
		if ( is_object( $post ) && $post->ID !== $frontpage_id ) {
			// We won't add the highlight parameter for the front page, as that will break the link.
			$permalink = esc_attr( add_query_arg( array( 'highlight' => rawurlencode( get_search_query() ) ), $permalink )
			);
		}
	}
	return $permalink;
}

/**
 * Gets the permalink to the current post within Loop.
 *
 * Uses get_permalink() to get the permalink, then adds the 'highlight' parameter
 * if necessary using relevanssi_add_highlight().
 *
 * @return string The permalink.
 */
function relevanssi_get_permalink() {
	/**
	 * Filters the permalink.
	 *
	 * @param string The permalink, generated by get_permalink().
	 */
	$permalink = apply_filters( 'relevanssi_permalink', get_permalink() );
	return $permalink;
}

/**
 * Echoes out the permalink to the current post within Loop.
 *
 * Uses get_permalink() to get the permalink, then adds the 'highlight' parameter
 * if necessary using relevanssi_add_highlight(), then echoes it out.
 */
function relevanssi_the_permalink() {
	echo esc_attr( relevanssi_get_permalink() );
}

/**
 * Adjusts the permalink to use the Relevanssi-generated link.
 *
 * This function is used to filter 'the_permalink', 'post_link' and
 * 'relevanssi_permalink'. It changes the permalink to point to
 * $post->relevanssi_link, if that exists. This means the permalinks to
 * user profiles and taxonomy terms work. This function also adds the
 * 'highlight' parameter to the URL.
 *
 * @global object $post The global post object.
 *
 * @param string $link      The link to adjust.
 * @param object $link_post The post to modify. If null, use global $post.
 * Defaults null.
 *
 * @return string The modified link.
 */
function relevanssi_permalink( $link, $link_post = null ) {
	if ( null === $link_post ) {
		global $post;
	}
	// Using property_exists() to avoid troubles from magic variables.
	if ( is_object( $post ) && property_exists( $post, 'relevanssi_link' ) ) {
		$link = $post->relevanssi_link;
	}
	$link = relevanssi_add_highlight( $link );
	return $link;
}

/**
 * Generates the Did you mean suggestions.
 *
 * A wrapper function that prints out the Did you mean suggestions. If Premium is
 * available, will use relevanssi_premium_didyoumean(), otherwise the
 * relevanssi_simple_didyoumean() is used.
 *
 * @param string  $query The query.
 * @param string  $pre   Printed out before the suggestion.
 * @param string  $post  Printed out after the suggestion.
 * @param int     $n     Maximum number of search results found for the suggestions
 * to show up. Default 5.
 * @param boolean $echo  If true, echo out. Default true.
 *
 * @return string The suggestion HTML element.
 */
function relevanssi_didyoumean( $query, $pre, $post, $n = 5, $echo = true ) {
	if ( function_exists( 'relevanssi_premium_didyoumean' ) ) {
		$result = relevanssi_premium_didyoumean( $query, $pre, $post, $n );
	} else {
		$result = relevanssi_simple_didyoumean( $query, $pre, $post, $n );
	}

	if ( $echo ) {
		echo $result; // WPCS: XSS ok, already escaped.
	}

	return $result;
}

/**
 * Generates the Did you mean suggestions HTML code.
 *
 * Uses relevanssi_simple_generate_suggestion() to come up with a suggestion, then
 * wraps that up with HTML code.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variables.
 * @global object $wp_query             The WP_Query object.
 *
 * @param string $query The query.
 * @param string $pre   Printed out before the suggestion.
 * @param string $post  Printed out after the suggestion.
 * @param int    $n     Maximum number of search results found for the suggestions
 * to show up. Default 5.
 *
 * @return string The suggestion HTML code, null if nothing found.
 */
function relevanssi_simple_didyoumean( $query, $pre, $post, $n = 5 ) {
	global $wpdb, $relevanssi_variables, $wp_query;

	$total_results = $wp_query->found_posts;

	if ( $total_results > $n ) {
		return null;
	}

	$suggestion = relevanssi_simple_generate_suggestion( $query );

	$result = null;
	if ( $suggestion ) {
		$url = get_bloginfo( 'url' );
		$url = esc_attr( add_query_arg( array( 's' => rawurlencode( $suggestion ) ), $url ) );

		/**
		 * Filters the 'Did you mean' suggestion URL.
		 *
		 * @param string $url        The URL for the suggested search query.
		 * @param string $query      The search query.
		 * @param string $suggestion The suggestion.
		 */
		$url = apply_filters( 'relevanssi_didyoumean_url', $url, $query, $suggestion );

		// Escape the suggestion to avoid XSS attacks.
		$suggestion = htmlspecialchars( $suggestion );

		/**
		 * Filters the complete 'Did you mean' suggestion.
		 *
		 * @param string The suggestion HTML code.
		 */
		$result = apply_filters( 'relevanssi_didyoumean_suggestion', "$pre<a href='$url'>$suggestion</a>$post" );
	}

	return $result;
}

/**
 * Generates the 'Did you mean' suggestions. Can be used to correct any queries.
 *
 * Uses the Relevanssi search logs as source material for corrections. If there are
 * no logged search queries, can't do anything.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variables, used for
 * table names.
 *
 * @param string $query The query to correct.
 *
 * @return string Corrected query, empty if nothing found.
 */
function relevanssi_simple_generate_suggestion( $query ) {
	global $wpdb, $relevanssi_variables;

	$q = 'SELECT query, count(query) as c, AVG(hits) as a FROM ' . $relevanssi_variables['log_table'] . ' WHERE hits > 1 GROUP BY query ORDER BY count(query) DESC';
	$q = apply_filters( 'relevanssi_didyoumean_query', $q );

	$data = wp_cache_get( 'relevanssi_didyoumean_query' );
	if ( empty( $data ) ) {
		$data = $wpdb->get_results( $q ); // WPCS: unprepared SQL ok. No user-generated input involved.
		wp_cache_set( 'relevanssi_didyoumean_query', $data );
	}

	$query            = htmlspecialchars_decode( $query );
	$tokens           = relevanssi_tokenize( $query );
	$suggestions_made = false;
	$suggestion       = '';

	foreach ( $tokens as $token => $count ) {
		$closest  = '';
		$distance = -1;
		foreach ( $data as $row ) {
			if ( $row->c < 2 ) {
				break;
			}

			if ( $token === $row->query ) {
				$closest = '';
				break;
			} else {
				$lev = levenshtein( $token, $row->query );

				if ( $lev < $distance || $distance < 0 ) {
					if ( $row->a > 0 ) {
						$distance = $lev;
						$closest  = $row->query;
						if ( $lev < 2 ) {
							break; // get the first with distance of 1 and go.
						}
					}
				}
			}
		}
		if ( ! empty( $closest ) ) {
			$query            = str_ireplace( $token, $closest, $query );
			$suggestions_made = true;
		}
	}

	if ( $suggestions_made ) {
		$suggestion = $query;
	}

	return $suggestion;
}

/**
 * Instructs a multisite installation to drop the tables.
 *
 * Attaches to 'wpmu_drop_tables' and adds the Relevanssi tables to the list of
 * tables to drop.
 *
 * @global array  $relevanssi_variables The Relevanssi global variables, used for
 * table names.
 *
 * @param array $tables The list of tables to drop.
 *
 * @return array Table list, with Relevanssi tables included.
 */
function relevanssi_wpmu_drop( $tables ) {
	global $relevanssi_variables;
	$tables[] = $relevanssi_variables['relevanssi_table'];
	$tables[] = $relevanssi_variables['stopword_table'];
	$tables[] = $relevanssi_variables['log_table'];
	return $tables;
}

/**
 * Replacement for get_post() that uses the Relevanssi post cache.
 *
 * Tries to fetch the post from the Relevanssi post cache. If that doesn't work, gets
 * the post using get_post().
 *
 * @param int $post_id The post ID.
 * @param int $blog_id The blog ID, default -1.
 *
 * @return object The post object.
 */
function relevanssi_get_post( $post_id, $blog_id = -1 ) {
	if ( function_exists( 'relevanssi_premium_get_post' ) ) {
		return relevanssi_premium_get_post( $post_id, $blog_id );
	}

	global $relevanssi_post_array;

	if ( isset( $relevanssi_post_array[ $post_id ] ) ) {
		$post = $relevanssi_post_array[ $post_id ];
	} else {
		$post = get_post( $post_id );

		$relevanssi_post_array[ $post_id ] = $post;
	}
	return $post;
}

/**
 * Recursively flattens a multidimensional array to produce a string.
 *
 * @param array $array The source array.
 *
 * @return string The array contents as a string.
 */
function relevanssi_flatten_array( array $array ) {
	$return_value = '';
	foreach ( new RecursiveIteratorIterator( new RecursiveArrayIterator( $array ) ) as $value ) {
		$return_value .= ' ' . $value;
	}
	return $return_value;
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
function relevanssi_sanitize_hex_color( $color ) {
	if ( '' === $color ) {
		return '';
	}

	// 3 or 6 hex digits, or the empty string.
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}

	return '';
}
