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

add_filter(
	'bricks/posts/query_vars',
	function( $query_vars ) {
		$query_vars['relevanssi'] = true;
		return $query_vars;
	}
);
