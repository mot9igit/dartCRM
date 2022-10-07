<?php
$corePath = $modx->getOption('dartcrm_core_path', null, $modx->getOption('core_path') . 'components/dartcrm/');
$dartCRM = $modx->getService('dartCRM', 'dartCRM', $corePath . 'model/', array());
if (!$dartCRM) {
	$modx->log(xPDO::LOG_LEVEL_ERROR, "Could not load dartCRM class!");
}else {
	$dartCRM->initialize();
}
/** @var modX $modx */
switch ($modx->event->name) {
	case 'msOnCreateOrder':
		$dartCRM->amo->processOrder($msOrder);
		break;
	case 'OnBeforeDocFormDelete':
		$class_key = $resource->get("class_key");
		if($class_key == 'msProduct'){
			$response = $dartCRM->amo->deleteProduct($id);
		}
		break;
}