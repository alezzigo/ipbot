<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/sections/header.php');
	$items = array(
		array(
			'quantity' => 10,
			'price' => 18.80
		),
		array(
			'quantity' => 30,
			'price' => 56.40
		),
		array(
			'quantity' => 50,
			'price' => 94.00
		),
		array(
			'quantity' => 100,
			'price' => 188.00
		),
		array(
			'quantity' => 300,
			'price' => 564.00
		),
		array(
			'quantity' => 500,
			'price' => 940.00
		),
		array(
			'quantity' => 1000,
			'price' => 1880.00
		)
	);
	$product = array(
		'id' => 1,
		'interval_type' => 'month',
		'interval_value' => 1,
		'name' => 'Proxies'
	);
?>
<main process="cart">
	<div class="section">
		<div class="container small">
			<h1>Buy Static Proxies</h1>
			<p>Performance-oriented HTTP / HTTPS proxies with static IPv4 addresses and powerful <a href="<?php echo $config->settings['base_url']; ?>features">control panel features</a>.</p>
			<div class="message-container"></div>
			<div class="section table">
				<table>
					<tbody>
						<?php foreach ($items as $item): ?>
						<tr>
							<td><strong><?php echo $item['quantity'] . ' ' . $product['name']; ?></strong></td>
							<td><span class="monthly-price">$<?php echo number_format($item['price'], 2, '.', ''); ?> per <?php echo $product['interval_value'] > 1 ? $product['interval_value'] . ' ' . $product['interval_type'] . 's' : $product['interval_type']; ?></span></td>
							<td><a class="add-to-cart button main-button" disabled href="javascript:void(0);" interval_type="<?php echo $product['interval_type']; ?>" interval_value="<?php echo $product['interval_value']; ?>" product_id="<?php echo $product['id']; ?>" quantity="<?php echo $item['quantity']; ?>">Add to Cart</a></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="section">
				<h2>Unmetered bandwidth</h2>
				<p>Go crazy with unlimited and unmetered data transfer without worrying about bandwidth fees or hidden overage costs.</p>
				<h2>High thread limits</h2>
				<p>Eightomic Proxies are configured for virtually unlimited threads and guaranteed to allow enough simultaneous connections for any use case.</p>
				<h2>Open source control panel</h2>
				<p>The control panel for managing your list of proxy servers is completely custom-built. You can <a href="<?php echo $config->settings['base_url']; ?>contact" target="_blank">request new features</a> or just <a href="https://github.com/parsonsbots/proxies" target="_blank">build them yourself</a>.</p>
				<h2>Elite anonymity</h2>
				<p>Enjoy high-anonymous proxy IPs without revealing your original source IP in HTTP request headers.</p>
				<h2>Private authentication</h2>
				<p>Both username:password and whitelisted IP authentication are supported for secure private proxy access.</p>
			</div>
		</div>
	</div>
</main>
<?php
	$scripts = array(
		$config->settings['base_url'] . 'resources/js/default.js',
		$config->settings['base_url'] . 'resources/js/carts.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
