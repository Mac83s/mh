<?php

add_action('2minutescron','init_get_contract_owner_tansactions');
function init_get_contract_owner_tansactions() { 
	$args = array(
		'post_type' => 'tokens',
		'posts_per_page' => -1,
		'tax_query' => array(
			array(
				'taxonomy' => 'token_status', 
				'field' => 'slug',
				'terms' => array('verified'), 
				'operator' => 'IN',
			),
		),
		'date_query' => array(
			array(
				'after' => '5 hour ago'
			)
		)
	);
	
	$tokens = new WP_Query($args);
	
	if ($tokens->have_posts()) {	
  
		while ($tokens->have_posts()) {
			$tokens->the_post();
			$post_id = get_the_ID(); 
			get_contract_owner_tansactions($post_id);
			
		}
		wp_reset_postdata();
	}
	
}

//+++ END +++ Check token status by etherscan API


function get_contract_owner_tansactions($post_id) {
	if (!$post_id) return;

	$creatorcontract = get_post_meta( $post_id, 'creatorcontract',true);
	$token_address = get_post_meta( $post_id, 'address',true);

    $terms_token_status = get_the_terms( $post_id ,  'blockchain' );
	foreach ( $terms_token_status as $term ) {					
        $tax_term = $term->name;

		if ($term->name == 'Ethereum'){			
            global $ethereum_api_2;  			
	        $url = "https://api.etherscan.io/api?module=account&action=txlist&address=$creatorcontract&offset=20&sort=desc&apikey=$ethereum_api_2";
		}
		if ($term->name == 'Arbitrum'){
            global $arbitrum_api;    
			$url = "https://api.arbiscan.io/api?module=account&action=txlist&address=$creatorcontract&offset=20&sort=desc&apikey=$arbitrum_api";
            
		}
	}
    
    if (!$url) return;

	$response = file_get_contents($url);
	$result = json_decode($response, true);

	$targetFunctionNames = [
		'renounceOwnership',
        'addLiquidityETH',
        'lockLPToken',
        'removeLimits',
        'openTrading',
        'updateBuyFees',
        'updateSellFees',
        'setFee',
        'setMaxWalletSize',
        'lock',
        'removeLiquidity',
        'removeLiquidityETHWithPermit',
        'removeLiquidityWithPermit',
        'removeliquidityethwithpermitsupportingfeeontransfertokens',
	];
    if (isset($result) && (is_array($result['result']) || is_object($result['result']))) {
        foreach ($result['result'] as $tx) {
            foreach ($targetFunctionNames as $fn) {
                if (strpos($tx['functionName'], $fn) !== false) {

                    // Skip if the field already exists
                    if (get_post_meta($post_id, $fn, true) !== '') {
                        continue;
                    }
                    
                    // if ($tx['to'] === $token_address) {} // todo test godziny i sprawdzać tylko tx po godzinie deploy
                    $timestamp = $tx['timeStamp'];
                    $date = date('d-m-y H:i:s', $timestamp);			
                    $update_value = array('etherscan_date' => $date);
                    update_post_meta($post_id, $fn, $update_value);
                    
                    if ($fn === 'lockLPToken' || $fn === 'lock' ){
                        update_post_meta( $post_id, 'locked_lp_token', 'Yes', true ); 
                    }
                    if ($fn === 'addLiquidityETH'){
                        update_post_meta( $post_id, 'add_liquidity', 'Yes', true );
                    }
                    if ($fn === 'renounceOwnership'){
                        $current_owner = get_current_owner($post_id);
                        if ($current_owner !== null && strpos($current_owner, '0x00000000000') !== false) {
                            update_post_meta( $post_id, 'owner_renounced', 'Yes', true );
                        }
                    } else { 
                        delete_post_meta( $post_id, 'owner_renounced' );
                    }
                        
                }

                if ($tx['from'] == '0xba6e11347856c79797af6b2eac93a8145746b4f9') { // scam-warning.eth
                    
                    // Po wykryciu od razu oznaczamy jako SCAM
                    if (!get_post_meta($post_id, 'blocked_token', true) == 'SCAM') {
                        update_post_meta( $post_id, 'blocked_token', 'SCAM' );
                        wp_set_object_terms( $post_id, 'scam' , 'token_status' );
                        update_field_with_history_if_changed('token_changelog', 'Oznaczony przez scam-warning.eth', $post_id);                
                    }
                    
                }

            }
        }
	
	    FWP()->indexer->index( $post_id );
	} else {
        //Daj error log lub info że dostalismy blokadę od explorera
    }
}

//++++++++++++++++++++++
//++
//+++ Owner Renounced Confirmation 
//++
//++++++++++++++++++++++

add_action('2minutescron','update_get_current_owner');
function update_get_current_owner() { 
	send_discord_error_log('update_get_current_owner' . date("h:i:s"));
	$args = array(
		'post_type' => 'tokens',
		'posts_per_page' => -1,
		'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'blocked_token',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key' => 'owner_renounced',
                'compare' => 'NOT EXISTS',
            ),
        ),
		'tax_query' => array(
			array(
				'taxonomy' => 'token_status', 
				'field' => 'slug',
				'terms' => array('verified'), 
				'operator' => 'IN',
			),
		),
		'date_query' => array(
			array(
				'after' => '72 hour ago'
			)
		)
	);
	
	$tokens = new WP_Query($args);
	
	if ($tokens->have_posts()) {
		$all_tokens = $tokens->found_posts;
        $i = 0;
		while ($tokens->have_posts()) {

			$tokens->the_post();

			$post_id = get_the_ID(); 
			$i++;
			$counter = $i . '/' . $all_tokens;
			save_heartbeat_to_file($post_id, $counter,  'owner_heartbeat');
			
			$current_owner = get_current_owner($post_id);
			if ($current_owner !== null && strpos($current_owner, '0x0000000000000000000000000000000000000000000000000000000000000000') !== false) {
				update_post_meta( $post_id, 'owner_renounced', 'Yes', true );
				update_field_with_history('token_changelog', 'Owner Renounced potwierdzony na blockchain.', $post_id);
			} else {
				delete_post_meta( $post_id, 'owner_renounced' );
			}

			FWP()->indexer->index( $post_id );

		}
		wp_reset_postdata();
	}
	
}

// //+++ END +++ 


//++++++++++++++++++++++
//++
//+++ Get Current Contract Owner
//++
//++++++++++++++++++++++

function get_current_owner($post_id) {

	$token_address = get_post_meta( $post_id, 'address',true);

	$terms_token_status = get_the_terms( $post_id ,  'blockchain' );
    $url = '';
	foreach ( $terms_token_status as $term ) {					
        $tax_term = $term->name;

		if ($term->name == 'Ethereum'){			
            global $ETHalchemyapi;  			
	        $url = $ETHalchemyapi;
		}
		if ($term->name == 'Arbitrum'){
            global $ARBalchemyapi;    
			$url = $ARBalchemyapi;
            
		}
	}

    if (!$url) return;

	$data = array(
        'jsonrpc' => '2.0',
        'method' => 'eth_call',
        'params' => array(
            array(
                'to' => $token_address,
                'data' => '0x8da5cb5b' // funkcja owner() w ABI
            ),
            'latest'
        ),
        'id' => 1
    );
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ),
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $resultObj = json_decode($result);
    $owner = isset($resultObj->result) ? $resultObj->result : null;
    return $owner;

}



