<?php
	if ($_GET['test'] != 'true'){
		header('Content-type: text/cache-manifest');
	}
?>
CACHE MANIFEST
# Using Manifest: <?php echo ($the_app->is('using_manifest') ? 'true' : 'false')."\n"; ?>
# Version <?php echo ($the_app->get('manifest_version') ? $the_app->get('manifest_version') : '1')."\n"; ?>

# The Main App files
<?php echo plugins_url('the-app-maker/the-app/resources/css/oreilly.css')."\n"; ?>
<?php echo plugins_url('the-app-maker/the-app/resources/css/the-app.css')."\n"; ?>
<?php echo plugins_url('the-app-maker/the-app/sencha-touch'.($the_app->is('debug_on') ? '-debug' : '').'.js')."\n"; ?>
<?php echo $the_app->get('mothership').'globals/'."\n"; ?>
<?php echo $the_app->get('mothership').'appscript/'."\n"; ?>
<?php if ($the_app->get('stylesheet')) echo $the_app->get('stylesheet')."\n"; ?>
		
# The App Images
<?php echo apply_filters('the_app_startup_tablet',($the_app->get('startup_tablet') ? $the_app->get('startup_tablet') : plugins_url('the-app-maker/the-app/resources/img/startup_tablet.jpg')))."\n"; ?>
<?php echo apply_filters('the_app_startup_phone',($the_app->get('startup_phone') ? $the_app->get('startup_phone') : plugins_url('the-app-maker/the-app/resources/img/startup_phone.jpg')))."\n"; ?>
<?php echo apply_filters('the_app_icon',($the_app->get('icon') ? $the_app->get('icon') : plugins_url('the-app-maker/the-app/resources/img/icon.jpg')))."\n"; ?>

# The Post Images
<?php
	$all_registered_queries = $the_app->get('registered_post_queries');

	$post_ids = array();
	foreach ($all_registered_queries as $type => $registered_queries){
		foreach ($registered_queries as $query_instance => $registered_query){
			if (isset($registered_query['query_vars']['data_callback']) and function_exists($registered_query['query_vars']['data_callback'])){
				// This is an outside query, so no need to get post images
				continue;
			}
			$registered_query['query_vars']['numberposts'] = -1;
			$posts = get_posts($registered_query['query_vars']);
			foreach ($posts as $post){
				if ($query_instance > 0 and array_key_exists($post->ID,$post_ids)){
					// Already output it, no need to again
				}
				else{
					$image = $the_app->getPostImages($post->ID,0);
					if ($image){
						echo $image."\n";
					}
				}
			}
		}	
	}
?>

<?php do_action('the_app_maker_print_manifest'); ?>

#Everything Else
NETWORK:
*

