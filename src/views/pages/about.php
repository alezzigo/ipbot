<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main>
	<div class="section">
		<div class="container small">
			<h1>About Eightomic Proxies</h1>
			<div class="section">
				<h2>What is a Proxy Server?</h2>
				<p>A proxy server is an intermediary connection with its own public IP address between your device and its connection to the internet.</p>
				<h2>Cloud Infrastructure With Static Dedicated IPs</h2>
				<p>Scale any number of your application's cloud server IPs through a static HTTP proxy with programmatic API authentication and IP whitelisting.</p>
				<h2>Network Infrastructure With Granular Control</h2>
				<p>Provide and manage private access to multiple users on your network using convenient proxy authentication features.</p>
				<h2>Internet Privacy Through Obscurity</h2>
				<p>Browse with multiple static dedicated HTTP proxies to change your IP address as often as you'd like. Decrease your IP-based footprint from websites and cookie tracking.</p>
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
