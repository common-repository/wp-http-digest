<?php

/*
Plugin Name:  wp-http-digest
Plugin URI: http://www.monperrus.net/martin/wp-http-digest
Description: wp-http-digest is a Wordpress plugin that enables you to store the Wordpress passwords in a format that is compatible with HTTP digest authentication
Author: Martin Monperrus
Author URI: http://www.monperrus.net/martin/
Version: 0.1
*/

define('WP_HTTP_DIGEST_REALM','wordpress');
// uncomment if you want to maintain a digest file that mirrors your database passwords
// the file has the same format has those produced by htdigest
//define('WP_HTTP_DIGEST_FILE',dirname(__FILE__).'/passwd.htdigest');


// a special hook on profile update
function action_profile_update($user_id) {
  if ( isset( $_POST['pass1'] ) && $_POST['pass1'] != '' && $_POST['pass1'] == $_POST['pass2']) {
    $newpass_plain = $_POST['pass1'];
    wp_set_password( $newpass_plain, $user_id );
  }
}
add_action('profile_update', 'action_profile_update');

// this is the only place where we can access the generated plain text password and the user_id at the same time
if ( !function_exists('wp_new_user_notification') ) :
function wp_new_user_notification($user_id, $plaintext_pass = '') {
  $user = new WP_User($user_id);

  $user_login = stripslashes($user->user_login);
  $user_email = stripslashes($user->user_email);
  
  // The blogname option is escaped with esc_html on the way into the database in sanitize_option
  // we want to reverse this for the plain text arena of emails.
  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

  if ( empty($plaintext_pass) )
    return;
    
  wp_set_password($plaintext_pass, $user_id);
    
  $message = "Welcome to $blogname!\r\n\r\n";
  $message .= sprintf(__('Username: %s'), $user_login) . "\r\n";
  $message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
  $message .= wp_login_url() . "\r\n";

  wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message);

}
endif;



if ( !function_exists('wp_set_password') ) :
/** Updates the user's password with a new encrypted one. 
 * Overridden to support htdigest password
 */
function wp_set_password( $password, $user_id ) {

  // getting the login
  $user = new WP_User($user_id);
  $user_login = stripslashes($user->user_login);
  
  // the hash is in digest format
  $hash = md5($user_login.':'.WP_HTTP_DIGEST_REALM.":".$password);

  global $wpdb;
  $wpdb->update($wpdb->users, array('user_pass' => $hash, 'user_activation_key' => ''), array('ID' => $user_id) );

  wp_cache_delete($user_id, 'users');
  
  // we may update a .htdigest file
  if (defined(WP_HTTP_DIGEST_FILE) && is_writable(WP_HTTP_DIGEST_FILE)) {
    // loading the file if any
    if (is_file(WP_HTTP_DIGEST_FILE)) {
      $htdigest_content = @file_get_contents(WP_HTTP_DIGEST_FILE);
    }
    else { $htdigest_content = ''; }
    
    // do we update an existing login? 
    if (preg_match('/^'.$user_login.':'.WP_HTTP_DIGEST_REALM.':/m', $htdigest_content)) {
      // replacing the content
      $htdigest_content = preg_replace('/^'.$user_login.':'.WP_HTTP_DIGEST_REALM.':.*$/m', $user_login.':'.WP_HTTP_DIGEST_REALM.':'.$hash, $htdigest_content);
    } 
    else { // we add a new one
      $htdigest_content.= $user_login.':'.WP_HTTP_DIGEST_REALM.':'.$hash."\n";
    }
    
    // putting the new content in the file
    file_put_contents(WP_HTTP_DIGEST_FILE, $htdigest_content);
  }
}
endif;

if ( !function_exists('wp_check_password') ) :
// checks using the digest A1
function wp_check_password($password, $dbhash, $user_id = '') {
  // getting the login
  $user = new WP_User($user_id);
  $user_login = stripslashes($user->user_login);
  // digest format
  $hash = md5($user_login.':'.WP_HTTP_DIGEST_REALM.":".$password);
  return ($dbhash == $hash);
}
endif;


if ( !function_exists('wp_hash_password') ) :
// we want to prevent entering in an inconsistent state
function wp_hash_password($password) {
  die("The activated plugin wp-http-digest disables this function. The HTTP digest hash format requires the login which is not available in this function. See also http://core.trac.wordpress.org/ticket/17830.");
}
endif;



?>