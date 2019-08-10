<?php
/**
 * Carts Controller
 *
 * @author Will Parsons
 * @link   https://parsonsbots.com
 */
require_once($config->settings['base_path'] . '/models/carts.php');

class CartsController extends CartsModel {

/**
 * Carts API
 *
 * @return array Response
 */
	public function api() {
		return $this->_request($_POST);
	}

}

$cartsController = new CartsController();
$data = $cartsController->route($config->parameters);
