<?php
/**
 * Proxies Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/models/proxies.php');

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

$controller = new ProxiesController();
$data = $controller->route();
