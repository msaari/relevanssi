<?php
/**
 * /lib/class-relevanssi-taxonomy-walker.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * A taxonomy walker used in Relevanssi interface.
 *
 * This is needed for wp_terms_checklist() in the Relevanssi admin interface to
 * control the way the taxonomies are listed.
 */
class Relevanssi_Taxonomy_Walker extends Walker_Category_Checklist {
	/**
	 * Name of the input element.
	 *
	 * @var string $name Name of the input element.
	 */
	public $name;

	/**
	 * Creates a single element of the list.
	 *
	 * @see Walker::start_el()
	 *
	 * @param string $output   Used to append additional content (passed by reference).
	 * @param object $category Category data object.
	 * @param int    $depth    Optional. Depth of category in reference to parents. Default 0.
	 * @param array  $args     Optional. An array of arguments. See wp_list_categories(). Default empty array.
	 * @param int    $id       Optional. ID of the current category. Default 0.
	 */
	public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		if ( empty( $args['taxonomy'] ) ) {
			$taxonomy = 'category';
		} else {
			$taxonomy = $args['taxonomy'];
		}

		$name = $this->name;

		if ( ! isset( $args['popular_cats'] ) ) {
			$args['popular_cats'] = array();
		}

		if ( ! isset( $args['selected_cats'] ) ) {
			$args['selected_cats'] = array();
		}

		$class       = '';
		$inner_class = '';

		if ( ! empty( $args['list_only'] ) ) {
			$aria_checked = 'false';
			$inner_class  = 'category';

			/** This filter is documented in wp-includes/category-template.php */
			$output .= "\n" . '<li' . $class . '>' .
			'<div class="' . $inner_class . '" data-term-id=' . $category->term_id .
			' tabindex="0" role="checkbox" aria-checked="' . $aria_checked . '">' .
			esc_html( apply_filters( 'the_category', $category->name ) ) . '</div>';
		} else {
			/** This filter is documented in wp-includes/category-template.php */
			$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" .
			'<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="' . $name . '[]" id="in-' . $taxonomy . '-' . $category->term_id . '"' .
			checked( in_array( intval( $category->term_id ), $args['selected_cats'], true ), true, false ) .
			disabled( empty( $args['disabled'] ), false, false ) . ' /> ' .
			esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
		}
	}
}
