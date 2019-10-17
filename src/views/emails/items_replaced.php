<?php
	$message = 'Hello,' . "\n";
	$message .= "\n";
	$message .= 'This is a confirmation of your recent replacement request at ' . $this->settings['site_name'] . '.' . "\n";
	$message .= "\n";
	$message .= 'Below is the link to your control panel so you can manage your list of ' . $templateParameters['table'] . ' and change configuration settings if needed:' . "\n";
	$message .= $templateParameters['link'] . "\n";
	$message .= "\n";
	$message .= 'If you didn\'t request these replacement ' . $templateParameters['table'] . ', please reply to this email immediately.' . "\n";
	$message .= "\n";
	$message .= 'List of old replaced ' . $templateParameters['table'] . ':' . "\n";

	foreach ($templateParameters['old_items'] as $key => $item) {
		$message .= $item['ip'] . "\n";
	}

	$message .= "\n";
	$message .= 'List of new ' . $templateParameters['table'] . ':' . "\n";

	foreach ($templateParameters['new_items'] as $key => $item) {
		$message .= $item['ip'] . "\n";
	}

	$message .= "\n";
	$message .= '--' . "\n";
	$message .= $this->settings['site_name'] . "\n";
	$message .= 'https://' . $this->settings['base_domain'] . "\n";
	$message .= $this->settings['from_email'] . "\n";
	$message .= "\n";
	$message .= date('M d, Y g:ia', time()) . ' ' . $this->settings['timezone']['display'];
?>
