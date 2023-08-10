<?php

// add_action('5minutescron','update_new_followings');
function  update_new_followings(){

    $serverName = "hektordbserver.database.windows.net";
    $connectionInfo = array( "Database"=>"hektordatabase",
                            "UID"=>"maciek",
                            "PWD"=>"m@ciek3790123");
    $conn = sqlsrv_connect( $serverName, $connectionInfo);    
    if( $conn === false )
    {
        die( print_r( sqlsrv_errors(), true));
    }
    $lastcheckDateTime = get_option('wp_GetTwitterAccountFollowingV1_lastcheck');

    $array_data = sqlsrv_query($conn, 'exec chain.wp_GetTwitterAccountFollowingV1 @UpdatedAt = "'.$lastcheckDateTime.'"' );    
    if($array_data === false) {
        die( print_r( sqlsrv_errors(), true));
        echo 'Error';
    }
    $Next_UserAddedAt = '';

    while ($row = sqlsrv_fetch_array($array_data, SQLSRV_FETCH_ASSOC)) {
 
        $Influencer = $row['Influencer'];
        $account = $row['Follower']; 
        $UserAddedAt = $row['UserAddedAt']->format("Y-m-d H:i:s");
        if(!$Next_UserAddedAt){
            $Next_UserAddedAt = $UserAddedAt;
        }

        $args = array(
            'post_type'  => 'twitter_account',
            'name'       => $account,
            'posts_per_page' => 1,
        );
        $posts = get_posts($args);

        if (empty($posts)) {
            $new_post_id = wp_insert_post(array(
                'post_title'      => $account ,
                'post_type'       => 'twitter_account',
                'post_status'     => 'publish',
            ));

            update_post_meta($new_post_id, "influencer", array($Influencer => array('user_followed_at'=> $UserAddedAt)));

        } else {
            $post_id = $posts[0]->ID;

            $influencers = get_post_meta($post_id, "influencer", true);
    
            if (!is_array($influencers)) {
                $influencers = array();
            }
            
            if (!array_key_exists($Influencer,$influencers)){
                $influencers[$Influencer] = array('user_followed_at'=> $UserAddedAt );
                update_post_meta($post_id,"influencer",$influencers);
            }
        }
        wp_reset_postdata();

        update_option('wp_GetTwitterAccountFollowingV1_lastcheck', $Next_UserAddedAt);

    }
}


// add_action('2minutescron','start_accounts_analysis');
function start_accounts_analysis(){
    
    $args = array(
        'post_type'      => 'twitter_account',
        'posts_per_page' => 3,
        'meta_query'     => array(
            'relation' => 'AND', 
            array(
                'relation' => 'OR', 
                array(
                    'key'     => 'precision',
                    'compare' => 'NOT EXISTS',
                ),          
                array(
                    'key'     => 'precision',
                    'value'   => 5,
                    'type'    => 'numeric',
                    'compare' => '<',
                ),
            ),
            array(
                'key'     => 'blocked',
                'compare' => 'NOT EXISTS',
            ),
        ),
    );
    $query = new WP_Query($args);
    if ($query->have_posts()) { 
        while ($query->have_posts()) {
            
            $query->the_post();      
            $post_id = get_the_ID();
            $account = get_the_title();
            
            // Sprawdź Tweeter i pobierz dane konta oraz 5 najnowszych i najstarszych tweetów
            $twitter_data = check_twetter_account($account);
            if ($twitter_data == 'Not Found Error'){
                update_post_meta($post_id, "blocked" , 'Not Found Error');
                continue;
            }

            // Analiza OpenAI
            if (is_array($twitter_data)) {

                $ai_response = '';
                $system_prompt = 'You are a tweeter account analytic.';
                $prompt_start = 'Examine the provided tweeter account data and categorize it as a "cryptocurrency token account", an "cryptocurrency influencer", or "other". Rate your confidence in your assessment from 0 to 10. Also, indicate keywords that you think might point to the account category as guidance. Focus on respond in JSON: {"account_type": "<Your determination>", "precision": <Your confidence score>, "guidance": <Your confidence guidance>}. Here is data to analysis: ';
                $ai_response = openai_twitter_analysis($system_prompt, $prompt_start, $twitter_data);

                if (is_array($ai_response)) {
                    $array = json_decode($ai_response['answer'], true);
                    if (($array)){
                        if ($array['account_type'] == 'cryptocurrency influencer')  $tax = 'Influencer';
                        if ($array['account_type'] == 'cryptocurrency token account')  $tax = 'Token';
                        if ($array['account_type'] == 'other')  $tax = 'Other';
            
                        wp_set_object_terms( $post_id, $tax, 'account_type' );
                        update_post_meta($post_id, "prompt" , $twitter_data);
                        update_post_meta($post_id, "precision" , $array['precision']);
                        update_post_meta($post_id, "guidance" , $array['guidance']);
                        update_post_meta($post_id, "total_cost" , $ai_response['total_cost']);
                    }
                   
                }
            }
            sleep(3);
        }

        wp_reset_postdata();
    }
    
}


function check_twetter_account($account){
    
    $api_url = 'https://api.twitter.com/2/users/by/username/' . $account . '?user.fields=public_metrics,created_at,description,pinned_tweet_id,verified';
    $twitter_data = return_twitter_api_data($api_url);
    if ($twitter_data == 'Not Found Error'){
        return 'Not Found Error';
    }

    if (is_array($twitter_data)) {

        $tweets_combined_data = array();
        
        $tweets_combined_data['account'] = $account;
        $tweets_combined_data['description'] = $twitter_data['description'];

        // ----------------------------
        // Get account details

        $account_id = $twitter_data['id'];
        $account_creation_date = $twitter_data['created_at'];
        $account_creation_date_formated = date("Y-m-d H:i:s", strtotime($account_creation_date));
        // $pinned_tweet_id = $twitter_data['pinned_tweet_id'];
        $verified = $twitter_data['verified'];
        
        $public_metrics = $twitter_data['public_metrics'];
            $followers_count = $public_metrics['followers_count'];
            $tweet_count = $public_metrics['tweet_count'];
            $listed_count = $public_metrics['listed_count'];

        $args = array(
            'post_type'  => 'twitter_account',
            'name'       => $account,
            'posts_per_page' => 1,
        );

        $posts = get_posts($args);
        if (!empty($posts)) {
              
            $post_id = $posts[0]->ID;

            update_post_meta($post_id, "account_id" , $account_id);
            update_post_meta($post_id, "account_creation_date" , $account_creation_date_formated);
            update_post_meta($post_id, "verified" , $verified);
            update_post_meta($post_id, "followers_count" , $followers_count);
            update_post_meta($post_id, "tweet_count" , $tweet_count);
            update_post_meta($post_id, "listed_count" , $listed_count);
            
        } else {
            echo 'error2'; 
        }
        wp_reset_postdata();
        

        // ----------------------------
        // Get recent tweets
        $api_url = 'https://api.twitter.com/2/users/' . $account_id . '/tweets?tweet.fields=created_at,public_metrics&max_results=5';
        $user_tweets_recent = return_twitter_api_data($api_url);

        if (is_array($user_tweets_recent)) {
            foreach ($user_tweets_recent as $tweet) {
                if (isset($tweet['text'])) {
        
                    $tweet_created_at = $tweet['created_at'];
                    $tweets_combined_data[$tweet_created_at] = array(
                        'text' => $tweet['text'],
                        // 'like_count' => $tweet['public_metrics']['like_count'],
                        // 'retweet_count' => $tweet['public_metrics']['retweet_count'],
                        // 'reply_count' => $tweet['public_metrics']['reply_count'],
                        // 'quote_count' => $tweet['public_metrics']['quote_count'],
                        // 'bookmark_count' => $tweet['public_metrics']['bookmark_count'],
                        // 'impression_count' => $tweet['public_metrics']['impression_count'],
                    );
                }
            }
        } else {
            echo 'error3'; 
        }


        // ----------------------------
        // Get oldest tweets

        $account_creation_datetime = DateTime::createFromFormat("Y-m-d\TH:i:s.000\Z", $account_creation_date);
        $maximum_date = new DateTime();
        $maximum_date->modify('-9 years');
        if ($account_creation_datetime < $maximum_date) {
            $account_creation_date = $maximum_date->format("Y-m-d\TH:i:s.000\Z");
        }

        $api_url = 'https://api.twitter.com/2/users/' . $account_id . '/tweets?tweet.fields=created_at,public_metrics&start_time=' . $account_creation_date . '&max_results=5';
        $user_tweets_oldest = return_twitter_api_data($api_url);

        if (is_array($user_tweets_oldest)) {
            foreach ($user_tweets_oldest as $tweet) {
                if (isset($tweet['text'])) {
        
                    $tweet_created_at = $tweet['created_at'];
                    $tweets_combined_data[$tweet_created_at] = array(
                        'text' => $tweet['text'],
                        // 'like_count' => $tweet['public_metrics']['like_count'],
                        // 'retweet_count' => $tweet['public_metrics']['retweet_count'],
                        // 'reply_count' => $tweet['public_metrics']['reply_count'],
                        // 'quote_count' => $tweet['public_metrics']['quote_count'],
                        // 'bookmark_count' => $tweet['public_metrics']['bookmark_count'],
                        // 'impression_count' => $tweet['public_metrics']['impression_count'],
                    );
                }
            }
        } else {
            echo '<br/> Error no data from Twitter <br/>';  
        }

    } else {

        $status = 'error'; 
        echo '<br/> error5 <br/>'; 

    }

    $status = $tweets_combined_data;

    return $status;
}
    



function return_twitter_api_data($api_url){

    $bearer_tokens = [
        'AAAAAAAAAAAAAAAAAAAAAHMxkAEAAAAACJf5yMx2xw61q3zHuuxrCDaKET4%3DTvOxwtsqc2YZ5thfXHFnKbihnhvvbkGFK4JABSlkpMhrJfiG7d'
    ];

    $token_index = get_transient('twitter_bearer_token_index');
    if ($token_index === false || !isset($bearer_tokens[$token_index])) {
        $token_index = 0;
    }

    $bearer_token = $bearer_tokens[$token_index];

    $next_token_index = ($token_index + 1) % count($bearer_tokens);
    set_transient('twitter_bearer_token_index', $next_token_index, 60 * 30); 
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer '.$bearer_token,
        'Content-Type: application/json',
        'User-Agent: v2FilteredStreamPHP'
    ));
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['errors'])) {
        if (isset($data['errors'][0]['title'])) {
            $error_message = $data['errors'][0]['title'];
        } elseif (isset($data['errors'][0]['message'])) {
            $error_message = $data['errors'][0]['message'];
        } else {
            $error_message = 'Brak informacji o błędzie';
        }

        $status = $error_message;
       
    } elseif( isset($data['status']) && $data['status'] == 429 ) {
       
        $status = "Overload";  

    } else {
        $status = $data['data'];
    }

    return $status;
}




function openai_twitter_analysis($system_prompt, $prompt_start, $prompt_question){

    if (is_array($prompt_question)){
        
        $result = $prompt_start;
        foreach ($prompt_question as $key => $value) {
            if(is_array($value)) {
                foreach($value as $subKey => $subValue) {
                    $result .= $key . '=>' . $subValue;
                }
                $result .= "\n";
            } else {
                $result .= $value . "\n";
            }
        }
    } else {
        $result = $prompt_question;
    }

    $openai_keys = [
        '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',
        // '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',
    ];
    $key_index = get_transient('openai_key_index');
    if ($key_index === false || !isset($openai_keys[$key_index])) {
        $key_index = 0;
    }

    $openai_key = $openai_keys[$key_index];

    // Zapisz indeks następnego klucza, który ma być użyty
    $next_key_index = ($key_index + 1) % count($openai_keys);
    set_transient('openai_key_index', $next_key_index, 60 * 5); // ustaw na godzinę

    $openai_url = 'https://api.openai.com/v1/chat/completions';
    $openai_model_id = 'gpt-3.5-turbo';

    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_key,
    );

    $data = array(
        'model' => $openai_model_id,
        'messages' => array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $result)
        ),
        'max_tokens' => 1000,
        'temperature' => 0.3,
    );

    $status = false;
    for($i=0; $i<3; $i++) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $openai_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($curl);

        if ($response !== false) {
            $data = json_decode($response, true);
            
            if (isset($data['choices'][0]['message']['content'])) {
                $answer = $data['choices'][0]['message']['content'];
                $prompt_tokens = $data['usage']['prompt_tokens'];
                $completion_tokens =  $data['usage']['completion_tokens'];
                $total_tokens = $data['usage']['total_tokens'];
                $total_cost = '$'.($total_tokens / 1000) * 0.002;
                if ($answer){
                    $status = array(
                        'answer' => $answer,
                        'total_cost' => $total_cost,
                    );
                    break;
                }
            } 
        }
        curl_close($curl);	
    }
    if(!$status){
        echo 'Błąd curl: ' . curl_error($curl);
    }

    return $status;
}










