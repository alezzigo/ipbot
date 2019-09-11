<?php
/**
 * Users Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
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
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error sending password reset instructions, please try again.')
			)
		);

		if (!empty($parameters['data']['email'])) {
			$response['message']['text'] = 'Password reset is required, please check your inbox for instructions.';
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
				$tokenParameters = array(
					'conditions' => $existingUser['data'][0]
				);

				if (!empty($token = $this->_getToken('users', $tokenParameters, 'password_reset', $existingUser['data'][0]['id'] . '_' . $existingUser['data'][0]['password_modified'], false, 5))) {
					$mailParameters = array(
						'from' => $this->settings['default_email'],
						'subject' => 'Password reset request',
						'template' => array(
							'name' => 'user_forgot_password',
							'parameters' => array(
								'token' => $token['string'],
								'user' => $existingUser['data'][0]
							)
						),
						'to' => $email
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
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error logging in, please try again.')
			)
		);

		if (!empty($parameters['data']['email'])) {
			$response['message']['text'] = 'Invalid email or password, please try again.';
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
				$response['message']['text'] = 'Password is required, please try again.';

				if (!empty($parameters['data']['password'])) {
					$response['message']['text'] = $defaultMessage;

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
									'message' => array(
										'status' => 'success',
										'text' => 'Logged in successfully.'
									),
									'redirect' => $this->settings['base_url'] . 'orders',
									'user' => $existingUser['data'][0]
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
			'message' => array(
				'status' => 'error',
				'text' => 'Error logging out, please try again.'
			)
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
				'message' => array(
					'status' => 'success',
					'text' => (!empty($tokens['count']) ? 'Logged out successfully.' : '')
				),
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
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error processing your user registration, please try again.')
			)
		);

		if (!empty($parameters['data']['email'])) {
			$response['message']['text'] = 'Invalid email or password, please try again.';
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
				$response['message']['text'] = 'Password and confirmation are required, please try again.';

				if (
					!empty($parameters['data']['password']) &&
					!empty($parameters['data']['confirm_password'])
				) {
					$response['message']['text'] = 'Password must be at least 10 characters, please try again.';

					if (strlen($parameters['data']['password']) >= 10) {
						$response['message']['text'] = 'Password confirmation doesn\'t match password, please try again.';

						if ($parameters['data']['password'] == $parameters['data']['confirm_password']) {
							$response['message']['text'] = $defaultMessage;
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
			'message' => array(
				'status' => 'error',
				'text' => 'Password and confirmation are required, please try again.'
			)
		);

		if (
			!empty($parameters['data']['password']) &&
			!empty($parameters['data']['confirm_password'])
		) {
			$response['message']['text'] = 'Password must be at least 10 characters, please try again.';

			if (strlen($parameters['data']['password']) >= 10) {
				$response['message']['text'] = 'Password confirmation doesn\'t match password, please try again.';

				if ($parameters['data']['password'] == $parameters['data']['confirm_password']) {
					$response['message']['text'] = 'Invalid or expired password reset token, please try again..';

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
							$response['message']['text'] = 'Error resetting password, please try again.';

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
										'message' => array(
											'status' => 'success',
											'text' => 'Password reset successfully, you can now log in with your new password.'
										),
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
