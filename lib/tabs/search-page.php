<?php
/**
 * /lib/tabs/search-tab.php
 *
 * Prints out the search tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the search tab in Relevanssi settings.
 */
function relevanssi_search_tab() {
	?>
	<p><?php esc_html_e( 'You can use this search to perform Relevanssi searches without any restrictions from WordPress. You can search all post types here.', 'relevanssi' ); ?></p>

	<form action="" method="get">
	<table class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<label for='s'><?php esc_html_e( 'Search terms', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='s' id='s' size='60' />
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='post_types'><?php esc_html_e( 'Post type', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='post_types' id='post_types'>
				<option value="any"><?php esc_html_e( 'Any', 'relevanssi' ); ?></option>
	<?php
	echo implode(
		' ',
		array_map( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			function ( $post_type ) {
				$pt = get_post_type_object( $post_type );
				if ( $pt ) {
					$post_type_value = esc_attr( $post_type );
					$post_type_name  = esc_html( $pt->labels->singular_name );
					return "<option value='{$post_type_value}'>{$post_type_name}</option>";
				}
				return null;
			},
			get_option( 'relevanssi_index_post_types', array() )
		)
	);
	if ( 'on' === get_option( 'relevanssi_index_users' ) ) {
		?>
		<option value='user'><?php esc_html_e( 'Users', 'relevanssi' ); ?></option>
		<?php
	}
	?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='posts_per_page'><?php esc_html_e( 'Posts per page', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='posts_per_page' id='posts_per_page'>
				<option value='0'><?php esc_html_e( 'All', 'relevanssi' ); ?></option>
				<option>10</option>
				<option>50</option>
				<option>100</option>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='args'><?php esc_html_e( 'Search parameters', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='args' id='args' size='60' />
			<?php // Translators: example query string. ?>
			<p class='description'><?php printf( esc_html__( 'Use query parameter formatting here, the same that would appear on search page results URL. For example %s.', 'relevanssi' ), '<code>posts_per_page=10&post_types=page&from=2018-01-01</code>' ); ?></p>
		</td>
	</tr>
	<tr>
		<td>
		</td>
		<td>
			<input type='submit' name='search' id='search' value='<?php echo esc_html_x( 'Search', 'button action', 'relevanssi' ); ?>' class='button' />
		</td>
	</tr>
	</table>
	</form>

	<div id='results'></div>
	<?php
}
