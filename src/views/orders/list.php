<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/orders.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
	$frames = array(
		'downgrade',
		'upgrade'
	);

	foreach ($frames as $frame) {
		if (file_exists($file = $config->settings['base_path'] . '/views/sections/' . $frame . '.php')) {
			require_once($file);
		}
	}
?>
<main process="orders">
	<div class="section">
		<div class="container small">
			<h1>Orders <a class="button main-button" href="<?php echo $config->settings['base_url'] ?>static-proxies">Create New Order</a></h1>
			<div class="item-list" table="orders"></div>
		</div>
	</div>
</main>
<?php
	$scripts = array(
		$config->settings['base_url'] . 'resources/js/default.js',
		$config->settings['base_url'] . 'resources/js/orders.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
