<div class="hidden frame-container" frame="endpoint">
	<div class="section frame">
		<div class="item-container">
			<div class="item">
				<div class="endpoint-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Proxy API Endpoint Configuration</h1>
					</div>
					<div class="item-body">
						<div class="checkbox-container">
							<span checked="0" class="endpoint-enable checkbox" id="endpoint-enable" name="endpoint_enable"></span>
							<label class="custom-checkbox-label" for="endpoint-enable" name="endpoint_enable">Enable Proxy API Endpoint</label>
						</div>
						<div class="endpoint-enabled-container hidden">
							<input class="hidden" name="confirm_endpoint_settings" type="hidden" value="1">
							<label for="endpoint-username">Proxy Username</label>
							<input class="endpoint-username" id="endpoint-username" name="endpoint_username" placeholder="Between 4 and 15 characters" type="text">
							<label for="endpoint-password">Proxy Password</label>
							<input class="endpoint-password" id="endpoint-password" name="endpoint_password" placeholder="Between 4 and 15 characters" type="text">
							<label for="endpoint-whitelisted-ips">Whitelisted IPv4 Addresses</label>
							<textarea class="endpoint-whitelisted-ips" id="endpoint-whitelisted-ips" name="endpoint_whitelisted_ips" placeholder="<?php echo "127.0.0.1\n127.0.0.2\netc..." ?>" type="text"></textarea>
							<div class="checkbox-container">
								<span checked="1" class="endpoint-require-authentication checkbox" id="endpoint-require-authentication" name="endpoint_require_authentication"></span>
								<label class="custom-checkbox-label" for="endpoint-require-authentication" name="endpoint_require_authentication">Require Authentication</label>
							</div>
							<div class="checkbox-container no-margin-top">
								<span checked="0" class="endpoint-require-match checkbox" id="endpoint-require-match" name="endpoint_require_match"></span>
								<label class="custom-checkbox-label" for="endpoint-require-match" name="endpoint_require_match">Require Both Username/Password and Whitelisted IPs to Match</label>
							</div>
						</div>
						<div class="clear"></div>
						<a class="endpoint-show-documentation" href="javascript:void(0);">Show API documentation</a>
						<div class="endpoint-documentation hidden">
							<pre>
								// ..
							</pre>
						</div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button close main-button submit" frame="endpoint" process="endpoint">Apply Configuration</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="frame-overlay"></div>
</div>
