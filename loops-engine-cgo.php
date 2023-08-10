<?php

// ATH ATL coin_id
// 'https://api.coingecko.com/api/v3/coins/ethereum/contract/0x49642110b712c1fd7261bc074105e9e44676c68f'


function audit_token_cgo( $post_id ) {

    sleep(20);

    $data = get_ath_atl_coindid($post_id);

    if ($data == 'overload'){
        $status = 5;
    }
    
    if (is_array($data)){

        $ath = $data['ath'];
        if (preg_match('/[0-9\.]+E[+-][0-9]+/', $ath)) {
            $ath = number_format($ath, 10, '.', '');
        }
        if ( get_post_meta( $post_id, 'ath',true) < $ath){
            update_post_meta( $post_id, 'ath', $ath );
            $utcDateTime = new DateTime($data['ath_date'], new DateTimeZone('UTC'));
            $localTimeZone = new DateTimeZone('Europe/Warsaw');
            $utcDateTime->setTimeZone($localTimeZone);
            $localDateStr = $utcDateTime->format('Y-m-d H:i:s');
            update_post_meta( $post_id, 'ath_date', $localDateStr );
        }

        $atl = $data['atl'];
        if (preg_match('/[0-9\.]+E[+-][0-9]+/', $atl)) {
            $atl = number_format($atl, 15, '.', '');
        }

        if ( get_post_meta( $post_id, 'atl',true) > $atl){
            update_post_meta( $post_id, 'atl', $atl );
            $utcDateTime = new DateTime($data['atl_date'], new DateTimeZone('UTC'));
            $localTimeZone = new DateTimeZone('Europe/Warsaw');
            $utcDateTime->setTimeZone($localTimeZone);
            $localDateStr = $utcDateTime->format('Y-m-d H:i:s');
            update_post_meta( $post_id, 'atl_date', $localDateStr );
        }

        $coin_id = $data['coin_id'];
        if (!get_post_meta( $post_id, 'coingeckoid',true)){
            update_post_meta( $post_id, 'coingeckoid', $coin_id );
        }

        $watchlist_portfolio_users = $data['watchlist_portfolio_users'];
        if ($watchlist_portfolio_users > 0){
            update_field_with_history_if_changed('watchlist_portfolio_users', $watchlist_portfolio_users, $post_id);
        }
        $twitter_screen_name = strtolower($data['twitter_screen_name']);
        if ($twitter_screen_name  && !get_post_meta( $post_id, 'twitter_account_url',true)){
            update_post_meta($post_id, 'twitter_account_url', 'https://twitter.com/' . $twitter_screen_name);
            update_post_meta($post_id, 'twitter_account_name', $twitter_screen_name);
        }
        $homepage = $data['homepage'];
        // if ($homepage  && !get_post_meta( $post_id, 'website',true)){
        if ($homepage){
            if (is_array($homepage)){
                $homepage = $homepage[0];
            }
            update_post_meta($post_id, 'website', $homepage);
            update_post_meta($post_id, 'website_filtering', 'Website');
        }
        $telegram_channel_identifier = 'https://t.me/' . $data['telegram_channel_identifier'];
        if ($telegram_channel_identifier  && !get_post_meta( $post_id, 'telegram_url',true)){
            update_post_meta($post_id, 'telegram_url', $telegram_channel_identifier);
            
            $class = '.tgme_page_extra';
            $data =  scrapeURL($telegram_channel_identifier, $class);
            update_post_meta($post_id, 'telegram_subscribers', $data);
            update_post_meta($post_id, 'telegram_account_filtering', 'Telegram');
        }

        $status = 86000;

    } elseif ($data == 'coin not found') {
        $status = 36000;
    }

    return $status;
}     


function get_ath_atl_coindid($post_id) {

    $token_address = get_post_meta($post_id, 'address', true);
    $terms_token_blockchain = get_the_terms( $post_id ,  'blockchain' );

    foreach ( $terms_token_blockchain as $term ) {					
        $term_id = $term->term_id;
        $blockchain = $term->name;
        $blockchain_slug = $term->slug;
    }
    if ($term_id) {
        $blockchain_id = get_blockchain_cf( $term_id, 'blockchain_id' );
    }

    $url = "https://api.coingecko.com/api/v3/coins/$blockchain_id/contract/$token_address";

    // Inicjalizacja sesji cURL
    $ch = curl_init();

    // Ustawienie URL
    curl_setopt($ch, CURLOPT_URL, $url);

    // Ustawienie, żeby zwrócić wynik, zamiast go wyświetlić
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Wykonanie zapytania
    $output = curl_exec($ch);

    // Zamknięcie sesji cURL
    curl_close($ch);

    // Dekodowanie odpowiedzi JSON
    $data = json_decode($output, true);

    if (isset($data, $data['status']) && array_key_exists('error_code', $data['status']) && $data['status']['error_code'] == 429){
        return  'overload';
    }
    
    if (isset($data['error'])){
        return $data['error'];
    } else {
        return [
            "ath" => $data["market_data"]["ath"]["usd"],
            "ath_date" => $data["market_data"]["ath_date"]["usd"],
            "atl" => $data["market_data"]["atl"]["usd"],
            "atl_date" => $data["market_data"]["atl_date"]["usd"],            
            "coin_id" => $data["id"],
            "homepage" => $data["links"]["homepage"],
            "official_forum_url" => $data["links"]["official_forum_url"],
            "chat_url" => $data["links"]["chat_url"],
            "announcement_url" => $data["links"]["announcement_url"],
            "twitter_screen_name" => $data["links"]["twitter_screen_name"],
            "telegram_channel_identifier" => $data["links"]["telegram_channel_identifier"],
            "subreddit_url" => $data["links"]["subreddit_url"],
            "github" => $data["links"]["repos_url"]["github"],
            "bitbucket" => $data["links"]["repos_url"]["bitbucket"],
            "watchlist_portfolio_users" => $data["watchlist_portfolio_users"],
        ];
    }
    
}

