<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-redirects.php
 *
 * Implements the configuration-driven Redirects field rendering engine.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 */

/**
 * Class Relevanssi_Setting_Field_Redirects
 *
 * Manages rendering, dynamic record parsing, and inline data-matrix extraction
 * for premium search redirection tables when active in the system context.
 */
class Relevanssi_Setting_Field_Redirects extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Overrides the abstract renderer to break out of the 2-column table grid constraints.
	 * Maps across both layout columns to allow inputs to breathe at full container width.
	 *
	 * @return void Writes layout markup straight to the active viewport stream.
	 */
	public function render() {
		?>
		<tr id="row_<?php echo esc_attr( $this->id ); ?>_wrapper" class="rlv-full-bleed-row">
			<td colspan="2" class="rlv-full-bleed-cell">
				<?php $this->render_input(); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the redirection field input data workspace panel.
	 *
	 * @return void Writes raw layout elements directly to output buffers.
	 */
	protected function render_input() {
		$site_url  = site_url();
		$redirects = get_option( 'relevanssi_redirects', array() );

		if ( ! is_array( $redirects ) ) {
			$redirects = array();
		}

		$empty_redirect    = $redirects['empty'] ?? '';
		$termless_redirect = $redirects['no_terms'] ?? '';

		unset( $redirects['empty'], $redirects['no_terms'] );
		?>
		<div class="rlv-field-section-header">
			<h2 class="rlv-field-section-title">
				<span><?php $this->render_label(); ?></span>
				<?php $this->render_tooltip(); ?>
			</h2>
		</div>

		<div class="rlv-redirects-subtable-wrapper">
			<div class="rlv-redirect-fallback-card">
				<div class="rlv-fallback-label-block">
					<label for="redirect_empty_searches"><strong><?php esc_html_e( 'Redirect empty searches', 'relevanssi' ); ?></strong></label>
					<p class="description"><?php esc_html_e( 'Target URL to route users to when a query yields zero hits.', 'relevanssi' ); ?></p>
				</div>
				<div class="rlv-fallback-input-block">
					<input type="text" id="redirect_empty_searches" name="redirect_empty_searches" class="rlv-redirect-base-input" value="<?php echo esc_attr( str_replace( $site_url, '', $empty_redirect ) ); ?>" placeholder="<?php esc_html_e( '/no-results-landing/', 'relevanssi' ); ?>" />
				</div>
			</div>

			<div class="rlv-redirect-fallback-card">
				<div class="rlv-fallback-label-block">
					<label for="redirect_no_terms"><strong><?php esc_html_e( 'Redirect searches without terms', 'relevanssi' ); ?></strong></label>
					<p class="description"><?php esc_html_e( 'Target URL to route users to when an empty string search form is submitted.', 'relevanssi' ); ?></p>
				</div>
				<div class="rlv-fallback-input-block">
					<input type="text" id="redirect_no_terms" name="redirect_no_terms" class="rlv-redirect-base-input" value="<?php echo esc_attr( str_replace( $site_url, '', $termless_redirect ) ); ?>" placeholder="<?php esc_html_e( '/search-help/', 'relevanssi' ); ?>" />
				</div>
			</div>
		</div>

		<div class="rlv-redirects-matrix-container" id="redirect_table">
			<div class="rlv-redirects-matrix-header">
				<div class="rlv-hdr-keyword"><?php esc_html_e( 'Query String keyword', 'relevanssi' ); ?></div>
				<div class="rlv-hdr-partial"><?php esc_html_e( 'Partial match', 'relevanssi' ); ?></div>
				<div class="rlv-hdr-url"><?php esc_html_e( 'Target Redirect URL', 'relevanssi' ); ?></div>
				<div class="rlv-hdr-hits"><?php esc_html_e( 'Hits', 'relevanssi' ); ?></div>
			</div>

			<div class="rlv-redirects-matrix-body">
			<?php
			if ( empty( $redirects ) ) {
				$this->render_table_row( 0, array() );
			} else {
				$row_index = 0;
				foreach ( $redirects as $redirect ) {
					if ( ! isset( $redirect['query'] ) ) {
						continue;
					}
					$this->render_table_row( $row_index, $redirect );
					++$row_index;
				}
			}
			?>
			</div>
		</div>

		<div class="rlv-redirects-actions-bar">
			<button type="button" class="button button-secondary button-thin" id="add_redirect">
				<span class="dashicons dashicons-plus-alt2" style="margin-right: 4px; font-size: 16px; width:16px; height:16px; display:inline-flex; align-items:center;"></span>
				<?php esc_html_e( 'Add New Redirect Rule', 'relevanssi' ); ?>
			</button>
		</div>

		<details class="rlv-redirects-bulk-card">
			<summary>
				<span><?php esc_html_e( 'Bulk Add via CSV', 'relevanssi' ); ?></span>
			</summary>
			<div class="rlv-bulk-card-content">
				<label for="relevanssi_csv_redirects"><strong><?php esc_html_e( 'CSV Redirect Data', 'relevanssi' ); ?></strong></label>
				<textarea name="relevanssi_csv_redirects" id="relevanssi_csv_redirects" rows="5" placeholder="<?php esc_html_e( 'keyword; /target-url/', 'relevanssi' ); ?>; 0"></textarea>
				<p class="description">
					<?php esc_html_e( 'Format: query;url;partial matching flag [1|0]. Separate each rule with a line break.', 'relevanssi' ); ?>
				</p>
			</div>
		</details>
		<?php
	}

	/**
	 * Output an individual dynamic configuration item row row framework.
	 *
	 * @param int   $row_id   Numerical iteration sorting ID value tracker context.
	 * @param array $redirect Initial parameter layout values containing localized database definitions.
	 * @return void Prints tabular elements directly.
	 */
	private function render_table_row( int $row_id, array $redirect ) {
		$site_url   = site_url();
		$query      = $redirect['query'] ?? '';
		$url        = str_replace( $site_url, '', $redirect['url'] ?? '' );
		$hits       = isset( $redirect['hits'] ) ? intval( $redirect['hits'] ) : 0;
		$is_partial = ! empty( $redirect['partial'] );
		?>
		<div class="redirect_table_row rlv-redirect-matrix-row" id="row_redirect_entry_<?php echo (int) $row_id; ?>">
			<div class="rlv-matrix-cell rlv-col-keyword" data-label="<?php esc_attr_e( 'Query String keyword', 'relevanssi' ); ?>">
				<div class="rlv-input-with-actions">
					<label class="screen-reader-text" for="query_<?php echo (int) $row_id; ?>"><?php esc_html_e( 'Query string', 'relevanssi' ); ?></label>
					<input type="text" id="query_<?php echo (int) $row_id; ?>" name="query_<?php echo (int) $row_id; ?>" class="rlv-redirect-base-input" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php esc_html_e( 'e.g., help', 'relevanssi' ); ?>" />

					<div class="row-actions rlv-redirect-row-utilities">
						<span class="copy"><a href="#" class="copy"><?php esc_html_e( 'Copy', 'relevanssi' ); ?></a></span>
						<span class="delete"><a href="#" class="remove"><?php esc_html_e( 'Remove', 'relevanssi' ); ?></a></span>
					</div>
				</div>
			</div>

			<div class="rlv-matrix-cell rlv-col-partial" data-label="<?php esc_attr_e( 'Partial match', 'relevanssi' ); ?>">
				<label class="screen-reader-text" for="partial_<?php echo (int) $row_id; ?>"><?php esc_html_e( 'Partial match', 'relevanssi' ); ?></label>
				<input class="relevanssi-toggle" type="checkbox" id="partial_<?php echo (int) $row_id; ?>" name="partial_<?php echo (int) $row_id; ?>" <?php checked( $is_partial ); ?> />
			</div>

			<div class="rlv-matrix-cell rlv-col-url" data-label="<?php esc_attr_e( 'Target Redirect URL', 'relevanssi' ); ?>">
				<label class="screen-reader-text" for="url_<?php echo (int) $row_id; ?>"><?php esc_html_e( 'Target URL', 'relevanssi' ); ?></label>
				<input type="text" name="url_<?php echo (int) $row_id; ?>" id="url_<?php echo (int) $row_id; ?>" class="rlv-redirect-base-input" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php esc_html_e( '/support/', 'relevanssi' ); ?>" />
			</div>

			<div class="rlv-matrix-cell rlv-col-hits" data-label="<?php esc_attr_e( 'Hits', 'relevanssi' ); ?>">
				<input type="hidden" name="hits_<?php echo (int) $row_id; ?>" id="hits_<?php echo (int) $row_id; ?>" value="<?php echo (int) $hits; ?>" />
				<span class="rlv-redirect-hits-badge"><?php echo esc_html( $hits ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Processes, compiles, and saves the compound redirects options state.
	 *
	 * @param array $request The raw request payload from the administration form.
	 * @return bool True if the database option was successfully updated, false otherwise.
	 */
	public function save( array $request ): bool {
		if ( ! defined( 'RELEVANSSI_PREMIUM' ) || ! RELEVANSSI_PREMIUM ) {
			return false;
		}

		// Process the CSV bulk insertion text if present through the core legacy helpers.
		if ( function_exists( 'relevanssi_parse_csv_redirects' ) ) {
			$request = relevanssi_parse_csv_redirects( $request );
		}

		// Compile the dynamic table matrix entries into a structured option array.
		if ( function_exists( 'relevanssi_process_redirects' ) ) {
			$redirects_value = relevanssi_process_redirects( $request );
		} else {
			$redirects_value = array();
			$row             = 0;

			while ( isset( $request[ 'query_' . $row ] ) ) {
				$query = sanitize_text_field( wp_unslash( $request[ 'query_' . $row ] ) );
				if ( ! empty( $query ) ) {
					$url        = sanitize_text_field( wp_unslash( $request[ 'url_' . $row ] ?? '' ) );
					$is_partial = isset( $request[ 'partial_' . $row ] );
					$hits       = isset( $request[ 'hits_' . $row ] ) ? intval( $request[ 'hits_' . $row ] ) : 0;

					$redirects_value[] = array(
						'query'   => $query,
						'url'     => $url,
						'partial' => $is_partial,
						'hits'    => $hits,
					);
				}
				++$row;
			}

			// Capture the localized subtable redirect parameters.
			$redirects_value['empty']    = isset( $request['redirect_empty_searches'] ) ? sanitize_text_field( wp_unslash( $request['redirect_empty_searches'] ) ) : '';
			$redirects_value['no_terms'] = isset( $request['redirect_no_terms'] ) ? sanitize_text_field( wp_unslash( $request['redirect_no_terms'] ) ) : '';
		}

		$autoload_state = $this->config['autoload'] ?? true;
		return update_option( 'relevanssi_redirects', $redirects_value, $autoload_state );
	}
}