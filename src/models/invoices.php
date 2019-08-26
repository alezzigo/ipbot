<?php
/**
 * Invoices Model
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/app.php');

class InvoicesModel extends AppModel {

/**
 * List invoices
 *
 * @return array Invoices data
 */
	public function list() {
		return array();
	}

/**
 * View invoice
 *
 * @param array $parameters Parameters
 *
 * @return array Invoice data
 */
	public function view($parameters) {
		if (
			empty($invoiceId = $parameters['id']) ||
			!is_numeric($invoiceId)
		) {
			$this->redirect($this->settings['base_url'] . 'invoices');
		}

		return array(
			'invoice_id' => $parameters['id']
		);
	}

}
