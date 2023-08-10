<?php


function audit_token_tta( $post_id ) {

    $status = audit_token_tta_init( $post_id );

    if (is_numeric($status)) { 
        $status = 600;
    } elseif ($status == 'overload'){ // deprecated without twitter api
        $status = 5;
    } elseif ($status == 'skip'){
        $status = 160000; // co ~48h
    }

    return  $status;
   
}

function audit_token_tta_init( $post_id ) {
    
    $error_data = get_post_meta( $post_id, 'twitter_error',true);
    if (is_array($error_data) && count($error_data) > 3) {
        $error_data = $error_data[0]['count'];
        
        // Clear data
        delete_post_meta($post_id, 'twitter_account_filtering');
        delete_post_meta($post_id, 'twitter_error');

        // Remove token from twitter loop
        $message = 'Twitter removed > ' . $error_data;
        update_field_with_history_if_changed('token_changelog', 'Twitter removed reason: ' . $error_data , $post_id);         

        // Dac ostrzeżenie że cos nie tak 
        ////////////////////////////////////////

        $status = 'skip';
        
    } else {
        $status = update_twitter($post_id);      
    }

  	return $status;
}



function update_twitter($post_id){

    $twitter_account_name = get_post_meta( $post_id, 'twitter_account_name',true);
    $twitter_account_name_from_url = strtolower(basename(get_post_meta( $post_id, 'twitter_account_url',true)));
    if ($twitter_account_name != $twitter_account_name_from_url) {
        update_post_meta($post_id, 'twitter_account_name', $twitter_account_name_from_url);
    }
    $status = "no_data";

    $twitter_account_name = get_post_meta( $post_id, 'twitter_account_name',true);
    if ($twitter_account_name) {

        update_post_meta($post_id, 'twitter_account_filtering', 'Twitter');
   
        $twitter_data = return_twitter_user_data($twitter_account_name);

        $followers = '';
        if (is_array($twitter_data['props']['pageProps']['timeline']['entries'])) {
            if (isset($twitter_data['props']['pageProps']['timeline']['entries'][0])) {
                $tweet = $twitter_data['props']['pageProps']['timeline']['entries'][0]['content']['tweet'];
                
                $followers = $tweet['user']['normal_followers_count'];
                $tweets = $tweet['user']['statuses_count'];
                $listed = $tweet['user']['listed_count'];
                // $verified = $tweet['user']['is_blue_verified'];
                // $created_at = $tweet['user']['created_at'];
                // $name = $tweet['user']['name'];
                // $description = $tweet['user']['description'];
                // $user_id = $tweet['user']['id_str'];
                // $location = $tweet['user']['location'];
                if ($followers > 0 ) {
                    update_field_with_history_if_changed('twitter_followers',$followers, $post_id);
                    update_field_with_history_if_changed('tweet_count',$tweets, $post_id);
                    update_field_with_history_if_changed('tweeter_listed',$listed, $post_id);

                    if(get_post_meta( $post_id, 'twitter_error',true)) {
                        delete_post_meta($post_id,  'twitter_error');
                    }
                        
                    $status = $followers;
                } else {
                    $status = 'skip';
                }
                
            } else {
                $status = 'skip';
            }
        } else {
            $status = 'skip';
        }
      
    } else {
        $status = 'skip';
    }

    return $status;
         
}




function return_twitter_user_data($twitter_username){

    $url = "https://syndication.twitter.com/srv/timeline-profile/screen-name/$twitter_username?dnt=true&embedId=twitter-widget-0&frame=false&hideBorder=false&hideFooter=false&hideHeader=false&hideScrollBar=false&lang=en&limit=1";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    curl_close($ch);

    $doc = new DOMDocument();
    libxml_use_internal_errors(true); // aby uniknąć ostrzeżeń
    $doc->loadHTML($output);
    libxml_use_internal_errors(false);

    $scripts = $doc->getElementsByTagName('script');

    foreach ($scripts as $script) {
        if ($script->getAttribute('id') == "__NEXT_DATA__") {
            $json = $script->nodeValue; // Pobierz JSON jako ciąg znaków
            break;
        }
    }

    $data = json_decode($json, true);       

    return $data;
}



/* AJAX Refresh Twitter Followers */

add_action( 'wp_ajax_nopriv_refresh_twitter_ajax', 'refresh_twitter_ajax' );
add_action( 'wp_ajax_refresh_twitter_ajax', 'refresh_twitter_ajax' );

function refresh_twitter_ajax(){

	global $wpdb;
    $post_id = $_POST['post_id'];

	$followers = update_twitter($post_id);

    if (is_numeric($followers)) {
		delete_post_meta($post_id, 'twitter_blocked');
		FWP()->indexer->index( $post_id );
        $response = array(	
            'success' => true,
            'followers' => $followers
        );
    } else {
        $response = array(
            'success' => false,
            'error' => $followers
        );
    }

    wp_send_json($response);
	
}