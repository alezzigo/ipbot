<?php
	$styleSheets = array(
		'/src/resources/css/default.css'
	);
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/default/header.php');
?>
<main class="section">
	<div class="container small">
		[Homepage contents]
		<a href="views/orders/">View Orders</a>
	</div>
</main>
<?php
	$scripts = array(
		'/src/resources/js/default.js',
		'/src/resources/js/users.js',
		'/src/resources/js/app.js'
	);
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/default/footer.php');
?>
