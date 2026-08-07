<?php
/**
 * Basic weight score multipliers element engine component mapper.
 *
 * @package Relevanssi
 */

/**
 * Class Relevanssi_Setting_Field_Weights_Table
 *
 * Randers the field weights table
 */
class Relevanssi_Setting_Field_Weights_Table extends Relevanssi_Abstract_Setting_Field {

	/**
	 * Builds the inner scoring table layout inside the standard table cell structure.
	 *
	 * @return void
	 */
	protected function render_input() {
		$content_boost = get_option( 'relevanssi_content_boost', '1' );
		$title_boost   = get_option( 'relevanssi_title_boost', '5' );
		$comment_boost = get_option( 'relevanssi_comment_boost', '0.75' );
		?>
		<fieldset class="relevanssi-settings-fieldset" style="border: none; padding: 0; margin: 0;">
			<legend class="screen-reader-text" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;"><?php esc_html_e( 'Weight score multipliers', 'relevanssi' ); ?></legend>

			<table class="widefat striped content-types-table" id="basic_scores_weights_table" style="width: 100%; max-width: 500px; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: none; table-layout: fixed;">
				<thead>
					<tr>
						<th style="font-weight: 600; padding: 8px 12px; width: 83%;"><?php esc_html_e( 'Page Element', 'relevanssi' ); ?></th>
						<th style="font-weight: 600; padding: 8px 12px; width: 17%; text-align: center;"><?php esc_html_e( 'Weight', 'relevanssi' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="padding: 8px 12px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
							<label for="relevanssi_content_boost">
								<strong><?php esc_html_e( 'Main Body Content', 'relevanssi' ); ?></strong>
							</label>
						</td>
						<td style="text-align: center; vertical-align: middle; padding: 8px 12px;">
							<input type='text' name='relevanssi_content_boost' id='relevanssi_content_boost' size='4' value='<?php echo esc_attr( $content_boost ); ?>' style="text-align: center;" />
						</td>
					</tr>
					<tr>
						<td style="padding: 8px 12px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
							<label for="relevanssi_title_boost">
								<strong><?php esc_html_e( 'Titles', 'relevanssi' ); ?></strong>
							</label>
						</td>
						<td style="text-align: center; vertical-align: middle; padding: 8px 12px;">
							<input type='text' name='relevanssi_title_boost' id='relevanssi_title_boost' size='4' value='<?php echo esc_attr( $title_boost ); ?>' style="text-align: center;" />
						</td>
					</tr>
					<tr>
						<td style="padding: 8px 12px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
							<label for="relevanssi_comment_boost">
								<strong><?php esc_html_e( 'User Comments', 'relevanssi' ); ?></strong>
							</label>
						</td>
						<td style="text-align: center; vertical-align: middle; padding: 8px 12px;">
							<input type='text' name='relevanssi_comment_boost' id='relevanssi_comment_boost' size='4' value='<?php echo esc_attr( $comment_boost ); ?>' style="text-align: center;" />
						</td>
					</tr>
					<?php
					if ( ! defined( 'RELEVANSSI_PREMIUM' ) || ! RELEVANSSI_PREMIUM ) {
						if ( function_exists( 'relevanssi_form_tag_weight' ) ) {
							relevanssi_form_tag_weight();
						}
					}
					?>
				</tbody>
			</table>
		</fieldset>
		<?php
	}

	/**
	 * Intercepts post submission variables and saves them sequentially.
	 *
	 * @param array $request Post submission server request payload matrix.
	 * @return bool True if operations successfully update all settings values.
	 */
	public function save( array $request ): bool {
		$autoload_state = $this->config['autoload'] ?? true;

		$content_boost = isset( $request['relevanssi_content_boost'] ) ? sanitize_text_field( $request['relevanssi_content_boost'] ) : '1';
		$title_boost   = isset( $request['relevanssi_title_boost'] ) ? sanitize_text_field( $request['relevanssi_title_boost'] ) : '5';
		$comment_boost = isset( $request['relevanssi_comment_boost'] ) ? sanitize_text_field( $request['relevanssi_comment_boost'] ) : '0.75';

		update_option( 'relevanssi_content_boost', $content_boost, $autoload_state );
		update_option( 'relevanssi_title_boost', $title_boost, $autoload_state );
		update_option( 'relevanssi_comment_boost', $comment_boost, $autoload_state );

		if ( isset( $request['relevanssi_weight_post_tag'] ) || isset( $request['relevanssi_weight_category'] ) ) {
			$taxonomy_weights = get_option( 'relevanssi_post_type_weights', array() );
			if ( ! is_array( $taxonomy_weights ) ) {
				$taxonomy_weights = array();
			}

			if ( isset( $request['relevanssi_weight_post_tag'] ) ) {
				$taxonomy_weights['post_tag'] = sanitize_text_field( $request['relevanssi_weight_post_tag'] );
			}
			if ( isset( $request['relevanssi_weight_category'] ) ) {
				$taxonomy_weights['category'] = sanitize_text_field( $request['relevanssi_weight_category'] );
			}

			update_option( 'relevanssi_post_type_weights', $taxonomy_weights, $autoload_state );
		}

		return true;
	}
}