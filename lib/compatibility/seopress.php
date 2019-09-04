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
 * @return boolean True, if the post shouldn't be indexed.
 */
function relevanssi_seopress_noindex( $do_not_index, $post_id ) {
	$noindex = get_post_meta( $post_id, '_seopress_robots_index', true );
	if ( 'yes' === $noindex ) {
		$do_not_index = true;
	}
	return $do_not_index;
}

/**
 * Excludes the "noindex" posts from Relevanssi indexing.
 *
 * Adds a MySQL query restriction that blocks posts that have the SEOPress
 * "noindex" setting set to "1" from indexing.
 *
 * @param string $restriction The MySQL query restriction to modify.
 */
function relevanssi_seopress_exclude( $restriction ) {
	global $wpdb;
	$restriction .= " AND post.ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_seopress_robots_index' AND meta_value = 'yes' ) ";
	return $restriction;
}
