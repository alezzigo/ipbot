<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/carts.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main class="carts-view">
	<div class="section">
		<div class="container small">
			<h1>Shopping Cart</h1>
			<div class="item-container item-configuration-container">
				<div class="item">
					<div class="item-configuration">
						<div class="item-controls-container controls-container scrollable">
							<div class="item-header">
								<div class="align-left item-controls">
									<span checked="0" class="align-left checkbox icon no-margin-left" index="all-visible"></span>
									<span class="button icon hidden tooltip tooltip-bottom window-button" data-title="Delete item from cart" item-function process="delete"></span>
								</div>
								<div class="clear"></div>
								<p class="hidden item-controls no-margin-bottom"><span class="checked-container pull-left"><span class="total-checked">0</span> of <span class="total-results"></span> selected.</span><span class="clear"></span></p>
							</div>
						</div>
						<div class="item-body">
							<div class="message-container"><p class="message">Loading...</p></div>
							<div class="items-container cart-items-container"></div>
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
