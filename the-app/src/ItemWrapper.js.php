<?php if (!defined('APP_IS_VALID')) die('// Move along...'); ?>

the_app.views.ItemWrapper = Ext.extend(Ext.Panel, {
    layout: 'card',
    initComponent: function(){

		this.store = new Ext.data.Store({
			fields: ["name","card"]
		});		
		
		this.store.loadData(this.pages);
		
        this.list = new Ext.List({
            itemTpl: '<div class="page x-hasbadge">{title}</div>',
            ui: 'round',
			store: this.store,
            listeners: {
                selectionchange: {fn: this.onSelect, scope: this}
            }
            //title: this.title
        });
        
        this.listpanel = new Ext.Panel({
            title: this.title,
            items: this.list,
            layout: 'fit',
            dockedItems: {
                xtype: 'toolbar',
                title: this.title
            }
        })
        
        this.listpanel.on('activate', function(){
            this.list.getSelectionModel().deselectAll();
        }, this);
        
        this.items = [this.listpanel];
        
        the_app.views.ItemWrapper.superclass.initComponent.call(this);
    },
    
    onSelect: function(sel, records){
        if (records[0] !== undefined) {
            var newCard = Ext.apply({}, records[0].data.card, { 
                prevCard: this.listpanel,
                title: records[0].data.title
            });
            
            this.setActiveItem(Ext.create(newCard), get_option('transition'));
        }
    }
});

Ext.reg('itemwrapper', the_app.views.ItemWrapper);

