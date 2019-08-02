<?php
/**
 * Users Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class UsersModel extends AppModel {

/**
 * Login user
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return array $response Response data
 */
	public function login($table, $parameters = array()) {
		$message = $defaultMessage = 'Error logging in, please try again.';
		$response = array();

		if (!empty($parameters['data']['email'])) {
			$message = 'Invalid email or password, please try again.';
			$existingUser = $this->find('users', array(
				'conditions' => array(
					'email' => $email = $this->_validateEmailFormat($parameters['data']['email'])
				),
				'limit' => 1
			));

			if (
				!empty($email) &&
				!empty($existingUser['count'])
			) {
				$message = 'Password is required, please try again.';

				if (!empty($parameters['data']['password'])) {
					$message = 'Password must be at least 10 characters, please try again.';

					if (strlen($parameters['data']['password']) >= 10) {
						$message = $defaultMessage;

						if ($this->_passwordVerify($parameters['data']['password'], $existingUser['data'][0])) {
							$message = 'Logged in successfully.';
							$parameters['conditions'] = array(
								'user_id' => $existingUser['data'][0]['id']
							);
							$response['tokens'][$table] = $this->_getToken($parameters);
							unset($existingUser['data'][0]['password']);
							unset($existingUser['data'][0]['password_modified']);
						}
					}
				}
			}
		}

		$response = array_merge(array(
			'count' => !empty($existingUser['count']) ? $existingUser['count'] : 0,
			'data' => !empty($existingUser['data'][0]) ? $existingUser['data'][0] : array(),
			'message' => $message
		), $response);
		return $response;
	}

/**
 * Register user
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return array $response Response data
 */
	public function register($table, $parameters = array()) {
		$message = $defaultMessage = 'Error processing your user registration, please try again.';

		if (!empty($parameters['data']['email'])) {
			$message = 'Invalid email or password, please try again.';
			$existingUser = $this->find('users', array(
				'conditions' => array(
					'email' => $email = $this->_validateEmailFormat($parameters['data']['email'])
				),
				'limit' => 1
			));

			if (
				!empty($email) &&
				empty($existingUser['count'])
			) {
				$message = 'Password and confirmation are required, please try again.';

				if (
					!empty($parameters['data']['password']) &&
					!empty($parameters['data']['confirm_password'])
				) {
					$message = 'Password must be at least 10 characters, please try again.';

					if (strlen($parameters['data']['password']) >= 10) {
						$message = 'Password confirmation doesn\'t match password, please try again.';

						if ($parameters['data']['password'] == $parameters['data']['confirm_password']) {
							$message = $defaultMessage;
							$passwordHash = $this->_passwordHash($parameters['data']['password'], time());
							$user = array(
								'email' => $email,
								'password' => $passwordHash['string'],
								'password_modified' => $passwordHash['modified']
							);
							$this->save('users', array(
								$user
							));
							$user = $this->find('users', array(
								'conditions' => $user,
								'limit' => 1
							));

							if (!empty($user['count'])) {
								$message = 'User account created successfully.';
								return $this->login($table, $parameters);
							}
						}
					}
				}
			}
		}

		$response = array(
			'count' => !empty($user['count']) ? $user['count'] : 0,
			'data' => !empty($user['data'][0]) ? $user['data'][0] : array(),
			'message' => $message
		);
		return $response;
	}

}
