<footer>
	<div class="container small">
		<div class="align-left navigation primary-navigation">
			<div class="align-left">
				<p>Product of <a href="https://eightomic.com" target="_blank">Eightomic</a>. Copyright <?php echo date('Y'); ?> <a href="https://parsonsbots.com" target="_blank">Will Parsons</a>. All rights reserved.</p>
			</div>
		</div>
	</div>
</footer>
<?php
	if (!empty($config->settings['base_url'])) {
		echo '<div class="hidden base-url">' . $config->settings['base_url'] . '</div>';
	}

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
