<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/orders.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
	$windows = array(
		'downgrade',
		'upgrade'
	);

	foreach ($windows as $window) {
		if (file_exists($file = $config->settings['base_path'] . '/views/sections/' . $window . '.php')) {
			require_once($file);
		}
	}
?>
<main process="orders">
	<div class="section">
		<div class="container small">
			<h1>Orders</h1>
			<div class="item-configuration-container item-container">
				<div class="item">
					<div class="item-configuration">
						<div class="controls-container item-controls-container scrollable">
							<div class="item-header">
								<div class="hidden item-controls">
									<span checked="0" class="align-left checkbox no-margin-left" index="all-visible"></span>
									<span class="button icon upgrade hidden tooltip tooltip-bottom window-button" data-title="Request upgrade and/or merge for selected orders" item-function process="upgrade" window="upgrade"></span>
									<span class="button icon downgrade hidden tooltip tooltip-bottom window-button" data-title="Request downgrade for selected orders" item-function process="downgrade" window="downgrade"></span>
								</div>
								<div class="clear"></div>
								<p class="hidden item-controls no-margin-bottom">
									<span class="align-right checked-container"><span class="total-checked">0</span> of <span class="total-results"></span> selected.</span><span class="clear"></span>
								</p>
								<div class="clear"></div>
								<div class="message-container"><p class="message no-margin-top">Loading...</p></div>
							</div>
						</div>
						<div class="item-body">
							<div class="orders-container items-container"></div>
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
		$config->settings['base_url'] . 'resources/js/orders.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
