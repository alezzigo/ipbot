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
				<h1>Start Your Own Proxy Network</h1>
				<p>Deploy IPBot to automate your company's IPv4 and IPv6 proxy server management tasks with a free <a href="https://github.com/parsonsbots/proxies" target="_blank">open-source</a> control panel and API.</p>
				<div class="align-left feature-buttons">
					<a class="button main-button" href="<?php echo $config->settings['base_url']; ?>contact">Try a Free Demo</a>
					<a class="button alternate-button" href="<?php echo $config->settings['base_url']; ?>contact">Learn More</a>
				</div>
				<div class="clear"></div>
				<div class="feature-screenshot">
					<img alt="Proxy control panel screenshot" class="no-margin-bottom" src="<?php echo $config->settings['base_url']; ?>resources/images/screenshots/control-panel.png">
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
