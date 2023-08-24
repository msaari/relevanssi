<?php
/**
 * /lib/compatibility/product-gtin-ean-upc-isbn-for-woocommerce.php.php
 *
 * Adds Product GTIN (EAN, UPC, ISBN) for WooCommerce support for Relevanssi.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'pre_option_wpm_pgw_search_by_code', 'relevanssi_disable_gtin_code' );
add_filter( 'relevanssi_index_custom_fields', 'relevanssi_add_wpm_gtin_code' );
add_filter( 'option_relevanssi_index_fields', 'relevanssi_wpm_pgw_fix_none_setting' );

/**
 * Disables the 'wpm_pgw_search_by_code' option.
 *
 * If this option is enabled, it will break Relevanssi search when there's a
 * match for the code.
 *
 * @return string 'no'.
 */
function relevanssi_disable_gtin_code() {
	return 'no';
}

/**
 * Adds the `_wpm_gtin_code` to the list of indexed custom fields.
 *
 * @param array|boolean $fields An array of custom fields to index, or false.
 *
 * @return array An array of custom fields, including `_wpm_gtin_code`.
 */
function relevanssi_add_wpm_gtin_code( $fields ) {
	if ( ! is_array( $fields ) ) {
		$fields = array();
	}
	if ( ! in_array( '_wpm_gtin_code', $fields, true ) ) {
		$fields[] = '_wpm_gtin_code';
	}
	return $fields;
}

/**
 * Makes sure the GTIN code is included in the index, even when the custom field
 * setting is set to 'none'.
 *
 * @param string $value The custom field indexing setting value. The parameter
 * is ignored, Relevanssi disables this filter and then checks the option to
 * see what the value is.
 *
 * @return string If value is undefined, it's set to '_wpm_gtin_code'.
 */
function relevanssi_wpm_pgw_fix_none_setting( $value ) {
	if ( ! $value ) {
		$value = '_wpm_gtin_code';
	}

	return $value;
}
