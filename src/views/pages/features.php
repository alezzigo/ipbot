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
			<h2>Scale Proxy Quantities Seamlessly</h2>
			<p>Easily upgrade or downgrade proxies from the control panel without waiting for custom invoices or change orders.</p>
			<h2>Schedule Automatic Proxy Replacements</h2>
			<p>Set custom proxy replacement frequencies for individual proxies. All proxies can be refreshed up to twice per month.</p>
			<h2>Retrieve Proxies Programatically Using a Robust Proxy API</h2>
			<p>Automate existing proxy management functions with a complete API for authentication, replacements and proxy retrieval.</p>
			<h2>Authenticate Proxies With Granular Access Controls</h2>
			<p>Create unique username:password combinations and whitelisted access IPs for private authenticated proxy access.</p>
			<h2>Filter Proxies With Flexible Search Functions</h2>
			<p>Sort through large proxy lists with powerful search functions by IP address, subnet and/or keyword.</p>
			<h2>Manage Proxies Using Custom Groups</h2>
			<p>Save time organizing groups of proxies with custom labeling and grouping for individually-selected proxies.</p>
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
