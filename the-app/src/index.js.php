<?php if (!defined('APP_IS_VALID')) die('// Move along...'); ?>

Ext.ns('the_app', 'the_app.views');

launchApp = function(){
	var splash = Ext.create({xtype:'splashscreen'}); 
	splash.splashIn();
	
	if (false && typeof WebKitPoint != 'function'){
		document.body.innerHTML = 'This app works only in Webkit Browsers, like Safari, Chrome, iPhone, Android.  It does not work in Firefox or Internet Explorer.  Sorry.  <a href="javascript:history.go(-1)">Go back</a>.';
		return;
	}

	var options = {
        title: get_option('title'),
	};
	
	maybeCheckForDataUpdates();
	loadStores();
	
	if (the_app.App != undefined){
		maybelog('doing layout');
		the_app.App.doLayout();
	}
	else{
	    the_app.App = new the_app.AppObject(options);
		the_app.App.on('show',splash.splashOut,splash);
		setTimeout(function(){the_app.App.show('fade');},2000); // The delay is literally just to give the splash screen a chance to show.  
	}
}

var loadedStoresAlready = false;
loadStores = function(forceOnline,specificStore){
	if (forceOnline === undefined){
		forceOnline = false;
	}
	
	<?php $Stores = $the_app->get('stores');  ?>
	<?php foreach ($Stores as $key => $store) : ?>
		// Load the the_app.<?php echo $key; ?> Store
		<?php if ($store['offline_enable']) : ?>
			if (!loadedStoresAlready){
				the_app.<?php echo $key; ?>.addListener('load',function(){
					maybelog('in load listener for <?php echo $key; ?>');
					the_app.Offline<?php echo $key; ?>.sync_from_store(the_app.<?php echo $key; ?>); 
				});
			}
			if (specificStore == undefined || specificStore == '<?php echo $key; ?>'){
				if (!forceOnline && localStorage.getItem('<?php echo $the_app->get('app_id').'_'.$store['model']; ?>')){
					maybelog('loading offline <?php echo $key; ?>');
					the_app.Offline<?php echo $key; ?>.load();
				}
				else{
					maybelog('loading online <?php echo $key; ?>');
					if (the_app.<?php echo $key; ?>.getCount()){
						maybelog('emptying store');
						the_app.<?php echo $key; ?>.suspendEvents(false);
						the_app.<?php echo $key; ?>.remove(the_app.<?php echo $key; ?>.getRange());
						the_app.<?php echo $key; ?>.resumeEvents();
					}
					the_app.<?php echo $key; ?>.load(); // Will also load the Offline version
				}
			}
		<?php elseif (isset($store['proxy']) and $store['autoload'] == true) : ?>
			the_app.<?php echo $key; ?>.load(); 
		<?php endif; ?>
	<?php endforeach; ?>
	loadedStoresAlready = true;
}

reloadStore = function(key){
	loadStores(true,key);
}

maybeCheckForDataUpdates = function(){
	if (get_option('_is_using_manifest') == 1){
		checkForDataUpdates();
	}
}

checkForDataUpdates = function(){
	the_app.StoreStatusStore.on('load',lambalambdalambda = function(_this,records,successful){
		// We'll load the online Store Status Store to get the timestamps for
		// the other stores.  We can then compare then against
		// the offline data (if any exists), and if there's a change, then 
		// we can trigger loading the online stores
		maybelog('Store Status:');
		maybelog(records);
		maybelog('Offline Store Status:');
		maybelog(the_app.OfflineStoreStatusStore.getRange());
		var offline_record,offline_index,stores_to_update = new Array;
		for(var r = 0; r < records.length; r++){
			offline_index = the_app.OfflineStoreStatusStore.find('store',records[r].data.store);
			if (offline_index >= 0){
				offline_record = the_app.OfflineStoreStatusStore.getAt(offline_index);
				if (offline_record.data.timestamp < records[r].data.timestamp){
					stores_to_update.push(records[r].data.store);;
					maybelog('update available for ' + records[r].data.store);
				}
			}
		}
		maybelog('Stores to update');
		maybelog(stores_to_update);
		if (stores_to_update.length){
			// when we've discovered there are updates, we don't actually want to write
			// the new timestamps to LocalStorage, so the same state is entered next time
			// the app loads.
			the_app.App.showPopup({
				id: 'updating', 
				title: '<?php echo the_app_gettext('Updating'); ?>',
				html: '<?php echo the_app_gettext('__updating'); ?>',
				spinner: 'black x48'
			});
			
			// Great.  I've got a mask showing.  Now, let's update the stores
			var stores_done_updating = 0;
			for (var s = 0; s < stores_to_update.length; s++){
				switch(stores_to_update[s]){
				<?php foreach ($Stores as $key => $store) : ?>
					<?php if ($store['offline_enable']) : ?>
						case '<?php echo $key; ?>':
							maybelog('attempting to update the_app.<?php echo $key; ?>');
							the_app.<?php echo $key; ?>.on('load',function(){
								stores_done_updating++;
							});
							reloadStore('<?php echo $key; ?>');
							break;
					<?php endif; ?>
				<?php endforeach; ?>
				}
			}
			
			// Now, setup a little loop to check when the number of stores_done_updating
			// equals the length of stores_to_update
			var elapsed = 0, interval = 500, allowed = 60000, display_for = 2000, action_completed = false;
			var updateInterval = setInterval(function(){
				elapsed += interval;
				
				if (!action_completed && stores_done_updating >= stores_to_update.length){
					action_completed = true;
					
					_this.un('load',lambalambdalambda);
					_this.fireEvent('load',_this,records,successful);
					
				}
				if (elapsed >= display_for){
					if (action_completed){
						clearInterval(updateInterval);
						the_app.App.hidePopup('updating');
						var current_page = the_app.App.getActiveItem();
						if (current_page.xtype == 'sessionlist'){
							maybelog('updating session list');
							current_page.hasInitializedDate = false;
							current_page.checkActiveDate();
						}
					}
					else if (elapsed >= allowed){
						clearInterval(updateInterval);
						maybelog('problem updating data');
						the_app.App.hidePopup('updating');
						the_app.App.showPopup({
							id: 'update_error', 
							title: '<?php echo the_app_gettext('What Happened?'); ?>',
							html: '<?php echo the_app_gettext('Could not communicate with the Mothership'); ?>',
							hideOnMaskTap: true
						});
					}
				}
			},interval);
			
			return false;
		}
	});
	maybelog('navigator: '+(navigator.onLine ? 'online' : 'offline'));
	if (navigator.onLine){
		maybelog('loading Stores Status Store');
		the_app.StoreStatusStore.load();
	}
}

logCacheEvent = function(e){
	var cacheStatusValues = [];
		cacheStatusValues[0] = 'uncached';
		cacheStatusValues[1] = 'idle';
		cacheStatusValues[2] = 'checking';
		cacheStatusValues[3] = 'downloading';
		cacheStatusValues[4] = 'updateready';
		cacheStatusValues[5] = 'obsolete';
	var online, status, type, message;
	online = (navigator.onLine) ? 'yes' : 'no';
	status = cacheStatusValues[this.status];
	type = e.type;
	message = 'online: ' + online;
	message+= ', event: ' + type;
	message+= ', status: ' + status;
	if (type == 'error' && navigator.onLine) {
		message+= ' There was an unknown error, check your Cache Manifest.';
	}
	maybelog('Event: ' + message);
}

newCacheReady = function(){
	maybelog('It is ready...');
	this.swapCache();
	
	Ext.Msg.confirm(
		'<?php echo the_app_gettext('Software Update'); ?>', 
		'<?php echo the_app_gettext('__new_version'); ?>', 
		function(button){
			if (button == 'yes'){
				window.location.reload();
			}
		}
	);
}

if (!window.applicationCache) {
	maybelog('No Cache Manifest listed on the tag.')
}
else{
	window.applicationCache.addEventListener('cached', logCacheEvent, false);
	window.applicationCache.addEventListener('checking', logCacheEvent, false);
	window.applicationCache.addEventListener('downloading', logCacheEvent, false);
	window.applicationCache.addEventListener('error', logCacheEvent, false);
	window.applicationCache.addEventListener('noupdate', logCacheEvent, false);
	window.applicationCache.addEventListener('obsolete', logCacheEvent, false);
	window.applicationCache.addEventListener('progress', logCacheEvent, false);
	window.applicationCache.addEventListener('updateready', logCacheEvent, false);
	window.applicationCache.addEventListener('updateready', newCacheReady, false); 
}


Ext.setup({
    statusBarStyle: 'black',
    tabletStartupScreen: '<?php echo apply_filters('the_app_startup_tablet',($the_app->get('startup_tablet') ? $the_app->get('startup_tablet') : plugins_url('the-app-maker/the-app/resources/img/startup_tablet.jpg'))); ?>',
    phoneStartupScreen: '<?php echo apply_filters('the_app_startup_phone',($the_app->get('startup_phone') ? $the_app->get('startup_phone') : plugins_url('the-app-maker/the-app/resources/img/startup_phone.jpg'))); ?>',
    icon: '<?php echo apply_filters('the_app_icon',($the_app->get('icon') ? $the_app->get('icon') : plugins_url('the-app-maker/the-app/resources/img/icon.jpg'))); ?>',
	glossOnIcon: false,
    onReady: launchApp
});
