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
				<h2>Do you provide datacenter or residential proxies?</h2>
				<p>You can start with a small quantity of proxies to determine the proxy type for your use case.</p>
				<h2>Do you provide free trials?</h2>
				<p>No.</p>
				<h2>Do you provide refunds?</h2>
				<p>Yes, there is an 8-day <a href="<?php echo $config->settings['base_url']; ?>refunds">refund policy</a>.</p>
				<h2>Do your proxies block any specific websites?</h2>
				<p>No.</p>
				<h2>Do you provide backconnect proxies?</h2>
				<p>No, the proxies are provided with direct IP:port connections.</p>
				<h2>How often do you allow proxy IP replacements?</h2>
				<p>Once every 2 weeks.</p>
				<h2>Do you allow proxy IP location targeting?</h2>
				<p>Due to varying IPv4 geolocation results, the proxies currently aren't guaranteed to be specific to any location.</p>
				<h2>Do your proxies support SOCKS?</h2>
				<p>Only HTTP / HTTPS is supported, SOCKS are usually for bad bots.</p>
				<h2>Can you provide a discount code?</h2>
				<p>No, quality is expensive.</p>
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
