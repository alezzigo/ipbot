<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/controllers/orders.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/layouts/default/header.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/includes/forms/search.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/includes/forms/replace.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/includes/forms/authenticate.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/includes/forms/group.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/includes/forms/copy.php');
?>
<main class="section">
	<div class="container small">
		<h1><?php echo $data['order']['name']; ?></h1>
		<div class="item-container proxy-configuration-container">
			<div class="item">
				<?php
					if (!empty($search)) {
						echo '<strong>0 proxy results</strong> found with your search criteria.';
					}
				?>
				<div class="item-body loading">
					<p class="no-margin-bottom">Loading...</p>
				</div>
				<div class="proxy-configuration hidden">
					<div class="proxy-controls-container controls-container scrollable">
						<div class="item-header">
							<div class="proxy-controls">
								<div class="align-right">
									<span class="pagination" current_page="1" results="<?php echo $data['pagination']['results_per_page']; ?>">
										<span class="align-left results">
											<span class="first-result">1</span> - <span class="last-result"></span> of <span class="total-results"></span>
										</span>
										<span class="icon button previous align-left"></span>
										<span class="icon button next align-left"></span>
									</span>
								</div>
								<div class="align-left">
									<span checked="0" class="align-left checkbox icon no-margin-left tooltip tooltip-bottom" data-title="Select all" index="all-visible"></span>
									<div class="search-container align-left">
										<span class="icon search button window tooltip tooltip-bottom" data-title="Advanced proxy search and filter" window="search"></span>
									</div>
									<span class="icon replace button window tooltip tooltip-bottom hidden" data-title="Configure proxy replacement settings" proxy-function window="replace"></span>
									<span class="icon authenticate button window tooltip tooltip-bottom hidden" data-title="Configure authentication settings" proxy-function window="authenticate"></span>
									<span class="icon group button window tooltip tooltip-bottom hidden" data-title="Create group from selected proxies" proxy-function window="group"></span>
									<span class="icon copy button window tooltip tooltip-bottom hidden" data-title="Copy selected proxies to clipboard" proxy-function window="copy"></span>
								</div>
								<div class="clear"></div>
								<p class="item-details no-margin-bottom"><span class="checked-container pull-left"><span class="total-checked">0</span> of <span class="total-results"></span> selected.</span> <a class="item-action hidden" href="javascript:void(0);" index="all" status="1"><span class="action">Select</span> all results</a><span class="clear"></span></p>
							</div>
						</div>
					</div>
					<div class="item-body">
						<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
							<?php
								$hiddenFields = array(
									'broad_search' => '',
									'match_all_search' => '',
									'exclude_search' => '',
									'granular_search' => '',
									'instant_replacement' => '0',
									'auto_replacement_interval_type' => 'month',
									'auto_replacement_interval_value' => '0',
									'leave_replacement_online_hours' => '0',
									'location_replacements' => '',
									'order_id' => $data['order']['id'],
									'preferred_subnet' => '',
									'generate_unique' => '0',
									'disable_http' => '0',
									'configuration_action' => '',
									'username' => '',
									'password' => '',
									'authorized_ips' => '',
									'group_name' => ''
								);

								foreach ($hiddenFields as $fieldName => $fieldValue) {
									echo '<input class="' . str_replace('_', '-', $fieldName) . '" name="' . $fieldName . '" type="hidden" value="' . $fieldValue . '">';
								}
							?>
							<div class="proxy-table" previous_checked="0"></div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php require_once($_SERVER['DOCUMENT_ROOT'] . '/src/php/views/layouts/default/footer.php'); ?>
