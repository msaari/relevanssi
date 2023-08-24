<?php
/**
 * /lib/compatibility/wpml.php
 *
 * WPML filtering function.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_hits_filter', 'relevanssi_wpml_filter', 9 );
add_filter( 'relevanssi_tag_before_tokenize', 'relevanssi_wpml_term_fix', 10, 4 );
add_action( 'relevanssi_pre_index_taxonomies', 'relevanssi_disable_wpml_terms' );
add_action( 'relevanssi_post_index_taxonomies', 'relevanssi_enable_wpml_terms' );

/**
 * Filters posts based on WPML language.
 *
 * Attaches to 'relevanssi_hits_filter' to restrict WPML searches to the current
 * language. Whether this filter is used or not depends on the option
 * 'relevanssi_wpml_only_current'. Thanks to rvencu for the initial code.
 *
 * @global object $sitepress The WPML global object.
 *
 * @param array $data Index 0 has the array of results, index 1 has the search query.
 *
 * @return array $data The whole parameter array, with the filtered posts in the index 0.
 */
function relevanssi_wpml_filter( $data ) {
	$filter_enabled = get_option( 'relevanssi_wpml_only_current' );
	if ( 'on' === $filter_enabled ) {
		$wpml_post_type_setting = apply_filters( 'wpml_setting', false, 'custom_posts_sync_option' );
		$wpml_taxonomy_setting  = apply_filters( 'wpml_setting', false, 'taxonomies_sync_option' );

		$current_blog_language = get_bloginfo( 'language' );
		$filtered_hits         = array();
		foreach ( $data[0] as $hit ) {
			$original_hit = $hit;

			$object_array = relevanssi_get_an_object( $hit );
			$hit          = $object_array['object'];

			if ( isset( $hit->blog_id ) && function_exists( 'switch_to_blog' ) ) {
				// This is a multisite search.
				switch_to_blog( $hit->blog_id );
				if ( function_exists( 'icl_object_id' ) ) {
					// Reset the WPML cache when blog is switched, otherwise WPML
					// will be confused.
					global $wpml_post_translations;
					$wpml_post_translations->reload();
				}
			}

			global $sitepress;

			// Check if WPML is used.
			if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_is_translated_post_type' ) ) {
				if ( $sitepress->is_translated_post_type( $hit->post_type ) ) {
					$fallback_to_default = false;
					if ( isset( $wpml_post_type_setting[ $hit->post_type ] ) && '2' === $wpml_post_type_setting[ $hit->post_type ] ) {
						$fallback_to_default = true;
					}
					$id = apply_filters( 'wpml_object_id', $hit->ID, $hit->post_type, $fallback_to_default );
					// This is a post in a translated post type.
					if ( intval( $hit->ID ) === intval( $id ) ) {
						// The post exists in the current language, and can be included.
						$filtered_hits[] = $original_hit;
					}
				} elseif ( isset( $hit->term_id ) ) {
					$fallback_to_default = false;
					if ( isset( $wpml_taxonomy_setting[ $hit->post_type ] ) && '2' === $wpml_taxonomy_setting[ $hit->post_type ] ) {
						$fallback_to_default = true;
					}
					if ( ! isset( $hit->post_type ) ) {
						// This is a term object, not a Relevanssi-generated post object.
						$hit->post_type = $hit->taxonomy;
					}
					$id = apply_filters( 'wpml_object_id', $hit->term_id, $hit->post_type, $fallback_to_default );
					if ( intval( $hit->term_id ) === intval( $id ) ) {
						// The post exists in the current language, and can be included.
						$filtered_hits[] = $original_hit;
					}
				} else {
					// This is not a translated post type, so include all posts.
					$filtered_hits[] = $original_hit;
				}
			} elseif ( get_bloginfo( 'language' ) === $current_blog_language ) {
				// If there is no WPML but the target blog has identical language with current blog,
				// we use the hits. Note en-US is not identical to en-GB!
				$filtered_hits[] = $original_hit;
			}

			if ( isset( $hit->blog_id ) && function_exists( 'restore_current_blog' ) ) {
				restore_current_blog();
			}
		}

		// A bit of foolproofing, avoid a warning if someone passes this filter bad data.
		$query = '';
		if ( isset( $data[1] ) ) {
			$query = $data[1];
		}
		return array( $filtered_hits, $query );
	}

	return $data;
}

/**
 * Fixes translated term indexing for WPML.
 *
 * WPML indexed translated terms based on current admin language, not the post
 * language. This filter changes the term indexing to match the post language.
 *
 * @param string $term_content All terms in the taxonomy as a string.
 * @param array  $terms        All the term objects in the current taxonomy.
 * @param string $taxonomy     The taxonomy name.
 * @param int    $post_id      The post ID.
 *
 * @return string The term names as a string.
 */
function relevanssi_wpml_term_fix( string $term_content, array $terms, string $taxonomy, int $post_id ) {
	$post_language = apply_filters( 'wpml_post_language_details', null, $post_id );
	if ( ! is_wp_error( $post_language ) ) {
		$term_content = '';

		global $sitepress;
		remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );

		foreach ( $terms as $term ) {
			$term = get_term(
				apply_filters(
					'wpml_object_id',
					$term->term_id,
					$taxonomy,
					true,
					$post_language['language_code']
				),
				$taxonomy
			);

			$term_content .= ' ' . $term->name;
		}

		add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
	}

	return $term_content;
}

/**
 * Disables WPML term filtering.
 *
 * This function disables the WPML term filtering, so that Relevanssi can index
 * the terms in the correct language.
 */
function relevanssi_disable_wpml_terms() {
	global $sitepress;
	remove_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1 );
}

/**
 * Enables WPML term filtering.
 *
 * This function enables the WPML term filtering.
 */
function relevanssi_enable_wpml_terms() {
	global $sitepress;
	add_filter( 'get_term', array( $sitepress, 'get_term_adjust_id' ), 1, 1 );
}
