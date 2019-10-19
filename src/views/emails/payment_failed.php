<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of a failed payment at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Payment Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Payment Transaction ID: #' . ($templateParameters['transaction']['id'] ? $templateParameters['transaction']['id'] : 'N/A') . "\n";
	$message .= 'Payment Method: ' . ($templateParameters['transaction']['payment_method'] ? $templateParameters['transaction']['payment_method'] : 'N/A') . "\n";
	$message .= 'Payment Amount: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['transaction']['payment_amount'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . "\n";
	$message .= "\n";
	$message .= 'User Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'User Email: ' . $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'If you have any questions about this failed payment or would like to submit a new payment, please reply to this email.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
