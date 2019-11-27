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
				<p>Power your web crawlers and applications through the premium <a href="https://eightomic.com" target="_blank">Eightomic</a> proxy network and <a href="https://github.com/parsonsbots/proxies" target="_blank">open-source</a> proxy <a href="<?php echo $config->settings['base_url']; ?>features">control panel</a>.</p>
				<div class="align-left feature-buttons">
					<a class="button main-button" href="<?php echo $config->settings['base_url']; ?>static-proxies">Get Started</a>
					<a class="button alternate-button" href="<?php echo $config->settings['base_url']; ?>about">What's a Proxy?</a>
				</div>
				<div class="clear"></div>
				<div class="feature-screenshot">
					<img alt="Proxy control panel screenshot" class="no-margin-bottom" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/control-panel.png">
				</div>
			</div>
			<h2 class="no-margin-top">Static Proxy IPs</h2>
			<p>Buy elite (high-anonymous) proxies with high-end dedicated proxy IPs, private authenticated access, HTTP and HTTPS support, <a href="https://github.com/parsonsbots/dynamic-proxy-node-reconfiguration/blob/master/dynamic_reconfiguration.php" target="_blank">fast speeds</a> and unmetered data transfer.</p>
			<a href="<?php echo $config->settings['base_url']; ?>static-proxies">Buy Static Proxies</a>
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
