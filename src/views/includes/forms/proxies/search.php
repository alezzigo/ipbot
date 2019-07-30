<div class="hidden window-container" window="search">
	<div class="section window">
		<div class="item-container">
			<div class="item">
				<div class="search-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Advanced Proxy Search</h1>
					</div>
					<div class="item-body">
						<label for="broad-search">Search Terms</label>
						<input class="broad-search" id="broad-search" name="broad_search" type="text">
						<label for="granular-search">Filter List of Specific IPs or Subnets</label>
						<textarea class="granular-search" id="granular-search" name="granular_search"></textarea>
						<div class="checkbox-container">
							<span checked="0" class="checkbox" id="exclude-search" name="exclude_search"></span>
							<label class="custom-checkbox-label" for="exclude-search" name="exclude_search">Exclude proxies matching terms and filter</label>
						</div>
						<div class="checkbox-container">
							<span checked="0" class="checkbox" id="match-all-search" name="match_all_search"></span>
							<label class="custom-checkbox-label" for="match-all-search" name="match_all_search">Require all search terms to match proxy results</label>
						</div>
					</div>
					<div class="item-footer">
						<button class="button close">Close</button>
						<button class="button submit" process="proxies" window="search">Search</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="window-overlay"></div>
</div>
