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

/**
 * Truncates the Relevanssi index.
 *
 * Wipes the index clean using relevanssi_truncate_index().
 */
function relevanssi_truncate_index_ajax_wrapper() {
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
		_n( 'Indexed %1$d post (total %2$d), processed %3$d / %4$d.', 'Indexed %1$d posts (total %2$d), processed %3$d / %4$d.',
			$indexing_response['indexed'], 'relevanssi'
		),
		$indexing_response['indexed'], $completed, $processed, $total
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
	$categories = get_categories( array(
		'taxonomy'   => 'category',
		'hide_empty' => false,
	) );
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
	relevanssi_do_query( $query );

	$results = relevanssi_admin_search_debugging_info( $query );

	// Take the posts array and create a string out of it.
	$offset = 0;
	if ( isset( $query->query_vars['offset'] ) ) {
		$offset = $query->query_vars['offset'];
	}
	$results .= relevanssi_admin_search_format_posts( $query->posts, $query->found_posts, $offset );

	echo wp_json_encode( $results );
	wp_die();
}

/**
 * Formats the posts for admin search.
 *
 * Results are presented as an ordered list of posts. The format is very basic, and
 * can be modified with the 'relevanssi_admin_search_element' filter hook.
 *
 * @param array $posts  The posts array.
 * @param int   $total  The number of posts found in total.
 * @param int   $offset Offset value.
 *
 * @return string The formatted posts.
 *
 * @since 2.2.0
 */
function relevanssi_admin_search_format_posts( $posts, $total, $offset ) {
	$result = '<h3>' . __( 'Results', 'relevanssi' ) . '</h3>';
	// Translators: %1$d is the total number of posts found, %2$d is the current search result count, %3$d is the offset.
	$result .= '<p>' . sprintf( __( 'Found a total of %1$d posts, showing %2$d posts from offset %3$s.', 'relevanssi' ), $total, count( $posts ), '<span id="offset">' . $offset . '</span>' ) . '</p>';
	if ( $offset > 0 ) {
		$result .= '<button type="button" id="prev_page">Previous page</button>';
	}
	if ( count( $posts ) + $offset < $total ) {
		$result .= '<button type="button" id="next_page">Next page</button>';
	}
	$result .= '<ol>';

	$score_label = __( 'Score:', 'relevanssi' );

	foreach ( $posts as $post ) {
		$blog_name = '';
		if ( isset( $post->blog_id ) ) {
			switch_to_blog( $post->blog_id );
			$blog_name = get_bloginfo( 'name' ) . ': ';
		}
		$permalink = get_permalink( $post->ID );
		$edit_link = get_edit_post_link( $post->ID );
		$post_type = $post->post_type;
		if ( isset( $post->relevanssi_link ) ) {
			$permalink = $post->relevanssi_link;
		}
		if ( 'user' === $post->post_type ) {
			$edit_link = get_edit_user_link( $post->ID );
		}
		if ( empty( $edit_link ) ) {
			if ( isset( $post->term_id ) ) {
				$edit_link = get_edit_term_link( $post->term_id, $post->post_type );
			}
		}
		$pinned = '';
		if ( isset( $post->relevanssi_pinned ) ) {
			$pinned = '<strong>(pinned)</strong>';
		}
		$post_element = <<<EOH
<li>$blog_name <strong>$post->post_title</strong> (<a href="$permalink">View $post_type</a>) (<a href="$edit_link">Edit $post_type</a>) <br />
$post->post_excerpt<br />
$score_label $post->relevance_score $pinned</li>
EOH;
		/**
		 * Filters the admin search results element.
		 *
		 * The post element is a <li> element. Feel free to edit the element any way you want to.
		 *
		 * @param string $post_element The post element.
		 */
		$result .= apply_filters( 'relevanssi_admin_search_element', $post_element );
		if ( isset( $post->blog_id ) ) {
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
 * @param array $query The WP_Query object.
 *
 * @return string The formatted debugging information.
 *
 * @since 2.2.0
 */
function relevanssi_admin_search_debugging_info( $query ) {
	$result  = '<h3>' . __( 'Query variables', 'relevanssi' ) . '</h3>';
	$result .= '<ul style="list-style: disc; margin-left: 1.5em">';
	foreach ( $query->query_vars as $key => $value ) {
		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}
		if ( empty( $value ) ) {
			continue;
		}
		$result .= "<li>$key: $value</li>";
	}
	if ( ! empty( $query->tax_query ) ) {
		$result .= '<li><strong>tax_query</strong>:<ul style="list-style: disc; margin-left: 1.5em">';
		foreach ( $query->tax_query as $tax_query ) {
			foreach ( $tax_query as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
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
		'relevanssi_default_meta_query_relation',
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
					$result .= "<li>$priority: " . $function['function'] . '</li>';
				}
			}
			$result .= '</ul>';
		}
	}
	$result .= '</div>';

	return $result;
}
