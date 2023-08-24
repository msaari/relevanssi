<?php
/**
 * /lib/admin-ajax.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'wp_ajax_relevanssi_truncate_index', 'relevanssi_truncate_index_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_index_posts', 'relevanssi_index_posts_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_count_posts', 'relevanssi_count_posts_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_count_missing_posts', 'relevanssi_count_missing_posts_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_list_categories', 'relevanssi_list_categories' );
add_action( 'wp_ajax_relevanssi_admin_search', 'relevanssi_admin_search' );
add_action( 'wp_ajax_relevanssi_update_counts', 'relevanssi_update_counts' );
add_action( 'wp_ajax_nopriv_relevanssi_update_counts', 'relevanssi_update_counts' );
add_action( 'wp_ajax_relevanssi_list_custom_fields', 'relevanssi_list_custom_fields' );

/**
 * Checks if current user can access Relevanssi options.
 *
 * If the current user doesn't have sufficient access to Relevanssi options,
 * the function will die. If the user has access, nothing happens.
 *
 * @return void
 */
function relevanssi_current_user_can_access_options() {
	/**
	 * Filters the capability required to access Relevanssi options.
	 *
	 * @param string The capability required. Default 'manage_options'.
	 */
	if ( ! current_user_can( apply_filters( 'relevanssi_options_capability', 'manage_options' ) ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'relevanssi' ) );
	}
}

/**
 * Truncates the Relevanssi index.
 *
 * Wipes the index clean using relevanssi_truncate_index().
 */
function relevanssi_truncate_index_ajax_wrapper() {
	check_ajax_referer( 'relevanssi_indexing_nonce', 'security' );
	relevanssi_current_user_can_access_options();

	$response = relevanssi_truncate_index();
	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Indexes posts in AJAX context.
 *
 * AJAX wrapper for indexing posts. Parses the arguments, uses the
 * relevanssi_build_index() to do the hard work, then creates the AJAX response.
 */
function relevanssi_index_posts_ajax_wrapper() {
	check_ajax_referer( 'relevanssi_indexing_nonce', 'security' );
	relevanssi_current_user_can_access_options();

	$completed = absint( $_POST['completed'] );
	$total     = absint( $_POST['total'] );
	$offset    = absint( $_POST['offset'] );
	$limit     = absint( $_POST['limit'] );
	$extend    = strval( $_POST['extend'] );

	if ( 'true' === $extend ) {
		$extend = true;
	}

	if ( $limit < 1 ) {
		$limit = 1;
	}

	$response = array();

	$is_ajax = true;
	$verbose = false;
	if ( $extend ) {
		$offset = true;
	}

	$indexing_response = relevanssi_build_index( $offset, $verbose, $limit, $is_ajax );

	if ( $indexing_response['indexing_complete'] ) {
		$response['completed']   = 'done';
		$response['percentage']  = 100;
		$completed              += $indexing_response['indexed'];
		$response['total_posts'] = $completed;
		$processed               = $total;
	} else {
		$completed            += $indexing_response['indexed'];
		$response['completed'] = $completed;

		if ( true === $offset ) {
			$processed = $completed;
		} else {
			$offset    = $offset + $limit;
			$processed = $offset;
		}

		if ( $total > 0 ) {
			$response['percentage'] = $processed / $total * 100;
		} else {
			$response['percentage'] = 0;
		}
	}

	$response['feedback'] = sprintf(
		// translators: Number of posts indexed on this go, total number of posts indexed so far, number of posts processed on this go, total number of posts to process.
		_n(
			'Indexed %1$d post (total %2$d), processed %3$d / %4$d.',
			'Indexed %1$d posts (total %2$d), processed %3$d / %4$d.',
			$indexing_response['indexed'],
			'relevanssi'
		),
		$indexing_response['indexed'],
		$completed,
		$processed,
		$total
	) . "\n";
	$response['offset'] = $offset;

	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Counts the posts to index.
 *
 * AJAX wrapper for relevanssi_count_total_posts().
 */
function relevanssi_count_posts_ajax_wrapper() {
	relevanssi_current_user_can_access_options();

	$count = relevanssi_count_total_posts();
	echo wp_json_encode( $count );
	wp_die();
}

/**
 * Counts the posts missing from the index.
 *
 * AJAX wrapper for relevanssi_count_missing_posts().
 */
function relevanssi_count_missing_posts_ajax_wrapper() {
	relevanssi_current_user_can_access_options();

	$count = relevanssi_count_missing_posts();
	echo wp_json_encode( $count );
	wp_die();
}

/**
 * Lists categories.
 *
 * AJAX wrapper for get_categories().
 */
function relevanssi_list_categories() {
	relevanssi_current_user_can_access_options();

	$categories = get_categories(
		array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
		)
	);
	echo wp_json_encode( $categories );
	wp_die();
}

/**
 * Performs an admin search.
 *
 * Performs an admin dashboard search.
 *
 * @since 2.2.0
 */
function relevanssi_admin_search() {
	check_ajax_referer( 'relevanssi_admin_search_nonce', 'security' );
	/**
	 * Filters the capability required to access Relevanssi admin search page.
	 *
	 * @param string The capability required. Default 'edit_posts'.
	 */
	if ( ! current_user_can( apply_filters( 'relevanssi_admin_search_capability', 'edit_posts' ) ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'relevanssi' ) );
	}

	$args = array();
	if ( isset( $_POST['args'] ) ) {
		parse_str( $_POST['args'], $args );
	}
	if ( isset( $_POST['posts_per_page'] ) ) {
		$posts_per_page = intval( $_POST['posts_per_page'] );
		if ( $posts_per_page > 0 ) {
			$args['posts_per_page'] = $posts_per_page;
		}
	}
	if ( isset( $_POST['post_types'] ) ) {
		$post_type          = $_POST['post_types'];
		$args['post_types'] = $post_type;
	}
	if ( isset( $_POST['offset'] ) ) {
		$offset = intval( $_POST['offset'] );
		if ( $offset > 0 ) {
			$args['offset'] = $offset;
		}
	}
	if ( isset( $_POST['s'] ) ) {
		$args['s'] = $_POST['s'];
	}

	$query = new WP_Query();
	$query->parse_query( $args );
	$query->set( 'relevanssi_admin_search', true );
	$query = apply_filters( 'relevanssi_modify_wp_query', $query );
	relevanssi_do_query( $query );

	$results = relevanssi_admin_search_debugging_info( $query );

	// Take the posts array and create a string out of it.
	$offset = 0;
	if ( isset( $query->query_vars['offset'] ) ) {
		$offset = $query->query_vars['offset'];
	}
	$results .= relevanssi_admin_search_format_posts( $query->posts, $query->found_posts, $offset, $args['s'] );

	echo wp_json_encode( $results );
	wp_die();
}

/**
 * Formats the posts for admin search.
 *
 * Results are presented as an ordered list of posts. The format is very basic, and
 * can be modified with the 'relevanssi_admin_search_element' filter hook.
 *
 * @param array  $posts  The posts array.
 * @param int    $total  The number of posts found in total.
 * @param int    $offset Offset value.
 * @param string $query  The search query.
 *
 * @return string The formatted posts.
 *
 * @since 2.2.0
 */
function relevanssi_admin_search_format_posts( $posts, $total, $offset, $query ) {
	$result = '<h3>' . __( 'Results', 'relevanssi' ) . '</h3>';
	$start  = $offset + 1;
	$end    = $offset + count( $posts );
	// Translators: %1$d is the total number of posts found, %2$d is the current search result count, %3$d is the offset.
	$result .= '<p>' . sprintf( __( 'Found a total of %1$d posts, showing posts %2$d–%3$s.', 'relevanssi' ), $total, $start, '<span id="offset">' . $end . '</span>' ) . '</p>';
	if ( $offset > 0 ) {
		$result .= sprintf( '<button type="button" id="prev_page">%s</button>', __( 'Previous page', 'relevanssi' ) );
	}
	if ( count( $posts ) + $offset < $total ) {
		$result .= sprintf( '<button type="button" id="next_page">%s</button>', __( 'Next page', 'relevanssi' ) );
	}
	$result .= '<ol start="' . $start . '">';

	$score_label = __( 'Score:', 'relevanssi' );

	foreach ( $posts as $post ) {
		$blog_name = '';
		if ( isset( $post->blog_id ) && function_exists( 'switch_to_blog' ) ) {
			switch_to_blog( $post->blog_id );
			$blog_name = get_bloginfo( 'name' ) . ': ';
		}
		$permalink = get_permalink( $post->ID );
		$edit_url  = get_edit_post_link( $post->ID );
		$post_type = $post->post_type;
		if ( isset( $post->relevanssi_link ) ) {
			$permalink = $post->relevanssi_link;
		}
		if ( 'user' === $post->post_type ) {
			$edit_url = get_edit_user_link( $post->ID );
		}
		if ( empty( $edit_url ) ) {
			if ( isset( $post->term_id ) ) {
				$edit_url = get_edit_term_link( $post->term_id, $post->post_type );
			}
		}
		$title     = sprintf( '<a href="%1$s">%2$s %3$s</a>', $permalink, $post->post_title, $post_type );
		$edit_link = '';
		if ( current_user_can( 'edit_post', $post->ID ) ) {
			$edit_link = sprintf( '(<a href="%1$s">%2$s %3$s</a>)', $edit_url, __( 'Edit', 'relevanssi' ), $post_type );
		}

		$pinning_buttons = '';
		$pinned          = '';

		if ( function_exists( 'relevanssi_admin_search_pinning' ) ) {
			// Relevanssi Premium adds pinning features to the admin search.
			list( $pinning_buttons, $pinned ) = relevanssi_admin_search_pinning( $post, $query );
		}

		$post_element = <<<EOH
<li>$blog_name <strong>$title</strong> $edit_link $pinning_buttons <br />
$post->post_excerpt<br />
$score_label $post->relevance_score $pinned</li>
EOH;
		/**
		 * Filters the admin search results element.
		 *
		 * The post element is a <li> element. Feel free to edit the element any
		 * way you want to.
		 *
		 * @param string $post_element The post element.
		 * @param object $post         The post object.
		 */
		$result .= apply_filters( 'relevanssi_admin_search_element', $post_element, $post );
		if ( isset( $post->blog_id ) && function_exists( 'restore_current_blog' ) ) {
			restore_current_blog();
		}
	}
	$result .= '</ol>';
	return $result;
}

/**
 * Shows debugging information about the search.
 *
 * Formats the WP_Query parameters, looks at some filter hooks and presents the
 * information in an easy-to-read format.
 *
 * @param WP_Query $query The WP_Query object.
 *
 * @return string The formatted debugging information.
 *
 * @since 2.2.0
 */
function relevanssi_admin_search_debugging_info( $query ) {
	$result  = '<div id="debugging">';
	$result .= '<h3>' . __( 'Query variables', 'relevanssi' ) . '</h3>';
	$result .= '<ul style="list-style: disc; margin-left: 1.5em">';
	foreach ( $query->query_vars as $key => $value ) {
		if ( 'tax_query' === $key ) {
			$result .= '<li>tax_query:<ul style="list-style: disc; margin-left: 1.5em">';
			$result .= implode(
				'',
				array_map(
					function ( $row ) {
						$result = '';
						if ( is_array( $row ) ) {
							foreach ( $row as $row_key => $row_value ) {
								$result .= "<li>$row_key: $row_value</li>";
							}
						}
						return $result;
					},
					$value
				)
			);
			$result .= '</ul></li>';
		} else {
			if ( is_array( $value ) ) {
				$value = relevanssi_flatten_array( $value );
			}
			if ( empty( $value ) ) {
				continue;
			}
			$result .= "<li>$key: $value</li>";
		}
	}
	if ( ! empty( $query->tax_query ) ) {
		$result .= '<li>tax_query:<ul style="list-style: disc; margin-left: 1.5em">';
		foreach ( $query->tax_query as $tax_query ) {
			if ( ! is_array( $tax_query ) ) {
				continue;
			}
			foreach ( $tax_query as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = relevanssi_flatten_array( $value );
				}
				$result .= "<li>$key: $value</li>";
			}
		}
		$result .= '</ul></li>';
	}
	$result .= '</ul>';

	global $wp_filter;

	$filters = array(
		'relevanssi_search_ok',
		'relevanssi_modify_wp_query',
		'relevanssi_search_filters',
		'relevanssi_where',
		'relevanssi_join',
		'relevanssi_fuzzy_query',
		'relevanssi_exact_match_bonus',
		'relevanssi_query_filter',
		'relevanssi_match',
		'relevanssi_post_ok',
		'relevanssi_search_again',
		'relevanssi_results',
		'relevanssi_orderby',
		'relevanssi_order',
		'relevanssi_default_tax_query_relation',
		'relevanssi_hits_filter',
	);

	$result .= '<h3>' . __( 'Filters', 'relevanssi' ) . '</h3>';
	$result .= '<button type="button" id="show_filters">' . __( 'show', 'relevanssi' ) . '</button>';
	$result .= '<button type="button" id="hide_filters" style="display: none">' . __( 'hide', 'relevanssi' ) . '</button>';
	$result .= '<div id="relevanssi_filter_list">';
	foreach ( $filters as $filter ) {
		if ( isset( $wp_filter[ $filter ] ) ) {
			$result .= '<h4>' . $filter . '</h4>';
			$result .= '<ul style="list-style: disc; margin-left: 1.5em">';
			foreach ( $wp_filter[ $filter ] as $priority => $functions ) {
				foreach ( $functions as $function ) {
					if ( $function['function'] instanceof Closure ) {
						$function['function'] = 'Anonymous function';
					}
					$result .= "<li>$priority: " . $function['function'] . '</li>';
				}
			}
			$result .= '</ul>';
		}
	}
	$result .= '</div>';
	$result .= '</div>';

	return $result;
}

/**
 * Updates count options.
 *
 * Updates 'relevanssi_doc_count', 'relevanssi_terms_count' (and in Premium
 * 'relevanssi_user_count' and 'relevanssi_taxterm_count'). These are slightly
 * expensive queries, so they are updated when necessary as a non-blocking AJAX
 * action and stored in options for quick retrieval.
 *
 * @global object $wpdb                 The WordPress database interface.
 * @global array  $relevanssi_variables The Relevanssi global variable, used for table names.
 */
function relevanssi_update_counts() {
	global $wpdb, $relevanssi_variables;

	relevanssi_update_doc_count();

	$terms_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $relevanssi_variables['relevanssi_table'] );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	update_option( 'relevanssi_terms_count', is_null( $terms_count ) ? 0 : $terms_count, false );

	if ( RELEVANSSI_PREMIUM ) {
		$user_count    = $wpdb->get_var( 'SELECT COUNT(DISTINCT item) FROM ' . $relevanssi_variables['relevanssi_table'] . " WHERE type = 'user'" );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$taxterm_count = $wpdb->get_var( 'SELECT COUNT(DISTINCT item) FROM ' . $relevanssi_variables['relevanssi_table'] . " WHERE (type != 'post' AND type != 'attachment' AND type != 'user')" );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

		update_option( 'relevanssi_user_count', is_null( $user_count ) ? 0 : $user_count, false );
		update_option( 'relevanssi_taxterm_count', is_null( $taxterm_count ) ? 0 : $taxterm_count, false );
	}
}

/**
 * Returns a comma-separated list of indexed custom field names.
 *
 * @uses relevanssi_list_all_indexed_custom_fields()
 */
function relevanssi_list_custom_fields() {
	$response = relevanssi_list_all_indexed_custom_fields();

	echo wp_json_encode( $response );
	wp_die();
}
