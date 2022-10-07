dartCRM.window.UpdateField = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'dartcrm-field-window-update';
    }
    Ext.applyIf(config, {
        title: _('dartcrm_field_update'),
        width: 550,
        autoHeight: true,
        url: dartCRM.config.connector_url,
        action: 'mgr/fields/update',
        fields: this.getFields(config),
        keys: [{
            key: Ext.EventObject.ENTER, shift: true, fn: function () {
                this.submit()
            }, scope: this
        }]
    });
    dartCRM.window.UpdateField.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM.window.UpdateField, MODx.Window, {

    getFields: function (config) {
        return [{
            xtype: 'hidden',
            name: 'id',
            id: config.id + '-id',
        }, {
            xtype: 'hidden',
            name: 'crm_id',
            id: config.id + '-crm_id',
        }, {
            xtype: 'textfield',
            fieldLabel: _('dartcrm_field_name'),
            name: 'name',
            id: config.id + '-name',
            anchor: '99%',
            allowBlank: false,
        }, {
            xtype: 'statictextfield',
            fieldLabel: _('dartcrm_field_enums'),
            name: 'enums',
            id: config.id + '-enums',
            anchor: '99%'
        },{
            xtype: 'dartcrm-combo-type',
            fieldLabel: _('dartcrm_field_type'),
            name: 'type',
            hiddenName: 'type',
            id: config.id + '-type',
            anchor: '99%',
            allowBlank: false
        }, {
            xtype: 'dartcrm-combo-orderfield',
            fieldLabel: _('dartcrm_field_field'),
            name: 'field',
            id: config.id + '-field',
            anchor: '99%',
        }, {
            xtype: 'textarea',
            fieldLabel: _('dartcrm_field_properties'),
            name: 'properties',
            id: config.id + '-properties',
            anchor: '99%'
        }/*, {
            xtype: 'xcheckbox',
            boxLabel: _('dartcrm_field_active'),
            name: 'active',
            id: config.id + '-active',
        }*/];
    },

    loadDropZones: function () {
    }

});
Ext.reg('dartcrm-field-window-update', dartCRM.window.UpdateField);