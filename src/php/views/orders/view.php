<?php
	require_once('../../controllers/orders.php');
	include('../layouts/default/header.php');
	require_once('../../views/includes/forms/search.php');
	require_once('../../views/includes/forms/replace.php');
	require_once('../../views/includes/forms/authenticate.php');
	require_once('../../views/includes/forms/group.php');
	require_once('../../views/includes/forms/copy.php');
?>
<main class="section">
	<div class="container small">
		<h1><?php echo $data['order']['name']; ?></h1>
		<?php
			if (empty($data['proxies'])) :
				echo 'There are no proxies in this order.';
			else:
		?>
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
					<div class="item-header proxy-controls-container controls-container scrollable">
						<div class="proxy-controls">
							<div class="align-right">
								<span class="pagination" current="1" results="<?php echo $data['results_per_page']; ?>">
									<span class="align-left results">
										<span class="first-result">1</span> - <span class="last-result"><?php echo (count($data['proxies']) >= $data['results_per_page'] ? $data['results_per_page'] : count($data['proxies'])); ?></span> of <?php echo count($data['proxies']); ?>
									</span>
									<span title="Previous <?php echo $data['results_per_page']; ?> proxies" class="icon button previous align-left tooltip tooltip-bottom"></span>
									<span title="Next <?php echo $data['results_per_page']; ?> proxies" class="icon button next align-left tooltip tooltip-bottom"></span>
								</span>
							</div>
							<div class="align-left">
								<span class="align-left checkbox all tooltip tooltip-bottom" title="Check all" visible-only></span>
								<div class="search-container align-left">
									<span class="icon search button window tooltip tooltip-bottom" title="Advanced proxy search and filter" window="search"></span>
								</div>
								<span class="icon replace button window tooltip tooltip-bottom" title="Configure proxy replacement settings" window="replace"></span>
								<span class="icon authenticate button window tooltip tooltip-bottom" title="Configure authentication settings" window="authenticate"></span>
								<span class="icon group button window tooltip tooltip-bottom" title="Create group from selected proxies" window="group"></span>
								<span class="icon copy button window tooltip tooltip-bottom" title="Copy selected proxies to copy" window="copy"></span>
							</div>
							<div class="clear"></div>
							<p class="no-margin-bottom"><span class="checked-container pull-left"><span class="total-checked">0</span> of <span class="total-results"><?php echo count($data['proxies']); ?></span> proxies selected</span><span class="check-action"></span>.</p>
						</div>
					</div>
					<div class="item-body">
						<form>
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
									'preferred_subnet' => '',
									'generate_unique' => '0',
									'disable_http' => '0',
									'configuration_action' => '',
									'username' => '',
									'password' => '',
									'authorized_ips' => '',
									'group_name' => '',
									'selected_ips' => ''
								);

								foreach ($hiddenFields as $fieldName => $fieldValue) {
									echo '<input class="' . str_replace('_', '-', $fieldName) . '" name="' . $fieldName . '" type="hidden" value="' . $fieldValue . '">';
								}

								if (!empty($search)) {
									$proxyCount = count($order['Proxy']);

									if (!empty($proxyCount)) {
										echo $this->Html->tag('div', '<strong>' . $proxyCount . ' proxy ' . ($proxyCount == 1 ? 'result' : 'results') . '</strong> found with your search criteria. ' . $this->Html->link('Clear search filter', '/orders/view/' . $order['Order']['id']), array(
											'class' => 'alert custom-alert'
										));
									}
								}
							?>
							<div class="proxy-table">
								<table class="table" previous_checked="0">
									<tbody>
										<?php foreach (array_values($data['proxies']) as $index => $proxy): ?>
											<tr data='<?php echo json_encode($proxy); // TODO: retrieve with API call during pagination ?>'
												page="<?php echo $proxy['current_page']; ?>"
												proxy_id="<?php echo $proxy['id']; ?>">
												<td style="width: 1px;">
													<span class="checkbox" index="<?php echo $index; ?>" proxy_id="<?php echo $proxy['id']; ?>"></span>
												</td>
												<td>
													<span class="details-container"></span>
													<span class="table-text"><?php echo $proxy['ip']; ?></span>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php include('../layouts/default/footer.php'); ?>
