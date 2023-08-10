<?php 

// add_action('5minutescron','get_twitter_mentions_feed');

function get_twitter_mentions_feed(){

    $serverName = "hektordbserver.database.windows.net";
    $connectionInfo = array( "Database"=>"hektordatabase",
                            "UID"=>"maciek",
                            "PWD"=>"m@ciek3790123");
    $conn = sqlsrv_connect( $serverName, $connectionInfo);    
    if( $conn === false )
    {
        die( print_r( sqlsrv_errors(), true));
    }
    $lastMentionDateTime = get_option('wp_GetCryptoSymbolV1_lastMention', '2023-06-06 07:15:00');

    $array_data = sqlsrv_query($conn, 'exec chain.wp_GetCryptoSymbolV1 @LastMention = "'.$lastMentionDateTime.'" , @Symbol = NULL ');    
    if($array_data === false) {
        die( print_r( sqlsrv_errors(), true));
        echo 'Error';
    }
   
    while ($row = sqlsrv_fetch_array($array_data, SQLSRV_FETCH_ASSOC)) {
        sleep(1);
        $firstMention = $row['FirstMention']->format("Y-m-d H:i:s");
        $lastMention = $row['LastMention']->format("Y-m-d H:i:s");
        $account = $row['Account'];
        $symbol = $row['Symbol'];
    
        $args = array(
            'post_type'  => 'symbol_container',
            'name'       => $symbol,
            'posts_per_page' => 1,
        );
    
        $posts = get_posts($args);
        if (!empty($posts)) {
            $post = $posts[0];
            $post_id = $post->ID;
            $existing_data = get_post_meta($post_id, 'symbol_mentions', true);
            $existing_data_array = json_decode($existing_data, true);
    
            // Jeżeli pole niestandardowe już istnieje, dopisz do niego dane
            if ($existing_data_array) {
                if (array_key_exists($account, $existing_data_array)) {
                    if (!in_array($lastMention, $existing_data_array[$account]['mentions'])) {
                        $existing_data_array[$account]['mentions'][] = $lastMention;
                    }
                } else {
                    $existing_data_array[$account] = array(
                        'mentions' => array($firstMention, $lastMention),
                    );
                }
    
                // Sortowanie tablicy 'mentions'
                usort($existing_data_array[$account]['mentions'], function($a, $b) {
                    return strtotime($a) - strtotime($b);
                });
            // Jeżeli pole niestandardowe nie istnieje, utwórz je
            } else {
                $existing_data_array = array(
                    $account => array(
                        'mentions' => array($firstMention, $lastMention),
                    ),
                );
            }
    
            $customFieldDataJson = json_encode($existing_data_array);

        } else {
            // Jeżeli posty nie istnieją, utwórz nowy post
            $post_id = wp_insert_post(array(
                'post_title'    => $symbol,
                'post_type'     => 'symbol_container',
                'post_status'   => 'publish',
            ));
    
            $new_data_array = array(
                $account => array(
                    'mentions' => array($firstMention, $lastMention),
                ),
            );
    
            $customFieldDataJson = json_encode($new_data_array);
        }



        if (!empty($post_id)) {

            update_post_meta($post_id, 'symbol_mentions', $customFieldDataJson);
            update_post_meta($post_id, 'last_mention_date', $lastMention);

            $tokens_ids = find_tokens_with_symbol($symbol);

            calculate_changes_for_symbol($post_id);

            if($tokens_ids){
                update_post_meta($post_id, 'tokens_ids_relation', $tokens_ids);
                update_mentions_on_token($post_id, $tokens_ids);
            }

        }



        update_option('wp_GetCryptoSymbolV1_lastMention', $lastMention);
    }
}


function find_tokens_with_symbol($symbol){

    // Update 
    $args = array(
        'post_type'  => 'tokens',
        'meta_key'   => 'symbol',
        'meta_value' => $symbol,
        'tax_query' => array(
            array(
                'taxonomy' => 'token_status',
                'field' => 'slug',
                'terms' => array('verified'),
                'operator' => 'IN',
            ),
        ),
    );

    $posts = get_posts($args);

    $tokens_ids = array();

    if (!empty($posts)) {
        foreach ($posts as $post) {
            $post_id = $post->ID;

            $tokens_ids[] =  $post_id;   
            
        }          
    } 
    wp_reset_postdata();

    return $tokens_ids;
} 





function update_mentions_on_token($symbol_id, $tokens_ids){

    $influencers = get_post_meta($symbol_id, 'symbol_mentions_influencers', true);
    $total_mentions = get_post_meta($symbol_id, 'symbol_mentions_total', true);

    if (is_array($tokens_ids)){
        foreach($tokens_ids as $tokens_id){
            update_post_meta($tokens_id, 'symbol_id', $symbol_id);
            update_post_meta($tokens_id, 'symbol_mentions_influencers', $influencers);
            update_post_meta($tokens_id, 'symbol_mentions_total', $total_mentions);
        }
    } else {
        update_post_meta($tokens_ids, 'symbol_id', $symbol_id);
        update_post_meta($tokens_ids, 'symbol_mentions_influencers', $influencers);
        update_post_meta($tokens_ids, 'symbol_mentions_total', $total_mentions);
    }

}



function calculate_changes_for_symbol($post_id){

    $metadata_array = get_post_meta($post_id, 'symbol_mentions', true);
    $metadata_array = json_decode($metadata_array, true);

    $influencers = 0;
    $total_mentions = 0;
    foreach ($metadata_array as $name => $data) {
        $influencers++;
        $total_mentions += count($data['mentions']);  
    }
    update_post_meta($post_id, 'symbol_mentions_influencers', $influencers);
    update_post_meta($post_id, 'symbol_mentions_total', $total_mentions);

    //------------------------
    //------------------------
    // From day to day
    $all_dates = array();
    foreach ($metadata_array as $user => $data) {
        $all_dates = array_merge($all_dates, $data['mentions']);
    }
    $date_format = "Y-m-d H:i:s";
    $dates_as_objects = array_map(function($date) use ($date_format) {
        return DateTime::createFromFormat($date_format, $date);
    }, $all_dates);

    usort($dates_as_objects, function($a, $b) {
        return $b <=> $a;
    });
    $newest_date = $dates_as_objects[0];
    $last_mention_date = $newest_date->format($date_format);

    // Calculate the date 24 hours ago and 48 hours ago
    $one_day_ago_date = clone $newest_date;
    $one_day_ago_date->modify('-1 day');
    $two_days_ago_date = clone $newest_date;
    $two_days_ago_date->modify('-2 days');

    // Iterate over all the dates and count how many of them are in the last day, and the day before that
    $mentions_last_day = 0;
    $mentions_day_before = 0;
    foreach ($dates_as_objects as $date) {
        if ($date > $one_day_ago_date) {
            $mentions_last_day++;
        } elseif ($date <= $one_day_ago_date && $date > $two_days_ago_date) {
            $mentions_day_before++;
        }
    }   

    // Calculate the percent change in the number of mentions from the day before to the last day
    $percent_change_day = 0;
    if ($mentions_day_before > 0) {
        $percent_change_day = (($mentions_last_day - $mentions_day_before) / $mentions_day_before) * 100;
        $percent_change_day = round($percent_change_day,0);
        update_post_meta($post_id, 'percent_change_daily', $percent_change_day);
    }

    // Dla dnia do dnia (wzrost absolutny)
    $absolute_growth_day = $mentions_last_day - $mentions_day_before;
    update_post_meta($post_id, 'absolute_change_daily', $absolute_growth_day);

    $interest_score_day = $percent_change_day + 10 * $absolute_growth_day;
    update_post_meta($post_id, 'interest_score_daily', $interest_score_day);

    update_post_meta($post_id, 'mentions_last_day', $mentions_last_day);
    update_post_meta($post_id, 'mentions_day_before', $mentions_day_before);


    //------------------------
    //------------------------
    // From day to 7th day ago
    $all_dates = array();
    foreach ($metadata_array as $user => $data) {
        $all_dates = array_merge($all_dates, $data['mentions']);
    }
    $date_format = "Y-m-d H:i:s";
    $dates_as_objects = array_map(function($date) use ($date_format) {
        return DateTime::createFromFormat($date_format, $date);
    }, $all_dates);

    usort($dates_as_objects, function($a, $b) {
        return $b <=> $a;
    });
    $newest_date = $dates_as_objects[0];
    $last_mention_date = $newest_date->format($date_format);

    // Calculate the date 24 hours ago and 24 hours ago a week ago
    $one_day_ago_date = clone $newest_date;
    $one_day_ago_date->modify('-1 day');
    $one_week_ago_day_date = clone $newest_date;
    $one_week_ago_day_date->modify('-8 days');

    // Iterate over all the dates and count how many of them are in the last day, and the day a week ago
    $mentions_last_day = 0;
    $mentions_day_week_ago = 0;
    foreach ($dates_as_objects as $date) {
        if ($date > $one_day_ago_date) {
            $mentions_last_day++;
        } elseif ($date <= $one_week_ago_day_date && $date > $one_week_ago_day_date->modify('-1 day')) {
            $mentions_day_week_ago++;
        }
    }

    // Calculate the percent change in the number of mentions from the day a week ago to the last day
    $percent_change_day = 0;
    if ($mentions_day_week_ago > 0) {
        $percent_change_day = (($mentions_last_day - $mentions_day_week_ago) / $mentions_day_week_ago) * 100;
        $percent_change_day = round($percent_change_day,0);
        update_post_meta($post_id, 'percent_change_daily_7ago', $percent_change_day);
    }

    // For the day-to-day (absolute growth)
    $absolute_growth_day = $mentions_last_day - $mentions_day_week_ago;
    update_post_meta($post_id, 'absolute_change_daily_7ago', $absolute_growth_day);

    $interest_score_day = $percent_change_day + 10 * $absolute_growth_day;
    update_post_meta($post_id, 'interest_score_daily_7ago', $interest_score_day);

    update_post_meta($post_id, 'mentions_day_week_ago', $mentions_day_week_ago);

    
    //------------------------
    //------------------------
    // From week to week

    $all_dates = array();
    foreach ($metadata_array as $user => $data) {
        $all_dates = array_merge($all_dates, $data['mentions']);
    }
    $date_format = "Y-m-d H:i:s";
    $dates_as_objects = array_map(function($date) use ($date_format) {
        return DateTime::createFromFormat($date_format, $date);
    }, $all_dates);

    usort($dates_as_objects, function($a, $b) {
        return $b <=> $a;
    });
    $newest_date = $dates_as_objects[0];
    $last_mention_date = $newest_date->format($date_format);

    // Calculate the date one week ago and two weeks ago
    $one_week_ago_date = clone $newest_date;
    $one_week_ago_date->modify('-7 days');
    $two_weeks_ago_date = clone $newest_date;
    $two_weeks_ago_date->modify('-14 days');

    // Iterate over all the dates and count how many of them are in the last week, and the week before that
    $mentions_last_week = 0;
    $mentions_week_before = 0;
    foreach ($dates_as_objects as $date) {
        if ($date > $one_week_ago_date) {
            $mentions_last_week++;
        } elseif ($date <= $one_week_ago_date && $date > $two_weeks_ago_date) {
            $mentions_week_before++;
        }
    }   
    

    // Calculate the percent change in the number of mentions from the week before last to the last week
    $percent_change_week = 0;
    if ($mentions_week_before > 0) {
        $percent_change_week = (($mentions_last_week - $mentions_week_before) / $mentions_week_before) * 100;
        $percent_change_week = round($percent_change_week,0);
        update_post_meta($post_id, 'percent_change_weekly', $percent_change_week);
    }

    // Dla tygodnia do tygodnia (wzrost absolutny)
    $absolute_growth_week = $mentions_last_week - $mentions_week_before;
    update_post_meta($post_id, 'absolute_change_weekly', $absolute_growth_week);

    $interest_score_week = $percent_change_week + 10 * $absolute_growth_week;
    update_post_meta($post_id, 'interest_score_weekly', $interest_score_week);

    update_post_meta($post_id, 'mentions_last_week', $mentions_last_week);
    update_post_meta($post_id, 'mentions_week_before', $mentions_week_before);
   
    


    // Porównianie ostatnich 24h z 24h sprzed tygodnia
    // $all_dates = array();
    // foreach ($metadata_array as $user => $data) {
    //     $all_dates = array_merge($all_dates, $data['mentions']);
    // }
    // $date_format = "Y-m-d H:i:s";
    // $dates_as_objects = array_map(function($date) use ($date_format) {
    //     return DateTime::createFromFormat($date_format, $date);
    // }, $all_dates);

    // usort($dates_as_objects, function($a, $b) {
    //     return $b <=> $a;
    // });
    // $newest_date = $dates_as_objects[0];
    // $last_mention_date = $newest_date->format($date_format);

    // // Calculate the date 24 hours ago and 24 hours ago a week ago
    // $one_day_ago_date = clone $newest_date;
    // $one_day_ago_date->modify('-1 day');
    // $one_week_ago_day_date = clone $newest_date;
    // $one_week_ago_day_date->modify('-8 days');

    // // Iterate over all the dates and count how many of them are in the last day, and the day a week ago
    // $mentions_last_day = 0;
    // $mentions_day_week_ago = 0;
    // foreach ($dates_as_objects as $date) {
    //     if ($date > $one_day_ago_date) {
    //         $mentions_last_day++;
    //     } elseif ($date <= $one_week_ago_day_date && $date > $one_week_ago_day_date->modify('-1 day')) {
    //         $mentions_day_week_ago++;
    //     }
    // }

    // // Calculate the percent change in the number of mentions from the day a week ago to the last day
    // $percent_change_week = 0;
    // if ($mentions_day_week_ago > 0) {
    //     $percent_change_week = (($mentions_last_day - $mentions_day_week_ago) / $mentions_day_week_ago) * 100;
    //     $percent_change_week = round($percent_change_week,0);
    //     update_post_meta($post_id, 'percent_change_weekly', $percent_change_week);
    // }

    // // For the week-to-week (absolute growth)
    // $absolute_growth_week = $mentions_last_day - $mentions_day_week_ago;
    // update_post_meta($post_id, 'absolute_change_weekly', $absolute_growth_week);

    // $interest_score_week = $percent_change_week + 10 * $absolute_growth_week;
    // update_post_meta($post_id, 'interest_score_weekly', $interest_score_week);



    //------------------------
    //------------------------
    // From month to month

    $all_dates = array();
    foreach ($metadata_array as $user => $data) {
        $all_dates = array_merge($all_dates, $data['mentions']);
    }
    $date_format = "Y-m-d H:i:s";
    $dates_as_objects = array_map(function($date) use ($date_format) {
        return DateTime::createFromFormat($date_format, $date);
    }, $all_dates);

    usort($dates_as_objects, function($a, $b) {
        return $b <=> $a;
    });
    $newest_date = $dates_as_objects[0];
    $last_mention_date = $newest_date->format($date_format);

    // Calculate the date one month ago and two months ago
    $one_month_ago_date = clone $newest_date;
    $one_month_ago_date->modify('-1 month');
    $two_months_ago_date = clone $newest_date;
    $two_months_ago_date->modify('-2 months');

    // Iterate over all the dates and count how many of them are in the last month, and the month before that
    $mentions_last_month = 0;
    $mentions_month_before = 0;
    foreach ($dates_as_objects as $date) {
        if ($date > $one_month_ago_date) {
            $mentions_last_month++;
        } elseif ($date <= $one_month_ago_date && $date > $two_months_ago_date) {
            $mentions_month_before++;
        }
    }

    // Calculate the percent change in the number of mentions from the month before last to the last month
    $percent_change_month = 0;
    if ($mentions_month_before > 0) {
        $percent_change_month = (($mentions_last_month - $mentions_month_before) / $mentions_month_before) * 100;
        $percent_change_month = round($percent_change_month,0);
        update_post_meta($post_id, 'percent_change_monthly', $percent_change_month);
    }

    // Dla miesiąca do miesiąca (wzrost absolutny)
    $absolute_growth_month = $mentions_last_month - $mentions_month_before;
    update_post_meta($post_id, 'absolute_change_monthly', $absolute_growth_month);

    $interest_score_month = $percent_change_month + 10 * $absolute_growth_month;
    update_post_meta($post_id, 'interest_score_monthly', $interest_score_month);

    update_post_meta($post_id, 'mentions_last_month', $mentions_last_month);
    update_post_meta($post_id, 'mentions_month_before', $mentions_month_before);
} 



// Function to colorize table 
function get_class_for_percent_change($percent_change) {
    if ($percent_change < 100) {
        return 'low-rise';
    } elseif ($percent_change >= 100 && $percent_change <= 500) {
        return 'medium-rise';
    } else {
        return 'high-rise';
    }
}


























function search_tweets($hashtag, $max_results = 30) {

    sleep(1);

    $bearer_tokens = [
        'AAAAAAAAAAAAAAAAAAAAAD0cmAEAAAAAzQCEJCFLHb0AwAt2swaEolgit0s%3DtrwZPYWrKoqTTaDBwTU4kTkPn3JjggJmYbzhOIckX3WOUX0hFv',
        'AAAAAAAAAAAAAAAAAAAAAE1ImQEAAAAA6Zn%2FMKsYqnpYv1O%2BbHSSu6cfl20%3D3TUaBJXQMEYhvXiTamB3TVFiGP4avF00zs5ZdvHCcU9BZZhpHP',
        'AAAAAAAAAAAAAAAAAAAAAHMxkAEAAAAAg6lFpp6YH9deSgYwNEw%2B5c28w10%3DtCcANogRcBuzFr4cgzSgj2T2Y0JxMOr6KstoZPEoDfmEJY3B7e',
    ];

    $token_index = get_transient('twitter_tweets_bearer_token_index');
    if ($token_index === false || !isset($bearer_tokens[$token_index])) {
        $token_index = 0;
    }

    $bearer_token = $bearer_tokens[$token_index];

    // Zapisz indeks następnego tokena, który ma być użyty
    $next_token_index = ($token_index + 1) % count($bearer_tokens);
    set_transient('twitter_tweets_bearer_token_index', $next_token_index, 60 * 30); // ustaw na godzinę

    $api_url = 'https://api.twitter.com/2/tweets/search/recent?query=' . urlencode("#$hashtag") . '&tweet.fields=created_at,public_metrics,text&max_results=' . $max_results;

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

    return $data;
}

function count_shib_tweets($tweets, $hashtag) {
    $shib_count = 0;

    foreach ($tweets as $tweet) {
        if (strpos($tweet['text'], '$'. $hashtag ) !== false) {
            $shib_count++;
        }
    }

    return $shib_count;
}

function init_search_tweets() {
    try {
        $hashtag = 'SHIB';
        $post_id = 118239;
        $tweets = search_tweets($hashtag);

        // Odczytaj wartość z pola niestandardowego
        $saved_data = get_post_meta($post_id, 'tweets_by_cashtag', true);
        $last_checked_date = $saved_data['last_checked_date'] ?? "2000-01-01T00:00:00.000Z";
        $count = $saved_data['count'] ?? 0;

        $result = count_shib_tweets($tweets, $last_checked_date);
        $new_count = $count + $result['count'];
        $new_last_checked_date = $result['last_checked_date'];

        if ($new_last_checked_date !== $last_checked_date) {
            $new_data = array(
                'count' => $new_count,
                'last_checked_date' => $new_last_checked_date,
            );

            // Zapisz nowe wartości w polu niestandardowym
            update_post_meta($post_id, 'tweets_by_cashtag', $new_data);
        }

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br/>";
    }
}
// add_action('5minutescron', 'init_search_tweets');
