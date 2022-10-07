dartCRM.combo.ComboBoxDefault = function (config) {
    config = config || {};

    Ext.applyIf(config, {
        assertValue : function () {
            var val = this.getRawValue(),
                rec;
            if (this.valueField && Ext.isDefined(this.value)) {
                rec = this.findRecord(this.valueField, this.value);
            }
            /* fix for https://github.com/bezumkin/miniShop2/pull/350
            if(!rec || rec.get(this.displayField) != val){
                rec = this.findRecord(this.displayField, val);
            }*/
            if (rec && rec.get(this.displayField) != val) {
                rec = null;
            }
            if (!rec && this.forceSelection) {
                if (val.length > 0 && val != this.emptyText) {
                    this.el.dom.value = Ext.value(this.lastSelectionText, '');
                    this.applyEmptyText();
                } else {
                    this.clearValue();
                }
            } else {
                if (rec && this.valueField) {
                    if (this.value == val) {
                        return;
                    }
                    val = rec.get(this.valueField || this.displayField);
                }
                this.setValue(val);
            }
        },

    });
    dartCRM.combo.ComboBoxDefault.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM.combo.ComboBoxDefault, MODx.combo.ComboBox);
Ext.reg('dartcrm-combo-combobox-default', dartCRM.combo.ComboBoxDefault);

dartCRM.combo.Search = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        xtype: 'twintrigger',
        ctCls: 'x-field-search',
        allowBlank: true,
        msgTarget: 'under',
        emptyText: _('search'),
        name: 'query',
        triggerAction: 'all',
        clearBtnCls: 'x-field-search-clear',
        searchBtnCls: 'x-field-search-go',
        onTrigger1Click: this._triggerSearch,
        onTrigger2Click: this._triggerClear,
    });
    dartCRM.combo.Search.superclass.constructor.call(this, config);
    this.on('render', function () {
        this.getEl().addKeyListener(Ext.EventObject.ENTER, function () {
            this._triggerSearch();
        }, this);
    });
    this.addEvents('clear', 'search');
};
Ext.extend(dartCRM.combo.Search, Ext.form.TwinTriggerField, {

    initComponent: function () {
        Ext.form.TwinTriggerField.superclass.initComponent.call(this);
        this.triggerConfig = {
            tag: 'span',
            cls: 'x-field-search-btns',
            cn: [
                {tag: 'div', cls: 'x-form-trigger ' + this.searchBtnCls},
                {tag: 'div', cls: 'x-form-trigger ' + this.clearBtnCls}
            ]
        };
    },

    _triggerSearch: function () {
        this.fireEvent('search', this);
    },

    _triggerClear: function () {
        this.fireEvent('clear', this);
    },

});
Ext.reg('dartcrm-combo-search', dartCRM.combo.Search);
Ext.reg('dartcrm-field-search', dartCRM.combo.Search);

dartCRM.combo.User = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        name: 'user',
        fieldLabel: config.name || 'createdby',
        hiddenName: config.name || 'createdby',
        displayField: 'fullname',
        valueField: 'id',
        anchor: '99%',
        fields: ['username', 'id', 'fullname'],
        pageSize: 20,
        typeAhead: false,
        editable: true,
        allowBlank: true,
        url: dartCRM.config['connector_url'],
        baseParams: {
            action: 'mgr/system/user/getlist',
            combo: true,
        },
        tpl: new Ext.XTemplate(
            '\
            <tpl for=".">\
                <div class="x-combo-list-item">\
                    <span>\
                        <small>({id})</small>\
                        <b>{username}</b>\
                        <tpl if="fullname && fullname != username"> - {fullname}</tpl>\
                    </span>\
                </div>\
            </tpl>',
            {compiled: true}
        ),
    });
    dartCRM.combo.User.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM.combo.User, dartCRM.combo.ComboBoxDefault);
Ext.reg('dartcrm-combo-user', dartCRM.combo.User);

dartCRM.combo.field_type = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        store: new Ext.data.ArrayStore({
            id: 0,
            fields: ['type','display'],
            data: [
                [1, 'Сделка'],
                [2, 'Контакт'],
                [3, 'Товар']
            ]
        }),
        mode: 'local',
        displayField: 'display',
        valueField: 'type'
    });
    dartCRM.combo.field_type.superclass.constructor.call(this,config);
};
Ext.extend(dartCRM.combo.field_type,MODx.combo.ComboBox);
Ext.reg('dartcrm-combo-type', dartCRM.combo.field_type);

dartCRM.combo.Product = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        url: dartCRM.config.connector_url,
        baseParams: {
            action: 'mgr/system/product/getlist',
        },
        name: 'product_id',
        hiddenName: 'product_id',
        fields: ['id', 'pagetitle', 'article', 'price'],
        mode: 'remote',
        displayField: 'pagetitle',
        fieldLabel: _('dartcrm_product_name'),
        valueField: 'id',
        editable: true,
        anchor: '99%',
        allowBlank: true,
        autoLoad: true,
        tpl: new Ext.XTemplate(
            '\
            <tpl for=".">\
                <div class="x-combo-list-item">\
                    <span>\
                        <small>({id})</small>\
                        <b>{pagetitle} ({article}) {price} р.</b>\
                    </span>\
                </div>\
            </tpl>',
            {compiled: true}
        ),
    });
    dartCRM.combo.Product.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM.combo.Product, MODx.combo.ComboBox);
Ext.reg('dartcrm-combo-product', dartCRM.combo.Product);

dartCRM.combo.ms2Status = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        url: dartCRM.config.connector_url,
        baseParams: {
            action: 'mgr/system/ms2status/getlist',
        },
        name: 'ms2status_id',
        hiddenName: 'ms2status_id',
        fields: ['id', 'name', 'description'],
        mode: 'remote',
        displayField: 'name',
        fieldLabel: _('dartcrm_ms2status'),
        valueField: 'id',
        editable: true,
        anchor: '99%',
        allowBlank: true,
        autoLoad: true,
        tpl: new Ext.XTemplate(
            '\
            <tpl for=".">\
                <div class="x-combo-list-item">\
                    <span>\
                        <small>({id})</small>\
                        <b>{name}</b>\
                    </span>\
                </div>\
            </tpl>',
            {compiled: true}
        ),
    });
    dartCRM.combo.ms2Status.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM.combo.ms2Status, MODx.combo.ComboBox);
Ext.reg('dartcrm-combo-ms2status', dartCRM.combo.ms2Status);

dartCRM.combo.orderField = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        url: dartCRM.config.connector_url,
        baseParams: {
            action: 'mgr/system/order_field/getlist',
        },
        name: 'field',
        hiddenName: 'field',
        fields: ['val', 'name'],
        mode: 'remote',
        displayField: 'name',
        fieldLabel: _('dartcrm_order_field'),
        valueField: 'val',
        editable: true,
        pageSize: 10,
        anchor: '99%',
        allowBlank: true,
        autoLoad: true,
        tpl: new Ext.XTemplate(
            '\
            <tpl for=".">\
                <div class="x-combo-list-item">\
                    <span>\
                        <b>{name}</b>\
                    </span>\
                </div>\
            </tpl>',
            {compiled: true}
        ),
    });
    dartCRM.combo.orderField.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM.combo.orderField, MODx.combo.ComboBox);
Ext.reg('dartcrm-combo-orderfield', dartCRM.combo.orderField);