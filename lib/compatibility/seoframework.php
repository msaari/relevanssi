<?php
/**
 * /lib/compatibility/seoframework.php
 *
 * The SEO Framework noindex filtering function.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_do_not_index', 'relevanssi_seoframework_noindex', 10, 2 );
add_filter( 'relevanssi_indexing_restriction', 'relevanssi_seoframework_exclude' );
add_action( 'relevanssi_advanced_indexing_config', 'relevanssi_seoframework_form', 20 );
add_action( 'relevanssi_advanced_indexing_sidebar_list', 'relevanssi_seoframework_sidebar', 20 );
add_action( 'relevanssi_indexing_options', 'relevanssi_seoframework_options' );

/**
 * Blocks indexing of posts marked "Exclude this page from all search queries
 * on this site." in the SEO Framework settings.
 *
 * Attaches to the 'relevanssi_do_not_index' filter hook.
 *
 * @param boolean $do_not_index True, if the post shouldn't be indexed.
 * @param integer $post_id      The post ID number.
 *
 * @return string|boolean If the post shouldn't be indexed, this returns
 * 'SEO Framework'. The value may also be a boolean.
 */
function relevanssi_seoframework_noindex( $do_not_index, $post_id ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $do_not_index;
	}

	$noindex = get_post_meta( $post_id, 'exclude_local_search', true );
	if ( '1' === $noindex ) {
		$do_not_index = 'SEO Framework';
	}
	return $do_not_index;
}

/**
 * Excludes the "noindex" posts from Relevanssi indexing.
 *
 * Adds a MySQL query restriction that blocks posts that have the SEO Framework
 * "Exclude this page from all search queries on this site" setting set to "1"
 * from indexing.
 *
 * @param array $restriction An array with two values: 'mysql' for the MySQL
 * query restriction to modify, 'reason' for the reason of restriction.
 */
function relevanssi_seoframework_exclude( $restriction ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $restriction;
	}

	global $wpdb;

	$restriction['mysql']  .= " AND post.ID NOT IN (SELECT post_id FROM
		$wpdb->postmeta WHERE meta_key = 'exclude_local_search'
		AND meta_value = '1' ) ";
	$restriction['reason'] .= ' SEO Framework';
	return $restriction;
}

/**
 * Adds the config element for the SEO Framework setting.
 *
 * @param array $config The configuration array.
 *
 * @return array
 */
function relevanssi_seoframework_form( array $config ) {
	$config['relevanssi_seo_framework'] = array(
		'type'         => 'checkbox',
		'label'        => __( 'SEO Framework', 'relevanssi' ),
		'description'  => __( 'Use SEO Framework local search exclude', 'relevanssi' ),
		'hover_target' => 'sb-seo-framework',
		'value'        => get_option( 'relevanssi_seo_noindex' ),
		'advanced'     => true,
	);

	return $config;
}

/**
 * Adds the sidebar note for the SEO Framework setting.
 */
function relevanssi_seoframework_sidebar() {
	?>
	<li id="sb-seo-framework">
		<strong><?php esc_html_e( 'SEO Framework:', 'relevanssi' ); ?></strong>
		<?php esc_html_e( 'If checked, Relevanssi will not index posts marked as "Exclude this page from all search queries on this site" in SEO Framework settings.', 'relevanssi' ); ?>
	</li>
	<?php
}

/**
 * Saves the SEO No index option.
 *
 * @param array $request An array of option values from the request.
 */
function relevanssi_seoframework_options( array $request ) {
	$request['relevanssi_seo_noindex'] = $request['relevanssi_seo_framework'] ?? false;
	relevanssi_update_off_or_on( $request, 'relevanssi_seo_noindex', true );
}
