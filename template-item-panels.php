<?php

add_action('wp_ajax_price_chart_panel', 'price_chart_panel');
add_action('wp_ajax_nopriv_price_chart_panel', 'price_chart_panel');

function price_chart_panel() {
    global $wpdb;
    $post_id = $_POST['post_id'];
    $blockchain = $_POST['blockchain'];
    $address = get_post_meta( $post_id, 'address',true);

    ob_start();
	
	echo "<div class='dex_container'><style>#dexscreener-embed{position:relative;width:100%;padding-bottom:80%;}@media(min-width:1400px){#dexscreener-embed{padding-bottom:70%;}}#dexscreener-embed iframe{position:absolute;width:100%;height:100%;top:0;left:0;border:0;}</style><div id='dexscreener-embed'><iframe src='https://dexscreener.com/$blockchain/$address?embed=1&theme=light&trades=0&info=0'></iframe></div></div>";
    
    $price_data =  prepare_chart_data($post_id, 'priceUsd',40); 
    $liquidity_data = prepare_chart_data($post_id, 'liquidity',40);
    $marketcap_data = prepare_chart_data($post_id, 'marketcap',40);

    if (is_array($price_data) && is_array($liquidity_data) && is_array($marketcap_data) ) {
        $chart_data = [
            'price' => $price_data,
            'liquidity' => $liquidity_data,
            'marketcap' => $marketcap_data,
        ];
    ?> 

   
        <canvas id="CombinedHistoryChart_<?php echo $post_id; ?>"></canvas>
  

    <script>
        setTimeout(function(){
            const data = <?php echo json_encode($chart_data); ?>;
            const ctx = document.getElementById('CombinedHistoryChart_<?php echo $post_id; ?>').getContext('2d');

            const price_labels = data.price.map(row => row.updated_at);
            const price_values = data.price.map(row => row.count);

            const liquidity_labels = data.liquidity.map(row => row.updated_at);
            const liquidity_values = data.liquidity.map(row => row.count);

            const marketcap_labels = data.marketcap.map(row => row.updated_at);
            const marketcap_values = data.marketcap.map(row => row.count);

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: price_labels,
                    datasets: [
                        {
                            label: 'Price USDT',
                            data: price_values,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            yAxisID: 'y1',
                        },
                        {
                            label: 'Liquidity',
                            data: liquidity_values,
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1,
                            yAxisID: 'y2',
                        },
                        {
                            label: 'Market Cap',
                            data: marketcap_values,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            yAxisID: 'y3',
                        },
                    ],
                },
                options: {
                    scales: {
                        y1: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                        },
                        y2: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                        },
                        y3: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                        },
                    
                    },
                },
            });
        }, 1000);
    </script>
    <?php }

    $output = ob_get_clean();
    wp_send_json_success($output);
}




add_action('wp_ajax_volume_panel', 'volume_panel');
add_action('wp_ajax_nopriv_volume_panel', 'volume_panel');

function volume_panel() {
    global $wpdb;
    $post_id = $_POST['post_id'];
  
    ob_start();

    $data = get_post_meta( $post_id, 'volume_5m',true);
    if ( !empty( $data ) && is_array($data) ) { 
    $chart_data = prepare_chart_data( $post_id, 'volume_5m',40);
    usort( $data, function( $a, $b ) {
        return strtotime( $b['updated_at'] ) - strtotime( $a['updated_at'] );
    });
    ?>
    <div style="
        width: 100%;
        max-width: 1200px;
    ">
        <h4>Volume 1h</h4>    
        <div class="outer_wrap"style="
            max-height: 437px;
        ">
            <div class="table">
                    <table>
                    <thead>
                        <tr>
                        <th>Value</th>
                        <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?php echo $row['count']; ?></td>
                            <td><?php echo $row['updated_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    </table>
                </div>
            
            <div class="chart">
                <canvas id="volume5mChart_<?php echo $post_id; ?>"></canvas>
            </div>
        </div>
        <script>
            setTimeout(function(){

                const data = <?php echo json_encode($chart_data); ?>;
                const ctx = document.getElementById('volume5mChart_<?php echo $post_id; ?>').getContext('2d');

                const labels = data.map(row => row.updated_at);
                const values = data.map(row => row.count);

                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Buy Volume',
                            data: values,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }, 1000);
        </script>
    </div>
    <?php 
    }
    ?>

    <?php 
    $data = get_post_meta( $post_id, 'buy_volume',true);
    if ( !empty( $data ) && is_array($data) ) { 
    $chart_data = prepare_chart_data_15minutes($post_id, 'buy_volume', 40); 

    ?>
    <div class="chart">
        <canvas id="buy_volumeChart_<?php echo $post_id; ?>"></canvas>    
        <script>
            setTimeout(function(){
                const data = <?php echo json_encode($chart_data); ?>;
                const ctx = document.getElementById('buy_volumeChart_<?php echo $post_id; ?>').getContext('2d');

                const labels = data.map(row => row.updated_at);
                const values = data.map(row => row.count);

                const chart = new Chart(ctx, {
                type: 'line',
                data: {
                        labels: labels,
                        datasets: [{
                            label: 'Huge Buy Volume',
                            data: values,
                            backgroundColor: 'rgba(0, 255, 153, 0.2)',
                            borderColor: 'rgb(0 255 153)',
                            borderWidth: 1
                        }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        // legend: false 
                    },
                    scales: {
                        y: {
                            display: true, 
                            fontSize: 8
                        },
                        x: {
                            fontSize: 8,
                            display: true 
                        }
                    }   
                }
                    
                });
            }, 1000);
        </script>
    </div>
    <?php 
    }

    $output = ob_get_clean();
    wp_send_json_success($output);
}




add_action('wp_ajax_twitter_panel', 'twitter_panel');
add_action('wp_ajax_nopriv_twitter_panel', 'twitter_panel');

function twitter_panel() {
    global $wpdb;
    $token_id = $_POST['post_id'];
  
    ob_start();
    $symbol = get_post_meta($token_id, 'symbol', true); // token symbol
    $post_id = get_post_meta($token_id, 'symbol_id', true);
     
    include( get_stylesheet_directory() . '/template-parts/single_part_symbol_container.php' );

    $metadata_array = get_post_meta($post_id, 'symbol_mentions', true);
    $original_data = json_decode($metadata_array, true);

    $chart_data = [];

    foreach($original_data as $user => $data) {
        foreach($data['mentions'] as $mention) {
            $date = explode(' ', $mention)[0];

            if (!isset($chart_data[$date])) {
                $chart_data[$date] = ['mentions' => 0];
            }

            $chart_data[$date]['mentions']++;
        }
    }

    $twitter_followers_data = prepare_chart_data($token_id, 'twitter_followers',168);
    $tweet_count_data = prepare_chart_data($token_id, 'tweet_count',168);

    foreach ($twitter_followers_data as $data) {
        $date = explode(' ', $data['updated_at'])[0];

        if (!isset($chart_data[$date])) {
            $chart_data[$date] = ['followers' => 0, 'tweets' => 0];
        }

        $chart_data[$date]['followers'] = $data['count'];
    }

    foreach ($tweet_count_data as $data) {
        $date = explode(' ', $data['updated_at'])[0];

        if (!isset($chart_data[$date])) {
            $chart_data[$date] = ['followers' => 0, 'tweets' => 0];
        }

        $chart_data[$date]['tweets'] = $data['count'];
    }

    ksort($chart_data);

    $labels = array_keys($chart_data);
    $mentions_values = array_map(function($row) { return $row['mentions'] ?? 0; }, $chart_data);
    $followers_values = array_map(function($row) { return $row['followers'] ?? 0; }, $chart_data);
    $tweets_values = array_map(function($row) { return $row['tweets'] ?? 0; }, $chart_data);

    ?>

    <div class="chart-fill_width">
        <canvas id="combined_chart_<?php echo $post_id; ?>"></canvas>    
        <script>
            setTimeout(function(){
                const ctx = document.getElementById('combined_chart_<?php echo $post_id; ?>').getContext('2d');

                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [
                            {
                                label: 'Mentions',
                                data: <?php echo json_encode($mentions_values); ?>,
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Twitter Followers',
                                data: <?php echo json_encode($followers_values); ?>,
                                type: 'line',
                                fill: false,
                                borderColor: 'rgba(255, 99, 132, 1)',
                                yAxisID: 'y-axis-2',
                            },
                            {
                                label: 'Tweet Count',
                                data: <?php echo json_encode($tweets_values); ?>,
                                type: 'line',
                                fill: false,
                                borderColor: 'rgba(255, 206, 86, 1)',
                                yAxisID: 'y-axis-2',
                            }
                        ]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            },
                            'y-axis-2': {
                                type: 'linear',
                                display: true,
                                position: 'right',
                            }
                        }
                    }
                });
            }, 1000);
        </script>
    </div>
    <?php

    $output = ob_get_clean();
    wp_send_json_success($output);
}





add_action('wp_ajax_edit_panel', 'edit_panel');
add_action('wp_ajax_nopriv_edit_panel', 'edit_panel');

function edit_panel() {
    global $wpdb;
    $post_id = $_POST['post_id'];
    $token_status = $_POST['status'];
    $token_category = $_POST['category'];
    $token_tags= $_POST['tags'];
    $current_user_id = $_POST['current_user_id'];

    $next_week_check = get_post_meta( $post_id, 'token_cheked_by_human' ,true);
    $current_date = date("Y-m-d");
    $edit_status='';
    if (!empty($next_week_check)) {
        if ($current_date < $next_week_check) {
           $edit_status = "locked";
        }
    }
    $immunity_test = get_post_meta( $post_id, 'token_immune' ,true);
    $immunity_status='';
    if (!empty($immunity_test)) {
        $immunity_status = "active";
    }

    ob_start();

    ?>

    <div class="tax_buttons">
        <div>
            <p>Token Status</p>
            <?php
               $terms_token_tags = get_terms( array(
                'taxonomy' => 'token_status',
                'hide_empty' => false, 
            ) );
            
            if ( ! empty( $terms_token_tags ) && ! is_wp_error( $terms_token_tags ) ) {
                foreach ( $terms_token_tags as $term ) {
                    $tag = $term->name;
                    $tagslug = $term->slug;
                    if ($tag == 'Unverified') continue;
                     ?>
                    <button class="change-status-btn <?php if ($token_status == $tagslug) echo ' active';?>" data-term="<?php echo $tagslug; ?>"><?php echo $tag; ?></button>
                    <?php
                }
            }
            ?>
        </div>
        <div>
            <p>Kategoria</p>
            <?php
               $terms_category_tokens = get_terms( array(
                'taxonomy' => 'category_tokens',
                'hide_empty' => false, 
            ) );
            
            if ( ! empty( $terms_category_tokens ) && ! is_wp_error( $terms_category_tokens ) ) {
                foreach ( $terms_category_tokens as $term ) {
                    $category = $term->name;
                    $categoryslug = $term->slug;
                     ?>
                    <button class="change-category-btn <?php if ($token_category == $categoryslug) echo ' active';?>" data-term="<?php echo $categoryslug; ?>"><?php echo $category; ?></button>
                    <?php
                }
            }
            ?>
            <button class="change-category-btn" data-term="delete">Usuń kategorię</button>
        </div>
        <div>
            <p>Tag</p>
            <?php
               $terms_token_tags = get_terms( array(
                'taxonomy' => 'tags',
                'hide_empty' => false, 
            ) );
            
            if ( ! empty( $terms_token_tags ) && ! is_wp_error( $terms_token_tags ) ) {
                foreach ( $terms_token_tags as $term ) {
                    $tag = $term->name;
                    $tagslug = $term->slug;
                     ?>
                    <button class="change-tag-btn <?php if ($token_tags == $tagslug) echo ' active';?>" data-term="<?php echo $tagslug; ?>"><?php echo $tag; ?></button>
                    <?php
                }
            }
            ?>
            <button class="change-tag-btn" data-term="delete">Usuń tag</button>
          
        </div>
        <div>
            <p>Uruchom proces</p>
            <button class="init_dex_scan_single" title="Wymuś ponowne sprawdzenie.">DexScreener Check</button>
            <button class="refresh_twitter" title="Wymuś ponowne sprawdzenie.">Twitter Check</button>
            <button class="refresh_GoPlusLabs" title="Wymuś ponowne sprawdzenie.">GoPlusLabs Check</button>
        </div>
        <div>
            <p>Edycja</p>
            <button class="token_cheked_by_human <?php echo $edit_status;?>">Oznacz jako sprawdzony</button>
            <button class="immunity <?php echo $immunity_status;?>">Immunitet</button>
            <br/>
            <p>Token sprwadzony pojawi się ponownie po tygodniu od sprawdzenia.</p>
            <p>Token z immunitetem nie może być zablokowany oraz oznaczony jako DEAD/SCAM.</p>
        </div>
        <div>
            <p>Zarządzaj pozycją</p>
            <button class="buy_position">Open Buy</button>
            <?php 
            $postions = get_post_meta( $post_id, 'trading_postion_' . $current_user_id ,true);
            if ($postions) {
                echo '<div class="dropdown sell_position"><button>Close Sell</button>';
                    echo '<div class="dropdown-content">';
                    foreach ($postions as $row) {
                        if (!$row['close_date']){
                            echo '<span class="position_item" data-position-date="' . $row['open_date'] . '">' . $row['open_date'] . ' $'. $row['open_trade'] .'</span>';
                        } 
                    }
                    echo '</div>';
                echo '</div>';
            }
            
            ?>
        </div>
        
    </div>
    <div class="main_editor">
        <form id="custom-field-form">
           
            <div class="social_section">
                
            <?php 
            $fields = array(
                'field_name_twitter_account_url' => 'Twitter URL',
                'field_name_telegram_url' => 'Telegram URL',
                'field_name_telegram_subscribers' => 'Telegram Subscribers',
                'field_name_medium_url' => 'Medium URL',
                'field_name_website' => 'Website URL',
                'field_name_coingecko_url' => 'Coingecko URL',
                'field_name_coingeckoid' => 'Coingecko ID',
                'field_name_binanceid' => 'Binance ID',
                'field_name_coinmarcetcap_url' => 'CoinMarketCap URL',
            );

            foreach ($fields as $name => $label) {
                
                $clean_field_name = str_replace('field_name_', '', $name);
                $value = get_post_meta($post_id, $clean_field_name, true);
                if (is_array($value)){
                    $value = $value[0];
                }
                $value = strip_tags($value);
                echo '<div class="form-group">';
                echo '<label for="' . $name . '">' . $label . '</label>';
                echo '<input type="text" class="form-control" name="' . $name . '" id="' . $name . '" value="' . $value . '">';
                echo '</div>';
            }

            ?>
                
            </div>
            
            <div class="finance_section">
            <?php if ($token_category == 'fake') {
                  echo '<div class="form-group">';
                  echo '<label for="field_name_origin">Wskaż adres właściwego tokenu</label>';
                  echo '<input type="text" class="form-control" name="field_name_origin" id="field_name_origin" value="' . get_post_meta($post_id, 'origin', true) . '">';
                  echo '</div>';    
            } ?>
            <?php 
            $fields = array(
                'field_name_ath' => 'ATH',
                'field_name_atl' => 'ATL',
                'field_name_dsp' => 'Deploy Price',
            );

            foreach ($fields as $name => $label) {
                
                $clean_field_name = str_replace('field_name_', '', $name);
                $value = get_post_meta($post_id, $clean_field_name, true);

                $value = strip_tags($value);
                echo '<div class="form-group">';
                echo '<label for="' . $name . '">' . $label . '</label>';
                echo '<input type="text" class="form-control" name="' . $name . '" id="' . $name . '" value="' . $value . '">';
                echo '</div>';
            }
            ?>
            </div>
            <div class="note_section">
                <div class="form-group">
                    <label for="field_name_private_custom_field_<?php echo $current_user_id; ?>">Notatka</label>
                    <textarea id="field_name_private_custom_field_<?php echo $current_user_id; ?>" name="field_name_private_custom_field_<?php echo $current_user_id; ?>"><?php echo strip_tags(get_post_meta( $post_id, 'private_custom_field_' . $current_user_id, true )); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="field_name_post_content">Główny opis (wspólny)</label>
                    <textarea id="field_name_post_content" name="field_name_post_content"><?php echo strip_tags(get_post_field(  'post_content', $post_id)); ?></textarea>
                </div>                   
                <label>
                    <input type="checkbox" name="end_today" value="true"> Zakończ analizę na dziś (tylko poglądowo, abym nie zapomniał ;)
                </label>
            </div>
            <button type="submit" id="save-changes">Zapisz zmiany</button>
        </form>
    </div>
    <div class='description'>
    <?php 
    for ($i = 1; $i <= 6; $i++) {
        $user_data = get_userdata($i);
        $user_name = $user_data->display_name;
        echo '<div class="column-group"><label>' . $user_name . '</label><div class="user_content">';
        echo nl2br(replace_links_images_and_nl2br(get_post_meta( $post_id, 'private_custom_field_' . $i, true )));
        echo '</div>';

        $postions = get_post_meta( $post_id, 'trading_postion_' . $i ,true);
        if ($postions) {
            $price_data = get_post_meta( $post_id, 'priceUsd', true ); 
            if ( $price_data  && is_array(  $price_data  )){
                $latest = end($price_data); 
                $currentPrice = $latest['count'];               
            }
            
            $roe = '';
            echo '<div class="positions_history">';
            echo '<label>Zlecenia</label>';
            foreach ($postions as $row) {
                if ( $price_data  && is_array(  $price_data  )){
                    $roe = '<span>Current price: ' . $currentPrice . '</span><span>ROE: ' . round((($currentPrice - $row['open_trade']) / $row['open_trade']) * 100, 2) . '%</span>';
                }
                echo '<div class="position_row"><span>Trade Open: ' . $row['open_date'] . '</span><span>Open price: ' . $row['open_trade'] . '</span><span>Close date: ' . $row['close_date'] . '</span><span>Close price: ' . $row['close_trade'] . '</span>' . $roe . '</div>';
            }
            
            echo '</div>';
        }
        echo '</div>';
    }
    ?>
    </div>

    <?php
    $output = ob_get_clean();
    wp_send_json_success($output);
}