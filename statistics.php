<?php 
add_shortcode('tokens_by_date', 'countTokensByDate');
function countTokensByDate() {
    $query_args = array(
        'post_type' => 'tokens', // typ posta
        'posts_per_page' => -1, // liczba postów do pobrania
        'tax_query' => array(
			array(
				'taxonomy' => 'token_status', 
				'field' => 'slug',
				'terms' => array('verified'), 
			),
			array(
				'taxonomy' => 'group', 
				'field' => 'slug',
				'terms' => array('db_import'), 
			),
		),
        'date_query' => array(
            array(
                'after' => 'February 15th, 2023',
                'inclusive' => true,
            ),
        ),
    );
    $posts = get_posts($query_args);
    $count_by_date = array();
    $count_by_date_locked_lp = array();
    $count_by_date_renounceOwnership = array();

    // pętla po postach
    foreach ($posts as $post) {
        // pobierz datę publikacji posta
        $post_date = strtotime($post->post_date);
        // wyodrębnij datę (bez godziny i minut)
        $date = date('Y-m-d', $post_date);

        // zlicz ilość wystąpień daty dla postów tokens
        if (isset($count_by_date[$date])) {
            $count_by_date[$date]++;
        } else {
            $count_by_date[$date] = 1;
        }

        $locked_lp_token = get_post_meta($post->ID, 'locked_lp_token', true);
        if ($locked_lp_token) {
            if (isset($count_by_date_locked_lp[$date])) {
                $count_by_date_locked_lp[$date]++;
            } else {
                $count_by_date_locked_lp[$date] = 1;
            }
        }

        $renounceOwnership = get_post_meta($post->ID, 'renounceOwnership', true);
        if ($renounceOwnership) {
            if (isset($count_by_date_renounceOwnership[$date])) {
                $count_by_date_renounceOwnership[$date]++;
            } else {
                $count_by_date_renounceOwnership[$date] = 1;
            }
        }

      
    }

    echo '<div class="stats_wrap">';
    echo '<div class="tab_left">';
    echo '<h3>Tokeny prawdopodobnie LEGIT</h3>';   
    echo '<table>';
    echo '<tr><th>Data</th><th>Liczba postów</th><th>Locked LP</th><th>Renounced</th></tr>';

    foreach ($count_by_date as $date => $count) {
        $count_locked_lp = isset($count_by_date_locked_lp[$date]) ? $count_by_date_locked_lp[$date] : 0;
        $count_renounceOwnership = isset($count_by_date_renounceOwnership[$date]) ? $count_by_date_renounceOwnership[$date] : 0;

        echo '<tr>';
        echo "<td><a href='/?_data=$date%2C'>$date</a></td>";
        echo "<td>$count</td>";
        echo "<td>$count_locked_lp</td>";
        echo "<td>$count_renounceOwnership</td>";
        echo '</tr>';
    }

    echo '</table>';
    echo '<p>Jest to lista aktywnych i zweryfikowanych tokenów</p>';
    echo '<p>Na aktualny dzień nie ma co patrzeć bo do końca dnia większość projektów wypadnie z obiegu.</p>';
    echo '</div>';


    $query_args = array(
        'post_type' => 'tokens', // typ posta
        'posts_per_page' => -1, // liczba postów do pobrania  
        'date_query' => array(
            array(
                'after' => 'February 15th, 2023',
                'inclusive' => true,
            ),
        ),     
    );
    $posts = get_posts($query_args);
    $count_by_date = array();
    $count_by_date_locked_lp = array();
    $count_by_date_renounceOwnership = array();

    // pętla po postach
    foreach ($posts as $post) {
        // pobierz datę publikacji posta
        $post_date = strtotime($post->post_date);
        // wyodrębnij datę (bez godziny i minut)
        $date = date('Y-m-d', $post_date);

        // zlicz ilość wystąpień daty dla postów tokens
        if (isset($count_by_date[$date])) {
            $count_by_date[$date]++;
        } else {
            $count_by_date[$date] = 1;
        }

        $locked_lp_token = get_post_meta($post->ID, 'locked_lp_token', true);
        if ($locked_lp_token) {
            if (isset($count_by_date_locked_lp[$date])) {
                $count_by_date_locked_lp[$date]++;
            } else {
                $count_by_date_locked_lp[$date] = 1;
            }
        }

        $renounceOwnership = get_post_meta($post->ID, 'renounceOwnership', true);
        if ($renounceOwnership) {
            if (isset($count_by_date_renounceOwnership[$date])) {
                $count_by_date_renounceOwnership[$date]++;
            } else {
                $count_by_date_renounceOwnership[$date] = 1;
            }
        }

      
    }
    echo '<div class="tab_right">';
    echo '<h3>Wszystkie Tokeny zaimportowane z blockchain</h3>';   
    echo '<table>';
    echo '<tr><th>Data</th><th>Liczba postów</th><th>Locked LP</th><th>Renounced</th></tr>';

    foreach ($count_by_date as $date => $count) {
        $count_locked_lp = isset($count_by_date_locked_lp[$date]) ? $count_by_date_locked_lp[$date] : 0;
        $count_renounceOwnership = isset($count_by_date_renounceOwnership[$date]) ? $count_by_date_renounceOwnership[$date] : 0;

        echo '<tr>';
        echo "<td><a href='/?_data=$date%2C'>$date</a></td>";
        echo "<td>$count</td>";
        echo "<td>$count_locked_lp</td>";
        echo "<td>$count_renounceOwnership</td>";
        echo '</tr>';
    }

    echo '</table>';
    echo '<p>Deployer balance 1.9 ETH od 2023-03-01 0.5 ETH</p>';
    echo '</div>';
    echo '</div>';


}






