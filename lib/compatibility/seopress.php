<?php
/**
 * /lib/compatibility/seopress.php
 *
 * SEOPress noindex filtering function.
 *
 * @package Relevanssi
 * @author  Benjamin Denis
 * @source ./yoast-seo.php (Mikko Saari)
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_do_not_index', 'relevanssi_seopress_noindex', 10, 2 );
add_filter( 'relevanssi_indexing_restriction', 'relevanssi_seopress_exclude' );
add_action( 'relevanssi_advanced_indexing_config', 'relevanssi_seopress_form', 20 );
add_action( 'relevanssi_advanced_indexing_sidebar_list', 'relevanssi_seopress_sidebar', 20 );
add_action( 'relevanssi_indexing_options', 'relevanssi_seopress_options' );

/**
 * Blocks indexing of posts marked "noindex" in the SEOPress settings.
 *
 * Attaches to the 'relevanssi_do_not_index' filter hook.
 *
 * @param boolean $do_not_index True, if the post shouldn't be indexed.
 * @param integer $post_id      The post ID number.
 *
 * @return string|boolean If the post shouldn't be indexed, this returns
 * 'seopress'. The value may also be a boolean.
 */
function relevanssi_seopress_noindex( $do_not_index, $post_id ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $do_not_index;
	}

	$noindex = get_post_meta( $post_id, '_seopress_robots_index', true );
	if ( 'yes' === $noindex ) {
		$do_not_index = 'SEOPress';
	}
	return $do_not_index;
}

/**
 * Excludes the "noindex" posts from Relevanssi indexing.
 *
 * Adds a MySQL query restriction that blocks posts that have the SEOPress
 * "noindex" setting set to "1" from indexing.
 *
 * @param array $restriction An array with two values: 'mysql' for the MySQL
 * query restriction to modify, 'reason' for the reason of restriction.
 */
function relevanssi_seopress_exclude( $restriction ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $restriction;
	}

	global $wpdb;
	// Backwards compatibility code for 2.8.0, remove at some point.
	if ( is_string( $restriction ) ) {
		$restriction = array(
			'mysql'  => $restriction,
			'reason' => '',
		);
	}

	$restriction['mysql']  .= " AND post.ID NOT IN (SELECT post_id FROM
		$wpdb->postmeta WHERE meta_key = '_seopress_robots_index'
		AND meta_value = 'yes' ) ";
	$restriction['reason'] .= 'SEOPress';
	return $restriction;
}

/**
 * Prints out the form fields for disabling the feature.
 *
 * @param array $config The configuration array.
 *
 * @return array
 */
function relevanssi_seopress_form( array $config ) {
	$config['relevanssi_seopress'] = array(
		'type'         => 'checkbox',
		'label'        => __( 'SEOPress', 'relevanssi' ),
		'description'  => __( 'Use SEOPress noindex', 'relevanssi' ),
		'hover_target' => 'sb-seopress',
		'value'        => get_option( 'relevanssi_seo_noindex' ),
		'advanced'     => true,
	);

	return $config;
}


/**
 * Adds the sidebar note for the SEOPress setting.
 */
function relevanssi_seopress_sidebar() {
	?>
	<li id="sb-seo-press">
		<strong><?php esc_html_e( 'SEOPress:', 'relevanssi' ); ?></strong>
		<?php esc_html_e( 'If checked, Relevanssi will not index posts marked as "No index" in SEOPress settings.', 'relevanssi' ); ?>
	</li>
	<?php
}


/**
 * Saves the SEO No index option.
 *
 * @param array $request An array of option values from the request.
 */
function relevanssi_seopress_options( array $request ) {
	$request['relevanssi_seo_noindex'] = $request['relevanssi_seopress'] ?? false;
	relevanssi_update_off_or_on( $request, 'relevanssi_seo_noindex', true );
}
