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
				<h2>Cloud Infrastructure With Static Dedicated IPs</h2>
				<p>Scale any number of your application's dynamic cloud server IPs through the Eightomic proxy network using <a href="<?php echo $config->settings['base_url']; ?>static-proxies">static HTTP proxy IPs</a>.</p>
				<h2>Network Infrastructure With Granular Control</h2>
				<p>Provide and manage convenient access for multiple private users on your network using programmatic API authentication and IP whitelisting.</p>
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
