<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of activation for your order at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Order Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Order ID: #' . $templateParameters['order']['id'] . "\n";
	$message .= 'Order Name: ' . $templateParameters['order']['quantity'] . ' ' . $templateParameters['order']['name'] . "\n";
	$message .= 'Order Price: ' . number_format($templateParameters['order']['price'], 2, '.', ',') . ' ' . $this->settings['billing']['currency'] . "\n";
	$message .= 'Order Interval: ' . $templateParameters['order']['interval_value'] . ' ' . $templateParameters['order']['interval_type'] . "\n";
	$message .= 'Order URL: ' . ($domain = 'https://' . $this->settings['base_domain']) . '/orders/' . $templateParameters['order']['id'] . "\n";
	$message .= "\n";
	$message .= 'User Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'User Email: ' . $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t create this order, please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
