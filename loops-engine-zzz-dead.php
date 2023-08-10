<?php
//++++++++++++++++++++++
//++
//+++ Get data from Dex Screener API
//++
//++++++++++++++++++++++
function audit_token_dead( $post_id ) {
    
    // Usuń tokeny z niezweryfikowanym kontraktem 
    if (!get_post_meta( $post_id, 'contract_verification_date',true)) {        
        archive_and_delete_post($post_id, 'unverified');
        return 'remove';
    }

    // Usuń tokeny oznaczone jako SCAM
    if (get_post_meta( $post_id, 'remove_scam',true)) {        
        archive_and_delete_post($post_id, 'scam');
        return 'remove';
    }

    $status = audit_token_dead_init( $post_id );
    
    if (is_numeric($status)) {       
        // wartość liczbowa to godziny  
        if ($status > 144) {
            archive_and_delete_post($post_id, 'dead');
            $status = 'remove';
        } elseif ($status < 1) {

            // 1. usuń tax Dead i nadaj Verified aby mogła byc przetwarzana przez głowny węzeł
            wp_set_object_terms($post_id, 'verified', 'token_status', false);
                
            // 2. usuń blocked
            delete_post_meta( $post_id, 'blocked_token' );

            // 3. ukryj token przed człowiekiem aby nie zwracać jescze uwagi jak jest nie pewny
            $message = 'Powrót z DEAD';
            update_post_meta( $post_id, 'hidden_token', $message );

            // 4. Zapisz Changelog
            update_field_with_history_if_changed('token_changelog', $message . ' - jeszcze wątpliwy, musimy go sprawdzić', $post_id);

            // 5. Usuń token z pliku DEAD 
            $status = 'remove';

            // 6. Dodaj token do głównego węzła
            add_post_id_to_file($post_id, 'dex');

            add_post_id_to_file($post_id, 'isback'); // tymczasowo dla weryfikacji co wraca
            
        } else {
            $status = 3601;
        }
    } elseif ($status === 'no_data'){
        archive_and_delete_post($post_id, 'dead');
        $status = 'remove';
    } else {
        $status = 7200; // na wypadek błędu w logice
    }

    return $status;
   
}


function audit_token_dead_init($post_id) {
    $address = get_post_meta($post_id, 'address', true);

    $terms_token_status = get_the_terms($post_id , 'blockchain');
    $chain_id = null;
    foreach ($terms_token_status as $term) {					
        if ($term->name == 'Ethereum') {
            $chain_id = 1;
        } elseif ($term->name == 'Arbitrum') {
            $chain_id = 42161;
        }
    }

    $api_key = 'EFnfHRu3Zh3fRTJVdHOhmNoq8nNV8-UPMPxqTjMup1g';

    $transactions_url = "https://api.dev.dex.guru/v1/chain/$chain_id/tokens/$address/transactions?api-key=$api_key";
    $market_url = "https://api.dev.dex.guru/v1/chain/$chain_id/tokens/$address/market?api-key=$api_key";

    $data = get_api_data($transactions_url);
    if ($data === null) {
        $data = get_api_data($market_url);
    }

    if ($data === null) {
        return 'no_data';
    } else {
        $last_transaction_time = $data['data'][0]['timestamp'];
        $current_time = time();
        $seconds_since_last_transaction = $current_time - $last_transaction_time;
        $hours_since_last_transaction = $seconds_since_last_transaction / 3600;
        return $hours_since_last_transaction;        
    }
}


function get_api_data($url) {
    $max_retries = 3; 
    $try_count = 0; 

    do {
        $response = wp_remote_get($url);
        $try_count++;

        if (is_wp_error($response)) {
            sleep(1);
            continue;
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code >= 404) {
                sleep(1);
                continue;
            } else {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($data['data']) && isset($data['data'][0]['timestamp'])) {
                    return $data;
                }
            }
        }
    } while ($try_count < $max_retries);

    return null;
}




function archive_and_delete_post($post_id, $status) {
    global $wpdb;

    $address = get_post_meta($post_id, 'address', true);

    $token_immune = get_post_meta($post_id, 'token_immune', true);

    if ($token_immune) {
        send_discord_buyvolume_log('Trying to remove immune token: ' .  $address . ' \ ' . $post_id);
        return;
    }

    // Pobierz wszystkie metadane postu
    $metadata = get_post_meta($post_id);

    // Dodaj informacje o taxonomii
    $terms_token_blockchain = get_the_terms($post_id, 'blockchain');
    $blockchain = 'unknown';
    if ($terms_token_blockchain) {
        foreach ($terms_token_blockchain as $term) {
            $blockchain = $term->slug;
        }
    }
    $metadata['blockchain'] = $blockchain;

    // Dodaj adres
    $metadata['address'] = $address;

    // Dodaj status
    $metadata['status'] = $status;

    // Przekształć tablicę $metadata na łańcuch znaków JSON
    $metadata_json = json_encode($metadata);

    // Nazwa tabeli w bazie danych
    $table_name = $wpdb->prefix . 'archive_data';

    // Sprawdź, czy adres już istnieje w bazie danych
    $existing_entry = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE address = %s",
            $address
        )
    );

    if ($existing_entry) {
        // Adres już istnieje, zwiększ wartość remove_action o 1
        $remove_action = intval($existing_entry->remove_action) + 1;

        // Aktualizuj wpis dla istniejącego adresu
        $wpdb->update(
            $table_name,
            array(
                'blockchain' => $blockchain,
                'status' => $status,
                'metadata' => $metadata_json,
                'remove_action' => $remove_action
            ),
            array('address' => $address)
        );
    } else {
        // Adres nie istnieje, utwórz nowy wpis
        $wpdb->insert(
            $table_name,
            array(
                'address' => $address,
                'blockchain' => $blockchain,
                'status' => $status,
                'metadata' => json_encode($metadata),
                'remove_action' => 1
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d')
        );
    }

    if ($status == "scam"){
        if ($blockchain == 'ethereum') export_ETH_dead_or_scam_to_big_db($address, 666);
        if ($blockchain == 'arbitrum') export_ARB_dead_or_scam_to_big_db($address, 666);
    } 

    if ($status == "dead" || $status == "unverified" ){
        if ($blockchain == 'ethereum') export_ETH_dead_or_scam_to_big_db($address, 100);
        if ($blockchain == 'arbitrum') export_ARB_dead_or_scam_to_big_db($address, 100);
    } 
    
    wp_delete_post($post_id, true); 
    return;
}






function is_address_archived($address) {
    global $wpdb;

    // Jeśli adres jest pusty, zwróć false
    if (empty($address)) {
        return false;
    }

    // Nazwa tabeli w bazie danych
    $table_name = $wpdb->prefix . 'archive_data';

    // Sprawdź, czy adres istnieje w tabeli
    $existing_entry = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE address = %s",
            $address
        )
    );

    if ($existing_entry) {
        return true;
    }

    return false;
}


function export_ETH_dead_or_scam_to_big_db($address, $ContractType){
    $serverName = "hektordbserver.database.windows.net";
    $connectionInfo = array( "Database"=>"hektordatabase",
                            "UID"=>"maciek",
                            "PWD"=>"m@ciek3790123");

    $conn = sqlsrv_connect( $serverName, $connectionInfo);
    
    if( $conn === false )
    {   
        die( print_r( sqlsrv_errors(), true));
    }

    $array_data = sqlsrv_query($conn, "exec chain.wp_SetAccountsTokenContractAdditionalEthereumV1 @Address = '$address', @ContractType = $ContractType");

    if($array_data === false) {
        die( print_r( sqlsrv_errors(), true)); 
        return false;              
    } else {
        return true;
    }
}

function export_ARB_dead_or_scam_to_big_db($address, $ContractType){
    $serverName = "hektordbserver.database.windows.net";
    $connectionInfo = array( "Database"=>"hektordatabase",
                            "UID"=>"maciek",
                            "PWD"=>"m@ciek3790123");

    $conn = sqlsrv_connect( $serverName, $connectionInfo);
    
    if( $conn === false )
    {   
        die( print_r( sqlsrv_errors(), true));
    }

    $array_data = sqlsrv_query($conn, "exec chain.wp_SetAccountsTokenContractAdditionalArbitrumV1 @Address = '$address', @ContractType = $ContractType");

    if($array_data === false) {
        die( print_r( sqlsrv_errors(), true)); 
        return false;              
    } else {
        return true;
    }
}

