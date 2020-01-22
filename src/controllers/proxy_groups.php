<?php
	require_once($config->settings['base_path'] . '/models/proxy_groups.php');

	class ProxyGroupsController extends ProxyGroupsModel {

	/**
	 * Proxy Groups API
	 *
	 * @return array Response
	 */
		public function api() {
			return $this->_request($_POST);
		}

	}

	$proxyGroupsController = new ProxyGroupsController();
	$data = $proxyGroupsController->route($config->parameters);
?>
