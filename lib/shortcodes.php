<?php

add_shortcode('search', 'relevanssi_shortcode');
add_shortcode('noindex', 'relevanssi_noindex_shortcode');
add_shortcode('searchform', 'relevanssi_search_form'); 

function relevanssi_shortcode($atts, $content, $name) {
	global $wpdb;

	extract(shortcode_atts(array('term' => false, 'phrase' => 'not'), $atts));
	
	if ($term != false) {
		$term = urlencode(relevanssi_strtolower($term));
	}
	else {
		$term = urlencode(strip_tags(relevanssi_strtolower($content)));
	}
	
	if ($phrase != 'not') {
		$term = '%22' . $term . '%22';	
	}
	
	$link = get_bloginfo('url') . "/?s=$term";
	
	$pre  = "<a href='$link'>";
	$post = "</a>";

	return $pre . do_shortcode($content) . $post;
}

function relevanssi_noindex_shortcode($atts, $content) {
	// When in general use, make the shortcode disappear.
	return do_shortcode($content);
}

function relevanssi_noindex_shortcode_indexing($atts, $content) {
	// When indexing, make the text disappear.
	return '';
}

function relevanssi_search_form($atts) {
	$form = get_search_form(false); 
	if (is_array($atts)) {
		$additional_fields = array();
		foreach ($atts as $key => $value) {
			$key = esc_attr($key);
			$value = esc_attr($value);
			$additional_fields[] = "<input type='hidden' name='$key' value='$value' />";
		}
		$form = str_replace("</form>", implode("\n", $additional_fields) . "</form>", $form);
	}
	return $form;
}

?>