<?php
/**
 * /lib/compatibility/slimseo.php
 *
 * The Slim SEO noindex filtering function.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_do_not_index', 'relevanssi_slimseo_noindex', 10, 2 );
add_filter( 'relevanssi_indexing_restriction', 'relevanssi_slimseo_exclude' );
add_action( 'relevanssi_advanced_indexing_config', 'relevanssi_slimseo_form', 20 );
add_action( 'relevanssi_advanced_indexing_sidebar_list', 'relevanssi_slimseo_sidebar', 20 );
add_action( 'relevanssi_indexing_options', 'relevanssi_slimseo_options' );

/**
 * Blocks indexing of posts marked "Hide from search results" in Slim SEO
 * settings.
 *
 * Attaches to the 'relevanssi_do_not_index' filter hook.
 *
 * @param boolean $do_not_index True, if the post shouldn't be indexed.
 * @param integer $post_id      The post ID number.
 *
 * @return string|boolean If the post shouldn't be indexed, this returns
 * 'Slim SEO'. The value may also be a boolean.
 */
function relevanssi_slimseo_noindex( $do_not_index, $post_id ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $do_not_index;
	}

	$slim_seo_settings = get_post_meta( $post_id, 'slim_seo', true );
	if ( isset( $slim_seo_settings['noindex'] ) && 1 === $slim_seo_settings['noindex'] ) {
		$do_not_index = 'Slim SEO';
	}
	return $do_not_index;
}

/**
 * Excludes the "noindex" posts from Relevanssi indexing.
 *
 * Adds a MySQL query restriction that blocks posts that have the Slim SEO
 * "Hide from search results" setting set to "1" from indexing.
 *
 * @param array $restriction An array with two values: 'mysql' for the MySQL
 * query restriction to modify, 'reason' for the reason of restriction.
 */
function relevanssi_slimseo_exclude( $restriction ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $restriction;
	}

	global $wpdb;

	$restriction['mysql']  .= " AND post.ID NOT IN (SELECT post_id FROM
		$wpdb->postmeta WHERE meta_key = 'exclude_local_search'
		AND meta_value LIKE '%s:7:\"noindex\";i:1%' ) ";
	$restriction['reason'] .= ' Slim SEO';
	return $restriction;
}

/**
 * Adds the config element for the Slim SEO setting.
 *
 * @param array $config The configuration array.
 *
 * @return array
 */
function relevanssi_slimseo_form( array $config ) {
	$config['relevanssi_slim_seo'] = array(
		'type'         => 'checkbox',
		'label'        => __( 'Slim SEO', 'relevanssi' ),
		'description'  => __( 'Use Slim SEO search exclude', 'relevanssi' ),
		'hover_target' => 'sb-seo-framework',
		'value'        => get_option( 'relevanssi_seo_noindex' ),
		'advanced'     => true,
	);

	return $config;
}

/**
 * Adds the sidebar note for the Slim SEO setting.
 */
function relevanssi_slimseo_sidebar() {
	?>
	<li id="sb-seo-framework">
		<strong><?php esc_html_e( 'Slim SEO:', 'relevanssi' ); ?></strong>
		<?php esc_html_e( 'If checked, Relevanssi will not index posts marked as "Hide from search results" in Slim SEO settings.', 'relevanssi' ); ?>
	</li>
	<?php
}

/**
 * Saves the SEO No index option.
 *
 * @param array $request An array of option values from the request.
 */
function relevanssi_slimseo_options( array $request ) {
	$request['relevanssi_seo_noindex'] = $request['relevanssi_slim_seo'] ?? false;
	relevanssi_update_off_or_on( $request, 'relevanssi_seo_noindex', true );
}
