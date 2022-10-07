<?php

class dartCRMFieldsGetProcessor extends modObjectGetProcessor
{
    public $objectType = 'crmFields';
    public $classKey = 'crmFields';
    public $languageTopics = ['dartcrm:default'];
    //public $permission = 'view';


    /**
     * We doing special check of permission
     * because of our objects is not an instances of modAccessibleObject
     *
     * @return mixed
     */
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }

        return parent::process();
    }

}

return 'dartCRMFieldsGetProcessor';