<footer>
	<div class="container small">
		<div class="align-left navigation primary-navigation">
			<div class="align-left">
				<p>Copyright <?php echo date('Y'); ?> <a href="https://eightomic.com" target="_blank">Eightomic</a>. All rights reserved.</p>
			</div>
		</div>
	</div>
</footer>
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
