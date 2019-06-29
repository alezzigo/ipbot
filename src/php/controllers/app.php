<?php
/**
 * App Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/models/app.php');

class AppController extends AppModel {

/**
 * API for data retrieval
 * @todo Use tokens for API request authentication with user auth, containable queries
 *
 * @return array Response
 */
	public function api() {
		return $this->_request($_POST);
	}

}

$controller = new AppController();
$data = $controller->route();
