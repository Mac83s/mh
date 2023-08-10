<?php
add_action('wp_ajax_create_new_post', 'create_new_post');
add_action('wp_ajax_nopriv_create_new_post', 'create_new_post');

function create_new_post() {
    if (!isset($_POST['symbol'], $_POST['name'], $_POST['blockchain'])) {
        wp_send_json_error('Wszystkie wymagane pola muszą być wypełnione.');
        wp_die();
    }

    $symbol = sanitize_text_field($_POST['symbol']);
    $name = sanitize_text_field($_POST['name']);
    $blockchain = sanitize_text_field($_POST['blockchain']);
    $tags = sanitize_text_field($_POST['tags']);
    $address = sanitize_text_field($_POST['address']);
    $twitter = sanitize_text_field($_POST['twitter']);
    $coingeckoid = sanitize_text_field($_POST['coingeckoid']);
    $binanceid = sanitize_text_field($_POST['binanceid']);
    $private_custom_field = sanitize_text_field($_POST['private_custom_field']);

    if (empty($address)) {
        $add_address = $symbol . '-' . date('ymdHis');
    } else {
        $add_address = $address;
    }

    $post_slug = $blockchain. '-' . $add_address;
    $post = get_page_by_path($post_slug, OBJECT, 'tokens');
        
    if ($post == null && is_address_archived($address) === false) {

        $post_id = wp_insert_post([
            'post_title'    => $name . ' (' . $symbol . ')',
            'post_name'     => $blockchain . '-' . $add_address,
            'post_status'   => 'publish',
            'post_type'     => 'tokens',
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error('Nie udało się utworzyć postu.');
            wp_die();
        }

        // Dodaj custom fields
        add_post_meta($post_id, 'symbol', $symbol);
        add_post_meta($post_id, 'name', $name);
        if ($address){ add_post_meta($post_id, 'address', $address); }
        if ($twitter){ add_post_meta($post_id, 'twitter_account_url', $twitter); }
        if ($coingeckoid){ add_post_meta($post_id, 'coingeckoid', $coingeckoid); }
        if ($binanceid){ add_post_meta($post_id, 'binanceid', $tradingviewid); }
        add_post_meta($post_id, 'create_date', current_time('mysql'));
        add_post_meta($post_id, 'token_guardian', get_current_user_id());
        add_post_meta($post_id, 'private_custom_field_' . get_current_user_id(), $private_custom_field);

        // Dodaj taxonomy
        wp_set_post_terms($post_id, $blockchain, 'blockchain');
        wp_set_post_terms($post_id, 109, 'group'); // manual
        if ($tags){ 
            wp_set_post_terms($post_id, $tags, 'tags');
        }
        if (empty($address) && empty($binanceid)){
            update_post_meta($post_id, 'token_immune', 'Upcoming Immunity'); 
            wp_set_post_terms($post_id, 'upcoming', 'token_status');
            add_post_id_to_file($post_id, 'dex'); // dodajemy do algorytmu aby śledzić twitter i inne 
            // add_post_id_to_file($post_id, 'upg');
        } else if ($binanceid){
            update_post_meta($post_id, 'token_immune', 'Legit Immunity');  
            wp_set_post_terms($post_id, 'legit', 'token_status');
            add_post_id_to_file($post_id, 'dex'); // dodajemy do algorytmu aby śledzić twitter i inne 
            // add_post_id_to_file($post_id, 'legit');
        } else {
            add_post_id_to_file($post_id, 'vfn');
        }

        FWP()->indexer->index($post_id);
        wp_send_json_success();
    }

    if ($post != null ) {
        wp_send_json_error('Token o podanym adresie jest już naszej bazie.');
    }
    if ( is_address_archived($address) === true) {
        wp_send_json_error('Token o podanym adresie był w naszej bazie ale został oznaczony jako martwy.');
    }
   
    
    wp_die();
}
