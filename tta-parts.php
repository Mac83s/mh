<?php












////////////////////////////////////////////////disabled
//++++++++++++++++++++++
//++
//+++ Get twitter data
//++
//++++++++++++++++++++++
 
function get_twitter_data(){ ///// disabled

	$loop_serie = get_option('twitter_loop');

    if (!is_array($loop_serie)) {
        $loop_serie = array(
            'loop_serie' => 1,
        );
    }

    $order_direction = get_transient('twitter_order_direction');
    if (empty($order_direction)) {
        $order_direction = 'asc';
    }
    $current_loop_serie = $loop_serie['loop_serie'];

    $message =  'Start Twitter Loop nr ' . $current_loop_serie;
    $address = '-';
    $procces = 'Twitter Account Scan';
    $post_id = '-';

    save_log_to_file($post_id,$address,$procces,$message);
	send_discord_twitter_log('Start Twitter Loop ' . $current_loop_serie . ' ' . date("h:i:s"));

    $args = array(
        'post_type' => 'tokens',
        'posts_per_page' => -1,
		'order' => $order_direction,
        'orderby' => 'date',
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'twitter_account_url',
				'compare' => 'EXISTS'
			),
            array(
                'key' => 'twitter_blocked',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'relation' => 'OR',
                array(
                    'key' => 'twitter_loop_serie',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => 'twitter_loop_serie',
                    'value' => $current_loop_serie,
                    'compare' => '!=',
                ),
                array(
                    'key' => 'blocked_token',
                    'compare' => 'NOT EXISTS'
                ),
             
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
    );

    $tokens = new WP_Query($args);
    if ($tokens->have_posts()) {
       
        $all_tokens = $tokens->found_posts;
        $i = 0;
        while ($tokens->have_posts()) {
            try {
                $tokens->the_post();
                $post_id = get_the_ID();

                $i++;
                $counter = $i . '/' . $all_tokens;
                save_heartbeat_to_file($post_id, $counter,  'twitter_heartbeat');

                update_post_meta($post_id, 'twitter_loop_serie', $current_loop_serie);

                $error_data = get_post_meta( $post_id, 'twitter_error',true);
                if (is_array($error_data) && count($error_data) > 3) {
                    $error_data = $error_data[0]['count'];
                    
                    // Clear data
                    delete_post_meta($post_id, 'twitter_account_filtering');

                    // Remove token from twitter loop
                    $address = get_post_meta( $post_id, 'address',true);
                    $message = 'Twitter removed > ' . $error_data;
                    $procces = 'Twitter Account Scan';
                    save_log_to_file($post_id,$address,$procces,$message);
                    send_discord_twitter_log($post_id . ' Twitter removed reason: ' . $error_data . ' - ' . date("h:i:s"));				
                    update_field_with_history_if_changed('token_changelog', 'Twitter removed reason: ' . $error_data , $post_id);            
                               
                    FWP()->indexer->index( $post_id );
                    continue;
                }

                update_twitter($post_id);	
                FWP()->indexer->index( $post_id );
            } catch (Exception $e) {
                send_discord_twitter_log('Twitter Loop > Wystąpił błąd podczas przetwarzania postu: ' . $e->getMessage() . ' ' . current_time('mysql'));
                delete_transient('twitter_heartbeat');
            }
		
        }

        $message =  'End Twitter Loop nr ' . $loop_serie['loop_serie'] . ' ' . $order_direction;
        $address = '-';
        $procces = 'Twitter Account Scan';
        $post_id = '-';
        save_log_to_file($post_id,$address,$procces,$message);
        
        $loop_serie['loop_serie']++;
	    update_option('twitter_loop', $loop_serie);

         // Zmień wartość w transient na przeciwną
        if ($order_direction == 'asc') {
            $order_direction = 'desc';
        } else {
            $order_direction = 'asc';
        }
        set_transient('twitter_order_direction', $order_direction, 60 * 60); // 1 godzina
       
    }
}

//+++ END Twitter Data+++ 


