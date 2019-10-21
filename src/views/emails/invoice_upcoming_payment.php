<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a notice for your upcoming payment due for the amount of ' . number_format($templateParameters['invoice']['amount_due'], 2, '.', ',') . ' ' . $this->settings['billing']['currency'] . ' for invoice #' . $templateParameters['invoice']['id'] . ' (due on ' . date('M d, Y', strtotime($templateParameters['invoice']['due'])) . ").\n";

	if (!empty($templateParameters['subscriptions'])) {
		$message .= "\n";
		$message .= 'You have ' . count($templateParameters['subscriptions']) . ' active recurring subscription' . (count($templateParameters['subscriptions']) !== 1 ? 's' : '') . ' for this invoice. You\'ll receive multiple notifications if the subscription payment' . (count($templateParameters['subscriptions']) !== 1 ? 's' : '') . ' ' . (count($templateParameters['subscriptions']) !== 1 ? 'don\'t' : 'doesn\'t') . ' cover the remaining amount due.' . "\n";
	}

	$message .= "\n";
	$message .= 'Invoice Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Invoice ID: #' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'Invoice URL: ' . ($domain = 'https://' . $this->settings['base_domain']) . '/invoices/' . $templateParameters['invoice']['id'] . "\n";
	$message .= 'Invoice Status: ' . ucwords($templateParameters['invoice']['status']) . "\n";
	$message .= 'Remaining Amount Due: ' . number_format(is_numeric($templateParameters['invoice']['remainder_pending']) ? $templateParameters['invoice']['remainder_pending'] : $templateParameters['invoice']['amount_due'], 2, '.', ',') . ' ' . $this->settings['billing']['currency'] . "\n";
	$message .= 'Total Amount Paid to Invoice: ' . number_format($templateParameters['invoice']['amount_paid'], 2, '.', ',') . ' ' . $this->settings['billing']['currency'] . "\n";
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
