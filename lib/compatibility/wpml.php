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

add_filter( 'relevanssi_hits_filter', 'relevanssi_wpml_filter' );

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

		$current_blog_language = get_bloginfo( 'language' );
		$filtered_hits         = array();
		foreach ( $data[0] as $hit ) {
			$original_hit = $hit;
			if ( is_numeric( $hit ) ) {
				// In case "fields" is set to "ids", fetch the post object we need.
				$original_hit = $hit;
				$hit          = get_post( $hit );
			}
			if ( ! isset( $hit->post_content ) ) {
				// The "fields" is set to "id=>parent".
				$original_hit = $hit;
				$hit          = get_post( $hit->ID );
			}

			if ( isset( $hit->blog_id ) ) {
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
				} else {
					// This is not a translated post type, so include all posts.
					$filtered_hits[] = $original_hit;
				}
			} elseif ( get_bloginfo( 'language' ) === $current_blog_language ) {
				// If there is no WPML but the target blog has identical language with current blog,
				// we use the hits. Note en-US is not identical to en-GB!
				$filtered_hits[] = $original_hit;
			}

			if ( isset( $hit->blog_id ) ) {
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
