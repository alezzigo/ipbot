<?php
	class Config {

		public static $config;

		public function __construct() {
			$this->config = array(
				'database' => array(
					'type' => 'mysql',
					'hostname' => '127.0.0.1',
					'username' => 'root',
					'password' => 'password',
					'name' => 'proxy_control_panel'
				),
				'base_url' => '/src/php/'
			);
		}

	}
?>
