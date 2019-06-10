var elements = {
	addClass: (selector, className) => {
		selectAllElements(selector).map((element) => {
			element[1].classList.add(className);
		});
	},
	loop: (selector, callback) => {
		selectAllElements(selector).map((element) => {
			callback(element[0], element[1]);
		});
	},
	removeClass: (selector, className) => {
		selectAllElements(selector).map((element) => {
			element[1].classList.remove(className);
		});
	},
	setAttribute: (selector, attribute, value) => {
		selectAllElements(selector).map((element) => {
			element[1].setAttribute(attribute, value);
		});
	}
};

var onLoad = (callback) => {
	document.readyState != 'complete' ? setTimeout('onLoad(' + callback + ')', 1) : callback();
};

var processPagination = (currentPage) => {
	elements.addClass('.proxy-table tbody tr:not(.hidden)', 'hidden');
	elements.removeClass('.proxy-configuration tbody tr[page="' + currentPage + '"]', 'hidden');
	elements.setAttribute('.proxy-configuration .pagination', 'current', currentPage);
	elements.setAttribute('.proxy-configuration .pagination .previous', 'previous', currentPage <= 0 ? 0 : currentPage - 1);
	elements.setAttribute('.proxy-configuration .pagination .next', 'next', currentPage + 1);
	elements.loop('.proxy-table tbody tr:not(.hidden)', (index, element) => {
		var proxyData = JSON.parse(element.getAttribute('data')),
			proxyStatusDisplay = 'Next IP Replacement ' + proxyData.next_replacement_available_formatted + ' Auto Replacements ' + (proxyData.auto_replacement_interval_value == '0' ? 'Disabled' : 'Enabled') + '';

		if (proxyData.status.toLowerCase() == 'replaced') {
			proxyStatusDisplay = 'Time to Removal ' + proxyData.replacement_removal_date_formatted;
		}

		element.querySelector('.details-container').innerHTML = '<span class="details">' + proxyData.status + ' Proxy IP ' + proxyData.ip + ' Location ' + proxyData.city + ', ' + proxyData.region + ' ' + proxyData.country_code + ' <span class="icon-container"><img src="../../resources/images/flags/' + proxyData.country_code.toLowerCase() + '.png" class="flag" alt="' + proxyData.country_code + ' flag"></span> ISP ' + proxyData.asn + ' Timezone ' + proxyData.timezone + ' ' + proxyStatusDisplay + ' HTTP + HTTPS Port ' + (proxyData.disable_http == 1 ? 'Disabled' : '80') + ' Whitelisted IPs ' + (proxyData.whitelisted_ips ? '<textarea>' + proxyData.whitelisted_ips + '</textarea>' : 'N/A') + ' Username ' + (proxyData.username ? proxyData.username : 'N/A') + ' Password ' + (proxyData.password ? proxyData.password : 'N/A') + '</span>';
		element.querySelector('.checkbox').addEventListener('click', (checkbox) => {
			var container = document.querySelector('.proxy-table table'),
				index = checkbox.target.getAttribute('index');
			container.setAttribute('current_checked', index);
			processChecked((!!event.shiftKey ? range(container.getAttribute('previous_checked'), index) : [index]), container);
			container.setAttribute('previous_checked', index);
		});
	});

	// ...
};

var processChecked = (checkboxes, container) => {
	var totalChecked = selectAllElements('.checkbox[checked]').length,
		totalResults = document.querySelector('.total-results').innerHTML;

	checkboxes.map((checkbox, checkboxIndex) => {
		var checkboxElement = document.querySelector('.checkbox[index="' + checkbox + '"]'),
			checkboxState = document.querySelector('.checkbox[index="' + (checkboxes.length === 1 ? checkbox : container.getAttribute('previous_checked')) + '"]').hasAttribute('checked');

		if (!!event.shiftKey && checkboxIndex) {
			checkboxState ? checkboxElement.setAttribute('checked', null) : checkboxElement.removeAttribute('checked');
		} else if (checkboxes.length === 1) {
			!checkboxState ? checkboxElement.setAttribute('checked', null) : checkboxElement.removeAttribute('checked');
		}
	});

	// TODO: Append link and add event listeners for processChecked([all]) and processChecked([]);
	document.querySelector('.total-checked').innerHTML = totalChecked;
	// ..
};

var range = (low, high, step) => {
	var array = [],
		high = parseInt(high, 10),
		low = parseInt(low, 10),
		plus,
		step = step || 1;

	if (low < high) {
		while (low <= high) {
			array.push(low);
			low += step;
		}
	} else {
		while (low >= high) {
			array.push(low);
			low -= step;
		}
	}

	return array;
}

var selectAllElements = (selector) => {
	return Object.entries(document.querySelectorAll(selector));
};

var unique = (value, index, self) => {
	return self.indexOf(value) === index;
};

String.prototype.trim = (charlist) => {
	return this.replace(new RegExp("[" + charlist + "]+$"), "").replace(new RegExp("^[" + charlist + "]+"), "");
};

onLoad(() => {
	elements.removeClass('.proxy-configuration', 'hidden');
	elements.addClass('.loading, .select-all', 'hidden');
	processPagination(0);

	// ...
});
