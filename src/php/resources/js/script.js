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
	selectAllElements('.pagination .button').map((element) => {
		element[1].addEventListener('click', (element) => {
			if ((page = parseInt(element.target.getAttribute('page'), 10)) > 0) {
				processPagination(page);
			}
		});
	});
	selectAllElements('.button.window').map((element) => {
		element[1].addEventListener('click', (element) => {
			elements.removeClass('.window-container[window="' + element.target.getAttribute('window') + '"]', 'hidden');
			document.querySelector('input[name="configuration_action"]').value = element.target.getAttribute('window');
		});
	});
	selectAllElements('.window .button.close').map((element) => {
		element[1].addEventListener('click', (element) => {
			elements.loop('.window input', (index, input) => {
				input.value = '';
			});
			elements.addClass('.window-container', 'hidden');
		});
	});
	selectAllElements('.window .button.submit').map((element) => {
		element[1].addEventListener('click', (element) => {
			var form = '.window-container[window="' + element.target.getAttribute('form') + '"]';
			elements.loop(form + ' input, ' + form + ' select, ' + form + ' textarea', (index, element) => {
				document.querySelector('input[name="' + element.getAttribute('name') + '"][type="hidden"]').value = element.value;
			});
			document.querySelector('.proxy-configuration form').submit(); // TODO: Chunk post data and send to dynamic JSON API instead of submitting HTML form
		});
	});
	selectAllElements('.window .checkbox, .window label.custom-checkbox-label').map((element) => {
		element[1].addEventListener('click', (element) => {
			checkbox = document.querySelector('.checkbox[name="' + element.target.getAttribute('name') + '"]');
			checkbox.hasAttribute('checked') ? checkbox.removeAttribute('checked') : checkbox.setAttribute('checked', 'checked');
			document.querySelector('input[name="' + element.target.getAttribute('name') + '"][type="hidden"]').value = +checkbox.hasAttribute('checked');
		});
	});
	// ...
});
