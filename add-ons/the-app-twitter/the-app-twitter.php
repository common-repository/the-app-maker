<?php
/*
Plugin Name: The App Twitter
*/

add_filter('TheAppMaker_init','TheAppMakerTwitter_init');
function TheAppMakerTwitter_init($this){
	add_shortcode('app_twitter','app_twitter_shortcode');
}

function app_twitter_shortcode($atts = array(),$content=null,$code=''){

	$item_defaults = array(
		'xtype' => 'tweetlist',
		'icon' => 'chat',
		'title' => __('Twitter','app-maker'),
		'search' => '#WordPress'
	);
	$meta_defaults = array(
		'_is_default' => 'false',
		'list_template' => ''
	);
	
	$item_atts = shortcode_atts($item_defaults,$atts);
	$meta_atts = shortcode_atts($meta_defaults,$atts);
	
	$the_app = & TheAppMaker::getInstance();
	$the_app->addItem($item_atts,$meta_atts);

	if (!has_filter('TheAppMaker_scripts','app_twitter_scripts')){
		add_filter('TheAppMaker_scripts','app_twitter_scripts',10,2);
		add_filter('TheAppMaker_models','app_twitter_models',10,2);
		add_action('the_app_maker_print_stylesheets','app_twitter_print_stylesheets');
		add_action('the_app_maker_print_manifest','app_twitter_print_manifest');
	}
}
function app_twitter_scripts($Scripts,$args){
	$the_app = & $args[0];
	$found = false;
	$src_proxy = dirname(__FILE__).'/the-app/src/TwitterProxy.js.php';
	$src_list = dirname(__FILE__).'/the-app/src/TweetList.js.php';
	foreach ($Scripts as $Script){
		if ($Script == $src_proxy){
			$found = true; // Don't add twice, it's alright
			break;
		}
	}
	if (!$found){
		array_unshift($Scripts,$src_proxy); // Must come before the app gets instantiated
		$Scripts[] = $src_list;
	}
	
	return $Scripts;
}	

function app_twitter_models($Models,$args){
	$Models['Tweet'] = array();
	$Models['Tweet']['fields'] = array('id', 'id_str', 'text', 'to_user_id', 'to_user', 'from_user', 'created_at', 'profile_image_url', 'created_ago');
	$Models['Tweet']['proxy'] = 'twitter';
	
	$Models['TwitterSearch'] = array();
	$Models['TwitterSearch']['fields'] = array('id', 'query');
	$Models['TwitterSearch']['hasMany'] = array('model' => 'Tweet', 'name' => 'tweets', 'filterProperty' => 'query', 'storeConfig' => array('pageSize' => 20, 'remoteFilter' => true, 'clearOnPageLoad' => false));

	return $Models;
}

function app_twitter_print_stylesheets(){
	echo '<link rel="stylesheet" href="'.plugins_url('the-app-maker/add-ons/the-app-twitter/the-app/resources/css/app-twitter.css').'" type="text/css" />'."\n";
}

function app_twitter_print_manifest(){
	echo plugins_url('the-app-maker/add-ons/the-app-twitter/the-app/resources/css/app-twitter.css')."\n";
}

?>