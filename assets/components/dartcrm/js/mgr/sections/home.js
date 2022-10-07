dartCRM.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'dartcrm-panel-home',
            renderTo: 'dartcrm-panel-home-div'
        }]
    });
    dartCRM.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM.page.Home, MODx.Component);
Ext.reg('dartcrm-page-home', dartCRM.page.Home);