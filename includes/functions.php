<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


function wcra_dot($status='success'){
      if($status == 'error'){
            return '<i class="fa fa-circle" style="color:red;" aria-hidden="true"></i>';
      }
       return '<i class="fa fa-circle" style="color:green;" aria-hidden="true"></i>';
      
}

function wcra_create_database_tables(){
      global $wpdb;
      $prefix = $wpdb->prefix.WCRA_DB;
      $query = array();
      $query['cus_api_base'] = "CREATE TABLE ".$prefix."api_base (
                                `id` bigint(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                                `Fullname` text NOT NULL,
                                `Email` text NOT NULL,
                                `ApiSecret` text NOT NULL,
                                `Status` int(2) NOT NULL,
                                `CreatedAt` text NOT NULL
                              ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
      $query['cus_api_endpoints'] = "CREATE TABLE ".$prefix."api_endpoints (
                                `id` bigint(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                                `base` text NOT NULL,
                                `basedata` text NOT NULL,
                                `param` text  NULL,
                                `secret` text NOT NULL
                              ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
      $query['cus_api_log'] = "CREATE TABLE ".$prefix."api_log (
                                `id` bigint(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                                `secret` text NULL,
                                `requested_url` text NOT NULL,
                                `response_delivered` text NOT NULL,
                                `connectedAt` text NOT NULL,
                                `System_info` text NOT NULL,
                                `Ip` text NOT NULL
                              ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
      $query['notification_log'] = "CREATE TABLE ".$prefix."notification_log (
                                `id` bigint(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                                `notification` text NULL,
                                `date_time` datetime NOT NULL
                              ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

      foreach ($query as $q) {
            $wpdb->query($q); 
      }


}

function wcra_is_user($user){

    global $wpdb;

    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user));

    if($count == 1){ return true; }else{ return false; }

}


//add_action('init' , 'wcra_invalid_request');
function wcra_invalid_request(){
  $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $link = home_url().'/wp-json';

  if($actual_link == $link || $actual_link == $link.'/'){
    $error =  error('Invalid Namespace/base/secret key' ,203);
    $create_log = wcra_create_api_log($secret_key,$error,'error');
   // die;
    return $error;
    die;
  }
}

function wcra_get_basedata($base){
  global $wpdb;
  $tab = $wpdb->prefix.WCRA_DB.'api_endpoints';
  $q = "SELECT * FROM $tab WHERE base = '$base' ";
  $get = $wpdb->get_row($q);
  if($get){
    return  $get;
  }else{
    return array();
  }
}

function wcra_get_base_by_id($id){
  global $wpdb;
  $tab = $wpdb->prefix.WCRA_DB.'api_endpoints';
  $q = "SELECT * FROM $tab WHERE id =".$id;
  $get = $wpdb->get_row($q);
  if(count($get)){
    return  $get->base;
  }else{
    return array();
  }
}

function wcra_endpoints_data($format=''){
  global $wpdb;
  $tab = $wpdb->prefix.WCRA_DB.'api_endpoints';
  $q = "SELECT * FROM $tab ORDER BY id DESC";
  $get_endspts = $wpdb->get_results($q);
  if($format == 'array'){
    foreach ($get_endspts as $key => $value) {
      $callback = unserialize($value->basedata);
      $data[$value->base] = array('callback' => $callback);
    }
    return $data;
  }else{
    return $get_endspts;
  }
}

function wcra_add_settings_link( $links ) {
    $settings_link = '<a href="'.admin_url( "admin.php?page=wcra_api_endpoints" ).'">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
    return $links;
}

function wcra_activation_redirect( $plugin ) {
        exit( wp_redirect( admin_url( 'admin.php?page=wcra_api_endpoints' ) ) );    
}
//disable cron


// CUSTOM TIME INTERVAL

function wcra_cron_intervel( $schedules ) {
  $wpr_set_cron_int_log = wcra_get_settings('wpr_set_cron_int_log');
  $schedules['wcra_intervel'] = array(
    'interval' => $wpr_set_cron_int_log,
    'display' => __('Every 30sec')
  );
  return $schedules;
}

$_is_cron_enabled = wcra_is_cron_enabled();




if($_is_cron_enabled){
  if (! wp_next_scheduled ( 'wcra_cron_delete_log' )) {
        wp_schedule_event(time()  , 'wcra_intervel', 'wcra_cron_delete_log');
  }
  add_filter('cron_schedules', 'wcra_cron_intervel');
  add_action('wcra_cron_delete_log', 'wcra_cron_delete_log_handler');
}


function wcra_cron_activation() {
  $_is_cron_enabled = wcra_is_cron_enabled();
  if($_is_cron_enabled){
    if (! wp_next_scheduled ( 'wcra_cron_delete_log' )) {
        wp_schedule_event(time()  , 'wcra_intervel', 'wcra_cron_delete_log');
    }
  }
      
}
 
function wcra_intervel_array($selected=''){
  $intervel = array();
  $intervel['0'] = 'Select Intervel';
  $intervel['3600'] = 'Hourly';
  $intervel['1800'] = 'Half Hourly';
  $intervel['86400'] = 'Once Daily';
  $intervel['43200'] = 'Twice Daily';
  $intervel['172800'] = 'Once Per Two Day';
  $intervel['604800'] = 'Weekly';
  //$intervel['10'] = '10 Sec';
  if($selected){
    return $intervel[$selected];
  }
  return $intervel;
}

function wcra_intervel__html($selected = ''){
  $html = '';
  $_intervel_array = wcra_intervel_array();
  foreach ($_intervel_array as $int => $disp) {
    if($selected == $int){
      $html .= '<option value="'.esc_attr($int).'" selected >'.esc_attr($disp).'</option>';
    }else{
      $html .= '<option value="'.esc_attr($int).'" >'.esc_attr($disp).'</option>';
    }   
  }
  return $html;
}

function wcra_is_cron_enabled(){
  $wpr_set_backlog = @wcra_get_settings('wpr_set_backlog');
  $wpr_set_cron_int_log =@ wcra_get_settings('wpr_set_cron_int_log');
  if( $wpr_set_backlog == 1 && $wpr_set_cron_int_log != 0 ){
    return true;
  }
  return false;
}

//add_action('init' , '_disable_cron');
function wcra_cron_deactivation(){
  $_is_cron_enabled = wcra_is_cron_enabled();
  if(!$_is_cron_enabled){
    wp_clear_scheduled_hook('wcra_cron_delete_log');
  }
  
}

function wcra_cron_delete_log_handler() {
    $wpr_set_cron_log_dur = wcra_get_settings('wpr_set_cron_log_dur');
    wcra_delete_log($wpr_set_cron_log_dur);
}

function wcra_log_delete_duration(){
  $duration = array();
  for ($i=0; $i < 30 ; $i++) { 
    if($i == 0){
        $day = 'Today' ;
    }else if($i == 1){
        $day = 'Yesterday' ;
    }else{
      $day = date('j M, Y', strtotime("-$i days"));
    }

   // $duration[$i] = $i.' '.$day.' Before';
    $duration[$i] = 'Upto '.$day;
  }

  return $duration;
}

function wcra_log_delete_duration_html($selected= ''){
  $html = '';
  $_log_delete_duration = wcra_log_delete_duration();
  foreach ($_log_delete_duration as $dur => $disp) {
    if($selected == $dur){
      $html .= '<option value="'.esc_attr($dur).'" selected >'.esc_attr($disp).'</option>';
    }else{
      $html .= '<option value="'.esc_attr($dur).'" >'.esc_attr($disp).'</option>';
    }   
  }
  return $html;
}

function wcra_save_recent_activity($data){
  global $wpdb;
  $notification = '';
  if(isset($data['txt'])){
    $notification = $data['txt'];
  }
  $tab = $wpdb->prefix.WCRA_DB.'notification_log';
  $tobeinsert = array('notification' => $notification, 'date_time' => date('Y-m-d H:i:s'));
  $insert = $wpdb->insert($tab , $tobeinsert );
  if($insert){
    wcra_delete_recent_activity();
    return true;
  }else{
    return false;
  }
}


function wcra_get_recent_activity(){
  global $wpdb;
  $get = array();
  $tab = $wpdb->prefix.WCRA_DB.'notification_log';
  $q = "SELECT * FROM $tab ORDER BY id DESC";
  $get = $wpdb->get_results($q);
  return $get;
}

function wcra_delete_recent_activity($days=0){
  global $wpdb;
  $_get_settings = wcra_get_settings();
  $days = $days > 0 ? $days : $_get_settings['wpr_set_recent_activity_dur'];
  $set_border = date('Y-m-d', strtotime("-$days days"));
  $notification_log = $wpdb->prefix.WCRA_DB.'notification_log';
  $q = "SELECT count(*) from $notification_log where STR_TO_DATE(date_time, '%Y-%m-%d %H:%i:%s') <= '$set_border 11:59:59' ";
  $get = $wpdb->get_var($q);
  if($get > 0 ){
    $notification = "<strong>$get</strong> Recent Activity Log has been deleted by system";
    $tobeinsert = array('notification' => $notification, 'date_time' => date('Y-m-d H:i:s'));
    $insert = $wpdb->insert($notification_log , $tobeinsert );
  }
  
  //return $get;
  $q1 = "DELETE from $notification_log where  STR_TO_DATE(date_time, '%Y-%m-%d %H:%i:%s') <= '$set_border 11:59:59' ";
  $delete = $wpdb->query($q1);
  return $get;
}

function wcra_recent_activity_options($selected=''){
  $duration = array();
  for ($i=1; $i < 7 ; $i++) { 
    $day = $i > 1 ? 'Days' : 'Day';
    $duration[$i] = "Last $i $day";
  }

  return $duration;
}

function wcra_recent_activity_options_html($selected=''){
  $html = '';
  $_recent_activity_options = wcra_recent_activity_options();
  foreach ($_recent_activity_options as $key => $value) {
    if($selected == $key){
      $html .= '<option value="'.$key.'" selected >'.$value.'</option>';
    }else{
      $html .= '<option value="'.$key.'">'.$value.'</option>';
    }
    
  }
  return $html;
}

function wcra_get_logged_user(){
  $id = get_current_user_id();
  $data = get_user_by('ID' , $id);
  return $data->display_name;
}


function wcra_save_default_endpoint(){
      global $wpdb;
      $tab = $wpdb->prefix.WCRA_DB.'api_endpoints';
      $wpr_set_base = 'wcra_test';
      $q = "SELECT * FROM $tab WHERE base = '$wpr_set_base'";
      $get = $wpdb->get_row($q);
      if(empty($get)){
        $wpr_get_params = array('param1','param2');
        $callback = WCRA_DB.$wpr_set_base.'_callback';
        $permission_callback = WCRA_DB.$wpr_set_base.'_permission_callback';
        $basedata = array('callback' => $callback ) ;
        //print_r($wpr_get_params);die;
        $_get_root_secret = wcra_get_root_secret();
        $data = array('base' => $wpr_set_base ,'basedata' =>serialize($basedata),'param' => '' , 'secret' => $_get_root_secret );
        $inseret  = $wpdb->insert( $tab , $data );
        $notification = "<strong>1</strong> Deafult base has been created - <strong>$wpr_set_base</strong>";
        wcra_save_recent_activity(array('txt' => $notification ));
      }
                
}

function wcra_admin_page_tabs( $current = 'wcra_new_api' ) {
    $tabs = array(
        'wcra_api_endpoints'  => __( '<i class="fa fa-list-alt" aria-hidden="true"></i>&nbsp;Endpoint URLs', 'plugin-textdomain' ),
        'wcra_new_api'   => __( '<i class="fa fa-plus" aria-hidden="true"></i>&nbsp;New Api Secret', 'plugin-textdomain' ), 
        'wcra_api_list'  => __( '<i class="fa fa-user-secret" aria-hidden="true"></i>&nbsp;Secret List', 'plugin-textdomain' ),
        'wcra_api_settings'  => __( '<i class="fa fa-wrench" aria-hidden="true"></i>&nbsp;Settings', 'plugin-textdomain' ),       
        'wcra_api_log'  => __( '<i class="fa fa-history" aria-hidden="true"></i>&nbsp;Log', 'plugin-textdomain' ),
        'wcra_api_recent_activity'  => __( '<i class="fa fa-bell" aria-hidden="true"></i>&nbsp;Recent Activity', 'plugin-textdomain' ),
        'wcra_api_walk_help'  => __( '<i class="fa fa-question-circle" aria-hidden="true"></i>&nbsp;Walk Through', 'plugin-textdomain' ),
    );
    $html = '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? 'nav-tab-active' : '';
        $html .= '<a class="nav-tab navmar ' . esc_attr($class) . '" href="?page=' . $tab . '">' . $name . '</a>';
    }
    $html .= '</h2>';
    echo $html;
}

function wcra_clear_recent_activity(){
   global $wpdb;
  $get = array();
  $tab = $wpdb->prefix.WCRA_DB.'notification_log';
  $q = "DELETE FROM $tab ";
  //echo $q;die;
  $get = $wpdb->query($q);
}

add_action('init' , 'wcra_redirect_old_to_new');
function wcra_redirect_old_to_new(){
  $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $make_link = admin_url('options-general.php?page=wcra_settings&tab=wcra_api_settings');
  //echo $make_link;die;
  if( $actual_link == $make_link ){
    exit(wp_redirect(admin_url('admin.php?page=wcra_api_endpoints')));
  }
}