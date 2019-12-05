<div class="hidden frame-container" frame="replace">
	<div class="section frame">
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
							<label class="custom-checkbox-label" for="instant-replacement" name="instant_replacement">Replace Selected Proxies Instantly</label>
						</div>
						<div class="clear"></div>
						<div class="checkbox-container no-margin-top">
							<span class="checkbox" id="replace-with-specific-node-locations" name="replace_with_specific_node_locations"></span>
							<label class="custom-checkbox-label" for="replace-with-specific-node-locations" name="replace_with_specific_node_locations">Replace With Specific Proxy Node Locations</label>
						</div>
						<div class="clear"></div>
						<div class="checkbox-option-container hidden" field="replace_with_specific_node_locations">
							<div class="field-group no-margin-top">
								<span>City</span>
								<select class="node-city" name="replacement_city"></select>
							</div>
							<div class="field-group no-margin-top">
								<span>Region</span>
								<select class="node-region" name="replacement_region"></select>
							</div>
							<div class="field-group no-margin">
								<span>Country</span>
								<select class="node-country-code" name="replacement_country_code"></select>
							</div>
						</div>
						<div class="checkbox-container no-margin-top">
							<span class="checkbox" id="transfer-authentication" name="transfer_authentication"></span>
							<label class="custom-checkbox-label" for="transfer-authentication" name="transfer_authentication">Transfer Authentication Settings to Replacement Proxies</label>
						</div>
						<div class="checkbox-container no-margin-top">
							<span class="checkbox" id="enable-automatic-replacements" name="enable_automatic_replacements"></span>
							<label class="custom-checkbox-label" for="enable-automatic-replacements" name="enable_automatic_replacements">Enable Automatic Replacements</label>
						</div>
						<div class="clear"></div>
						<div class="checkbox-option-container hidden" field="enable_automatic_replacements">
							<div class="field-group no-margin">
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
									<option value="13">13</option>
									<option value="14">14</option>
									<option value="15">15</option>
									<option value="16">16</option>
									<option value="17">17</option>
									<option value="18">18</option>
									<option value="19">19</option>
									<option value="20">20</option>
									<option value="21">21</option>
									<option value="22">22</option>
									<option value="23">23</option>
									<option value="24">24</option>
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
						<button class="button close alternate-button">Close</button>
						<button class="button close main-button submit" frame="replace" process="proxies">Apply Configuration</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="frame-overlay"></div>
</div>
