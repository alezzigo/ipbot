<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of a refund for invoice #' . $templateParameters['invoice']['id'] . ' at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Refund Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Payment Method: ' . ($templateParameters['transaction']['payment_method'] ? $templateParameters['transaction']['payment_method'] : 'N/A') . "\n";
	$message .= 'Refund Amount: ' . $this->settings['billing']['currency_symbol'] . number_format(($templateParameters['transaction']['payment_amount'] * -1), 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'];

	if (!empty($templateParameters['transaction']['amount_deducted_from_balance'])) {
		$message .= ' (' . $this->settings['billing']['currency_symbol'] . number_format(($templateParameters['transaction']['amount_deducted_from_balance'] * -1), 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . ' from this amount deducted from your account balance)';
	}

	$message .= "\n";
	$message .= "\n";
	$message .= 'Invoice Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Invoice ID: #' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'Invoice URL: ' . ($domain = 'https://' . $this->settings['base_domain']) . '/invoices/' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'Invoice Status: ' . ucwords($templateParameters['invoice']['status']) . "\n";
	$message .= 'Remaining Amount Due: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['invoice']['amount_due'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . "\n";
	$message .= 'Total Amount Paid to Invoice: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['invoice']['amount_paid'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . "\n";
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
	$message .= $this->settings['default_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
