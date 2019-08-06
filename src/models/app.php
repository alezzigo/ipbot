<?php
/**
 * App Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */

class AppModel extends Config {

/**
 * Authenticate requests
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return mixed [string/boolean] $response Response data if authentication is successful, false if user request is invalid or expired
 */
	protected function _authenticate($table, $parameters) {
		$response = false;

		if (
			!empty($parameters['keys']['users']) &&
			$this->_verifyKeys()
		) {
			$existingToken = $this->find('tokens', array(
				'conditions' => array(
					'foreign_key' => 'id',
					'foreign_table' => $table,
					'string' => $this->_createTokenString($table, array(), sha1($parameters['keys']['users']))
				),
				'fields' => array(
					'foreign_key',
					'foreign_value',
					'id',
					'string'
				),
				'limit' => 1,
				'sort' => array(
					'field' => 'created',
					'order' => 'DESC'
				)
			));

			if (!empty($existingToken['count'])) {
				$existingData = $this->find($table, array(
					'conditions' => array(
						$existingToken['data'][0]['foreign_key'] => $existingToken['data'][0]['foreign_value']
					),
					'limit' => 1
				));

				if (!empty($existingData['count'])) {
					unset($existingData['data'][0]['password']);
					unset($existingData['data'][0]['password_modified']);
					$response = $existingData['data'][0];
				}
			}
		}

		return $response;
	}

/**
 * Create token string from parameters and results
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 * @param string $salt Salt for token string
 *
 * @return array Token string
 */
	protected function _createTokenString($table, $parameters, $salt = '') {
		$tokenParts = array(
			$this->keys['start']
		);

		if (!empty($parameters['conditions'])) {
			$tokenParts[] = $this->find($table, array(
				'conditions' => $parameters['conditions'],
				'fields' => array(
					'id'
				),
				'limit' => 1,
				'sort' => array(
					'field' => 'modified',
					'order' => 'DESC'
				)
			));
		}

		if (!empty($salt)) {
			$tokenParts[] = $salt;
		}

		return sha1(json_encode(implode(')-(', $tokenParts)));
	}

/**
 * Format array of data to SQL query conditions
 *
 * @param array $conditions Conditions
 * @param string $condition Condition
 *
 * @return array $conditions SQL query conditions
 */
	protected function _formatConditions($conditions = array(), $condition = 'OR') {
		$operators = array('>', '>=', '<', '<=', '=', '!=', 'LIKE');

		foreach ($conditions as $key => $value) {
			$condition = !empty($key) && (in_array($key, array('AND', 'OR'))) ? $key : $condition;

			if (count($value) == count($value, COUNT_RECURSIVE)) {
				if (is_array($value)) {
					array_walk($value, function(&$fieldValue, $fieldKey) use ($key, $operators) {
						$key = (strlen($fieldKey) > 1 && is_string($fieldKey) ? $fieldKey : $key);
						$fieldValue = (is_null($fieldValue) ? $key . ' IS NULL' : trim(in_array($operator = trim(substr($key, strpos($key, ' '))), $operators) ? $key : $key . ' =') . ' ' . $this->_prepareValue($fieldValue));
					});
				} else {
					$value = array((is_null($value) ? $key . ' IS NULL' : trim(in_array($operator = trim(substr($key, strpos($key, ' '))), $operators) ? $key : $key . ' =') . ' ' . $this->_prepareValue($value)));
				}

				$conditions[$key] = '(' . implode(' ' . $condition . ' ', $value) . ')';
			} else {
				$conditions[$key] = ($key === 'NOT' ? 'NOT' : null) . '(' . implode(' ' . $condition . ' ', $this->_formatConditions($value, $condition)) . ')';
			}
		}

		return $conditions;
	}

/**
 * Save and retrieve database token based on parameters
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 * @param string $foreignKey Foreign key
 * @param string $foreignValue Foreign value
 * @param string $salt Salt for token string
 *
 * @return array $token Token
 */
	protected function _getToken($table, $parameters, $foreignKey, $foreignValue, $salt = '') {
		$tokenParameters = array(
			'conditions' => array(
				'foreign_key' => $foreignKey,
				'foreign_table' => $table,
				'foreign_value' => $foreignValue,
				'string' => $this->_createTokenString($table, $parameters, $salt)
			),
			'fields' => array(
				'id'
			),
			'limit' => 1
		);
		$existingToken = $this->find('tokens', $tokenParameters);

		if (!empty($existingToken['count'])) {
			$tokenParameters['conditions']['id'] = $existingToken['data'][0];
		}

		$this->save('tokens', array(
			$tokenParameters['conditions']
		));
		$tokenParameters['fields'] = array(
			'created',
			'foreign_key',
			'foreign_value',
			'string'
		);
		$token = $this->find('tokens', $tokenParameters);
		return !empty($token['data'][0]) ? $token['data'][0] : array();
	}

/**
 * Hash password
 *
 * @param string $string Raw password string
 * @param string $time Timestamp
 *
 * @return array $response Response with hashed password
 */
	protected function _hashPassword($string, $time) {
		$response = array(
			'modified' => $modified = date('Y-m-d h:i:s', $time),
			'string' => 'e1Gh7$' . sha1($string . $modified . $this->keys['start'])
		);
		return $response;
	}

/**
 * Retrieve parameterized SQL query and array of values
 *
 * @param string $query Query
 *
 * @return array Parameterized SQL query and array of values
 */
	protected function _parameterizeSQL($query) {
		$queryChunks = explode($this->keys['start'], $query);
		$parameterValues = array();

		foreach ($queryChunks as $key => $queryChunk) {
			if (
				($position = strpos($queryChunk, $this->keys['stop'])) !== false &&
				$queryChunk = str_replace($this->keys['stop'], '?', $queryChunk)
			) {
				$queryChunks[$key] = str_replace(($between = substr($queryChunk, 0, $position)), '', $queryChunk);
				$parameterValues[] = $between;
			}
		}

		return array(
			'parameterizedQuery' => implode('', $queryChunks),
			'parameterizedValues' => $parameterValues
		);
	}

/**
 * Parse and filter IPv4 address list.
 *
 * @param array $ips Unfiltered IPv4 address list
 * @param boolean $subnets Allow partial IPv4 subnets instead of full /32 mask
 *
 * @return array $ips Filtered IPv4 address list
 */
	protected function _parseIps($ips = array(), $subnets = false) {
		$ips = implode("\n", array_map(function($ip) {
			return trim($ip, '.');
		}, array_filter(preg_split("/[](\r\n|\n|\r) @#$+,[;:_-]/", $ips))));
		$ips = $this->_validateIps($ips, $subnets);
		return explode("\n", $ips);
	}

/**
 * Prepare user input value for SQL parameterization parsing with hash strings
 *
 * @param string $value Value
 *
 * @return string Prepared value
 */
	protected function _prepareValue($value) {
		return $this->keys['start'] . (is_bool($value) ? (integer) $value : $value) . $this->keys['stop'];
	}

/**
 * Process API action requests
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return array Response data
 */
	protected function _processAction($table, $parameters) {
		if (
			!method_exists($this, $action = $parameters['action']) ||
			(
				($itemTable = (in_array($table, array('proxies')))) &&
				!empty($orderId = $parameters['conditions']['order_id']) &&
				($token = $this->_getToken($table, $parameters, 'order_id', $orderId)) === false
			)
		) {
			return false;
		}

		$noItems = array(
			$table => array()
		);
		$response = array(
			'items' => $parameters['items'] = isset($parameters['items']) ? $parameters['items'] : $noItems
		);
		$response['tokens'][$table] = $token;

		if (
			empty($parameters['tokens'][$table]) ||
			$parameters['tokens'][$table] === $token
		) {
			if (
				$itemTable &&
				!in_array($action, array('find',  'search'))
			) {
				$parameters['items'] = $this->_retrieveItems($parameters);
			}
		} else {
			$action = 'find';
			$response['items'] = $noItems;
			$response['message'] = 'Your ' . $table . ' have been recently modified and your previously-selected results have been deselected automatically.';
		}

		if (!empty($parameters['redirect'])) {
			$response['redirect'] = $parameters['redirect'];
		}

		return array_merge($response, $this->$action($table, $parameters));
	}

/**
 * Construct and execute database queries
 *
 * @param string $query Query string
 * @param array $parameters Parameters
 *
 * @return array $response Return data if query results exists, otherwise return boolean status
 */
	protected function _query($query, $parameters = array()) {
		$database = new PDO($this->settings['database']['type'] . ':host=' . $this->settings['database']['hostname'] . '; dbname=' . $this->settings['database']['name'] . ';', $this->settings['database']['username'], $this->settings['database']['password']);
		$database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		$database->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$parameterized = $this->_parameterizeSQL($query);

		if (empty($parameterized['parameterizedQuery'])) {
			return false;
		}

		$connection = $database->prepare($parameterized['parameterizedQuery']);
		$data = array();

		if (
			empty($connection) ||
			!is_object($connection)
		) {
			return false;
		}

		$findDataRowChunkSize = 100000;
		$hasResults = (!empty($parameters['count']) && !empty($parameters['limit']));

		foreach (array_fill(0, max(1, ($hasResults ? round($parameters['limit'] / $findDataRowChunkSize, 1, PHP_ROUND_HALF_UP) : 1)), true) as $chunkIndex => $value) {
			if ($hasResults) {
				end($parameterized['parameterizedValues']);
				$offset = $parameterized['parameterizedValues'][key($parameterized['parameterizedValues'])] = $chunkIndex * $findDataRowChunkSize;
				$limit = prev($parameterized['parameterizedValues']);
				$parameterized['parameterizedValues'][key($parameterized['parameterizedValues'])] = $parameters['limit'] > $findDataRowChunkSize ? ($offset + $findDataRowChunkSize) < $parameters['count'] ? $findDataRowChunkSize : $parameters['count'] - $offset : $parameters['limit'];
			}

			$execute = $connection->execute($parameterized['parameterizedValues']);
			$data[] = $connection->fetchAll(!empty($parameters['field_count']) && $parameters['field_count'] === 1 ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC);
		}

		$response = !empty($data[0]) ? call_user_func_array('array_merge', $data) : $execute;
		$connection->closeCursor();
		return $response;
	}

/**
 * Validate and structure API request based on parameters
 *
 * @param array $parameters Parameters
 *
 * @return array $response Response data
 */
	protected function _request($parameters) {
		$response = array(
			'code' => 400,
			'message' => 'Request parameters are required for API.'
		);

		if (
			!empty($_POST['json']) &&
			is_string($_POST['json'])
		) {
			$parameters = json_decode($_POST['json'], true);
			$response['message'] = 'No results found, please try again.';

			if (
				empty($table = $parameters['table']) ||
				empty($this->permissions[$table])
			) {
				$response['message'] = 'Invalid request table, please try again.';
			} else {
				if (
					($parameters['action'] = $action = (!empty($parameters['action']) ? $parameters['action'] : 'find')) &&
					(
						empty($this->permissions[$table][$action]) ||
						!method_exists($this, $action)
					)
				) {
					$response['message'] = 'Invalid request action, please try again.';
				} else {
					if (
						($fieldPermissions = $this->permissions[$table][$action]['fields']) &&
						($parameters['fields'] = $fields = !empty($parameters['fields']) ? $parameters['fields'] : $fieldPermissions) &&
						count(array_intersect($fields, $fieldPermissions)) !== count($fields)
					) {
						$response['message'] = 'Invalid request fields, please try again.';
					} else {
						if (
							(
								(
									isset($parameters['conditions']) &&
									empty($parameters['conditions'])
								) ||
								(
									!empty($parameters['conditions']) &&
									!is_array($parameters['conditions'])
								)
							) ||
							(
								isset($parameters['limit']) &&
								!is_int($parameters['limit'])
							) ||
							(
								isset($parameters['offset']) &&
								!is_int($parameters['offset'])
							) ||
							(
								(
									!empty($parameters['sort']['field']) &&
									!in_array($parameters['sort']['field'], array_merge($fieldPermissions, array(
										'created',
										'modified'
									)))
								) ||
								(
									!empty($parameters['sort']['order']) &&
									!in_array(strtoupper($parameters['sort']['order']), array('ASC', 'DESC'))
								)
							)
						) {
							$response['message'] = 'Invalid request parameters, please try again.';
						} else {
							$response = array(
								'code' => 407,
								'message' => 'Authentication required, please log in and try again.',
								'redirect' => $this->settings['base_url'] . '/#login'
							);

							if (
								empty($this->permissions[$table][$action]['group']) ||
								(
									($parameters['user'] = $this->_authenticate('users', $parameters)) &&
									in_array('user_id', $this->permissions[$table][$action]['fields']) &&
									($parameters['conditions']['user_id'] = $parameters['user']['id'])
								)
							) {
								if (array_search($parameters['user']['permissions'], $this->groups) > 1) {
									unset($parameters['conditions']['user_id']);

									if (
										(
											$table == 'orders' &&
											!empty($orderId = $parameters['conditions']['id'])
										) ||
										!empty($orderId = $parameters['conditions']['order_id'])
									) {
										$userData = $this->find('orders', array(
											'conditions' => array(
												'id' => $orderId
											),
											'fields' => array(
												'user_id'
											),
											'limit' => 1
										));

										if (!empty($userData['count'])) {
											$parameters['conditions']['user_id'] = $userData['data'][0];
										}
									}
								}

								$parameters['redirect'] = '';
								$queryResponse = $this->_processAction($table, $parameters);

								if (!empty($queryResponse)) {
									$response = array_merge($queryResponse, array(
										'code' => 200
									));
								}
							}
						}
					}
				}
			}
		}

		return $response;
	}

/**
 * Unserialize indexes and retrieve corresponding item IDs based on parameters
 *
 * @param array $parameters Parameters
 *
 * @return array $response Response data
 */
	protected function _retrieveItems($parameters) {
		$response = array();

		if (!empty($parameters['items'])) {
			foreach ($parameters['items'] as $table => $items) {
				$response[$table] = array(
					'count' => count($items),
					'data' => $items
				);

				if (
					!empty($items) &&
					is_numeric(array_search(current($items), $items))
				) {
					$itemIndexes = array();
					$itemIndexLines = $items;
					$index = 0;

					foreach ($itemIndexLines as $itemIndexLine) {
						$itemIndexLineChunks = explode('_', $itemIndexLine);

						foreach ($itemIndexLineChunks as $itemIndexLineChunk) {
							$itemStatus = substr($itemIndexLineChunk, 0, 1);
							$itemStatusCount = substr($itemIndexLineChunk, 1);

							if ($itemStatus) {
								for ($i = 0; $i < $itemStatusCount; $i++) {
									$itemIndexes[$index + $i] = 1;
								}
							}

							$index += $itemStatusCount;
						}
					}

					if (
						empty($itemIndexes) ||
						!$index
					) {
						continue;
					}

					unset($parameters['offset']);
					$ids = $this->find($table, array_merge($parameters, array(
						'fields' => array(
							'id'
						),
						'limit' => end($itemIndexes) ? key($itemIndexes) + 1 : $index,
						'offset' => 0
					)));
					$conditions = array(
						'id' => !empty($ids['data']) ? array_intersect_key($ids['data'], $itemIndexes) : array()
					);

					if ($parameters['action'] == 'replace') {
						$conditions[]['NOT']['AND'] = array(
							'status' => 'replaced'
						);
						$conditions[]['OR'] = array(
							'next_replacement_available' => null,
							'next_replacement_available <' => date('Y-m-d H:i:s', time())
						);
					}

					$response[$table] = $this->find($table, array(
						'conditions' => $conditions,
						'fields' => array(
							'id'
						)
					));
				}
			}
		}

		return $response;
	}

/**
 * Send mail
 *
 * @param string $parameters Parameters
 *
 * @return boolean True if mail is sent
 */
	protected function _sendMail($parameters) {
		if (
			empty($to = $this->_validateEmailFormat($parameters['to'])) ||
			(
				empty($subject = $parameters['subject']) ||
				!is_string($subject)
			) ||
			(
				empty($message = $parameters['message']) ||
				!is_string($message)
			) ||
			(
				empty($headers = $parameters['message']) ||
				!is_array($headers)
			)
		) {
			return false;
		}

		return mail($to, $subject, $message, $headers);
	}

/**
 * Validate email address format
 *
 * @param string $email Email address
 *
 * @return mixed [string/boolean] $email Email if valid email address format, false if invalid
 */
	protected function _validateEmailFormat($email) {
		$email = strtolower(trim($email));
		$emailSplitCharacters = explode('@', $email);
		$validAlphaNumericCharacters = 'abcdefghijklmnopqrstuvwxyz1234567890';
		$validLocalCharacters = '!#$%&\'*+-/=?^_`{|}~' . $validAlphaNumericCharacters;
		$validLocalSpecialCharacters = ' .(),:;<>@[]';
		$validDomainCharacters = '-.' . $validAlphaNumericCharacters;

		if (count($emailSplitCharacters) !== 2) {
			return false;
		}

		$localString = $emailSplitCharacters[0];
		$localStringCharacters = str_split($localString);
		$domainString = $emailSplitCharacters[1];
		$domainStringCharacters = str_split($domainString);
		$domainStringSplitCharacters = explode('.', $domainString);

		if (
			strstr(' .-', $lastLocalStringCharacter = end($localStringCharacters)) !== false ||
			strstr(' .-', $firstLocalStringCharacter = reset($localStringCharacters)) !== false ||
			strstr(' .-', $lastDomainStringCharacter = end($domainStringCharacters)) !== false ||
			strstr(' .-', $firstDomainStringCharacter = reset($domainStringCharacters)) !== false ||
			strpos($domainString, '-.') !== false ||
			strpos($domainString, '.-') !== false ||
			count($domainStringSplitCharacters) < 2 ||
			strlen(end($domainStringSplitCharacters)) < 2 ||
			$lastDomainStringCharacter == '-'
		) {
			return false;
		}

		if (
			$lastLocalStringCharacter == '"' &&
			$firstLocalStringCharacter == '"'
		) {
			$validLocalCharacters .= $validLocalSpecialCharacters;
			array_shift($localStringCharacters);
			array_pop($localStringCharacters);
			$localString = implode('', $localStringCharacters);
			$localString = str_replace('\\' . '\\', ' \\' . '\\ ', $localString);
			$localString = str_replace('\"', ' \" ', $localString);
			$localStringCharacters = array();
			$localStringSplitCharacters = explode(' ', $localString);

			foreach ($localStringSplitCharacters as $key => $localStringSplitCharacter) {
				$localStringCharacters = array_filter(array_merge($localStringCharacters, !in_array($localStringSplitCharacter, array('\\' . '\\', '\"')) ? str_split($localStringSplitCharacter) : array()));
			}
		} elseif (strstr($domainString, '..')) {
			return false;
		}

		if (
			$invalidLocalCharacters = array_diff($localStringCharacters, str_split($validLocalCharacters)) ||
			$invalidDomainCharacters = array_diff($domainStringCharacters, str_split($validDomainCharacters))
		) {
			return false;
		}

		return $email;
	}

/**
 * Validate IPv4 address/subnet list
 *
 * @param array $ips Filtered IPv4 address/subnet list
 * @param boolean $subnets Allow partial IPv4 subnets instead of full /32 mask
 *
 * @return array $ips Validated IPv4 address/subnet list
 */
	protected function _validateIps($ips, $subnets = false) {
		$ips = array_values(array_filter(explode("\n", $ips)));

		foreach ($ips as $key => $ip) {
			$splitIpSubnets = array_map('trim', explode('.', trim($ip)));

			if (
				count($splitIpSubnets) != 4 &&
				$subnets === false
			) {
				unset($ips[$key]);
				continue;
			}

			foreach ($splitIpSubnets as $splitIpSubnet) {
				if (
					!is_numeric($splitIpSubnet) ||
					strlen($splitIpSubnet) > 3 ||
					$splitIpSubnet > 255 ||
					$splitIpSubnet < 0
				) {
					unset($ips[$key]);
					continue;
				}
			}

			$ips[$key] = implode('.', $splitIpSubnets);
		}
		return implode("\n", array_unique($ips));
	}

/**
 * Verify configuration keys
 *
 * @return boolean $response True if keys are verified, false if new keys are set
 */
	protected function _verifyKeys() {
		$response = false;

		if (
			!empty($this->keys['start']) &&
			!empty($this->keys['stop'])
		) {
			$keys = sha1(json_encode($this->keys['start'] . $this->keys['stop']));
			$existingKeys = $this->find('settings', array(
				'conditions' => array(
					'name' => 'keys'
				),
				'fields' => array(
					'name',
					'value'
				),
				'sort' => array(
					'field' => 'modified',
					'order' => 'DESC'
				)
			));

			if (!empty($existingKeys['count'])) {
				$response = true;
			}

			if (
				empty($existingKeys['count']) ||
				(
					!empty($existingKeys['count']) &&
					$existingKeys['data'][0]['value'] != $keys
				)
			) {
				$response = false;
				$users = $this->find('users', array(
					'fields' => array(
						'id',
						'password',
						'password_modified'
					)
				));

				if (!empty($users['count'])) {
					foreach ($users['data'] as $key => $user) {
						$users['data'][$key]['password'] = '';
						$users['data'][$key]['password_modified'] = date('Y-m-d h:i:s', time());
					}

					$this->save('users', $users['data']);
				}

				$this->delete('tokens');
				$this->save('settings', array(
					array(
						'name' => 'keys',
						'value' => $keys
					)
				));
			}
		}

		return $response;
	}

/**
 * Verify password
 *
 * @param string $password Raw password string
 * @param array $user User data
 *
 * @return boolean $response True/false if password is valid/invalid
 */
	protected function _verifyPassword($password, $user) {
		$response = false;

		if (!empty($user['password'])) {
			$password = $this->_hashPassword($password, strtotime($user['password_modified']));

			if ($password['string'] == $user['password']) {
				$response = true;
			}
		}

		return $response;
	}

/**
 * Database helper method for deleting data
 *
 * @param string $table Table name
 * @param array $conditions Conditions for deletion
 *
 * @return boolean True if all data is deleted
 */
	public function delete($table, $conditions = array()) {
		$query = 'DELETE FROM ' . $table;

		if (
			!empty($conditions) &&
			is_array($conditions)
		) {
			$query .= ' WHERE ' . implode(' AND ', $this->_formatConditions($conditions));
		}

		$data = $this->_query($query, $parameters);
	}

/**
 * Database helper method for retrieving data
 *
 * @param string $table Table name
 * @param array $parameters Parameters
 *
 * @return array $response Return associative array if it exists, otherwise return boolean ($execute)
 */
	public function find($table, $parameters = array()) {
		$query = ' FROM ' . $table;

		if (
			!empty($parameters['conditions']) &&
			is_array($parameters['conditions'])
		) {
			$query .= ' WHERE ' . implode(' AND ', $this->_formatConditions($parameters['conditions']));
		}

		$count = $this->_query('SELECT COUNT(id)' . $query);

		if (!empty($parameters['sort']['field'])) {
			$query .= ' ORDER BY ' . $parameters['sort']['field'] . ' ' . (!empty($parameters['sort']['order']) ? $parameters['sort']['order'] : 'DESC');
		}

		$parameters = array_merge($parameters, array(
			'count' => $count = $parameters['count'] = !empty($count[0]['COUNT(id)']) ? $count[0]['COUNT(id)'] : 0,
			'field_count' => !empty($parameters['fields']) && is_array($parameters['fields']) ? count($parameters['fields']) : 0,
			'limit' => !empty($parameters['limit']) && $parameters['limit'] < $count ? $parameters['limit'] : $count,
			'offset' => !empty($parameters['offset']) ? !empty($parameters['offset']) : 0
		));

		$query = 'SELECT ' . (!empty($parameters['fields']) && is_array($parameters['fields']) ? implode(',', $parameters['fields']) : '*') . $query;
		$query .= ' LIMIT ' . $this->_prepareValue($parameters['limit']) . ' OFFSET ' . $this->_prepareValue($parameters['offset']);
		$data = $this->_query($query, $parameters);
		$response = array(
			'count' => $count,
			'data' => $data
		);

		if (empty($count)) {
			$response['message'] = 'No ' . $table . ' found, please try again.';
		}

		return $response;
	}

/**
 * Redirect helper method
 *
 * @param string $redirect Redirect URL
 * @param string $responseCode HTTP response code
 *
 * @return exit
 */
	public function redirect($redirect, $responseCode = 301) {
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
		header('Location: ' . $redirect, false, $responseCode);
		exit;
	}

/**
 * Routing helper method
 *
 * @return mixed [array/exit] Return data if action exists, redirect to base URL if action doesn't exist
 */
	public function route() {
		if (
			!empty($action = array_shift(array_reverse(explode('/', str_replace('.php', '', $_SERVER['SCRIPT_NAME']))))) &&
			method_exists($this, $action)
		) {
			return array_merge($this->$action(), array(
				'action' => $action,
				'table' => str_replace('/', '', strrchr(dirname($_SERVER['SCRIPT_NAME']), '/'))
			));
		}

		$this->redirect($this->settings['base_url'] . '/');
	}

/**
 * Database helper method for saving data
 *
 * @param string $table Table name
 * @param array $rows Data to save
 *
 * @return boolean True if all data is saved
 */
	public function save($table, $rows = array()) {
		$ids = array();
		$queries = array();
		$success = true;

		foreach (array_chunk($rows, 1000) as $rows) {
			$groupValues = array();

			foreach ($rows as $row) {
				$fields = array_keys($row);
				$values = array_map(function($value) {
					return (is_bool($value) ? (integer) $value : $value);
				}, array_values($row));

				if (!in_array('modified', $fields)) {
					$fields[] = 'modified';
					$values[] = date('Y-m-d H:i:s', time());
				}

				$groupValues[implode(',', $fields)][] = $this->keys['start'] . implode($this->keys['stop'] . ',' . $this->keys['start'], $values) . $this->keys['stop'];
			}

			foreach ($groupValues as $fields => $values) {
				$updateFields = explode(',', $fields);
				array_walk($updateFields, function(&$field, $index) {
					$field = $field . '=VALUES(' . $field . ')';
				});
				$queries[] = 'INSERT INTO ' . $table . '(' . $fields . ') VALUES (' . implode('),(', $values) . ') ON DUPLICATE KEY UPDATE ' . implode(',', $updateFields);
			}
		}

		foreach ($queries as $query) {
			$connection = $this->_query($query);

			if (empty($connection)) {
				$success = false;
			}
		}

		return $success;
	}

}
