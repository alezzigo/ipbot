<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/support_tickets.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main process="supportTickets">
	<div class="section">
		<div class="container small">
			<h1>Support Tickets</h1>
			<div class="item-container item-configuration-container">
				<div class="item">
					<div class="item-configuration">
						<div class="controls-container item-controls-container scrollable">
							<div class="item-header">
								<div class="item-controls">
									<a class="align-right button main-button" frame="add" href="javascript:void(0);">Create New Ticket</a>
								</div>
								<div class="clear"></div>
								<div class="message-container"><p class="message">Loading...</p></div>
							</div>
						</div>
						<div class="item-body">
							<div class="support-tickets-container"></div>
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
		$config->settings['base_url'] . 'resources/js/support_tickets.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
