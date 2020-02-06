<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ActionsModel extends AppModel {

	/**
	 * Retrieve order IDs with processing actions
	 *
	 * @return array $response
	 */
		public function retrieveOrderIdsWithProcessingActions() {
			$response = array();
			$orderIdsWithProcessingActions = $this->fetch('actions', array(
				'conditions' => array(
					'foreign_key' => 'order_id',
					'processed' => false
				),
				'fields' => array(
					'foreign_value'
				)
			));

			if (!empty($orderIdsWithProcessingActions['count'])) {
				$response = $orderIdsWithProcessingActions['data'];
			}

			return $response;
		}

	/**
	 * Shell method for processing bulk actions
	 *
	 * @param string $table
	 *
	 * @return array $response
	 */
		public function shellProcessActions($table) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => 'There aren\'t any new ' . $table . ' to process, please try again later.'
				)
			);
			$actionParameters = array(
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
					'chunks',
					'encoded_items_processed',
					'encoded_items_to_process',
					'encoded_parameters',
					'foreign_key',
					'foreign_value',
					'id',
					'progress',
					'token_id',
					'user_id'
				),
				'sort' => array(
					'field' => 'created',
					'order' => 'ASC'
				)
			);
			$actionsProcessing = $this->fetch('actions', $actionParameters);

			if (empty($actionsProcessing['count'])) {
				$actionParameters['conditions']['AND']['OR'] = array(
					'processing' => false,
					'AND' => array(
						'processing' => true,
						'modified <' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
					)
				);
				$actionsToProcess = $this->fetch('actions', $actionParameters);
				$actionsProcessedCount = 0;

				if (!empty($actionsToProcess['count'])) {
					$actionData = array();

					foreach ($actionsToProcess['data'] as $actionToProcess) {
						$actionData[] = array(
							'id ' => $actionToProcess['id'],
							'processing' => true
						);
					}

					if ($this->save('actions', $actionData)) {
						foreach ($actionsToProcess['data'] as $actionToProcess) {
							$itemsProcessed = (array) json_decode($actionToProcess['encoded_items_processed'], true);
							$itemsToProcess = json_decode($actionToProcess['encoded_items_to_process'], true);
							$parameters = json_decode($actionToProcess['encoded_parameters'], true);

							if ($actionTable = $parameters['table']) {
								$actionData = array(
									array(
										'id' => $actionToProcess['id']
									)
								);
								$actionProgress = min(100, $actionToProcess['progress'] + ceil(100 / $actionToProcess['chunks']));
								$encode = $this->encode[$table] ? $this->encode[$table] : false;

								if (!empty($itemsToProcess)) {
									$parameters['items'][$parameters['item_list_name']] = array(
										'data' => array_splice($itemsToProcess, count($itemsProcessed), 1),
										'table' => $actionTable
									);
									$actionData[0]['encoded_items_processed'] = json_encode(array_merge($itemsProcessed, $parameters['items'][$parameters['item_list_name']]['data']));
									$parameters['items'] = $this->_retrieveItems($parameters, true);
								}

								$parameters['data']['action'] = array_merge($actionData[0], array(
									'processed' => ($actionProgress === 100),
									'processing' => false,
									'progress' => $actionProgress
								));
								$actionResponse = $this->_call($actionTable, array(
									'methodName' => $parameters['action'],
									'methodParameters' => array(
										$actionTable,
										$parameters
									)
								));
								$actionData = array(
									$parameters['data']['action']
								);
								// ..

								if ($this->save($table, $actionData)) {
									$actionsProcessedCount++;
								}
							}
						}
					}

					if ($actionsProcessedCount) {
						$response = array(
							'message' => array(
								'status' => 'success',
								'text' => $actionsProcessedCount . ' ' . ($actionsProcessedCount === 1 ? $this->_formatPluralToSingular($table) : $table) . ' processed successfully.'
							)
						);
					}
				}
			}

			return $response;
		}

	}
?>
