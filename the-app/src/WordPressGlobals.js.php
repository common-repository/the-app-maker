<?php 
	header('Content-type: text/javascript');
?>
/***************************************************
* The following are globals set to let the 
* Sencha app know about the WordPress installation
* 
* I am definitely cautious about the security risks 
* of doing this.  Big @TODO should be to encrypt 
* or obfuscate this.
***************************************************/

/** 
 * function get_bloginfo - a js map to WP get_bloginfo
 * 
 */
get_bloginfo = function(show,filter){
<?php
	// Anything you can pass to @get_blog_info as at version 3.1.3
	$valid_args = array('home','siteurl','url','wpurl','description','rdf_url','rss_url','rss2_url','atom_url','comments_atom_url','comments_rss2_url','pingback_url','stylesheet_url','stylesheet_directory','template_directory','template_url','charset','html_type','version','language','text_direction','name')
?>	
	var output;
	switch( show ) {
		<? foreach($valid_args as $arg) : 
			$url = true;
			if (strpos($show, 'url') === false &&
				strpos($show, 'directory') === false &&
				strpos($show, 'home') === false)
				$url = false;
		?>
		
		case '<?php echo $arg; ?>' : 
			output = "<?php $output = get_bloginfo($arg); echo addslashes_gpc($output); ?>"; 
			if (filter == 'display'){
				output = "<?php
					if ( $url )
						echo addslashes_gpc(apply_filters('bloginfo_url', $output, $arg));
					else
						echo addslashes_gpc(apply_filters('bloginfo', $output, $arg));
				?>";
			}
			break;
		<?php endforeach; ?>
		
		default: output = "<?php echo addslashes_gpc(get_option('blogname')); ?>"; break;
	}
	return output;
}

plugins_url = function(path,plugin,in_mu){
	<?php
		$mu_plugin_dir = WPMU_PLUGIN_DIR;
		foreach ( array('path', 'plugin', 'mu_plugin_dir') as $var ) {
			$$var = str_replace('\\' ,'/', $$var); // sanitize for Win32 installs
			$$var = preg_replace('|/+|', '/', $$var);
		}
	?>

	var url;
	if (plugin != undefined && in_mu){
		url = "<?php echo addslashes_gpc(WPMU_PLUGIN_URL); ?>";
	}
	else{
		url = "<?php echo addslashes_gpc(WP_PLUGIN_URL); ?>";
	}
	
	if (url.indexOf('http') == 0 && <?php echo (is_ssl() ? 'true' : 'false'); ?>){
		url = url.replace('http://','https://');
	}
	if (plugin != undefined){
		url+= '/'+plugin;
	}
	if (path != undefined){
		url+= '/'+path;
	}

	return url;
}

get_option = function(option){
	<?php 
	$options = $the_app->get();
	$options = apply_filters('the_app_maker_options',$options);
	foreach ($options as $option => $value){
		if (is_string($value)){
			$value = str_replace("\n","",$value);
			$value = str_replace("\r","",$value);			
			$options[$option] = $value;
		}
		elseif(is_bool($value)){
			$options[$option] = $value;
		}
		else{
			unset($options[$option]);
		}
	}
	?>
	
	var output;
	switch(option){
<?php foreach($options as $option => $value) : ?>
		case '<?php echo $option; ?>': output = "<?php echo addslashes_gpc($value); ?>"; break;
<?php endforeach; ?>
		default: output = get_bloginfo('blogurl');
	}
	return output;
}

maybelog = function(txt){
	// App Debug is <?php echo ($the_app->is('debug_on') ? 'ON' : 'OFF')."\n\n"; ?>
	<?php if ($the_app->is('debug_on')) : ?>
		console.log(txt);
	<?php else : ?>
		return;
	<?php endif; ?>
}

<?php exit(); ?>