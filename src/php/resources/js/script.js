'use_strict';
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
	var checkboxAll = document.querySelector('.checkbox.all'),
		items = document.querySelector('.proxy-configuration table'),
		pagination = document.querySelector('.pagination');

	var toggle = (checkbox) => {
		var index = checkbox.target.getAttribute('index');
		items.setAttribute('current_checked', index);
		processChecked((!!event.shiftKey ? range(items.getAttribute('previous_checked'), index) : [index]), document.querySelector('.checkbox[index="' + (!!event.shiftKey ? items.getAttribute('previous_checked') : index) + '"]').hasAttribute('checked'));
		items.setAttribute('previous_checked', index);
		toggleAll();
	}
	var toggleAll = (checkbox = null) => {
		if (!checkbox) {
			return (selectAllElements('.proxy-configuration tr:not(.hidden)').length === selectAllElements('.proxy-configuration tr:not(.hidden) .checkbox[checked]').length ? checkboxAll.setAttribute('checked', 'checked') : checkboxAll.removeAttribute('checked'));
		}

		checkbox.target.hasAttribute('checked') ? checkbox.target.removeAttribute('checked') : checkbox.target.setAttribute('checked', null);
		processChecked(selectAllElements('.proxy-configuration tr' + (checkbox.target.hasAttribute('visible-only') ? ':not(.hidden)' : null) + ' .checkbox').map((checkbox) => {
			return checkbox[1].getAttribute('index');
		}), checkbox.target.hasAttribute('checked'), true);
	};

	pagination.setAttribute('current', currentPage);
	pagination.querySelector('.next').setAttribute('page', selectAllElements('.proxy-configuration tr[page="' + (currentPage + 1) + '"]').length ? currentPage + 1 : 0);
	pagination.querySelector('.previous').setAttribute('page', currentPage <= 0 ? 0 : currentPage - 1);
	elements.addClass('.proxy-configuration tr:not(.hidden)', 'hidden');
	elements.removeClass('.proxy-configuration tr[page="' + currentPage + '"]', 'hidden');

	elements.loop('.proxy-configuration tr:not(.hidden)', (index, element) => {
		var checkbox = element.querySelector('.checkbox'),
			proxyData = JSON.parse(element.getAttribute('data')),
			proxyStatusDisplay = 'Next IP Replacement ' + proxyData.next_replacement_available_formatted + ' Auto Replacements ' + (proxyData.auto_replacement_interval_value == '0' ? 'Disabled' : 'Enabled') + '';

		if (proxyData.status.toLowerCase() == 'replaced') {
			proxyStatusDisplay = 'Time to Removal ' + proxyData.replacement_removal_date_formatted;
		}

		element.querySelector('.details-container').innerHTML = '<span class="details">' + proxyData.status + ' Proxy IP ' + proxyData.ip + ' Location ' + proxyData.city + ', ' + proxyData.region + ' ' + proxyData.country_code + ' <span class="icon-container"><img src="../../resources/images/icons/flags/' + proxyData.country_code.toLowerCase() + '.png" class="flag" alt="' + proxyData.country_code + ' flag"></span> ISP ' + proxyData.asn + ' Timezone ' + proxyData.timezone + ' ' + proxyStatusDisplay + ' HTTP + HTTPS Port ' + (proxyData.disable_http == 1 ? 'Disabled' : '80') + ' Whitelisted IPs ' + (proxyData.whitelisted_ips ? '<textarea>' + proxyData.whitelisted_ips + '</textarea>' : 'N/A') + ' Username ' + (proxyData.username ? proxyData.username : 'N/A') + ' Password ' + (proxyData.password ? proxyData.password : 'N/A') + '</span>';
		checkbox.removeEventListener('click', checkbox.listener);
		checkbox.listener = toggle;
		checkbox.addEventListener('click', toggle);
	});

	checkboxAll.removeEventListener('click', checkboxAll.listener);
	checkboxAll.listener = toggleAll;
	checkboxAll.addEventListener('click', toggleAll);
	toggleAll();
	// ...
};

var processChecked = (checkboxes, checkboxState, all = false) => {
	var totalResults = document.querySelector('.total-results').innerHTML;

	checkboxes.map((checkbox, checkboxIndex) => {
		(((checkboxes.length > 1 || all) && checkboxState) || ((checkboxes.length === 1 && !all) && !checkboxState) ? document.querySelector('.checkbox[index="' + checkbox + '"]').setAttribute('checked', 'checked') : document.querySelector('.checkbox[index="' + checkbox + '"]').removeAttribute('checked'));
	});

	document.querySelector('.total-checked').innerHTML = selectAllElements('.checkbox:not(.all)[checked]').length;
	// ...
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
	elements.addClass('.loading', 'hidden');
	processPagination(parseInt(document.querySelector('.pagination').getAttribute('current')), 10);
	selectAllElements('.pagination .button').map((button) => {
		button[1].addEventListener('click', (button) => {
			if ((page = parseInt(button.target.getAttribute('page'), 10)) > 0) {
				processPagination(page);
			}
		});
	});
	selectAllElements('.button.window').map((button) => {
		button[1].addEventListener('click', (button) => {
			elements.removeClass('.window-container[window="' + button.target.getAttribute('window') + '"]', 'hidden');
		});
	});
	selectAllElements('.window .button.close').map((button) => {
		button[1].addEventListener('click', (button) => {
			elements.addClass('.window-container', 'hidden');
		});
	});
	// ...
});
