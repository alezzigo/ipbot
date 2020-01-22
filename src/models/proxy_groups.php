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
					'text' => ($defaultMessage = 'Error removing proxy groups, please try again.')
				)
			);
			// ..
			return $response;
		}

	}
?>
