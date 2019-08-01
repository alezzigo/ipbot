<?php
/**
 * Users Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/controllers/app.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/models/users.php');

class UsersController extends UsersModel {

/**
 * Users API
 *
 * @return array Response
 */
	public function api() {
		return $this->_request($_POST);
	}

}

$appController = new AppController();
$usersController = new UsersController();
$data = $usersController->route();
