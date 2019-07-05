<?php
/**
 * App Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/config/config.php');

class AppModel extends Config {

/**
 * Create token string from parameters and results
 *
 * @param array $parameters Parameters
 *
 * @return array Token string
 */
	protected function _createTokenString($parameters) {
		return sha1($this->config['database']['sanitizeKeys']['hashSalt'] . json_encode($this->find($parameters['current']['table'], array(
			'conditions' => $parameters['current']['conditions'],
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
 * Helper method for extracting values from a specific key in a multidimensional array
 *
 * @param array $data Data
 * @param string $key Key
 * @param boolean $unique Extract only unique values
 *
 * @return array $data Flattened array of values
 */
	protected function _extract($data, $key, $unique = false) {
		if (!is_array($data)) {
			return;
		}

		array_walk($data, function(&$value, $index, $key) {
			$value = !empty($value[$key]) ? $value[$key] : null;
		}, $key);

		if ($unique === true) {
			$data = array_unique($data);
		}

		return array_filter($data);
	}

/**
 * Format array of conditions to SQL query
 *
 * @param array $conditions Conditions
 * @param string $condition Condition
 *
 * @return array $conditions SQL query conditions
 */
	protected function _formatConditionsToSQL($conditions = array(), $condition = 'OR') {
		foreach ($conditions as $key => $value) {
			$condition = !empty($key) && (in_array($key, array('AND', 'OR'))) ? $key : $condition;

			if (count($value) == count($value, COUNT_RECURSIVE)) {
				if (is_array($value)) {
					$key = (strlen($key) > 1 && is_string($key) ? $key : null);
					array_walk($value, function(&$fieldValue, $fieldKey) use ($key) {
						$fieldValue = (strlen($fieldKey) > 1 && is_string($fieldKey) ? $fieldKey : $key) . ' LIKE ' . $this->_prepareValue($fieldValue);
					});
				} else {
					$value = array($key . ' LIKE ' . $this->_prepareValue($value));
				}

				$conditions[$key] = '(' . implode(' ' . $condition . ' ', $value) . ')';
			} else {
				$conditions[$key] = ($key === 'NOT' ? 'NOT' : null) . '(' . implode(' ' . $condition . ' ', $this->_formatConditionsToSQL($value, $condition)) . ')';
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
				'foreign_table' => $parameters['current']['table'],
				'foreign_key' => $key = key($parameters['current']['conditions']),
				'foreign_value' => $parameters['current']['conditions'][$key],
				'string' => $this->_createTokenString($parameters)
			),
			'fields' => array(
				'id'
			),
			'limit' => 1,
			'sort' => array(
				'field' => 'modified',
				'order' => 'DESC'
			)
		);

		$existingToken = $this->find('tokens', $tokenParameters);

		if (!empty($existingToken['data'][0])) {
			$tokenParameters['conditions']['id'] = $existingToken['data'][0];
		}

		$this->save('tokens', array(
			$tokenParameters['conditions']
		));
		$tokenParameters['fields'] = array(
			'id',
			'string',
			'created'
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
 * Prepare user input value for SQL parameterization parsing with hash strings
 *
 * @param string $value Value
 *
 * @return string Prepared value
 */
	protected function _prepareValue($value) {
		return $this->sanitizeKeys['start'] . $value . $this->sanitizeKeys['end'];
	}

/**
 * Process API action requests
 *
 * @param string $table Table name
 * @param array $parameters Action query parameters
 *
 * @return array $response Response data
 */
	protected function _processAction($table, $parameters) {
		if (
			!method_exists($this, $actionMethod = $parameters['current']['action']) ||
			($token = $parameters['current']['token'] = $this->_getToken($parameters)) === false
		) {
			return false;
		}

		$response = array(
			'grid' => $parameters['current']['grid'] = isset($parameters['current']['grid']) ? $parameters['current']['grid'] : array(),
			'token' => $token
		);

		if (
			empty($parameters['previous']['token']) ||
			(
				!empty($parameters['previous']['token']) &&
				$parameters['previous']['token'] === $token
			)
		) {
			if (!in_array($actionMethod, array('find', 'search'))) {
				$parameters['current']['unserialized_grid'] = $this->_unserializeGrid($parameters);
			}

			$response = array_merge($this->$actionMethod($table, $parameters['current']), $response);
		} else {
			$actionMethod = 'find';
			$parameters['current']['grid'] = $response['grid'] = array();
			$response['message'] = 'Your ' . $parameters['current']['table'] . ' have been recently modified and your previously-selected results have been deselected automatically.';
		}

		$response = array_merge($this->$actionMethod($table, $parameters['current']), $response);
		return $response;
	}

/**
 * Construct and execute database queries
 *
 * @param string $query Query string
 * @param boolean $associative True to fetch associative data, false to fetch list of values
 *
 * @return array $result Return associative array if data exists, otherwise return boolean ($execute)
 */
	protected function _query($query, $associative = true) {
		$database = new PDO($this->config['database']['type'] . ':host=' . $this->config['database']['hostname'] . '; dbname=' . $this->config['database']['name'] . '; charset=' . $this->config['database']['charset'], $this->config['database']['username'], $this->config['database']['password']);
		$database->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$parameterized = $this->_parameterizeSQL($query);

		if (empty($parameterized['parameterizedQuery'])) {
			return false;
		}

		$connection = $database->prepare($parameterized['parameterizedQuery']);

		if (
			empty($connection) ||
			!is_object($connection)
		) {
			return false;
		}

		$execute = $connection->execute(!empty($parameterized['parameterizedValues']) ? $parameterized['parameterizedValues'] : array());
		$result = $connection->fetchAll($associative ? PDO::FETCH_ASSOC : PDO::FETCH_COLUMN);
		$connection->closeCursor();
		return !empty($result) ? $result : $execute;
	}

/**
 * Validate and structure API request based on parameters
 *
 * @param array $request Request data
 *
 * @return array $response Response data
 */
	protected function _request($request) {
		$response = array(
			'code' => 400,
			'message' => 'Request parameters are required for API.'
		);

		if (
			!empty($request['json']) &&
			is_string($request['json'])
		) {
			$parameters = json_decode($request['json'], true);
			$response['message'] = 'No results found, please try again.';

			if (
				empty($parameters['current']['table']) ||
				empty($this->permissions['api'][$parameters['current']['table']])
			) {
				$response['message'] = 'Invalid request table, please try again.';
			} else {
				if (
					($parameters['current']['action'] = $action = (!empty($parameters['current']['action']) ? $parameters['current']['action'] : 'find')) &&
					(
						empty($this->permissions['api'][$parameters['current']['table']][$action]) ||
						!method_exists($this, $parameters['current']['action'])
					)
				) {
					$response['message'] = 'Invalid request action, please try again.';
				} else {
					if (
						($fieldPermissions = $this->permissions['api'][$parameters['current']['table']][$action]['fields']) &&
						($parameters['current']['fields'] = $fields = !empty($parameters['current']['fields']) ? $parameters['current']['fields'] : $fieldPermissions) &&
						count(array_intersect($fields, $fieldPermissions)) !== count($fields)
					) {
						$response['message'] = 'Invalid request fields, please try again.';
					} else {
						if (
							(
								empty($parameters['current']['conditions']) ||
								!is_array($parameters['current']['conditions'])
							) ||
							(
								!isset($parameters['current']['limit']) ||
								!is_int($parameters['current']['limit'])
							) ||
							(
								isset($parameters['current']['offset']) &&
								!is_int($parameters['current']['offset'])
							) ||
							(
								(
									!empty($parameters['current']['sort']['field']) &&
									!in_array($parameters['current']['sort']['field'], $fieldPermissions)
								) ||
								(
									!empty($parameters['current']['sort']['order']) &&
									!in_array(strtoupper($parameters['current']['sort']['order']), array('ASC', 'DESC'))
								)
							)
						) {
							$response['message'] = 'Invalid request parameters, please try again.';
						} else {
							$queryResponse = $this->_processAction($parameters['current']['table'], $parameters);

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
 * Unserialize grid indexes and map to IDs based on previous and current tokens
 *
 * @param array $parameters Parameters
 *
 * @return array Grid
 */
	protected function _unserializeGrid($parameters) {
		$grid = array();
		$gridLines = $parameters['current']['grid'];
		$index = 0;

		foreach ($gridLines as $gridLineKey => $gridLine) {
			$gridLineChunks = explode('_', $gridLine);

			foreach ($gridLineChunks as $gridLineChunkKey => $gridLineChunk) {
				$itemStatus = substr($gridLineChunk, 0, 1);
				$itemStatusCount = substr($gridLineChunk, 1);

				if ($itemStatus) {
					for ($i = 0; $i < $itemStatusCount; $i++) {
						$grid[$index + $i] = 1;
					}
				}

				$index += $itemStatusCount;
			}
		}

		unset($parameters['current']['offset']);
		$ids = $this->find($parameters['current']['table'], array_merge($parameters['current'], array(
			'fields' => array(
				'id'
			),
			'limit' => $index,
			'offset' => 0
		)));

		return array(
			'count' => count($grid),
			'data' => !empty($ids['data']) ? array_intersect_key($ids['data'], $grid) : array()
		);
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
 * Database helper method for retrieving data
 *
 * @param string $table Table name
 * @param array $parameters Find query parameters
 *
 * @return array $result Return associative array if it exists, otherwise return boolean ($execute)
 */
	public function find($table, $parameters = array()) {
		$query = ' FROM ' . $table;

		if (
			!empty($parameters['conditions']) &&
			is_array($parameters['conditions'])
		) {
			$query .= ' WHERE ' . implode(' AND ', $this->_formatConditionsToSQL($parameters['conditions']));
		}

		$count = $this->_query('SELECT COUNT(id)' . $query);

		if (!empty($parameters['sort']['field'])) {
			$query .= ' ORDER BY ' . $parameters['sort']['field'] . ' ' . (!empty($parameters['sort']['order']) ? $parameters['sort']['order'] : 'DESC');
		}

		if (!empty($parameters['limit'])) {
			$query .= ' LIMIT ' . $this->_prepareValue($parameters['limit']);
		}

		if (!empty($parameters['offset'])) {
			$query .= ' OFFSET ' . $this->_prepareValue($parameters['offset']);
		}

		return array(
			'count' => !empty($count[0]['COUNT(id)']) ? $count[0]['COUNT(id)'] : 0,
			'data' => $this->_query('SELECT ' . (!empty($parameters['fields']) && is_array($parameters['fields']) ? implode(',', $parameters['fields']) : '*') . $query, (count($parameters['fields']) === 1 ? false : true)),
			'message' => ''
		);
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
				$values = array_values($row);

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
 * Process search requests
 *
 * @param string $table Table name
 * @param array $parameters Search query parameters
 *
 * @return array $response Response data
 */
	public function search($table, $parameters) {
		$broadSearchFields = $this->permissions['api'][$table]['search']['fields'];
		$conditions = array();

		if (!empty($broadSearchTerms = array_filter(explode(' ', $parameters['data']['broad_search'])))) {
			$conditions = array_map(function($broadSearchTerm) use ($broadSearchFields) {
				return array(
					'OR' => array_fill_keys($broadSearchFields, '%' . $broadSearchTerm . '%')
				);
			}, $broadSearchTerms);
		}

		if (
			!empty($parameters['data']['granular_search']) &&
			($conditions['ip'] = $this->_parseIps($parameters['data']['granular_search'], true))
		) {
			array_walk($conditions['ip'], function(&$value, $key) {
				$value .= '%'; // Add trailing wildcard for A/B/C class subnet search
			});
		}

		if (!empty($conditions)) {
			$conditions = array(
				($parameters['data']['match_all_search'] ? 'AND' : 'OR') => $conditions
			);
		}

		if (!empty($parameters['data']['exclude_search'])) {
			$conditions = array(
				'NOT' => $conditions
			);
		}

		unset($parameters['conditions']['id']);
		$parameters['conditions'] = array_merge($conditions, $parameters['conditions']);
		return $this->find($table, $parameters);
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
