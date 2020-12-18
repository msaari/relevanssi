<?php
/**
 * /lib/compatibility/rankmath.php
 *
 * Rank Math noindex filtering function.
 *
 * @package Relevanssi
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_do_not_index', 'relevanssi_rankmath_noindex', 10, 2 );
add_filter( 'relevanssi_indexing_restriction', 'relevanssi_rankmath_exclude' );

/**
 * Blocks indexing of posts marked "noindex" in the Rank Math settings.
 *
 * Attaches to the 'relevanssi_do_not_index' filter hook.
 *
 * @param boolean $do_not_index True, if the post shouldn't be indexed.
 * @param integer $post_id      The post ID number.
 *
 * @return string|boolean If the post shouldn't be indexed, this returns
 * 'RankMath'. The value may also be a boolean.
 */
function relevanssi_rankmath_noindex( $do_not_index, $post_id ) {
	$noindex = get_post_meta( $post_id, 'rank_math_robots', true );
	if ( is_array( $noindex ) && in_array( 'noindex', $noindex, true ) ) {
		$do_not_index = 'RankMath';
	}
	return $do_not_index;
}

/**
 * Excludes the "noindex" posts from Relevanssi indexing.
 *
 * Adds a MySQL query restriction that blocks posts that have the Rank Math
 * "rank_math_robots" setting set to something that includes "noindex".
 *
 * @param array $restriction An array with two values: 'mysql' for the MySQL
 * query restriction to modify, 'reason' for the reason of restriction.
 */
function relevanssi_rankmath_exclude( $restriction ) {
	global $wpdb;

	// Backwards compatibility code for 2.8.0, remove at some point.
	if ( is_string( $restriction ) ) {
		$restriction = array(
			'mysql'  => $restriction,
			'reason' => '',
		);
	}

	$restriction['mysql']  .= " AND post.ID NOT IN (SELECT post_id FROM
		$wpdb->postmeta WHERE meta_key = 'rank_math_robots'
		AND meta_value LIKE '%noindex%' ) ";
	$restriction['reason'] .= ' Rank Math';
	return $restriction;
}
