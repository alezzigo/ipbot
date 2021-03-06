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
						<div class="checkbox-container">
							<span checked="0" class="checkbox gateway-enable" id="gateway-enable" name="gateway_enable"></span>
							<label class="custom-checkbox-label" for="gateway-enable" name="gateway_enable">Convert Selected Proxies to Gateway Proxies <span class="details icon tooltip tooltip-bottom" item_title="Gateway proxies will route HTTP and HTTPS requests to a list of selected static proxies at a specific rotation frequency. Leave this option blank to allow direct access without IP rotation."></span></label>
						</div>
						<div class="clear"></div>
						<div class="checkbox-option-container hidden" field="gateway_enable">
							<div class="field-group-container">
								<div class="field-group rotation-frequency no-margin-top width-auto">
									<span>Rotate IP Every</span>
									<input min="5" name="rotation_frequency" type="number" width="88" value="5">
									<span>Minute(s)</span>
								</div>
								<span class="align-left details icon tooltip tooltip-bottom" item_title="Every request to the selected gateway proxies will route to a static proxy IP from the selected static proxy IPs below. The static proxy IP will rotate every X number of minutes and the gateway proxies will remain the same."></span>
							</div>
							<div class="clear"></div>
							<div class="align-left checkbox-container">
								<span checked="0" class="checkbox rotation-on-every-request" id="rotation-on-every-request" name="rotation_on_every_request"></span>
								<label class="custom-checkbox-label" for="rotation-on-every-request" name="rotation_on_every_request">Rotate IP on Every Request <span class="details icon tooltip tooltip-bottom" item_title="Every request to the selected gateway proxies will route to a different IP from the selected static proxy IPs below. Consecutive request IPs may repeat occasionally for request pattern obscurity."></label>
							</div>
							<div class="clear"></div>
							<div class="item-list" page="static" table="proxies"></div>
						</div>
						<div class="clear"></div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button close main-button submit" frame="rotate" process="proxyItems">Apply Configuration</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="frame-overlay"></div>
</div>
