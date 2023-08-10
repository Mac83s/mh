<?php 

// Add User as observer
add_action( 'wp_ajax_nopriv_assign_observer', 'assign_observer' );
add_action( 'wp_ajax_assign_observer', 'assign_observer' );
add_action( '2minutescron', 'assign_observer' );
function assign_observer() {

    $args = array(
        'post_type' => 'tokens',
        'posts_per_page' => -1,
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'group',
                'field' => 'slug',
                'terms' => array('db_import'),
            ),
            array(
                'taxonomy' => 'token_status',
                'field' => 'slug',
                'terms' => array('verified'),
            ),
        ),
        'meta_query' => array(
            'relation' => 'AND',
            // array(
            //     'key' => 'blocked_token',
            //     'compare' => 'NOT EXISTS',
            // ),
            array(
                'key' => 'token_guardian',
                'compare' => 'NOT EXISTS',
            ),
        ),
    );
    
    $query = new WP_Query($args);

    if ($query->have_posts()) {
            
        while ($query->have_posts()) {
            $query->the_post();      
            $post_id = get_the_ID();
            $current_user_number = get_option('current_user_number');
            update_post_meta($post_id, 'token_guardian', $current_user_number);
            $current_user_number = $current_user_number < 6 ? $current_user_number + 1 : 3;
            update_option('current_user_number', $current_user_number);
            FWP()->indexer->index( $post_id );
        }
        wp_reset_postdata();
    } 
}






add_action( 'wp_ajax_nopriv_lock_post_editing', 'lock_post_editing' );
add_action( 'wp_ajax_lock_post_editing', 'lock_post_editing' );

function lock_post_editing() {

    global $wpdb;
    $post_id = $_POST['post_id'];

    $user_id = get_current_user_id();
    $user_name = 'Unknown';

    if ($user_id) {
        $user_data = get_userdata($user_id);
        $user_name = $user_data->display_name;
    }

    if ( empty(get_post_meta( $post_id, 'token_guardian',true))){
        update_post_meta($post_id, 'token_guardian', $user_id);  
    }

    if ( empty(get_post_meta( $post_id, 'block_edit',true))){
    
        update_post_meta($post_id, 'block_edit', $user_id);   

        $response = array(
            'success' => true,
            'message' => 'Token przygotowany do edycji.'
        );

    } else if ( get_post_meta($post_id, 'block_edit', true) == $user_id ){

        $response = array(
            'success' => true,
            'message' => 'Kontynuuj edycję.'
        );

    } else {
        $user_data = get_userdata(get_post_meta( $post_id, 'block_edit',true));
        $user_name = $user_data->display_name;
        $response = array(
            'success' => false,
            'message' => 'Post jest już edytowany przez ' .  $user_name
        );
    }
    

    wp_send_json_success( $response );
        
}




add_action( 'wp_ajax_nopriv_unlock_post_editing', 'unlock_post_editing' );
add_action( 'wp_ajax_unlock_post_editing', 'unlock_post_editing' );

// Funkcja odblokowująca edycję posta
function unlock_post_editing() {
    $post_id = $_POST['post_id'];
    delete_post_meta($post_id, 'block_edit');
    wp_send_json_success( "Edycja odblokowana." );
}




add_action( 'wp_ajax_nopriv_token_cheked_by_human', 'token_cheked_by_human' );
add_action( 'wp_ajax_token_cheked_by_human', 'token_cheked_by_human' );

function token_cheked_by_human() {
    $post_id = $_POST['post_id'];
    $next_week = date("Y-m-d", strtotime("+7 days"));
    update_post_meta($post_id, 'token_cheked_by_human', $next_week);  
    $response = array(
        'success' => true,
        'message' => "Oznaczony jako sprawdzony."
    );
    wp_send_json_success( $response );
}




function token_immune() {
    global $wpdb;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $action = isset($_POST['token_immune_action']) ? sanitize_text_field($_POST['token_immune_action']) : '';

    if ($post_id) {
        if ($action == 'delete') { 
            delete_post_meta($post_id, 'token_immune'); 
            FWP()->indexer->index( $post_id );
            wp_send_json_success('Cofnięto immunitet');
        } else {
            update_post_meta($post_id, 'token_immune', 'Immunity');  
            wp_send_json_success('Nadano immunitet');
            FWP()->indexer->index( $post_id );
        }
    } else {
        wp_send_json_error('Błąd: Nieprawidłowe dane');
    }
}
add_action( 'wp_ajax_nopriv_token_immune', 'token_immune' );
add_action( 'wp_ajax_token_immune', 'token_immune' );




function add_to_fav() {
    global $wpdb;
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $action = isset($_POST['fav_action']) ? sanitize_text_field($_POST['fav_action']) : '';

    if ($post_id && $username) {
        if ($action == 'delete') { 
            wp_delete_object_term_relationships($post_id, 'favorites');
            FWP()->indexer->index( $post_id );
            wp_send_json_success('Usunięto z ulubionych');
        } else {
            $result = wp_set_object_terms($post_id, $username, 'favorites', false);

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success('Dodano do ulubionych');
                FWP()->indexer->index( $post_id );
            }
        }
    } else {
        wp_send_json_error('Błąd: Nieprawidłowe dane');
    }
}
add_action('wp_ajax_add_to_fav', 'add_to_fav');
add_action('wp_ajax_nopriv_add_to_fav', 'add_to_fav');






// Dodanie akcji do obsługi żądań AJAX
add_action( 'wp_ajax_update_custom_field', 'update_custom_field' );
add_action( 'wp_ajax_nopriv_update_custom_field', 'update_custom_field' ); // dla niezalogowanych użytkowników

// Funkcja przetwarzająca żądania AJAX
function update_custom_field() {
    // Odczytanie danych z żądania
    parse_str($_POST['data'], $data);
    $post_id = intval($data['post_id']);

    // Sprawdzenie, czy podany post istnieje
    if ( ! get_post($post_id) ) {
        wp_send_json_error('Post o podanym ID nie istnieje.');
    }

    // Zapisanie wartości pola meta
    foreach ($data as $field_name => $field_value) {
        if (strpos($field_name, 'field_name_') === 0) {
            $clean_field_name = str_replace('field_name_', '', $field_name);
            if ($field_value !== '') {                
                $current_field_value = get_post_meta($post_id, $clean_field_name, true);
                if ($field_value !== $current_field_value) {
                    if ( ! update_post_meta( $post_id, $clean_field_name, $field_value ) ) {
                        wp_send_json_error('Nie udało się zaktualizować pola ' . $clean_field_name . ' with value ' . $field_value);
                    }
                }
            } else {
                delete_post_meta($post_id, $clean_field_name);
            }
        }
        if (strpos($field_name, 'field_name_post_content') === 0) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $field_value
            ));
        }
    }
    delete_post_meta($post_id, 'block_edit');
    wp_send_json_success('Pola meta zostały zaktualizowane.');
}




function replace_links_images_and_nl2br($text) {
    // Zamiana nowych linii na znacznik <br>
    
    
    // Dopasowanie linków URL
    $text = preg_replace_callback(
        '/(https?:\/\/[^\s]+)/',
        function ($matches) {
            return '<a href="' . $matches[0] . '">' . $matches[0] . '</a>';
        },
        $text
    );   


       
    return $text;
}
















add_action( 'wp_ajax_nopriv_buy_position_ajax', 'buy_position_ajax' );
add_action( 'wp_ajax_buy_position_ajax', 'buy_position_ajax' );

function buy_position_ajax() {

    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = get_current_user_id();

    
    $price_data = get_post_meta( $post_id, 'priceUsd', true ); 
    if ( $price_data  && is_array(  $price_data  )){
        $latest = end($price_data); 
                    
        // Open Price
        $current_value = $latest['count'];
        
        // Open Date
        $current_time = current_time('mysql');

        $current_position = get_post_meta( $post_id, 'trading_postion_' . $user_id, true );

        if (!is_array($current_position)) {
            $current_position = array();
        }
        
        $current_position[] = array( 
            'open_trade' => $current_value,
            'open_date' => $current_time,
            'close_trade' => '',
            'close_date' => '',
        );
        
        update_post_meta($post_id,'trading_postion_' . $user_id, $current_position);

        $response = array(
            'success' => true,
            'message' => 'Transakcja zakupu zrealizowana ;)'
        );

    }  else {
        $response = array(
            'success' => false,
            'message' => 'Brak aktualnej ceny, zlecenie odrzucone.'
        );
    }

    wp_send_json_success( $response );
        
}

add_action( 'wp_ajax_nopriv_sell_position_ajax', 'sell_position_ajax' );
add_action( 'wp_ajax_sell_position_ajax', 'sell_position_ajax' );

function sell_position_ajax() {

    global $wpdb;
    $post_id = $_POST['post_id'];
    $position_date = $_POST['position_date'];
    
    $user_id = get_current_user_id();
    
    $current_position = get_post_meta($post_id, 'trading_postion_' . $user_id, true);
    if ($current_position && is_array($current_position)) {
    
        $price_data = get_post_meta($post_id, 'priceUsd', true);
        if ($price_data && is_array($price_data)) {
            $latest = end($price_data);
            $current_value = $latest['count'];
        }
    
        $current_time = current_time('mysql');
    
        // Przeszukanie tablicy i znalezienie wiersza z pasującym identyfikatorem
        foreach ($current_position as &$row) {
            if ($row['open_date'] == $position_date) {
                // Dopisanie danych do wiersza
                $row['close_trade'] = $current_value;
                $row['close_date'] = $current_time;
                break;
            }
        }
    
        // Zapisanie zmodyfikowanej tablicy
        update_post_meta($post_id, 'trading_postion_' . $user_id, $current_position);
    
        $response = array(
            'success' => true,
            'message' => 'Transakcja sprzedaży zrealizowana ;)'
        );
    
    } else {
        $response = array(
            'success' => false,
            'message' => 'Coś poszło nie tak. '
        );
    }
    
    wp_send_json_success($response);
        
}
