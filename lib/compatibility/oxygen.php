<?php
/**
 * /lib/compatibility/oxygen.php
 *
 * Oxygen Builder compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_custom_field_value', 'relevanssi_oxygen_compatibility', 10, 3 );
add_filter( 'relevanssi_index_custom_fields', 'relevanssi_add_oxygen' );
add_filter( 'option_relevanssi_index_fields', 'relevanssi_oxygen_fix_none_setting' );
add_filter( 'relevanssi_oxygen_section_content', 'relevanssi_oxygen_code_block' );
add_filter( 'relevanssi_oxygen_section_content', 'relevanssi_oxygen_rich_text' );
add_action( 'save_post', 'relevanssi_insert_edit', 99, 1 );

/**
 * Cleans up the Oxygen Builder custom field for Relevanssi consumption.
 *
 * Splits up the big custom field content from ct_builder_shortcodes into
 * sections ([ct_section] tags). Each section can be processed with filters
 * defined with `relevanssi_oxygen_section_filters`, for example to remove
 * sections based on their "nicename" or "ct_category" values. After that the
 * section is passed through the `relevanssi_oxygen_section_content` filter.
 * Finally all shortcode tags are removed, leaving just the content.
 *
 * @param array  $value   An array of custom field values.
 * @param string $field   The name of the custom field. This function only looks
 * at `ct_builder_shortcodes` fields.
 * @param int    $post_id The post ID.
 *
 * @return array|null An array of custom field values, null if no value exists.
 */
function relevanssi_oxygen_compatibility( $value, $field, $post_id ) {
	if ( 'ct_builder_json' === $field ) {
		$json = array();
		foreach ( $value as $row ) {
			$json[] = json_decode( $row );
		}

		$content = '';
		if ( isset( $json[0]->children ) ) {
			foreach ( $json[0]->children as $child ) {
				$content .= relevanssi_process_oxygen_child( $child );
			}
		}

		$value[0] = $content;
		return $value;
	}

	if ( 'ct_builder_shortcodes_revisions_dates' === $field ) {
		return '';
	}
	if ( 'ct_builder_shortcodes_revisions' === $field ) {
		return '';
	}
	if ( 'ct_builder_shortcodes' === $field ) {
		if ( version_compare( CT_VERSION, '4.0', '>=' ) ) {
			return null;
		}
		if ( empty( $value ) ) {
			return null;
		}

		$content_tags = explode( '[ct_section', $value[0] );
		$page_content = '';

		foreach ( $content_tags as $content ) {
			if ( empty( $content ) ) {
				continue;
			}
			if ( '[' !== substr( $content, 0, 1 ) ) {
				$content = '[ct_section' . $content;
			}
			/**
			 * Allows defining filters to remove Oxygen Builder sections.
			 *
			 * The filters are arrays, with the array key defining the key and
			 * the value defining the value. If the filter array is
			 * array( 'nicename' => 'Hero BG' ), Relevanssi will look for
			 * sections that have "nicename":"Hero BG" in their settings and
			 * will remove those.
			 *
			 * @param array An array of filtering rules, defaults empty.
			 *
			 * @return array
			 */
			$filters = apply_filters(
				'relevanssi_oxygen_section_filters',
				array()
			);
			array_walk(
				$filters,
				function ( $filter ) use ( &$content ) {
					foreach ( $filter as $key => $value ) {
						if ( stristr( $content, '"' . $key . '":"' . $value . '"' ) !== false ) {
							$content = '';
						}
					}
				}
			);

			$content = preg_replace(
				array(
					'/\[oxygen.*?\]/',
					'/\[\/?ct_.*?\]/',
					'/\[\/?oxy_.*?\]/',
				),
				' ',
				/**
				 * Filters the Oxygen Builder section content before the
				 * Oxygen Builder shortcode tags are removed.
				 *
				 * @param string $content The single section content.
				 * @param int    $post_id The post ID.
				 *
				 * @return string
				 */
				apply_filters(
					'relevanssi_oxygen_section_content',
					$content,
					$post_id
				)
			);

			$page_content .= $content;
		}

		$page_content = relevanssi_do_shortcode( $page_content );

		$value[0] = $page_content;
	}
	return $value;
}

/**
 * Recursively processes the Oxygen JSON data.
 *
 * This function extracts all the ct_content data from the JSON. All elements
 * are run through the relevanssi_oxygen_element filter hook. You can use that
 * filter hook to modify or to eliminate elements from the JSON.
 *
 * @param array $child The child element array.
 *
 * @return string The content from the child and the grandchildren.
 */
function relevanssi_process_oxygen_child( $child ): string {
	/**
	 * Filters the Oxygen JSON child element.
	 *
	 * If the filter returns an empty value, the child element and all its
	 * children will be ignored.
	 *
	 * @param array $child The JSON child element.
	 */
	$child = apply_filters( 'relevanssi_oxygen_element', $child );
	if ( empty( $child ) ) {
		return '';
	}

	$child_content = ' ';
	if ( isset( $child->options->ct_content ) ) {
		$child_content .= $child->options->ct_content;
	}

	if ( isset( $child->options->original->{'code-php'} ) ) {
		// For code and HTML blocks, strip all tags.
		$child_content .= wp_strip_all_tags( $child->options->original->{'code-php'} );
	}

	if ( isset( $child->children ) ) {
		foreach ( $child->children as $grandchild ) {
			$child_content .= relevanssi_process_oxygen_child( $grandchild );
		}
	}

	return $child_content;
}

/**
 * Adds the Oxygen custom field to the list of indexed custom fields.
 *
 * @param array|boolean $fields An array of custom fields to index, or false.
 *
 * @return array An array of custom fields, including `ct_builder_json` or
 * `ct_builder_shortcodes`.
 */
function relevanssi_add_oxygen( $fields ) {
	$oxygen_field = version_compare( CT_VERSION, '4.0', '>=' )
		? 'ct_builder_json'
		: 'ct_builder_shortcodes';
	if ( ! is_array( $fields ) ) {
		$fields = array();
	}
	if ( ! in_array( $oxygen_field, $fields, true ) ) {
		$fields[] = $oxygen_field;
	}

	return $fields;
}

/**
 * Makes sure the Oxygen builder shortcode is included in the index, even when
 * the custom field setting is set to 'none'.
 *
 * @param string $value The custom field indexing setting value. The parameter
 * is ignored, Relevanssi disables this filter and then checks the option to
 * see what the value is.
 *
 * @return string If value is undefined, it's set to 'ct_builder_json' or
 * 'ct_builder_shortcodes'.
 */
function relevanssi_oxygen_fix_none_setting( $value ) {
	if ( ! $value ) {
		$value = version_compare( CT_VERSION, '4.0', '>=' )
			? 'ct_builder_json'
			: 'ct_builder_shortcodes';
	}

	return $value;
}

/**
 * Indexes the Base64 encoded PHP & HTML code block contents.
 *
 * @param string $content The section content from the
 * relevanssi_oxygen_section_content filter hook.
 *
 * @return string $content The content with the decoded code block content
 * added to the end.
 */
function relevanssi_oxygen_code_block( $content ) {
	if ( preg_match_all( '/\[ct_code_block.*?ct_code_block\]/', $content, $matches ) ) {
		foreach ( $matches[0] as $match ) {
			if ( preg_match_all( '/"code-php":"(.*?)"/', $match, $block_matches ) ) {
				foreach ( $block_matches[1] as $encoded_text ) {
					$content .= ' ' . base64_decode( $encoded_text ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				}
			}
		}
	}
	return $content;
}

/**
 * Removes the Oxygen rich text shortcode.
 *
 * @param string $content The content of the Oxygen section.
 *
 * @return string The content with the oxy_rich_text shortcodes removed.
 */
function relevanssi_oxygen_rich_text( $content ) {
	$content = preg_replace( '/\[\/?oxy_rich_text.*?\]/im', '', $content );
	return $content;
}
