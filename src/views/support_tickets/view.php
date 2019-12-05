<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/controllers/support_tickets.php');
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main process="supportTicket">
	<div class="section">
		<div class="container small">
			<h1>Support Ticket</h1>
			<div class="item-configuration-container item-container">
				<div class="item">
					<div class="item-configuration">
						<div class="controls-container item-controls-container scrollable">
							<div class="item-header">
								<div class="hidden item-controls">
									<a class="alternate-button button" href="<?php echo $config->settings['base_url']; ?>support">Back to Support Tickets</a>
								</div>
								<div class="clear"></div>
								<div class="message-container"><p class="message">Loading...</p></div>
							</div>
						</div>
						<div class="item-body">
							<input name="support_ticket_id" type="hidden" value="<?php echo $data['support_ticket_id']; ?>">
							<div class="support-ticket-container" previous_checked="0"></div>
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
