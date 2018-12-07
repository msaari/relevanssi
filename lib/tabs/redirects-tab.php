<?php
/**
 * /lib/tabs/redirects-tab.php
 *
 * Prints out the Redirects tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the redirects tab in Relevanssi settings.
 */
function relevanssi_redirects_tab() {
	?>
	<h2><?php esc_html_e( 'Redirects', 'relevanssi' ); ?></h2>

	<p><?php esc_html_e( 'With Relevanssi Premium, you can set up redirects. These are keywords that automatically redirect the user to certain page, without going through the usual search process. For example, you could set it up so that all searches for "job" automatically lead to your "Careers" page.', 'relevanssi' ); ?></p>
	<?php // Translators: %1$s starts the link, %2$s closes it. ?>
	<p><?php printf( esc_html__( 'In order to access this and many other delightful Premium features, %1$sbuy Relevanssi Premium here%2$s.', 'relevanssi' ), '<a href="https://www.relevanssi.com/buy-premium/">', '</a>' ); ?></p>
	<?php
}
