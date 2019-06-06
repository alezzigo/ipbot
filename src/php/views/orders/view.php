<?php
	require_once('../../controllers/orders.php');
	include('../layouts/default/header.php');
	$data['proxies_encoded'] = array();
	require_once('../../views/includes/forms/search.php');
	require_once('../../views/includes/forms/replacement.php');
	require_once('../../views/includes/forms/authentication.php');
	require_once('../../views/includes/forms/group.php');
	require_once('../../views/includes/forms/clipboard.php');
?>
<div class="section">
	<div class="container small">
		<h1><?php echo $data['order']['name']; ?></h1>
		<div class="item-container">
			<div class="item">
				<div class="item-body">
				</div>
			</div>
		</div>
	</div>
</div>
<div class="proxies-encoded hidden"><?php echo json_encode($data['proxies_encoded']); ?></div>
<?php include('../layouts/default/footer.php'); ?>
