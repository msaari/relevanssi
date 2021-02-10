<?php
/**
 * /lib/compatibility/aioseo.php
 *
 * All-in-One SEO noindex filtering function.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_do_not_index', 'relevanssi_aioseo_noindex', 10, 2 );
add_filter( 'relevanssi_indexing_restriction', 'relevanssi_aioseo_exclude' );
add_action( 'relevanssi_indexing_tab_advanced', 'relevanssi_aioseo_form', 20 );
add_action( 'relevanssi_indexing_options', 'relevanssi_aioseo_options' );

/**
 * Blocks indexing of posts marked "noindex" in the All-in-One SEO settings.
 *
 * Attaches to the 'relevanssi_do_not_index' filter hook.
 *
 * @param boolean $do_not_index True, if the post shouldn't be indexed.
 * @param integer $post_id      The post ID number.
 *
 * @return string|boolean If the post shouldn't be indexed, this returns
 * 'aioseo_seo'. The value may also be a boolean.
 */
function relevanssi_aioseo_noindex( bool $do_not_index, int $post_id ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $do_not_index;
	}
	$noindex_posts = relevanssi_aioseo_get_noindex_posts();
	if ( in_array( $post_id, $noindex_posts, true ) ) {
		$do_not_index = 'All-in-One SEO';
	}
	return $do_not_index;
}

/**
 * Excludes the "noindex" posts from Relevanssi indexing.
 *
 * Adds a MySQL query restriction that blocks posts that have the aioseo SEO
 * "noindex" setting set to "1" from indexing.
 *
 * @param array $restriction An array with two values: 'mysql' for the MySQL
 * query restriction to modify, 'reason' for the reason of restriction.
 */
function relevanssi_aioseo_exclude( array $restriction ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $restriction;
	}

	global $wpdb;

	$restriction['mysql']  .= " AND post.ID NOT IN (SELECT post_id FROM
		{$wpdb->prefix}aioseo_posts WHERE robots_noindex = '1' ) ";
	$restriction['reason'] .= ' All-in-One SEO';

	return $restriction;
}

/**
 * Fetches the post IDs where robots_noindex is set to 1 in the aioseo_posts
 * table.
 *
 * @return array An array of post IDs.
 */
function relevanssi_aioseo_get_noindex_posts() {
	global $wpdb, $relevanssi_aioseo_noindex_cache;
	if ( ! empty( $relevanssi_aioseo_noindex_cache ) ) {
		return $relevanssi_aioseo_noindex_cache;
	}
	$relevanssi_aioseo_noindex_cache = $wpdb->get_col( "SELECT post_id FROM {$wpdb->prefix}aioseo_posts WHERE 'robots_noindex' = '1'" );
	return $relevanssi_aioseo_noindex_cache;
}

/**
 * Prints out the form fields for disabling the feature.
 */
function relevanssi_aioseo_form() {
	$seo_noindex = get_option( 'relevanssi_seo_noindex' );
	$seo_noindex = relevanssi_check( $seo_noindex );

	?>
	<tr>
		<th scope="row">
			<label for='relevanssi_seo_noindex'><?php esc_html_e( 'Use All-in-One SEO noindex', 'relevanssi' ); ?></label>
		</th>
		<td>
			<label for='relevanssi_seo_noindex'>
				<input type='checkbox' name='relevanssi_seo_noindex' id='relevanssi_seo_noindex' <?php echo esc_attr( $seo_noindex ); ?> />
				<?php esc_html_e( 'Use All-in-One SEO noindex.', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'If checked, Relevanssi will not index posts marked as "No index" in All-in-One SEO settings.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Saves the SEO No index option.
 *
 * @param array $request An array of option values from the request.
 */
function relevanssi_aioseo_options( array $request ) {
	relevanssi_update_off_or_on( $request, 'relevanssi_seo_noindex', true );
}
