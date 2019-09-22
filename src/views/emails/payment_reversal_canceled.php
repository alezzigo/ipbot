<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of a canceled payment reversal for invoice #' . $templateParameters['invoice']['id'] . ' at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Payment Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Payment Reversal Transaction ID: #' . ($templateParameters['transaction']['id'] ? $templateParameters['transaction']['id'] : 'N/A') . "\n";
	$message .= 'Original Payment Transaction ID: #' . ($templateParameters['transaction']['parent_transaction_id'] ? $templateParameters['transaction']['parent_transaction_id'] : 'N/A') . "\n";
	$message .= 'Payment Method: ' . ($templateParameters['transaction']['payment_method'] ? $templateParameters['transaction']['payment_method'] : 'N/A') . "\n";
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
	$message .= 'If you have any questions about this canceled payment reversal, please reply to this email.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
