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
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-searching',
			'title'   => __( 'Searching', 'relevanssi' ),
			'content' => '<ul>' .
				// Translators: %1$s is 'orderby', %2$s is the Codex page URL.
				'<li>' . sprintf( __( "To adjust the post order, you can use the %1\$s query parameter. With %1\$s, you can use multiple layers of different sorting methods. See <a href='%2\$s'>WordPress Codex</a> for more details on using arrays for orderby.", 'relevanssi' ), '<code>orderby</code>', 'https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters' ) . '</li>' .
				'<li>' . __( "Inside-word matching is disabled by default, because it increases garbage results that don't really match the search term. If you want to enable it, add the following function to your theme functions.php:", 'relevanssi' ) .
				'<pre>add_filter( \'relevanssi_fuzzy_query\', \'rlv_partial_inside_words\' );
function rlv_partial_inside_words( $query ) {
	return "(relevanssi.term LIKE \'%#term#%\')";
}</pre></li>' .
				// Translators: %s is 'Uncheck this if you use non-ASCII characters' option name.
				'<li>' . sprintf( __( 'To get inside-word highlights, uncheck the "%s" option. That has a side-effect of enabling the inside-word highlights.', 'relevanssi' ), __( 'Uncheck this if you use non-ASCII characters', 'relevanssi' ) ) . '</li>' .
				// Translators: %s is 'relevanssi_throttle_limit'.
				'<li>' . sprintf( __( 'In order to adjust the throttle limit, you can use the %s filter hook.', 'relevanssi' ), '<code>pre_option_relevanssi_throttle_limit</code>' ) .
				'<pre>add_filter( \'pre_option_relevanssi_throttle_limit\', function( $limit ) { return 200; } );</pre></li>' .
				'<li>' . __( "It's not usually necessary to adjust the limit from 500, but in some cases performance gains can be achieved by setting a lower limit. We don't suggest going under 200, as low values will make the results worse.", 'relevanssi' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-search-restrictions',
			'title'   => __( 'Restrictions', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( 'If you want the general search to target all posts, but have a single search form target only certain posts, you can add a hidden input variable to the search form. ', 'relevanssi' ) . '</li>' .
				'<li>' . __( 'For example in order to restrict the search to categories 10, 14 and 17, you could add this to the search form:', 'relevanssi' ) .
				'<pre>&lt;input type="hidden" name="cats" value="10,14,17" /&gt;</pre></li>' .
				'<li>' . __( 'To restrict the search to posts tagged with alfa AND beta, you could add this to the search form:', 'relevanssi' ) .
				'<pre>&lt;input type="hidden" name="tag" value="alfa+beta" /&gt;</pre></li>' .
				// Translators: %s is the link to the Codex page.
				'<li>' . sprintf( __( 'For all the possible options, see the Codex documentation for %s.', 'relevanssi' ), '<a href="https://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-search-exclusions',
			'title'   => __( 'Exclusions', 'relevanssi' ),
			'content' => '<ul>' .
				// Translators: %s is the link to the Codex page.
				'<li>' . sprintf( __( 'For more exclusion options, see the Codex documentation for %s. For example, to exclude tag ID 10, use', 'relevanssi' ), '<a href="https://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>' ) .
				'<pre>&lt;input type="hidden" name="tag__not_in" value="10" /&gt;</pre></li>' .
				// Translators: %s is 'relevanssi_do_not_index'.
				'<li>' . sprintf( __( 'To exclude posts from the index and not just from the search, you can use the %s filter hook. This would not index posts that have a certain taxonomy term:', 'relevanssi' ), '<code>relevanssi_do_not_index</code>' ) .
				'<pre>add_filter( \'relevanssi_do_not_index\', \'rlv_index_filter\', 10, 2 );
	function rlv_index_filter( $block, $post_id ) {
		if ( has_term( \'jazz\', \'genre\', $post_id ) ) {
			$block = true;
		}
		return $block;
	}
	</pre></li>' .
				// Translators: %s is a link to the Relevanssi knowledge base.
				'<li>' . sprintf( __( "For more examples, see <a href='%s'>the related knowledge base posts</a>.", 'relevanssi' ), 'https://www.relevanssi.com/tag/relevanssi_do_not_index/' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-logging',
			'title'   => __( 'Logs', 'relevanssi' ),
			'content' => '<ul>' .
				// Translators: %s is 'relevanssi_user_searches_limit'.
				'<li>' . sprintf( __( 'By default, the User searches page shows 20 most common keywords. In order to see more, you can adjust the value with the %s filter hook, like this:', 'relevanssi' ), '<code>relevanssi_user_searches_limit</code>' ) .
				"<pre>add_filter( 'relevanssi_user_searches_limit', function() { return 50; } );</pre></li>" .
				// Translators: %s is the name of the database table.
				'<li>' . sprintf( __( 'The complete logs are stored in the %s database table, where you can access them if you need more information than what the User searches page provides.', 'relevanssi' ), '<code>' . $wpdb->prefix . 'relevanssi_log</code>' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-excerpts',
			'title'   => __( 'Excerpts', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( 'Building custom excerpts can be slow. If you are not actually using the excerpts, make sure you disable the option.', 'relevanssi' ) . '</li>' .
				// Translators: %s is 'the_excerpt()'.
				'<li>' . sprintf( __( 'Custom snippets require that the search results template uses %s to print out the excerpts.', 'relevanssi' ), '<code>the_excerpt()</code>' ) . '</li>' .
				'<li>' . __( 'Generally, Relevanssi generates the excerpts from post content. If you want to include custom field content in the excerpt-building, this can be done with a simple setting from the excerpt settings.', 'relevanssi' ) . '</li>' .
				// Translators: %1$s is 'relevanssi_pre_excerpt_content', %2$s is 'relevanssi_excerpt_content'.
				'<li>' . sprintf( __( 'If you want more control over what content Relevanssi uses to create the excerpts, you can use the %1$s and %2$s filter hooks to adjust the content.', 'relevanssi' ), '<code>relevanssi_pre_excerpt_content</code>', '<code>relevanssi_excerpt_content</code>' ) . '</li>' .
				// Translators: %s is 'relevanssi_disable_shortcodes_excerpt'.
				'<li>' . sprintf( __( 'Some shortcode do not work well with Relevanssi excerpt-generation. Relevanssi disables some shortcodes automatically to prevent problems. This can be adjusted with the %s filter hook.', 'relevanssi' ), '<code>relevanssi_disable_shortcodes_excerpt</code>' ) . '</li>' .
				// Translators: %s is 'relevanssi_optimize_excerpts'.
				'<li>' . sprintf( __( "If you want Relevanssi to build excerpts faster and don't mind that they may be less than perfect in quality, add a filter that returns true on hook %s.", 'relevanssi' ), '<code>relevanssi_optimize_excerpts</code>' ) .
				"<pre>add_filter( 'relevanssi_optimize_excerpts', '__return_true' );</pre></li>" .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-highlights',
			'title'   => __( 'Highlights', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( "Title highlights don't appear automatically, because that led to problems with highlights appearing in wrong places and messing up navigation menus, for example.", 'relevanssi' ) . '</li>' .
				// Translators: %1$s is 'the_title()', %2$s is 'relevanssi_the_title()'.
				'<li>' . sprintf( __( 'In order to see title highlights from Relevanssi, replace %1$s in the search results template with %2$s. It does the same thing, but supports Relevanssi title highlights.', 'relevanssi' ), '<code>the_title()</code>', '<code>relevanssi_the_title()</code>' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-punctuation',
			'title'   => __( 'Punctuation', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( 'Relevanssi removes punctuation. Some punctuation is removed, some replaced with spaces. Advanced indexing settings include some of the more common settings people want to change.', 'relevanssi' ) . '</li>' .
				// Translators: %1$s is 'relevanssi_punctuation_filter', %2$s is 'relevanssi_remove_punctuation'.
				'<li>' . sprintf( __( 'For more fine-tuned changes, you can use %1$s filter hook to adjust what is replaced with what, and %2$s filter hook to completely override the default punctuation control.', 'relevanssi' ), '<code>relevanssi_punctuation_filter</code>', '<code>relevanssi_remove_punctuation</code>' ) . '</li>' .
				// Translators: %s is the URL to the Knowledge Base entry.
				'<li>' . sprintf( __( "For more examples, see <a href='%s'>the related knowledge base posts</a>.", 'relevanssi' ), 'https://www.relevanssi.com/tag/relevanssi_remove_punct/' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-helpful-shortcodes',
			'title'   => __( 'Helpful shortcodes', 'relevanssi' ),
			'content' => '<ul>' .
				// Translators: %s is '[noindex]'.
				'<li>' . sprintf( __( "If you have content that you don't want indexed, you can wrap that content in a %s shortcode.", 'relevanssi' ), '<code>[noindex]</code>' ) . '</li>' .
				// Translators: %s is '[searchform]'.
				'<li>' . sprintf( __( 'If you need a search form on some page on your site, you can use the %s shortcode to print out a basic search form.', 'relevanssi' ), '<code>[searchform]</code>' ) . '</li>' .
				// Translators: %1$s is '[searchform post_types="page"]', %2$s is '[searchform cats="10,14,17"]'.
				'<li>' . sprintf( __( 'If you need to add query variables to the search form, the shortcode takes parameters, which are then printed out as hidden input fields. To get a search form with a post type restriction, you can use %1$s. To restrict the search to categories 10, 14 and 17, you can use %2$s and so on.', 'relevanssi' ), '<code>[searchform post_types="page"]</code>', '<code>[searchform cats="10,14,17"]</code>' ) . '</li>' .
				// Translators: %1$s is 'dropdown', %2$s is '[searchform dropdown="category"]'.
				'<li>' . sprintf( __( 'You can use the %1$s parameter to add a taxonomy dropdown to the search form. Just use the name of the taxonomy, like %2$s. This works best with hierarchical taxonomies like categories with relatively few options available.', 'relevanssi' ), '<code>dropdown</code>', '<code>[searchform dropdown="category"]</code>' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-title-woocommerce',
			'title'   => __( 'WooCommerce', 'relevanssi' ),
			'content' => '<ul>' .
				'<li>' . __( "If your SKUs include hyphens or other punctuation, do note that Relevanssi replaces most punctuation with spaces. That's going to cause issues with SKU searches.", 'relevanssi' ) . '</li>' .
				// Translators: %s is the Knowledge Base URL.
				'<li>' . sprintf( __( "For more details how to fix that issue, see <a href='%s'>WooCommerce tips in Relevanssi user manual</a>.", 'relevanssi' ), 'https://www.relevanssi.com/user-manual/woocommerce/' ) . '</li>' .
				'<li>' . __( "If you don't want to index products that are out of stock, excluded from the catalog or excluded from the search, there's a product visibility filtering method that is described in the user manual (see link above).", 'relevanssi' ) . '</li>' .
				'</ul>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'relevanssi-exact-match',
			'title'   => __( 'Exact match bonus', 'relevanssi' ),
			'content' => '<ul>' .
				// Translators: %s is the name of the filter hook.
				'<li>' . sprintf( __( 'To adjust the amount of the exact match bonus, you can use the %s filter hook. It works like this:', 'relevanssi' ), '<code>relevanssi_exact_match_bonus</code>' ) .
				"<pre>add_filter( 'relevanssi_exact_match_bonus', 'rlv_adjust_bonus' );
	function rlv_adjust_bonus( \$bonus ) {
		return array( 'title' => 10, 'content' => 5 );
	}</li>" .
				// Translators: %1$s is the title weight and %2$s is the content weight.
				'<li>' . sprintf( esc_html__( 'The default values are %1$s for titles and %2$s for content.', 'relevanssi' ), '<code>5</code>', '<code>2</code>' ) . '</ul>',
		)
	);
	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'relevanssi' ) . '</strong></p>' .
		'<p><a href="https://www.relevanssi.com/knowledge-base/" target="_blank">' . __( 'Plugin knowledge base', 'relevanssi' ) . '</a></p>' .
		'<p><a href="https://wordpress.org/tags/relevanssi?forum_id=10" target="_blank">' . __( 'WordPress.org forum', 'relevanssi' ) . '</a></p>'
	);
}
