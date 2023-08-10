<?php




function fwp_index_custom_field( $params, $class ) {
   if ( 'buy_volume' == $params['facet_name'] ) {
        $cf = (array) $params['facet_value']; // already unserialized
        $last_element = end($cf);
        $params['facet_value'] = $last_element['count'];
        $params['facet_display_value'] = $last_element['count'];
    }
    return $params;
}
// add_filter( 'facetwp_index_row', 'fwp_index_custom_field', 10, 2 );

function fwp_index_your_custom_field( $params, $class ) {
    if ( 'buy_volume' == $params['facet_name'] ) {
        $cf = (array) maybe_unserialize($params['facet_value']);
        $last_element = end($cf);
        $params['facet_value'] = $last_element['count'];
        $params['facet_display_value'] = $last_element['count'];
    }
    return $params;
}
// add_filter( 'facetwp_index_row', 'fwp_index_your_custom_field', 10, 2 );




function save_last_edit_date($meta_id, $object_id, $meta_key, $meta_value) {
    if ($meta_key !== 'last_edit' || $meta_key !== 'last_edit_by_user') {
        update_post_meta($object_id, 'last_edit', current_time('mysql'));

        $user_id = get_current_user_id();
        if ($user_id) {
            update_post_meta($object_id, 'last_edit_user', $user_id);
            update_post_meta($object_id, 'last_edit_by_user', current_time('mysql'));
        }
       
    }
    // FWP()->indexer->index( $object_id );
}
add_action('updated_post_meta', 'save_last_edit_date', 10, 4);


function calculate_change($data, $current_value) {
    $result = array();
  
    $time_ranges = array(
      '1h' => 3600,
      '4h' => 14400,
      '12h' => 43200,
      '24h' => 86400,
    );
  
    foreach ($time_ranges as $range => $seconds) {
      $count = 0;
      $last_value = null;
  
      for ($i = count($data) - 1; $i >= 0; $i--) {
        $timestamp = strtotime($data[$i]['updated_at']);
  
        if ($timestamp < (time() - $seconds)) {
          break;
        }
  
        $count++;
        $last_value = $data[$i]['count'];
      }
  
      if ($last_value !== null) {
        if ($last_value !== 0) {
            $change = ($current_value - $last_value) / $last_value * 100;
        } else {
            $change = 0;
        }
        $result[$range] = round($change, 2);
      } else {
        $result[$range] = null;
      }
    }
  
    return $result;
  }

function get_change($data, $timestamps, $current_value, $timeframe) {
    $time = time();
    $count = count($data);
    $total_change = 0;
    $current_timestamp = strtotime($data[$count - 1]['updated_at']);
    $prev_timestamp = $current_timestamp;

    for ($i = $count - 1; $i >= 0; $i--) {
        $timestamp = $timestamps[$i];
        if ($current_timestamp - $timestamp > $timeframe) {
            break;
        }
        $prev_value = $data[$i]['count'];
        $current_value = $data[$count - 1]['count'];
        $change = ($current_value - $prev_value) / $prev_value * 100;
        $total_change += $change;
        $prev_timestamp = $timestamp;
    }

    $average_change = $total_change / ($current_timestamp - $prev_timestamp) * $timeframe;
    $change = ($current_value - $prev_value) / $prev_value * 100;
    $result = $change - $average_change;
    return round($result, 2);
}

// Oblicz spadek % ceny (tylko spadek)
function calculate_percentage_change($data)
{
    $data_count = count($data);
    $start_index = max(0, $data_count - 30);

    $max_count = $data[$start_index]['count'];
    for ($i = $start_index; $i < $data_count; $i++) {
        $item = $data[$i];
        if ($item['count'] > $max_count) {
            $max_count = $item['count'];
        }
    }

    $last_count = $data[$data_count - 1]['count'];
    if ($max_count > 0) {
        $percentage_change = (($max_count - $last_count) / $max_count) * 100;
    } else {
        $percentage_change = 0;
    }

    $percentage_change = round($percentage_change, 1);
    return $percentage_change;
}

function calculate_percentage_drop($start_price, $end_price, $current_price) {
    // Oblicz różnicę między początkową i końcową ceną
    $price_difference = $start_price - $end_price;

    // Oblicz, ile procent stanowi różnica w cenie
    $percentage_difference = ($price_difference / $start_price) * 100;

    // Oblicz wartość spadku od początkowej ceny do bieżącej ceny
    $drop = $start_price - $current_price;

    // Oblicz, ile procent stanowi wartość spadku w stosunku do początkowej ceny
    $percentage_drop = ($drop / $start_price) * 100;

    // Oblicz, ile procent stanowi wartość spadku w stosunku do różnicy cen
    $final_percentage = ($percentage_drop / $percentage_difference) * 100;

    // Zaokrąglij wynik do dwóch miejsc po przecinku i zwróć
    return round($final_percentage, 2);
}


// Oblicz spadek % ceny z ostatnich 24h dla wskaźnika fibo
function calculate_24h_fibo($data) {
        $latest = end($data ); 
        $current_value = $latest['count'];

        foreach ($data as $entry) {
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

        return calculate_percentage_drop($min, $max, $current_value);
}

// Oblicz spadek % ceny z całej historii dla wskaźnika fibo
function calculate_atl_to_ath_fibo($data) {
        $latest = end($data ); 
        $current_value = $latest['count'];

        foreach ($data as $entry) {
            
            $count = $entry['count'];
            
            if ($min === null || $count < $min) {
                $min = $count;
            }
            
            if ($max === null || $count > $max) {
                $max = $count;
            }
        }

        return calculate_percentage_drop($min, $max, $current_value);
}


function prepare_chart_data($post_id, $meta_key, $limit = null) {
    $data = get_post_meta($post_id, $meta_key, true);
    if (!is_array($data)) {
        return array();
    }

    $hourly_data = array();
    foreach ($data as $item) {
        $item_time = strtotime($item['updated_at']);
        $hour_key = date('Y-m-d H:00:00', $item_time);
    
        if (!array_key_exists($hour_key, $hourly_data) || $hourly_data[$hour_key]['count'] < $item['count']) {
            $hourly_data[$hour_key] = [
                'updated_at' => $hour_key,
                'count' => $item['count'],                
            ];
        }

        // Ograniczenie ilości odczytywanych rekordów
        if (!is_null($limit) && count($hourly_data) >= $limit) {
            break;
        }
    }

    $new_data = array();
    $index = 0;
    foreach ($hourly_data as $item) {
        $new_data[$index] = $item;
        $index++;
    }

    return $new_data;
}

function prepare_chart_data_1h_open($post_id, $meta_key, $limit = null) {
    $data = get_post_meta($post_id, $meta_key, true);
    if (!is_array($data)) {
        return array();
    }

    $hourly_data = array();
    foreach ($data as $item) {
        $item_time = strtotime($item['updated_at']);
        $hour_key = date('Y-m-d H:00:00', $item_time);
    
        if (!array_key_exists($hour_key, $hourly_data) || $hourly_data[$hour_key]['open'] < $item['open']) {
            $hourly_data[$hour_key] = [
                'updated_at' => $hour_key,
                'open' => $item['open'],                
            ];
        }

        // Ograniczenie ilości odczytywanych rekordów
        if (!is_null($limit) && count($hourly_data) >= $limit) {
            break;
        }
    }

    $new_data = array();
    $index = 0;
    foreach ($hourly_data as $item) {
        $new_data[$index] = $item;
        $index++;
    }

    return $new_data;
}
    

function prepare_chart_data_15minutes($post_id, $meta_key, $limit = null) {
    $data = get_post_meta($post_id, $meta_key, true);
    if (!is_array($data)) {
        return array();
    }

    $hourly_data = array();
    foreach ($data as $item) {
        $item_time = strtotime($item['updated_at']);
        $hour_key = date('Y-m-d H:i:s', floor($item_time / (15 * 60)) * (15 * 60));
    
        if (!array_key_exists($hour_key, $hourly_data)) {
            $hourly_data[$hour_key] = [
                'updated_at' => $hour_key,
                'count' => $item['count'],                
            ];
        } else {
            if ($hourly_data[$hour_key]['count'] < $item['count']) {
                $hourly_data[$hour_key]['count'] = $item['count'];
            }
        }

        // Ograniczenie ilości odczytywanych rekordów
        if (!is_null($limit) && count($hourly_data) >= $limit) {
            break;
        }
    }

    $new_data = array();
    $index = 0;
    foreach ($hourly_data as $item) {
        $new_data[$index] = $item;
        $index++;
    }

    return $new_data;
}


// EX. update_field_with_history('twitter_followers',$followers);

function update_field_with_history($key,$value,$post_id){
    $data = get_post_meta($post_id, $key, true);
    $current_time = current_time('mysql');
        
    if (!is_array($data)) {
        $data = array();
    }
    
    $data[] = array( 
        'count' => $value,
        'updated_at' => $current_time
    );
    
    update_post_meta($post_id, $key, $data);
}


function update_field_with_history_if_changed($key,$value,$post_id){
    $data = get_post_meta($post_id, $key, true);
    $current_time = current_time('mysql');
        
    if (!is_array($data)) {
        $data = array();        
    }

    if (!empty($data)) {
        $last_data = end($data);
        
        if ($last_data !== false && $last_data['count'] !== $value) {
            if ($key == 'token_changelog') {
                $user_id = get_current_user_id();
                if ($user_id) {
                    $value = $value . ' (' . $user_id . ')';
                }
            }

            $data[] = array(  
                'count' => $value,
                'updated_at' => $current_time
            );

            update_post_meta($post_id, $key, $data);
        }
    } else {
        $data[] = array(  
            'count' => $value,
            'updated_at' => $current_time
        );
 
        update_post_meta($post_id, $key, $data);
    }
}


 
function generate_hourly_data_v2($key, $post_id) {
    date_default_timezone_set('Europe/Warsaw');

    $data = get_post_meta($post_id, $key, true);

    if (empty($data) || !is_array($data)) {
        return;
    }

    $prev_hourly_data = get_post_meta($post_id, $key . "_hourly", true);

    if (!is_array($prev_hourly_data)) {
        $prev_hourly_data = array();
    }
    
    $current_hour = date('Y-m-d H:00'); 

    $hourly_data = array();
    $data_hour = '';

    foreach($data as $data_point) {
        $data_hour = date('Y-m-d H:00', strtotime($data_point['updated_at']));

        // If it's a new hour and there's existing data for the previous hour, save it to the hourly data
        if ($data_hour == $current_hour && !empty($current_hour_data)) {
            if (!array_key_exists($current_hour, $prev_hourly_data)) {
                $hourly_data[$current_hour] = array(
                    'count' => $current_hour_data['count'],                    
                );
            }

            $current_hour_data = array();
        }

        $current_hour_data = $data_point;
        $current_hour = $data_hour;
    }

    $hourly_data = array_merge($prev_hourly_data, $hourly_data);

    update_post_meta($post_id, $key . "_hourly", $hourly_data);
}




function generate_hourly_data($key, $post_id) {
    // Get data from 5-minute intervals
    $data = get_post_meta($post_id, $key, true);

    if (empty($data) || !is_array($data)) {
        return;
    }

    // Get the previously processed hourly data
    $prev_hourly_data = get_post_meta($post_id, $key . "_1h", true);

    if (!is_array($prev_hourly_data)) {
        $prev_hourly_data = array();
    }

    // Get the current hour
    $current_hour = date('Y-m-d H:00:00');

    // Initialize the new hourly data array
    $hourly_data = array();
    $current_hour_data = array();
    $data_hour = '';

    foreach($data as $data_point) {
        $data_hour = date('Y-m-d H:00:00', strtotime($data_point['updated_at']));

        // If it's a new hour and there's existing data for the previous hour, save it to the hourly data
        if ($data_hour !== $current_hour && !empty($current_hour_data)) {
            if (!array_key_exists($current_hour, $prev_hourly_data)) {
                $hourly_data[$current_hour] = array(
                    'open' => $current_hour_data[0]['count'],
                    'high' => max(array_column($current_hour_data, 'count')),
                    'low' => min(array_column($current_hour_data, 'count')),
                    'close' => end($current_hour_data)['count'],
                    'updated_at' => $current_hour
                );
            }

            $current_hour_data = array();
        }

        // Add the data point to the current hour's data
        $current_hour_data[] = $data_point;
        $current_hour = $data_hour;
    }

    // Merge the previous and new hourly data
    $hourly_data = array_merge($prev_hourly_data, $hourly_data);

    // Save the hourly data
    update_post_meta($post_id, $key . "_1h", $hourly_data);

    // After processing and saving hourly data, clear the old data from the $data variable
    // $data = array_filter($data, function($datapoint) {
    //     // Remove data older than 48 hours
    //     return strtotime($datapoint['updated_at']) > strtotime('-48 hours');
    // });

    // // // Update the post meta with the reduced data set
    // update_post_meta($post_id, $key, array_values($data));
}



function clean_data_with_array_as_val($key, $post_id) {
    // Get data from 5-minute intervals
    $data = get_post_meta($post_id, $key, true);

    if (empty($data) || !is_array($data) || empty($data[0])) {
        return;
    }

    // Initialize an empty array for the hourly data
    $hourly_data = array();

    // Ensure $data[0] has 'updated_at' key
    if (!isset($data[0]['updated_at'])) {
        return;
    }

    // The start of the first hour
    $current_hour = date('Y-m-d H:00:00', strtotime($data[0]['updated_at']));

    // Initialize the nearest data point to the start of the hour
    $nearest_data_point = $data[0];

    foreach ($data as $data_point) {
        // Make sure 'updated_at' key exists in $data_point
        if (!isset($data_point['updated_at'])) {
            continue; // Skip this data point if 'updated_at' is not set
        }
    
        // Make sure 'updated_at' key exists in $nearest_data_point
        if (!isset($nearest_data_point['updated_at'])) {
            $nearest_data_point = $data_point; // Set the current data point as the nearest one if 'updated_at' is not set in the nearest data point
        }
    
        // Check if we've moved to a new hour
        if (date('Y-m-d H:00:00', strtotime($data_point['updated_at'])) != $current_hour) {
            // Save the nearest data point from the previous hour
            $hourly_data[] = $nearest_data_point;
    
            // Start a new hour
            $current_hour = date('Y-m-d H:00:00', strtotime($data_point['updated_at']));
    
            // Reset the nearest data point for the new hour
            $nearest_data_point = $data_point;
        } else {
            // Update the nearest data point if this data point is closer to the start of the hour
            if (abs(strtotime($data_point['updated_at']) - strtotime($current_hour)) < abs(strtotime($nearest_data_point['updated_at']) - strtotime($current_hour))) {
                $nearest_data_point = $data_point;
            }
        }
    }

    // Save the nearest data point for the last hour
    $hourly_data[] = $nearest_data_point;

    // Save the hourly data
    update_post_meta($post_id, $key, $hourly_data);
}





function calculatePercentageChange($data, $timeframe) {
    if (is_array($data) && count($data) > 1) {
        // Sortujemy dane chronologicznie
        usort($data, function($a, $b) {
            return strtotime($a['updated_at']) - strtotime($b['updated_at']);
        });
      
        $now = new DateTime(); // Utwórz obiekt daty i czasu dla aktualnego czasu
        if($timeframe){
            $interval = $timeframe .' hours';
        }else{
            $interval = '4 hours'; 
        }

        $date_interval = DateInterval::createFromDateString($interval);
        $target_date = $now->sub($date_interval); // Odejmij interwał czasowy od aktualnego czasu
        
        $closest = null;
        $closest_diff = null;
        
        foreach ($data as $item) {
            $item_date = new DateTime($item['updated_at']);
            $diff = abs($target_date->getTimestamp() - $item_date->getTimestamp());
        
            if (is_null($closest) || $diff < $closest_diff) {
                $closest = $item;
                $closest_diff = $diff;
            }
        }
        $past_value = $closest['count'];
       
        if ($past_value !== null && $past_value != 0) {
            $percentage_change = (($data[count($data) - 1]['count'] - $past_value) / $past_value) * 100;
        } else {
            $percentage_change = 1;
        }
        return round($percentage_change, 2);
    }
}



function calculatePercentageChange_for_session($data) {
    if (count($data) > 1) {

         // Sortujemy dane chronologicznie
         usort($data, function ($a, $b) {
            return strtotime($a['updated_at']) - strtotime($b['updated_at']);
        });
        
        $now = new DateTime(); // Utwórz obiekt daty i czasu dla aktualnego czasu
        $now->setTime(0, 0); // Ustaw godzinę na 00:00 dnia dzisiejszego
        
        $closest = null;
        $closest_diff = null;
        
        foreach ($data as $item) {
            $item_date = new DateTime($item['updated_at']);
            $diff = abs($now->getTimestamp() - $item_date->getTimestamp());
        
            if (is_null($closest) || $diff < $closest_diff) {
                $closest = $item;
                $closest_diff = $diff;
            }
        }
        $past_value = $closest['count'];
        
        if ($past_value !== null && $past_value != 0) {
            $percentage_change = (($data[count($data) - 1]['count'] - $past_value) / $past_value) * 100;
        } else {
            $percentage_change = 1;
        }
        return round($percentage_change, 2);
    }
}
    

function calculate_age($datetime) {
    $now = new DateTime(current_time('mysql'));
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    if ($date){
        $diff = $now->diff($date);
        $hours = $diff->h;
        $hours += $diff->days * 24;
        $minutes = $diff->i;
        
        if ($hours >= 24) {
            $days = floor($hours / 24);
            $hours = $hours % 24;
            return $days . 'D ' . $hours . 'h';
        } elseif ($hours >= 1) {
            return $hours . 'h';
        } else {
            return $minutes . 'm';
        }
    }
}


function checkdateformat($data) {
    $format = 'Y-m-d H:i:s';
    $d = DateTime::createFromFormat($format, $data);
    return $d && $d->format($format) == $data;
}


function format_number($number) {
    if ($number >= 1000 && $number < 1000000) {
        $number = number_format($number/1000, 1, '.', '').'k';
    } elseif ($number >= 1000000 && $number < 1000000000) {
        $number = number_format($number/1000000, 1, '.', '').'m';
    } elseif ($number >= 1000000000 && $number < 1000000000000) {
        $number = number_format($number/1000000000, 1, '.', '').'b';
    }
    return $number;
}


function clean_number($str) {

    $str = str_replace("$", "", $str);

	$check_zero = explode(".", $str, 2);
	$first = $check_zero[0];
	if ($first == 0){
		$str = 0; 
	}

    if ($str[strlen($str) - 1] == "K" || $str[strlen($str) - 1] == "k"){
        $find = array("K","k");
        $remove_letters = str_replace($find, "", $str);
        $fix_num = str_replace("," , ".", $remove_letters);
        $clean_number = $fix_num * 1000;
    }else if ($str[strlen($str) - 1] == "M" || $str[strlen($str) - 1] == "m"){
        $find = array("M","m");
        $remove_letters = str_replace($find, "", $str);
        $fix_num = str_replace("," , ".", $remove_letters);
        $clean_number = $fix_num * 1000000;
    }else if ($str[strlen($str) - 1] == "B" || $str[strlen($str) - 1] == "b"){
        $find = array("B","b");
        $remove_letters = str_replace($find, "", $str);
        $fix_num = str_replace("," , ".", $remove_letters);
        $clean_number = $fix_num * 1000000000;
    }else{
		$clean_number = $str;
		$clean_number = str_replace("," , ".", $clean_number);
	}

    return $clean_number;
}    









function clear_old_data($key, $post_id) {
    // Pobierz dane z metadanych posta
    $data = get_post_meta($post_id, $key, true);
    if (is_array($data)) {
        // Obecny czas i data
        $current_time = current_time('mysql');

        // Wylicz czas dla 23:00 dnia wczorajszego
        $yesterday = strtotime('-1 day', strtotime($current_time));
        $yesterday_23 = date('Y-m-d 17:00:00', $yesterday);

        // Przefiltruj dane, usuwając te starsze niż 23:00 dnia wczorajszego
        $filtered_data = array_filter($data, function ($item) use ($yesterday_23) {
            $item_time = strtotime($item['updated_at']);
            return $item_time < strtotime($yesterday_23);
        });
        $filtered_data = array_values($filtered_data);
        // Zaktualizuj metadane posta z przefiltrowaną tablicą danych
        update_post_meta($post_id, $key, $filtered_data);
    } else {
        delete_post_meta($post_id,$key);
    }
}

function fix_arrays($key,$post_id){
    $data = get_post_meta( $post_id, $key ,true);
    
    if (!is_array($data)) {
        $data = array();
    }
            
    $new_array = array();
    $i = 0;
    foreach ($data as $keyz => $value) {
        $new_array[$i] = $value;
        $i++;
    }

    update_post_meta($post_id, $key, $new_array);

}



function calculate_custom_fields_size($post_id) {
    $custom_fields = get_post_custom($post_id);
    $size = 0;
    foreach ($custom_fields as $key => $value) {
        foreach ($value as $val) {
            $size += strlen($key . $val);
        }
    }
    $size_kb = round($size / 1024, 2);
    return $size_kb;
}


function removeElement($arr, $value) {
    return array_filter($arr, function($item) use ($value) {
        return $item['count'] !== $value;
    });
}

function user_name() {
    $user_id = get_current_user_id();

    $user_name = 'Unknown';

    if ($user_id) {
        $user_data = get_userdata($user_id);
        $user_name = $user_data->display_name;
    }
    return  $user_name;
}
