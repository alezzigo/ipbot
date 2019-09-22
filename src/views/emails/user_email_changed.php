<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'Your email address was changed recently for your account at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Your old email address was:' . "\n";
	$message .= $templateParameters['old_email'] . "\n";
	$message .= "\n";
	$message .= 'Your new email address is:' . "\n";
	$message .= $templateParameters['new_email'] . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t request this change, please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= 'https://' . $this->settings['base_domain'] . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone'];
?>
