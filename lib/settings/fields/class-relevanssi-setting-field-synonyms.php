<?php
/**
 * /lib/settings/fields/class-relevanssi-setting-field-synonyms.php
 *
 * Implements the configuration-driven Synonyms field rendering engine.
 *
 * @package Relevanssi
 * @author   Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 */

/**
 * Class Relevanssi_Setting_Field_Synonyms
 *
 * Manages linguistic mapping overrides, evaluation of implicit engine operators,
 * and Polylang language state restrictions.
 */
class Relevanssi_Setting_Field_Synonyms extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Overrides the abstract class label output.
	 * Ensures the label element correctly points to the explicit ID of the textarea.
	 *
	 * @return void Writes sanitized label elements straight to the active view stream.
	 */
	protected function render_label() {
		$label = $this->config['label'] ?? '';

		if ( empty( $label ) ) {
			return;
		}

		printf(
			'<label for="relevanssi_synonyms">%s</label>',
			wp_kses_post( $label )
		);
	}

	/**
	 * Main input workspace entry point.
	 *
	 * @return void Writes layout elements straight onto the active administration view.
	 */
	protected function render_input() {
		$is_premium = defined( 'RELEVANSSI_PREMIUM' ) && RELEVANSSI_PREMIUM;

		if ( class_exists( 'Polylang', false ) && function_exists( 'pll_current_language' ) && ! pll_current_language() ) {
			?>
			<p class="description" style="color: #d63638; font-weight: 500;">
				<?php esc_html_e( 'You are using Polylang and are in "Show all languages" mode. Please select a specific language from the admin toolbar before adjusting your synonym settings.', 'relevanssi' ); ?>
			</p>
			<?php
			return;
		}

		$current_language  = function_exists( 'relevanssi_get_current_language' ) ? relevanssi_get_current_language() : 'all';
		$synonyms_array    = get_option( 'relevanssi_synonyms', array() );
		$raw_synonyms      = isset( $synonyms_array[ $current_language ] ) ? $synonyms_array[ $current_language ] : '';
		$synonyms_string   = ! empty( $raw_synonyms ) ? str_replace( ';', "\n", $raw_synonyms ) : '';
		$synonyms_disabled = false;

		$operator = get_option( 'relevanssi_implicit_operator', 'AND' );
		if ( 'AND' === $operator ) {
			$index_synonyms = get_option( 'relevanssi_index_synonyms', 'off' );
			if ( 'on' !== $index_synonyms ) {
				$synonyms_disabled = true;
			}
		}

		if ( $synonyms_disabled ) :
			?>
			<div class="relevanssi-notice relevanssi-notice-warning" style="margin-top: 0; margin-bottom: 16px;">
				<p>
					<strong><?php esc_html_e( 'Synonyms Locked:', 'relevanssi' ); ?></strong>
					<?php esc_html_e( 'Synonyms are inactive when the searching operator is set to AND.', 'relevanssi' ); ?>
				</p>
				<?php if ( $is_premium ) : ?>
					<p class="description" style="margin-top: 4px;">
						<?php esc_html_e( "Enable 'Synonym Indexing' on the Indexing tab and rebuild your index to make synonyms work with AND searches.", 'relevanssi' ); ?>
					</p>
				<?php else : ?>
					<p class="description" style="margin-top: 4px;">
						<?php esc_html_e( 'Relevanssi Premium allows you to index synonyms, which makes text expansions fully functional during restrictive AND queries.', 'relevanssi' ); ?>
					</p>
				<?php endif; ?>
			</div>
			<?php
		endif;
		?>
		<p class="description" style="margin-bottom: 12px;">
			<?php esc_html_e( 'Add synonym rules below (one per line) to expand keyword matching in searches.', 'relevanssi' ); ?>
		</p>

		<?php
		$placeholder = ! empty( $this->config['placeholder'] ) ? ' placeholder="' . esc_attr( $this->config['placeholder'] ) . '"' : '';
		?>
		<textarea
			name="relevanssi_synonyms"
			id="relevanssi_synonyms"
			rows="9"
			style="width: 100%; max-width: 600px; font-family: monospace;"
			<?php echo $placeholder; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php disabled( $synonyms_disabled ); ?>><?php echo esc_textarea( $synonyms_string ); ?></textarea>
		<?php
	}

	/**
	 * Intercept and map incoming post arrays before option payload validation hooks.
	 *
	 * Since option values are structured within dynamic multidimensional lang arrays,
	 * we load the root config tree, slice out the line values, and overwrite the specific node.
	 *
	 * @param array $request Raw incoming administrative form POST array data parameters.
	 * @return bool True if options updating processes passed validation arrays successfully.
	 */
	public function save( array $request ): bool {
		if ( ! isset( $request['relevanssi_synonyms'] ) ) {
			return false;
		}

		$current_language = function_exists( 'relevanssi_get_current_language' ) ? relevanssi_get_current_language() : 'all';
		$synonyms_array   = get_option( 'relevanssi_synonyms', array() );

		if ( ! is_array( $synonyms_array ) ) {
			$synonyms_array = array();
		}

		// Convert clean line-breaks back into raw storage configuration separators.
		$raw_textarea = sanitize_textarea_field( wp_unslash( $request['relevanssi_synonyms'] ) );
		$clean_string = str_replace( array( "\r\n", "\r", "\n" ), ';', $raw_textarea );

		$synonyms_array[ $current_language ] = $clean_string;
		$autoload_state                      = $this->config['autoload'] ?? true;

		return update_option( 'relevanssi_synonyms', $synonyms_array, $autoload_state );
	}
}