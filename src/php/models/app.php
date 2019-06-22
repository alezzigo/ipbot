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
						$fieldValue = (strlen($fieldKey) > 1 && is_string($fieldKey) ? $fieldKey : $key) . ' LIKE ' . $this->sanitizeKeys['start'] . $fieldValue . $this->sanitizeKeys['end'];
					});
				} else {
					$value = array($key . ' LIKE ' . $this->sanitizeKeys['start'] . $value . $this->sanitizeKeys['end']);
				}

				$conditions[$key] = '(' . implode(' ' . $condition . ' ', $value) . ')';
			} else {
				$conditions[$key] = ($key === 'NOT' ? 'NOT' : null) . '(' . implode(' ' . $condition . ' ', $this->_formatConditionsToSQL($value, $condition)) . ')';
			}
		}

		return $conditions;
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
 * Construct and execute database queries
 *
 * @param string $query Query string
 *
 * @return array $result Return associative array if data exists, otherwise return boolean ($execute)
 */
	protected function _query($query) {
		$database = new PDO($this->config['database']['type'] . ':host=' . $this->config['database']['hostname'] . '; dbname=' . $this->config['database']['name'] . '; charset=' . $this->config['database']['charset'], $this->config['database']['username'], $this->config['database']['password']);

		$sanitized = $this->_sanitizeSQL($query);

		if (empty($sanitized['sanitizedQuery'])) {
			return false;
		}

		$connection = $database->prepare($sanitized['sanitizedQuery']);

		if (!empty($sanitized['sanitizedValues'])) {
			foreach ($sanitized['sanitizedValues'] as $index => $value) {
				$connection->bindValue($index + 1, $value);
			}
		}

		if (
			empty($connection) ||
			!is_object($connection)
		) {
			return false;
		}

		$execute = $connection->execute();
		$result = $connection->fetchAll(PDO::FETCH_ASSOC);
		$connection->closeCursor();
		return !empty($result) ? $result : $execute;
	}

/**
 * Sanitize SQL query and user input values
 *
 * @param string $query Query
 *
 * @return array Prepared SQL query and values for binding
 */
	protected function _sanitizeSQL($query) {
		$queryChunks = explode($this->sanitizeKeys['start'], $query);
		$parameterValues = array();

		foreach ($queryChunks as &$queryChunk) {
			if (
				($position = strpos($queryChunk, $this->sanitizeKeys['end'])) !== false &&
				$queryChunk = str_replace($this->sanitizeKeys['end'], '?', $queryChunk)
			) {
				$queryChunk = str_replace(($between = substr($queryChunk, 0, $position)), '', $queryChunk);
				$parameterValues[] = $between;
			}
		}

		return array(
			'sanitizedQuery' => implode('', $queryChunks),
			'sanitizedValues' => $parameterValues
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

			$ips[$key] = $splitIpSubnets[0] . '.' . $splitIpSubnets[1] . '.' . $splitIpSubnets[2] . '.' . $splitIpSubnets[3];
		}
		return implode("\n", array_unique($ips));
	}

/**
 * Database helper method for retrieving data
 *
 * @param string $table Table name
 * @param array $parameters Query parameters
 *
 * @return array $result Return associative array if it exists, otherwise return boolean ($execute)
 */
	public function find($table, $parameters = array()) {
		$query = 'SELECT ' . (!empty($parameters['count']) && $parameters['count'] === true ? 'COUNT(*) ' : (!empty($parameters['fields']) && is_array($parameters['fields']) ? implode(',', $parameters['fields']) : '*')) . ' FROM ' . $table;

		if (
			!empty($parameters['conditions']) &&
			is_array($parameters['conditions'])
		) {
			$query .= ' WHERE ' . implode(' AND ', $this->_formatConditionsToSQL($parameters['conditions']));
		}

		if (!empty($parameters['order'])) {
			$query .= ' ORDER BY ' . (string) $parameters['order'];
		}

		if (!empty($parameters['limit'])) {
			$query .= ' LIMIT ' . (integer) $parameters['limit'];
		}

		if (!empty($parameters['offset'])) {
			$query .= ' OFFSET ' . (integer) $parameters['offset'];
		}

		return $this->_query($query);
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

			foreach ($rows as &$row) {
				$fields = array_keys($row);
				$values = array_values($row);
				array_walk($values, function(&$value, $index) {
					$value = $this->sanitizeKeys['start'] . $value . $this->sanitizeKeys['end'];
				});
				$groupValues[implode(',', $fields)][] = implode(',', $values);
			}

			foreach ($groupValues as $fields => $values) {
				$updateFields = explode(',', $fields);
				array_walk($updateFields, function(&$field, $index) {
					$field = $field . '=' . $field;
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
