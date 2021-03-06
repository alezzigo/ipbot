<div class="hidden frame-container" frame="search">
	<div class="section frame">
		<div class="item-container">
			<div class="item">
				<div class="search-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Proxy Search</h1>
					</div>
					<div class="item-body">
						<label for="broad-search">Search Terms</label>
						<input class="broad-search" id="broad-search" name="broad_search" placeholder="<?php echo "Enter broad search terms (e.g. online AS88888)"; ?>" type="text">
						<label for="granular-search">Filter List of Specific IPs or Subnets</label>
						<textarea class="granular-search" id="granular-search" name="granular_search" placeholder="<?php echo "Enter list of specific proxy IPs to filter within your order\n127.0.0.1\n127.0.0.2\netc..."; ?>"></textarea>
						<div class="checkbox-container">
							<span checked="0" class="checkbox" id="filter-proxy-types" name="filter_proxy_types"></span>
							<label class="custom-checkbox-label" for="filter-proxy-types" name="filter_proxy_types">Filter By Specific Proxy Types</label>
						</div>
						<div class="checkbox-option-container hidden" field="filter_proxy_types">
							<!--<div class="checkbox-container no-margin-top">
								<span checked="0" class="checkbox" id="dns-ips" name="dns_ips"></span>
								<label class="custom-checkbox-label" for="dns-ips" name="dns_ips">DNS IPs</label>
							</div>-->
							<div class="checkbox-container no-margin-top">
								<span checked="0" class="checkbox" id="gateway-proxies" name="type[gateway]"></span>
								<label class="custom-checkbox-label" for="gateway-proxies" name="type[gateway]">Gateway Proxies</label>
							</div>
							<div class="checkbox-container no-margin-top">
								<span checked="0" class="checkbox" id="forwarding-proxies" name="type[forwarding]"></span>
								<label class="custom-checkbox-label" for="forwarding-proxies" name="type[forwarding]">Forwarding Proxies</label>
							</div>
							<div class="checkbox-container no-margin">
								<span checked="0" class="checkbox" id="static-proxies" name="type[static]"></span>
								<label class="custom-checkbox-label" for="static-proxies" name="type[static]">Static Proxies</label>
							</div>
						</div>
						<div class="checkbox-container no-margin-top">
							<span checked="0" class="checkbox" id="exclude-search" name="exclude_search"></span>
							<label class="custom-checkbox-label" for="exclude-search" name="exclude_search">Exclude Proxies Matching Search Terms and Filters</label>
						</div>
						<div class="checkbox-container no-margin-top">
							<span checked="0" class="checkbox" id="match-all-search" name="match_all_search"></span>
							<label class="custom-checkbox-label" for="match-all-search" name="match_all_search">Require All Search Terms to Match Proxy Results</label>
						</div>
						<div class="clear"></div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button close main-button submit" frame="search" process="proxyItems">Search</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="frame-overlay"></div>
</div>
