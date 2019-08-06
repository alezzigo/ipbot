<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/config.php');
	require_once($config->settings['base_path'] . '/controllers/orders.php');
	echo json_encode($data);
?>
