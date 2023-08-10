<?php

// Arbitrum
$ARBalchemyapi = "https://arb-mainnet.g.alchemy.com/v2/11SrojGKITJ6qXjtTDt-YCTELJECJuU3"; // Alchemy
$arbitrum_api = "AYKIBT83KDMG7KC45DKBI8E624UK42PGX2"; // Arbiscan


// Etehereum
$ETHalchemyapi = 'https://eth-mainnet.g.alchemy.com/v2/Mo4N-R-28M8GVJiFT1s_EMPnQBhHOq9u'; // Alchemy
$ethereum_api = 'C6NDHKTA7K3EAM4KTZVMKZNKXMNSDX12CF'; // Etherscan
$ethereum_api_2 = '1GTIVSJMZXX3W6R3W97PCD714GV8624C7I'; // Etherscan

// Etehereum
$bsc_api = 'UWM5TZEIRRM5Z8857P8XD3HKSCKRF4BRUD'; // BSC




//++++++++++++++++++++++
//++
//+++ Check Remove LQ method on contract
//++
//++++++++++++++++++++++

function check_remove_liquidity($post_id) {
	if (!$post_id) return;

	$address = get_post_meta( $post_id, 'address',true);

    if(!$address) {
        return 'skip';
    }

    $terms_token_blockchain = get_the_terms( $post_id ,  'blockchain' );
    if (is_array($terms_token_blockchain)) {
        foreach ( $terms_token_blockchain as $term ) {					
            $explorer_api = get_blockchain_cf( $term->term_id, 'explorer_api' );
            $explorer = get_blockchain_cf( $term->term_id, 'explorer' );
            $url = "https://api.$explorer/api?module=account&action=txlist&address=$address&offset=20&sort=desc&apikey=$explorer_api";
        }
    } else {
        error_log('Błąd: REM Brak terminów dla posta o ID: ' . $post_id  . ' ' . get_permalink($post_id));
        return;
    }
    
	if (!isset($url) || !$url) {
        return;
    }

	$response = file_get_contents($url);
	$result = json_decode($response, true);

	$targetFunctionNames = [
        'removeLiquidity',
        'removeLiquidityETHWithPermit',
        'removeLiquidityWithPermit',
        'removeliquidityethwithpermitsupportingfeeontransfertokens',
	];
    if (isset($result['result']) && is_array($result['result'])) {
        foreach ($result['result'] as $tx) {
            foreach ($targetFunctionNames as $fn) {
                if (strpos($tx['functionName'], $fn) !== false) {

                    $timestamp = $tx['timeStamp'];
                    $date = date('d-m-y H:i:s', $timestamp);
                    $current_date = date('d-m-y H:i:s');
                    $update_value = array('etherscan_date' => $date, 'current_date' => $current_date);
                    update_post_meta($post_id, $fn, $update_value);

                    // Immunitet //
                    $immunity_test = get_post_meta($post_id, 'token_immune', true);
                    if (!empty($immunity_test)) continue;
                   
                    // Zapisz ostrzeżenie
                    $message = 'Remove Liquidity';    
                    update_post_meta( $post_id, 'warning', $message );                
                    update_field_with_history('token_changelog',$message , $post_id);
                    
                }

            }
        }
	}
	
}
//+++ END +++ Check token owner transactions  by etherscan API 


