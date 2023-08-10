<?php

//++++++++++++++++++++++
//++
//+++ Get data from Dex Screener API
//++
//++++++++++++++++++++++


function audit_token_vfn( $post_id ) {
    if (!$post_id) return;

	$token_address = get_post_meta( $post_id, 'address',true);
	if(!$token_address) {
        return 'skip';
    }

    if (verify_contract_($post_id) === true){
        wp_set_object_terms( $post_id, 'verified' , 'token_status' );
        update_post_meta( $post_id, 'contract_verification_date', current_time('mysql')); 
        update_field_with_history('token_changelog', 'Kontrakt zweryfikowany', $post_id);

		get_urls_from_contract($post_id);

		
		// Update SYMBOL data for token
		$symbol = get_post_meta($post_id, 'symbol', true);
		$args = array(
			'post_type'  => 'symbol_container',
			'name'       => $symbol,
            'posts_per_page' => 1,
		);
	
		$posts = get_posts($args);			
	
		if (!empty($posts)) {
			foreach ($posts as $post) {
				$symbol_id = $post->ID;
				$influencers = get_post_meta($symbol_id, 'symbol_mentions_influencers', true);
				$total_mentions = get_post_meta($symbol_id, 'symbol_mentions_total', true);
			
				update_post_meta($post_id, 'symbol_id', $symbol_id);
				update_post_meta($post_id, 'symbol_mentions_influencers', $influencers);
				update_post_meta($post_id, 'symbol_mentions_total', $total_mentions);
			}          
		} 
		wp_reset_postdata();
		
		// move to main loop
        add_post_id_to_file($post_id, 'dex');
        $status = 'remove';			

    } else {
		$create_date = get_post_meta( $post_id, 'create_date',true);
		if (empty($create_date)) {
			$create_date = get_the_date('Y-m-d H:i:s', $post_id);
		}
		if ($create_date) {
			$now = current_time('timestamp'); 
			$create_date = DateTime::createFromFormat('Y-m-d H:i:s', $create_date);
			$create_date_timestamp = $create_date->getTimestamp();

			$difference = $now - $create_date_timestamp;

			if ($difference > 43200) {
				wp_set_object_terms( $post_id, 'unverified' , 'token_status' );
				update_field_with_history('token_changelog', 'Kontrakt niezweryfikowany w pierwszych 12h.', $post_id);
				add_post_id_to_file($post_id, 'dead');
				return 'remove'; // remove and forget	
			} else {
				$status = 300;
			}
		} 
	}
    
    return $status;
}



//++++++++++++++++++++++
//++
//+++ Check token contract verification
//++
//++++++++++++++++++++++

function verify_contract_($post_id) {
	if (!$post_id) return;
	sleep(1);
    $token_address = get_post_meta( $post_id, 'address',true);

	$terms_token_blockchain = get_the_terms( $post_id ,  'blockchain' );
    if (is_array($terms_token_blockchain)) {
        foreach ( $terms_token_blockchain as $term ) {					
            $explorer_api = get_blockchain_cf( $term->term_id, 'explorer_api' );
            $explorer = get_blockchain_cf( $term->term_id, 'explorer' );
            $url = "https://api.$explorer/api?module=contract&action=getabi&address=$token_address&apikey=$explorer_api";
        }
	} else {
		error_log('Błąd: Brak terminów dla posta o ID: ' . $post_id  . ' ' . get_permalink($post_id));
        return;
	}
    
	$json = file_get_contents($url);
	$data = json_decode($json, true);

	// If get message OK contract exist and is verified
	if (isset($data) && isset($data['message']) && $data['message'] == 'OK') {
		$result = true;
	} else {
		$result = false;
	}
	
	return $result;
}


//++++++++++++++++++++++
//++
//+++ Get URLs from contract
//++
//++++++++++++++++++++++

function get_urls_from_contract($post_id) { 
    if (!$post_id) return;

	$token_address = get_post_meta( $post_id, 'address',true);

	$text =  get_contract_code($post_id);

	preg_match_all('/(http|https):\/\/[^\s\*]+/', $text, $matches);
	
	$website_updated = false;
    $allurls = '';
	foreach (array_slice($matches[0], 0, 30) as $url) {
		if (strpos($url, "twitter") !== false) {

            update_post_meta($post_id, 'twitter_account_url', $url);

			add_post_id_to_file($post_id, 'tta');

			$twitter_name = strtolower(basename($url));
			if ($twitter_name) {
                update_post_meta($post_id, 'twitter_account_name', $twitter_name);
			}			
			
		} elseif (strpos($url, "t.me") !== false) {
            update_post_meta($post_id, 'telegram_url', $url);

			$class = '.tgme_page_extra';
			$data =  scrapeURL($url, $class);

            update_post_meta($post_id, 'telegram_subscribers', $data);
			update_post_meta($post_id, 'telegram_account_filtering', 'Telegram');
			
		}elseif (strpos($url, "medium") !== false) {
            update_post_meta($post_id, 'medium_url', $url);
			update_post_meta($post_id, 'medium_account_filtering', 'Medium');
		} else {
            if (!$website_updated && preg_match('/^\/\*\*\n\s*(https?:\/\/[^\s]+)/', $text, $website_match)) {
            // if (!$website_updated) {
                update_post_meta($post_id, 'website', $website_match[1]);
				update_post_meta($post_id, 'website_filtering', 'Website');
				$website_updated = true;
			} 
        } 
		
		$allurls .= $url ;
	}

	// Uaktualnij treść postu
	$update_post = array(
		'ID'           => $post_id,
		'post_content' => $allurls
	);

	wp_update_post($update_post);
}


//++++++++++++++++++++++
//++
//+++ Get contract code by etherscan API 
//++
//++++++++++++++++++++++
function get_contract_code($post_id) {
	if (!$post_id) return;

    $token_address = get_post_meta( $post_id, 'address',true);

    $terms_token_blockchain = get_the_terms( $post_id ,  'blockchain' );
    if (is_array($terms_token_blockchain)) {
        foreach ( $terms_token_blockchain as $term ) {					
            $explorer_api = get_blockchain_cf( $term->term_id, 'explorer_api' );
            $explorer = get_blockchain_cf( $term->term_id, 'explorer' );
			$url = "https://api.$explorer/api?module=contract&action=getsourcecode&address=$token_address&apikey=$explorer_api";
        }					
	}

	$response = file_get_contents($url);
    $obj = json_decode($response);
    $sourceCode = $obj->result[0]->SourceCode;  
	// $source_code = str_replace(['\\n', '\r\n'], PHP_EOL, $source_code);
    return $sourceCode;
}