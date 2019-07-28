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

		if (!empty($parameters)) {
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
 * Process copy requests
 * @todo File downloads for large lists
 *
 * @param string $table Table name
 * @param array $parameters Copy query parameters
 *
 * @return array $response Response data
 */
	public function copy($table, $parameters) {
		$items = array();
		$response = $this->find($table, array(
			'conditions' => array(
				'id' => $parameters['items'][$table]['data']
			),
			'fields' => $this->permissions['api'][$table]['copy']['fields']
		));

		if (!empty($response['data'])) {
			$delimiters = implode('', array_unique(array_filter(array(
				!empty($parameters['data']['ipv4_delimiter_1']) ? $parameters['data']['ipv4_delimiter_1'] : '',
				!empty($parameters['data']['ipv4_delimiter_2']) ? $parameters['data']['ipv4_delimiter_2'] : '',
				!empty($parameters['data']['ipv4_delimiter_3']) ? $parameters['data']['ipv4_delimiter_3'] : ''
			))));

			foreach ($response['data'] as $key => $data) {
				$items[$key] = '';

				for ($i = 1; $i < 5; $i++) {
					$items[$key] .= !empty($column = $response['data'][$key][$parameters['data']['ipv4_column_' . $i]]) ? $column . $parameters['data']['ipv4_delimiter_' . $i] : '';
				}

				$items[$key] = rtrim($items[$key], $delimiters);
			}
		}

		$response = array(
			'count' => count($items),
			'data' => implode("\n", $items)
		);
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
 * Process group requests
 *
 * @param string $table Table name
 * @param array $parameters Group query parameters
 *
 * @return array $response Response data
 */
	public function group($table, $parameters) {
		$message = 'Error processing your group request, please try again.';

		if (!empty($groupData = array_intersect_key($parameters['data'], array(
			'id' => true,
			'name' => true,
			'order_id' => true
		)))) {
			$groupParameters = array(
				'conditions' => $groupData,
				'limit' => 1
			);

			if (
				!empty($groupName = $groupData['name']) &&
				!empty($groupData['order_id'])
			) {
				$message = 'Group "' . $groupName . '" already exists for this order.';
				$existingGroup = $this->find('proxy_groups', $groupParameters);

				if (empty($existingGroup['count'])) {
					$message = 'Error creating new group, please try again.';
					$this->save('proxy_groups', array(
						$groupData
					));
					$groupData = $this->find('proxy_groups', $groupParameters);

					if (!empty($groupData['count'])) {
						$message = 'Group "' . $groupName . '" saved successfully.';
					}
				}
			}

			if (
				!empty($groupData['id']) &&
				!isset($groupData['name'])
			) {
				$message = 'Error deleting group, please try again.';
				$existingGroup = $this->find('proxy_groups', $groupParameters);

				if (!empty($existingGroup['count'])) {
					$this->delete('proxy_groups', $groupData);
					$deletedGroup = $this->find('proxy_groups', $groupParameters);

					if (empty($deletedGroup['count'])) {
						$this->delete('proxy_group_proxies', array(
							'proxy_group_id' => $groupData['id']
						));
						$message = 'Group deleted successfully.';
					}
				}
			}

			if (
				$table == 'proxies' &&
				!empty($parameters['items']['proxies']['count']) &&
				!empty($parameters['items']['proxy_groups']['count'])
			) {
				$groups = array();
				$proxyIds = array();
				$existingProxyGroupProxies = $this->find('proxy_group_proxies', array(
					'conditions' => array(
						'proxy_group_id' => array_values($parameters['items']['proxy_groups']['data']),
						'proxy_id' => $parameters['items']['proxies']['data']
					),
					'fields' => array(
						'id',
						'proxy_group_id',
						'proxy_id'
					)
				));

				foreach ($parameters['items']['proxies']['data'] as $key => $proxyId) {
					foreach ($parameters['items']['proxy_groups']['data'] as $key => $proxyGroupId) {
						$groups[$proxyGroupId . '_' . $proxyId] = array(
							'proxy_group_id' => $proxyGroupId,
							'proxy_id' => $proxyId
						);
					}
				}

				if (!empty($existingProxyGroupProxies['count'])) {
					foreach ($existingProxyGroupProxies['data'] as $existingProxyGroupProxy) {
						if (!empty($groups[$key = $existingProxyGroupProxy['proxy_group_id'] . '_' . $existingProxyGroupProxy['proxy_id']])) {
							$groups[$key]['id'] = $existingProxyGroupProxy['id'];
						}
					}
				}

				$message = 'Error adding selected items to groups.';

				if ($this->save('proxy_group_proxies', array_values($groups))) {
					$message = 'Items added to selected groups successfully.';
				}
			}
		}

		return array_merge($this->find($table, $parameters), array(
			'message' => $message
		));
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
			!empty($parameters['items'][$table]['count']) &&
			is_array($parameters['items'][$table]['data'])
		) {
			$response['message'] = 'There was an error applying the replacement settings to your ' . $table . ', please try again';
			$newItemData = $oldItemData = $oldItemIds = array();

			if (
				(
					!empty($parameters['data']['automatic_replacement_interval_value']) &&
					is_numeric($parameters['data']['automatic_replacement_interval_value'])
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

			if (
				!empty($parameters['data']['instant_replacement']) &&
				($orderId = !empty($parameters['conditions']['order_id']) ? $parameters['conditions']['order_id'] : 0)
			) {
				$oldItemData += array(
					'replacement_removal_date' => date('Y-m-d H:i:s', strtotime('+24 hours')),
					'status' => 'replaced'
				);
				$newItemData += array(
					'user_id' => 1,
					'order_id' => $orderId,
					'next_replacement_available' => date('Y-m-d H:i:s', strtotime('+1 week')),
					'status' => 'online'
				);
			}

			if (!empty($newItemData)) {
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
					'limit' => $parameters['items'][$table]['count'],
					'sort' => array(
						'field' => 'id',
						'order' => 'DESC'
					)
				));

				if (count($processingNodes['data']) !== $parameters['items'][$table]['count']) {
					$response['message'] = 'There aren\'t enough ' . $table . ' available to replace your ' . $parameters['items'][$table]['count'] . ' selected ' . $table . ', please try again in a few minutes.';
				} else {
					$allocatedNodes = array();
					$processingNodes['data'] = array_replace_recursive($processingNodes['data'], array_fill(0, count($processingNodes['data']), array(
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
						$oldItemIds[]['id'] = $parameters['items'][$table]['data'][$key];
					}

					if ($parameters['tokens'][$table] === $this->_getToken($parameters)) {
						if (!empty($oldItemData)) {
							$oldItemData = array_replace_recursive(array_fill(0, $parameters['items'][$table]['count'], $oldItemData), $oldItemIds);
							$this->save($table, $oldItemData);
						}

						if (
							$this->save($table, $processingNodes['data']) &&
							$this->save('nodes', $allocatedNodes)
						) {
							$response['items'][$table] = array();
							$response['message'] = $parameters['items'][$table]['count'] . ' of your selected ' . $table . ' replaced successfully.';
						}
					}
				}
			}
		}

		$response = array_merge($this->find($table, $parameters), $response);

		if (($response['token'] = $this->_getToken($parameters)) !== $parameters['tokens'][$table]) {
			$response['items'][$table] = array();
		}

		return $response;
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
			!empty($broadSearchFields = $this->permissions['api'][$table]['search']['fields']) &&
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
			($conditions['ip LIKE'] = $this->_parseIps($parameters['data']['granular_search'], true))
		) {
			array_walk($conditions['ip LIKE'], function(&$value, $key) {
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

		if (!empty($parameters['data']['groups'])) {
			$conditions['id'] = false;
			$groupProxies = $this->find('proxy_group_proxies', array(
				'conditions' => array(
					'proxy_group_id' => array_values($parameters['data']['groups'])
				),
				'fields' => array(
					'proxy_id'
				)
			));

			if (
				!empty($groupProxies['count']) &&
				!empty($groupProxyIds = array_unique($groupProxies['data']))
			) {
				$conditions['id'] = $groupProxyIds;
			}
		}

		$parameters['conditions'] = array_merge($conditions, $parameters['conditions']);
		$response = $this->find($table, $parameters);
		return array_merge($response, array(
			'message' => $response['count'] . ' search results found. <a class="clear" href="javascript:void(0);">Clear search filter</a>.'
		));
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
