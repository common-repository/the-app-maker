<?php if (!defined('APP_IS_VALID')) die('// Move along...'); ?>

<?php if (is_array($the_app->get('registered_post_queries'))) : foreach ($the_app->get('registered_post_queries') as $post_type => $meta) :  ?>

the_app.views.<?php echo $post_type; ?> = Ext.extend(Ext.Panel, {
    layout: 'card',
	isActivated: false,
    
    initComponent: function() {
	
		this.store = get<?php echo $post_type; ?>Store();
		
		var list_config = {};
		switch(this.query_instance){
		<?php foreach($meta as $query_instance => $query_vars) : ?>
		case <?php echo $query_instance; ?>: 
			list_config.grouped = <?php echo ($meta[$query_instance]['grouped'] == 'true' ? 'true' : 'false'); ?>;
			list_config.indexBar = <?php echo ($meta[$query_instance]['indexbar'] == 'true' ? 'true' : 'false'); ?>;
            list_config.itemTpl = '<?php echo addslashes_gpc($meta[$query_instance]['list_template']); ?>';
			break;
		<?php endforeach; ?>
		}
		
		list_config.store = this.store;
		list_config.loadText = false;
		list_config.listeners = {
            selectionchange: {fn: this.onSelect, scope: this}
        }
        
        this.list = new Ext.List(list_config);
        
		maybelog('<?php echo $post_type; ?> ' + this.store.isLoadedEh);
		if (this.store.isLoadedEh){
			this.maybeAdjustStore();
		}
		this.on('beforeactivate',this.maybeAdjustStore,this);
		
        var toolbarBase = {
            xtype: 'toolbar',
            title: this.title
        };
        
        if (this.prevCard !== undefined) {
            toolbarBase.items = {
                ui: 'back',
                text: this.prevCard.title,
                scope: this,
                handler: function(){
                    this.ownerCt.setActiveItem(this.prevCard, { type: get_option('transition'), reverse: true });
                }
            }
        }
        
        this.listpanel = new Ext.Panel({
            layout: 'fit',
            items: this.list,
            dockedItems: [toolbarBase],
            listeners: {
                activate: { fn: function(){
                    this.list.getSelectionModel().deselectAll();
                    Ext.repaint();
                }, scope: this }
            }
        });
        
        this.items = this.listpanel;
        
		// There is a bug where the last list header becomes sticky when scrolling
		// Don't know why, but this fixes it on the initial panel view.  However,
		// when switching back to the panel, the original buggy behaviour reappears.
		if ( this.scroller ){
		    this.scroller.updateBoundary();
		}
		
		this.on('activate', this.doActivation, this);

        the_app.views.<?php echo $post_type; ?>.superclass.initComponent.call(this);
    },

	doActivation: function(){
		if (!this.isActivated){
			if (this.list.store.isLoadedEh){
				this.initializeData(this.list.store);
			}
			else{
				this.list.store.addListener('load',this.initializeData,this);
			}
			this.isActivated = true;
		}
	},
	
	maybeAdjustStore: function(){
		maybelog('maybe adjusting');
		if (this.list.store.query_instance == undefined || this.list.store.query_instance != this.query_instance){
			maybelog('query instance: '+this.query_instance);
			switch(this.query_instance){
			<?php foreach($meta as $query_instance => $query_vars) : ?>
		
			case <?php echo $query_instance; ?>: 
				<?php 
				if ($query_vars['grouped'] == 'true') : 
					switch ($query_vars['group_by']){
					case 'category':
						$getGroupString = 'function(r){if (r.get(\'category\') == \'\') return \'-\'; else return r.get(\'category\');}';
						$groupField = 'category';
						break;
					case 'month':
						global $month; // WordPress

						$getGroupString = 'function(r){
							var date;
							if (r.get(\'spoof_id\') != \'\'){
								var r2 = this.getById(r.get(\'spoof_id\'));
								date = new Date(r2.get(\'date\'));
							}
							else{
								date = new Date(r.get(\'date\'));
							}
							var months = ["'.implode('", "',$month).'"];
							return months[date.getMonth()]+\', \'+date.getFullYear();
						}';
						$groupField = 'date';
						break;
					case 'first_letter': 
					default:
						$getGroupString = 'function(r){if (r.get(\''.$query_vars['query_vars']['orderby'].'\') == \'\') return \'-\'; else return r.get(\''.$query_vars['query_vars']['orderby'].'\')[0];}';
						$groupField = 'title';
						break;
					}
					?>
					this.list.store.getGroupString = <?php echo $getGroupString."\n"; ?>
					this.list.store.groupField = '<?php echo $groupField; ?>';
				<?php endif; ?>
				queryFilter = new Ext.util.Filter({
					filterFn: function(item){
						return item.data.query_num.match(/_<?php echo $query_instance; ?>_/);
					}
				});
				maybelog(queryFilter);
				if (this.list.store.isLoadedEh){
					this.list.store.clearFilter(true);
					this.list.store.filter(queryFilter);
				}
				else{
					// We're store hasn't loaded yet.  We'll filter by that once the store loads
					this.list.store.on('load',function(store){store.filter(queryFilter);});
				}
				this.list.store.sort([
					{
						property : '<?php echo $query_vars['query_vars']['orderby']; ?>',
						direction : '<?php echo $query_vars['query_vars']['order']; ?>'
					}
					<?php if($query_vars['query_vars']['orderby'] == 'category') : ?>
					,{
						property : 'title',
						direction : '<?php echo $query_vars['query_vars']['order']; ?>'
					}
					<?php endif; ?>
				]);
				
				break;
			<?php endforeach; ?>
			}
			this.list.store.query_instance = this.query_instance;
		}
	},
    
    initializeData: function(data) {
    },

    onSelect: function(sel, records){
        if (records[0] !== undefined) {
			if (records[0].data.spoof_id != undefined && records[0].data.spoof_id != ''){
				records[0] = this.store.getById(records[0].data.spoof_id);
			}
            var theCard = this.add({
				xtype: '<?php echo $post_type; ?>detail',
                prevCard: this.listpanel,
                record: records[0]
            });
            
            this.setActiveItem(theCard, get_option('transition'));
        }
    }
});

Ext.reg('<?php echo $post_type; ?>', the_app.views.<?php echo $post_type; ?>);
<?php endforeach; endif; ?>