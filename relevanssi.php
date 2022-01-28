<?php
/**
 * Relevanssi
 *
 * /relevanssi.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 *
 * @wordpress-plugin
 * Plugin Name: Relevanssi
 * Plugin URI: https://www.relevanssi.com/
 * Description: This plugin replaces WordPress search with a relevance-sorting search.
 * Version: 4.14.7
 * Author: Mikko Saari
 * Author URI: http://www.mikkosaari.fi/
 * Text Domain: relevanssi
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Copyright 2022 Mikko Saari  (email: mikko@mikkosaari.fi)
 * This file is part of Relevanssi, a search plugin for WordPress.
 *
 * Relevanssi is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Relevanssi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Relevanssi.  If not, see <http://www.gnu.org/licenses/>.
 */

define( 'RELEVANSSI_PREMIUM', false );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'relevanssi_action_links' );

global $wp_version;
if ( version_compare( $wp_version, '5.1', '>=' ) ) {
	add_action( 'wp_insert_site', 'relevanssi_new_blog', 10, 1 );
} else {
	add_action( 'wpmu_new_blog', 'relevanssi_new_blog', 10, 1 );
}

global $relevanssi_variables;
global $wpdb;

$relevanssi_variables['relevanssi_table']                      = $wpdb->prefix . 'relevanssi';
$relevanssi_variables['stopword_table']                        = $wpdb->prefix . 'relevanssi_stopwords';
$relevanssi_variables['log_table']                             = $wpdb->prefix . 'relevanssi_log';
$relevanssi_variables['content_boost_default']                 = 1;
$relevanssi_variables['comment_boost_default']                 = 0.75;
$relevanssi_variables['title_boost_default']                   = 5;
$relevanssi_variables['comment_boost_default']                 = 0.75;
$relevanssi_variables['post_type_weight_defaults']['post_tag'] = 0.75;
$relevanssi_variables['post_type_weight_defaults']['category'] = 0.75;
$relevanssi_variables['post_type_index_defaults']              = array( 'post', 'page' );
$relevanssi_variables['database_version']                      = 6;
$relevanssi_variables['file']                                  = __FILE__;
$relevanssi_variables['plugin_dir']                            = plugin_dir_path( __FILE__ );
$relevanssi_variables['plugin_basename']                       = plugin_basename( __FILE__ );
$relevanssi_variables['plugin_version']                        = '4.14.7';

require_once 'lib/admin-ajax.php';
require_once 'lib/common.php';
require_once 'lib/didyoumean.php';
require_once 'lib/excerpts-highlights.php';
require_once 'lib/indexing.php';
require_once 'lib/init.php';
require_once 'lib/install.php';
require_once 'lib/interface.php';
require_once 'lib/log.php';
require_once 'lib/options.php';
require_once 'lib/phrases.php';
require_once 'lib/privacy.php';
require_once 'lib/search.php';
require_once 'lib/search-tax-query.php';
require_once 'lib/search-query-restrictions.php';
require_once 'lib/shortcodes.php';
require_once 'lib/sorting.php';
require_once 'lib/stopwords.php';
require_once 'lib/user-searches.php';
require_once 'lib/utils.php';
