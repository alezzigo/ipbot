<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation for your recently-modified subscription at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Subscription Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Subscription ID: #' . $templateParameters['subscription']['id'] . "\n";
	$message .= 'Subscription Status: ' . ucwords($templateParameters['subscription']['status']) . "\n";
	$message .= 'Recurring Payment Amount: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['subscription']['price'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . "\n";
	$message .= 'Recurring Payment Interval: ' . $templateParameters['subscription']['interval_value'] . ' ' . $templateParameters['subscription']['interval_type'] . ($templateParameters['subscription']['interval_value'] !== 1 ? 's' : '') . "\n";
	$message .= "\n";
	$message .= 'User Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'User Email: ' . $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'If you\'re not sure why this subscription modification happened or if you have any questions, please reply to this email.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= 'https://' . $this->settings['base_domain'] . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
