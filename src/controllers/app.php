<?php
/**
 * App Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/models/app.php');

class AppController extends AppModel {

/**
 * API for actions and data retrieval
 *
 * @return array Response
 */
	public function api() {
		$parameters = ((!empty($_POST['json']) && is_string($_POST['json'])) ? json_decode($_POST['json'], true) : array());
		return $this->_request($parameters);
	}

}

$controller = new AppController();
$data = $controller->route();
