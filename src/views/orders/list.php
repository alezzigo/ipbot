<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/config.php');
	$styleSheets = array(
		$config->settings['base_url'] . '/resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/orders.php');
	require_once($config->settings['base_path'] . '/views/layouts/default/header.php');
?>
<main class="section orders-list">
	<div class="container small">
		<h1>Orders</h1>
		<div class="message-container"></div>
		<div class="orders-container"></div>
	</div>
</div>

<?php
	$scripts = array(
		$config->settings['base_url'] . '/resources/js/default.js',
		$config->settings['base_url'] . '/resources/js/orders.js',
		$config->settings['base_url'] . '/resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/layouts/default/footer.php');
?>
