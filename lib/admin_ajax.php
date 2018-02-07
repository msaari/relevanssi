<?php

add_action( 'wp_ajax_relevanssi_truncate_index', 'relevanssi_truncate_index_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_index_posts', 'relevanssi_index_posts_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_count_posts', 'relevanssi_count_posts_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_count_missing_posts', 'relevanssi_count_missing_posts_ajax_wrapper' );
add_action( 'wp_ajax_relevanssi_list_categories', 'relevanssi_list_categories' );

function relevanssi_truncate_index_ajax_wrapper() {
    $response = relevanssi_truncate_index();
    echo json_encode($response);
    wp_die();
}

function relevanssi_index_posts_ajax_wrapper() {
    $completed = absint( $_POST['completed'] );
    $total = absint( $_POST['total'] );
    $offset = absint( $_POST['offset'] );
    $limit = absint( $_POST['limit'] );
    $extend = strval($_POST['extend']);
    $extend === 'true' ? $extend = true : $extend = false;

    if ($limit < 1) $limit = 1;

    $response = array();
    
    $is_ajax = true;
    $verbose = false;
    //$limit = apply_filters('relevanssi_ajax_indexing_limit', 50);
    if ($extend) $offset = true;
    
    $indexing_response = relevanssi_build_index($offset, $verbose, $limit, $is_ajax);
    
    if ($indexing_response['indexing_complete']) {
        $response['completed'] = "done";
        $response['percentage'] = 100;
        $completed += $indexing_response['indexed'];
        $response['total_posts'] = $completed;
        $processed = $total;
    } 
    else {
        $completed += $indexing_response['indexed'];
        $response['completed'] = $completed;
    
        if ($offset === true) {
            $processed = $completed;
        }
        else {
            $offset = $offset + $limit;
            $processed = $offset;
        }
        
        $total > 0 ? $response['percentage'] = $processed / $total * 100 : $response['percentage'] = 0;
    }

    $response['feedback'] = sprintf(_n("Indexed %d post (total %d), processed %d / %d.", "Indexed %d posts (total %d), processed %d / %d.", $indexing_response['indexed'], 'relevanssi'), $indexing_response['indexed'], $completed, $processed, $total) . "\n";
    $response['offset'] = $offset;

    echo json_encode($response);
    wp_die();
}

function relevanssi_count_posts_ajax_wrapper() {
    $count = relevanssi_count_total_posts();
    echo json_encode($count);
    wp_die();
}

function relevanssi_count_missing_posts_ajax_wrapper() {
    $count = relevanssi_count_missing_posts();
    echo json_encode($count);
    wp_die();
}

function relevanssi_list_categories() {
    $categories = get_categories(array('taxonomy' => 'category', 'hide_empty' => false));
    echo json_encode($categories);
    wp_die();
}

?>