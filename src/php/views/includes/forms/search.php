<div class="hidden window-container" window="search">
	<div class="window">
		<div class="item-container">
			<div class="item">
				<div class="search-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Advanced Proxy Search</h1>
					</div>
					<div class="item-body">
						<label for="broad-search">Search Terms</label>
						<input class="broad-search" id="broad-search" name="broad_search" placeholder="Enter broad search terms (e.g. California 154.3.x, replaced, etc.)" type="text">
						<label for="granular-search">Filter List of Specific IPs</label>
						<input class="granular-search" id="granular-search" name="granular_search" placeholder="<?php echo 'Enter list of specific proxy IPs to filter within your order.' . "\n" . '1.2.3.4' . "\n" . '1.2.3.5' . "\n" . 'etc...'; ?>" type="textarea">
						<div class="checkbox-container">
							<span class="checkbox" id="exclude-search"></span>
						</div>
						<label class="custom-checkbox-label" for="exclude-search">Exclude proxies that match search terms and filter</label>
						<div class="checkbox-container">
							<span class="checkbox" id="match-all-search"></span>
						</div>
						<label class="custom-checkbox-label" for="match-all-search">Require all search terms to match proxy results</label>
					</div>
					<div class="item-footer">
						<button class="button close">Close</button>
						<button class="button search">Search</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="window-overlay"></div>
</div>
