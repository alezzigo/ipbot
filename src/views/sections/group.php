<div class="hidden frame-container" frame="group">
	<div class="section frame">
		<div class="item-container">
			<div class="item">
				<div class="group-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Manage Proxy Groups</h1>
					</div>
					<div class="item-body">
						<p class="hidden message submit">The items you've selected previously will be added to the groups selected below.</p>
						<label for="group_name">Add New Proxy Group</label>
						<div class="field-group no-margin">
							<input class="group-name-field" id="group-name" name="group_name" placeholder="Enter group name" type="text">
							<button class="button group-name-button">Add</button>
						</div>
						<div class="clear"></div>
						<div class="item-controls-container controls-container scrollable"></div>
						<div class="item-list" table="proxy_groups"></div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button close hidden main-button submit" frame="group" process="proxyItems">Add to Selected Groups</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="frame-overlay"></div>
</div>
