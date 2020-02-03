<div class="hidden frame-container" frame="rotate">
	<div class="section frame">
		<div class="item-container">
			<div class="item">
				<div class="rotate-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Proxy IP Rotation Configuration</h1>
					</div>
					<div class="item-body">
						<p class="error message">Gateway proxy and forwarding proxy IP/DNS rotation config options are currently in development.</p>
						<div class="checkbox-container">
							<span checked="0" class="checkbox gateway-enable" id="gateway-enable" name="gateway_enable"></span>
							<label class="custom-checkbox-label" for="gateway-enable" name="gateway_enable">Convert Selected Proxies to Gateway Proxies</label>
							<p class="message"><strong>Gateway proxies</strong> will route HTTP requests to a list of selected static proxies at a specific rotation interval. Leave this option blank to allow direct access without IP rotation.</p>
						</div>
						<div class="clear"></div>
						<div class="checkbox-option-container hidden" field="gateway_enable">
							<label>Configure Static IP Rotation Interval for Gateway Proxies</label>
							<div class="clear"></div>
							<div class="field-group rotation-interval no-margin-bottom width-auto">
								<span>Every</span>
								<input min="1" name="rotation_interval" type="number" width="88" value="1">
								<span>Minute(s)</span>
							</div>
							<div class="align-left checkbox-container">
								<span checked="0" class="checkbox rotation-on-every-request" id="rotation-on-every-request" name="rotation_on_every_request"></span>
								<label class="custom-checkbox-label" for="rotation-on-every-request" name="rotation_on_every_request">Rotate IP on Every Request</label>
							</div>
							<div class="clear"></div>
							<label>Select Forwarding Proxies for Gateway Proxies</label>
							<div class="clear"></div>
							<p class="message no-margin-bottom">Select a list of <strong>forwarding proxies</strong> which will forward HTTP requests from the gateway proxy to the selected list of static proxies below. It's highly recommended to enable forwarding proxies for larger static proxy lists to decrease request time.</p>
							<div class="item-list" page="forwarding" table="proxies"></div>
							<div class="clear"></div>
							<label>Select Static Proxies for Gateway Proxies</label>
							<div class="clear"></div>
							<p class="message no-margin-bottom">Select a list of <strong>static proxies</strong> which will be accessible through the selected gateway proxies. This selection will override any forwarding proxy selections for the same IP.</p>
							<div class="item-list" page="static" table="proxies"></div>
						</div>
						<div class="clear"></div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button close main-button submit" disabled frame="rotate" process="proxyItems">Apply Configuration</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="frame-overlay"></div>
</div>
