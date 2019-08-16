<?php
/**
 * /lib/compatibility/yoast-seo.php
 *
 * Yoast SEO noindex filtering function.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_do_not_index', 'relevanssi_yoast_noindex', 10, 2 );

/**
 * Blocks indexing of posts marked "noindex" in the Yoast SEO settings.
 *
 * Attaches to the 'relevanssi_do_not_index' filter hook.
 *
 * @param boolean $do_not_index True, if the post shouldn't be indexed.
 * @param integer $post_id      The post ID number.
 *
 * @return boolean True, if the post shouldn't be indexed.
 */
function relevanssi_yoast_noindex( $do_not_index, $post_id ) {
	$noindex = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
	if ( '1' === $noindex ) {
		$do_not_index = true;
	}
	return $do_not_index;
}
