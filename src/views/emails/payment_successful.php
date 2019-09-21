<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of payment receipt for invoice #' . $templateParameters['invoice']['id'] . ' at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Payment Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Payment Method: ' . ($templateParameters['transaction']['payment_method'] ? $templateParameters['transaction']['payment_method'] : 'N/A') . "\n";
	$message .= 'Payment Amount: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['transaction']['payment_amount'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'];

	if (!empty($templateParameters['transaction']['amount_applied_to_balance'])) {
		$message .= ' (' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['transaction']['amount_applied_to_balance'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . ' applied to your account balance)';
	}

	$message .= "\n";

	if (
		!empty($templateParameters['transaction']['customer_first_name']) &&
		!empty($templateParameters['transaction']['customer_last_name'])
	) {
		$message .= 'Customer Name: ' . $templateParameters['transaction']['customer_first_name'] . ' ' . $templateParameters['transaction']['customer_last_name'] . "\n";
	}

	$message .= 'Billing Email: ' . ($templateParameters['transaction']['customer_email'] ? $templateParameters['transaction']['customer_email'] : 'N/A') . "\n";
	$message .= 'Billing Name: ' . ($templateParameters['transaction']['billing_name'] ? $templateParameters['transaction']['billing_name'] : 'N/A') . "\n";
	$message .= 'Billing Address: ' . ($templateParameters['transaction']['billing_address_1'] ? $templateParameters['transaction']['billing_address_1'] : 'N/A') . "\n";
	$message .= 'Billing City: ' . ($templateParameters['transaction']['billing_city'] ? $templateParameters['transaction']['billing_city'] : 'N/A') . "\n";
	$message .= 'Billing State: ' . ($templateParameters['transaction']['billing_region'] ? $templateParameters['transaction']['billing_region'] : 'N/A') . "\n";
	$message .= 'Billing Zip: ' . ($templateParameters['transaction']['billing_zip'] ? $templateParameters['transaction']['billing_zip'] : 'N/A') . "\n";
	$message .= 'Billing Country: ' . ($templateParameters['transaction']['billing_country_code'] ? $templateParameters['transaction']['billing_country_code'] : 'N/A') . "\n";
	$message .= "\n";
	$message .= 'Invoice Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Invoice ID: #' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'Invoice URL: ' . ($domain = 'https://' . $this->settings['base_domain']) . '/invoices/' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'Invoice Status: ' . ucwords($templateParameters['invoice']['status']) . "\n";
	$message .= 'Remaining Amount Due: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['invoice']['amount_due'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . "\n";
	$message .= 'Total Amount Paid to Invoice: ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['invoice']['amount_paid'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'];

	if (!empty($templateParameters['invoice']['amount_applied_to_balance'])) {
		$message .= ' (' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['invoice']['amount_applied_to_balance'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . ' applied to account balance)';
	}

	$message .= "\n";
	$message .= "\n";
	$message .= 'User Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'User Email: ' . $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'We may occasionally review payments manually based on your order, billing and user details to avoid fraudulent orders. If you didn\'t submit payment for this invoice, or if you have any questions, please reply to this email.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['default_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
