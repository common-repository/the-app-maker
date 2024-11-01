<?php 
	/** 
	 * $Scripts is an array of the scripts that we want to include
	 */
	$Scripts = $the_app->get('scripts'); 
	
	define('APP_IS_VALID',true);
	header('Content-type: text/javascript');
	
	foreach ($Scripts as $Script){
		include($Script);
	}
?>