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
			<h1>Premium Static Proxies</h1>
			<div class="message-container"></div>
			<div class="table">
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
