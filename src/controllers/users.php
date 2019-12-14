<?php
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
?>
