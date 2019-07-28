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
<header></header>
<body>
