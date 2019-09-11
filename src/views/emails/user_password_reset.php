<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'Your password was recently reset for your account at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Your email address is:' . "\n";
	$message .= $templateParameters['user']['email'] . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t request this password reset, please reply to this email immediately or reset your password at:' . "\n";
	$message .= ($domain = 'https://' . $this->settings['base_domain']) . '/?#forgot' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= $domain . "\n";
	$message .= $this->settings['default_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
