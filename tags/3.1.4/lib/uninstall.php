<?php

function relevanssi_clear_database_tables() {
	global $wpdb;
	
	wp_clear_scheduled_hook('relevanssi_truncate_cache');

	$relevanssi_table = $wpdb->prefix . "relevanssi";	
	$stopword_table = $wpdb->prefix . "relevanssi_stopwords";
	$log_table = $wpdb->prefix . "relevanssi_log";
	$relevanssi_cache = $wpdb->prefix . 'relevanssi_cache';
	$relevanssi_excerpt_cache = $wpdb->prefix . 'relevanssi_excerpt_cache';
	
	if($wpdb->get_var("SHOW TABLES LIKE '$stopword_table'") == $stopword_table) {
		$sql = "DROP TABLE $stopword_table";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_table'") == $relevanssi_table) {
		$sql = "DROP TABLE $relevanssi_table";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$log_table'") == $log_table) {
		$sql = "DROP TABLE $log_table";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_cache'") == $relevanssi_cache) {
		$sql = "DROP TABLE $relevanssi_cache";
		$wpdb->query($sql);
	}

	if($wpdb->get_var("SHOW TABLES LIKE '$relevanssi_excerpt_cache'") == $relevanssi_excerpt_cache) {
		$sql = "DROP TABLE $relevanssi_excerpt_cache";
		$wpdb->query($sql);
	}
	
	echo '<div id="message" class="updated fade"><p>' . __("Data wiped clean, you can now delete the plugin.", "relevanssi") . '</p></div>';
}

?>