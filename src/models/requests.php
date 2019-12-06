<?php
/**
 * Requests Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
require_once($config->settings['base_path'] . '/models/proxies.php');

class RequestsModel extends ProxiesModel {

/**
 * Shell method for processing bulk requests
 *
 * @param string $table
 *
 * @return array $response
 */
	public function shellProcessRequests($table) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'There aren\'t any new ' . $table . ' to process, please try again later.'
			)
		);
		$requestParameters = array(
			'conditions' => array(
				'AND' => array(
					'request_processed' => false,
					'OR' => array(
						'AND' => array(
							'modified >' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
							'request_processing' => true
						)
					)
				)
			),
			'fields' => array(
				'encoded_items_processed',
				'encoded_items_to_process',
				'encoded_parameters',
				'foreign_key',
				'foreign_value',
				'id',
				'request_chunks',
				'request_progress',
				'token_id'
			),
			'sort' => array(
				'field' => 'created',
				'order' => 'ASC'
			)
		);
		$requestsProcessing = $this->fetch('requests', $requestParameters);

		if (empty($requestsProcessing['count'])) {
			$requestParameters['conditions']['AND']['OR'] = array(
				'request_processing' => false,
				'AND' => array(
					'modified <' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
					'request_processing' => true
				)
			);
			$requestsToProcess = $this->fetch('requests', $requestParameters);
			$requestsProcessedCount = 0;

			if (!empty($requestsToProcess['count'])) {
				foreach ($requestsToProcess['data'] as $request) {
					// ..
					$itemsProcessed = (array) json_decode($request['encoded_items_processed'], true);
					$itemsToProcess = json_decode($request['encoded_items_to_process'], true);
					$parameters = json_decode($request['encoded_parameters'], true);

					if (
						!empty($itemsToProcess) &&
						($requestAction = $parameters['action']) &&
						($requestTable = $parameters['table']) &&
						method_exists($this, $requestAction)
					) {
						$parameters['items'][$requestTable] = array_splice($itemsToProcess, count($itemsProcessed), 1);
						$itemsProcessed = array_merge($itemsProcessed, $parameters['items'][$requestTable]);
						$completed = (count($itemsProcessed) === count($itemsToProcess));
						$parameters['items'] = $this->_retrieveItems($parameters, true);
						$requestResponse = $this->$requestAction($requestTable, $parameters);
						$requestData = array(
							array(
								'encoded_items_processed' => json_encode($itemsProcessed),
								'id' => $request['id'],
								'request_processed' => $completed,
								'request_processing' => false,
								'request_progress' => ($completed ? 100 : min(100, $request['request_progress'] + (ceil(100 / $request['request_chunks']))))
							)
						);

						if (
							$requestResponse['message']['status'] === 'success' &&
							$this->save($table, $requestData)
						) {
							$requestsProcessedCount++;
						}
					}
				}

				if ($requestsProcessedCount) {
					$response = array(
						'message' => array(
							'status' => 'success',
							'text' => $requestsProcessedCount . ' requests processed successfully.'
						)
					);
				}
			}
		}

		return $response;
	}

}
