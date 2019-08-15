<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/carts.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main class="checkout-view">
	<div class="section">
		<div class="container small">
			<h1>Checkout</h1>
			<div class="item-container item-configuration-container">
				<div class="item">
					<div class="item-configuration">
						<div class="item-controls-container controls-container scrollable">
							<div class="item-header">
								<div class="align-left item-controls">
									<!-- .. -->
								</div>
								<div class="clear"></div>
							</div>
						</div>
						<div class="item-body">
							<div class="items-container checkout-items-container"></div>
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
		//$config->settings['base_url'] . 'resources/js/carts.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
