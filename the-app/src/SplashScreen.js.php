the_app.views.SplashScreen = Ext.extend(Ext.Panel, {
	fullscreen: true,
	cls: 'loading',
	hidden:true,
	listeners:{
		render: function(){
            this.el.mask('<span class="top"></span><span class="right"></span><span class="bottom"></span><span class="left"></span>', 'x-spinner', false);
		}
	},
    initComponent: function(){
        the_app.views.SplashScreen.superclass.initComponent.call(this);
    },
	splashIn: function(){
		this.show('fade');
	},
	splashOut: function(e){
		this.el.unmask();
		var that = this;
		setTimeout(function(){
			that.destroy();
			Ext.get(document.body).addCls('loaded');
		},2000); 
	}
});

Ext.reg('splashscreen', the_app.views.SplashScreen);