<?php
/**
 * Users Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/controllers/app.php');
require_once($config->settings['base_path'] . '/models/users.php');

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
