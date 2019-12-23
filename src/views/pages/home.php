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
				<h1>Create Your Own Cloud Firewall and Proxy IP Network</h1>
				<p>Deploy IPBot to automate your company's IPv4 and IPv6 proxy server management tasks with a free <a href="https://github.com/parsonsbots/ipbot" target="_blank">open-source</a> control panel and API.</p>
				<div class="align-left feature-buttons">
					<a class="button main-button" href="<?php echo $config->settings['base_url']; ?>orders">Try a Free Demo</a>
					<a class="button alternate-button" href="https://github.com/parsonsbots/ipbot" target="_blank">Get Source</a>
				</div>
				<div class="clear"></div>
				<div class="feature-screenshot">
					<img alt="Proxy control panel screenshot" class="no-margin-bottom" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/control-panel.png">
				</div>
				<div id="features">
				<h2>Granular Access Controls</h2>
					<p>Take full control of your network firewalls, cloud applications and web crawlers using authentication rules and grouping for individual IP addresses.</p>
					<h2>Minimal Design and Code Structure</h2>
					<p>The dashboard uses minimal design elements so you can add your own theme to match company branding.</p>
					<p>Developer implementation is a breeze using simple and secure authentication, MVC routing and API structure. The custom-built framework uses vanilla JavaScript and PHP/MySQL.</p>
					<h2>Built for Scale</h2>
					<p><a href="<?php echo $config->settings['base_url']; ?>features">Control panel functions</a> are designed specifically for managing billions of IPs across multiple internet service providers using custom data encoding and batch request processing.</p>
					<h2>Complete Recurring Billing System</h2>
					<p>Provide client-facing <a href="<?php echo $config->settings['base_url']; ?>static-proxies">proxy services</a> (in addition to your own internal proxy network) with a simple shopping cart, user management system and recurring billing automation platform.</p>
				</div>
			</div>
			<p>Full FAQ and documentation coming soon.</p>
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
