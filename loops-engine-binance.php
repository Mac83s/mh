<?php

//++++++++++++++++++++++
//++
//+++ Get data from Dex Screener API
//++
//++++++++++++++++++++++
function audit_token_binance( $post_id ) {

    // $status = get_binance_data( $post_id,'1h' );

    if ($status == 'ok'){
        $status = 60;
    } elseif ($status == 'wait'){
        $status = 300;
    } elseif ($status == 'skip'){
        $status = 'skip';
    } 
    $status = 'skip';
    return $status;
   
}

function get_binance_data( $post_id, $interval) {

    $symbol = get_post_meta($post_id, 'binanceid', true);
    if(!$symbol) return 'skip';

    $api_key = 'yy6Uh1ZoMiZKOr5VfCMJnZowKzvobZUzLCEvAkJ8cGdTTNUcP17EluCKy7ODLga9';
    // $api_secret = 'YNYwzj7K0qYKqwMJBtcJ2Kc67GtxgQ3cY95NDz0qTmHgjAPGqvHde7CmUKbAzj1u';
   
    $base_url = 'https://api.binance.com'; // URL API Binance
    $endpoint = '/api/v3/klines'; // Endpoint dla danych świecowych

    // Parametry do przesłania w zapytaniu
    $last_record = get_post_meta($post_id, 'binance_data', true);
    if (!empty($last_record)) {
        $last_record = end($last_record); // Pobierz ostatni element tablicy
        $start_time = $last_record['close_time']; // Pobierz czas zamknięcia ostatniego rekordu
    } else {
        $start_time = null; // Jeśli nie ma jeszcze żadnych zapisanych rekordów, ustaw start_time na null
    }

    // Parametry do przesłania w zapytaniu
    $params = [
        'symbol' => $symbol,
        'interval' => $interval,
        'limit' => 1000, // Dodaj ten parametr
    ];

    // Jeśli mamy punkt startowy, dodaj go do parametrów zapytania
    if ($start_time !== null) {
        // $params['startTime'] = $start_time;
    }

    // Tworzenie zapytania cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . $endpoint . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Dodaj klucz API do nagłówków zapytania
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-MBX-APIKEY: $api_key"
    ));

    // Wykonywanie zapytania i dekodowanie odpowiedzi
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Sprawdzanie kodu odpowiedzi HTTP
    if ($httpcode == 429 || $httpcode == 418) {
        return 'wait';
    }

    $data = json_decode($response, true);
    if (is_array($data[0])){
        // Przekształcenie danych do formatu z kluczami
        $transformed_data = array_map(function ($item) {
            return [
                'open_time' => $item[0],
                'open_price' => $item[1],
                'high_price' => $item[2],
                'low_price' => $item[3],
                'close_price' => $item[4],
                'volume' => $item[5],
                'close_time' => $item[6],
                'quote_asset_volume' => $item[7],
                'number_of_trades' => $item[8],
                'taker_buy_base_a_vol' => $item[9],
                'taker_buy_quote_a_vol' => $item[10],
                'unused' => $item[11],
            ];
        }, $data);

        // Zapisanie danych do pola niestandardowego
        // update_post_meta($post_id, 'binance_data_' . $interval , $transformed_data);
        update_post_meta($post_id, 'binance_data' , $transformed_data);

        // Dodatkowo zapisz cenę otwarcia do pola niestandardowego 'priceUsd'
        update_field_with_history('priceUsd',$data[0][1],$post_id);
        // Dodatkowo zapisz volumen do pola niestandardowego 'volume_5m'
        update_field_with_history('volume_5m',$data[0][5],$post_id);
        
        return 'ok';
    } else {
        return 'wait';
    }
}   





// Function to generate HTML table for News
function createNewsTable($newsArray) {
    $tableHTML = '<table>';
    $tableHTML .= '<tr><th>Title</th><th>URL</th><th>Time Published</th><th>Authors</th><th>Summary</th><th>Source</th></tr>';
    
    foreach ($newsArray as $news) {
        $tableHTML .= '<tr>';
        $tableHTML .= '<td>' . $news['title'] . '</td>';
        $tableHTML .= '<td><a href="' . $news['url'] . '">Link</a></td>';
        $tableHTML .= '<td>' . $news['time_published'] . '</td>';
        $tableHTML .= '<td>' . implode(', ', $news['authors']) . '</td>';
        $tableHTML .= '<td>' . $news['summary'] . '</td>';
        $tableHTML .= '<td>' . $news['source'] . '</td>';
        $tableHTML .= '</tr>';
    }

    $tableHTML .= '</table>';

    return $tableHTML;
}


// Function to generate HTML table for Cryptopanic
function createCryptopanicTable($cryptopanicArray) {
    $tableHTML = '<table>';
    $tableHTML .= '<tr><th>Title</th><th>URL</th><th>Published At</th><th>Domain</th><th>Votes</th></tr>';
    
    foreach ($cryptopanicArray as $news) {
        $tableHTML .= '<tr>';
        $tableHTML .= '<td>' . $news['title'] . '</td>';
        $tableHTML .= '<td><a href="' . $news['url'] . '">Link</a></td>';
        $tableHTML .= '<td>' . $news['published_at'] . '</td>';
        $tableHTML .= '<td>' . $news['domain'] . '</td>';
        $tableHTML .= '<td>Positive: ' . $news['votes']['positive'] . ', Negative: ' . $news['votes']['negative'] . '</td>';
        $tableHTML .= '</tr>';
    }

    $tableHTML .= '</table>';

    return $tableHTML;
}



    // Function to generate News data array
    function createNewsDataArray($newsArray) {
        $data = [];
        
        foreach ($newsArray as $news) {
            $newsData = [];
            $newsData['Title'] = $news['title'];
            $newsData['URL'] = $news['url'];
            // $newsData['Time Published'] = $news['time_published'];
            // $newsData['Authors'] = implode(', ', $news['authors']);
            $newsData['Summary'] = $news['summary'];
            $newsData['Source'] = $news['source'];

            $data[] = $newsData;
        }

        return $data;
    }

    // Function to generate Cryptopanic data array
    function createCryptopanicDataArray($cryptopanicArray) {
        $data = [];
        
        foreach ($cryptopanicArray as $news) {
            $newsData = [];
            $newsData['Title'] = $news['title'];
            // $newsData['URL'] = $news['url'];
            // $newsData['Published At'] = $news['published_at'];
            // $newsData['Domain'] = $news['domain'];
            $newsData['Votes'] = 'Positive: ' . $news['votes']['positive'] . ', Negative: ' . $news['votes']['negative'];

            $data[] = $newsData;
        }

        return $data;
    }