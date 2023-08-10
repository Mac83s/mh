<?php

//++++++++++++++++++++++
//++
//+++ Get data from Dex Screener API
//++
//++++++++++++++++++++++
function audit_token_dex( $post_id ) {

    $status = audit_token_dex_init( $post_id );

    if ($status == 'ok'){
        $status = 30;
    } elseif ($status == 'overload'){
        $status = 5;
    } elseif ($status == 'hidden'){
        $status = 1800;
    } elseif ($status == 'blocked'){
        $status = 3601;
    } elseif ($status == 'scam'){
        wp_set_object_terms( $post_id, 'scam' , 'token_status' );   
        update_post_meta( $post_id, 'remove_scam', 'yes' );  
        add_post_id_to_file($post_id, 'dead');              
        $status = 'remove';
    } elseif ($status == 'skip'){     
        $status = 'skip';
    } else {
        // dead
        add_post_id_to_file($post_id, 'dead');
        $status = 'remove';
    }

    return $status;
   
}


function audit_token_dex_init( $post_id ) {

    usleep(300000);
    
    $address = get_post_meta( $post_id, 'address',true);

    if(!$address) {
        return 'skip';
    }
    
    $api_url = 'https://api.dexscreener.com/latest/dex/tokens/' . $address;

    $response = wp_remote_get( $api_url );

    if ( is_wp_error( $response ) ) {
        $status = 'error';
      
    } else {
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code >= 400 ) {
            $status = 'overload';
            
        } else {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            $status = 'ok';
        }
    }

    $hidden_token = get_post_meta( $post_id, 'hidden_token', true );
    $blocked_token = get_post_meta( $post_id, 'blocked_token', true );
    $security_critical = get_post_meta( $post_id, 'security_critical', true );
    $immunity_test = get_post_meta($post_id, 'token_immune', true);
    $publish_time = get_post_time('U', true, $post_id);
    
    //--------------------------------------
    // Sprawdź czy dane zostały poprawnie pobrane
    //--------------------------------------
    if ( ! isset( $data ) || !isset($data['pairs'])) {

        if (empty($immunity_test)) {
    
            $message = 'No DexScreener Data'; 

            if(!$hidden_token){
                update_post_meta( $post_id, 'hidden_token', $message );
                update_field_with_history_if_changed('token_changelog', $message . ' - problem z danymi. Token zostaje ukryty.', $post_id);
                $status = 'hidden';   
               
            } else {
                // Przez pierwsze dwie godziny od publikacji nie blokujemy tokenu
                if(!$blocked_token){
                    if (strtotime("-2 hours") > $publish_time) {
                        $status = dex_3_steps_to_block( $post_id, $message );   
                    }    
                }    
               
            }

            if($blocked_token){

                if (strtotime("-26 hours") > $publish_time) {
                    wp_set_object_terms( $post_id, 'dead' , 'token_status' );
                    update_field_with_history_if_changed('token_changelog', 'Oznaczony jako Dead po 26h blokady - No DexScreener Data', $post_id);
                    $status = 'dead';
                }  

            }

        }

    }

    //--------------------------------------
    // Aktualizuj historię wartości price
    //--------------------------------------
    if (isset($data['pairs'][0]['priceUsd'])){
        $priceUsd = $data['pairs'][0]['priceUsd'];
        update_field_with_history('priceUsd',$priceUsd, $post_id);

        $priceUsd_ = get_post_meta( $post_id, 'priceUsd', true );

        // TU będziemy spradzac na świecach godzinnych <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
        // Spadek ceny o 99.9% blokuje i oznacza jako scam 
        if (is_array($priceUsd_) && calculate_percentage_change($priceUsd_) > 99.1 || $priceUsd_ < 0.0000000000000001) {
            update_field_with_history_if_changed('token_changelog', 'Spadek ceny o 100% - RUG', $post_id);
            return 'scam';
        }
    }
    
    //--------------------------------------
    // Aktualizuj historię wartości liquidity
    //--------------------------------------
    if (isset($data['pairs'][0]['liquidity']['usd'])){
        $liquidityUsd = $data['pairs'][0]['liquidity']['usd'];
        update_field_with_history('liquidity',$liquidityUsd, $post_id);

        $liquidity_ = get_post_meta( $post_id, 'liquidity', true );

        // Spadek ceny o 99.9% blokuje i oznacza jako scam
        if (is_array($liquidity_) && calculate_percentage_change($liquidity_) > 95 || $liquidityUsd < 1000) {
            update_field_with_history_if_changed('token_changelog', 'Usunięcie płynności o 95% - DEX SCAN - RUG', $post_id);
            return 'scam';
        }
        
    }

    //--------------------------------------
    //Aktualizuj historię wartości MarketCap
    //--------------------------------------
    if (isset($data['pairs'][0]['fdv'])){
        $marketcap = $data['pairs'][0]['fdv'];
        update_field_with_history('marketcap',$marketcap, $post_id);
        update_post_meta( $post_id, 'marketcap_filter', $marketcap );
    }

    //--------------------------------------
    // Sprawdź transakcje
    //--------------------------------------
    if (isset($data['pairs'][0]['txns'])){
        $transactions_1h = $data['pairs'][0]['txns']['h1'];
        $transactions_6h = $data['pairs'][0]['txns']['h6'];
        $transactions_24h = $data['pairs'][0]['txns']['h24'];
        
        // Zapisuj ilość transakcji 1h do obliczenia średniej, wykresu itd.
        update_field_with_history('dex_transactions_1h',$transactions_1h, $post_id);  
        update_field_with_history('dex_transactions_24h',$transactions_24h, $post_id);  

        // if ($data['pairs'][0]['txns']['h1']['buys'] + $data['pairs'][0]['txns']['h1']['sells'] == 0) {} 
        if (empty($immunity_test)) {

            if ($data['pairs'][0]['txns']['h6']['buys'] + $data['pairs'][0]['txns']['h6']['sells'] == 0) {

                $message = 'No transactions 6h';

                // Za pierwszym razem jak wykryję brak transakcji 6h ukrywam token i sprawdzam drugą kolejką co 1h
                if(!$hidden_token){
                    update_post_meta( $post_id, 'hidden_token', $message );
                    update_field_with_history_if_changed('token_changelog', 'DexScreener - Brak transakcji przez min. 6h. Token zostaje ukryty.' , $post_id);
                    $status = 'hidden'; 
                } 

            } else {
                if($hidden_token){
                    // Wyjście z hidden_token do main  <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
                    update_field_with_history_if_changed('token_changelog', 'DexScreener - Otrzymano dane, token przywrócony.' , $post_id);
                    delete_post_meta($post_id,  'hidden_token');
                    $status = 'ok';
                }
            }

            if ($data['pairs'][0]['txns']['h24']['buys'] + $data['pairs'][0]['txns']['h24']['sells'] == 0) {
                
                if($blocked_token){
                    if (strtotime("-26 hours") > $publish_time) {
                        wp_set_object_terms( $post_id, 'dead' , 'token_status' );
                        update_field_with_history_if_changed('token_changelog', 'Dead - No transactions 24h DexScreener', $post_id);
                        $status = 'dead';
                    }  
                } else {
                    //Po 3 kolejnych testach nie mamy transakcji token zostaje zablokowany
                    $status = dex_3_steps_to_block( $post_id, $message );   
                }
            } else {
                
                if($blocked_token){
                    // Wyjście z blocked_token do hidden  <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
                    $message = 'Blocked up to hidden';
                    delete_post_meta( $post_id, 'blocked_token' );
                    update_post_meta( $post_id, 'hidden_token', $message );
                    update_field_with_history_if_changed('token_changelog', $message, $post_id);
                    $status = 'hidden';
                }
            }

        }

    }

    //--------------------------------------
    // Pobierz Volume
    //--------------------------------------
    if (isset($data['pairs'][0]['volume'])){

        $volume_5m = $data['pairs'][0]['volume']['m5'];
        $volume_h24 = $data['pairs'][0]['volume']['h24'];
        update_field_with_history('volume_5m',$volume_5m, $post_id);
        update_field_with_history('volume_24h',$volume_h24, $post_id);
       
    }


    
    // Remove as dead with market cap less than 20k and age min 2 days
    $marketcap = get_post_meta( $post_id, 'marketcap', true);
    if (is_array($marketcap)) {
        $last_10_values = array_slice($marketcap, -10);
        $average = array_sum(array_column($last_10_values, 'count')) / 10;
        $post_date = new DateTime(get_the_date('Y-m-d', $post_id));
        $now = new DateTime();
        $interval = $post_date->diff($now)->days;
    
        if ($average < 20000 && $interval >= 2) {
            update_field_with_history('token_changelog', 'Low market cap < 20k', $post_id);
            // archive_and_delete_post($post_id, 'dead'); 
            // return 'dead';
        }
    }
    $liquidity  = get_post_meta( $post_id, 'liquidity', true);
    if (is_array($liquidity )) {
        $last_10_values = array_slice($liquidity , -10);
        $average_liquidity  = array_sum(array_column($last_10_values, 'count')) / 10;
        $post_date = new DateTime(get_the_date('Y-m-d', $post_id));
        $now = new DateTime();
        $interval = $post_date->diff($now)->days;
    
        if ($average_liquidity < 20000 && $interval >= 2) {
            update_field_with_history('token_changelog', 'Low liquidity < 20k', $post_id);
            archive_and_delete_post($post_id, 'dead'); 
            return 'dead';
        }
    }


       
    if ($security_critical) {
        update_field_with_history_if_changed('token_changelog', 'Krytyczne luki gopluslabs --> SCAM', $post_id);   
        $status = 'scam';         
    }

    if ($immunity_test) {
        delete_post_meta($post_id,  'hidden_token');
        delete_post_meta($post_id,  'blocked_token');
        $status = 'ok'; 
    }
    

    return $status;
}




//  Block token after 3 tests

function dex_3_steps_to_block( $post_id, $message ) {

    $status = 'hidden';

    $dex_test_status = get_post_meta( $post_id, 'dex_test', true );

    if ( $dex_test_status ) {
        $dex_test_counter = $dex_test_status;
    } else {
        $dex_test_counter = 0;
    }

    if ( $dex_test_counter >= 3 ) {
        $address = get_post_meta($post_id, 'address', true);
        update_post_meta( $post_id, 'blocked_token', $message );

        update_field_with_history('token_changelog',$message  . ' Token zablokowany po 3 testach.', $post_id);

        $status = 'blocked';
        
    } else {
        $dex_test_counter++;
        update_post_meta( $post_id, 'dex_test', $dex_test_counter );
    }

    return $status;
}


