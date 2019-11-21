<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main>
	<div class="section">
		<div class="container small">
			<h1>Control Panel Features</h1>
			<div class="section">
				<h2>Schedule Automatic Proxy Replacements</h2>
				<p>Set custom proxy replacement frequencies for individually-selected proxies. All proxies can be refreshed up to twice per month.</p>
				<div class="feature-screenshot">
					<img alt="Proxy replacement schedule screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-replacements.png">
				</div>
				<h2>Scale Proxy Quantities Seamlessly</h2>
				<p>Easily upgrade, downgrade and merge proxy orders from the control panel without waiting for custom invoices or support requests.</p>
				<div class="feature-screenshot">
					<img alt="Proxy order upgrade screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/order-upgrade.png">
				</div>
				<h2>Manage Proxies Programatically via API</h2>
				<p>Automate existing control panel functions with a complete API for authentication, replacements and proxy retrieval.</p>
				<div class="feature-screenshot">
					<img alt="Proxy JSON API documentation screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/api-endpoint.png">
				</div>
				<h2>Authenticate Proxies With Granular Access Controls</h2>
				<p>Create unique username:password combinations and whitelisted access IPs for private authenticated proxy access.</p>
				<div class="feature-screenshot">
					<img alt="Proxy authentication form screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-authentication.png">
				</div>
				<h2>Filter Proxies With Flexible Search Functions</h2>
				<p>Save time spent sorting through large proxy lists with powerful search functions by IP address, location, ASN, proxy status, subnet and/or keyword.</p>
				<div class="feature-screenshot">
					<img alt="Advanced proxy search form screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-search.png">
				</div>
				<h2>Organize Proxies Using Custom Groups</h2>
				<p>Coordinate specific proxies with custom proxy labeling and grouping features for easy proxy list management.</p>
				<div class="feature-screenshot">
					<img alt="Proxy group management screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-groups.png">
				</div>
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
