<?php
/**
 * /lib/compatibility/acf.php
 *
 * Advanced Custom Fields compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'acf/render_field_settings', 'relevanssi_acf_exclude_setting' );
add_filter( 'relevanssi_search_ok', 'relevanssi_acf_relationship_fields' );
add_filter( 'relevanssi_index_custom_fields', 'relevanssi_acf_exclude_fields', 10, 2 );

/**
 * Disables Relevanssi in the ACF Relationship field post search.
 *
 * We don't want to use Relevanssi on the ACF Relationship field post searches, so
 * this function disables it (on the 'relevanssi_search_ok' hook).
 *
 * @param boolean $search_ok Block the search or not.
 *
 * @return boolean False, if this is an ACF Relationship field search, pass the
 * parameter unchanged otherwise.
 */
function relevanssi_acf_relationship_fields( $search_ok ) {
	// phpcs:disable WordPress.Security.NonceVerification
	if ( isset( $_REQUEST['action'] )
		&& is_string( $_REQUEST['action'] )
		&& 'acf' === substr( $_REQUEST['action'], 0, 3 ) ) {
		$search_ok = false;
	}
	return $search_ok;
}

/**
 * Indexes the human-readable value of "choice" options list from ACF.
 *
 * @author Droz Raphaël
 *
 * @param array  $insert_data The insert data array.
 * @param int    $post_id     The post ID.
 * @param string $field_name  Name of the field.
 * @param string $field_value The field value.
 *
 * @return int Number of tokens indexed.
 */
function relevanssi_index_acf( &$insert_data, $post_id, $field_name, $field_value ) {
	if ( ! is_admin() ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php'; // Otherwise is_plugin_active() will cause a fatal error.
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		return 0;
	}
	if ( ! is_plugin_active( 'advanced-custom-fields/acf.php' ) && ! is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
		return 0;
	}
	if ( ! function_exists( 'get_field_object' ) ) {
		return 0; // ACF is active, but not loaded.
	}

	$field_object = get_field_object( $field_name, $post_id );
	if ( ! isset( $field_object['choices'] ) ) {
		return 0; // Not a "select" field.
	}
	if ( is_array( $field_value ) ) {
		return 0; // Not handled (currently).
	}
	if ( ! isset( $field_object['choices'][ $field_value ] ) ) {
		return 0; // Value does not exist.
	}

	$n = 0;

	/**
	 * Filters the field value before it is used to save the insert data.
	 *
	 * The value is used as an array key, so it needs to be an integer or a
	 * string. If your custom field values are arrays or objects, use this
	 * filter hook to convert them into strings.
	 *
	 * @param mixed  $field_content The ACF field value.
	 * @param string $field_name    The ACF field name.
	 * @param int    $post_id       The post ID.
	 *
	 * @return string|int The field value.
	 */
	$value = apply_filters(
		'relevanssi_acf_field_value',
		$field_object['choices'][ $field_value ],
		$field_name,
		$post_id
	);

	if ( $value && ( is_integer( $value ) || is_string( $value ) ) ) {
		$min_word_length = get_option( 'relevanssi_min_word_length', 3 );

		/** This filter is documented in lib/indexing.php */
		$value_tokens = apply_filters( 'relevanssi_indexing_tokens', relevanssi_tokenize( $value, true, $min_word_length, 'indexing' ), 'custom_field' );
		foreach ( $value_tokens as $token => $count ) {
			++$n;
			if ( ! isset( $insert_data[ $token ]['customfield'] ) ) {
				$insert_data[ $token ]['customfield'] = 0;
			}
			$insert_data[ $token ]['customfield'] += $count;

			// Premium indexes more detail about custom fields.
			if ( function_exists( 'relevanssi_customfield_detail' ) ) {
				$insert_data = relevanssi_customfield_detail( $insert_data, $token, $count, $field_name );
			}
		}
	}

	return $n;
}

/**
 * Adds a Relevanssi exclude setting to ACF fields.
 *
 * @param array $field The field object array.
 */
function relevanssi_acf_exclude_setting( $field ) {
	if ( ! function_exists( 'acf_render_field_setting' ) ) {
		return;
	}
	if ( 'clone' === $field['type'] ) {
		return;
	}
	acf_render_field_setting(
		$field,
		array(
			'label'        => __( 'Exclude from Relevanssi index', 'relevanssi' ),
			'instructions' => __( 'If this setting is enabled, Relevanssi will not index the value of this field for posts.', 'relevanssi' ),
			'name'         => 'relevanssi_exclude',
			'type'         => 'true_false',
			'ui'           => 1,
		),
		true
	);
}

/**
 * Excludes ACF fields based on the exclude setting.
 *
 * Hooks on to relevanssi_index_custom_fields.
 *
 * @param array $fields  The list of custom fields to index.
 * @param int   $post_id The post ID.
 *
 * @return array Filtered list of custom fields.
 */
function relevanssi_acf_exclude_fields( $fields, $post_id ) {
	$included_fields = array();
	$excluded_fields = array();

	/**
	 * Filters the types of ACF fields to exclude from indexing.
	 *
	 * By default, blocks 'repeater', 'flexible_content' and 'group' are
	 * excluded from Relevanssi indexing. You can add other field types here.
	 *
	 * @param array $excluded_field_types The field types to exclude.
	 */
	$blocked_field_types = apply_filters(
		'relevanssi_blocked_field_types',
		array( 'repeater', 'flexible_content', 'group' )
	);

	global $post;
	foreach ( $fields as $field ) {
		$global_post  = $post; // ACF fields can change the global $post.
		$field_object = get_field_object( $field );
		$post         = $global_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( ! $field_object || ! is_array( $field_object ) ) {
			$field_id = relevanssi_acf_get_field_id( $field, $post_id );
			if ( ! $field_id ) {
				// No field ID -> not an ACF field. Include.
				$included_fields[] = $field;
			} else {
				/*
				 * This field has a field ID, but get_field_object() does not
				 * return a field object. This may be a clone field, in which
				 * case we can try to get the field object from the field ID.
				 * Clone fields have keys like field_xxx_field_yyy, where the
				 * field_yyy is the part we need.
				 */
				$field_id     = preg_replace( '/.*_(field_.*)/', '$1', $field_id );
				$field_object = get_field_object( $field_id );
			}
		}
		if ( $field_object ) {
			/**
			 * Filters the ACF field object.
			 *
			 * If the filter returns a false value, Relevanssi will not index
			 * the field.
			 *
			 * @param array $field_object The field object.
			 * @param int   $post_id      The post ID.
			 *
			 * @return array The filtered field object.
			 */
			$field_object = apply_filters(
				'relevanssi_acf_field_object',
				$field_object,
				$post_id
			);

			if ( ! $field_object ) {
				continue;
			}
			if ( isset( $field_object['relevanssi_exclude'] ) && 1 === $field_object['relevanssi_exclude'] ) {
				continue;
			}
			if ( relevanssi_acf_is_parent_excluded( $field_object ) ) {
				continue;
			}
			if ( isset( $field_object['type'] ) && in_array( $field_object['type'], $blocked_field_types, true ) ) {
				continue;
			}
			$included_fields[] = $field;
		}
	}
	return $included_fields;
}

/**
 * Checks if the field has an excluded parent field.
 *
 * If the field has a "parent" value set, this function gets the parent field
 * post based on the post ID in the "parent" value. This is done recursively
 * until we reach the top or find an excluded parent.
 *
 * @param array $field_object The field object.
 *
 * @return bool Returns true if the post has an excluded parent.
 */
function relevanssi_acf_is_parent_excluded( $field_object ) {
	if ( isset( $field_object['parent'] ) ) {
		$parent = $field_object['parent'];
		if ( $parent ) {
			$parent_field_post = get_post( $parent );
			if ( $parent_field_post ) {
				$parent_object = get_field_object( $parent_field_post->post_name );
				if ( $parent_object ) {
					if ( isset( $parent_object['relevanssi_exclude'] ) && 1 === $parent_object['relevanssi_exclude'] ) {
						return true;
					}
					return relevanssi_acf_is_parent_excluded( $parent_object );
				}
			}
		}
	}
	return false;
}

/**
 * Gets the field ID from the field name.
 *
 * The field ID is stored in the postmeta table with the field name prefixed
 * with an underscore as the key.
 *
 * @param string $field_name The field name.
 * @param int    $post_id    The post ID.
 *
 * @return string The field ID.
 */
function relevanssi_acf_get_field_id( $field_name, $post_id ) {
	global $wpdb;

	$field_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT meta_value FROM $wpdb->postmeta
			WHERE post_id = %d
			AND meta_key = %s",
			$post_id,
			'_' . $field_name
		)
	);
	return $field_id;
}
