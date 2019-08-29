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
 * Apply saved session data to current user
 *
 * @param array $parameters
 * @param array $user
 *
 * @return boolean $response
 */
	protected function _applySessionToUser($parameters, $user) {
		$response = true;

		if (!empty($this->defaultFields)) {
			foreach ($this->defaultFields as $defaultFieldTable => $defaultFields) {
				if (in_array('session_id', $defaultFields)) {
					$sessionData = $this->find($defaultFieldTable, array(
						'conditions' => array(
							'session_id' => $parameters['session'],
							'user_id' => null
						),
						'fields' => array(
							'id',
							'session_id',
							'user_id'
						)
					));

					if (!empty($sessionData['count'])) {
						if (!$this->save($defaultFieldTable, array_replace_recursive($sessionData['data'], array_fill(0, $sessionData['count'], array('user_id' => $user['id']))))) {
							$response = false;
						}
					}
				}
			}
		}

		return $response;
	}

/**
 * Request user password reset
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function forgot($table, $parameters = array()) {
		$response = array(
			'message' => ($defaultMessage = 'Error sending password reset instructions, please try again.')
		);

		if (!empty($parameters['data']['email'])) {
			$response['message'] = 'Password reset is required, please check your inbox for instructions.';
			$existingUser = $this->find($table, array(
				'conditions' => array(
					'email' => $email = $this->_validateEmailFormat($parameters['data']['email'])
				),
				'fields' => array(
					'id',
					'password_modified'
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

		return $response;
	}

/**
 * Log in user
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function login($table, $parameters) {
		$response = array(
			'message' => ($defaultMessage = 'Error logging in, please try again.')
		);

		if (!empty($parameters['data']['email'])) {
			$response['message'] = 'Invalid email or password, please try again.';
			$existingUser = $this->find($table, array(
				'conditions' => array(
					'email' => $email = $this->_validateEmailFormat($parameters['data']['email'])
				),
				'fields' => array(
					'created',
					'email',
					'id',
					'modified',
					'password',
					'password_modified',
					'permissions'
				),
				'limit' => 1
			));

			if (!empty($email)) {
				$response['message'] = 'Password is required, please try again.';

				if (!empty($parameters['data']['password'])) {
					$response['message'] = $defaultMessage;

					if (!empty($existingUser['count'])) {
						if (empty($existingUser['data'][0]['password'])) {
							return $this->forgot($table, $parameters);
						} else {
							if (
								$this->_verifyPassword($parameters['data']['password'], $existingUser['data'][0]) &&
								$this->_getToken($table, $parameters, 'id', $existingUser['data'][0]['id'], sha1($parameters['keys']['users'])) &&
								$this->_applySessionToUser($parameters, $existingUser['data'][0])
							) {
								$response = array(
									'message' => 'Logged in successfully.',
									'redirect' => $this->settings['base_url'] . 'orders'
								);
							}
						}
					}
				}
			}
		}

		return $response;
	}

/**
 * Log out user
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function logout($table, $parameters = array()) {
		$response = array(
			'message' => 'Error logging out, please try again.',
		);
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

		if ($this->delete('tokens', array(
			'id' => $tokens['data']
		))) {
			$response = array(
				'message' => !empty($tokens['count']) ? 'Logged out successfully.' : '',
				'redirect' => $this->settings['base_url'] . '#login'
			);
		}

		return $response;
	}

/**
 * Register user
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function register($table, $parameters = array()) {
		$response = array(
			'message' => ($defaultMessage = 'Error processing your user registration, please try again.')
		);

		if (!empty($parameters['data']['email'])) {
			$response['message'] = 'Invalid email or password, please try again.';
			$existingUser = $this->find($table, array(
				'conditions' => array(
					'email' => $email = $this->_validateEmailFormat($parameters['data']['email'])
				),
				'fields' => array(
					'id'
				),
				'limit' => 1
			));

			if (
				!empty($email) &&
				empty($existingUser['count'])
			) {
				$response['message'] = 'Password and confirmation are required, please try again.';

				if (
					!empty($parameters['data']['password']) &&
					!empty($parameters['data']['confirm_password'])
				) {
					$response['message'] = 'Password must be at least 10 characters, please try again.';

					if (strlen($parameters['data']['password']) >= 10) {
						$response['message'] = 'Password confirmation doesn\'t match password, please try again.';

						if ($parameters['data']['password'] == $parameters['data']['confirm_password']) {
							$response['message'] = $defaultMessage;
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
								$response['message'] = 'User account created successfully.';
								return $this->login($table, $parameters);
							}
						}
					}
				}
			}
		}

		return $response;
	}

/**
 * Reset user password
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function reset($table, $parameters = array()) {
		$response = array(
			'message' => 'Password and confirmation are required, please try again.'
		);

		if (
			!empty($parameters['data']['password']) &&
			!empty($parameters['data']['confirm_password'])
		) {
			$response['message'] = 'Password must be at least 10 characters, please try again.';

			if (strlen($parameters['data']['password']) >= 10) {
				$response['message'] = 'Password confirmation doesn\'t match password, please try again.';

				if ($parameters['data']['password'] == $parameters['data']['confirm_password']) {
					$response['message'] = 'Invalid or expired password reset token.';

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
							$response['message'] = 'Error resetting password, please try again.';

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
									$response = array(
										'message' => 'Password reset successfully, you can now log in with your new password.',
										'redirect' => $this->settings['base_url'] . '#login'
									);
								}
							}
						}
					}
				}
			}
		}

		return $response;
	}

}
