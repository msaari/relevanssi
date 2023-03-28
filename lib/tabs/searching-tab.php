<?php
/**
 * /lib/tabs/searching-tab.php
 *
 * Prints out the Searching tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the searching tab in Relevanssi settings.
 *
 * @global $wpdb                 The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables array.
 */
function relevanssi_searching_tab() {
	global $wpdb, $relevanssi_variables;

	$implicit            = get_option( 'relevanssi_implicit_operator' );
	$orderby             = get_option( 'relevanssi_default_orderby' );
	$fuzzy               = get_option( 'relevanssi_fuzzy' );
	$content_boost       = get_option( 'relevanssi_content_boost' );
	$title_boost         = get_option( 'relevanssi_title_boost' );
	$comment_boost       = get_option( 'relevanssi_comment_boost' );
	$exact_match_bonus   = get_option( 'relevanssi_exact_match_bonus' );
	$wpml_only_current   = get_option( 'relevanssi_wpml_only_current' );
	$polylang_allow_all  = get_option( 'relevanssi_polylang_all_languages' );
	$admin_search        = get_option( 'relevanssi_admin_search' );
	$disable_or_fallback = get_option( 'relevanssi_disable_or_fallback' );
	$throttle            = get_option( 'relevanssi_throttle' );
	$respect_exclude     = get_option( 'relevanssi_respect_exclude' );
	$cat                 = get_option( 'relevanssi_cat' );
	$excat               = get_option( 'relevanssi_excat' );
	$exclude_posts       = get_option( 'relevanssi_exclude_posts' );
	$index_post_types    = get_option( 'relevanssi_index_post_types', array() );
	$index_users         = get_option( 'relevanssi_index_users' );
	$index_terms         = get_option( 'relevanssi_index_taxonomies' );

	$throttle            = relevanssi_check( $throttle );
	$respect_exclude     = relevanssi_check( $respect_exclude );
	$admin_search        = relevanssi_check( $admin_search );
	$wpml_only_current   = relevanssi_check( $wpml_only_current );
	$polylang_allow_all  = relevanssi_check( $polylang_allow_all );
	$exact_match_bonus   = relevanssi_check( $exact_match_bonus );
	$disable_or_fallback = relevanssi_check( $disable_or_fallback );
	$implicit_and        = relevanssi_select( $implicit, 'AND' );
	$implicit_or         = relevanssi_select( $implicit, 'OR' );
	$orderby_relevance   = relevanssi_select( $orderby, 'relevance' );
	$orderby_date        = relevanssi_select( $orderby, 'post_date' );
	$fuzzy_sometimes     = relevanssi_select( $fuzzy, 'sometimes' );
	$fuzzy_always        = relevanssi_select( $fuzzy, 'always' );
	$fuzzy_never         = relevanssi_select( $fuzzy, 'never' );

	$orfallback_visibility = 'screen-reader-text';
	if ( 'AND' === $implicit ) {
		$orfallback_visibility = '';
	}

	if ( ! $throttle ) {
		$docs_count = get_transient( 'relevanssi_docs_count' );
		if ( ! $docs_count ) {
			$docs_count = $wpdb->get_var( 'SELECT COUNT(DISTINCT doc) FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE doc != -1' );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			set_transient( 'relevanssi_docs_count', $docs_count, WEEK_IN_SECONDS );
		}
	} else {
		$docs_count = null;
	}
	?>

	<table class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<label for='relevanssi_implicit_operator'><?php esc_html_e( 'Default operator', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_implicit_operator' id='relevanssi_implicit_operator'>
				<option value='AND' <?php echo esc_html( $implicit_and ); ?>><?php esc_html_e( 'AND - require all terms', 'relevanssi' ); ?></option>
				<option value='OR' <?php echo esc_html( $implicit_or ); ?>><?php esc_html_e( 'OR - any term present is enough', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'This setting determines the default operator for the search.', 'relevanssi' ); ?></p>
	<?php
	if ( RELEVANSSI_PREMIUM ) {
		// Translators: %1$s is the name of the 'operator' query variable, %2$s is an example url.
		echo "<p class='description'>" . sprintf( esc_html__( 'You can override this setting with the %1$s query parameter, like this: %2$s', 'relevanssi' ), '<code>operator</code>', 'http://www.example.com/?s=term&amp;operator=or' ) . '</p>';
	}
	?>
		</td>
	</tr>
	<tr id="orfallback" class='<?php echo esc_attr( $orfallback_visibility ); ?>'>
		<th scope="row">
			<?php esc_html_e( 'Fallback to OR', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Disable the OR fallback.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_disable_or_fallback'>
				<input type='checkbox' name='relevanssi_disable_or_fallback' id='relevanssi_disable_or_fallback' <?php echo esc_html( $disable_or_fallback ); ?> />
				<?php esc_html_e( 'Disable the OR fallback.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'By default, if AND search fails to find any results, Relevanssi will switch the operator to OR and run the search again. You can prevent that by checking this option.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_default_orderby'><?php esc_html_e( 'Default order', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_default_orderby' id='relevanssi_default_orderby'>
				<option value='relevance' <?php echo esc_html( $orderby_relevance ); ?>><?php esc_html_e( 'Relevance (highly recommended)', 'relevanssi' ); ?></option>
				<option value='post_date' <?php echo esc_html( $orderby_date ); ?>><?php esc_html_e( 'Post date', 'relevanssi' ); ?></option>
			</select>
			<?php // Translators: name of the query variable. ?>
			<p class="description"><?php printf( esc_html__( 'If you want to override this or use multi-layered ordering (eg. first order by relevance, but sort ties by post title), you can use the %s query variable. See Help for more information.', 'relevanssi' ), '<code>orderby</code>' ); ?></p>
			<?php if ( RELEVANSSI_PREMIUM ) { ?>
			<p class="description"><?php esc_html_e( 'If you want date-based results, see the recent post bonus in the Weights section.', 'relevanssi' ); ?></p>
			<?php } // End if ( RELEVANSSI_PREMIUM ). ?>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_fuzzy'><?php esc_html_e( 'Keyword matching', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_fuzzy' id='relevanssi_fuzzy'>
				<option value='never' <?php echo esc_html( $fuzzy_never ); ?>><?php esc_html_e( 'Whole words', 'relevanssi' ); ?></option>
				<option value='always' <?php echo esc_html( $fuzzy_always ); ?>><?php esc_html_e( 'Partial words', 'relevanssi' ); ?></option>
				<option value='sometimes' <?php echo esc_html( $fuzzy_sometimes ); ?>><?php esc_html_e( 'Partial words if no hits for whole words', 'relevanssi' ); ?></option>
			</select>
			<?php if ( $fuzzy_sometimes ) : ?>
				<?php // Translators: %1$s is the "partial words if no hits" option and %2$s is the "partial words" option. ?>
				<p class="description important"><?php printf( esc_html__( 'Choosing the "%1$s" option may lead to unexpected results. Most of the time the "%2$s" option is the better choice.', 'relevanssi' ), esc_html__( 'Partial words if not hits for whole words', 'relevanssi' ), esc_html__( 'Partial words', 'relevanssi' ) ); ?></p>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Whole words means Relevanssi only finds posts that include the whole search term.', 'relevanssi' ); ?></p>
			<p class="description"><?php esc_html_e( "Partial words also includes cases where the word in the index begins or ends with the search term (searching for 'ana' will match 'anaconda' or 'banana', but not 'banal'). See Help, if you want to make Relevanssi match also inside words.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Weights', 'relevanssi' ); ?>
		</th>
		<td>
			<p class="description"><?php esc_html_e( 'All the weights in the table are multipliers. To increase the weight of an element, use a higher number. To make an element less significant, use a number lower than 1.', 'relevanssi' ); ?></p>
			<table class="relevanssi-weights-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Element', 'relevanssi' ); ?></th>
					<th class="col-2"><?php esc_html_e( 'Weight', 'relevanssi' ); ?></th>
				</tr>
			</thead>
			<tr>
				<td>
					<label for="relevanssi_content_boost"><?php esc_html_e( 'Content', 'relevanssi' ); ?></label>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_content_boost' id='relevanssi_content_boost' size='4' value='<?php echo esc_attr( $content_boost ); ?>' />
				</td>
			</tr>
			<tr>
				<td>
					<label for="relevanssi_title_boost"><?php esc_html_e( 'Titles', 'relevanssi' ); ?></label>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_title_boost' id='relevanssi_title_boost' size='4' value='<?php echo esc_attr( $title_boost ); ?>' />
				</td>
			</tr>
			<?php
			if ( function_exists( 'relevanssi_form_link_weight' ) ) {
				relevanssi_form_link_weight();
			}
			?>
			<tr>
				<td>
					<label for="relevanssi_comment_boost"><?php esc_html_e( 'Comment text', 'relevanssi' ); ?></label>
				</td>
				<td class="col-2">
					<input type='text' name='relevanssi_comment_boost' id='relevanssi_comment_boost' size='4' value='<?php echo esc_attr( $comment_boost ); ?>' />
				</td>
			</tr>
			<?php
			if ( function_exists( 'relevanssi_form_post_type_weights' ) ) {
				relevanssi_form_post_type_weights();
			}
			if ( function_exists( 'relevanssi_form_taxonomy_weights' ) ) {
				relevanssi_form_taxonomy_weights();
			} elseif ( function_exists( 'relevanssi_form_tag_weight' ) ) {
				relevanssi_form_tag_weight();
			}
			if ( function_exists( 'relevanssi_form_recency_weight' ) ) {
				relevanssi_form_recency_weight();
			}
			?>
			</table>
		</td>
	</tr>
	<?php
	if ( function_exists( 'relevanssi_form_recency_cutoff' ) ) {
		relevanssi_form_recency_cutoff();
	}
	?>
	<tr>
		<th scope="row">
		<?php esc_html_e( 'Boost exact matches', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Give boost to exact matches.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_exact_match_bonus'>
				<input type='checkbox' name='relevanssi_exact_match_bonus' id='relevanssi_exact_match_bonus' <?php echo esc_html( $exact_match_bonus ); ?> />
				<?php esc_html_e( 'Give boost to exact matches.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<?php // Translators: %s is the name of the filter hook. ?>
		<p class="description"><?php printf( esc_html__( 'If you enable this option, matches where the search query appears in title or content as a phrase will get a weight boost. To adjust the boost, you can use the %s filter hook. See Help for more details.', 'relevanssi' ), '<code>relevanssi_exact_match_bonus</code>' ); ?></p>
		</td>
	</tr>
	<?php
	if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_get_post' ) ) {
		?>
	<tr>
		<th scope="row">
		<?php esc_html_e( 'WPML', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Limit results to current language.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_wpml_only_current'>
				<input type='checkbox' name='relevanssi_wpml_only_current' id='relevanssi_wpml_only_current' <?php echo esc_html( $wpml_only_current ); ?> />
				<?php esc_html_e( 'Limit results to current language.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Enabling this option will restrict the results to the currently active language. If the option is disabled, results will include posts in all languages.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php } // WPML. ?>
	<?php if ( function_exists( 'pll_get_post' ) ) { ?>
	<tr>
		<th scope="row">
		<?php esc_html_e( 'Polylang', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Allow results from all languages.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_polylang_all_languages'>
				<input type='checkbox' name='relevanssi_polylang_all_languages' id='relevanssi_polylang_all_languages' <?php echo esc_html( $polylang_allow_all ); ?> />
				<?php esc_html_e( 'Allow results from all languages.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'By default Polylang restricts the search to the current language. Enabling this option will lift this restriction.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php } // Polylang. ?>
	<tr>
		<th scope="row">
		<?php esc_html_e( 'Admin search', 'relevanssi' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Use Relevanssi for admin searches.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_admin_search'>
				<input type='checkbox' name='relevanssi_admin_search' id='relevanssi_admin_search' <?php echo esc_html( $admin_search ); ?> />
				<?php esc_html_e( 'Use Relevanssi for admin searches.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( "If checked, Relevanssi will be used for searches in the admin interface. The page search doesn't use Relevanssi, because WordPress works like that.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php // Translators: %s is 'exclude_from_search'. ?>
			<?php printf( esc_html__( 'Respect %s', 'relevanssi' ), 'exclude_from_search' ); ?>
		</th>
		<td>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Respect exclude_from_search for custom post types', 'relevanssi' ); ?></legend>
			<label for='relevanssi_respect_exclude'>
				<input type='checkbox' name='relevanssi_respect_exclude' id='relevanssi_respect_exclude' <?php echo esc_html( $respect_exclude ); ?> />
				<?php // Translators: %s is 'exclude_from_search'. ?>
				<?php printf( esc_html__( 'Respect %s for custom post types', 'relevanssi' ), '<code>exclude_from_search</code>' ); ?>
			</label>
			<p class="description"><?php esc_html_e( "If checked, Relevanssi won't display posts of custom post types that have 'exclude_from_search' set to true.", 'relevanssi' ); ?></p>
			<?php
			if ( ! empty( $respect_exclude ) ) {
				$pt_1 = get_post_types( array( 'exclude_from_search' => '1' ) );
				$pt_2 = get_post_types( array( 'exclude_from_search' => true ) );

				$private_types      = array_merge( $pt_1, $pt_2 );
				$problem_post_types = array_intersect( $index_post_types, $private_types );
				if ( ! empty( $problem_post_types ) ) {
					?>
					<p class="description important">
					<?php
					esc_html_e( "You probably should uncheck this option, because you've set Relevanssi to index the following non-public post types:", 'relevanssi' );
					echo ' ' . esc_html( implode( ', ', $problem_post_types ) );
					?>
					</p>
					<?php
				}
			}
			?>
		</fieldset>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Throttle searches', 'relevanssi' ); ?>
		</th>
		<td id="throttlesearches">
		<div id="throttle_enabled">
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Throttle searches.', 'relevanssi' ); ?></legend>
			<label for='relevanssi_throttle'>
				<input type='checkbox' name='relevanssi_throttle' id='relevanssi_throttle' <?php echo esc_html( $throttle ); ?> />
				<?php esc_html_e( 'Throttle searches.', 'relevanssi' ); ?>
			</label>
		</fieldset>
		<?php if ( $docs_count && $docs_count < 1000 ) { ?>
			<p class="description important"><?php esc_html_e( "Your database is so small that you don't need to enable this.", 'relevanssi' ); ?></p>
		<?php } ?>
		<p class="description"><?php esc_html_e( 'If this option is checked, Relevanssi will limit search results to at most 500 results per term. This will improve performance, but may cause some relevant documents to go unfound. See Help for more details.', 'relevanssi' ); ?></p>
		<?php if ( 'post_date' === $orderby && ( 'on' === $index_users || 'on' === $index_terms ) ) { ?>
			<p class="important"><?php esc_html_e( 'You have the default ordering set to post date and have enabled user or taxonomy term indexing. If you enable the throttle, the search results will only include posts. Users and taxonomy terms will be excluded. Either keep the throttle disabled or set the post ordering to relevance.', 'relevanssi' ); ?></p>
		<?php } ?>
		</div>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Category restriction', 'relevanssi' ); ?>
		</th>
		<td>
			<div class="categorydiv" style="max-width: 400px">
			<div class="tabs-panel">
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Category restriction', 'relevanssi' ); ?></legend>
			<ul id="category_inclusion_checklist">
			<?php
				$selected_cats = explode( ',', $cat );
				$walker        = get_relevanssi_taxonomy_walker();
				$walker->name  = 'relevanssi_cat';
				wp_terms_checklist(
					0,
					array(
						'taxonomy'      => 'category',
						'selected_cats' => $selected_cats,
						'walker'        => $walker,
					)
				);
			?>
			</ul>
			</fieldset>
			<input type="hidden" name="relevanssi_cat_active" value="1" />
			</div>
			</div>
			<p class="description"><?php esc_html_e( 'You can restrict search results to a category for all searches. For restricting on a per-search basis and more options (eg. tag restrictions), see Help.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Category exclusion', 'relevanssi' ); ?>
		</th>
		<td>
			<div class="categorydiv" style="max-width: 400px">
			<div class="tabs-panel">
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Category exclusion', 'relevanssi' ); ?></legend>
			<ul id="category_exclusion_checklist">
			<?php
				$selected_cats = explode( ',', $excat );
				$walker        = get_relevanssi_taxonomy_walker();
				$walker->name  = 'relevanssi_excat';
				wp_terms_checklist(
					0,
					array(
						'taxonomy'      => 'category',
						'selected_cats' => $selected_cats,
						'walker'        => $walker,
					)
				);
			?>
			</ul>
			<input type="hidden" name="relevanssi_excat_active" value="1" />
			</fieldset>
			</div>
			</div>
			<p class="description"><?php esc_html_e( 'Posts in these categories are not included in search results. To exclude the posts completely from the index, see Help.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_exclude_posts'><?php esc_html_e( 'Post exclusion', 'relevanssi' ); ?>
		</th>
		<td>
			<input type='text' name='relevanssi_exclude_posts' id='relevanssi_exclude_posts' size='60' value='<?php echo esc_attr( $exclude_posts ); ?>' />
			<p class="description"><?php esc_html_e( "Enter a comma-separated list of post or page ID's to exclude those pages from the search results.", 'relevanssi' ); ?></p>
			<?php if ( RELEVANSSI_PREMIUM ) { ?>
				<p class="description"><?php esc_html_e( "With Relevanssi Premium, it's better to use the check box on post edit pages. That will remove the posts completely from the index, and will work with multisite searches unlike this setting.", 'relevanssi' ); ?></p>
			<?php } ?>
		</td>
	</tr>
	<?php
	if ( function_exists( 'relevanssi_form_searchblogs_setting' ) ) {
		relevanssi_form_searchblogs_setting();
	}
	?>
	</table>

	<?php
}
