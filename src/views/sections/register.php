<div class="hidden frame-container" frame="register">
	<div class="section frame">
		<div class="item-container">
			<div class="item">
				<div class="register">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Register</h1>
					</div>
					<div class="item-body">
						<div class="message-container"></div>
						<label for="register-email">Email</label>
						<input class="email" id="register-email" name="email" type="text">
						<label for="register-password">Password</label>
						<input class="password" id="register-password" name="password" type="password">
						<label for="confirm_password">Confirm Password</label>
						<input class="confirm-password" id="register-confirm-password" name="confirm_password" type="password">
						<div class="checkbox-container">
							<span checked="1" class="checkbox" id="test-account" name="test_account"></span>
							<label class="custom-checkbox-label" for="test-account" name="test_account">This is a test account for demo purposes</label>
						</div>
						<div class="clear"></div>
						<a class="button frame-button" frame="login" href="javascript:void(0);">Already have an account?</a>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<button class="button main-button submit" process="users" frame="register">Register</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="frame-overlay"></div>
</div>
