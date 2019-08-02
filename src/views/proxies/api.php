<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/config.php');
	require_once($config->settings['base_path'] . '/controllers/proxies.php');
	echo json_encode($data);
?>
