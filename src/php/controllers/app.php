<?php
/**
 * App Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/models/app.php');

class AppController extends AppModel {

/**
 * API for data retrieval
 * @todo Use tokens for API request authentication with user auth, containable queries, additional sanitation for order and condition values
 *
 * @return array Data, status code
 */
	public function api() {
		if (empty($_POST['json'])) {
			http_response_code(400);
			return 'JSON data is required for API.';
		}

		$data = json_decode($_POST['json'], true);

		if (empty($data['group'])) {
			http_response_code(400);
			return 'Group selection is required.';
		}

		if (!empty($this->permissions['api'][$data['group']])) {
			if (empty($data['action'])) {
				$data['action'] = 'find';
			}

			if (
				empty($this->permissions['api'][$data['group']][$data['action']]) ||
				!method_exists($this, $data['action'])
			) {
				http_response_code(400);
				return 'Invalid API action [' . $data['action'] . '], please check permissions.';
			}

			if (empty($data['fields'])) {
				$data['fields'] = $this->permissions['api'][$data['group']][$data['action']]['fields'];
			} else {
				foreach ($data['fields'] as $field) {
					if (!in_array($field, $this->permissions['api'][$data['group']][$data['action']]['fields'])) {
						http_response_code(400);
						return 'Invalid API field [' . $field . '], please check permissions.';
					}
				}
			}

			if (!empty($data['conditions'])) {
				if (!is_array($data['conditions'])) {
					http_response_code(400);
					return 'Invalid API field [' . $field . '], please check permissions.';
				}

				foreach ($data['conditions'] as $field => $condition) {
					if (
						(
							!is_string($field) &&
							!is_int($field)
						) ||
						(
							!is_string($condition) &&
							!is_int($condition)
						)
					) {
						unset($data['conditions'][$field]);
					}
				}
			}

			if (empty($data['order'])) {
				$data['order'] = 'modified DESC';
			}

			if (
				!empty($data['order']) &&
				!is_string($data['order'])
			) {
				http_response_code(400);
				return 'String required for order parameter.';
			}

			if (
				(
					!empty($data['limit']) &&
					!is_int($data['limit'])
				) ||
				(
					!empty($data['offset']) &&
					!is_int($data['offset'])
				)
			) {
				http_response_code(400);
				return 'Integer required for API limit and offset parameters.';
			}

			$method = $data['action'];

			return array(
				'data' => $this->$method($data['group'], $data),
				'count' => current(array_shift($this->find($data['group'], array(
					'conditions' => $data['conditions'],
					'count' => true
				))))
			);
		} else {
			http_response_code(400);
			return 'Invalid API data group [' . $data['group'] . '], please check permissions.';
		}
	}

}

$controller = new AppController();
$data = $controller->route();
