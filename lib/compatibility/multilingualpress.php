<?php
/**
 * /lib/compatibility/multilingualpress.php
 *
 * MultilingualPress compatibility features.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action('multilingualpress.save_metabox', static function () {
    // These hooks break the media cloning feature in MultilingualPress.
    remove_filter( 'attachment_link', 'relevanssi_permalink' );
    remove_filter( 'attachment_link', 'relevanssi_post_link_replace' );
});