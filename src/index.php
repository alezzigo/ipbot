<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/app.php');
	$styleSheets = array(
		$app->config['base_url'] . '/resources/css/default.css'
	);
	require_once($app->config['base_path'] . '/views/layouts/default/header.php');
?>
<main class="section">
	<div class="container small">
		[Homepage contents]
		<a href="views/orders/">View Orders</a>
	</div>
</main>
<?php
	$scripts = array(
		$app->config['base_url'] . '/resources/js/default.js',
		$app->config['base_url'] . '/resources/js/users.js',
		$app->config['base_url'] . '/resources/js/app.js'
	);
	require_once($app->config['base_path'] . '/views/layouts/default/footer.php');
?>
