<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/invoices.php');
	require_once($config->settings['base_path'] . '/views/sections/invoice_header.php');
	$frames = array(
		'payment'
	);

	foreach ($frames as $frame) {
		if (file_exists($file = $config->settings['base_path'] . '/views/sections/' . $frame . '.php')) {
			require_once($file);
		}
	}
?>
<main process="invoice">
	<div class="no-margin-top section">
		<div class="container small">
			<h1 class="invoice-name"></h1>
			<div class="item-container item-configuration-container">
				<div class="item">
					<div class="item-configuration">
						<div class="controls-container item-controls-container scrollable">
							<div class="item-header">
								<div class="hidden item-controls">
									<a class="button frame-button main-button" frame="payment" href="javascript:void(0);">Pay Invoice</a>
									<a class="alternate-button button" href="<?php echo $config->settings['base_url']; ?>invoices">Back to Invoices</a>
								</div>
								<div class="clear"></div>
								<div class="message-container"><p class="message">Loading...</p></div>
							</div>
						</div>
						<div class="item-body">
							<input name="invoice_id" type="hidden" value="<?php echo $data['invoice_id']; ?>">
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
	require_once($config->settings['base_path'] . '/views/sections/invoice_footer.php');
?>
