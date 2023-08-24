<?php
/**
 * /lib/compatibility/avada.php
 *
 * Avada theme compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter(
	'fusion_live_search_query_args',
	function ( $args ) {
		$args['relevanssi'] = true;
		return $args;
	}
);
