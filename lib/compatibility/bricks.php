<?php
/**
 * /lib/compatibility/bricks.php
 *
 * Bricks theme compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'bricks/posts/query_vars', 'relevanssi_bricks_enable', 10 );
add_filter( 'relevanssi_custom_field_value', 'relevanssi_bricks_values', 10, 2 );
add_filter( 'relevanssi_index_custom_fields', 'relevanssi_add_bricks' );
add_filter( 'option_relevanssi_index_fields', 'relevanssi_bricks_fix_none_setting' );
add_action( 'save_post', 'relevanssi_insert_edit', 99, 1 );

/**
 * Enables Relevanssi in the query when the 's' query var is set.
 *
 * @param array $query_vars The query variables.
 *
 * @return array The query variables with the Relevanssi toggle enabled.
 */
function relevanssi_bricks_enable( $query_vars ) {
	if ( isset( $query_vars['s'] ) ) {
		$query_vars['relevanssi'] = true;
	}
	return $query_vars;
}

/**
 * Adds the `_bricks_page_content_2` to the list of indexed custom fields.
 *
 * @param array|boolean $fields An array of custom fields to index, or false.
 *
 * @return array An array of custom fields, including `_bricks_page_content_2`.
 */
function relevanssi_add_bricks( $fields ) {
	if ( ! is_array( $fields ) ) {
		$fields = array();
	}
	if ( ! in_array( '_bricks_page_content_2', $fields, true ) ) {
		$fields[] = '_bricks_page_content_2';
	}

	return $fields;
}

/**
 * Includes only text from _bricks_page_content_2 custom field.
 *
 * This function goes through the multilevel array of _bricks_page_content_2
 * and only picks up the "text" elements inside it, discarding everything else.
 *
 * @param array  $value   An array of custom field values.
 * @param string $field   The name of the custom field.
 *
 * @return array An array containing a string with all the values concatenated
 * together.
 */
function relevanssi_bricks_values( $value, $field ) {
	if ( '_bricks_page_content_2' !== $field ) {
		return $value;
	}

	$content = '';
	array_walk_recursive(
		$value,
		function ( $text, $key ) use ( &$content ) {
			if ( 'text' === $key ) {
				$content .= ' ' . $text;
			}
		}
	);

	return array( $content );
}

/**
 * Makes sure the Bricks builder shortcode is included in the index, even when
 * the custom field setting is set to 'none'.
 *
 * @param string $value The custom field indexing setting value. The parameter
 * is ignored, Relevanssi disables this filter and then checks the option to
 * see what the value is.
 *
 * @return string If value is undefined, it's set to '_bricks_page_content_2'.
 */
function relevanssi_bricks_fix_none_setting( $value ) {
	if ( ! $value ) {
		$value = '_bricks_page_content_2';
	}

	return $value;
}
