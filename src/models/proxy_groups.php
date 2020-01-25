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
			// ..
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
			// ..
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
					'text' => 'Selected proxy groups deleted successfully.'
				);
			}

			return $response;
		}

	}
?>
