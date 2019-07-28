<footer></footer>
<?php
	if (!empty($scripts)) {
		foreach ($scripts as $script) {
			echo '<script src="' . $script . '?' . time() . '"></script>' . "\n";
		}
	}
?>
</body>
</html>
