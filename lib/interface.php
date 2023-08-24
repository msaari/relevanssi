<?php
/**
 * /lib/interface.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Controls the Relevanssi settings page.
 *
 * @global array $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_options() {
	global $relevanssi_variables;
	$options_txt = __( 'Relevanssi Search Options', 'relevanssi' );
	if ( RELEVANSSI_PREMIUM ) {
		$options_txt = __( 'Relevanssi Premium Search Options', 'relevanssi' );
	}

	printf( "<div class='wrap'><h2>%s</h2>", esc_html( $options_txt ) );
	if ( ! empty( $_REQUEST ) ) {
		if ( isset( $_REQUEST['submit'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			update_relevanssi_options( $_REQUEST );
		}

		if ( isset( $_REQUEST['import_options'] ) ) {
			if ( function_exists( 'relevanssi_import_options' ) ) {
				check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
				$options = $_REQUEST['relevanssi_settings'];
				relevanssi_import_options( $options );
			}
		}

		if ( isset( $_REQUEST['search'] ) ) {
			relevanssi_search( $_REQUEST['q'] );
		}

		if ( isset( $_REQUEST['dowhat'] ) ) {
			if ( 'add_stopword' === $_REQUEST['dowhat'] ) {
				if ( isset( $_REQUEST['term'] ) ) {
					check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
					relevanssi_add_stopword( $_REQUEST['term'] );
				}
				if ( isset( $_REQUEST['body_term'] ) ) {
					check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
					relevanssi_add_body_stopword( $_REQUEST['body_term'] );
				}
			}
		}

		if ( isset( $_REQUEST['addstopword'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_add_stopword( $_REQUEST['addstopword'] );
		}

		if ( isset( $_REQUEST['removestopword'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_remove_stopword( $_REQUEST['removestopword'] );
		}

		if ( isset( $_REQUEST['removeallstopwords'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_remove_all_stopwords();
		}

		if ( isset( $_REQUEST['repopulatestopwords'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			$verbose = true;
			relevanssi_populate_stopwords( $verbose );
		}

		if ( isset( $_REQUEST['addbodystopword'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_add_body_stopword( $_REQUEST['addbodystopword'] );
		}

		if ( isset( $_REQUEST['removebodystopword'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_remove_body_stopword( $_REQUEST['removebodystopword'] );
		}

		if ( isset( $_REQUEST['removeallbodystopwords'] ) ) {
			check_admin_referer( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );
			relevanssi_remove_all_body_stopwords();
		}

		if ( isset( $_REQUEST['update_counts'] ) ) {
			check_admin_referer( 'update_counts' );
			relevanssi_update_counts();
		}
	}

	relevanssi_options_form();

	echo "<div style='clear:both'></div></div>";
}

/**
 * Prints out the 'Admin search' page.
 */
function relevanssi_admin_search_page() {
	global $relevanssi_variables;

	$options_txt = __( 'Admin Search', 'relevanssi' );

	printf( "<div class='wrap'><h2>%s</h2>", esc_html( $options_txt ) );

	require_once dirname( $relevanssi_variables['file'] ) . '/lib/tabs/search-page.php';
	relevanssi_search_tab();
}

/**
 * Truncates the Relevanssi logs.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables array.
 *
 * @param boolean $verbose If true, prints out a notice. Default true.
 *
 * @return boolean True if success, false if failure.
 */
function relevanssi_truncate_logs( $verbose = true ) {
	global $wpdb, $relevanssi_variables;

	$result = $wpdb->query( 'TRUNCATE ' . $relevanssi_variables['log_table'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( isset( $relevanssi_variables['tracking_table'] ) ) {
		$tracking_result = $wpdb->query( 'TRUNCATE ' . $relevanssi_variables['tracking_table'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results         = $result && $tracking_result;
	}

	if ( $verbose ) {
		if ( false !== $result ) {
			printf( "<div id='relevanssi-warning' class='updated fade'>%s</div>", esc_html__( 'Logs clear!', 'relevanssi' ) );
		} else {
			printf( "<div id='relevanssi-warning' class='updated fade'>%s</div>", esc_html__( 'Clearing the logs failed.', 'relevanssi' ) );
		}
	}

	return $result;
}

/**
 * Prints out the Relevanssi options form.
 *
 * @global array $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_options_form() {
	global $relevanssi_variables;

	echo "<div class='postbox-container'>";
	echo "<form method='post'>";

	wp_nonce_field( plugin_basename( $relevanssi_variables['file'] ), 'relevanssi_options' );

	$display_save_button = true;

	$active_tab = 'overview';
	if ( isset( $_REQUEST['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$active_tab = $_REQUEST['tab']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	if ( 'stopwords' === $active_tab ) {
		$display_save_button = false;
	}

	printf( "<input type='hidden' name='tab' value='%s' />", esc_attr( $active_tab ) );

	$this_page = '?page=' . plugin_basename( $relevanssi_variables['file'] );

	$tabs = array(
		array(
			'slug'     => 'overview',
			'name'     => __( 'Overview', 'relevanssi' ),
			'require'  => 'tabs/overview-tab.php',
			'callback' => 'relevanssi_overview_tab',
			'save'     => 'premium',
		),
		array(
			'slug'     => 'indexing',
			'name'     => __( 'Indexing', 'relevanssi' ),
			'require'  => 'tabs/indexing-tab.php',
			'callback' => 'relevanssi_indexing_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'attachments',
			'name'     => __( 'Attachments', 'relevanssi' ),
			'require'  => 'tabs/attachments-tab.php',
			'callback' => 'relevanssi_attachments_tab',
			'save'     => false,
		),
		array(
			'slug'     => 'searching',
			'name'     => __( 'Searching', 'relevanssi' ),
			'require'  => 'tabs/searching-tab.php',
			'callback' => 'relevanssi_searching_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'logging',
			'name'     => __( 'Logging', 'relevanssi' ),
			'require'  => 'tabs/logging-tab.php',
			'callback' => 'relevanssi_logging_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'excerpts',
			'name'     => __( 'Excerpts and highlights', 'relevanssi' ),
			'require'  => 'tabs/excerpts-tab.php',
			'callback' => 'relevanssi_excerpts_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'synonyms',
			'name'     => __( 'Synonyms', 'relevanssi' ),
			'require'  => 'tabs/synonyms-tab.php',
			'callback' => 'relevanssi_synonyms_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'stopwords',
			'name'     => __( 'Stopwords', 'relevanssi' ),
			'require'  => 'tabs/stopwords-tab.php',
			'callback' => 'relevanssi_stopwords_tab',
			'save'     => true,
		),
		array(
			'slug'     => 'redirects',
			'name'     => __( 'Redirects', 'relevanssi' ),
			'require'  => 'tabs/redirects-tab.php',
			'callback' => 'relevanssi_redirects_tab',
			'save'     => false,
		),
		array(
			'slug'     => 'debugging',
			'name'     => __( 'Debugging', 'relevanssi' ),
			'require'  => 'tabs/debugging-tab.php',
			'callback' => 'relevanssi_debugging_tab',
			'save'     => true,
		),
	);

	/**
	 * Allows adding new tabs to the Relevanssi menu.
	 *
	 * @param array $tabs An array of arrays defining the tabs.
	 *
	 * @return array Filtered tab array.
	 */
	$tabs = apply_filters( 'relevanssi_tabs', $tabs );
	?>
<h2 class="nav-tab-wrapper">
	<?php
	array_walk(
		$tabs,
		function ( $tab ) use ( $this_page, $active_tab ) {
			?>
			<a href="<?php echo esc_attr( $this_page ); ?>&amp;tab=<?php echo esc_attr( $tab['slug'] ); ?>"
			class="nav-tab <?php echo esc_attr( $tab['slug'] === $active_tab ? 'nav-tab-active' : '' ); ?>">
			<?php echo esc_html( $tab['name'] ); ?></a>
			<?php
		}
	);
	?>
</h2>

	<?php
	$current_tab = $tabs[ array_search( $active_tab, wp_list_pluck( $tabs, 'slug' ), true ) ];
	if ( ! $current_tab['save'] || ( ! RELEVANSSI_PREMIUM && 'premium' === $current_tab['save'] ) ) {
		$display_save_button = false;
	}
	if ( $current_tab['require'] ) {
		require_once $current_tab['require'];
	}
	call_user_func( $current_tab['callback'] );

	if ( $display_save_button ) :
		?>

	<input type='submit' name='submit' value='<?php esc_attr_e( 'Save the options', 'relevanssi' ); ?>' class='button button-primary' />

	<?php endif; ?>

	</form>
</div>

	<?php
}

/**
 * Adds admin scripts to Relevanssi pages.
 *
 * Hooks to the 'admin_enqueue_scripts' action hook.
 *
 * @global array $relevanssi_variables The global Relevanssi variables array.
 *
 * @param string $hook The hook suffix for the current admin page.
 */
function relevanssi_add_admin_scripts( $hook ) {
	global $relevanssi_variables;

	$plugin_dir_url = plugin_dir_url( $relevanssi_variables['file'] );

	// Only enqueue on Relevanssi pages.
	$acceptable_hooks = array(
		'toplevel_page_relevanssi-premium/relevanssi',
		'settings_page_relevanssi-premium/relevanssi',
		'dashboard_page_relevanssi-premium/relevanssi',
		'toplevel_page_relevanssi/relevanssi',
		'settings_page_relevanssi/relevanssi',
		'dashboard_page_relevanssi/relevanssi',
		'dashboard_page_relevanssi_admin_search',
	);
	/**
	 * Filters the hooks where Relevanssi scripts are enqueued.
	 *
	 * By default Relevanssi only enqueues the Relevanssi admin javascript on
	 * specific admin page hooks to avoid polluting the admin. If you want to
	 * move things around, this means the javascript bits won't work. You can
	 * introduce new hooks with this filter hook.
	 *
	 * @param array An array of page hook strings where Relevanssi scripts are
	 * added.
	 */
	if ( ! in_array( $hook, apply_filters( 'relevanssi_acceptable_hooks', $acceptable_hooks ), true ) ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'relevanssi_admin_js', $plugin_dir_url . 'lib/admin_scripts.js', array( 'wp-color-picker' ), $relevanssi_variables['plugin_version'], true );
	if ( ! RELEVANSSI_PREMIUM ) {
		wp_enqueue_script( 'relevanssi_admin_js_free', $plugin_dir_url . 'lib/admin_scripts_free.js', array( 'relevanssi_admin_js' ), $relevanssi_variables['plugin_version'], true );
	}
	if ( RELEVANSSI_PREMIUM ) {
		wp_enqueue_script( 'relevanssi_admin_js_premium', $plugin_dir_url . 'premium/admin_scripts_premium.js', array( 'relevanssi_admin_js' ), $relevanssi_variables['plugin_version'], true );
	}
	wp_enqueue_style( 'relevanssi_admin_css', $plugin_dir_url . 'lib/admin_styles.css', array(), $relevanssi_variables['plugin_version'] );

	if ( 'dashboard_page_relevanssi' === substr( $hook, 0, strlen( 'dashboard_page_relevanssi' ) ) ) {
		wp_enqueue_script( 'chartjs', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.3.2/chart.min.js', array(), '3.3.2', false );
	}

	$localizations = array(
		'confirm'              => __( 'Click OK to copy Relevanssi options to all subsites', 'relevanssi' ),
		'confirm_stopwords'    => __( 'Are you sure you want to remove all stopwords?', 'relevanssi' ),
		'confirm_delete_query' => __( 'Are you sure you want to delete the query?', 'relevanssi' ),
		'truncating_index'     => __( 'Wiping out the index...', 'relevanssi' ),
		'done'                 => __( 'Done.', 'relevanssi' ),
		'indexing_users'       => __( 'Indexing users...', 'relevanssi' ),
		'indexing_taxonomies'  => __( 'Indexing the following taxonomies:', 'relevanssi' ),
		'indexing_attachments' => __( 'Indexing attachments...', 'relevanssi' ),
		'counting_posts'       => __( 'Counting posts...', 'relevanssi' ),
		'counting_terms'       => __( 'Counting taxonomy terms...', 'relevanssi' ),
		'counting_users'       => __( 'Counting users...', 'relevanssi' ),
		'counting_attachments' => __( 'Counting attachments...', 'relevanssi' ),
		'posts_found'          => __( 'posts found.', 'relevanssi' ),
		'terms_found'          => __( 'taxonomy terms found.', 'relevanssi' ),
		'users_found'          => __( 'users found.', 'relevanssi' ),
		'attachments_found'    => __( 'attachments found.', 'relevanssi' ),
		'taxonomy_disabled'    => __( 'Taxonomy term indexing is disabled.', 'relevanssi' ),
		'user_disabled'        => __( 'User indexing is disabled.', 'relevanssi' ),
		'indexing_complete'    => __( 'Indexing complete.', 'relevanssi' ),
		'excluded_posts'       => __( 'posts excluded.', 'relevanssi' ),
		'options_changed'      => __( 'Settings have changed, please save the options before indexing.', 'relevanssi' ),
		'reload_state'         => __( 'Reload the page to refresh the state of the index.', 'relevanssi' ),
		'pdf_reset_confirm'    => __( 'Are you sure you want to delete all attachment content from the index?', 'relevanssi' ),
		'pdf_reset_done'       => __( 'Relevanssi attachment data wiped clean.', 'relevanssi' ),
		'pdf_reset_problems'   => __( 'There were problems wiping the Relevanssi attachment data clean.', 'relevanssi' ),
		'hour'                 => __( 'hour', 'relevanssi' ),
		'hours'                => __( 'hours', 'relevanssi' ),
		'about'                => __( 'about', 'relevanssi' ),
		'sixty_min'            => __( 'about an hour', 'relevanssi' ),
		'ninety_min'           => __( 'about an hour and a half', 'relevanssi' ),
		'minute'               => __( 'minute', 'relevanssi' ),
		'minutes'              => __( 'minutes', 'relevanssi' ),
		'underminute'          => __( 'less than a minute', 'relevanssi' ),
		'notimeremaining'      => __( "we're done!", 'relevanssi' ),
	);

	wp_localize_script( 'relevanssi_admin_js', 'relevanssi', $localizations );

	$nonce = array(
		'indexing_nonce'  => wp_create_nonce( 'relevanssi_indexing_nonce' ),
		'searching_nonce' => wp_create_nonce( 'relevanssi_admin_search_nonce' ),
	);

	if ( ! RELEVANSSI_PREMIUM ) {
		wp_localize_script( 'relevanssi_admin_js', 'nonce', $nonce );
	}

	/**
	 * Sets the indexing limit, ie. how many posts are indexed at once.
	 *
	 * Relevanssi starts by indexing this many posts at once. If the process
	 * goes fast enough, Relevanssi will then increase the limit and if the
	 * process is slow, the limit will be decreased. If necessary, you can
	 * use the relevanssi_indexing_adjust filter hook to disable that
	 * adjustment.
	 *
	 * @param int The indexing limit, default 10.
	 */
	$indexing_limit = apply_filters( 'relevanssi_indexing_limit', 10 );

	/**
	 * Sets the indexing adjustment.
	 *
	 * Relevanssi will adjust the number of posts indexed at once to speed
	 * up the process if it goes fast and to slow down, if the posts are
	 * slow to index. You can use this filter to stop that behaviour, making
	 * Relevanssi index posts at constant pace. That's generally slower, but
	 * more reliable.
	 *
	 * @param boolean Should the limit be adjusted, default true.
	 */
	$indexing_adjust = apply_filters( 'relevanssi_indexing_adjust', true );

	wp_localize_script(
		'relevanssi_admin_js',
		'relevanssi_params',
		array(
			'indexing_limit'  => $indexing_limit,
			'indexing_adjust' => $indexing_adjust,
		)
	);
}

/**
 * Prints out the form fields for tag and category weights.
 */
function relevanssi_form_tag_weight() {
	$taxonomy_weights = get_option( 'relevanssi_post_type_weights' );

	$tag_value = 1;
	if ( isset( $taxonomy_weights['post_tag'] ) ) {
		$tag_value = $taxonomy_weights['post_tag'];
	}
	$category_value = 1;
	if ( isset( $taxonomy_weights['category'] ) ) {
		$category_value = $taxonomy_weights['category'];
	}
	?>
<tr>
	<td>
		<?php esc_html_e( 'Tag weight', 'relevanssi' ); ?>
	</td>
	<td class="col-2">
		<input type='text' id='relevanssi_weight_post_tag' name='relevanssi_weight_post_tag' size='4' value='<?php echo esc_attr( $tag_value ); ?>' />
	</td>
</tr>
<tr>
	<td>
		<?php esc_html_e( 'Category weight', 'relevanssi' ); ?>
	</td>
	<td class="col-2">
		<input type='text' id='relevanssi_weight_category' name='relevanssi_weight_category' size='4' value='<?php echo esc_attr( $category_value ); ?>' />
	</td>
</tr>
	<?php
}

/**
 * Creates a line chart.
 *
 * @param array $labels   An array of labels for the line chart. These will be
 * wrapped in apostrophes.
 * @param array $datasets An array of (label, dataset) pairs.
 */
function relevanssi_create_line_chart( array $labels, array $datasets ) {
	$labels         = implode( ', ', array_map( 'relevanssi_add_apostrophes', $labels ) );
	$datasets_array = array();
	$bg_colors      = array(
		"'rgba(255, 99, 132, 0.2)'",
		"'rgba(0, 175, 255, 0.2)'",
	);
	$border_colors  = array(
		"'rgba(255, 99, 132, 1)'",
		"'rgba(0, 175, 255, 1)'",
	);
	foreach ( $datasets as $label => $values ) {
		$values           = implode( ', ', $values );
		$bg_color         = array_shift( $bg_colors );
		$border_color     = array_shift( $border_colors );
		$datasets_array[] = <<< EOJSON
	{
		label: "$label",
		data: [ $values ],
		backgroundColor: [ $bg_color ],
		borderColor: [ $border_color ],
		borderWidth: 2,
		fill: {
			target: 'origin',
			below: $border_color,
		},
		pointRadius: 1,
		cubicInterpolationMode: 'monotone'
	}
EOJSON;
	}
	?>
<canvas id="search_chart" height="100"></canvas>
<script>
var ctx = document.getElementById('search_chart').getContext('2d');
var myChart = new Chart(ctx, {
	type: 'line',
	data: {
		labels: [<?php echo $labels; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>],
		datasets: [<?php echo implode( ",\n", $datasets_array ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>],
	},
	options: {
		scales: {
			y: {
				beginAtZero: true
			}
		}
	}
});
</script>
	<?php
}
