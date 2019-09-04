<?php
/**
 * Transactions Controller
 *
 * @author    Will Parsons parsonsbots@gmail.com
 * @copyright 2019 Will Parsons
 * @license   https://github.com/parsonsbots/proxies/blob/master/LICENSE MIT License
 * @link      https://parsonsbots.com
 * @link      https://eightomic.com
 */
require_once($config->settings['base_path'] . '/models/transactions.php');

class TransactionsController extends TransactionsModel {

/**
 * Transactions API
 *
 * @return array Response
 */
	public function api() {
		$response = $this->_saveTransaction($_POST);

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
