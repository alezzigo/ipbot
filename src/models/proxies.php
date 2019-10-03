<?php
/**
 * Proxies Model
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class ProxiesModel extends AppModel {

/**
 * Generate random proxy username:password authentication
 *
 * @param array $proxyData
 *
 * @return array $response
 */
	protected function _generateRandomAuthentication($proxyData) {
		$characters = 'bcdfghjklmnpqrstvwxyzbcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ01234567890123456789012345678901234567890123456789012345678901234567890123456789';
		$proxyData['username'] .= uniqid();
		$proxyData['password'] .= uniqid();

		for ($i = 0; $i < mt_rand(5, 10); $i++) {
			$proxyData['username'] .= $characters[mt_rand(0, strlen($characters) - 1)];
		}

		for ($i = 0; $i < mt_rand(5, 10); $i++) {
			$proxyData['password'] .= $characters[mt_rand(0, strlen($characters) - 1)];
		}

		$response = $proxyData;
		return $response;
	}

/**
 * Process authenticate requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function authenticate($table, $parameters) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => ($defaultMessage = 'Error authenticating proxies, please try again.')
			)
		);

		if (empty($parameters['items'][$table]['count'])) {
			$response['message']['text'] = 'There are no ' . $table . ' selected to authenticate.';
		} else {
			$proxies = $parameters['items'][$table]['data'];

			if (
				empty($parameters['data']['generate_unique']) &&
				(
					!empty($parameters['data']['username']) ||
					!empty($parameters['data']['password'])
				) &&
				(
					empty($parameters['data']['username']) ||
					empty($parameters['data']['password'])
				)
			) {
				$response['message']['text'] = 'Both username and password must be either set or empty.';
			} else {
				if (
					empty($parameters['data']['generate_unique']) &&
					(
						(
							!empty($parameters['data']['username']) &&
							(
								strlen($parameters['data']['username']) < 4 ||
								strlen($parameters['data']['username']) > 15
							)
						) ||
						(
							!empty($parameters['data']['password']) &&
							(
								strlen($parameters['data']['password']) < 4 ||
								strlen($parameters['data']['password']) > 15
							)
						)
					)
				) {
					$response['message']['text'] = 'Both username and password must be between 4 and 15 characters.';
				} else {
					if (
						($usernames = array()) &&
						!empty($parameters['data']['username'])
					) {
						$existingUsernames = $this->find('proxies', array(
							'conditions' => array(
								'username !=' => ''
							),
							'fields' => array(
								'username'
							)
						));

						if (!empty($existingUsernames['count'])) {
							$usernames = array_unique($existingUsernames['data']);
						}
					}

					$whitelistedIps = implode("\n", (!empty($parameters['data']['whitelisted_ips']) ? $this->_parseIps($parameters['data']['whitelisted_ips']) : array()));

					foreach ($proxies as $key => $proxy) {
						$proxy = array(
							'id' => $proxy,
							'username' => $parameters['data']['username'],
							'password' => $parameters['data']['password'],
							'whitelisted_ips' => $whitelistedIps
						);

						if (!empty($parameters['data']['generate_unique'])) {
							$proxy = $this->_generateRandomAuthentication($proxy);
						}

						$proxies[$key] = $proxy;
					}

					if (
						!empty($parameters['data']['username']) &&
						in_array($parameters['data']['username'], $usernames)
					) {
						$response['message']['text'] = 'Username [' . $parameters['data']['username'] . '] is already in use, please try a different username.';
					} else {
						$response['message']['text'] = $defaultMessage;

						if ($this->save('proxies', $proxies)) {
							$response['message'] = array(
								'status' => 'success',
								'text' => 'Authentication saved successfully'
							);
						}
					}
				}
			}

			$response['items'][$table] = array();
		}

		$response = array_merge($this->find($table, $parameters), $response);
		return $response;
	}

/**
 * Process copy requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function copy($table, $parameters) {
		$items = array();
		$response = $this->find($table, array(
			'conditions' => array(
				'id' => $parameters['items'][$table]['data']
			),
			'fields' => $this->permissions[$table]['copy']['fields']
		));

		if (
			!empty($parameters['data']) &&
			is_array($parameters['data']) &&
			!empty($parameters['data']['proxy_list_type'])
		) {
			foreach ($parameters['data'] as $columnField => $columnValue) {
				if ($columnValue == 'port') {
					$parameters['data'][$columnField] = $parameters['data']['proxy_list_type'] . '_port';
				}
			}
		}

		if (!empty($response['data'])) {
			$delimiters = array(
				!empty($parameters['data']['ipv4_delimiter_1']) ? $parameters['data']['ipv4_delimiter_1'] : '',
				!empty($parameters['data']['ipv4_delimiter_2']) ? $parameters['data']['ipv4_delimiter_2'] : '',
				!empty($parameters['data']['ipv4_delimiter_3']) ? $parameters['data']['ipv4_delimiter_3'] : '',
				''
			);
			$delimiterMask = implode('', array_unique(array_filter($delimiters)));
			$separators = array(
				'comma' => ',',
				'new_line' => "\n",
				'semicolon' => ';',
				'space' => ' ',
				'underscore' => '_'
			);

			if (
				empty($parameters['data']['separated_by']) ||
				empty($separator = $separators[$parameters['data']['separated_by']])
			) {
				$separator = "\n";
			}

			foreach ($response['data'] as $key => $data) {
				$items[$key] = '';

				for ($i = 1; $i < 5; $i++) {
					$items[$key] .= !empty($column = $response['data'][$key][$parameters['data']['ipv4_column_' . $i]]) ? $column . $delimiters[($i - 1)] : '';
				}

				$items[$key] = rtrim($items[$key], $delimiterMask);
			}
		}

		$response = array(
			'count' => count($items),
			'data' => implode($separator, $items)
		);
		return $response;
	}

/**
 * Process group requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function group($table, $parameters) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'Error processing your group request, please try again.'
			)
		);

		if (
			$table == 'proxy_groups' &&
			!empty($groupData = array_intersect_key($parameters['data'], array(
				'id' => true,
				'name' => true,
				'order_id' => true
			)
		))) {
			if (!empty($parameters['conditions'])) {
				$groupData = array_merge($groupData, $parameters['conditions']);
			}

			$groupParameters = array(
				'conditions' => $groupData,
				'fields' => array(
					'created',
					'id',
					'modified',
					'name',
					'order_id',
					'user_id'
				),
				'limit' => 1
			);

			if (
				!empty($groupName = $groupData['name']) &&
				!empty($groupData['order_id'])
			) {
				$response['message']['text'] = 'Group "' . $groupName . '" already exists for this order.';
				unset($groupParameters['conditions']['id']);
				$existingGroup = $this->find('proxy_groups', $groupParameters);

				if (empty($existingGroup['count'])) {
					$response['message']['text'] = 'Error creating new group, please try again.';
					$this->save('proxy_groups', array(
						$groupData
					));
					$groupData = $this->find('proxy_groups', $groupParameters);

					if (!empty($groupData['count'])) {
						$response['message'] = array(
							'status' => 'success',
							'text' => 'Group "' . $groupName . '" saved successfully.'
						);
					}
				}
			}

			if (
				!empty($groupData['id']) &&
				!isset($groupData['name'])
			) {
				$response['message']['text'] = 'Error deleting group, please try again.';
				$existingGroup = $this->find('proxy_groups', $groupParameters);

				if (
					!empty($existingGroup['count']) &&
					$this->delete('proxy_groups', $groupData) &&
					$this->delete('proxy_group_proxies', array(
						'proxy_group_id' => $groupData['id']
					))
				) {
					$response['message'] = array(
						'status' => 'success',
						'text' => 'Group deleted successfully.'
					);
				}
			}
		}

		if ($table == 'proxies') {
			if (
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

				$response['message']['text'] = 'Error adding selected items to groups.';

				if ($this->save('proxy_group_proxies', array_values($groups))) {
					$response['message'] = array(
						'status' => 'success',
						'text' => 'Items added to selected groups successfully.'
					);
				}
			}

			$response['items'][$table] = array();
		} else {
			unset($parameters['limit']);
			unset($parameters['offset']);
		}

		$parameters['fields'] = $this->permissions[$table]['find']['fields'];
		$response = array_merge($this->find($table, $parameters), $response);
		return $response;
	}

/**
 * Process replace requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function replace($table, $parameters) {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'Error processing your replacement request, please try again.'
			)
		);

		if (
			!empty($parameters['items'][$table]['count']) &&
			is_array($parameters['items'][$table]['data'])
		) {
			$response['message']['text'] = 'There was an error applying the replacement settings to your ' . $table . ', please try again.';
			$newItemData = $oldItemData = array(
				'automatic_replacement_interval_value' => 0,
				'transfer_authentication' => !empty($parameters['data']['transfer_authentication']) ? true : false
			);

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
				$newItemData = $oldItemData = array_merge($newItemData, $intervalData);
			}

			if (($orderId = !empty($parameters['conditions']['order_id']) ? $parameters['conditions']['order_id'] : 0)) {
				$oldItemIds = array();

				if (empty($parameters['data']['instant_replacement'])) {
					foreach ($parameters['items'][$table]['data'] as $key => $itemId) {
						$oldItemIds[]['id'] = $itemId;
					}

					$oldItemData = array_replace_recursive(array_fill(0, $parameters['items'][$table]['count'], $oldItemData), $oldItemIds);

					if ($this->save($table, $oldItemData)) {
						$response['message'] = array(
							'status' => 'success',
							'text' => 'Replacement settings applied to ' . $parameters['items'][$table]['count'] . ' of your selected ' . $table . ' successfully.'
						);
					}
				} else {
					$oldItemData += array(
						'replacement_removal_date' => date('Y-m-d H:i:s', strtotime('+24 hours')),
						'status' => 'replaced'
					);
					$newItemData += array(
						'last_replacement_date' => date('Y-m-d H:i:s', time()),
						'next_replacement_available' => date('Y-m-d H:i:s', strtotime('+1 week')),
						'order_id' => $orderId,
						'status' => 'online',
						'user_id' => $parameters['user']['id']
					);
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
						$response['message']['text'] = 'There aren\'t enough ' . $table . ' available to replace your ' . $parameters['items'][$table]['count'] . ' selected ' . $table . ', please try again in a few minutes.';
					} else {
						$allocatedNodes = array();
						$processingNodes['data'] = array_replace_recursive($processingNodes['data'], array_fill(0, count($processingNodes['data']), array(
							'processing' => true
						)));

						if ($this->save('nodes', $processingNodes['data'])) {
							foreach ($processingNodes['data'] as $key => $row) {
								$allocatedNodes[] = array(
									'allocated' => true,
									'id' => ($processingNodes['data'][$key]['node_id'] = $processingNodes['data'][$key]['id']),
									'processing' => false
								);
								$processingNodes['data'][$key] += $newItemData;
								$processingNodes['data'][$key]['previous_node_id'] = $oldItemIds[]['id'] = $parameters['items'][$table]['data'][$key];
								unset($processingNodes['data'][$key]['id']);
								unset($processingNodes['data'][$key]['processing']);
							}

							if ($parameters['tokens'][$table] === $this->_getToken($table, $parameters, 'order_id', $orderId)) {
								if (!empty($parameters['data']['transfer_authentication'])) {
									$oldItemAuthentication = $this->find($table, array(
										'conditions' => array(
											'id' => $parameters['items'][$table]['data']
										),
										'fields' => array(
											'disable_http',
											'disable_socks',
											'password',
											'require_authentication',
											'username',
											'whitelisted_ips'
										)
									));

									if (
										!empty($oldItemAuthentication['count']) &&
										count($oldItemAuthentication['data']) === count($parameters['items'][$table]['data'])
									) {
										$processingNodes['data'] = array_replace_recursive($processingNodes['data'], $oldItemAuthentication['data']);
									}
								}

								$oldItemData = array_replace_recursive(array_fill(0, $parameters['items'][$table]['count'], $oldItemData), $oldItemIds);
								$oldItems = $this->find($table, array(
									'conditions' => array(
										'id' => $parameters['items'][$table]['data']
									),
									'fields' => array(
										'id',
										'ip'
									)
								));

								if (
									$this->save('nodes', $allocatedNodes) &&
									$this->save($table, $oldItemData) &&
									$this->save($table, $processingNodes['data']) &&
									!empty($oldItems['count'])
								) {
									$response['items'][$table] = array();
									$response['message'] = array(
										'status' => 'success',
										'text' => $parameters['items'][$table]['count'] . ' of your selected ' . $table . ' replaced successfully.'
									);
									$mailParameters = array(
										'from' => $this->settings['from_email'],
										'subject' => count($processingNodes['data']) . ' ' . $table . ' replaced successfully',
										'template' => array(
											'name' => 'items_replaced',
											'parameters' => array(
												'link' => 'https://' . $this->settings['base_domain'] . '/orders/' . $orderId,
												'new_items' => $processingNodes['data'],
												'old_items' => $oldItems['data'],
												'table' => str_replace('_', ' ', $table),
												'user' => $parameters['user']
											)
										),
										'to' => $parameters['user']['email']
									);
									$this->_sendMail($mailParameters);
								}
							}
						}
					}
				}
			}
		}

		if (($response['tokens'][$table] = $this->_getToken($table, $parameters, 'order_id', $orderId)) !== $parameters['tokens'][$table]) {
			$response['items'][$table] = array();
		}

		$response = array_merge($this->find($table, $parameters), $response);
		return $response;
	}

/**
 * Process search requests
 *
 * @param string $table
 * @param array $parameters
 *
 * @return array $response
 */
	public function search($table, $parameters) {
		$conditions = array();

		if (
			!empty($broadSearchFields = $this->permissions[$table]['search']['fields']) &&
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
				$value .= '%';
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
		$response = array_merge($response = $this->find($table, $parameters), array(
			'message' => array(
				'status' => 'success',
				'text' => $response['count'] . ' search result' . ($response['count'] !== 1 ? 's' : '')  . ' found. <a class="clear" href="javascript:void(0);">Clear search filter</a>.'
			)
		));
		return $response;
	}

/**
 * Shell method for processing replaced proxy removal
 *
 * @return array $response
 */
	public function shellProcessRemoveReplacedProxies() {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'There aren\'t any new replaced proxies to remove, please try again later.'
			)
		);
		$proxies = $this->find('proxies', array(
			'conditions' => array(
				'replacement_removal_date <' => date('Y-m-d H:i:s', time()),
				'status' => 'replaced'
			),
			'fields' => array(
				'id',
				'node_id',
				'replacement_removal_date',
				'status'
			),
			'limit' => 100000
		));

		if (!empty($proxies['count'])) {
			$nodeData = $proxyIds = array();

			foreach ($proxies['data'] as $proxy) {
				$nodeData[] = array(
					'allocated' => false,
					'id' => $proxy['node_id'],
					'processing' => false
				);
				$proxyIds[] = $proxy['id'];
			}

			if (
				$this->delete('proxies', array(
					'id' => $proxyIds
				)) &&
				$this->save('nodes', $nodeData)
			) {
				$response = array(
					'message' => array(
						'status' => 'success',
						'text' => $proxies['count'] . ' replaced proxies removed successfully.'
					)
				);
			}
		}

		return $response;
	}

/**
 * Shell method for processing scheduled proxy replacements
 *
 * @return array $response
 */
	public function shellProcessScheduledProxyReplacements() {
		$response = array(
			'message' => array(
				'status' => 'error',
				'text' => 'There aren\'t any new scheduled proxies to replace, please try again later.'
			)
		);
		$intervalTypes = array(
			'month',
			'week'
		);
		$intervalValues = array_keys(array_fill(1, 24, true));
		$proxyParameters = array(
			'conditions' => array(
				'automatic_replacement_interval_value >' => 0,
				'status !=' => 'replaced'
			),
			'fields' => array(
				'automatic_replacement_interval_type',
				'automatic_replacement_interval_value',
				'disable_http',
				'disable_socks',
				'id',
				'ip',
				'node_id',
				'password',
				'require_authentication',
				'transfer_authentication',
				'user_id',
				'username',
				'whitelisted_ips'
			),
			'limit' => 100000
		);

		foreach ($intervalTypes as $intervalType) {
			foreach ($intervalValues as $intervalValue) {
				$proxyParameters['conditions']['OR'][] = array(
					'automatic_replacement_interval_type' => $intervalType,
					'last_replacement_date <' => date('Y-m-d H:i:s', strtotime('-' . $intervalValue . ' ' . $intervalType))
				);
			}
		}

		$proxies = $this->find('proxies', $proxyParameters);

		if (!empty($proxies['count'])) {
			$users = array();

			foreach ($proxies['data'] as $proxy) {
				$users[$proxy['user_id']][] = array_merge($proxy, array(
					'replacement_removal_date' => date('Y-m-d H:i:s', strtotime('+24 hours')),
					'status' => 'replaced'
				));
			}

			foreach ($users as $userId => $userProxies) {
				$userEmail = $this->find('users', array(
					'conditions' => array(
						'id' => $userId
					),
					'fields' => array(
						'email'
					)
				));

				if (!empty($userEmail['count'])) {
					$userEmail = $userEmail['data'][0];
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
						'limit' => count($userProxies),
						'sort' => array(
							'field' => 'id',
							'order' => 'DESC'
						)
					));

					if (count($processingNodes['data']) === count($userProxies)) {
						$allocatedNodes = array();
						$processingNodes['data'] = array_replace_recursive($processingNodes['data'], array_fill(0, count($processingNodes['data']), array(
							'processing' => true
						)));

						if ($this->save('nodes', $processingNodes['data'])) {
							foreach ($processingNodes['data'] as $key => $row) {
								$allocatedNodes[] = array(
									'allocated' => true,
									'id' => ($processingNodes['data'][$key]['node_id'] = $processingNodes['data'][$key]['id']),
									'processing' => false
								);
								$processingNodes['data'][$key] += array(
									'automatic_replacement_interval_type' => $userProxies[$key]['automatic_replacement_interval_type'],
									'automatic_replacement_interval_value' => $userProxies[$key]['automatic_replacement_interval_value'],
									'last_replacement_date' => date('Y-m-d H:i:s', time()),
									'next_replacement_available' => date('Y-m-d H:i:s', strtotime('+1 week')),
									'order_id' => $orderId,
									'status' => 'online',
									'user_id' => $userId
								);
								$processingNodes['data'][$key]['previous_node_id'] = $userProxies[$key]['node_id'];

								if (!empty($userProxies[$key]['transfer_authentication'])) {
									$processingNodes['data'][$key] += array(
										'disable_http' => $userProxies[$key]['disable_http'],
										'disable_socks' => $userProxies[$key]['disable_socks'],
										'password' => $userProxies[$key]['password'],
										'transfer_authentication' => true,
										'username' => $userProxies[$key]['username'],
										'whitelisted_ips' => $userProxies[$key]['whitelisted_ips']
									);
								}

								unset($processingNodes['data'][$key]['id']);
								unset($processingNodes['data'][$key]['processing']);
							}

							if (
								$this->save('nodes', $allocatedNodes) &&
								$this->save('proxies', $userProxies) &&
								$this->save('proxies', $processingNodes['data'])
							) {
								$mailParameters = array(
									'from' => $this->settings['from_email'],
									'subject' => count($processingNodes['data']) . ' proxies replaced successfully',
									'template' => array(
										'name' => 'items_replaced',
										'parameters' => array(
											'link' => 'https://' . $this->settings['base_domain'] . '/orders/' . $orderId,
											'new_items' => $processingNodes['data'],
											'old_items' => $userProxies,
											'table' => 'proxies'
										)
									),
									'to' => $userEmail
								);
								$this->_sendMail($mailParameters);
							}
						}
					}
				}
			}

			$response = array(
				'message' => array(
					'status' => 'success',
					'text' => $proxies['count'] . ' scheduled proxies replaced successfully.'
				)
			);
		}

		return $response;
	}

}
