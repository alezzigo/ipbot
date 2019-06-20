<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/controllers/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/layouts/default/header.php');
?>
<main class="section">
	<div class="container small">
		<h1>Orders</h1>
		<?php
			if (empty($data['orders'])) :
				echo 'There are no orders in your account.';
			else:
			foreach ($data['orders'] as $order):
				$data['orders_encoded'][$order['id']] = array(
					'id' => $order['id'],
					'quantity' => $order['quantity'],
					'type' => $order['type'],
					'price' => $order['price'],
					'interval_type' => $order['interval_type'],
					'interval_value' => $order['interval_value'],
					'status' => $order['status']
				);
		?><div class="item-container item-button">
			<div class="item">
				<div class="item-body">
					<p><strong><?php echo $order['name']; ?></strong></p>
					<p><?php echo '$' . $order['price'] . ' per ' . ($order['interval_value'] > 1 ? $order['interval_value'] . ' ' . $order['interval_type'] . 's' : $order['interval_type']); ?></p>
				</div>
			</div>
			<div class="item-link-container">
				<a class="item-link" href="view.php?id=<?php echo $order['id']; ?>"></a>
			</div>
		</div>
		<?php
			endforeach;
			endif;
		?></div>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/layouts/default/footer.php'); ?>
