<?php
/** @var modX $modx */
/** @var array $scriptProperties */
/** @var dartCRM $dartCRM */
$corePath = $modx->getOption('dartcrm_core_path', null, $modx->getOption('core_path') . 'components/dartcrm/');
$dartCRM = $modx->getService('dartCRM', 'dartCRM', $corePath . 'model/', $scriptProperties);
if (!$dartCRM) {
	$modx->log(xPDO::LOG_LEVEL_ERROR, "Could not load dartCRM class!");
}else{
	$dartCRM->initialize();
	$name = $modx->getOption('formName', $scriptProperties, 'Заявка с сайта');
	$config = $modx->getOption('dartCRMFields', $scriptProperties, '');
	$pipeline_id = $modx->getOption('pipeline_id', $scriptProperties, '');
	$status_id = $modx->getOption('status_new', $scriptProperties, '');

	// если включена AMO
	if($modx->getOption('dartcrm_amo_active')){
		$formValues = $hook->getValues();
		$c_data = array();
		foreach($formValues as $key => $val){
			if($key == "name"){
				$c_data['contact']['name'] = $val;
			}
			if($key == "phone"){
				$c_data['contact']['phone'] = $val;
			}
			if($key == "email"){
				$c_data['contact']['email'] = $val;
			}
		}
		if(!isset($c_data['contact']['name'])){
			$c_data['contact']['name'] = "Клиент";
		}
		// линкуем контакт
		$out = $dartCRM->amo->linkContact($c_data);
		$data = array(
			"name" => $name,
			"price" => 0,
			"_embedded" => array(
				"contacts" => array(
					array(
						"id" => $out['id']
					)
				)
			)
		);
		if($config){
			$custom_fields = $dartCRM->amo->getFormCustomFields($formValues, $config);
			if(count($custom_fields)){
				$data["custom_fields_values"] = $custom_fields;
			}
		}
		if($pipeline_id){
			$data['pipeline_id'] = (int)$pipeline_id;
		}
		if($status_id){
			$data['status_id'] = (int)$status_id;
		}
		$response = $dartCRM->amo->addFormLead(array($data));
	}
}

return true;