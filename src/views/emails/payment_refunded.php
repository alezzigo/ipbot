<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of a refunded payment at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Transaction Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Payment Method: ' . ($templateParameters['transaction']['payment_method'] ? $templateParameters['transaction']['payment_method'] : 'N/A') . "\n";
	$message .= 'Payment Amount: -' . $this->settings['billing']['currency_symbol'] . number_format(($templateParameters['transaction']['payment_amount'] * -1), 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . "\n";
	$message .= "\n";
	$message .= 'Refund Details' . "\n";
	$message .= '--' . "\n";

	foreach ($templateParameters['deductions'] as $deduction) {
		$message .= 'Applied -' . $this->settings['billing']['currency_symbol'] . number_format(($deduction['payment_amount'] * -1), 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . ' to ' . (!empty($deduction['invoice_id']) ? 'invoice #' . $deduction['invoice_id'] . '.' : 'account balance.') . "\n";
	}

	$message .= "\n";
	$message .= 'User Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'User Email: ' . $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t request this refund, or if you have any questions, please reply to this email.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
