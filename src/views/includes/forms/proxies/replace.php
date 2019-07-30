<div class="hidden window-container" window="replace">
	<div class="section window">
		<div class="item-container">
			<div class="item">
				<div class="replace-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Proxy Replacement Configuration</h1>
					</div>
					<div class="item-body">
						<div class="checkbox-container no-margin-top">
							<span class="checkbox" id="instant-replacement" name="instant_replacement"></span>
							<label class="custom-checkbox-label" for="instant-replacement" name="instant_replacement">Replace selected proxies instantly</label>
						</div>
						<div class="checkbox-container no-margin-bottom">
							<span class="checkbox" id="enable-automatic-replacements" name="enable_automatic_replacements"></span>
							<label class="custom-checkbox-label" for="enable-automatic-replacements" name="enable_automatic_replacements">Enable automatic replacements</label>
						</div>
						<div class="checkbox-option-container hidden" field="enable_automatic_replacements">
							<div class="field-group">
								<span>Every</span>
								<select class="automatic-replacement-interval-value" name="automatic_replacement_interval_value">
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
									<option value="5">5</option>
									<option value="6">6</option>
									<option value="7">7</option>
									<option value="8">8</option>
									<option value="9">9</option>
									<option value="10">10</option>
									<option value="11">11</option>
									<option value="12">12</option>
								</select>
								<select class="automatic-replacement-interval-type" name="automatic_replacement_interval_type">
									<option value="week">Weeks</option>
									<option value="month">Months</option>
								</select>
							</div>
						</div>
						<div class="clear"></div>
					</div>
					<div class="item-footer">
						<button class="button close">Close</button>
						<button class="button submit" process="proxies" window="replace">Apply Configuration</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="window-overlay"></div>
</div>
