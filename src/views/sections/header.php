<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="image/png" href="/resources/images/icons/favicon.png">
<title><?php echo $config->parameters['title']; ?></title>
<?php
	if (!empty($styleSheets)) {
		foreach ($styleSheets as $styleSheet) {
			echo '<link rel="stylesheet" href="' . $styleSheet . '?' . time() . '" type="text/css">' . "\n";
		}
	}

	$primaryNavigationItems = array(
		array(
			'href' => $config->settings['base_url'] . 'orders',
			'text' => 'Dashboard'
		),
		array(
			'href' => $config->settings['base_url'] . 'cart',
			'text' => 'Cart'
		)
	);
	$secondaryNavigationItems = array(
		array(
			'href' => $config->settings['base_url'],
			'text' => 'Home'
		),
		array(
			'href' => $config->settings['base_url'] . 'private-proxies',
			'text' => 'Buy Proxies'
		),
		array(
			'href' => $config->settings['base_url'] . 'features',
			'text' => 'Features'
		),
		array(
			'href' => $config->settings['base_url'] . 'faq',
			'text' => 'FAQ'
		),
		array(
			'href' => $config->settings['base_url'] . 'contact',
			'text' => 'Contact'
		)
	);

	if (!empty($config->permissions[$data['table']][$data['action']]['group'])) {
		$primaryNavigationItems = array(
			array(
				'class' => 'button window-button',
				'process' => 'logout',
				'text' => 'Log Out'
			)
		);
		$secondaryNavigationItems = array(
			array(
				'href' => $config->settings['base_url'] . 'orders',
				'text' => 'Orders'
			)
		);
	}
?>
</head>
<header>
	<div class="container small">
		<div class="align-left navigation primary-navigation">
			<div class="align-left">
				<div class="logo-container">
					<a class="logo" href="<?php echo $config->settings['base_url']; ?>"><?php echo $config->settings['site_name']; ?></a>
				</div>
			</div>
			<div class="align-right">
				<?php
					if (!empty($primaryNavigationItems)) {
						echo '<nav><ul>';

						foreach ($primaryNavigationItems as $navigationItem) {
							if (!empty($navigationItem['text'])) {
								$class = (!empty($navigationItem['class']) ? $navigationItem['class'] : 'button') . ($config->parameters['route']['url'] === $navigationItem['href'] ? ' active' : false);
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
		<?php if (!empty($secondaryNavigationItems)): ?>
		<div class="align-left navigation secondary-navigation">
			<div class="align-left">
				<nav>
					<ul>
						<?php
							foreach ($secondaryNavigationItems as $navigationItem) {
								if (!empty($navigationItem['text'])) {
									$class = (!empty($navigationItem['class']) ? $navigationItem['class'] : 'button') . ($config->parameters['route']['url'] === $navigationItem['href'] ? ' active' : false);
									$href = !empty($navigationItem['href']) ? $navigationItem['href'] : 'javascript:void(0);';
									$process = !empty($navigationItem['process']) ? $navigationItem['process'] : '';
									$window = !empty($navigationItem['window']) ? $navigationItem['window'] : '';
									echo '<li><a class="' . $class . '" href="' . $href . '"' . (!empty($process) ? ' process="' . $process . '"' : '') . (!empty($window) ? ' window="' . $window . '"' : '') . '>' . $navigationItem['text'] . '</a></li>';
								}
							}
						?>
					</ul>
				</nav>
			</div>
		</div>
		<?php endif; ?>
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
