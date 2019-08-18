<?php
	$styleSheets = array(
		$config->settings['base_url'] . 'resources/css/default.css'
	);
	require_once($config->settings['base_path'] . '/views/sections/header.php');
?>
<main class="private-proxies">
	<div class="section">
		<div class="container small">
			<h1>Private HTTP Proxies</h1>
			<div class="table">
				<table>
					<tbody>
						<tr>
							<td><strong>5 proxies</strong></td>
							<td><span class="monthly-price">$2.99 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>10 proxies</strong></td>
							<td><span class="monthly-price">$5.98 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>15 proxies</strong></td>
							<td><span class="monthly-price">$8.95 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>20 proxies</strong></td>
							<td><span class="monthly-price">$11.90 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>30 proxies</strong></td>
							<td><span class="monthly-price">$17.78 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>50 proxies</strong></td>
							<td><span class="monthly-price">$29.40 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>100 proxies</strong></td>
							<td><span class="monthly-price">$57.60 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>200 proxies</strong></td>
							<td><span class="monthly-price">$110.40 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>300 proxies</strong></td>
							<td><span class="monthly-price">$158.40 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>400 proxies</strong></td>
							<td><span class="monthly-price">$201.60 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>500 proxies</strong></td>
							<td><span class="monthly-price">$240.00 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
						<tr>
							<td><strong>1000 proxies</strong></td>
							<td><span class="monthly-price">$360.00 per month</span></td>
							<td><a class="add-to-cart button main-button" href="javascript:void(0);">Add to Cart</a></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</main>
<?php
	$scripts = array(
		$config->settings['base_url'] . 'resources/js/default.js',
		$config->settings['base_url'] . 'resources/js/users.js',
		$config->settings['base_url'] . 'resources/js/app.js'
	);
	require_once($config->settings['base_path'] . '/views/sections/footer.php');
?>
