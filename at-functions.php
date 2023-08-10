<?php



// funkcja wykrywająca higher high i higher low oraz osobno lower high i lower low
function detectTrend($data) {
    $trend = '';
    $last = count($data) - 1;
    $last_high = 0;
    $last_low = 0;

    for ($i = $last - 1; $i >= 0; $i--) {
        $count = $data[$i]['count'];
        $prev_count = $data[$i + 1]['count'];
    
        if ($count > $prev_count) {
            if ($last_high == 0) {
                $last_high = $i;
            } elseif ($data[$i]['count'] > $data[$last_high]['count']) {
                $trend = 'Higher High';
                break;
            }
        } elseif ($count < $prev_count) {
            if ($last_low == 0) {
                $last_low = $i;
            } elseif ($data[$i]['count'] < $data[$last_low]['count']) {
                $trend = 'Lower Low';
                break;
            }
        }
    }
    return $trend;
}

// wywołanie funkcji i wyświetlenie powiadomienia w zależności od wykrytej tendencji
// $result = detectTrend($data);

// if ($result === 'Higher High') {
//     echo 'Wykryto Higher High';
// } elseif ($result === 'Lower Low') {
//     echo 'Wykryto Lower Low';
// } else {
//     echo 'Nie wykryto trendu';
// }