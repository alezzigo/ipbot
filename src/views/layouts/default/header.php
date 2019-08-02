<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
	if (!empty($styleSheets)) {
		foreach ($styleSheets as $styleSheet) {
			echo '<link rel="stylesheet" href="' . $styleSheet . '?' . time() . '">' . "\n";
		}
	}
?>
</head>
<header>
	<div class="container small">
		<div class="align-left">
			<div class="logo-container">
				<a class="logo" href="/src">Proxies</a>
			</div>
		</div>
		<div class="align-right">
			<nav>
				<ul>
					<li><a class="button window" href="javascript:void(0);" window="login">Log In</a></li>
					<li><a class="button window" href="javascript:void(0);" window="register">Register</a></li>
				</ul>
			</nav>
		</div>
	</div>
</header>
<body>
<?php
	if (!empty($forms = array_diff(scandir($config->settings['base_path'] . '/views/includes/forms/users/'), array('.', '..', '.DS_Store')))) {
		foreach ($forms as $form) {
			require_once($config->settings['base_path'] . '/views/includes/forms/users/' . $form);
		}
	}
?>
