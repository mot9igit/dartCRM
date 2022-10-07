<?php

class dartCRMFieldsGetListProcessor extends modObjectGetListProcessor
{
    public $objectType = 'crmFields';
    public $classKey = 'crmFields';
    public $defaultSortField = 'id';
    public $defaultSortDirection = 'DESC';
    //public $permission = 'list';


    /**
     * We do a special check of permissions
     * because our objects is not an instances of modAccessibleObject
     *
     * @return boolean|string
     */
    public function beforeQuery()
    {
        if (!$this->checkPermissions()) {
            return $this->modx->lexicon('access_denied');
        }

        return true;
    }


    /**
     * @param xPDOQuery $c
     *
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $query = trim($this->getProperty('query'));
        if ($query) {
            $c->where([
                'name:LIKE' => "%{$query}%",
                'OR:crm_id:LIKE' => "%{$query}%",
				'OR:field:LIKE' => "%{$query}%",
				'OR:enums:LIKE' => "%{$query}%",
            ]);
        }
        if($this->modx->getOption("dartcrm_amo_active")){
			$c->where(array("crm" => 1));
		}

        return $c;
    }


    /**
     * @param xPDOObject $object
     *
     * @return array
     */
    public function prepareRow(xPDOObject $object)
    {
        $array = $object->toArray();
        $array['actions'] = [];

        // Edit
        $array['actions'][] = [
            'cls' => '',
            'icon' => 'icon icon-edit',
            'title' => $this->modx->lexicon('dartcrm_field_update'),
            //'multiple' => $this->modx->lexicon('dartcrm_items_update'),
            'action' => 'updateField',
            'button' => true,
            'menu' => true,
        ];

        return $array;
    }

}

return 'dartCRMFieldsGetListProcessor';