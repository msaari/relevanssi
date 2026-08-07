<?php
/**
 * /lib/compatibility/document-library-pro.php
 *
 * Document Library Pro compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'document_library_pro_query_args', 'relevanssi_dlp_support' );

/**
 * Adds the "relevanssi" parameter to DLP above-documents search box.
 *
 * @param array $args The query argument array.
 * @return array
 */
function relevanssi_dlp_support( array $args ) {
	$args['relevanssi'] = true;
	return $args;
}
