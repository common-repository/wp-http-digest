=== wp-http-digest ===

Contributors: monperrus
Requires at least: ?
Tested up to: trunk
Tags: authentication, digest, wp_user
Description: wp-http-digest is a Wordpress plugin that enables you to store the Wordpress passwords in a format that is compatible with HTTP digest authentication
Stable tag: trunk

== Description ==

wp-http-digest is a Wordpress plugin that enables you to store the Wordpress passwords in a format that is compatible with HTTP digest authentication. The advantages of this approach is that it enables you to build and integrate services using HTTP digest authentication on top of standard wordpress accounts. The passwords are stored in the database as md5(username:realm:password), which is called the HA1 hash. 



== Installation ==

This section describes how to install the plugin and get it working:

1. Upload `wp-http-digest.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. *Important* You are still logged-in with you administrator account, then go into your profile (top, right-hand side) and update your password

*WARNING*: once you've installed the plugin, the previous passwords become invalid. If something goes wrong, you may have to:

1. recreate a new password for an existing administrator account, for instance using htdigest:
    * `$ htdigest -c digest-file wordpress username`
    * `$  awk -F : '{print "HA1 for user "$1" is "$3}' digest-file`
2. copy the HA1 in the database (table:wp_user, colum:user_pass), for instance with phpMyAdmin


== Frequently Asked Questions ==

#### I can not log in anymore 
See the warning in section Installation 

#### Is this method secure? 
According to Wikipedia <http://en.wikipedia.org/wiki/Digest_access_authentication>, although md5 is getting weak, there are no known attacks on HTTP Digest hash.


== Changelog ==

= 20110623 =
First upload to <http://wordpress.org/extend/plugins/>

== Implementation ==

The implementation could be more concise and beautiful if `./wp-includes/user.php` would rely on `wp_set_password` instead of `wp_hash_password` (no `action_profile_update` and `wp_new_user_notification` required). See discussion and patch at <http://core.trac.wordpress.org/ticket/17830>. 
