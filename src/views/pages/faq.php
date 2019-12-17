<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main>
	<div class="section">
		<div class="container small">
			<h1>FAQs</h1>
			<div class="section">
				<h2>What's a proxy server?</h2>
				<p>A proxy server is an intermediary connection with its own public IP address between your device and its connection to the internet.</p>
				<p>Full FAQ and documentation coming soon.</p>
			</div>
		</div>
	</div>
</main>
<?php
	$scripts = array(
		$config->settings['base_url'] . 'resources/js/default.js',
		$config->settings['base_url'] . 'resources/js/users.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
