<?php

class dartCRM
{
    /** @var modX $modx */
    public $modx;


    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;
		$corePath = $this->modx->getOption('dartcrm_core_path', $config, $this->modx->getOption('core_path') . 'components/dartcrm/');
		$assetsUrl = $this->modx->getOption('dartcrm_assets_url', $config, $this->modx->getOption('assets_url') . 'components/dartcrm/');
		$assetsPath = $this->modx->getOption('dartcrm_assets_path', $config, $this->modx->getOption('base_path') . 'assets/components/dartcrm/');

        $this->config = array_merge([
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'processorsPath' => $corePath . 'processors/',
			'version' => '0.0.2',

            'connectorUrl' => $assetsUrl . 'connector.php',
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
        ], $config);

        $this->modx->addPackage('dartcrm', $this->config['modelPath']);
        $this->modx->lexicon->load('dartcrm:default');
    }

	/**
	 * Initializes component into different contexts.
	 *
	 * @param string $ctx The context to load. Defaults to web.
	 * @param array $scriptProperties Properties for initialization.
	 *
	 * @return bool
	 */
	public function initialize($ctx = 'web', $scriptProperties = array())
	{
		if (isset($this->initialized[$ctx])) {
			return $this->initialized[$ctx];
		}
		$this->config = array_merge($this->config, $scriptProperties);
		$this->config['ctx'] = $ctx;
		$this->modx->lexicon->load('dartcrm:default');
		$load = $this->loadServices($ctx);
		if($this->modx->getOption('dartcrm_amo_active')){
			$this->amo = new Amo($this->modx, $this);
		}
		$this->initialized[$ctx] = $load;

		return $load;
	}


	/**
	 * @param string $ctx
	 *
	 * @return bool
	 */
	public function loadServices($ctx = 'web')
	{
		// Default classes
		if (!class_exists('amo')) {
			require_once dirname(__FILE__) . '/handlers/amo/amo.class.php';
		}
		// link ms2
		if(is_dir($this->modx->getOption('core_path').'components/minishop2/model/minishop2/')) {
			$this->ms2 = $this->modx->getService('miniShop2');
			if ($this->ms2 instanceof miniShop2) {
				$this->ms2->initialize($ctx);
				return true;
			}
		}
		return true;
	}

	/**
	 * Shorthand for original modX::invokeEvent() method with some useful additions.
	 *
	 * @param $eventName
	 * @param array $params
	 * @param $glue
	 *
	 * @return array
	 */
	public function invokeEvent($eventName, array $params = array(), $glue = '<br/>')
	{
		if (isset($this->modx->event->returnedValues)) {
			$this->modx->event->returnedValues = null;
		}

		$response = $this->modx->invokeEvent($eventName, $params);
		if (is_array($response) && count($response) > 1) {
			foreach ($response as $k => $v) {
				if (empty($v)) {
					unset($response[$k]);
				}
			}
		}

		$message = is_array($response) ? implode($glue, $response) : trim((string)$response);
		if (isset($this->modx->event->returnedValues) && is_array($this->modx->event->returnedValues)) {
			$params = array_merge($params, $this->modx->event->returnedValues);
		}

		return array(
			'success' => empty($message),
			'message' => $message,
			'data' => $params,
		);
	}

	public function log($message = '', $data = array()){
		$this->modx->log(xPDO::LOG_LEVEL_ERROR, $message.':\n\r'.print_r($data, 1), array(
			'target' => 'FILE',
			'options' => array(
				'filename' => 'dartcrm.log'
			)
		));
	}
}