var dartCRM = function (config) {
    config = config || {};
    dartCRM.superclass.constructor.call(this, config);
};
Ext.extend(dartCRM, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('dartcrm', dartCRM);

dartCRM = new dartCRM();