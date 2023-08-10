<?php 

add_action('wp_ajax_second_section', 'second_section');
add_action('wp_ajax_nopriv_second_section', 'second_section');

function second_section() {
    global $wpdb;
    $post_id = $_POST['post_id'];

  
    ob_start();

    ?> 
    <div class="progress_chart">
        <?php
        $price_data = get_post_meta( $post_id, 'priceUsd', true ); 
        
        if ( $price_data  && is_array(  $price_data  )){
            
            $latest = end($price_data); 
            $current_value = $latest['count'];
            $current_price = $current_value;

            $changes = calculate_change($price_data, $current_value);

            $percentage = $changes['24h'];
            $percentage_text = $changes['24h'] . '%';
            $percentage_class = '';
            $percentage_val = 0;
            if ($percentage < 0) {
                $percentage_class  = 'red';
                $percentage_color  = "colors:['#FF0000', '#171717']";
                $percentage_val = 100 - $percentage*(-1);
            } else {
                $percentage_color  = "colors:['#171717', '#00FF99']";
                $percentage_val = $percentage;
            }
            if ($percentage_val) {

                $min = null;
                $max = null;
                $currentTime = strtotime(current_time('mysql'));

                foreach ($price_data as $entry) {
                    $updatedAt = strtotime($entry['updated_at']);
                    $timeDifference = $currentTime - $updatedAt;
                    
                    if ($timeDifference <= 24 * 60 * 60) {
                        $count = $entry['count'];
                        
                        if ($min === null || $count < $min) {
                            $min = $count;
                        }
                        
                        if ($max === null || $count > $max) {
                            $max = $count;
                        }
                    }
                }

                $range = number_format($max - $min, 10, '.', '');
                $distance = number_format($current_value - $min, 10, '.', '');
                
                if ($range > 0) {
                    $percentage_fibo = ($distance / $range) * 100 ;
                } else {
                    $percentage_fibo = 0;
                }
                $percentage_fibo = 100 - $percentage_fibo;
            
                    ?>
                    <div class="flex">
                        <div id="price_chart_<?php echo $post_id; ?>" class="circular_chart price_chart  <?php echo $percentage_class; ?>" ></div>
                        <div class="wrap_progres_bar progress-bar_<?php echo $post_id; ?>">
                            <div class="progress"></div>
                        </div>
                    </div>
                    <label>Cena 24h</label>
                    <?php 
                        $percent_to_ath = get_post_meta( $post_id, 'percent_to_ath', true ); 
                        if ($percent_to_ath){
                            echo '<small>' .$percent_to_ath . '% do ATH</small>';
                        }

                    ?>                    
                    <script>
                    document.querySelector('.progress-bar_<?php echo $post_id; ?> .progress').style.height = `<?php echo $percentage_fibo; ?>%`;
        
                    setTimeout(function(){
                        var myCircle = Circles.create({
                            id:                  'price_chart_<?php echo $post_id; ?>',
                            radius:              60,
                            value:               <?php echo $percentage_val;?>,
                            maxValue:            100,
                            width:               3,
                            text:                function(value){return '<div class="info"><span>'+ <?php echo $percentage;?> + '%</span><small>$<?php echo $current_value;?></small></div>';},
                            <?php echo $percentage_color; ?>,
                            duration:            0,
                            wrpClass:            'circles-wrp',
                            textClass:           'circles-text',
                            valueStrokeClass:    'circles-valueStroke',
                            maxValueStrokeClass: 'circles-maxValueStroke',
                            styleWrapper:        true,
                            styleText:           true
                        });
                    }, 1000);
                    </script>
                    <?php
                } else { ?>
                    <div class="info"><small>$<?php echo $current_value;?></small></div>
                    <label class="warning">Brak danych</label>
                    <?php
                }
            }
        ?>
    </div>
    <div class="progress_chart">
        <?php
        $price_data = get_post_meta( $post_id, 'liquidity', true );
        if ( $price_data  && is_array(  $price_data  )){
            $latest = end($price_data); 
            $current_value = $latest['count'];

            $changes = calculate_change($price_data, $current_value);

            $percentage = $changes['24h'];
            $percentage_text = $changes['24h'] . '%';
            $percentage_class = '';
            $percentage_val = 0;
            if ($percentage < 0) {
                $percentage_class  = 'red';
                $percentage_color  = "colors:['#FF0000', '#171717']";
                $percentage_val = 100 - $percentage*(-1);
            } else {
                $percentage_color  = "colors:['#171717', '#8ED53E']";
                $percentage_val = $percentage;
            }
            if ($percentage_val) {
                
                ?>
                <div id="liquidity_chart_<?php echo $post_id; ?>" class="circular_chart liquidity_chart <?php echo $percentage_class; ?>"></div>               
                <label>Liqudity pool</label>
                <script>
                setTimeout(function(){
                    var myCircle = Circles.create({
                        id:                  'liquidity_chart_<?php echo $post_id; ?>',
                        radius:              60,
                        value:               <?php echo $percentage_val;?>,
                        maxValue:            100,
                        width:               3,
                        text:                function(value){return '<div class="info"><span>$<?php echo format_number($current_value);?></span><small><?php echo $percentage;?>%</small></div>';},
                        <?php echo $percentage_color; ?>,
                        duration:            0,
                        wrpClass:            'circles-wrp',
                        textClass:           'circles-text',
                        valueStrokeClass:    'circles-valueStroke',
                        maxValueStrokeClass: 'circles-maxValueStroke',
                        styleWrapper:        true,
                        styleText:           true
                    });
                    
                }, 1000);
                </script>
                <?php
                } else { ?>
                    <div class="info"><small>$<?php echo $current_value;?></small></div>
                    <label class="warning">Brak danych</label>
                    <?php
                }
            }

        ?>
    </div>
    <div class="progress_chart">
        <?php
        $price_data = get_post_meta( $post_id, 'marketcap', true );
        if ( $price_data  && is_array(  $price_data  )){

            $latest = end($price_data); 
            $current_value = $latest['count'];
            
            $changes = calculate_change($price_data, $current_value);
            
            $percentage = $changes['24h'];
            $percentage_text = $changes['24h'] . '%';

            $percentage_class = '';
            $percentage_val = 0;
            if ($percentage < 0) {
                $percentage_class  = 'red';
                $percentage_color  = "colors:['#FF0000', '#171717']";
                $percentage_val = 100 - $percentage*(-1);
            } else {
                $percentage_color  = "colors:['#171717', '#0CBDF5']";
                $percentage_val = $percentage;
            }
            if ($percentage_val) {
                ?>
                <div id="marketcap_chart_<?php echo $post_id; ?>" class="circular_chart marketcap_chart <?php echo $percentage_class; ?>"></div>               
                <label>Market Cap</label>
                <script>
                setTimeout(function(){
                    var myCircle = Circles.create({
                        id:                  'marketcap_chart_<?php echo $post_id; ?>',
                        radius:              60,
                        value:               <?php echo $percentage_val;?>,
                        maxValue:            100,
                        width:               3,
                        text:                function(value){return '<div class="info"><span>$<?php echo format_number($current_value); ?></span><small><?php echo $percentage;?>%</small></div>';},
                        <?php echo $percentage_color; ?>,
                        duration:            0,
                        wrpClass:            'circles-wrp',
                        textClass:           'circles-text',
                        valueStrokeClass:    'circles-valueStroke',
                        maxValueStrokeClass: 'circles-maxValueStroke',
                        styleWrapper:        true,
                        styleText:           true
                    });
                    
                }, 1000);
                </script>
                <?php
                } else { ?>
                    <div class="info"><small>$<?php echo $current_value;?></small></div>
                    <label class="warning">Brak danych</label>
                    <?php
                }
            }

        ?>
    </div>    
    <?php

    $output = ob_get_clean();
    wp_send_json_success($output);

}

add_action('wp_ajax_third_section', 'third_section');
add_action('wp_ajax_nopriv_third_section', 'third_section');

function third_section() {
    global $wpdb;
    $post_id = $_POST['post_id'];
    $token_address = get_post_meta( $post_id, 'address',true);
    $token_symbol = get_post_meta( $post_id, 'symbol',true);
    $token_name = get_post_meta( $post_id, 'name',true);
    $creatorcontract = get_post_meta( $post_id, 'creatorcontract',true);
    $deploy_balance = get_post_meta( $post_id, 'deploy_balance',true);

    $terms_token_blockchain = get_the_terms( $post_id ,  'blockchain' );

    foreach ( $terms_token_blockchain as $term ) {					
        $term_id = $term->term_id;
        $blockchain = $term->name;
        $blockchain_slug = $term->slug;
    }
    if ($term_id) {
        $blockchain_id = get_blockchain_cf( $term_id, 'blockchain_id' );
        $dex_tools_url = get_blockchain_cf( $term_id, 'dex_tools_url' );
        $explorer = get_blockchain_cf( $term_id, 'explorer' );
        $short = get_blockchain_cf( $term_id, 'short' );
    }

    $coingeckoid = get_post_meta( $post_id, 'coingeckoid',true);

    ob_start();
    ?>

    <label>Security</label>
    <?php 
    if ($blockchain_id){
    ?>
        <a target="_blank" href="https://www.dextools.io/app/en/<?php echo $dex_tools_url; ; ?>/pair-explorer/<?php echo $token_address; ?>">Dex Tools</a> 
        <a target="_blank" href="https://dexscreener.com/<?php echo $blockchain_slug; ?>/<?php echo $token_address; ?>">Dex Screener</a> 
        <a target="_blank" href="https://dex.guru/token/eth/<?php echo $token_address; ?>">Dex Guru</a> 
        <a target="_blank" href="https://isrug.app/<?php echo $blockchain_slug; ?>/<?php echo $token_address; ?>">IsRug</a> 
        <?php if ($coingeckoid) { ?>
            <a target="_blank" href="https://www.coingecko.com/pl/waluty/<?php echo $coingeckoid; ?>">CoinGecko</a> 
        <?php } ?>
        <div class="dropdown" href="#">...
            <div class="dropdown-content">
                <label>Explorer</label>
                <a target="_blank" href="https://<?php echo $explorer; ?>/token/<?php echo $token_address; ?>">Explorer</a> 
                <a target="_blank" href="https://<?php echo $explorer; ?>/token/<?php echo $token_address; ?>#code">Contract</a> 
                <a target="_blank" href="https://<?php echo $explorer; ?>/address/<?php echo $creatorcontract; ?>">Deployer (<?php echo $deploy_balance; ?>ETH)</a> 
                <a href="https://api.dexscreener.com/latest/dex/tokens/<?php echo $token_address; ?>" target="_new">Dex Screener API Data</a>
                <label>Security</label>
                <a  target="_blank" href="https://honeypot.is/<?php echo $blockchain_slug; ?>?address=<?php echo $token_address; ?>">HoneyPot</a> 
                <a class="yelow" target="_blank" href="https://gopluslabs.io/token-security/<?php echo $blockchain_id; ?>/<?php echo $token_address; ?>">GoLabs</a> 
                <a class="yelow" target="_blank" href="https://api.gopluslabs.io/api/v1/token_security/<?php echo $blockchain_id; ?>?contract_addresses=<?php echo $token_address; ?>">GoLabs API</a>
                <a class="yelow" target="_blank" href="https://tokensniffer.com/token/<?php echo $short; ?>/<?php echo $token_address; ?>">Sniffer</a> 
                <a class="yelow" target="_blank" href="https://moonscan.com/scan/contract/<?php echo $short; ?>/<?php echo $token_address; ?>">Moonscan</a> 
                <a target="_blank" href="https://www.cryptogems.info/<?php echo $short; ?>/token/<?php echo $token_address; ?>">CryptoGems</a> 
                <a target="_blank" href="https://www.followeraudit.com/">Follower Audit</a> 
            </div> 
        </div> 
                
    <?php
    }
    
    echo '<label style="margin-top:10px">FOMO</label>';
    $holder_count_data = get_post_meta( $post_id, 'holder_count',true);
    $class_val0 = '';
    $class_val_session = '';
    if ( is_array($holder_count_data) ) { 
        $percent_holder_count = calculatePercentageChange($holder_count_data, 24);
        if ($percent_holder_count > 0) $class_val0 = 'green';
        if ($percent_holder_count < 0) $class_val0 = 'red';

        $percent_holder_count_for_session = calculatePercentageChange_for_session($holder_count_data);
        if ($percent_holder_count_for_session > 0) $class_val_session = 'green';
        if ($percent_holder_count_for_session < 0) $class_val_session = 'red';

        $latest = end($holder_count_data);
        $holder_count = $latest['count']; 
        ?>
        <p class="holder_count">Holders: <?php echo $holder_count;  
        
        if ($holder_count) echo ' <span class="prercents '.$class_val0.'" title="Procent dobowej zmiany wartości.">(' . $percent_holder_count . '%)</span>';      
        // if ($holder_count) echo ' <span class="prercents '.$class_val_session.'" title="Procent zmiany wartości od początku sesji.">(' . $percent_holder_count_for_session . '%)</span>';      
        echo  '</p>';

    }
    $telegram_subscribers = get_post_meta( $post_id, 'telegram_subscribers', true ) ?: 'Unknown';

    $telegram_url = get_post_meta( $post_id, 'telegram_url',true);
    if ($telegram_url){ ?>
    <div class="telegram dropdown">Telegram: <span class="telegram_val"><?php echo $telegram_subscribers; ?></span>
        <div class="dropdown-content">
            <a href="<?php echo $telegram_url; ?>" target="_blank">Telegram Link</a>
        </div>
    </div>
    <?php
    }
    

    
    // Twitter
    $twitter_account_url = get_post_meta( $post_id, 'twitter_account_url',true);
    $twitter_account_name = get_post_meta( $post_id, 'twitter_account_name',true);
    if ( $twitter_account_url ) { 
        $error_class = '';
        $latest_count = '';
        $latest_tweet_count = '';
        $updated_at = '';
        $twitter_followers = '';
        $percent_twitter_followers = '';
        $percent_tweets = '';
        $class_val = '';
        $class_val_session = '';
        $class_val2 = '';
        $class_val2_session = '';

        $twitter_followers = get_post_meta( $post_id, 'twitter_followers',true);
        $percent_twitter_followers = '';
        $percent_twitter_followers_session = '';
        if ( is_array( $twitter_followers ) ) { 

            // Followers
            $percent_twitter_followers = calculatePercentageChange($twitter_followers, 24);     
            if ($percent_twitter_followers > 0) $class_val = 'green';
            if ($percent_twitter_followers < 0) $class_val = 'red';

            // $percent_twitter_followers_session = calculatePercentageChange_for_session($twitter_followers); 
            // if ($percent_twitter_followers_session > 0) $class_val_session = 'green';
            // if ($percent_twitter_followers_session < 0) $class_val_session = 'red';
            
            $latest = end($twitter_followers); 
            $latest_count = $latest['count'];
            $updated_at = $latest['updated_at'];  
           
            // Tweets
            $tweet_count = get_post_meta( $post_id, 'tweet_count',true);
            if ( is_array( $tweet_count ) ) { 
                $percent_tweets = calculatePercentageChange($tweet_count, 24);     
                if ($percent_tweets > 0) $class_val2 = 'green';
                if ($percent_tweets < 0) $class_val2 = 'red';

                // $percent_tweets_session = calculatePercentageChange_for_session($tweet_count); 
                // if ($percent_tweets_session > 0) $class_val2_session = 'green';
                // if ($percent_tweets_session < 0) $class_val2_session = 'red';

                $latest_tweet = end($tweet_count); 
                $latest_tweet_count = $latest_tweet['count'];
            }   
        
        } else {
            $latest_count = 'Error';
            $twitter_error = get_post_meta( $post_id, 'twitter_error',true);
            if ( is_array( $twitter_error ) ) {  
            $twitter_error = $twitter_error[0]['count'];
            }
            $error_class = 'error';
        }            
        ?>  
        <div class="twitter_followers dropdown <?php echo $error_class; ?>">Twitter followers: <span><?php echo $latest_count; 
            if ($percent_twitter_followers) echo ' <span class="prercents '.$class_val.'" title="Procent dobowej zmiany wartości.">(' . $percent_twitter_followers . '%)</span>';      
            // if ($percent_twitter_followers_session) echo ' <span class="prercents '.$class_val_session.'" title="Procent zmiany wartości od początku sesji.">(' . $percent_twitter_followers_session . '%)</span>';  
            
            if ($latest_tweet_count) { 
                echo "<br/>Tweets: $latest_tweet_count"; 
                if ($percent_tweets) echo ' <span class="prercents '.$class_val2.'" title="Procent dobowej zmiany wartości.">(' . $percent_tweets . '%)</span>'; 
            }
            // if ($latest_tweet_count) echo ' <span class="prercents '.$class_val2_session.'" title="Procent zmiany wartości od początku sesji.">(' . $percent_tweets_session . '%)</span>';             
            ?>
            <div class="dropdown-content">
                <?php if ($twitter_account_url){
                    echo '<a target="_blank" href="' . $twitter_account_url .'">Twitter</a>';
                } ?>
                <a target="_blank" href="https://twitter.com/search?q=%24<?php echo $token_symbol; ?>&src=typed_query&f=live">Szukaj: $<?php echo $token_symbol; ?></a> 
                <a target="_blank" href="https://twitter.com/search?q=<?php echo $token_name; ?>&src=typed_query&f=live">Szukaj: <?php echo $token_name; ?></a> 
                <a target="_blank" href="https://socialbearing.com/search/general/$<?php echo $token_symbol; ?>">Social Bearing  $<?php echo $token_symbol; ?></a>
                <?php if ($twitter_account_name){
                    echo '<a target="_blank" href="https://socialbearing.com/search/user/' . $twitter_account_name .'">Social Bearing USER</a>';
                    echo '<a target="_blank" href="https://socialblade.com/twitter/user/' . $twitter_account_name .'">Social Blade</a>';
                } 
                if ( $latest_count == 'Error') {  ?>
                <label>BŁĄD: <?php echo $twitter_error; ?></label>                    
                <?php 
                $updated_at = "Error";
                } ?>                
            </div>
        </div>      
    <?php 
    } else {
        ?>  
        <div class="twitter_followers dropdown">Twitter: <span>None</span>
            <div class="dropdown-content">                 
            <a target="_blank" href="https://twitter.com/search?q=%24<?php echo $token_symbol; ?>&src=typed_query&f=live">Szukaj: $<?php echo $token_symbol; ?></a> 
                <a target="_blank" href="https://twitter.com/search?q=<?php echo $token_name; ?>&src=typed_query&f=live">Szukaj: <?php echo $token_name; ?></a> 
                <a target="_blank" href="https://socialbearing.com/search/general/$<?php echo $token_symbol; ?>">Social Bearing  $<?php echo $token_symbol; ?></a>
            </div>
        </div>
    <?php 
        
    }
    ?>
    <?php
    $symbol_mentions_influencers = get_post_meta($post_id, 'symbol_mentions_influencers', true);
    $symbol_id = get_post_meta($post_id, 'symbol_id', true);
    if ($symbol_mentions_influencers) {
        echo '<a href="'. get_permalink($symbol_id) .'" target="_new">Wspominało ' . $symbol_mentions_influencers . ' influencerów.</a>';         
    }
    ?>
    <?php 
    $medium_url = get_post_meta( $post_id, 'medium_url',true);
    if ( $medium_url){ ?>
        <a target="_blank" href="<?php echo  $medium_url; ?>">Medium Link</a>
    <?php
    } 

  
    $website = get_post_meta( $post_id, 'website',true);
    if (is_array($website)){
        $website = $website[0];
        update_post_meta($post_id, 'website', $website);
    }
    if ($website){
        echo '<a target="_blank" href="' . $website .'">Website</a>';
    } ?>
    <?php

    $output = ob_get_clean();
    wp_send_json_success($output);

}