<!DOCTYPE html>
<html<?php if ($the_app->is('using_manifest')) : ?> manifest="<?php echo $the_app->get('mothership').'manifest/'; ?>"<?php endif; ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php $post = $the_app->get('post'); echo $post->post_title; ?></title >

    <link rel="stylesheet" href="<?php echo plugins_url('the-app-maker/the-app/resources/css/oreilly.css'); ?>" type="text/css" />
    <link rel="stylesheet" href="<?php echo plugins_url('the-app-maker/the-app/resources/css/the-app.css'); ?>" type="text/css" />
	<?php if ($the_app->get('stylesheet')) : ?><link rel="stylesheet" href="<?php echo $the_app->get('stylesheet'); ?>" type="text/css" /><?php endif;?>
	<?php do_action('the_app_maker_print_stylesheets'); ?>
	
    <script type="text/javascript" src="<?php echo plugins_url('the-app-maker/the-app/sencha-touch'.($the_app->is('debug_on') ? '-debug' : '').'.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo $the_app->get('mothership').'globals/'; // note trailing slash ?>"></script>
    <script type="text/javascript" src="<?php echo $the_app->get('mothership').'appscript/'; // note trailing slash ?>"></script>
	<?php do_action('the_app_maker_print_scripts'); ?>

    <link rel="apple-touch-startup-image" media="screen and (resolution: 326dpi)" href="<?php echo apply_filters('the_app_startup_tablet',($the_app->get('startup_tablet') ? $the_app->get('startup_tablet') : plugins_url('the-app-maker/the-app/resources/img/startup_tablet.jpg'))); ?>" />
    <link rel="apple-touch-startup-image" media="screen and (resolution: 163dpi)" href="<?php echo apply_filters('the_app_startup_phone',($the_app->get('startup_phone') ? $the_app->get('startup_phone') : plugins_url('the-app-maker/the-app/resources/img/startup_phone.jpg'))); ?>" />

    <link rel="apple-touch-icon-precomposed" href="<?php echo apply_filters('the_app_icon',($the_app->get('icon') ? $the_app->get('icon') : plugins_url('the-app-maker/the-app/resources/img/icon.jpg'))); ?>" />
	<style type="text/css">
	.loading{
		background-image: url(<?php echo apply_filters('the_app_startup_tablet',($the_app->get('startup_tablet') ? $the_app->get('startup_tablet') : plugins_url('the-app-maker/the-app/resources/img/startup_tablet.jpg'))); ?>);
	}
	</style>
</head>
<body>
</body>
</html> 