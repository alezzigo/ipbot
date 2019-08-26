<?php
/**
 * Invoices Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/invoices.php');

class InvoicesController extends InvoicesModel {

/**
 * Invoices API
 *
 * @return array Response
 */
	public function api() {
		return $this->_request($_POST);
	}

}

$invoicesController = new InvoicesController();
$data = $invoicesController->route($config->parameters);
