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
	action: "list",
	data: {
		authentication: {
			password: "API_PASSWORD",
			username: "API_USERNAME"
		},
		order_id: <?php echo $data['order_id']; ?>
	},
	table: "proxies"
}</pre>
							<p>Response JSON object:</p>
							<pre>
{
	data: {
		proxies: {
			count: 100,
			data: [
				{
					asn: "AS88888 ISP Communications",
					automatic_replacement_interval_type: "month",
					automatic_replacement_interval_value: 1,
					city: "Ventura",
					country_code: "US",
					country_name: "United States",
					disable_http: 0,
					http_port: 80,
					id: 886,
					ip: "10.3.3.7",
					isp: "ISP Communications",
					last_replacement_date: <?php echo date('Y-m-d H:i:s', time()); ?>,
					next_replacement_available: <?php echo date('Y-m-d H:i:s', strtotime('+1 week')); ?>,
					node_id: 189,
					order_id: <?php echo $data['order_id']; ?>,
					password: "PROXY_PASSWORD",
					region: "California",
					replacement_removal_date: null,
					require_authentication: 1,
					status: "online",
					transfer_authentication: 0,
					user_id: 1,
					username: "PROXY_USERNAME",
					whitelisted_ips: "127.0.0.1
127.0.0.2"
				},
				// ..
			]
		}
	}
}</pre>
							<p>Request JSON object for configuring proxy authentication settings:</p>
							<pre>POST
{
	action: "authenticate",
	data: {
		authentication: {
			password: "API_PASSWORD",
			username: "API_USERNAME"
		},
		generate_unique: false,
		items: [
			100,
			101,
			// List of proxy IDs
		],
		order_id: <?php echo $data['order_id']; ?>,
		password: "NEW_PROXY_PASSWORD",
		username: "NEW_PROXY_USERNAME",
		whitelisted_ips: [
			"127.0.0.1",
			// ..
		]
	},
	table: "proxies"
}</pre>
							<p>Response JSON object:</p>
							<pre>
{
	data: {
		proxies: {
			count: 10,
			data: [
				{
					asn: "AS88888 ISP Communications",
					city: "Ventura",
					country_code: "US",
					country_name: "United States",
					disable_http: 0,
					http_port: 80,
					id: 886,
					ip: "10.3.3.7",
					isp: "ISP Communications",
					order_id: <?php echo $data['order_id']; ?>,
					password: "NEW_PROXY_PASSWORD",
					region: "California",
					status: "online",
					transfer_authentication: 0,
					user_id: 1,
					username: "NEW_PROXY_USERNAME",
					whitelisted_ips: "127.0.0.1
127.0.0.2"
				},
				// ..
			]
		}
	}
}
</pre>
							<p>Request JSON object for configuring proxy replacement settings:</p>
							<pre>POST
{
	action: "replace",
	data: {
		authentication: {
			password: "API_PASSWORD",
			username: "API_USERNAME"
		},
		automatic_replacement_interval_type: "week", // week|month
		automatic_replacement_interval_value: 1,
		enable_automatic_replacements: false,
		instant_replacement: true,
		items: [
			100,
			101,
			// List of proxy IDs
		],
		order_id: <?php echo $data['order_id']; ?>,
		transfer_authentication: true
	},
	table: "proxies"
}
</pre>
							<p>Response JSON object:</p>
							<pre>
{
	data: {
		proxies: {
			count: 7,
			data: [
				{
					asn: "AS88888 ISP Communications",
					city: "Ventura",
					country_code: "US",
					country_name: "United States",
					disable_http: 0,
					http_port: 80,
					id: 886,
					ip: "10.3.3.7",
					isp: "ISP Communications",
					order_id: <?php echo $data['order_id']; ?>,
					password: "PROXY_PASSWORD",
					region: "California",
					status: "replaced",
					transfer_authentication: 0,
					user_id: 1,
					username: "PROXY_USERNAME",
					whitelisted_ips: "127.0.0.1
127.0.0.2"
				},
				// ..
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
