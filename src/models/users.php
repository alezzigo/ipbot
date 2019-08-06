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
 * Request user password reset
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return array $response Response data
 */
	public function forgot($table, $parameters = array()) {
		$message = $defaultMessage = 'Error sending password reset instructions, please try again.';

		if (!empty($parameters['data']['email'])) {
			$message = 'Please check your inbox for password reset instructions.';
			$existingUser = $this->find($table, array(
				'conditions' => array(
					'email' => $email = $this->_validateEmailFormat($parameters['data']['email'])
				),
				'limit' => 1
			));

			if (
				!empty($email) &&
				!empty($existingUser['count'])
			) {
				$mailParameters = array(
					'to' => $email,
					'subject' => 'Password reset request',
					'message' => '...',
					'headers' => array(
						'From' => $this->settings['default_email'],
						'Reply-To' => $this->settings['default_email'],
						'X-Mailer' => 'PHP/' . phpversion()
					)
				);
				$this->_sendMail($mailParameters);
				// ...
			}
		}

		$response = array(
			'message' => $message
		);
		return $response;
	}

/**
 * Login user
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 * @param string $redirect Redirect URL
 *
 * @return array $response Response data
 */
	public function login($table, $parameters = array(), $redirect = '') {
		$message = $defaultMessage = 'Error logging in, please try again.';

		if (!empty($parameters['data']['email'])) {
			$message = 'Invalid email or password, please try again.';
			$existingUser = $this->find($table, array(
				'conditions' => array(
					'email' => $email = $this->_validateEmailFormat($parameters['data']['email'])
				),
				'limit' => 1
			));

			if (!empty($email)) {
				$message = 'Password is required, please try again.';

				if (!empty($parameters['data']['password'])) {
					$message = $defaultMessage;

					if (
						$this->_passwordVerify($parameters['data']['password'], $existingUser['data'][0]) &&
						$this->_getToken($table, $parameters, 'id', $existingUser['data'][0]['id'], sha1($parameters['keys']['users']))
					) {
						$message = 'Logged in successfully.';
						$redirect = $this->settings['base_url'] . '/views/orders/list.php';
					}
				}
			}
		}

		$response = array(
			'message' => $message,
			'redirect' => $redirect
		);
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
			$existingUser = $this->find($table, array(
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
								'password_modified' => $passwordHash['modified'],
								'permissions' => 'user'
							);
							$this->save($table, array(
								$user
							));
							$user = $this->find($table, array(
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

/**
 * Reset user password
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return array $response Response data
 */
	public function reset($table, $parameters = array()) {
		// ...
	}

}
