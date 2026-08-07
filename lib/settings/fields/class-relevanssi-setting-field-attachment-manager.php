<?php
/**
 * Action buttons and metrics dashboard field component handler.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Attachment_Manager
 *
 * Handles rendering the interactive attachment processing tools, metrics status charts,
 * and background operations controls seamlessly inside the OO layout context.
 */
class Relevanssi_Setting_Field_Attachment_Manager extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Renders the full structural component block seamlessly without wrapping row constraints.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! $this->is_visible() ) {
			return;
		}

		$pdf_count       = $this->config['pdf_count'] ?? 0;
		$pdf_error_count = $this->config['pdf_error_count'] ?? 0;
		?>
		<div id="attachments_tab" class="card-attachment-actions-inner">
			<h2><?php esc_html_e( 'Attachment Status & Processing', 'relevanssi' ); ?></h2>
			
			<div id="stateofthepdfindex" class="relevanssi-metrics" style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #f0f0f1;">
				<div class="metric">
					<span class="metric-number"><?php echo esc_html( $pdf_count ); ?></span>
					<span class="metric-label"><?php echo esc_html( _n( 'Document has read attachment content', 'Documents have read attachment content', $pdf_count, 'relevanssi' ) ); ?></span>
				</div>
				<div class="metric">
					<span class="metric-number" style="color: <?php echo $pdf_error_count > 0 ? '#d63638' : '#3c434a'; ?>;"><?php echo esc_html( $pdf_error_count ); ?></span>
					<span class="metric-label">
						<?php echo esc_html( _n( 'Document has a reading error', 'Documents have reading errors', $pdf_error_count, 'relevanssi' ) ); ?>
						<?php if ( $pdf_error_count > 0 ) : ?>
							(<span id="relevanssi_show_pdf_errors" style="cursor:pointer; text-decoration:underline; color:#2271b1;"><?php esc_html_e( 'Show errors', 'relevanssi' ); ?></span>)
						<?php endif; ?>
					</span>
				</div>
				<label for="relevanssi_pdf_errors" class="screen-reader-text"><?php esc_html_e( 'Attachment reading errors', 'relevanssi' ); ?></label>
				<textarea id="relevanssi_pdf_errors" rows="4" style="display:none; width: 100%; margin-top: 12px;"></textarea>
			</div>

			<table class="form-table" role="presentation">
				<tbody>
					<tr id="row_read_attachments">
						<th scope="row"><?php esc_html_e( 'Read attachments', 'relevanssi' ); ?></th>
						<td>
							<div style="margin-bottom: 12px;">
								<input type='button' id='index' value='<?php esc_attr_e( 'Read all unread attachments', 'relevanssi' ); ?>' class='button-primary' />
							</div>
							<p class="description" id="indexing_button_instructions">
								<?php /* translators: the placeholder has the name of the custom field for PDF content */ ?>
								<?php printf( esc_html__( 'Clicking the button will read the contents of all unread attachment files and store the contents to the %s custom field. Files with timeout or connection errors will be re-attempted.', 'relevanssi' ), '<code>_relevanssi_pdf_content</code>' ); ?>
							</p>
							
							<div id='relevanssi-note' style='display: none'></div>
							<div id='relevanssi-progress' class='rpi-progress'><div></div></div>
							<div id='relevanssi-timer' style="margin-top: 8px; font-weight: 500;">
								<?php esc_html_e( 'Time elapsed', 'relevanssi' ); ?>: <span id="relevanssi_elapsed">0:00:00</span> | 
								<?php esc_html_e( 'Time remaining', 'relevanssi' ); ?>: <span id="relevanssi_estimated"><?php esc_html_e( 'calculating...', 'relevanssi' ); ?></span>
							</div>
							
							<label for="relevanssi_results" class="screen-reader-text"><?php esc_html_e( 'Results', 'relevanssi' ); ?></label>
							<textarea id='relevanssi_results' rows='6' style="width: 100%; margin-top: 12px; display: none;"></textarea>
						</td>
					</tr>
					<tr id="row_reset_attachment_content">
						<th scope="row"><?php esc_html_e( 'Reset data', 'relevanssi' ); ?></th>
						<td>
							<input type="button" id="reset" value="<?php esc_attr_e( 'Reset all attachment data from posts', 'relevanssi' ); ?>" class="button" />
							<?php /* translators: the placeholders are the names of the custom fields */ ?>
							<p class="description"><?php printf( esc_html__( "Removes all %1\$s and %2\$s custom fields from all posts. Use this to clean up before a fresh read; clicking the read button doesn't wipe the slate clean automatically.", 'relevanssi' ), '<code>_relevanssi_pdf_content</code>', '<code>_relevanssi_pdf_error</code>' ); ?></p>
						</td>
					</tr>
					<tr id="row_reset_server_errors">
						<th scope="row"><?php esc_html_e( 'Clear server errors', 'relevanssi' ); ?></th>
						<td>
							<input type="button" id="clearservererrors" value="<?php esc_attr_e( 'Clear server errors', 'relevanssi' ); ?>" class="button" />
							<p class="description"><?php esc_html_e( "Clears all 'Server did not respond' errors from posts to reattempt reading files.", 'relevanssi' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Passive no-op saving override for purely structural or execution nodes.
	 *
	 * @param array $request Raw admin form submission request data matrix.
	 * @return bool Always true to prevent pipeline validation failures.
	 */
	public function save( array $request ): bool {
		return true;
	}

	/**
	 * Unused abstract requirements fallback.
	 *
	 * @return void
	 */
	protected function render_input() {}
}