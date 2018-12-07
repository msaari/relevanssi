<?php
/**
 * /lib/compatibility/woocommerce.php
 *
 * WooCommerce compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_indexing_restriction', 'relevanssi_woocommerce_restriction' );

/**
 * Applies the WooCommerce product visibility filter.
 *
 * @param string $restriction The restriction clause.
 *
 * @return string The restriction clause with the WC filter added, if necessary.
 */
function relevanssi_woocommerce_restriction( $restriction ) {
	$restriction .= relevanssi_woocommerce_indexing_filter();
	return $restriction;
}

/**
 * WooCommerce product visibility filtering for indexing.
 *
 * This filter is applied before the posts are selected for indexing, so this will
 * skip all the excluded posts right away.
 *
 * @since 4.0.9 (2.1.5)
 * @global $wpdb The WordPress database interface.
 *
 * @return string $restriction The query restriction for the WooCommerce filtering.
 */
function relevanssi_woocommerce_indexing_filter() {
	global $wpdb;

	$restriction        = '';
	$woocommerce_blocks = array(
		'outofstock'           => false,
		'exclude-from-catalog' => false,
		'exclude-from-search'  => true,
	);
	/**
	 * Controls the WooCommerce product visibility filtering.
	 *
	 * @param array $woocommerce_blocks Has three keys: 'outofstock',
	 * 'exclude-from-catalog' and 'exclude-from-search', matching three different
	 * product visibility settings. If the filter sets some of these to 'true',
	 * those posts will be filtered in the indexing.
	 */
	$woocommerce_blocks     = apply_filters( 'relevanssi_woocommerce_indexing', $woocommerce_blocks );
	$term_taxonomy_id_array = array();
	if ( $woocommerce_blocks['outofstock'] ) {
		$out_of_stock = get_term_by( 'slug', 'outofstock', 'product_visibility', OBJECT );
		if ( $out_of_stock && isset( $out_of_stock->term_taxonomy_id ) ) {
			$term_taxonomy_id_array[] = $out_of_stock->term_taxonomy_id;
		}
	}
	if ( $woocommerce_blocks['exclude-from-catalog'] ) {
		$exclude_from_catalog = get_term_by( 'slug', 'exclude-from-catalog', 'product_visibility', OBJECT );
		if ( $exclude_from_catalog && isset( $exclude_from_catalog->term_taxonomy_id ) ) {
			$term_taxonomy_id_array[] = $exclude_from_catalog->term_taxonomy_id;
		}
	}
	if ( $woocommerce_blocks['exclude-from-search'] ) {
		$exclude_from_search = get_term_by( 'slug', 'exclude-from-search', 'product_visibility', OBJECT );
		if ( $exclude_from_search && isset( $exclude_from_search->term_taxonomy_id ) ) {
			$term_taxonomy_id_array[] = $exclude_from_search->term_taxonomy_id;
		}
	}
	if ( ! empty( $term_taxonomy_id_array ) ) {
		$term_taxonomy_id_string = implode( ',', $term_taxonomy_id_array );
		$restriction            .= " AND post.ID NOT IN (SELECT object_id FROM $wpdb->term_relationships WHERE object_id = post.ID AND term_taxonomy_id IN ($term_taxonomy_id_string)) ";
	}
	return $restriction;
}
