<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main>
	<div class="section">
		<div class="container small">
			<div class="feature-container">
				<h1>Premium Proxy Services</h1>
				<p>Power your web applications through the anonymous Eightomic IP network and open-source <a href="https://github.com/parsonsbots/proxies" target="_blank">proxy control panel</a>.</p>
				<a class="button main-button" href="<?php echo $config->settings['base_url']; ?>contact#register">Get Started</a>
				<a class="button alternate-button" href="<?php echo $config->settings['base_url']; ?>features">See Features</a>
				<div class="feature-screenshot">
					<img alt="Proxy control panel screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/control-panel.png">
				</div>
			</div>
		</div>
	</div>
	<div class="section">
		<div class="container small">
			<div class="item">
				<h2>Static Proxies</h2>
				<p>Buy elite (high-anonymous) proxies with clean dedicated IPs, private authenticated access, HTTP and HTTPS support, fast speeds and unmetered data transfer.</p>
				<a href="<?php echo $config->settings['base_url']; ?>static-proxies">Buy Static Proxies</a>
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
