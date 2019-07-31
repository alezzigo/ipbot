<?php
/**
 * Users Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once('../../models/app.php');

class UsersModel extends AppModel {

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

			if (!empty($email = $this->_validateEmailFormat($parameters['data']['email']))) {
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
							$password = $this->_passwordHash($parameters['data']['password']);
							$user = array(
								'email' => $email,
								'password' => $password['hashed'],
								'password_modified' => $password['modified']
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

								if (method_exists($this, 'login')) {
									$this->login($user);
								}
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
