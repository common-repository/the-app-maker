<?php if (!defined('APP_IS_VALID')) die('// Move along...'); ?>

<?php if (is_array($the_app->get('registered_post_queries'))) : foreach ($the_app->get('registered_post_queries') as $post_type => $meta) : ?>

the_app.views.<?php echo $post_type; ?>Detail = Ext.extend(Ext.Panel, {
    scroll: 'vertical',
    initComponent: function(){
	
        this.dockedItems = [{
            xtype: 'toolbar',
            title: '',
            items: [{
                ui: 'back',
                text: '<?php echo the_app_gettext('Back'); ?>',
                scope: this,
                handler: function(){
                    this.ownerCt.setActiveItem(this.prevCard, {
                        type: get_option('transition'),
                        reverse: true,
                        scope: this,
                        after: function(){
                            this.destroy();
                        }
                    });
                }
            }]
        }];
        
        this.items = [{
            styleHtmlContent: true,
            tpl: new Ext.XTemplate( '<?php echo $meta[0]['detail_template']; ?>'),
            data: this.record.data
        }];
        
        this.listeners = {
            activate: { fn: function(){
            }, scope: this }
        };
        
        the_app.views.<?php echo $post_type; ?>Detail.superclass.initComponent.call(this);
    },
    
});

Ext.reg('<?php echo $post_type; ?>detail', the_app.views.<?php echo $post_type; ?>Detail);

<?php endforeach; endif; ?>