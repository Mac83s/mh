<?php

function custom_taxonomy_add_new_meta_field($term) {
    ?>
    <style>
    .form-field.term-group {
        display: flex;
        margin-bottom: 10px;
    }
    .form-field.term-group label{
        min-width:200px
    }
    </style>
    <div class="form-field term-group">
        <label for="blockchain_id">Blockchain ID</label>
        <input type="text" id="blockchain_id" name="blockchain_id" value="<?php echo get_term_meta($term->term_id, 'blockchain_id', true); ?>">
    </div>
    <div class="form-field term-group">
        <label for="short">Short</label>
        <input type="text" id="short" name="short" value="<?php echo get_term_meta($term->term_id, 'short', true); ?>">
    </div>
    <div class="form-field term-group">
        <label for="explorer">Explorer</label>
        <input type="text" id="explorer" name="explorer" value="<?php echo get_term_meta($term->term_id, 'explorer', true); ?>">
    </div>
    <div class="form-field term-group">
        <label for="explorer_api">Explorer API</label>
        <input type="text" id="explorer_api" name="explorer_api" value="<?php echo get_term_meta($term->term_id, 'explorer_api', true); ?>">
    </div>
    <div class="form-field term-group">
        <label for="dex_tools_url">Dex Tools blockchain URL</label>
        <input type="text" id="dex_tools_url" name="dex_tools_url" value="<?php echo get_term_meta($term->term_id, 'dex_tools_url', true); ?>">
    </div>
    <?php
}
// add_action('blockchain_add_form_fields', 'custom_taxonomy_add_new_meta_field', 10, 2);
add_action('blockchain_edit_form_fields', 'custom_taxonomy_add_new_meta_field', 10, 2);

// This will save what you input into the custom fields when you add a new term or edit an existing one
function save_taxonomy_custom_meta($term_id) {
    if (isset($_POST['blockchain_id'])) {
        update_term_meta($term_id, 'blockchain_id', sanitize_text_field($_POST['blockchain_id']));
    }
    if (isset($_POST['short'])) {
        update_term_meta($term_id, 'short', sanitize_text_field($_POST['short']));
    }
    if (isset($_POST['explorer'])) {
        update_term_meta($term_id, 'explorer', sanitize_text_field($_POST['explorer']));
    }
    if (isset($_POST['explorer_api'])) {
        update_term_meta($term_id, 'explorer_api', sanitize_text_field($_POST['explorer_api']));
    }
    if (isset($_POST['dex_tools_url'])) {
        update_term_meta($term_id, 'dex_tools_url', sanitize_text_field($_POST['dex_tools_url']));
    }
}
add_action('edited_blockchain', 'save_taxonomy_custom_meta', 10, 2);
// add_action('create_blockchain', 'save_taxonomy_custom_meta', 10, 2);



// $terms_token_blockchain = get_the_terms( $post_id ,  'blockchain' );

// foreach ( $terms_token_blockchain as $term ) {					
//     $term_id = $term->term_id;
//     $blockchain = $term->name;
//     $blockchain_slug = $term->slug;
// }
// if ($term_id) {
//     $blockchain_id = get_blockchain_cf( $term_id, 'blockchain_id' );
// }

function get_blockchain_cf($term_id, $field) {

    $value = get_term_meta($term_id, $field, true);

    return $value;
}



// if ($term->name == 'Ethereum'){			
//     $chain_id = '1';
// }
// if ($term->name == 'Arbitrum'){
//     $chain_id = '42161';
// }
// if ($term->name == 'BSC'){
//     $chain_id = '56';
// }
// if ($term->name == 'OKC'){
//     $chain_id = '66';
// }
// if ($term->name == 'Gnosis'){
//     $chain_id = '100';
// }
// if ($term->name == 'HECO'){
//     $chain_id = '128';
// }
// if ($term->name == 'Polygon'){
//     $chain_id = '137';
// }
// if ($term->name == 'Fantom'){
//     $chain_id = '250';
// }
// if ($term->name == 'KCC'){
//     $chain_id = '321';
// }
// if ($term->name == 'zkSync Era'){
//     $chain_id = '324';
// }
// if ($term->name == 'ETHW'){
//     $chain_id = '10001';
// }
// if ($term->name == 'FON'){
//     $chain_id = '201022';
// }
// if ($term->name == 'Avalanche'){
//     $chain_id = '43114';
// }
// if ($term->name == 'Linea'){
//     $chain_id = '59140';
// }
// if ($term->name == 'Harmony'){
//     $chain_id = '1666600000';
// }
// if ($term->name == 'Tron'){
//     $chain_id = 'tron';
// }
// if ($term->name == 'Cronos'){
//     $chain_id = '25';
// }
// if ($term->name == 'Optimism'){
//     $chain_id = '10';
// }
/*
<option value="optimism">Optimism</option>
<option value="cronos">Cronos</option>
<option value="okc">OKC</option>
<option value="gnosis">Gnosis</option>
<option value="heco">HECO</option>
<option value="polygon">Polygon</option>
<option value="fantom">Fantom</option>
<option value="kcc">KCC</option>
<option value="zksync">zkSync Era</option>
<option value="ethw">ETHW</option>
<option value="fon">FON</option>
<option value="avalanche">Avalanche</option>
<option value="linea">Linea</option>
<option value="cosmos">Cosmos</option>
<option value="curve">Curve</option>
<option value="sui">Sui</option>
<option value="osmo">Osmo</option>
<option value="layer-0">Layer 0</option>
<option value="harmony">Harmony</option>
<option value="tron">Tron</option>

*/