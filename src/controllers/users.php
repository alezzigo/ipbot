<?php
/**
 * Users Controller
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */

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

$usersController = new UsersController();
$data = $usersController->route($config->parameters);
