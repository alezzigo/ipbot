<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ProxyGroupsModel extends AppModel {

	/**
	 * Process proxy group adding requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function add($table, $parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error adding proxy group, please try again.')
				)
			);

			if (
				!empty($parameters['data']['name']) &&
				!empty($parameters['data']['order_id']) &&
				is_string($parameters['data']['name']) &&
				is_string($parameters['data']['order_id'])
			) {
				$response['message']['text'] = 'Proxy group <strong>' . $parameters['data']['name'] . '</strong> already exists for this order, please try a different proxy group name.';
				$existingProxyGroupParameters = array(
					'conditions' => array_merge(array_intersect_key($parameters['data'], array(
						'name' => true,
						'order_id' => true
					)), array(
						'user_id' => $parameters['user']['id']
					))
				);
				$existingProxyGroupParameters['conditions']['name'] = strtolower($existingProxyGroupParameters['conditions']['name']);
				$existingProxyGroup = $this->fetch('proxy_groups', $existingProxyGroupParameters);
				$proxyGroupData = array(
					array_merge($existingProxyGroupParameters['conditions'], array(
						'name' => $parameters['data']['name']
					))
				);

				if (empty($existingProxyGroup['count'])) {
					$response['message']['text'] = 'Error adding new proxy group <strong>' . $parameters['data']['name'] . '</strong>, please try again.';

					if ($this->save('proxy_groups', $proxyGroupData)) {
						$response['message'] = array(
							'status' => 'success',
							'text' => 'Proxy group <strong>' . $parameters['data']['name'] . '</strong> added successfully.'
						);
					}
				}
			}

			return $response;
		}

	/**
	 * Process proxy group editing requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function edit($table, $parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error editing selected proxy group, please try again.')
				)
			);

			if (
				!empty($parameters['data']['id']) &&
				!empty($parameters['data']['name']) &&
				!empty($parameters['data']['order_id']) &&
				is_string($parameters['data']['id']) &&
				is_string($parameters['data']['name']) &&
				is_string($parameters['data']['order_id'])
			) {
				$response['message']['text'] = 'Proxy group <strong>' . $parameters['data']['name'] . '</strong> already exists for this order, please try a different proxy group name.';
				$existingProxyGroupParameters = array(
					'conditions' => array_merge(array_intersect_key($parameters['data'], array(
						'id' => true,
						'order_id' => true
					)), array(
						'user_id' => $parameters['user']['id']
					))
				);
				$existingProxyGroup = $this->fetch('proxy_groups', $existingProxyGroupParameters);
				$existingProxyGroupParameters['conditions']['name'] = strtolower($parameters['data']['name']);
				unset($existingProxyGroupParameters['conditions']['id']);
				$existingProxyGroupName = $this->fetch('proxy_groups', $existingProxyGroupParameters);
				$proxyGroupData = array(
					array(
						'id' => $parameters['data']['id'],
						'name' => $parameters['data']['name']
					)
				);

				if (
					!empty($existingProxyGroup['count']) &&
					empty($existingProxyGroupName['count'])
				) {
					$response['message']['text'] = 'Error editing proxy group <strong>' . $existingProxyGroup['data'][0]['name'] . '</strong>, please try again.';

					if ($this->save('proxy_groups', $proxyGroupData)) {
						$response['message'] = array(
							'status' => 'success',
							'text' => 'Proxy group <strong>' . $existingProxyGroup['data'][0]['name'] . '</strong> renamed to <strong>' . $parameters['data']['name'] . '</strong> successfully.'
						);
					}
				}
			}

			return $response;
		}

	/**
	 * Process proxy group removal requests
	 *
	 * @param string $table
	 * @param array $parameters
	 *
	 * @return array $response
	 */
		public function remove($table, $parameters) {
			$response = array(
				'message' => array(
					'status' => 'error',
					'text' => ($defaultMessage = 'Error removing selected proxy groups, please try again.')
				)
			);

			if (
				!empty($parameters['items'][$table]['data']) &&
				($proxyGroupIds = $parameters['items'][$table]['data']) &&
				$this->delete('proxy_groups', array(
					'id' => $proxyGroupIds
				)) &&
				$this->delete('proxy_group_proxies', array(
					'proxy_group_id' => $proxyGroupIds
				))
			) {
				$response['message'] = array(
					'status' => 'success',
					'text' => 'Selected proxy groups removed successfully.'
				);
			}

			return $response;
		}

	}
?>
