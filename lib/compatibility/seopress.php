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
