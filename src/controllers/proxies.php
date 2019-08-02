<?php
/**
 * Proxies Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/controllers/app.php');
require_once($config->settings['base_path'] . '/models/proxies.php');

class ProxiesController extends ProxiesModel {

/**
 * Proxies API
 *
 * @return array Response
 */
	public function api() {
		return $this->_request($_POST);
	}

}

$appController = new AppController();
$proxiesController = new ProxiesController();
$data = $proxiesController->route();
