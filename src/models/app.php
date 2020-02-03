<?php
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
				$existingToken = $this->fetch('tokens', array(
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
					$existingData = $this->fetch($table, array(
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
	 * Authenticate public API endpoint requests
	 *
	 * @param string $table
	 * @param array $parameters
	 * @param array $conditions
	 *
	 * @return array $response
	 */
		protected function _authenticateEndpoint($table, $parameters, $conditions = array()) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error authenticating your API request, please try again.')
				)
			);

			if (!empty($conditions)) {
				$endpointSettings = $this->fetch($table, array(
					'conditions' => $conditions,
					'fields' => array(
						'id',
						'endpoint_enable',
						'endpoint_password',
						'endpoint_require_authentication',
						'endpoint_require_match',
						'endpoint_username',
						'endpoint_whitelisted_ips'
					),
					'limit' => 1
				));

				if (!empty($endpointSettings['count'])) {
					if (empty($endpointSettings['data'][0]['endpoint_enable'])) {
						$response['message']['text'] = 'API endpoint is deactivated.';
					} else {
						$response['message']['status'] = 'success';

						if (!empty($endpointSettings['data'][0]['endpoint_require_authentication'])) {
							$response['message'] = array(
								'status' => 'error',
								'text' => 'API endpoint authentication required, please try again.'
							);

							if (
								(
									!empty($parameters['data']['authentication']['username']) &&
									!empty($parameters['data']['authentication']['password'])
								) &&
								(
									$parameters['data']['authentication']['username'] === $endpointSettings['data'][0]['endpoint_username'] &&
									$parameters['data']['authentication']['password'] === $endpointSettings['data'][0]['endpoint_password']
								)
							) {
								$response['message']['status'] = 'success';
							}

							if (
								$response['message']['status'] === 'error' ||
								$endpointSettings['data'][0]['endpoint_require_match']
							) {
								$whitelistedIps = explode("\n", $endpointSettings['data'][0]['endpoint_whitelisted_ips']);

								if (!in_array($_SERVER['REMOTE_ADDR'], $whitelistedIps)) {
									$response['message'] = array(
										'status' => 'error',
										'text' => ($endpointSettings['data'][0]['endpoint_require_match'] ? 'Both username/password and ' : '') . 'IP address ' . $_SERVER['REMOTE_ADDR'] . ' must be authenticated. Please check your API endpoint settings and try again.'
									);
								}
							}
						}
					}
				}
			}

			if ($response['message']['status'] === 'success') {
				$response['message']['text'] = 'API endpoint authenticated successfully.';
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
			$interval = ($item['interval_value'] * ($item['interval_type'] == 'year' ? 12 : 1));
			$response = number_format(((max((100 - ($interval)) + 1, 80) / 100) * ($item['quantity'] * $item['price_per'])) * $interval, 2, '.', '');
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
			return $response;
		}

	/**
	 * Helper method for calling model methods
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return mixed $response
	 */
		protected function _call($table, $parameters = array()) {
			$response = false;
			$modelName = ucwords($table . 'Model');
			$modelPath = $this->settings['base_path'] . '/models/' . $table . '.php';

			if (
				!class_exists($modelName) &&
				file_exists($modelPath)
			) {
				require_once($modelPath);
			}

			if (empty($this->$modelName)) {
				$this->$modelName = new $modelName();
			}

			if (
				!empty($parameters['methodName']) &&
				method_exists($this->$modelName, $parameters['methodName'])
			) {
				$methodName = $parameters['methodName'];
				$methodParameters = !empty($parameters['methodParameters']) ? $parameters['methodParameters'] : array();
				$response = call_user_func_array(array($this->$modelName, $methodName), $methodParameters);
			}

			return $response;
		}

	/**
	 * Create token string from parameters and results
	 *
	 * @param string $table
	 * @param array $parameters
	 * @param string $sessionId
	 * @param string $salt
	 * @param array $encode
	 *
	 * @return array $response
	 */
		protected function _createTokenString($table, $parameters, $sessionId = false, $salt = false, $encode = false) {
			$response = array(
				$this->keys['start']
			);
			$tokenStringParameters = array(
				'fields' => array(
					'id'
				),
				'limit' => 1,
				'sort' => array(
					'field' => 'modified',
					'order' => 'DESC'
				)
			);

			if (!empty($parameters['conditions'])) {
				$tokenStringParameters['conditions'] = $parameters['conditions'];
			}

			if (!empty($encode)) {
				$table = $encode['data_table'];
				$tokenStringParameters['sort'] = $encode['sort'];

				if (empty($tokenStringParameters['conditions'])) {
					$tokenStringParameters['conditions'] = array(
						'user_id' => $parameters['user']['id']
					);
				}
			}

			if (!empty($tokenStringParameters['conditions'])) {
				$data = $this->fetch($table, $tokenStringParameters);
				$response[] = $data['count'] . $data['data'][0];
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
	 * cURL function
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _curl($parameters) {
			$response = array(
				'data' => false,
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error processing your cURL request, please try again.')
				)
			);
			$curl = curl_init();

			if ($curl) {
				$response['message']['text'] = 'URL parameter is required for your cURL request.';

				if (!empty($parameters['url'])) {
					$response['message']['text'] = $defaultMessage;
					curl_setopt($curl, CURLOPT_URL, $parameters['url']);

					if (
						!empty($parameters['headers']) &&
						is_array($parameters['headers'])
					) {
						curl_setopt($curl, CURLOPT_HTTPHEADER, $parameters['headers']);
					}

					if (
						!empty($parameters['post_fields']) &&
						is_array($parameters['post_fields'])
					) {
						curl_setopt($curl, CURLOPT_POST, true);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters['post_fields']);
					}

					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

					$response['data'] = curl_exec($curl);
					curl_close($curl);
				}
			}

			if ($response['data']) {
				$response['message'] = array(
					'status' => 'success',
					'text' => 'Successfully processed cURL request.'
				);
			}

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

				if (
					is_array($value) &&
					count($value) != count($value, COUNT_RECURSIVE)
				) {
					$conditions[$key] = implode(' ' . $condition . ' ', $this->_formatConditions($value, $condition)) ;
				} else {
					if (is_array($value)) {
						array_walk($value, function(&$fieldValue, $fieldKey) use ($key, $operators) {
							$key = (strlen($fieldKey) > 1 && is_string($fieldKey) ? $fieldKey : $key);
							$fieldValue = (is_null($fieldValue) ? $key . ' IS NULL' : trim(in_array($operator = trim(substr($key, strpos($key, ' '))), $operators) ? $key : $key . ' =') . ' ' . $this->_prepareValue($fieldValue));
						});
					} else {
						$value = array((is_null($value) ? $key . ' IS NULL' : trim(in_array($operator = trim(substr($key, strpos($key, ' '))), $operators) ? $key : $key . ' =') . ' ' . $this->_prepareValue($value)));
					}

					$conditions[$key] = implode(' ' . (strpos($key, '!=') !== false ? 'AND' : $condition) . ' ', $value);
				}

				$conditions[$key] = ($key === 'NOT' ? 'NOT' : null) . ($conditions[$key] ? '(' . $conditions[$key] . ')' : $conditions[$key]);
			}

			$response = $conditions;
			return $response;
		}

	/**
	 * Format plural to singular string
	 *
	 * @param string $string
	 *
	 * @return string $response
	 */
		protected function _formatPluralToSingular($string) {
			$response = substr_replace($string, ($consonantPlural = (substr($string, -3) === 'ies')) ? 'y' : '', $consonantPlural ? -3 : -1);
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
	 * @param array $encode
	 *
	 * @return array $response
	 */
		protected function _getToken($table, $parameters, $foreignKey, $foreignValue, $sessionId = false, $salt = false, $expirationMinutes = false, $encode = false) {
			$tokenParameters = array(
				'conditions' => array(
					'foreign_key' => $foreignKey,
					'foreign_table' => $table,
					'foreign_value' => $foreignValue,
					'string' => $this->_createTokenString($table, $parameters, $sessionId, $salt, $encode)
				),
				'fields' => array(
					'id'
				),
				'limit' => 1
			);
			$existingToken = $this->fetch('tokens', $tokenParameters);

			if (!empty($existingToken['count'])) {
				$tokenParameters['conditions']['id'] = $existingToken['data'][0];
			}

			if (
				!empty($expirationMinutes) &&
				is_numeric($expirationMinutes)
			) {
				$tokenParameters['conditions']['expiration'] = date('Y-m-d H:i:s', strtotime('+' . $expirationMinutes . ' minutes'));
			}

			$this->save('tokens', array(
				$tokenParameters['conditions']
			));
			$tokenParameters['fields'] = array(
				'created',
				'expiration',
				'foreign_key',
				'foreign_value',
				'id',
				'string'
			);
			$response = $this->fetch('tokens', $tokenParameters);
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
				'modified' => $modified = date('Y-m-d H:i:s', $timestamp),
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
	 * Parsing helper method for converting parameter keys from snake_case to camelCase
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _parseParametersToCamelCase($parameters) {
			$response = array();

			foreach ($parameters as $parameterKey => $parameterValue) {
				unset($parameters[$parameterKey]);

				if (strpos($parameterKey, '_') !== false) {
					$parameterKeyChunks = explode('_', $parameterKey);
					$parameterKeyFirstChunk = array_shift($parameterKeyChunks);
					$parameterKey = $parameterKeyFirstChunk . implode('', array_map(function($parameterKeyChunk) {
						return ucwords($parameterKeyChunk);
					}, $parameterKeyChunks));
				}

				if (
					is_string($parameterValue) &&
					($jsonString = trim($parameterValue)) &&
					($jsonStringLength = strlen($jsonString)) &&
					(
						(
							stripos($jsonString, '{') === 0 &&
							(stripos($jsonString, '}', -1) + 1) === $jsonStringLength
						) ||
						(
							stripos($jsonString, '[{') === 0 &&
							(stripos($jsonString, '}]', -1) + 2) === $jsonStringLength
						)
					) &&
					($decodedJsonString = json_decode($jsonString, true)) &&
					is_array($decodedJsonString)
				) {
					$parameterValue = $decodedJsonString;
				}

				$parameters[$parameterKey] = $parameterValue;

				if (is_array($parameterValue)) {
					$parameters[$parameterKey] = $this->_parseParametersToCamelCase($parameterValue);
				}
			}

			$response = $parameters;
			return $response;
		}

	/**
	 * Parsing helper method for converting parameter keys from camelCase to snake_case
	 *
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		protected function _parseParametersToSnakeCase($parameters) {
			$response = array();

			foreach ($parameters as $parameterKey => $parameterValue) {
				unset($parameters[$parameterKey]);

				if (strtolower($parameterKey) !== $parameterKey) {
					$parameterKeyCharacters = str_split($parameterKey);
					$parameterKeyCharacters[0] = strtolower($parameterKeyCharacters[0]);
					$parameterKey = implode('', array_map(function($parameterKeyCharacter) {
						if (ctype_upper($parameterKeyCharacter)) {
							$parameterKeyCharacter = '_' . strtolower($parameterKeyCharacter);
						}

						return $parameterKeyCharacter;
					}, $parameterKeyCharacters));
				}

				$parameters[$parameterKey] = $parameterValue;

				if (is_array($parameterValue)) {
					$parameters[$parameterKey] = $this->_parseParametersToSnakeCase($parameterValue);
				}
			}

			$response = $parameters;
			return $response;
		}

	/**
	 * Parsing helper method for parameter object case types
	 *
	 * @param array $parameters
	 * @param string $caseType
	 *
	 * @return array $response
	 */
		protected function _parseParameters($parameters, $caseType) {
			$response = array();
			$parseMethod = '_parseParametersTo' . ucwords($caseType) . 'Case';

			if (method_exists($this, $parseMethod)) {
				foreach ($parameters as $parameterKey => $parameterValue) {
					$parameter = array(
						$parameterKey => $parameterValue
					);
					$parsedParameter = $this->$parseMethod($parameter);
					unset($parameters[$parameterKey]);
					$parameters = array_merge_recursive($parameters, $parsedParameter);
				}
			}

			$response = $parameters;
			return $response;
		}

	/**
	 * Parsing helper method for a form data item if correct brackets exist
	 *
	 * @param string $formDataItemKey
	 * @param string $formDataItemValue
	 *
	 * @return array $response
	 */
		protected function _parseFormDataItem($formDataItemKey, $formDataItemValue) {
			$parsedFormDataItem = array(
				$formDataItemKey => $formDataItemValue
			);

			if (
				!empty($formDataItemKey) &&
				($openingBracket = stripos($formDataItemKey, '[')) !== false &&
				($closingBracket = stripos($formDataItemKey, ']', -1)) !== false &&
				$closingBracket === (strlen($formDataItemKey) - 1)
			) {
				$parsedFormDataItemKey = substr_replace(substr($formDataItemKey, $openingBracket), '', -1);
				$parsedFormDataItemKey = substr($parsedFormDataItemKey, 1);

				$parsedFormDataItem = array(
					substr_replace($formDataItemKey, '', $openingBracket) => $this->_parseFormDataItem($parsedFormDataItemKey, $formDataItemValue)
				);
			}

			$response = $parsedFormDataItem;
			return $response;
		}

	/**
	 * Parsing helper method for form data items
	 *
	 * @param array $formDataItems
	 *
	 * @return array $response
	 */
		protected function _parseFormDataItems($formDataItems) {
			foreach ($formDataItems as $formDataItemKey => $formDataItemValue) {
				$formDataItem = array(
					$formDataItemKey => $formDataItemValue
				);
				$parsedFormDataItem = $this->_parseFormDataItem($formDataItemKey, $formDataItemValue);

				if ($formDataItem !== $parsedFormDataItem) {
					unset($formDataItems[$formDataItemKey]);
					$formDataItems = array_merge_recursive($formDataItems, $parsedFormDataItem);
				}
			}

			$response = $formDataItems;
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
			if (!is_array($ips)) {
				$ips = array_filter(preg_split("/[](\r\n|\n|\r) <>()~{}|`\"'=?!*&@#$+,[;:_-]/", $ips));
			}

			$ips = implode("\n", array_map(function($ip) {
				return trim($ip, '.');
			}, $ips));
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
			$clearItems = $message = array();

			if (
				!method_exists($this, $action = $parameters['action']) ||
				(
					isset($this->encode[$table]) &&
					($encode = $this->encode[$table]) &&
					($foreignKey = $encode['foreign_key']) &&
					(
						isset($parameters['conditions'][$foreignKey]) ||
						$foreignKey == 'user_id'
					) &&
					($foreignValue = $foreignKey == 'user_id' ? $parameters['user']['id'] : $parameters['conditions'][$foreignKey]) &&
					($token = $this->_getToken($table, $parameters, $foreignKey, $foreignValue, false, false, false, $encode)) === false
				)
			) {
				return false;
			}

			if (!empty($parameters['item_list_name'])) {
				$clearItems = array(
					$parameters['item_list_name'] => array(
						'count' => 0,
						'data' => array(),
						'name' => $parameters['item_list_name'],
						'table' => $table
					)
				);
			}

			$defaultAction = !empty($encode['default_action']) ? $encode['default_action'] : 'fetch';
			$itemListName = $parameters['item_list_name'] = !empty($parameters['item_list_name']) ? $parameters['item_list_name'] : $table;
			$response['items'] = $parameters['items'] = isset($parameters['items']) ? $parameters['items'] : $clearItems;
			$response['tokens'][$itemListName] = $token;

			if (
				!empty($parameters['data']) &&
				is_array($parameters['data'])
			) {
				$parameters['data'] = $this->_parseFormDataItems($parameters['data']);
			}

			if (
				$encode &&
				(
					empty($encode['exclude_actions']) ||
					(
						is_array($encode['exclude_actions']) &&
						!in_array($action, $encode['exclude_actions'])
					)
				)
			) {
				if (
					empty($parameters['tokens'][$itemListName]) ||
					$parameters['tokens'][$itemListName] === $token
				) {
					$actionsProcessing = $this->fetch('actions', array(
						'conditions' => array(
							'AND' => array(
								'processed' => false,
								'OR' => array(
									'AND' => array(
										'processing' => true,
										'modified >' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
									)
								)
							)
						),
						'fields' => array(
							'id'
						)
					));

					if (
						empty($actionsProcessing['count']) &&
						!empty($parameters['items'][$itemListName])
					) {
						$itemIndexLineCount = count($parameters['items'][$itemListName]['data']);
						$items = $this->_retrieveItems($parameters);
						$parametersToEncode = array_intersect_key($parameters, array(
							'action' => true,
							'conditions' => true,
							'data' => true,
							'item_list_name' => true,
							'limit' => true,
							'sort' => true,
							'table' => true,
							'tokens' => true
						));
						$parametersToEncode['item_count'] = $items[$itemListName]['count'];

						if ($parametersToEncode['item_count'] === 1) {
							$parametersToEncode['table'] = $this->_formatPluralToSingular($parametersToEncode['table']);
						}

						$actionData = array(
							array(
								'chunks' => $itemIndexLineCount,
								'encoded_items_to_process' => json_encode($items[$itemListName]['data']),
								'encoded_parameters' => json_encode($parametersToEncode),
								'foreign_key' => $foreignKey,
								'foreign_value' => $foreignValue,
								'processed' => false,
								'progress' => 0,
								'token_id' => $token['id'],
								'user_id' => $parameters['user']['id']
							)
						);

						if ($itemIndexLineCount === 1) {
							if (is_string($parameters['items'][$itemListName]['data'][0])) {
								$parameters['items'] = $this->_retrieveItems($parameters, true);
							}

							$actionData[0] = array_merge($actionData[0], array(
								'processed' => true,
								'progress' => 100
							));
						}

						if ($this->save('actions', $actionData)) {
							$response['processing'] = $actionData[0];

							if ($itemIndexLineCount > 1) {
								$action = $defaultAction;
								$message = array(
									'status' => 'success',
									'text' => 'Your action to ' . $action . ' ' . $items[$itemListName]['count'] . ' selected ' . $table . ' is currently processing.'
								);
							}
						} else {
							$message = array(
								'status' => 'error',
								'text' => 'Error processing action to ' . $action . ' ' . $items[$itemListName]['count'] . ' selected ' . $table . ', please try again.'
							);
						}
					}
				} else {
					$action = $defaultAction;
					$response['items'] = $clearItems;

					if (!empty($parameters['items'][$itemListName])) {
						$dataTable = $table;

						if (!empty($encode['data_table'])) {
							$dataTable = str_replace('_', ' ', $encode['data_table']);
						}

						$message = array(
							'status' => 'error',
							'text' => 'Your ' . $dataTable . ' have been recently modified and your previously-selected results have been deselected automatically.'
						);
					}
				}
			}

			if (!empty($foreignValue)) {
				if (!isset($response['processing'])) {
					$response['processing'] = $this->_retrieveProcessingAction($foreignKey, $foreignValue);
				}

				if (!empty($response['processing'])) {
					$response['processing']['parameters'] = json_decode($response['processing']['encoded_parameters'], true);
				}
			}

			if (!empty($parameters['redirect'])) {
				$response['redirect'] = $parameters['redirect'];
			}

			if (!empty($userId = $parameters['user']['id'])) {
				$subscriptions = $this->fetch('subscriptions', array(
					'conditions' => array(
						'user_id' => $userId
					),
					'fields' => array(
						'id',
						'interval_type',
						'interval_value',
						'invoice_id',
						'payment_attempts',
						'plan_id',
						'price',
						'status',
						'user_id'
					)
				));

				if (!empty($subscriptions['count'])) {
					$response['user']['subscriptions'] = $subscriptions['data'];
				}
			}

			$response = array_merge($response, $this->$action($table, $parameters));

			if (!empty($message)) {
				$response['message'] = $message;
			}

			return $response;
		}

	/**
	 * Process public API endpoint requests
	 *
	 * @param string $table
	 * @param array $parameters
	 * @param array $conditions
	 *
	 * @return array $response
	 */
		protected function _processEndpointRequest($table, $parameters, $conditions = array()) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'There aren\'t any ' . $table . ' available to ' . $parameters['action'] . ', please log in and check your order at ' . $this->settings['base_domain'] . $this->settings['base_url'] . 'orders/' . $orderId . '.'
				)
			);

			if (!empty($conditions)) {
				$items = $this->fetch($table, array(
					'conditions' => $conditions,
					'fields' => array(
						'id'
					)
				));

				if (!empty($items['count'])) {
					$response = array(
						'conditions' => $conditions,
						'message' => array(
							'status' => 'success',
							'text' => 'API endpoint items retrieved successfully.'
						),
						'items' => $items['data']
					);
				}
			}

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
				$response['message']['text'] = 'No results found, please try again.';
				$parameters = $this->_parseParameters(json_decode($_POST['json'], true), 'snake');

				if (
					!isset($parameters['table']) ||
					(
						($table = $parameters['table']) &&
						empty($table)
					) ||
					empty($this->permissions[$table])
				) {
					$response['message']['text'] = 'Invalid request table, please try again.';
				} else {
					if (
						($parameters['action'] = $action = (!empty($parameters['action']) ? $parameters['action'] : 'fetch')) &&
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
								$response = array(
									'code' => 407,
									'message' => array(
										'status' => 'error',
										'text' => 'Authentication required, please log in and try again.'
									),
									'redirect' => $this->settings['base_url'] . '#login',
									'user' => false
								);
								$parameters = array_merge($parameters, array(
									'redirect' => '',
									'session' => '_' . $this->_createTokenString($table, array(), sha1($parameters['keys']['users'])),
									'user' => $this->_authenticate('users', $parameters)
								));
								unset($parameters['conditions']['session_id']);
								unset($parameters['conditions']['user_id']);
								$userIdExists = (
									$table === 'users' ||
									in_array('user_id', $this->permissions[$table][$action]['fields'])
								);

								if (
									empty($this->permissions[$table][$action]['group']) ||
									(
										$userIdExists &&
										(
											(
												!empty($parameters['user']) &&
												($parameters['conditions']['user_id'] = $parameters['user']['id'])
											) ||
											(
												empty($this->permissions[$table][$action]['group']) &&
												($parameters['conditions']['user_id'] = $parameters['session'])
											)
										)
									)
								) {
									if (
										!empty($parameters['user']['permissions']) &&
										array_search($parameters['user']['permissions'], $this->groups) > 1
									) {
										$foreignId = $this->_formatPluralToSingular($table);
										unset($parameters['conditions']['user_id']);

										if (
											$userIdExists &&
											(
												(
													!empty($parameters['conditions'][$foreignId]) &&
													($id = $parameters['conditions'][$foreignId])
												) ||
												(
													!empty($parameters['conditions']['id']) &&
													($id = $parameters['conditions']['id'])
												)
											)
										) {
											$userData = $this->fetch($table, array(
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

			$response = $this->_parseParameters($response, 'camel');
			return $response;
		}

	/**
	 * Decode indexes and retrieve corresponding item IDs based on parameters
	 *
	 * @param array $parameters
	 * @param boolean $decode
	 *
	 * @return array $response
	 */
		protected function _retrieveItems($parameters, $decode = false) {
			$response = array();

			if (!empty($parameters['items'])) {
				foreach ($parameters['items'] as $itemListName => $items) {
					$response[$itemListName] = array(
						'count' => count($items['data']),
						'data' => $items['data'],
						'name' => $itemListName,
						'table' => $items['table']
					);

					if (
						!empty($items['data']) &&
						!empty($this->encode[$items['table']])
					) {
						$itemIndexes = array();
						$itemIndexLines = $items['data'];
						$index = $itemCount = 0;

						foreach ($itemIndexLines as $offsetIndex => $itemIndexLine) {
							$itemIndexLineChunks = explode('_', $itemIndexLine);

							foreach ($itemIndexLineChunks as $itemIndexLineChunk) {
								$itemStatus = substr($itemIndexLineChunk, 0, 1);
								$itemStatusCount = substr($itemIndexLineChunk, 1);

								if ($itemStatus) {
									if ($decode) {
										for ($i = 0; $i < $itemStatusCount; $i++) {
											$itemIndexes[$index + $i] = 1;
										}
									}

									$itemCount += $itemStatusCount;
								}

								$index += $itemStatusCount;
							}
						}

						$response[$itemListName]['count'] = $itemCount;

						if (
							empty($itemIndexes) ||
							!$itemCount
						) {
							continue;
						}

						if ($decode) {
							$dataTable = !empty($this->encode[$items['table']]['data_table']) ? $this->encode[$items['table']]['data_table'] : $items['table'];
							$itemParameters = array_merge($parameters, array(
								'fields' => array(
									'id'
								),
								'limit' => $index,
								'offset' => 0
							));

							if (!empty($this->encode[$items['table']]['sort'])) {
								$itemParameters['sort'] = $this->encode[$items['table']]['sort'];
							}

							$itemIds = $this->fetch($dataTable, $itemParameters);
							$conditions = array(
								'id' => !empty($itemIds['data']) ? array_values(array_intersect_key($itemIds['data'], $itemIndexes)) : array()
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

							$response[$itemListName] = array_merge($response[$itemListName], $this->fetch($dataTable, array(
								'conditions' => $conditions,
								'fields' => array(
									'id'
								)
							)));
						}
					}
				}
			}

			return $response;
		}

	/**
	 * Retrieve most-recent processing action
	 *
	 * @param string $foreignKey
	 * @param mixed [integer/string] $foreignValue
	 *
	 * @return array $response
	 */
		protected function _retrieveProcessingAction($foreignKey, $foreignValue) {
			$response = false;
			$actionData = $this->fetch('actions', array(
				'conditions' => array(
					'foreign_key' => $foreignKey,
					'foreign_value' => $foreignValue,
					'processed' => false
				),
				'fields' => array(
					'chunks',
					'encoded_items_processed',
					'encoded_items_to_process',
					'encoded_parameters',
					'foreign_key',
					'foreign_value',
					'id',
					'progress',
					'token_id'
				),
				'limit' => 1
			));

			if (!empty($actionData['count'])) {
				$response = $actionData['data'][0];
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
				$existingKeys = $this->fetch('settings', array(
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
					$users = $this->fetch('users', array(
						'fields' => array(
							'id',
							'password',
							'password_modified'
						)
					));

					if (!empty($users['count'])) {
						foreach ($users['data'] as $key => $user) {
							$users['data'][$key]['password'] = '';
							$users['data'][$key]['password_modified'] = date('Y-m-d H:i:s', time());
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
				$query .= ' WHERE ' . ($queryConditions = implode(' AND ', array_filter($this->_formatConditions($conditions))));

				if (empty($queryConditions)) {
					return true;
				}
			}

			$response = $this->_query($query);
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
		public function fetch($table, $parameters = array()) {
			$query = ' FROM ' . $table;

			if (
				!empty($parameters['conditions']) &&
				is_array($parameters['conditions'])
			) {
				$query .= ' WHERE ' . implode(' AND ', $this->_formatConditions($parameters['conditions']));
			}

			$count = $this->_query('SELECT COUNT(id)' . $query);

			if (!empty($parameters['sort'])) {
				$query .= ' ORDER BY ';

				if ($parameters['sort'] === 'random') {
					$query .= 'RAND()';
				} elseif (!empty($sortField = $parameters['sort']['field'])) {
					$query .= $sortField . ' ' . (!empty($parameters['sort']['order']) ? $parameters['sort']['order'] : 'DESC') . ', ' . implode(' DESC, ', array_diff(array('modified', 'created', 'id'), array($sortField))) . ' DESC';
				}
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
				'data' => is_array($data) ? $data : array()
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
				$response = array_merge($this->$action($action, $parameters), array(
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
			$ids = $queries = array();
			$response = true;

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
					$response = false;
				}
			}

			return $response;
		}

	}
?>
