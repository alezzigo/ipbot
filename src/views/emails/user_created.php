<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'A new account was recently created at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Your email address is:' . "\n";
	$message .= $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'You can view your dashboard at:' . "\n";
	$message .= ($domain = 'https://' . $this->settings['base_domain']) . '/orders' . "\n";
	$message .= "\n";
	$message .= 'Once logged in, you may change your password at:' . "\n";
	$message .= $domain . '/?#reset' . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t create this new account, please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['default_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' PDT';
?>
