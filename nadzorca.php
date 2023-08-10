<?php

function check_and_run_function($filename, $init_function_name) {
    $file_path = get_stylesheet_directory() . '/logs/' . $filename . '.csv';

    if (!file_exists($file_path)) {
        return;
    }

    $lines = file($file_path);

    if (empty($lines)) {
        return;
    }

    $last_line = trim(end($lines));
    $last_line_parts = explode(',', $last_line);

    if (count($last_line_parts) < 3) {
        return;
    }

    $last_time = strtotime($last_line_parts[0]);
    $current_time = strtotime(current_time('mysql'));

    if (($current_time - $last_time) >= (3 * 60) && intval(explode('/', $last_line_parts[2])[0]) < intval(explode('/', $last_line_parts[2])[1])) {
        if (function_exists($init_function_name)) {
            call_user_func($init_function_name);
            save_log_to_file('-', '-', 'Nadzorca' , 'Resetuję '. $init_function_name);
        }
    }
}



function nadzorca() {
    $stop_flag = get_transient('check_and_run_function_stop_flag');
    save_log_to_file('-', '-', 'Nadzorca' , 'Uruchomiony');
    while (!$stop_flag) {
        save_log_to_file('-', '-', 'Nadzorca' , 'Sprawdzam');
        check_and_run_function('allinone_heartbeat', 'init_all_tests');
        check_and_run_function('dex_heartbeat', 'get_dexscreener_data_loop');
        // check_and_run_function('twitter_heartbeat', 'get_twitter_data');
        // check_and_run_function('lq_heartbeat', 'get_liquid_loop');

        sleep(60);  // Poczekaj 5 minut przed kolejnym sprawdzeniem

        // Sprawdź, czy funkcja powinna zostać zatrzymana
        $stop_flag = get_transient('check_and_run_function_stop_flag');
    }
}

function stop_nadzorca() {
    set_transient('check_and_run_function_stop_flag', true);
    save_log_to_file('-', '-', 'Nadzorca' , 'Zatrzymanie');
}

function reset_nadzorca() {
    stop_nadzorca();
    sleep(5); 
    delete_transient('check_and_run_function_stop_flag');
    nadzorca();
}

 