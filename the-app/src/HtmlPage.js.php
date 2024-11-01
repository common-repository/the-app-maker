the_app.views.HtmlPage = Ext.extend(Ext.Panel, {
    scroll: 'vertical',
    styleHtmlContent: true,
    initComponent: function(){
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
        
        this.dockedItems = toolbarBase;

		this.update('<div class="htmlpage">'+this.content+'</div>');
		
        the_app.views.HtmlPage.superclass.initComponent.call(this);
    }
});

Ext.reg('htmlpage', the_app.views.HtmlPage);