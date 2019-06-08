<?php
	require_once('../../controllers/orders.php');
	include('../layouts/default/header.php');
	require_once('../../views/includes/forms/search.php');
	require_once('../../views/includes/forms/replacement.php');
	require_once('../../views/includes/forms/authentication.php');
	require_once('../../views/includes/forms/group.php');
	require_once('../../views/includes/forms/clipboard.php');
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
								<span class="pagination" current="0" pages="<?php echo $pages; ?>" results="<?php echo $data['results_per_page']; ?>">
									<span class="align-left">
										<span class="first-result">1</span> - <span class="last-result"><?php echo (count($data['proxies']) >= $data['results_per_page'] ? $data['results_per_page'] : count($data['proxies'])); ?></span> of <?php echo count($data['proxies']); ?>
									</span>
									<a title="Previous <?php echo $data['results_per_page']; ?> proxies" href="javascript:void(0);" class="btn btn-primary previous align-left tooltip tooltip-bottom" previous="0"></a>
									<a title="Next <?php echo $data['results_per_page']; ?> proxies" href="javascript:void(0);" class="btn btn-primary next align-left tooltip tooltip-bottom" next="1"></a>
								</span>
							</div>
							<div class="align-left">
								<div class="checkbox-container align-left">
									<span class="checkbox select-all tooltip tooltip-bottom" title="Select all"></span>
								</div>
								<div class="search-container align-left">
									<span class="icon-container align-left tooltip tooltip-bottom" title="Advanced proxy search and filter">
										<span class="icon search button window">
											<svg xmlns="http://www.w3.org/2000/svg" svg-license="https://raw.githubusercontent.com/feathericons/feather/master/LICENSE" svg-copyright="The MIT License (MIT) Copyright (c) 2013-2017 Cole Bemis" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
										</span>
									</span>
								</div>
								<span class="icon-container align-left disabled tooltip tooltip-bottom" title="Configure proxy replacement settings">
									<span href="#" class="icon replacements button window">
										<svg xmlns="http://www.w3.org/2000/svg" svg-license="https://raw.githubusercontent.com/feathericons/feather/master/LICENSE" svg-copyright="The MIT License (MIT) Copyright (c) 2013-2017 Cole Bemis" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
									</span>
								</span>
								<span class="icon-container align-left disabled tooltip tooltip-bottom" title="Configure authentication settings">
									<span href="#" class="icon authentication button window">
										<svg xmlns="http://www.w3.org/2000/svg" svg-license="https://raw.githubusercontent.com/feathericons/feather/master/LICENSE" svg-copyright="The MIT License (MIT) Copyright (c) 2013-2017 Cole Bemis" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
									</span>
								</span>
								<span class="icon-container align-left disabled tooltip tooltip-bottom" title="Create group from selected proxies">
									<span href="#" class="icon group button window">
										<svg xmlns="http://www.w3.org/2000/svg" svg-license="https://raw.githubusercontent.com/feathericons/feather/master/LICENSE" svg-copyright="The MIT License (MIT) Copyright (c) 2013-2017 Cole Bemis" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
									</span>
								</span>
								<span class="icon-container align-left disabled tooltip tooltip-bottom" title="Copy selected proxies to clipboard">
									<span href="#" class="icon clipboard button window">
										<svg xmlns="http://www.w3.org/2000/svg" svg-license="https://raw.githubusercontent.com/feathericons/feather/master/LICENSE" svg-copyright="The MIT License (MIT) Copyright (c) 2013-2017 Cole Bemis" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
									</span>
								</span>
							</div>
							<div class="clear"></div>
							<p class="no-margin-bottom"><span class="selected-container pull-left"><span class="selected">0</span> of <?php echo count($data['proxies']); ?> proxies selected</span>. <a class="select-all" href="javascript:void(0);">Select all <?php echo count($data['proxies']); ?> proxies</a></p>
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
								<table class="table" last_selected_row="">
									<tbody>
										<?php foreach ($data['proxies'] as $index => $proxy): ?>
											<tr row_index="<?php echo $index; ?>"
												data='<?php echo json_encode($proxy); // TODO: retrieve with API call during pagination ?>'
												page="<?php echo $proxy['current_page']; ?>"
												proxy_id="<?php echo $proxy['id']; ?>">
												<td style="width: 1px;">
													<div class="checkbox-container no-margin-right" proxy_id="<?php echo $proxy['id']; ?>">
														<span class="checkbox"></span>
													</div>
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
