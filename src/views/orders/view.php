<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/orders.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
	$frames = array(
		'actions',
		'authenticate',
		'downgrade',
		'download',
		'endpoint',
		'group',
		'replace',
		'requests',
		'rotate',
		'search'
	);

	foreach ($frames as $frame) {
		if (file_exists($file = $config->settings['base_path'] . '/views/sections/' . $frame . '.php')) {
			require_once($file);
		}
	}
?>
<main process="order">
	<div class="section">
		<div class="container small">
			<div class="item-controls-container controls-container scrollable"></div>
			<h1 class="order-name"></h1>
			<div class="item-list" page="all" table="proxies"></div>
			<input name="order_id" type="hidden" value="<?php echo $data['order_id']; ?>">
		</div>
	</div>
</main>
<?php
	$scripts = array(
		$config->settings['base_url'] . 'resources/js/default.js',
		$config->settings['base_url'] . 'resources/js/proxies.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
