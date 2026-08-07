<?php
/**
 * Relevanssi_Setting_Field_Body_Stopwords_Manager class file.
 *
 * Handles rendering and processing logic for content-specific premium body stopwords.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Body_Stopwords_Manager
 */
class Relevanssi_Setting_Field_Body_Stopwords_Manager extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Safely interfaces premium procedural layout actions or builds a clear fallback notice.
	 * Utilizes clean, unified CSS layout definitions to align with core stopword wrappers.
	 *
	 * @return void
	 */
	protected function render_input() {
		?>
		<div class="rlv-manager-wrapper">
			<?php if ( function_exists( 'relevanssi_show_body_stopwords' ) ) : ?>
				<div class="rlv-premium-body-stopwords-container">
					<?php relevanssi_show_body_stopwords(); ?>
				</div>
			<?php else : ?>
				<div class="rlv-input-group">
					<textarea disabled placeholder="<?php esc_attr_e( 'Requires Relevanssi Premium...', 'relevanssi' ); ?>"></textarea>
					<button disabled type="button" class="button button-secondary disabled"><?php esc_html_e( 'Add', 'relevanssi' ); ?></button>
				</div>
				<p class="description">
					<?php esc_html_e( 'Content stopwords are an advanced premium feature. They allow you to exclude words only from the post body text, while keeping them fully searchable if they appear inside post titles, categories, custom fields, or tags. To unlock this feature, upgrade to Relevanssi Premium.', 'relevanssi' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Intercepts request parameters sent by body stopword mutation buttons.
	 *
	 * @param array $request The raw submission payload from $_POST.
	 * @return bool True if a premium mutation key is detected, false otherwise.
	 */
	public function save( array $request ): bool {
		$actions = array( 'addbodystopword', 'removebodystopword', 'removeallbodystopwords', 'repopulatebodystopwords' );
		foreach ( $actions as $action ) {
			if ( isset( $request[ $action ] ) ) {
				return true;
			}
		}
		return false;
	}
}