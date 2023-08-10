<?php 

add_filter( 'cron_schedules', 'one_minute_interval' );
function one_minute_interval( $schedules ) {
    $schedules['everyminute'] = array(
            'interval'  => 60, // time in seconds
            'display'   => '60 sec'
    );
    return $schedules;
}
function one_minute_interval_schedule(){   
  if(!wp_next_scheduled('minutescron')) 
   wp_schedule_event (time(), 'everyminute', 'minutescron');
}
add_action('wp','one_minute_interval_schedule');



add_filter( 'cron_schedules', 'two_minute_interval' );
function two_minute_interval( $schedules ) {
    $schedules['every2minute'] = array(
            'interval'  => 120, // time in seconds
            'display'   => 'Every 2 Minutes'
    );
    return $schedules;
}
function two_minute_interval_schedule(){   
  if(!wp_next_scheduled('2minutescron')) 
   wp_schedule_event (time(), 'every2minute', '2minutescron');
}
add_action('wp','two_minute_interval_schedule');



add_filter( 'cron_schedules', 'five_minute_interval' );
function five_minute_interval( $schedules ) {
  $schedules['every5minutes'] = array(
    'interval' => 5*60,
    'display' => __( 'Every 5 Minutes', 'dexscreener' )
  );
  return $schedules;
}
function five_minute_interval_schedule() {
  if ( ! wp_next_scheduled( '5minutescron' ) ) {
    wp_schedule_event( time(), 'every5minutes', '5minutescron' );
  }
}
add_action( 'wp', 'five_minute_interval_schedule' );



add_filter( 'cron_schedules', 'fiveteen_minute_interval' );
function fiveteen_minute_interval( $schedules ) {
  $schedules['15_minutes'] = array(
    'interval' => 15*60,
    'display' => __( 'Every 15 Minutes', 'dexscreener' )
  );
  return $schedules;
}
function fiveteen_minute_interval_schedule() {
  if ( ! wp_next_scheduled( '15minutescron' ) ) {
    wp_schedule_event( time(), '15_minutes', '15minutescron' );
  }
}
add_action( 'wp', 'fiveteen_minute_interval_schedule' );


