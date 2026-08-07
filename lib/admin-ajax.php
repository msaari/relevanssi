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
	 * @param string $capability The capability required. Default 'manage_options'.
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
	 * @param string $capability The capability required. Default 'edit_posts'.
	 */
	if ( ! current_user_can( apply_filters( 'relevanssi_admin_search_capability', 'edit_posts' ) ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'relevanssi' ) );
	}

	$args = array();
	if ( isset( $_POST['args'] ) ) {
		parse_str( $_POST['args'], $args );
		$args = wp_slash( $args );
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
	$result  = '<div class="relevanssi-results-header" style="margin-top: 32px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">';
	$result .= '<h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1d2327;">' . __( 'Results', 'relevanssi' ) . '</h3>';

	$start = $offset + 1;
	$end   = $offset + count( $posts );
	// Translators: %1$d is the total number of posts found, %2$d is the current search result count, %3$d is the offset.
	$result .= '<p class="description" style="margin: 0; font-size: 14px;">' . sprintf( __( 'Found a total of %1$d posts, showing posts %2$d–%3$s.', 'relevanssi' ), $total, $start, '<span id="offset" style="font-weight: 600; color: #1d2327;">' . $end . '</span>' ) . '</p>';
	$result .= '</div>';

	if ( $offset > 0 || count( $posts ) + $offset < $total ) {
		$result .= '<div class="relevanssi-action-group" style="margin-bottom: 24px;">';
		if ( $offset > 0 ) {
			$result .= sprintf( '<button type="button" id="prev_page" class="button">%s</button>', __( 'Previous page', 'relevanssi' ) );
		}
		if ( count( $posts ) + $offset < $total ) {
			$result .= sprintf( '<button type="button" id="next_page" class="button">%s</button>', __( 'Next page', 'relevanssi' ) );
		}
		$result .= '</div>';
	}

	$result .= '<ol class="relevanssi-results-list" start="' . esc_attr( $start ) . '" style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 16px;">';

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

		$title     = sprintf( '<a href="%1$s" style="color: #1d2327; text-decoration: none; transition: color 0.2s ease;">%2$s</a>', esc_url( $permalink ), esc_html( $post->post_title ) );
		$edit_link = '';
		if ( current_user_can( 'edit_post', $post->ID ) && ! empty( $edit_url ) ) {
			$edit_link = sprintf( '<a href="%1$s" class="button-ghost" style="padding: 2px 8px; font-size: 11px; height: auto; min-height: auto; margin-left: 8px;">%2$s</a>', esc_url( $edit_url ), __( 'Edit', 'relevanssi' ) );
		}

		$pinning_buttons = '';
		$pinned          = '';

		if ( function_exists( 'relevanssi_admin_search_pinning' ) ) {
			// Relevanssi Premium adds pinning features to the admin search.
			list( $pinning_buttons, $pinned ) = relevanssi_admin_search_pinning( $post, $query );
		}

		$post_element  = '<li class="relevanssi-result-item" style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 20px; transition: all 0.2s ease; display: flex; flex-direction: column; gap: 12px;">';
		$post_element .= '<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">';
		$post_element .= '<div style="flex: 1; min-width: 0;">';
		$post_element .= '<h4 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 600; line-height: 1.4;">' . esc_html( $blog_name ) . $title . $edit_link . '</h4>';
		$post_element .= '</div>';
		$post_element .= '<div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">';
		$post_element .= '<span class="relevanssi-badge" style="font-size: 11px; font-weight: 600; background: #f0f2f5; color: #646970; border: 1px solid #dcdcde; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em;">' . esc_html( $post_type ) . '</span>';
		$post_element .= '<span class="relevanssi-badge" style="font-size: 11px; font-weight: 600; background: rgba(27, 133, 61, 0.08); color: #1b853d; border: 1px solid rgba(27, 133, 61, 0.15); padding: 4px 8px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em;">' . esc_html( $score_label ) . ' ' . esc_html( $post->relevance_score ) . '</span>';
		$post_element .= $pinned;
		$post_element .= $pinning_buttons;
		$post_element .= '</div>';
		$post_element .= '</div>';

		if ( ! empty( $post->post_excerpt ) ) {
			$post_element .= '<div class="relevanssi-result-excerpt" style="font-size: 13.5px; line-height: 1.6; color: #4f565d; border-top: 1px solid #f0f0f1; padding-top: 10px; margin: 0;">' . $post->post_excerpt . '</div>';
		}
		$post_element .= '</li>';

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
	// Style adjustments: Removed border-top accent and updated margins to cleanly sit inside the sidebar container.
	$result  = '<details class="relevanssi-card" id="debugging" style="margin-top: 16px; background: #ffffff; border: 1px solid #c3c4c7; border-radius: 4px; width: 100%; box-sizing: border-box;">';
	$result .= '<summary style="cursor: pointer; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; user-select: none;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; color: #1d2327;">' . __( 'Advanced Query Details', 'relevanssi' ) . '</h3></summary>';
	$result .= '<div class="accordion-content" style="padding: 0 16px 16px 16px;">';
	$result .= '<h3 style="margin-top: 12px; font-size: 13px; font-weight: 600; color: #1d2327; margin-bottom: 12px; border-bottom: 1px solid #f0f0f1; padding-bottom: 8px;">' . __( 'Query variables', 'relevanssi' ) . '</h3>';
	$result .= '<ul class="relevanssi-sidebar-list" style="margin-left: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; padding-left: 0;">';

	foreach ( $query->query_vars as $key => $value ) {
		if ( 'tax_query' === $key ) {
			$result .= '<li style="padding: 4px 0;"><code style="background: #f0f2f5; color: #2c3338; padding: 2px 6px; border-radius: 4px; font-family: monospace;">tax_query</code>';
			$result .= '<ul style="list-style: disc; margin-left: 20px; margin-top: 6px; color: #646970; font-size: 12px;">';
			$result .= implode(
				'',
				array_map(
					function ( $row ) {
						$sub_result = '';
						if ( is_array( $row ) ) {
							foreach ( $row as $row_key => $row_value ) {
								$sub_result .= sprintf( '<li style="margin-bottom: 4px; word-break: break-all;"><strong>%s:</strong> %s</li>', esc_html( $row_key ), esc_html( $row_value ) );
							}
						}
						return $sub_result;
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
			$result .= sprintf( '<li style="padding: 4px 0; font-size: 12px; color: #3c434a; display: flex; flex-direction: column; align-items: flex-start; gap: 4px; word-break: break-all;"><code style="background: #f0f2f5; color: #2c3338; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-weight: 600;">%s</code> <span>%s</span></li>', esc_html( $key ), esc_html( $value ) );
		}
	}

	if ( ! empty( $query->tax_query ) ) {
		$result .= '<li style="padding: 4px 0;"><code style="background: #f0f2f5; color: #2c3338; padding: 2px 6px; border-radius: 4px; font-family: monospace;">tax_query</code>';
		$result .= '<ul style="list-style: disc; margin-left: 20px; margin-top: 6px; color: #646970; font-size: 12px;">';
		foreach ( $query->tax_query as $tax_query ) {
			if ( ! is_array( $tax_query ) ) {
				continue;
			}
			foreach ( $tax_query as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = relevanssi_flatten_array( $value );
				}
				$result .= sprintf( '<li style="margin-bottom: 4px; word-break: break-all;"><strong>%s:</strong> %s</li>', esc_html( $key ), esc_html( $value ) );
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
		'relevanssi_ignore_theme_post_type',
	);

	$result .= '<h3 style="margin-top: 16px; font-size: 13px; font-weight: 600; color: #1d2327; margin-bottom: 12px; border-bottom: 1px solid #f0f0f1; padding-bottom: 8px;">' . __( 'Filters', 'relevanssi' ) . '</h3>';
	$result .= '<div class="relevanssi-action-group" style="margin-bottom: 12px;">';
	$result .= '<button type="button" id="show_filters" class="button button-outline" style="padding: 4px 12px; font-size: 11px; height: auto; min-height: auto;">' . __( 'show', 'relevanssi' ) . '</button>';
	$result .= '<button type="button" id="hide_filters" class="button button-outline" style="display: none; padding: 4px 12px; font-size: 11px; height: auto; min-height: auto;">' . __( 'hide', 'relevanssi' ) . '</button>';
	$result .= '</div>';

	$result .= '<div id="relevanssi_filter_list" style="background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 6px; padding: 12px; display: none; max-height: 350px; overflow-y: auto;">';
	foreach ( $filters as $filter ) {
		if ( isset( $wp_filter[ $filter ] ) ) {
			$result .= '<h4 style="margin: 8px 0 4px 0; font-size: 11px; color: #1b853d; font-family: monospace; word-break: break-all;">' . esc_html( $filter ) . '</h4>';
			$result .= '<ul style="list-style: none; padding-left: 8px; margin: 0 0 8px 0; border-left: 2px solid #dcdcde;">';
			foreach ( $wp_filter[ $filter ] as $priority => $functions ) {
				foreach ( $functions as $function ) {
					if ( $function['function'] instanceof Closure ) {
						$function['function'] = 'Anonymous function';
					}
					$result .= sprintf( '<li style="font-size: 11px; margin-bottom: 2px; color: #4f565d; word-break: break-all;"><span style="color: #dba617; font-weight: 600; margin-right: 4px;">[%s]</span> %s</li>', esc_html( $priority ), esc_html( $function['function'] ) );
				}
			}
			$result .= '</ul>';
		}
	}
	$result .= '</div>';
	$result .= '</div>';
	$result .= '</details>';

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

	if ( ! current_user_can( 'manage_options' ) ) {
		die();
	}

	check_admin_referer( 'update_counts', '_wpnonce' );

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
