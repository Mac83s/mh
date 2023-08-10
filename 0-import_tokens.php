<?php 


// Import tokens Ethereum

add_action('2minutescron','import_uniswap_tokens');
function import_uniswap_tokens(){
    echo "test1";
    $serverName = "hektordbserver.database.windows.net";

    $connectionInfo = array( "Database"=>"hektordatabase",
                            "UID"=>"maciek",
                            "PWD"=>"m@ciek3790123");

    $conn = sqlsrv_connect( $serverName, $connectionInfo);
    
    if( $conn === false )
    {
        die( print_r( sqlsrv_errors(), true));
    }

    $last_token_id = get_option('last_token_ethereum');
    // $array_data = sqlsrv_query($conn, "exec chain.pa_GetAccountsTokenContract_SwapScore");
    $array_data = sqlsrv_query($conn, "exec chain.wp_GetAccountsTokenContractEthereumV1 @Id = $last_token_id , @CreatorBalance = 0");

    if($array_data === false) {
        die( print_r( sqlsrv_errors(), true));
        echo 'Error';
    }
   
    $first_mssql_id = null;
    while( $row = sqlsrv_fetch_array($array_data, SQLSRV_FETCH_ASSOC) ) {
      
        sleep(1);
        $mssql_id = $row['Id'];
        if (!isset($first_mssql_id)) {
            $first_mssql_id = $mssql_id;
        }
        $name = $row['Name'];
        $symbol = $row['Symbol'];
        $address = $row['Address'];
        
        $MaxTotalSupply = $row['MaxTotalSupply'];
        $CreatorBalance = (float)$row['CreatorBalance'];
        $CreatorBalance = round($CreatorBalance, 2) ;
        $CreatorContract = $row['CreatorContract'];
        // $TransactionHash = $row['TransactionHash'];

        $CreatedAt = $row['CreatedAt'];
        $CreatedAt->modify('+2 hour');
        $CreatedAt = $CreatedAt->format("Y-m-d H:i:s");

        //Prepare loop 
        $title = $name . ' (' . $symbol . ')';
       
        $post_slug = 'ethereum-' . $address;
        // Check if post with the given slug exists
        $post = get_page_by_path($post_slug, OBJECT, 'tokens');
        if ($post == null && is_address_archived($address) === false) {

            // Post with the given slug doesn't exist yet
            $new_post = array(
                'post_title' => $title,
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => 1,
                'post_name'   =>  $post_slug,
                'post_type' => 'tokens'
            );
            // Insert post
            $post_id = wp_insert_post($new_post);

            // Insert post meta if available  
            update_post_meta( $post_id, 'id', $mssql_id );
            update_post_meta( $post_id, 'create_date', $CreatedAt );
            update_post_meta( $post_id, 'name', $name );
            update_post_meta( $post_id, 'symbol', $symbol );
            update_post_meta( $post_id, 'address', $address );
            update_post_meta( $post_id, 'total_supply', $MaxTotalSupply );
            update_post_meta( $post_id, 'deploy_balance', $CreatorBalance );
            update_post_meta( $post_id, 'creatorcontract', $CreatorContract );
        
            wp_set_object_terms( $post_id, 'db_import' , 'group' );
            wp_set_object_terms( $post_id, 'ethereum' , 'blockchain' );
        
            // Facet WP reload cache
            if ( function_exists( 'FWP' ) ) {
                FWP()->indexer->index( $post_id );
            }

            add_post_id_to_file($post_id, 'vfn');

        }
        wp_reset_query();

        update_option('last_token_ethereum', $first_mssql_id);

    }
    
}





add_action('2minutescron','import_arbitrum_tokens');
function import_arbitrum_tokens(){
   
    $serverName = "hektordbserver.database.windows.net";

    $connectionInfo = array( "Database"=>"hektordatabase",
                            "UID"=>"maciek",
                            "PWD"=>"m@ciek3790123");

    $conn = sqlsrv_connect( $serverName, $connectionInfo);
    
    if( $conn === false )
    {
        die( print_r( sqlsrv_errors(), true));
    }

    $last_token_id = get_option('last_token_arbitrum');
    
    $array_data = sqlsrv_query($conn, "exec chain.wp_GetAccountsTokenContractArbitrumV1 @Id = $last_token_id , @CreatorBalance = 0");
    
    if($array_data === false) {
        die( print_r( sqlsrv_errors(), true));
        echo 'Error';
    }
    
    $first_mssql_id = null;
    while( $row = sqlsrv_fetch_array($array_data, SQLSRV_FETCH_ASSOC) ) {
        sleep(1);
        $mssql_id = $row['Id'];
        if (!isset($first_mssql_id)) {
            $first_mssql_id = $mssql_id;
        }
        $name = $row['Name'];
        $symbol = $row['Symbol'];
        $address = $row['Address'];
        
        $MaxTotalSupply = $row['MaxTotalSupply'];
        $CreatorBalance = (float)$row['CreatorBalance'];
        $CreatorBalance = round($CreatorBalance, 2) ;
        $CreatorContract = $row['CreatorContract'];
        // $TransactionHash = $row['TransactionHash'];

        $CreatedAt = $row['CreatedAt'];
        $CreatedAt->modify('+2 hour');
        $CreatedAt = $CreatedAt->format("Y-m-d H:i:s");

        //Prepare loop 
        $title = $name . ' (' . $symbol . ')';

        $post_slug = 'arbitrum-' . $address;
        // Check if post with the given slug exists
        $post = get_page_by_path($post_slug, OBJECT, 'tokens');
        
        if ($post == null && is_address_archived($address) === false) {

            // Post with the given slug doesn't exist yet
            $new_post = array(
                'post_title' => $title,
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => 1,
                'post_name'   =>  $post_slug,
                'post_type' => 'tokens'
            );
            // Insert post
            $post_id = wp_insert_post($new_post);

            

            // Insert post meta if available  
            update_post_meta( $post_id, 'id', $mssql_id );
            update_post_meta( $post_id, 'create_date', $CreatedAt );
            update_post_meta( $post_id, 'name', $name );
            update_post_meta( $post_id, 'symbol', $symbol );
            update_post_meta( $post_id, 'address', $address );
            update_post_meta( $post_id, 'total_supply', $MaxTotalSupply );
            update_post_meta( $post_id, 'deploy_balance', $CreatorBalance );
            update_post_meta( $post_id, 'creatorcontract', $CreatorContract );
        
            wp_set_object_terms( $post_id, 'db_import' , 'group' );
            wp_set_object_terms( $post_id, 'arbitrum' , 'blockchain' );
        
            // Facet WP reload cache
            if ( function_exists( 'FWP' ) ) {
                FWP()->indexer->index( $post_id );
            }
            add_post_id_to_file($post_id, 'vfn');


        }
        wp_reset_query();

        update_option('last_token_arbitrum', $first_mssql_id);

    }   
    

}