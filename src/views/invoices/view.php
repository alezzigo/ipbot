<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/invoices.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main process="invoice">
	<div class="section">
		<div class="container small">
			<h1 class="invoice-name"></h1>
			<div class="item-container item-configuration-container">
				<div class="item">
					<div class="item-configuration">
						<div class="controls-container item-controls-container scrollable">
							<div class="item-header">
								<div class="hidden item-controls">
									<a class="button main-button" disabled href="javascript:void(0);">Pay Invoice</a>
									<a class="alternate-button button" href="<?php echo $config->settings['base_url']; ?>cart">Return to Invoices</a>
								</div>
								<div class="clear"></div>
								<p class="hidden item-controls no-margin-bottom">
									<span class="align-right cart-total">Total: <span class="total"></span></span>
								</p>
								<div class="clear"></div>
								<div class="message-container"><p class="message">Loading...</p></div>
							</div>
						</div>
						<div class="item-body">
							<input name='invoice_id' type='hidden' value="<?php echo $data['invoice_id']; ?>">
							<div class="invoice-container"></div>
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
		$config->settings['base_url'] . 'resources/js/invoices.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
