<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a notice for your past-due payment for the amount of ' . $this->settings['billing']['currency_symbol'] . number_format($templateParameters['invoice']['amount_due'], 2, '.', ',') . ' ' . $this->settings['billing']['currency_name'] . ' for invoice #' . $templateParameters['invoice']['id'] . ' (due on ' . date('M d, Y', strtotime($templateParameters['invoice']['due'])) . ").\n";

	if (!empty($templateParameters['subscriptions'])) {
		$message .= "\n";
		$message .= 'You have ' . count($templateParameters['subscriptions']) . ' active recurring subscription' . (count($templateParameters['subscriptions']) !== 1 ? 's' : '') . ' for this invoice.' . "\n";
	}

	$message .= "\n";
	$message .= 'There is a grace period of about 6 days from ' . date('M d, Y', strtotime($templateParameters['invoice']['due'])) . ' before all orders attached to this invoice are deactivated.' . "\n";
	$message .= "\n";
	$message .= 'Please log in and pay this past-due invoice to prevent order deactivation at ' . ($domain = 'https://' . $this->settings['base_domain']) . '/invoices/' . $templateParameters['invoice']['id'] . '.' . "\n";
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
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
