<?php
/**
 * App Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once('../../config/config.php');

class App extends Config {

	protected function _extract($data, $key) {
		array_walk($data, function(&$value, $index, $key) {
			print_r($value);
			$value = !empty($value[$key]) ? $value[$key] : null;
		}, $key);

		return array_filter($data);
	}

	protected function _generateId() {
		return uniqid() . '-' . mt_rand(1000, 9999) . '-' . mt_rand(100000, 999999) . '-' . time();
	}

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

	public function find($table, $conditions = array(), $order = 'created DESC') {
		$query = 'SELECT * FROM ' . $table;

		if (!empty($conditions)) {
			array_walk($conditions, function(&$value, $key) {
				$value = $key . "='" . $value . "'";
			});
			$query .= ' WHERE ' . implode(' AND ', $conditions); // TODO: simplify complex nested AND + OR + NOT queries without using joins
		}

		$query .= ' ORDER BY ' . $order;

		return $this->_query($query);
	}

	public function redirect($path, $responseCode = 301) {
		header('Location: ' . $path, true, $responseCode);
	}

	// TODO: URL routing
	public function route() {
		$method = array_shift(array_reverse(explode('/', str_replace('.php', '', $_SERVER['SCRIPT_NAME']))));

		if (method_exists($this, $method)) {
			return $this->$method();
		}

		$this->redirect($this->config['base_url']);
	}

	public function save($table, $rows = array()) {
		$ids = array();
		$queries = array();

		foreach (array_chunk($rows, 51) as $rows) {
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
				return false;
			}
		}

		return true;
	}

	public function validateId($id, $table) {
		return !empty($this->find($table, array(
			'id' => preg_replace("/[^a-zA-Z0-9-]+/", '', $id)
		)));
	}

}
