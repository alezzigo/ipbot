<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=506">
<link rel="shortcut icon" type="image/png" href="/resources/images/icons/favicon.png">
<title><?php echo $config->parameters['title']; ?></title>
<?php
	if (!empty($styleSheets)) {
		foreach ($styleSheets as $styleSheet) {
			echo '<link rel="stylesheet" href="' . $styleSheet . '?' . time() . '" type="text/css">' . "\n";
		}
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
		</div>
	</div>
</header>
<body>
