<?php
	if (!empty($config->settings['base_path'])) {
		require_once($config->settings['base_path'] . '/models/app.php');
	}

	class ProxyGroupsModel extends AppModel {

	/**
	 * Process proxy group removal
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
