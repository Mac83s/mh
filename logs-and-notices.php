<?php 

function send_discord_error_log($content){

    $url = "https://discord.com/api/webhooks/1087737694194511872/IT7GFrKzcV1MDNo6bqhP1dR-pnF34bLDnVYBxfB3ZESLmKzwDES4KJQ_g3D9_gWAvjAo";
    $headers = [ 'Content-Type: application/json; charset=utf-8' ];
    $POST = [ 'username' => 'Token Watch Error', 'content' =>  $content];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($POST));
    curl_exec($ch);

}
function send_discord_twitter_log($content){

    $url = "https://discord.com/api/webhooks/1088804881931960371/44sT8_dvvyv89Ca2nGoO7NUW22EKuSB17LNHZTK3z214uJrxrgClfFC4cEw0XlwoskDT";
    $headers = [ 'Content-Type: application/json; charset=utf-8' ];
    $POST = [ 'username' => 'Token Watch', 'content' =>  $content];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($POST));
    curl_exec($ch);

}
function send_discord_buyvolume_log($content){

    $url = "https://discord.com/api/webhooks/1089947973565284463/4PFLyHawIQoMO2JuXQhwgvKf0c9RMQgQIVwzQ0it7Qlhg8-UhTLbUBMjZupga_mZ4F4l";
    $headers = [ 'Content-Type: application/json; charset=utf-8' ];
    $POST = [ 'username' => 'Token Watch', 'content' =>  $content];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($POST));
    curl_exec($ch);

}

//TESTING - PRICATE CHANNEL
function send_discord_alert($content){

    $url = "https://discord.com/api/webhooks/1098944894350925884/rjeh9FW2decsQwUSl6d930s1AQi0VxkSweSB02d6jk8NFwO644dLn3cTSsPZkjdms_Gf";
    $headers = [ 'Content-Type: application/json; charset=utf-8' ];
    $POST = [ 'username' => 'Token Watch', 'content' =>  $content];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($POST));
    curl_exec($ch);

}

function save_log_to_file($post_id, $address, $procces , $content) {
    $file_path = get_stylesheet_directory() . '/logs/dex_screener.csv';
    $config_path = get_stylesheet_directory() . '/logs/dex_screener_config.txt';

    // Odczytanie numeru ostatniej linii z pliku konfiguracyjnego
    $last_row_number = intval(file_get_contents($config_path));

    // Generowanie nowego identyfikatora dla wiersza
    $row_id = $last_row_number + 1;
   
    // Tworzenie wpisu dziennika zdarzeń w formacie CSV
    $log_entry = current_time('mysql') . ',' . $post_id . ',' . $address . ',' . $procces  . ',' . $content . ',' . $row_id  . "\n";
    file_put_contents($file_path, $log_entry, FILE_APPEND);

    // Zapisanie numeru ostatniego wiersza do pliku konfiguracyjnego
    file_put_contents($config_path, strval($row_id));

    // Usunięcie najstarszych wpisów, jeśli jest więcej niż 1000
    $lines = file($file_path);
    $count = count($lines);
    if ($count > 999) {
        $start = 500;
        $new_lines = array_slice($lines, $start);
        file_put_contents($file_path, implode('', $new_lines));
    }
}



function save_heartbeat_to_file($post_id, $counter, $filename){
    $file_path = get_stylesheet_directory() . '/logs/' . $filename . '.csv';

    // Usunięcie najstarszego wpisu, jeśli jest więcej niż 100
    $lines = file($file_path);
    $count = count($lines);
    if ($count > 100) {
        $new_lines = array_slice($lines, $count - 100);
        file_put_contents($file_path, implode('', $new_lines));
    }

    // Dodanie nowego wpisu do pliku
    $log_entry = current_time('mysql') . ',' . $post_id . ',' . $counter . "\n";
    file_put_contents($file_path, $log_entry, FILE_APPEND);
}