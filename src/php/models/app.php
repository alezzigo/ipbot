<?php
/**
 * App Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once('../../config/config.php');

class App extends Config {

/**
 * Helper method for extracting values from a specific key in a multidimensional array
 *
 * @param array $data Data
 * @param string $key Key
 *
 * @return array $data Flattened array of values
 */
	protected function _extract($data, $key) {
		array_walk($data, function(&$value, $index, $key) {
			$value = !empty($value[$key]) ? $value[$key] : null;
		}, $key);

		return array_filter($data);
	}

/**
 * Generate 36-character unique ID
 *
 * @return string Unique ID
 */
	protected function _generateId() {
		return uniqid() . '-' . mt_rand(1000, 9999) . '-' . mt_rand(100000, 999999) . '-' . time();
	}

/**
 * Construct and execute database queries
 *
 * @param string $query Query string
 *
 * @return array $result Return associative array if data exists, otherwise return boolean ($execute)
 */
	protected function _query($query) {
		$database = new mysqli($this->config['database']['hostname'], $this->config['database']['username'], $this->config['database']['password'], $this->config['database']['name']);
		$connection = $database->prepare($query);

		if (
			empty($connection) ||
			!is_object($connection)
		) {
			return false;
		}

		$execute = $connection->execute();
		$result = $connection->get_result();
		$connection->close();
		return !empty($result) ? $result->fetch_all(MYSQLI_ASSOC) : $execute;
	}

/**
 * Database helper method for retrieving data
 * @todo Simplify complex nested AND + OR + NOT queries without using joins
 *
 * @param string $table Table name
 * @param array $conditions Search parameters
 * @param string $order Sort results
 *
 * @return array $result Return associative array if it exists, otherwise return boolean ($execute)
 */
	public function find($table, $conditions = array(), $order = 'id DESC') {
		$query = 'SELECT * FROM ' . $table;

		if (!empty($conditions)) {
			array_walk($conditions, function(&$value, $key) {
				if (
					!empty($value) &&
					is_array($value)
				) {
					$value = $key . " IN ('" . implode("','", $value) . "')";
				} else {
					$value = $key . "='" . $value . "'";
				}
			});
			$query .= ' WHERE ' . implode(' AND ', $conditions);
		}

		$query .= ' ORDER BY ' . $order;

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
 * @todo If third parameter is passed, return modified rows with all fields
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

		foreach (array_chunk($rows, 88) as $rows) {
			$groupValues = array();

			foreach ($rows as &$row) {
				$fields = array_keys($row);
				$values = array_values($row);

				if (!in_array('id', $fields)) {
					array_unshift($fields, 'id');
					array_unshift($values, $this->_generateId());
				}

				$ids[] = $values[array_search('id', $fields)];
				$groupValues[implode(',', $fields)][] = implode("','", $values);
			}

			foreach ($groupValues as $fields => $values) {
				$updateFields = explode(',', $fields);
				array_walk($updateFields, function(&$value, $index) {
					$value = $value . '=' . $value;
				});

				$queries[] = 'INSERT INTO ' . $table . '(' . $fields . ") VALUES ('" . implode("'),('", $values) . "') ON DUPLICATE KEY UPDATE " . implode(',', $updateFields);
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
 * @return boolean True if ID exists and is formatted correctly (alphanumeric [xxxxxxxxxxxxx-xxxx-xxxxxx-xxxxxxxxxx]).
 */
	public function validateId($id, $table) {
		return !empty($this->find($table, array(
			'id' => preg_replace("/[^a-zA-Z0-9-]+/", '', $id)
		)));
	}

}
