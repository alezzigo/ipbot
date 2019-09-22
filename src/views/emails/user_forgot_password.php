<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'A password reset was recently requested for your account.' . "\n";
	$message .= "\n";
	$message .= 'Your email address is:' . "\n";
	$message .= $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'To reset your password, visit:' . "\n";
	$message .= ($domain = 'https://' . $this->settings['base_domain']) . '/?' . $templateParameters['token'] . '#reset' . "\n";
	$message .= "\n";
	$message .= 'The password reset link above is scheduled to expire in 5 minutes. If you didn\'t request this password reset, please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
