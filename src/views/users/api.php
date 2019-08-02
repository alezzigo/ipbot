<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/config.php');
	require_once($config->settings['base_path'] . '/controllers/users.php');
	echo json_encode($data);
?>
