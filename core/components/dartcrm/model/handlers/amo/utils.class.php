<?php

class amoUtils{

	/** @var modX $modx */
	public $modx;
	/** @var amo $amo */
	public $amo;
	public $auth;
	public $account_data;

	function __construct(modX &$modx, amo &$amo)
	{
		$this->modx = $modx;
		$this->amo = $amo;

		$this->config = array_merge(array(
			'domain' => $this->modx->getOption('dartcrm_amo_domain'),
			'protocol' => 'https',
			'account' => $this->modx->getOption('dartcrm_amo_account'),
		), $this->amo->config);

		$this->auth = new amoAuth($this->modx, $this);
		$this->account_data = $this->updateAccountConfig();
		//$this->modx->log(1, print_r($account_data['custom_fields']['contacts'], 1));
	}

	/**
	 * Обновление свойств аккаунта
	 */
	public function updateAccountConfig()
	{
		$account = $this->sendRequest('/api/v4/account', array(), 'GET');
		$this->account_config = $account;
		$leads_custom_fields = $this->getCustomFields($type = 'leads');
		if ($leads_custom_fields) {
			$this->account_config['custom_fields']['leads'] = $leads_custom_fields;
			// type 1 это сделка
			$this->checkFields($leads_custom_fields, 1);
		}
		$contacts_custom_fields = $this->getCustomFields($type = 'contacts');
		if ($contacts_custom_fields) {
			$this->account_config['custom_fields']['contacts'] = $contacts_custom_fields;
			// type 2 это контакт
			$this->checkFields($contacts_custom_fields, 2);
		}

		if($this->modx->getOption("dartcrm_amo_order_link_products")){
			$response = $this->checkProducts();
			if($response['catalog_id']){
				$c_id = $this->modx->getOption("dartcrm_amo_order_products_catalog");
				if($c_id != $response['catalog_id']){
					$setting = $this->modx->getObject('modSystemSetting', array("key" => "dartcrm_amo_order_products_catalog"));
					$setting->set('value', $response['catalog_id']);
					$setting->save();
					$this->modx->cacheManager->refresh(array('dartcrm_amo_order_products_catalog' => array()));
				}
				$type = 'catalogs/'.$response['catalog_id'];
				$products_custom_fields = $this->getCustomFields($type);
				if ($products_custom_fields) {
					$this->account_config['custom_fields']['products'] = $products_custom_fields;
					// type 3 это товар
					$this->checkFields($products_custom_fields, 3);
				}
			}
		}

		$pipelines = $this->getPipelinesForAccount();
		if ($pipelines) {
			$this->account_config['pipelines'] = $pipelines;
		}

		return $this->account_config;
	}

	public function checkProducts(){
		//6207
		/*
		 * SKU
		 * description
		 * PRICE
		 * GROUP
		 * EXTERNAL_ID
		 */
		$url = '/api/v2/products_settings/';
		$response = $this->sendRequest($url, array("enabled" => true), 'POST');
		return $response;
	}

	/**
	 * Функция проверки полей (сделка и контакт) и обновления
	 */
	public function checkFields($fields, $type = 1){
		$existFields = array();
		foreach($fields as $key => $value){
			$criteria = array(
				"crm" => 1,
				"crm_id" => $value['id'],
				"type" => $type
			);
			$obj = $this->modx->getObject("crmFields", $criteria);
			if(!isset($value['code'])){
				$value['code'] = '';
			}
			if(!$obj){
				// если поле не найдено - создаем
				$obj = $this->modx->newObject("crmFields");
				$obj->set("crm", 1);
				$obj->set("crm_id", $value['id']);
				$obj->set("name", $value['name']);
				$obj->set("type", $type);
				$obj->set("code", $value['code']);
				$obj->set("active", 1);
				if($value['enums']){
					$obj->set("enums", json_encode($value['enums'], JSON_UNESCAPED_UNICODE));
				}
			}else{
				// иначе обновляем поля
				$obj->set("name", $value['name']);
				$obj->set("code", $value['code']);
				if($value['enums']){
					$obj->set("enums", json_encode($value['enums'], JSON_UNESCAPED_UNICODE));
				}else{
					$obj->set("enums", "");
				}
			}
			$obj->save();
			$existFields[] = $obj->get('id');
		}
		// удаляем поля, которых нет в CRM
		$query = $this->modx->newQuery("crmFields");
		$query->where(array(
			"crm:=" => 1,
			"AND:type:=" => $type,
			"AND:id:NOT IN" => $existFields
		));
		$query->prepare();
		$objects = $this->modx->getCollection("crmFields", $query);
		foreach($objects as $object){
			$object->remove();
		}
	}

	/**
	 * Запрос кастомных полей в AMOCRM
	 *
	 * @param string $type
	 *
	 * @return array|false
	 * @internal param string $type
	 */
	public function getCustomFields($type = 'leads')
	{
		$result = $this->sendRequest('/api/v4/' . $type . '/custom_fields', array(), 'GET');
		if (!empty($result) && $result['_total_items'] > 0) {

			$output = [];
			foreach ($result['_embedded']['custom_fields'] as $field) {
				$output[mb_strtolower($field['id'])] = $field;
			}
			if($result['_page_count'] > 1) {
				for ($i = $result['_page'] + 1; $i <= $result['_page_count']; $i++) {
					$result = $this->sendRequest('/api/v4/' . $type . '/custom_fields', array('page' => $i), 'GET');
					foreach ($result['_embedded']['custom_fields'] as $field) {
						$output[mb_strtolower($field['id'])] = $field;
					}
				}
			}

			return $output;
		}
		return false;
	}

	public function getPipelinesForAccount()
	{
		$result = $this->sendRequest('/api/v4/leads/pipelines', array(), 'GET');
		if (!empty($result) && $result['_total_items'] > 0) {
			$output = [];
			foreach ($result['_embedded']['pipelines'] as $item) {
				$output[$item['id']] = [
					'id' => $item['id'],
					'name' => $item['name'],
					'statuses' => $item['_embedded']['statuses']
				];
			}

			return $output;
		}
		return false;
	}

	/**
	 * Подготовка ссылки для отправки и/или получения данных
	 *
	 * @param string $url
	 *
	 * @return string
	 * @internal param string $type
	 */
	public function prepareLink($url = '')
	{
		$link = '';
		if ($this->config['protocol'] && $this->config['account']) {
			$link = $this->config['protocol'] . '://' . $this->config['account'] . '.' . $this->config['domain'] . $url;
		}
		return $link;
	}

	/**
	 * Отправка запроса
	 *
	 * @param $link
	 * @param $data
	 * @param string $method
	 *
	 * @return array
	 */
	public function sendRequest($link, $data, $method = 'POST')
	{
		$authorized = $this->auth->checkAuth();
		if ($authorized) {
			$link = $this->prepareLink($link);
			return $this->sendCURL($link, $data, $method);
		}
	}

	/*
	 * Проверка email.
	 *
	*/
	public function formatEmail($email){
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$data = $email;
		} else {
			$data = false;
		}
		return $data;
	}

	/*
	 * Проверка телефона. Работает только для России.
	 *
	*/
	public function formatPhone($phone){
		$phone = mb_eregi_replace("[^0-9]", '', $phone);
		if(strlen($phone) > 9){
			$data = '+7'.substr($phone, -10);
		}else{
			$data = false;
		}
		return $data;
	}


	/**
	 * Отправка запроса
	 *
	 * @param string $link
	 * @param array $data
	 * @param string $method
	 *
	 * @return array
	 * @internal param $post
	 */
	public function sendCURL($link, $data, $method = 'POST')
	{
		$headers = [];
		$headers[] = 'Content-Type:application/json';

		$token = $this->auth->token;
		$authorized = $this->auth->authorized;

		if ($authorized && !empty($token)) {
			$headers[] = 'Authorization: Bearer ' . $token;
		}

		$userAgent = 'amoCRM-API-client/1.0';

		if (!empty($data['grant_type'])) {
			switch ($data['grant_type']) {
				case 'authorization_code':
				case 'refresh_token':
					$userAgent = 'amoCRM-oAuth-client/1.0';
					break;
			}
		}

		if ($method === 'GET') {
			if (!empty($data)) {
				$link = $link . '?' . http_build_query($data);
			}
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curl, CURLOPT_URL, $link);
		if (!empty($headers)) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}

		if ($method === 'GET') {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		}

		if ($method === 'POST') {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}

		if ($method === 'PATCH') {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}

		if ($method === 'DELETE') {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}

		curl_setopt($curl, CURLOPT_HEADER, false);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		$out = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		$code = (int)$code;
		$errors = [
			400 => 'Bad request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not found',
			500 => 'Internal server error',
			502 => 'Bad gateway',
			503 => 'Service unavailable',
		];

		$response = json_decode($out, true);

		try {
			if ($code < 200 || $code > 204) {
				throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
			}
		} catch (\Exception $e) {
			$this->modx->log(1, print_r(array(
				'[dartCRM] AMO: Ошибка запроса',
				$e->getMessage() . ' Код ошибки: ' . $e->getCode(),
				$link,
				$data,
				$response
			), 1));

			return false;
		}
		$this->modx->log(1, print_r($response, 1));
		return $response;
	}

}