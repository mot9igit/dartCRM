<?php

class dartCRMFieldsUpdateProcessor extends modObjectUpdateProcessor
{
    public $objectType = 'crmFields';
    public $classKey = 'crmFields';
    public $languageTopics = ['dartcrm'];
    //public $permission = 'save';

	/** @var  dartCRM $dartCRM */
	protected $dartCRM;

	/**
	 * @return bool|null|string
	 */
	public function initialize()
	{
		$this->dartCRM = $this->modx->getService('dartCRM');
		$this->dartCRM->initialize(); // it will be "mgr"

		return parent::initialize();
	}
    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return bool|string
     */
    public function beforeSave()
    {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $id = (int)$this->getProperty('id');
        $name = trim($this->getProperty('name'));
        if (empty($id)) {
            return $this->modx->lexicon('dartcrm_item_err_ns');
        }

        if (empty($name)) {
            $this->modx->error->addField('name', $this->modx->lexicon('dartcrm_item_err_name'));
        } elseif ($this->modx->getCount($this->classKey, ['name' => $name, 'id:!=' => $id])) {
            $this->modx->error->addField('name', $this->modx->lexicon('dartcrm_item_err_ae'));
        }

        $rdata = $this->getProperties();
        if($rdata){
			$response = $this->dartCRM->amo->updateField($rdata);
		}
        return parent::beforeSet();
    }
}

return 'dartCRMFieldsUpdateProcessor';
