<?php
	$countries = array('AF' => 'Afghanistan', 'AX' => 'Aland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua And Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia', 'BA' => 'Bosnia And Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CD' => 'Congo, Democratic Republic', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Cote D\'Ivoire', 'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands (Malvinas)', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HT' => 'Haiti', 'HM' => 'Heard Island & Mcdonald Islands', 'VA' => 'Holy See (Vatican City State)', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran, Islamic Republic Of', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle Of Man', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KR' => 'Korea', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libyan Arab Jamahiriya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MK' => 'Macedonia', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia, Federated States Of', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'AN' => 'Netherlands Antilles', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestinian Territory, Occupied', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'BL' => 'Saint Barthelemy', 'SH' => 'Saint Helena', 'KN' => 'Saint Kitts And Nevis', 'LC' => 'Saint Lucia', 'MF' => 'Saint Martin', 'PM' => 'Saint Pierre And Miquelon', 'VC' => 'Saint Vincent And Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome And Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia And Sandwich Isl.', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard And Jan Mayen', 'SZ' => 'Swaziland', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania', 'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad And Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TC' => 'Turks And Caicos Islands', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'US' => 'United States', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'UM' => 'United States Outlying Islands', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela', 'VN' => 'Viet Nam', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'WF' => 'Wallis And Futuna', 'EH' => 'Western Sahara', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe');
	$months = array('01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December');
	$years = range(date('Y', time()), date('Y', time()) + 10);
	$countrySelectValues = $monthSelectValues = $yearSelectValues = '';

	foreach ($countries as $countryCode => $countryName) {
		$countrySelectValues .= '<option ' . ($countryCode === 'US' ? 'selected ' : ' ') . 'value="' . $countryCode . '">' . $countryName . '</option>';
	}

	foreach ($months as $monthNumber => $monthName) {
		$monthSelectValues .= '<option value="' . $monthNumber . '">' . $monthName . '</option>';
	}

	foreach ($years as $year) {
		$yearSelectValues .= '<option value="' . $year . '">' . $year . '</option>';
	}
?>
<div class="hidden window-container" window="payment">
	<div class="section window">
		<div class="item-container">
			<div class="item">
				<div class="search-configuration">
					<div class="item-header">
						<span class="button close icon icon-close"></span>
						<h1>Payment</h1>
					</div>
					<div class="item-body">
						<div class="message-container"></div>
						<h2 class="no-margin-top">Account Details</h2>
						<div class="account-details">
							<p class="message">Create a new <?php echo $config->settings['site_name']; ?> account or log in with an existing email and password below.</p>
							<label for="register-email">Email</label>
							<input class="email" id="register-email" name="email" placeholder="Enter account email address" type="text">
							<label for="register-password">Password</label>
							<input class="password" id="register-password" name="password" placeholder="Enter account password" type="password">
							<label for="confirm_password">Confirm Password</label>
							<input class="confirm-password" id="register-confirm-password" name="confirm_password" placeholder="Confirm account password" type="password">
						</div>
						<h2>Payment Method</h2>
						<div class="field-group payment-methods">
							<label for="paypal" type="radio"><input checked id="paypal" name="payment_method" type="radio" value="paypal"> PayPal</label>
							<label for="credit-card" type="radio"><input id="credit-card" name="payment_method" type="radio" value="credit_card"> Credit Card</label>
						</div>
						<div class="clear"></div>
						<div class="payment-method paypal">
							<p class="message">You'll be redirected to log into PayPal after clicking "Complete Payment".</p>
						</div>
						<div class="credit-card hidden payment-method">
							<label for="billing-cc-name">Full Name on Card</label>
							<input class="billing-cc-name" id="billing-cc-name" name="billing_cc_name" placeholder="Enter full name on credit card" type="text">
							<label for="billing-cc-number">Card Number</label>
							<input class="billing-cc-number no-margin-bottom" id="billing-cc-number" name="billing_cc_number" placeholder="Enter 16-digit number on credit card" type="text">
							<div class="field-group">
								<span>Month</span><select class="billing-cc-month" name="billing_cc_month"><?php echo $monthSelectValues; ?></select>
								<span>Year</span><select class="billing-cc-year" name="billing_cc_year"><?php echo $yearSelectValues; ?></select>
								<span>CVV Code</span><input class="billing-cc-code" name="billing_cc_code" type="number" placeholder="123">
							</div>
							<label for="billing-name">Billing Name</label>
							<input class="billing-name" id="billing-name" name="billing_name" placeholder="Enter your full name" type="text">
							<label for="billing-name">Billing Address</label>
							<input class="billing-address-1 no-margin-bottom" id="billing-address-1" name="billing_address_1" placeholder="Enter billing address line 1" type="text">
							<div class="field-group">
								<input class="billing-address-2" id="billing-address-2" name="billing_address_2" placeholder="Enter billing address line 2" type="text">
							</div>
							<label for="billing-city">Billing City</label>
							<input class="billing-city" id="billing-city" name="billing_city" placeholder="Enter your billing city" type="text">
							<label for="billing-state">Billing State</label>
							<input class="billing-state" id="billing-state" name="billing_state" placeholder="Enter your billing state" type="text">
							<label for="billing-zip">Billing Zip</label>
							<input class="billing-zip" id="billing-zip" name="billing_zip" placeholder="Enter your billing zip" type="text">
							<label for="billing-country">Billing Country</label>
							<select class="billing-country" id="billing-country" name="billing_country"><?php echo $countrySelectValues; ?></select>
						</div>
					</div>
					<div class="item-footer">
						<button class="button close alternate-button">Close</button>
						<!--<button class="button main-button submit" process="payment" window="payment">Complete Payment</button>-->
						<button class="button main-button submit" disabled process="payment" window="payment">Payment Disabled</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="window-overlay"></div>
</div>
