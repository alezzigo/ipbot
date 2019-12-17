<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of ' . $templateParameters['order']['previous_action'] . ' for your order at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= ucwords($templateParameters['order']['previous_action']) . 'd Order Details' . "\n";
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
	$message .= 'If you didn\'t request this order ' . $templateParameters['order']['previous_action'] . ', please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
