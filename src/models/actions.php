<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ActionsModel extends AppModel {

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
							'processing' => true,
							'id ' => $actionToProcess['id']
						);
					}

					if ($this->save('actions', $actionData)) {
						foreach ($actionsToProcess['data'] as $actionToProcess) {
							$itemsProcessed = (array) json_decode($actionToProcess['encoded_items_processed'], true);
							$itemsToProcess = json_decode($actionToProcess['encoded_items_to_process'], true);
							$parameters = json_decode($actionToProcess['encoded_parameters'], true);

							if (
								!empty($itemsToProcess) &&
								($actionTable = $parameters['table'])
							) {
								$actionData = array(
									array(
										'id' => $actionToProcess['id']
									)
								);

								if ($parameters['tokens'][$actionTable] === $this->_getToken($actionTable, $parameters, $actionToProcess['foreign_key'], $actionToProcess['foreign_value'])) {
									$parameters['items'][$actionTable] = array_splice($itemsToProcess, count($itemsProcessed), 1);
									$itemsProcessed = array_merge($itemsProcessed, $parameters['items'][$actionTable]);
									$completed = (count($itemsProcessed) === count($itemsToProcess));
									$parameters['items'] = $this->_retrieveItems($parameters, true);
									$actionResponse = $this->_call($actionTable, array(
										'methodName' => $parameters['action'],
										'methodParameters' => array(
											$actionTable,
											$parameters
										)
									));

									if (
										!empty($actionResponse) &&
										$actionResponse['message']['status'] === 'success'
									) {
										$actionData = array(
											array_merge($actionData[0], array(
												'encoded_items_processed' => json_encode($itemsProcessed),
												'processed' => $completed,
												'processing' => false,
												'progress' => ($completed ? 100 : min(100, $actionToProcess['progress'] + (ceil(100 / $actionToProcess['chunks']))))
											))
										);
									}
								} else {
									$actionData = array(
										array_merge($actionData[0], array(
											'progress' => ($completed ? 100 : min(100, $actionToProcess['progress'] + (ceil(100 / $actionToProcess['chunks'])))),
											'processed' => true,
											'processing' => false
										))
									);
								}

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