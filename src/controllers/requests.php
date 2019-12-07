<?php
/**
 * Requests Controller
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
require_once($config->settings['base_path'] . '/models/requests.php');

class RequestsController extends RequestsModel {

/**
 * Requests API
 *
 * @return array Response
 */
	public function api() {
		return $this->_request($_POST);
	}

}

$requestsController = new RequestsController();
$data = $requestsController->route($config->parameters);
