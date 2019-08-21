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
			<div class="item-configuration-container item-container">
				<div class="item">
					<div class="item-configuration">
						<div class="controls-container item-controls-container scrollable">
							<div class="item-header">
								<div class="item-controls">
									<a class="button confirm main-button" disabled href="<?php echo $config->settings['base_url']; ?>confirm">Proceed to Payment</a>
									<a class="alternate-button button cart" href="<?php echo $config->settings['base_url']; ?>cart">Return to Cart</a>
								</div>
								<div class="clear"></div>
								<p class="item-controls no-margin-bottom">
									<span class="align-right cart-total">Total: <span class="total"></span></span>
								</p>
								<div class="clear"></div>
								<div class="message-container"><p class="message">Loading...</p></div>
							</div>
						</div>
						<div class="item-body">
							<div class="checkout-items-container items-container"></div>
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
