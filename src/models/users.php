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
 * Retrieve user data
 *
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _retrieveUser($parameters) {
		$response = array();

		if (
			!empty($id = $parameters['user_id']) ||
			!empty($id = $parameters['id'])
		) {
			$user = $this->find('users', array(
				'conditions' => array(
					'id' => $id
				),
				'fields' => array(
					'balance',
					'created',
					'email',
					'id',
					'modified'
				)
			));

			if (!empty($user['count'])) {
				$response = $user['data'][0];
			}
		}

		return $response;
	}

/**
 * Add balance to user account
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function balance($table, $parameters = array()) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error adding balance amount to your account, please try again.')
			)
		);

		if (
			!empty($userId = $parameters['user']['id']) &&
			!empty($balance = $parameters['data']['balance'])
		) {
			$response['message']['text'] = 'Invalid account balance amount, please try again.';
			$balanceData = $this->find('products', array(
				'conditions' => array(
					'type' => 'balance'
				),
				'fields' => array(
					'id',
					'minimum_quantity',
					'maximum_quantity',
					'type'
				),
				'limit' => 1,
				'sort' => array(
					'field' => 'modified',
					'order' => 'DESC'
				)
			));

			if (
				!empty($balanceData['count']) &&
				is_numeric($parameters['data']['balance'])
			) {
				$response['message']['text'] = 'Balance amount must be <strong>less than ' . $this->settings['billing']['currency_symbol'] . number_format($balanceData['data'][0]['maximum_quantity'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . '</strong> and <strong>greater than ' . $this->settings['billing']['currency_symbol'] . number_format($balanceData['data'][0]['minimum_quantity'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . '</strong>, please try again.';

				if (
					$parameters['data']['balance'] > $balanceData['data'][0]['minimum_quantity'] &&
					$parameters['data']['balance'] < $balanceData['data'][0]['maximum_quantity']
				) {
					$response['message']['text'] = $defaultMessage;
					$invoiceConditions = array(
						'cart_items' => sha1($balance . uniqid() . time()),
						'status' => 'unpaid',
						'subtotal' => $balance,
						'total' => $balance,
						'user_id' => $userId
					);

					if ($this->save('invoices', array(
						$invoiceConditions
					))) {
						$invoice = $this->find('invoices', array(
							'conditions' => $invoiceConditions,
							'fields' => array(
								'id'
							)
						));

						if (!empty($invoice['count'])) {
							$response = array(
								'message' => array(
									'status' => 'success',
									'text' => 'Invoice for balance payment created successfully.'
								),
								'redirect' => $this->settings['base_url'] . 'invoices/' . $invoice['data'][0] . '#payment'
							);
						}
					}
				}
			}
		}

		return $response;
	}

/**
 * Request email address change
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function email($table, $parameters) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error requesting email address change, please try again.')
			)
		);

		if (
			empty($parameters['data']['email']) &&
			!empty($parameters['data']['token']) &&
			is_string($parameters['data']['token'])
		) {
			$token = $this->find('tokens', array(
				'conditions' => array(
					'foreign_key' => 'change_email',
					'foreign_table' => $table,
					'string' => $parameters['data']['token']
				),
				'fields' => array(
					'expiration',
					'foreign_value',
					'string'
				),
				'limit' => 1,
				'sort' => array(
					'fields' => 'modified',
					'order' => 'DESC'
				)
			));

			if (!empty($token['count'])) {
				$response['message']['text'] = 'Email address change request expired, please try again.';

				if ($token['data'][0]['expiration'] > date('Y-m-d h:i:s', time())) {
					$response['message']['text'] = $defaultMessage;
					$tokenParameters = explode('_', $token['data'][0]['foreign_value']);

					if (
						$token['data'][0]['expiration'] > date('Y-m-d h:i:s', time()) &&
						!empty($userId = $tokenParameters[0]) &&
						is_numeric($userId) &&
						!empty($newEmail = $tokenParameters[1])
					) {
						$userData = array(
							array(
								'id' => $userId,
								'email' => $newEmail
							)
						);

						if (
							($user = $this->_retrieveUser($userData[0])) &&
							$this->save($table, $userData) &&
							$this->delete('tokens', array(
								array(
									'string' => $token['data'][0]['string']
								)
							))
						) {
							$response['message'] = array(
								'status' => 'success',
								'text' => 'Email address changed from <strong>' . $user['email'] . '</strong> to <strong>' . $newEmail . '</strong> successfully.'
							);
							$response['data'] = $emails = array(
								'new_email' => $newEmail,
								'old_email' => $user['email']
							);

							foreach ($emails as $email) {
								$mailParameters = array(
									'from' => $this->settings['from_email'],
									'subject' => 'Email address changed successfully',
									'template' => array(
										'name' => 'user_email_changed',
										'parameters' => $emails
									),
									'to' => $email
								);
								$this->_sendMail($mailParameters);
							}
						}
					}
				}
			}
		} else {
			if (
				!empty($oldEmail = $parameters['user']['email']) &&
				!empty($newEmail = $parameters['data']['email'])
			) {
				$response['message']['text'] = 'Invalid email address, please try again.';

				if ($this->_validateEmailFormat($newEmail)) {
					$response['message']['text'] = 'You\'ve entered your current account email address, please enter a different email address.';

					if ($oldEmail !== $newEmail) {
						$response = array(
							'message' => array(
								'status' => 'success',
								'text' => 'Please check your inbox at <strong>' . $newEmail . '</strong> for instructions (if this email address doesn\'t exist in another user account).'
							)
						);
						$existingUser = $this->find($table, array(
							'conditions' => array(
								'email' => $newEmail
							),
							'fields' => array(
								'id',
								'email'
							),
							'limit' => 1
						));
						$tokenParameters = array(
							'conditions' => array(
								'id' => $parameters['user']['id']
							)
						);
						$tokenSalt = sha1($newEmail . $this->keys['start'] . time());

						if (
							empty($existingUser['count']) &&
							!empty($token = $this->_getToken('users', $tokenParameters, 'change_email', $parameters['user']['id'] . '_' . $newEmail, false, $tokenSalt, 10))
						) {
							$mailParameters = array(
								'from' => $this->settings['from_email'],
								'subject' => 'Email address change request',
								'template' => array(
									'name' => 'user_email_request_change',
									'parameters' => array(
										'new_email' => $newEmail,
										'old_email' => $oldEmail,
										'token' => $token['string']
									)
								),
								'to' => $newEmail
							);
							$this->_sendMail($mailParameters);
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
					'email',
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
				$tokenSalt = sha1($email . $this->keys['start'] . time());

				if (!empty($token = $this->_getToken('users', $tokenParameters, 'password_reset', $existingUser['data'][0]['id'] . '_' . $existingUser['data'][0]['password_modified'], false, $tokenSalt, 10))) {
					$mailParameters = array(
						'from' => $this->settings['from_email'],
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
								$mailParameters = array(
									'from' => $this->settings['from_email'],
									'subject' => 'New account created at ' . $this->settings['site_name'],
									'template' => array(
										'name' => 'user_created',
										'parameters' => array(
											'user' => $user
										)
									),
									'to' => $email
								);
								$this->_sendMail($mailParameters);
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
				'text' => ($defaultMessage = 'Error processing your user password reset, please try again.')
			)
		);

		if (!empty($parameters['user'])) {
			$existingUser = $parameters['user'];
		} else {
			if (
				!empty($parameters['data']['token']) &&
				is_string($parameters['data']['token'])
			) {
				$response['message']['text'] = 'Invalid password reset token, please <a href="' . $this->settings['base_url'] . '?#forgot">request a password reset</a> and try again.';
				$token = $this->find('tokens', array(
					'conditions' => array(
						'foreign_key' => 'password_reset',
						'foreign_table' => $table,
						'string' => $parameters['data']['token']
					),
					'fields' => array(
						'expiration',
						'foreign_value',
						'string'
					),
					'limit' => 1,
					'sort' => array(
						'fields' => 'modified',
						'order' => 'DESC'
					)
				));

				if (!empty($token['count'])) {
					$response['message']['text'] = 'Password reset token expired, please <a href="' . $this->settings['base_url'] . '?#forgot">request a new password reset</a> and try again.';
					$tokenParameters = explode('_', $token['data'][0]['foreign_value']);

					if (
						$token['data'][0]['expiration'] > date('Y-m-d h:i:s', time()) &&
						!empty($userId = $tokenParameters[0]) &&
						is_numeric($userId) &&
						!empty($passwordModified = $tokenParameters[1]) &&
						(boolean) strtotime($passwordModified)
					) {
						$response['message']['text'] = 'Password was already reset with another token, please <a href="' . $this->settings['base_url'] . '?#forgot">request a new password reset</a> and try again.';
						$user = $this->find('users', array(
							'conditions' => array(
								'id' => $userId,
								'password_modified' => $passwordModified
							),
							'fields' => array(
								'email',
								'id',
								'password_modified'
							)
						));

						if (!empty($user)) {
							$existingUser = $user['data'][0];
						}
					}
				}
			}
		}

		if (!empty($existingUser)) {
			if (
				!empty($parameters['data']['password']) &&
				!empty($parameters['data']['confirm_password'])
			) {
				$response['message']['text'] = 'Password must be at least 10 characters, please try again.';

				if (strlen($parameters['data']['password']) >= 10) {
					$response['message']['text'] = 'Password confirmation doesn\'t match password, please try again.';

					if ($parameters['data']['password'] == $parameters['data']['confirm_password']) {
						$response['message']['text'] = $defaultMessage;

						if ($this->_verifyKeys()) {
							$password = $this->_hashPassword($parameters['data']['password'], time());
							$userData = array(
								'id' => $existingUser['id'],
								'password' => $password['string'],
								'password_modified' => $password['modified']
							);

							if (
								(
									empty($token['data'][0]['foreign_value']) ||
									$this->delete('tokens', array(
										'foreign_value' => $token['data'][0]['foreign_value']
									))
								) &&
								$this->save($table, array(
									$userData
								))
							) {
								$response = array(
									'message' => array(
										'status' => 'success',
										'text' => 'Password reset successfully' . (!$parameters['user'] ? ', you can now <a href="' . $this->settings['base_url'] . '?#login">log in</a> with your new password' : '') . '.'
									)
								);
								$mailParameters = array(
									'from' => $this->settings['from_email'],
									'subject' => 'Password reset successful',
									'template' => array(
										'name' => 'user_password_reset',
										'parameters' => array(
											'user' => $existingUser
										)
									),
									'to' => $existingUser['email']
								);
								$this->_sendMail($mailParameters);
							}
						}
					}
				}
			} else {
				$response = array(
					'user' => $existingUser,
					'message' => array(
						'status' => 'success',
						'text' => 'Enter a new password for ' . $existingUser['email'] . '.'
					)
				);
			}
		}

		return $response;
	}

/**
 * View user
 *
 * @return array
 */
	public function view($parameters) {
		return array();
	}

}
