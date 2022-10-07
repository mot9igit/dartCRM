<?php


class slProductGetListProcessor extends modObjectGetListProcessor
{
	public $classKey = 'modResource';
	public $defaultSortField = 'id';


	/**
	 * @param xPDOQuery $c
	 *
	 * @return xPDOQuery
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c)
	{
		$c->leftJoin('msProductData', 'msProductData', '`modResource`.`id` = `msProductData`.`id`');

		$id = $this->getProperty('id');
		if (!empty($id) and $this->getProperty('combo')) {
			$c->sortby("FIELD (modResource.id, {$id})", "DESC");
		}
		$c->where(array(
			'modResource.class_key:=' => "msProduct"
		));
		$query = $this->getProperty('query', '');
		if (!empty($query)) {
			$c->where(array(
				'modResource.pagetitle:LIKE' => "%{$query}%",
				'OR:msProductData.article:LIKE' => "%{$query}%"
			));
		}

		return $c;
	}


	/**
	 * @param xPDOQuery $c
	 *
	 * @return xPDOQuery
	 */
	public function prepareQueryAfterCount(xPDOQuery $c)
	{
		$c->select($this->modx->getSelectColumns('modResource', 'modResource'));
		$c->select($this->modx->getSelectColumns('msProductData', 'msProductData', '', array('article', 'price')));

		return $c;
	}

	public function prepareRow(xPDOObject $object)
	{
		$array = $object->toArray();
		//$this->modx->log(1, print_r($array, 1));
		if ($this->getProperty('combo')) {
			$array = array(
				'id' => $array['id'],
				'pagetitle' => $array['pagetitle'],
				'article' => $array['article'],
				'price' => $array['price'],
			);
		}

		return $array;
	}
}

return 'slProductGetListProcessor';