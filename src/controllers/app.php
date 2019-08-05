<?php
/**
 * App Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class AppController extends AppModel {

/**
 * Authenticate request parameters
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return array $parameters Parameters
 */
	public function authenticate($table, $parameters = array()) {
		if (empty($parameters)) {
			$parameters['action'] = $action = array_shift(array_reverse(explode('/', str_replace('.php', '', $_SERVER['SCRIPT_NAME']))));
			$parameters['keys']['users'] = $this->keys['users'];
		}

		if (
			!empty($this->permissions[$table][$parameters['action']]['group']) &&
			!($parameters['user'] = $this->_authenticate('users', $parameters))
		) {
			$parameters['redirect'] = $this->settings['base_url'] . '/#login';
		}

		return $parameters;
	}

}
