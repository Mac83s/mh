<?php


/* AJAX Show Chart */

add_action( 'wp_ajax_nopriv_show_token_details', 'show_token_details_function' );
add_action( 'wp_ajax_show_token_details', 'show_token_details_function' );

function show_token_details_function(){

	global $wpdb;
    $token_id = strtoupper($_POST['token_id']);
    $address = get_post_meta($token_id, 'address', true);


	$terms_token_status = get_the_terms( $token_id ,  'blockchain' );
	foreach ( $terms_token_status as $term ) {					
		$blockchain = $term->slug;
	}

	
	$response .= "<style>#dexscreener-embed{position:relative;width:100%;padding-bottom:80%;}@media(min-width:1400px){#dexscreener-embed{padding-bottom:70%;}}#dexscreener-embed iframe{position:absolute;width:100%;height:100%;top:0;left:0;border:0;}</style><div id='dexscreener-embed'><iframe src='https://dexscreener.com/$blockchain/$address?embed=1&theme=light&trades=0&info=0'></iframe></div>";
	// $response .= "<style>#dexscreener-embed{position:relative;width:100%;padding-bottom:125%;}@media(min-width:1400px){#dexscreener-embed{padding-bottom:65%;}}#dexscreener-embed iframe{position:absolute;width:100%;height:100%;top:0;left:0;border:0;}</style><div id='dexscreener-embed'><iframe src='https://dexscreener.com/$blockchain/$address?embed=1&theme=dark&trades=0&info=1'></iframe></div>";
	// $response .= '<button class="show_less" data-id="' . $post_id . '">Show Less</button>';
	
	wp_send_json_success( $response );
}





// Diagnostics table update
function update_table_callback()
{
    if (!isset($_POST['table_prefix']) || !isset($_POST['table_group'])) {
        wp_send_json_error(array('error' => 'Brak wymaganych danych.'));
        return;
    }

    $table_prefix = sanitize_text_field($_POST['table_prefix']);
    $table_group = sanitize_text_field($_POST['table_group']);

    $file_path = get_stylesheet_directory() . "/files/post_ids_{$table_prefix}_{$table_group}.json";
    $locked_file_path = get_stylesheet_directory() . "/files/post_ids_{$table_prefix}_{$table_group}_locked.json";

    $post_ids = array();
    if (file_exists($file_path)) {
        $json_data = file_get_contents($file_path);
        $post_ids = json_decode($json_data, true);
    }

    if (file_exists($locked_file_path)) {
        $locked_json_data = file_get_contents($locked_file_path);
        $locked_post_ids = json_decode($locked_json_data, true);
        $post_ids = array_merge($post_ids, $locked_post_ids);
    }

    if ($post_ids === false) {
        $post_ids = array();
    }

    $current_time = current_time('timestamp');
    $data = array();

    if ($post_ids) {
        foreach ($post_ids as $post_data) {
            $last_processed_time = isset($post_data['next_update']) ? $post_data['next_update'] : 0;
            $time_diff = $last_processed_time - $current_time;
            $updated =  $time_diff . 's' ;
            $test_status =  isset($post_data['status']) ? $post_data['status'] : 'none';

            $data[] = array(
                'id' => $post_data['id'],
                'updated' => $updated,
                'test_status' => $test_status,
            );
        }
        
        $row_count = count($post_ids);

        // Sprawdź, czy plik _locked istnieje
        $status = file_exists($locked_file_path) ? 'LOCKED' : 'UNLOCKED';

        wp_send_json(array('data' => $data, 'row_count' => $row_count, 'file_status' => $status));
    }
}

add_action('wp_ajax_update_table', 'update_table_callback');
add_action('wp_ajax_nopriv_update_table', 'update_table_callback');









function init_dex_scan_single() {

	global $wpdb;
    $post_id = $_POST['post_id'];

	$status = audit_token_dex_init($post_id);	

    if ($status == 'ok'){
        $message = 'Token zaktualizowany i widoczny.';	
    } elseif ($status == 'overload'){
        $message = 'Przeciążenie DEX. Spróbuj jeszcze raz.';	
    } elseif ($status == 'hidden'){
        $message = 'Token zaktualizowany i ukryty.';
    } elseif ($status == 'blocked'){
        $message = 'Token zaktualizowany i zablokowany.';
    } elseif ($status == 'scam'){
        wp_set_object_terms( $post_id, 'scam' , 'token_status' );                   
        archive_and_delete_post($post_id, 'scam');
        $message = 'Token oznaczony jako scam i usunięty.';
    } else {
        // dead
        add_post_id_to_file($post_id, 'dead');
        $message = 'Token oznaczony jako scam i usunięty.';
    }
    FWP()->indexer->index( $post_id );
	
	$response = array(
        'success' => true,
        'message' => $message
    );
    wp_send_json($response); // zwrócenie odpowiedzi w formacie JSON
   
}
add_action('wp_ajax_init_dex_scan_single', 'init_dex_scan_single');
add_action('wp_ajax_nopriv_init_dex_scan_single', 'init_dex_scan_single');




function init_get_golabs_contract() {

	global $wpdb;
    $post_id = $_POST['post_id'];

	$message = golabs_contract_security_test($post_id);
	
	$response = array(
        'success' => true,
        'message' => $message
    );
    wp_send_json($response); // zwrócenie odpowiedzi w formacie JSON
   
}
add_action('wp_ajax_init_get_golabs_contract', 'init_get_golabs_contract');
add_action('wp_ajax_nopriv_init_get_golabs_contract', 'init_get_golabs_contract');




function ajax_reset_nadzorca_func() {
	
	reset_nadzorca();
   
}
add_action('wp_ajax_ajax_reset_nadzorca', 'ajax_reset_nadzorca_func');
add_action('wp_ajax_nopriv_ajax_reset_nadzorca', 'ajax_reset_nadzorca_func');





function assign_status_term() {
    global $wpdb;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';

    if ($post_id && $term) {
        $result = wp_set_object_terms($post_id, $term, 'token_status', false);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            if ($term = 'scam'){
                update_post_meta( $post_id, 'blocked_token', 'Oznaczony ręcznie jako Scam' );
                update_field_with_history_if_changed('token_changelog', 'Oznaczony jako Scam przez ' . user_name(), $post_id);    
            }
            if ( $term = 'dead'){
                update_post_meta( $post_id, 'blocked_token', 'Oznaczony ręcznie jako Dead' );
                update_field_with_history_if_changed('token_changelog', 'Oznaczony jako Dead przez ' . user_name(), $post_id);    
            }
            FWP()->indexer->index( $post_id );
            update_post_meta( $post_id, 'modified_by_user', get_current_user_id() );
            wp_send_json_success('Gotowe');
        }
    } else {
        wp_send_json_error('Błąd: Nieprawidłowe dane');
    }
}
add_action('wp_ajax_assign_status_term', 'assign_status_term');
add_action('wp_ajax_nopriv_assign_status_term', 'assign_status_term');



function assign_category_term() {
    global $wpdb;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
    
    if ($post_id && $term) {
        if ($term == 'delete') { 
            wp_delete_object_term_relationships($post_id, 'category_tokens');
            update_field_with_history_if_changed('token_changelog', 'Usunięty kategorię przez ' . user_name(), $post_id);
            update_post_meta($post_id, 'modified_by_user', get_current_user_id());
            wp_send_json_success('Usunięto');
            FWP()->indexer->index( $post_id );
        } else {
            $result = wp_set_object_terms($post_id, $term, 'category_tokens', false);

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                FWP()->indexer->index( $post_id );
                update_field_with_history_if_changed('token_changelog', 'Oznaczony jako ' . $term  . ' przez ' . user_name(), $post_id);   
                update_post_meta( $post_id, 'modified_by_user', get_current_user_id() );
                wp_send_json_success('Dodano kategorię');
            }
        }
    } else {
        wp_send_json_error('Błąd: Nieprawidłowe dane');
    }
}
add_action('wp_ajax_assign_category_term', 'assign_category_term');
add_action('wp_ajax_nopriv_assign_category_term', 'assign_category_term');



function assign_tag_terms() {
    global $wpdb;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $terms = isset($_POST['terms']) ? sanitize_text_field($_POST['terms']) : '';

    if ($post_id && $terms) {
        if ($terms == 'delete') { 
            wp_delete_object_term_relationships($post_id, 'tags');
            update_field_with_history_if_changed('token_changelog', 'Usunięty tagi przez ' . user_name(), $post_id);
            update_post_meta($post_id, 'modified_by_user', get_current_user_id());
            wp_send_json_success('Usunięto');
            FWP()->indexer->index( $post_id );
        } else {
            $result = wp_set_object_terms($post_id, $terms, 'tags', false);

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                FWP()->indexer->index( $post_id );
                update_field_with_history_if_changed('token_changelog', 'Oznaczony jako ' . join(', ', $terms)  . ' przez ' . user_name(), $post_id);   
                update_post_meta( $post_id, 'modified_by_user', get_current_user_id() );
                wp_send_json_success('Dodano tagi');
            }
        }
    } else {
        wp_send_json_error('Błąd: Nieprawidłowe dane');
    }
}
add_action('wp_ajax_assign_tag_terms', 'assign_tag_terms');
add_action('wp_ajax_nopriv_assign_tag_terms', 'assign_tag_terms');











add_action( 'wp_ajax_clean_init', 'clean_init' );
add_action( 'wp_ajax_nopriv_clean_init', 'clean_init' );
function clean_init() {
    $paged = isset( $_POST['paged'] ) ? (int) $_POST['paged'] : 1;

    $args = array(
        'post_type' => 'tokens',
        'posts_per_page' => 20,
        'paged' => $paged,
        // 'tax_query' => array(
        //     'relation' => 'AND',
        //     array(
        //         'taxonomy' => 'group',
        //         'field' => 'slug',
        //         'terms' => array('db_import'),
        //     ),
        //     array(
        //         'taxonomy' => 'token_status',
        //         'field' => 'slug',
        //         'terms' => array('unverified'),
        //     ),
        // ),
       
    );
    
    $query = new WP_Query($args);

    
    if ($query->have_posts()) {
        $total_count = $query->found_posts;        
        while ($query->have_posts()) {
            $query->the_post();      
            $post_id = get_the_ID();
          
        }
        wp_reset_postdata();
        wp_send_json_success( array( 'success' => true, 'message' => $total_count ) );
    } else {
        wp_send_json_success( array( 'success' => true, 'has_more_pages' => false ) );
    }
    wp_die();
}




