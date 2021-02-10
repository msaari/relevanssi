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
add_filter( 'relevanssi_indexing_restriction', 'relevanssi_yoast_exclude' );
add_action( 'relevanssi_indexing_tab_advanced', 'relevanssi_yoast_form', 20 );
add_action( 'relevanssi_indexing_options', 'relevanssi_yoast_options' );

/**
 * Blocks indexing of posts marked "noindex" in the Yoast SEO settings.
 *
 * Attaches to the 'relevanssi_do_not_index' filter hook.
 *
 * @param boolean $do_not_index True, if the post shouldn't be indexed.
 * @param integer $post_id      The post ID number.
 *
 * @return string|boolean If the post shouldn't be indexed, this returns
 * 'yoast_seo'. The value may also be a boolean.
 */
function relevanssi_yoast_noindex( $do_not_index, $post_id ) {
	if ( 'on' !== get_option( 'relevanssi_seo_noindex' ) ) {
		return $do_not_index;
	}

	$noindex = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
	if ( '1' === $noindex ) {
		$do_not_index = 'Yoast SEO';
	}
	return $do_not_index;
}

/**
 * Excludes the "noindex" posts from Relevanssi indexing.
 *
 * Adds a MySQL query restriction that blocks posts that have the Yoast SEO
 * "noindex" setting set to "1" from indexing.
 *
 * @param array $restriction An array with two values: 'mysql' for the MySQL
 * query restriction to modify, 'reason' for the reason of restriction.
 */
function relevanssi_yoast_exclude( $restriction ) {
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
		$wpdb->postmeta WHERE meta_key = '_yoast_wpseo_meta-robots-noindex'
		AND meta_value = '1' ) ";
	$restriction['reason'] .= ' Yoast SEO';
	return $restriction;
}

/**
 * Prints out the form fields for disabling the feature.
 */
function relevanssi_yoast_form() {
	$seo_noindex = get_option( 'relevanssi_seo_noindex' );
	$seo_noindex = relevanssi_check( $seo_noindex );

	?>
	<tr>
		<th scope="row">
			<label for='relevanssi_seo_noindex'><?php esc_html_e( 'Use Yoast SEO noindex', 'relevanssi' ); ?></label>
		</th>
		<td>
			<label for='relevanssi_seo_noindex'>
				<input type='checkbox' name='relevanssi_seo_noindex' id='relevanssi_seo_noindex' <?php echo esc_attr( $seo_noindex ); ?> />
				<?php esc_html_e( 'Use Yoast SEO noindex.', 'relevanssi' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'If checked, Relevanssi will not index posts marked as "No index" in Yoast SEO settings.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Saves the SEO No index option.
 *
 * @param array $request An array of option values from the request.
 */
function relevanssi_yoast_options( array $request ) {
	relevanssi_update_off_or_on( $request, 'relevanssi_seo_noindex', true );
}
