<?php
/**
 * /tests/bootstrap.php
 *
 * Set up environment for Relevanssi tests suite.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Do the vendor autoload.
 */
require_once './vendor/autoload.php';

\rask\WpTestFramework\Framework::load();

/**
 * Load and install Relevanssi.
 */
require_once dirname( __DIR__ ) . '/relevanssi.php';
relevanssi_install();
