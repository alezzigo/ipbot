<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of downgrade for your order at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Order Downgrade Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Order ID: #' . $templateParameters['order']['id'] . "\n";
	$message .= 'Order Name: ' . $templateParameters['order']['quantity'] . ' ' . $templateParameters['order']['name'] . ' downgraded to ' . $templateParameters['order']['quantity_pending'] . ' ' . $templateParameters['order']['name'] . "\n";
	$message .= 'Order Price: ' . number_format($templateParameters['order']['price'], 2, '.', ',') . ' ' . $this->settings['billing']['currency'] . ($templateParameters['order']['price_pending'] > $templateParameters['order']['price'] ? ' increased to ' . number_format($templateParameters['order']['price_pending'], 2, '.', ',') : '') . ' ' . $this->settings['billing']['currency'] . "\n";
	$message .= 'Order Interval: ' . $templateParameters['order']['interval_value'] . ' ' . $templateParameters['order']['interval_type'] . "\n";
	$message .= 'Order URL: ' . ($domain = 'https://' . $this->settings['base_domain']) . '/orders/' . $templateParameters['order']['id'] . "\n";
	$message .= "\n";
	$message .= 'Invoice Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Invoice ID: #' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'Invoice URL: ' . $domain . '/invoices/' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'Invoice Status: ' . ucwords($templateParameters['invoice']['status']) . "\n";
	$message .= "\n";
	$message .= 'User Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'User Email: ' . $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t request this order downgrade, please reply to this email immediately. The list of proxies to be removed below will remain active for 2 hours before removal.' . "\n";
	$message .= "\n";
	$message .= 'List of proxies to be removed ' . $templateParameters['table'] . ':' . "\n";

	foreach ($templateParameters['items_to_remove'] as $key => $item) {
		$message .= $item['ip'] . "\n";
	}

	$message .= "\n";
	$message .= 'List of proxies to keep ' . $templateParameters['table'] . ':' . "\n";

	foreach ($templateParameters['items_to_keep'] as $key => $item) {
		$message .= $item['ip'] . "\n";
	}

	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
