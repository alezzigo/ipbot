<?php
/**
 * App Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
class AppModel extends Config {

/**
 * Authenticate requests
 *
 * @param string $table
 * @param array $parameters
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
					$passwordModified = strtotime($existingData['data'][0]['password_modified']);
					$existingData['data'][0]['password_modified'] = date('M d, Y', $passwordModified) . ' at ' . date('g:ia', $passwordModified) . ' ' . $this->settings['timezone']['display'];
					$response = $existingData['data'][0];
				}
			}
		}

		return $response;
	}

/**
 * Calculate item price
 *
 * @param array $item
 *
 * @return float $response
 */
	protected function _calculateItemPrice($item) {
		$interval = $item['interval_value'] * ($item['interval_type'] == 'year' ? 12 : 1);
		$response = number_format(($item['price_per'] * $item['quantity'] * $interval) - (($item['price_per'] * $item['quantity']) * ((min(1000 + ($item['quantity'] / 10), $item['quantity']) / $item['volume_discount_divisor']) * $item['volume_discount_multiple'] * $item['interval_value'])), 2, '.', '');
		// ..
		return $response;
	}

/**
 * Calculate item shipping price
 *
 * @param array $item
 *
 * @return float $response
 */
	protected function _calculateItemShippingPrice($item) {
		$response = 0.00;
		// ..
		return $response;
	}

/**
 * Calculate item tax price
 *
 * @param array $item
 *
 * @return float $response
 */
	protected function _calculateItemTaxPrice($item) {
		$response = 0.00;
		// ..
		return $response;
	}

/**
 * Create token string from parameters and results
 *
 * @param string $table
 * @param array $parameters
 * @param string $sessionId
 * @param string $salt
 *
 * @return array $response
 */
	protected function _createTokenString($table, $parameters, $sessionId = false, $salt = false) {
		$response = array(
			$this->keys['start']
		);

		if (!empty($parameters['conditions'])) {
			$response[] = $this->find($table, array(
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

		if ($sessionId !== false) {
			$response[] = (!empty($_SESSION['key']) ? $_SESSION['key'] : $sessionId);
		}

		if ($salt !== false) {
			$response[] = $salt;
		}

		$response = sha1(json_encode(implode(')-(', $response)));
		return $response;
	}

/**
 * Format array of data to SQL query conditions
 *
 * @param array $conditions
 * @param string $condition
 *
 * @return array $response
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

		$response = $conditions;
		return $response;
	}

/**
 * Save and retrieve database token based on parameters
 *
 * @param string $table
 * @param array $parameters
 * @param string $foreignKey
 * @param string $foreignValue
 * @param string $sessionId
 * @param string $salt
 * @param integer $expirationMinutes
 *
 * @return array $response
 */
	protected function _getToken($table, $parameters, $foreignKey, $foreignValue, $sessionId = false, $salt = false, $expirationMinutes = false) {
		$tokenParameters = array(
			'conditions' => array(
				'foreign_key' => $foreignKey,
				'foreign_table' => $table,
				'foreign_value' => $foreignValue,
				'string' => $this->_createTokenString($table, $parameters, $sessionId, $salt)
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

		if (
			!empty($expirationMinutes) &&
			is_numeric($expirationMinutes)
		) {
			$tokenParameters['conditions']['expiration'] = date('Y-m-d h:i:s', strtotime('+' . $expirationMinutes . ' minutes'));
		}

		$this->save('tokens', array(
			$tokenParameters['conditions']
		));
		$tokenParameters['fields'] = array(
			'created',
			'expiration',
			'foreign_key',
			'foreign_value',
			'string'
		);
		$response = $this->find('tokens', $tokenParameters);
		return !empty($response['data'][0]) ? $response['data'][0] : array();
	}

/**
 * Hash password
 *
 * @param string $string
 * @param string $timestamp
 *
 * @return array $response
 */
	protected function _hashPassword($string, $timestamp) {
		$response = array(
			'modified' => $modified = date('Y-m-d h:i:s', $timestamp),
			'string' => 'e1Gh7$' . sha1($string . $modified . $this->keys['start'])
		);
		return $response;
	}

/**
 * Retrieve parameterized SQL query and array of values
 *
 * @param string $query
 *
 * @return array $response
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

		$response = array(
			'parameterizedQuery' => implode('', $queryChunks),
			'parameterizedValues' => $parameterValues
		);
		return $response;
	}

/**
 * Parse and filter IPv4 address list.
 *
 * @param array $ips
 * @param boolean $subnets
 *
 * @return array $response
 */
	protected function _parseIps($ips = array(), $subnets = false) {
		$ips = implode("\n", array_map(function($ip) {
			return trim($ip, '.');
		}, array_filter(preg_split("/[](\r\n|\n|\r) <>()~{}|`\"'=?!*&@#$+,[;:_-]/", $ips))));
		$ips = $this->_validateIps($ips, $subnets);
		$response = explode("\n", $ips);
		return $response;
	}

/**
 * Prepare user input value for SQL parameterization parsing with hash strings
 *
 * @param string $value Value
 *
 * @return string $response
 */
	protected function _prepareValue($value) {
		$response = $this->keys['start'] . (is_bool($value) ? (integer) $value : $value) . $this->keys['stop'];
		return $response;
	}

/**
 * Process API action requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _processAction($table, $parameters) {
		$response = array(
			'user' => $parameters['user']
		);

		if (
			!method_exists($this, $action = $parameters['action']) ||
			(
				(!empty($serialize = $this->serialize[$table])) &&
				(!empty($foreignKey = $serialize['foreign_key'])) &&
				(!empty($foreignValue = $parameters['conditions'][$foreignKey])) &&
				($token = $this->_getToken($table, $parameters, $foreignKey, $foreignValue)) === false
			)
		) {
			return false;
		}

		$clearItems = array(
			$table => array()
		);
		$response['items'] = $parameters['items'] = isset($parameters['items']) ? $parameters['items'] : $clearItems;
		$response['tokens'][$table] = $token;

		if (
			empty($parameters['tokens'][$table]) ||
			$parameters['tokens'][$table] === $token
		) {
			if (
				$serialize &&
				!in_array($action, array('find',  'search'))
			) {
				$parameters['items'] = $this->_retrieveItems($parameters);
			}
		} else {
			$action = 'find';
			$response['items'] = $clearItems;
			$response['message'] = array(
				'status' => 'error',
				'text' => 'Your ' . $table . ' have been recently modified and your previously-selected results have been deselected automatically.'
			);
		}

		if (!empty($parameters['redirect'])) {
			$response['redirect'] = $parameters['redirect'];
		}

		$response = array_merge($response, $this->$action($table, $parameters));
		return $response;
	}

/**
 * Construct and execute database queries
 *
 * @param string $query
 * @param array $parameters
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
		$queryChunks = array_fill(0, max(1, ($hasResults ? ceil($parameters['limit'] / $findDataRowChunkSize) : 1)), true);

		foreach ($queryChunks as $queryChunkIndex => $value) {
			if ($hasResults) {
				end($parameterized['parameterizedValues']);
				$offset = $parameterized['parameterizedValues'][key($parameterized['parameterizedValues'])] = $parameters['offset'] + ($queryChunkIndex * $findDataRowChunkSize);
				$limit = prev($parameterized['parameterizedValues']);

				if ($parameters['limit'] > $findDataRowChunkSize) {
					if ($parameters['limit'] < (($queryChunkIndex + 1) * $limit)) {
						$limit = $parameters['limit'] + $parameters['offset'] - $offset;
					} else {
						$limit = $findDataRowChunkSize;
					}
				}

				$parameterized['parameterizedValues'][key($parameterized['parameterizedValues'])] = $limit;
			}

			if (
				!empty($parameterized['parameterizedValues']) &&
				is_array($parameterized['parameterizedValues'])
			) {
				foreach ($parameterized['parameterizedValues'] as $parameterizedValueIndex => $parameterizedValue) {
					if ($parameterizedValue === $this->keys['salt'] . 'is_null' . $this->keys['salt']) {
						$parameterized['parameterizedValues'][$parameterizedValueIndex] = null;
					}
				}
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
 * @param array $parameters
 *
 * @return array $response
 */
	protected function _request($parameters) {
		$response = array(
			'code' => 400,
			'message' => array(
				'status' => 'error',
				'text' => 'Request parameters are required for API.'
			)
		);

		if (
			!empty($_POST['json']) &&
			is_string($_POST['json'])
		) {
			$parameters = json_decode($_POST['json'], true);
			$response['message']['text'] = 'No results found, please try again.';

			if (
				empty($table = $parameters['table']) ||
				empty($this->permissions[$table])
			) {
				$response['message']['text'] = 'Invalid request table, please try again.';
			} else {
				if (
					($parameters['action'] = $action = (!empty($parameters['action']) ? $parameters['action'] : 'find')) &&
					(
						empty($this->permissions[$table][$action]) ||
						!method_exists($this, $action)
					)
				) {
					$response['message']['text'] = 'Invalid request action, please try again.';
				} else {
					if (
						($fieldPermissions = $this->permissions[$table][$action]['fields']) &&
						($parameters['fields'] = $fields = !empty($parameters['fields']) ? $parameters['fields'] : $fieldPermissions) &&
						count(array_intersect($fields, $fieldPermissions)) !== count($fields)
					) {
						$response['message']['text'] = 'Invalid request fields, please try again.';
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
							$response['message']['text'] = 'Invalid request parameters, please try again.';
						} else {
							$parameters = array_merge($parameters, array(
								'redirect' => '',
								'session' => $this->_createTokenString($table, array(), sha1($parameters['keys']['users'])),
								'user' => $this->_authenticate('users', $parameters)
							));
							$response = array(
								'code' => 407,
								'message' => array(
									'status' => 'error',
									'text' => 'Authentication required, please log in and try again.'
								),
								'redirect' => $this->settings['base_url'] . '#login',
								'user' => false
							);
							unset($parameters['conditions']['session_id']);
							unset($parameters['conditions']['user_id']);
							$userIdExists = (
								$table === 'users' ||
								in_array('user_id', $this->permissions[$table][$action]['fields'])
							);

							if (
								empty($this->permissions[$table][$action]['group']) ||
								(
									!empty($parameters['user']) &&
									$userIdExists &&
									($parameters['conditions']['user_id'] = $parameters['user']['id'])
								) ||
								(
									in_array('session_id', $this->permissions[$table][$action]['fields']) &&
									($parameters['conditions']['session_id'] = $parameters['session'])
								)
							) {
								if (array_search($parameters['user']['permissions'], $this->groups) > 1) {
									$foreignId = substr_replace($table, ($consonantPlural = (substr($table, -3) === 'ies')) ? 'y_id' : '_id', $consonantPlural ? -3 : -1);
									unset($parameters['conditions']['user_id']);

									if (
										$userIdExists &&
										(
											!empty($id = $parameters['conditions'][$foreignId]) ||
											!empty($id = $parameters['conditions']['id'])
										)
									) {
										$userData = $this->find($table, array(
											'conditions' => array(
												'id' => $id
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
 * @param array $parameters
 *
 * @return array $response
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

					$ids = $this->find($table, array_merge($parameters, array(
						'fields' => array(
							'id'
						),
						'limit' => $index,
						'offset' => 0
					)));
					$conditions = array(
						'id' => !empty($ids['data']) ? array_intersect_key($ids['data'], $itemIndexes) : array()
					);

					if (
						!empty($parameters['data']['instant_replacement']) &&
						$parameters['action'] == 'replace'
					) {
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
 * @param array $parameters
 *
 * @return boolean $response
 */
	protected function _sendMail($parameters) {
		if (
			empty($from = $this->_validateEmailFormat($parameters['from'])) ||
			empty($to = $this->_validateEmailFormat($parameters['to'])) ||
			(
				empty($subject = $parameters['subject']) ||
				!is_string($subject)
			) ||
			(
				empty($template = $parameters['template']) ||
				!is_array($template) ||
				!file_exists($templateFile = $this->settings['base_path'] . '/views/emails/' . $template['name'] . '.php') ||
				(
					!empty($templateParameters = $template['parameters']) &&
					!is_array($templateParameters)
				)
			) ||
			(
				!empty($headers = $parameters['headers']) &&
				!is_array($headers)
			)
		) {
			return false;
		}

		$headers = array(
			'charset' => 'utf-8',
			'Content-Type' => 'text/plain',
			'From' => '"' . $this->settings['site_name'] . '" <' . $from . '>'
		);
		array_walk($headers, function(&$headerValue, $headerKey) {
			$headerValue = $headerKey . ': ' . $headerValue;
		});
		$headers = implode("\r\n", $headers);
		require_once($templateFile);
		$response = mail($to, $subject, $message, $headers);
		return $response;
	}

/**
 * Validate email address format
 *
 * @param string $email
 *
 * @return mixed [string/boolean] $response Email if valid email address format, false if invalid
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
			count($domainStringSplitCharacters) < 2 ||
			strlen(end($domainStringSplitCharacters)) < 2 ||
			strstr(' .-', $lastLocalStringCharacter = end($localStringCharacters)) !== false ||
			strstr(' .-', $firstLocalStringCharacter = reset($localStringCharacters)) !== false ||
			strstr(' .-', $lastDomainStringCharacter = end($domainStringCharacters)) !== false ||
			strstr(' .-', $firstDomainStringCharacter = reset($domainStringCharacters)) !== false ||
			strpos($domainString, '-.') !== false ||
			strpos($domainString, '.-') !== false ||
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

		$response = $email;
		return $response;
	}

/**
 * Validate IPv4 address/subnet list
 *
 * @param array $ips Filtered IPv4 address/subnet list
 * @param boolean $subnets Allow partial IPv4 subnets instead of full /32 mask
 *
 * @return array $response
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

			if (!empty($ips[$key])) {
				$ips[$key] = implode('.', $splitIpSubnets);
			}
		}

		$response = implode("\n", array_unique($ips));
		return $response;
	}

/**
 * Verify configuration keys
 *
 * @return boolean $response
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
					'id' => 'keys'
				),
				'fields' => array(
					'id',
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
						'id' => 'keys',
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
 * @param string $password
 * @param array $user
 *
 * @return boolean $response
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
 * @param string $table
 * @param array $conditions
 *
 * @return boolean $response
 */
	public function delete($table, $conditions = array()) {
		$query = 'DELETE FROM ' . $table;

		if (
			!empty($conditions) &&
			is_array($conditions)
		) {
			$query .= ' WHERE ' . implode(' AND ', $this->_formatConditions($conditions));
		}

		$response = $this->_query($query, $parameters);
		return $response;
	}

/**
 * Database helper method for retrieving data
 *
 * @param string $table
 * @param array $parameters
 *
 * @return mixed [array/boolean] $response Return associative array if it exists, otherwise return boolean ($execute)
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

		if (!empty($sortField = $parameters['sort']['field'])) {
			$query .= ' ORDER BY ' . $sortField . ' ' . (!empty($parameters['sort']['order']) ? $parameters['sort']['order'] : 'DESC') . ', ' . implode(' DESC, ', array_diff(array('modified', 'created', 'id'), array($sortField))) . ' DESC';
		}

		$parameters = array_merge($parameters, array(
			'count' => $count = $parameters['count'] = !empty($count[0]['COUNT(id)']) ? $count[0]['COUNT(id)'] : 0,
			'field_count' => !empty($parameters['fields']) && is_array($parameters['fields']) ? count($parameters['fields']) : 0,
			'limit' => !empty($parameters['limit']) && $parameters['limit'] < $count ? $parameters['limit'] : $count,
			'offset' => !empty($parameters['offset']) ? $parameters['offset'] : 0
		));

		$query = 'SELECT ' . (!empty($parameters['fields']) && is_array($parameters['fields']) ? implode(',', $parameters['fields']) : '*') . $query;
		$query .= ' LIMIT ' . $this->_prepareValue($parameters['limit']) . ' OFFSET ' . $this->_prepareValue($parameters['offset']);
		$data = $this->_query($query, $parameters);
		$response = array(
			'count' => $count,
			'data' => $data
		);

		if (empty($count)) {
			$response['message'] = array(
				'status' => 'error',
				'text' => 'No ' . str_replace('_', ' ', $table) . ' found, please try again.'
			);
		}

		return $response;
	}

/**
 * Routing helper method
 *
 * @param array $parameters
 *
 * @return mixed [array/exit] $response Return data if action exists, redirect to base URL if action doesn't exist
 */
	public function route($parameters) {
		if (
			!empty($action = array_shift(array_reverse(explode('/', str_replace('.php', '', $parameters['route']['file']))))) &&
			method_exists($this, $action)
		) {
			$response = array_merge($this->$action($parameters), array(
				'action' => $action,
				'table' => str_replace('/', '', strrchr(dirname($parameters['route']['file']), '/'))
			));
			return $response;
		}

		$this->redirect($this->settings['base_url']);
	}

/**
 * Database helper method for saving data
 *
 * @param string $table
 * @param array $rows
 *
 * @return boolean $response
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
					if (is_bool($value)) {
						$value = (integer) $value;
					}

					if (is_null($value)) {
						$value = $this->keys['salt'] . 'is_null' . $this->keys['salt'];
					}

					return $value;
				}, array_values($row));

				if (
					!in_array('created', $fields) &&
					!in_array('id', $fields)
				) {
					$fields[] = 'created';
					$values[] = date('Y-m-d H:i:s', time());
				}

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

		$response = $success;
		return $response;
	}

}
