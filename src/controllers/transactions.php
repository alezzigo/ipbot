<?php
/**
 * Transactions Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/transactions.php');

class TransactionsController extends TransactionsModel {

/**
 * Transactions API
 *
 * @return array Response
 */
	public function api() {
		$response = $this->_processTransaction($_POST);

		if (
			!empty($_POST['json']) &&
			is_string($_POST['json'])
		) {
			$response = $this->_request($_POST);
		}

		return $response; ;
	}

}

$transactionsController = new TransactionsController();
$data = $transactionsController->route($config->parameters);
