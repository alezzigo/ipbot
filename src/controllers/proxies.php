<?php
/**
 * Proxies Controller
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
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

$proxiesController = new ProxiesController();
$data = $proxiesController->route($config->parameters);
