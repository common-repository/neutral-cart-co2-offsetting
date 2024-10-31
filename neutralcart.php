<?php
/*
Plugin Name: Neutral Cart - CO2 Offsetting
Plugin URI: neutralcart.com
Description: One-Click CO2 Offsetting/Donations for WooCommerce
Version: 1.1.6
Author: Neutral Cart
Author URI: https://neutralcart.com/
* WC requires at least: 3.5.0
* WC tested up to: 5.2.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	wp_die('Please Go Back');
	exit;
}
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
add_action( 'admin_init', 'active_check' );
add_action( 'init', 'neutralcart_load_plugin_textdomain');


// settings link in plugin list
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'neutralcart_add_plugin_page_settings_link');
function neutralcart_add_plugin_page_settings_link( $links ) {
  $links[] = '<a href="' .
    admin_url( 'options-general.php?page=neutralcart-admin-menu' ) .
    '">' . __('Settings', 'neutralcart') . '</a>';
  return $links;
}


function neutralcart_load_plugin_textdomain()
{
  load_plugin_textdomain( 'neutralcart', FALSE, basename( dirname( __FILE__ ) ) . '/lang/' );
}

function active_check() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'active_failed_notice' );
        deactivate_plugins( plugin_basename( __FILE__ ) ); 
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

define( 'NEUTRALCART', 'Neutral Cart'); //used for the fee name in checkout, and potentially some other stuff
define( 'NEUTRALCART_BASE', plugin_basename( __FILE__ ) );
define( 'NEUTRALCART_DIR', plugin_dir_path( __FILE__ ) );
define( 'NEUTRALCART_URL', plugin_dir_url( __FILE__ ) );
define( 'NEUTRALCART_AST', plugin_dir_url( __FILE__ ).'assets/' );
define( 'NEUTRALCART_JS', plugin_dir_url( __FILE__ ).'assets/js' );
define( 'NEUTRALCART_IMG', plugin_dir_url( __FILE__ ).'assets/img/' );
define( 'NEUTRALCART_API_URL', 'http://api.neutralcart.com/1.0.0');
define( 'NEUTRALCART_AUTHOR_EMAIL' , 'info@neutralcart.com');
define( 'NEUTRALCART_AUTHOR_WEB' , 'https://neutralcart.com');
define( 'NEUTRALCART_BADGE_LINK' , 'https://neutralcart.com/en/frequently-asked-questions');



function active_failed_notice(){
    ?><div class="error"><p>Please Activate <b>WooCommerce</b> Plugin.</p></div><?php
}


function neutralcart_error_reporting($error_message) {
  $name = "Error Message";
  $email = NEUTRALCART_AUTHOR_EMAIL; // $_SERVER['SERVER_NAME'];
  $message = "response from server: " . $error_message; 

  //php mailer variables
  $to = NEUTRALCART_AUTHOR_EMAIL; // get_option('admin_email');
  $subject = NEUTRALCART . " error message";
  $headers = 'From: '. $email . "\r\n" .
    'Reply-To: ' . $email . "\r\n";

  //Here put your Validation and send mail
  $sent = wp_mail($to, $subject, strip_tags($message), $headers);
        if($sent) {
          // write_log('email sent: ' . $sent);
        }//message sent!
        else  {
          // write_log('email not sent: ' . $sent);

        }//message wasn't sent
}



if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}


/*** session management ***/
require 'wp-session-manager/wp-session-manager.php';
wp_session_manager_initialize();


require 'classes/neutralcart-main.php';
require 'classes/neutralcart-admin.php';
require 'classes/neutralcart-admin-settings.php';
require 'classes/neutralcart-frontend.php';


?>