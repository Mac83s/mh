<?php

add_action( 'init', 'register_webhook_endpoint' );
function register_webhook_endpoint() {
    add_rewrite_endpoint( 'webhook_huge_volume', EP_ROOT );
}

add_action( 'parse_request', 'handle_webhook_request' );
function handle_webhook_request( $wp ) {
    if ( array_key_exists( 'webhook_huge_volume', $wp->query_vars ) ) {
        
        // Obsługa tylko zapytań HTTP POST
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $body = file_get_contents( 'php://input' );
            $data = json_decode( $body, true );
            $updated_status = "not exist in DB";
            send_discord_buyvolume_log($data);
            // Znajdź post TOKENS o podanym adresie
            $tokens = get_posts( array(
                'post_type' => 'tokens',
                'meta_key' => 'address',
                'meta_value' => $data['address']
            ) );
        
            // Jeśli znaleziono dokładnie jeden post TOKENS, zaktualizuj jego custom field buy_volume
            if ( count( $tokens ) === 1 ) {
                $token = $tokens[0];
                $post_id = $token->ID;
                $value = $data['volume'];

                if (!is_array(get_post_meta( $post_id, 'buy_volume' ,true))) {
                    delete_post_meta( $post_id, 'buy_volume' );
                }
                update_field_with_history('buy_volume',$value,$post_id);
                update_post_meta($post_id, 'buy_volume_filtering', 'Huge Vol');
                $updated_status = "updated";
                FWP()->indexer->index( $token->ID );
            }
            if ( count( $tokens ) > 1 ) {
                $updated_status = "not updated - duplicates in DB";
            }

            // send_discord_buyvolume_log($data['address'] . ' is ' . $updated_status);
        
            wp_send_json_success( 'Dane z webhooka zostały przetworzone.' );
        }
        exit;
    }
} 