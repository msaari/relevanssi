<?php
/**
 * /lib/tabs/excerpts-tab.php
 *
 * Prints out the Excerpts tab in Relevanssi settings.
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Prints out the excerpts tab in Relevanssi settings.
 */
function relevanssi_excerpts_tab() {
	$excerpts               = get_option( 'relevanssi_excerpts' );
	$excerpt_length         = get_option( 'relevanssi_excerpt_length' );
	$excerpt_type           = get_option( 'relevanssi_excerpt_type' );
	$excerpt_allowable_tags = get_option( 'relevanssi_excerpt_allowable_tags' );
	$excerpt_custom_fields  = get_option( 'relevanssi_excerpt_custom_fields' );
	$highlight              = get_option( 'relevanssi_highlight' );
	$txt_col                = get_option( 'relevanssi_txt_col' );
	$bg_col                 = get_option( 'relevanssi_bg_col' );
	$css                    = get_option( 'relevanssi_css' );
	$class                  = get_option( 'relevanssi_class' );
	$highlight_title        = get_option( 'relevanssi_hilite_title' );
	$highlight_docs         = get_option( 'relevanssi_highlight_docs' );
	$highlight_coms         = get_option( 'relevanssi_highlight_comments' );
	$show_matches           = get_option( 'relevanssi_show_matches' );
	$show_matches_text      = get_option( 'relevanssi_show_matches_text' );
	$index_fields           = get_option( 'relevanssi_index_fields' );
	$expand_highlights      = get_option( 'relevanssi_expand_highlights' );

	if ( '#' !== substr( $txt_col, 0, 1 ) ) {
		$txt_col = '#' . $txt_col;
	}
	$txt_col = relevanssi_sanitize_hex_color( $txt_col );
	if ( '#' !== substr( $bg_col, 0, 1 ) ) {
		$bg_col = '#' . $bg_col;
	}
	$bg_col = relevanssi_sanitize_hex_color( $bg_col );

	$show_matches_text = stripslashes( $show_matches_text );

	$excerpts              = relevanssi_check( $excerpts );
	$excerpt_custom_fields = relevanssi_check( $excerpt_custom_fields );
	$highlight_title       = relevanssi_check( $highlight_title );
	$highlight_docs        = relevanssi_check( $highlight_docs );
	$highlight_coms        = relevanssi_check( $highlight_coms );
	$show_matches          = relevanssi_check( $show_matches );
	$expand_highlights     = relevanssi_check( $expand_highlights );
	$excerpt_chars         = relevanssi_select( $excerpt_type, 'chars' );
	$excerpt_words         = relevanssi_select( $excerpt_type, 'words' );
	$highlight_none        = relevanssi_select( $highlight, 'no' );
	$highlight_mark        = relevanssi_select( $highlight, 'mark' );
	$highlight_em          = relevanssi_select( $highlight, 'em' );
	$highlight_strong      = relevanssi_select( $highlight, 'strong' );
	$highlight_col         = relevanssi_select( $highlight, 'col' );
	$highlight_bgcol       = relevanssi_select( $highlight, 'bgcol' );
	$highlight_style       = relevanssi_select( $highlight, 'css' );
	$highlight_class       = relevanssi_select( $highlight, 'class' );

	$txt_col_display = 'screen-reader-text';
	$bg_col_display  = 'screen-reader-text';
	$css_display     = 'screen-reader-text';
	$class_display   = 'screen-reader-text';

	if ( 'col' === $highlight ) {
		$txt_col_display = '';
	}
	if ( 'bgcol' === $highlight ) {
		$bg_col_display = '';
	}
	if ( 'css' === $highlight ) {
		$css_display = '';
	}
	if ( 'class' === $highlight ) {
		$class_display = '';
	}

	?>

	<h2 id="excerpts"><?php esc_html_e( 'Custom excerpts/snippets', 'relevanssi' ); ?></h2>

	<table class="form-table" role="presentation">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Custom search result snippets', 'relevanssi' ); ?>
		</th>
		<td>
			<label >
				<input type='checkbox' name='relevanssi_excerpts' id='relevanssi_excerpts' <?php echo esc_html( $excerpts ); ?> />
				<?php esc_html_e( 'Create custom search result snippets', 'relevanssi' ); ?>
			</label>
		<p class="description"><?php esc_html_e( 'Only enable this if you actually use the custom excerpts.', 'relevanssi' ); ?></p>
		<?php
		$theme    = wp_get_theme();
		$template = $theme->get( 'Template' );
		if ( 'divi' === strtolower( $template ) ) :
			?>
			<?php // Translators: %1$s opens the link, %2$s closes it. ?>
			<p class="important"><?php printf( esc_html__( 'Looks like you are using Divi. In order to use custom excerpts with Divi, you need to make some changes to your templates. %1$sSee instructions here%2$s.', 'relevanssi' ), '<a href="https://www.relevanssi.com/knowledge-base/divi-page-builder-and-cleaner-excerpts/">', '</a>' ); ?></p>
		<?php endif; ?>
		</td>
	</tr>
	<tr id="tr_excerpt_length"
	<?php
	if ( empty( $excerpts ) ) {
		echo "class='relevanssi_disabled'";
	}
	?>
	>
		<th scope="row">
			<label for='relevanssi_excerpt_length'><?php esc_html_e( 'Length of the snippet', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_excerpt_length' id='relevanssi_excerpt_length' size='4' value='<?php echo esc_attr( $excerpt_length ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
			<label for="relevanssi_excerpt_type" class="screen-reader-text"><?php esc_html_e( 'Excerpt length type', 'relevanssi' ); ?></label>
			<select name='relevanssi_excerpt_type' id='relevanssi_excerpt_type'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			>
				<option value='chars' <?php echo esc_html( $excerpt_chars ); ?>><?php esc_html_e( 'characters', 'relevanssi' ); ?></option>
				<option value='words' <?php echo esc_html( $excerpt_words ); ?>><?php esc_html_e( 'words', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( "Using words is much faster than characters. Don't use characters, unless you have a really good reason and your posts are short.", 'relevanssi' ); ?></p>
		</td>
	</tr>
	<?php
	if ( function_exists( 'relevanssi_form_max_excerpts' ) ) {
		relevanssi_form_max_excerpts( $excerpts );
	}
	?>
	<tr id="tr_excerpt_allowable_tags"
		<?php
		if ( empty( $excerpts ) ) {
			echo "class='relevanssi_disabled'";
		}
		?>
		>
		<th scope="row">
			<label for='relevanssi_excerpt_allowable_tags'><?php esc_html_e( 'Allowable tags in excerpts', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_excerpt_allowable_tags' id='relevanssi_excerpt_allowable_tags' size='60' value='<?php echo esc_attr( $excerpt_allowable_tags ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
			<p class="description"><?php esc_html_e( 'List all tags you want to allow in excerpts. For example: &lt;p&gt;&lt;a&gt;&lt;strong&gt;.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="tr_excerpt_custom_fields"
		<?php
		if ( empty( $excerpts ) ) {
			echo "class='relevanssi_disabled'";
		}
		?>
		>
		<th scope="row">
			<?php esc_html_e( 'Use custom fields for excerpts', 'relevanssi' ); ?>
		</th>
		<td>
			<label>
				<input type='checkbox' name='relevanssi_excerpt_custom_fields' id='relevanssi_excerpt_custom_fields' <?php echo esc_html( $excerpt_custom_fields ); ?>
				<?php
				if ( empty( $excerpts ) || empty( $index_fields ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Use custom field content for building excerpts', 'relevanssi' ); ?>
			</label>
		<p class="description"><?php esc_html_e( 'Use the custom fields setting for indexing for excerpt-making as well. Enabling this option will show custom field content in Relevanssi-generated excerpts.', 'relevanssi' ); ?>
		<?php
		if ( RELEVANSSI_PREMIUM ) {
			esc_html_e( 'Enable this option to use PDF content for excerpts.', 'relevanssi' );
		}
		?>
		</p>

		<p class="description"><?php esc_html_e( 'Current custom field setting', 'relevanssi' ); ?>:
		<?php
		if ( 'visible' === $index_fields ) {
			esc_html_e( 'all visible custom fields', 'relevanssi' );
		} elseif ( 'all' === $index_fields ) {
			esc_html_e( 'all custom fields', 'relevanssi' );
		} elseif ( ! empty( $index_fields ) ) {
			printf( '<code>%s</code>', esc_html( $index_fields ) );
		} elseif ( RELEVANSSI_PREMIUM ) {
			esc_html_e( 'Just PDF content', 'relevanssi' );
		} else {
			esc_html_e( 'None selected', 'relevanssi' );
		}
		?>
		</p>
		</td>
	</tr>
	</table>

	<h2><?php esc_html_e( 'Search hit highlighting', 'relevanssi' ); ?></h2>

	<table id="relevanssi_highlighting" class="form-table
	<?php
	if ( empty( $excerpts ) ) {
		echo 'relevanssi_disabled';
	}
	?>
	" role="presentation">
	<tr>
		<th scope="row">
			<label for='relevanssi_highlight'><?php esc_html_e( 'Highlight type', 'relevanssi' ); ?></label>
		</th>
		<td>
			<select name='relevanssi_highlight' id='relevanssi_highlight'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			>
				<option value='no' <?php echo esc_html( $highlight_none ); ?>><?php esc_html_e( 'No highlighting', 'relevanssi' ); ?></option>
				<option value='mark' <?php echo esc_html( $highlight_mark ); ?>>&lt;mark&gt;</option>
				<option value='em' <?php echo esc_html( $highlight_em ); ?>>&lt;em&gt;</option>
				<option value='strong' <?php echo esc_html( $highlight_strong ); ?>>&lt;strong&gt;</option>
				<option value='col' <?php echo esc_html( $highlight_col ); ?>><?php esc_html_e( 'Text color', 'relevanssi' ); ?></option>
				<option value='bgcol' <?php echo esc_html( $highlight_bgcol ); ?>><?php esc_html_e( 'Background color', 'relevanssi' ); ?></option>
				<option value='css' <?php echo esc_html( $highlight_style ); ?>><?php esc_html_e( 'CSS Style', 'relevanssi' ); ?></option>
				<option value='class' <?php echo esc_html( $highlight_class ); ?>><?php esc_html_e( 'CSS Class', 'relevanssi' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( 'Requires custom snippets to work.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr id="tr_relevanssi_txt_col" class='<?php echo esc_attr( $txt_col_display ); ?>'>
		<th scope="row">
			<?php esc_html_e( 'Text color', 'relevanssi' ); ?>
		</th>
		<td>
			<input type='text' name='relevanssi_txt_col' id='relevanssi_txt_col' size='7' class="color-field" data-default-color="#ff0000" value='<?php echo esc_attr( $txt_col ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
		</td>
	</tr>
	<tr id="tr_relevanssi_bg_col" class=' <?php echo esc_attr( $bg_col_display ); ?>'>
		<th scope="row">
			<?php esc_html_e( 'Background color', 'relevanssi' ); ?>
		</th>
		<td>
			<input type='text' name='relevanssi_bg_col' id='relevanssi_bg_col' size='7' class="color-field" data-default-color="#ffaf75" value='<?php echo esc_attr( $bg_col ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
		</td>
	</tr>
	<tr id="tr_relevanssi_css" class=' <?php echo esc_attr( $css_display ); ?>'>
		<th scope="row">
			<label for='relevanssi_css'><?php esc_html_e( 'CSS style for highlights', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_css' id='relevanssi_css' size='60' value='<?php echo esc_attr( $css ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
			<?php // Translators: %s is a <span> tag. ?>
			<p class="description"><?php printf( esc_html__( 'The highlights will be wrapped in a %s with this CSS in the style parameter.', 'relevanssi' ), '&lt;span&gt;' ); ?></p>
		</td>
	</tr>
	<tr id="tr_relevanssi_class" class=' <?php echo esc_attr( $class_display ); ?>'>
		<th scope="row">
			<label for='relevanssi_class'><?php esc_html_e( 'CSS class for highlights', 'relevanssi' ); ?></label>
		</th>
		<td>
			<input type='text' name='relevanssi_class' id='relevanssi_class' size='60' value='<?php echo esc_attr( $class ); ?>'
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			/>
			<?php // Translators: %s is a <span> tag. ?>
			<p class="description"><?php printf( esc_html__( 'The highlights will be wrapped in a %s with this class.', 'relevanssi' ), '&lt;span&gt;' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Highlight in titles', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_hilite_title'>
				<input type='checkbox' name='relevanssi_hilite_title' id='relevanssi_hilite_title' <?php echo esc_html( $highlight_title ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Highlight query terms in titles', 'relevanssi' ); ?>
			</label>
		<?php // Translators: %1$s is 'the_title()', %2$s is 'relevanssi_the_title()'. ?>
		<p class="description"><?php printf( esc_html__( 'Highlights in titles require changes to the search results template. You need to replace %1$s in the search results template with %2$s. For more information, see the contextual help.', 'relevanssi' ), '<code>the_title()</code>', '<code>relevanssi_the_title()</code>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Highlight in documents', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_highlight_docs'>
				<input type='checkbox' name='relevanssi_highlight_docs' id='relevanssi_highlight_docs' <?php echo esc_html( $highlight_docs ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Highlight query terms in documents', 'relevanssi' ); ?>
			</label>
		<?php // Translators: %s is 'highlight'. ?>
		<p class="description"><?php printf( esc_html__( 'Highlights hits when user opens the post from search results. This requires an extra parameter (%s) to the links from the search results pages, which Relevanssi should add automatically.', 'relevanssi' ), '<code>highlight</code>' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Highlight in comments', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_highlight_comments'>
				<input type='checkbox' name='relevanssi_highlight_comments' id='relevanssi_highlight_comments' <?php echo esc_html( $highlight_coms ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Highlight query terms in comments', 'relevanssi' ); ?>
			</label>
		<p class="description"><?php esc_html_e( 'Highlights hits in comments when user opens the post from search results.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Expand highlights', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_expand_highlights'>
				<input type='checkbox' name='relevanssi_expand_highlights' id='relevanssi_expand_highlights' <?php echo esc_html( $expand_highlights ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Expand highlights to cover full words', 'relevanssi' ); ?>
			</label>
		<p class="description"><?php esc_html_e( 'When a highlight matches part of the word, if this option is enabled, the highlight will be expanded to highlight the whole word.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	</table>

	<h2><?php esc_html_e( 'Breakdown of search results', 'relevanssi' ); ?></h2>

	<table id="relevanssi_breakdown" class="form-table
	<?php
	if ( empty( $excerpts ) ) {
		echo 'relevanssi_disabled';
	}
	?>
	" role="presentation">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Breakdown of search hits in excerpts', 'relevanssi' ); ?>
		</th>
		<td>
			<label for='relevanssi_show_matches'>
				<input type='checkbox' name='relevanssi_show_matches' id='relevanssi_show_matches' <?php echo esc_html( $show_matches ); ?>
				<?php
				if ( empty( $excerpts ) ) {
					echo "disabled='disabled'";
				}
				?>
				/>
				<?php esc_html_e( 'Show the breakdown of search hits in the excerpts.', 'relevanssi' ); ?>
			</label>
		<p class="description"><?php esc_html_e( 'Requires custom snippets to work.', 'relevanssi' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for='relevanssi_show_matches_text'><?php esc_html_e( 'The breakdown format', 'relevanssi' ); ?></label>
		</th>
		<td>
			<textarea name='relevanssi_show_matches_text' id='relevanssi_show_matches_text' cols="80" rows="4"
			<?php
			if ( empty( $excerpts ) ) {
				echo "disabled='disabled'";
			}
			?>
			><?php echo esc_attr( $show_matches_text ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Use %body%, %title%, %categories%, %tags%, %taxonomies%, %comments%, %customfields%, %author%, %excerpt% and %mysqlcolumns% to display the number of hits (in different parts of the post), %total% for total hits, %score% to display the document weight and %terms% to show how many hits each search term got.', 'relevanssi' ); /* phpcs:ignore WordPress.WP.I18n */ ?></p>
		</td>
	</tr>
	</table>


		<?php
}
