<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="google-site-verification" content="<?php echo $config->settings['google_site_verification']; ?>">
<meta name="viewport" content="initial-scale=2, width=1000">
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
			'href' => $config->settings['base_url'] . 'static-proxies',
			'text' => 'Buy Proxies'
		),
		array(
			'href' => $config->settings['base_url'] . 'about',
			'text' => 'About'
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

	if (isset($config->permissions[$data['table']][$data['action']]['group'])) {
		$primaryNavigationItems = array(
			array(
				'class' => 'button frame-button hidden guest',
				'href' => $config->settings['base_url'] . '?#login',
				'text' => 'Log In'
			),
			array(
				'class' => 'button frame-button hidden guest',
				'href' => $config->settings['base_url'] . '?#register',
				'text' => 'Register'
			),
			array(
				'class' => 'button frame-button user',
				'process' => 'logout',
				'text' => 'Log Out'
			)
		);
		$secondaryNavigationItems = array(
			array(
				'href' => $config->settings['base_url'] . 'orders',
				'text' => 'Orders'
			),
			array(
				'href' => $config->settings['base_url'] . 'invoices',
				'text' => 'Invoices'
			),
			array(
				'href' => $config->settings['base_url'] . 'account',
				'text' => 'Account'
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
								$class = (!empty($navigationItem['class']) ? $navigationItem['class'] : 'button') . ($config->settings['base_url'] . $config->parameters['route']['parts'][1] === $navigationItem['href'] ? ' active' : false);
								$href = !empty($navigationItem['href']) ? $navigationItem['href'] : 'javascript:void(0);';
								$process = !empty($navigationItem['process']) ? $navigationItem['process'] : '';
								$frame = !empty($navigationItem['frame']) ? $navigationItem['frame'] : '';
								echo '<li><a class="' . $class . '" href="' . $href . '"' . (!empty($process) ? ' process="' . $process . '"' : '') . (!empty($frame) ? ' frame="' . $frame . '"' : '') . '>' . $navigationItem['text'] . '</a></li>';
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
									$class = (!empty($navigationItem['class']) ? $navigationItem['class'] : 'button') . ($config->settings['base_url'] . $config->parameters['route']['parts'][1] === $navigationItem['href'] ? ' active' : false);
									$href = !empty($navigationItem['href']) ? $navigationItem['href'] : 'javascript:void(0);';
									$process = !empty($navigationItem['process']) ? $navigationItem['process'] : '';
									$frame = !empty($navigationItem['frame']) ? $navigationItem['frame'] : '';
									echo '<li><a class="' . $class . '" href="' . $href . '"' . (!empty($process) ? ' process="' . $process . '"' : '') . (!empty($frame) ? ' frame="' . $frame . '"' : '') . '>' . $navigationItem['text'] . '</a></li>';
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
	$frames = array(
		'forgot',
		'login',
		'register',
		'reset'
	);

	foreach ($frames as $frame) {
		if (file_exists($file = $config->settings['base_path'] . '/views/sections/' . $frame . '.php')) {
			require_once($file);
		}
	}
?>
