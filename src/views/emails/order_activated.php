<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of payment receipt for invoice #' . $templateParameters['invoice']['id'] . ' at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Order details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Order name: ' . $templateParameters['order']['quantity'] . ' ' . $templateParameters['order']['name'] . "\n";
	$message .= 'Order price: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['order']['price'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . "\n";
	$message .= 'Order interval: ' . $templateParameters['order']['interval_value'] . ' ' . $templateParameters['order']['interval_type'] . "\n";
	$message .= 'Order URL: ' . ($domain = 'https://' . $this->settings['base_domain']) . '/orders/' . $templateParameters['order']['id'] . "\n";
	$message .= "\n";
	$message .= 'Invoice details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Invoice URL: ' . $domain . '/invoices/' . $templateParameters['invoice']['id'] . "\n";
	$message .= "\n";
	$message .= 'User details' . "\n";
	$message .= '--' . "\n";
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
