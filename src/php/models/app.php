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
 * Format array of conditions to SQL query
 *
 * @param array $conditions Conditions
 * @param string $condition Condition
 *
 * @return array $conditions SQL query conditions
 */
	protected function _formatConditionsToSQL($conditions = array(), $condition = 'OR') {
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
				'foreign_table' => $parameters['table'],
				'foreign_key' => $key = key($parameters['conditions']),
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
			'id',
			'foreign_table',
			'foreign_key',
			'foreign_value',
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
			($token = $this->_getToken($parameters)) === false
		) {
			return false;
		}

		$response = array(
			'grid' => $parameters['grid'] = isset($parameters['grid']) ? $parameters['grid'] : array(),
			'token' => $token
		);

		if (
			empty($parameters['token']) ||
			$parameters['token'] === $token
		) {
			if (!in_array($action, array('find', 'search'))) {
				$parameters['unserialized_grid'] = $this->_unserializeGrid($parameters);
			}
		} else {
			$action = 'find';
			$response['grid'] = array();
			$response['message'] = 'Your ' . $parameters['table'] . ' have been recently modified and your previously-selected results have been deselected automatically.';
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
								empty($parameters['conditions']) ||
								!is_array($parameters['conditions'])
							) ||
							(
								!isset($parameters['limit']) ||
								!is_int($parameters['limit'])
							) ||
							(
								isset($parameters['offset']) &&
								!is_int($parameters['offset'])
							) ||
							(
								(
									!empty($parameters['sort']['field']) &&
									!in_array($parameters['sort']['field'], $fieldPermissions)
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
 * Unserialize grid indexes and map to IDs based on previous and current tokens
 *
 * @param array $parameters Parameters
 *
 * @return array Grid
 */
	protected function _unserializeGrid($parameters) {
		$grid = array();
		$gridLines = $parameters['grid'];
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

		if (
			empty($grid) ||
			!$index
		) {
			return $grid;
		}

		unset($parameters['offset']);

		$ids = $this->find($parameters['table'], array_merge($parameters, array(
			'fields' => array(
				'id'
			),
			'limit' => end($grid) ? key($grid) + 1 : $index,
			'offset' => 0
		)));

		$conditions = array(
			'id' => !empty($ids['data']) ? array_intersect_key($ids['data'], $grid) : array()
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

		return $this->find($parameters['table'], array(
			'conditions' => $conditions,
			'fields' => array(
				'id',
				'node_id'
			)
		));
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
 * @param string $message Message
 *
 * @return array $result Return associative array if it exists, otherwise return boolean ($execute)
 */
	public function find($table, $parameters = array(), $message = '') {
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

		$parameters = array_merge($parameters, array(
			'count' => $count = $parameters['count'] = !empty($count[0]['COUNT(id)']) ? $count[0]['COUNT(id)'] : 0,
			'field_count' => !empty($parameters['fields']) && is_array($parameters['fields']) ? count($parameters['fields']) : 0,
			'limit' => !empty($parameters['limit']) && $parameters['limit'] < $count ? $parameters['limit'] : $count,
			'offset' => !empty($parameters['offset']) ? !empty($parameters['offset']) : 0
		));

		$query = 'SELECT ' . (!empty($parameters['fields']) && is_array($parameters['fields']) ? implode(',', $parameters['fields']) : '*') . $query;
		$query .= ' LIMIT ' . $this->_prepareValue($parameters['limit']) . ' OFFSET ' . $this->_prepareValue($parameters['offset']);
		$data = $this->_query($query, $parameters);

		return array(
			'count' => $count,
			'data' => $data,
			'message' => $message
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
 * Process replace requests
 * @todo Retrieve user ID from auth token, remove replaced proxies on replacement_removal_date with cron
 *
 * @param string $table Table name
 * @param array $parameters Replace query parameters
 *
 * @return array $response Response data
 */
	public function replace($table, $parameters) {
		$response = array(
			'message' => 'No selected items were eligible for replacements, please try again.'
		);

		if (
			!empty($parameters['unserialized_grid']['count']) &&
			is_array($parameters['unserialized_grid']['data'])
		) {
			$response['message'] = 'There was an error applying the replacement settings to your ' . $table . ', please try again';
			$newItemData = $oldItemData = array();

			if (
				(
					!empty($parameters['data']['automatic_replacement_interval_value']) &&
					is_int($parameters['data']['automatic_replacement_interval_value'])
				) &&
				(
					!empty($parameters['data']['automatic_replacement_interval_type']) &&
					in_array($automaticReplacementIntervalType = strtolower($parameters['data']['automatic_replacement_interval_type']), array('month', 'week'))
				) &&
				!empty($parameters['data']['enable_automatic_replacements'])
			) {
				$intervalData = array(
					'automatic_replacement_interval_value' => $parameters['data']['automatic_replacement_interval_value'],
					'automatic_replacement_interval_type' => $automaticReplacementIntervalType,
					'last_replacement_date' => date('Y-m-d H:i:s', time())
				);
				$newItemData += $intervalData;
				$oldItemData += $intervalData;
			}

			if (!empty($parameters['data']['instant_replacement'])) {
				$oldItemData += array(
					'replacement_removal_date' => date('Y-m-d H:i:s', strtotime('+24 hours')),
					'status' => 'replaced'
				);
				$newItemData += array(
					'user_id' => 1,
					'order_id' => $parameters['token']['foreign_value'],
					'next_replacement_available' => date('Y-m-d H:i:s', strtotime('+1 week')),
					'status' => 'online'
				);
			}

			if (
				!empty($newItemData) &&
				($orderId = !empty($parameters['conditions']['order_id']) ? $parameters['conditions']['order_id'] : 0)
			) {
				$processingNodes = $this->find('nodes', array(
					'conditions' => array(
						'AND' => array(
							'allocated' => false,
							'OR' => array(
								'processing' => false,
								'modified <' => date('Y-m-d H:i:s', strtotime('-1 minute'))
							)
						)
					),
					'fields' => array(
						'id',
						'ip',
						'asn',
						'isp',
						'city',
						'region',
						'country_name',
						'country_code'
					),
					'limit' => $parameters['unserialized_grid']['count'],
					'sort' => array(
						'field' => 'id',
						'order' => 'DESC'
					)
				));

				if (count($processingNodes['data']) !== $parameters['unserialized_grid']['count']) {
					$response['message'] = 'There aren\'t enough ' . $table . ' available to replace your ' . $parameters['unserialized_grid']['count'] . ' selected ' . $table . ', please try again in a few minutes.';
				} else {
					$allocatedNodes = array();
					$processingNodes['data'] = array_replace_recursive($processingNodes['data'], array_fill(0, $parameters['unserialized_grid']['count'], array(
						'processing' => true
					)));
					$this->save('nodes', $processingNodes['data']);

					foreach ($processingNodes['data'] as $key => $row) {
						$allocatedNodes[] = array(
							'id' => ($processingNodes['data'][$key]['node_id'] = $processingNodes['data'][$key]['id']),
							'allocated' => true,
							'processing' => false
						);
						$processingNodes['data'][$key] += $newItemData;
						unset($processingNodes['data'][$key]['id']);
						unset($processingNodes['data'][$key]['processing']);
					}

					if ($parameters['token'] === $this->_getToken($parameters)) {
						if (!empty($oldItemData)) {
							$oldItemData = array_replace_recursive(array_fill(0, $parameters['unserialized_grid']['count'], $oldItemData), $parameters['unserialized_grid']['data']);
							$this->save($table, $oldItemData);
						}

						if (
							$this->save($table, $processingNodes['data']) &&
							$this->save('nodes', $allocatedNodes)
						) {
							$response['message'] = $parameters['unserialized_grid']['count'] . ' of your selected ' . $table . ' replaced successfully.';
						}
					}
				}
			}
		}

		$response = $this->find($table, $parameters, $response['message']);

		if (($response['token'] = $this->_getToken($parameters)) !== $parameters['token']) {
			$response['grid'] = array();
		}

		return $response;
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
		$conditions = array();

		if (
			!empty($broadSearchFields = array_diff($this->permissions['api'][$table]['search']['fields'], array('created', 'modified'))) &&
			!empty($broadSearchTerms = array_filter(explode(' ', $parameters['data']['broad_search'])))
		) {
			$conditions = array_map(function($broadSearchTerm) use ($broadSearchFields) {
				return array(
					'OR' => array_combine(explode('-', implode(' LIKE' . '-', $broadSearchFields) . ' LIKE'), array_fill(1, count($broadSearchFields), '%' . $broadSearchTerm . '%'))
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
