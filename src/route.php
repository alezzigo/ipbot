<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/config.php');

	if (
		$_SERVER['HTTP_HOST'] !== $config->settings['base_domain'] ||
		(
			strpos($config->settings['base_domain'], '.') !== false &&
			(
				empty($_SERVER['HTTPS']) ||
				strtolower($_SERVER['HTTPS']) != 'on'
			)
		)
	) {
		$config->redirect('https://' . $config->settings['base_domain'] . $_SERVER['REQUEST_URI']);
	}

	if (
		$_SERVER['REDIRECT_URL'] !== '/' &&
		substr($_SERVER['REDIRECT_URL'], -1) === '/'
	) {
		$config->redirect(substr($_SERVER['REDIRECT_URL'], 0, -1));
	}

	$pathParts = array_filter(explode('/', $_SERVER['REDIRECT_URL']));
	$routes = array(
		array(
			'file' => $config->settings['base_path'] . '/resources/css/[file]',
			'headers' => array(
				'Content-type: text/css'
			),
			'url' => '/resources/css/[file]'
		),
		array(
			'file' => $config->settings['base_path'] . '/resources/images/[type]/[file]',
			'headers' => array(
				'Content-type: image/png'
			),
			'url' => '/resources/images/[type]/[file]'
		),
		array(
			'file' => $config->settings['base_path'] . '/resources/js/[file]',
			'headers' => array(
				'Content-type: text/javascript'
			),
			'url' => '/resources/js/[file]'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/[table]/api.php',
			'url' => '/api/[table]'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/carts/checkout.php',
			'title' => 'Checkout',
			'url' => '/checkout'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/carts/confirm.php',
			'title' => 'Confirm',
			'url' => '/confirm'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/carts/view.php',
			'title' => 'Shopping Cart',
			'url' => '/cart'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/invoices/list.php',
			'title' => 'Proxy Invoices',
			'url' => '/invoices'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/invoices/view.php',
			'title' => 'Proxy Invoice [id]',
			'url' => '/invoices/[id]'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/orders/list.php',
			'title' => 'Proxy Orders',
			'url' => '/orders'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/orders/view.php',
			'title' => 'Proxy Order [id]',
			'url' => '/orders/[id]'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/about.php',
			'title' => 'About',
			'url' => '/about'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/contact.php',
			'title' => 'Contact',
			'url' => '/contact'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/faq.php',
			'title' => 'FAQs',
			'url' => '/faq'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/features.php',
			'title' => 'Feature Tour',
			'url' => '/features'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/home.php',
			'title' => 'Buy Premium Proxies',
			'url' => '/'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/privacy.php',
			'title' => 'Privacy Policy',
			'url' => '/privacy'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/refunds.php',
			'title' => 'Refund Policy',
			'url' => '/refunds'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/robots.php',
			'headers' => array(
				'Content-type: text/plain'
			),
			'title' => 'Robots',
			'url' => '/robots.txt'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/static_proxies.php',
			'title' => 'Static Proxies',
			'url' => '/static-proxies'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/pages/terms.php',
			'title' => 'Terms of Service',
			'url' => '/terms'
		),
		array(
			'file' => $config->settings['base_path'] . '/views/users/view.php',
			'title' => 'Manage Account',
			'url' => '/account'
		)
	);

	foreach ($routes as $key => $route) {
		$routes['files'][$key] = $route['file'];
		$routes['headers'][$key] = !empty($route['headers']) ? $route['headers'] : array();
		$routes['parts'][$key] = array_filter(explode('/', $route['url']));
		$routes['titles'][$key] = (!empty($route['title']) ? $route['title'] : false) . (!empty($config->settings['site_name']) ? ' | ' . $config->settings['site_name'] : false);
		$routes['urls'][$key] = $route['url'];
		unset($routes[$key]);
		unset($route);
	}

	$route = array_search($pathParts, $routes['parts']);

	if (!is_numeric($route)) {
		foreach ($routes['parts'] as $routeKey => $routePathParts) {
			if (
				count($routePathParts) !== count($pathParts) ||
				$routePathParts[0] !== $pathParts[0]
			) {
				continue;
			}

			foreach ($routePathParts as $routePathPartKey => $routePathPart) {
				if (
					substr($routePathPart, 0, 1) !== '[' &&
					substr($routePathPart, -1) !== ']'
				) {
					if ($routePathPart !== $pathParts[$routePathPartKey]) {
						break;
					}
				} else {
					if (strpos($routes['files'][$routeKey], $routePathPart) !== false) {
						$routes['files'][$routeKey] = str_replace($routePathPart, $pathParts[$routePathPartKey], $routes['files'][$routeKey]);
					}

					if (strpos($routes['titles'][$routeKey], $routePathPart) !== false) {
						$routes['titles'][$routeKey] = str_replace($routePathPart, $pathParts[$routePathPartKey], $routes['titles'][$routeKey]);
					}
				}

				if ($routePathPartKey === (count($pathParts) - 1)) {
					$route = $routeKey;
				}
			}
		}
	}

	if (
		!$route ||
		!file_exists($routes['files'][$route])
	) {
		$route = 0;
	}

	$config->parameters = array(
		'title' => $routes['titles'][$route],
		'route' => array(
			'file' => $routes['files'][$route],
			'parts' => $routes['parts'][$route],
			'url' => $routes['urls'][$route]
		)
	);
	$headers = $routes['headers'][$route];
	$routePathParts = $routes['parts'][$route];

	if (!empty($headers)) {
		foreach ($headers as $header) {
			header($header);
		}

		if (in_array('Content-type: image/png', $headers) ) {
			readfile($routes['files'][$route]);
			exit;
		}
	}

	if (!empty($routePathParts)) {
		foreach ($routePathParts as $routePathPartKey => $routePathPart) {
			if (
				substr($routePathPart, 0, 1) === '[' &&
				substr($routePathPart, -1) === ']'
			) {
				$config->parameters[trim($routePathPart, '[]')] = $pathParts[$routePathPartKey];
			}
		}
	}

	if (
		(
			!empty($config->settings['session_cookies']['enabled']) &&
			$config->settings['session_cookies']['enabled'] === true
		) &&
		!empty($config->settings['session_cookies']['lifetime'])
	) {
		session_start();
		setrawcookie('PHPSESSID', session_id(), $config->settings['session_cookies']['lifetime'], '/', $_SERVER['HTTP_HOST']);

		if (empty($_SESSION['key'])) {
			$_SESSION['key'] = md5(uniqid() . time() . $config->keys['start']);
		}
	}

	require_once($routes['files'][$route]);
?>
