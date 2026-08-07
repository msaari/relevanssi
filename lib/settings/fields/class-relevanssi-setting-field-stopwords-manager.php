<?php
/**
 * Relevanssi_Setting_Field_Stopwords_Manager class file.
 *
 * Handles rendering and option lifecycle management for core index stopwords.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Stopwords_Manager
 */
class Relevanssi_Setting_Field_Stopwords_Manager extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Renders clean object-oriented input interfaces for managing search stopwords.
	 * Preserves the exact request parameter structures required by core database mutation handlers.
	 *
	 * @return void
	 */
	protected function render_input() {
		$stopwords = array();
		if ( function_exists( 'relevanssi_fetch_stopwords' ) ) {
			$stopwords = array_map( 'stripslashes', relevanssi_fetch_stopwords() );
		}
		sort( $stopwords );
		$export_list = implode( ', ', $stopwords );
		?>
		<div class="rlv-manager-wrapper">
			<div class="rlv-input-group">
				<textarea name="addstopword" id="addstopword" placeholder="<?php esc_attr_e( 'Enter words separated by commas (e.g. and, the, with)...', 'relevanssi' ); ?>"></textarea>
				<label for="addstopword" class="screen-reader-text"><?php esc_html_e( 'Add a comma-separated list of stopwords here.', 'relevanssi' ); ?></label>
				<input type="submit" value="<?php esc_attr_e( 'Add', 'relevanssi' ); ?>" class="button button-secondary" />
			</div>
			<p class="description"><?php esc_html_e( 'Exclusions are automatically and immediately removed from the search index.', 'relevanssi' ); ?></p>

			<h3 style="margin: 20px 0 4px 0; font-size: 13px; font-weight: 600;"><?php esc_html_e( 'Active Database Stopwords', 'relevanssi' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Click any term below to remove it from the stopwords. Reindexing is required to restore deleted words back to search results.', 'relevanssi' ); ?></p>

			<div class="rlv-cloud-container">
				<?php if ( ! empty( $stopwords ) ) : ?>
					<ul class="rlv-word-list">
						<?php foreach ( $stopwords as $word ) : ?>
							<li class="rlv-word-item">
								<input type="submit" name="removestopword" value="<?php echo esc_attr( $word ); ?>" class="button button-small" title="<?php esc_attr_e( 'Click to remove this stopword', 'relevanssi' ); ?>" />
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p style="margin: 0; padding: 12px; font-style: italic; color: #646970; text-align: center;"><?php esc_html_e( 'No custom stopwords defined yet.', 'relevanssi' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="rlv-control-row">
				<div class="rlv-actions-left">
					<input type="submit" id="repopulatestopwords" name="repopulatestopwords" value="<?php esc_attr_e( 'Restore Defaults', 'relevanssi' ); ?>" class="button button-secondary" />

					<?php if ( ! empty( $stopwords ) ) : ?>
						<button type="button" class="button button-secondary rlv-copy-trigger" data-clipboard-text="<?php echo esc_attr( $export_list ); ?>">
							<span class="dashicons dashicons-clipboard"></span>
							<span class="rlv-copy-text"><?php esc_html_e( 'Copy List', 'relevanssi' ); ?></span>
						</button>
					<?php endif; ?>
				</div>

				<div class="rlv-actions-right">
					<input type="submit" id="removeallstopwords" name="removeallstopwords" value="<?php esc_attr_e( 'Clear All', 'relevanssi' ); ?>" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently clear all stopwords from your database?', 'relevanssi' ) ); ?>');" />
				</div>
			</div>
		</div>

		<script>
			document.addEventListener('DOMContentLoaded', function() {
	// Select all possible stopword copy triggers on the page
	var copyButtons = document.querySelectorAll('.rlv-copy-trigger, .rlv-body-copy-trigger');

	copyButtons.forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();

			var textToCopy  = this.getAttribute('data-clipboard-text');
			var textSpan    = this.querySelector('.rlv-copy-text, .rlv-body-copy-text');
			if (!textSpan || !textToCopy) return;

			var originalText = textSpan.textContent;

			// Visual feedback UI updates
			function showSuccessFeedback() {
				textSpan.textContent = '<?php echo esc_js( __( 'Copied!', 'relevanssi' ) ); ?>';
				btn.classList.add('updated');
				setTimeout(function() {
					textSpan.textContent = originalText;
					btn.classList.remove('updated');
				}, 2000);
			}

			// Pipeline 1: Modern Async Clipboard API (Requires HTTPS or localhost)
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(textToCopy)
					.then(showSuccessFeedback)
					.catch(function(err) {
						console.error('Modern copy pipeline failed: ', err);
					});
			} else {
				// Pipeline 2: Legacy Fallback for Custom HTTP Dev Domains (e.g., http://site.test)
				var textarea = document.createElement('textarea');
				textarea.value = textToCopy;

				// Prevent page jumping by clipping the position elements off-screen
				textarea.style.position = 'fixed';
				textarea.style.top      = '0';
				textarea.style.left     = '0';
				textarea.style.opacity  = '0';

				document.body.appendChild(textarea);
				textarea.focus();
				textarea.select();

				try {
					var successful = document.execCommand('copy');
					if (successful) {
						showSuccessFeedback();
					} else {
						console.error('Fallback copy transaction execution returned false');
					}
				} catch (err) {
					console.error('Fallback copy runtime command failed: ', err);
				}

				document.body.removeChild(textarea);
			}
		});
	});
});
		</script>
		<?php
	}

	/**
	 * Custom intercept pass verifying if a stopword database mutation action occurs.
	 * Preserves backend hooks by passing execution tracking back to core POST save routines.
	 *
	 * @param array $request The raw administration payload matrix from $_POST.
	 * @return bool True if an active stopword tracking parameter is resolved, false otherwise.
	 */
	public function save( array $request ): bool {
		$actions = array( 'addstopword', 'removestopword', 'removeallstopwords', 'repopulatestopwords' );
		foreach ( $actions as $action ) {
			if ( isset( $request[ $action ] ) ) {
				return true;
			}
		}
		return false;
	}
}