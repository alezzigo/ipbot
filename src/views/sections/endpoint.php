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
							<label for="endpoint-username">API Endpoint Username</label>
							<input class="endpoint-username" id="endpoint-username" name="endpoint_username" placeholder="Between 4 and 15 characters" type="text">
							<label for="endpoint-password">API Endpoint Password</label>
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
							<p>API endpoint URL:</p>
							<pre>https://<?php echo $config->settings['base_domain'] . $config->settings['base_url'] . 'api/proxies'; ?></pre>
							<p>Request JSON object for retrieving proxies:</p>
							<pre>POST
{
	action: <span>"list"</span>,
	data: {
		authentication: {
			password: <span>"API_PASSWORD"</span>,
			username: <span>"API_USERNAME"</span>
		},
		order_id: <span><?php echo $data['order_id']; ?></span>
	},
	table: <span>"proxies"</span>
}</pre>
							<p>Response JSON object:</p>
							<pre>
{
	data: {
		proxies: {
			count: <span>100</span>,
			data: [
				{
					asn: <span>"AS88888 ISP Communications"</span>,
					automatic_replacement_interval_type: <span>"month"</span>,
					automatic_replacement_interval_value: <span>1</span>,
					city: <span>"Ventura"</span>,
					country_code: <span>"US"</span>,
					country_name: <span>"United States"</span>,
					disable_http: <span>false</span>,
					http_port: <span>80</span>,
					id: <span>886</span>,
					ip: <span>"10.3.3.7"</span>,
					isp: <span>"ISP Communications"</span>,
					last_replacement_date: <span><?php echo date('Y-m-d H:i:s', time()); ?></span>,
					next_replacement_available: <span><?php echo date('Y-m-d H:i:s', strtotime('+1 week')); ?></span>,
					node_id: <span>189</span>,
					order_id: <span><?php echo $data['order_id']; ?></span>,
					password: <span>"PROXY_PASSWORD"</span>,
					region: <span>"California"</span>,
					replacement_removal_date: <span>null</span>,
					require_authentication: <span>true</span>,
					status: <span>"online"</span>,
					transfer_authentication: <span>false</span>,
					user_id: <span>1</span>,
					username: <span>"PROXY_USERNAME"</span>,
					whitelisted_ips: <span>"127.0.0.1
127.0.0.2"</span>
				},
				<span>// ..</span>
			]
		}
	}
}</pre>
							<p>Request JSON object for configuring proxy authentication settings:</p>
							<pre>POST
{
	action: <span>"authenticate"</span>,
	data: {
		authentication: {
			password: <span>"API_PASSWORD"</span>,
			username: <span>"API_USERNAME"</span>
		},
		generate_unique: <span>false</span>,
		items: [
			<span>100</span>,
			<span>101</span>,
			<span>// List of proxy IDs</span>
		],
		order_id: <span><?php echo $data['order_id']; ?></span>,
		password: <span>"NEW_PROXY_PASSWORD"</span>,
		username: <span>"NEW_PROXY_USERNAME"</span>,
		whitelisted_ips: [
			<span>"127.0.0.1"</span>,
			<span>// ..</span>
		]
	},
	table: <span>"proxies"</span>
}</pre>
							<p>Response JSON object:</p>
							<pre>
{
	data: {
		proxies: {
			count: <span>10</span>,
			data: [
				{
					asn: <span>"AS88888 ISP Communications"</span>,
					city: <span>"Ventura"</span>,
					country_code: <span>"US"</span>,
					country_name: <span>"United States"</span>,
					disable_http: <span>false</span>,
					http_port: <span>80</span>,
					id: <span>886</span>,
					ip: <span>"10.3.3.7"</span>,
					isp: <span>"ISP Communications"</span>,
					order_id: <span><?php echo $data['order_id']; ?></span>,
					password: <span>"NEW_PROXY_PASSWORD"</span>,
					region: <span>"California"</span>,
					status: <span>"online"</span>,
					transfer_authentication: <span>false</span>,
					user_id: <span>1</span>,
					username: <span>"NEW_PROXY_USERNAME"</span>,
					whitelisted_ips: <span>"127.0.0.1
127.0.0.2"</span>
				},
				<span>// ..</span>
			]
		}
	}
}
</pre>
							<p>Request JSON object for configuring proxy replacement settings:</p>
							<pre>POST
{
	action: <span>"replace"</span>,
	data: {
		authentication: {
			password: <span>"API_PASSWORD"</span>,
			username: <span>"API_USERNAME"</span>
		},
		automatic_replacement_interval_type: <span>"week"</span>, <span>// week|month</span>
		automatic_replacement_interval_value: <span>1</span>,
		enable_automatic_replacements: <span>false</span>,
		instant_replacement: <span>true</span>,
		items: [
			<span>100</span>,
			<span>101</span>,
			<span>// List of proxy IDs</span>
		],
		order_id: <span><?php echo $data['order_id']; ?></span>,
		replacement_city: <span>"Ventura"</span>,
		replacement_country_code: <span>"US"</span>,
		replacement_region: <span>"California"</span>,
		replace_with_specific_node_locations: <span>true</span>,
		transfer_authentication: <span>true</span>
	},
	table: <span>"proxies"</span>
}
</pre>
							<p>Response JSON object:</p>
							<pre>
{
	data: {
		proxies: {
			count: <span>7</span>,
			data: [
				{
					asn: <span>"AS88888 ISP Communications"</span>,
					city: <span>"Ventura"</span>,
					country_code: <span>"US"</span>,
					country_name: <span>"United States"</span>,
					disable_http: <span>false</span>,
					http_port: <span>80</span>,
					id: <span>886</span>,
					ip: <span>"10.3.3.7"</span>,
					isp: <span>"ISP Communications"</span>,
					order_id: <span><?php echo $data['order_id']; ?></span>,
					password: <span>"PROXY_PASSWORD"</span>,
					region: <span>"California"</span>,
					status: <span>"replaced"</span>,
					transfer_authentication: <span>false</span>,
					user_id: <span>1</span>,
					username: <span>"PROXY_USERNAME"</span>,
					whitelisted_ips: <span>"127.0.0.1
127.0.0.2"</span>
				},
				<span>// ..</span>
			]
		}
	}
}
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
