<div class="hidden window-container" window="api">
	<div class="section window">
		<div class="item-container">
			<div class="item">
				<div class="api-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Proxy API Configuration</h1>
					</div>
					<div class="item-body">
						<div class="checkbox-container">
							<span checked="0" class="api-enable checkbox" id="api-enable" name="api_enable"></span>
							<label class="custom-checkbox-label" for="api-enable" name="api_enable">Enable proxy API</label>
						</div>
						<div class="api-enabled-container hidden">
							<input class="hidden" name="confirm_api_settings" type="hidden" value="1">
							<label for="api-username">Proxy Username</label>
							<input class="api-username" id="api-username" name="api_username" placeholder="Between 4 and 15 characters" type="text">
							<label for="api-password">Proxy Password</label>
							<input class="api-password" id="api-password" name="api_password" placeholder="Between 4 and 15 characters" type="text">
							<label for="api-whitelisted-ips">Whitelisted IPv4 Addresses</label>
							<textarea class="api-whitelisted-ips" id="api-whitelisted-ips" name="api_whitelisted_ips" placeholder="<?php echo "127.0.0.1\n127.0.0.2\netc..." ?>" type="text"></textarea>
						</div>
						<div class="clear"></div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button close main-button submit" process="proxies" window="api">Apply Configuration</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="window-overlay"></div>
</div>