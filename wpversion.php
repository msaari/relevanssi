<?php
/**
 * WP version checker.
 *
 * Fetches the current WP version information from the WordPress.org API.
 *
 * @package Relevanssi
 */

// phpcs:disable

$version_data    = file_get_contents( 'https://api.wordpress.org/core/version-check/1.7/' );
$minimum_version = false;
if ( isset( $argv[1] ) ) {
	$minimum_version = $argv[1];
}

$version_data     = json_decode( $version_data );
$current_versions = array();
foreach ( $version_data->offers as $offer ) {
	if ( $minimum_version && version_compare( $offer->current, $minimum_version ) < 0 ) {
		continue;
	}
	$current_versions[] = $offer->current;
}
$version_list = implode( ' ', array_unique( $current_versions ) );
echo $version_list;