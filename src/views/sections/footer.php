<footer>
	<div class="container small">
		<div class="align-left navigation primary-navigation">
			<div class="align-left">
				<p>Product of <a href="https://eightomic.com" target="_blank">Eightomic</a>. Copyright <?php echo date('Y'); ?> <a href="https://parsonsbots.com" target="_blank">Will Parsons</a>.</p>
			</div>
		</div>
	</div>
</footer>
<?php
	$settings = array(
		'base_url' => (!empty($config->settings['base_url']) ? $config->settings['base_url'] : ''),
		'billing_currency_name' => (!empty($config->settings['billing']['currency_name']) ? $config->settings['billing']['currency_name'] : ''),
		'billing_currency_symbol' => (!empty($config->settings['billing']['currency_symbol']) ? $config->settings['billing']['currency_symbol'] : '')
	);
	echo '<div class="hidden settings">' . json_encode($settings) . '</div>';

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
<!-- Copyright (c) <?php echo date('Y'); ?> Will Parsons

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE. -->
