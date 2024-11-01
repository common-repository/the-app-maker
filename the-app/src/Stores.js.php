<?php if (!defined('APP_IS_VALID')) die('// Move along...'); ?>

Ext.data.OfflineStore = Ext.extend(Ext.data.Store, {
	// It turns out that running a sync on a store
	// bound to a list in the app without suspending 
	// events causes lag in the app, to the point of 
	// javascript execution timeout.  This method
	// suspends events, runs its own version of the sync
	// then resumes events.  
	sync_from_store: function(store){
		maybelog('syncing from store '+store.model.modelName);
		
		// Suspend events, discarding anything that is fired 
		this.suspendEvents(false);
		
		// remove all records from this (offline) store
		this.clearFilter();
		this.remove(this.getRange());
		
		// add all records from the passed (online) store
	    store.each(function (record) {
	        this.add(record.data);
	    },this);
		
		// Ooops...  Can't run sync with events suspended.  Have to run it manually
        var options   = {},
            toUpdate  = this.getUpdatedRecords(),
            toDestroy = this.getRemovedRecords(),
            toCreate  = this.getNewRecords();

        options.create = toCreate;
        options.update = toUpdate;
        options.destroy = toDestroy;
		this.proxy.batchOrder = 'destroy,create,update';
		maybelog(this.proxy.batchOrder);
        this.proxy.batch(options, this.getBatchListeners());
		this.resumeEvents();
	    this.fireEvent('load',this);
	}
});
<?php
		foreach ($the_app->get('stores') as $key => $store){
			$offline_enable = $store['offline_enable'];
			unset($store['offline_enable']); // internal flag.  Don't output
			unset($store['autoload']); // internal flag.  Don't output
			
			$store['isLoadedEh'] = TheAppMaker::do_not_escape('false');
			
			// Instantiate the Store.  
			echo "the_app.$key = new Ext.data.Store({\n";
			$sep = "\t";
			foreach ($store as $what => $details){
				echo $sep."$what: ";
				echo TheAppMaker::anti_escape(json_encode($details));
				echo "\n";
				$sep = "\t,";
			}
			echo "});\n";
			echo "the_app.$key.on('load',function(){this.isLoadedEh = true;});\n";
			
			if ($offline_enable){
				$offline_store = $store;
				$offline_store['proxy'] = array('type'=>'localstorage','id' => $the_app->get('app_id').'_'.$store['model']);
				echo "the_app.Offline$key = new Ext.data.OfflineStore({\n";
				$sep = "\t";
				foreach ($offline_store as $what => $details){
					echo $sep."$what: ";
					echo TheAppMaker::anti_escape(json_encode($details));
					echo "\n";
					$sep = "\t,";
				}
				echo "});\n";
				echo "the_app.Offline$key.on('load',function(){this.isLoadedEh = true;});\n";
			}
			
			echo "get$key = function(){return the_app.".($offline_enable ? 'Offline' : '')."$key; }\n";
			
		}
?>