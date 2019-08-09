<footer></footer>
<?php
	if (!empty($config->keys['users'])) {
		echo '<div class="hidden keys">' . json_encode(array('users' => $config->keys['users'])) . '</div>';
	}

	if (!empty($scripts)) {
		foreach ($scripts as $script) {
			echo '<script src="' . $script . '?' . time() . '" type="text/javascript"></script>' . "\n";
		}
	}
?>
</body>
</html>
