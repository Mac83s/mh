<?php

function save_custom_fields_to_json($post_id) {
    $filename = get_stylesheet_directory() . "/files/export/all_data.json";

    // Pobierz wszystkie pola niestandardowe posta
    $custom_fields = get_post_meta($post_id);

    // Pobierz zawartość pliku JSON
    $json = file_exists($filename) ? file_get_contents($filename) : '{}';
    
    // Zdekoduj zawartość pliku JSON do tablicy PHP
    $data = json_decode($json, true);

    // Sprawdź, czy pole 'address' istnieje
    if(isset($custom_fields['address'])) {
        // Użyj pola 'address' jako klucza i zapisz wszystkie pola niestandardowe
        $data[$custom_fields['address'][0]] = $custom_fields;
    }

    // Zaktualizuj plik JSON
    file_put_contents($filename, json_encode($data));
}
