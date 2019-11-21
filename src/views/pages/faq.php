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
				<h2>Do your proxies support SOCKS?</h2>
				<p>Only HTTP / HTTPS is supported.</p>
				<h2>Do you have a free trial?</h2>
				<p>No, but there is an 8-day <a href="<?php echo $config->settings['base_url']; ?>refunds">refund policy</a>.</p>
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
