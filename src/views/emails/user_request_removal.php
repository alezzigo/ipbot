<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'You\'ve recently requested a user account removal at ' . $this->settings['site_name'] . ' and your account will be removed shortly.' . "\n";
	$message .= "\n";
	$message .= 'Your user email address is:' . "\n";
	$message .= $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'Orders, invoices, subscriptions, account balance and other user data will be removed as well.' . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t request this account removal, please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= 'https://' . $this->settings['base_domain'] . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
