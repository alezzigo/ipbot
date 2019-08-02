<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/config.php');
	$styleSheets = array(
		$config->settings['base_url'] . '/resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/layouts/default/header.php');
?>
<main class="section">
	<div class="container small">
		[Homepage contents]
		<a href="views/orders/">View Orders</a>
	</div>
</main>
<?php
	$scripts = array(
		$config->settings['base_url'] . '/resources/js/default.js',
		$config->settings['base_url'] . '/resources/js/users.js',
		$config->settings['base_url'] . '/resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/layouts/default/footer.php');
?>
