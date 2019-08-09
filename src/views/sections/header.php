<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
	if (!empty($styleSheets)) {
		foreach ($styleSheets as $styleSheet) {
			echo '<link rel="stylesheet" href="' . $styleSheet . '?' . time() . '" type="text/css">' . "\n";
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
				'href' => $config->settings['base_url'] . 'orders',
				'text' => 'Dashboard'
			),
			array(
				'class' => 'button window-button',
				'process' => 'logout',
				'text' => 'Log Out'
			)
		);
	}
?>
</head>
<header>
	<div class="container small">
		<div class="align-left">
			<div class="logo-container">
				<a class="logo" href="<?php echo $config->settings['base_url']; ?>">Proxies</a>
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
							$process = !empty($navigationItem['process']) ? $navigationItem['process'] : '';
							$window = !empty($navigationItem['window']) ? $navigationItem['window'] : '';
							echo '<li><a class="' . $class . '" href="' . $href . '"' . (!empty($process) ? ' process="' . $process . '"' : '') . (!empty($window) ? ' window="' . $window . '"' : '') . '>' . $navigationItem['text'] . '</a></li>';
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
	$windows = array(
		'forgot',
		'login',
		'register',
		'reset'
	);

	foreach ($windows as $window) {
		if (file_exists($file = $config->settings['base_path'] . '/views/sections/' . $window . '.php')) {
			require_once($file);
		}
	}
?>
