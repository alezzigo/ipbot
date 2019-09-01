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
		return $this->_request($_POST);
	}

}

$transactionsController = new TransactionsController();
$data = $transactionsController->route($config->parameters);
