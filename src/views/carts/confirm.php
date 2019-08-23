<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/carts.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main class="confirm-view" process="confirm">
	<div class="section">
		<div class="container small">
			<div class="item-configuration-container item-container">
				<div class="item">
					<div class="item-configuration">
						<div class="controls-container item-controls-container scrollable">
							<div class="item-header">
								<p class="message no-margin-top">Processing, please wait...</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>
<?php
	$scripts = array(
		$config->settings['base_url'] . 'resources/js/default.js',
		$config->settings['base_url'] . 'resources/js/carts.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
