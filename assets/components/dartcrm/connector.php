<?php
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
	/** @noinspection PhpIncludeInspection */
	require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
} else {
	require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.core.php';
}
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var dartCRM $dartCRM */
$corePath = $modx->getOption('dartcrm_core_path', null, $modx->getOption('core_path') . 'components/dartcrm/');
$dartCRM = $modx->getService('dartCRM', 'dartCRM', $corePath . 'model/');
$modx->lexicon->load('dartcrm:default');

// handle request
$corePath = $modx->getOption('dartcrm_core_path', null, $modx->getOption('core_path') . 'components/dartcrm/');
$path = $modx->getOption('processorsPath', $dartCRM->config, $corePath . 'processors/');
$modx->getRequest();

/** @var modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest([
    'processors_path' => $path,
    'location' => '',
]);