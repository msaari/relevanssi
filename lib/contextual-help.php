<?php
/**
 * /lib/contextual-help.php
 *
 * Adds the contextual help menus.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Displays the contextual help menu.
 *
 * @global object $wpdb The WP database interface.
 */
function relevanssi_admin_help() {
	global $wpdb;

	$screen = get_current_screen();

	// Translators: %1$s is 'orderby', %2$s is the Codex page URL.
	$orderby_parameters = sprintf( __( "To adjust the post order, you can use the %1\$s query parameter. With %1\$s, you can use multiple layers of different sorting methods. See <a href='%2\$s'>WordPress Codex</a> for more details on using arrays for orderby.", 'relevanssi' ), '<code>orderby</code>', 'https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters' );
	// Translators: %s is 'relevanssi_throttle_limit'.
	$throttle_limit = sprintf( __( 'In order to adjust the throttle limit, you can use the %s filter hook.', 'relevanssi' ), '<code>pre_option_relevanssi_throttle_limit</code>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-searching',
			'title'   => __( 'Searching', 'relevanssi' ),
			'content' => '<ul>' .
				"<li>$orderby_parameters</li>" .
				'<li>' . __( "Inside-word matching is disabled by default, because it increases garbage results that don't really match the search term. If you want to enable it, add the following function to your theme functions.php:", 'relevanssi' ) .
				'<pre>add_filter( \'relevanssi_fuzzy_query\', \'rlv_partial_inside_words\' );
function rlv_partial_inside_words( $query ) {
	return "(relevanssi.term LIKE \'%#term#%\')";
}</pre></li>' .
				"<li>$throttle_limit" .
				'<pre>add_filter( \'pre_option_relevanssi_throttle_limit\', function( $limit ) { return 200; } );</pre></li>' .
				'<li>' . __( "It's not usually necessary to adjust the limit from 500, but in some cases performance gains can be achieved by setting a lower limit. We don't suggest going under 200, as low values will make the results worse.", 'relevanssi' ) . '</li>' .
				'</ul>',
		)
	);

	// Translators: %s is the link to the Codex page.
	$codex_documentation = sprintf( __( 'For all the possible options, see the Codex documentation for %s.', 'relevanssi' ), '<a href="https://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-search-restrictions',
			'title'   => __( 'Restrictions', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( 'If you want the general search to target all posts, but have a single search form target only certain posts, you can add a hidden input variable to the search form.', 'relevanssi' ) . '</li>' .
				'<li>' . __( 'For example in order to restrict the search to categories 10, 14 and 17, you could add this to the search form:', 'relevanssi' ) .
				'<pre>&lt;input type="hidden" name="cats" value="10,14,17" /&gt;</pre></li>' .
				'<li>' . __( 'To restrict the search to posts tagged with alfa AND beta, you could add this to the search form:', 'relevanssi' ) .
				'<pre>&lt;input type="hidden" name="tag" value="alfa+beta" /&gt;</pre></li>' .
				"<li>$codex_documentation</li>" .
				'</ul>',
		)
	);
	// Translators: %s is the link to the Codex page.
	$exclusion_options = sprintf( __( 'For more exclusion options, see the Codex documentation for %s. For example, to exclude tag ID 10, use', 'relevanssi' ), '<a href="https://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>' );
	// Translators: %s is 'relevanssi_do_not_index'.
	$exclusion_instructions = sprintf( __( 'To exclude posts from the index and not just from the search, you can use the %s filter hook. This would not index posts that have a certain taxonomy term:', 'relevanssi' ), '<code>relevanssi_do_not_index</code>' );
	// Translators: %s is a link to the Relevanssi knowledge base.
	$more_examples = sprintf( __( "For more examples, see <a href='%s'>the related knowledge base posts</a>.", 'relevanssi' ), 'https://www.relevanssi.com/tag/relevanssi_do_not_index/' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-search-exclusions',
			'title'   => __( 'Exclusions', 'relevanssi' ),
			'content' => '<ul>' .
				"<li>$exclusion_options" .
				'<pre>&lt;input type="hidden" name="tag__not_in" value="10" /&gt;</pre></li>' .
				"<li>$exclusion_instructions" .
				'<pre>add_filter( \'relevanssi_do_not_index\', \'rlv_index_filter\', 10, 2 );
	function rlv_index_filter( $block, $post_id ) {
		if ( has_term( \'jazz\', \'genre\', $post_id ) ) {
			$block = true;
		}
		return $block;
	}
	</pre></li>' .
				"<li>$more_examples</li>" .
				'</ul>',
		)
	);

	// Translators: %s is 'relevanssi_user_searches_limit'.
	$user_searches_limit = sprintf( __( 'By default, the User searches page shows 20 most common keywords. In order to see more, you can adjust the value with the %s filter hook, like this:', 'relevanssi' ), '<code>relevanssi_user_searches_limit</code>' );
	// Translators: %s is the name of the database table.
	$log_database = sprintf( __( 'The complete logs are stored in the %s database table, where you can access them if you need more information than what the User searches page provides.', 'relevanssi' ), '<code>' . $wpdb->prefix . 'relevanssi_log</code>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-logging',
			'title'   => __( 'Logs', 'relevanssi' ),
			'content' => '<ul>' .
				"<li>$user_searches_limit" .
				"<pre>add_filter( 'relevanssi_user_searches_limit', function() { return 50; } );</pre></li>" .
				"<li>$log_database</li>" .
				'</ul>',
		)
	);

	// Translators: %s is 'the_excerpt()'.
	$custom_snippets = sprintf( __( 'Custom snippets require that the search results template uses %s to print out the excerpts.', 'relevanssi' ), '<code>the_excerpt()</code>' );
	// Translators: %1$s is 'relevanssi_pre_excerpt_content', %2$s is 'relevanssi_excerpt_content'.
	$pre_excerpt_content = sprintf( __( 'If you want more control over what content Relevanssi uses to create the excerpts, you can use the %1$s and %2$s filter hooks to adjust the content.', 'relevanssi' ), '<code>relevanssi_pre_excerpt_content</code>', '<code>relevanssi_excerpt_content</code>' );
	// Translators: %s is 'relevanssi_disable_shortcodes_excerpt'.
	$disable_shortcodes = sprintf( __( 'Some shortcode do not work well with Relevanssi excerpt-generation. Relevanssi disables some shortcodes automatically to prevent problems. This can be adjusted with the %s filter hook.', 'relevanssi' ), '<code>relevanssi_disable_shortcodes_excerpt</code>' );
	// Translators: %s is 'relevanssi_optimize_excerpts'.
	$optimize_excerpts = sprintf( __( "If you want Relevanssi to build excerpts faster and don't mind that they may be less than perfect in quality, add a filter that returns true on hook %s.", 'relevanssi' ), '<code>relevanssi_optimize_excerpts</code>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-excerpts',
			'title'   => __( 'Excerpts', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( 'Building custom excerpts can be slow. If you are not actually using the excerpts, make sure you disable the option.', 'relevanssi' ) . '</li>' .
				"<li>$custom_snippets</li>" .
				'<li>' . __( 'Generally, Relevanssi generates the excerpts from post content. If you want to include custom field content in the excerpt-building, this can be done with a simple setting from the excerpt settings.', 'relevanssi' ) . '</li>' .
				"<li>$pre_excerpt_content</li>" .
				"<li>$disable_shortcodes</li>" .
				"<li>$optimize_excerpts" .
				"<pre>add_filter( 'relevanssi_optimize_excerpts', '__return_true' );</pre></li>" .
				'</ul>',
		)
	);

	// Translators: %1$s is 'the_title()', %2$s is 'relevanssi_the_title()'.
	$the_title = sprintf( __( 'In order to see title highlights from Relevanssi, replace %1$s in the search results template with %2$s. It does the same thing, but supports Relevanssi title highlights.', 'relevanssi' ), '<code>the_title()</code>', '<code>relevanssi_the_title()</code>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-highlights',
			'title'   => __( 'Highlights', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( "Title highlights don't appear automatically, because that led to problems with highlights appearing in wrong places and messing up navigation menus, for example.", 'relevanssi' ) . '</li>' .
				"<li>$the_title</li>" .
				'</ul>',
		)
	);

	// Translators: %1$s is 'relevanssi_punctuation_filter', %2$s is 'relevanssi_remove_punctuation'.
	$remove_punctuation = sprintf( __( 'For more fine-tuned changes, you can use %1$s filter hook to adjust what is replaced with what, and %2$s filter hook to completely override the default punctuation control.', 'relevanssi' ), '<code>relevanssi_punctuation_filter</code>', '<code>relevanssi_remove_punctuation</code>' );
	// Translators: %s is the URL to the Knowledge Base entry.
	$remove_punct_guide = sprintf( __( "For more examples, see <a href='%s'>the related knowledge base posts</a>.", 'relevanssi' ), 'https://www.relevanssi.com/tag/relevanssi_remove_punct/' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-punctuation',
			'title'   => __( 'Punctuation', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( 'Relevanssi removes punctuation. Some punctuation is removed, some replaced with spaces. Advanced indexing settings include some of the more common settings people want to change.', 'relevanssi' ) . '</li>' .
				"<li>$remove_punctuation</li>" .
				"<li>$remove_punct_guide</li>" .
				'</ul>',
		)
	);

	// Translators: %s is '[noindex]'.
	$noindex = sprintf( __( "If you have content that you don't want indexed, you can wrap that content in a %s shortcode.", 'relevanssi' ), '<code>[noindex]</code>' );
	// Translators: %s is '[searchform]'.
	$searchform = sprintf( __( 'If you need a search form on some page on your site, you can use the %s shortcode to print out a basic search form.', 'relevanssi' ), '<code>[searchform]</code>' );
	// Translators: %1$s is '[searchform post_types="page"]', %2$s is '[searchform cats="10,14,17"]'.
	$searchform_cats = sprintf( __( 'If you need to add query variables to the search form, the shortcode takes parameters, which are then printed out as hidden input fields. To get a search form with a post type restriction, you can use %1$s. To restrict the search to categories 10, 14 and 17, you can use %2$s and so on.', 'relevanssi' ), '<code>[searchform post_types="page"]</code>', '<code>[searchform cats="10,14,17"]</code>' );
	// Translators: %1$s is 'dropdown', %2$s is '[searchform dropdown="category"]'.
	$searchform_dropdown = sprintf( __( 'You can use the %1$s parameter to add a taxonomy dropdown to the search form. Just use the name of the taxonomy, like %2$s. This works best with hierarchical taxonomies like categories with relatively few options available.', 'relevanssi' ), '<code>dropdown</code>', '<code>[searchform dropdown="category"]</code>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-helpful-shortcodes',
			'title'   => __( 'Helpful shortcodes', 'relevanssi' ),
			'content' => "<ul>
				<li>$noindex</li>
				<li>$searchform</li>
				<li>$searchform_cats</li>
				<li>$searchform_dropdown</li>
				</ul>",
		)
	);

	// Translators: %s is the Knowledge Base URL.
	$woocommerce = sprintf( __( "For more details how to fix that issue, see <a href='%s'>WooCommerce tips in Relevanssi user manual</a>.", 'relevanssi' ), 'https://www.relevanssi.com/user-manual/woocommerce/' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-title-woocommerce',
			'title'   => __( 'WooCommerce', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( "If your SKUs include hyphens or other punctuation, do note that Relevanssi replaces most punctuation with spaces. That's going to cause issues with SKU searches.", 'relevanssi' ) . '</li>' .
				"<li>$woocommerce</li>" .
				'<li>' . __( "If you don't want to index products that are out of stock, excluded from the catalog or excluded from the search, there's a product visibility filtering method that is described in the user manual (see link above).", 'relevanssi' ) . '</li>' .
				'</ul>',
		)
	);

	// Translators: %s is the name of the filter hook.
	$exact_match_bonus = sprintf( __( 'To adjust the amount of the exact match bonus, you can use the %s filter hook. It works like this:', 'relevanssi' ), '<code>relevanssi_exact_match_bonus</code>' );
	// Translators: %1$s is the title weight and %2$s is the content weight.
	$weights = sprintf( esc_html__( 'The default values are %1$s for titles and %2$s for content.', 'relevanssi' ), '<code>5</code>', '<code>2</code>' );

	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-exact-match',
			'title'   => __( 'Exact match bonus', 'relevanssi' ),
			'content' => '<ul>' .
				"<li>$exact_match_bonus" .
				"<pre>add_filter( 'relevanssi_exact_match_bonus', 'rlv_adjust_bonus' );
	function rlv_adjust_bonus( \$bonus ) {
		return array( 'title' => 10, 'content' => 5 );
	}</li>" .
				"<li>$weights</ul>",
		)
	);
	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'relevanssi' ) . '</strong></p>' .
		'<p><a href="https://www.relevanssi.com/knowledge-base/" target="_blank">' . __( 'Plugin knowledge base', 'relevanssi' ) . '</a></p>' .
		'<p><a href="https://wordpress.org/tags/relevanssi?forum_id=10" target="_blank">' . __( 'WordPress.org forum', 'relevanssi' ) . '</a></p>'
	);
}
