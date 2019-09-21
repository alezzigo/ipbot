<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/users.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main process="user">
	<div class="section">
		<div class="container small">
			<h1>Manage Account</h1>
			<div class="message-container">
				<p class="message">Loading...</p>
			</div>
			<div class="invoices-container"></div>
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
