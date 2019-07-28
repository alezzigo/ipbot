<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/controllers/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/default/header.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/includes/forms/proxies/search.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/includes/forms/proxies/replace.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/includes/forms/proxies/authenticate.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/includes/forms/proxies/group.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/includes/forms/proxies/copy.php');
?>
<main class="section">
	<div class="container small">
		<h1><?php echo $data['order']['name']; ?></h1>
		<div class="item-container item-configuration-container">
			<div class="item">
				<div class="item-configuration">
					<div class="item-controls-container controls-container scrollable">
						<div class="item-header">
							<div class="align-right">
								<span class="pagination" current_page="1" results="<?php echo $data['pagination']['results_per_page']; ?>">
									<span class="align-left hidden item-controls results">
										<span class="first-result"></span> - <span class="last-result"></span> of <span class="total-results"></span>
									</span>
									<span class="align-left button icon previous"></span>
									<span class="align-left button icon next"></span>
								</span>
							</div>
							<div class="align-left item-controls">
								<span checked="0" class="align-left checkbox icon no-margin-left" index="all-visible"></span>
								<div class="search-container align-left">
									<span class="button icon tooltip tooltip-bottom window" data-title="Advanced proxy search and filter" window="search"></span>
								</div>
								<span class="button icon tooltip tooltip-bottom window" data-title="Manage proxy groups" window="group"></span>
								<span class="button icon hidden tooltip tooltip-bottom window" data-title="Configure proxy replacement settings" item-function window="replace"></span>
								<span class="button icon hidden tooltip tooltip-bottom window" data-title="Configure authentication settings" item-function window="authenticate"></span>
								<span class="button icon hidden tooltip tooltip-bottom window" data-title="Copy selected proxies to clipboard" item-function window="copy"></span>
							</div>
							<div class="clear"></div>
							<p class="hidden item-controls no-margin-bottom"><span class="checked-container pull-left"><span class="total-checked">0</span> of <span class="total-results"></span> selected.</span> <a class="item-action hidden" href="javascript:void(0);" index="all" status="1"><span class="action">Select</span> all results</a><span class="clear"></span></p>
						</div>
					</div>
					<div class="item-body">
						<input name='order_id' type='hidden' value="<?php echo $data['order']['id']; ?>">
						<div class="item-table" previous_checked="0"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
	$scripts = array(
		'/src/resources/js/app/script.js',
		'/src/resources/js/proxies/script.js'
	);
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/views/layouts/default/footer.php');
?>
