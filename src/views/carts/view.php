<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/carts.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main process="cart">
	<div class="section">
		<div class="container small">
			<h1>Shopping Cart</h1>
			<div class="item-configuration-container item-container">
				<div class="item">
					<div class="item-configuration">
						<div class="controls-container item-controls-container scrollable">
							<div class="item-header">
								<div class="hidden item-controls">
									<a class="button main-button checkout" href="/checkout">Checkout</a>
									<span checked="0" class="align-left checkbox no-margin-left" index="all-visible"></span>
									<span class="button icon delete hidden tooltip tooltip-bottom window-button" data-title="Delete item from cart" item-function process="delete"></span>
								</div>
								<div class="clear"></div>
								<p class="hidden item-controls no-margin-bottom">
									<span class="align-right checked-container"><span class="total-checked">0</span> of <span class="total-results"></span> selected.</span><span class="clear"></span>
									<span class="align-right cart-subtotal">Cart Subtotal: <span class="total"></span></span>
								</p>
								<div class="clear"></div>
								<div class="message-container"><p class="message no-margin-top">Loading...</p></div>
							</div>
						</div>
						<div class="item-body">
							<div class="cart-items-container items-container"></div>
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
