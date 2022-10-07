<?php

class amoAuth
{
	/** @var modX $modx */
	private $modx;
	public $token;
	public $authorized = false;
	private $tools;
	private $config = [];

	/**
	 * Auth constructor.
	 * @param $modx
	 * @param $tools
	 */
	public function __construct($modx, $utils)
	{
		$this->modx = $modx;
		$this->utils = $utils;

		$this->config = [
			'client_id' => $this->modx->getOption('dartcrm_amo_client_id'),
			'client_secret' => $this->modx->getOption('dartcrm_amo_client_secret'),
			'client_code' => $this->modx->getOption('dartcrm_amo_client_code'),
			'site_url' => $this->modx->getOption('site_url')
		];
	}

	/**
	 * Проверка наличия актуального токена
	 * @return bool
	 */
	public function checkAuth()
	{
		$access_token = $this->checkToken();
		if (!$access_token) {
			$this->modx->log(xPDO::LOG_LEVEL_ERROR, '[dartCRM] AMO auth error');
			$this->authorized = false;
		} else {
			$this->authorized = true;
			$this->token = $access_token;
		}

		return $this->authorized;
	}

	/**
	 * Проверка существования токена
	 * @return string
	 */
	private function checkToken()
	{
		$this->modx->cacheManager->refresh(['system_settings' => ['dartcrm_amo_token_field']]);
		$data = $this->modx->getOption('dartcrm_amo_token_field');
		if (!empty($data)) {
			$data = json_decode($data, true);
			$expires = $data['expires_in'];

			if ($expires <= time()) {
				//Время жизни токена истекло - обновляю токен
				$accessToken = $this->refreshToken($data['refresh_token']);
				if ($accessToken) {
					return $accessToken;
				}
			}

			$refresh_token_lifetime = $data['refresh_token_lifetime'];
			if ($refresh_token_lifetime <= time()) {
				$this->modx->log(xPDO::LOG_LEVEL_ERROR, '[dartCRM] AMO: истек срок жизни refresh Токена. Требуется заново создать токен');
			}
			return $data['access_token'];
		}
		return $this->getToken();
	}

	/**
	 * Получение Токена из API AmoCRM
	 * @return string
	 */
	private function getToken()
	{
		$link = $this->utils->prepareLink('/oauth2/access_token');
		$data = [
			'client_id' => $this->config['client_id'],
			'client_secret' => $this->config['client_secret'],
			'code' => $this->config['client_code'],
			'redirect_uri' => $this->config['site_url'],
			'grant_type' => 'authorization_code'
		];

		$accessToken = $this->utils->sendCURL($link, $data, 'POST');

		if ($accessToken) {
			$this->saveToken($accessToken);
			$this->clearClientCode();
			return $accessToken['access_token'];
		}
	}

	/**
	 * Сохранение данных о токене в системной настройке
	 * @param array $accessToken
	 */
	private function saveToken($accessToken)
	{
		$accessToken['expires_in'] = time() + $accessToken['expires_in'];
		$lifetime = strtotime('3 month');
		$accessToken['refresh_token_lifetime'] = $lifetime;

		$setting = $this->modx->getObject('modSystemSetting', array('key' => 'dartcrm_amo_token_field'));
		$setting->set('value', json_encode($accessToken));
		$setting->save();
	}

	/**
	 * Обновление истекшего токена
	 * @param $refresh_token
	 * @return bool|string
	 */
	private function refreshToken($refresh_token)
	{
		$link = $this->utils->prepareLink('/oauth2/access_token');

		$data = [
			'client_id' => $this->config['client_id'],
			'client_secret' => $this->config['client_secret'],
			'code' => $this->config['client_code'],
			'grant_type' => 'refresh_token',
			'refresh_token' => $refresh_token,
			'redirect_uri' => $this->config['site_url']
		];

		$accessToken = $this->utils->sendCURL($link, $data, 'POST');

		if ($accessToken) {
			$this->saveToken($accessToken);
			return $accessToken['access_token'];
		}
		return false;
	}

	private function clearClientCode()
	{
		$setting = $this->modx->getObject('modSystemSetting', array('key' => 'dartcrm_amo_client_code'));
		$setting->set('value', '');
		$setting->save();
	}
}