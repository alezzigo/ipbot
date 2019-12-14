<?php
require_once($config->settings['base_path'] . '/models/servers.php');

class ServersController extends ServersModel {

/**
 * Servers API
 *
 * @return array Response
 */
	public function api() {
		$response = $this->_retrieveServerDetails();

		if (
			!empty($_POST['json']) &&
			is_string($_POST['json'])
		) {
			$response = $this->_request($_POST);
		}

		return $response;
	}

}

$serversController = new ServersController();
$data = $serversController->route($config->parameters);
?>
