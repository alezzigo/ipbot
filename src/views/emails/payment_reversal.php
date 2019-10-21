<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of a pending payment reversal at ' . $this->settings['site_name'] . ' due to a recent ' . ucwords(str_replace('_', ' ', $templateParameters['transaction']['payment_status_code'])) . "\n";
	$message .= "\n";
	$message .= 'Payment Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'Payment Reversal Transaction ID: #' . ($templateParameters['transaction']['payment_transaction_id'] ? $templateParameters['transaction']['payment_transaction_id'] : 'N/A') . "\n";
	$message .= 'Original Payment Transaction ID: #' . ($templateParameters['transaction']['parent_transaction_id'] ? $templateParameters['transaction']['parent_transaction_id'] : 'N/A') . "\n";
	$message .= 'Payment Method: ' . ($templateParameters['transaction']['payment_method'] ? $templateParameters['transaction']['payment_method'] : 'N/A') . "\n";
	$message .= "\n";
	$message .= 'User Details' . "\n";
	$message .= '--' . "\n";
	$message .= 'User Email: ' . $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'This reversal won\'t affect your account balance or any active orders until either a refund is processed or the reversal is cancelled.' . "\n";
	$message .= "\n";
	$message .= 'If you\'d like to cancel this reversal or if you have any questions, please reply to this email.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
