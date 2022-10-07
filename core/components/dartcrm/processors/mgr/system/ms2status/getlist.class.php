<?php


class slMS2StatusGetListProcessor extends modObjectGetListProcessor
{
	public $classKey = 'msOrderStatus';
	public $defaultSortField = 'id';


	/**
	 * @param xPDOQuery $c
	 *
	 * @return xPDOQuery
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c)
	{
		$id = $this->getProperty('id');
		$query = $this->getProperty('query', '');
		if (!empty($query)) {
			$c->where(array(
				'name:LIKE' => "%{$query}%",
				'OR:description:LIKE' => "%{$query}%"
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
		return $c;
	}

	public function prepareRow(xPDOObject $object)
	{
		$array = $object->toArray();
		//$this->modx->log(1, print_r($array, 1));
		if ($this->getProperty('combo')) {
			$array = array(
				'id' => $array['id'],
				'name' => $array['name'],
				'description' => $array['description']
			);
		}

		return $array;
	}
}

return 'slMS2StatusGetListProcessor';