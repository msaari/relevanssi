<?php
/**
 * /lib/tabs/searching-tab.php
 *
 * Prints out the fully consolidated Search Behavior tab using the factory pattern.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the searching tab in Relevanssi settings.
 *
 * @global wpdb  $wpdb                The WordPress database interface.
 * @global array $relevanssi_variables The global Relevanssi variables array.
 * @return void Writes layout and table configurations directly to screen.
 */
function relevanssi_searching_tab() {
	global $wpdb, $relevanssi_variables;

	$is_premium = defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM;

	// Get saved search settings.
	$implicit               = get_option( 'relevanssi_implicit_operator' );
	$orderby                = get_option( 'relevanssi_default_orderby' );
	$fuzzy                  = get_option( 'relevanssi_fuzzy' );
	$throttle               = get_option( 'relevanssi_throttle' );
	$exact_match_bonus      = get_option( 'relevanssi_exact_match_bonus' );
	$wpml_only_current      = get_option( 'relevanssi_wpml_only_current' );
	$polylang_allow_all     = get_option( 'relevanssi_polylang_all_languages' );
	$admin_search           = get_option( 'relevanssi_admin_search' );
	$disable_or_fallback    = get_option( 'relevanssi_disable_or_fallback' );
	$respect_exclude        = get_option( 'relevanssi_respect_exclude' );
	$cat                    = get_option( 'relevanssi_cat' );
	$excat                  = get_option( 'relevanssi_excat' );
	$exclude_posts          = get_option( 'relevanssi_exclude_posts' );
	$ignore_theme_post_type = get_option( 'relevanssi_ignore_theme_post_type' );
	$index_post_types       = get_option( 'relevanssi_index_post_types', array() );
	$index_users            = get_option( 'relevanssi_index_users' );
	$index_terms            = get_option( 'relevanssi_index_taxonomies' );

	if ( ! $throttle ) {
		$docs_count = get_transient( 'relevanssi_docs_count' );
		if ( ! $docs_count ) {
			$docs_count = $wpdb->get_var( 'SELECT COUNT(DISTINCT doc) FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE doc != -1' );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			set_transient( 'relevanssi_docs_count', $docs_count, WEEK_IN_SECONDS );
		}
	} else {
		$docs_count = null;
	}

	// =========================================================================
	// 1. SEARCH CONFIGURATIONS
	// =========================================================================

	// --- Card A: Basic Search Settings ---
	$basic_search_config = array(
		'relevanssi_implicit_operator'   => array(
			'type'          => 'select',
			'label'         => __( 'Default operator', 'relevanssi' ),
			'hover_target'  => 'sb-def-operator',
			'value'         => $implicit,
			'options'       => array(
				'AND' => __( 'AND', 'relevanssi' ),
				'OR'  => __( 'OR', 'relevanssi' ),
			),
			'sidebar_title' => __( 'Search Matching Behavior:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Choose how multiple search words are matched. <code>AND</code> means every typed word must be present in a post to show it. <code>OR</code> means showing any post that matches at least one of the typed words.', 'relevanssi' ),
		),
		'relevanssi_disable_or_fallback' => array(
			'type'          => 'checkbox',
			'label'         => __( 'Fallback to OR', 'relevanssi' ),
			'description'   => __( 'Disable the OR fallback.', 'relevanssi' ),
			'hover_target'  => 'sb-disable-or',
			'value'         => $disable_or_fallback,
			'visible'       => 'AND' === $implicit,
			'sidebar_title' => __( 'Fallback to OR Matching:', 'relevanssi' ),
			'sidebar_desc'  => __( 'If a strict all-words (AND) search fails to find anything, Relevanssi will automatically search again matching any single word (OR) so your visitors do not see an empty results page. Check this box to turn off this automatic backup search.', 'relevanssi' ),
		),
		'relevanssi_default_orderby'     => array(
			'type'          => 'select',
			'label'         => __( 'Default order for search results', 'relevanssi' ),
			'hover_target'  => 'sb-def-order',
			'value'         => $orderby,
			'options'       => array(
				'relevance' => __( 'Relevance', 'relevanssi' ),
				'post_date' => __( 'Post date', 'relevanssi' ),
			),
			'sidebar_title' => __( 'Sort Order:', 'relevanssi' ),
			/* translators: %s is the code-styled name of the sorting parameter. */
			'sidebar_desc'  => sprintf( __( 'Sorting by "Relevance" ensures the best matching posts appear at the top of the list. If you need to override this or use custom sort rules (like sorting by relevance first, then by title), you can use the %s query parameter.', 'relevanssi' ), '<code>orderby</code>' ),
		),
		'relevanssi_fuzzy'               => array(
			'type'          => 'select',
			'label'         => __( 'Keyword matching', 'relevanssi' ),
			'hover_target'  => 'sb-kw-matching',
			'value'         => $fuzzy,
			'options'       => array(
				'never'  => __( 'Whole words only', 'relevanssi' ),
				'always' => __( 'Partial words', 'relevanssi' ),
			),
			'sidebar_title' => __( 'Word Matching Type:', 'relevanssi' ),
			'sidebar_desc'  => __( '"Whole words" means the search term must match the exact word. "Partial words" matches words that begin or end with the search term (for example, searching for "cat" will also display "catapult" or "bobcat").', 'relevanssi' ),
		),
		'relevanssi_exact_match_bonus'   => array(
			'type'          => 'checkbox',
			'label'         => __( 'Boost exact matches', 'relevanssi' ),
			'description'   => __( 'Give a ranking boost to exact phrase matches.', 'relevanssi' ),
			'value'         => $exact_match_bonus,
			'hover_target'  => 'sb-exact-match',
			'sidebar_title' => __( 'Phrase Boosting:', 'relevanssi' ),
			/* translators: %s is the name of the filter hook. */
			'sidebar_desc'  => sprintf( __( 'When checked, posts where the exact search words appear together in order as a complete phrase will rank higher. To adjust the strength of this boost, you can use the %s code filter.', 'relevanssi' ), '<code>relevanssi_exact_match_bonus</code>' ),
		),
	);

	if ( $is_premium ) {
		$basic_search_config['relevanssi_implicit_operator']['sidebar_desc'] .=
			' ' . sprintf(
				/* translators: %s is the name of the query parameter variable. */
				__( 'You can override this setting dynamically using the %s parameter in your search URLs.', 'relevanssi' ),
				'<code>"operator"</code>'
			);
		$basic_search_config['relevanssi_default_orderby']['sidebar_desc'] .=
			' ' . __( 'If you want date-based results with relevance, see the recent post bonus settings inside the Weights panel.', 'relevanssi' );
	}

	if ( 'sometimes' === $fuzzy ) {
		$basic_search_config['relevanssi_fuzzy']['options']['sometimes'] = __( 'Partial words if no hits for whole words', 'relevanssi' );
		$basic_search_config['relevanssi_fuzzy']['notice']               = array(
			'type' => 'warning',
			'text' => __( 'This option is outdated and will be removed in future versions. Please switch to either "Partial words" or "Whole words only".', 'relevanssi' ),
		);
	}

	if ( $is_premium ) {
		$basic_search_config = apply_filters( 'relevanssi_form_did_you_mean', $basic_search_config );
	}

	// --- Card B: Search Scope & Throttling ---
	$scope_config = array(
		'relevanssi_admin_search'           => array(
			'type'          => 'checkbox',
			'label'         => __( 'Admin search', 'relevanssi' ),
			'description'   => __( 'Use Relevanssi for searches inside the WordPress dashboard.', 'relevanssi' ),
			'value'         => $admin_search,
			'hover_target'  => 'sb-scope-admin',
			'sidebar_title' => __( 'Dashboard Search:', 'relevanssi' ),
			'sidebar_desc'  => __( "When checked, Relevanssi will manage search requests within your WordPress admin dashboard. Note: This doesn't work everywhere (such as the Media Library or Pages) due to core WordPress restrictions.", 'relevanssi' ),
		),
		'relevanssi_respect_exclude'        => array(
			'type'          => 'checkbox',
			/* translators: %s is the name of the structural setting parameter. */
			'label'         => sprintf( __( 'Respect %s', 'relevanssi' ), 'exclude_from_search' ),
			/* translators: %s is the name of the code snippet setting container. */
			'description'   => sprintf( __( 'Respect the %s flag for custom post types.', 'relevanssi' ), '<code>exclude_from_search</code>' ),
			'value'         => $respect_exclude,
			'hover_target'  => 'sb-scope-exclude',
			'sidebar_title' => __( 'Exclude Parameter:', 'relevanssi' ),
			'sidebar_desc'  => __( 'When checked, Relevanssi will hide any custom post types that have explicitly been set to hide from public search results.', 'relevanssi' ),
		),
		'relevanssi_ignore_theme_post_type' => array(
			'type'          => 'checkbox',
			'label'         => __( 'Ignore theme post type settings', 'relevanssi' ),
			'description'   => __( 'Ignore search restrictions set by third-party themes or plugins.', 'relevanssi' ),
			'value'         => $ignore_theme_post_type,
			'hover_target'  => 'sb-scope-ignore-theme',
			'sidebar_title' => __( 'Theme Restrictions:', 'relevanssi' ),
			/* translators: %1$s and %2$s are the code styled post type query variables. */
			'sidebar_desc'  => sprintf( __( 'When checked, Relevanssi ignores default restrictions set by your theme or templates. All indexed content types are included in search results. To limit specific search queries manually, use the %1$s or %2$s parameters.', 'relevanssi' ), '<code>post_type</code>', '<code>post_types</code>' ),
		),
		'relevanssi_throttle'               => array(
			'type'          => 'checkbox',
			'label'         => __( 'Throttle searches', 'relevanssi' ),
			'description'   => __( 'Limit the searches on large websites to protect performance.', 'relevanssi' ),
			'value'         => $throttle,
			'hover_target'  => 'sb-throttle-toggle',
			'sidebar_title' => __( 'Search Throttling:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Limits the search engine to checking a maximum of 500 items per search term. This prevents server slowdowns on massive sites, but might skip older or lower-ranked pages during very broad searches.', 'relevanssi' ),
		),
	);

	if ( ! empty( $respect_exclude ) ) {
		$pt_1               = get_post_types( array( 'exclude_from_search' => '1' ) );
		$pt_2               = get_post_types( array( 'exclude_from_search' => true ) );
		$private_types      = array_merge( $pt_1, $pt_2 );
		$problem_post_types = array_intersect( $index_post_types, $private_types );

		if ( ! empty( $problem_post_types ) ) {
			$scope_config['relevanssi_respect_exclude']['notice'] = array(
				'type' => 'warning',
				'text' => __( 'You should probably uncheck this option. You have instructed Relevanssi to index the following hidden content types, but this setting is currently blocking them from appearing:', 'relevanssi' ) . ' ' . esc_html( implode( ', ', $problem_post_types ) ),
			);
		}
	}

	if ( $docs_count && $docs_count < 1000 ) {
		$scope_config['relevanssi_throttle']['notice'] = array(
			'type' => 'info',
			'text' => __( 'Your database is small enough that you do not need to enable search throttling.', 'relevanssi' ),
		);
	}

	if ( 'post_date' === $orderby && ( 'on' === $index_users || 'on' === $index_terms ) ) {
		$scope_config['relevanssi_throttle']['notice'] = array(
			'type' => 'warning',
			'text' => __( 'You have search ordering set to post date while user or category indexing is active. If you enable throttling under these conditions, users and category terms will be excluded from results. To fix this, either disable throttling or set your default sort order to Relevance.', 'relevanssi' ),
		);
	}

	if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_get_post' ) ) {
		$scope_config['relevanssi_wpml_only_current'] = array(
			'type'          => 'checkbox',
			'label'         => __( 'WPML Language Matching', 'relevanssi' ),
			'description'   => __( 'Limit search results to the current active language.', 'relevanssi' ),
			'value'         => $wpml_only_current,
			'hover_target'  => 'sb-scope-wpml',
			'sidebar_title' => __( 'WPML Integration:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Enabling this option restricts search results only to the language your visitor is currently browsing. Disabling it displays matching content from all languages combined.', 'relevanssi' ),
		);
	}

	if ( function_exists( 'pll_get_post' ) ) {
		$scope_config['relevanssi_polylang_all_languages'] = array(
			'type'          => 'checkbox',
			'label'         => __( 'Polylang Language Matching', 'relevanssi' ),
			'description'   => __( 'Show search results from all languages.', 'relevanssi' ),
			'value'         => $polylang_allow_all,
			'hover_target'  => 'sb-scope-polylang',
			'sidebar_title' => __( 'Polylang Integration:', 'relevanssi' ),
			'sidebar_desc'  => __( 'By default, Polylang limits searches to the user\'s current language. Checking this box lifts that restriction and allows cross-language results.', 'relevanssi' ),
		);
	}

	// --- Card C: Exclusions ---
	$exclusions_config = array();

	if ( $cat ) {
		$exclusions_config['relevanssi_cat'] = array(
			'type'          => 'category_checklist',
			'label'         => __( 'Category restriction', 'relevanssi' ),
			'value'         => $cat,
			'hover_target'  => 'sb-exclude-cat-inc',
			'sidebar_title' => __( 'Include Categories:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Limits all searches across your site to the selected categories. To restrict results dynamically on specific pages, see the Help section.', 'relevanssi' ),
		);
	}

	if ( $excat ) {
		$exclusions_config['relevanssi_excat'] = array(
			'type'          => 'category_checklist',
			'label'         => __( 'Category exclusion', 'relevanssi' ),
			'value'         => $excat,
			'hover_target'  => 'sb-exclude-cat-exc',
			'sidebar_title' => __( 'Exclude Categories:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Posts assigned to these checked categories will be hidden from search results. To remove them from the database entirely, see the Help section.', 'relevanssi' ),
		);
	}

	$exclusions_config['relevanssi_exclude_posts'] = array(
		'type'          => 'text',
		'label'         => __( 'Exclude specific IDs', 'relevanssi' ),
		'tooltip'       => __( 'example: 2,3,5,8', 'relevanssi' ),
		'placeholder'   => __( '2,3,5,8', 'relevanssi' ),
		'value'         => $exclude_posts,
		'hover_target'  => 'sb-exclude-posts-string',
		'sidebar_title' => __( 'Hide Specific Pages:', 'relevanssi' ),
		'sidebar_desc'  => __( 'Enter a list of post or page IDs separated by commas to stop them from appearing in your public search results.', 'relevanssi' ),
	);

	if ( $is_premium ) {
		$exclusions_config['relevanssi_exclude_posts']['sidebar_desc'] .=
			' ' . __( 'With Relevanssi Premium, it is easier to use the exclusion checkbox directly inside the post editor page. That completely drops the post from the index and works cleanly across multi-site networks.', 'relevanssi' );
	}

	// --- Card D: Relevance Weights ---
	$weights_config = array(
		'weights' => array(
			'type'          => 'weights_table',
			'label'         => __( 'Basic scores', 'relevanssi' ),
			'hover_target'  => 'sb-weights',
			'callback'      => 'relevanssi_render_weights_callback',
			'sidebar_title' => __( 'Adjust Weights:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Control how much value Relevanssi assigns to different parts of your pages. Changing these multipliers alters how high posts rank based on where keywords are found.', 'relevanssi' ),
		),
	);

	if ( $is_premium ) {
		$weights_config = apply_filters( 'relevanssi_weights_config', $weights_config );
		$weights_config = apply_filters( 'relevanssi_form_recency_cutoff', $weights_config );
	} else {
		$weights_config['premium_weights_upsell'] = array(
			'type'          => 'upsell',
			'label'         => __( 'Advanced Weighting', 'relevanssi' ),
			'feature_name'  => __( 'Smart Relevance & Timing Multipliers', 'relevanssi' ),
			'features_list' => array(
				__( 'Recency Scoring Bonus: Boost newer posts automatically so fresh content outranks old, stale pages.', 'relevanssi' ),
				__( 'Internal Link Multiplier: Reward pages that your other articles frequently link to using matching keywords.', 'relevanssi' ),
				__( 'Taxonomy & Profile Weighting: Fine-tune search importance for standalone categories, tags, and user profiles.', 'relevanssi' ),
			),
			'hover_target'  => 'sb-premium-weights',
			'sidebar_title' => __( 'Premium Weights:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Unlock timing control and internal link value distribution to make your search results noticeably smarter and more relevant.', 'relevanssi' ),
		);
	}

	$weights_config['relevanssi_link_boost'] = array(
		'type'          => 'number',
		'step'          => 0.01,
		'label'         => __( 'Internal links boost', 'relevanssi' ),
		'hover_target'  => 'sb-weights-link-boost',
		'value'         => get_option( 'relevanssi_link_boost', '0.75' ),
		'sidebar_title' => __( 'Internal Link Boost:', 'relevanssi' ),
		'sidebar_desc'  => __( 'Boosts the search score of posts when search terms are found inside the text of internal links pointing to them.', 'relevanssi' ),
		'visible'       => $is_premium,
	);

	// --- Card E: Search Redirects ---
	$redirects_config = array(
		'relevanssi_redirects' => array(
			'type'          => $is_premium ? 'redirects' : 'upsell',
			'label'         => __( 'Redirect Rules', 'relevanssi' ),
			'tooltip'       => __( 'Don\'t forget to save the options when you are done!', 'relevanssi' ),
			'hover_target'  => 'sb-redirects-workspace',
			'feature_name'  => __( 'Search Term Redirects', 'relevanssi' ),
			'features_list' => array(
				__( 'Direct Matching: Skip standard results pages completely and forward users straight to a specific link when they search for certain words.', 'relevanssi' ),
				__( 'Partial Word Matches: Redirect users even if their search query only contains a part of your keyword rule.', 'relevanssi' ),
				__( 'Empty Search Routing: Send visitors to a custom destination (like a contact form or help center) if their search yields zero results.', 'relevanssi' ),
				__( 'Blank Search Fallback: Redirect users who click search without typing anything.', 'relevanssi' ),
				__( 'Bulk Import: Save time by importing a list of search redirects from a CSV file.', 'relevanssi' ),
			),
			'sidebar_title' => __( 'Search Redirects:', 'relevanssi' ),
			'sidebar_desc'  => __( 'Forward specific search queries directly to custom landing pages. For example, sending searches for "help" straight to your contact page. <br/><br/><strong>Partial Matching:</strong> If turned on, the redirect triggers if the keyword phrase appears anywhere inside the search query. Use partial settings carefully to avoid accidental redirect loops.', 'relevanssi' ),
		),
	);

	// --- Card F: Search Synonyms ---
	$synonyms_config = array(
		'relevanssi_synonyms_container' => array(
			'type'          => 'synonyms',
			'label'         => __( 'Search Synonyms', 'relevanssi' ),
			'placeholder'   => __( 'people = person', 'relevanssi' ),
			'tooltip'       => __( 'Don\'t forget to save the options when you are done!', 'relevanssi' ),
			'hover_target'  => 'sb-synonyms-workspace',
			'sidebar_title' => __( 'Synonyms & Variations:', 'relevanssi' ),
			'sidebar_desc'  => sprintf(
				/* translators: %1$s, %2$s, %3$s and %4$s are formatting code tags detailing setup examples. */
				__( 'Format entries as %1$sword = synonym%2$s (one rule per line). For example, %3$sdog = hound%4$s means searching for "dog" will also automatically find posts containing "hound". These rules are one-way; to make the relationship work both ways, add a second line reversing the words (%1$shound = dog%2$s). Synonyms can contain multiple words, but the main search term must be a single word.', 'relevanssi' ),
				'<code>',
				'</code>',
				'<code>',
				'</code>'
			),
		),
	);

	// --- Card G: Multisite Settings ---
	if ( is_multisite() ) {
		$multisite_config = array(
			'relevanssi_searchblogs_all' => array(
				'type'          => 'checkbox',
				'label'         => __( 'Search all subsites', 'relevanssi' ),
				'description'   => __( 'Automatically include all network subsites in search results.', 'relevanssi' ),
				'value'         => get_option( 'relevanssi_searchblogs_all', 'off' ),
				'hover_target'  => 'sb-ms-search-all',
				'sidebar_title' => __( 'Network Scope:', 'relevanssi' ),
				'sidebar_desc'  => __( 'If checked, multisite searches will include all subsites. Warning: if you have dozens of sites in your network, searches may become slow. This can be overridden directly from the search form.', 'relevanssi' ),
			),
			'relevanssi_searchblogs'     => array(
				'type'          => 'text',
				'label'         => __( 'Target Specific Subsites', 'relevanssi' ),
				'placeholder'   => '2, 5, 9',
				'value'         => get_option( 'relevanssi_searchblogs', '' ),
				'hover_target'  => 'sb-ms-search-specific',
				'sidebar_title' => __( 'Target Subsite IDs:', 'relevanssi' ),
				'sidebar_desc'  => __( 'Enter a comma-separated list of subsite IDs. This can be overridden directly from the search form.', 'relevanssi' ),
			),
		);
	}

		// =========================================================================
		// 2. MARKUP CONTAINER VIEW GENERATION
		// =========================================================================
	?>
	<div id="searching_tab_consolidated" class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Search Behavior', 'relevanssi' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Configure how the search engine reads queries, handles empty results, and sorts your matching content.', 'relevanssi' ); ?></p>

		<div class="relevanssi-dashboard-layout">
			<div class="relevanssi-main">

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-basic-search">
							<h2><?php esc_html_e( 'Basic Settings', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $basic_search_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Matching Behavior', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Adjust primary configuration rules governing phrase boosts, order sorting, and partial keyword configurations.', 'relevanssi' ); ?></p>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $basic_search_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-searching-scopes">
							<h2><?php esc_html_e( 'Search Scope & Performance', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $scope_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Scope & Performance', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Manage where the search runs (like the admin dashboard) and configure safety limits like throttling to protect server speeds.', 'relevanssi' ); ?></p>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $scope_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-searching-exclusions">
							<h2><?php esc_html_e( 'Exclusion Rules & Filters', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $exclusions_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Exclusions Processing', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Block specific content pools, page IDs, or entire post categories from showing up on frontend search returns.', 'relevanssi' ); ?></p>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $exclusions_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row">
					<div class="relevanssi-settings-content">
						<details class="relevanssi-card" id="card-weights" open>
							<summary><h2><?php esc_html_e( 'Weight Multipliers', 'relevanssi' ); ?></h2></summary>
							<?php Relevanssi_Settings_Renderer::render_table( $weights_config ); ?>
						</details>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Weight Settings', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'All values are multipliers. To add search importance to an element, use numbers higher than 1. To lower importance, use numbers below 1.', 'relevanssi' ); ?></p>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $weights_config ); ?>
							</ul>
							<hr style="border: 0; border-top: 1px solid #e0e0e0; margin: 16px 0;">
							<p><strong><span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'Developer Tips', 'relevanssi' ); ?></strong></p>
							<p><?php esc_html_e( 'You can safely hook into Relevanssi filters to refine scoring metrics. Custom filters on relevanssi_match or relevanssi_results offer a reliable entry point for adjusting the weights.', 'relevanssi' ); ?></p>
						</div>
					</aside>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content" style="<?php echo ! $is_premium ? 'width: 100%; max-width: 100%;' : ''; ?>">
						<div class="relevanssi-card" id="card-redirects">
							<h2><?php esc_html_e( 'Redirects', 'relevanssi' ); ?></h2>
							<p class="description" style="margin-bottom: 20px;">
								<?php
								if ( $is_premium ) {
									printf(
										/* translators: %1$s opens a documentation link, %2$s closes the link anchor. */
										esc_html__( 'Set up search word triggers to automatically forward visitors directly to specific pages before standard search rules run. %1$sRead documentation guide →%2$s', 'relevanssi' ),
										'<a href="https://www.relevanssi.com/user-manual/redirects/" target="_blank" rel="noopener noreferrer" style="text-decoration: none; margin-left: 4px;">',
										'</a>'
									);
								} else {
									esc_html_e( 'Automatically intercept specific search terms to bypass standard results pages entirely, sending visitors straight to designated help articles or landing pages.', 'relevanssi' );
								}
								?>
							</p>
							<?php Relevanssi_Settings_Renderer::render_table( $redirects_config ); ?>
						</div>
					</div>
					<?php if ( $is_premium ) : ?>
						<aside class="relevanssi-settings-sidebar">
							<div class="relevanssi-info-box">
								<h3><?php esc_html_e( 'Redirections', 'relevanssi' ); ?></h3>
								<p><?php esc_html_e( 'Configure targeted search rules to forward matched intent phrases directly to specific system paths.', 'relevanssi' ); ?></p>
								<ul class="relevanssi-sidebar-list">
									<?php Relevanssi_Settings_Renderer::render_sidebar_list( $redirects_config ); ?>
								</ul>
							</div>
						</aside>
					<?php endif; ?>
				</div>

				<div class="relevanssi-settings-row" style="margin-bottom: 24px;">
					<div class="relevanssi-settings-content">
						<div class="relevanssi-card" id="card-synonyms">
							<h2><?php esc_html_e( 'Synonyms', 'relevanssi' ); ?></h2>
							<?php Relevanssi_Settings_Renderer::render_table( $synonyms_config ); ?>
						</div>
					</div>
					<aside class="relevanssi-settings-sidebar">
						<div class="relevanssi-info-box">
							<h3><?php esc_html_e( 'Synonyms Engine', 'relevanssi' ); ?></h3>
							<p><?php esc_html_e( 'Connect word variations, spelling typos, and language abbreviations to your main content keywords.', 'relevanssi' ); ?></p>
							<ul class="relevanssi-sidebar-list">
								<?php Relevanssi_Settings_Renderer::render_sidebar_list( $synonyms_config ); ?>
							</ul>
						</div>
					</aside>
				</div>

		<?php
		if ( is_multisite() ) :
			?>
	<div class="relevanssi-settings-row">
		<div class="relevanssi-settings-content">
			<div class="relevanssi-card" id="card-multisite-routing">
				<h2><?php esc_html_e( 'Multisite Network Reach Settings', 'relevanssi' ); ?></h2>
					<?php Relevanssi_Settings_Renderer::render_table( $multisite_config ); ?>
			</div>
		</div>
		<aside class="relevanssi-settings-sidebar">
			<div class="relevanssi-info-box">
				<h3><?php esc_html_e( 'Network Visibility', 'relevanssi' ); ?></h3>
				<ul class="relevanssi-sidebar-list">
						<?php Relevanssi_Settings_Renderer::render_sidebar_list( $multisite_config ); ?>
				</ul>
			</div>
		</aside>
	</div>
	<?php endif; ?>

			</div>
		</div>
	</div>

	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			const operatorSelect = document.getElementById('relevanssi_implicit_operator');
			const fallbackRow = document.getElementById('row_relevanssi_disable_or_fallback');

			function evaluateSearchingDependencies() {
				if (!operatorSelect || !fallbackRow) return;
				if (operatorSelect.value === 'AND') {
					fallbackRow.classList.remove('rlv-js-hidden');
				} else {
					fallbackRow.classList.add('rlv-js-hidden');
				}
			}

			if (operatorSelect) {
				operatorSelect.addEventListener('change', evaluateSearchingDependencies);
				evaluateSearchingDependencies();
			}
		});
	</script>
		<?php
}

