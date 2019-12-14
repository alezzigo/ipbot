<?php
require_once($config->settings['base_path'] . '/models/actions.php');

class ActionsController extends ActionsModel {

/**
 * Actions API
 *
 * @return array Response
 */
	public function api() {
		return $this->_request($_POST);
	}

}

$actionsController = new ActionsController();
$data = $actionsController->route($config->parameters);
?>
