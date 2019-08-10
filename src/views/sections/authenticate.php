<div class="hidden window-container" window="authenticate">
	<div class="section window">
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
						<input class="username" id="username" name="username" type="text">
						<label for="password">Proxy Password</label>
						<input class="password" id="password" name="password" type="text">
						<div class="checkbox-container">
							<span checked="0" class="checkbox" id="generate-unique" name="generate_unique"></span>
							<label class="custom-checkbox-label" for="generate-unique" name="generate_unique">Generate random unique usernames and passwords</label>
						</div>
						<label for="whitelisted_ips">Whitelisted IPs</label>
						<textarea class="whitelisted-ips" id="whitelisted-ips" name="whitelisted_ips" type="text"></textarea>
					</div>
					<div class="item-footer">
						<button class="button close main-button">Close</button>
						<button class="button close main-button submit" process="proxies" window="authenticate">Apply Configuration</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="window-overlay"></div>
</div>