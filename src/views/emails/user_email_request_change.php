<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'An email address change was recently requested for your account at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Your current email address is:' . "\n";
	$message .= $templateParameters['old_email'] . "\n";
	$message .= "\n";
	$message .= 'Your new email address will be:' . "\n";
	$message .= $templateParameters['new_email'] . "\n";
	$message .= "\n";
	$message .= 'To confirm this email address change, visit:' . "\n";
	$message .= ($domain = 'https://' . $this->settings['base_domain']) . '/?' . $templateParameters['token'] . '#email' . "\n";
	$message .= "\n";
	$message .= 'The email address change link above is scheduled to expire in 5 minutes. If you didn\'t request this change, please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
