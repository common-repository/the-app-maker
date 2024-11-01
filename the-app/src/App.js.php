<?php if (!defined('APP_IS_VALID')) die('Move along...'); ?>
<?php
	// Get the items
	$items = $the_app->the_items();
?>

the_app.cfg = {};

the_app.AppObject = Ext.extend(Ext.TabPanel, {
    
    fullscreen: true,
	cache: null,
	hidden: true,

    
    tabBar: {
        ui: 'gray',
        dock: 'bottom',
        layout: { pack: 'center' }
    },
    
    cardSwitchAnimation: false,
    
    initComponent: function() {

        if (true || navigator.onLine) {
            this.items = <?php echo TheAppMaker::anti_escape(json_encode($items)); ?>;
			this.on('cardswitch',function(c){
				this.cardJustSwitched = true;
			}, this);
        } else {
            this.on('render', function(){
                this.el.mask('No internet connection.');
            }, this);
        }

        the_app.cfg = {};
        the_app.cfg.shortUrl = this.shortUrl;
        the_app.cfg.title = this.title;

        the_app.AppObject.superclass.initComponent.call(this);
    },

	setupTabBarClickEvent: function(c){
         var tabs = c.ownerCt,
             bar = tabs.tabBar,
             tab;
             
        bar.items.each(function(item){
            if(item.card == c){
                tab = item;
                return false;
            }
        });
        if(tab){
            tab.el.on('click', function(){
				// This event fires after the 'cardswitch' event above.  
				// The following if statement makes sure the reset only happens 
				// if the user clicks the tabbar icon while already on that tab.
				if (!this.cardJustSwitched){
					// this == Overall (parent) TabPanel
					// this.getActiveItem() == The Current Tab
					this.getActiveItem().setActiveItem(0); // return to the first item (the list)
					this.getActiveItem().items.each(function(item,i){
						// Destroy all but the first item
						if (i > 0){
							item.destroy();
						}
					});
				}
				this.cardJustSwitched = false;
            },this);
        }
	},

	isOnline: function(){
		return navigator.onLine;
	},
	
	checkForUpdates: function(){
		this.cache.update();
	},
	
	showPopup: function(options){
		if (this.popupStack == undefined){
			this.popupStack = {};
		}
		
		if (this.popupStack[options.id] == undefined){
			this.popupStack[options.id] = new Ext.Panel({
                floating: true,
                modal: true,
				hideOnMaskTap: (options.hideOnMaskTap ? true : false),
                centered: true,
                width: (options.width  ? options.width : 300),
                height: (options.height  ? options.height : 200),
                styleHtmlContent: true,
                scroll: 'vertical',
                html: options.html+(options.spinner ? '<div class="spinner '+options.spinner+'"></div>' : ''),
				cls: 'popup '+options.id,
                dockedItems: [{
                    dock: 'top',
                    xtype: 'toolbar',
                    title: options.title
                }]
            });
		}
		
		this.popupStack[options.id].show('pop');
	},
	
	hidePopup: function(id){
		if (this.popupStack != undefined && this.popupStack[id] != undefined){
			this.popupStack[id].hide('pop');
		}
		
	}

});