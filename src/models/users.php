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
			$message = 'Password reset is required, please check your inbox for instructions.';
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
				if (!empty($token = $this->_getToken('users', array(), 'password_reset', $existingUser['data'][0]['id'] . '_' . $existingUser['data'][0]['password_modified'], false, 5))) {
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
				}
			}
		}

		$response = array(
			'message' => $message
		);
		return $response;
	}

/**
 * Log in user
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

					if (!empty($existingUser['count'])) {
						if (empty($existingUser['data'][0]['password'])) {
							return $this->forgot($table, $parameters);
						} else {
							if (
								$this->_verifyPassword($parameters['data']['password'], $existingUser['data'][0]) &&
								$this->_getToken($table, $parameters, 'id', $existingUser['data'][0]['id'], sha1($parameters['keys']['users']))
							) {
								$message = 'Logged in successfully.';
								$redirect = $this->settings['base_url'] . 'orders';
							}
						}
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
 * Log out user
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return array $response Response data
 */
	public function logout($table, $parameters = array()) {
		$message = 'Error logging out, please try again.';
		$tokens = $this->find('tokens', array(
			'fields' => array(
				'id'
			),
			'conditions' => array(
				'foreign_key' => 'id',
				'foreign_table' => $table,
				'string' => $this->_createTokenString($table, array(), sha1($parameters['keys']['users']))
			)
		));

		if (
			!empty($tokens['count']) &&
			$this->delete('tokens', array(
				'id' => $tokens['data']
			))
		) {
			$message = 'Logged out successfully.';
			$redirect = $this->settings['base_url'] . '#login';
		}

		return array(
			'message' => $message,
			'redirect' => (!empty($redirect) ? $redirect : '')
		);
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
							$password = $this->_hashPassword($parameters['data']['password'], time());
							$user = array(
								'email' => $email,
								'password' => $password['string'],
								'password_modified' => $password['modified'],
								'permissions' => 'user'
							);

							if ($this->save($table, array(
								$user
							))) {
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
		$message = 'Password and confirmation are required, please try again.';

		if (
			!empty($parameters['data']['password']) &&
			!empty($parameters['data']['confirm_password'])
		) {
			$message = 'Password must be at least 10 characters, please try again.';

			if (strlen($parameters['data']['password']) >= 10) {
				$message = 'Password confirmation doesn\'t match password, please try again.';

				if ($parameters['data']['password'] == $parameters['data']['confirm_password']) {
					$message = 'Invalid or expired password reset token.';

					if (!empty($token = $parameters['data']['password_token'])) {
						$existingToken = $this->find('tokens', array(
							'fields' => array(
								'foreign_value'
							),
							'conditions' => array(
								'expiration >' => date('Y-m-d h:i:s', time()),
								'string' => $token
							)
						));

						if (!empty($existingToken['count'])) {
							$message = 'Error resetting password, please try again.';

							if ($this->_verifyKeys()) {
								$password = $this->_hashPassword($parameters['data']['password'], time());
								$tokenParts = explode('_', $existingToken['data'][0]);
								$user = array(
									'id' => $tokenParts[key($tokenParts)],
									'password' => $password['string'],
									'password_modified' => $password['modified']
								);

								if (
									$this->delete('tokens', array(
										'foreign_value' => $existingToken['data']
									)) &&
									$this->save($table, array(
										$user
									))
								) {
									$message = 'Password reset successfully, you can now log in with your new password.';
									$redirect = $this->settings['base_url'] . '#login';
								}
							}
						}
					}
				}
			}
		}

		return array(
			'message' => $message,
			'redirect' => (!empty($redirect) ? $redirect : '')
		);
	}

}
