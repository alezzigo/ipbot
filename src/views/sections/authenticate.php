<div class="hidden frame-container" frame="authenticate">
	<div class="section frame">
		<div class="item-container">
			<div class="item">
				<div class="authentication-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Proxy Authentication Configuration</h1>
					</div>
					<div class="item-body">
						<p class="message">These proxy usernames and passwords may be stored in plain text. Please make sure they don't include any sensitive information.</p>
						<label for="username">Proxy Username</label>
						<input class="username" id="username" name="username" placeholder="Between 4 and 15 characters" type="text">
						<label for="password">Proxy Password</label>
						<input class="password" id="password" name="password" placeholder="Between 4 and 15 characters" type="text">
						<div class="checkbox-container">
							<span checked="0" class="checkbox" id="generate-unique" name="generate_unique"></span>
							<label class="custom-checkbox-label" for="generate-unique" name="generate_unique">Generate Random Unique Usernames and Passwords</label>
						</div>
						<label for="whitelisted_ips">Whitelisted IPv4 Addresses</label>
						<textarea class="whitelisted-ips" id="whitelisted-ips" name="whitelisted_ips" placeholder="<?php echo "127.0.0.1\n127.0.0.2\netc..." ?>" type="text"></textarea>
						<div class="checkbox-container">
							<span checked="0" class="checkbox" id="disable-http" name="disable_http"></span>
							<label class="custom-checkbox-label" for="disable-http" name="disable_http">Disable HTTP Internet Ports</label>
						</div>
						<div class="clear"></div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button close main-button submit" frame="authenticate" process="proxies">Apply Configuration</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="frame-overlay"></div>
</div>
