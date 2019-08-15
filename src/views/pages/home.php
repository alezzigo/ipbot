<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main class="home">
	<div class="section">
		<div class="container small">
			<div class="feature-container">
				<h1>Premium Proxy Services</h1>
				<p>Power your business and applications through the anonymous Eightomic IP network using a custom-built, <a href="https://github.com/parsonsbots/proxies" target="_blank">open-source</a> proxy control panel with granular access controls.</p>
				<a class="button main-button" href="/contact#register">Get Started</a>
				<a class="button text-button" href="/features">See Features</a>
				<div class="feature-screenshot">
					<img alt="Proxy control panel screenshot" src="/resources/images/screenshots/control-panel.png">
				</div>
			</div>
		</div>
	</div>
	<div class="section">
		<div class="container small">
			<div class="item">
				<h2>Private Proxies</h2>
				<p>Buy elite (high-anonymous) HTTP proxies with private access, unlimited threads, fast speeds, stable uptime and unmetered data transfer.</p>
				<a href="/private-proxies">Buy Private Proxies</a>
			</div>
			<div class="item no-margin-bottom">
				<h2>SOCKS 5 Proxies</h2>
				<p>Buy SOCKS 5 proxies with all outbound ports open. Private HTTP proxy access is included with all SOCKS 5 plans.</p>
				<a href="/socks-proxies">Buy SOCKS Proxies</a>
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
