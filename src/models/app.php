<?php
/**
 * App Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/config/config.php');

class AppModel extends Config {

/**
 * Create token string from parameters and results
 *
 * @param array $parameters Parameters
 *
 * @return array Token string
 */
	protected function _createTokenString($parameters) {
		return sha1($this->config['database']['sanitizeKeys']['hashSalt'] . json_encode($this->find($parameters['table'], array(
			'conditions' => $parameters['conditions'],
			'fields' => array(
				'id'
			),
			'limit' => 1,
			'sort' => array(
				'field' => 'modified',
				'order' => 'DESC'
			)
		))));
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
 * @param array $parameters Parameters
 *
 * @return array $token Token
 */
	protected function _getToken($parameters) {
		$tokenParameters = array(
			'conditions' => array(
				'foreign_key' => $key = key($parameters['conditions']),
				'foreign_table' => $parameters['table'],
				'foreign_value' => $parameters['conditions'][$key],
				'string' => $this->_createTokenString($parameters)
			),
			'fields' => array(
				'id'
			),
			'limit' => 1
		);

		$existingToken = $this->find('tokens', $tokenParameters);

		if (!empty($existingToken['data'][0])) {
			$tokenParameters['conditions']['id'] = $existingToken['data'][0];
		}

		$this->save('tokens', array(
			$tokenParameters['conditions']
		));
		$tokenParameters['fields'] = array(
			'created',
			'id',
			'foreign_key',
			'foreign_table',
			'foreign_value',
			'string'
		);
		$token = $this->find('tokens', $tokenParameters);
		return !empty($token['data'][0]) ? $token['data'][0] : array();
	}

/**
 * Retrieve parameterized SQL query and array of values
 *
 * @param string $query Query
 *
 * @return array Parameterized SQL query and array of values
 */
	protected function _parameterizeSQL($query) {
		$queryChunks = explode($this->sanitizeKeys['start'], $query);
		$parameterValues = array();

		foreach ($queryChunks as $key => $queryChunk) {
			if (
				($position = strpos($queryChunk, $this->sanitizeKeys['end'])) !== false &&
				$queryChunk = str_replace($this->sanitizeKeys['end'], '?', $queryChunk)
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
 * Hash password
 *
 * @param string $password Raw password string
 *
 * @return array $response Response with hashed password
 */
	protected function _passwordHash($password) {
		$passwordCharacters = str_split($password);
		$passwordLength = strlen($password);
		$salt = $this->config['database']['sanitizeKeys']['hashSalt'];
		$time = time();

		foreach ($passwordCharacters as $key => $passwordCharacter) {
			$passwordCharacters[$key] = sha1($passwordCharacter . $salt . ($key % 2 == 0 ? (($passwordLength / 2) * $passwordLength) : (($passwordLength * $passwordLength) + ($passwordLength * $passwordLength))));
		}

		$hashedPassword = implode('', $passwordCharacters);
		$modified = date('Y-m-d h:i:s', $time);

		for ($i = 0; $i < $passwordLength; $i++) {
			$hashedPassword = 'e1Gh7$' . sha1($salt . $time . $hashedPassword . $modified);
		}

		$response = array(
			'hashed' => $hashedPassword,
			'modified' => $modified,
			'raw' => $password
		);

		return $response;
	}

/**
 * Prepare user input value for SQL parameterization parsing with hash strings
 *
 * @param string $value Value
 *
 * @return string Prepared value
 */
	protected function _prepareValue($value) {
		return $this->sanitizeKeys['start'] . (is_bool($value) ? (integer) $value : $value) . $this->sanitizeKeys['end'];
	}

/**
 * Process API action requests
 *
 * @param string $table Table name
 * @param array $parameters Action query parameters
 *
 * @return array Response data
 */
	protected function _processAction($table, $parameters) {
		if (
			!method_exists($this, $action = $parameters['action']) ||
			(
				($itemTable = (in_array($table, array('proxies')))) &&
				($token = $this->_getToken($parameters)) === false
			)
		) {
			return false;
		}

		$response = array(
			'items' => $parameters['items'] = isset($parameters['items']) ? $parameters['items'] : array(),
			'token' => $token
		);

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
			$response['items'] = array();
			$response['message'] = 'Your ' . $table . ' have been recently modified and your previously-selected results have been deselected automatically.';
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
		$database = new PDO($this->config['database']['type'] . ':host=' . $this->config['database']['hostname'] . '; dbname=' . $this->config['database']['name'] . ';', $this->config['database']['username'], $this->config['database']['password']);
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

		$findDataRowChunkSize = (!empty($this->config['database']['findDataRowChunkSize']) ? (integer) $this->config['database']['findDataRowChunkSize'] : 100000);
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
				empty($parameters['table']) ||
				empty($this->permissions['api'][$parameters['table']])
			) {
				$response['message'] = 'Invalid request table, please try again.';
			} else {
				if (
					($parameters['action'] = $action = (!empty($parameters['action']) ? $parameters['action'] : 'find')) &&
					(
						empty($this->permissions['api'][$parameters['table']][$action]) ||
						!method_exists($this, $parameters['action'])
					)
				) {
					$response['message'] = 'Invalid request action, please try again.';
				} else {
					if (
						($fieldPermissions = $this->permissions['api'][$parameters['table']][$action]['fields']) &&
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
							$queryResponse = $this->_processAction($parameters['table'], $parameters);

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
 * Validate email address format
 *
 * @param string $email Email address
 *
 * @return mixed $email String if valid email address format, boolean false if invalid
 */
	protected function _validateEmailFormat($email) {
		$email = strtolower(trim($email));
		$emailCharacters = str_split($email);
		$emailCharacterOccurences = array_count_values($emailCharacters);
		$emailSplitCharacters = explode('@', $email);
		$validAlphaNumericCharacters = 'abcdefghijklmnopqrstuvwxyz1234567890';
		$validLocalCharacters = '!#$%&\'*+-/=?^_`{|}~' . $validAlphaNumericCharacters;
		$validLocalSpecialCharacters = ' .(),:;<>@[]';
		$validDomainCharacters = '-.' . $validAlphaNumericCharacters;

		if (count($emailSplitCharacters) !== 2) {
			$email = false;
		}

		$localString = $emailSplitCharacters[0];
		$localStringCharacters = str_split($localString);
		$localStringCharacterOccurences = array_count_values($localStringCharacters);
		$domainString = $emailSplitCharacters[1];
		$domainStringCharacters = str_split($domainString);
		$domainStringCharacterOccurences = array_count_values($domainStringCharacters);
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
			$email = false;
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
			$email = false;
		}

		if (
			$email &&
			(
				$invalidLocalCharacters = array_diff($localStringCharacters, str_split($validLocalCharacters)) ||
				$invalidDomainCharacters = array_diff($domainStringCharacters, str_split($validDomainCharacters))
			)
		) {
			$email = false;
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
 * @param array $parameters Find query parameters
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
		return $response;
	}

/**
 * Redirect helper method
 *
 * @param string $path URL path
 * @param string $responseCode HTTP response code
 *
 * @return exit
 */
	public function redirect($path, $responseCode = 301) {
		header('Location: ' . $path, true, $responseCode);
		exit;
	}

/**
 * Routing helper method
 * @todo Custom URL routing
 *
 * @return function redirect()
 */
	public function route() {
		$method = array_shift(array_reverse(explode('/', str_replace('.php', '', $_SERVER['SCRIPT_NAME']))));

		if (method_exists($this, $method)) {
			return $this->$method();
		}

		$this->redirect($this->config['base_url']);
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

		foreach (array_chunk($rows, (!empty($this->config['database']['saveDataRowChunkSize']) ? (integer) $this->config['database']['saveDataRowChunkSize'] : 100)) as $rows) {
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

				$groupValues[implode(',', $fields)][] = $this->sanitizeKeys['start'] . implode($this->sanitizeKeys['end'] . ',' . $this->sanitizeKeys['start'], $values) . $this->sanitizeKeys['end'];
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

/**
 * Validation and sanitization helper method for unique IDs
 *
 * @param string $id ID
 * @param string $table Table name
 *
 * @return boolean True if ID exists and is formatted correctly (integer).
 */
	public function validateId($id, $table) {
		return !empty($this->find($table, array(
			'conditions' => array(
				'id' => (integer) $id
			)
		)));
	}

}
