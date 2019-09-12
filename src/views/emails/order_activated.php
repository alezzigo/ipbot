<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'Order #' . $templateParameters['order']['id'] . ' is now activated at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'You can log in and manage your order at:' . "\n";
	$message .= $templateParameters['link'] . "\n";
	$message .= "\n";
	$message .= 'Order name: ' . $templateParameters['order']['quantity'] . ' ' . $templateParameters['order']['name'] . "\n";
	$message .= 'Order price: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['order']['price'], 2, '.', '') . ' ' . $this->settings['billing']['currency_name'] . "\n";
	$message .= 'Order interval: ' . $templateParameters['order']['interval_value'] . ' ' . $templateParameters['order']['interval_type'] . "\n";
	$message .= 'Order URL: ' . ($domain = 'https://' . $this->settings['base_domain']) . '/orders/' . $templateParameters['order']['id'] . "\n";
	$message .= 'Invoice URL: ' . $domain . '/invoices/' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'User email: ' . $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t create this order, please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['default_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
