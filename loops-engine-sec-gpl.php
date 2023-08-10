<?php

function get_golabs_contract_security($post_id) {
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

    if ($blockchain_id === null) {
        return;
    }
    
    $url = "https://api.gopluslabs.io/api/v1/token_security/{$blockchain_id}?contract_addresses=$token_address";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
   
    if (!isset($data['result'][$token_address]) || !is_array($data['result'][$token_address])) {
        return;
    }

    foreach ($data['result'][$token_address] as $key => $value) {
        update_field_with_history_if_changed($key,$value,$post_id);
    }
}





function critical_security_cleaning($post_id){
    $state = 0;

    $cannot_buy = get_post_meta( $post_id, 'cannot_buy',true);
    if ( is_array($cannot_buy) ) { 
        $cannot_buy = end($cannot_buy); 
        if ( $cannot_buy['count'] == 1 ) $state++;
    }
    $is_honeypot = get_post_meta( $post_id, 'is_honeypot',true);
    if ( is_array($is_honeypot) ) { 
        $is_honeypot = end($is_honeypot); 
        if ( $is_honeypot['count'] == 1 ) $state++;
    }
    $honeypot_with_same_creator = get_post_meta( $post_id, 'honeypot_with_same_creator',true);
    if ( is_array($honeypot_with_same_creator) ) { 
        $honeypot_with_same_creator = end($honeypot_with_same_creator); 
        if ( $honeypot_with_same_creator['count'] == 1 ) $state++;
    }
    $sell_tax = get_post_meta( $post_id, 'sell_tax',true);
    if ( is_array($sell_tax) ) { 
        $sell_tax = end($sell_tax); 
        if ( $sell_tax['count'] > 0.5 ) $state++;
    }
    $buy_tax = get_post_meta( $post_id, 'buy_tax',true);
    if ( is_array($buy_tax) ) { 
        $buy_tax = end($buy_tax); 
        if ( $buy_tax['count'] > 0.5 ) $state++;
    }

    if ( $state  > 0)   {

        $contract_verification_date = get_post_meta( $post_id, 'contract_verification_date', true );
        if ($contract_verification_date){	
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $contract_verification_date);
            $contract_verification_date = $date->modify('+2 hour');			
            $datetime_str = $contract_verification_date->format("Y-m-d H:i:s");		

            $immunity_test = get_post_meta($post_id, 'token_immune', true);
            if (empty($immunity_test)) {

                $datetime = new DateTime($datetime_str);
                $datetime->add(new DateInterval('PT2H'));
                $message = 'Krytyczne Luki GoPlusLabs';
                
                // Test if 2h from verification - we need to give some time for GPL update
                if ($datetime < new DateTime()) {
                    update_post_meta( $post_id, 'security_critical', $message );
                } else {
                    update_field_with_history_if_changed('token_changelog',$message , $post_id);
                }
            } else {
                $message = 'Krytyczne Luki GoPlusLabs tokenu z immunitetem';

                update_post_meta( $post_id, 'warning', 'Yes' );
                delete_post_meta( $post_id, 'security_critical');
                update_field_with_history_if_changed('token_changelog',$message , $post_id);
            }
        } else {
            error_log('Błąd: Brak posta o ID: ' . $post_id  . ' ' . get_permalink($post_id));
        }
        
    }

    return $state;
}    
























function trust_checker_lvl_1($post_id){
    
    $warnings = 0;
    $locked_lp_token = get_post_meta( $post_id, 'locked_lp_token', true);
    $trust_checker_lvl_1 = get_post_meta( $post_id, 'trust_checker_lvl_1', true);

    $keys = array(
        'is_open_source',
        'is_in_dex',
    );    
    foreach ($keys as $key) {
        $value = get_post_meta( $post_id, $key, true);
        if ( !empty( $value ) && is_array($value) ) { 
            $latest = end($value); 
            if ( $latest['count'] == 0 ) { 
                $warnings++;
                // Ostrzeżenie o zmianie statusu parametru - TYLKO DLA GEMÓW
                if ($locked_lp_token == 'Yes' && $trust_checker_lvl_1 == 'Trust' ) {
                    update_field_with_history_if_changed('token_changelog', 'GPL ostrzega ' . $key , $post_id);
                }     
            }
        }
    }

    $keys2 = array(
        'is_proxy',
        'is_mintable',
        'can_take_back_ownership',
        'owner_change_balance',
        'hidden_owner',
        'selfdestruct',
        'external_call',
        'buy_tax',
        'sell_tax',
        'cannot_buy',
        'cannot_sell_all',
        'slippage_modifiable',
        'transfer_pausable',
        'is_blacklisted',
        'is_whitelisted',
        'is_anti_whale',
        'anti_whale_modifiable',
        'trading_cooldown',
        'personal_slippage_modifiable',
    );    
    foreach ($keys2 as $key2) {
        $value2 = get_post_meta( $post_id, $key2, true);
        if ( !empty( $value2 ) && is_array($value2) ) { 
            $latest2 = end($value2); 
            if ( $latest2['count'] == 1 ) { 
                $warnings++;
                // Ostrzeżenie o zmianie statusu parametru - TYLKO DLA GEMÓW
                if ($locked_lp_token == 'Yes' && $trust_checker_lvl_1 == 'Trust' ) {
                    update_field_with_history_if_changed('token_changelog', 'GPL ostrzega ' . $key2 , $post_id);
                }     
            }
        }
    }

    if ($warnings == 0) {
        if ($locked_lp_token == 'Yes' && !$trust_checker_lvl_1 == 'Trust' ) {
             update_field_with_history_if_changed('token_changelog', 'Oznaczony jako GEM' , $post_id);
        }     
        update_post_meta( $post_id, 'trust_checker_lvl_1', 'Trust' );
             
    } else {       
        delete_post_meta( $post_id, 'trust_checker_lvl_1');
    }

    update_post_meta( $post_id, 'trust_checker_lvl_1_count', $warnings );


    // Price update
    $sell_tax = get_post_meta( $post_id, 'sell_tax',true);
    if ( is_array($sell_tax) ) { 
        $latest = end($sell_tax); 
        $tax = $latest['count'];
        if ($tax > 0) {
          $tax = $tax * 100;
        }
        if(is_numeric($tax)){
            update_post_meta( $post_id, 'sale_tax_number', round($tax ,1) );
        } else {
            update_post_meta( $post_id, 'sale_tax_number', $tax );
        }
    }

    $buy_tax = get_post_meta( $post_id, 'buy_tax',true);
    if ( is_array($buy_tax) ) { 
        $latest = end($buy_tax); 
        $tax = $latest['count'];
        if ($tax > 0) {
          $tax = $tax * 100;
        }
        if(is_numeric($tax)){
            update_post_meta( $post_id, 'buy_tax_number', round($tax ,1) );
        } else {
            update_post_meta( $post_id, 'buy_tax_number', $tax );
        }
    }

}    

function trust_checker_lvl_2($post_id){
    
    $warnings = 0;
    $locked_lp_token = get_post_meta( $post_id, 'locked_lp_token', true);
    $trust_checker_lvl_2 = get_post_meta( $post_id, 'trust_checker_lvl_2', true);

    $keys = array(
        'is_open_source',
        'is_in_dex',
    );    
    foreach ($keys as $key) {
        $value = get_post_meta( $post_id, $key, true);
        if ( !empty( $value ) && is_array($value) ) { 
            $latest = end($value); 
            if ( $latest['count'] == 0 ) { 
                $warnings++;                
            }
        }
    }

    $keys2 = array(
        'is_proxy',
        'is_mintable',
        'can_take_back_ownership',
        'owner_change_balance',
        'hidden_owner',
        'selfdestruct',
        'external_call',
        'buy_tax',
        'sell_tax',
        'cannot_buy',
        'cannot_sell_all',
        'slippage_modifiable',
        'transfer_pausable',
        'is_blacklisted',
        'is_whitelisted',
        // 'is_anti_whale',
        // 'anti_whale_modifiable',
        'trading_cooldown',
        'personal_slippage_modifiable',
    );    
    foreach ($keys2 as $key2) {
        $value2 = get_post_meta( $post_id, $key2, true);
        if ( !empty( $value2 ) && is_array($value2) ) { 
            $latest2 = end($value2); 
            if ( $latest2['count'] == 1 ) { 
                $warnings++;
            }
        }
    }

    if ($warnings == 0 ) {
        if ($locked_lp_token == 'Yes' && !$trust_checker_lvl_2 == 'Trust' ) {
           update_field_with_history_if_changed('token_changelog', 'Oznaczony jako GEM lvl2' , $post_id);
        }     
        update_post_meta( $post_id, 'trust_checker_lvl_2', 'Trust' );
             
    } else {       
        delete_post_meta( $post_id, 'trust_checker_lvl_2');
    }
    delete_post_meta( $post_id, 'trust_checker_lvl_2_count');

}    







function golabs_contract_security_test($post_id){
    
    get_golabs_contract_security($post_id);	

    $immunity_test = get_post_meta($post_id, 'token_immune', true);
    if (empty($immunity_test)) {

        if (critical_security_cleaning($post_id) == 0){

            wp_set_object_terms($post_id, 'Verified', 'token_status', false);
            delete_post_meta($post_id,  'hidden_token');
            delete_post_meta($post_id,  'blocked_token');
            update_field_with_history('token_changelog','GoPlusLabs bez krytycznych luk.' , $post_id);
            trust_checker_lvl_1($post_id);
            trust_checker_lvl_2($post_id);

            $message = 'Token zaktualizowany. Odśwież stronę aby zobaczyć wszystkie zmiany.';
        
            FWP()->indexer->index( $post_id );
        } else {
            $message = 'Krytyczne Luki GoPlusLabs';
            wp_set_object_terms($post_id, 'Dead', 'token_status', false);
            update_post_meta( $post_id, 'blocked_token', $message );
            update_field_with_history('token_changelog',$message , $post_id);
            FWP()->indexer->index( $post_id );
        }
    } else{

        $terms_token_status = get_the_terms( $post_id ,  'token_status' );
        if($terms_token_status){
            foreach ( $terms_token_status as $term ) {	 
                $token_status_slug = $term->slug;
            }
        }
        if ($token_status_slug == 'scam' || $token_status_slug == 'dead'){
            wp_set_object_terms($post_id, 'verified', 'token_status', false);
        }
        if (critical_security_cleaning($post_id) == 0){
            $message = 'Token zaktualizowany. Odśwież stronę aby zobaczyć wszystkie zmiany.';
        } else {
            $message = 'Krytyczne Luki GoPlusLabs tokenu z immunitetem';
            update_post_meta( $post_id, 'warning', $message );
        }
        delete_post_meta($post_id,  'hidden_token');
        delete_post_meta($post_id,  'blocked_token');
        trust_checker_lvl_1($post_id);
        trust_checker_lvl_2($post_id);
        update_field_with_history_if_changed('token_changelog',$message , $post_id);

    }

    return $message;
}    















// Badge TRUST

// is_open_source = 0
// is_in_dex = 0

// is_proxy = 1
// is_mintable = 1
// can_take_back_ownership = 1
// owner_change_balance = 1
// hidden_owner = 1
// selfdestruct = 1
// external_call = 1
// buy_tax = 1
// sell_tax = 1
// cannot_buy = 1
// cannot_sell_all = 1
// slippage_modifiable = 1
// is_honeypot = 1
// transfer_pausable = 1
// is_blacklisted = 1
// is_whitelisted = 1
// is_anti_whale = 1
// anti_whale_modifiable = 1
// trading_cooldown = 1
// personal_slippage_modifiable = 1





// Badge ???

// is_open_source = 0
// is_proxy = 1
// is_mintable = 1
// can_take_back_ownership = 1
// owner_change_balance = 1
// hidden_owner = 1
// selfdestruct = 1
// external_call = 1


// buy_tax = 1
// sell_tax = 1
// cannot_buy = 1
// cannot_sell_all = 1
// slippage_modifiable = 1
// is_honeypot = 1
// transfer_pausable = 1
// is_blacklisted = 1
// is_whitelisted = 1
// is_anti_whale = 1
// anti_whale_modifiable = 1
// trading_cooldown = 1
// personal_slippage_modifiable = 1




// is_open_source = 0
// is_proxy = 1
// is_mintable = 1
// can_take_back_ownership = 1
// owner_change_balance = 1
// hidden_owner = 1
// selfdestruct = 1
// external_call = 1


// buy_tax = 1
// sell_tax = 1
// cannot_buy = 1
// cannot_sell_all = 1
// slippage_modifiable = 1
// is_honeypot = 1
// transfer_pausable = 1
// is_blacklisted = 1
// is_whitelisted = 1
// is_anti_whale = 1
// anti_whale_modifiable = 1
// trading_cooldown = 1
// personal_slippage_modifiable = 1

