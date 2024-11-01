<?php
/*
Plugin Name: The App Maker
Plugin URI: http://wordpress.org/extend/plugins/the-app-maker
Description: Creates a cross-device mobile app out of any post type using Sencha Touch as the framework
Version: 1.1.0
Author: Top Quark
Author URI: http://topquark.com

Copyright (C) 2011 Trevor Mills (support@topquark.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

Note: This plugin is distributed with a debug and production version of
Sencha Touch, which is also released under GPLv3.  
See http://www.sencha.com/products/touch/license/

*/

/**
 * The App Maker
 *
 * This plugin is an effort to marry Sencha Touch and WordPress
 * It creates a custom post type called 'App'.  Any app that
 * gets run is defined by App posts and the shortcodes within them.  
 * 
 * @TODO
 * 	- add storemeta_callback for callback queries
 *  - fix category bug in the-data/index.php 
 *  - allow shortcode atts to effectively add fields to the model (?)
 */


//register_activation_hook(__FILE__,'the_app_maker_activation');
function the_app_maker_activation(){
	wp_cache_delete('rewrite_rules');
}

add_action('init', 'the_app_maker_init');
function the_app_maker_init() 
{
	// Defining the App Custom Post Type
	$labels = array(
		'name' => _x('Apps', 'post type general name'),
		'singular_name' => _x('App', 'post type singular name'),
		'add_new' => _x('Add New', 'apps'),
		'add_new_item' => __('Add New App'),
		'edit_item' => __('Edit App'),
		'new_item' => __('New App'),
		'all_items' => __('All Apps'),
		'view_item' => __('View App'),
		'search_items' => __('Search Apps'),
		'not_found' =>  __('No apps found'),
		'not_found_in_trash' => __('No apps found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Apps'
	);
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array('title','editor','author','thumbnail','permalink','revisions')
	); 
	if (!defined('APP_POST_TYPE')){
		define('APP_POST_TYPE','apps');
	}
	if (!defined('APP_POST_VAR')){
		define('APP_POST_VAR','app');
	}
	if (!defined('APP_DATA_VAR')){
		define('APP_DATA_VAR','the_data');
	}
	if (!defined('APP_MANIFEST_VAR')){
		define('APP_MANIFEST_VAR','the_manifest');
	}
	if (!defined('APP_APPSCRIPT_VAR')){
		define('APP_APPSCRIPT_VAR','the_script');
	}
	if (!defined('APP_GLOBALS_VAR')){
		define('APP_GLOBALS_VAR','globals');
	}
	
	register_post_type(APP_POST_TYPE,$args);
	
	add_action( 'template_redirect', 'the_app_maker_redirect' );	
	
	// Load some plugins
	include_once('add-ons/the-app-twitter/the-app-twitter.php');
	include_once('add-ons/the-app-maps/the-app-maps.php');
}

//add filter to ensure the text Record, or record, is displayed when user updates a record 
add_filter('post_updated_messages', 'the_app_maker_updated_messages');
function the_app_maker_updated_messages( $messages ) {
  global $post, $post_ID;

  $messages['records'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('App updated. <a href="%s">View App</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('App updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('App restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('App published. <a href="%s">View app</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Record saved.'),
    8 => sprintf( __('App submitted. <a target="_blank" href="%s">Preview app</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('App scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview app</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('App draft updated. <a target="_blank" href="%s">Preview app</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );

  return $messages;
}

add_filter('option_rewrite_rules','the_app_maker_rewrite_rules');
function the_app_maker_rewrite_rules($rules){
	//$the_app_maker_rules[APP_POST_TYPE.'/?$'] = 'index.php?'.CONF_APP_QUERY_VAR.'=on'; // the app page
	$the_app_maker_rules[APP_POST_TYPE.'/([^/]+)/data/?$'] = 'index.php?'.APP_POST_VAR.'=$matches[1]&'.APP_DATA_VAR.'=posts'; // the posts page
	$the_app_maker_rules[APP_POST_TYPE.'/([^/]+)/data/([^/]+)/?$'] = 'index.php?'.APP_POST_VAR.'=$matches[1]&'.APP_DATA_VAR.'=$matches[2]'; // the posts page
	$the_app_maker_rules[APP_POST_TYPE.'/([^/]+)/manifest/?$'] = 'index.php?'.APP_POST_VAR.'=$matches[1]&'.APP_MANIFEST_VAR.'=on'; // the cache manifest
	$the_app_maker_rules[APP_POST_TYPE.'/([^/]+)/globals/?$'] = 'index.php?'.APP_POST_VAR.'=$matches[1]&'.APP_GLOBALS_VAR.'=on'; // the WordPress Globals js file
	$the_app_maker_rules[APP_POST_TYPE.'/([^/]+)/appscript/?$'] = 'index.php?'.APP_POST_VAR.'=$matches[1]&'.APP_APPSCRIPT_VAR.'=on'; // the app js file
	//$the_app_maker_rules[APP_POST_TYPE.'/([^/]*)/storemeta/?$'] = 'index.php?'.APP_POST_VAR.'=$matches[1]&'.APP_DATA_VAR.'=storemeta'; // the store meta page
	
	// I want the CONF_APP rules to appear at the beginning - thereby taking precedence over other rules
	$rules = $the_app_maker_rules + $rules;
	
	return $rules;
}

add_filter('query_vars','the_app_maker_query_vars');
function the_app_maker_query_vars($query_vars){
	$query_vars[] = APP_POST_VAR;
	$query_vars[] = APP_DATA_VAR;
	$query_vars[] = APP_MANIFEST_VAR;
	$query_vars[] = APP_GLOBALS_VAR;
	$query_vars[] = APP_APPSCRIPT_VAR;
	return $query_vars;
}

//add_action('parse_request','the_app_maker_parse_request'); // uncomment to check what was matched
function the_app_maker_parse_request($wp_rewrite){
	print_r($wp_rewrite);
	exit();
}

function the_app_maker_redirect(){
	$build_the_app = false;
	switch(true){
	case (get_query_var('post_type') == APP_POST_TYPE and have_posts() and !is_archive()):
		$build_the_app = true;
		break;
	case (get_query_var(APP_POST_VAR) != ''):
		// We need to find the App Custom Post that corresponds to the slug in APP_POST_VAR
		$query = 'name=' . get_query_var(APP_POST_VAR) . '&post_type=' . APP_POST_TYPE;
		$posts = get_posts($query);
		if (count($posts)){
			global $post;
			reset($posts); $post = current($posts); 
			$build_the_app = true;
		}
		break;
	}
	if ($build_the_app){
		require_once('TheAppMaker.class.php');
		setup_the_app_maker();
		switch(true){
		case get_query_var(APP_DATA_VAR) != '': $include = 'the-data/index.php'; break;
		case get_query_var(APP_MANIFEST_VAR) != '': $include = 'the-manifest/index.php'; break;
		case get_query_var(APP_GLOBALS_VAR) != '': $include = 'the-app/src/WordPressGlobals.js.php'; break;
		case get_query_var(APP_APPSCRIPT_VAR) != '': $include = 'the-app/the-app.js.php'; break;
		default: $include = 'the-app/index.php'; break;
		}
		
		do_action('the_app_maker_template_redirect',$include);
	}
}

add_action('the_app_maker_template_redirect','the_app_maker_include_and_exit');
function the_app_maker_include_and_exit($include){
	$the_app = & TheAppMaker::getInstance();
	include($include);
	exit();
}

global $the_app_maker;
function setup_the_app_maker(){
	// This is the function where we set up some globals
	$the_app_maker = & TheAppMaker::getInstance();
	global $post;
	$the_app_maker->setup($post);
	
	do_action('setup_the_app_maker');
}

function the_app_gettext($text){
	return apply_filters('the_app_gettext',$text);
}
add_filter('the_app_gettext','the_app_gettext_format',99);
function the_app_gettext_format($text){
	// We're sending back text that will end up within single quotes 
	// in JavaScript code.  Therefore, we need to properly escape what 
	// we're returning
	
	$text = str_replace("'","\\'",$text);
	
	// Also, need to deal with linebreaks
	$text = str_replace("\r","",$text);
	$text = str_replace("\n","<br/>",$text);
	
	return $text;
}

add_filter('the_app_gettext','the_app_textblocks');
function the_app_textblocks($text){
	if(substr($text,0,2) == '__'){
		switch($text){
		case '__updating':
			$text = __('Downloading updates from the server.  Please standby.','app-maker');
			break;
		case '__new_version':
			$text = __('A new version of this app is ready.  The app must be restarted.  Restart now?','app-maker');
			break;
		}
	}
	return $text;
}

add_filter('upload_mimes', 'the_app_upload_mimes');
function the_app_upload_mimes ( $existing_mimes=array() ) {
    // add the file extension to the array
    $existing_mimes['css'] = 'text/css';
    // call the modified list of extensions 
    return $existing_mimes;
 
}

add_action('parse_query','the_app_parse_query',1);
function the_app_parse_query(&$query){
	// This was an interesting one.  When loading the data or the manifest
	// WP_Query thinks that it's on the home page.  Some plugins (The Events 
	// Calendar, for example) might suppress certain categories on the home
	// page.  I don't want that.  I want The App Maker to have complete control
	// over its query.  
	if (get_query_var(APP_POST_VAR) and $query->is_home){
		$query->is_home = false;
	}
}



?>