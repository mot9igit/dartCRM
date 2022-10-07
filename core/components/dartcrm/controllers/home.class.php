<?php

/**
 * The home manager controller for dartCRM.
 *
 */
class dartCRMHomeManagerController extends modExtraManagerController
{
    /** @var dartCRM $dartCRM */
    public $dartCRM;


    /**
     *
     */
    public function initialize()
    {
		$corePath = $this->modx->getOption('dartcrm_core_path', array(), $this->modx->getOption('core_path') . 'components/dartcrm/');
        $this->dartCRM = $this->modx->getService('dartCRM', 'dartCRM', $corePath . 'model/');
		$this->dartCRM->initialize();
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return ['dartcrm:default'];
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('dartcrm');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->dartCRM->config['cssUrl'] . 'mgr/main.css');
        $this->addJavascript($this->dartCRM->config['jsUrl'] . 'mgr/dartcrm.js');
        $this->addJavascript($this->dartCRM->config['jsUrl'] . 'mgr/misc/utils.js');
        $this->addJavascript($this->dartCRM->config['jsUrl'] . 'mgr/misc/combo.js');
        $this->addJavascript($this->dartCRM->config['jsUrl'] . 'mgr/widgets/fields.grid.js');
        $this->addJavascript($this->dartCRM->config['jsUrl'] . 'mgr/widgets/fields.windows.js');
        $this->addJavascript($this->dartCRM->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->dartCRM->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addHtml('<script type="text/javascript">
        dartCRM.config = ' . json_encode($this->dartCRM->config) . ';
        dartCRM.config.connector_url = "' . $this->dartCRM->config['connectorUrl'] . '";
        Ext.onReady(function() {MODx.load({ xtype: "dartcrm-page-home"});});
        </script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $this->content .= '<div id="dartcrm-panel-home-div"></div>';

        return '';
    }
}