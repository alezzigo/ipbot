<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/sections/header.php');
	$items = array(
		array(
			'quantity' => 2,
			'price' => 8.00
		),
		array(
			'quantity' => 10,
			'price' => 40.00
		),
		array(
			'quantity' => 20,
			'price' => 80.00
		),
		array(
			'quantity' => 40,
			'price' => 160.00
		),
		array(
			'quantity' => 80,
			'price' => 320.00
		),
		array(
			'quantity' => 160,
			'price' => 640.00
		),
		array(
			'quantity' => 320,
			'price' => 1280.00
		),
		array(
			'quantity' => 640,
			'price' => 2560.00
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
							<td><span class="monthly-price"><?php echo number_format($item['price'], 2, '.', '') . ' ' . $config->settings['billing']['currency']; ?> per <?php echo $product['interval_value'] > 1 ? $product['interval_value'] . ' ' . $product['interval_type'] . 's' : $product['interval_type']; ?></span></td>
							<td><a class="add-to-cart button main-button" disabled href="javascript:void(0);" interval_type="<?php echo $product['interval_type']; ?>" interval_value="<?php echo $product['interval_value']; ?>" product_id="<?php echo $product['id']; ?>" quantity="<?php echo $item['quantity']; ?>">Add to Cart</a></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="section">
				<h2>Unmetered Bandwidth</h2>
				<p>Go crazy with unlimited and unmetered data transfer without worrying about bandwidth fees or hidden overage costs.</p>
				<h2>High Thread Limits</h2>
				<p>Eightomic Proxies are configured for virtually unlimited threads and guaranteed to allow enough simultaneous connections for any use case.</p>
				<h2>Open-source Control Panel</h2>
				<p>The control panel for managing your list of proxy servers is completely custom-built. You can <a href="<?php echo $config->settings['base_url']; ?>contact" target="_blank">request new features</a> or just <a href="https://github.com/parsonsbots/proxies" target="_blank">build them yourself</a>.</p>
				<h2>Large IPv4 Reserve</h2>
				<p>Extra proxies are kept on standby and factored into the total pricing to supply urgent proxy IP refreshes when needed.</p>
				<h2>Fast Order Delivery</h2>
				<p>Proxies are activated instantly after payment confirmation is received. You can request a <a href="<?php echo $config->settings['base_url']; ?>refund">refund</a> if the automated delivery system is too slow.</p>
				<h2>Elite Anonymity</h2>
				<p>Enjoy high-anonymous proxy IPs without revealing your original source IP in HTTP request headers.</p>
				<h2>Non-sequential IP Addresses</h2>
				<p>Ensure proxy IP diversity with random IP address allocation and a range of multiple class-C subnets available.</p>
				<h2>Private Authentication</h2>
				<p>Both username:password and whitelisted IP authentication are supported for secure private proxy access.</p>
				<h2>Sustainable Proxy Hosting</h2>
				<p>Efficient tuning allows thousands of proxy IPs per dedicated server for a more sustainable global footprint.</p>
				<h2>Supporting an Open Internet</h2>
				<p>All unallocated proxy nodes are open to the public (without authentication) until they're allocated to a paying user.</p>
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
