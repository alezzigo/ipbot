<?php
/**
 * Proxies Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class ProxiesModel extends AppModel {

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
					'proxy_id' => $parameters['items']['proxies']['data'],
					'proxy_group_id' => array_values($parameters['items']['proxy_groups']['data'])
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

		return array_merge($this->find($table, $parameters), array(
			'message' => $message
		));
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
					'automatic_replacement_interval_type' => $automaticReplacementIntervalType,
					'automatic_replacement_interval_value' => $parameters['data']['automatic_replacement_interval_value'],
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
					'next_replacement_available' => date('Y-m-d H:i:s', strtotime('+1 week')),
					'order_id' => $orderId,
					'status' => 'online',
					'user_id' => 1,
					'whitelisted_ips' => ''
				);
			}

			if (!empty($newItemData)) {
				$processingNodes = $this->find('nodes', array(
					'conditions' => array(
						'AND' => array(
							'allocated' => false,
							'OR' => array(
								'modified <' => date('Y-m-d H:i:s', strtotime('-1 minute')),
								'processing' => false
							)
						)
					),
					'fields' => array(
						'asn',
						'city',
						'country_code',
						'country_name',
						'id',
						'ip',
						'isp',
						'region'
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
							'allocated' => true,
							'id' => ($processingNodes['data'][$key]['node_id'] = $processingNodes['data'][$key]['id']),
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

		if (($response['tokens'][$table] = $this->_getToken($parameters)) !== $parameters['tokens'][$table]) {
			$response['items'][$table] = array();
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

}
