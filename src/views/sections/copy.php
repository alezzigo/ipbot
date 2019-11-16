<div class="hidden window-container" window="copy">
	<div class="section window">
		<div class="item-container">
			<div class="item">
				<div class="copy-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Copy Proxy List to Clipboard</h1>
					</div>
					<div class="item-body">
						<label>Proxy List Format</label>
						<div class="field-group list-format no-margin-top">
							<select class="ipv4-column-1" name="ipv4_column_1">
								<option value=""></option>
								<option selected value="ip">ip</option>
								<option value="port">port</option>
								<option value="username">user</option>
								<option value="password">pass</option>
							</select>
							<select class="ipv4-delimiter-1" name="ipv4_delimiter_1">
								<option value=" "></option>
								<option selected value=":">:</option>
								<option value=";">;</option>
								<option value=",">,</option>
								<option value="@">@</option>
							</select>
							<select class="ipv4-column-2" name="ipv4_column_2">
								<option value=""></option>
								<option value="ip">ip</option>
								<option selected value="port">port</option>
								<option value="username">user</option>
								<option value="password">pass</option>
							</select>
							<select class="ipv4-delimiter-2" name="ipv4_delimiter_2">
								<option value=" "></option>
								<option selected value=":">:</option>
								<option value=";">;</option>
								<option value=",">,</option>
								<option value="@">@</option>
							</select>
							<select class="ipv4-column-3" name="ipv4_column_3">
								<option value=""></option>
								<option value="ip">ip</option>
								<option value="port">port</option>
								<option selected value="username">user</option>
								<option value="password">pass</option>
							</select>
							<select class="ipv4-delimiter-3" name="ipv4_delimiter_3">
								<option value=" "></option>
								<option selected value=":">:</option>
								<option value=";">;</option>
								<option value=",">,</option>
								<option value="@">@</option>
							</select>
							<select class="ipv4-column-4" name="ipv4_column_4">
								<option value=""></option>
								<option value="ip">ip</option>
								<option value="port">port</option>
								<option value="username">user</option>
								<option selected value="password">pass</option>
							</select>
						</div>
						<div class="clear"></div>
						<div class="align-left">
							<label class="clear">Proxy List Type</label>
							<div class="field-group no-margin-top proxy-list-type">
								<select class="proxy-list-type" name="proxy_list_type">
									<option selected value="http">HTTP / HTTPS</option>
								</select>
							</div>
						</div>
						<div class="align-left">
							<label class="clear">Separated By</label>
							<div class="field-group no-margin-top separated-by">
								<select class="separated-by" name="separated_by">
									<option selected value="new_line">New Line</option>
									<option value="comma">Comma</option>
									<option value="semicolon">Semicolon</option>
									<option value="space">Space</option>
									<option value="underscore">Underscore</option>
								</select>
							</div>
						</div>
						<div class="clear"></div>
						<p class="message loading">Loading...</p>
						<div class="copy hidden">
							<label>Proxy List</label>
							<div class="copy-textarea-container">
								<textarea class="copy" id="copy" name="copy"></textarea>
							</div>
						</div>
						<div class="clear"></div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button copy main-button" field="copy">Copy to Clipboard</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="window-overlay"></div>
</div>
