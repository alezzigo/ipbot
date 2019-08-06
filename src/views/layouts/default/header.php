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

	$navigationItems = array(
		array(
			'class' => 'button window-button',
			'text' => 'Log In',
			'window' => 'login'
		),
		array(
			'class' => 'button window-button',
			'text' => 'Register',
			'window' => 'register'
		)
	);

	if (!empty($config->permissions[$data['table']][$data['action']]['group'])) {
		$navigationItems = array(
			array(
				'href' => $config->settings['base_url'] . '/views/orders/list.php',
				'text' => 'Dashboard'
			),
			array(
				'class' => 'button window-button',
				'text' => 'Log Out',
				'window' => 'logout'
			)
		);
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
			<?php
				if (!empty($navigationItems)) {
					echo '<nav><ul>';

					foreach ($navigationItems as $navigationItem) {
						if (!empty($navigationItem['text'])) {
							$class = !empty($navigationItem['class']) ? $navigationItem['class'] : 'button';
							$href = !empty($navigationItem['href']) ? $navigationItem['href'] : 'javascript:void(0);';
							$window = !empty($navigationItem['window']) ? $navigationItem['window'] : '';
							echo '<li><a class="' . $class . '" href="' . $href . '"' . (!empty($window) ? ' window="' . $window . '"' : '') . '>' . $navigationItem['text'] . '</a></li>';
						}
					}

					echo '</ul></nav>';
				}
			?>
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
