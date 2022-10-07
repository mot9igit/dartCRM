dartCRM.panel.Home = function (config) {
    config = config || {};
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',
        /*
         stateful: true,
         stateId: 'dartcrm-panel-home',
         stateEvents: ['tabchange'],
         getState:function() {return {activeTab:this.items.indexOf(this.getActiveTab())};},
         */
        hideMode: 'offsets',
        items: [{
            html: '<h2>' + _('dartcrm') + '</h2>',
            cls: '',
            style: {margin: '15px 0'}
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            border: true,
            hideMode: 'offsets',
            items: [{
                title: _('dartcrm_fields'),
                layout: 'anchor',
                items: [{
                    html: _('dartcrm_fields_desc'),
                    cls: 'panel-desc',
                }, {
                    xtype: 'dartcrm-grid-fields',
                    cls: 'main-wrapper',
                }]
            }]
        }]
    });
    dartCRM.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM.panel.Home, MODx.Panel);
Ext.reg('dartcrm-panel-home', dartCRM.panel.Home);
