<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/orders.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
	$windows = array(
		'authenticate',
		'copy',
		'group',
		'replace',
		'search'
	);

	foreach ($windows as $window) {
		if (file_exists($file = $config->settings['base_path'] . '/views/sections/' . $window . '.php')) {
			require_once($file);
		}
	}
?>
<main class="section orders-view">
	<div class="container small">
		<h1 class="order-name"></h1>
		<div class="item-container item-configuration-container">
			<div class="item">
				<div class="item-configuration">
					<div class="item-controls-container controls-container scrollable">
						<div class="item-header">
							<div class="align-right">
								<span class="pagination" current_page="1" results="<?php echo $data['results_per_page']; ?>">
									<span class="align-left hidden item-controls results">
										<span class="first-result"></span> - <span class="last-result"></span> of <span class="total-results"></span>
									</span>
									<span class="align-left button icon previous"></span>
									<span class="align-left button icon next"></span>
								</span>
							</div>
							<div class="align-left item-controls">
								<span checked="0" class="align-left checkbox icon no-margin-left" index="all-visible"></span>
								<div class="search-container align-left">
									<span class="button icon tooltip tooltip-bottom window-button" data-title="Advanced proxy search and filter" window="search"></span>
								</div>
								<span class="button icon tooltip tooltip-bottom window-button" data-title="Manage proxy groups" process="group" window="group"></span>
								<span class="button icon hidden tooltip tooltip-bottom window-button" data-title="Configure proxy replacement settings" item-function window="replace"></span>
								<span class="button icon hidden tooltip tooltip-bottom window-button" data-title="Configure authentication settings" item-function window="authenticate"></span>
								<span class="button icon hidden tooltip tooltip-bottom window-button" data-title="Copy selected proxies to clipboard" item-function process="copy" window="copy"></span>
							</div>
							<div class="clear"></div>
							<p class="hidden item-controls no-margin-bottom"><span class="checked-container pull-left"><span class="total-checked">0</span> of <span class="total-results"></span> selected.</span> <a class="item-action hidden" href="javascript:void(0);" index="all" status="1"><span class="action">Select</span> all results</a><span class="clear"></span></p>
						</div>
					</div>
					<div class="item-body">
						<input name='order_id' type='hidden' value="<?php echo $data['order_id']; ?>">
						<div class="item-table" previous_checked="0"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
	$scripts = array(
		$config->settings['base_url'] . 'resources/js/default.js',
		$config->settings['base_url'] . 'resources/js/proxies.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
