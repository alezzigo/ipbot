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
			<div class="no-margin-bottom feature-container section">
				<h2>Schedule Automatic Proxy Replacements</h2>
				<p>Set custom proxy replacement frequencies for individually-selected proxies. All proxies can be refreshed up to twice per month.</p>
				<div class="feature-screenshot">
					<img alt="Proxy replacement schedule screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-replacements.png">
				</div>
				<h2>Scale Proxy Quantities Dynamically</h2>
				<p>Easily upgrade, downgrade and merge proxy orders from the control panel without waiting for custom invoices or support requests.</p>
				<div class="feature-screenshot">
					<img alt="Proxy order upgrade screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/order-upgrade.png">
				</div>
				<h2>Maintain Proxy Settings Programatically via API</h2>
				<p>Automate existing control panel functions with a complete API for authentication, replacements and proxy retrieval.</p>
				<div class="feature-screenshot">
					<img alt="Proxy JSON API documentation screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/api-endpoint.png">
				</div>
				<h2>Authenticate Proxies With Granular Access Controls</h2>
				<p>Create unique username:password combinations and whitelisted access IPs for private authenticated proxy access.</p>
				<div class="feature-screenshot">
					<img alt="Proxy authentication form screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-authentication.png">
				</div>
				<h2>Track Payments and Invoices Efficiently</h2>
				<p>Effortlessly manage your account invoices and recurring payments using a custom open-source billing system.</p>
				<div class="feature-screenshot">
					<img alt="Order invoice screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/order-invoice.png">
				</div>
				<h2>Filter Proxies With Flexible Search Functions</h2>
				<p>Save time spent sorting through large proxy lists with powerful search functions by IP address, location, ASN, proxy status, subnet and/or keyword.</p>
				<div class="feature-screenshot">
					<img alt="Advanced proxy search form screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-search.png">
				</div>
				<h2>Format List of Proxies Easily</h2>
				<p>Copy your proxies and authentication info to the clipboard in multiple formats to accomodate your application.</p>
				<div class="feature-screenshot">
					<img alt="Proxy list formatting screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-list-formatting.png">
				</div>
				<h2>Organize Proxies Using Custom Groups</h2>
				<p>Coordinate specific proxies with custom proxy labeling and grouping features for easy proxy list management.</p>
				<div class="feature-screenshot">
					<img alt="Proxy group management screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-groups.png">
				</div>
				<h2>Manage Millions of IPs</h2>
				<p>Allocate, authenticate, change and group proxy IPs in bulk with background request processing.</p>
				<div class="feature-screenshot">
					<img alt="Bulk request processing screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/order-bulk-action.png">
				</div>
				<h2>Configure Gateway IP Rotation Settings</h2>
				<p>Convert any static proxy IP in your control panel into a rotating proxy gateway with custom rotation intervals. IPs can be used as direct static proxies and gateway exit proxies simultaneously.</p>
				<div class="feature-screenshot">
					<img alt="Proxy rotation settings screenshot" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/proxy-rotation.png">
				</div>
				<h2>Download HTTP Request Logs</h2>
				<p>Coming soon: Select individual proxy IPs and download a comprehensive log of all HTTP / HTTPS proxy requests within a specified date range.</p>
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
