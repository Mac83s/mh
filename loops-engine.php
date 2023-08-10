<?php

$GLOBALS['container_configs'] = array(
    'vfn' => [
        'groups' => ['A', 'B'],
        'function_mode' => 'regular',
    ],
    'dex' => array(
        'groups' => ['A', 'B', 'C', 'D'],
        'function_mode' => 'regular',
    ),
    'tta' => [
        'groups' => ['A', 'B', 'C', 'D'],
        'function_mode' => 'read_from_main',
    ],
    'sec' => [
        'groups' => ['A', 'B', 'C', 'D'],
        'function_mode' => 'read_from_main',
    ],
    'dead' => [
        'groups' => ['A', 'B', 'C'],
        'function_mode' => 'regular',
    ],
    'cgo' => [
        'groups' => ['A', 'B', 'C','D'],
        'function_mode' => 'read_from_main',
    ],
    'binance' => [
        'groups' => ['A', 'B'],
        'function_mode' => 'regular',
    ],
);



function add_container_cron_actions() {
    $container_configs = $GLOBALS['container_configs'];

    foreach ($container_configs as $type => $config) {
        $groups = $config['groups'];
        
        foreach ($groups as $group) {
            $event_name = "looper_cron_{$type}_{$group}";
            add_action($event_name, function() use ($type, $group, $config) {
                unlock_files_if_stuck();
                control_and_process_files($type);
            });

            // Ustawienie harmonogramu dla danego typu i grupy
            $interval = isset($config['cron_interval']) ? $config['cron_interval'] : 'everyminute';
            if (!wp_next_scheduled($event_name)) {
                wp_schedule_event(time(), $interval, $event_name);
            }
        }
    }
}

add_container_cron_actions();


function control_and_process_files($type) {

    $container_configs = $GLOBALS['container_configs'];
    $config = $container_configs[$type];
    $groups = $config['groups'];

    $function_name = 'audit_token_' . $type;
    $function_mode = $config['function_mode'];

    if (!function_exists($function_name)) {
        return;
    }

    $files_empty_or_missing = true;
    foreach ($groups as $group) {
        $filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$group}.json";
        $locked_filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$group}_locked.json";

        if ((file_exists($filename) && filesize($filename) > 0) || (file_exists($locked_filename) && filesize($locked_filename) > 0)) {
            $files_empty_or_missing = false;
        }
    }
    if ($files_empty_or_missing && $function_mode != 'read_from_main') {
        return;
    }
    
    

    if ($function_mode === 'read_from_main') {
        $groups_dex = $container_configs['dex']['groups'];
        foreach ($groups_dex as $group_dex) {
            $filename_dex = get_stylesheet_directory() . "/files/post_ids_dex_{$group_dex}.json";
            $locked_filename_dex = get_stylesheet_directory() . "/files/post_ids_dex_{$group_dex}_locked.json";
            
            $filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$group_dex}.json";

            if (file_exists($locked_filename_dex)) {
                $source_filename = $locked_filename_dex;
            } else {
                $source_filename = $filename_dex;
            }

            if (!file_exists($filename)) {
                // Jeżeli plik nie istnieje, skopiuj całą zawartość.
                copy($source_filename, $filename);
            } else {
                // Jeżeli plik istnieje, porównaj dane i dopisz tylko nowe rekordy.
                $source_data = json_decode(file_get_contents($source_filename), true);
                $destination_data = json_decode(file_get_contents($filename), true);

                // Indeksuj dane docelowe dla łatwiejszego porównania.
                $destination_data_indexed = [];
                foreach ($destination_data as $item) {
                    $destination_data_indexed[$item['id']] = $item;
                }

                // Przejdź przez dane źródłowe i dodaj nowe rekordy do danych docelowych.
                foreach ($source_data as $item) {
                    if (!isset($destination_data_indexed[$item['id']])) {
                        $destination_data[] = $item; // Dodaj nowy rekord.
                    }
                }

                // Usuń stare rekordy z danych docelowych.
                $destination_data = array_filter($destination_data, function($item) use ($source_data) {
                    foreach ($source_data as $source_item) {
                        if ($source_item['id'] == $item['id']) {
                            return true;
                        }
                    }
                    return false;
                });

                // Zapisz zaktualizowane dane do pliku docelowego.
                file_put_contents($filename, json_encode($destination_data));
            }

        }
    }

    $oldestProcessedTime = PHP_INT_MAX;
    $oldestGroup = null;
    $totalFiles = 0;
    $defaultExecutionTime = null;

    foreach ($groups as $group) {
        
        $globPattern = get_stylesheet_directory() . "/files/processed_{$type}_{$group}.json";
        $files = glob($globPattern);
        $totalFiles += count($files);

        $process_history_filename = get_stylesheet_directory() . '/files/history/process_history_' . $type . '_' . $group.'.json';

        if (!file_exists($process_history_filename)) {
            $oldestGroup = $group;
            $oldestProcessedTime = 0;
            break;
        }

        $process_history = json_decode(file_get_contents($process_history_filename), true);

        if ($process_history === null && json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        $latestProcessedTime = 0;

        foreach ($process_history as $process) {
            if (!isset($process['start_time'])) {
                $process['start_time'] = current_time('timestamp');
            }  
        
            if (isset($process['start_time']) && $process['start_time'] > $latestProcessedTime) {
                $latestProcessedTime = $process['start_time'];
            }
        }

        if ($latestProcessedTime < $oldestProcessedTime) {
            $oldestProcessedTime = $latestProcessedTime;
            $oldestGroup = $group;
        }
    }

    if ($oldestGroup === null) {
        return;
    }

    $group = $oldestGroup;

    if ($totalFiles < count($groups)-1) {
        $filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$group}.json";
        $locked_filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$group}_locked.json";

        $processed_filename = get_stylesheet_directory() . "/files/processed_{$type}_{$group}.json";
        if (file_exists($processed_filename)) {
            return;
        }
        file_put_contents($processed_filename, current_time('timestamp'));

        $process_history_filename = get_stylesheet_directory() . '/files/history/process_history_' . $type . '_' . $group.'.json';
        if (!file_exists($process_history_filename)) {
            $process_history = [];
        } else {
            $process_history = json_decode(file_get_contents($process_history_filename), true);
            if ($process_history === null && json_last_error() !== JSON_ERROR_NONE) {
                echo "Błąd odczytu pliku JSON: " . json_last_error_msg();
                return;
            }
        }

        $loop_id = 'ID_' . current_time('timestamp');
        $process_history[$loop_id]['start_time'] = current_time('timestamp');
        file_put_contents($process_history_filename, json_encode($process_history, JSON_PRETTY_PRINT));

        try {
            if (!file_exists($locked_filename)) {
                rename($filename, $locked_filename);
                $data = json_decode(file_get_contents($locked_filename), true);

                foreach ($data as $key => $post_data) {
                    $save_changes = false;

                    $process_kill = get_stylesheet_directory() . "/files/process_kill_{$type}.json";
                    if (file_exists($process_kill)) {
                        break;
                    }

                    if (current_time('timestamp') >= $post_data["next_update"] && (!isset($post_data["status"]) || ($post_data["status"] != 'skip' && $post_data["status"] != 'remove'))) {   

                        file_put_contents($processed_filename, current_time('timestamp'));  

                        if (function_exists($function_name)) {
                            $status = call_user_func($function_name, $post_data['id']);
                            if (is_numeric($status)) {                            
                                $data[$key]["next_update"] = current_time('timestamp') + $status;
                            }
                            $data[$key]["status"] = $status;
                        }
                        $save_changes = true;
                    }

                    if ($save_changes) {
                        FWP()->indexer->index($post_data['id']);
                        file_put_contents($locked_filename, json_encode($data, JSON_PRETTY_PRINT));
                    }
                }
                sleep(1);
                $data_to_clean = json_decode(file_get_contents($locked_filename), true);

                $cleaned_data = array();

                foreach ($data_to_clean as $index => $post_data) {
                    if (!isset($post_data['status']) || $post_data['status'] !== 'remove') {
                        $cleaned_data[] = $post_data;
                    }
                }

                file_put_contents($locked_filename, json_encode($cleaned_data, JSON_PRETTY_PRINT));

                rename($locked_filename, $filename);
            }
        } catch (Exception $e) {
            if (file_exists($locked_filename)) {
                rename($locked_filename, $filename);
            }
            error_log("Błąd podczas przetwarzania pliku: " . $e->getMessage());
        }

        $process_history[$loop_id]['end_time'] = current_time('timestamp');
        $process_history[$loop_id]['execution_time'] = $process_history[$loop_id]['end_time'] - $process_history[$loop_id]['start_time'];
        file_put_contents($process_history_filename, json_encode($process_history, JSON_PRETTY_PRINT));
        prune_history_file($process_history_filename, 20);

        if (file_exists($processed_filename)) {
            unlink($processed_filename);
        }
    }
    
}


















function unlock_files_if_stuck() {
    foreach ($GLOBALS['container_configs'] as $type => $config) {
        $groups = $config['groups'];
        foreach ($groups as $group) {
            $locked_filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$group}_locked.json";
            $processed_filename = get_stylesheet_directory() . "/files/processed_{$type}_{$group}.json";
            if (file_exists($processed_filename)) {
                $file_time = (int) file_get_contents($processed_filename);
                $time_difference = current_time('timestamp') - $file_time;
                if ($time_difference > 300) {
                    if (file_exists($locked_filename)){
                        $unlocked_filename = str_replace('_locked', '', $locked_filename);
                        rename($locked_filename, $unlocked_filename);
                    }
                    unlink($processed_filename);
                }
            }
        }
    }
}






















function prune_history_file($filename, $max_records) {
    if (file_exists($filename)) {
        // Dekodowanie pliku JSON
        $data = json_decode(file_get_contents($filename), true);

        // Sprawdzenie, czy dekodowanie się powiodło
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            echo "Błąd odczytu pliku JSON: " . json_last_error_msg();
            return;
        }

        // Usunięcie starych rekordów, jeżeli jest ich za dużo
        if (count($data) > $max_records) {
            // Sortowanie danych od najstarszych do najmłodszych
            uasort($data, function ($a, $b) {
                return $a['start_time'] <=> $b['start_time'];
            });

            // Usunięcie starych rekordów
            $data = array_slice($data, -1 * $max_records);

            // Zapisanie zmienionych danych do pliku
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}












function add_post_id_to_file($post_id, $type) {
    if (isset($GLOBALS['container_configs'][$type])) {
        $files = $GLOBALS['container_configs'][$type]['groups'];
    } else {
        $files = ['A', 'B'];
    }
    $found = false;

    $smallest_file = null;
    $smallest_file_size = PHP_INT_MAX;

    foreach ($files as $file) {
        $filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$file}.json";
        $locked_filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$file}_locked.json";

        if (!file_exists($filename) && !file_exists($locked_filename)) {
            $data = array(
                array(
                    "next_update" => current_time('timestamp'),
                    "id" => $post_id,
                    "address" => get_post_meta( $post_id, 'address',true),
                ),
            );
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
            return;
        }

        $checked_filename = file_exists($filename) ? $filename : $locked_filename;
        $data = json_decode(file_get_contents($checked_filename), true);

        foreach ($data as $post_data) {
            if ($post_data['id'] == $post_id) {
                $found = true;
                break;
            }
        }

        if (!$found && !file_exists($locked_filename)) {
            if (count($data) < $smallest_file_size) {
                $smallest_file = $filename;
                $smallest_file_size = count($data);
            }
        }
    }

    if (!$found && $smallest_file !== null) {
        $filename = $smallest_file;
        $locked_filename = str_replace('.json', '_locked.json', $smallest_file);

        $data = json_decode(file_get_contents($filename), true);
        $data[] = array(
            "next_update" => current_time('timestamp'),
            "id" => $post_id,
            "address" => get_post_meta( $post_id, 'address',true),
        );

        rename($filename, $locked_filename); // LOCK
        file_put_contents($locked_filename, json_encode($data, JSON_PRETTY_PRINT));
        rename($locked_filename, $filename); // UNLOCK
    }
}












function remove_post_id_from_all_containers($post_id) {

    $container_configs = $GLOBALS['container_configs'];
    foreach ($container_configs as $type => $config) {
        remove_post_id_from_file($post_id, $type);
    }
}


function remove_post_id_from_file($post_id, $type) {
    if (isset($GLOBALS['container_configs'][$type])) {
        $files = $GLOBALS['container_configs'][$type]['groups'];
    } else {
        // Wartość domyślna dla $groups, jeśli $type nie istnieje w tablicy
        $files = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
    }
    $file_to_remove_from = null;

    // Sprawdź, czy post_id znajduje się w którymkolwiek z plików (zablokowanym lub nie)
    foreach ($files as $suffix) {
        $filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$suffix}.json";
        $locked_filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$suffix}_locked.json";

        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
        } elseif (file_exists($locked_filename)) {
            $data = json_decode(file_get_contents($locked_filename), true);
        } else {
            continue;
        }

        foreach ($data as $post_data) {
            if ($post_data['id'] === $post_id) {
                $file_to_remove_from = $suffix;
                break 2;
            }
        }
    }

    if ($file_to_remove_from === null) {
        // post_id nie został znaleziony w żadnym pliku, zakończ funkcję
        return;
    }

    $max_retries = 5;
    $retry_count = 0;

    while ($retry_count < $max_retries) {
        $filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$file_to_remove_from}.json";
        $locked_filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$file_to_remove_from}_locked.json";

        if (file_exists($locked_filename)) {
            // Plik jest zablokowany, oczekiwanie na odblokowanie
            sleep(1);
            $retry_count++;
            continue;
        }

        if (file_exists($filename)) {
            // Odblokowany plik istnieje
            $data = json_decode(file_get_contents($filename), true);

            // Wyszukaj i usuń post_id
            $index_to_remove = -1;
            foreach ($data as $index => $post_data) {
                if ($post_data['id'] === $post_id) {
                    $index_to_remove = $index;
                    break;
                }
            }

            if ($index_to_remove !== -1) {
                rename($filename, $locked_filename); // LOCK
                unset($data[$index_to_remove]);
                $data = array_values($data);
                file_put_contents($locked_filename, json_encode($data, JSON_PRETTY_PRINT));
                rename($locked_filename, $filename); // UNLOCK
                break;
            }
        }
        $retry_count++;
    }
}











/////////////////////////////////////////
// Funkcja wypełniająca kontenery
/////////////////////////////////////////

function save_ids_to_container($type) {
    sleep(1);

    $args = array(
        'post_type' => 'tokens',
        'posts_per_page' => 1,
    );


    if ($type === 'dex') {

        $args = array(
            'post_type' => 'tokens',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'token_status',
                    'field' => 'slug',
                    'terms' => array('verified', 'upcoming', 'legit'),
                    'operator' => 'IN',
                ),
            ),
        );
    }

    if ($type === 'binance') {

        $args = array(
            'post_type' => 'tokens',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'token_status',
                    'field' => 'slug',
                    'terms' => array('legit'),
                    'operator' => 'IN',
                ),
            ),
        );
    }

    if ($type === 'vfn') {

        $args = array(
            'post_type' => 'tokens',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'token_status', 
                    'operator' => 'NOT EXISTS',
                ),
            ),
          
        );
        
    }  
    if ($type === 'dead') {

        $args = array(
            'post_type' => 'tokens',
            'posts_per_page' => -1,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'group',
                    'field' => 'slug',
                    'terms' => array('db_import'),
                ),
                array(
                    'taxonomy' => 'token_status',
                    'field' => 'slug',
                    'terms' => array('dead','scam','unverified'),
                ),
            ),
        );
        
    }  

    if ($type === 'tta') {
        // Jeśli twitter to pracujemy na duplikacie plików dex
        return;
    }  

    if ($type === 'sec') {
        // Jeśli security to pracujemy na duplikacie plików dex
        return;
    }

    if ($type === 'cgo') {
        // Jeśli coingecko to pracujemy na duplikacie plików dex
        return;
    }
   
    $tokens = new WP_Query($args);

    $groups = $GLOBALS['container_configs'][$type]['groups'];
    $group_count = count($groups);

    $existing_post_ids = array();

    foreach ($groups as $suffix) {
        $filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$suffix}.json";
        $locked_filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$suffix}_locked.json";

        $checked_filename = file_exists($filename) ? $filename : $locked_filename;

        if (file_exists($checked_filename)) {
            $data = json_decode(file_get_contents($checked_filename), true);
            foreach ($data as $post_data) {
                $existing_post_ids[] = $post_data['id'];
            }
        }
    }

    $group_index = 0;

    if ($tokens->have_posts()) {
        while ($tokens->have_posts()) {
            $tokens->the_post();
            $post_id = get_the_ID();
            
            if (in_array($post_id, $existing_post_ids)) {
                continue;
            }
        
            $added = false;
            for ($i = 0; $i < $group_count && !$added; $i++) {
                $suffix = $groups[$group_index];
                $filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$suffix}.json";
                $locked_filename = get_stylesheet_directory() . "/files/post_ids_{$type}_{$suffix}_locked.json";
                
                if (!file_exists($locked_filename)) {
                    if (!file_exists($filename)) {
                        // Tworzenie nowego pliku z dodaniem post_id
                        $data = array();
                        $data[] = array(
                            "next_update" => current_time('timestamp'),
                            "id" => $post_id,
                            "address" => get_post_meta( $post_id, 'address',true),
                        );
        
                        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
                    } else {
                        // Dodawanie post_id do istniejącego pliku
                        $data = json_decode(file_get_contents($filename), true);
                        $data[] = array(
                            "next_update" => current_time('timestamp'),
                            "id" => $post_id,
                            "address" => get_post_meta( $post_id, 'address',true),
                        );
        
                        rename($filename, $locked_filename); // LOCK
                        file_put_contents($locked_filename, json_encode($data, JSON_PRETTY_PRINT));
                        rename($locked_filename, $filename); // UNLOCK
                    }
                    $added = true;
                }
        
                $group_index = ($group_index + 1) % $group_count;
            }
        }
        wp_reset_postdata();
    }
    

}









function reset_all() {

    foreach ($GLOBALS['container_configs'] as $type => $config) {
        $process_kill = get_stylesheet_directory() . "/files/process_kill_{$type}.json";
        file_put_contents($process_kill, '');
        sleep(3);
        if (file_exists($process_kill)) {
            unlink($process_kill);
        }
    }

    delete_all_post_id_files();

}

add_action('wp_ajax_reset_all', 'reset_all');
add_action('wp_ajax_nopriv_reset_all', 'reset_all');


/////////////////////////////////////////
// Funkcja usuwająca wszystkie pliki (bez historii)
/////////////////////////////////////////

function delete_all_post_id_files() {
    $dir = get_stylesheet_directory() . '/files/';
    $files = glob($dir . 'post_ids_*');
    foreach($files as $file) {
        if(is_file($file)) {
            unlink($file);
        }
    }
    $files = glob($dir . 'processing_*');
    foreach($files as $file) {
        if(is_file($file)) {
            unlink($file);
        }
    }
}


/////////////////////////////////////////
// Funkcja usuwająca wszystkie pliki danego typu (bez historii)
/////////////////////////////////////////
function delete_post_id_files_by_type($type) {
    $dir = get_stylesheet_directory() . '/files/';
    $files = glob($dir . "post_ids_{$type}*");
    foreach($files as $file) {
        if(is_file($file)) {
            unlink($file);
        }
    }
    $files = glob($dir . "processing_{$type}*");
    foreach($files as $file) {
        if(is_file($file)) {
            unlink($file);
        }
    }
}


 

function save_ids_to_container_x($type) {

    $process_kill = get_stylesheet_directory() . "/files/process_kill_{$type}.json";
    file_put_contents($process_kill, '');
    sleep(3);

    delete_post_id_files_by_type($type);
    save_ids_to_container($type);
    if (file_exists($process_kill)) {
        unlink($process_kill); 
    }
}



function start_loop_x($type) {
    // generate_dead_to_trash('dead');
    
    control_and_process_files($type);
}

$types = ['vfn','dex', 'tta','sec', 'dead', 'cgo', 'binance'];

foreach ($types as $type) {
    add_action("wp_ajax_save_ids_to_container_{$type}", function () use ($type) {
        save_ids_to_container_x($type);
    });
    add_action("wp_ajax_nopriv_save_ids_to_container_{$type}", function () use ($type) {
        save_ids_to_container_x($type);
    });
    add_action("wp_ajax_start_loop_{$type}", function () use ($type) {
        start_loop_x($type);
    });
    add_action("wp_ajax_nopriv_start_loop_{$type}", function () use ($type) {
        start_loop_x($type);
    });
} 


