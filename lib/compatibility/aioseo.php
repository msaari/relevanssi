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
add_action( 'relevanssi_advanced_indexing_config', 'relevanssi_aioseo_form', 20 );
add_action( 'relevanssi_advanced_indexing_sidebar_list', 'relevanssi_aioseo_sidebar', 20 );
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
 * Adds the config element for the All-in-one SEO setting.
 *
 * @param array $config The configuration array.
 *
 * @return array
 */
function relevanssi_aioseo_form( array $config ) {
	$config['relevanssi_aioseo'] = array(
		'type'         => 'checkbox',
		'label'        => __( 'All-in-One SEO', 'relevanssi' ),
		'description'  => __( 'Use All-in-One SEO noindex', 'relevanssi' ),
		'hover_target' => 'sb-aioseo',
		'value'        => get_option( 'relevanssi_seo_noindex' ),
		'advanced'     => true,
	);

	return $config;
}

/**
 * Adds the sidebar note for the SEO Framework setting.
 */
function relevanssi_aioseo_sidebar() {
	?>
	<li id="sb-seo-framework">
		<strong><?php esc_html_e( 'All-in-One SEO:', 'relevanssi' ); ?></strong>
		<?php esc_html_e( 'If checked, Relevanssi will not index posts marked as "No index" in All-in-One SEO settings.', 'relevanssi' ); ?>
	</li>
	<?php
}

/**
 * Saves the SEO No index option.
 *
 * @param array $request An array of option values from the request.
 */
function relevanssi_aioseo_options( array $request ) {
	$request['relevanssi_seo_noindex'] = $request['relevanssi_aioseo'] ?? false;
	relevanssi_update_off_or_on( $request, 'relevanssi_seo_noindex', true );
}
