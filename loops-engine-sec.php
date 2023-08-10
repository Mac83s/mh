<?php

function audit_token_sec( $post_id ) {

    $status = audit_token_sec_init( $post_id );

    if ($status == 'ok'){
        $status = 900;
    } elseif ($status == 'overload'){
        $status = 5;
    } elseif ($status == 'skip'){     
        $status = 'skip';
    } 
    
    return $status;
}     

function audit_token_sec_init( $post_id ) {
    
    $address = get_post_meta( $post_id, 'address',true);

    if(!$address) {
        return 'skip';
    }

    if (get_post_meta($post_id, 'tx_scam1', true)) {
        $message = 'Oznaczony przez scam-warning.eth';
        update_field_with_history_if_changed('token_changelog', $message, $post_id);
        update_post_meta( $post_id, 'security_critical', $message );
        return 'skip';
    }

    check_remove_liquidity($post_id);

    get_golabs_contract_security($post_id);	

    critical_security_cleaning($post_id);

    trust_checker_lvl_1($post_id);
    if (empty(get_post_meta($post_id, 'trust_checker_lvl_1', true)) || get_post_meta($post_id, 'trust_checker_lvl_1', true) !== 'Trust') {
        trust_checker_lvl_2($post_id);
    }


    // Some Important Calculations

    // Update ATH, ATL
    $price_data = get_post_meta( $post_id, 'priceUsd', true ); 
    if ( $price_data  && is_array(  $price_data  )){
            
        $latest = end($price_data); 
        $current_price = $latest['count'];

        $atl = get_post_meta( $post_id, 'atl', true );
        if($current_price  < $atl) {
            update_post_meta( $post_id, 'atl', $current_price );
        }

        $ath = get_post_meta( $post_id, 'ath', true );
        if($current_price  > $ath) {
            update_post_meta( $post_id, 'ath', $current_price );
        }

        if ($ath){
            $toATH = (($ath - $current_price) / $current_price) * 100;
            update_post_meta( $post_id, 'percent_to_ath', round($toATH,0) );
        }
    }

    $keys = [
        ['key' => 'priceUsd'],
        ['key' => 'liquidity'],
        ['key' => 'marketcap'],
        ['key' => 'volume_5m'],
        ['key' => 'holder_count']
    ];

    foreach ($keys as $key) {
        generate_hourly_data($key['key'], $post_id);
    }

    $keys = [
        ['key' => 'holders'],
        ['key' => 'owner_balance'],
        ['key' => 'owner_percent'],
        ['key' => 'dex_transactions_1h'],
        ['key' => 'tweeter_listed'],
        ['key' => 'tweet_count'],
        ['key' => 'twitter_followers'],
    ];

    foreach ($keys as $key) {
        clean_data_with_array_as_val($key['key'], $post_id);
    }


    $status = 'ok';
    return $status;
}     


