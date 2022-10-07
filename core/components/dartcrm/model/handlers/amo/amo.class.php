<?php

class amo{

	/** @var modX $modx */
	public $modx;
	public $dartCRM;
	public $utils;


	/**
	 * @param modX $modx
	 * @param array $config
	 */
	function __construct(modX &$modx, dartCRM &$dartCRM, array $config = [])
	{
		$this->modx =& $modx;
		$corePath = $this->modx->getOption('dartcrm_core_path', $config, $this->modx->getOption('core_path') . 'components/dartcrm/');
		$assetsUrl = $this->modx->getOption('dartcrm_assets_url', $config, $this->modx->getOption('assets_url') . 'components/dartcrm/');
		$assetsPath = $this->modx->getOption('dartcrm_assets_path', $config, $this->modx->getOption('base_path') . 'assets/components/dartcrm/');

		$this->config = array_merge([
			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'processorsPath' => $corePath . 'processors/',

			'connectorUrl' => $assetsUrl . 'connector.php',
			'assetsUrl' => $assetsUrl,
			'cssUrl' => $assetsUrl . 'css/',
			'jsUrl' => $assetsUrl . 'js/',
		], $config);

		$this->loadServices();
		$this->dartCRM = $dartCRM;
		$this->utils = new amoUtils($this->modx, $this);
		$this->auth = $this->utils->auth;
		$this->auth->checkAuth();
	}

	/**
	 * @param string $ctx
	 *
	 * @return bool
	 */
	public function loadServices()
	{
		// Default classes
		if (!class_exists('amoAuth')) {
			require_once dirname(__FILE__) . '/auth.class.php';
		}
		if (!class_exists('amoUtils')) {
			require_once dirname(__FILE__) . '/utils.class.php';
		}
		return true;
	}

	/**
	 * Редактируем поле
	 * $data
	 */
	public function updateField($data){
		$entity = false;
		$result['response'] = false;
		$obj = $this->modx->getObject("crmFields", $data['id']);
		if($obj){
			$data = array_merge($obj->toArray(), $data);
			if($data['type'] == 1){
				$entity = 'leads';
			}
			if($data['type'] == 2){
				$entity = 'contacts';
			}
			if($entity){
				if($data['id']){
					$link = '/api/v4/'.$entity.'/custom_fields/'.$data['crm_id'];
					// в нашем случае, меняем только NAME
					$dt = array(
						"name" => $data["name"]
					);
					// если поле enums, то обязательно передаем значения
					if($data["enums"]){
						$dt["enums"] = json_decode($data['enums'], 1);
					}
					$result['response'] = $this->utils->sendRequest($link, $dt, 'PATCH');
				}
			}
		}

		return $result['response'];
	}

	/**
	 * Формируем кастомные поля (позже будет работать на сопоставлении полей)
	 * $response['id']
	 */
	public function prepareCustomFields($data){
		$account = $this->utils->account_config;
		$custom_values = array();
		foreach ($account['custom_fields']['contacts'] as $field) {
			$tmp = array();
			if ($field['code'] == 'PHONE') {
				$phone = $this->utils->formatPhone($data['contact']['phone']);
				if ($phone) {
					$tmp['field_id'] = $field['id'];
					$tmp['values'] = array(
						array(
							"value" => $phone,
							"enum_code" => "WORK"
						)
					);
					$custom_values[] = $tmp;
				}
			}
			if ($field['code'] == 'EMAIL') {
				$email = $this->utils->formatEmail($data['contact']['email']);
				if ($email) {
					$tmp['field_id'] = $field['id'];
					$tmp['values'] = array(
						array(
							"value" => $email,
							"enum_code" => "WORK"
						)
					);
					$custom_values[] = $tmp;
				}
			}
		}
		return $custom_values;
	}

	/*
	 * Находит существующий контакт по email или phone
	 * $response['id']
	 */
	public function checkExistContact($data_req)
	{
		$result = array();

		$link = '/api/v4/contacts';
		$data = [];
		$data['page'] = 1;
		$data['limit'] = 2;
		if (!empty($data_req['contact']['phone'])) {
			$phone = $this->utils->formatPhone($data_req['contact']['phone']);
			if($phone){
				$data['query'] = $phone;
				$result['request'] = $data;
				$result['response'] = $this->utils->sendRequest($link, $data, 'GET');
				if (!empty($result['response']['_embedded'])) {
					$contact = array_shift($result['response']['_embedded']['contacts']);
					$result['response'] = $contact;
					return $result['response'];
				}
			}
		}
		if (!empty($data_req['contact']['email'])) {
			$email = $this->utils->formatEmail($data_req['contact']['email']);
			if($email){
				$data['query'] = $email;
				$result['request'] = $data;
				$result['response'] = $this->utils->sendRequest($link, $data, 'GET');
				if (!empty($result['response']['_embedded'])) {
					$contact = array_shift($result['response']['_embedded']['contacts']);
					$result['response'] = $contact;
					return $result['response'];
				}
			}
		}
		return false;
	}

	/**
	 * Обновляет контакт
	 * $response['id']
	 */
	public function updateContact($contact, $data, $add_data = array()){
		if($add_data){
			$custom_values = $add_data;
		}else{
			$custom_values = $this->prepareCustomFields($data);
		}
		$senddata = array(
			"id" => $contact['id'],
			//"name" => $data['contact']['name'], Не обновляем!
			//"responsible_user_id" => $this->modx->getOption("dartcrm_amo_responsible_user_id"),
			"custom_fields_values" => $custom_values
		);

		$result['request'] = $senddata;
		$result['response'] = $this->utils->sendRequest('/api/v4/contacts/'.$contact['id'], $senddata, 'PATCH');
		if (!empty($result['response'])) {
			return $result['response'];
		}else{
			return false;
		}
	}

	/*
	 * Создает контакт
	 * $response['id']
	 */
	public function addContact($data, $add_data = array()){
		if($add_data){
			$custom_values = $add_data;
		}else{
			$custom_values = $this->prepareCustomFields($data);
		}
		$senddata = array();
		$senddata[] = array(
			"name" => $data['contact']['name'],
			//"responsible_user_id" => $this->modx->getOption("dartcrm_amo_responsible_user_id"),
			"custom_fields_values" => $custom_values
		);

		$result['request'] = $senddata;
		$result['response'] = $this->utils->sendRequest('/api/v4/contacts', $senddata, 'POST');
		if (!empty($result['response']['_embedded'])) {
			$contact = array_shift($result['response']['_embedded']['contacts']);
			$result['response'] = $contact;
			return $result['response'];
		}
		return false;

	}

	/*
	 * Возвращает найденый или созданный контакт
	 * $response['id']
	 */
	public function linkContact($data, $add_data = array()){
		$contact = $this->checkExistContact($data);
		if($contact){
			// если контакт найден - обновляем
			$response = $this->updateContact($contact, $data, $add_data);
		}else {
			// иначе - создаем
			$response = $this->addContact($data, $add_data);
		}
		return $response;
	}

	public function addFormLead($data, $config = array()){
		$link = '/api/v4/leads';
		foreach($data as $key => $val){
			if(!isset($data[$key]['pipeline_id'])){
				$data[$key]['pipeline_id'] = (int)$this->modx->getOption('dartcrm_amo_form_pipeline_id');
			}
			if(!isset($data[$key]['status_id'])){
				$data[$key]['status_id'] = (int)$this->modx->getOption('dartcrm_amo_form_status_new');
			}
		}

		$result['request'] = $data;
		$result['response'] = $this->utils->sendRequest($link, $data, 'POST');
		return $result['response'];
	}

	/**
	 * Function parse line with fields and links without ENUM fields
	 *
	 * @param $config
	 *
	 */

	public function getFormCustomFields($data, $config){
		$config_array = array();
		$config_pars = explode("||", $config);
		foreach($config_pars as $pars){
			$field = explode("==", $pars);
			$config_array[$field[0]] = $field[1];
		}
		$custom_fields = array();
		foreach($config_array as $key => $field){
			if(in_array($key, array('name', 'phone', 'email')))
				continue;
			if(isset($data[$key])){
				$tmp = array();
				$tmp['field_id'] = (int) $field;
				$tmp['values'] = array(
					array(
						"value" => (string) $data[$key]
					)
				);
				$custom_fields[] = $tmp;
			}
		}
		return $custom_fields;
	}

	/**
	 * Process order
	 *
	 * @param $msOrder
	 * @return array|bool
	 */

	public function processOrder($msOrder){
		$link = '/api/v4/leads';
		$order_data = array(
			'order' => $msOrder->toArray(),
			'delivery' => $msOrder->Delivery->toArray(),
			'payment' => $msOrder->Payment->toArray(),
			'address' => $msOrder->Address->toArray(),
			'user_obj' => $msOrder->User->toArray(),
			'user' => $msOrder->UserProfile->toArray(),
		);
		if(isset($order_data['address']['sl_data'])){
			$order_data['address']['sl_data'] = json_decode($order_data['address']['sl_data'], 1);
		}
		if($this->modx->getOption("dartcrm_amo_order_pipeline_id")){
			// Обработка контакта
			$custom_values = $this->getCustomValues($order_data, 2);
			$c_data = array(
				"contact" => array(
					"name" => $order_data['address']['receiver'],
				)
			);
			foreach($custom_values as $key => $c_value){
				if($c_value['field_code'] == "PHONE"){
					$c_data['contact']['phone'] = $c_value['values'][0]['value'];
				}
				if($c_value['field_code'] == "EMAIL"){
					$c_data['contact']['email'] = $c_value['values'][0]['value'];
				}
			}
			$out = $this->linkContact($c_data, $custom_values);
			// Обработка сделки
			$custom_values = $this->getCustomValues($order_data, 1);
			$deal_data = array(
				"name" => "Заказ с сайта ".$order_data['order']['num'],
				"price" => (int) $order_data['order']['cost'],
				"pipeline_id" => (int) $this->modx->getOption("dartcrm_amo_order_pipeline_id"),
				"custom_fields_values" => $custom_values,
				"_embedded" => array(
					"contacts" => array(
						array(
							"id" => $out['id']
						)
					)
				)
			);
			if($this->modx->getOption("dartcrm_amo_order_status_new")){
				$deal_data['status_id'] = (int) $this->modx->getOption("dartcrm_amo_order_status_new");
			}
			if($this->modx->getOption("dartcrm_amo_responsible_user_id")){
				$deal_data['responsible_user_id'] = (int) $this->modx->getOption("dartcrm_amo_responsible_user_id");
			}
			$this->dartCRM->log("dartCRM AMO LEAD:", $deal_data);
			$result = array();
			$result['request'] = $deal_data;
			$result['response'] = $this->utils->sendRequest($link, array($deal_data), 'POST');
			if($result['response']){
				$order_link = $this->linkOrder($order_data, $result['response']);
				// привяжем товары
				$products = $msOrder->getMany('Products');
				foreach($products as $product){
					$product_id = $product->get('product_id');
					$link = $this->modx->getObject("crmProductLinks", array("product_id" => $product_id));
					$crm_product = 0;
					if($link){
						$crm_product = $link->get("crm_id");
					}else{
						$response = $this->addProduct($product_id);
						if($response['link']){
							$crm_product = $response['link']["crm_id"];
						}
					}
					if($crm_product && $order_link){
						// если все найдено
						$prod = array(
							"crm_id" => $crm_product,
							"count" => $product->get('count')
						);
						$prod_res = $this->linkCRMProducts($order_link['crm_id'], $prod);
						$result['response']["products"][] = $prod_res;
					}
				}
			}
			return $result['response'];
		}else{
			$this->modx->log(xPDO::LOG_LEVEL_ERROR, "[dartCRM] Укажите pipeline_id для заказов в системных настройках либо отключите AMOCRM.");
		}
		return false;
	}

	public function linkCRMProducts($lead, $product){
		$link = '/api/v4/leads/'.$lead.'/link';
		$data = array(
			"to_entity_id" => (int) $product["crm_id"],
			"to_entity_type" => "catalog_elements",
			"metadata" => array(
				"quantity" => (int) $product["count"],
				"catalog_id" => (int) $this->modx->getOption("dartcrm_amo_order_products_catalog")
			)
		);
		$result['request'] = $data;
		$result['response'] = $this->utils->sendRequest($link, array($data), 'POST');
		return $result['response'];
	}

	public function getCustomValues($order_data, $type){
		// товары не будут работать с ENUM полями !!!
		$criteria = array(
			"crm" => 1,
			"type" => $type
		);
		$contact_fields = $this->modx->getCollection("crmFields", $criteria);
		$custom_values = array();
		foreach($contact_fields as $f){
			$field = $f->toArray();
			if($field['field']){
				$tmp = array();
				$farr = explode(".", $field['field']);
				if(count($farr) > 1){
					$key = $farr[0];
					$ff = $farr[1];
				}else{
					$key = 'order';
					$ff = $farr[0];
				}
				if($order_data[$key][$ff]) {
					if($type == 3){
						$tmp['id'] = $field['crm_id'];
					}else{
						$tmp['field_id'] = $field['crm_id'];
						if ($field['code']) {
							$tmp['field_code'] = $field['code'];
						}
					}
					if ($field['properties']) {
						// TODO: сделать отмечаемым поле ENUM в админке (может доп таблица?)
						$props = json_decode($field['properties'], 1);
						if ($props[0]['enums']) {
							if (is_numeric($props[0]['enums'])) {
								$type = 'enum_id';
								$props[0]['enums'] = (int) $props[0]['enums'];
							} else {
								$type = 'enum_code';
							}
							$tmp['values'] = array(
								array(
									"value" => $order_data[$key][$ff],
									$type => $props[0]['enums']
								)
							);
						}
					} else {
						$tmp['values'] = array(
							array(
								"value" => (string) $order_data[$key][$ff]
							)
						);
					}
					$custom_values[] = $tmp;
				}
			}else{
				// на случай, если указаны properties
				if($field['properties']){
					$value = $this->getOrderProperties($field['properties'], $order_data['order']['properties']);
					if($value){
						$tmp = array();
						$tmp['field_id'] = $field['crm_id'];
						$tmp['values'] = array(
							array(
								"value" => (string) $value
							)
						);
						$custom_values[] = $tmp;
					}
				}
			}
		}
		return $custom_values;
	}

	public function getOrderProperties($config, $data){
		$c = explode('.', $config);
		$field = $data;

		foreach($c as $f){
			if(isset($field[$f])){
				$field = $field[$f];
			}else{
				$field = false;
			}
		}

		return $field;
	}

	public function linkOrder($order_data, $response){
		$obj = $this->modx->newObject("crmOrderLinks");
		if (!empty($response['_embedded'])) {
			$lead = array_shift($response['_embedded']['leads']);
			$lead_id = $lead['id'];
			$order_id = $order_data['order']['id'];
			$obj->set("crm_id", $lead_id);
			$obj->set("order_id", $order_id);
			$obj->save();
			return $obj->toArray();
		}
		return false;
	}

	public function deleteProduct($id){
		$response = false;
		$obj = $this->modx->getObject("crmProductLinks", array("product_id" => $id));
		if($obj){
			$crm_id = $obj->get("crm_id");
			if($crm_id){
				$url = '/api/v2/catalog_elements/';
				$data = array();
				$data['delete'] = array($crm_id);
				$response = $this->utils->sendRequest($url, $data, 'POST');
			}
		}
		return $response;
	}

	public function addProduct($id){
		$response = false;
		$resource = $this->modx->getObject("modResource", $id);
		if($resource){
			$product = $this->modx->getObject("msProductData", $id);
			if($product){
				$product_data = array(
					"resource" => $resource->toArray(),
					"product" => $product->toArray()
				);
				$custom_values = $this->getCustomValues($product_data, 3);
				$data['add'][0] = array(
					'catalog_id' => $this->modx->getOption("dartcrm_amo_order_products_catalog"),
					'name' => $product_data['resource']['pagetitle'],
					"custom_fields" => $custom_values
				);
				$url = '/api/v2/catalog_elements/';
				//$this->dartCRM->log("dartCRM AMO LEAD:", $data);
				$response = $this->utils->sendRequest($url, $data, 'POST');
				$link = $this->linkProduct($response, $id);
				$response['link'] = $link;
			}
		}
		return $response;
	}

	public function linkProduct($response, $product_id){
		$obj = $this->modx->newObject("crmProductLinks");
		if (!empty($response['_embedded'])) {
			$crm = array_shift($response['_embedded']['items']);
			$crm_id = $crm['id'];
			$product_id = $product_id;
			$obj->set("crm_id", $crm_id);
			$obj->set("product_id", $product_id);
			$obj->save();
			return $obj->toArray();
		}
		return false;
	}
}