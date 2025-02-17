<?php
/*
Plugin Name: The App Maps
*/

add_filter('TheAppMaker_init','TheAppMakerMaps_init');
function TheAppMakerMaps_init($this){
	add_shortcode('app_map','app_maps_shortcode');
	add_shortcode('app_map_point','app_maps_shortcode');
}

function app_maps_shortcode($atts = array(),$content=null,$code=''){
	$the_app = & TheAppMaker::getInstance();

	switch($code){
	case 'app_map':
		$item_defaults = array(
			'xtype' => 'location',
			'icon' => 'locate',
			'title' => __('Map','app-maker'),
			'use_current_location' => 'false'
		);
		$meta_defaults = array(
			'_is_default' => 'false'
		);
		$item_atts = shortcode_atts($item_defaults,$atts);
		$meta_atts = shortcode_atts($meta_defaults,$atts);
		
		// This will add in whatever points are there
		$the_app->set('GoogleMapPoints',array()); // reset
		do_shortcode($content);
		$item_atts['points'] = $the_app->get('GoogleMapPoints');
		
		if ($item_atts['use_current_location'] == 'true'){
			$item_atts['use_current_location'] = $the_app->do_not_escape('true');
		}
		else{
			$item_atts['use_current_location'] = $the_app->do_not_escape('false');
		}
	
		$the_app->addItem($item_atts,$meta_atts);

		if (!has_filter('TheAppMaker_scripts','app_maps_scripts')){
			add_filter('TheAppMaker_scripts','app_maps_scripts',10,2);
			add_filter('TheAppMaker_models','app_maps_models',10,2);
			add_action('the_app_maker_print_stylesheets','app_maps_print_stylesheets');
			add_action('the_app_maker_print_scripts','app_maps_print_scripts');
			add_action('the_app_maker_print_manifest','app_maps_print_manifest');
			add_filter('the_app_gettext','app_maps_textblocks');
		}
		break;
	case 'app_map_point':
		$current_points = $the_app->get('GoogleMapPoints');
		if (!is_array($current_points)){
			$current_points = array();
		}
		$item_defaults = array(
			'title' => '',
			'lat' => '',
			'long' => ''
		);
		$atts = shortcode_atts($item_defaults,$atts);
		if ($atts['title'] != '' and $atts['lat'] != '' and $atts['long'] != ''){
			// Must have a title, lat & long
			$point = array(
				'title' => $atts['title'],
				'lat' => $atts['lat'],
				'long' => $atts['long'],
				'text' => apply_filters('the_content',$content)
			);
			$current_points[] = $point;
		}
		$the_app->set('GoogleMapPoints',$current_points);
		break;
	}
	
}
function app_maps_scripts($Scripts,$args){
	$the_app = & $args[0];
	$Scripts[] = dirname(__FILE__).'/the-app/src/LocationMap.js.php';	
	return $Scripts;
}	

function app_maps_models($Models,$args){
	return $Models;
}

function app_maps_print_stylesheets(){
	echo '<link rel="stylesheet" href="'.plugins_url('the-app-maker/add-ons/the-app-maps/the-app/resources/css/app-map.css').'" type="text/css" />'."\n";
}

function app_maps_print_scripts(){
	echo '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>'."\n";
}

function app_maps_print_manifest(){
	echo 'http://maps.google.com/maps/api/js?sensor=true'."\n";
}

function app_maps_textblocks($text){
	switch($text){
	case '__offline_maps':
		$text = sprintf('It appears your devices is not connected to the internet.  Google Maps requires an internet connection.  To use the maps in this app, connect to the internet and %srestart the app%s.','<a href="javascript:window.location.reload();">','</a>');
		break;	
	}
	return $text;
}
?>